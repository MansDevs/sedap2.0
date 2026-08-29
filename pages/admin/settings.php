<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$userEmail = htmlspecialchars($_SESSION['user_email'] ?? '');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap/sedap2.0';

$pwMsg = ''; $pwError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if ($new !== $confirm) { $pwError = __('settings_new_pw', 'Kata laluan baharu tidak sepadan.'); }
    elseif (strlen($new) < 8) { $pwError = __('settings_new_pw', 'Kata laluan mestilah sekurang-kurangnya 8 aksara.'); }
    else {
        $row = $pdo->prepare("SELECT password FROM users WHERE id=?");
        $row->execute([$_SESSION['user_id']]);
        $row = $row->fetch();
        if ($row && password_verify($current, $row['password'])) {
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            $pwMsg = __('settings_change_pw', 'Kata laluan berjaya dikemas kini.');
        } else { $pwError = __('settings_current_pw', 'Kata laluan semasa tidak betul.'); }
    }
}
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
  <?php include '../shared/includes/sidebar_admin.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="settings-section">
        <div class="mb-4">
          <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">settings</span><?= __('settings_title', 'Tetapan Pentadbir') ?></h1>
          <p class="page-subtitle"><?= __('settings_subtitle', 'Urus penampilan, bahasa dan keselamatan akaun sistem') ?></p>
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
                <input class="form-check-input" type="checkbox" role="switch" id="darkModeSwitch"
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
                <div class="text-muted small"><?= __('settings_sound_desc', 'Mainkan nada amaran apabila mesej atau amaran kecemasan baharu tiba') ?></div>
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

        <!-- Password -->
        <div class="card mb-4">
          <div class="card-header"><span class="material-symbols-outlined">lock_reset</span><strong><?= __('settings_change_pw', 'Tukar Kata Laluan') ?></strong></div>
          <div class="card-body">
            <?php if ($pwMsg): ?>
              <div class="alert alert-success d-flex align-items-center gap-2 py-2">
                <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span><?= htmlspecialchars($pwMsg) ?>
              </div>
            <?php endif; ?>
            <?php if ($pwError): ?>
              <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
                <span class="material-symbols-outlined" style="font-size:18px;">error</span><?= htmlspecialchars($pwError) ?>
              </div>
            <?php endif; ?>
            <form method="POST">
              <input type="hidden" name="action" value="reset_password">
              <div class="mb-3">
                <label class="form-label small fw-semibold"><?= __('settings_current_pw', 'Kata Laluan Semasa') ?></label>
                <input type="password" name="current_password" class="form-control" required placeholder="••••••••">
              </div>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold"><?= __('settings_new_pw', 'Kata Laluan Baharu') ?></label>
                  <input type="password" name="new_password" class="form-control" required placeholder="Min 8 aksara">
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold"><?= __('settings_confirm_pw', 'Sahkan Kata Laluan Baharu') ?></label>
                  <input type="password" name="confirm_password" class="form-control" required placeholder="Ulang kata laluan baharu">
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                <span class="material-symbols-outlined" style="font-size:16px;">save</span><?= __('settings_btn_update_pw', 'Kemas Kini Kata Laluan') ?>
              </button>
            </form>
          </div>
        </div>

        <!-- Account Info -->
        <div class="card mb-4">
          <div class="card-header"><span class="material-symbols-outlined">badge</span><strong><?= __('settings_account_info', 'Maklumat Akaun') ?></strong></div>
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between">
              <span class="text-muted small"><?= __('settings_name', 'Nama') ?></span><span class="fw-semibold small"><?= $userName ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between">
              <span class="text-muted small"><?= __('settings_email', 'E-mel') ?></span><span class="fw-semibold small"><?= $userEmail ?: '—' ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between">
              <span class="text-muted small">Peranan</span><span class="badge bg-danger">Admin</span>
            </li>
            <li class="list-group-item">
              <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1" onclick="return confirm('<?= __('settings_confirm_logout', 'Log keluar?') ?>')">
                <span class="material-symbols-outlined" style="font-size:16px;">logout</span><?= __('nav_logout', 'Log Keluar') ?>
              </a>
            </li>
          </ul>
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
