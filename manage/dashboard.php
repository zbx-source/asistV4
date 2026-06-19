<?php
// MANAGE — manage/dashboard.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

auth_check();

$page_title  = 'Dashboard';
$active_menu = 'dashboard';

$pdo = db();

// Stat: Toplam aktif müşteri
$total_clients = $pdo->query(
    "SELECT COUNT(*) FROM clients WHERE status = 'active'"
)->fetchColumn();

// Stat: Bu ay yeni abonelik
$new_subs = $pdo->query(
    "SELECT COUNT(*) FROM subscriptions
     WHERE YEAR(created_at) = YEAR(NOW())
       AND MONTH(created_at) = MONTH(NOW())
       AND status = 'active'"
)->fetchColumn();

// Stat: Kota %80+ dolu müşteriler
$quota_warning = $pdo->query(
    "SELECT COUNT(DISTINCT q.client_id)
     FROM quota_usage q
     JOIN subscriptions s ON s.client_id = q.client_id AND s.status = 'active'
     JOIN plans p ON p.id = s.plan_id
     WHERE q.year = YEAR(NOW())
       AND q.month = MONTH(NOW())
       AND q.used_count >= (p.monthly_quota * 0.8)"
)->fetchColumn();

// Stat: Bu ay ödeme
$monthly_payment = $pdo->query(
    "SELECT COALESCE(SUM(amount), 0)
     FROM payments
     WHERE YEAR(payment_date) = YEAR(NOW())
       AND MONTH(payment_date) = MONTH(NOW())"
)->fetchColumn();

// AI Kullanım — tarih filtresi
$sys_range = get('sys_range', '30days');
$sys_from  = get('sys_from', '');
$sys_to    = get('sys_to', '');
$today_d   = date('Y-m-d');
switch ($sys_range) {
    case 'today':     $sys_start = $today_d; $sys_end = $today_d; break;
    case 'yesterday': $sys_start = date('Y-m-d', strtotime('-1 day')); $sys_end = $sys_start; break;
    case '7days':     $sys_start = date('Y-m-d', strtotime('-6 days')); $sys_end = $today_d; break;
    case 'custom':    $sys_start = $sys_from ?: $today_d; $sys_end = $sys_to ?: $today_d; break;
    default:          $sys_start = date('Y-m-d', strtotime('-29 days')); $sys_end = $today_d; break;
}

$ai_system = $pdo->prepare(
    "SELECT COUNT(*) AS calls, COALESCE(SUM(total_tokens), 0) AS tokens, COUNT(DISTINCT client_id) AS clients
     FROM ai_usage_log WHERE DATE(created_at) BETWEEN ? AND ?"
);
$ai_system->execute([$sys_start, $sys_end]);
$ai_system = $ai_system->fetch();

$ai_per_client = $pdo->prepare(
    "SELECT c.name, c.id AS cid, COUNT(*) AS calls, SUM(a.total_tokens) AS tokens
     FROM ai_usage_log a JOIN clients c ON c.id = a.client_id
     WHERE DATE(a.created_at) BETWEEN ? AND ?
     GROUP BY a.client_id ORDER BY tokens DESC LIMIT 10"
);
$ai_per_client->execute([$sys_start, $sys_end]);
$ai_clients = $ai_per_client->fetchAll();

// Son 10 müşteri
$recent_clients = $pdo->query(
    "SELECT c.id, c.name, c.type, c.status, c.created_at,
            p.name AS plan_name
     FROM clients c
     LEFT JOIN subscriptions s ON s.client_id = c.id AND s.status = 'active'
     LEFT JOIN plans p ON p.id = s.plan_id
     ORDER BY c.created_at DESC
     LIMIT 10"
)->fetchAll();

require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="stat-card d-flex justify-content-between align-items-center">
      <div>
        <div class="stat-value"><?= fmt_number((int)$total_clients) ?></div>
        <div class="stat-label">Aktif Müşteri</div>
      </div>
      <i class="bi bi-building stat-icon"></i>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card d-flex justify-content-between align-items-center">
      <div>
        <div class="stat-value"><?= fmt_number((int)$new_subs) ?></div>
        <div class="stat-label">Bu Ay Yeni Abonelik</div>
      </div>
      <i class="bi bi-card-checklist stat-icon"></i>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card d-flex justify-content-between align-items-center">
      <div>
        <div class="stat-value"><?= fmt_number((int)$quota_warning) ?></div>
        <div class="stat-label">Kota Uyarısı Olan</div>
      </div>
      <i class="bi bi-exclamation-triangle stat-icon text-warning"></i>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card d-flex justify-content-between align-items-center">
      <div>
        <div class="stat-value">$<?= fmt_number((float)$monthly_payment) ?></div>
        <div class="stat-label">Bu Ay Tahsilat</div>
      </div>
      <i class="bi bi-cash-stack stat-icon"></i>
    </div>
  </div>
