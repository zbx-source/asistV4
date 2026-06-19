<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

auth_check();

$pdo = db();
$cid = client_id();
$uid = portal_user_id();

$conv_id = (int)get('id');
if (!$conv_id) redirect('/dashboard.php');

$conv = $pdo->prepare(
    "SELECT c.*, c.summary_text, p.phone, p.name AS patient_name, p.language, p.country_code,
            p.treatment_interest, p.pipeline_status,
            pu.name AS assigned_to_name
     FROM conversations c
     JOIN patients p ON p.id = c.patient_id
     LEFT JOIN portal_users pu ON pu.id = c.assigned_to
     WHERE c.id = ? AND c.client_id = ?"
);
$conv->execute([$conv_id, $cid]);
$conv = $conv->fetch();
if (!$conv) redirect('/dashboard.php');

$page_title  = $conv['patient_name'] ?: $conv['phone'];
$active_menu = 'dashboard';

$msgs = $pdo->prepare(
    "SELECT m.*, pu.name AS sender_name
     FROM messages m
     LEFT JOIN portal_users pu ON pu.id = m.sender_id
     WHERE m.conversation_id = ?
     ORDER BY m.sent_at ASC"
);
$msgs->execute([$conv_id]);
$messages = $msgs->fetchAll();

$portal_users = [];
if (is_coordinator() && has_assignment()) {
    $pu = $pdo->prepare(
        "SELECT id, name, role FROM portal_users
         WHERE client_id = ? AND status = 'active' AND role != 'coordinator'
         ORDER BY name"
    );
    $pu->execute([$cid]);
    $portal_users = $pu->fetchAll();
}

// 24 saat penceresi kontrolü
$last_patient_msg = $pdo->prepare(
    "SELECT MAX(sent_at) AS last_at FROM messages
     WHERE conversation_id = ? AND direction = 'inbound' AND sender_type = 'patient'"
);
$last_patient_msg->execute([$conv_id]);
$last_msg_at = $last_patient_msg->fetchColumn();
$window_open = $last_msg_at && (time() - strtotime($last_msg_at)) < (24 * 3600);

$can_write = in_array($conv['status'], ['with_user']) &&
             ($conv['assigned_to'] === null || (int)$conv['assigned_to'] === $uid) &&
             $window_open;

if (has_assignment() && !is_coordinator()) {
    $can_write = $conv['template_replied'] && (int)$conv['assigned_to'] === $uid;
}

