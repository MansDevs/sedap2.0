<?php
/**
 * sidebar_admin.php — Admin Portal Sidebar
 * Location: pages/shared/includes/sidebar_admin.php
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
if (!function_exists('navActive')) {
    function navActive($file, $current, $dir = '') {
        return (basename($file) === $current || $dir === $current) ? 'active' : '';
    }
}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$initials = strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1));
?>
<nav class="sedap-sidebar">
    <div class="logo-area" style="display:flex;align-items:center;justify-content:space-between;width:100%;">
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <div class="logo-icon"><span class="material-symbols-outlined filled" style="font-size:22px;">health_and_safety</span></div>
            <div class="logo-text">
                <h1>SeDaP</h1>
                <p>Admin Portal</p>
            </div>
        </div>
        <button type="button" id="sidebar-dark-btn" class="header-btn" title="Toggle dark mode" onclick="toggleDarkMode()" style="background:transparent;border:none;cursor:pointer;color:var(--on-muted);padding:6px;border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <span class="material-symbols-outlined" id="dark-icon" style="font-size:20px;">dark_mode</span>
        </button>
    </div>

    <span class="nav-section-label">Dashboard</span>
    <a href="<?php echo $pagesBase; ?>admin/dashboard.php" class="nav-link <?php echo navActive('dashboard.php', $current); ?>">
        <span class="material-symbols-outlined">dashboard</span>
        <span>Dashboard</span>
    </a>

    <span class="nav-section-label">Content Management</span>
    <a href="<?php echo $pagesBase; ?>admin/announcements/index.php" class="nav-link <?php echo navActive('index.php', $current, 'announcements'); ?>">
        <span class="material-symbols-outlined">campaign</span>
        <span>Announcements</span>
    </a>
    <a href="<?php echo $pagesBase; ?>admin/posters/index.php" class="nav-link <?php echo navActive('index.php', $current, 'posters'); ?>">
        <span class="material-symbols-outlined">image</span>
        <span>Posters</span>
    </a>

    <span class="nav-section-label">Clinical</span>
    <a href="<?php echo $pagesBase; ?>admin/triage/index.php" class="nav-link <?php echo navActive('index.php', $current, 'triage'); ?>">
        <span class="material-symbols-outlined">emergency</span>
        <span>Triage List</span>
    </a>
    <a href="<?php echo $pagesBase; ?>admin/triage/add.php" class="nav-link <?php echo navActive('add.php', $current); ?>">
        <span class="material-symbols-outlined">add_circle</span>
        <span>New Triage Entry</span>
    </a>
    <a href="<?php echo $pagesBase; ?>admin/screening/index.php" class="nav-link <?php echo navActive('index.php', $current, 'screening'); ?>">
        <span class="material-symbols-outlined">quiz</span>
        <span>Health Screening</span>
    </a>
    <a href="<?php echo $pagesBase; ?>admin/patients/index.php" class="nav-link <?php echo navActive('index.php', $current, 'patients'); ?>">
        <span class="material-symbols-outlined">person</span>
        <span>Patients</span>
    </a>
    <a href="<?php echo $pagesBase; ?>admin/family/index.php" class="nav-link <?php echo navActive('index.php', $current, 'family'); ?>">
        <span class="material-symbols-outlined">family_restroom</span>
        <span>Family Info</span>
    </a>

    <span class="nav-section-label">Health Modules</span>
    <a href="<?php echo $pagesBase; ?>admin/health/bristol.php" class="nav-link <?php echo navActive('bristol.php', $current); ?>">
        <span class="material-symbols-outlined">analytics</span>
        <span>Bristol Scale</span>
    </a>
    <a href="<?php echo $pagesBase; ?>admin/health/water.php" class="nav-link <?php echo navActive('water.php', $current); ?>">
        <span class="material-symbols-outlined">water_drop</span>
        <span>Water Tracker</span>
    </a>
    <a href="<?php echo $pagesBase; ?>admin/health/mood.php" class="nav-link <?php echo navActive('mood.php', $current); ?>">
        <span class="material-symbols-outlined">sentiment_satisfied</span>
        <span>Mood Journal</span>
    </a>
    <a href="<?php echo $pagesBase; ?>admin/health/medicine.php" class="nav-link <?php echo navActive('medicine.php', $current); ?>">
        <span class="material-symbols-outlined">medication</span>
        <span>Medicine & Reminders</span>
    </a>

    <span class="nav-section-label">Administration</span>
    <a href="<?php echo $pagesBase; ?>admin/personnel/index.php" class="nav-link <?php echo navActive('index.php', $current, 'personnel'); ?>">
        <span class="material-symbols-outlined">badge</span>
        <span>Staff & Volunteers</span>
    </a>
    <a href="<?php echo $pagesBase; ?>admin/myaccount.php" class="nav-link <?php echo navActive('myaccount.php', $current); ?>">
        <span class="material-symbols-outlined">manage_accounts</span>
        <span>My Account</span>
    </a>
    <a href="<?php echo $pagesBase; ?>admin/settings.php" class="nav-link <?php echo navActive('settings.php', $current); ?>">
        <span class="material-symbols-outlined">settings</span>
        <span>Settings</span>
    </a>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?php echo $initials; ?></div>
            <div>
                <div class="user-name"><?php echo $userName; ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
        <a href="<?php echo $pagesBase; ?>auth/logout.php" class="nav-link" style="margin-top:0.5rem;"
           onclick="return confirm('Log keluar?')">
            <span class="material-symbols-outlined">logout</span>
            <span>Log Out</span>
        </a>
    </div>
</nav>
<script src="<?php echo $pagesBase; ?>shared/js/sedap-spa.js"></script>
