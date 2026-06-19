<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start_safe();

if (!empty($_SESSION['portal_user_id'])) redirect('/dashboard.php');

$error   = '';
$timeout = get('timeout');
$solo_conflict = get('solo_conflict');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //csrf_verify();
    $email    = trim(post('email'));
    $password = post('password');

    if (!$email || !$password) {
        $error = 'E-posta ve şifre gereklidir.';
    } elseif (!auth_login($email, $password)) {
        // Solo pakette çakışma mı?
        $error = 'E-posta/şifre hatalı veya hesabınız askıya alınmış.';
    } else {
        redirect('/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Giriş — Zbox Asist</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f0f2f5; }
    .login-wrap { max-width: 400px; margin: 80px auto; }
    .login-card { border: none; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
    .logo { font-size: 1.3rem; font-weight: 700; color: #0f6e56; }
    .btn-primary { background: #0f6e56; border-color: #0f6e56; }
    .btn-primary:hover { background: #0a5442; border-color: #0a5442; }
  </style>
</head>
<body>
<div class="container">
  <div class="login-wrap">
    <div class="card login-card">
      <div class="card-body p-5">
        <div class="text-center mb-4">
          <div class="logo">Zbox Asist</div>
          <div class="text-muted small mt-1">Klinik Portalı</div>
        </div>

        <?php if ($timeout): ?>
          <div class="alert alert-warning small">Oturumunuz sona erdi.</div>
        <?php endif; ?>
        <?php if ($solo_conflict): ?>
          <div class="alert alert-warning small">Bu hesapta aktif bir oturum var.</div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-danger small"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">E-posta</label>
            <input type="email" name="email" class="form-control"
                   value="<?= e(post('email')) ?>" required autofocus>
          </div>
          <div class="mb-4">
            <label class="form-label">Şifre</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body>
</html>
