<?php
/**
 * sidebar_user.php — Patient / User Portal Sidebar
 * Location: pages/shared/includes/sidebar_user.php
 */
$currentScriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF']);
if (strpos($currentScriptPath, '/pages/') !== false) {
    $afterPagesPath = substr($currentScriptPath, strpos($currentScriptPath, '/pages/') + 7);
    $dirDepth = substr_count($afterPagesPath, '/');
    $pagesBase = str_repeat('../', $dirDepth);
} else {
    $pagesBase = '../';
}

$current = basename($_SERVER['PHP_SELF']);
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Patient');
$initials = strtoupper(substr($_SESSION['user_name'] ?? 'P', 0, 1));
if (!function_exists('navActiveUsr')) {
    function navActiveUsr($file, $current) {
        return basename($file) === $current ? 'active' : '';
    }
}
?>
<nav class="sedap-sidebar">
    <div class="logo-area" style="display:flex;align-items:center;justify-content:space-between;width:100%;">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <div class="logo-icon"><span class="material-symbols-outlined filled" style="font-size:22px;">health_and_safety</span></div>
            <div class="logo-text">
                <h1>SeDaP</h1>
                <p>Patient Portal</p>
            </div>
        </div>
        <button type="button" id="sidebar-dark-btn" class="header-btn" title="Toggle dark mode" onclick="toggleDarkMode()" style="background:transparent;border:none;cursor:pointer;color:var(--on-muted);padding:6px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" id="dark-icon" style="font-size:20px;">dark_mode</span>
        </button>
    </div>

    <a href="<?php echo $pagesBase; ?>patient/dashboard.php" class="nav-link <?php echo navActiveUsr('dashboard.php', $current); ?>">
        <span class="material-symbols-outlined">dashboard</span><span>Utama (Home)</span>
    </a>

    <span class="nav-section-label">Perkhidmatan</span>
    <a href="<?php echo $pagesBase; ?>patient/screening.php" class="nav-link <?php echo navActiveUsr('screening.php', $current); ?>">
        <span class="material-symbols-outlined">quiz</span><span>Saringan Kesihatan</span>
    </a>
    <a href="<?php echo $pagesBase; ?>patient/family_register.php" class="nav-link <?php echo navActiveUsr('family_register.php', $current); ?>">
        <span class="material-symbols-outlined">family_restroom</span><span>Pendaftaran Isi Rumah</span>
    </a>
    <a href="<?php echo $pagesBase; ?>patient/livechat.php" class="nav-link <?php echo navActiveUsr('livechat.php', $current); ?>">
        <span class="material-symbols-outlined">forum</span><span>Bual Bersama Doktor</span>
    </a>

    <span class="nav-section-label">Modul Kesihatan</span>
    <a href="<?php echo $pagesBase; ?>patient/health/bristol.php" class="nav-link <?php echo navActiveUsr('bristol.php', $current); ?>">
        <span class="material-symbols-outlined">analytics</span><span>Skala Najis Bristol</span>
    </a>
    <a href="<?php echo $pagesBase; ?>patient/health/water.php" class="nav-link <?php echo navActiveUsr('water.php', $current); ?>">
        <span class="material-symbols-outlined">water_drop</span><span>Penjejak Air Minuman</span>
    </a>
    <a href="<?php echo $pagesBase; ?>patient/health/mood.php" class="nav-link <?php echo navActiveUsr('mood.php', $current); ?>">
        <span class="material-symbols-outlined">sentiment_satisfied</span><span>Jurnal Emosi &amp; Mood</span>
    </a>
    <a href="<?php echo $pagesBase; ?>patient/health/medicine.php" class="nav-link <?php echo navActiveUsr('medicine.php', $current); ?>">
        <span class="material-symbols-outlined">medication</span><span>Ubat &amp; Peringatan</span>
    </a>

    <span class="nav-section-label">Info &amp; Pengumuman</span>
    <a href="<?php echo $pagesBase; ?>patient/announcements.php" class="nav-link <?php echo navActiveUsr('announcements.php', $current); ?>">
        <span class="material-symbols-outlined">campaign</span><span>Pengumuman</span>
    </a>
    <a href="<?php echo $pagesBase; ?>patient/posters.php" class="nav-link <?php echo navActiveUsr('posters.php', $current); ?>">
        <span class="material-symbols-outlined">image</span><span>Poster Kesihatan</span>
    </a>
    <a href="<?php echo $pagesBase; ?>patient/settings.php" class="nav-link <?php echo navActiveUsr('settings.php', $current); ?>">
        <span class="material-symbols-outlined">settings</span><span>Tetapan</span>
    </a>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?php echo $initials; ?></div>
            <div>
                <div class="user-name"><?php echo $userName; ?></div>
                <div class="user-role">Pesakit / User</div>
            </div>
        </div>
        <a href="<?php echo $pagesBase; ?>auth/logout.php" class="nav-link" style="margin-top:0.5rem;"
           onclick="return confirm('Log keluar?')">
            <span class="material-symbols-outlined">logout</span><span>Log Out</span>
        </a>
    </div>
</nav>
<script src="<?php echo $pagesBase; ?>shared/js/sedap-spa.js"></script>
