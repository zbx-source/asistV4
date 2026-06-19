<?php
// ============================================================
// Zbox Asist — Yardımcı Fonksiyonlar
// ============================================================

// XSS koruması
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Tarih formatla
function fmt_date(?string $date, string $format = 'd.m.Y'): string {
    if (!$date) return '—';
    return date($format, strtotime($date));
}

function fmt_datetime(?string $date): string {
    return fmt_date($date, 'd.m.Y H:i');
}

// Flash mesaj set
function flash(string $type, string $message): void {
    session_start_safe();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Flash mesaj al ve temizle
function get_flash(): ?array {
    session_start_safe();
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Flash HTML çıktısı
function render_flash(): void {
    $flash = get_flash();
    if (!$flash) return;

    $type = match($flash['type']) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };

    echo '<div class="alert ' . $type . ' alert-dismissible fade show" role="alert">';
    echo e($flash['message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}

// Sayfalama
function paginate(int $total, int $per_page, int $current_page): array {
    $total_pages = max(1, (int)ceil($total / $per_page));
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;

    return [
        'total'        => $total,
        'per_page'     => $per_page,
        'current_page' => $current_page,
        'total_pages'  => $total_pages,
        'offset'       => $offset,
    ];
}

// Paket adı Türkçe
function plan_label(string $plan): string {
    return match($plan) {
        'solo'    => 'Solo',
        'ekip'    => 'Ekip',
        'hastane' => 'Hastane',
        default   => $plan,
    };
}

// Müşteri tipi Türkçe
function client_type_label(string $type): string {
    return match($type) {
        'clinic' => 'Klinik',
        'agency' => 'Acenta',
        default  => $type,
    };
}

// Status badge HTML
function status_badge(string $status): string {
    [$label, $class] = match($status) {
        'active'    => ['Aktif',    'success'],
        'suspended' => ['Askıda',   'warning'],
        'cancelled' => ['İptal',    'danger'],
        'expired'   => ['Süresi Doldu', 'secondary'],
        default     => [$status,    'secondary'],
    };
    return '<span class="badge bg-' . $class . '">' . $label . '</span>';
}

// CSRF token oluştur
function csrf_token(): string {
    session_start_safe();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF token doğrula
function csrf_verify(): void {
    session_start_safe();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Geçersiz istek.');
    }
}

// CSRF hidden input
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Redirect
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// GET param güvenli al
function get(string $key, mixed $default = ''): mixed {
    return $_GET[$key] ?? $default;
}

// POST param güvenli al
function post(string $key, mixed $default = ''): mixed {
    return $_POST[$key] ?? $default;
}

// Sayı formatla
function fmt_number(int|float $n): string {
    return number_format($n, 0, ',', '.');
}
