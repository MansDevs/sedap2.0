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
  <title><?= __('page_medicine_title', 'Pengurusan Ubat') ?> — SeDaP</title>
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
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">medication</span><?= __('page_medicine_title', 'Preskripsi & Peringatan Ubat') ?></h1>
        <p class="page-subtitle"><?= __('page_medicine_sub', 'Senarai ubat-ubatan lazim dan pematuhan dos pesakit') ?></p>
      </div>
      <div class="card">
        <div class="card-header"><span class="material-symbols-outlined">inventory_2</span><strong><?= __('med_stock_title', 'Senarai Stok Ubat Rawatan Awal') ?></strong></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr><th><?= __('col_med_name', 'Nama Ubat') ?></th><th><?= __('col_category', 'Kategori') ?></th><th><?= __('col_standard_dosage', 'Dos Lazim') ?></th><th><?= __('col_stock_status', 'Status Stok') ?></th></tr>
              </thead>
              <tbody>
                <tr><td>Oral Rehydration Salts (ORS)</td><td><?= __('cat_electrolyte', 'Elektrolit') ?></td><td><?= __('ors_dosage_desc', '1 paket dalam 250ml air selepas cirit') ?></td><td><span class="badge bg-success"><?= __('status_adequate', 'Mencukupi') ?></span></td></tr>
                <tr><td>Paracetamol 500mg</td><td><?= __('cat_antipyretic', 'Antipiretik / Analgesik') ?></td><td><?= __('pcm_dosage_desc', '1-2 biji setiap 6 jam jika demam') ?></td><td><span class="badge bg-success"><?= __('status_adequate', 'Mencukupi') ?></span></td></tr>
                <tr><td>Metoclopramide 10mg</td><td><?= __('cat_antiemetic', 'Antiemetik') ?></td><td><?= __('meto_dosage_desc', '1 biji 3 kali sehari sebelum makan') ?></td><td><span class="badge bg-success"><?= __('status_adequate', 'Mencukupi') ?></span></td></tr>
                <tr><td>Loperamide 2mg</td><td><?= __('cat_antidiarrheal', 'Antidiarrheal') ?></td><td><?= __('lop_dosage_desc', 'Atas preskripsi doktor sahaja') ?></td><td><span class="badge bg-warning text-dark"><?= __('status_controlled', 'Kawalan Ketat') ?></span></td></tr>
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
