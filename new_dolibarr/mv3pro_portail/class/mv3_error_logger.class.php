<?php
/**
 * Classe de journalisation des erreurs du module MV3 PRO Portail
 */

class Mv3ErrorLogger
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Générer un debug_id unique
     */
    private function generateDebugId()
    {
        return 'MV3-'.date('Ymd').'-'.strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    }

    /**
     * Logger une erreur
     */
    public function logError($data)
    {
        global $user;

        $debug_id = $data['debug_id'] ?? $this->generateDebugId();
        $error_type = $data['error_type'] ?? 'UNKNOWN';
        $error_source = $data['error_source'] ?? $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';
        $error_message = $data['error_message'] ?? 'No message';
        $error_details = isset($data['error_details']) ? json_encode($data['error_details']) : null;
        $http_status = $data['http_status'] ?? 0;
        $endpoint = $data['endpoint'] ?? $_SERVER['REQUEST_URI'] ?? null;
        $method = $data['method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $user_id = $user->id ?? null;
        $user_login = $user->login ?? 'anonymous';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $request_data = isset($data['request_data']) ? json_encode($data['request_data']) : null;
        $response_data = isset($data['response_data']) ? json_encode($data['response_data']) : null;
        $sql_error = $data['sql_error'] ?? $this->db->lasterror();
        $stack_trace = isset($data['stack_trace']) ? json_encode($data['stack_trace']) : null;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."mv3_error_log (
            debug_id,
            error_type,
            error_source,
            error_message,
            error_details,
            http_status,
            endpoint,
            method,
            user_id,
            user_login,
            ip_address,
            user_agent,
            request_data,
            response_data,
            sql_error,
            stack_trace,
            date_error,
            entity
        ) VALUES (
            '".$this->db->escape($debug_id)."',
            '".$this->db->escape($error_type)."',
            '".$this->db->escape($error_source)."',
            '".$this->db->escape($error_message)."',
            '".$this->db->escape($error_details)."',
            ".(int)$http_status.",
            '".$this->db->escape($endpoint)."',
            '".$this->db->escape($method)."',
            ".($user_id ? (int)$user_id : 'NULL').",
            '".$this->db->escape($user_login)."',
            '".$this->db->escape($ip_address)."',
            '".$this->db->escape($user_agent)."',
            '".$this->db->escape($request_data)."',
            '".$this->db->escape($response_data)."',
            '".$this->db->escape($sql_error)."',
            '".$this->db->escape($stack_trace)."',
            NOW(),
            ".(!empty($conf->entity) ? (int)$conf->entity : 1)."
        )";

        $resql = $this->db->query($sql);

        if ($resql) {
            return $debug_id;
        }

        return false;
    }

    /**
     * Récupérer les erreurs récentes
     */
    public function getRecentErrors($limit = 100)
    {
        $errors = [];

        $sql = "SELECT
            rowid,
            debug_id,
            error_type,
            error_source,
            error_message,
            error_details,
            http_status,
            endpoint,
            method,
            user_id,
            user_login,
            ip_address,
            date_error
        FROM ".MAIN_DB_PREFIX."mv3_error_log
        ORDER BY date_error DESC
        LIMIT ".(int)$limit;

        $resql = $this->db->query($sql);

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $errors[] = [
                    'id' => $obj->rowid,
                    'debug_id' => $obj->debug_id,
                    'error_type' => $obj->error_type,
                    'error_source' => $obj->error_source,
                    'error_message' => $obj->error_message,
                    'error_details' => $obj->error_details ? json_decode($obj->error_details, true) : null,
                    'http_status' => (int)$obj->http_status,
                    'endpoint' => $obj->endpoint,
                    'method' => $obj->method,
                    'user_id' => $obj->user_id,
                    'user_login' => $obj->user_login,
                    'ip_address' => $obj->ip_address,
                    'date_error' => $obj->date_error
                ];
            }
        }

        return $errors;
    }

    /**
     * Récupérer une erreur par debug_id
     */
    public function getErrorByDebugId($debug_id)
    {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."mv3_error_log
                WHERE debug_id = '".$this->db->escape($debug_id)."'
                LIMIT 1";

        $resql = $this->db->query($sql);

        if ($resql && $this->db->num_rows($resql) > 0) {
            $obj = $this->db->fetch_object($resql);

            return [
                'id' => $obj->rowid,
                'debug_id' => $obj->debug_id,
                'error_type' => $obj->error_type,
                'error_source' => $obj->error_source,
                'error_message' => $obj->error_message,
                'error_details' => $obj->error_details ? json_decode($obj->error_details, true) : null,
                'http_status' => (int)$obj->http_status,
                'endpoint' => $obj->endpoint,
                'method' => $obj->method,
                'user_id' => $obj->user_id,
                'user_login' => $obj->user_login,
                'ip_address' => $obj->ip_address,
                'user_agent' => $obj->user_agent,
                'request_data' => $obj->request_data ? json_decode($obj->request_data, true) : null,
                'response_data' => $obj->response_data ? json_decode($obj->response_data, true) : null,
                'sql_error' => $obj->sql_error,
                'stack_trace' => $obj->stack_trace ? json_decode($obj->stack_trace, true) : null,
                'date_error' => $obj->date_error
            ];
        }

        return null;
    }

    /**
     * Nettoyer les vieux logs selon la politique de rétention
     */
    public function cleanOldLogs($days = 30)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."mv3_error_log
                WHERE date_error < DATE_SUB(NOW(), INTERVAL ".(int)$days." DAY)";

        return $this->db->query($sql);
    }

    /**
     * Obtenir des statistiques sur les erreurs
     */
    public function getStats($days = 7)
    {
        $stats = [
            'total' => 0,
            'by_type' => [],
            'by_endpoint' => [],
            'by_status' => []
        ];

        // Total
        $sql = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX."mv3_error_log
                WHERE date_error >= DATE_SUB(NOW(), INTERVAL ".(int)$days." DAY)";
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $stats['total'] = (int)$obj->total;
        }

        // Par type
        $sql = "SELECT error_type, COUNT(*) as count
                FROM ".MAIN_DB_PREFIX."mv3_error_log
                WHERE date_error >= DATE_SUB(NOW(), INTERVAL ".(int)$days." DAY)
                GROUP BY error_type
                ORDER BY count DESC";
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $stats['by_type'][$obj->error_type] = (int)$obj->count;
            }
        }

        // Par endpoint
        $sql = "SELECT endpoint, COUNT(*) as count
                FROM ".MAIN_DB_PREFIX."mv3_error_log
                WHERE date_error >= DATE_SUB(NOW(), INTERVAL ".(int)$days." DAY)
                GROUP BY endpoint
                ORDER BY count DESC
                LIMIT 10";
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $stats['by_endpoint'][$obj->endpoint] = (int)$obj->count;
            }
        }

        // Par status HTTP
        $sql = "SELECT http_status, COUNT(*) as count
                FROM ".MAIN_DB_PREFIX."mv3_error_log
                WHERE date_error >= DATE_SUB(NOW(), INTERVAL ".(int)$days." DAY)
                GROUP BY http_status
                ORDER BY count DESC";
        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $stats['by_status'][$obj->http_status] = (int)$obj->count;
            }
        }

        return $stats;
    }
}
