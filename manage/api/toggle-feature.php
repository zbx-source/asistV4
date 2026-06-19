<?php
// manage/api/toggle-feature.php
// Admin panelden müşteri feature toggle'ı
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/features.php';

auth_check();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$client_id    = (int)($input['client_id'] ?? 0);
$feature_code = trim($input['feature_code'] ?? '');
$state        = $input['state'] ?? ''; // 'on' veya 'off'

if (!$client_id || !$feature_code || !in_array($state, ['on', 'off'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Eksik veya geçersiz parametre']);
    exit;
}

$pdo = db();

// Feature var mı?
$feat = $pdo->prepare("SELECT code, requires FROM features WHERE code = ? AND status = 'active'");
$feat->execute([$feature_code]);
$feat = $feat->fetch();
if (!$feat) {
    http_response_code(404);
    echo json_encode(['error' => 'Feature bulunamadı']);
    exit;
}

// Bağımlılık kontrolü — açarken requires feature da açık olmalı
if ($state === 'on' && $feat['requires']) {
    if (!client_has($feat['requires'], $client_id)) {
        http_response_code(409);
        echo json_encode([
            'error' => 'Önce "' . $feat['requires'] . '" özelliği açılmalı',
            'requires' => $feat['requires']
        ]);
        exit;
    }
}

// Kapatırken bağımlı feature'lar da kapanmalı
if ($state === 'off') {
    $dependents = $pdo->prepare(
        "SELECT code FROM features WHERE requires = ? AND status = 'active'"
    );
    $dependents->execute([$feature_code]);
    $dep_codes = $dependents->fetchAll(PDO::FETCH_COLUMN);

    foreach ($dep_codes as $dep_code) {
        if (client_has($dep_code, $client_id)) {
            // Bağımlı feature'ı da kapat
            $pdo->prepare(
                "INSERT INTO client_features (client_id, feature_code, state, enabled_by, enabled_at)
                 VALUES (?, ?, 'off', ?, NOW())
                 ON DUPLICATE KEY UPDATE state = 'off', enabled_by = ?, enabled_at = NOW()"
            )->execute([$client_id, $dep_code, $_SESSION['admin_id'], $_SESSION['admin_id']]);
        }
    }
}

// Plan varsayılanını kontrol et — override gerekiyor mu?
$plan_has = $pdo->prepare(
    "SELECT 1 FROM plan_features pf
     JOIN subscriptions s ON s.plan_id = pf.plan_id
     WHERE s.client_id = ? AND s.status = 'active' AND pf.feature_code = ?
     LIMIT 1"
);
$plan_has->execute([$client_id, $feature_code]);
$in_plan = (bool)$plan_has->fetchColumn();

if (($state === 'on' && $in_plan) || ($state === 'off' && !$in_plan)) {
    // Override'a gerek yok — plan varsayılanıyla aynı, varsa sil
    $pdo->prepare(
        "DELETE FROM client_features WHERE client_id = ? AND feature_code = ?"
    )->execute([$client_id, $feature_code]);
} else {
    // Override kaydet
    $pdo->prepare(
        "INSERT INTO client_features (client_id, feature_code, state, enabled_by, enabled_at)
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE state = ?, enabled_by = ?, enabled_at = NOW()"
    )->execute([
        $client_id, $feature_code, $state, $_SESSION['admin_id'],
        $state, $_SESSION['admin_id']
    ]);
}

// Güncel feature listesini döndür
$active = load_client_features($client_id);

echo json_encode([
    'ok' => true,
    'feature_code' => $feature_code,
    'state' => $state,
    'active_features' => $active
]);
