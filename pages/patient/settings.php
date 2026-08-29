<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php'); exit;
}
$userId = $_SESSION['user_id'];
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap/sedap2.0';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $weight  = floatval($_POST['weight_kg'] ?? 0);
    $dob     = trim($_POST['dob'] ?? '');
    if ($name) {
        $stmt = $pdo->prepare("UPDATE users SET name=?, contact_number=?, weight_kg=?, date_of_birth=? WHERE id=?");
        $stmt->execute([$name, $phone, $weight ?: null, $dob ?: null, $userId]);
        $_SESSION['user_name'] = $name;
        $msg = __('settings_profile', 'Profil berjaya dikemaskini.');
    } else {
        $err = 'Nama tidak boleh dibiarkan kosong.';
    }
}

$u = $pdo->prepare("SELECT * FROM users WHERE id=?");
$u->execute([$userId]);
$user = $u->fetch() ?: [];
$userName  = htmlspecialchars($user['name'] ?? 'Pesakit');
$userRole  = 'user';
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('settings_title', 'Tetapan') ?> — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar_user.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="settings-section">
        <div class="mb-4">
          <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">settings</span><?= __('settings_title', 'Tetapan Pesakit') ?></h1>
          <p class="page-subtitle"><?= __('settings_subtitle', 'Urus profil dan penampilan portal pesakit anda') ?></p>
        </div>

        <!-- Dark Mode -->
        <div class="card mb-4">
          <div class="card-header"><span class="material-symbols-outlined">palette</span><strong><?= __('settings_appearance', 'Penampilan') ?></strong></div>
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="fw-semibold small"><?= __('settings_dark_mode', 'Mod Gelap (Dark Mode)') ?></div>
                <div class="text-muted small"><?= __('settings_dark_desc', 'Tukar antara tema cerah dan gelap') ?></div>
              </div>
              <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch"
                       onchange="sedapToggleDark()" style="width:2.5rem;height:1.3rem;cursor:pointer;"
                       <?= !empty($_SESSION['dark_mode']) ? 'checked' : '' ?>>
              </div>
            </div>
          </div>
        </div>

        <!-- Language Setting (Bahasa) -->
        <div class="card mb-4">
          <div class="card-header"><span class="material-symbols-outlined">translate</span><strong><?= __('settings_lang', 'Bahasa / Language') ?></strong></div>
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
              <div>
                <div class="fw-semibold small"><?= __('settings_lang_select', 'Pilihan Bahasa / Language Selection') ?></div>
                <div class="text-muted small"><?= __('settings_lang_desc', 'Pilih bahasa paparan antaramuka sistem') ?></div>
              </div>
              <div class="btn-group" role="group" aria-label="Pilihan Bahasa">
                <button type="button" class="btn btn-sm <?= ($_SESSION['lang'] ?? 'ms') === 'ms' ? 'btn-primary' : 'btn-outline-secondary' ?>" onclick="sedapSetLanguage('ms')">
                  🇲🇾 Bahasa Melayu
                </button>
                <button type="button" class="btn btn-sm <?= ($_SESSION['lang'] ?? 'ms') === 'en' ? 'btn-primary' : 'btn-outline-secondary' ?>" onclick="sedapSetLanguage('en')">
                  🇬🇧 English
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sound Notifications (Bunyi Notifikasi Mesej) -->
        <div class="card mb-4">
          <div class="card-header"><span class="material-symbols-outlined">notifications_active</span><strong><?= __('settings_sound_title', 'Notifikasi & Bunyi Mesej') ?></strong></div>
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
              <div>
                <div class="fw-semibold small"><?= __('settings_sound_msg', 'Bunyi Notifikasi Mesej Baharu') ?></div>
                <div class="text-muted small"><?= __('settings_sound_desc', 'Mainkan nada amaran apabila mesej baharu daripada doktor diterima') ?></div>
              </div>
              <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill d-flex align-items-center gap-1" onclick="sedapTestSound()" title="<?= __('settings_sound_test', 'Uji Bunyi') ?>">
                  <span class="material-symbols-outlined" style="font-size:16px;">volume_up</span> <?= __('settings_sound_test', 'Uji Bunyi') ?>
                </button>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" role="switch" id="soundNotificationSwitch"
                         onchange="sedapToggleSound()" style="width:2.5rem;height:1.3rem;cursor:pointer;"
                         <?= (!isset($_SESSION['sound_notification']) || !empty($_SESSION['sound_notification'])) ? 'checked' : '' ?>>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Profile Update -->
        <div class="card mb-4">
          <div class="card-header"><span class="material-symbols-outlined">person</span><strong><?= __('settings_profile', 'Kemaskini Profil') ?></strong></div>
          <div class="card-body">
            <?php if ($msg): ?>
              <div class="alert alert-success d-flex align-items-center gap-2 py-2">
                <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span><?= htmlspecialchars($msg) ?>
              </div>
            <?php endif; ?>
            <?php if ($err): ?>
              <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
                <span class="material-symbols-outlined" style="font-size:18px;">error</span><?= htmlspecialchars($err) ?>
              </div>
            <?php endif; ?>
            <form method="POST">
              <input type="hidden" name="action" value="update_profile">
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold"><?= __('settings_name', 'Nama Penuh') ?></label>
                  <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold"><?= __('settings_phone', 'Nombor Telefon') ?></label>
                  <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['contact_number'] ?? $user['phone'] ?? '') ?>">
                </div>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold"><?= __('settings_dob', 'Tarikh Lahir') ?></label>
                  <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($user['date_of_birth'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold"><?= __('settings_weight', 'Berat Badan (kg)') ?></label>
                  <input type="number" step="0.1" name="weight_kg" class="form-control" value="<?= htmlspecialchars($user['weight_kg'] ?? '') ?>" placeholder="cth: 65.5">
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                <span class="material-symbols-outlined" style="font-size:16px;">save</span><?= __('settings_btn_save', 'Simpan Perubahan') ?>
              </button>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
  <?php include '../shared/includes/footer.php'; ?>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
</body>
</html>
