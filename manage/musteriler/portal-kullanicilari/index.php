<?php
// MANAGE — manage/musteriler/portal-kullanicilari/index.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/features.php';

auth_check();

$active_menu = 'musteriler';
$pdo = db();

$client_id = (int)get('client_id');
if (!$client_id) redirect('/musteriler/');

$client = $pdo->prepare("SELECT id, name FROM clients WHERE id = ?");
$client->execute([$client_id]);
$client = $client->fetch();
if (!$client) redirect('/musteriler/');

$page_title = 'Portal Kullanıcıları: ' . $client['name'];

// Plan adı (bilgi amaçlı)
$plan = $pdo->prepare(
    "SELECT p.name FROM subscriptions s
     JOIN plans p ON p.id = s.plan_id
     WHERE s.client_id = ? AND s.status = 'active' LIMIT 1"
);
$plan->execute([$client_id]);
$plan = $plan->fetch();

$users = $pdo->prepare(
    "SELECT id, name, email, role, status, last_login_at, created_at
     FROM portal_users WHERE client_id = ? ORDER BY created_at"
);
$users->execute([$client_id]);
$users = $users->fetchAll();

require_once __DIR__ . '/../../includes/layout_header.php';
?>

<div class="mb-3 d-flex justify-content-between align-items-center">
  <a href="/musteriler/detay.php?id=<?= $client_id ?>" class="text-muted text-decoration-none small">
    <i class="bi bi-arrow-left"></i> <?= e($client['name']) ?>
  </a>
  <a href="/musteriler/portal-kullanicilari/ekle.php?client_id=<?= $client_id ?>" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg"></i> Yeni Kullanıcı
  </a>
</div>

<?php if ($plan): ?>
  <div class="alert alert-info small py-2 mb-3">
    Paket: <strong><?= plan_label($plan['name']) ?></strong>
    <?php if (!client_has('coordinator', $client_id)): ?>
      — Bu pakette koordinatör rolü aktif değildir.
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th class="ps-4">Ad</th>
          <th>E-posta</th>
          <th>Rol</th>
          <th>Durum</th>
          <th>Son Giriş</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Henüz kullanıcı yok.</td></tr>
        <?php else: ?>
          <?php foreach ($users as $u): ?>
            <tr>
              <td class="ps-4"><?= e($u['name']) ?></td>
              <td class="text-muted"><?= e($u['email']) ?></td>
              <td><?= $u['role'] === 'coordinator' ? 'Koordinatör' : 'Kullanıcı' ?></td>
              <td><?= status_badge($u['status']) ?></td>
              <td class="text-muted"><?= fmt_datetime($u['last_login_at']) ?></td>
              <td>
                <a href="/musteriler/portal-kullanicilari/duzenle.php?id=<?= $u['id'] ?>&client_id=<?= $client_id ?>"
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
