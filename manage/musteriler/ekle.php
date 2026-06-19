<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();

$page_title  = 'Yeni Müşteri';
$active_menu = 'musteriler';

$pdo    = db();
$errors = [];

// Paketleri çek
$plans = $pdo->query("SELECT id, name FROM plans ORDER BY id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name                = trim(post('name'));
    $type                = post('type');
    $address             = trim(post('address'));
    $tax_no              = trim(post('tax_no'));
    $contact_phone       = trim(post('contact_phone'));
    $phone_2             = trim(post('phone_2'));
    $contact_email       = trim(post('contact_email'));
    $authorized_person_1 = trim(post('authorized_person_1'));
    $authorized_person_2 = trim(post('authorized_person_2'));
    $license_no          = trim(post('license_no'));
    $city                = trim(post('city'));
    $country             = trim(post('country')) ?: 'TR';
    $notes               = trim(post('notes'));

    // Abonelik
    $plan_id       = (int)post('plan_id');
    $billing_cycle = post('billing_cycle');
    $start_date    = post('start_date');
    $price_tl      = post('price_tl');

    // Validasyon
    if (!$name) $errors[] = 'Müşteri adı zorunludur.';
    if (!$type || !in_array($type, ['clinic', 'agency'])) $errors[] = 'Tip seçiniz.';
    if ($contact_email && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçersiz e-posta adresi.';
    }
    if ($plan_id && !$start_date) {
        $errors[] = 'Paket seçildiyse başlangıç tarihi zorunludur.';
    }
    if ($plan_id && !in_array($billing_cycle, ['monthly', 'yearly'])) {
        $errors[] = 'Fatura döngüsü seçiniz.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO clients
                 (type, name, address, tax_no, tax_office, license_no,
                  authorized_person_1, authorized_person_2,
                  contact_phone, phone_2, contact_email,
                  city, country, notes, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $type, $name, $address, $tax_no, $tax_office, $license_no,
                $authorized_person_1, $authorized_person_2,
                $contact_phone, $phone_2, $contact_email,
                $city, $country, $notes,
                $_SESSION['admin_id'],
            ]);

            $new_id = (int)$pdo->lastInsertId();

            if ($plan_id) {
                $end_date = $billing_cycle === 'yearly'
                    ? date('Y-m-d', strtotime($start_date . ' +1 year'))
                    : date('Y-m-d', strtotime($start_date . ' +1 month'));

                $sub_stmt = $pdo->prepare(
                    "INSERT INTO subscriptions
                     (client_id, plan_id, billing_cycle, start_date, end_date, status, price_usd, created_by)
                     VALUES (?, ?, ?, ?, ?, 'active', ?, ?)"
                );
                $sub_stmt->execute([
                    $new_id, $plan_id, $billing_cycle,
                    $start_date, $end_date,
                    $price_tl ?: null,
                    $_SESSION['admin_id'],
                ]);
            }

            $pdo->commit();
            flash('success', '"' . $name . '" başarıyla eklendi.');
            redirect('/musteriler/detay.php?id=' . $new_id);

        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = 'Kayıt sırasında hata oluştu: ' . $ex->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/layout_header.php';
?>

<div class="mb-3">
  <a href="/musteriler/" class="text-muted text-decoration-none small">
    <i class="bi bi-arrow-left"></i> Müşteriler
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

<form method="POST" action="/musteriler/ekle.php">
  <?= csrf_field() ?>
  <div class="row g-3">

    <div class="col-md-8">

      <div class="card mb-3">
        <div class="card-header">Genel Bilgiler</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Müşteri Adı <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?= e(post('name')) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tip <span class="text-danger">*</span></label>
              <select name="type" class="form-select" required>
                <option value="">Seçiniz</option>
                <option value="clinic" <?= post('type') === 'clinic' ? 'selected' : '' ?>>Klinik</option>
                <option value="agency" <?= post('type') === 'agency' ? 'selected' : '' ?>>Acenta</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Adres</label>
              <textarea name="address" class="form-control" rows="2"><?= e(post('address')) ?></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Vergi No</label>
              <input type="text" name="tax_no" class="form-control" value="<?= e(post('tax_no')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Vergi Dairesi</label>
              <input type="text" name="tax_office" class="form-control" value="<?= e(post('tax_office')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Lisans No</label>
              <input type="text" name="license_no" class="form-control" value="<?= e(post('license_no')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Şehir</label>
              <input type="text" name="city" class="form-control" value="<?= e(post('city')) ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header">İletişim</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Yetkili 1</label>
              <input type="text" name="authorized_person_1" class="form-control" value="<?= e(post('authorized_person_1')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Yetkili 2</label>
              <input type="text" name="authorized_person_2" class="form-control" value="<?= e(post('authorized_person_2')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefon 1</label>
              <input type="text" name="contact_phone" class="form-control" value="<?= e(post('contact_phone')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefon 2</label>
              <input type="text" name="phone_2" class="form-control" value="<?= e(post('phone_2')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-posta</label>
              <input type="email" name="contact_email" class="form-control" value="<?= e(post('contact_email')) ?>">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header">Abonelik</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Paket</label>
              <select name="plan_id" class="form-select">
                <option value="">— Seçiniz —</option>
                <?php foreach ($plans as $p): ?>
                  <option value="<?= $p['id'] ?>" <?= (int)post('plan_id') === (int)$p['id'] ? 'selected' : '' ?>>
                    <?= plan_label($p['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Fatura Döngüsü</label>
              <select name="billing_cycle" class="form-select">
                <option value="monthly" <?= post('billing_cycle') !== 'yearly' ? 'selected' : '' ?>>Aylık</option>
                <option value="yearly"  <?= post('billing_cycle') === 'yearly'  ? 'selected' : '' ?>>Yıllık</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Başlangıç Tarihi</label>
              <input type="date" name="start_date" class="form-control"
                     value="<?= e(post('start_date') ?: date('Y-m-d')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Fiyat (TL)</label>
              <input type="number" step="0.01" name="price_tl" class="form-control"
                     value="<?= e(post('price_tl')) ?>" placeholder="0.00">
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Notlar</div>
        <div class="card-body">
          <textarea name="notes" class="form-control" rows="3" placeholder="İç notlar..."><?= e(post('notes')) ?></textarea>
        </div>
      </div>

    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-header">Kaydet</div>
        <div class="card-body">
          <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="bi bi-check-lg"></i> Müşteriyi Kaydet
          </button>
          <a href="/musteriler/" class="btn btn-outline-secondary w-100">İptal</a>
        </div>
      </div>
    </div>

  </div>
</form>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
