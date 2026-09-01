<?php
session_start();

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SeDaP - Signed Out</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,400;8..144,500;8..144,600;8..144,700&amp;display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet">
    <script src="js/tailwind-config.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>

<body
    class="bg-background h-screen w-screen overflow-hidden antialiased selection:bg-primary-container selection:text-on-primary-container font-sans">
    <!-- Responsive Material 3 12-Column Grid Layout Container -->
    <div class="w-full h-full grid grid-cols-1 md:grid-cols-12 relative bg-background overflow-hidden">
        <!-- Left Split Area (6 of 12 Columns - 50%) -->
        <div
            class="hidden md:flex md:col-span-6 h-full relative overflow-hidden bg-gradient-to-br from-primary-fixed/60 via-secondary-fixed/30 to-tertiary-fixed/40 items-center justify-center">
            <div class="absolute inset-0 flex items-center justify-center">
                <img alt="Community Health Connect Illustration" class="w-full h-full object-cover"
                    src="screen.png">
            </div>
            <!-- Ambient Glow Overlays -->
            <div
                class="absolute -top-32 -left-32 w-[800px] h-[800px] bg-primary/20 rounded-full blur-[120px] mix-blend-multiply opacity-70">
            </div>
            <div
                class="absolute bottom-0 right-0 w-[600px] h-[600px] bg-secondary/15 rounded-full blur-[100px] mix-blend-multiply opacity-60">
            </div>
            <div
                class="absolute top-1/2 left-1/4 w-[400px] h-[400px] bg-tertiary/20 rounded-full blur-[80px] mix-blend-multiply opacity-50">
            </div>

            <!-- Floating Brand Card Badge -->
            <div class="absolute bottom-8 left-8 right-8 z-20 bg-white/70 dark:bg-slate-900/60 backdrop-blur-md rounded-2xl p-4 border border-white/40 shadow-lg flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary text-on-primary flex items-center justify-center shrink-0 shadow-sm">
                    <span class="material-symbols-outlined filled !text-[22px]">verified_user</span>
                </div>
                <div>
                    <h3 class="text-xs font-semibold text-on-surface">Secure Sign Out</h3>
                    <p class="text-[11px] text-on-surface-variant">Your credentials and health session have been securely closed.</p>
                </div>
            </div>
        </div>

        <!-- Right Split Area (6 of 12 Columns - 50%) -->
        <div class="col-span-1 md:col-span-6 h-full flex items-center justify-center relative bg-surface p-4 sm:p-6 overflow-y-auto">
            <!-- Elevated Expressive Card (Locked 420x580 M3 Container matching login) -->
            <div
                class="w-full max-w-[420px] min-h-[580px] sm:h-[580px] bg-surface-container-lowest rounded-3xl sm:rounded-tl-[72px] sm:rounded-br-[72px] shadow-[0_16px_48px_-12px_rgba(26,28,30,0.08)] p-6 sm:p-8 flex flex-col justify-between relative overflow-hidden border border-surface-variant/40 my-auto">
                
                <!-- Subtle Background Accent -->
                <div
                    class="absolute -top-24 -right-24 w-64 h-64 bg-primary/10 rounded-full blur-3xl pointer-events-none">
                </div>

                <!-- Pinned Header Slot -->
                <div class="flex flex-col items-center text-center gap-2 relative z-10">
                    <div class="relative">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center shadow-md overflow-hidden border-2 border-primary/20 bg-white">
                            <img src="sedap.jpg" alt="SEDAP logo" class="w-full h-full object-cover">
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow">
                            <span class="material-symbols-outlined !text-[16px]">check</span>
                        </div>
                    </div>
                    
                    <h1 class="text-on-surface text-xl sm:text-2xl font-bold tracking-tight mt-1">Signed Out</h1>
                    <p class="text-on-surface-variant text-xs sm:text-sm max-w-xs">You have safely signed out of your SeDaP account.</p>
                </div>

                <!-- Center Content Slot -->
                <div class="flex flex-col gap-4 relative z-10 w-full my-auto text-center">
                    <!-- Security Card Banner -->
                    <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl p-4 text-left flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 mt-0.5">
                            <span class="material-symbols-outlined !text-[18px]">lock</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-xs font-bold text-on-surface">Session Closed</h4>
                            <p class="text-[11px] text-on-surface-variant leading-relaxed mt-0.5">
                                For your security, close your browser window if you are using a shared or public computer.
                            </p>
                        </div>
                    </div>

                    <!-- Auto Redirect Countdown indicator -->
                    <div class="flex items-center justify-center gap-2 text-xs text-on-surface-variant bg-surface-container/60 py-2 px-3 rounded-full border border-outline-variant/20">
                        <span class="material-symbols-outlined !text-[16px] animate-spin text-primary">progress_activity</span>
                        <span>Redirecting to Sign In in <strong id="countdown" class="text-primary font-bold">5</strong> seconds</span>
                    </div>
                </div>

                <!-- Actions Slot -->
                <div class="flex flex-col gap-2.5 relative z-10 w-full">
                    <a href="login.php"
                        class="w-full h-11 bg-primary hover:bg-primary/90 active:scale-[0.99] text-on-primary text-sm font-semibold rounded-[32px] shadow-sm flex items-center justify-center gap-2 transition-all">
                        <span class="material-symbols-outlined !text-[18px]">login</span>
                        <span>Sign In Again</span>
                    </a>

                    <a href="register.php"
                        class="w-full h-11 bg-surface-container hover:bg-surface-container-high active:scale-[0.99] text-on-surface text-sm font-semibold rounded-[32px] border border-outline-variant/40 flex items-center justify-center gap-2 transition-all">
                        <span class="material-symbols-outlined !text-[18px]">person_add</span>
                        <span>Create New Account</span>
                    </a>

                    <div class="text-center mt-1">
                        <a href="login.php" class="text-xs text-primary font-semibold hover:underline">
                            Return to Login immediately
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Auto Countdown Script -->
    <script>
        let timeLeft = 5;
        const countdownEl = document.getElementById('countdown');
        const timer = setInterval(() => {
            timeLeft--;
            if (countdownEl) countdownEl.innerText = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timer);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>
</body>

</html>
