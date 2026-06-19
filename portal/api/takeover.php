<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();
header('Content-Type: application/json');

$input   = json_decode(file_get_contents('php://input'), true);
$conv_id = (int)($input['conversation_id'] ?? 0);
$cid     = client_id();
$uid     = portal_user_id();

if (!$conv_id) { json_response(['error' => 'Eksik parametre'], 400); }

$pdo = db();

$conv = $pdo->prepare("SELECT * FROM conversations WHERE id = ? AND client_id = ?");
$conv->execute([$conv_id, $cid]);
$conv = $conv->fetch();

if (!$conv) { json_response(['error' => 'Bulunamadı'], 404); }

// Son hasta mesajının zamanını bul
$last_patient_msg = $pdo->prepare(
    "SELECT MAX(sent_at) AS last_at FROM messages
     WHERE conversation_id = ? AND direction = 'inbound' AND sender_type = 'patient'"
);
$last_patient_msg->execute([$conv_id]);
$last_at = $last_patient_msg->fetchColumn();

// 24 saat penceresi kontrolü
$window_open = false;
if ($last_at) {
    $diff = time() - strtotime($last_at);
    $window_open = $diff < (24 * 3600); // 24 saat = 86400 saniye
}

// Devral
$pdo->prepare(
    "UPDATE conversations SET status = 'with_user', assigned_to = ?, taken_over_at = NOW(), updated_at = NOW()
     WHERE id = ?"
)->execute([$uid, $conv_id]);

json_response([
    'ok'          => true,
    'window_open' => $window_open,
    'last_msg_at' => $last_at,
]);
