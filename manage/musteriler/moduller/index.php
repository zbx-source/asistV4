<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

auth_check();

$active_menu = 'musteriler';
$pdo = db();

$client_id = (int)get('client_id');
if (!$client_id) redirect('/musteriler/');

$client = $pdo->prepare("SELECT id, name FROM clients WHERE id = ?");
$client->execute([$client_id]);
$client = $client->fetch();
if (!$client) redirect('/musteriler/');

$page_title = 'Tedavi Modülleri: ' . $client['name'];

// Plan — modül limiti
$plan = $pdo->prepare(
    "SELECT p.name, p.max_modules FROM subscriptions s
     JOIN plans p ON p.id = s.plan_id
     WHERE s.client_id = ? AND s.status = 'active' LIMIT 1"
);
$plan->execute([$client_id]);
$plan = $plan->fetch();

$modules = $pdo->prepare(
    "SELECT id, name, status, sort_order, created_at, updated_at
     FROM treatment_modules WHERE client_id = ? ORDER BY sort_order, id"
);
$modules->execute([$client_id]);
$modules = $modules->fetchAll();

$active_count = count(array_filter($modules, fn($m) => $m['status'] === 'active'));
$max = $plan ? (int)$plan['max_modules'] : 1;

require_once __DIR__ . '/../../includes/layout_header.php';
?>

<div class="mb-3 d-flex justify-content-between align-items-center">
  <a href="/musteriler/detay.php?id=<?= $client_id ?>" class="text-muted text-decoration-none small">
    <i class="bi bi-arrow-left"></i> <?= e($client['name']) ?>
  </a>
  <?php if ($active_count < $max): ?>
    <a href="/musteriler/moduller/ekle.php?client_id=<?= $client_id ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg"></i> Yeni Modül
    </a>
  <?php else: ?>
    <span class="text-muted small">Modül limitine ulaşıldı (<?= $active_count ?>/<?= $max ?>)</span>
  <?php endif; ?>
</div>

<?php if ($plan): ?>
  <div class="alert alert-info small py-2 mb-3">
    Paket: <strong><?= plan_label($plan['name']) ?></strong> —
    Aktif modül: <strong><?= $active_count ?>/<?= $max ?></strong>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th class="ps-4">Modül Adı</th>
          <th>Sıra</th>
          <th>Durum</th>
          <th>Güncelleme</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($modules)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Henüz modül yok.</td></tr>
        <?php else: ?>
          <?php foreach ($modules as $m): ?>
            <tr>
              <td class="ps-4 fw-500"><?= e($m['name']) ?></td>
              <td class="text-muted"><?= $m['sort_order'] ?></td>
              <td><?= $m['status'] === 'active' ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Arşiv</span>' ?></td>
              <td class="text-muted small"><?= fmt_datetime($m['updated_at'] ?: $m['created_at']) ?></td>
              <td>
                <a href="/musteriler/moduller/duzenle.php?id=<?= $m['id'] ?>&client_id=<?= $client_id ?>"
                   class="btn btn-sm btn-outline-secondary">Düzenle</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
