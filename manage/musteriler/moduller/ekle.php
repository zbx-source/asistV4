<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

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

$page_title = 'Yeni Tedavi Modülü';

// Limit kontrolü
$plan = $pdo->prepare(
    "SELECT p.max_modules FROM subscriptions s
     JOIN plans p ON p.id = s.plan_id
     WHERE s.client_id = ? AND s.status = 'active' LIMIT 1"
);
$plan->execute([$client_id]);
$plan = $plan->fetch();
$max = $plan ? (int)$plan['max_modules'] : 1;

$active_count_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM treatment_modules WHERE client_id = ? AND status = 'active'"
);
$active_count_stmt->execute([$client_id]);
$active_count = (int)$active_count_stmt->fetchColumn();

if ($active_count >= $max) {
    flash('error', 'Modül limitine ulaşıldı. Eklenti satın alarak limit artırabilirsiniz.');
    redirect('/musteriler/moduller/?client_id=' . $client_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name       = trim(post('name'));
    $prompt     = trim(post('prompt'));
    $sort_order = (int)post('sort_order');

    if (!$name)   $errors[] = 'Modül adı zorunludur.';
    if (!$prompt) $errors[] = 'Prompt içeriği zorunludur.';

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO treatment_modules (client_id, name, prompt, status, sort_order, created_by)
             VALUES (?, ?, ?, 'active', ?, ?)"
        );
        $stmt->execute([$client_id, $name, $prompt, $sort_order, $_SESSION['admin_id']]);
        flash('success', '"' . $name . '" modülü eklendi.');
        redirect('/musteriler/moduller/?client_id=' . $client_id);
    }
}

require_once __DIR__ . '/../../includes/layout_header.php';
?>

<div class="mb-3">
  <a href="/musteriler/moduller/?client_id=<?= $client_id ?>" class="text-muted text-decoration-none small">
    <i class="bi bi-arrow-left"></i> Tedavi Modülleri
  </a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">Yeni Modül — <?= e($client['name']) ?></div>
      <div class="card-body">
        <form method="POST">
          <?= csrf_field() ?>
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label">Modül Adı <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control"
                     value="<?= e(post('name')) ?>"
                     placeholder="Diş, Saç Ekimi, Estetik..." required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Sıra No</label>
              <input type="number" name="sort_order" class="form-control"
                     value="<?= e(post('sort_order', '0')) ?>" min="0">
              <div class="form-text">Düşük = önce yüklenir.</div>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">Prompt İçeriği <span class="text-danger">*</span></label>
            <textarea name="prompt" class="form-control" rows="16"
                      placeholder="Bu modülde AI'ın nasıl davranacağını tanımlayan prompt metni..."
                      style="font-family: monospace; font-size: 13px;"><?= e(post('prompt')) ?></textarea>
            <div class="form-text">Core rules ile birleşerek AI'a iletilir. Klinik adı, uzmanlık alanı, hizmetler, fiyat politikası gibi bilgileri buraya ekleyebilirsiniz.</div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Kaydet</button>
            <a href="/musteriler/moduller/?client_id=<?= $client_id ?>" class="btn btn-outline-secondary">İptal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
