<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();

$active_menu = 'musteriler';
$pdo = db();

$id = (int)get('id');
if (!$id) redirect('/musteriler/');

// Müşteriyi çek
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();
if (!$client) redirect('/musteriler/');

$page_title = 'Düzenle: ' . $client['name'];

// Aktif abonelik
$sub_stmt = $pdo->prepare(
    "SELECT s.*, p.name AS plan_name
     FROM subscriptions s
     JOIN plans p ON p.id = s.plan_id
     WHERE s.client_id = ? AND s.status = 'active'
     ORDER BY s.created_at DESC LIMIT 1"
);
$sub_stmt->execute([$id]);
$sub = $sub_stmt->fetch();

// Paketleri çek
$plans = $pdo->query("SELECT id, name FROM plans ORDER BY id")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name                = trim(post('name'));
    $type                = post('type');
    $address             = trim(post('address'));
    $tax_no              = trim(post('tax_no'));
    $tax_office          = trim(post('tax_office'));
    $contact_phone       = trim(post('contact_phone'));
    $phone_2             = trim(post('phone_2'));
    $contact_email       = trim(post('contact_email'));
    $authorized_person_1 = trim(post('authorized_person_1'));
    $authorized_person_2 = trim(post('authorized_person_2'));
    $license_no          = trim(post('license_no'));
    $city                = trim(post('city'));
    $country             = trim(post('country')) ?: 'TR';
    $status              = post('status');
    $notes               = trim(post('notes'));

    // Abonelik
    $plan_id       = (int)post('plan_id');
    $billing_cycle = post('billing_cycle');
    $start_date    = post('start_date');
    $price_tl      = post('price_tl');
    $sub_status    = post('sub_status');

    // Validasyon
    if (!$name) $errors[] = 'Müşteri adı zorunludur.';
    if (!$type || !in_array($type, ['clinic', 'agency'])) $errors[] = 'Tip seçiniz.';
    if (!in_array($status, ['active', 'suspended', 'cancelled'])) $errors[] = 'Geçersiz durum.';
    if ($contact_email && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçersiz e-posta adresi.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Müşteriyi güncelle
            $upd = $pdo->prepare(
                "UPDATE clients SET
                 type=?, name=?, address=?, tax_no=?, tax_office=?, license_no=?,
                 authorized_person_1=?, authorized_person_2=?,
                 contact_phone=?, phone_2=?, contact_email=?,
                 city=?, country=?, status=?, notes=?
                 WHERE id=?"
            );
            $upd->execute([
                $type, $name, $address, $tax_no, $tax_office, $license_no,
                $authorized_person_1, $authorized_person_2,
                $contact_phone, $phone_2, $contact_email,
                $city, $country, $status, $notes,
                $id,
            ]);

            // Abonelik güncelle
            if ($sub && $plan_id) {
                $end_date = $billing_cycle === 'yearly'
                    ? date('Y-m-d', strtotime($start_date . ' +1 year'))
                    : date('Y-m-d', strtotime($start_date . ' +1 month'));

                $sub_upd = $pdo->prepare(
                    "UPDATE subscriptions SET
                     plan_id=?, billing_cycle=?, start_date=?, end_date=?,
                     status=?, price_usd=?
                     WHERE id=?"
                );
                $sub_upd->execute([
                    $plan_id, $billing_cycle, $start_date, $end_date,
                    $sub_status ?: 'active', $price_tl ?: null,
                    $sub['id'],
                ]);
            } elseif (!$sub && $plan_id && $start_date) {
                // Yeni abonelik ekle
                $end_date = $billing_cycle === 'yearly'
                    ? date('Y-m-d', strtotime($start_date . ' +1 year'))
                    : date('Y-m-d', strtotime($start_date . ' +1 month'));

                $sub_ins = $pdo->prepare(
                    "INSERT INTO subscriptions
                     (client_id, plan_id, billing_cycle, start_date, end_date, status, price_usd, created_by)
                     VALUES (?, ?, ?, ?, ?, 'active', ?, ?)"
                );
                $sub_ins->execute([
                    $id, $plan_id, $billing_cycle,
                    $start_date, $end_date,
                    $price_tl ?: null,
                    $_SESSION['admin_id'],
                ]);
            }

            // Portal kullanıcılarını müşteri durumuyla senkronize et
            $portal_status = in_array($status, ['suspended', 'cancelled']) ? 'suspended' : 'active';
            $pdo->prepare("UPDATE portal_users SET status = ? WHERE client_id = ?")
                ->execute([$portal_status, $id]);

            // Müşteri suspend/cancel olunca aktif session'ları sil
            if (in_array($status, ['suspended', 'cancelled'])) {
                $pdo->prepare("DELETE FROM portal_sessions WHERE client_id = ?")
                    ->execute([$id]);
            }

            $pdo->commit();
            flash('success', '"' . $name . '" güncellendi.');
            redirect('/musteriler/detay.php?id=' . $id);

        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = 'Kayıt sırasında hata oluştu: ' . $ex->getMessage();
        }
    }

    // Hata varsa POST değerlerini koru
    $client = array_merge($client, [
        'name' => $name, 'type' => $type, 'address' => $address,
        'tax_no' => $tax_no, 'tax_office' => $tax_office, 'license_no' => $license_no,
        'authorized_person_1' => $authorized_person_1,
        'authorized_person_2' => $authorized_person_2,
        'contact_phone' => $contact_phone, 'phone_2' => $phone_2,
        'contact_email' => $contact_email, 'city' => $city,
        'status' => $status, 'notes' => $notes,
    ]);
}

