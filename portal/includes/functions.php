<?php
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function fmt_date(?string $date, string $format = 'd.m.Y'): string {
    if (!$date) return '—';
    return date($format, strtotime($date));
}

function fmt_datetime(?string $date): string {
    return fmt_date($date, 'd.m.Y H:i');
}

function fmt_time(?string $date): string {
    return fmt_date($date, 'H:i');
}

function flash(string $type, string $message): void {
    session_start_safe();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    session_start_safe();
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function render_flash(): void {
    $flash = get_flash();
    if (!$flash) return;
    $type = match($flash['type']) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info',
    };
    echo '<div class="alert ' . $type . ' alert-dismissible fade show">';
    echo e($flash['message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

function csrf_token(): string {
    session_start_safe();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void {
    session_start_safe();
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Geçersiz istek']));
    }
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function get(string $key, mixed $default = ''): mixed {
    return $_GET[$key] ?? $default;
}

function post(string $key, mixed $default = ''): mixed {
    return $_POST[$key] ?? $default;
}

function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Dil bayrağı emoji
function lang_flag(string $lang): string {
    return match(strtolower($lang)) {
        'de'    => '🇩🇪',
        'en'    => '🇬🇧',
        'ar'    => '🇸🇦',
        'ru'    => '🇷🇺',
        'fr'    => '🇫🇷',
        'nl'    => '🇳🇱',
        'tr'    => '🇹🇷',
        'es'    => '🇪🇸',
        'it'    => '🇮🇹',
        'pl'    => '🇵🇱',
        default => '🌐',
    };
}

// Konuşma durumu Türkçe
function conv_status_label(string $status): string {
    return match($status) {
        'ai_active'       => 'AI Aktif',
        'pending_takeover'=> 'Devralma Bekliyor',
        'assigned'        => 'Atandı',
        'with_user'       => 'Devralındı',
        'closed'          => 'Kapatıldı',
        default           => $status,
    };
}

function conv_status_badge(string $status): string {
    [$label, $class] = match($status) {
        'ai_active'        => ['AI Aktif',          'primary'],
        'pending_takeover' => ['Devralma Bekliyor',  'warning'],
        'assigned'         => ['Atandı',             'info'],
        'with_user'        => ['Devralındı',         'success'],
        'closed'           => ['Kapatıldı',          'secondary'],
        default            => [$status,              'secondary'],
    };
    return '<span class="badge bg-' . $class . '">' . $label . '</span>';
}

// Zaman önce
function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Az önce';
    if ($diff < 3600)   return floor($diff / 60) . ' dk önce';
    if ($diff < 86400)  return floor($diff / 3600) . ' saat önce';
    return fmt_datetime($datetime);
}

// Markdown render (bold, italic, satır sonu)
function render_markdown(string $text): string {
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    // **bold**
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $text);
    // *italic*
    $text = preg_replace('/\*(.+?)\*/s', '<i>$1</i>', $text);
    // Çift satır sonu → <br><br>, tek → <br>
    $text = preg_replace('/\n{2,}/', '<br><br>', $text);
    $text = str_replace("\n", '<br>', $text);
    return $text;
}
