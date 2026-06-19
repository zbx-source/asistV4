<?php
// PORTAL — portal/api/devral.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
auth_check();

$data = json_decode(file_get_contents('php://input'), true);
$conv_id = (int)($data['conversation_id'] ?? 0);
$cid = client_id();
$uid = portal_user_id();

if (!$conv_id) { echo json_encode(['ok'=>false,'error'=>'missing']); exit; }

$pdo = db();

// Konuşma bu client'a mı ait?
$chk = $pdo->prepare("SELECT id, status FROM conversations WHERE id = ? AND client_id = ?");
$chk->execute([$conv_id, $cid]);
$conv = $chk->fetch();

if (!$conv) { echo json_encode(['ok'=>false,'error'=>'not_found']); exit; }

$pdo->prepare(
    "UPDATE conversations SET status = 'with_user', assigned_to = ?, updated_at = NOW() WHERE id = ?"
)->execute([$uid, $conv_id]);

echo json_encode(['ok'=>true]);
