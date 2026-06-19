<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();
header('Content-Type: application/json');

$input   = json_decode(file_get_contents('php://input'), true);
$conv_id = (int)($input['conversation_id'] ?? 0);
$cid     = client_id();

if (!$conv_id) { json_response(['error' => 'Eksik parametre'], 400); }

db()->prepare(
    "UPDATE conversations SET status = 'closed', closed_at = NOW(), updated_at = NOW()
     WHERE id = ? AND client_id = ?"
)->execute([$conv_id, $cid]);

json_response(['ok' => true]);
