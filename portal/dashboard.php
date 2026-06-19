<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

auth_check();

$page_title  = 'Inbox';
$active_menu = 'dashboard';
$cid = client_id();
$uid = portal_user_id();
$pdo = db();

// Filtreler
$status_filter = get('status', '');
$per_page = 25;
$cur_page = max(1, (int)get('page', 1));
$offset   = ($cur_page - 1) * $per_page;

$where  = ['c.client_id = ?'];
$params = [$cid];

if (!is_coordinator() && has_assignment()) {
    $where[]  = '(c.assigned_to = ? OR c.status NOT IN ("assigned"))';
    $params[] = $uid;
}

if ($status_filter) {
    $where[]  = 'c.status = ?';
    $params[] = $status_filter;
} else {
    $where[] = 'c.status != "closed"';
}

$where_sql = implode(' AND ', $where);

$cnt = $pdo->prepare("SELECT COUNT(*) FROM conversations c WHERE $where_sql");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

$list = $pdo->prepare(
    "SELECT c.id, c.status, c.started_at, c.updated_at, c.topic_summary, c.summary_text,
            p.id AS patient_id, p.phone, p.name AS patient_name, p.language, p.country_code,
            p.treatment_interest, p.pipeline_status,
            m.body AS last_message, m.sent_at AS last_message_at, m.sender_type AS last_sender,
            pu.name AS assigned_to_name
     FROM conversations c
     JOIN patients p ON p.id = c.patient_id
     LEFT JOIN messages m ON m.id = (
         SELECT id FROM messages WHERE conversation_id = c.id ORDER BY sent_at DESC LIMIT 1
     )
     LEFT JOIN portal_users pu ON pu.id = c.assigned_to
     WHERE $where_sql
     ORDER BY c.updated_at DESC
     LIMIT $per_page OFFSET $offset"
);
$list->execute($params);
$conversations = $list->fetchAll();

// Özet sayaçlar
$counters = $pdo->prepare(
    "SELECT
        SUM(CASE WHEN c.status != 'closed' THEN 1 ELSE 0 END) AS active,
        SUM(CASE WHEN c.status = 'pending_takeover' THEN 1 ELSE 0 END) AS waiting,
        SUM(CASE WHEN p.pipeline_status = 'photo_pending' THEN 1 ELSE 0 END) AS photo,
        SUM(CASE WHEN DATE(c.started_at) = CURDATE() THEN 1 ELSE 0 END) AS today
     FROM conversations c
     JOIN patients p ON p.id = c.patient_id
     WHERE c.client_id = ?"
);
$counters->execute([$cid]);
$cnt_row = $counters->fetch();

// Kota
$quota_row = $pdo->prepare(
    "SELECT q.used_count, q.warning_1_sent, pl.monthly_quota
     FROM quota_usage q
     JOIN subscriptions s ON s.client_id = q.client_id AND s.status = 'active'
     JOIN plans pl ON pl.id = s.plan_id
     WHERE q.client_id = ? AND q.year = YEAR(NOW()) AND q.month = MONTH(NOW())"
);
$quota_row->execute([$cid]);
$quota = $quota_row->fetch();

$used = $limit = $quota_pct = 0;
$show_quota_warning = false;
if ($quota) {
    $used  = (int)$quota['used_count'];
    $limit = (int)$quota['monthly_quota'];
    $quota_pct = $limit > 0 ? min(100, round($used / $limit * 100)) : 0;
    if ($quota_pct >= 70 && !$quota['warning_1_sent']) {
        $pdo->prepare("UPDATE quota_usage SET warning_1_sent = 1 WHERE client_id = ? AND year = YEAR(NOW()) AND month = MONTH(NOW())")->execute([$cid]);
    }
    $show_quota_warning = ($quota_pct >= 70);
}

// Hekim listesi (Hastane paketi)
$hekimler = [];
if (client_has('assignment') && is_coordinator()) {
    $hq = $pdo->prepare("SELECT id, name FROM portal_users WHERE client_id = ? AND status = 'active' AND role = 'user' ORDER BY name");
    $hq->execute([$cid]);
    $hekimler = $hq->fetchAll();
}

$pipe_labels = [
    'new'           => ['Yeni',                'secondary'],
    'photo_pending' => ['Fotoğraf Bekleniyor', 'warning'],
    'price_given'   => ['Fiyat Verildi',       'info'],
    'followup'      => ['Takipte',             'primary'],
    'won'           => ['Kazanıldı',           'success'],
    'lost'          => ['Kaybedildi',          'danger'],
];

$av_colors = ['av-blue','av-orange','av-purple','av-teal','av-rose'];

function dash_initials(string $name, string $phone): string {
    if (!$name) return mb_strtoupper(mb_substr($phone, -2));
    $parts = explode(' ', trim($name));
    $i = mb_strtoupper(mb_substr($parts[0], 0, 1));
    if (count($parts) > 1) $i .= mb_strtoupper(mb_substr(end($parts), 0, 1));
    return $i;
}

