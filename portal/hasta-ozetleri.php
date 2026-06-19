<?php
// PORTAL — portal/hasta-ozetleri.php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

auth_check();
if (!client_has('patient_summary')) redirect('/dashboard.php');

$page_title  = 'Hasta Özetleri';
$active_menu = 'hasta-ozetleri';

$pdo = db();
$cid = client_id();

$search   = trim(get('q', ''));
$per_page = 30;
$cur_page = max(1, (int)get('page', 1));
$offset   = ($cur_page - 1) * $per_page;

$where  = ['p.client_id = ?'];
$params = [$cid];

if ($search) {
    $where[]  = '(p.name LIKE ? OR p.phone LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = implode(' AND ', $where);

$cnt = $pdo->prepare("SELECT COUNT(*) FROM patients p WHERE $where_sql");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

$list = $pdo->prepare(
    "SELECT p.*,
            (SELECT COUNT(*) FROM conversations c WHERE c.patient_id = p.id) AS conv_count,
            (SELECT MAX(m.sent_at) FROM messages m
             JOIN conversations c2 ON c2.id = m.conversation_id
             WHERE c2.patient_id = p.id) AS last_message_at
     FROM patients p
     WHERE $where_sql
     ORDER BY p.last_contact DESC
     LIMIT $per_page OFFSET $offset"
);
$list->execute($params);
$patients = $list->fetchAll();

$pipe_labels = [
    'new'           => ['Yeni', 'secondary'],
    'photo_pending' => ['Fotoğraf bekleniyor', 'warning'],
    'price_given'   => ['Fiyat verildi', 'info'],
    'followup'      => ['Takipte', 'primary'],
    'won'           => ['Kazanıldı', 'success'],
    'lost'          => ['Kaybedildi', 'danger'],
];

$has_card = client_has('patient_card');

require_once __DIR__ . '/includes/layout_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h6 class="mb-0">Hasta Özetleri</h6>
    <div class="text-muted small"><?= $total ?> hasta</div>
  </div>
  <form method="GET" class="d-flex gap-2">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Ara..." value="<?= e($search) ?>" style="width:200px">
    <button class="btn btn-sm btn-primary">Ara</button>
    <?php if ($search): ?>
      <a href="/hasta-ozetleri.php" class="btn btn-sm btn-outline-secondary">✕</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th class="ps-3">Hasta</th>
          <th>Tedavi</th>
          <th>Durum</th>
          <th>Telefon</th>
          <th>Dil</th>
          <th>Ülke</th>
          <th>Konuşma</th>
          <th>İlk İletişim</th>
          <th>Son Mesaj</th>
          <?php if ($has_card): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($patients)): ?>
          <tr><td colspan="<?= $has_card ? 10 : 9 ?>" class="text-center text-muted py-4">Hasta bulunamadı.</td></tr>
        <?php else: ?>
          <?php foreach ($patients as $p): ?>
            <?php $row_url = $has_card ? '/hasta-detay.php?id=' . $p['id'] : null; ?>
            <tr <?= $row_url ? 'onclick="location.href=\'' . $row_url . '\'" style="cursor:pointer"' : '' ?>>
              <td class="ps-3 fw-500"><?= e($p['name'] ?: '—') ?></td>
              <td><?= e($p['treatment_interest'] ?: '—') ?></td>
              <td>
                <?php $pl = $pipe_labels[$p['pipeline_status'] ?? 'new'] ?? ['—', 'secondary']; ?>
                <span class="badge bg-<?= $pl[1] ?>"><?= $pl[0] ?></span>
              </td>
              <td class="text-muted"><?= e($p['phone']) ?></td>
              <td><?= $p['language'] ? lang_flag($p['language']) . ' ' . strtoupper(e($p['language'])) : '—' ?></td>
              <td class="text-muted"><?= e($p['country_code'] ?: '—') ?></td>
              <td class="fw-500"><?= $p['conv_count'] ?></td>
              <td class="text-muted small"><?= fmt_date($p['first_contact']) ?></td>
              <td class="text-muted small"><?= $p['last_message_at'] ? time_ago($p['last_message_at']) : '—' ?></td>
              <?php if ($has_card): ?>
                <td class="text-end pe-3">
                  <a href="/hasta-detay.php?id=<?= $p['id'] ?>"
                     onclick="event.stopPropagation()"
                     class="btn btn-sm btn-outline-secondary" title="Hasta Kartı">
                    <i class="bi bi-person-lines-fill"></i>
                  </a>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total_pages > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
      <div class="text-muted small"><?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?> / <?= $total ?></div>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i === $cur_page ? 'active' : '' ?>">
              <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/layout_footer.php'; ?>
