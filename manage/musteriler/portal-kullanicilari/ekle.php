<?php
// MANAGE — manage/musteriler/portal-kullanicilari/ekle.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/features.php';

auth_check();

$active_menu = 'musteriler';
$pdo    = db();
$errors = [];

$client_id = (int)get('client_id');
if (!$client_id) redirect('/musteriler/');

$client = $pdo->prepare("SELECT id, name FROM clients WHERE id = ?");
$client->execute([$client_id]);
$client = $client->fetch();
if (!$client) redirect('/musteriler/');

$page_title = 'Yeni Portal Kullanıcısı';

// Plan adı (bilgi amaçlı)
$plan = $pdo->prepare(
    "SELECT p.name FROM subscriptions s
     JOIN plans p ON p.id = s.plan_id
     WHERE s.client_id = ? AND s.status = 'active' LIMIT 1"
);
$plan->execute([$client_id]);
$plan = $plan->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name      = trim(post('name'));
    $email     = trim(post('email'));
    $password  = post('password');
    $password2 = post('password2');
    $role      = post('role');

    if (!$name)  $errors[] = 'Ad zorunludur.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli e-posta giriniz.';
    if (!$password || strlen($password) < 8) $errors[] = 'Şifre en az 8 karakter olmalıdır.';
    if ($password !== $password2) $errors[] = 'Şifreler eşleşmiyor.';
    if (!in_array($role, ['user', 'coordinator'])) $errors[] = 'Rol seçiniz.';
    if ($role === 'coordinator' && !client_has('coordinator', $client_id)) {
        $errors[] = 'Bu pakette koordinatör rolü kullanılamaz.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM portal_users WHERE client_id = ? AND email = ?");
        $check->execute([$client_id, $email]);
        if ($check->fetch()) $errors[] = 'Bu e-posta bu müşteri için zaten kayıtlı.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO portal_users (client_id, name, email, password_hash, role, status)
             VALUES (?, ?, ?, ?, ?, 'active')"
        );
        $stmt->execute([
            $client_id, $name, $email,
            password_hash($password, PASSWORD_DEFAULT),
            $role,
        ]);
        flash('success', '"' . $name . '" eklendi.');
        redirect('/musteriler/portal-kullanicilari/?client_id=' . $client_id);
    }
}

require_once __DIR__ . '/../../includes/layout_header.php';
?>

<div class="mb-3">
  <a href="/musteriler/portal-kullanicilari/?client_id=<?= $client_id ?>" class="text-muted text-decoration-none small">
    <i class="bi bi-arrow-left"></i> Portal Kullanıcıları
  </a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Yeni Kullanıcı — <?= e($client['name']) ?></div>
      <div class="card-body">
        <form method="POST">
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
              <option value="user" <?= post('role') === 'user' ? 'selected' : '' ?>>Kullanıcı</option>
              <?php if (client_has('coordinator', $client_id)): ?>
                <option value="coordinator" <?= post('role') === 'coordinator' ? 'selected' : '' ?>>Koordinatör</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Kaydet</button>
            <a href="/musteriler/portal-kullanicilari/?client_id=<?= $client_id ?>" class="btn btn-outline-secondary">İptal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
