<?php
$doctorBase = '../';
$activeNav = 'settings';
$pageTitle = 'Settings & Preferences';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/header.php';

$userName = $currentUser['name'] ?? 'Doctor';
$userEmail = $currentUser['email'] ?? '';
$userRole = $currentUser['role'] ?? 'doctor';
$userPhone = $currentUser['phone'] ?? 'Not set';
$userId = (int) $currentUser['id'];

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(function ($p) { return mb_substr($p, 0, 1); }, array_slice($parts, 0, 2));
    return mb_strtoupper(implode('', $letters)) ?: '?';
}
?>

<div class="max-w-6xl mx-auto space-y-6">

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
                <p class="text-xs text-on-surface-variant mb-3"><?php echo htmlspecialchars($userEmail); ?></p>
                
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                    <?php echo htmlspecialchars(str_replace('_', ' ', $userRole)); ?>
                </span>

                <div class="mt-6 pt-5 border-t border-outline-variant/30 text-left space-y-3 text-sm">
                    <div class="flex items-center justify-between text-on-surface-variant">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">badge</span> Practitioner ID
                        </span>
                        <span class="font-medium text-on-surface">#<?php echo (int) $userId; ?></span>
                    </div>
                    <div class="flex items-center justify-between text-on-surface-variant">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">phone</span> Phone
                        </span>
                        <span class="font-medium text-on-surface"><?php echo htmlspecialchars($userPhone); ?></span>
                    </div>
                    <div class="flex items-center justify-between text-on-surface-variant">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">medical_services</span> Clinical Role
                        </span>
                        <span class="font-medium text-primary capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $userRole)); ?></span>
                    </div>
                </div>
            </div>

            <!-- Portal Badge Box -->
            <div class="bg-surface-container-low rounded-[28px] p-6 border border-outline-variant/30">
                <div class="flex items-center gap-3 mb-2 text-primary">
                    <span class="material-symbols-outlined text-[22px]">stethoscope</span>
                    <h3 class="font-semibold text-sm">Clinical Portal</h3>
                </div>
                <p class="text-xs text-on-surface-variant leading-relaxed">
                    Manage patient triage queues, consultation chats, and medicine tracking with dedicated clinical access.
                </p>
            </div>

        </div>

        <!-- Right Column: Settings Sections (8 cols) -->
        <div class="lg:col-span-8 space-y-6 stagger-2">

            <!-- 1. DISPLAY & PREFERENCES -->
            <div class="bg-surface-container-lowest rounded-[28px] p-6 sm:p-7 border border-outline-variant/40 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">palette</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-on-surface">Display & Interface</h2>
                        <p class="text-xs text-on-surface-variant">Adjust your clinical workspace preferences</p>
                    </div>
                </div>

                <div class="divide-y divide-outline-variant/25">
                    <!-- Dark Mode Toggle -->
                    <div class="flex items-center justify-between py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">dark_mode</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface">Dark Mode</p>
                                <p class="text-xs text-on-surface-variant">Reduce screen brightness in clinical rooms</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="doctorDarkModeToggle" class="sr-only peer">
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

            <!-- 2. NOTIFICATIONS & ALERTS -->
            <div class="bg-surface-container-lowest rounded-[28px] p-6 sm:p-7 border border-outline-variant/40 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">notifications_active</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-on-surface">Clinical Alerts & Notifications</h2>
                        <p class="text-xs text-on-surface-variant">Configure real-time clinical alerts</p>
                    </div>
                </div>

                <div class="divide-y divide-outline-variant/25">
                    <!-- Patient Chat Messages -->
                    <div class="flex items-center justify-between py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">chat</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface">Patient & Staff Messages</p>
                                <p class="text-xs text-on-surface-variant">Alert when urgent patient questions or team chats arrive</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <!-- Triage & Red Alerts -->
                    <div class="flex items-center justify-between py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">emergency</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface">Emergency Triage Red-Alerts</p>
                                <p class="text-xs text-on-surface-variant">High-priority sound alerts for critical red-tier cases</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 3. SECURITY & PRIVACY -->
            <div class="bg-surface-container-lowest rounded-[28px] p-6 sm:p-7 border border-outline-variant/40 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-tertiary/10 text-tertiary flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">lock_reset</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-on-surface">Security & Password</h2>
                        <p class="text-xs text-on-surface-variant">Update password and security details</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <!-- Reset Password Link -->
                    <a href="<?php echo $doctorBase; ?>../auth/forgotpass.php" class="flex items-center justify-between p-3.5 rounded-2xl hover:bg-surface-container-low transition-colors group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">password</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">Reset Password</p>
                                <p class="text-xs text-on-surface-variant">Update your clinical account password</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-outline-variant group-hover:text-primary group-hover:translate-x-1 transition-all">chevron_right</span>
                    </a>

                    <!-- Medical Confidentiality Policy -->
                    <a href="#" class="flex items-center justify-between p-3.5 rounded-2xl hover:bg-surface-container-low transition-colors group">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">policy</span>
                            <div>
                                <p class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">Medical Confidentiality & HIPAA/PDPA</p>
                                <p class="text-xs text-on-surface-variant">Patient privacy and clinical records confidentiality regulations</p>
                            </div>
                        </div>
                        <span class="material-symbols-outlined text-outline-variant group-hover:text-primary group-hover:translate-x-1 transition-all">chevron_right</span>
                    </a>
                </div>
            </div>

            <!-- 4. SIGN OUT SECTION -->
            <div class="bg-surface-container-lowest rounded-[28px] p-6 sm:p-7 border border-error/20 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 text-center sm:text-left">
                    <div class="w-10 h-10 rounded-xl bg-error/10 text-error flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">logout</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-sm text-on-surface">Terminate Session</h3>
                        <p class="text-xs text-on-surface-variant">Securely log out of the Clinical portal</p>
                    </div>
                </div>

                <a href="<?php echo $doctorBase; ?>../auth/logout.php" class="w-full sm:w-auto px-6 py-2.5 bg-error hover:bg-on-error-container text-on-error font-semibold text-sm rounded-full transition-all shadow-sm flex items-center justify-center gap-2 active:scale-95">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                    <span>Sign Out</span>
                </a>
            </div>

        </div>

    </div>

</div>

<script>
    const doctorDarkModeToggle = document.getElementById('doctorDarkModeToggle');
    if (doctorDarkModeToggle) {
        doctorDarkModeToggle.addEventListener('change', () => {
            if (doctorDarkModeToggle.checked) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
