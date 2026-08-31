<?php
/**
 * CoreUI Sidebar — Patient/User Portal
 */
require_once __DIR__ . '/lang.php';

// Calculate initial unread chat count (number of unique doctors/staff) for instant server-side badge rendering
$_chatUnread = 0;
if (!empty($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../../config/db.php';
        $_chatStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT m.sender_id)
            FROM messages m
            JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id AND cp.user_id = ?
            WHERE m.sender_id != ?
              AND m.id > IFNULL(cp.last_read_message_id, 0)
              AND m.deleted_at IS NULL
        ");
        $_chatStmt->execute([(int)$_SESSION['user_id'], (int)$_SESSION['user_id']]);
        $_chatUnread = (int)$_chatStmt->fetchColumn();
    } catch (Exception $e) {}
}
?>
<div class="sidebar sidebar-fixed sidebar-dark" id="sidebar">
  <div class="sidebar-brand d-flex align-items-center justify-content-between px-3 py-3 border-bottom" style="border-color:rgba(255,255,255,0.1)!important;min-height:56px;">
    <a href="/sedap/sedap2.0/pages/patient/dashboard.php" class="sidebar-brand-full d-flex align-items-center gap-2 text-decoration-none text-white">
      <span class="material-symbols-outlined" style="font-size:26px;color:#fff;">health_and_safety</span>
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
    <li class="nav-title"><?= __('nav_patient_portal', 'Menu Pesakit') ?></li>
    <li class="nav-item"><a class="nav-link" href="/sedap/sedap2.0/pages/patient/dashboard.php"><span class="material-symbols-outlined nav-icon">dashboard</span><span><?= __('nav_dashboard', 'Dashboard') ?></span></a></li>
    <li class="nav-item"><a class="nav-link" href="/sedap/sedap2.0/pages/patient/screening.php"><span class="material-symbols-outlined nav-icon">fact_check</span><span><?= __('nav_screening', 'Saringan Kesihatan') ?></span></a></li>
    <li class="nav-item"><a class="nav-link" href="/sedap/sedap2.0/pages/patient/family_register.php"><span class="material-symbols-outlined nav-icon">family_restroom</span><span><?= __('nav_family', 'Pendaftaran Keluarga') ?></span></a></li>
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center justify-content-between" href="/sedap/sedap2.0/pages/patient/livechat.php">
        <div class="d-flex align-items-center">
          <span class="material-symbols-outlined nav-icon">chat</span><span><?= __('nav_ask_doctor', 'Tanya Doktor') ?></span>
        </div>
        <span class="badge rounded-pill bg-danger sidebar-chat-badge <?= $_chatUnread > 0 ? '' : 'd-none' ?>" style="font-size:0.75rem;">
          <?= $_chatUnread > 99 ? '99+' : $_chatUnread ?>
        </span>
      </a>
    </li>
    <li class="nav-item"><a class="nav-link" href="/sedap/sedap2.0/pages/patient/announcements.php"><span class="material-symbols-outlined nav-icon">campaign</span><span><?= __('nav_announcements', 'Pengumuman') ?></span></a></li>
    <li class="nav-item"><a class="nav-link" href="/sedap/sedap2.0/pages/patient/posters.php"><span class="material-symbols-outlined nav-icon">image</span><span><?= __('nav_posters', 'Poster') ?></span></a></li>
    
    <li class="nav-title"><?= __('nav_health_module', 'Kesihatan Kendiri') ?></li>
    <li class="nav-item"><a class="nav-link" href="/sedap/sedap2.0/pages/patient/health/water.php"><span class="material-symbols-outlined nav-icon">water_drop</span><span><?= __('nav_water', 'Air Minum') ?></span></a></li>
    <li class="nav-item"><a class="nav-link" href="/sedap/sedap2.0/pages/patient/health/bristol.php"><span class="material-symbols-outlined nav-icon">bar_chart</span><span><?= __('nav_bristol', 'Skala Bristol') ?></span></a></li>
    <li class="nav-item"><a class="nav-link" href="/sedap/sedap2.0/pages/patient/health/mood.php"><span class="material-symbols-outlined nav-icon">sentiment_satisfied</span><span><?= __('nav_mood', 'Jurnal Mood') ?></span></a></li>
    <li class="nav-item"><a class="nav-link" href="/sedap/sedap2.0/pages/patient/health/medicine.php"><span class="material-symbols-outlined nav-icon">medication</span><span><?= __('nav_medicine', 'Peringatan Ubat') ?></span></a></li>

    <li class="nav-title"><?= __('nav_settings', 'Akaun') ?></li>
    <li class="nav-item"><a class="nav-link" href="/sedap/sedap2.0/pages/patient/settings.php"><span class="material-symbols-outlined nav-icon">settings</span><span><?= __('nav_settings', 'Tetapan') ?></span></a></li>
    <li class="nav-item"><a class="nav-link text-danger" href="/sedap/sedap2.0/pages/auth/logout.php" onclick="return confirm('<?= __('settings_confirm_logout', 'Log keluar?') ?>')"><span class="material-symbols-outlined nav-icon text-danger">logout</span><span><?= __('nav_logout', 'Log Keluar') ?></span></a></li>
  </ul>

  <button class="sidebar-toggler" type="button" onclick="sedapToggleSidebar()"></button>
</div>
