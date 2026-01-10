<?php
/**
 * Bibliothèque API - Helpers pour réponses JSON
 */

/**
 * Envoyer réponse JSON success
 */
function mv3_json_success($data = null, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array(
        'success' => true,
        'data' => $data
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Envoyer réponse JSON error
 */
function mv3_json_error($message, $code = 400, $error_code = null)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array(
        'success' => false,
        'error' => array(
            'code' => $error_code ?: 'ERROR',
            'message' => $message
        )
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Vérifier si utilisateur connecté
 */
function mv3_check_auth()
{
    global $user;

    if (empty($user) || empty($user->id)) {
        mv3_json_error('Non authentifié', 401, 'UNAUTHORIZED');
    }

    return $user;
}

/**
 * Vérifier droits admin
 */
function mv3_check_admin()
{
    global $user;

    mv3_check_auth();

    if (empty($user->admin)) {
        mv3_json_error('Droits insuffisants', 403, 'FORBIDDEN');
    }

    return $user;
}

/**
 * Parser le body JSON
 */
function mv3_get_json_body()
{
    $body = file_get_contents('php://input');
    if (empty($body)) {
        return array();
    }

    $data = json_decode($body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        mv3_json_error('JSON invalide', 400, 'INVALID_JSON');
    }

    return $data;
}

/**
 * Valider champs requis
 */
function mv3_require_fields($data, $fields)
{
    $missing = array();

    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        mv3_json_error('Champs requis manquants : '.implode(', ', $missing), 400, 'MISSING_FIELDS');
    }
}

/**
 * Convertir objet Dolibarr en array pour JSON
 */
function mv3_object_to_array($obj, $fields = null)
{
    if (empty($obj)) {
        return null;
    }

    $result = array();

    if (is_array($fields)) {
        foreach ($fields as $field) {
            $result[$field] = $obj->$field ?? null;
        }
    } else {
        foreach (get_object_vars($obj) as $key => $value) {
            if ($key !== 'db' && $key !== 'error' && $key !== 'errors') {
                $result[$key] = $value;
            }
        }
    }

    return $result;
}
