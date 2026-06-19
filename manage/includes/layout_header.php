<?php
// $page_title değişkeni sayfa tarafından set edilmeli
$page_title = $page_title ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> — Zbox Asist</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root {
      --zb-green: #0f6e56;
      --zb-green-dark: #0a5442;
      --zb-sidebar-width: 240px;
    }

    body { background: #f4f6f9; font-size: 14px; }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 0; left: 0;
      width: var(--zb-sidebar-width);
      height: 100vh;
      background: #fff;
      border-right: 1px solid #e9ecef;
      display: flex;
      flex-direction: column;
      z-index: 100;
    }

    .sidebar-logo {
      padding: 20px 20px 16px;
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--zb-green);
      border-bottom: 1px solid #e9ecef;
      letter-spacing: -0.5px;
    }

    .sidebar-logo span {
      font-weight: 400;
      color: #888;
      font-size: 0.75rem;
      display: block;
      margin-top: 2px;
    }

    .sidebar-nav {
      flex: 1;
      overflow-y: auto;
      padding: 12px 0;
    }

    .nav-section {
      font-size: 10px;
      font-weight: 600;
      color: #aaa;
      letter-spacing: 0.08em;
      padding: 12px 20px 4px;
      text-transform: uppercase;
    }

    .sidebar-nav .nav-link {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 20px;
      color: #444;
      border-radius: 0;
      font-size: 13.5px;
    }

    .sidebar-nav .nav-link:hover {
      background: #f4f6f9;
      color: var(--zb-green);
    }

    .sidebar-nav .nav-link.active {
      background: #e8f4f0;
      color: var(--zb-green);
      font-weight: 500;
    }

    .sidebar-nav .nav-link i {
      font-size: 15px;
      width: 18px;
      text-align: center;
    }

    .sidebar-footer {
      padding: 12px 20px;
      border-top: 1px solid #e9ecef;
      font-size: 12px;
      color: #888;
    }

    /* Main content */
    .main-content {
      margin-left: var(--zb-sidebar-width);
      min-height: 100vh;
    }

    .topbar {
      background: #fff;
      border-bottom: 1px solid #e9ecef;
      padding: 12px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 50;
    }

    .topbar-title {
      font-weight: 600;
      font-size: 15px;
      color: #222;
    }

    .page-body {
      padding: 24px;
    }

    /* Kartlar */
    .card {
      border: 1px solid #e9ecef;
      border-radius: 10px;
      box-shadow: none;
    }

    .card-header {
      background: #fff;
      border-bottom: 1px solid #e9ecef;
      font-weight: 500;
      padding: 14px 20px;
    }

    /* Tablo */
    .table th {
      font-size: 12px;
      font-weight: 600;
      color: #888;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      border-bottom: 2px solid #e9ecef;
    }

    .table td { vertical-align: middle; }

    /* Butonlar */
    .btn-primary {
      background: var(--zb-green);
      border-color: var(--zb-green);
    }
    .btn-primary:hover {
      background: var(--zb-green-dark);
      border-color: var(--zb-green-dark);
    }

    .btn-outline-primary {
      color: var(--zb-green);
      border-color: var(--zb-green);
    }
    .btn-outline-primary:hover {
      background: var(--zb-green);
      border-color: var(--zb-green);
    }

    /* Stat kartları */
    .stat-card {
      background: #fff;
      border: 1px solid #e9ecef;
      border-radius: 10px;
      padding: 20px;
    }

    .stat-card .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: #222;
      line-height: 1;
    }

    .stat-card .stat-label {
      font-size: 12px;
      color: #888;
      margin-top: 4px;
    }

    .stat-card .stat-icon {
      font-size: 1.8rem;
      color: var(--zb-green);
      opacity: 0.7;
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="sidebar-logo">
    Zbox Asist
    <span>Admin Panel</span>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Genel</div>
    <a href="/dashboard.php" class="nav-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section">Müşteriler</div>
    <a href="/musteriler/" class="nav-link <?= $active_menu === 'musteriler' ? 'active' : '' ?>">
      <i class="bi bi-building"></i> Müşteriler
    </a>
    <a href="/abonelikler/" class="nav-link <?= $active_menu === 'abonelikler' ? 'active' : '' ?>">
      <i class="bi bi-card-checklist"></i> Abonelikler
    </a>
    <a href="/eklentiler/" class="nav-link <?= $active_menu === 'eklentiler' ? 'active' : '' ?>">
      <i class="bi bi-puzzle"></i> Eklentiler
    </a>
    <a href="/odemeler/" class="nav-link <?= $active_menu === 'odemeler' ? 'active' : '' ?>">
      <i class="bi bi-cash-stack"></i> Ödemeler
    </a>

    <div class="nav-section">Sistem</div>
    <a href="/core-rules/" class="nav-link <?= $active_menu === 'core-rules' ? 'active' : '' ?>">
      <i class="bi bi-shield-check"></i> Core Rules
    </a>
    <a href="/adminler/" class="nav-link <?= $active_menu === 'adminler' ? 'active' : '' ?>">
      <i class="bi bi-people"></i> Admin Kullanıcıları
    </a>
  </nav>

  <div class="sidebar-footer">
    <div><?= e(admin_name()) ?></div>
    <a href="/logout.php" class="text-danger text-decoration-none">
      <i class="bi bi-box-arrow-right"></i> Çıkış
    </a>
  </div>
</div>

<!-- Main -->
<div class="main-content">
  <div class="topbar">
    <div class="topbar-title"><?= e($page_title) ?></div>
    <div class="text-muted small"><?= date('d.m.Y H:i') ?></div>
  </div>
  <div class="page-body">
    <?php render_flash(); ?>
