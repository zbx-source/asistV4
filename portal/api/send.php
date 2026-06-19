<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();
header('Content-Type: application/json');

$input   = json_decode(file_get_contents('php://input'), true);
$conv_id = (int)($input['conversation_id'] ?? 0);
$body    = trim($input['body'] ?? '');
$cid     = client_id();
$uid     = portal_user_id();

if (!$conv_id || !$body) { json_response(['error' => 'Eksik parametre'], 400); }

$pdo = db();

// Konuşma kontrolü
$conv = $pdo->prepare(
    "SELECT c.*, ct.whatsapp_number, ct.phone_number_id, ct.token,
            p.phone AS patient_phone
     FROM conversations c
     JOIN patients p ON p.id = c.patient_id
     JOIN client_tokens ct ON ct.client_id = c.client_id AND ct.status = 'active'
     WHERE c.id = ? AND c.client_id = ?"
);
$conv->execute([$conv_id, $cid]);
$conv = $conv->fetch();

if (!$conv) { json_response(['error' => 'Konuşma bulunamadı'], 404); }
if ($conv['status'] === 'closed')    { json_response(['error' => 'Konuşma kapalı'], 400); }
if ($conv['status'] === 'ai_active') { json_response(['error' => 'AI aktif, devralma gerekli'], 400); }

// 24 saat penceresi kontrolü
$last_patient_msg = $pdo->prepare(
    "SELECT MAX(sent_at) AS last_at FROM messages
     WHERE conversation_id = ? AND direction = 'inbound' AND sender_type = 'patient'"
);
$last_patient_msg->execute([$conv_id]);
$last_at = $last_patient_msg->fetchColumn();

if (!$last_at || (time() - strtotime($last_at)) >= (24 * 3600)) {
    json_response([
        'error'       => 'window_closed',
        'message'     => 'Hastanın son mesajından 24 saat geçti. WhatsApp kuralları gereği önce onaylı bir şablon mesajı göndermeniz gerekiyor.',
        'window_open' => false,
    ], 400);
}

// Mesajı DB'ye kaydet
$stmt = $pdo->prepare(
    "INSERT INTO messages (conversation_id, direction, sender_type, sender_id, message_type, body, sent_at)
     VALUES (?, 'outbound', 'portal_user', ?, 'text', ?, NOW())"
);
$stmt->execute([$conv_id, $uid, $body]);
$msg_id = (int)$pdo->lastInsertId();

// Konuşma updated_at güncelle
$pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$conv_id]);

// Meta'ya gönder (backend API üzerinden)
$backend_url = 'http://localhost:3001/portal/send';

$ch = curl_init($backend_url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'to'              => $conv['patient_phone'],
        'body'            => $body,
        'phone_number_id' => $conv['phone_number_id'],
    ]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
curl_exec($ch);
curl_close($ch);

json_response([
    'ok'      => true,
    'message' => [
        'id'          => $msg_id,
        'direction'   => 'outbound',
        'sender_type' => 'portal_user',
        'body'        => $body,
        'time'        => date('H:i'),
    ],
]);
