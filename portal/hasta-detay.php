<?php
// PORTAL — portal/hasta-detay.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

auth_check();
if (!client_has('patient_card')) redirect('/dashboard.php');

$pdo = db();
$cid = client_id();
$uid = portal_user_id();

$patient_id = (int)get('id');
if (!$patient_id) redirect('/dashboard.php');

// Hasta bu client'a mı ait?
$patient = $pdo->prepare(
    "SELECT p.*,
            (SELECT COUNT(*) FROM conversations c WHERE c.patient_id = p.id AND c.client_id = ?) AS conv_count,
            (SELECT MAX(m.sent_at) FROM messages m
             JOIN conversations c2 ON c2.id = m.conversation_id
             WHERE c2.patient_id = p.id AND c2.client_id = ?) AS last_message_at
     FROM patients p
     WHERE p.id = ? AND p.client_id = ?"
);
$patient->execute([$cid, $cid, $patient_id, $cid]);
$patient = $patient->fetch();
if (!$patient) redirect('/dashboard.php');

// Hastane paketinde: sadece koordinatör veya atanmış kullanıcı görebilir
if (client_has('coordinator')) {
    $is_coordinator = is_coordinator();
    if (!$is_coordinator) {
        // Bu hastanın herhangi bir konuşmasına atanmış mı?
        $assigned = $pdo->prepare(
            "SELECT COUNT(*) FROM conversations
             WHERE patient_id = ? AND client_id = ? AND assigned_to = ?"
        );
        $assigned->execute([$patient_id, $cid, $uid]);
        if ((int)$assigned->fetchColumn() === 0) {
            redirect('/dashboard.php');
        }
    }
}

// Konuşmalar
$conversations = $pdo->prepare(
    "SELECT c.id, c.status, c.topic_summary, c.summary_text, c.started_at, c.updated_at,
            c.module_id, tm.name AS module_name,
            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id) AS msg_count,
            (SELECT MAX(m.sent_at) FROM messages m WHERE m.conversation_id = c.id) AS last_msg_at,
            pu.name AS assigned_to_name
     FROM conversations c
     LEFT JOIN treatment_modules tm ON tm.id = c.module_id
     LEFT JOIN portal_users pu ON pu.id = c.assigned_to
     WHERE c.patient_id = ? AND c.client_id = ?
     ORDER BY c.updated_at DESC"
);
$conversations->execute([$patient_id, $cid]);
$conversations = $conversations->fetchAll();

// Son özet
$last_summary = '';
foreach ($conversations as $c) {
    if ($c['summary_text']) {
        $last_summary = $c['summary_text'];
        break;
    }
}

// Notlar — görünürlük kuralı
$notes_query = "SELECT n.*, pu.name AS user_name
                FROM patient_notes n
                JOIN portal_users pu ON pu.id = n.portal_user_id
                WHERE n.patient_id = ? AND n.client_id = ?";

// Hastane paketinde koordinatör değilse sadece kendi notlarını + atanan konuşmaların notlarını görebilir
// Ama biz şimdi basit tutuyoruz: herkes tüm notları görür (Solo/Ekip), Hastane zaten yukarıda filtreli
$notes_stmt = $pdo->prepare($notes_query . " ORDER BY n.created_at DESC");
$notes_stmt->execute([$patient_id, $cid]);
$notes = $notes_stmt->fetchAll();

// Not ekleme POST
$note_error = '';
$note_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    csrf_verify();
    $note_text = trim(post('note'));
    if (!$note_text) {
        $note_error = 'Not boş olamaz.';
    } else {
        $pdo->prepare(
            "INSERT INTO patient_notes (patient_id, client_id, portal_user_id, note)
             VALUES (?, ?, ?, ?)"
        )->execute([$patient_id, $cid, $uid, $note_text]);
        $note_success = true;
        // Refresh
        redirect('/hasta-detay.php?id=' . $patient_id . '&noted=1');
    }
}

$page_title  = $patient['name'] ?: $patient['phone'];
$active_menu = 'dashboard';

$pipe_labels = [
    'new'           => ['Yeni', 'secondary'],
    'photo_pending' => ['Fotoğraf Bekleniyor', 'warning'],
    'price_given'   => ['Fiyat Verildi', 'info'],
    'followup'      => ['Takipte', 'primary'],
    'won'           => ['Kazanıldı', 'success'],
    'lost'          => ['Kaybedildi', 'danger'],
];

require_once __DIR__ . '/includes/layout_header.php';
?>

<!-- Breadcrumb -->
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="/dashboard.php" class="text-muted text-decoration-none small">
        <i class="bi bi-arrow-left"></i> Inbox
    </a>
    <?php if (client_has('patient_summary')): ?>
        <span class="text-muted small">/</span>
        <a href="/hasta-ozetleri.php" class="text-muted text-decoration-none small">Hasta Özetleri</a>
    <?php endif; ?>
    <span class="text-muted small">/</span>
    <span class="small"><?= e($patient['name'] ?: $patient['phone']) ?></span>
</div>

<?php if (get('noted')): ?>
    <div class="alert alert-success py-2 mb-3 small">Not eklendi.</div>
<?php endif; ?>

