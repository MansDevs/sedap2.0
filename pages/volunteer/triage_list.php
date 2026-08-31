<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Sukarelawan');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';

$triages = $pdo->query("SELECT tr.*, p.full_name AS patient_name, p.ic_number FROM triage_records tr LEFT JOIN patients p ON tr.patient_id=p.id ORDER BY tr.triaged_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
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
  <?php include '../shared/includes/sidebar_volunteer.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">format_list_bulleted</span>Senarai Triaj Lapangan</h1>
      </div>
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light"><tr><th>Pesakit</th><th>Tahap</th><th>Aduan</th><th><?= __('col_time', 'Masa') ?></th></tr></thead>
              <tbody>
                <?php foreach ($triages as $t): 
                  $lv = strtolower($t['triage_level'] ?? 'green');
                  $bcMap = ['red' => 'badge-triage-red', 'yellow' => 'badge-triage-yellow', 'green' => 'badge-triage-green'];
                        $bc = $bcMap[$lv] ?? 'badge-triage-green';
                ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($t['patient_name'] ?? 'Pesakit') ?></td>
                    <td><span class="badge <?= $bc ?>"><?= ucfirst($lv) ?></span></td>
                    <td class="small"><?= htmlspecialchars(mb_strimwidth($t['chief_complaint'] ?? '—', 0, 40, '…')) ?></td>
                    <td class="small text-muted"><?= $t['triaged_at'] ? date('d/m H:i', strtotime($t['triaged_at'])) : '—' ?></td>
                  </tr>
                <?php endforeach; ?>
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
</body>
</html>
