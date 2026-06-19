<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();

$page_title  = 'Admin Düzenle';
$active_menu = 'adminler';
$pdo = db();

$id = (int)get('id');
if (!$id) redirect('/adminler/');

// Kendi kendini düzenleyemez
if ($id === (int)$_SESSION['admin_id']) redirect('/adminler/');

$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
$stmt->execute([$id]);
$admin = $stmt->fetch();
if (!$admin) redirect('/adminler/');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name     = trim(post('name'));
    $email    = trim(post('email'));
    $password = post('password');
    $password2= post('password2');
    $role     = post('role');
    $status   = post('status');

    if (!$name) $errors[] = 'Ad zorunludur.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli e-posta giriniz.';
    if (!in_array($role, ['super_admin', 'operator'])) $errors[] = 'Rol seçiniz.';
    if (!in_array($status, ['active', 'suspended'])) $errors[] = 'Geçersiz durum.';
    if ($password && strlen($password) < 8) $errors[] = 'Şifre en az 8 karakter olmalıdır.';
    if ($password && $password !== $password2) $errors[] = 'Şifreler eşleşmiyor.';

    if (empty($errors)) {
        // E-posta başkasında var mı?
        $check = $pdo->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
        $check->execute([$email, $id]);
        if ($check->fetch()) $errors[] = 'Bu e-posta başka admin tarafından kullanılıyor.';
    }

    if (empty($errors)) {
        if ($password) {
            $upd = $pdo->prepare(
                "UPDATE admin_users SET name=?, email=?, password_hash=?, role=?, status=? WHERE id=?"
            );
            $upd->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $status, $id]);
        } else {
            $upd = $pdo->prepare(
                "UPDATE admin_users SET name=?, email=?, role=?, status=? WHERE id=?"
            );
            $upd->execute([$name, $email, $role, $status, $id]);
        }
        flash('success', '"' . $name . '" güncellendi.');
        redirect('/adminler/');
    }

    $admin = array_merge($admin, compact('name', 'email', 'role', 'status'));
}

require_once __DIR__ . '/../includes/layout_header.php';
?>

<div class="mb-3">
  <a href="/adminler/" class="text-muted text-decoration-none small">
    <i class="bi bi-arrow-left"></i> Admin Kullanıcıları
  </a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Düzenle: <?= e($admin['name']) ?></div>
      <div class="card-body">
        <form method="POST" action="/adminler/duzenle.php?id=<?= $id ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= e($admin['name']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">E-posta <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="<?= e($admin['email']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Yeni Şifre</label>
            <input type="password" name="password" class="form-control" minlength="8">
            <div class="form-text">Boş bırakılırsa şifre değişmez.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Şifre Tekrar</label>
            <input type="password" name="password2" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Rol <span class="text-danger">*</span></label>
            <select name="role" class="form-select">
              <option value="super_admin" <?= $admin['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
              <option value="operator"   <?= $admin['role'] === 'operator'    ? 'selected' : '' ?>>Operator</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="form-label">Durum</label>
            <select name="status" class="form-select">
              <option value="active"    <?= $admin['status'] === 'active'    ? 'selected' : '' ?>>Aktif</option>
              <option value="suspended" <?= $admin['status'] === 'suspended' ? 'selected' : '' ?>>Askıya Al</option>
            </select>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Güncelle</button>
            <a href="/adminler/" class="btn btn-outline-secondary">İptal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
