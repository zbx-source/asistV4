<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();

$page_title  = 'Müşteriler';
$active_menu = 'musteriler';

$pdo = db();

// Filtreler
$search    = trim(get('q'));
$type      = get('type');
$status    = get('status');
$plan      = get('plan');
$per_page  = 20;
$cur_page  = max(1, (int)get('page', 1));

// WHERE koşulları
$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(c.name LIKE ? OR c.contact_email LIKE ? OR c.contact_phone LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type) {
    $where[]  = 'c.type = ?';
    $params[] = $type;
}

if ($status) {
    $where[]  = 'c.status = ?';
    $params[] = $status;
}

if ($plan) {
    $where[]  = 'p.name = ?';
    $params[] = $plan;
}

$where_sql = implode(' AND ', $where);

// Toplam kayıt
$total_stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM clients c
     LEFT JOIN subscriptions s ON s.client_id = c.id AND s.status = 'active'
     LEFT JOIN plans p ON p.id = s.plan_id
     WHERE $where_sql"
);
$total_stmt->execute($params);
$total = (int)$total_stmt->fetchColumn();

$pag = paginate($total, $per_page, $cur_page);

// Liste
$list_stmt = $pdo->prepare(
    "SELECT c.id, c.name, c.type, c.status, c.contact_phone, c.contact_email,
            c.city, c.created_at, p.name AS plan_name
     FROM clients c
     LEFT JOIN subscriptions s ON s.client_id = c.id AND s.status = 'active'
     LEFT JOIN plans p ON p.id = s.plan_id
     WHERE $where_sql
     ORDER BY c.created_at DESC
     LIMIT {$pag['per_page']} OFFSET {$pag['offset']}"
);
$list_stmt->execute($params);
$clients = $list_stmt->fetchAll();

require_once __DIR__ . '/../includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="text-muted small"><?= fmt_number($total) ?> müşteri</div>
  <a href="/musteriler/ekle.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg"></i> Yeni Müşteri
  </a>
</div>

<!-- Filtreler -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Ad, e-posta veya telefon..." value="<?= e($search) ?>">
      </div>
      <div class="col-md-2">
        <select name="type" class="form-select form-select-sm">
          <option value="">Tüm Tipler</option>
          <option value="clinic" <?= $type === 'clinic' ? 'selected' : '' ?>>Klinik</option>
          <option value="agency" <?= $type === 'agency' ? 'selected' : '' ?>>Acenta</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="plan" class="form-select form-select-sm">
          <option value="">Tüm Paketler</option>
          <option value="solo"    <?= $plan === 'solo'    ? 'selected' : '' ?>>Solo</option>
          <option value="ekip"    <?= $plan === 'ekip'    ? 'selected' : '' ?>>Ekip</option>
          <option value="hastane" <?= $plan === 'hastane' ? 'selected' : '' ?>>Hastane</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">Tüm Durumlar</option>
          <option value="active"    <?= $status === 'active'    ? 'selected' : '' ?>>Aktif</option>
          <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Askıda</option>
          <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>İptal</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">Filtrele</button>
        <?php if ($search || $type || $status || $plan): ?>
          <a href="/musteriler/" class="btn btn-outline-secondary btn-sm">✕</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Liste -->
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th class="ps-4">Müşteri Adı</th>
          <th>Tip</th>
          <th>Paket</th>
          <th>Telefon</th>
          <th>Şehir</th>
          <th>Durum</th>
          <th>Kayıt</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($clients)): ?>
          <tr>
            <td colspan="8" class="text-center text-muted py-4">Kayıt bulunamadı.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($clients as $c): ?>
            <tr>
              <td class="ps-4">
                <a href="/musteriler/detay.php?id=<?= $c['id'] ?>" class="text-decoration-none fw-500">
                  <?= e($c['name']) ?>
                </a>
              </td>
              <td><?= client_type_label($c['type']) ?></td>
              <td><?= $c['plan_name'] ? plan_label($c['plan_name']) : '<span class="text-muted">—</span>' ?></td>
              <td class="text-muted"><?= e($c['contact_phone'] ?: '—') ?></td>
              <td class="text-muted"><?= e($c['city'] ?: '—') ?></td>
              <td><?= status_badge($c['status']) ?></td>
              <td class="text-muted"><?= fmt_date($c['created_at']) ?></td>
              <td>
                <a href="/musteriler/detay.php?id=<?= $c['id'] ?>"
                   class="btn btn-sm btn-outline-secondary">Detay</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pag['total_pages'] > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="text-muted small">
        <?= $pag['offset'] + 1 ?>–<?= min($pag['offset'] + $per_page, $total) ?> / <?= $total ?>
      </div>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($i = 1; $i <= $pag['total_pages']; $i++): ?>
            <li class="page-item <?= $i === $pag['current_page'] ? 'active' : '' ?>">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                <?= $i ?>
              </a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
