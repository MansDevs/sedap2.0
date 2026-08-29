<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'admin'])) {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Doktor');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap/sedap2.0';

$triages = [];
try {
    $stmt = $pdo->query("SELECT tr.*, p.full_name AS patient_name, p.ic_number 
                         FROM triage_records tr 
                         LEFT JOIN patients p ON tr.patient_id = p.id 
                         ORDER BY tr.triaged_at DESC LIMIT 50");
    $triages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_triage_list_title', 'Senarai Triaj') ?> — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
          <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">format_list_bulleted</span><?= __('page_triage_list_title', 'Senarai Triaj Langsung') ?></h1>
          <p class="page-subtitle"><?= __('page_triage_list_sub', 'Pantau dan tapis tahap keseriusan pesakit secara masa nyata') ?></p>
        </div>
        <div class="d-flex gap-2">
          <a href="triage_counter.php" class="btn btn-primary d-flex align-items-center gap-1">
            <span class="material-symbols-outlined" style="font-size:18px;">add_circle</span>Daftar Triaj
          </a>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-outline-secondary active" onclick="filterTable('all', this)"><?= __('btn_all', 'Semua') ?></button>
              <button type="button" class="btn btn-outline-danger" onclick="filterTable('red', this)"><?= __('triage_red', 'Kritikal (Merah)') ?></button>
              <button type="button" class="btn btn-outline-warning" onclick="filterTable('yellow', this)"><?= __('triage_yellow', 'Separa Kritikal (Kuning)') ?></button>
              <button type="button" class="btn btn-outline-success" onclick="filterTable('green', this)"><?= __('triage_green', 'Biasa (Hijau)') ?></button>
            </div>
            <input type="text" id="searchInput" class="form-control" style="max-width:280px;" placeholder="<?= __('search_placeholder', 'Cari nama atau IC...') ?>" onkeyup="searchTable()">
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="triageTable">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th><?= __('col_patient_name', 'Nama Pesakit') ?></th>
                  <th><?= __('col_ic', 'No. IC / ID') ?></th>
                  <th><?= __('col_level', 'Tahap Triaj') ?></th>
                  <th><?= __('col_complaint', 'Aduan Utama') ?></th>
                  <th><?= __('col_status', 'Status') ?></th>
                  <th><?= __('col_time', 'Masa') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($triages)): ?>
                  <tr><td colspan="7" class="text-center text-muted py-4"><?= __('doc_no_records', 'Tiada rekod triaj dijumpai') ?></td></tr>
                <?php else: ?>
                  <?php foreach ($triages as $i => $tr): 
                    $lv = strtolower($tr['triage_level'] ?? 'green');
                    $bcMap = ['red' => 'badge-triage-red', 'yellow' => 'badge-triage-yellow', 'green' => 'badge-triage-green'];
                        $bc = $bcMap[$lv] ?? 'badge-triage-green';
                    $llMap = ['red' => __('triage_red', 'Merah'), 'yellow' => __('triage_yellow', 'Kuning'), 'green' => __('triage_green', 'Hijau')];
                        $ll = $llMap[$lv] ?? 'Hijau';
                  ?>
                  <tr data-level="<?= $lv ?>">
                    <td><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($tr['patient_name'] ?? 'Pesakit #' . $tr['id']) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($tr['ic_number'] ?? '—') ?></td>
                    <td><span class="badge <?= $bc ?>"><?= $ll ?></span></td>
                    <td class="small"><?= htmlspecialchars(mb_strimwidth($tr['chief_complaint'] ?? '—', 0, 45, '…')) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($tr['status'] ?? 'waiting') ?></span></td>
                    <td class="small text-muted"><?= $tr['triaged_at'] ? date('d/m H:i', strtotime($tr['triaged_at'])) : '—' ?></td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
  <?php include '../shared/includes/footer.php'; ?>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
<script>
function filterTable(level, btn) {
  document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const rows = document.querySelectorAll('#triageTable tbody tr');
  rows.forEach(r => {
    if (level === 'all' || r.getAttribute('data-level') === level) {
      r.style.display = '';
    } else {
      r.style.display = 'none';
    }
  });
}
function searchTable() {
  const filter = document.getElementById('searchInput').value.toLowerCase();
  const rows = document.querySelectorAll('#triageTable tbody tr');
  rows.forEach(r => {
    const text = r.textContent.toLowerCase();
    r.style.display = text.includes(filter) ? '' : 'none';
  });
}
</script>
</body>
</html>
