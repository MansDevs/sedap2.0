<?php
session_start();
require_once '../../config/db.php';
require_once '../../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Pesakit');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_mood_title', 'Jurnal Mood') ?> — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
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
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">sentiment_satisfied</span>Jurnal Mood &amp; Kesejahteraan</h1>
        <p class="page-subtitle">Catatkan perasaan harian anda untuk kesihatan mental yang lebih baik</p>
      </div>

      <div class="card p-4">
        <h6 class="fw-bold mb-3">Bagaimanakah perasaan anda hari ini?</h6>
        <div class="d-flex gap-3 flex-wrap mb-4">
          <button class="btn btn-outline-secondary p-3 text-center" style="font-size:24px;">😄<div class="small mt-1 fs-6">Sangat Baik</div></button>
          <button class="btn btn-outline-secondary p-3 text-center" style="font-size:24px;">🙂<div class="small mt-1 fs-6">Tenang</div></button>
          <button class="btn btn-outline-secondary p-3 text-center" style="font-size:24px;">😐<div class="small mt-1 fs-6">Biasa</div></button>
          <button class="btn btn-outline-secondary p-3 text-center" style="font-size:24px;">😟<div class="small mt-1 fs-6">Cemas</div></button>
          <button class="btn btn-outline-secondary p-3 text-center" style="font-size:24px;">😭<div class="small mt-1 fs-6">Sedih</div></button>
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
