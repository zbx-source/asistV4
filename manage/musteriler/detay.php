<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/features.php';

auth_check();

$active_menu = 'musteriler';
$pdo = db();

$id = (int)get('id');
if (!$id) redirect('/musteriler/');

// Müşteri
$client = $pdo->prepare(
    "SELECT * FROM clients WHERE id = ?"
);
$client->execute([$id]);
$client = $client->fetch();
if (!$client) redirect('/musteriler/');

$page_title = e($client['name']);

// Aktif abonelik
$sub = $pdo->prepare(
    "SELECT s.*, p.name AS plan_name, p.monthly_quota
     FROM subscriptions s
     JOIN plans p ON p.id = s.plan_id
     WHERE s.client_id = ? AND s.status = 'active'
     ORDER BY s.created_at DESC LIMIT 1"
);
$sub->execute([$id]);
$sub = $sub->fetch();

// Kota (bu ay)
$quota = $pdo->prepare(
    "SELECT q.used_count, q.warning_1_sent, q.warning_2_sent
     FROM quota_usage q
     WHERE q.client_id = ? AND q.year = YEAR(NOW()) AND q.month = MONTH(NOW())"
);
$quota->execute([$id]);
$quota = $quota->fetch();

// Token
$token = $pdo->prepare(
    "SELECT * FROM client_tokens WHERE client_id = ?"
);
$token->execute([$id]);
$token = $token->fetch();

// Portal kullanıcıları
$portal_users = $pdo->prepare(
    "SELECT id, name, email, role, status, last_login_at
     FROM portal_users WHERE client_id = ? ORDER BY created_at"
);
$portal_users->execute([$id]);
$portal_users = $portal_users->fetchAll();

// Tedavi modülleri
$modules = $pdo->prepare(
    "SELECT id, name, status, created_at FROM treatment_modules
     WHERE client_id = ? ORDER BY sort_order, id"
);
$modules->execute([$id]);
$modules = $modules->fetchAll();

// Son ödemeler
$payments = $pdo->prepare(
    "SELECT * FROM payments WHERE client_id = ? ORDER BY payment_date DESC LIMIT 5"
);
$payments->execute([$id]);
$payments = $payments->fetchAll();

// === Feature (Modül Yetkileri) verileri ===
$all_features = $pdo->query(
    "SELECT f.code, f.name, f.category, f.requires, f.is_addon, f.sort_order
     FROM features f WHERE f.status = 'active' ORDER BY f.sort_order"
)->fetchAll();

$active_features = load_client_features($id);

$plan_defaults = [];
if ($sub) {
    $pf = $pdo->prepare("SELECT feature_code FROM plan_features WHERE plan_id = ?");
    $pf->execute([$sub['plan_id']]);
    $plan_defaults = $pf->fetchAll(PDO::FETCH_COLUMN);
}

$overrides = [];
$ov = $pdo->prepare(
    "SELECT cf.feature_code, cf.state, cf.enabled_at, au.name AS enabled_by_name
     FROM client_features cf
     LEFT JOIN admin_users au ON au.id = cf.enabled_by
     WHERE cf.client_id = ?"
);
$ov->execute([$id]);
foreach ($ov->fetchAll() as $o) {
    $overrides[$o['feature_code']] = $o;
}

require_once __DIR__ . '/../includes/layout_header.php';
?>

<div class="mb-3 d-flex justify-content-between align-items-center">
  <a href="/musteriler/" class="text-muted text-decoration-none small">
    <i class="bi bi-arrow-left"></i> Müşteriler
  </a>
  <a href="/musteriler/duzenle.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
    <i class="bi bi-pencil"></i> Düzenle
  </a>
</div>

