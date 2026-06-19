<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();

$page_title  = 'Yeni Admin';
$active_menu = 'adminler';
$pdo    = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name     = trim(post('name'));
    $email    = trim(post('email'));
    $password = post('password');
    $password2= post('password2');
    $role     = post('role');

    if (!$name)    $errors[] = 'Ad zorunludur.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli e-posta giriniz.';
    if (!$password || strlen($password) < 8) $errors[] = 'Şifre en az 8 karakter olmalıdır.';
    if ($password !== $password2) $errors[] = 'Şifreler eşleşmiyor.';
    if (!in_array($role, ['super_admin', 'operator'])) $errors[] = 'Rol seçiniz.';

    if (empty($errors)) {
        // E-posta benzersiz mi?
        $check = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'Bu e-posta zaten kayıtlı.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO admin_users (name, email, password_hash, role, status)
             VALUES (?, ?, ?, ?, 'active')"
        );
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        flash('success', '"' . $name . '" eklendi.');
        redirect('/adminler/');
    }
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
      <div class="card-header">Yeni Admin</div>
      <div class="card-body">
        <form method="POST" action="/adminler/ekle.php">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= e(post('name')) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">E-posta <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="<?= e(post('email')) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Şifre <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="8">
            <div class="form-text">En az 8 karakter.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Şifre Tekrar <span class="text-danger">*</span></label>
            <input type="password" name="password2" class="form-control" required>
          </div>
          <div class="mb-4">
            <label class="form-label">Rol <span class="text-danger">*</span></label>
            <select name="role" class="form-select" required>
              <option value="">Seçiniz</option>
              <option value="super_admin" <?= post('role') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
              <option value="operator"   <?= post('role') === 'operator'    ? 'selected' : '' ?>>Operator</option>
            </select>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Kaydet</button>
            <a href="/adminler/" class="btn btn-outline-secondary">İptal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
