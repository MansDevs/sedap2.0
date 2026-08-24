<?php
/**
 * sidebar_doctor.php — Doctor/MA/Nurse Portal Sidebar
 * Location: pages/shared/includes/sidebar_doctor.php
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
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
if (!function_exists('navActiveDr')) {
    function navActiveDr($file, $current, $dir = '') {
        return (basename($file) === $current || ($dir && $dir === $current)) ? 'active' : '';
    }
}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Doctor');
$initials = strtoupper(substr($_SESSION['user_name'] ?? 'D', 0, 2));
?>
<nav class="sedap-sidebar">
    <div class="logo-area" style="display:flex;align-items:center;justify-space:between;justify-content:space-between;width:100%;">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <div class="logo-icon"><span class="material-symbols-outlined filled" style="font-size:22px;">health_and_safety</span></div>
            <div class="logo-text">
                <h1>SeDaP</h1>
                <p>Doctor Portal</p>
            </div>
        </div>
        <button type="button" id="sidebar-dark-btn" class="header-btn" title="Toggle dark mode" onclick="toggleDarkMode()" style="background:transparent;border:none;cursor:pointer;color:var(--on-muted);padding:6px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" id="dark-icon" style="font-size:20px;">dark_mode</span>
        </button>
    </div>

    <a href="<?php echo $pagesBase; ?>doctor/cdashboard.php" class="nav-link <?php echo navActiveDr('cdashboard.php', $current); ?>">
        <span class="material-symbols-outlined">dashboard</span><span>Dashboard</span>
    </a>

    <span class="nav-section-label">Announcements</span>
    <a href="<?php echo $pagesBase; ?>doctor/announcements.php" class="nav-link <?php echo navActiveDr('announcements.php', $current); ?>">
        <span class="material-symbols-outlined">campaign</span><span>Announcements</span>
    </a>
    <a href="<?php echo $pagesBase; ?>doctor/posters.php" class="nav-link <?php echo navActiveDr('posters.php', $current); ?>">
        <span class="material-symbols-outlined">image</span><span>Posters</span>
    </a>

    <span class="nav-section-label">Clinical</span>
    <a href="<?php echo $pagesBase; ?>doctor/triage_counter.php" class="nav-link <?php echo navActiveDr('triage_counter.php', $current); ?>">
        <span class="material-symbols-outlined">emergency</span><span>Triage Entry</span>
    </a>
    <a href="<?php echo $pagesBase; ?>doctor/triage_list.php" class="nav-link <?php echo navActiveDr('triage_list.php', $current); ?>">
        <span class="material-symbols-outlined">view_list</span><span>Triage List</span>
    </a>
    <a href="<?php echo $pagesBase; ?>doctor/patientfamily.php" class="nav-link <?php echo navActiveDr('patientfamily.php', $current); ?>">
        <span class="material-symbols-outlined">group</span><span>Patients &amp; Families</span>
    </a>

    <span class="nav-section-label">Communication</span>
    <a href="<?php echo $pagesBase; ?>doctor/livechat.php" class="nav-link <?php echo navActiveDr('livechat.php', $current); ?>">
        <span class="material-symbols-outlined">forum</span><span>Live Chat</span>
        <span class="nav-badge" id="chat-badge" style="display:none">0</span>
    </a>

    <span class="nav-section-label">Health Modules</span>
    <a href="<?php echo $pagesBase; ?>doctor/health/bristol.php" class="nav-link <?php echo navActiveDr('bristol.php', $current); ?>">
        <span class="material-symbols-outlined">analytics</span><span>Bristol Scale</span>
    </a>
    <a href="<?php echo $pagesBase; ?>doctor/health/water.php" class="nav-link <?php echo navActiveDr('water.php', $current); ?>">
        <span class="material-symbols-outlined">water_drop</span><span>Water Tracker</span>
    </a>
    <a href="<?php echo $pagesBase; ?>doctor/health/mood.php" class="nav-link <?php echo navActiveDr('mood.php', $current); ?>">
        <span class="material-symbols-outlined">sentiment_satisfied</span><span>Mood Journal</span>
    </a>
    <a href="<?php echo $pagesBase; ?>doctor/health/medicine.php" class="nav-link <?php echo navActiveDr('medicine.php', $current); ?>">
        <span class="material-symbols-outlined">medication</span><span>Medicine</span>
    </a>

    <span class="nav-section-label">Account</span>
    <a href="<?php echo $pagesBase; ?>doctor/settings.php" class="nav-link <?php echo navActiveDr('settings.php', $current); ?>">
        <span class="material-symbols-outlined">settings</span><span>Settings</span>
    </a>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?php echo $initials; ?></div>
            <div>
                <div class="user-name"><?php echo $userName; ?></div>
                <div class="user-role">Doctor / MA / Nurse</div>
            </div>
        </div>
        <a href="<?php echo $pagesBase; ?>auth/logout.php" class="nav-link" style="margin-top:0.5rem;"
           onclick="return confirm('Log keluar?')">
            <span class="material-symbols-outlined">logout</span><span>Log Out</span>
        </a>
    </div>
</nav>
<script src="<?php echo $pagesBase; ?>shared/js/sedap-spa.js"></script>
