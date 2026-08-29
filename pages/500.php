<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>500 — Ralat Pelayan | SeDaP</title>
  <link rel="stylesheet" href="/sedap/sedap2.0/assets/css/coreui.min.css">
  <link rel="stylesheet" href="/sedap/sedap2.0/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    body { font-family:'Inter',sans-serif; background:#f4f6f9; min-height:100vh; display:flex; align-items:center; justify-content:center; }
    .error-number { font-size:min(25vw,160px); font-weight:900; line-height:1; background:linear-gradient(135deg,#C0392B,#e74c3c); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
  </style>
</head>
<body>
<div class="text-center px-4" style="max-width:520px;">
  <div class="error-number">500</div>
  <span class="material-symbols-outlined text-danger d-block mb-3" style="font-size:56px;">error</span>
  <h1 class="fw-bold fs-3 mb-2">Ralat Dalaman Pelayan</h1>
  <p class="text-muted mb-4">
    Maaf, berlaku masalah teknikal pada pelayan. Pasukan kami sedang menyiasat. Sila cuba sebentar lagi.
  </p>
  <div class="d-flex justify-content-center gap-3 flex-wrap">
    <button onclick="location.reload()" class="btn btn-outline-danger d-flex align-items-center gap-1">
      <span class="material-symbols-outlined" style="font-size:18px;">refresh</span> Cuba Lagi
    </button>
    <a href="/sedap/sedap2.0/pages/auth/login.php" class="btn btn-primary d-flex align-items-center gap-1">
      <span class="material-symbols-outlined" style="font-size:18px;">home</span> Halaman Utama
    </a>
  </div>
  <p class="text-muted small mt-4">
    Jika masalah berterusan, hubungi sokongan di
    <a href="mailto:support@sedap.gov.my" style="color:#087383;">support@sedap.gov.my</a>
  </p>
</div>
<script src="/sedap/sedap2.0/assets/js/coreui.bundle.min.js"></script>
</body>
</html>
