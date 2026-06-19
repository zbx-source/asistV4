<?php
// MANAGE — manage/musteriler/portal-kullanicilari/duzenle.php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/features.php';

auth_check();

$active_menu = 'musteriler';
$pdo    = db();
$errors = [];

$id        = (int)get('id');
$client_id = (int)get('client_id');
if (!$id || !$client_id) redirect('/musteriler/');

$user = $pdo->prepare("SELECT * FROM portal_users WHERE id = ? AND client_id = ?");
$user->execute([$id, $client_id]);
$user = $user->fetch();
if (!$user) redirect('/musteriler/');

$client = $pdo->prepare("SELECT id, name FROM clients WHERE id = ?");
$client->execute([$client_id]);
$client = $client->fetch();

$page_title = 'Düzenle: ' . $user['name'];

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
    $status    = post('status');

    if (!$name)  $errors[] = 'Ad zorunludur.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Geçerli e-posta giriniz.';
    if (!in_array($role, ['user', 'coordinator'])) $errors[] = 'Rol seçiniz.';
    if (!in_array($status, ['active', 'suspended'])) $errors[] = 'Geçersiz durum.';
    if ($password && strlen($password) < 8) $errors[] = 'Şifre en az 8 karakter olmalıdır.';
    if ($password && $password !== $password2) $errors[] = 'Şifreler eşleşmiyor.';
    if ($role === 'coordinator' && !client_has('coordinator', $client_id)) {
        $errors[] = 'Bu pakette koordinatör rolü kullanılamaz.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM portal_users WHERE client_id = ? AND email = ? AND id != ?");
        $check->execute([$client_id, $email, $id]);
        if ($check->fetch()) $errors[] = 'Bu e-posta başka kullanıcıda kayıtlı.';
    }

    if (empty($errors)) {
        if ($password) {
            $upd = $pdo->prepare(
                "UPDATE portal_users SET name=?, email=?, password_hash=?, role=?, status=? WHERE id=?"
            );
            $upd->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $status, $id]);
        } else {
            $upd = $pdo->prepare(
                "UPDATE portal_users SET name=?, email=?, role=?, status=? WHERE id=?"
            );
            $upd->execute([$name, $email, $role, $status, $id]);
        }
        flash('success', '"' . $name . '" güncellendi.');
        redirect('/musteriler/portal-kullanicilari/?client_id=' . $client_id);
    }

    $user = array_merge($user, compact('name', 'email', 'role', 'status'));
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
      <div class="card-header">Düzenle: <?= e($user['name']) ?></div>
      <div class="card-body">
        <form method="POST" action="/musteriler/portal-kullanicilari/duzenle.php?id=<?= $id ?>&client_id=<?= $client_id ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">E-posta <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
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
            <label class="form-label">Rol</label>
            <select name="role" class="form-select">
              <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Kullanıcı</option>
              <?php if (client_has('coordinator', $client_id)): ?>
                <option value="coordinator" <?= $user['role'] === 'coordinator' ? 'selected' : '' ?>>Koordinatör</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="mb-4">
            <label class="form-label">Durum</label>
            <select name="status" class="form-select">
              <option value="active"    <?= $user['status'] === 'active'    ? 'selected' : '' ?>>Aktif</option>
              <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Askıya Al</option>
            </select>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Güncelle</button>
            <a href="/musteriler/portal-kullanicilari/?client_id=<?= $client_id ?>" class="btn btn-outline-secondary">İptal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