<div class="row g-3">

  <!-- Sol: Müşteri bilgileri -->
  <div class="col-md-8">

    <div class="card mb-3">
      <div class="card-header">Genel Bilgiler</div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-6">
            <div class="text-muted small">Müşteri Adı</div>
            <div><?= e($client['name']) ?></div>
          </div>
          <div class="col-md-3">
            <div class="text-muted small">Tip</div>
            <div><?= client_type_label($client['type']) ?></div>
          </div>
          <div class="col-md-3">
            <div class="text-muted small">Durum</div>
            <div><?= status_badge($client['status']) ?></div>
          </div>
          <?php if ($client['address']): ?>
          <div class="col-12 mt-2">
            <div class="text-muted small">Adres</div>
            <div><?= e($client['address']) ?></div>
          </div>
          <?php endif; ?>
          <div class="col-md-4 mt-2">
            <div class="text-muted small">Vergi No</div>
            <div><?= e($client['tax_no'] ?: '—') ?></div>
          </div>
          <div class="col-md-4 mt-2">
            <div class="text-muted small">Vergi Dairesi</div>
            <div><?= e($client['tax_office'] ?: '—') ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Lisans No</div>
            <div><?= e($client['license_no'] ?: '—') ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Şehir</div>
            <div><?= e($client['city'] ?: '—') ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">İletişim</div>
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-6">
            <div class="text-muted small">Yetkili 1</div>
            <div><?= e($client['authorized_person_1'] ?: '—') ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small">Yetkili 2</div>
            <div><?= e($client['authorized_person_2'] ?: '—') ?></div>
          </div>
          <div class="col-md-4 mt-2">
            <div class="text-muted small">Telefon 1</div>
            <div><?= e($client['contact_phone'] ?: '—') ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Telefon 2</div>
            <div><?= e($client['phone_2'] ?: '—') ?></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">E-posta</div>
            <div><?= e($client['contact_email'] ?: '—') ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Portal Kullanıcıları -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Portal Kullanıcıları</span>
        <a href="/musteriler/portal-kullanicilari/?client_id=<?= $id ?>" class="btn btn-sm btn-outline-primary">Yönet</a>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th class="ps-3">Ad</th>
              <th>E-posta</th>
              <th>Rol</th>
              <th>Durum</th>
              <th>Son Giriş</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($portal_users)): ?>
              <tr><td colspan="5" class="text-muted text-center py-3 ps-3">Henüz kullanıcı yok.</td></tr>
            <?php else: ?>
              <?php foreach ($portal_users as $u): ?>
                <tr>
                  <td class="ps-3"><?= e($u['name']) ?></td>
                  <td class="text-muted"><?= e($u['email']) ?></td>
                  <td><?= $u['role'] === 'coordinator' ? 'Koordinatör' : 'Kullanıcı' ?></td>
                  <td><?= status_badge($u['status']) ?></td>
                  <td class="text-muted"><?= fmt_datetime($u['last_login_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modül Yetkileri -->
    <div class="card mb-3">
      <div class="card-header">Modül Yetkileri</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th class="ps-3">Modül</th>
              <th>Kaynak</th>
              <th class="text-center" style="width:80px">Durum</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($all_features as $f):
              $code      = $f['code'];
              $is_active = in_array($code, $active_features, true);
              $in_plan   = in_array($code, $plan_defaults, true);
              $override  = $overrides[$code] ?? null;
              $requires  = $f['requires'];
              $is_core   = ($f['category'] === 'core');
              $dep_locked = $requires && !in_array($requires, $active_features, true);

              if ($is_core) {
                  $source_label = '<span class="text-muted">Temel</span>';
              } elseif ($override && $override['state'] === 'on') {
                  $source_label = '<span class="text-success">Eklenti — '
                      . e($override['enabled_by_name'] ?? '?') . ', '
                      . date('d.m.Y', strtotime($override['enabled_at']))
                      . '</span>';
              } elseif ($override && $override['state'] === 'off') {
                  $source_label = '<span class="text-danger">Kapatıldı</span>';
              } elseif ($in_plan) {
                  $source_label = '<span class="text-primary">Pakete dahil</span>';
              } else {
                  $source_label = '<span class="text-muted">—</span>';
              }
            ?>
              <tr>
                <td class="ps-3">
                  <?= e($f['name']) ?>
                  <?php if ($dep_locked): ?>
                    <span class="badge bg-secondary ms-1" title="<?= e($requires) ?> gerektirir">Kilitli</span>
                  <?php endif; ?>
                </td>
                <td class="small"><?= $source_label ?></td>
                <td class="text-center">
                  <?php if ($is_core): ?>
                    <span class="text-success"><i class="bi bi-check-circle-fill"></i></span>
                  <?php elseif ($dep_locked && !$is_active): ?>
                    <span class="text-muted"><i class="bi bi-lock-fill"></i></span>
                  <?php else: ?>
                    <div class="form-check form-switch d-inline-block mb-0">
                      <input class="form-check-input feature-toggle" type="checkbox"
                             data-code="<?= $code ?>"
                             <?= $is_active ? 'checked' : '' ?>>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tedavi Modülleri -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Tedavi Modülleri</span>
        <a href="/musteriler/moduller/?client_id=<?= $id ?>" class="btn btn-sm btn-outline-primary">Yönet</a>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th class="ps-3">Modül Adı</th>
              <th>Durum</th>
              <th>Oluşturulma</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($modules)): ?>
              <tr><td colspan="3" class="text-muted text-center py-3 ps-3">Henüz modül yok.</td></tr>
            <?php else: ?>
              <?php foreach ($modules as $m): ?>
                <tr>
                  <td class="ps-3"><?= e($m['name']) ?></td>
                  <td><?= $m['status'] === 'active' ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Arşiv</span>' ?></td>
                  <td class="text-muted"><?= fmt_date($m['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Son Ödemeler -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Son Ödemeler</span>
        <a href="/odemeler/ekle.php?client_id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
          + Ödeme Ekle
        </a>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead>
            <tr>
              <th class="ps-3">Tarih</th>
              <th>Tutar</th>
              <th>Yöntem</th>
              <th>Not</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($payments)): ?>
              <tr><td colspan="4" class="text-muted text-center py-3 ps-3">Henüz ödeme kaydı yok.</td></tr>
            <?php else: ?>
              <?php foreach ($payments as $p): ?>
                <tr>
                  <td class="ps-3"><?= fmt_date($p['payment_date']) ?></td>
                  <td><?= fmt_number($p['amount']) ?> <?= e($p['currency']) ?></td>
                  <td class="text-muted"><?= e($p['method'] ?: '—') ?></td>
                  <td class="text-muted"><?= e($p['note'] ?: '—') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- AI Kullanım -->
    <?php
    $ai_range = get('ai_range', 'today');
    $ai_from  = get('ai_from', '');
    $ai_to    = get('ai_to', '');
    $today_d  = date('Y-m-d');
    switch ($ai_range) {
        case 'yesterday': $ai_start = date('Y-m-d', strtotime('-1 day')); $ai_end_d = $ai_start; break;
        case '7days':     $ai_start = date('Y-m-d', strtotime('-6 days')); $ai_end_d = $today_d; break;
        case '30days':    $ai_start = date('Y-m-d', strtotime('-29 days')); $ai_end_d = $today_d; break;
        case 'custom':    $ai_start = $ai_from ?: $today_d; $ai_end_d = $ai_to ?: $today_d; break;
        default:          $ai_start = $today_d; $ai_end_d = $today_d; break;
    }
    $ai_stats = $pdo->prepare(
        "SELECT type, COUNT(*) AS calls, SUM(prompt_tokens) AS prompt_t, SUM(completion_tokens) AS comp_t, SUM(total_tokens) AS total_t
         FROM ai_usage_log WHERE client_id = ? AND DATE(created_at) BETWEEN ? AND ? GROUP BY type"
    );
    $ai_stats->execute([$id, $ai_start, $ai_end_d]);
    $ai_rows = $ai_stats->fetchAll();
    $ai_total_tokens = 0; $ai_total_calls = 0;
    foreach ($ai_rows as $r) { $ai_total_tokens += (int)$r['total_t']; $ai_total_calls += (int)$r['calls']; }
    $ai_daily = $pdo->prepare(
        "SELECT DATE(created_at) AS d, COUNT(*) AS calls, SUM(total_tokens) AS tokens
         FROM ai_usage_log WHERE client_id = ? AND DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY d DESC"
    );
    $ai_daily->execute([$id, $ai_start, $ai_end_d]);
    $ai_daily_rows = $ai_daily->fetchAll();
    ?>
    <div class="card">
      <div class="card-header">AI Kullanım</div>
      <div class="card-body">
        <div class="d-flex gap-2 align-items-center flex-wrap mb-3">
          <div class="btn-group btn-group-sm">
            <a href="?id=<?= $id ?>&ai_range=today" class="btn <?= $ai_range === 'today' ? 'btn-primary' : 'btn-outline-secondary' ?>">Bugün</a>
            <a href="?id=<?= $id ?>&ai_range=yesterday" class="btn <?= $ai_range === 'yesterday' ? 'btn-primary' : 'btn-outline-secondary' ?>">Dün</a>
            <a href="?id=<?= $id ?>&ai_range=7days" class="btn <?= $ai_range === '7days' ? 'btn-primary' : 'btn-outline-secondary' ?>">Son 7 gün</a>
            <a href="?id=<?= $id ?>&ai_range=30days" class="btn <?= $ai_range === '30days' ? 'btn-primary' : 'btn-outline-secondary' ?>">Son 30 gün</a>
          </div>
          <form method="GET" class="d-flex gap-1 align-items-center">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="ai_range" value="custom">
            <input type="date" name="ai_from" class="form-control form-control-sm" value="<?= e($ai_from ?: $ai_start) ?>" style="width:140px">
            <span class="text-muted">–</span>
            <input type="date" name="ai_to" class="form-control form-control-sm" value="<?= e($ai_to ?: $ai_end_d) ?>" style="width:140px">
            <button class="btn btn-sm btn-primary">Uygula</button>
          </form>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-4 text-center">
            <div class="fs-5 fw-bold"><?= number_format($ai_total_calls) ?></div>
            <div class="text-muted small">AI Çağrı</div>
          </div>
          <div class="col-4 text-center">
            <div class="fs-5 fw-bold"><?= number_format($ai_total_tokens) ?></div>
            <div class="text-muted small">Toplam Token</div>
          </div>
          <div class="col-4 text-center">
            <div class="fs-5 fw-bold">$<?= number_format($ai_total_tokens * 0.0003 / 1000, 4) ?></div>
            <div class="text-muted small">Tahmini Maliyet</div>
          </div>
        </div>
        <?php if ($ai_rows): ?>
        <table class="table table-sm mb-3">
          <thead><tr><th class="ps-3">Tip</th><th>Çağrı</th><th>Prompt</th><th>Completion</th><th>Toplam</th></tr></thead>
          <tbody>
            <?php foreach ($ai_rows as $r): ?>
            <tr>
              <td class="ps-3"><?= $r['type'] === 'chat' ? 'Konuşma' : 'Özet' ?></td>
              <td><?= number_format($r['calls']) ?></td>
              <td class="text-muted"><?= number_format($r['prompt_t']) ?></td>
              <td class="text-muted"><?= number_format($r['comp_t']) ?></td>
              <td class="fw-500"><?= number_format($r['total_t']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
        <?php if ($ai_daily_rows): ?>
        <div class="text-muted small fw-600 mb-1">Günlük Dağılım</div>
        <table class="table table-sm mb-0">
          <tbody>
            <?php foreach ($ai_daily_rows as $d): ?>
            <tr>
              <td class="ps-3 text-muted"><?= date('d.m.Y', strtotime($d['d'])) ?></td>
              <td><?= $d['calls'] ?> çağrı</td>
              <td class="fw-500"><?= number_format($d['tokens']) ?> token</td>
              <td class="text-muted">$<?= number_format($d['tokens'] * 0.0003 / 1000, 4) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
        <?php if (!$ai_rows): ?>
        <div class="text-center text-muted py-3">Bu dönemde AI kullanımı yok.</div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- Sağ: Abonelik & Kota & Token -->
  <div class="col-md-4">

    <div class="card mb-3">
      <div class="card-header">Abonelik</div>
      <div class="card-body">
        <?php if ($sub): ?>
          <div class="mb-2">
            <div class="text-muted small">Paket</div>
            <div class="fw-500"><?= plan_label($sub['plan_name']) ?></div>
          </div>
          <div class="mb-2">
            <div class="text-muted small">Döngü</div>
            <div><?= $sub['billing_cycle'] === 'yearly' ? 'Yıllık' : 'Aylık' ?></div>
          </div>
          <div class="mb-2">
            <div class="text-muted small">Başlangıç</div>
            <div><?= fmt_date($sub['start_date']) ?></div>
          </div>
          <div class="mb-2">
            <div class="text-muted small">Bitiş</div>
            <div><?= fmt_date($sub['end_date']) ?></div>
          </div>
          <div class="mb-2">
            <div class="text-muted small">Fiyat</div>
            <div><?= $sub['price_usd'] ? fmt_number($sub['price_usd']) . ' TL' : '—' ?></div>
          </div>
          <div><?= status_badge($sub['status']) ?></div>
        <?php else: ?>
          <div class="text-muted small">Aktif abonelik yok.</div>
          <a href="/abonelikler/ekle.php?client_id=<?= $id ?>" class="btn btn-sm btn-outline-primary mt-2">
            Abonelik Ekle
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">Bu Ay Kota</div>
      <div class="card-body">
        <?php if ($sub && $quota): ?>
          <?php
            $used  = (int)$quota['used_count'];
            $limit = (int)$sub['monthly_quota'];
            $pct   = $limit > 0 ? min(100, round($used / $limit * 100)) : 0;
            $color = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success');
          ?>
          <div class="d-flex justify-content-between mb-1">
            <span class="small"><?= $used ?> / <?= $limit ?> hasta</span>
            <span class="small text-muted">%<?= $pct ?></span>
          </div>
          <div class="progress" style="height:8px">
            <div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%"></div>
          </div>
          <?php if ($quota['warning_1_sent']): ?>
            <div class="text-warning small mt-2"><i class="bi bi-exclamation-triangle"></i> 1. uyarı gönderildi</div>
          <?php endif; ?>
          <?php if ($quota['warning_2_sent']): ?>
            <div class="text-danger small"><i class="bi bi-exclamation-triangle-fill"></i> 2. uyarı gönderildi</div>
          <?php endif; ?>
        <?php else: ?>
          <div class="text-muted small">Kota verisi yok.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center"><span>WhatsApp Token</span><a href="/musteriler/token.php?client_id=<?= $id ?>" class="btn btn-sm btn-outline-primary">Yönet</a></div>
      <div class="card-body">
        <?php if ($token): ?>
          <div class="mb-2">
            <div class="text-muted small">Token</div>
            <code class="small"><?= e($token['token']) ?></code>
          </div>
          <div class="mb-2">
            <div class="text-muted small">WhatsApp No</div>
            <div><?= e($token['whatsapp_number'] ?: '—') ?></div>
          </div>
          <div>
            <div class="text-muted small">Durum</div>
            <div><?= status_badge($token['status']) ?></div>
          </div>
        <?php else: ?>
          <div class="text-muted small">Token tanımlanmamış.</div>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

<?php if ($client['notes']): ?>
<div class="card mt-3">
  <div class="card-header">Notlar</div>
  <div class="card-body text-muted"><?= nl2br(e($client['notes'])) ?></div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.feature-toggle').forEach(function(toggle) {
  toggle.addEventListener('change', function() {
    var code  = this.dataset.code;
    var state = this.checked ? 'on' : 'off';
    var el    = this;

    el.disabled = true;

    fetch('/api/toggle-feature.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        client_id: <?= $id ?>,
        feature_code: code,
        state: state
      })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.error) {
        alert(data.error);
        el.checked = !el.checked;
      } else {
        location.reload();
      }
    })
    .catch(function() {
      alert('Bağlantı hatası');
      el.checked = !el.checked;
    })
    .finally(function() {
      el.disabled = false;
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/layout_footer.php'; ?>
