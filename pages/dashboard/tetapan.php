<?php
session_start();

// Protect the page: If no session exists, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/db.php';

$userId = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, name, email, role, phone, avatar_url, dark_mode FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

$userName = $currentUser['name'] ?? ($_SESSION['user_name'] ?? 'User');
$userEmail = $currentUser['email'] ?? '';
$userRole = $currentUser['role'] ?? 'staff';
$userPhone = $currentUser['phone'] ?? 'Not set';

function userInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(function ($p) { return mb_substr($p, 0, 1); }, array_slice($parts, 0, 2));
    return mb_strtoupper(implode('', $letters)) ?: '?';
}
?>

<!DOCTYPE html>
<html class="h-full" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Settings - SeDaP</title>
    <link rel="icon" type="image/jpeg" href="../auth/logo.jpg">
    
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS & MD3 Theme -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../../assets/js/theme-config.js"></script>
    <link rel="stylesheet" href="../../assets/css/animations.css">
    
    <style>
        .mesh-bg {
            background-color: #f7f9fb;
            background-image: 
                radial-gradient(at 10% 20%, hsla(212, 100%, 37%, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(188, 100%, 75%, 0.12) 0px, transparent 50%);
            background-attachment: fixed;
        }
        
        body::-webkit-scrollbar { width: 6px; }
        body::-webkit-scrollbar-thumb { background: #c2c6d5; border-radius: 10px; }
    </style>
</head>
<body class="h-full mesh-bg text-on-surface font-body antialiased">

<div class="min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <header class="bg-surface-container-lowest/80 backdrop-blur border-b border-outline-variant/30 sticky top-0 z-30 px-4 sm:px-8 py-4">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="dashboard.php" class="p-2 -ml-2 rounded-full hover:bg-surface-container-low text-primary transition-colors flex items-center justify-center active:scale-95" title="Back to Dashboard">
                    <span class="material-symbols-outlined text-[24px]">arrow_back</span>
                </a>
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl overflow-hidden shadow-sm border border-outline-variant/30 p-0.5 bg-surface-container-lowest flex items-center justify-center">
                        <img src="../auth/logo.jpg" alt="SeDaP Logo" class="w-full h-full object-cover rounded-[8px]">
                    </div>
                    <div>
                        <h1 class="font-headline font-bold text-lg text-on-surface leading-tight">Settings & Preferences</h1>
                        <p class="text-xs text-on-surface-variant">Manage your account preferences and security</p>
                    </div>
                </div>
            </div>

            <!-- Profile Chip -->
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex flex-col text-right">
                    <span class="text-sm font-semibold text-on-surface"><?php echo htmlspecialchars($userName); ?></span>
                    <span class="text-xs text-on-surface-variant capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $userRole)); ?></span>
                </div>
                <div class="w-10 h-10 rounded-full bg-primary/15 text-primary font-bold flex items-center justify-center font-headline shrink-0">
                    <?php echo htmlspecialchars(userInitials($userName)); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-5xl w-full mx-auto p-4 sm:p-6 md:p-8 space-y-6">

        <!-- Grid Layout: Sidebar Navigation & Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Left Column: User Profile Card (4 cols) -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- Profile Summary Card -->
                <div class="bg-surface-container-lowest rounded-[28px] p-6 border border-outline-variant/40 shadow-sm text-center">
                    <div class="w-20 h-20 rounded-full bg-primary/15 text-primary text-2xl font-bold flex items-center justify-center mx-auto mb-4 font-headline shadow-inner">
                        <?php echo htmlspecialchars(userInitials($userName)); ?>
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
                                <span class="material-symbols-outlined text-[18px]">badge</span> User ID
                            </span>
                            <span class="font-medium text-on-surface">#<?php echo (int) $userId; ?></span>
                        </div>
                        <div class="flex items-center justify-between text-on-surface-variant">
                            <span class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">phone</span> Phone
                            </span>
                            <span class="font-medium text-on-surface"><?php echo htmlspecialchars($userPhone); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Help / Information Box -->
                <div class="bg-surface-container-low rounded-[28px] p-6 border border-outline-variant/30">
                    <div class="flex items-center gap-3 mb-2 text-primary">
                        <span class="material-symbols-outlined text-[22px]">health_and_safety</span>
                        <h3 class="font-semibold text-sm">SeDaP Healthcare</h3>
                    </div>
                    <p class="text-xs text-on-surface-variant leading-relaxed">
                        Connecting volunteers, healthcare providers, and community care teams seamlessly.
                    </p>
                </div>

            </div>

            <!-- Right Column: Settings Sections (8 cols) -->
            <div class="lg:col-span-8 space-y-6">

                <!-- 1. DISPLAY & PREFERENCES -->
                <div class="bg-surface-container-lowest rounded-[28px] p-6 sm:p-7 border border-outline-variant/40 shadow-sm">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-[20px]">palette</span>
                        </div>
                        <div>
                            <h2 class="font-headline font-bold text-base text-on-surface">Display & Interface</h2>
                            <p class="text-xs text-on-surface-variant">Customize your viewing preferences</p>
                        </div>
                    </div>

                    <div class="divide-y divide-outline-variant/25">
                        <!-- Dark Mode Toggle -->
                        <div class="flex items-center justify-between py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-on-surface-variant">dark_mode</span>
                                <div>
                                    <p class="text-sm font-semibold text-on-surface">Dark Mode</p>
                                    <p class="text-xs text-on-surface-variant">Reduce glare and switch to darker tones</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="darkModeToggle" class="sr-only peer">
                                <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>

                        <!-- Language Option -->
                        <div class="flex items-center justify-between py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-on-surface-variant">language</span>
                                <div>
                                    <p class="text-sm font-semibold text-on-surface">Language</p>
                                    <p class="text-xs text-on-surface-variant">Choose your preferred system language</p>
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
                            <h2 class="font-headline font-bold text-base text-on-surface">Notifications</h2>
                            <p class="text-xs text-on-surface-variant">Control how and when you receive system alerts</p>
                        </div>
                    </div>

                    <div class="divide-y divide-outline-variant/25">
                        <!-- Live Chat Notifications -->
                        <div class="flex items-center justify-between py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-on-surface-variant">chat</span>
                                <div>
                                    <p class="text-sm font-semibold text-on-surface">Live Chat Messages</p>
                                    <p class="text-xs text-on-surface-variant">Get notified when new direct messages arrive</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" checked class="sr-only peer">
                                <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>

                        <!-- Reminders & Health Updates -->
                        <div class="flex items-center justify-between py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-on-surface-variant">alarm</span>
                                <div>
                                    <p class="text-sm font-semibold text-on-surface">Medicine & Health Reminders</p>
                                    <p class="text-xs text-on-surface-variant">Notifications for scheduled doses and checks</p>
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
                            <h2 class="font-headline font-bold text-base text-on-surface">Security & Privacy</h2>
                            <p class="text-xs text-on-surface-variant">Manage password, login safety, and terms</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <!-- Reset Password Link -->
                        <a href="../auth/forgotpass.php" class="flex items-center justify-between p-3.5 rounded-2xl hover:bg-surface-container-low transition-colors group">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">password</span>
                                <div>
                                    <p class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">Reset Password</p>
                                    <p class="text-xs text-on-surface-variant">Update your current account password</p>
                                </div>
                            </div>
                            <span class="material-symbols-outlined text-outline-variant group-hover:text-primary group-hover:translate-x-1 transition-all">chevron_right</span>
                        </a>

                        <!-- Privacy Policy -->
                        <a href="#" class="flex items-center justify-between p-3.5 rounded-2xl hover:bg-surface-container-low transition-colors group">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">policy</span>
                                <div>
                                    <p class="text-sm font-semibold text-on-surface group-hover:text-primary transition-colors">Privacy Policy</p>
                                    <p class="text-xs text-on-surface-variant">Read data protection & confidentiality terms</p>
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
                            <p class="text-xs text-on-surface-variant">Sign out of your account on this browser</p>
                        </div>
                    </div>

                    <a href="../auth/logout.php" class="w-full sm:w-auto px-6 py-2.5 bg-error hover:bg-on-error-container text-on-error font-semibold text-sm rounded-full transition-all shadow-sm flex items-center justify-center gap-2 active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        <span>Sign Out</span>
                    </a>
                </div>

            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="py-6 text-center text-xs text-on-surface-variant border-t border-outline-variant/20 mt-auto">
        <p>© <?php echo date('Y'); ?> SeDaP Healthcare Portal • All rights reserved.</p>
    </footer>

</div>

<!-- Dark Mode Demo Handler -->
<script>
    const darkModeToggle = document.getElementById('darkModeToggle');
    const htmlElement = document.documentElement;

    darkModeToggle.addEventListener('change', () => {
        if (darkModeToggle.checked) {
            htmlElement.classList.add('dark');
        } else {
            htmlElement.classList.remove('dark');
        }
    });
</script>

</body>
</html>