<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

auth_check();

$page_title  = 'Profil';
$active_menu = 'profil';
$pdo    = db();
$errors = [];
$success = false;
$uid = portal_user_id();
$cid = client_id();

// Kullanıcı bilgileri
$user = $pdo->prepare("SELECT id, name, email, role FROM portal_users WHERE id = ?");
$user->execute([$uid]);
$user = $user->fetch();

// Kurumsal bilgiler
$company = $pdo->prepare(
    "SELECT c.name AS company_name, c.type, c.address, c.tax_no, c.tax_office,
            c.license_no, c.contact_phone, c.phone_2, c.contact_email,
            c.city, c.authorized_person_1, c.authorized_person_2,
            p.name AS plan_name, p.monthly_quota,
            s.billing_cycle, s.start_date, s.end_date,
            q.used_count
     FROM clients c
     LEFT JOIN subscriptions s ON s.client_id = c.id AND s.status = 'active'
     LEFT JOIN plans p ON p.id = s.plan_id
     LEFT JOIN quota_usage q ON q.client_id = c.id
         AND q.year = YEAR(NOW()) AND q.month = MONTH(NOW())
     WHERE c.id = ?"
);
$company->execute([$cid]);
$company = $company->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $current  = post('current_password');
    $new      = post('new_password');
    $new2     = post('new_password2');

    if (!$current) $errors[] = 'Mevcut şifre gereklidir.';
    if (!$new || strlen($new) < 8) $errors[] = 'Yeni şifre en az 8 karakter olmalıdır.';
    if ($new !== $new2) $errors[] = 'Yeni şifreler eşleşmiyor.';

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT password_hash FROM portal_users WHERE id = ?");
        $check->execute([$uid]);
        $row = $check->fetch();
        if (!password_verify($current, $row['password_hash'])) {
            $errors[] = 'Mevcut şifre yanlış.';
        }
    }

    if (empty($errors)) {
        $pdo->prepare("UPDATE portal_users SET password_hash = ? WHERE id = ?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
        $pdo->prepare("DELETE FROM portal_sessions WHERE portal_user_id = ? AND session_token != ?")
            ->execute([$uid, $_SESSION['session_token']]);
        $success = true;
    }
}

require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="row g-3">

  <!-- Kurumsal Bilgiler -->
  <div class="col-md-7">
    <div class="card mb-3">
      <div class="card-header">Kurumsal Bilgiler</div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-8">
            <div class="text-muted small">Kurum Adı</div>
            <div class="fw-500"><?= e($company['company_name'] ?? '—') ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Tip</div>
            <div><?= client_type_label($company['type'] ?? '') ?></div>
          </div>
          <?php if ($company['address']): ?>
          <div class="col-12 mt-2">
            <div class="text-muted small">Adres</div>
            <div><?= e($company['address']) ?></div>
          </div>
          <?php endif; ?>
          <div class="col-md-4 mt-2">
            <div class="text-muted small">Vergi No</div>
            <div><?= e($company['tax_no'] ?: '—') ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Vergi Dairesi</div>
            <div><?= e($company['tax_office'] ?: '—') ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Lisans No</div>
            <div><?= e($company['license_no'] ?: '—') ?></div>
          </div>
          <div class="col-md-4 mt-2">
            <div class="text-muted small">Şehir</div>
            <div><?= e($company['city'] ?: '—') ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">İletişim</div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-6">
            <div class="text-muted small">Yetkili 1</div>
            <div><?= e($company['authorized_person_1'] ?: '—') ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small">Yetkili 2</div>
            <div><?= e($company['authorized_person_2'] ?: '—') ?></div>
          </div>
          <div class="col-md-4 mt-2">
            <div class="text-muted small">Telefon 1</div>
            <div><?= e($company['contact_phone'] ?: '—') ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Telefon 2</div>
            <div><?= e($company['phone_2'] ?: '—') ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">E-posta</div>
            <div><?= e($company['contact_email'] ?: '—') ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Abonelik & Kota</div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-4">
            <div class="text-muted small">Paket</div>
            <div class="fw-500"><?= plan_label($company['plan_name'] ?? '') ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Fatura Döngüsü</div>
            <div><?= ($company['billing_cycle'] ?? '') === 'yearly' ? 'Yıllık' : 'Aylık' ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Bitiş Tarihi</div>
            <div><?= fmt_date($company['end_date'] ?? '') ?></div>
          </div>
          <?php if ($company['plan_name']): ?>
          <div class="col-12 mt-2">
            <div class="text-muted small mb-1">
              Bu Ay Kota: <?= (int)($company['used_count'] ?? 0) ?>/<?= (int)($company['monthly_quota'] ?? 0) ?> hasta
            </div>
            <?php
              $used  = (int)($company['used_count'] ?? 0);
              $limit = (int)($company['monthly_quota'] ?? 1);
              $pct   = $limit > 0 ? min(100, round($used / $limit * 100)) : 0;
              $color = $pct >= 100 ? 'danger' : ($pct >= 70 ? 'warning' : 'success');
            ?>
            <div class="progress" style="height:8px">
              <div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%"></div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Kullanıcı & Şifre -->
  <div class="col-md-5">
    <div class="card mb-3">
      <div class="card-header">Kullanıcı Bilgileri</div>
      <div class="card-body">
        <div class="mb-2">
          <div class="text-muted small">Ad Soyad</div>
          <div><?= e($user['name']) ?></div>
        </div>
        <div class="mb-2">
          <div class="text-muted small">E-posta</div>
          <div><?= e($user['email']) ?></div>
        </div>
        <div>
          <div class="text-muted small">Rol</div>
          <div><?= $user['role'] === 'coordinator' ? 'Koordinatör' : 'Kullanıcı' ?></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Şifre Değiştir</div>
      <div class="card-body">
        <?php if ($success): ?>
          <div class="alert alert-success small">Şifreniz güncellendi.</div>
        <?php endif; ?>
        <?php if ($errors): ?>
          <div class="alert alert-danger small">
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?>
                <li><?= e($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <form method="POST">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Mevcut Şifre</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Yeni Şifre</label>
            <input type="password" name="new_password" class="form-control" required minlength="8">
            <div class="form-text">En az 8 karakter.</div>
          </div>
          <div class="mb-4">
            <label class="form-label">Yeni Şifre Tekrar</label>
            <input type="password" name="new_password2" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Şifreyi Güncelle</button>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
