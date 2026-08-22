<?php
session_start();

// Protect the page: If no session exists, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html class="h-full" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Settings - Sedap</title>
    
    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class", 
            theme: {
                extend: {
                    colors: {
                        background: "#fff8f2",
                        primary: "#005359",
                        "primary-container": "#136d74",
                        "on-primary": "#ffffff",
                        secondary: "#835500",
                        "surface-container": "#fcecd4",
                        "surface-container-low": "#fff2e0",
                        "on-surface": "#221a0c",
                        "on-surface-variant": "#3f494a",
                        "outline-variant": "#bec8c9",
                        error: "#ba1a1a",
                        "error-container": "#ffdad6",
                        "on-error-container": "#93000a",
                    }, 
                    fontFamily: {
                        body: ["Inter", "sans-serif"],
                        headline: ["Plus Jakarta Sans", "sans-serif"]
                    }
                }
            }
        };
    </script>
    
    <style>
        .mesh-bg {
            background-color: #fff8f2;
            background-image: 
                radial-gradient(at 10% 20%, hsla(184, 72%, 26%, 0.15) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(33, 100%, 80%, 0.2) 0px, transparent 50%);
            background-attachment: fixed;
        }
        
        /* Hide Scrollbar */
        body::-webkit-scrollbar { display: none; }
        body { -ms-overflow-style: none; scrollbar-width: none; }

        /* Custom Toggle Switch styling */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #005359;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #005359;
        }
    </style>
</head>
<body class="h-full mesh-bg text-on-surface font-body p-4 md:p-8 flex items-center justify-center antialiased relative">

<!-- Top Left Back Button -->
<a href="dashboard.php" class="absolute top-6 left-6 md:top-8 md:left-8 bg-surface-container hover:bg-[#e7d8c1] text-primary p-3 rounded-full shadow-sm border border-[#e7d8c1] flex items-center justify-center transition-all group active:scale-95 z-50">
    <span class="material-symbols-outlined group-hover:-translate-x-1 transition-transform duration-300">arrow_back</span>
</a>

<main class="w-full max-w-[500px]">
    
    <!-- Settings Card -->
    <div class="bg-surface-container-low p-6 md:p-10 rounded-[32px] shadow-sm border border-[#e7d8c1]">
        
        <!-- Header -->
        <div class="mb-8 text-center">
            <h1 class="font-headline text-3xl font-bold text-primary">Settings</h1>
        </div>

        <div class="space-y-8">
            
            <!-- APPEARANCE SECTION -->
            <section>
                <h2 class="text-sm font-bold text-primary uppercase tracking-wider mb-4">Appearance</h2>
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-on-surface-variant">dark_mode</span>
                        <span class="font-medium">Dark Mode</span>
                    </div>
                    <!-- Tailwind Toggle -->
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" id="darkModeToggle">
                        <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>
            </section>

            <hr class="border-outline-variant/40">

            <!-- NOTIFICATIONS SECTION -->
            <section>
                <h2 class="text-sm font-bold text-primary uppercase tracking-wider mb-4">Notifications</h2>
                
                <div class="flex flex-col gap-2">
                    <!-- Live Chat -->
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">chat</span>
                            <span class="font-medium">Live Chat Notifications</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" value="" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <!-- Reminders -->
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant">alarm</span>
                            <span class="font-medium">Reminders</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" value="" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-outline-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                </div>
            </section>

            <hr class="border-outline-variant/40">

            <!-- ACCOUNT & SECURITY SECTION -->
            <section>
                <h2 class="text-sm font-bold text-primary uppercase tracking-wider mb-4">Account & Security</h2>
                
                <div class="flex flex-col">
                    <!-- Reset Password -->
                    <a href="#" class="flex items-center justify-between py-4 group hover:bg-surface-container rounded-xl px-2 -mx-2 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">lock_reset</span>
                            <span class="font-medium group-hover:text-primary transition-colors">Reset Password</span>
                        </div>
                        <span class="material-symbols-outlined text-outline-variant group-hover:translate-x-1 transition-transform">chevron_right</span>
                    </a>

                    <!-- Dasar Privasi & Keselamatan -->
                    <a href="#" class="flex items-center justify-between py-4 group hover:bg-surface-container rounded-xl px-2 -mx-2 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors">policy</span>
                            <span class="font-medium group-hover:text-primary transition-colors">Dasar Privasi & Keselamatan</span>
                        </div>
                        <span class="material-symbols-outlined text-outline-variant group-hover:translate-x-1 transition-transform">chevron_right</span>
                    </a>
                </div>
            </section>

        </div>

        <!-- Logout Button -->
        <div class="mt-10">
            <a href="../auth/logout.php" class="w-full h-14 bg-error hover:bg-on-error-container text-white font-semibold rounded-[32px] transition-colors shadow-sm flex justify-center items-center gap-2 group active:scale-[0.98]">
                <span class="material-symbols-outlined text-[22px] group-hover:-translate-x-1 transition-transform">logout</span>
                <span>Sign Out</span>
            </a>
        </div>

    </div>
    
    <!-- Minimal Footer -->
    <div class="text-center mt-6 text-on-surface-variant text-sm opacity-70">
        © 2024 Sedap Food-Tech
    </div>
</main>

<!-- Simple Dark Mode Script (For UI demonstration) -->
<script>
    const darkModeToggle = document.getElementById('darkModeToggle');
    const htmlElement = document.documentElement;

    darkModeToggle.addEventListener('change', () => {
        if (darkModeToggle.checked) {
            htmlElement.classList.add('dark');
            // Normally you would save this preference to the database via AJAX here
        } else {
            htmlElement.classList.remove('dark');
        }
    });
</script>

</body>
</html>