<div class="row g-3">

    <!-- SOL KOLON: Hasta profili + Notlar -->
    <div class="col-md-4">

        <!-- Hasta Bilgileri -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-circle me-1"></i> Hasta Bilgileri</span>
                <span class="text-muted small">#<?= $patient['id'] ?></span>
            </div>
            <div class="card-body">
                <div class="mb-3 text-center">
                    <div style="width:56px;height:56px;border-radius:50%;background:var(--zb-primary-soft);
                                color:var(--zb-primary);font-size:22px;font-weight:700;
                                display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                        <?= mb_strtoupper(mb_substr($patient['name'] ?: '?', 0, 1)) ?>
                    </div>
                    <div class="fw-600 fs-6"><?= e($patient['name'] ?: '—') ?></div>
                    <div class="text-muted small"><?= e($patient['phone']) ?></div>
                </div>

                <hr class="my-2">

                <div class="row g-2 small">
                    <div class="col-6">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Dil</div>
                        <div><?= $patient['language'] ? lang_flag($patient['language']) . ' ' . strtoupper($patient['language']) : '—' ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Ülke</div>
                        <div><?= e($patient['country_code'] ?: '—') ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">İlk İletişim</div>
                        <div><?= $patient['created_at'] ? date('d.m.Y', strtotime($patient['created_at'])) : '—' ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Son Mesaj</div>
                        <div><?= $patient['last_message_at'] ? time_ago($patient['last_message_at']) : '—' ?></div>
                    </div>
                    <div class="col-12 mt-1">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Tedavi İlgisi</div>
                        <div><?= e($patient['treatment_interest'] ?: '—') ?></div>
                    </div>
                    <div class="col-12 mt-1">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Pipeline</div>
                        <div>
                            <?php
                            $ps = $patient['pipeline_status'] ?? 'new';
                            [$pl_label, $pl_class] = $pipe_labels[$ps] ?? ['—', 'secondary'];
                            ?>
                            <span class="badge bg-<?= $pl_class ?>"><?= $pl_label ?></span>
                            <?php if ($patient['pipeline_updated_at']): ?>
                                <span class="text-muted small ms-1"><?= date('d.m.Y', strtotime($patient['pipeline_updated_at'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-muted small px-3 py-2">
                <i class="bi bi-chat-dots me-1"></i> <?= $patient['conv_count'] ?> konuşma
            </div>
        </div>

        <!-- Son AI Özeti -->
        <?php if ($last_summary && client_has('patient_summary')): ?>
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-file-text me-1"></i> Son AI Özeti
            </div>
            <div class="card-body small" style="white-space:pre-line;line-height:1.6">
                <?= e($last_summary) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Notlar -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-sticky me-1"></i> Notlar</span>
                <span class="badge bg-secondary"><?= count($notes) ?></span>
            </div>
            <div class="card-body p-0">

                <!-- Not Ekle -->
                <form method="POST" class="p-3 border-bottom">
                    <?= csrf_field() ?>
                    <input type="hidden" name="add_note" value="1">
                    <?php if ($note_error): ?>
                        <div class="alert alert-danger py-1 small mb-2"><?= e($note_error) ?></div>
                    <?php endif; ?>
                    <textarea name="note" class="form-control form-control-sm mb-2"
                              rows="2" placeholder="Not ekle..." style="resize:none;font-size:13px"></textarea>
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i> Not Ekle
                    </button>
                </form>

                <!-- Not Listesi -->
                <?php if (empty($notes)): ?>
                    <div class="text-center text-muted small py-3">Henüz not eklenmedi.</div>
                <?php else: ?>
                    <div style="max-height:400px;overflow-y:auto">
                        <?php foreach ($notes as $note): ?>
                            <div class="px-3 py-2 border-bottom" style="font-size:13px">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="fw-500 small"><?= e($note['user_name']) ?></span>
                                    <span class="text-muted" style="font-size:11px;white-space:nowrap">
                                        <?= date('d.m.Y H:i', strtotime($note['created_at'])) ?>
                                    </span>
                                </div>
                                <div style="color:var(--zb-text);white-space:pre-line;line-height:1.5">
                                    <?= e($note['note']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /SOL KOLON -->

    <!-- SAĞ KOLON: Konuşmalar -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-chat-dots me-1"></i> Konuşmalar
                <span class="text-muted small ms-1">(<?= count($conversations) ?>)</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($conversations)): ?>
                    <div class="text-center text-muted small py-4">Konuşma bulunamadı.</div>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <a href="/konusma.php?id=<?= $conv['id'] ?>"
                           class="d-block px-4 py-3 border-bottom text-decoration-none"
                           style="transition:background .1s" onmouseover="this.style.background='var(--zb-bg)'" onmouseout="this.style.background=''">

                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <?= conv_status_badge($conv['status']) ?>
                                    <?php if ($conv['module_name']): ?>
                                        <span class="text-muted small"><?= e($conv['module_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-muted" style="font-size:12px;white-space:nowrap">
                                    <?= $conv['last_msg_at'] ? time_ago($conv['last_msg_at']) : date('d.m.Y', strtotime($conv['created_at'])) ?>
                                </span>
                            </div>

                            <?php if ($conv['topic_summary']): ?>
                                <div class="small mb-1" style="color:var(--zb-primary)">
                                    <i class="bi bi-tag"></i> <?= e($conv['topic_summary']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($conv['summary_text']): ?>
                                <div class="text-muted small" style="line-height:1.4">
                                    <?= e(mb_strimwidth($conv['summary_text'], 0, 180, '...')) ?>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex gap-3 mt-2" style="font-size:12px;color:var(--zb-text-muted)">
                                <span><i class="bi bi-chat me-1"></i><?= $conv['msg_count'] ?> mesaj</span>
                                <?php if ($conv['assigned_to_name']): ?>
                                    <span><i class="bi bi-person-check me-1"></i><?= e($conv['assigned_to_name']) ?></span>
                                <?php endif; ?>
                                <span><i class="bi bi-calendar me-1"></i><?= date('d.m.Y', strtotime($conv['started_at'])) ?></span>
                            </div>

                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div><!-- /SAĞ KOLON -->

</div><!-- /row -->

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