$extra_head = '<style>
body { overflow: hidden; }
.app-body { overflow: hidden; }
.main-content { padding: 0 !important; display: flex; flex-direction: column; overflow: hidden; background: #f6f9fd; }

/* ===== HEADER ===== */
.conv-header {
  padding: 0 22px;
  height: 60px;
  background: #fff;
  border-bottom: 1px solid #e4ebf5;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
  box-shadow: 0 1px 0 rgba(15,39,77,.03);
}
.conv-header-left { display: flex; align-items: center; gap: 12px; }
.conv-header-right { display: flex; align-items: center; gap: 8px; }

.conv-back {
  width: 34px; height: 34px; border-radius: 10px;
  background: #f2f5fa; color: #42546f;
  display: flex; align-items: center; justify-content: center;
  text-decoration: none; font-size: 16px;
  transition: background .15s;
}
.conv-back:hover { background: #e4ebf5; color: #071d43; }

.conv-patient-name { font-size: 15px; font-weight: 800; color: #071d43; letter-spacing: -.3px; }
.conv-patient-sub  { font-size: 12px; color: #7d8ca3; margin-top: 1px; }

.lang-chip {
  font-size: 12px; color: #42546f; background: #f2f5fa;
  border-radius: 999px; padding: 3px 9px;
  display: inline-flex; gap: 4px; align-items: center;
}

/* ===== ÖZET PANEL ===== */
.summary-panel {
  padding: 12px 22px;
  background: #f1f6ff;
  border-bottom: 1px solid #e0eaff;
  flex-shrink: 0;
}
.summary-panel-title {
  font-size: 12px; font-weight: 800; color: #1c5ed8;
  display: flex; align-items: center; gap: 6px; margin-bottom: 5px;
}
.summary-panel-text { font-size: 12.5px; color: #30425e; line-height: 1.55; }

/* ===== MESAJLAR ===== */
.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px 22px;
  background: #f6f9fd;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.msg-bubble { max-width: 68%; display: flex; flex-direction: column; }
.msg-bubble.inbound  { margin-right: auto; align-items: flex-start; }
.msg-bubble.outbound { margin-left: auto; align-items: flex-end; }
.msg-bubble.ai       { margin-right: auto; align-items: flex-start; max-width: 78%; }

.msg-sender-label {
  font-size: 11px; font-weight: 700; color: #7d8ca3;
  margin-bottom: 3px; display: flex; align-items: center; gap: 5px;
}
.msg-sender-label.ai-label { color: #1c5ed8; }

.msg-content {
  padding: 10px 14px;
  font-size: 13.5px;
  line-height: 1.6;
  word-break: break-word;
}
.msg-content b { font-weight: 700; }

.inbound .msg-content {
  background: #fff;
  border-radius: 4px 16px 16px 16px;
  box-shadow: 0 2px 8px rgba(15,39,77,.07);
  color: #071d43;
}
.outbound .msg-content {
  background: #071d43;
  color: #fff;
  border-radius: 16px 4px 16px 16px;
}
.ai .msg-content {
  background: #eef4ff;
  border: 1px solid #dce9ff;
  border-radius: 4px 16px 16px 16px;
  color: #071d43;
}

.msg-meta { font-size: 11px; color: #9aa5b8; margin-top: 4px; padding: 0 2px; }
.outbound .msg-meta { color: rgba(255,255,255,.5); }

/* ===== ÇEVİRİ ===== */
.msg-tr-wrap { margin-top: 6px; }
.msg-tr-label {
  font-size: 10px; font-weight: 700; color: #1c5ed8;
  letter-spacing: .04em; margin-bottom: 3px; padding-left: 2px;
  display: flex; align-items: center; gap: 4px;
}
.msg-tr {
  padding: 8px 13px;
  border-radius: 10px;
  font-size: 13px;
  line-height: 1.55;
  word-break: break-word;
  background: #e8f1ff;
  color: #1c4fa8;
  border: 1px solid #d0e2ff;
}
.msg-tr-loading { font-size: 11px; color: #9aa5b8; font-style: italic; padding: 3px 8px; }

/* ===== ÇEVİRİ BUTONU ===== */
.btn-translate-mode {
  font-size: 12px; padding: 6px 12px; border-radius: 10px;
  border: 1px solid #e0e8f4; background: #fff; color: #42546f;
  cursor: pointer; transition: all .15s;
  display: flex; align-items: center; gap: 5px; font-weight: 600;
}
.btn-translate-mode.active {
  background: #eef4ff; border-color: #1c5ed8; color: #1c5ed8;
}

/* ===== INPUT ALANI ===== */
.chat-input-area {
  padding: 14px 22px;
  background: #fff;
  border-top: 1px solid #e4ebf5;
  flex-shrink: 0;
}
.chat-input-area textarea {
  border-radius: 14px;
  border-color: #e0e8f4;
  background: #f8fbff;
  font-size: 13.5px;
  resize: none;
}
.chat-input-area textarea:focus {
  border-color: #1c5ed8;
  box-shadow: 0 0 0 3px rgba(30,99,233,.1);
  background: #fff;
}
.chat-input-area .btn-primary {
  border-radius: 12px;
  width: 44px; height: 44px;
  padding: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
}

/* ===== WINDOW CLOSED ===== */
.window-closed-bar {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 14px 22px;
  background: #fff8f0;
  border-top: 2px solid #f07800;
}
.window-closed-bar i { color: #f07800; font-size: 18px; margin-top: 1px; flex-shrink: 0; }
.window-closed-bar .title { font-size: 13px; font-weight: 800; color: #7c3a00; }
.window-closed-bar .sub { font-size: 12px; color: #a05300; margin-top: 3px; }

.msg-media img { max-width: 220px; border-radius: 12px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,.1); }

/* AI status bar */
.ai-status-bar {
  text-align: center; padding: 10px;
  font-size: 12px; color: #7d8ca3;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  flex-shrink: 0; background: #fff; border-top: 1px solid #e4ebf5;
}
</style>';

require_once __DIR__ . '/includes/layout_header.php';
?>

<!-- Konuşma header -->
<div class="conv-header">
  <div class="conv-header-left">
    <a href="/dashboard.php" class="conv-back"><i class="bi bi-arrow-left"></i></a>
    <div>
      <div class="conv-patient-name d-flex align-items-center gap-2">
        <?= e($conv['patient_name'] ?: $conv['phone']) ?>
        <?php if ($conv['language']): ?>
          <span class="lang-chip"><?= lang_flag($conv['language']) ?> <?= strtoupper(e($conv['language'])) ?></span>
        <?php endif; ?>
        <?= conv_status_badge($conv['status']) ?>
      </div>
      <?php
        $lang_names = [
          'tr'=>'Türkçe','ar'=>'Arapça','fr'=>'Fransızca','en'=>'İngilizce',
          'de'=>'Almanca','ru'=>'Rusça','nl'=>'Hollandaca','es'=>'İspanyolca',
          'it'=>'İtalyanca','pl'=>'Lehçe','fa'=>'Farsça','az'=>'Azerbaycanca',
          'uk'=>'Ukraynaca','uz'=>'Özbekçe',
        ];
        $country_flags = [
          'TR'=>'🇹🇷','SA'=>'🇸🇦','AE'=>'🇦🇪','FR'=>'🇫🇷','DE'=>'🇩🇪',
          'GB'=>'🇬🇧','RU'=>'🇷🇺','NL'=>'🇳🇱','IQ'=>'🇮🇶','EG'=>'🇪🇬',
          'MA'=>'🇲🇦','DZ'=>'🇩🇿','LY'=>'🇱🇾','TN'=>'🇹🇳','JO'=>'🇯🇴',
          'KW'=>'🇰🇼','QA'=>'🇶🇦','BH'=>'🇧🇭','OM'=>'🇴🇲','YE'=>'🇾🇪',
          'LB'=>'🇱🇧','SD'=>'🇸🇩','IT'=>'🇮🇹','ES'=>'🇪🇸','PL'=>'🇵🇱',
          'UA'=>'🇺🇦','AZ'=>'🇦🇿','IR'=>'🇮🇷','PK'=>'🇵🇰','US'=>'🇺🇸',
        ];
        $country_names = [
          'TR'=>'Türkiye','SA'=>'Suudi Arabistan','AE'=>'BAE','FR'=>'Fransa',
          'DE'=>'Almanya','GB'=>'İngiltere','RU'=>'Rusya','NL'=>'Hollanda',
          'IQ'=>'Irak','EG'=>'Mısır','MA'=>'Fas','DZ'=>'Cezayir','LY'=>'Libya',
          'TN'=>'Tunus','JO'=>'Ürdün','KW'=>'Kuveyt','QA'=>'Katar','BH'=>'Bahreyn',
          'OM'=>'Umman','YE'=>'Yemen','LB'=>'Lübnan','SD'=>'Sudan','IT'=>'İtalya',
          'ES'=>'İspanya','PL'=>'Polonya','UA'=>'Ukrayna','AZ'=>'Azerbaycan',
          'IR'=>'İran','PK'=>'Pakistan','US'=>'ABD',
        ];
        $lang_display = '';
        if ($conv['language']) {
          $flag = lang_flag($conv['language']);
          $name = $lang_names[strtolower($conv['language'])] ?? strtoupper($conv['language']);
          $lang_display = $flag . ' ' . $name;
        } elseif ($conv['country_code']) {
          $cc = strtoupper($conv['country_code']);
          $flag = $country_flags[$cc] ?? '📍';
          $name = $country_names[$cc] ?? $cc;
          $lang_display = $flag . ' ' . $name;
        }
      ?>
      <div class="conv-patient-sub">
        <?= e($conv['phone']) ?>
        <?php if ($lang_display): ?>
          <span style="margin-left:8px;color:#1c5ed8;font-weight:600"><?= $lang_display ?></span>
        <?php endif; ?>
      </div>
    </div>
    <button class="btn-translate-mode" id="translateModeBtn" onclick="toggleTranslateMode()">
      🇹🇷 Türkçeye Çevir
    </button>
  </div>

  <div class="conv-header-right">
    <?php if ($conv['status'] === 'pending_takeover' && !is_coordinator()): ?>
      <button class="btn btn-sm btn-primary" onclick="takeOver()" style="background:var(--zb-accent);border-color:var(--zb-accent)">
        <i class="bi bi-person-check"></i> Devral
      </button>
    <?php endif; ?>

    <?php if (is_coordinator() && has_assignment() && $conv['status'] === 'pending_takeover' && !empty($portal_users)): ?>
      <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-check"></i> Hekim Ata
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php foreach ($portal_users as $pu): ?>
            <li>
              <a class="dropdown-item" href="#" onclick="assignTo(<?= $pu['id'] ?>, '<?= e($pu['name']) ?>')">
                <?= e($pu['name']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($conv['status'] !== 'closed'): ?>
      <button class="btn btn-sm btn-outline-secondary" onclick="closeConversation()">
        <i class="bi bi-x-circle"></i> Kapat
      </button>
    <?php endif; ?>

    <button class="btn btn-sm btn-outline-secondary" onclick="generateSummary()" id="btnSummary">
      <i class="bi bi-stars"></i> Özet Çıkar
    </button>
  </div>
</div>

<!-- Özet paneli -->
<div id="summaryPanel" class="summary-panel" style="display:<?= $conv['summary_text'] ? 'block' : 'none' ?>">
  <div class="d-flex justify-content-between align-items-start">
    <div class="summary-panel-title">
      <i class="bi bi-stars"></i> AI Özeti
      <?php if ($conv['treatment_interest']): ?>
        <span class="badge" style="background:#dce9ff;color:#1c5ed8;font-size:11px"><?= e($conv['treatment_interest']) ?></span>
      <?php endif; ?>
      <?php if ($conv['pipeline_status'] && $conv['pipeline_status'] !== 'new'):
        $pl = ['photo_pending'=>'Fotoğraf bekleniyor','price_given'=>'Fiyat verildi','followup'=>'Takipte','won'=>'Kazanıldı','lost'=>'Kaybedildi'];
      ?>
        <span class="badge" style="background:#fff1dd;color:#e16a00;font-size:11px"><?= $pl[$conv['pipeline_status']] ?? $conv['pipeline_status'] ?></span>
      <?php endif; ?>
    </div>
    <button class="btn btn-sm btn-link p-0" style="color:#9aa5b8" onclick="document.getElementById('summaryPanel').style.display='none'">&times;</button>
  </div>
  <div class="summary-panel-text" id="summaryText"><?= e($conv['summary_text'] ?? '') ?></div>
</div>

<!-- Mesajlar -->
<div class="chat-messages" id="chatMessages">
  <?php foreach ($messages as $msg): ?>
    <?php
      $isInbound   = $msg['direction'] === 'inbound';
      $isAI        = $msg['sender_type'] === 'ai';
      $bubbleClass = $isInbound ? 'inbound' : ($isAI ? 'ai' : 'outbound');
      $needsTr     = ($msg['language'] ?? $conv['language']) !== 'tr' || $isAI;
    ?>
    <div class="msg-bubble <?= $bubbleClass ?>"
         data-msg-id="<?= $msg['id'] ?>"
         data-body="<?= e($msg['body'] ?? '') ?>"
         data-needs-tr="<?= $needsTr ? '1' : '0' ?>"
         data-has-tr="<?= $msg['body_tr'] ? '1' : '0' ?>">

      <?php if ($isAI): ?>
        <div class="msg-sender-label ai-label"><i class="bi bi-stars"></i> AI</div>
      <?php elseif (!$isInbound && $msg['sender_name']): ?>
        <div class="msg-sender-label" style="justify-content:flex-end"><?= e($msg['sender_name']) ?></div>
      <?php endif; ?>

      <div class="msg-content">
        <?php if ($msg['media_url'] && in_array($msg['message_type'], ['image','document'])): ?>
          <div class="msg-media mb-1">
            <?php if ($msg['message_type'] === 'image'): ?>
              <img src="<?= e($msg['media_url']) ?>" alt="Görsel" loading="lazy">
            <?php else: ?>
              <a href="<?= e($msg['media_url']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark"></i> Belge İndir
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?= render_markdown($msg['body'] ?? '') ?>
      </div>

      <?php if ($needsTr): ?>
        <div class="msg-tr-content" style="display:none">
          <?php if ($msg['body_tr']): ?>
            <div class="msg-tr-wrap">
              <div class="msg-tr-label"><span>🇹🇷</span> Türkçe</div>
              <div class="msg-tr"><?= render_markdown($msg['body_tr']) ?></div>
            </div>
          <?php else: ?>
            <div class="msg-tr-loading">Çeviriliyor...</div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="msg-meta"><?= fmt_time($msg['sent_at']) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Mesaj gönderme -->
<div class="chat-input-area" id="chatInputArea">
  <?php if ($can_write): ?>
    <div style="display:flex;flex-direction:column;gap:8px">
      <textarea id="msgInput" class="form-control" rows="3"
                placeholder="Mesaj yaz... (Enter = gönder, Shift+Enter = yeni satır)"
                style="resize:none"></textarea>
      <div style="display:flex;justify-content:flex-end">
        <button class="btn" onclick="sendMessage()" style="background:#f07800;border-color:#f07800;color:#fff;border-radius:12px;padding:9px 20px;font-size:13px;font-weight:700;white-space:nowrap">
          <i class="bi bi-send-fill me-1"></i> Mesaj Gönder
        </button>
      </div>
    </div>
  <?php elseif (in_array($conv['status'], ['with_user']) && !$window_open): ?>
    <div class="window-closed-bar">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <div>
        <div class="title">24 Saatlik Mesajlaşma Penceresi Kapandı</div>
        <div class="sub">Hastanın son mesajından 24 saat geçti. WhatsApp kuralları gereği önce onaylı bir şablon mesajı göndermeniz gerekiyor.</div>
        <div class="sub mt-1"><i class="bi bi-info-circle"></i> Şablon mesajı özelliği yakında eklenecek.</div>
      </div>
    </div>
  <?php elseif ($conv['status'] === 'ai_active'): ?>
    <div class="ai-status-bar">
      <i class="bi bi-stars" style="color:#1c5ed8"></i>
      <span>AI bu konuşmayı yönetiyor</span>
    </div>
  <?php elseif ($conv['status'] === 'pending_takeover'): ?>
    <div class="ai-status-bar" style="color:#e16a00">
      <i class="bi bi-clock"></i>
      <span>Hasta insan desteği bekliyor</span>
      <button class="btn btn-sm btn-primary ms-2" onclick="takeOver()" style="background:var(--zb-accent);border-color:var(--zb-accent);border-radius:8px;font-size:12px">
        <i class="bi bi-person-check"></i> Devral
      </button>
    </div>
  <?php elseif ($conv['status'] === 'closed'): ?>
    <div class="ai-status-bar">
      <i class="bi bi-check-circle" style="color:#1c5ed8"></i>
      <span>Konuşma kapatıldı</span>
    </div>
  <?php else: ?>
    <div class="text-muted small text-center py-2">Yazma yetkiniz yok.</div>
  <?php endif; ?>
</div>

<script>
const CONV_ID    = <?= $conv_id ?>;
const CSRF_TOKEN = '<?= csrf_token() ?>';
let lastMsgId    = <?= !empty($messages) ? end($messages)['id'] : 0 ?>;
let translateMode = false;
let polling      = null;

// ── Sayfa yüklenince ────────────────────────────────────────────────────
scrollBottom();
polling = setInterval(pollMessages, 5000);
window.addEventListener('beforeunload', () => clearInterval(polling));

document.getElementById('msgInput')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// ── Çeviri modu ─────────────────────────────────────────────────────────
function toggleTranslateMode() {
  translateMode = !translateMode;
  const btn = document.getElementById('translateModeBtn');
  btn.classList.toggle('active', translateMode);
  btn.innerHTML = translateMode
    ? '🇹🇷 Çeviri Açık'
    : '🇹🇷 Türkçeye Çevir';

  if (translateMode) {
    // Mevcut tüm mesajları çevir
    document.querySelectorAll('.msg-bubble[data-needs-tr="1"]').forEach(bubble => {
      showTranslation(bubble);
    });
  } else {
    // Tüm çevirileri gizle
    document.querySelectorAll('.msg-tr-content').forEach(el => el.style.display = 'none');
  }
}

async function showTranslation(bubble) {
  const trContent = bubble.querySelector('.msg-tr-content');
  if (!trContent) return;

  trContent.style.display = 'block';

  // Zaten çevrilmiş
  if (bubble.dataset.hasTr === '1') return;
  if (bubble.dataset.translating) return;
  bubble.dataset.translating = '1';

  const msgId = bubble.dataset.msgId;
  const body  = bubble.dataset.body;
  if (!body) return;

  // Loading göster
  trContent.innerHTML = '<div class="msg-tr-loading">Çeviriliyor...</div>';

  const res = await fetch('/api/translate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
    body: JSON.stringify({ message_id: msgId, text: body }),
  });
  const data = await res.json();

  if (data.tr) {
    trContent.innerHTML = '<div class="msg-tr-wrap"><div class="msg-tr-label">🇹🇷 Türkçe</div><div class="msg-tr">' + renderMarkdown(data.tr) + '</div></div>';
    bubble.dataset.hasTr = '1';
  } else {
    trContent.innerHTML = '';
    trContent.style.display = 'none';
  }
  delete bubble.dataset.translating;
}

// ── Mesaj gönder ────────────────────────────────────────────────────────
async function sendMessage() {
  const input = document.getElementById('msgInput');
  const text  = input.value.trim();
  if (!text) return;
  input.value = '';

  const res = await fetch('/api/send.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
    body: JSON.stringify({ conversation_id: CONV_ID, body: text }),
  });
  const data = await res.json();
  if (data.ok) {
    appendMessage(data.message);
  } else if (data.error === 'window_closed') {
    showWindowClosedWarning();
  }
}

function showWindowClosedWarning() {
  const area = document.getElementById('chatInputArea');
  if (!area) return;
  area.innerHTML = `
    <div class="d-flex align-items-start gap-3 p-3" style="background:#fff8f0;border-top:1px solid #fed7aa">
      <i class="bi bi-exclamation-triangle-fill text-warning fs-5 mt-1"></i>
      <div>
        <div class="fw-600 text-warning-emphasis" style="font-size:13px">24 Saatlik Mesajlaşma Penceresi Kapandı</div>
        <div class="text-muted small mt-1">WhatsApp kuralları gereği hastanın son mesajından 24 saat geçti.
        Tekrar iletişime geçmek için Meta onaylı bir şablon mesajı göndermeniz gerekiyor.</div>
        <div class="mt-2 text-muted small"><i class="bi bi-info-circle"></i> Şablon mesajı özelliği yakında eklenecek.</div>
      </div>
    </div>`;
}

// ── Polling ─────────────────────────────────────────────────────────────
async function pollMessages() {
  try {
    const res  = await fetch(`/api/messages.php?conversation_id=${CONV_ID}&after=${lastMsgId}`);
    const data = await res.json();
    if (data.messages?.length > 0) {
      data.messages.forEach(msg => appendMessage(msg));
    }
  } catch(e) {}
}

// ── Yeni mesaj DOM'a ekle ────────────────────────────────────────────────
function appendMessage(msg) {
  const isInbound  = msg.direction === 'inbound';
  const isAI       = msg.sender_type === 'ai';
  const bubbleClass = isInbound ? 'inbound' : (isAI ? 'ai outbound' : 'outbound');
  const needsTr     = true; // yeni gelen mesajları çeviri modundaysa çevir

  const wrap = document.getElementById('chatMessages');
  const div  = document.createElement('div');
  div.className = 'msg-bubble ' + bubbleClass;
  div.dataset.msgId   = msg.id;
  div.dataset.body    = msg.body || '';
  div.dataset.needsTr = '1';
  div.dataset.hasTr   = '0';

  let senderLabel = '';
  if (isAI) senderLabel = '<div class="text-muted small mb-1" style="padding-left:4px"><i class="bi bi-robot"></i> AI</div>';
  else if (!isInbound && msg.sender_name) senderLabel = `<div class="text-muted small mb-1" style="text-align:right;padding-right:4px">${escHtml(msg.sender_name)}</div>`;

  div.innerHTML = `
    ${senderLabel}
    <div class="msg-content">${renderMarkdown(msg.body || '')}</div>
    <div class="msg-tr-content" style="display:none"><div class="msg-tr-loading">Çeviriliyor...</div></div>
    <div class="msg-meta">${msg.time}</div>
  `;
  wrap.appendChild(div);
  scrollBottom();
  lastMsgId = msg.id;

  // Çeviri modu açıksa yeni mesajı da çevir
  if (translateMode) showTranslation(div);
}

// ── Devral ──────────────────────────────────────────────────────────────
async function takeOver() {
  if (!confirm('Bu konuşmayı devralmak istiyor musunuz?')) return;
  const res = await fetch('/api/takeover.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
    body: JSON.stringify({ conversation_id: CONV_ID }),
  });
  const data = await res.json();
  if (data.ok) location.reload();
}

// ── Ata ─────────────────────────────────────────────────────────────────
async function assignTo(userId, userName) {
  if (!confirm(userName + ' kişisine atanacak. Onaylıyor musunuz?')) return;
  const res = await fetch('/api/assign.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
    body: JSON.stringify({ conversation_id: CONV_ID, user_id: userId }),
  });
  const data = await res.json();
  if (data.ok) location.reload();
}

// ── Kapat ────────────────────────────────────────────────────────────────
async function closeConversation() {
  if (!confirm('Konuşmayı kapatmak istiyor musunuz?')) return;
  const res = await fetch('/api/close.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
    body: JSON.stringify({ conversation_id: CONV_ID }),
  });
  const data = await res.json();
  if (data.ok) location.reload();
}

// ── Özet çıkar ──────────────────────────────────────────────────────────
async function generateSummary() {
  const btn = document.getElementById('btnSummary');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Özet çıkarılıyor...';

  try {
    const res = await fetch('https://api.zboxasist.com/portal/summary', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ conversation_id: CONV_ID, client_id: <?= $cid ?> }),
    });
    const data = await res.json();

    if (data.ok) {
      const panel = document.getElementById('summaryPanel');
      document.getElementById('summaryText').textContent = data.summary;
      panel.style.display = 'block';

      if (data.cached) {
        btn.innerHTML = '<i class="bi bi-card-text"></i> Özet (önbellek)';
      } else {
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Özet hazır';
      }
    } else {
      alert(data.error || 'Özet oluşturulamadı');
      btn.innerHTML = '<i class="bi bi-card-text"></i> Özet Çıkar';
    }
  } catch (err) {
    alert('Bağlantı hatası');
    btn.innerHTML = '<i class="bi bi-card-text"></i> Özet Çıkar';
  }

  btn.disabled = false;
}

function scrollBottom() {
  const el = document.getElementById('chatMessages');
  if (el) el.scrollTop = el.scrollHeight;
}

function renderMarkdown(str) {
  // Escape HTML
  let s = escHtml(str);
  // Bold ve italic
  s = s.replace(/\*\*([\s\S]+?)\*\*/g, '<b>$1</b>');
  s = s.replace(/\*([\s\S]+?)\*/g, '<i>$1</i>');
  // Paragraflar: çift satır sonu → <br><br> tek satır → <br>
  s = s.replace(/\n{2,}/g, '<br><br>');
  s = s.replace(/\n/g, '<br>');
  return s;
}

function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>