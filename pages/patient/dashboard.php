<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php'); exit;
}
$userId    = $_SESSION['user_id'];
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Pesakit');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap/sedap2.0';

// Fetch patient's own triage records
$myTriages = [];
try {
    $myTriages = $pdo->prepare("SELECT tr.triage_level, tr.chief_complaint, tr.status, tr.triaged_at FROM triage_records tr JOIN patients p ON tr.patient_id=p.id WHERE p.user_id=? ORDER BY tr.triaged_at DESC LIMIT 5");
    $myTriages->execute([$userId]);
    $myTriages = $myTriages->fetchAll();
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Portal Pesakit — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar_user.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="mb-4">
        <h1 class="page-title">
          <span class="material-symbols-outlined" style="color:var(--cui-primary);">health_and_safety</span>
          Portal Pesakit
        </h1>
        <p class="page-subtitle">Selamat datang, <?= $userName ?>. Semak status kesihatan anda di sini.</p>
      </div>

      <div class="row g-4">
        <!-- My Triage Records -->
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <span class="material-symbols-outlined">monitor_heart</span>
              <strong>Rekod Triaj Saya</strong>
            </div>
            <div class="card-body p-0">
              <?php if (empty($myTriages)): ?>
                <div class="p-4 text-center text-muted">
                  <span class="material-symbols-outlined d-block" style="font-size:48px;opacity:.3;">assignment</span>
                  <?= __('doc_no_records', 'Tiada rekod triaj') ?> ditemui.
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr><th>Tahap</th><th>Aduan</th><th><?= __('col_status', 'Status') ?></th><th>Tarikh</th></tr>
                    </thead>
                    <tbody>
                      <?php foreach ($myTriages as $t):
                        $lv = strtolower($t['triage_level']);
                        $bcMap = ['red' => 'badge-triage-red', 'yellow' => 'badge-triage-yellow', 'green' => 'badge-triage-green'];
                        $bc = $bcMap[$lv] ?? 'badge-triage-green';
                        $llMap = ['red' => __('triage_red', 'Merah'), 'yellow' => __('triage_yellow', 'Kuning'), 'green' => __('triage_green', 'Hijau')];
                        $ll = $llMap[$lv] ?? 'Hijau';
                      ?>
                      <tr>
                        <td><span class="badge <?= $bc ?>"><?= $ll ?></span></td>
                        <td class="small"><?= htmlspecialchars(mb_strimwidth($t['chief_complaint'] ?? '—', 0, 40, '…')) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($t['status']) ?></span></td>
                        <td class="small text-muted"><?= $t['triaged_at'] ? date('d/m/Y H:i', strtotime($t['triaged_at'])) : '—' ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header"><span class="material-symbols-outlined">apps</span><strong>Pautan Pantas</strong></div>
            <div class="card-body d-flex flex-column gap-2">
              <a href="settings.php" class="btn btn-outline-primary w-100 d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="font-size:18px;">settings</span>Tetapan Akaun
              </a>
              <a href="/sedap/sedap2.0/pages/tos.php" class="btn btn-outline-secondary w-100 d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="font-size:18px;">gavel</span>Terma Penggunaan
              </a>
              <a href="/sedap/sedap2.0/pages/privacy.php" class="btn btn-outline-secondary w-100 d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="font-size:18px;">privacy_tip</span>Dasar Privasi
              </a>
              <a href="../auth/logout.php" class="btn btn-outline-danger w-100 d-flex align-items-center gap-2 mt-2">
                <span class="material-symbols-outlined" style="font-size:18px;">logout</span>Log Keluar
              </a>
            </div>
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
