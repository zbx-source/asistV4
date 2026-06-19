<?php
// PORTAL — portal/proaktif-mesaj.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

auth_check();
if (!client_has('proactive_msg')) redirect('/dashboard.php');

$page_title  = 'Proaktif Mesaj';
$active_menu = 'proaktif-mesaj';

$pdo = db();
$cid = client_id();

// Son 30 günde iletişim kurulan ama 24 saatten uzun süredir mesaj almayan hastalar
// (WhatsApp 24 saat kuralı — template mesaj gerektirir)
$patients = $pdo->prepare(
    "SELECT p.id, p.name, p.phone, p.language, p.country_code, p.last_contact,
            c.id AS conv_id, c.status AS conv_status, c.template_sent, c.template_replied,
            (SELECT MAX(m.sent_at) FROM messages m WHERE m.conversation_id = c.id) AS last_msg
     FROM patients p
     JOIN conversations c ON c.patient_id = p.id AND c.client_id = p.client_id
     WHERE p.client_id = ?
       AND c.id = (SELECT MAX(c2.id) FROM conversations c2 WHERE c2.patient_id = p.id AND c2.client_id = ?)
     ORDER BY p.last_contact DESC
     LIMIT 100"
);
$patients->execute([$cid, $cid]);
$patients = $patients->fetchAll();

// 24 saat kontrolü
$now = time();
foreach ($patients as &$p) {
    $last = strtotime($p['last_msg'] ?? $p['last_contact']);
    $p['hours_since'] = $last ? round(($now - $last) / 3600) : 999;
    $p['needs_template'] = $p['hours_since'] > 24;
}
unset($p);

require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="mb-3">
  <h6 class="mb-1">Proaktif Mesaj</h6>
  <div class="text-muted small">Son iletişim kurduğunuz hastalar. 24 saatten eski konuşmalarda template mesaj gerekir.</div>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th class="ps-3">Hasta</th>
          <th>Telefon</th>
          <th>Dil</th>
          <th>Son Mesaj</th>
          <th>Durum</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($patients)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Henüz hasta kaydı yok.</td></tr>
        <?php else: ?>
          <?php foreach ($patients as $p): ?>
            <tr>
              <td class="ps-3 fw-500"><?= e($p['name'] ?: '—') ?></td>
              <td class="text-muted"><?= e($p['phone']) ?></td>
              <td><?= $p['language'] ? lang_flag($p['language']) . ' ' . strtoupper(e($p['language'])) : '—' ?></td>
              <td class="text-muted small">
                <?php if ($p['hours_since'] < 999): ?>
                  <?= $p['hours_since'] ?> saat önce
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td>
                <?php if ($p['needs_template']): ?>
                  <span class="badge bg-warning text-dark">Template gerekli</span>
                <?php else: ?>
                  <span class="badge bg-success">Açık pencere</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!$p['needs_template']): ?>
                  <a href="/konusma.php?id=<?= $p['conv_id'] ?>" class="btn btn-sm btn-outline-primary">Mesaj Yaz</a>
                <?php else: ?>
                  <button class="btn btn-sm btn-outline-secondary" disabled title="Template mesaj entegrasyonu yakında">
                    Template Gönder
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
