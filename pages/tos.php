<?php
$_ROOT = $_ROOT ?? sedap_root();
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Terma Penggunaan — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-light bg-white border-bottom px-4 py-3">
  <div class="container d-flex align-items-center justify-content-between">
    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-primary" href="<?= $_ROOT ?>/index.php">
      <span class="material-symbols-outlined" style="font-size:28px;">medical_services</span>SeDaP
    </a>
    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm">Kembali</a>
  </div>
</nav>

<div class="container py-5" style="max-width:800px;">
  <div class="card p-4 shadow-sm">
    <h1 class="fw-bold fs-3 mb-3">Terma Penggunaan (Terms of Service)</h1>
    <p class="text-muted small mb-4">Dikemas kini: <?= date('d M Y') ?></p>

    <h5 class="fw-bold">1. Pengenalan</h5>
    <p class="small text-muted">Selamat datang ke Sistem e-Data Awam Perubatan (SeDaP). Dengan mengakses platform ini, anda bersetuju untuk mematuhi segala terma dan syarat yang termaktub di bawah.</p>

    <h5 class="fw-bold mt-4">2. Penggunaan Data Kesihatan</h5>
    <p class="small text-muted">Data saringan dan triaj yang dikumpulkan adalah bertujuan untuk penyelarasan rawatan perubatan kecemasan komuniti, penilaian risiko kesihatan awam, dan pemantauan wabak.</p>

    <h5 class="fw-bold mt-4">3. Tanggungjawab Pengguna</h5>
    <p class="small text-muted">Pengguna bertanggungjawab memastikan maklumat yang dimasukkan adalah tepat dan benar mengikut pengetahuan terbaik mereka.</p>
  </div>
</div>
<?php include 'shared/includes/footer.php'; ?>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js"></script>
</body>
</html>
