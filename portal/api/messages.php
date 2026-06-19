<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();
header('Content-Type: application/json');

$conv_id  = (int)get('conversation_id');
$after_id = (int)get('after', 0);
$cid      = client_id();

if (!$conv_id) { echo json_encode(['messages' => []]); exit; }

// Konuşma bu client'a mı ait?
$check = db()->prepare("SELECT id FROM conversations WHERE id = ? AND client_id = ?");
$check->execute([$conv_id, $cid]);
if (!$check->fetch()) { echo json_encode(['messages' => []]); exit; }

$msgs = db()->prepare(
    "SELECT m.id, m.direction, m.sender_type, m.body, m.media_url, m.message_type,
            m.sent_at, pu.name AS sender_name
     FROM messages m
     LEFT JOIN portal_users pu ON pu.id = m.sender_id
     WHERE m.conversation_id = ? AND m.id > ?
     ORDER BY m.sent_at ASC"
);
$msgs->execute([$conv_id, $after_id]);
$rows = $msgs->fetchAll();

$result = array_map(function($m) {
    return [
        'id'          => $m['id'],
        'direction'   => $m['direction'],
        'sender_type' => $m['sender_type'],
        'sender_name' => $m['sender_name'],
        'body'        => $m['body'],
        'media_url'   => $m['media_url'],
        'message_type'=> $m['message_type'],
        'time'        => date('H:i', strtotime($m['sent_at'])),
    ];
}, $rows);

echo json_encode(['messages' => $result]);
