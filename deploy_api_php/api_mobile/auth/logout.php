<?php
require_once '../config.php';

requireAuth();

jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
