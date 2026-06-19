<?php
// PORTAL — portal/capraz-tedavi.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

auth_check();
if (!client_has('cross_treatment')) redirect('/dashboard.php');

$page_title  = 'Çapraz Tedavi Fırsatları';
$active_menu = 'capraz-tedavi';

$pdo = db();
$cid = client_id();

// Birden fazla tedavi modülü olan hastaları bul
// Farklı modüllerde konuşması olan hasta = çapraz tedavi potansiyeli
$cross = $pdo->prepare(
    "SELECT p.id, p.name, p.phone, p.language, p.country_code,
            COUNT(DISTINCT c.module_id) AS module_count,
            GROUP_CONCAT(DISTINCT tm.name SEPARATOR ', ') AS modules,
            MAX(c.started_at) AS last_conv
     FROM patients p
     JOIN conversations c ON c.patient_id = p.id AND c.client_id = ?
     LEFT JOIN treatment_modules tm ON tm.id = c.module_id
     WHERE c.module_id IS NOT NULL
     GROUP BY p.id
     HAVING module_count >= 2
     ORDER BY last_conv DESC"
);
$cross->execute([$cid]);
$cross_patients = $cross->fetchAll();

// Tek modülde olan ama potansiyel çapraz tedavi adayları
// (birden fazla konuşması olan, farklı modüle yönlendirilebilecek hastalar)
$potential = $pdo->prepare(
    "SELECT p.id, p.name, p.phone, p.language,
            COUNT(c.id) AS conv_count,
            tm.name AS current_module,
            MAX(c.started_at) AS last_conv
     FROM patients p
     JOIN conversations c ON c.patient_id = p.id AND c.client_id = ?
     LEFT JOIN treatment_modules tm ON tm.id = c.module_id
     WHERE c.module_id IS NOT NULL
     GROUP BY p.id, tm.id
     HAVING conv_count >= 2
       AND p.id NOT IN (
         SELECT p2.id FROM patients p2
         JOIN conversations c2 ON c2.patient_id = p2.id AND c2.client_id = ?
         WHERE c2.module_id IS NOT NULL
         GROUP BY p2.id
         HAVING COUNT(DISTINCT c2.module_id) >= 2
       )
     ORDER BY last_conv DESC
     LIMIT 50"
);
$potential->execute([$cid, $cid]);
$potential_patients = $potential->fetchAll();

require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="mb-3">
  <h6 class="mb-1">Çapraz Tedavi Fırsatları</h6>
  <div class="text-muted small">Birden fazla tedavi alanıyla ilgilenen veya potansiyel çapraz tedavi adayı hastalar.</div>
</div>

<!-- Mevcut çapraz tedavi hastaları -->
<div class="card mb-3">
  <div class="card-header">Birden Fazla Tedavi Alanı (<?= count($cross_patients) ?>)</div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th class="ps-3">Hasta</th>
          <th>Dil</th>
          <th>Tedavi Alanları</th>
          <th>Son Konuşma</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($cross_patients)): ?>
          <tr><td colspan="4" class="text-center text-muted py-3">Henüz çapraz tedavi hastası yok.</td></tr>
        <?php else: ?>
          <?php foreach ($cross_patients as $cp): ?>
            <tr>
              <td class="ps-3 fw-500"><?= e($cp['name'] ?: $cp['phone']) ?></td>
              <td><?= $cp['language'] ? lang_flag($cp['language']) : '—' ?></td>
              <td class="text-muted small"><?= e($cp['modules']) ?></td>
              <td class="text-muted small"><?= time_ago($cp['last_conv']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Potansiyel adaylar -->
<div class="card">
  <div class="card-header">Potansiyel Adaylar (<?= count($potential_patients) ?>)</div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th class="ps-3">Hasta</th>
          <th>Mevcut Alan</th>
          <th>Konuşma Sayısı</th>
          <th>Son Konuşma</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($potential_patients)): ?>
          <tr><td colspan="4" class="text-center text-muted py-3">Potansiyel aday bulunamadı.</td></tr>
        <?php else: ?>
          <?php foreach ($potential_patients as $pp): ?>
            <tr>
              <td class="ps-3 fw-500"><?= e($pp['name'] ?: $pp['phone']) ?></td>
              <td class="text-muted"><?= e($pp['current_module'] ?: '—') ?></td>
              <td class="fw-500"><?= $pp['conv_count'] ?></td>
              <td class="text-muted small"><?= time_ago($pp['last_conv']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
