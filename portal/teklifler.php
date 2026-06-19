<?php
// PORTAL — portal/teklifler.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

auth_check();
if (!client_has('offer_request')) redirect('/dashboard.php');

$page_title  = 'Teklifler';
$active_menu = 'teklifler';

$pdo = db();
$cid = client_id();

$show_form = get('yeni') === '1';
$errors    = [];

// Yeni teklif talebi gönder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $agency_id    = (int)post('agency_id');
    $patient_id   = (int)post('patient_id');
    $patient_country = trim(post('patient_country'));
    $estimated_date  = post('estimated_date');
    $treatment_dur   = (int)post('treatment_duration');
    $companion       = post('companion') ? 1 : 0;
    $companion_count = $companion ? max(1, (int)post('companion_count')) : 0;
    $special         = trim(post('special_requests'));

    if (!$agency_id) $errors[] = 'Acenta seçiniz.';
    if (!$patient_id) $errors[] = 'Hasta seçiniz.';

    if (empty($errors)) {
        $pdo->prepare(
            "INSERT INTO quote_requests
             (from_client_id, to_agency_id, patient_id, patient_country, estimated_date,
              treatment_duration, companion, companion_count, special_requests)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $cid, $agency_id, $patient_id, $patient_country ?: null,
            $estimated_date ?: null, $treatment_dur ?: null,
            $companion, $companion_count, $special ?: null
        ]);
        flash('success', 'Teklif talebi gönderildi.');
        redirect('/teklifler.php');
    }

    $show_form = true;
}

// Teklif listesi
$requests = $pdo->prepare(
    "SELECT qr.*, a.name AS agency_name, p.name AS patient_name, p.phone AS patient_phone,
            (SELECT COUNT(*) FROM quote_responses qresp WHERE qresp.request_id = qr.id) AS response_count
     FROM quote_requests qr
     JOIN clients a ON a.id = qr.to_agency_id
     JOIN patients p ON p.id = qr.patient_id
     WHERE qr.from_client_id = ?
     ORDER BY qr.created_at DESC"
);
$requests->execute([$cid]);
$requests = $requests->fetchAll();

// Form verileri
$agencies = [];
$patients = [];
if ($show_form) {
    $agencies = $pdo->prepare(
        "SELECT id, name FROM clients WHERE type = 'agency' AND status = 'active' ORDER BY name"
    );
    $agencies->execute();
    $agencies = $agencies->fetchAll();

    $patients = $pdo->prepare(
        "SELECT id, name, phone FROM patients WHERE client_id = ? ORDER BY last_contact DESC LIMIT 50"
    );
    $patients->execute([$cid]);
    $patients = $patients->fetchAll();
}

require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0">Teklifler</h6>
  <?php if (!$show_form): ?>
    <a href="/teklifler.php?yeni=1" class="btn btn-sm btn-primary">
      <i class="bi bi-plus-lg"></i> Yeni Teklif Talebi
    </a>
  <?php endif; ?>
</div>

<?php if ($show_form): ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-header">Yeni Teklif Talebi</div>
    <div class="card-body">
      <form method="POST">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Acenta <span class="text-danger">*</span></label>
            <select name="agency_id" class="form-select" required>
              <option value="">Seçiniz</option>
              <?php foreach ($agencies as $a): ?>
                <option value="<?= $a['id'] ?>" <?= (int)post('agency_id') === $a['id'] || (int)get('agency_id') === $a['id'] ? 'selected' : '' ?>>
                  <?= e($a['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Hasta <span class="text-danger">*</span></label>
            <select name="patient_id" class="form-select" required>
              <option value="">Seçiniz</option>
              <?php foreach ($patients as $p): ?>
                <option value="<?= $p['id'] ?>" <?= (int)post('patient_id') === $p['id'] ? 'selected' : '' ?>>
                  <?= e($p['name'] ?: $p['phone']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Hasta Ülkesi</label>
            <input type="text" name="patient_country" class="form-control" value="<?= e(post('patient_country')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tahmini Tarih</label>
            <input type="date" name="estimated_date" class="form-control" value="<?= e(post('estimated_date')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tedavi Süresi (gün)</label>
            <input type="number" name="treatment_duration" class="form-control" value="<?= e(post('treatment_duration')) ?>" min="1">
          </div>
          <div class="col-md-4">
            <div class="form-check mt-4">
              <input type="checkbox" name="companion" value="1" class="form-check-input" id="chkComp" <?= post('companion') ? 'checked' : '' ?>>
              <label class="form-check-label" for="chkComp">Refakatçi var</label>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Refakatçi Sayısı</label>
            <input type="number" name="companion_count" class="form-control" value="<?= e(post('companion_count') ?: '0') ?>" min="0">
          </div>
          <div class="col-12">
            <label class="form-label">Özel İstekler</label>
            <textarea name="special_requests" class="form-control" rows="3"><?= e(post('special_requests')) ?></textarea>
          </div>
          <div class="col-12">
            <button class="btn btn-primary">Gönder</button>
            <a href="/teklifler.php" class="btn btn-outline-secondary ms-2">İptal</a>
          </div>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- Teklif listesi -->
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th class="ps-3">Acenta</th>
          <th>Hasta</th>
          <th>Tarih</th>
          <th>Durum</th>
          <th>Yanıt</th>
          <th>Oluşturulma</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($requests)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Henüz teklif talebi yok.</td></tr>
        <?php else: ?>
          <?php foreach ($requests as $r): ?>
            <tr>
              <td class="ps-3 fw-500"><?= e($r['agency_name']) ?></td>
              <td class="text-muted"><?= e($r['patient_name'] ?: $r['patient_phone']) ?></td>
              <td class="text-muted"><?= $r['estimated_date'] ? fmt_date($r['estimated_date']) : '—' ?></td>
              <td>
                <?php if ($r['status'] === 'pending'): ?>
                  <span class="badge bg-warning text-dark">Bekliyor</span>
                <?php elseif ($r['status'] === 'responded'): ?>
                  <span class="badge bg-success">Yanıtlandı</span>
                <?php else: ?>
                  <span class="badge bg-secondary">İptal</span>
                <?php endif; ?>
              </td>
              <td class="fw-500"><?= $r['response_count'] ?></td>
              <td class="text-muted small"><?= fmt_datetime($r['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
