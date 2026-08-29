<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>404 — Halaman Tidak Dijumpai | SeDaP</title>
  <link rel="stylesheet" href="/sedap/sedap2.0/assets/css/coreui.min.css">
  <link rel="stylesheet" href="/sedap/sedap2.0/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    body { font-family:'Inter',sans-serif; background:#f4f6f9; min-height:100vh; display:flex; align-items:center; justify-content:center; }
    .error-number { font-size:min(25vw,160px); font-weight:900; line-height:1; background:linear-gradient(135deg,#087383,#0a9eb5); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
  </style>
</head>
<body>
<div class="text-center px-4" style="max-width:520px;">
  <div class="error-number">404</div>
  <span class="material-symbols-outlined text-secondary d-block mb-3" style="font-size:56px;">search_off</span>
  <h1 class="fw-bold fs-3 mb-2">Halaman Tidak Dijumpai</h1>
  <p class="text-muted mb-4">
    Halaman yang anda cari tidak wujud, telah dialihkan, atau anda tidak mempunyai akses kepadanya.
  </p>
  <div class="d-flex justify-content-center gap-3 flex-wrap">
    <button onclick="history.back()" class="btn btn-outline-secondary d-flex align-items-center gap-1">
      <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Kembali
    </button>
    <a href="/sedap/sedap2.0/pages/auth/login.php" class="btn btn-primary d-flex align-items-center gap-1">
      <span class="material-symbols-outlined" style="font-size:18px;">home</span> Log Masuk
    </a>
  </div>
</div>
<script src="/sedap/sedap2.0/assets/js/coreui.bundle.min.js"></script>
</body>
</html>
