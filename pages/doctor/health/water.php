<?php
session_start();
require_once '../../config/db.php';
require_once '../../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'admin'])) {
    header('Location: ../../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Doktor');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_water_title', 'Pemantauan Air Minum') ?> — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../../shared/includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">water_drop</span><?= __('page_water_title', 'Pemantauan Pengambilan Air') ?></h1>
        <p class="page-subtitle"><?= __('page_water_sub', 'Panduan klinikal dan log hidrasi pesakit') ?></p>
      </div>
      <div class="row g-4">
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm text-center p-4">
            <span class="material-symbols-outlined text-primary mb-2" style="font-size:48px;">local_drink</span>
            <h5 class="fw-bold"><?= __('water_daily_target_title', 'Sasaran Hidrasi Harian') ?></h5>
            <p class="text-muted small"><?= __('water_target_guide', 'Pesakit dewasa disyorkan mengambil sekurang-kurangnya 2.0L - 2.5L (8-10 gelas) air bersih setiap hari, terutamanya semasa rawatan cirit-birit atau demam.') ?></p>
          </div>
        </div>
        <div class="col-md-6 col-lg-8">
          <div class="card h-100 shadow-sm">
            <div class="card-header"><span class="material-symbols-outlined">analytics</span><strong><?= __('water_zone_status_title', 'Status Hidrasi Mengikut Zon') ?></strong></div>
            <div class="card-body">
              <div class="alert alert-success d-flex align-items-center gap-2">
                <span class="material-symbols-outlined">check_circle</span>
                <?= __('water_zone_alert', 'Bekalan air bersih dan larutan ORS tersedia di semua pos rawatan komuniti.') ?>
              </div>
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
</body>
</html>
