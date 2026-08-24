<?php
/**
 * header.php — Universal SeDaP Top Header Banner
 * Location: pages/shared/includes/header.php
 * Included on every working page via:
 * <?php include '../shared/includes/header.php'; ?>
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$siteName = 'SeDaP';
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$userRole = $_SESSION['user_role'] ?? 'user';
$darkMode = $_SESSION['dark_mode'] ?? false;

$roleLabel = [
    'admin'     => 'Administrator',
    'doctor'    => 'Doctor / Medical Staff',
    'volunteer' => 'Volunteer',
    'user'      => 'Patient',
][$userRole] ?? 'User';

// Dynamically calculate relative depth to pages/ root
$currentScriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF']);
if (strpos($currentScriptPath, '/pages/') !== false) {
    $afterPagesPath = substr($currentScriptPath, strpos($currentScriptPath, '/pages/') + 7);
    $dirDepth = substr_count($afterPagesPath, '/');
    $pagesBase = str_repeat('../', $dirDepth);
} else {
    $pagesBase = '../';
}

switch ($userRole) {
    case 'admin':     $settingsUrl = $pagesBase . 'admin/settings.php'; break;
    case 'doctor':    $settingsUrl = $pagesBase . 'doctor/settings.php'; break;
    case 'volunteer': $settingsUrl = $pagesBase . 'volunteer/settings.php'; break;
    case 'user':
    default:          $settingsUrl = $pagesBase . 'patient/settings.php'; break;
}

$logoutUrl = $pagesBase . 'auth/logout.php';
?>
<header class="sedap-header" id="sedap-header">
    <span class="site-name">
        <span class="material-symbols-outlined filled" style="color:var(--primary);vertical-align:-4px;font-size:20px;">health_and_safety</span>
        <?php echo $siteName; ?>
    </span>
    <div class="header-actions">
        <!-- Dark mode toggle -->
        <button class="header-btn" id="dark-mode-btn" title="Toggle dark mode" onclick="toggleDarkMode()">
            <span class="material-symbols-outlined" id="dark-icon">dark_mode</span>
        </button>
        <!-- Settings -->
        <a href="<?php echo $settingsUrl; ?>" class="header-btn" title="Settings">
            <span class="material-symbols-outlined">settings</span>
        </a>
        <!-- Logout -->
        <a href="<?php echo $logoutUrl; ?>" class="header-btn" title="Logout" onclick="return confirm('Adakah anda ingin log keluar?')">
            <span class="material-symbols-outlined">logout</span>
        </a>
    </div>
</header>
<div class="sedap-welcome">
    Selamat datang, <strong><?php echo $userName; ?></strong> &mdash;
    <span style="opacity:0.85;"><?php echo $roleLabel; ?></span>
</div>
<script>
// Dark mode persistence
function toggleDarkMode() {
    document.documentElement.classList.toggle('dark');
    const isDark = document.documentElement.classList.contains('dark');
    const icons = document.querySelectorAll('#dark-icon');
    icons.forEach(ic => { ic.textContent = isDark ? 'light_mode' : 'dark_mode'; });
    localStorage.setItem('sedap_dark', isDark ? '1' : '0');
    // Persist via AJAX
    const url = '<?php echo $pagesBase; ?>shared/actions/toggle_dark.php';
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'dark=' + (isDark ? '1' : '0')
    }).catch(e => {});
}
// Apply on load
(function() {
    const dark = localStorage.getItem('sedap_dark');
    if (dark === '1' || <?php echo $darkMode ? 'true' : 'false'; ?>) {
        document.documentElement.classList.add('dark');
        const icons = document.querySelectorAll('#dark-icon');
        icons.forEach(ic => { ic.textContent = 'light_mode'; });
    }
})();
</script>
