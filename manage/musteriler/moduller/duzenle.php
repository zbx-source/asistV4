<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

auth_check();

$active_menu = 'musteriler';
$pdo    = db();
$errors = [];

$id        = (int)get('id');
$client_id = (int)get('client_id');
if (!$id || !$client_id) redirect('/musteriler/');

$module = $pdo->prepare("SELECT * FROM treatment_modules WHERE id = ? AND client_id = ?");
$module->execute([$id, $client_id]);
$module = $module->fetch();
if (!$module) redirect('/musteriler/');

$client = $pdo->prepare("SELECT id, name FROM clients WHERE id = ?");
$client->execute([$client_id]);
$client = $client->fetch();

$page_title = 'Modül Düzenle: ' . $module['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $action = post('action');

    if ($action === 'archive') {
        $pdo->prepare("UPDATE treatment_modules SET status = 'archived', updated_at = NOW() WHERE id = ?")
            ->execute([$id]);
        flash('success', '"' . $module['name'] . '" arşivlendi.');
        redirect('/musteriler/moduller/?client_id=' . $client_id);
    }

    if ($action === 'activate') {
        $pdo->prepare("UPDATE treatment_modules SET status = 'active', updated_at = NOW() WHERE id = ?")
            ->execute([$id]);
        flash('success', '"' . $module['name'] . '" aktifleştirildi.');
        redirect('/musteriler/moduller/?client_id=' . $client_id);
    }

    $name       = trim(post('name'));
    $prompt     = trim(post('prompt'));
    $sort_order = (int)post('sort_order');

    if (!$name)   $errors[] = 'Modül adı zorunludur.';
    if (!$prompt) $errors[] = 'Prompt içeriği zorunludur.';

    if (empty($errors)) {
        $pdo->prepare(
            "UPDATE treatment_modules SET name=?, prompt=?, sort_order=?, updated_at=NOW() WHERE id=?"
        )->execute([$name, $prompt, $sort_order, $id]);
        flash('success', 'Modül güncellendi.');
        redirect('/musteriler/moduller/?client_id=' . $client_id);
    }

    $module = array_merge($module, compact('name', 'prompt', 'sort_order'));
}

require_once __DIR__ . '/../../includes/layout_header.php';
?>

<div class="mb-3 d-flex justify-content-between align-items-center">
  <a href="/musteriler/moduller/?client_id=<?= $client_id ?>" class="text-muted text-decoration-none small">
    <i class="bi bi-arrow-left"></i> Tedavi Modülleri
  </a>
  <div class="d-flex gap-2">
    <?php if ($module['status'] === 'active'): ?>
      <form method="POST" onsubmit="return confirm('Arşivlenecek. Emin misiniz?')">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="archive">
        <button type="submit" class="btn btn-sm btn-outline-warning">
          <i class="bi bi-archive"></i> Arşivle
        </button>
      </form>
    <?php else: ?>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="activate">
        <button type="submit" class="btn btn-sm btn-outline-success">
          <i class="bi bi-check-circle"></i> Aktifleştir
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span><?= e($module['name']) ?></span>
        <?= $module['status'] === 'active' ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Arşiv</span>' ?>
      </div>
      <div class="card-body">
        <form method="POST" action="/musteriler/moduller/duzenle.php?id=<?= $id ?>&client_id=<?= $client_id ?>">
          <?= csrf_field() ?>
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label">Modül Adı <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control"
                     value="<?= e($module['name']) ?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Sıra No</label>
              <input type="number" name="sort_order" class="form-control"
                     value="<?= e($module['sort_order']) ?>" min="0">
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">Prompt İçeriği <span class="text-danger">*</span></label>
            <textarea name="prompt" class="form-control" rows="18"
                      style="font-family: monospace; font-size: 13px;"><?= e($module['prompt']) ?></textarea>
            <div class="form-text">Son güncelleme: <?= fmt_datetime($module['updated_at'] ?: $module['created_at']) ?></div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Güncelle</button>
            <a href="/musteriler/moduller/?client_id=<?= $client_id ?>" class="btn btn-outline-secondary">İptal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/layout_footer.php'; ?>
