<?php
$adminBase = '../';
$activeNav = 'settings';
$pageTitle = 'Settings & Preferences';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/access.php';
requireRole($currentUser, [], $adminBase);

$msg = '';
$err = '';

// Handle Profile Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $newName     = trim($_POST['name'] ?? '');
    $newUsername = trim($_POST['username'] ?? '');
    $newPhone    = trim($_POST['phone'] ?? '');

    if (empty($newName)) {
        $err = 'Name cannot be empty.';
    } elseif (empty($newUsername)) {
        $err = 'Username cannot be empty.';
    } else {
        try {
            // Check if username is already taken by another user
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $checkStmt->execute([$newUsername, $currentUser['id']]);
            if ($checkStmt->fetch()) {
                $err = 'This username is already taken by another account. Please choose a different one.';
            } else {
                $upStmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, phone = ? WHERE id = ?");
                $upStmt->execute([$newName, $newUsername, $newPhone, $currentUser['id']]);

                // Update session and local variable
                $_SESSION['user_name'] = $newName;
                $_SESSION['username']  = $newUsername;
                $_SESSION['user_phone'] = $newPhone;
                
                $currentUser['name']     = $newName;
                $currentUser['username'] = $newUsername;
                $currentUser['phone']    = $newPhone;

                $msg = 'Your profile information (username and phone number) has been updated successfully!';
            }
        } catch (Exception $e) {
            $err = 'Failed to update profile: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

$userName     = $currentUser['name'] ?? 'Admin';
$userUsername = $currentUser['username'] ?? '';
$userEmail    = $currentUser['email'] ?? '';
$userRole     = $currentUser['role'] ?? 'admin';
$userPhone    = !empty($currentUser['phone']) ? $currentUser['phone'] : '';
$userId       = (int) $currentUser['id'];

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(function ($p) { return mb_substr($p, 0, 1); }, array_slice($parts, 0, 2));
    return mb_strtoupper(implode('', $letters)) ?: '?';
}
?>

<div class="max-w-6xl mx-auto space-y-6">

    <!-- Alert Messages -->
    <?php if ($msg): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-[20px] flex items-center gap-3 shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-emerald-600 text-[24px]">check_circle</span>
            <div class="font-medium text-sm"><?php echo htmlspecialchars($msg); ?></div>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-[20px] flex items-center gap-3 shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-rose-600 text-[24px]">error</span>
            <div class="font-medium text-sm"><?php echo htmlspecialchars($err); ?></div>
        </div>
    <?php endif; ?>

    <!-- Grid Layout: Profile & Information + Settings Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Column: User Profile Card (4 cols) -->
        <div class="lg:col-span-4 space-y-6 stagger-1">
            
            <!-- Profile Summary Card -->
            <div class="interactive-card bg-surface-container-lowest rounded-[28px] p-6 border border-outline-variant/40 shadow-sm text-center">
                <div class="w-20 h-20 rounded-full bg-primary/15 text-primary text-2xl font-bold flex items-center justify-center mx-auto mb-4 font-headline shadow-inner">
                    <?php echo htmlspecialchars(initials($userName)); ?>
                </div>
                <h2 class="font-headline text-lg font-bold text-on-surface mb-0.5"><?php echo htmlspecialchars($userName); ?></h2>
                <?php if (!empty($userUsername)): ?>
                    <p class="text-xs font-mono font-semibold text-primary mb-1">@<?php echo htmlspecialchars($userUsername); ?></p>
                <?php endif; ?>
                <p class="text-xs text-on-surface-variant mb-3"><?php echo htmlspecialchars($userEmail); ?></p>
                
                <div class="flex flex-col items-center">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary uppercase tracking-wider">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                        <?php echo htmlspecialchars(str_replace('_', ' ', $userRole)); ?>
                    </span>

                    <!-- Edit Profile Trigger Button Directly Below Badge -->
                    <button type="button" onclick="openEditProfileModal()" class="mt-3.5 inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-semibold bg-primary/10 hover:bg-primary text-primary hover:text-on-primary border border-primary/20 shadow-xs hover:shadow transition-all duration-200 active:scale-95 group">
                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:rotate-12">edit</span>
                        <span>Edit Profile</span>
                    </button>
                </div>

                <div class="mt-6 pt-5 border-t border-outline-variant/30 text-left space-y-3 text-sm">
                    <div class="flex items-center justify-between text-on-surface-variant">
                        <span class="flex items-center gap-2 text-xs">
                            <span class="material-symbols-outlined text-[18px]">badge</span> User ID
                        </span>
                        <span class="font-medium text-on-surface text-xs">#<?php echo (int) $userId; ?></span>
                    </div>
                    <div class="flex items-center justify-between text-on-surface-variant">
                        <span class="flex items-center gap-2 text-xs">
                            <span class="material-symbols-outlined text-[18px]">alternate_email</span> Username
                        </span>
                        <span class="font-medium text-on-surface text-xs font-mono"><?php echo !empty($userUsername) ? htmlspecialchars($userUsername) : '<span class="text-on-surface-variant/60">Not set</span>'; ?></span>
                    </div>
                    <div class="flex items-center justify-between text-on-surface-variant">
                        <span class="flex items-center gap-2 text-xs">
                            <span class="material-symbols-outlined text-[18px]">phone</span> Phone
                        </span>
                        <span class="font-medium text-on-surface text-xs"><?php echo !empty($userPhone) ? htmlspecialchars($userPhone) : '<span class="text-on-surface-variant/60">Not set</span>'; ?></span>
                    </div>
                    <div class="flex items-center justify-between text-on-surface-variant">
                        <span class="flex items-center gap-2 text-xs">
                            <span class="material-symbols-outlined text-[18px]">shield_person</span> Access Level
                        </span>
                        <span class="font-medium text-primary capitalize text-xs">Full Admin</span>
                    </div>
                </div>
            </div>

            <!-- Portal Badge Box -->
            <div class="bg-surface-container-low rounded-[28px] p-6 border border-outline-variant/30">
                <div class="flex items-center gap-3 mb-2 text-primary">
                    <span class="material-symbols-outlined text-[22px]">admin_panel_settings</span>
                    <h3 class="font-semibold text-sm">Admin Control Center</h3>
                </div>
                <p class="text-xs text-on-surface-variant leading-relaxed">
                    You have administrator permissions to configure system options, personnel rosters, screening forms, and patient management.
                </p>
            </div>

        </div>

        <!-- Right Column: Settings Sections (8 cols) -->
        <div class="lg:col-span-8 space-y-6 stagger-2">



            <!-- 2. DISPLAY & THEME PREFERENCES -->
            <div class="bg-surface-container-lowest rounded-[28px] p-6 sm:p-7 border border-outline-variant/40 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">palette</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-on-surface">Display & Theme</h2>
                        <p class="text-xs text-on-surface-variant">Manage your admin workspace appearance</p>
                    </div>
                </div>

                <div class="divide-y divide-outline-variant/25">
                    <!-- Dark Mode Toggle -->
                    <div class="flex items-center justify-between py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">dark_mode</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface">Dark Mode</p>
                                <p class="text-xs text-on-surface-variant">Reduce screen glare during low-light hours</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="adminDarkModeToggle" class="sr-only peer" <?php echo !empty($currentUser['dark_mode']) ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <!-- System Language -->
                    <div class="flex items-center justify-between py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">language</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface">System Language</p>
                                <p class="text-xs text-on-surface-variant">Set default interface language</p>
                            </div>
                        </div>
                        <select class="text-xs font-semibold px-3 py-1.5 bg-surface-container-low border border-outline-variant/40 rounded-xl text-on-surface focus:outline-none focus:border-primary">
                            <option value="en">English (US)</option>
                            <option value="ms">Bahasa Melayu</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 3. NOTIFICATIONS & ALERTS -->
            <div class="bg-surface-container-lowest rounded-[28px] p-6 sm:p-7 border border-outline-variant/40 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">notifications_active</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-on-surface">Notifications</h2>
                        <p class="text-xs text-on-surface-variant">Configure real-time system alerts and communication</p>
                    </div>
                </div>

                <div class="divide-y divide-outline-variant/25">
                    <!-- Live Chat Notifications -->
                    <div class="flex items-center justify-between py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">chat</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface">Live Chat Messages</p>
                                <p class="text-xs text-on-surface-variant">Receive notifications for incoming staff and patient messages</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <!-- Triage & Incident Alerts -->
                    <div class="flex items-center justify-between py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">emergency</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface">Triage Emergency Alerts</p>
                                <p class="text-xs text-on-surface-variant">Immediate sound alerts when red-level triage entries occur</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 4. SECURITY & PRIVACY -->
            <div class="bg-surface-container-lowest rounded-[28px] p-6 sm:p-7 border border-outline-variant/40 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-tertiary/10 text-tertiary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">lock_reset</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-on-surface">Security & Privacy</h2>
                        <p class="text-xs text-on-surface-variant">Manage authentication and password security</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <!-- Reset Password Link -->
                    <a href="<?php echo $adminBase; ?>../auth/forgotpass.php" class="flex items-center justify-between p-3.5 rounded-2xl hover:bg-surface-container-low transition-colors group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">password</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">Reset Password</p>
                                <p class="text-xs text-on-surface-variant">Update your administrator account password</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-outline-variant group-hover:text-primary group-hover:translate-x-1 transition-all">chevron_right</span>
                    </a>

                    <!-- Privacy Policy -->
                    <a href="#" class="flex items-center justify-between p-3.5 rounded-2xl hover:bg-surface-container-low transition-colors group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">policy</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">Data Privacy & Compliance</p>
                                <p class="text-xs text-on-surface-variant">Healthcare records compliance and data security policies</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-outline-variant group-hover:text-primary group-hover:translate-x-1 transition-all">chevron_right</span>
                    </a>
                </div>
            </div>

            <!-- 5. SIGN OUT SECTION -->
            <div class="bg-surface-container-lowest rounded-[28px] p-6 sm:p-7 border border-error/20 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 text-center sm:text-left">
                    <div class="w-10 h-10 rounded-xl bg-error/10 text-error flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">logout</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-sm text-on-surface">Terminate Session</h3>
                        <p class="text-xs text-on-surface-variant">Securely log out of the Admin portal</p>
                    </div>
                </div>

                <a href="<?php echo $adminBase; ?>../auth/logout.php" class="w-full sm:w-auto px-6 py-2.5 bg-error hover:bg-on-error-container text-on-error font-semibold text-sm rounded-full transition-all shadow-sm flex items-center justify-center gap-2 active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                    <span>Sign Out</span>
                </a>
            </div>

        </div>

    </div>

</div>

<!-- ============================================================= -->
<!-- MODAL: EDIT ADMINISTRATOR PROFILE -->
<!-- ============================================================= -->
<div id="editProfileModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm hidden overflow-y-auto">
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] w-full max-w-lg shadow-2xl overflow-hidden my-auto animate-scale-up">
        <div class="px-6 py-5 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[24px]">edit_note</span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-lg text-on-surface">Edit Profile</h3>
                    <p class="text-xs text-on-surface-variant">Update your administrator credentials and contacts</p>
                </div>
            </div>
            <button type="button" onclick="closeEditProfileModal()" class="text-on-surface-variant hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <form method="POST" class="p-6 space-y-4 text-sm">
            <input type="hidden" name="action" value="update_profile">

            <!-- Full Name -->
            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1.5">
                    Full Legal Name <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">person</span>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($userName); ?>"
                           class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm pl-10 pr-4 py-2.5 rounded-2xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                </div>
            </div>

            <!-- Username -->
            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1.5">
                    Username (@username) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">alternate_email</span>
                    <input type="text" name="username" required value="<?php echo htmlspecialchars($userUsername); ?>" placeholder="e.g. admin_mans"
                           class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm pl-10 pr-4 py-2.5 rounded-2xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 font-mono transition-all">
                </div>
                <div class="text-[11px] text-on-surface-variant mt-1">Unique handle used across system logs and chat.</div>
            </div>

            <!-- Phone Number -->
            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1.5">
                    Phone Number
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">phone</span>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($userPhone); ?>" placeholder="e.g. +60 12-345 6789"
                           class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm pl-10 pr-4 py-2.5 rounded-2xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all">
                </div>
            </div>

            <!-- Email Address (Read-only) -->
            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1.5">
                    Email Address (Read-only)
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">mail</span>
                    <input type="email" readonly disabled value="<?php echo htmlspecialchars($userEmail); ?>"
                           class="w-full bg-surface-container/60 border border-outline-variant/30 text-on-surface-variant text-sm pl-10 pr-4 py-2.5 rounded-2xl cursor-not-allowed">
                </div>
            </div>

            <div class="pt-4 border-t border-outline-variant/20 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeEditProfileModal()" class="px-5 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold rounded-full transition-colors">
                    Cancel
                </button>
                <button type="submit" class="inline-flex items-center gap-1.5 px-6 py-2 bg-primary hover:bg-primary/90 text-on-primary text-xs font-bold rounded-full shadow-sm transition-all duration-200 active:scale-95">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('editProfileModal');
        if (el && el.parentElement !== document.body) document.body.appendChild(el);
    });

    function openEditProfileModal() {
        var modal = document.getElementById('editProfileModal');
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        var mainEl = document.querySelector('main');
        if (mainEl) mainEl.style.overflow = 'hidden';
    }

    function closeEditProfileModal() {
        var modal = document.getElementById('editProfileModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
        var mainEl = document.querySelector('main');
        if (mainEl) mainEl.style.overflow = 'auto';
    }

    const adminDarkModeToggle = document.getElementById('adminDarkModeToggle');
    if (adminDarkModeToggle) {
        adminDarkModeToggle.addEventListener('change', async () => {
            const isDark = adminDarkModeToggle.checked;
            
            // 1. Instantly update HTML class
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // 2. Save to localStorage for instant synchronous restoration
            try {
                localStorage.setItem('sedap_dark_mode', isDark ? 'true' : 'false');
            } catch (e) {}

            // 3. Save to users.dark_mode in MySQL Database & Session via AJAX
            try {
                const res = await fetch('<?php echo $adminBase; ?>../shared/actions/set_dark_mode.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ dark_mode: isDark ? 1 : 0 })
                });
                const data = await res.json();
                if (!data.ok) {
                    console.error('Failed to save dark mode to DB:', data.error);
                }
            } catch (err) {
                console.error('Network error saving dark mode:', err);
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
