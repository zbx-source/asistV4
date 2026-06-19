<?php
// PORTAL — portal/api/assign.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
auth_check();

if (!client_has('assignment') || !is_coordinator()) {
    echo json_encode(['ok'=>false,'error'=>'forbidden']); exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$conv_id = (int)($data['conversation_id'] ?? 0);
$user_id = (int)($data['user_id'] ?? 0);
$cid = client_id();

if (!$conv_id || !$user_id) { echo json_encode(['ok'=>false,'error'=>'missing']); exit; }

$pdo = db();

// Hekim bu client'a mı ait?
$chk = $pdo->prepare("SELECT id FROM portal_users WHERE id = ? AND client_id = ? AND status = 'active'");
$chk->execute([$user_id, $cid]);
if (!$chk->fetch()) { echo json_encode(['ok'=>false,'error'=>'invalid_user']); exit; }

// Konuşma bu client'a mı ait?
$chk2 = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND client_id = ?");
$chk2->execute([$conv_id, $cid]);
if (!$chk2->fetch()) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }

$pdo->prepare(
    "UPDATE conversations SET assigned_to = ?, status = 'assigned', updated_at = NOW() WHERE id = ?"
)->execute([$user_id, $conv_id]);

echo json_encode(['ok'=>true]);
