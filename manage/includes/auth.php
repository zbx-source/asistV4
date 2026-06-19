<?php
// ============================================================
// Zbox Asist — Auth & Session
// ============================================================

define('SESSION_LIFETIME', 3600); // 1 saat

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

    if (empty($_SESSION['admin_id'])) {
        header('Location: /index.php');
        exit;
    }

    // Session timeout kontrolü
    if (!empty($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        session_destroy();
        header('Location: /index.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function auth_login(string $email, string $password): bool {
    $pdo = db();

    $stmt = $pdo->prepare(
        "SELECT id, name, email, password_hash, role
         FROM admin_users
         WHERE email = ? AND status = 'active'
         LIMIT 1"
    );
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }

    session_start_safe();
    session_regenerate_id(true);

    $_SESSION['admin_id']       = $admin['id'];
    $_SESSION['admin_name']     = $admin['name'];
    $_SESSION['admin_email']    = $admin['email'];
    $_SESSION['admin_role']     = $admin['role'];
    $_SESSION['last_activity']  = time();

    return true;
}

function auth_logout(): void {
    session_start_safe();
    session_destroy();
    header('Location: /index.php');
    exit;
}

function admin_name(): string {
    return $_SESSION['admin_name'] ?? '';
}

function admin_role(): string {
    return $_SESSION['admin_role'] ?? '';
}
