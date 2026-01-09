<?php
/**
 * Système de log pour debug
 * Active avec: touch /tmp/mv3pro_debug.flag
 * Désactive avec: rm /tmp/mv3pro_debug.flag
 */

class DebugLogger {
    private static $enabled = null;
    private static $log_file = '/tmp/mv3pro_auth_debug.log';
    private static $flag_file = '/tmp/mv3pro_debug.flag';

    public static function isEnabled() {
        if (self::$enabled === null) {
            self::$enabled = file_exists(self::$flag_file);
        }
        return self::$enabled;
    }

    public static function log($message, $data = null) {
        if (!self::isEnabled()) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message";

        if ($data !== null) {
            $log_entry .= "\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $log_entry .= "\n" . str_repeat('-', 80) . "\n";

        file_put_contents(self::$log_file, $log_entry, FILE_APPEND);
    }

    public static function getLogFile() {
        return self::$log_file;
    }

    public static function clearLog() {
        if (file_exists(self::$log_file)) {
            unlink(self::$log_file);
        }
    }

    public static function enable() {
        touch(self::$flag_file);
        self::$enabled = true;
    }

    public static function disable() {
        if (file_exists(self::$flag_file)) {
            unlink(self::$flag_file);
        }
        self::$enabled = false;
    }
}
