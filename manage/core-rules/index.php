<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();

$page_title  = 'Core Rules';
$active_menu = 'core-rules';
$pdo = db();

// Aktif kural
$active = $pdo->query(
    "SELECT * FROM core_rules WHERE status = 'active' ORDER BY id DESC LIMIT 1"
)->fetch();

// Tüm versiyonlar
$versions = $pdo->query(
    "SELECT cr.id, cr.version, cr.status, cr.created_at, a.name AS created_by_name
     FROM core_rules cr
     LEFT JOIN admin_users a ON a.id = cr.created_by
     ORDER BY cr.id DESC"
)->fetchAll();

// Yeni versiyon kaydet
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $version = trim(post('version'));
    $content = trim(post('content'));

    if (!$version) $errors[] = 'Versiyon zorunludur.';
    if (!$content) $errors[] = 'İçerik zorunludur.';

    // Versiyon benzersiz mi?
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM core_rules WHERE version = ?");
        $check->execute([$version]);
        if ($check->fetch()) $errors[] = 'Bu versiyon zaten mevcut.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Mevcutları arşivle
            $pdo->exec("UPDATE core_rules SET status = 'archived' WHERE status = 'active'");

            // Yeni ekle
            $ins = $pdo->prepare(
                "INSERT INTO core_rules (version, content, status, created_by)
                 VALUES (?, ?, 'active', ?)"
            );
            $ins->execute([$version, $content, $_SESSION['admin_id']]);

            $pdo->commit();
            flash('success', 'Core rules v' . $version . ' aktif edildi.');
            redirect('/core-rules/');
        } catch (Exception $ex) {
            $pdo->rollBack();
            $errors[] = 'Hata: ' . $ex->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/layout_header.php';
?>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row g-3">

  <!-- Sol: Aktif kural -->
  <div class="col-md-7">

    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Aktif Kural</span>
        <?php if ($active): ?>
          <span class="badge bg-success">v<?= e($active['version']) ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if ($active): ?>
          <pre class="bg-light p-3 rounded small" style="max-height:400px;overflow-y:auto;white-space:pre-wrap"><?= e($active['content']) ?></pre>
          <div class="text-muted small mt-2">
            Eklenme: <?= fmt_datetime($active['created_at']) ?>
          </div>
        <?php else: ?>
          <div class="text-muted">Henüz aktif kural yok.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Versiyon geçmişi -->
    <div class="card">
      <div class="card-header">Versiyon Geçmişi</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th class="ps-3">Versiyon</th>
              <th>Durum</th>
              <th>Ekleyen</th>
              <th>Tarih</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($versions)): ?>
              <tr><td colspan="5" class="text-muted text-center py-3 ps-3">Kayıt yok.</td></tr>
            <?php else: ?>
              <?php foreach ($versions as $v): ?>
                <tr>
                  <td class="ps-3 fw-500">v<?= e($v['version']) ?></td>
                  <td>
                    <?php if ($v['status'] === 'active'): ?>
                      <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Arşiv</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted"><?= e($v['created_by_name'] ?: '—') ?></td>
                  <td class="text-muted"><?= fmt_datetime($v['created_at']) ?></td>
                  <td>
                    <a href="/core-rules/goruntule.php?id=<?= $v['id'] ?>"
                       class="btn btn-sm btn-outline-secondary">Görüntüle</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Sağ: Yeni versiyon ekle -->
  <div class="col-md-5">
    <div class="card">
      <div class="card-header">Yeni Versiyon Ekle</div>
      <div class="card-body">
        <form method="POST" action="/core-rules/">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Versiyon <span class="text-danger">*</span></label>
            <input type="text" name="version" class="form-control"
                   value="<?= e(post('version')) ?>" placeholder="1.0, 1.1, 2.0 ...">
          </div>
          <div class="mb-3">
            <label class="form-label">İçerik <span class="text-danger">*</span></label>
            <textarea name="content" class="form-control" rows="12"
                      placeholder="AI davranış kuralları..."><?= e(post('content')) ?></textarea>
          </div>
          <div class="alert alert-warning small py-2">
            Kaydedilince mevcut aktif kural arşive alınır.
          </div>
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-lg"></i> Aktif Et
          </button>
        </form>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
