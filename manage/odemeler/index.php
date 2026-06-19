<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();

$page_title  = 'Ödemeler';
$active_menu = 'odemeler';
$pdo = db();

// Filtreler
$client_id  = (int)get('client_id');
$date_from  = get('date_from');
$date_to    = get('date_to');
$per_page   = 20;
$cur_page   = max(1, (int)get('page', 1));

$where  = ['1=1'];
$params = [];

if ($client_id) {
    $where[]  = 'p.client_id = ?';
    $params[] = $client_id;
}
if ($date_from) {
    $where[]  = 'p.payment_date >= ?';
    $params[] = $date_from;
}
if ($date_to) {
    $where[]  = 'p.payment_date <= ?';
    $params[] = $date_to;
}

$where_sql = implode(' AND ', $where);

// Toplam
$total = (int)$pdo->prepare(
    "SELECT COUNT(*) FROM payments p WHERE $where_sql"
)->execute($params) ? $pdo->prepare(
    "SELECT COUNT(*) FROM payments p WHERE $where_sql"
)->execute($params) : 0;

$cnt_stmt = $pdo->prepare("SELECT COUNT(*) FROM payments p WHERE $where_sql");
$cnt_stmt->execute($params);
$total = (int)$cnt_stmt->fetchColumn();

$pag = paginate($total, $per_page, $cur_page);

// Toplam tutar (filtreye göre)
$sum_stmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE $where_sql");
$sum_stmt->execute($params);
$total_amount = (float)$sum_stmt->fetchColumn();

// Liste
$list_stmt = $pdo->prepare(
    "SELECT p.*, c.name AS client_name
     FROM payments p
     JOIN clients c ON c.id = p.client_id
     WHERE $where_sql
     ORDER BY p.payment_date DESC, p.id DESC
     LIMIT {$pag['per_page']} OFFSET {$pag['offset']}"
);
$list_stmt->execute($params);
$payments = $list_stmt->fetchAll();

// Müşteri listesi (filtre dropdown için)
$clients = $pdo->query(
    "SELECT id, name FROM clients WHERE status = 'active' ORDER BY name"
)->fetchAll();

require_once __DIR__ . '/../includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="text-muted small"><?= fmt_number($total) ?> kayıt — Toplam: <strong><?= fmt_number($total_amount) ?> TL</strong></div>
  <a href="/odemeler/ekle.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg"></i> Ödeme Ekle
  </a>
</div>

<!-- Filtreler -->
<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4">
        <select name="client_id" class="form-select form-select-sm">
          <option value="">Tüm Müşteriler</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $client_id === (int)$c['id'] ? 'selected' : '' ?>>
              <?= e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="date_from" class="form-control form-control-sm"
               value="<?= e($date_from) ?>" placeholder="Başlangıç">
      </div>
      <div class="col-md-2">
        <input type="date" name="date_to" class="form-control form-control-sm"
               value="<?= e($date_to) ?>" placeholder="Bitiş">
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">Filtrele</button>
        <?php if ($client_id || $date_from || $date_to): ?>
          <a href="/odemeler/" class="btn btn-outline-secondary btn-sm">✕</a>
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
          <th class="ps-4">Müşteri</th>
          <th>Tutar</th>
          <th>Tarih</th>
          <th>Yöntem</th>
          <th>Referans</th>
          <th>Not</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">Kayıt bulunamadı.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td class="ps-4">
                <a href="/musteriler/detay.php?id=<?= $p['client_id'] ?>"
                   class="text-decoration-none"><?= e($p['client_name']) ?></a>
              </td>
              <td class="fw-500"><?= fmt_number($p['amount']) ?> <?= e($p['currency']) ?></td>
              <td class="text-muted"><?= fmt_date($p['payment_date']) ?></td>
              <td class="text-muted"><?= e($p['method'] ?: '—') ?></td>
              <td class="text-muted small"><?= e($p['reference'] ?: '—') ?></td>
              <td class="text-muted small"><?= e($p['note'] ?: '—') ?></td>
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
