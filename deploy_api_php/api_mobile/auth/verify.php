<?php
require_once '../config.php';

$userId = requireAuth();

jsonResponse(['valid' => true, 'user_id' => $userId]);
