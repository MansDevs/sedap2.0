<?php
/**
 * sidebar_volunteer.php — Volunteer Portal Sidebar
 * Location: pages/shared/includes/sidebar_volunteer.php
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
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Volunteer');
$initials = strtoupper(substr($_SESSION['user_name'] ?? 'V', 0, 1));
if (!function_exists('navActiveVol')) {
    function navActiveVol($file, $current) {
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
                <p>Volunteer Portal</p>
            </div>
        </div>
        <button type="button" id="sidebar-dark-btn" class="header-btn" title="Toggle dark mode" onclick="toggleDarkMode()" style="background:transparent;border:none;cursor:pointer;color:var(--on-muted);padding:6px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" id="dark-icon" style="font-size:20px;">dark_mode</span>
        </button>
    </div>

    <a href="<?php echo $pagesBase; ?>volunteer/dashboard.php" class="nav-link <?php echo navActiveVol('dashboard.php', $current); ?>">
        <span class="material-symbols-outlined">dashboard</span><span>Dashboard</span>
    </a>

    <span class="nav-section-label">Information</span>
    <a href="<?php echo $pagesBase; ?>volunteer/announcements.php" class="nav-link <?php echo navActiveVol('announcements.php', $current); ?>">
        <span class="material-symbols-outlined">campaign</span><span>Announcements</span>
    </a>
    <a href="<?php echo $pagesBase; ?>volunteer/posters.php" class="nav-link <?php echo navActiveVol('posters.php', $current); ?>">
        <span class="material-symbols-outlined">image</span><span>Posters</span>
    </a>

    <span class="nav-section-label">Clinical</span>
    <a href="<?php echo $pagesBase; ?>volunteer/triage_counter.php" class="nav-link <?php echo navActiveVol('triage_counter.php', $current); ?>">
        <span class="material-symbols-outlined">emergency</span><span>Triage Entry</span>
    </a>
    <a href="<?php echo $pagesBase; ?>volunteer/triage_list.php" class="nav-link <?php echo navActiveVol('triage_list.php', $current); ?>">
        <span class="material-symbols-outlined">view_list</span><span>Triage List</span>
    </a>
    <a href="<?php echo $pagesBase; ?>volunteer/patients.php" class="nav-link <?php echo navActiveVol('patients.php', $current); ?>">
        <span class="material-symbols-outlined">person_add</span><span>Patient Registration</span>
    </a>

    <span class="nav-section-label">Account</span>
    <a href="<?php echo $pagesBase; ?>volunteer/settings.php" class="nav-link <?php echo navActiveVol('settings.php', $current); ?>">
        <span class="material-symbols-outlined">settings</span><span>Settings</span>
    </a>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?php echo $initials; ?></div>
            <div>
                <div class="user-name"><?php echo $userName; ?></div>
                <div class="user-role">Volunteer</div>
            </div>
        </div>
        <a href="<?php echo $pagesBase; ?>auth/logout.php" class="nav-link" style="margin-top:0.5rem;"
           onclick="return confirm('Log keluar?')">
            <span class="material-symbols-outlined">logout</span><span>Log Out</span>
        </a>
    </div>
</nav>
<script src="<?php echo $pagesBase; ?>shared/js/sedap-spa.js"></script>
