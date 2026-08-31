<?php
/**
 * CoreUI Header — Universal top bar for all portals
 * Expects: $userName, $userRole, $_cuiTheme, $_ROOT to be set by the page.
 */
require_once __DIR__ . '/lang.php';

$_userName  = htmlspecialchars($userName  ?? $_SESSION['user_name'] ?? 'Pengguna');
$_userRole  = $_SESSION['user_role'] ?? 'user';
$_darkMode  = !empty($_SESSION['dark_mode']);
$_currentLang = $_SESSION['lang'] ?? 'ms';
$_ROOT      = '/sedap/sedap2.0';

$_roleMap = [
  'admin'     => __('role_admin', 'Pentadbir Sistem'),
  'doctor'    => __('role_doctor', 'Pegawai Perubatan / Doktor'),
  'volunteer' => __('role_volunteer', 'Sukarelawan'),
  'user'      => __('role_user', 'Pesakit / Komuniti')
];
$_roleLabel = $_roleMap[$_userRole] ?? __('role_user', 'Pengguna');

$_settingsMap = [
  'admin'     => "$_ROOT/pages/admin/settings.php",
  'doctor'    => "$_ROOT/pages/doctor/settings.php",
  'volunteer' => "$_ROOT/pages/volunteer/settings.php",
  'user'      => "$_ROOT/pages/patient/settings.php"
];
$_settingsUrl = $_settingsMap[$_userRole] ?? "$_ROOT/pages/patient/settings.php";
?>
<div class="header header-sticky p-0" id="header">
  <div class="container-fluid border-bottom px-3 px-md-4 d-flex align-items-center" style="height:56px;">

    <!-- Sidebar Toggler (Mobile Drawer trigger: <= 991px only) -->
    <button class="header-toggler me-2 d-lg-none" type="button"
            onclick="sedapToggleSidebar()"
            title="<?= __('nav_dashboard', 'Buka Menu') ?>">
      <span class="material-symbols-outlined" style="font-size:24px;">menu</span>
    </button>

    <!-- Breadcrumb / Role label -->
    <nav aria-label="breadcrumb" class="me-auto d-none d-md-flex">
      <span class="small text-muted fw-semibold"><?= $_roleLabel ?></span>
    </nav>

    <!-- Right nav items -->
    <ul class="header-nav ms-auto d-flex align-items-center gap-2 list-unstyled mb-0">

      <!-- Language Dropdown Switcher -->
      <li class="nav-item dropdown">
        <button class="btn btn-ghost-secondary btn-sm d-flex align-items-center gap-1 px-2 py-1 rounded-pill"
                data-coreui-toggle="dropdown" aria-expanded="false"
                title="<?= __('settings_lang', 'Bahasa / Language') ?>">
          <span class="material-symbols-outlined" style="font-size:18px;color:var(--cui-primary);">translate</span>
          <span class="small fw-bold text-uppercase"><?= $_currentLang === 'en' ? 'EN' : 'BM' ?></span>
          <span class="material-symbols-outlined" style="font-size:14px;opacity:.6;">expand_more</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:170px;">
          <li>
            <a class="dropdown-item d-flex align-items-center justify-content-between gap-2 <?= $_currentLang === 'ms' ? 'active fw-bold' : '' ?>"
               href="javascript:void(0)" onclick="sedapSetLanguage('ms')">
              <span>🇲🇾 Bahasa Melayu</span>
              <?php if ($_currentLang === 'ms'): ?>
                <span class="material-symbols-outlined" style="font-size:16px;">check</span>
              <?php endif; ?>
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center justify-content-between gap-2 <?= $_currentLang === 'en' ? 'active fw-bold' : '' ?>"
               href="javascript:void(0)" onclick="sedapSetLanguage('en')">
              <span>🇬🇧 English</span>
              <?php if ($_currentLang === 'en'): ?>
                <span class="material-symbols-outlined" style="font-size:16px;">check</span>
              <?php endif; ?>
            </a>
          </li>
        </ul>
      </li>

      <!-- Dark mode toggle -->
      <li class="nav-item">
        <button class="btn btn-ghost-secondary btn-sm d-flex align-items-center p-2 rounded-circle"
                onclick="sedapToggleDark()"
                title="<?= __('settings_dark_mode', 'Tukar Tema') ?>">
          <span class="material-symbols-outlined" id="theme-icon" style="font-size:20px;">
            <?= $_darkMode ? 'light_mode' : 'dark_mode' ?>
          </span>
        </button>
      </li>

      <!-- User profile dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link d-flex align-items-center gap-2 px-2" href="#"
           data-coreui-toggle="dropdown" aria-expanded="false">
          <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white shadow-sm"
               style="width:32px;height:32px;background:#087383;font-size:.85rem;flex-shrink:0;">
            <?= mb_strtoupper(mb_substr($_userName, 0, 1)) ?>
          </div>
          <span class="d-none d-md-inline small fw-medium text-truncate" style="max-width:120px;"><?= $_userName ?></span>
          <span class="material-symbols-outlined" style="font-size:16px;opacity:.6;">expand_more</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <li>
            <span class="dropdown-item-text">
              <div class="fw-semibold small"><?= $_userName ?></div>
              <div class="text-muted" style="font-size:.75rem;"><?= $_roleLabel ?></div>
            </span>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2" href="<?= $_settingsUrl ?>">
              <span class="material-symbols-outlined" style="font-size:18px;">settings</span><?= __('nav_settings', 'Tetapan') ?>
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
               href="<?= $_ROOT ?>/pages/auth/logout.php"
               onclick="return confirm('<?= __('settings_confirm_logout', 'Log keluar?') ?>')">
              <span class="material-symbols-outlined" style="font-size:18px;">logout</span><?= __('nav_logout', 'Log Keluar') ?>
            </a>
          </li>
        </ul>
      </li>

    </ul>
  </div>
</div>
