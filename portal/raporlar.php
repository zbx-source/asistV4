<?php
// PORTAL — portal/raporlar.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

auth_check();
if (!client_has('reporting')) redirect('/dashboard.php');

$page_title  = 'Raporlar';
$active_menu = 'raporlar';

$pdo = db();
$cid = client_id();

// Tarih filtresi
$month = get('month', date('Y-m'));
$year  = (int)substr($month, 0, 4);
$mon   = (int)substr($month, 5, 2);

$start = "$year-$mon-01";
$end   = date('Y-m-t', strtotime($start));

// Bu ay toplam konuşma
$total_conv = $pdo->prepare(
    "SELECT COUNT(*) FROM conversations WHERE client_id = ? AND started_at BETWEEN ? AND ?"
);
$total_conv->execute([$cid, $start, "$end 23:59:59"]);
$total_conv = (int)$total_conv->fetchColumn();

// Tekil hasta sayısı
$unique_patients = $pdo->prepare(
    "SELECT COUNT(DISTINCT patient_id) FROM conversations WHERE client_id = ? AND started_at BETWEEN ? AND ?"
);
$unique_patients->execute([$cid, $start, "$end 23:59:59"]);
$unique_patients = (int)$unique_patients->fetchColumn();

// Toplam mesaj
$total_msg = $pdo->prepare(
    "SELECT COUNT(*) FROM messages m
     JOIN conversations c ON c.id = m.conversation_id
     WHERE c.client_id = ? AND m.sent_at BETWEEN ? AND ?"
);
$total_msg->execute([$cid, $start, "$end 23:59:59"]);
$total_msg = (int)$total_msg->fetchColumn();

// Durum dağılımı
$status_dist = $pdo->prepare(
    "SELECT status, COUNT(*) AS cnt FROM conversations
     WHERE client_id = ? AND started_at BETWEEN ? AND ?
     GROUP BY status ORDER BY cnt DESC"
);
$status_dist->execute([$cid, $start, "$end 23:59:59"]);
$status_dist = $status_dist->fetchAll();

// Dil dağılımı
$lang_dist = $pdo->prepare(
    "SELECT COALESCE(p.language, 'bilinmiyor') AS lang, COUNT(*) AS cnt
     FROM conversations c
     JOIN patients p ON p.id = c.patient_id
     WHERE c.client_id = ? AND c.started_at BETWEEN ? AND ?
     GROUP BY p.language ORDER BY cnt DESC
     LIMIT 10"
);
$lang_dist->execute([$cid, $start, "$end 23:59:59"]);
$lang_dist = $lang_dist->fetchAll();

// Ülke dağılımı
$country_dist = $pdo->prepare(
    "SELECT COALESCE(p.country_code, '??') AS cc, COUNT(*) AS cnt
     FROM conversations c
     JOIN patients p ON p.id = c.patient_id
     WHERE c.client_id = ? AND c.started_at BETWEEN ? AND ?
     GROUP BY p.country_code ORDER BY cnt DESC
     LIMIT 10"
);
$country_dist->execute([$cid, $start, "$end 23:59:59"]);
$country_dist = $country_dist->fetchAll();

// Günlük konuşma trendi
$daily = $pdo->prepare(
    "SELECT DATE(started_at) AS d, COUNT(*) AS cnt
     FROM conversations
     WHERE client_id = ? AND started_at BETWEEN ? AND ?
     GROUP BY DATE(started_at) ORDER BY d"
);
$daily->execute([$cid, $start, "$end 23:59:59"]);
$daily = $daily->fetchAll();

require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="mb-0">Raporlar</h6>
  <form method="GET" class="d-flex gap-2">
    <input type="month" name="month" class="form-control form-control-sm" value="<?= e($month) ?>">
    <button class="btn btn-sm btn-primary">Filtrele</button>
  </form>
</div>

<!-- Özet kartları -->
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body text-center">
        <div class="fs-3 fw-bold"><?= $total_conv ?></div>
        <div class="text-muted small">Toplam Konuşma</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body text-center">
        <div class="fs-3 fw-bold"><?= $unique_patients ?></div>
        <div class="text-muted small">Tekil Hasta</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body text-center">
        <div class="fs-3 fw-bold"><?= $total_msg ?></div>
        <div class="text-muted small">Toplam Mesaj</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">

  <!-- Durum dağılımı -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Konuşma Durumları</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <tbody>
            <?php foreach ($status_dist as $s): ?>
              <tr>
                <td class="ps-3"><?= conv_status_badge($s['status']) ?></td>
                <td class="text-end pe-3 fw-500"><?= $s['cnt'] ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($status_dist)): ?>
              <tr><td class="text-muted text-center py-3" colspan="2">Veri yok</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Dil dağılımı -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Dil Dağılımı</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <tbody>
            <?php foreach ($lang_dist as $l): ?>
              <tr>
                <td class="ps-3"><?= lang_flag($l['lang']) ?> <?= strtoupper(e($l['lang'])) ?></td>
                <td class="text-end pe-3 fw-500"><?= $l['cnt'] ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($lang_dist)): ?>
              <tr><td class="text-muted text-center py-3" colspan="2">Veri yok</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Ülke dağılımı -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Ülke Dağılımı</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <tbody>
            <?php foreach ($country_dist as $c): ?>
              <tr>
                <td class="ps-3"><?= e($c['cc']) ?></td>
                <td class="text-end pe-3 fw-500"><?= $c['cnt'] ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($country_dist)): ?>
              <tr><td class="text-muted text-center py-3" colspan="2">Veri yok</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Günlük trend -->
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Günlük Konuşma</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <tbody>
            <?php foreach ($daily as $d): ?>
              <tr>
                <td class="ps-3 text-muted"><?= date('d.m', strtotime($d['d'])) ?></td>
                <td class="pe-3">
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px">
                      <div class="progress-bar bg-success" style="width:<?= $total_conv > 0 ? round($d['cnt'] / $total_conv * 100) : 0 ?>%"></div>
                    </div>
                    <span class="fw-500 small"><?= $d['cnt'] ?></span>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($daily)): ?>
              <tr><td class="text-muted text-center py-3" colspan="2">Veri yok</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
