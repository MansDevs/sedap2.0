<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';

$_ROOT  = '/sedap/sedap2.0';
$error  = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $new_pw   = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($new_pw)) {
        $error = 'Sila isi semua medan.';
    } elseif ($new_pw !== $confirm) {
        $error = 'Kata laluan baru tidak sepadan.';
    } elseif (strlen($new_pw) < 8) {
        $error = 'Kata laluan mestilah sekurang-kurangnya 8 aksara.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username=? AND email=? AND status='active'");
            $stmt->execute([$username, $email]);
            $user = $stmt->fetch();
            if ($user) {
                $hash = password_hash($new_pw, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $user['id']]);
                $success = 'Kata laluan berjaya dikemas kini! Sila log masuk dengan kata laluan baharu.';
            } else {
                $error = 'Nama pengguna dan e-mel tidak sepadan dalam sistem.';
            }
        } catch (Exception $e) {
            $error = 'Ralat sistem. Sila cuba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Lupa Kata Laluan — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>body { font-family:'Inter',sans-serif; }</style>
</head>
<body>
<div class="row g-0 min-vh-100">
  <!-- Left Panel -->
  <div class="col-md-5 auth-brand-panel d-none d-md-flex">
    <div class="text-center">
      <span class="material-symbols-outlined" style="font-size:72px;color:rgba(255,255,255,.9);">lock_reset</span>
      <h1 class="display-5 fw-bold text-white mt-3 mb-2">Tetapkan Semula</h1>
      <p class="text-white opacity-75 small px-4">Masukkan nama pengguna dan e-mel berdaftar untuk menetapkan semula kata laluan.</p>
    </div>
  </div>

  <!-- Right Form -->
  <div class="col-md-7 d-flex align-items-center justify-content-center bg-white px-4 py-5">
    <div style="width:100%;max-width:400px;">
      <h2 class="fw-bold mb-1">Lupa Kata Laluan?</h2>
      <p class="text-muted small mb-4">Sahkan identiti anda dan tetapkan kata laluan baharu.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
          <span class="material-symbols-outlined" style="font-size:18px;">error</span>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 py-2">
          <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span>
          <?= htmlspecialchars($success) ?>
          <a href="login.php" class="ms-2 fw-semibold">Log Masuk &rarr;</a>
        </div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label fw-semibold small">Nama Pengguna</label>
          <input type="text" name="username" class="form-control" placeholder="Nama pengguna anda" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold small">Alamat E-mel Berdaftar</label>
          <input type="email" name="email" class="form-control" placeholder="E-mel yang didaftarkan" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold small">Kata Laluan Baharu</label>
          <input type="password" name="new_password" class="form-control" placeholder="Min. 8 aksara" required>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold small">Sahkan Kata Laluan Baharu</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Ulang kata laluan" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
          <span class="material-symbols-outlined me-1" style="font-size:18px;">lock_reset</span>
          Tetapkan Semula Kata Laluan
        </button>
      </form>
      <?php endif; ?>

      <p class="text-center text-muted small mt-4">
        <a href="login.php" style="color:#087383;">
          <span class="material-symbols-outlined" style="font-size:14px;vertical-align:-2px;">arrow_back</span>
          Kembali ke Log Masuk
        </a>
      </p>
    </div>
  </div>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js"></script>
</body>
</html>
