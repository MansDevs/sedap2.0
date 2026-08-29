<?php
/**
 * CoreUI Sidebar — Doctor Portal
 */
require_once __DIR__ . '/lang.php';
$_cuiActive = basename($_SERVER['PHP_SELF'], '.php');

// Calculate initial unread chat count (number of unique patients) for instant server-side badge rendering
$_chatUnread = 0;
if (!empty($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../../config/db.php';
        $_chatStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT m.sender_id)
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            LEFT JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id AND cp.user_id = ?
            WHERE u.role = 'user'
              AND m.id > IFNULL(cp.last_read_message_id, 0)
              AND m.deleted_at IS NULL
        ");
        $_chatStmt->execute([(int)$_SESSION['user_id']]);
        $_chatUnread = (int)$_chatStmt->fetchColumn();
    } catch (Exception $e) {}
}
?>
<div class="sidebar sidebar-fixed sidebar-dark" id="sidebar">
  <div class="sidebar-brand d-flex align-items-center justify-content-between px-3 py-3 border-bottom" style="border-color:rgba(255,255,255,0.1)!important;min-height:56px;">
    <a href="/sedap/sedap2.0/pages/doctor/cdashboard.php" class="sidebar-brand-full d-flex align-items-center gap-2 text-decoration-none text-white">
      <span class="material-symbols-outlined" style="font-size:26px;color:#fff;">medical_services</span>
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
    <li class="nav-title"><?= __('nav_doctor_portal', 'Portal Doktor') ?></li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap/sedap2.0/pages/doctor/cdashboard.php">
        <span class="material-symbols-outlined nav-icon">dashboard</span><span><?= __('nav_dashboard', 'Dashboard') ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap/sedap2.0/pages/doctor/triage_list.php">
        <span class="material-symbols-outlined nav-icon">format_list_bulleted</span><span><?= __('nav_triage_list', 'Senarai Triaj') ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap/sedap2.0/pages/doctor/triage_counter.php">
        <span class="material-symbols-outlined nav-icon">add_circle</span><span><?= __('nav_triage_counter', 'Kaunter Triaj') ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap/sedap2.0/pages/doctor/announcements.php">
        <span class="material-symbols-outlined nav-icon">campaign</span><span><?= __('nav_announcements', 'Pengumuman') ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap/sedap2.0/pages/doctor/posters.php">
        <span class="material-symbols-outlined nav-icon">image</span><span><?= __('nav_posters', 'Poster') ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap/sedap2.0/pages/doctor/patientfamily.php">
        <span class="material-symbols-outlined nav-icon">groups</span><span><?= __('nav_family', 'Pesakit & Keluarga') ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center justify-content-between" href="/sedap/sedap2.0/pages/doctor/livechat.php">
        <div class="d-flex align-items-center">
          <span class="material-symbols-outlined nav-icon">chat</span><span><?= __('nav_livechat', 'Live Chat') ?></span>
        </div>
        <span class="badge rounded-pill bg-danger sidebar-chat-badge <?= $_chatUnread > 0 ? '' : 'd-none' ?>" style="font-size:0.75rem;">
          <?= $_chatUnread > 99 ? '99+' : $_chatUnread ?>
        </span>
      </a>
    </li>

    <li class="nav-title"><?= __('nav_health_module', 'Modul Kesihatan') ?></li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap/sedap2.0/pages/doctor/health/water.php">
        <span class="nav-icon material-symbols-outlined">water_drop</span><span><?= __('nav_water', 'Air') ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap/sedap2.0/pages/doctor/health/bristol.php">
        <span class="nav-icon material-symbols-outlined">bar_chart</span><span><?= __('nav_bristol', 'Bristol') ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap/sedap2.0/pages/doctor/health/mood.php">
        <span class="nav-icon material-symbols-outlined">sentiment_satisfied</span><span><?= __('nav_mood', 'Mood') ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap/sedap2.0/pages/doctor/health/medicine.php">
        <span class="nav-icon material-symbols-outlined">medication</span><span><?= __('nav_medicine', 'Ubat') ?></span>
      </a>
    </li>

    <li class="nav-title"><?= __('nav_settings', 'Akaun') ?></li>
    <li class="nav-item">
      <a class="nav-link" href="/sedap/sedap2.0/pages/doctor/settings.php">
        <span class="material-symbols-outlined nav-icon">settings</span><span><?= __('nav_settings', 'Tetapan') ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link text-danger" href="/sedap/sedap2.0/pages/auth/logout.php" onclick="return confirm('<?= __('settings_confirm_logout', 'Log keluar?') ?>')">
        <span class="material-symbols-outlined nav-icon text-danger">logout</span><span><?= __('nav_logout', 'Log Keluar') ?></span>
      </a>
    </li>
  </ul>

  <button class="sidebar-toggler" type="button" onclick="sedapToggleSidebar()"></button>
</div>
