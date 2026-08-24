<?php
/**
 * sidebar.php — Universal SeDaP Sidebar Dispatcher
 * Location: pages/shared/includes/sidebar.php
 * Included on every working page via: <?php include '../shared/includes/sidebar.php'; ?>
 * Automatically detects session role and renders the matching sidebar from this shared folder.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentScriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF']);
if (strpos($currentScriptPath, '/pages/') !== false) {
    $afterPagesPath = substr($currentScriptPath, strpos($currentScriptPath, '/pages/') + 7);
    $dirDepth = substr_count($afterPagesPath, '/');
    $pagesBase = str_repeat('../', $dirDepth);
} else {
    $pagesBase = '../';
}

$userRole = $_SESSION['user_role'] ?? 'user';

switch ($userRole) {
    case 'admin':
        include __DIR__ . '/sidebar_admin.php';
        break;
    case 'doctor':
        include __DIR__ . '/sidebar_doctor.php';
        break;
    case 'volunteer':
        include __DIR__ . '/sidebar_volunteer.php';
        break;
    case 'user':
    default:
        include __DIR__ . '/sidebar_user.php';
        break;
}
?>
<!-- Dark Mode Toggle Script -->
<script>
function toggleDarkMode() {
    document.documentElement.classList.toggle('dark');
    const isDark = document.documentElement.classList.contains('dark');
    const icon = document.getElementById('dark-icon');
    if (icon) icon.textContent = isDark ? 'light_mode' : 'dark_mode';
    localStorage.setItem('sedap_dark', isDark ? '1' : '0');
    const url = '<?php echo $pagesBase; ?>shared/actions/toggle_dark.php';
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'dark=' + (isDark ? '1' : '0')
    }).catch(e => {});
}
(function() {
    const dark = localStorage.getItem('sedap_dark');
    if (dark === '1' || <?php echo ($_SESSION['dark_mode'] ?? false) ? 'true' : 'false'; ?>) {
        document.documentElement.classList.add('dark');
        const icon = document.getElementById('dark-icon');
        if (icon) icon.textContent = 'light_mode';
    }
})();
</script>
<!-- Seamless No-Refresh Sidebar Navigation Script -->
<script src="<?php echo $pagesBase; ?>shared/js/sedap-spa.js"></script>
