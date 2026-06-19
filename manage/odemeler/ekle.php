<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();

$page_title  = 'Ödeme Ekle';
$active_menu = 'odemeler';
$pdo    = db();
$errors = [];

// URL'den müşteri geldiyse
$preset_client_id = (int)get('client_id');

// Müşteri listesi
$clients = $pdo->query(
    "SELECT id, name FROM clients WHERE status = 'active' ORDER BY name"
)->fetchAll();

// Seçili müşterinin abonelikleri (opsiyonel bağlantı için)
$subscriptions = [];
$sel_client_id = $preset_client_id ?: (int)post('client_id');
if ($sel_client_id) {
    $sub_stmt = $pdo->prepare(
        "SELECT s.id, p.name AS plan_name, s.start_date, s.billing_cycle
         FROM subscriptions s
         JOIN plans p ON p.id = s.plan_id
         WHERE s.client_id = ?
         ORDER BY s.created_at DESC"
    );
    $sub_stmt->execute([$sel_client_id]);
    $subscriptions = $sub_stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $client_id      = (int)post('client_id');
    $subscription_id= (int)post('subscription_id') ?: null;
    $amount         = post('amount');
    $payment_date   = post('payment_date');
    $method         = trim(post('method'));
    $reference      = trim(post('reference'));
    $note           = trim(post('note'));

    if (!$client_id)    $errors[] = 'Müşteri seçiniz.';
    if (!$amount || !is_numeric($amount) || $amount <= 0) $errors[] = 'Geçerli tutar giriniz.';
    if (!$payment_date) $errors[] = 'Ödeme tarihi zorunludur.';

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO payments
             (client_id, subscription_id, amount, currency, payment_date, method, reference, note, recorded_by)
             VALUES (?, ?, ?, 'TL', ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $client_id, $subscription_id, $amount,
            $payment_date, $method, $reference, $note,
            $_SESSION['admin_id'],
        ]);

        flash('success', 'Ödeme kaydedildi.');

        // Müşteri detayına dön
        redirect('/musteriler/detay.php?id=' . $client_id);
    }
}

require_once __DIR__ . '/../includes/layout_header.php';
?>

<div class="mb-3">
  <a href="/odemeler/" class="text-muted text-decoration-none small">
    <i class="bi bi-arrow-left"></i> Ödemeler
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
      <div class="card-header">Yeni Ödeme</div>
      <div class="card-body">
        <form method="POST" action="/odemeler/ekle.php" id="odeme-form">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label">Müşteri <span class="text-danger">*</span></label>
            <select name="client_id" class="form-select" required id="client-select">
              <option value="">Seçiniz</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= $c['id'] ?>"
                  <?= ($preset_client_id === (int)$c['id'] || (int)post('client_id') === (int)$c['id']) ? 'selected' : '' ?>>
                  <?= e($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php if (!empty($subscriptions)): ?>
          <div class="mb-3">
            <label class="form-label">Abonelik (opsiyonel)</label>
            <select name="subscription_id" class="form-select">
              <option value="">— Bağlantısız —</option>
              <?php foreach ($subscriptions as $s): ?>
                <option value="<?= $s['id'] ?>"
                  <?= (int)post('subscription_id') === (int)$s['id'] ? 'selected' : '' ?>>
                  <?= plan_label($s['plan_name']) ?> —
                  <?= $s['billing_cycle'] === 'yearly' ? 'Yıllık' : 'Aylık' ?> —
                  <?= fmt_date($s['start_date']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Tutar (TL) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="amount" class="form-control"
                   value="<?= e(post('amount')) ?>" placeholder="0.00" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Ödeme Tarihi <span class="text-danger">*</span></label>
            <input type="date" name="payment_date" class="form-control"
                   value="<?= e(post('payment_date') ?: date('Y-m-d')) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Yöntem</label>
            <select name="method" class="form-select">
              <option value="">— Seçiniz —</option>
              <option value="Kredi Kartı"  <?= post('method') === 'Kredi Kartı'  ? 'selected' : '' ?>>Kredi Kartı</option>
              <option value="Havale/EFT"   <?= post('method') === 'Havale/EFT'   ? 'selected' : '' ?>>Havale / EFT</option>
              <option value="Nakit"        <?= post('method') === 'Nakit'        ? 'selected' : '' ?>>Nakit</option>
              <option value="Diğer"        <?= post('method') === 'Diğer'        ? 'selected' : '' ?>>Diğer</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Referans No</label>
            <input type="text" name="reference" class="form-control"
                   value="<?= e(post('reference')) ?>" placeholder="Dekont / işlem no">
          </div>

          <div class="mb-4">
            <label class="form-label">Not</label>
            <textarea name="note" class="form-control" rows="2"><?= e(post('note')) ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg"></i> Kaydet
            </button>
            <a href="/odemeler/" class="btn btn-outline-secondary">İptal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
// Müşteri değişince abonelik listesini güncelle
document.getElementById('client-select')?.addEventListener('change', function() {
    const id = this.value;
    if (id) {
        window.location.href = '/odemeler/ekle.php?client_id=' + id;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
