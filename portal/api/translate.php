<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true);
$msg_id = (int)($input['message_id'] ?? 0);
$text   = trim($input['text'] ?? '');

if (!$text) { json_response(['error' => 'Metin gerekli'], 400); }

$pdo = db();

// Daha önce çevrilmiş mi?
if ($msg_id) {
    $existing = $pdo->prepare("SELECT body_tr FROM messages WHERE id = ?");
    $existing->execute([$msg_id]);
    $row = $existing->fetch();
    if ($row && $row['body_tr']) {
        json_response(['tr' => $row['body_tr']]);
    }
}

// OpenAI ile çevir
$api_key = getenv('OPENAI_API_KEY') ?: '';
if (!$api_key) {
    // .env dosyasından oku
    $env_file = dirname(__DIR__, 2) . '/backend/.env';
    if (file_exists($env_file)) {
        foreach (file($env_file) as $line) {
            if (str_starts_with(trim($line), 'OPENAI_API_KEY=')) {
                $api_key = trim(explode('=', $line, 2)[1]);
            }
        }
    }
}

if (!$api_key) { json_response(['error' => 'API key yok'], 500); }

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'model'      => 'gpt-4o-mini',
        'messages'   => [
            ['role' => 'system', 'content' => 'Verilen metni Türkçeye çevir. Sadece çeviriyi yaz, başka hiçbir şey ekleme.'],
            ['role' => 'user',   'content' => $text],
        ],
        'max_tokens' => 500,
    ]),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
]);
$res  = curl_exec($ch);
curl_close($ch);

$data  = json_decode($res, true);
$tr    = $data['choices'][0]['message']['content'] ?? null;

if ($tr && $msg_id) {
    $pdo->prepare("UPDATE messages SET body_tr = ? WHERE id = ?")->execute([$tr, $msg_id]);
}

json_response(['tr' => $tr]);
