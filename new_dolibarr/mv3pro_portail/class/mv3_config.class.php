<?php
/**
 * Classe de gestion de la configuration du module MV3 PRO Portail
 */

class Mv3Config
{
    private $db;
    private static $cache = [];

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer une valeur de configuration
     */
    public function get($name, $default = null)
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $sql = "SELECT value FROM ".MAIN_DB_PREFIX."mv3_config WHERE name = '".$this->db->escape($name)."'";
        $resql = $this->db->query($sql);

        if ($resql && $this->db->num_rows($resql) > 0) {
            $obj = $this->db->fetch_object($resql);
            self::$cache[$name] = $obj->value;
            return $obj->value;
        }

        return $default;
    }

    /**
     * Définir une valeur de configuration
     */
    public function set($name, $value)
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_config (name, value, date_creation, date_modification)
                VALUES ('".$this->db->escape($name)."', '".$this->db->escape($value)."', NOW(), NOW())
                ON DUPLICATE KEY UPDATE value = '".$this->db->escape($value)."', date_modification = NOW()";

        $resql = $this->db->query($sql);

        if ($resql) {
            self::$cache[$name] = $value;
            return true;
        }

        return false;
    }

    /**
     * Récupérer toutes les configurations
     */
    public function getAll()
    {
        $configs = [];

        $sql = "SELECT name, value, description, type FROM ".MAIN_DB_PREFIX."mv3_config ORDER BY name";
        $resql = $this->db->query($sql);

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $configs[$obj->name] = [
                    'value' => $obj->value,
                    'description' => $obj->description,
                    'type' => $obj->type
                ];
            }
        }

        return $configs;
    }

    /**
     * Vérifier si le mode DEV est activé
     */
    public function isDevMode()
    {
        return $this->get('DEV_MODE_ENABLED', '0') === '1';
    }

    /**
     * Vérifier si un utilisateur a accès en mode DEV
     * En mode DEV, seuls les admins peuvent accéder
     */
    public function hasDevAccess($user)
    {
        if (!$this->isDevMode()) {
            return true; // Mode DEV OFF = accès pour tous
        }

        // Mode DEV ON = accès admin uniquement
        return !empty($user->admin);
    }
}
