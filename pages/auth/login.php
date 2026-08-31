<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';

$_ROOT = '/sedap2.0';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input    = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if (empty($input) || empty($password)) {
        $error = 'Sila isi semua medan.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND status='active'");
            $stmt->execute([$input, $input]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_role']  = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['dark_mode']  = (bool)($user['dark_mode'] ?? false);
                $_SESSION['lang']       = $user['lang'] ?? 'ms';

                switch ($user['role']) {
            case 'admin': $redirect = '../admin/dashboard.php'; break;
            case 'doctor': $redirect = '../doctor/cdashboard.php'; break;
            case 'volunteer': $redirect = '../volunteer/dashboard.php'; break;
            case 'user': $redirect = '../patient/dashboard.php'; break;
            default: $redirect = 'login.php'; break;
        }
                header("Location: $redirect"); exit;
            } else {
                $error = 'Nama pengguna atau kata laluan tidak betul.';
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
  <title>Log Masuk — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .role-pill { cursor:pointer; border-radius:999px; padding:.35rem 1rem; font-size:.82rem; border:2px solid #e0e0e0; background:#fff; transition:.2s; }
    .role-pill.active { border-color:#087383; background:#087383; color:#fff; }
    .login-panel { min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f4f4f4; }
    @media(min-width:768px){ .auth-brand-panel { min-height:100vh; } }
  </style>
</head>
<body>
<div class="row g-0 min-vh-100">

  <!-- Left Brand Panel -->
  <div class="col-md-5 auth-brand-panel d-none d-md-flex">
    <div class="text-center">
      <div class="mb-4">
        <span class="material-symbols-outlined" style="font-size:72px;color:rgba(255,255,255,.9);">medical_services</span>
      </div>
      <h1 class="display-5 fw-bold text-white mb-2">SeDaP</h1>
      <p class="text-white opacity-75 fs-6 mb-4">Sistem e-Data Awam Perubatan</p>
      <hr style="border-color:rgba(255,255,255,.3);width:60%;margin:0 auto 1.5rem;">
      <p class="text-white opacity-60 small px-4">
        Platform digital untuk pengurusan data kesihatan komuniti semasa kejadian bencana dan program kesihatan awam.
      </p>
    </div>
  </div>

  <!-- Right Login Form -->
  <div class="col-md-7 d-flex align-items-center justify-content-center bg-white px-4 py-5">
    <div style="width:100%;max-width:420px;">

      <!-- Mobile Logo -->
      <div class="text-center d-md-none mb-4">
        <span class="material-symbols-outlined" style="font-size:48px;color:#087383;">medical_services</span>
        <h2 class="fw-bold mt-2" style="color:#087383;">SeDaP</h2>
      </div>

      <h2 class="fw-bold mb-1">Log Masuk</h2>
      <p class="text-muted small mb-4">Masukkan maklumat akaun anda untuk meneruskan.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
          <span class="material-symbols-outlined" style="font-size:18px;">error</span>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" autocomplete="on">
        <!-- Role Selector -->
        <!-- <div class="mb-4">
          <label class="form-label fw-semibold small">Peranan</label>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="role-pill active" data-role="admin"     onclick="setRole('admin')">Admin</button>
            <button type="button" class="role-pill"        data-role="doctor"    onclick="setRole('doctor')">Doktor / MA / Jururawat</button>
            <button type="button" class="role-pill"        data-role="volunteer" onclick="setRole('volunteer')">Sukarelawan</button>
            <button type="button" class="role-pill"        data-role="user"      onclick="setRole('user')">Pesakit</button>
          </div>
          <input type="hidden" name="role" id="role-input" value="admin">
        </div> -->

        <!-- Username -->
        <div class="mb-3">
          <label for="username" class="form-label fw-semibold small">Nama Pengguna / E-mel</label>
          <div class="input-group">
            <span class="input-group-text">
              <span class="material-symbols-outlined" style="font-size:18px;">person</span>
            </span>
            <input type="text" class="form-control" id="username" name="username"
                   placeholder="Nama pengguna atau e-mel"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
          </div>
        </div>

        <!-- Password -->
        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center">
            <label for="password" class="form-label fw-semibold small mb-0">Kata Laluan</label>
            <a href="forgotpass.php" class="small" style="color:#087383;">Lupa kata laluan?</a>
          </div>
          <div class="input-group mt-1">
            <span class="input-group-text">
              <span class="material-symbols-outlined" style="font-size:18px;">lock</span>
            </span>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
            <button type="button" class="input-group-text" onclick="togglePw()" title="Tunjuk/Sembunyi">
              <span class="material-symbols-outlined" id="pw-eye" style="font-size:18px;">visibility</span>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
          Log Masuk
          <span class="material-symbols-outlined ms-1" style="font-size:18px;">arrow_forward</span>
        </button>
      </form>

      <p class="text-center text-muted small mt-4">
        Pengguna baru?
        <a href="register.php" style="color:#087383;">Daftar akaun</a>
      </p>

    </div>
  </div>
</div>

<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js"></script>
<script>
function setRole(role) {
  document.getElementById('role-input').value = role;
  document.querySelectorAll('.role-pill').forEach(p => {
    p.classList.toggle('active', p.dataset.role === role);
  });
}
function togglePw() {
  const pw = document.getElementById('password');
  const eye = document.getElementById('pw-eye');
  if (pw.type === 'password') { pw.type = 'text'; eye.textContent = 'visibility_off'; }
  else { pw.type = 'password'; eye.textContent = 'visibility'; }
}
</script>
</body>
</html>