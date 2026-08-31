<?php
session_start();
require_once '../../config/db.php';
require_once '../../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../../auth/login.php'); exit;
}
$userId    = $_SESSION['user_id'];
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Pesakit');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Penjejak Air — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../../shared/includes/sidebar_user.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">water_drop</span>Penjejak Pengambilan Air Minum</h1>
        <p class="page-subtitle">Pantau sasaran pengambilan 8 gelas air bersih setiap hari</p>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-4">
          <div class="card text-center p-4">
            <h5 class="fw-bold mb-3">Gelas Diminum Hari Ini</h5>
            <div class="display-3 fw-bold text-primary mb-3" id="glassCount">0 / 8</div>
            <div class="d-flex justify-content-center gap-2">
              <button class="btn btn-outline-secondary" onclick="addGlass(-1)">- 1 Gelas</button>
              <button class="btn btn-primary" onclick="addGlass(1)">+ 1 Gelas</button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <?php include '../../shared/includes/footer.php'; ?>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
<script>
let count = 0;
function addGlass(n) {
  count = Math.max(0, count + n);
  document.getElementById('glassCount').textContent = count + ' / 8';
}
</script>
</body>
</html>