require_once __DIR__ . '/../includes/layout_header.php';
?>

<div class="mb-3">
  <a href="/musteriler/detay.php?id=<?= $id ?>" class="text-muted text-decoration-none small">
    <i class="bi bi-arrow-left"></i> <?= e($client['name']) ?>
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

<form method="POST" action="/musteriler/duzenle.php?id=<?= $id ?>">
  <?= csrf_field() ?>
  <div class="row g-3">

    <div class="col-md-8">

      <div class="card mb-3">
        <div class="card-header">Genel Bilgiler</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Müşteri Adı <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?= e($client['name']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tip <span class="text-danger">*</span></label>
              <select name="type" class="form-select" required>
                <option value="clinic" <?= $client['type'] === 'clinic' ? 'selected' : '' ?>>Klinik</option>
                <option value="agency" <?= $client['type'] === 'agency' ? 'selected' : '' ?>>Acenta</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Adres</label>
              <textarea name="address" class="form-control" rows="2"><?= e($client['address']) ?></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Vergi No</label>
              <input type="text" name="tax_no" class="form-control" value="<?= e($client['tax_no']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Vergi Dairesi</label>
              <input type="text" name="tax_office" class="form-control" value="<?= e($client['tax_office']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Lisans No</label>
              <input type="text" name="license_no" class="form-control" value="<?= e($client['license_no']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Şehir</label>
              <input type="text" name="city" class="form-control" value="<?= e($client['city']) ?>">
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
              <input type="text" name="authorized_person_1" class="form-control" value="<?= e($client['authorized_person_1']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Yetkili 2</label>
              <input type="text" name="authorized_person_2" class="form-control" value="<?= e($client['authorized_person_2']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefon 1</label>
              <input type="text" name="contact_phone" class="form-control" value="<?= e($client['contact_phone']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefon 2</label>
              <input type="text" name="phone_2" class="form-control" value="<?= e($client['phone_2']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-posta</label>
              <input type="email" name="contact_email" class="form-control" value="<?= e($client['contact_email']) ?>">
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
                  <option value="<?= $p['id'] ?>"
                    <?= $sub && (int)$sub['plan_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                    <?= plan_label($p['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Fatura Döngüsü</label>
              <select name="billing_cycle" class="form-select">
                <option value="monthly" <?= (!$sub || $sub['billing_cycle'] === 'monthly') ? 'selected' : '' ?>>Aylık</option>
                <option value="yearly"  <?= ($sub && $sub['billing_cycle'] === 'yearly') ? 'selected' : '' ?>>Yıllık</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Başlangıç Tarihi</label>
              <input type="date" name="start_date" class="form-control"
                     value="<?= e($sub ? $sub['start_date'] : date('Y-m-d')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Fiyat (TL)</label>
              <input type="number" step="0.01" name="price_tl" class="form-control"
                     value="<?= e($sub ? $sub['price_usd'] : '') ?>" placeholder="0.00">
            </div>
            <?php if ($sub): ?>
            <div class="col-md-4">
              <label class="form-label">Abonelik Durumu</label>
              <select name="sub_status" class="form-select">
                <option value="active"    <?= $sub['status'] === 'active'    ? 'selected' : '' ?>>Aktif</option>
                <option value="expired"   <?= $sub['status'] === 'expired'   ? 'selected' : '' ?>>Süresi Doldu</option>
                <option value="cancelled" <?= $sub['status'] === 'cancelled' ? 'selected' : '' ?>>İptal</option>
              </select>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Notlar</div>
        <div class="card-body">
          <textarea name="notes" class="form-control" rows="3"><?= e($client['notes']) ?></textarea>
        </div>
      </div>

    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-header">Kaydet</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Müşteri Durumu</label>
            <select name="status" class="form-select">
              <option value="active"    <?= $client['status'] === 'active'    ? 'selected' : '' ?>>Aktif</option>
              <option value="suspended" <?= $client['status'] === 'suspended' ? 'selected' : '' ?>>Askıya Al</option>
              <option value="cancelled" <?= $client['status'] === 'cancelled' ? 'selected' : '' ?>>İptal Et</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary w-100 mb-2">
            <i class="bi bi-check-lg"></i> Güncelle
          </button>
          <a href="/musteriler/detay.php?id=<?= $id ?>" class="btn btn-outline-secondary w-100">İptal</a>
        </div>
      </div>
    </div>

  </div>
</form>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
