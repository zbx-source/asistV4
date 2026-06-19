<?php
// ============================================================
// Zbox Asist — Capability (Feature) Sistemi
// Portal + Manage ortak helper
// ============================================================

/**
 * Müşterinin bir feature'a erişimi var mı?
 * Öncelik: client_features override → plan_features varsayılan
 *
 * Session'daki cache'i kullanır (login sırasında yüklenir).
 * Session yoksa veya force=true ise DB'den çeker.
 */
function client_has(string $feature_code, ?int $client_id = null): bool
{
    // Manage tarafı: client_id parametreyle gelir, session cache kullanılmaz
    if ($client_id !== null) {
        $features = load_client_features($client_id);
        return in_array($feature_code, $features, true);
    }

    // Portal tarafı: session cache varsa kullan
    if (isset($_SESSION['client_features']) && is_array($_SESSION['client_features'])) {
        return in_array($feature_code, $_SESSION['client_features'], true);
    }

    // Session yoksa DB'den çek ve cache'le
    $cid = (int)($_SESSION['client_id'] ?? 0);
    if (!$cid) return false;

    $features = load_client_features($cid);
    $_SESSION['client_features'] = $features;

    return in_array($feature_code, $features, true);
}

/**
 * Müşterinin tüm aktif feature code'larını DB'den yükle.
 * Mantık:
 *   1. Plan varsayılanlarını al (plan_features)
 *   2. client_features override'ları uygula (on ekle, off çıkar)
 *   3. Sonuç = aktif feature code listesi
 */
function load_client_features(int $client_id): array
{
    $pdo = db();

    // Müşterinin aktif planını bul
    $stmt = $pdo->prepare(
        "SELECT s.plan_id
         FROM subscriptions s
         WHERE s.client_id = ? AND s.status = 'active'
         ORDER BY s.start_date DESC LIMIT 1"
    );
    $stmt->execute([$client_id]);
    $plan_id = $stmt->fetchColumn();

    // Plan varsayılanları
    $plan_codes = [];
    if ($plan_id) {
        $stmt = $pdo->prepare(
            "SELECT feature_code FROM plan_features WHERE plan_id = ?"
        );
        $stmt->execute([$plan_id]);
        $plan_codes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Client override'ları
    $stmt = $pdo->prepare(
        "SELECT feature_code, state FROM client_features WHERE client_id = ?"
    );
    $stmt->execute([$client_id]);
    $overrides = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['reporting' => 'on', ...]

    // Birleştir
    $active = $plan_codes;
    foreach ($overrides as $code => $state) {
        if ($state === 'on' && !in_array($code, $active, true)) {
            $active[] = $code; // Plan vermese de aç
        } elseif ($state === 'off') {
            $active = array_values(array_diff($active, [$code])); // Plan verse de kapat
        }
    }

    return $active;
}

/**
 * Feature cache'ini yenile (admin panelden toggle sonrası çağrılır)
 */
function refresh_client_features(): void
{
    $cid = (int)($_SESSION['client_id'] ?? 0);
    if ($cid) {
        $_SESSION['client_features'] = load_client_features($cid);
    }
}

/**
 * Geriye uyumluluk — eski fonksiyonların yerine
 */
function has_assignment(): bool   { return client_has('assignment'); }
function has_agency_dir(): bool   { return client_has('agency_dir'); }
function has_report(): bool       { return client_has('reporting'); }
function has_proactive_msg(): bool { return client_has('proactive_msg'); }
function has_patient_summary(): bool { return client_has('patient_summary'); }
function has_cross_treatment(): bool { return client_has('cross_treatment'); }
