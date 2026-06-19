<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

session_start_safe();

// Zaten giriş yapmışsa dashboard'a yönlendir
if (!empty($_SESSION['admin_id'])) {
    redirect('/dashboard.php');
}

$error = '';
$timeout = get('timeout');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = trim(post('email'));
    $password = post('password');

    if (empty($email) || empty($password)) {
        $error = 'E-posta ve şifre gereklidir.';
    } elseif (auth_login($email, $password)) {
        redirect('/dashboard.php');
    } else {
        $error = 'E-posta veya şifre hatalı.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Zbox Asist — Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; }
    .login-card {
      max-width: 420px;
      margin: 100px auto;
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 24px rgba(0,0,0,.08);
    }
    .login-logo {
      font-size: 1.4rem;
      font-weight: 700;
      color: #0f6e56;
      letter-spacing: -0.5px;
    }
    .btn-primary {
      background: #0f6e56;
      border-color: #0f6e56;
    }
    .btn-primary:hover {
      background: #0a5442;
      border-color: #0a5442;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="card login-card">
    <div class="card-body p-5">
      <div class="text-center mb-4">
        <div class="login-logo">Zbox Asist</div>
        <div class="text-muted small mt-1">Admin Panel</div>
      </div>

      <?php if ($timeout): ?>
        <div class="alert alert-warning">Oturumunuz sona erdi. Lütfen tekrar giriş yapın.</div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="/index.php">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label">E-posta</label>
          <input
            type="email"
            name="email"
            class="form-control"
            value="<?= e(post('email')) ?>"
            required
            autofocus
          >
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
</body>
</html>
