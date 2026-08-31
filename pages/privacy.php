<?php
$_ROOT = '/sedap2.0';
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dasar Privasi — SeDaP</title>
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
    <h1 class="fw-bold fs-3 mb-3">Dasar Privasi (Privacy Policy)</h1>
    <p class="text-muted small mb-4">Dikemas kini: <?= date('d M Y') ?></p>

    <h5 class="fw-bold">1. Perlindungan Data Peribadi</h5>
    <p class="small text-muted">SeDaP komited untuk melindungi privasi data peribadi dan rekod kesihatan semua pesakit mengikut Akta Perlindungan Data Peribadi (PDPA) 2010.</p>

    <h5 class="fw-bold mt-4">2. Pengumpulan Data</h5>
    <p class="small text-muted">Kami mengumpul nama, nombor kad pengenalan, nombor telefon, data tanda vital, dan gejala saringan semata-mata untuk tujuan bantuan perubatan.</p>

    <h5 class="fw-bold mt-4">3. Keselamatan Maklumat</h5>
    <p class="small text-muted">Semua maklumat perubatan disimpan dalam pangkalan data yang dilindungi dan hanya boleh diakses oleh petugas kesihatan bertauliah dan pentadbir sistem.</p>
  </div>
</div>
<?php include 'shared/includes/footer.php'; ?>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js"></script>
</body>
</html>
