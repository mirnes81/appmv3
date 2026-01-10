<?php
/**
 * Database Helpers
 * Fonctions utilitaires pour les requêtes de base de données
 */

function executeQuery($db, $sql, $returnType = 'array')
{
    $resql = $db->query($sql);

    if (!$resql) {
        error_log("SQL Error: " . $db->lasterror() . " | Query: " . $sql);
        return false;
    }

    if ($returnType === 'single') {
        return $db->fetch_object($resql);
    }

    if ($returnType === 'count') {
        return $db->num_rows($resql);
    }

    $results = [];
    while ($obj = $db->fetch_object($resql)) {
        $results[] = $obj;
    }

    return $results;
}

function fetchSingle($db, $table, $conditions = [], $fields = '*')
{
    global $conf;

    $sql = "SELECT " . $fields . " FROM " . MAIN_DB_PREFIX . $table;
    $sql .= " WHERE entity IN (0, " . $conf->entity . ")";

    foreach ($conditions as $field => $value) {
        if (is_int($value)) {
            $sql .= " AND " . $field . " = " . (int)$value;
        } else {
            $sql .= " AND " . $field . " = '" . $db->escape($value) . "'";
        }
    }

    $sql .= " LIMIT 1";

    $resql = $db->query($sql);

    if (!$resql || $db->num_rows($resql) === 0) {
        return null;
    }

    return $db->fetch_object($resql);
}

function insertRecord($db, $table, $data)
{
    global $conf;

    if (!isset($data['entity'])) {
        $data['entity'] = $conf->entity;
    }

    $fields = [];
    $values = [];

    foreach ($data as $field => $value) {
        $fields[] = $field;

        if ($value === null) {
            $values[] = 'NULL';
        } elseif (is_int($value) || is_float($value)) {
            $values[] = $value;
        } elseif ($value === 'NOW()') {
            $values[] = 'NOW()';
        } else {
            $values[] = "'" . $db->escape($value) . "'";
        }
    }

    $sql = "INSERT INTO " . MAIN_DB_PREFIX . $table;
    $sql .= " (" . implode(', ', $fields) . ")";
    $sql .= " VALUES (" . implode(', ', $values) . ")";

    if ($db->query($sql)) {
        return $db->last_insert_id(MAIN_DB_PREFIX . $table);
    }

    error_log("Insert Error: " . $db->lasterror() . " | Query: " . $sql);
    return false;
}

function updateRecord($db, $table, $data, $conditions)
{
    $sets = [];

    foreach ($data as $field => $value) {
        if ($value === null) {
            $sets[] = $field . " = NULL";
        } elseif (is_int($value) || is_float($value)) {
            $sets[] = $field . " = " . $value;
        } elseif ($value === 'NOW()') {
            $sets[] = $field . " = NOW()";
        } else {
            $sets[] = $field . " = '" . $db->escape($value) . "'";
        }
    }

    $where = [];
    foreach ($conditions as $field => $value) {
        if (is_int($value)) {
            $where[] = $field . " = " . (int)$value;
        } else {
            $where[] = $field . " = '" . $db->escape($value) . "'";
        }
    }

    $sql = "UPDATE " . MAIN_DB_PREFIX . $table;
    $sql .= " SET " . implode(', ', $sets);
    $sql .= " WHERE " . implode(' AND ', $where);

    if ($db->query($sql)) {
        return true;
    }

    error_log("Update Error: " . $db->lasterror() . " | Query: " . $sql);
    return false;
}

function deleteRecord($db, $table, $conditions)
{
    $where = [];
    foreach ($conditions as $field => $value) {
        if (is_int($value)) {
            $where[] = $field . " = " . (int)$value;
        } else {
            $where[] = $field . " = '" . $db->escape($value) . "'";
        }
    }

    $sql = "DELETE FROM " . MAIN_DB_PREFIX . $table;
    $sql .= " WHERE " . implode(' AND ', $where);

    if ($db->query($sql)) {
        return true;
    }

    error_log("Delete Error: " . $db->lasterror() . " | Query: " . $sql);
    return false;
}

function getTimeAgo($datetime)
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return "À l'instant";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "Il y a " . $mins . " min";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Il y a " . $hours . " h";
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Il y a " . $days . " jour" . ($days > 1 ? 's' : '');
    } else {
        return date('d.m.Y', $timestamp);
    }
}

function formatAddress($address, $zip, $town)
{
    $address_parts = [];

    if (!empty($address)) {
        $address_parts[] = $address;
    }

    if (!empty($zip) || !empty($town)) {
        $location = trim($zip . ' ' . $town);
        if (!empty($location)) {
            $address_parts[] = $location;
        }
    }

    return !empty($address_parts) ? implode(', ', $address_parts) : '';
}

function formatClientData($obj)
{
    return [
        'rowid' => $obj->rowid,
        'nom' => $obj->nom,
        'address' => formatAddress($obj->address ?? '', $obj->zip ?? '', $obj->town ?? ''),
        'phone' => $obj->phone ?? '',
        'email' => $obj->email ?? ''
    ];
}
