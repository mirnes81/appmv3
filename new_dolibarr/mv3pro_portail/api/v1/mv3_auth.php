<?php
/**
 * MV3 Authentication Helper
 *
 * Authentification unifiée pour toutes les API PWA
 * Support: Bearer token + X-Auth-Token + Session PHP (legacy)
 *
 * @author  MV3 Pro
 * @version 2.0
 */

// Désactiver l'affichage des erreurs PHP dans la réponse
ini_set('display_errors', 0);
error_reporting(E_ALL);

/**
 * Récupère le token Bearer depuis les headers
 *
 * @return string|null Token ou null si absent
 */
function mv3_getBearerToken() {
    $headers = null;

    // Apache
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(
            array_map('ucwords', array_keys($requestHeaders)),
            array_values($requestHeaders)
        );
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    // Bearer token
    if (!empty($headers)) {
        if (preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
            return $matches[1];
        }
    }

    // X-Auth-Token header (fallback)
    if (isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        return $_SERVER['HTTP_X_AUTH_TOKEN'];
    }

    return null;
}

/**
 * Authentifie l'utilisateur via token ou session
 *
 * @param Database $db Instance de base de données Dolibarr
 * @param bool $debug Mode debug (log dans fichier)
 * @return array|false ['user' => User, 'mobile_user' => array] ou false
 */
function mv3_authenticateOrFail($db, $debug = false) {
    global $conf, $user;

    $logFile = DOL_DATA_ROOT . '/mv3pro_portail/logs/api.log';

    // Logger si debug
    $log = function($message) use ($debug, $logFile) {
        if ($debug) {
            $dir = dirname($logFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $timestamp = date('Y-m-d H:i:s');
            @file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
        }
    };

    $log('=== MV3 Auth Start ===');

    // 1. Essayer authentification par token
    $token = mv3_getBearerToken();

    if ($token) {
        $log("Token trouvé: " . substr($token, 0, 20) . "...");

        // Vérifier le token dans la table des utilisateurs mobiles
        $sql = "SELECT u.rowid, u.email, u.firstname, u.lastname, u.dolibarr_user_id, u.active,
                       u.last_login, u.device_info
                FROM " . MAIN_DB_PREFIX . "mv3_mobile_users as u
                WHERE u.token = '" . $db->escape($token) . "'
                AND u.active = 1";

        $log("SQL: $sql");

        $resql = $db->query($sql);

        if ($resql) {
            $obj = $db->fetch_object($resql);

            if ($obj) {
                $log("Mobile user trouvé: ID=" . $obj->rowid . ", Email=" . $obj->email);

                // Vérifier que l'utilisateur Dolibarr est lié
                if (empty($obj->dolibarr_user_id)) {
                    $log("ERROR: Compte non lié à un utilisateur Dolibarr");
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'error' => 'ACCOUNT_NOT_LINKED',
                        'message' => 'Votre compte n\'est pas lié à un utilisateur Dolibarr'
                    ]);
                    exit;
                }

                // Charger l'utilisateur Dolibarr
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $dolibarrUser = new User($db);
                $result = $dolibarrUser->fetch($obj->dolibarr_user_id);

                if ($result > 0 && $dolibarrUser->statut == 1) {
                    $log("Utilisateur Dolibarr chargé: ID=" . $dolibarrUser->id . ", Login=" . $dolibarrUser->login);

                    // Mettre à jour last_seen
                    $sql_update = "UPDATE " . MAIN_DB_PREFIX . "mv3_mobile_users
                                   SET last_seen = NOW()
                                   WHERE rowid = " . (int)$obj->rowid;
                    $db->query($sql_update);

                    // Définir $user global (important pour Dolibarr)
                    $user = $dolibarrUser;

                    $log("Auth SUCCESS via token");

                    return [
                        'user' => $dolibarrUser,
                        'mobile_user' => [
                            'id' => $obj->rowid,
                            'email' => $obj->email,
                            'firstname' => $obj->firstname,
                            'lastname' => $obj->lastname,
                            'dolibarr_user_id' => $obj->dolibarr_user_id
                        ]
                    ];
                } else {
                    $log("ERROR: Utilisateur Dolibarr inactif ou non trouvé");
                }
            } else {
                $log("ERROR: Token invalide ou utilisateur inactif");
            }
        } else {
            $log("ERROR SQL: " . $db->lasterror());
        }
    } else {
        $log("Pas de token trouvé, essai session PHP...");
    }

    // 2. Fallback: Essayer la session PHP (legacy)
    if (isset($user) && is_object($user) && $user->id > 0) {
        $log("Session PHP active: User ID=" . $user->id);

        return [
            'user' => $user,
            'mobile_user' => null // Pas de mobile user en session classique
        ];
    }

    // 3. Échec d'authentification
    $log("Auth FAILED: Aucune méthode d'authentification valide");

    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'UNAUTHORIZED',
        'message' => 'Non authentifié. Token manquant ou invalide.'
    ]);
    exit;
}

/**
 * Retourne une erreur JSON standardisée
 *
 * @param int $code HTTP status code
 * @param string $error Code erreur
 * @param string $message Message détaillé
 * @param array $data Données supplémentaires
 */
function mv3_jsonError($code, $error, $message, $data = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => false,
        'error' => $error,
        'message' => $message
    ], $data));
    exit;
}

/**
 * Retourne une réponse JSON de succès standardisée
 *
 * @param array $data Données à retourner
 * @param int $code HTTP status code (200 par défaut)
 */
function mv3_jsonSuccess($data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

/**
 * Vérifie les permissions de l'utilisateur
 *
 * @param User $user Utilisateur Dolibarr
 * @param string $module Module (ex: 'agenda', 'projet')
 * @param string $permission Permission (ex: 'read', 'create', 'delete')
 * @return bool
 */
function mv3_checkPermission($user, $module, $permission) {
    if ($user->admin) {
        return true;
    }

    // Mapping des permissions
    $permissions = [
        'agenda' => [
            'read' => $user->rights->agenda->myactions->read ?? false,
            'create' => $user->rights->agenda->myactions->create ?? false,
            'delete' => $user->rights->agenda->myactions->delete ?? false,
        ],
        'projet' => [
            'read' => $user->rights->projet->lire ?? false,
            'create' => $user->rights->projet->creer ?? false,
            'delete' => $user->rights->projet->supprimer ?? false,
        ],
        'facture' => [
            'read' => $user->rights->facture->lire ?? false,
            'create' => $user->rights->facture->creer ?? false,
        ],
    ];

    return $permissions[$module][$permission] ?? false;
}

/**
 * Active le mode debug
 *
 * @return bool
 */
function mv3_isDebugMode() {
    global $conf;

    // Vérifier variable globale
    if (defined('MV3_DEBUG') && MV3_DEBUG) {
        return true;
    }

    // Vérifier conf Dolibarr
    if (!empty($conf->global->MV3_DEBUG)) {
        return true;
    }

    // Vérifier variable d'environnement
    if (getenv('MV3_DEBUG') == '1') {
        return true;
    }

    return false;
}
