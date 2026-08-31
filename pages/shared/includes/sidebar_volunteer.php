<?php
/**
 * CoreUI Sidebar — Volunteer Portal
 */
require_once __DIR__ . '/lang.php';
?>
<div class="sidebar sidebar-fixed sidebar-dark" id="sidebar">
  <div class="sidebar-brand d-flex align-items-center justify-content-between px-3 py-3 border-bottom" style="border-color:rgba(255,255,255,0.1)!important;min-height:56px;">
    <a href="/sedap2.0/pages/volunteer/dashboard.php" class="sidebar-brand-full d-flex align-items-center gap-2 text-decoration-none text-white">
      <span class="material-symbols-outlined" style="font-size:26px;color:#fff;">volunteer_activism</span>
      <span class="fw-bold fs-5 text-white">SeDaP</span>
    </a>
    <a href="javascript:void(0)" onclick="sedapToggleSidebar()" class="sidebar-brand-narrow text-decoration-none text-white" title="Buka / Tutup Menu">
      <span class="material-symbols-outlined" style="font-size:26px;color:#fff;">menu</span>
    </a>
    <button class="sidebar-toggle-btn sidebar-brand-full" type="button" onclick="sedapToggleSidebar()" title="Kecilkan / Buka Sidebar">
      <span class="material-symbols-outlined" style="font-size:22px;">menu</span>
    </button>
  </div>

  <ul class="sidebar-nav">
    <li class="nav-title"><?= __('nav_volunteer_portal', 'Portal Sukarelawan') ?></li>
    <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/volunteer/dashboard.php"><span class="material-symbols-outlined nav-icon">dashboard</span><span><?= __('nav_dashboard', 'Dashboard') ?></span></a></li>
    <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/volunteer/triage_counter.php"><span class="material-symbols-outlined nav-icon">add_circle</span><span><?= __('nav_triage_counter', 'Kaunter Triaj') ?></span></a></li>
    <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/volunteer/triage_list.php"><span class="material-symbols-outlined nav-icon">format_list_bulleted</span><span><?= __('nav_triage_list', 'Senarai Triaj') ?></span></a></li>
    <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/volunteer/patients.php"><span class="material-symbols-outlined nav-icon">person_add</span><span><?= __('nav_patients', 'Pendaftaran Pesakit') ?></span></a></li>
    <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/volunteer/announcements.php"><span class="material-symbols-outlined nav-icon">campaign</span><span><?= __('nav_announcements', 'Pengumuman') ?></span></a></li>
    <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/volunteer/posters.php"><span class="material-symbols-outlined nav-icon">image</span><span><?= __('nav_posters', 'Poster') ?></span></a></li>
    <li class="nav-title"><?= __('nav_settings', 'Akaun') ?></li>
    <li class="nav-item"><a class="nav-link" href="/sedap2.0/pages/volunteer/settings.php"><span class="material-symbols-outlined nav-icon">settings</span><span><?= __('nav_settings', 'Tetapan') ?></span></a></li>
    <li class="nav-item"><a class="nav-link text-danger" href="/sedap2.0/pages/auth/logout.php" onclick="return confirm('<?= __('settings_confirm_logout', 'Log keluar?') ?>')"><span class="material-symbols-outlined nav-icon text-danger">logout</span><span><?= __('nav_logout', 'Log Keluar') ?></span></a></li>
  </ul>

  <button class="sidebar-toggler" type="button" onclick="sedapToggleSidebar()"></button>
</div>