</div>

<!-- AI Kullanım & Maliyet -->
<div class="card mb-4">
  <div class="card-header">AI Kullanım & Maliyet</div>
  <div class="card-body">
    <div class="d-flex gap-2 align-items-center flex-wrap mb-3">
      <div class="btn-group btn-group-sm">
        <a href="?sys_range=today" class="btn <?= $sys_range === 'today' ? 'btn-primary' : 'btn-outline-secondary' ?>">Bugün</a>
        <a href="?sys_range=yesterday" class="btn <?= $sys_range === 'yesterday' ? 'btn-primary' : 'btn-outline-secondary' ?>">Dün</a>
        <a href="?sys_range=7days" class="btn <?= $sys_range === '7days' ? 'btn-primary' : 'btn-outline-secondary' ?>">Son 7 gün</a>
        <a href="?sys_range=30days" class="btn <?= $sys_range === '30days' ? 'btn-primary' : 'btn-outline-secondary' ?>">Son 30 gün</a>
      </div>
      <form method="GET" class="d-flex gap-1 align-items-center">
        <input type="hidden" name="sys_range" value="custom">
        <input type="date" name="sys_from" class="form-control form-control-sm" value="<?= e($sys_from ?: $sys_start) ?>" style="width:140px">
        <span class="text-muted">–</span>
        <input type="date" name="sys_to" class="form-control form-control-sm" value="<?= e($sys_to ?: $sys_end) ?>" style="width:140px">
        <button class="btn btn-sm btn-primary">Uygula</button>
      </form>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-3 text-center">
        <div class="fs-5 fw-bold"><?= number_format($ai_system['calls']) ?></div>
        <div class="text-muted small">Toplam Çağrı</div>
      </div>
      <div class="col-3 text-center">
        <div class="fs-5 fw-bold"><?= number_format($ai_system['tokens']) ?></div>
        <div class="text-muted small">Toplam Token</div>
      </div>
      <div class="col-3 text-center">
        <div class="fs-5 fw-bold">$<?= number_format($ai_system['tokens'] * 0.0003 / 1000, 4) ?></div>
        <div class="text-muted small">Tahmini Maliyet</div>
      </div>
      <div class="col-3 text-center">
        <div class="fs-5 fw-bold"><?= $ai_system['clients'] ?></div>
        <div class="text-muted small">Aktif Müşteri</div>
      </div>
    </div>
    <?php if ($ai_clients): ?>
    <div class="text-muted small fw-600 mb-1">Müşteri Bazlı</div>
    <table class="table table-sm mb-0">
      <thead><tr><th class="ps-3">Müşteri</th><th>Çağrı</th><th>Token</th><th>Maliyet</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($ai_clients as $ac): ?>
        <tr>
          <td class="ps-3 fw-500"><?= e($ac['name']) ?></td>
          <td><?= number_format($ac['calls']) ?></td>
          <td><?= number_format($ac['tokens']) ?></td>
          <td class="text-muted">$<?= number_format($ac['tokens'] * 0.0003 / 1000, 4) ?></td>
          <td><a href="/musteriler/detay.php?id=<?= $ac['cid'] ?>" class="btn btn-sm btn-outline-secondary">Detay</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <div class="text-center text-muted py-2">Bu dönemde AI kullanımı yok.</div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Son Eklenen Müşteriler</span>
    <a href="/musteriler/" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th class="ps-4">Müşteri</th>
          <th>Tip</th>
          <th>Paket</th>
          <th>Durum</th>
          <th>Kayıt Tarihi</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recent_clients)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">Henüz müşteri yok.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($recent_clients as $c): ?>
            <tr>
              <td class="ps-4 fw-500"><?= e($c['name']) ?></td>
              <td><?= client_type_label($c['type']) ?></td>
              <td><?= $c['plan_name'] ? plan_label($c['plan_name']) : '<span class="text-muted">—</span>' ?></td>
              <td><?= status_badge($c['status']) ?></td>
              <td class="text-muted"><?= fmt_datetime($c['created_at']) ?></td>
              <td>
                <a href="/musteriler/detay.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">
                  Detay
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
