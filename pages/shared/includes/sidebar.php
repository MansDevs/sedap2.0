<?php
/**
 * ============================================================================
 *   ███████╗███████╗██████╗  █████╗ ██████╗ 
 *   ██╔════╝██╔════╝██╔══██╗██╔══██╗██╔══██╗
 *   ███████╗█████╗  ██║  ██║███████║██████╔╝
 *   ╚════██║██╔══╝  ██║  ██║██╔══██║██╔═══╝ 
 *   ███████║███████╗██████╔╝██║  ██║██║     
 *   ╚══════╝╚══════╝╚═════╝ ╚═╝  ╚═╝╚═╝     
 *   UNIFIED COREUI RESPONSIVE SIDEBAR
 *   Dynamically renders navigation based on users.role:
 *   [ADMIN] | [DOCTOR] | [VOLUNTEER] | [PATIENT / USER]
 * ============================================================================
 */

require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/../../config/db.php';

$_ROOT = $_ROOT ?? sedap_root();
$_userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'user';
$_currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

// Normalize role
if (in_array($_userRole, ['doctor', 'nurse', 'medical_assistant'], true)) {
    $_navRole = 'doctor';
} elseif ($_userRole === 'admin') {
    $_navRole = 'admin';
} elseif ($_userRole === 'volunteer') {
    $_navRole = 'volunteer';
} else {
    $_navRole = 'user'; // patient / user
}

// Brand links & labels
$_brandConfig = [
    'admin'     => ['url' => "$_ROOT/pages/admin/dashboard.php", 'label' => 'SeDaP Admin'],
    'doctor'    => ['url' => "$_ROOT/pages/doctor/cdashboard.php", 'label' => 'SeDaP Doktor'],
    'volunteer' => ['url' => "$_ROOT/pages/volunteer/dashboard.php", 'label' => 'SeDaP Sukarelawan'],
    'user'      => ['url' => "$_ROOT/pages/patient/dashboard.php", 'label' => 'SeDaP'],
];
$_currentBrand = $_brandConfig[$_navRole] ?? $_brandConfig['user'];

// Helper to determine if link is active
if (!function_exists('is_nav_active')) {
    function is_nav_active(string $path, string $currentScript): bool {
        return strpos($currentScript, $path) !== false;
    }
}

// Unread chat calculation for doctor and patient
$_chatUnread = 0;
if (!empty($_SESSION['user_id']) && isset($pdo)) {
    try {
        if ($_navRole === 'doctor') {
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
        } elseif ($_navRole === 'user') {
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
        }
    } catch (Exception $e) {}
}
?>

