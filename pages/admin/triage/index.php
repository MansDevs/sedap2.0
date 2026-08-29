<?php
session_start();
require_once '../../config/db.php';
require_once '../../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap/sedap2.0';

$triages = [];
try {
    $triages = $pdo->query("SELECT tr.*, p.full_name AS patient_name, p.ic_number, u.name AS staff_name FROM triage_records tr LEFT JOIN patients p ON tr.patient_id=p.id LEFT JOIN users u ON tr.triaged_by=u.id ORDER BY tr.triaged_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pengurusan Triaj — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../../shared/includes/sidebar_admin.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
          <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">monitor_heart</span>Pengurusan Triaj</h1>
          <p class="page-subtitle">Paparan pentadbiran rekod triaj dan statistik kesihatan komuniti</p>
        </div>
        <a href="add.php" class="btn btn-primary d-flex align-items-center gap-1">
          <span class="material-symbols-outlined" style="font-size:18px;">add_circle</span>Triaj Baharu
        </a>
      </div>

      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr><th>#</th><th>Pesakit</th><th>No. IC</th><th>Tahap</th><th>Aduan</th><th><?= __('col_status', 'Status') ?></th><th>Ditapis Oleh</th><th><?= __('col_time', 'Masa') ?></th></tr>
              </thead>
              <tbody>
                <?php if (empty($triages)): ?>
                  <tr><td colspan="8" class="text-center text-muted py-4"><?= __('doc_no_records', 'Tiada rekod triaj dijumpai') ?></td></tr>
                <?php else: ?>
                  <?php foreach ($triages as $idx => $tr): 
                    $lv = strtolower($tr['triage_level'] ?? 'green');
                    $bcMap = ['red' => 'badge-triage-red', 'yellow' => 'badge-triage-yellow', 'green' => 'badge-triage-green'];
                        $bc = $bcMap[$lv] ?? 'badge-triage-green';
                    $llMap = ['red' => __('triage_red', 'Merah'), 'yellow' => __('triage_yellow', 'Kuning'), 'green' => __('triage_green', 'Hijau')];
                        $ll = $llMap[$lv] ?? 'Hijau';
                  ?>
                  <tr>
                    <td><?= $idx + 1 ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($tr['patient_name'] ?? 'Pesakit #' . $tr['id']) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($tr['ic_number'] ?? '—') ?></td>
                    <td><span class="badge <?= $bc ?>"><?= $ll ?></span></td>
                    <td class="small"><?= htmlspecialchars(mb_strimwidth($tr['chief_complaint'] ?? '—', 0, 35, '…')) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($tr['status'] ?? 'waiting') ?></span></td>
                    <td class="small"><?= htmlspecialchars($tr['staff_name'] ?? 'Sistem') ?></td>
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
  <?php include '../../shared/includes/footer.php'; ?>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
</body>
</html>
