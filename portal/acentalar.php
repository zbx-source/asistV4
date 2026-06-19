<?php
// PORTAL — portal/acentalar.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

auth_check();
if (!client_has('agency_dir')) redirect('/dashboard.php');

$page_title  = 'Acenta Dizini';
$active_menu = 'acentalar';

$pdo = db();
$cid = client_id();

$search = trim(get('q', ''));

$where  = ["c.type = 'agency'", "c.status = 'active'"];
$params = [];

if ($search) {
    $where[]  = '(c.name LIKE ? OR c.city LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = implode(' AND ', $where);

$agencies = $pdo->prepare(
    "SELECT c.id, c.name, c.city, c.contact_phone, c.contact_email
     FROM clients c
     WHERE $where_sql
     ORDER BY c.name"
);
$agencies->execute($params);
$agencies = $agencies->fetchAll();

require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h6 class="mb-0">Acenta Dizini</h6>
    <div class="text-muted small"><?= count($agencies) ?> acenta</div>
  </div>
  <form method="GET" class="d-flex gap-2">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Ara..." value="<?= e($search) ?>" style="width:200px">
    <button class="btn btn-sm btn-primary">Ara</button>
    <?php if ($search): ?>
      <a href="/acentalar.php" class="btn btn-sm btn-outline-secondary">✕</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th class="ps-3">Acenta Adı</th>
          <th>Şehir</th>
          <th>Telefon</th>
          <th>E-posta</th>
          <?php if (client_has('offer_request')): ?>
            <th></th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($agencies)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Acenta bulunamadı.</td></tr>
        <?php else: ?>
          <?php foreach ($agencies as $a): ?>
            <tr>
              <td class="ps-3 fw-500"><?= e($a['name']) ?></td>
              <td class="text-muted"><?= e($a['city'] ?: '—') ?></td>
              <td class="text-muted"><?= e($a['contact_phone'] ?: '—') ?></td>
              <td class="text-muted"><?= e($a['contact_email'] ?: '—') ?></td>
              <?php if (client_has('offer_request')): ?>
                <td>
                  <a href="/teklifler.php?yeni=1&agency_id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary">
                    Teklif İste
                  </a>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