$extra_head = <<<'HTML'
<style>
/* ===== PREMIUM PATIENT OPS DASHBOARD — NO GREEN THEME ===== */
.main-content {
  padding: 0 !important;
  overflow: hidden !important;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #f8fbff 0%, #f4f7fb 100%);
}
.dash-wrap { display: flex; flex-direction: column; height: 100%; overflow: hidden; }

.summary-bar {
  display: grid;
  grid-template-columns: repeat(5, minmax(150px, 1fr));
  gap: 14px;
  padding: 18px 22px 16px;
  background: #f7faff;
  border-bottom: 1px solid #e6edf7;
  flex-shrink: 0;
}
.summary-card {
  background: rgba(255,255,255,.96);
  border: 1px solid #e3ebf6;
  border-radius: 16px;
  padding: 16px 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 86px;
  box-shadow: 0 10px 26px rgba(15, 39, 77, .055);
}
.summary-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}
.si-blue   { background:#eef5ff; color:#1e63e9; }
.si-orange { background:#fff3e3; color:#f07800; }
.si-red    { background:#fff1dd; color:#f07800; }
.si-teal   { background:#edf5ff; color:#2367d8; }
.si-gray   { background:#edf6ff; color:#0f6ab7; }
.summary-title { font-size: 12.5px; font-weight: 600; color: #17335f; margin-bottom: 4px; white-space: nowrap; }
.summary-val { font-size: 27px; font-weight: 800; color: #071d43; line-height: 1; letter-spacing: -1px; }
.summary-lbl { font-size: 11px; color: #7d8ca3; margin-top: 5px; }
.summary-progress { width: 100%; height: 6px; background:#e9eef7; border-radius:20px; margin-top:10px; overflow:hidden; }
.summary-progress span { display:block; height:100%; background:#2269e8; border-radius:20px; }

.dash-split {
  display: grid;
  grid-template-columns: minmax(620px, 1fr) 430px;
  gap: 14px;
  flex: 1;
  min-height: 0;
  overflow: hidden;
  padding: 14px 22px 22px;
}
.dash-left, .dash-right {
  background: rgba(255,255,255,.98);
  border: 1px solid #e3ebf6;
  border-radius: 18px;
  box-shadow: 0 16px 38px rgba(15, 39, 77, .07);
  overflow: hidden;
}
.dash-left { display: flex; flex-direction: column; min-width: 0; }
.dash-right { overflow-y: auto; }

.filter-bar {
  padding: 14px 14px;
  background: #fff;
  border-bottom: 1px solid #e7edf6;
  display: flex;
  align-items: center;
  gap: 9px;
  flex-shrink: 0;
}
.filter-pill {
  font-size: 12.5px;
  padding: 8px 13px;
  border-radius: 12px;
  border: 1px solid transparent;
  color: #274263;
  background: transparent;
  cursor: pointer;
  font-weight: 650;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all .15s ease;
}
.filter-pill:hover { color:#0b2a5b; background:#f2f6fd; }
.filter-pill.active { background:#071d43; color:#fff; box-shadow:0 8px 20px rgba(7,29,67,.18); }
.filter-pill.warn { color:#d86600; }
.filter-pill .fpill-cnt {
  min-width: 21px;
  height: 21px;
  padding: 0 6px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  background:#eef3fb;
  color:#66748b;
  border-radius: 999px;
  font-size: 10.5px;
  font-weight: 800;
}
.filter-pill.active .fpill-cnt { background:rgba(255,255,255,.16); color:#fff; }
.filter-search {
  margin-left: auto;
  width: 250px;
  height: 40px;
  border: 1px solid #e0e8f4;
  border-radius: 12px;
  display:flex;
  align-items:center;
  gap:8px;
  padding:0 12px;
  color:#7b8ca5;
  font-size:12px;
  background:#fbfdff;
}
.filter-icon-btn {
  width: 40px; height:40px; border-radius:12px;
  border:1px solid #e0e8f4;
  display:flex; align-items:center; justify-content:center;
  color:#506987; background:#fff;
}

.inbox-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
}
.conv-card {
  display: grid;
  grid-template-columns: 56px minmax(0, 1fr) 150px 22px;
  align-items: center;
  gap: 14px;
  padding: 18px 16px;
  background: #fff;
  border: 1px solid #e4ebf5;
  border-radius: 16px;
  cursor: pointer;
  transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease, background .15s ease;
  position: relative;
  min-height: 112px;
}
.conv-card:hover { border-color:#c7d8f3; box-shadow:0 12px 28px rgba(11, 42, 91, .075); transform:translateY(-1px); }
.conv-card.active { border-color:#1d66e5; box-shadow:0 0 0 1px #1d66e5, 0 18px 34px rgba(30, 99, 233, .10); background:#fcfdff; }
.conv-card.urgent { border-color:#ffcf95; }
.conv-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 800;
  flex-shrink: 0;
  letter-spacing: -.5px;
  position: relative;
}
.conv-avatar::after {
  content:"";
  width: 9px; height: 9px; border-radius:50%;
  position:absolute; right:3px; bottom:3px;
  background:#2169e8;
  border:2px solid #fff;
}
.av-blue   { background:linear-gradient(135deg,#e7f0ff,#b9d3ff); color:#0c3d89; }
.av-orange { background:linear-gradient(135deg,#fff4e7,#ffd7a8); color:#a44d00; }
.av-purple { background:linear-gradient(135deg,#f0efff,#d8d4ff); color:#4c35b1; }
.av-teal   { background:linear-gradient(135deg,#eef6ff,#c9ddff); color:#0c3d89; }
.av-rose   { background:linear-gradient(135deg,#eef4ff,#d8e8ff); color:#0c3d89; }
.conv-body { min-width: 0; }
.conv-title-line { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:7px; }
.conv-name { font-size: 16px; font-weight: 800; color:#071d43; letter-spacing:-.35px; }
.lang-chip { font-size: 12px; color:#42546f; background:#f2f5fa; border-radius: 999px; padding: 2px 7px; display:inline-flex; gap:4px; align-items:center; }
.conv-preview { font-size: 13px; color:#52647f; line-height:1.45; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.conv-preview .ai-prefix { color:#2367d8; font-weight:800; }
.conv-preview .user-prefix { color:#0b2a5b; font-weight:800; }
.conv-tags { display:flex; align-items:center; gap:7px; margin-top:10px; flex-wrap:wrap; }
.conv-tag, .conv-pill {
  font-size: 11.5px;
  padding: 5px 10px;
  border-radius: 999px;
  font-weight: 700;
  line-height: 1;
}
.conv-tag { background:#edf4ff; color:#1c5ed8; }
.pill-warning  { background:#fff1dd; color:#e16a00; }
.pill-info     { background:#edf4ff; color:#1c5ed8; }
.pill-success  { background:#edf4ff; color:#1c5ed8; }
.pill-danger   { background:#fff0f0; color:#cd2b2b; }
.pill-secondary{ background:#f1f4f8; color:#65758d; }
.pill-primary  { background:#edf4ff; color:#1c5ed8; }
.conv-side { display:flex; flex-direction:column; align-items:flex-end; gap:10px; min-width:0; }
.conv-time { font-size: 12px; color:#7d8ca3; text-align:right; }
.status-pill {
  font-size: 12px;
  padding: 7px 10px;
  border-radius: 12px;
  font-weight: 800;
  display:inline-flex;
  gap:6px;
  align-items:center;
  white-space:nowrap;
}
.status-ai { background:#edf4ff; color:#1c5ed8; }
.status-wait { background:#fff1dd; color:#e16a00; }
.status-assigned, .status-user { background:#edf4ff; color:#1c5ed8; }
.status-closed { background:#f2f4f8; color:#65758d; }
.conv-menu { color:#63738d; font-size:18px; display:flex; align-items:center; justify-content:center; }
.empty-state { flex:1; display:flex; align-items:center; justify-content:center; color:#7d8ca3; font-size:13px; padding:50px; }
.pagination-wrap { padding: 12px; border-top:1px solid #e7edf6; background:#fff; flex-shrink:0; }

.right-empty {
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  height:100%;
  color:#8391a8;
  font-size:13px;
  gap:10px;
  background:linear-gradient(180deg,#fff,#f8fbff);
}
.rpanel { padding: 0; }
.rpanel-topbar {
  padding: 18px 18px 10px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
}
.ai-label {
  background:#edf4ff;
  color:#1c5ed8;
  border-radius:10px;
  padding:7px 10px;
  font-size:12px;
  font-weight:800;
  display:inline-flex;
  align-items:center;
  gap:6px;
}
.rpanel-icons { display:flex; gap:12px; color:#46617f; }
.rpanel-head {
  padding: 8px 18px 16px;
  display:grid;
  grid-template-columns: 56px minmax(0,1fr) auto;
  gap:12px;
  align-items:center;
  border-bottom:1px solid #e7edf6;
}
.rpanel-avatar { width:54px; height:54px; font-size:18px; }
.rpanel-name { font-size: 17px; font-weight: 850; color:#071d43; letter-spacing:-.35px; }
.rpanel-sub { font-size: 12.5px; color:#596b83; margin-top:5px; }
.rpanel-stamp { text-align:right; display:flex; flex-direction:column; align-items:flex-end; gap:8px; }
.rpanel-section { padding: 16px 18px; border-bottom:1px solid #e7edf6; }
.ai-summary-box { background:#f1f6ff; border:1px solid #e0eaff; border-radius:14px; padding:14px; }
.rpanel-section-title { font-size:12px; font-weight:850; color:#1c5ed8; display:flex; gap:6px; align-items:center; margin-bottom:8px; }
.rpanel-ai-text { font-size: 12.7px; color:#30425e; line-height:1.55; }
.rpanel-row {
  display:grid;
  grid-template-columns: minmax(130px,1fr) minmax(130px,1fr);
  gap:12px;
  padding: 11px 0;
  border-bottom:1px solid #edf1f7;
  font-size:12.7px;
  align-items:start;
}
.rpanel-row:last-child { border-bottom:0; }
.rpanel-row-label { color:#17335f; font-weight:750; display:flex; align-items:center; gap:9px; }
.rpanel-row-label i { color:#47688e; font-size:16px; }
.rpanel-row-val { color:#1b2d4c; text-align:right; font-weight:700; }
.rpanel-row-val.orange { color:#e16a00; }
.rpanel-row-val.blue { color:#1c5ed8; }
.rpanel-actions { padding: 16px 18px; display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; }
.rpanel-actions .btn { font-size:12.5px; padding:10px 10px; border-radius:10px; font-weight:800; }
.rpanel-recommend { margin:0 18px 18px; background:#f4f8ff; border:1px solid #e1ebfb; border-radius:14px; padding:14px; }
.recommend-title { font-size:12.5px; font-weight:850; color:#1c5ed8; display:flex; gap:7px; align-items:center; margin-bottom:8px; }
.recommend-text { font-size:12.2px; line-height:1.55; color:#3c4f6c; }
@media (max-width: 1200px) {
  .dash-split { grid-template-columns: 1fr; overflow-y:auto; }
  .dash-left { min-height: 560px; }
  .dash-right { min-height: 520px; }
  .summary-bar { grid-template-columns: repeat(2, 1fr); }
}
</style>
HTML;

require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="dash-wrap">

<?php if ($show_quota_warning): ?>
  <?php
    $ac = $quota_pct >= 100 ? 'alert-danger' : 'alert-warning';
    $ic = $quota_pct >= 100 ? 'bi-exclamation-octagon-fill' : 'bi-exclamation-triangle-fill';
    $mg = $quota_pct >= 100
      ? 'Aylık hasta kotanız doldu. Yeni hasta karşılaması durdu.'
      : "Aylık hasta kotanızın %{$quota_pct}'ini kullandınız. ({$used}/{$limit} hasta)";
  ?>
  <div class="alert <?= $ac ?> d-flex align-items-center gap-2 py-2 mb-0 rounded-0" style="border-radius:0!important">
    <i class="bi <?= $ic ?>"></i>
    <div class="flex-grow-1 small"><?= $mg ?></div>
    <a href="mailto:destek@zboxasist.com" class="btn btn-sm btn-outline-dark">Paket Yükselt</a>
  </div>
<?php endif; ?>

<div class="summary-bar">
  <div class="summary-card">
    <div class="summary-icon si-blue"><i class="bi bi-person-plus"></i></div>
    <div>
      <div class="summary-title">Yeni Başvuru</div>
      <div class="summary-val"><?= (int)($cnt_row['today'] ?? 0) ?></div>
      <div class="summary-lbl">Bugün</div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon si-blue"><i class="bi bi-camera"></i></div>
    <div>
      <div class="summary-title">Fotoğraf Bekleniyor</div>
      <div class="summary-val"><?= (int)($cnt_row['photo'] ?? 0) ?></div>
      <div class="summary-lbl">Toplam</div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon si-orange"><i class="bi bi-person-exclamation"></i></div>
    <div>
      <div class="summary-title">İnsan Devri Bekliyor</div>
      <div class="summary-val"><?= (int)($cnt_row['waiting'] ?? 0) ?></div>
      <div class="summary-lbl">Toplam</div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon si-blue"><i class="bi bi-chat-dots"></i></div>
    <div>
      <div class="summary-title">Bugün Aktif Konuşma</div>
      <div class="summary-val"><?= (int)($cnt_row['active'] ?? 0) ?></div>
      <div class="summary-lbl">Şu an</div>
    </div>
  </div>
  <div class="summary-card">
    <div class="summary-icon si-gray"><i class="bi bi-activity"></i></div>
    <div style="width:100%">
      <div class="summary-title">Bu Ay Kullanım</div>
      <div class="summary-val"><?= $used ?> <span style="font-size:16px;font-weight:600;color:#516783">/ <?= $limit ?></span> <span style="font-size:12px;font-weight:600;color:#7d8ca3;letter-spacing:0">Hasta</span></div>
      <div class="summary-progress"><span style="width:<?= $quota_pct ?>%"></span></div>
    </div>
  </div>
</div>

<div class="dash-split">
  <div class="dash-left">
    <div class="filter-bar">
      <a href="/dashboard.php" class="filter-pill <?= !$status_filter ? 'active' : '' ?>">
        Tümü <?php if ($total): ?><span class="fpill-cnt"><?= $total ?></span><?php endif; ?>
      </a>
      <a href="/dashboard.php?status=ai_active" class="filter-pill <?= $status_filter === 'ai_active' ? 'active' : '' ?>">AI Aktif</a>
      <a href="/dashboard.php?status=pending_takeover" class="filter-pill <?= $status_filter === 'pending_takeover' ? 'active warn' : 'warn' ?>">
        Bekliyor <?php if ($cnt_row['waiting']): ?><span class="fpill-cnt"><?= $cnt_row['waiting'] ?></span><?php endif; ?>
      </a>
      <a href="/dashboard.php?status=with_user" class="filter-pill <?= $status_filter === 'with_user' ? 'active' : '' ?>">Devralındı</a>
      <?php if (is_coordinator() && client_has('assignment')): ?>
        <a href="/dashboard.php?status=assigned" class="filter-pill <?= $status_filter === 'assigned' ? 'active' : '' ?>">Atandı</a>
      <?php endif; ?>
      <a href="/dashboard.php?status=closed" class="filter-pill <?= $status_filter === 'closed' ? 'active' : '' ?>">Kapalı</a>
      <div class="filter-search"><span>Hasta adı, ülke veya etiket</span><i class="bi bi-search ms-auto"></i></div>
      <div class="filter-icon-btn"><i class="bi bi-sliders"></i></div>
    </div>

    <div class="inbox-scroll" id="inbox-list">
      <?php if (empty($conversations)): ?>
        <div class="empty-state">Konuşma bulunamadı.</div>
      <?php else: ?>
        <?php foreach ($conversations as $i => $conv):
          $is_urgent = $conv['status'] === 'pending_takeover';
          $av_class  = $av_colors[($conv['patient_id'] ?? $i) % 5];
          $initials  = dash_initials($conv['patient_name'] ?? '', $conv['phone']);
          $pl        = $pipe_labels[$conv['pipeline_status'] ?? 'new'] ?? null;
          $pill_cls  = $pl ? 'pill-' . $pl[1] : '';
          $status_class = match($conv['status']) {
              'ai_active' => 'status-ai',
              'pending_takeover' => 'status-wait',
              'assigned' => 'status-assigned',
              'with_user' => 'status-user',
              'closed' => 'status-closed',
              default => 'status-closed',
          };
          $status_label = match($conv['status']) {
              'ai_active' => 'AI Aktif',
              'pending_takeover' => 'Bekliyor',
              'assigned' => 'Atandı',
              'with_user' => 'Devralındı',
              'closed' => 'Kapalı',
              default => $conv['status'],
          };
        ?>
          <div class="conv-card <?= $is_urgent ? 'urgent' : '' ?>"
               onclick="openPanel(<?= $i ?>)"
               data-idx="<?= $i ?>">
            <div class="conv-avatar <?= $av_class ?>"><?= e($initials) ?></div>
            <div class="conv-body">
              <div class="conv-title-line">
                <span class="conv-name"><?= e($conv['patient_name'] ?: $conv['phone']) ?></span>
                <?php
                  $lang_names_d = [
                    'tr'=>'Türkçe','ar'=>'Arapça','fr'=>'Fransızca','en'=>'İngilizce',
                    'de'=>'Almanca','ru'=>'Rusça','nl'=>'Hollandaca','es'=>'İspanyolca',
                    'it'=>'İtalyanca','pl'=>'Lehçe','fa'=>'Farsça','az'=>'Azerbaycanca',
                  ];
                  $country_flags_d = [
                    'TR'=>'🇹🇷','SA'=>'🇸🇦','AE'=>'🇦🇪','FR'=>'🇫🇷','DE'=>'🇩🇪',
                    'GB'=>'🇬🇧','RU'=>'🇷🇺','NL'=>'🇳🇱','IQ'=>'🇮🇶','EG'=>'🇪🇬',
                    'MA'=>'🇲🇦','DZ'=>'🇩🇿','KW'=>'🇰🇼','QA'=>'🇶🇦','IT'=>'🇮🇹',
                    'ES'=>'🇪🇸','UA'=>'🇺🇦','AZ'=>'🇦🇿','IR'=>'🇮🇷','US'=>'🇺🇸',
                  ];
                  $country_names_d = [
                    'TR'=>'Türkiye','SA'=>'Suudi Arabistan','AE'=>'BAE','FR'=>'Fransa',
                    'DE'=>'Almanya','GB'=>'İngiltere','RU'=>'Rusya','NL'=>'Hollanda',
                    'IQ'=>'Irak','EG'=>'Mısır','MA'=>'Fas','DZ'=>'Cezayir',
                    'KW'=>'Kuveyt','QA'=>'Katar','IT'=>'İtalya','ES'=>'İspanya',
                    'UA'=>'Ukrayna','AZ'=>'Azerbaycan','IR'=>'İran','US'=>'ABD',
                  ];
                  if ($conv['language']) {
                    $lf = lang_flag($conv['language']);
                    $ln = $lang_names_d[strtolower($conv['language'])] ?? strtoupper($conv['language']);
                    echo '<span class="lang-chip">' . $lf . ' ' . $ln . '</span>';
                  } elseif ($conv['country_code']) {
                    $cc = strtoupper($conv['country_code']);
                    $lf = $country_flags_d[$cc] ?? '📍';
                    $ln = $country_names_d[$cc] ?? $cc;
                    echo '<span class="lang-chip">' . $lf . ' ' . $ln . '</span>';
                  }
                ?>
              </div>
              <div class="conv-preview">
                <?php if ($conv['last_sender'] === 'ai'): ?><span class="ai-prefix">AI:</span>
                <?php elseif ($conv['last_sender'] === 'portal_user'): ?><span class="user-prefix">Siz:</span><?php endif; ?>
                <?= e($conv['last_message'] ? mb_strimwidth($conv['last_message'], 0, 115, '...') : '—') ?>
              </div>
              <?php if ($conv['topic_summary'] || ($pl && $conv['pipeline_status'] !== 'new')): ?>
                <div class="conv-tags">
                  <?php if ($conv['topic_summary']): ?><span class="conv-tag"><?= e($conv['topic_summary']) ?></span><?php endif; ?>
                  <?php if ($pl && $conv['pipeline_status'] !== 'new'): ?><span class="conv-pill <?= $pill_cls ?>"><?= e($pl[0]) ?></span><?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="conv-side">
              <span class="status-pill <?= $status_class ?>"><i class="bi bi-stars"></i><?= e($status_label) ?></span>
              <span class="conv-time"><?= time_ago($conv['last_message_at'] ?: $conv['updated_at']) ?></span>
              <?php if ($conv['assigned_to_name']): ?>
                <span class="conv-time"><i class="bi bi-person-check"></i> <?= e($conv['assigned_to_name']) ?></span>
              <?php endif; ?>
            </div>
            <div class="conv-menu"><i class="bi bi-three-dots-vertical"></i></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
      <div class="pagination-wrap">
        <ul class="pagination pagination-sm justify-content-center mb-0">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i === $cur_page ? 'active' : '' ?>">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>

  <div class="dash-right" id="dash-right">
    <div class="right-empty">
      <i class="bi bi-stars" style="font-size:34px;opacity:.35"></i>
      <span>AI hasta özeti için bir konuşma seçin</span>
    </div>
  </div>
</div>
</div>

<script>
const convData = <?= json_encode(array_map(function($conv) use ($av_colors) {
  $initials = function($name, $phone) {
    if (!$name) return mb_strtoupper(mb_substr($phone, -2));
    $parts = explode(' ', trim($name));
    $i = mb_strtoupper(mb_substr($parts[0], 0, 1));
    if (count($parts) > 1) $i .= mb_strtoupper(mb_substr(end($parts), 0, 1));
    return $i;
  };
  static $idx = 0;
  $av = $av_colors[($conv['patient_id'] ?? $idx) % 5];
  $idx++;
  return [
    "id"          => $conv['id'],
    "patient_id"  => $conv['patient_id'],
    "name"        => $conv['patient_name'] ?: $conv['phone'],
    "language"    => $conv['language'] ?? '',
    "status"      => $conv['status'],
    "pipeline"    => $conv['pipeline_status'] ?? 'new',
    "treatment"   => $conv['treatment_interest'] ?? '',
    "topic"       => $conv['topic_summary'] ?? '',
    "summary"     => $conv['summary_text'] ?? '',
    "assigned"    => $conv['assigned_to_name'] ?? '',
    "initials"    => (function($conv) {
      $name = $conv['patient_name'] ?? '';
      $phone = $conv['phone'] ?? '';
      if (!$name) return mb_strtoupper(mb_substr($phone, -2));
      $parts = explode(' ', trim($name));
      $i = mb_strtoupper(mb_substr($parts[0], 0, 1));
      if (count($parts) > 1) $i .= mb_strtoupper(mb_substr(end($parts), 0, 1));
      return $i;
    })($conv),
    "av_class"    => $av,
    "last_sender"  => $conv['last_sender'] ?? '',
    "country_code" => $conv['country_code'] ?? '',
    "time"         => $conv['last_message_at'] ?? $conv['updated_at'],
  ];
}, $conversations), JSON_UNESCAPED_UNICODE) ?>;

const pipeLabels = {
  new:           ['Yeni',                'secondary'],
  photo_pending: ['Fotoğraf Bekleniyor', 'warning'],
  price_given:   ['Fiyat Verildi',       'info'],
  followup:      ['Takipte',             'primary'],
  won:           ['Kazanıldı',           'success'],
  lost:          ['Kaybedildi',          'danger'],
};

function escapeHtml(v) {
  return String(v ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
}
const langNames = {
  tr:'Türkçe',ar:'Arapça',fr:'Fransızca',en:'İngilizce',de:'Almanca',
  ru:'Rusça',nl:'Hollandaca',es:'İspanyolca',it:'İtalyanca',pl:'Lehçe',
  fa:'Farsça',az:'Azerbaycanca',uk:'Ukraynaca',uz:'Özbekçe',
};
const countryFlags = {
  TR:'🇹🇷',SA:'🇸🇦',AE:'🇦🇪',FR:'🇫🇷',DE:'🇩🇪',GB:'🇬🇧',RU:'🇷🇺',
  NL:'🇳🇱',IQ:'🇮🇶',EG:'🇪🇬',MA:'🇲🇦',DZ:'🇩🇿',KW:'🇰🇼',QA:'🇶🇦',
  IT:'🇮🇹',ES:'🇪🇸',UA:'🇺🇦',AZ:'🇦🇿',IR:'🇮🇷',US:'🇺🇸',
};
const countryNames = {
  TR:'Türkiye',SA:'Suudi Arabistan',AE:'BAE',FR:'Fransa',DE:'Almanya',
  GB:'İngiltere',RU:'Rusya',NL:'Hollanda',IQ:'Irak',EG:'Mısır',
  MA:'Fas',DZ:'Cezayir',KW:'Kuveyt',QA:'Katar',IT:'İtalya',
  ES:'İspanya',UA:'Ukrayna',AZ:'Azerbaycan',IR:'İran',US:'ABD',
};
function langFlag(l) {
  const m = {ar:'🇸🇦',fr:'🇫🇷',en:'🇬🇧',de:'🇩🇪',ru:'🇷🇺',nl:'🇳🇱',tr:'🇹🇷',es:'🇪🇸',it:'🇮🇹',pl:'🇵🇱'};
  return m[l] || '🌐';
}
function getLangDisplay(language, countryCode) {
  if (language) {
    const flag = langFlag(language);
    const name = langNames[language.toLowerCase()] || language.toUpperCase();
    return flag + ' ' + name;
  } else if (countryCode) {
    const cc = countryCode.toUpperCase();
    const flag = countryFlags[cc] || '📍';
    const name = countryNames[cc] || cc;
    return flag + ' ' + name;
  }
  return '';
}
function statusMeta(s) {
  const m = {
    ai_active:        ['AI Aktif', 'status-ai'],
    pending_takeover: ['Bekliyor', 'status-wait'],
    assigned:         ['Atandı', 'status-assigned'],
    with_user:        ['Devralındı', 'status-user'],
    closed:           ['Kapalı', 'status-closed'],
  };
  return m[s] || [s, 'status-closed'];
}

let activeCard = null;
function openPanel(idx) {
  const el = document.querySelector(`[data-idx="${idx}"]`);
  if (!el) return;
  if (activeCard) activeCard.classList.remove('active');
  el.classList.add('active');
  activeCard = el;

  const d = convData[idx];
  const convId = d.id;
  const patientId = d.patient_id;
  const pl = pipeLabels[d.pipeline] || pipeLabels.new;
  const sm = statusMeta(d.status);
  const hasAssignment = <?= (client_has('assignment') && is_coordinator()) ? 'true' : 'false' ?>;
  const hasPatientCard = <?= client_has('patient_card') ? 'true' : 'false' ?>;
  const hekimOpts = <?= json_encode(array_map(fn($h) => ['id'=>$h['id'],'name'=>$h['name']], $hekimler), JSON_UNESCAPED_UNICODE) ?>;

  let hekimPanel = '';
  if (hasAssignment) {
    const opts = hekimOpts.map(h => `<option value="${h.id}">${escapeHtml(h.name)}</option>`).join('');
    hekimPanel = `<div class="collapse px-3 pb-3" id="hekim-panel">
      <select class="form-select form-select-sm" id="hekim-select" style="font-size:12px">
        <option value="">— Hekim seç —</option>${opts}
      </select>
      <button class="btn btn-sm btn-primary mt-2 w-100" onclick="ataHekim(${convId})">Ata</button>
    </div>`;
  }

  const summaryText = d.summary
    ? escapeHtml(d.summary).replace(/\n/g,'<br>')
    : `${escapeHtml(d.name)} için konuşma özeti henüz oluşmamış. Son mesaj ve etiketlere göre takip planı oluşturabilirsiniz.`;

  const topicLine = d.topic ? escapeHtml(d.topic) : (d.treatment ? escapeHtml(d.treatment) : 'Genel başvuru');
  const nextAction = d.pipeline === 'photo_pending'
    ? 'Fotoğraflar geldikten sonra uzman değerlendirmesi planla'
    : 'Hastanın son mesajına göre takip aksiyonunu netleştir';

  const noteText = d.treatment
    ? `${escapeHtml(d.treatment)} ile ilgili beklentilerini detaylıca öğrenmek istiyor.`
    : 'Hastanın beklenti ve karar kriterlerini netleştirmek gerekiyor.';

  document.getElementById('dash-right').innerHTML = `
    <div class="rpanel">
      <div class="rpanel-topbar">
        <span class="ai-label"><i class="bi bi-stars"></i> AI ile güçlendirilmiş özet</span>
        <div class="rpanel-icons"><i class="bi bi-arrows-fullscreen"></i><i class="bi bi-x-lg"></i></div>
      </div>
      <div class="rpanel-head">
        <div class="conv-avatar ${escapeHtml(d.av_class)} rpanel-avatar">${escapeHtml(d.initials)}</div>
        <div class="min-width-0">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="rpanel-name">${escapeHtml(d.name)}</span>
            ${getLangDisplay(d.language, d.country_code) ? `<span class="lang-chip">${getLangDisplay(d.language, d.country_code)}</span>` : ''}
          </div>
          <div class="rpanel-sub">İlgi Alanı: ${d.treatment ? escapeHtml(d.treatment) : topicLine}</div>
        </div>
        <div class="rpanel-stamp">
          <a href="/konusma.php?id=${convId}" class="btn btn-primary btn-sm" style="background:var(--zb-accent);border-color:var(--zb-accent);font-weight:700;border-radius:10px">
            <i class="bi bi-chat-dots"></i> Konuşmayı Aç
          </a>
          <span class="status-pill ${sm[1]} mt-1"><i class="bi bi-stars"></i>${escapeHtml(sm[0])}</span>
        </div>
      </div>

      <div class="rpanel-section">
        <div class="ai-summary-box">
          <div class="rpanel-section-title"><i class="bi bi-stars"></i> AI Özeti</div>
          <div class="rpanel-ai-text">${summaryText}</div>
        </div>
      </div>

      <div class="rpanel-section">
        <div class="rpanel-row">
          <span class="rpanel-row-label"><i class="bi bi-camera"></i> Fotoğraf Durumu</span>
          <span class="rpanel-row-val ${d.pipeline === 'photo_pending' ? 'orange' : ''}">${escapeHtml(pl[0])}</span>
        </div>
        <div class="rpanel-row">
          <span class="rpanel-row-label"><i class="bi bi-calendar-event"></i> Geliş Tarihi</span>
          <span class="rpanel-row-val" style="color:#7d8ca3">Belirtilmedi</span>
        </div>
        <div class="rpanel-row">
          <span class="rpanel-row-label"><i class="bi bi-airplane"></i> Otel / Transfer Talebi</span>
          <span class="rpanel-row-val" style="color:#7d8ca3">Talep edilmedi</span>
        </div>
        <div class="rpanel-row">
          <span class="rpanel-row-label"><i class="bi bi-bullseye"></i> Sonraki Aksiyon</span>
          <span class="rpanel-row-val blue">${escapeHtml(nextAction)}</span>
        </div>
        ${d.assigned ? `<div class="rpanel-row"><span class="rpanel-row-label"><i class="bi bi-person-check"></i> Atanan</span><span class="rpanel-row-val">${escapeHtml(d.assigned)}</span></div>` : ''}
        <div class="rpanel-row">
          <span class="rpanel-row-label"><i class="bi bi-file-earmark-text"></i> Notlar</span>
          <span class="rpanel-row-val">${noteText}</span>
        </div>
      </div>

      <div class="rpanel-actions">
        <button class="btn btn-primary" onclick="devral(${convId})"><i class="bi bi-person-check"></i> Devral</button>
        ${hasPatientCard ? `<a href="/hasta-detay.php?id=${patientId}" class="btn btn-outline-secondary"><i class="bi bi-file-earmark-plus"></i> Not Ekle</a>` : `<a href="/konusma.php?id=${convId}" class="btn btn-outline-secondary"><i class="bi bi-chat-dots"></i> Aç</a>`}
        ${hasAssignment ? `<button class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#hekim-panel"><i class="bi bi-people"></i> Hekim Atama</button>` : ''}
      </div>
      ${hekimPanel}

      <div class="rpanel-recommend">
        <div class="recommend-title"><i class="bi bi-stars"></i> AI Önerilen aksiyon</div>
        <div class="recommend-text">Örnek vaka görselleri ile tedavi süreci videosu gönderilmesi önerilir.<br>Ayrıca yaklaşan müsaitlik bilgisi ve fiyat aralığı paylaşılabilir.</div>
      </div>
    </div>`;
}

function devral(convId) {
  fetch('/api/takeover.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({conversation_id: convId})
  }).then(r => r.json()).then(d => {
    if (d.ok) window.location.href = '/konusma.php?id=' + convId;
  });
}

function ataHekim(convId) {
  const sel = document.getElementById('hekim-select');
  if (!sel || !sel.value) return;
  fetch('/api/assign.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({conversation_id: convId, user_id: parseInt(sel.value)})
  }).then(r => r.json()).then(d => {
    if (d.ok) location.reload();
  });
}

document.addEventListener('DOMContentLoaded', function () {
  openPanel(0);
});

setInterval(() => {}, 30000);
</script>