<?php
// ============================================================
// Zbox Asist Portal — Auth (capability migration sonrası)
// ============================================================

define('SESSION_LIFETIME', 28800);

require_once __DIR__ . '/features.php';

function session_start_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function auth_check(): void {
    session_start_safe();
    if (empty($_SESSION['portal_user_id'])) {
        header('Location: /index.php');
        exit;
    }
    if (!empty($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        session_destroy();
        header('Location: /index.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
    check_client_status();
}

function auth_login(string $email, string $password): bool {
    $pdo = db();

    // Plan flag'leri kaldırıldı — sadece temel bilgiler
    $stmt = $pdo->prepare(
        "SELECT pu.id, pu.name, pu.email, pu.password_hash, pu.role,
                pu.client_id, pu.status,
                c.name AS client_name, c.status AS client_status,
                pl.name AS plan_name
         FROM portal_users pu
         JOIN clients c ON c.id = pu.client_id
         LEFT JOIN subscriptions s ON s.client_id = c.id AND s.status = 'active'
         LEFT JOIN plans pl ON pl.id = s.plan_id
         WHERE pu.email = ? AND pu.status = 'active' AND c.status = 'active'
         LIMIT 1"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    // Tek kullanıcı kontrolü — artık feature sistemi üzerinden
    // Önce features yükle (session henüz yok, direkt DB'den)
    $features = load_client_features($user['client_id']);
    $is_multi_user = in_array('multi_user', $features, true);

    if (!$is_multi_user) {
        $pdo->prepare(
            "DELETE FROM portal_sessions WHERE client_id = ? AND expires_at < NOW()"
        )->execute([$user['client_id']]);

        $active = $pdo->prepare(
            "SELECT COUNT(*) FROM portal_sessions WHERE client_id = ? AND expires_at > NOW()"
        );
        $active->execute([$user['client_id']]);
        if ($active->fetchColumn() > 0) {
            return false;
        }
    }

    session_start_safe();
    session_regenerate_id(true);

    $_SESSION['portal_user_id']   = $user['id'];
    $_SESSION['portal_user_name'] = $user['name'];
    $_SESSION['portal_user_role'] = $user['role'];
    $_SESSION['client_id']        = $user['client_id'];
    $_SESSION['client_name']      = $user['client_name'];
    $_SESSION['plan_name']        = $user['plan_name'];
    $_SESSION['client_features']  = $features; // Capability cache
    $_SESSION['last_activity']    = time();

    $token = bin2hex(random_bytes(32));
    $_SESSION['session_token'] = $token;

    $pdo->prepare(
        "INSERT INTO portal_sessions (portal_user_id, client_id, session_token, ip_address, expires_at)
         VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 8 HOUR))"
    )->execute([
        $user['id'], $user['client_id'], $token,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    $pdo->prepare("UPDATE portal_users SET last_login_at = NOW() WHERE id = ?")
        ->execute([$user['id']]);

    return true;
}

function auth_logout(): void {
    session_start_safe();
    if (!empty($_SESSION['session_token'])) {
        db()->prepare("DELETE FROM portal_sessions WHERE session_token = ?")
             ->execute([$_SESSION['session_token']]);
    }
    session_destroy();
    header('Location: /index.php');
    exit;
}

function portal_user_id(): int      { return (int)($_SESSION['portal_user_id'] ?? 0); }
function portal_user_name(): string { return $_SESSION['portal_user_name'] ?? ''; }
function portal_user_role(): string { return $_SESSION['portal_user_role'] ?? ''; }
function client_id(): int           { return (int)($_SESSION['client_id'] ?? 0); }
function client_name(): string      { return $_SESSION['client_name'] ?? ''; }
function plan_name(): string        { return $_SESSION['plan_name'] ?? ''; }
function is_coordinator(): bool     { return portal_user_role() === 'coordinator'; }

function check_client_status(): void {
    $cid = client_id();
    if (!$cid) return;
    $pdo = db();
    $stmt = $pdo->prepare("SELECT status FROM clients WHERE id = ?");
    $stmt->execute([$cid]);
    $client = $stmt->fetch();
    if (!$client || $client['status'] !== 'active') {
        if (!empty($_SESSION['session_token'])) {
            $pdo->prepare("DELETE FROM portal_sessions WHERE session_token = ?")
                ->execute([$_SESSION['session_token']]);
        }
        session_destroy();
        header('Location: /index.php?suspended=1');
        exit;
    }
}
