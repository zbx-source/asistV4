<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();

$page_title  = 'Admin Kullanıcıları';
$active_menu = 'adminler';
$pdo = db();

$admins = $pdo->query(
    "SELECT id, name, email, role, status, created_at, updated_at
     FROM admin_users ORDER BY id"
)->fetchAll();

require_once __DIR__ . '/../includes/layout_header.php';
?>

<div class="d-flex justify-content-end mb-3">
  <a href="/adminler/ekle.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg"></i> Yeni Admin
  </a>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th class="ps-4">Ad</th>
          <th>E-posta</th>
          <th>Rol</th>
          <th>Durum</th>
          <th>Son Güncelleme</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($admins as $a): ?>
          <tr>
            <td class="ps-4"><?= e($a['name']) ?></td>
            <td class="text-muted"><?= e($a['email']) ?></td>
            <td>
              <?php if ($a['role'] === 'super_admin'): ?>
                <span class="badge bg-dark">Super Admin</span>
              <?php else: ?>
                <span class="badge bg-secondary">Operator</span>
              <?php endif; ?>
            </td>
            <td><?= status_badge($a['status']) ?></td>
            <td class="text-muted"><?= fmt_datetime($a['updated_at'] ?: $a['created_at']) ?></td>
            <td>
              <?php if ($a['id'] !== (int)$_SESSION['admin_id']): ?>
                <a href="/adminler/duzenle.php?id=<?= $a['id'] ?>"
                   class="btn btn-sm btn-outline-secondary">Düzenle</a>
              <?php else: ?>
                <span class="text-muted small">Aktif oturum</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
