<?php
// PORTAL — portal/includes/layout_header.php
$page_title = $page_title ?? 'Portal';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> — Zbox Asist</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root {
      --zb-primary:        #1e63e9;
      --zb-primary-dark:   #0b2a5b;
      --zb-primary-soft:   #edf4ff;
      --zb-primary-muted:  #b9cef8;
      --zb-accent:         #f07800;
      --zb-accent-soft:    #fff1dd;
      --zb-accent-dark:    #d86600;

      --zb-sidebar-bg:        #061a3d;
      --zb-sidebar-bg-2:      #071d43;
      --zb-sidebar-active:    rgba(255,255,255,.10);
      --zb-sidebar-border:    rgba(255,255,255,.10);
      --zb-sidebar-text:      #d6e3f7;
      --zb-sidebar-text-muted:#8fa2bd;
      --zb-sidebar-section:   #c4d2e8;

      --zb-bg:        #f6f9fd;
      --zb-surface:   #ffffff;
      --zb-border:    #e4ebf5;
      --zb-border-hover: #c8d8ea;

      --zb-text:           #071d43;
      --zb-text-secondary: #42546f;
      --zb-text-muted:     #8391a8;

      --zb-success:      #1e63e9;
      --zb-success-soft: #edf4ff;
      --zb-warning:      #f07800;
      --zb-warning-soft: #fff1dd;
      --zb-danger:       #dc2626;
      --zb-danger-soft:  #fff0f0;
      --zb-info:         #1e63e9;
      --zb-info-soft:    #edf4ff;

      --zb-sidebar: 250px;
      --zb-topbar:  66px;
      --zb-radius:    14px;
      --zb-radius-sm: 10px;
      --zb-shadow:    0 1px 3px rgba(15,39,77,.05), 0 1px 2px rgba(15,39,77,.03);
      --zb-shadow-md: 0 14px 32px rgba(15,39,77,.11);
    }

    * { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; padding: 0; }
    body {
      background: var(--zb-bg);
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      font-size: 13.5px;
      color: var(--zb-text);
      display: flex;
      flex-direction: column;
      -webkit-font-smoothing: antialiased;
    }

    .topbar {
      background: #fff;
      border-bottom: 1px solid #e6edf7;
      padding: 0 22px 0 0;
      height: var(--zb-topbar);
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 200;
      flex-shrink: 0;
      box-shadow: 0 1px 0 rgba(15,39,77,.03);
    }
    .topbar-logo {
      width: var(--zb-sidebar);
      height: 100%;
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 0 22px;
      font-weight: 800;
      font-size: 1.22rem;
      color: #fff;
      text-decoration: none;
      letter-spacing: -0.55px;
      background: var(--zb-sidebar-bg);
      border-right: 1px solid var(--zb-sidebar-border);
      flex-shrink: 0;
    }
    .topbar-logo-dot,
    .topbar-logo-mark {
      width: 32px;
      height: 32px;
      border-radius: 12px;
      border: 2px solid rgba(255,255,255,.86);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 15px;
      font-weight: 800;
      color: #fff;
      flex-shrink: 0;
      background: transparent;
    }
    .topbar-right { display:flex; align-items:center; gap:14px; font-size:13px; color:var(--zb-text); }
    .plan-badge {
      font-size: 12px;
      padding: 8px 12px;
      border-radius: 10px;
      background: #fff;
      color: var(--zb-text);
      border: 1px solid #e1e8f3;
      font-weight: 800;
      display:inline-flex;
      align-items:center;
      gap:6px;
      box-shadow: 0 6px 16px rgba(15,39,77,.05);
    }
    .topbar-account { font-weight:700; color:#071d43; }
    .topbar-user-dot {
      width:34px;height:34px;border-radius:50%;background:#071d43;color:#fff;
      display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;
    }

    .app-body { display: flex; flex: 1; overflow: hidden; height: calc(100vh - var(--zb-topbar)); }
    .sidebar {
      width: var(--zb-sidebar);
      background: linear-gradient(180deg, var(--zb-sidebar-bg) 0%, #05152f 100%);
      border-right: 1px solid var(--zb-sidebar-border);
      display: flex;
      flex-direction: column;
      overflow-y: auto;
      flex-shrink: 0;
      padding-top: 18px;
    }
    .sidebar-section {
      font-size: 11px;
      font-weight: 800;
      color: rgba(255,255,255,.76);
      letter-spacing: 0.08em;
      padding: 18px 22px 8px;
      text-transform: uppercase;
    }
    .sidebar-section:first-child { padding-top: 0; }
    .sidebar .nav-link {
      display: flex;
      align-items: center;
      gap: 13px;
      padding: 13px 16px;
      margin: 3px 14px;
      color: var(--zb-sidebar-text);
      font-size: 14px;
      font-weight: 650;
      border-radius: 12px;
      transition: all 0.15s ease;
      text-decoration: none;
    }
    .sidebar .nav-link:hover { background: rgba(255,255,255,.08); color:#fff; }
    .sidebar .nav-link.active { background: rgba(255,255,255,.13); color: #fff; box-shadow: inset 0 0 0 1px rgba(255,255,255,.05); }
    .sidebar .nav-link i { font-size: 20px; width: 22px; text-align: center; opacity: .94; }
    .notif-badge {
      background: var(--zb-accent);
      color: #fff;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 800;
      padding: 2px 7px;
      margin-left: auto;
    }
    .sidebar-footer {
      margin: auto 14px 18px;
      padding: 14px;
      border: 1px solid rgba(255,255,255,.10);
      border-radius: 14px;
      background: rgba(255,255,255,.055);
      font-size: 12px;
      display: grid;
      grid-template-columns: 42px 1fr 14px;
      gap: 11px;
      align-items: center;
    }
    .sidebar-user-avatar {
      width: 42px; height:42px; border-radius:50%; background:#3a78d8; color:#fff;
      display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px;
    }
    .sidebar-footer .fw-500 { color: #fff; font-weight:800; }
    .sidebar-footer .text-muted { color: #c7d6ec !important; font-size:11.5px; }
    .online-dot { width:8px; height:8px; border-radius:50%; background:#2367d8; display:inline-block; margin-right:5px; }

    .main-content { flex: 1; overflow-y: auto; padding: 24px 28px; }
    .card { background: var(--zb-surface); border: 1px solid var(--zb-border); border-radius: var(--zb-radius); box-shadow: var(--zb-shadow); }
    .card-header { background: transparent; border-bottom: 1px solid var(--zb-border); padding: 14px 20px; font-weight: 700; font-size: 13.5px; color: var(--zb-text); }
    .card-body { padding: 20px; }
    .card-footer { background: transparent; border-top: 1px solid var(--zb-border); }

    .table { --bs-table-bg: transparent; margin-bottom: 0; }
    .table th { font-size: 11px; font-weight: 800; color: var(--zb-text-muted); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--zb-border); padding: 10px 12px; white-space: nowrap; }
    .table td { vertical-align: middle; padding: 12px; border-bottom: 1px solid var(--zb-border); color: var(--zb-text); }
    .table tbody tr:last-child td { border-bottom: none; }
    .table-hover tbody tr:hover { background: var(--zb-bg); }

    .btn { border-radius: var(--zb-radius-sm); font-weight: 750; font-size: 13px; padding: 7px 16px; transition: all 0.15s ease; }
    .btn-sm { padding: 5px 12px; font-size: 12.5px; }
    .btn-primary { background: #071d43; border-color: #071d43; color: #fff; box-shadow: 0 8px 18px rgba(7,29,67,.18); }
    .btn-primary:hover, .btn-primary:focus { background:#0b2a5b; border-color:#0b2a5b; color:#fff; }
    .btn-outline-primary { color: #1e63e9; border-color: #b9cef8; }
    .btn-outline-primary:hover { background: #edf4ff; border-color: #1e63e9; color: #1e63e9; }
    .btn-outline-secondary { color: #17335f; border-color: #cad8ea; background:#fff; }
    .btn-outline-secondary:hover { background: #f3f7fd; border-color: #b7c8df; color: #071d43; }
    .btn-success, .btn-info { background:#1e63e9; border-color:#1e63e9; color:#fff; }
    .btn-warning { background: var(--zb-warning); border-color: var(--zb-warning); color: #fff; }

    .badge { font-weight: 750; font-size: 11px; padding: 5px 10px; border-radius: 999px; letter-spacing: 0.01em; }
    .badge.bg-success   { background: #edf4ff !important; color: #1e63e9; }
    .badge.bg-warning   { background: var(--zb-warning-soft) !important; color: #d86600; }
    .badge.bg-danger    { background: var(--zb-danger-soft)  !important; color: #dc2626; }
    .badge.bg-info      { background: var(--zb-info-soft)    !important; color: #1e63e9; }
    .badge.bg-secondary { background: #f1f4f8 !important; color: var(--zb-text-muted); }
    .badge.bg-primary   { background: var(--zb-primary-soft)  !important; color: var(--zb-primary); }

    .alert { border-radius: var(--zb-radius); border: none; font-size: 13px; }
    .alert-warning { background: var(--zb-warning-soft); color: #92400e; }
    .alert-danger  { background: var(--zb-danger-soft);  color: #991b1b; }
    .alert-info, .alert-success { background: var(--zb-info-soft); color: #1e63e9; }
    .progress { background: var(--zb-border); border-radius: 20px; overflow: hidden; }
    .progress-bar, .progress-bar.bg-success, .progress-bar.bg-info { background: #1e63e9 !important; border-radius: 20px; }
    .progress-bar.bg-warning { background: var(--zb-warning) !important; }
    .progress-bar.bg-danger  { background: var(--zb-danger)  !important; }

    .form-control, .form-select { border-radius: var(--zb-radius-sm); border-color: var(--zb-border-hover); font-size: 13.5px; padding: 8px 12px; transition: border-color 0.15s, box-shadow 0.15s; }
    .form-control:focus, .form-select:focus { border-color: var(--zb-primary); box-shadow: 0 0 0 3px rgba(30,99,233,.12); }
    .form-label { font-size: 12.5px; font-weight: 650; color: var(--zb-text-secondary); margin-bottom: 4px; }
    .form-text { font-size: 12px; color: var(--zb-text-muted); }
    .form-check-input:checked { background-color: #1e63e9; border-color: #1e63e9; }

    .pagination .page-link { border-radius: var(--zb-radius-sm); border: none; color: var(--zb-text-secondary); font-size: 12.5px; margin: 0 2px; padding: 5px 10px; }
    .pagination .page-item.active .page-link { background: #071d43; color: #fff; }
    .pagination .page-link:hover { background: var(--zb-bg); color: var(--zb-primary); }
    .list-group-item { border-color: var(--zb-border); transition: background 0.1s; }
    .list-group-item:hover { background: var(--zb-bg); }
    .list-group-item-action { color: var(--zb-text); }
    .list-group-item-action:hover { color: var(--zb-text); }

    .fw-500 { font-weight: 500; }
    .fw-600 { font-weight: 600; }
    .text-muted { color: var(--zb-text-muted) !important; }
    .small, .text-muted.small { font-size: 12.5px; }
    h6, .h6 { font-weight: 800; font-size: 15px; color: var(--zb-text); letter-spacing: -0.2px; }
    .card .fs-3 { font-weight: 800; color: var(--zb-text); font-size: 1.75rem !important; }
    .dropdown-menu { border-radius: var(--zb-radius); box-shadow: var(--zb-shadow-md); border: 1px solid var(--zb-border); padding: 6px; }
    .dropdown-item { border-radius: var(--zb-radius-sm); font-size: 13px; padding: 8px 12px; }
    .dropdown-item:hover { background: var(--zb-bg); }
    .form-switch .form-check-input { width: 36px; height: 20px; cursor: pointer; }

    .sidebar::-webkit-scrollbar, .main-content::-webkit-scrollbar { width: 5px; }
    .sidebar::-webkit-scrollbar-thumb, .main-content::-webkit-scrollbar-thumb { background: rgba(255,255,255,.18); border-radius: 10px; }
    a { color: var(--zb-primary); }
    a:hover { color: var(--zb-primary-dark); }
    .btn-group .btn { border-radius: 0; }
    .btn-group .btn:first-child { border-radius: var(--zb-radius-sm) 0 0 var(--zb-radius-sm); }
    .btn-group .btn:last-child  { border-radius: 0 var(--zb-radius-sm) var(--zb-radius-sm) 0; }
  </style>
  <?php if (isset($extra_head)) echo $extra_head; ?>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <a href="/dashboard.php" class="topbar-logo">
    <div class="topbar-logo-mark"><i class="bi bi-heart-pulse"></i></div>
    Zbox Asist
  </a>
  <div class="topbar-right">
    <span class="plan-badge"><i class="bi bi-people"></i> <?= ucfirst(plan_name()) ?></span>
    <span class="topbar-account"><?= e(client_name()) ?></span>
    <span class="topbar-user-dot">ZS</span>
    <a href="/logout.php" style="color:#dc2626;text-decoration:none;font-size:15px" title="Çıkış">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</div>

<div class="app-body">

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-section">Konuşmalar</div>
  <a href="/dashboard.php" class="nav-link <?= ($active_menu ?? '') === 'dashboard' ? 'active' : '' ?>">
    <i class="bi bi-chat-dots"></i> Hasta Akışı
    <?php
    $pending = db()->prepare(
        "SELECT COUNT(*) FROM conversations WHERE client_id = ? AND status = 'pending_takeover'"
    );
    $pending->execute([client_id()]);
    $cnt = (int)$pending->fetchColumn();
    if ($cnt > 0) echo '<span class="notif-badge">' . $cnt . '</span>';
    ?>
  </a>

  <?php if (client_has('agency_dir')): ?>
  <div class="sidebar-section">Acenta</div>
  <a href="/acentalar.php" class="nav-link <?= ($active_menu ?? '') === 'acentalar' ? 'active' : '' ?>">
    <i class="bi bi-building"></i> Acenta Dizini
  </a>
  <?php if (client_has('offer_request')): ?>
  <a href="/teklifler.php" class="nav-link <?= ($active_menu ?? '') === 'teklifler' ? 'active' : '' ?>">
    <i class="bi bi-envelope"></i> Teklifler
  </a>
  <?php endif; ?>
  <?php endif; ?>

  <?php if (client_has('reporting') || client_has('patient_summary') || client_has('cross_treatment')): ?>
  <div class="sidebar-section">Analitik</div>
  <?php if (client_has('reporting')): ?>
  <a href="/raporlar.php" class="nav-link <?= ($active_menu ?? '') === 'raporlar' ? 'active' : '' ?>">
    <i class="bi bi-bar-chart"></i> İçgörüler
  </a>
  <?php endif; ?>
  <?php if (client_has('patient_summary')): ?>
  <a href="/hasta-ozetleri.php" class="nav-link <?= ($active_menu ?? '') === 'hasta-ozetleri' ? 'active' : '' ?>">
    <i class="bi bi-stars"></i> AI Özetleri
  </a>
  <?php endif; ?>
  <?php if (client_has('cross_treatment')): ?>
  <a href="/capraz-tedavi.php" class="nav-link <?= ($active_menu ?? '') === 'capraz-tedavi' ? 'active' : '' ?>">
    <i class="bi bi-arrow-left-right"></i> Çapraz Tedavi
  </a>
  <?php endif; ?>
  <?php endif; ?>

  <?php if (client_has('proactive_msg')): ?>
  <div class="sidebar-section">İletişim</div>
  <a href="/proaktif-mesaj.php" class="nav-link <?= ($active_menu ?? '') === 'proaktif-mesaj' ? 'active' : '' ?>">
    <i class="bi bi-send"></i> Takip Mesajları
  </a>
  <?php endif; ?>

  <div class="sidebar-section">Hesap</div>
  <a href="/profil.php" class="nav-link <?= ($active_menu ?? '') === 'profil' ? 'active' : '' ?>">
    <i class="bi bi-person"></i> Profil
  </a>

  <div class="sidebar-footer">
    <div class="sidebar-user-avatar"><?= e(mb_strtoupper(mb_substr(portal_user_name(), 0, 1))) ?></div>
    <div>
      <div class="fw-500"><?= e(portal_user_name()) ?></div>
      <div class="text-muted"><?= is_coordinator() ? 'Hasta Koordinatörü' : 'Kullanıcı' ?></div>
      <div class="text-muted"><span class="online-dot"></span>Çevrimiçi</div>
    </div>
    <i class="bi bi-chevron-right" style="color:#c7d6ec"></i>
  </div>
</div>

<!-- Content -->
<div class="main-content">
<?php if (isset($render_flash_here) && $render_flash_here) render_flash(); ?>