<div class="sidebar sidebar-fixed sidebar-dark" id="sidebar">
  <!-- Sidebar Brand & Nav Rail Collapse Control -->
  <div class="sidebar-brand d-flex align-items-center justify-content-between px-3 py-3 border-bottom" style="border-color:rgba(255,255,255,0.1)!important;min-height:56px;">
    <a href="<?= $_currentBrand['url'] ?>" class="sidebar-brand-full d-flex align-items-center gap-2 text-decoration-none text-white">
      <img src="<?= $_ROOT ?>/pages/auth/logo.jpg" alt="SeDaP Logo" style="width:28px;height:28px;border-radius:8px;object-fit:cover;">
      <span class="fw-bold fs-5 text-white"><?= $_currentBrand['label'] ?></span>
    </a>
    <a href="javascript:void(0)" onclick="sedapToggleSidebar()" class="sidebar-brand-narrow text-decoration-none text-white" title="Buka / Expand Sidebar">
      <span class="material-symbols-outlined" style="font-size:26px;color:#fff;">menu</span>
    </a>
    <button type="button" id="railCollapseBtn" class="sidebar-brand-full btn btn-link text-white p-1 rounded-2 d-flex align-items-center justify-content-center text-decoration-none" onclick="sedapToggleSidebar()" title="Kecilkan Sidebar / Collapse into Nav Rail" style="opacity:0.85;transition:all .2s;text-decoration:none;" onmouseover="this.style.opacity='1';this.style.backgroundColor='rgba(255,255,255,0.12)'" onmouseout="this.style.opacity='0.85';this.style.backgroundColor='transparent'">
      <span class="material-symbols-outlined" style="font-size:20px;color:#fff;">dock_to_left</span>
    </button>
  </div>

  <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>

    <?php if ($_navRole === 'admin'): ?>
      <!--
      #########################################################################
      #########################################################################
      ###                                                                   ###
      ###   █████╗ ██████╗ ███╗   ███╗██╗███╗   ██╗                       ###
      ###  ██╔══██╗██╔══██╗████╗ ████║██║████╗  ██║                       ###
      ###  ███████║██║  ██║██╔████╔██║██║██╔██╗ ██║                       ###
      ###  ██╔══██║██║  ██║██║╚██╔╝██║██║██║╚██╗██║                       ###
      ###  ██║  ██║██████╔╝██║ ╚═╝ ██║██║██║ ╚████║                       ###
      ###  ╚═╝  ╚═╝╚═════╝ ╚═╝     ╚═╝╚═╝╚═╝  ╚═══╝                       ###
      ###                                                                   ###
      ###  =============================================================  ###
      ###  SECTION 1: ADMIN PORTAL NAVIGATION                             ###
      ###  =============================================================  ###
      ###                                                                   ###
      #########################################################################
      #########################################################################
      -->
      <li class="nav-title"><?= __('nav_admin_portal', 'Admin Portal') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/admin/dashboard.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/admin/dashboard.php">
          <span class="material-symbols-outlined nav-icon">dashboard</span>
          <span><?= __('nav_dashboard', 'Dashboard') ?></span>
        </a>
      </li>

      <li class="nav-title"><?= __('nav_personnel', 'Pengurusan') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/admin/triage/', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/admin/triage/index.php">
          <span class="material-symbols-outlined nav-icon">monitor_heart</span>
          <span><?= __('nav_triage', 'Triaj') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/admin/patients/', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/admin/patients/index.php">
          <span class="material-symbols-outlined nav-icon">person</span>
          <span><?= __('nav_patients', 'Pesakit') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/admin/family/', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/admin/family/index.php">
          <span class="material-symbols-outlined nav-icon">family_restroom</span>
          <span><?= __('nav_family', 'Maklumat Keluarga') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/admin/personnel/', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/admin/personnel/index.php">
          <span class="material-symbols-outlined nav-icon">badge</span>
          <span><?= __('nav_personnel', 'Kakitangan & Sukarelawan') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/admin/screening/', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/admin/screening/index.php">
          <span class="material-symbols-outlined nav-icon">fact_check</span>
          <span><?= __('nav_screening', 'Saringan Kesihatan') ?></span>
        </a>
      </li>

      <li class="nav-title"><?= __('nav_announcements', 'Kandungan') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/admin/announcements/', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/admin/announcements/index.php">
          <span class="material-symbols-outlined nav-icon">campaign</span>
          <span><?= __('nav_announcements', 'Pengumuman') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/admin/posters/', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/admin/posters/index.php">
          <span class="material-symbols-outlined nav-icon">image</span>
          <span><?= __('nav_posters', 'Galeri Poster') ?></span>
        </a>
      </li>

      <li class="nav-title"><?= __('nav_settings', 'Sistem') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/admin/settings.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/admin/settings.php">
          <span class="material-symbols-outlined nav-icon">settings</span>
          <span><?= __('nav_settings', 'Tetapan') ?></span>
        </a>
      </li>

    <?php elseif ($_navRole === 'doctor'): ?>
      <!--
      #########################################################################
      #########################################################################
      ###                                                                   ###
      ###   ██████╗  ██████╗  ██████╗████████╗ ██████╗ ██████╗              ###
      ###   ██╔══██╗██╔═══██╗██╔════╝╚══██╔══╝██╔═══██╗██╔══██╗             ###
      ###   ██║  ██║██║   ██║██║        ██║   ██║   ██║██████╔╝             ###
      ###   ██║  ██║██║   ██║██║        ██║   ██║   ██║██╔══██╗             ###
      ###   ██████╔╝╚██████╔╝╚██████╗   ██║   ╚██████╔╝██║  ██║             ###
      ###   ╚═════╝  ╚═════╝  ╚═════╝   ╚═╝    ╚═════╝ ╚═╝  ╚═╝             ###
      ###                                                                   ###
      ###  =============================================================  ###
      ###  SECTION 2: DOCTOR & MEDICAL STAFF NAVIGATION                   ###
      ###  =============================================================  ###
      ###                                                                   ###
      #########################################################################
      #########################################################################
      -->
      <li class="nav-title"><?= __('nav_doctor_portal', 'Portal Doktor') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= (is_nav_active('/doctor/cdashboard.php', $_currentScript) || is_nav_active('/doctor/dashboard.php', $_currentScript)) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/cdashboard.php">
          <span class="material-symbols-outlined nav-icon">dashboard</span>
          <span><?= __('nav_dashboard', 'Dashboard') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/doctor/triage_list.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/triage_list.php">
          <span class="material-symbols-outlined nav-icon">format_list_bulleted</span>
          <span><?= __('nav_triage_list', 'Senarai Triaj') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/doctor/triage_counter.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/triage_counter.php">
          <span class="material-symbols-outlined nav-icon">add_circle</span>
          <span><?= __('nav_triage_counter', 'Kaunter Triaj') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/doctor/announcements.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/announcements.php">
          <span class="material-symbols-outlined nav-icon">campaign</span>
          <span><?= __('nav_announcements', 'Pengumuman') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/doctor/posters.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/posters.php">
          <span class="material-symbols-outlined nav-icon">image</span>
          <span><?= __('nav_posters', 'Poster') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/doctor/patientfamily.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/patientfamily.php">
          <span class="material-symbols-outlined nav-icon">groups</span>
          <span><?= __('nav_family', 'Pesakit & Keluarga') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center justify-content-between <?= is_nav_active('/doctor/livechat.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/livechat.php">
          <div class="d-flex align-items-center">
            <span class="material-symbols-outlined nav-icon">chat</span>
            <span><?= __('nav_livechat', 'Live Chat') ?></span>
          </div>
          <span class="badge rounded-pill bg-danger sidebar-chat-badge <?= $_chatUnread > 0 ? '' : 'd-none' ?>" style="font-size:0.75rem;">
            <?= $_chatUnread > 99 ? '99+' : $_chatUnread ?>
          </span>
        </a>
      </li>

      <li class="nav-title"><?= __('nav_health_module', 'Modul Kesihatan') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/doctor/health/water.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/health/water.php">
          <span class="nav-icon material-symbols-outlined">water_drop</span>
          <span><?= __('nav_water', 'Air') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/doctor/health/bristol.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/health/bristol.php">
          <span class="nav-icon material-symbols-outlined">bar_chart</span>
          <span><?= __('nav_bristol', 'Bristol') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/doctor/health/mood.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/health/mood.php">
          <span class="nav-icon material-symbols-outlined">sentiment_satisfied</span>
          <span><?= __('nav_mood', 'Mood') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/doctor/health/medicine.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/health/medicine.php">
          <span class="nav-icon material-symbols-outlined">medication</span>
          <span><?= __('nav_medicine', 'Ubat') ?></span>
        </a>
      </li>

      <li class="nav-title"><?= __('nav_settings', 'Akaun') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/doctor/settings.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/doctor/settings.php">
          <span class="material-symbols-outlined nav-icon">settings</span>
          <span><?= __('nav_settings', 'Tetapan') ?></span>
        </a>
      </li>

    <?php elseif ($_navRole === 'volunteer'): ?>
      <!--
      #########################################################################
      #########################################################################
      ###                                                                   ###
      ###  ██╗   ██╗ ██████╗ ██╗     ██╗   ██╗███╗   ██╗████████╗███████╗  ###
      ###  ██║   ██║██╔═══██╗██║     ██║   ██║████╗  ██║╚══██╔══╝██╔════╝  ###
      ###  ██║   ██║██║   ██║██║     ██║   ██║██╔██╗ ██║   ██║   █████╗    ###
      ###  ╚██╗ ██╔╝██║   ██║██║     ██║   ██║██║╚██╗██║   ██║   ██╔══╝    ###
      ###   ╚████╔╝ ╚██████╔╝███████╗╚██████╔╝██║ ╚████║   ██║   ███████╗  ###
      ###    ╚═══╝   ╚═════╝ ╚══════╝ ╚═════╝ ╚═╝  ╚═══╝   ╚═╝   ╚══════╝  ###
      ###                                                                   ###
      ###  =============================================================  ###
      ###  SECTION 3: VOLUNTEER PORTAL NAVIGATION                         ###
      ###  =============================================================  ###
      ###                                                                   ###
      #########################################################################
      #########################################################################
      -->
      <li class="nav-title"><?= __('nav_volunteer_portal', 'Portal Sukarelawan') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/volunteer/dashboard.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/volunteer/dashboard.php">
          <span class="material-symbols-outlined nav-icon">dashboard</span>
          <span><?= __('nav_dashboard', 'Dashboard') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/volunteer/triage_counter.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/volunteer/triage_counter.php">
          <span class="material-symbols-outlined nav-icon">add_circle</span>
          <span><?= __('nav_triage_counter', 'Kaunter Triaj') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/volunteer/triage_list.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/volunteer/triage_list.php">
          <span class="material-symbols-outlined nav-icon">format_list_bulleted</span>
          <span><?= __('nav_triage_list', 'Senarai Triaj') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/volunteer/patients.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/volunteer/patients.php">
          <span class="material-symbols-outlined nav-icon">person_add</span>
          <span><?= __('nav_patients', 'Pendaftaran Pesakit') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/volunteer/announcements.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/volunteer/announcements.php">
          <span class="material-symbols-outlined nav-icon">campaign</span>
          <span><?= __('nav_announcements', 'Pengumuman') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/volunteer/posters.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/volunteer/posters.php">
          <span class="material-symbols-outlined nav-icon">image</span>
          <span><?= __('nav_posters', 'Poster') ?></span>
        </a>
      </li>

      <li class="nav-title"><?= __('nav_settings', 'Akaun') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/volunteer/settings.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/volunteer/settings.php">
          <span class="material-symbols-outlined nav-icon">settings</span>
          <span><?= __('nav_settings', 'Tetapan') ?></span>
        </a>
      </li>

    <?php else: ?>
      <!--
      #########################################################################
      #########################################################################
      ###                                                                   ###
      ###  ██████╗  █████╗ ████████╗██╗███████╗███╗   ██╗████████╗         ###
      ###  ██╔══██╗██╔══██╗╚══██╔══╝██║██╔════╝████╗  ██║╚══██╔══╝         ###
      ###  ██████╔╝███████║   ██║   ██║█████╗  ██╔██╗ ██║   ██║            ###
      ###  ██╔═══╝ ██╔══██║   ██║   ██║██╔══╝  ██║╚██╗██║   ██║            ###
      ###  ██║     ██║  ██║   ██║   ██║███████╗██║ ╚████║   ██║            ###
      ###  ╚═╝     ╚═╝  ╚═╝   ╚═╝   ╚═╝╚══════╝╚═╝  ╚═══╝   ╚═╝            ###
      ###                                                                   ###
      ###  =============================================================  ###
      ###  SECTION 4: PATIENT & COMMUNITY PORTAL NAVIGATION               ###
      ###  =============================================================  ###
      ###                                                                   ###
      #########################################################################
      #########################################################################
      -->
      <li class="nav-title"><?= __('nav_patient_portal', 'Menu Pesakit') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/patient/dashboard.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/patient/dashboard.php">
          <span class="material-symbols-outlined nav-icon">dashboard</span>
          <span><?= __('nav_dashboard', 'Dashboard') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/patient/screening.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/patient/screening.php">
          <span class="material-symbols-outlined nav-icon">fact_check</span>
          <span><?= __('nav_screening', 'Saringan Kesihatan') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/patient/family_register.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/patient/family_register.php">
          <span class="material-symbols-outlined nav-icon">family_restroom</span>
          <span><?= __('nav_family', 'Pendaftaran Keluarga') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center justify-content-between <?= is_nav_active('/patient/livechat.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/patient/livechat.php">
          <div class="d-flex align-items-center">
            <span class="material-symbols-outlined nav-icon">chat</span>
            <span><?= __('nav_ask_doctor', 'Tanya Doktor') ?></span>
          </div>
          <span class="badge rounded-pill bg-danger sidebar-chat-badge <?= $_chatUnread > 0 ? '' : 'd-none' ?>" style="font-size:0.75rem;">
            <?= $_chatUnread > 99 ? '99+' : $_chatUnread ?>
          </span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/patient/announcements.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/patient/announcements.php">
          <span class="material-symbols-outlined nav-icon">campaign</span>
          <span><?= __('nav_announcements', 'Pengumuman') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/patient/posters.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/patient/posters.php">
          <span class="material-symbols-outlined nav-icon">image</span>
          <span><?= __('nav_posters', 'Poster') ?></span>
        </a>
      </li>

      <li class="nav-title"><?= __('nav_health_module', 'Kesihatan Kendiri') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/patient/health/water.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/patient/health/water.php">
          <span class="material-symbols-outlined nav-icon">water_drop</span>
          <span><?= __('nav_water', 'Air Minum') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/patient/health/bristol.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/patient/health/bristol.php">
          <span class="material-symbols-outlined nav-icon">bar_chart</span>
          <span><?= __('nav_bristol', 'Skala Bristol') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/patient/health/mood.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/patient/health/mood.php">
          <span class="material-symbols-outlined nav-icon">sentiment_satisfied</span>
          <span><?= __('nav_mood', 'Jurnal Mood') ?></span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/patient/health/medicine.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/patient/health/medicine.php">
          <span class="material-symbols-outlined nav-icon">medication</span>
          <span><?= __('nav_medicine', 'Peringatan Ubat') ?></span>
        </a>
      </li>

      <li class="nav-title"><?= __('nav_settings', 'Akaun') ?></li>
      <li class="nav-item">
        <a class="nav-link <?= is_nav_active('/patient/settings.php', $_currentScript) ? 'active' : '' ?>" href="<?= $_ROOT ?>/pages/patient/settings.php">
          <span class="material-symbols-outlined nav-icon">settings</span>
          <span><?= __('nav_settings', 'Tetapan') ?></span>
        </a>
      </li>
    <?php endif; ?>

    <!--
    #########################################################################
    ###  UNIVERSAL LOGOUT BUTTON (SHOWN FOR ALL ROLES)                   ###
    #########################################################################
    -->
    <li class="nav-item mt-2">
      <a class="nav-link text-danger" href="<?= $_ROOT ?>/pages/auth/logout.php" onclick="return confirm('<?= __('settings_confirm_logout', 'Log keluar?') ?>')">
        <span class="material-symbols-outlined nav-icon text-danger">logout</span>
        <span><?= __('nav_logout', 'Log Keluar') ?></span>
      </a>
    </li>
  </ul>

  <!-- Sidebar Toggler (Desktop collapse) -->
  <button class="sidebar-toggler" type="button" onclick="sedapToggleSidebar()"></button>
</div>
