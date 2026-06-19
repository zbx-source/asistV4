<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

auth_check();

$active_menu = 'core-rules';
$pdo = db();

$id = (int)get('id');
if (!$id) redirect('/core-rules/');

$rule = $pdo->prepare("SELECT cr.*, a.name AS created_by_name FROM core_rules cr LEFT JOIN admin_users a ON a.id = cr.created_by WHERE cr.id = ?");
$rule->execute([$id]);
$rule = $rule->fetch();
if (!$rule) redirect('/core-rules/');

$page_title = 'Core Rules v' . $rule['version'];

require_once __DIR__ . '/../includes/layout_header.php';
?>

<div class="mb-3 d-flex justify-content-between align-items-center">
  <a href="/core-rules/" class="text-muted text-decoration-none small">
    <i class="bi bi-arrow-left"></i> Core Rules
  </a>
  <?php if ($rule['status'] === 'active'): ?>
    <span class="badge bg-success">Aktif</span>
  <?php else: ?>
    <span class="badge bg-secondary">Arşiv</span>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between">
    <span>v<?= e($rule['version']) ?></span>
    <span class="text-muted small"><?= fmt_datetime($rule['created_at']) ?> — <?= e($rule['created_by_name'] ?: '—') ?></span>
  </div>
  <div class="card-body">
    <pre class="bg-light p-3 rounded small" style="white-space:pre-wrap"><?= e($rule['content']) ?></pre>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
