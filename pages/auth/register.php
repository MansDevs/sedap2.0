<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';

$_ROOT  = '/sedap/sedap2.0';
$error  = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    if (empty($name) || empty($email) || empty($username) || empty($password)) {
        $error = 'Sila isi semua medan wajib.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format e-mel tidak sah.';
    } elseif ($password !== $confirm) {
        $error = 'Kata laluan tidak sepadan.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata laluan mestilah sekurang-kurangnya 8 aksara.';
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=?");
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $error = 'Nama pengguna atau e-mel telah digunakan.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (name, email, phone, username, password, role, status, created_at) VALUES (?,?,?,?,?,?,'active',NOW())")
                    ->execute([$name, $email, $phone, $username, $hash, $role]);
                $success = 'Akaun berjaya didaftarkan! Sila log masuk.';
            }
        } catch (Exception $e) {
            $error = 'Ralat sistem semasa pendaftaran.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Daftar Akaun — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    body { font-family:'Inter',sans-serif; }
    .role-pill { cursor:pointer; border-radius:999px; padding:.3rem .9rem; font-size:.8rem; border:2px solid #e0e0e0; background:#fff; transition:.2s; }
    .role-pill.active { border-color:#087383; background:#087383; color:#fff; }
  </style>
</head>
<body>
<div class="row g-0 min-vh-100">
  <!-- Left Brand Panel -->
  <div class="col-md-5 auth-brand-panel d-none d-md-flex">
    <div class="text-center">
      <span class="material-symbols-outlined" style="font-size:72px;color:rgba(255,255,255,.9);">person_add</span>
      <h1 class="display-5 fw-bold text-white mt-3 mb-2">Daftar Akaun</h1>
      <p class="text-white opacity-75 small px-4">Sertai platform SeDaP untuk mengakses portal kesihatan komuniti anda.</p>
    </div>
  </div>

  <!-- Right Register Form -->
  <div class="col-md-7 d-flex align-items-center justify-content-center bg-white px-4 py-5">
    <div style="width:100%;max-width:460px;">
      <h2 class="fw-bold mb-1">Daftar Akaun Baharu</h2>
      <p class="text-muted small mb-4">Isi maklumat di bawah untuk mencipta akaun.</p>

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
        <!-- Role -->
        <!-- <div class="mb-3">
          <label class="form-label fw-semibold small">Peranan</label>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="role-pill"        data-role="doctor"    onclick="setRole('doctor')">Doktor / MA</button>
            <button type="button" class="role-pill"        data-role="volunteer" onclick="setRole('volunteer')">Sukarelawan</button>
            <button type="button" class="role-pill active" data-role="user"      onclick="setRole('user')">Pesakit</button>
          </div>
          <input type="hidden" name="role" id="role-input" value="user">
        </div> -->

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold small">Nama Penuh <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="Nama penuh" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold small">No. Telefon</label>
            <input type="tel" name="phone" class="form-control" placeholder="01X-XXXXXXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold small">Alamat E-mel <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold small">Nama Pengguna <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" placeholder="Nama pengguna unik" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold small">Kata Laluan <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Min. 8 aksara" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold small">Sahkan Kata Laluan <span class="text-danger">*</span></label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Ulang kata laluan" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mt-4">
          Daftar Sekarang
        </button>
      </form>
      <?php endif; ?>

      <p class="text-center text-muted small mt-4">
        Sudah ada akaun?
        <a href="login.php" style="color:#087383;">Log masuk</a>
      </p>
    </div>
  </div>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js"></script>
<script>
function setRole(role) {
  document.getElementById('role-input').value = role;
  document.querySelectorAll('.role-pill').forEach(p => p.classList.toggle('active', p.dataset.role === role));
}
</script>
</body>
</html>