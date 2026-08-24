<?php
/**
 * dashboard.php - Triage Dashboard
 * SeDaP Clinical Dashboard - Doctor Portal
 *
 * Session-protected page. Redirects to login if unauthenticated.
 */

session_start();
require_once '../config/db.php';

// Redirect to login if not authenticated or not doctor
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'doctor') {
    header('Location: ../auth/login.php');
    exit;
}

// Fetch logged-in doctor info from session
$doctorName = htmlspecialchars($_SESSION['user_name'] ?? 'Dr. Sarah Jenkins');
$doctorRole = htmlspecialchars($_SESSION['user_role'] ?? 'Lead Triage');
$doctorAvatar = htmlspecialchars($_SESSION['user_avatar'] ?? 'https://lh3.googleusercontent.com/aida-public/AB6AXuCs8O1A02_zPLFwh2PHOPYC8yh5Bn8h5t2KbIQQ0XzT99ztbc8Z5Rcq4zYTlJ-Va4P4MJ-6oRHyRxAKt7Fhr8YGJ-vnJS1gjCGaXhbAtJMHU1V4_Mrm229ro3UA6653Dqlcke1ezeaP-0B39Usgl8QfFPl-qAMNVjaiOatDmF60azxj3U0hSwFi_CLJVDM2e1_WFI_1JCgQWt84WV_AxUOfMm4VbjuImeVlU4RVu6V5CcCOZ3-iQPXV');
?>
<!DOCTYPE html>

<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>SeDaP - Triage Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:wght@400;500;700;900&display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <!-- Page Stylesheet -->
    <link rel="stylesheet" href="css/dashboard.css" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "surface": "#f7f9fb",
                        "surface-container": "#eceef0",
                        "on-error": "#ffffff",
                        "surface-container-low": "#f2f4f6",
                        "on-tertiary": "#ffffff",
                        "on-secondary-container": "#395c80",
                        "outline-variant": "#c2c6d5",
                        "surface-container-high": "#e6e8ea",
                        "error": "#ba1a1a",
                        "secondary-container": "#b1d5ff",
                        "inverse-primary": "#adc6ff",
                        "surface-variant": "#e0e3e5",
                        "primary-fixed": "#d8e2ff",
                        "on-primary-fixed-variant": "#004494",
                        "on-tertiary-container": "#f8fdff",
                        "tertiary-fixed-dim": "#7bd3e5",
                        "tertiary-container": "#148090",
                        "secondary-fixed-dim": "#a6caf3",
                        "on-surface": "#191c1e",
                        "on-tertiary-fixed": "#001f25",
                        "background": "#f7f9fb",
                        "on-primary-fixed": "#001a41",
                        "on-surface-variant": "#424753",
                        "error-container": "#ffdad6",
                        "surface-tint": "#005ac1",
                        "primary-container": "#2771df",
                        "on-primary": "#ffffff",
                        "on-error-container": "#93000a",
                        "surface-bright": "#f7f9fb",
                        "secondary": "#3d6185",
                        "on-primary-container": "#fefcff",
                        "on-tertiary-fixed-variant": "#004e59",
                        "primary-fixed-dim": "#adc6ff",
                        "surface-dim": "#d8dadc",
                        "on-secondary-fixed": "#001d35",
                        "surface-container-highest": "#e0e3e5",
                        "outline": "#727785",
                        "on-secondary-fixed-variant": "#24496c",
                        "on-secondary": "#ffffff",
                        "inverse-on-surface": "#eff1f3",
                        "tertiary": "#006673",
                        "inverse-surface": "#2d3133",
                        "tertiary-fixed": "#a1efff",
                        "primary": "#0058bd",
                        "secondary-fixed": "#d0e4ff",
                        "on-background": "#191c1e",
                        "surface-container-lowest": "#ffffff"
                    },
                    "borderRadius": {
                        "DEFAULT": "1rem",
                        "lg": "2rem",
                        "xl": "3rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "stack-lg": "24px",
                        "margin-desktop": "32px",
                        "stack-sm": "8px",
                        "margin-mobile": "16px",
                        "stack-md": "16px",
                        "gutter": "16px",
                        "margin-tablet": "24px"
                    },
                    "fontFamily": {
                        "body-lg": ["Roboto Flex"],
                        "label-lg": ["Roboto Flex"],
                        "display-md": ["Roboto Flex"],
                        "headline-md": ["Roboto Flex"],
                        "label-sm": ["Roboto Flex"],
                        "headline-lg-mobile": ["Roboto Flex"],
                        "title-md": ["Roboto Flex"],
                        "display-lg": ["Roboto Flex"],
                        "body-md": ["Roboto Flex"],
                        "headline-lg": ["Roboto Flex"],
                        "title-lg": ["Roboto Flex"]
                    },
                    "fontSize": {
                        "body-lg": ["16px", { "lineHeight": "24px", "letterSpacing": "0.5px", "fontWeight": "400" }],
                        "label-lg": ["14px", { "lineHeight": "20px", "letterSpacing": "0.1px", "fontWeight": "500" }],
                        "display-md": ["45px", { "lineHeight": "52px", "fontWeight": "400" }],
                        "headline-md": ["28px", { "lineHeight": "36px", "fontWeight": "400" }],
                        "label-sm": ["11px", { "lineHeight": "16px", "letterSpacing": "0.5px", "fontWeight": "500" }],
                        "headline-lg-mobile": ["28px", { "lineHeight": "36px", "fontWeight": "400" }],
                        "title-md": ["16px", { "lineHeight": "24px", "letterSpacing": "0.15px", "fontWeight": "500" }],
                        "display-lg": ["57px", { "lineHeight": "64px", "letterSpacing": "-0.25px", "fontWeight": "400" }],
                        "body-md": ["14px", { "lineHeight": "20px", "letterSpacing": "0.25px", "fontWeight": "400" }],
                        "headline-lg": ["32px", { "lineHeight": "40px", "fontWeight": "400" }],
                        "title-lg": ["22px", { "lineHeight": "28px", "fontWeight": "500" }]
                    }
                },
            },
        }
    </script>
</head>

<body class="bg-background text-on-background min-h-screen flex overflow-hidden font-sans antialiased">
    <!-- SideNavBar -->
    <nav
        class="bg-surface-container-low dark:bg-surface-dim h-screen w-[280px] sticky left-0 top-0 rounded-r-lg shadow-sm flex flex-col h-full py-margin-desktop px-stack-md flex-shrink-0 z-50">
        <div class="flex items-center gap-stack-sm mb-stack-lg px-stack-md">
            <span class="material-symbols-outlined text-primary text-[32px]">health_and_safety</span>
            <div>
                <h1 class="font-headline-lg text-headline-lg font-bold text-primary dark:text-primary-fixed">SeDaP</h1>
                <p class="font-label-sm text-label-sm text-on-surface-variant">Clinical Dashboard</p>
            </div>
        </div>
        <ul class="flex flex-col gap-stack-sm flex-grow">
            <!-- Dashboard -->
            <li>
                <a class="flex items-center gap-stack-md text-on-surface-variant hover:bg-surface-container-highest rounded-full px-margin-tablet py-stack-md hover:bg-surface-container-highest transition-colors duration-200 active:scale-95 transition-transform"
                    href="#">
                    <span class="material-symbols-outlined">dashboard</span>
                    <span class="font-title-md text-title-md">Dashboard</span>
                </a>
            </li>
            <!-- Triage (ACTIVE) -->
            <li>
                <a class="flex items-center gap-stack-md bg-secondary-container text-on-secondary-container rounded-full px-margin-tablet py-stack-md hover:bg-surface-container-highest transition-colors duration-200 active:scale-95 transition-transform"
                    href="dashboard.php">
                    <span class="material-symbols-outlined"
                        style="font-variation-settings: 'FILL' 1;">medical_services</span>
                    <span class="font-title-md text-title-md font-bold">Triage</span>
                </a>
            </li>
            <!-- Chat -->
            <li>
                <a class="flex items-center gap-stack-md text-on-surface-variant hover:bg-surface-container-highest rounded-full px-margin-tablet py-stack-md hover:bg-surface-container-highest transition-colors duration-200 active:scale-95 transition-transform relative"
                    href="livechat.php">
                    <span class="material-symbols-outlined">chat</span>
                    <span class="font-title-md text-title-md flex-grow">Chat</span>
                    <span class="bg-error text-on-error rounded-full px-2 py-0.5 text-label-sm font-label-sm">3</span>
                </a>
            </li>
            <!-- Patients & Families -->
            <li>
                <a class="flex items-center gap-stack-md text-on-surface-variant hover:bg-surface-container-highest rounded-full px-margin-tablet py-stack-md hover:bg-surface-container-highest transition-colors duration-200 active:scale-95 transition-transform"
                    href="patientfamily.php">
                    <span class="material-symbols-outlined">group</span>
                    <span class="font-title-md text-title-md">Patients &amp; Families</span>
                </a>
            </li>
            <!-- Health Module -->
            <li>
                <a class="flex items-center gap-stack-md text-on-surface-variant hover:bg-surface-container-highest rounded-full px-margin-tablet py-stack-md hover:bg-surface-container-highest transition-colors duration-200 active:scale-95 transition-transform"
                    href="#">
                    <span class="material-symbols-outlined">monitor_heart</span>
                    <span class="font-title-md text-title-md">Health Module</span>
                </a>
            </li>
            <!-- Settings -->
            <li>
                <a class="flex items-center gap-stack-md text-on-surface-variant hover:bg-surface-container-highest rounded-full px-margin-tablet py-stack-md hover:bg-surface-container-highest transition-colors duration-200 active:scale-95 transition-transform mt-auto"
                    href="#">
                    <span class="material-symbols-outlined">settings</span>
                    <span class="font-title-md text-title-md">Settings</span>
                </a>
            </li>
        </ul>
        <div class="mt-stack-lg pt-stack-lg border-t border-surface-variant flex items-center gap-stack-md px-stack-md">
            <img class="w-10 h-10 rounded-full object-cover border-2 border-surface"
                alt="Doctor profile photo"
                src="<?php echo $doctorAvatar; ?>" />
            <div class="flex flex-col">
                <span class="font-label-lg text-label-lg text-on-surface"><?php echo $doctorName; ?></span>
                <span class="font-label-sm text-label-sm text-on-surface-variant"><?php echo $doctorRole; ?></span>
            </div>
        </div>
    </nav>
    <!-- Main Content Area -->
    <main class="flex-grow flex flex-col h-screen overflow-hidden">
        <!-- TopAppBar (Minimal for Dashboard context) -->
        <header
            class="bg-surface/95 dark:bg-surface-dim/95 w-full sticky top-0 z-40 bg-surface/50 backdrop-blur-md flex justify-between items-center px-margin-desktop h-20 w-full flex-shrink-0">
            <div class="flex items-center gap-stack-md">
                <h2 class="font-title-lg text-title-lg font-medium text-on-surface">Triage Operations Overview</h2>
            </div>
            <div class="flex items-center gap-stack-lg">
                <div
                    class="relative focus-within:ring-2 focus-within:ring-primary rounded-full bg-surface-container-low flex items-center px-4 py-2 w-64 transition-all">
                    <span class="material-symbols-outlined text-on-surface-variant mr-2">search</span>
                    <input
                        class="bg-transparent border-none focus:ring-0 text-body-md font-body-md w-full text-on-surface placeholder-on-surface-variant"
                        placeholder="Search patients, IDs..." type="text" />
                </div>
                <button class="text-on-surface-variant hover:text-primary transition-colors duration-200 relative">
                    <span class="material-symbols-outlined text-[28px]">notifications</span>
                    <span class="absolute top-0 right-0 w-3 h-3 bg-error rounded-full border-2 border-surface"></span>
                </button>
                <button class="text-on-surface-variant hover:text-primary transition-colors duration-200">
                    <span class="material-symbols-outlined text-[28px]">help_outline</span>
                </button>
            </div>
        </header>
        <!-- Content Split Layout -->
        <div class="flex flex-grow overflow-hidden p-margin-desktop gap-margin-desktop">
            <!-- LEFT COLUMN (60%): Triage & Urgent Actions -->
            <div class="w-[60%] flex flex-col gap-margin-desktop h-full overflow-y-auto pr-2 custom-scrollbar">
                <!-- Live Triage Counter Cards -->
                <div class="grid grid-cols-3 gap-gutter">
                    <!-- Critical -->
                    <div
                        class="bg-error-container rounded-xl p-stack-lg flex flex-col justify-between shadow-sm border border-error/20 relative overflow-hidden group">
                        <div
                            class="absolute -right-4 -top-4 w-24 h-24 bg-error/10 rounded-full blur-xl group-hover:bg-error/20 transition-all duration-500">
                        </div>
                        <div class="flex justify-between items-start mb-stack-md relative z-10">
                            <span
                                class="font-label-lg text-label-lg text-on-error-container uppercase tracking-wider font-semibold">Critical</span>
                            <span class="material-symbols-outlined text-error"
                                style="font-variation-settings: 'FILL' 1;">emergency</span>
                        </div>
                        <div class="relative z-10">
                            <span
                                class="font-display-lg text-display-lg text-on-error-container font-light leading-none">04</span>
                            <p class="font-body-md text-body-md text-on-error-container/80 mt-1">Requires immediate
                                attn.</p>
                        </div>
                    </div>
                    <!-- Urgent -->
                    <div
                        class="bg-secondary-container rounded-xl p-stack-lg flex flex-col justify-between shadow-sm border border-secondary/20 relative overflow-hidden group">
                        <div
                            class="absolute -right-4 -top-4 w-24 h-24 bg-secondary/10 rounded-full blur-xl group-hover:bg-secondary/20 transition-all duration-500">
                        </div>
                        <div class="flex justify-between items-start mb-stack-md relative z-10">
                            <span
                                class="font-label-lg text-label-lg text-on-secondary-container uppercase tracking-wider font-semibold">Urgent</span>
                            <span class="material-symbols-outlined text-secondary"
                                style="font-variation-settings: 'FILL' 1;">warning</span>
                        </div>
                        <div class="relative z-10">
                            <span
                                class="font-display-lg text-display-lg text-on-secondary-container font-light leading-none">12</span>
                            <p class="font-body-md text-body-md text-on-secondary-container/80 mt-1">Wait time &lt; 30m
                            </p>
                        </div>
                    </div>
                    <!-- Standard -->
                    <div
                        class="bg-surface-container-lowest rounded-xl p-stack-lg flex flex-col justify-between shadow-sm border border-outline-variant relative overflow-hidden group">
                        <div
                            class="absolute -right-4 -top-4 w-24 h-24 bg-primary/5 rounded-full blur-xl group-hover:bg-primary/10 transition-all duration-500">
                        </div>
                        <div class="flex justify-between items-start mb-stack-md relative z-10">
                            <span
                                class="font-label-lg text-label-lg text-on-surface-variant uppercase tracking-wider font-semibold">Standard</span>
                            <span class="material-symbols-outlined text-on-surface-variant"
                                style="font-variation-settings: 'FILL' 1;">schedule</span>
                        </div>
                        <div class="relative z-10">
                            <span
                                class="font-display-lg text-display-lg text-on-surface font-light leading-none">28</span>
                            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Wait time ~ 45m</p>
                        </div>
                    </div>
                </div>
                <!-- Urgent Action Queue -->
                <div
                    class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/50 flex-grow flex flex-col overflow-hidden">
                    <div
                        class="p-stack-lg border-b border-surface-variant flex justify-between items-center bg-surface-container-lowest/50 backdrop-blur-sm sticky top-0 z-10">
                        <h3 class="font-title-lg text-title-lg font-medium text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">view_list</span>
                            Urgent Action Queue
                        </h3>
                        <button
                            class="text-primary hover:bg-primary/10 px-4 py-2 rounded-full font-label-lg text-label-lg transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">filter_list</span>
                            Filter
                        </button>
                    </div>
                    <div class="flex-grow overflow-y-auto p-stack-md flex flex-col gap-stack-sm">
                        <!-- Queue Item 1 (Critical) -->
                        <div
                            class="bg-surface border-l-4 border-error rounded-r-lg p-stack-md flex items-center justify-between hover:bg-surface-container-low transition-colors cursor-pointer group">
                            <div class="flex items-center gap-stack-md">
                                <div
                                    class="w-12 h-12 bg-error-container rounded-full flex items-center justify-center flex-shrink-0">
                                    <span
                                        class="font-title-md text-title-md text-on-error-container font-bold">EJ</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-title-md text-title-md font-medium text-on-surface">Elias
                                            Johnson</h4>
                                        <span
                                            class="bg-error text-on-error px-2 py-0.5 rounded-full text-[10px] uppercase font-bold tracking-wider">Critical</span>
                                    </div>
                                    <p
                                        class="font-body-md text-body-md text-on-surface-variant mt-0.5 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">vital_signs</span>
                                        Chest pain, shortness of breath (3 mins waiting)
                                    </p>
                                </div>
                            </div>
                            <button
                                class="bg-error text-on-error px-4 py-2 rounded-full font-label-lg text-label-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1 shadow-sm hover:bg-error/90">
                                Action <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                            </button>
                        </div>
                        <!-- Queue Item 2 (Urgent) -->
                        <div
                            class="bg-surface border-l-4 border-secondary rounded-r-lg p-stack-md flex items-center justify-between hover:bg-surface-container-low transition-colors cursor-pointer group">
                            <div class="flex items-center gap-stack-md">
                                <img class="w-12 h-12 rounded-full object-cover flex-shrink-0 border border-surface-variant"
                                    alt="Patient Maria Garcia"
                                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCWKdSBP_r5WNXygxqPRgpiLoKkkaC-OmeqIV48CuLNWBlmdj6WiLqk_J9Q4bKemlfKCXQLKLdgKx5Xzm6QylcpLT7en1rd5xkxeChmf369sma28XwY_lTWwkXE_8tshNCAuJpWktoJOKwCSsrJyL2b2zLnTrHlUwUYfFP41Dt4NkWX1u_V-bWI7Ip02g-PopfHi8qmp4ajzkAbLmYyf1UD_ilpBc9nSB-E1P3RlgxJXc_TeSzZayR7" />
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-title-md text-title-md font-medium text-on-surface">Maria Garcia
                                        </h4>
                                        <span
                                            class="bg-secondary text-on-secondary px-2 py-0.5 rounded-full text-[10px] uppercase font-bold tracking-wider">Urgent</span>
                                    </div>
                                    <p
                                        class="font-body-md text-body-md text-on-surface-variant mt-0.5 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">local_hospital</span>
                                        High fever, laceration (14 mins waiting)
                                    </p>
                                </div>
                            </div>
                            <button
                                class="bg-primary text-on-primary px-4 py-2 rounded-full font-label-lg text-label-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1 shadow-sm hover:bg-primary/90">
                                Action <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                            </button>
                        </div>
                        <!-- Queue Item 3 (Urgent) -->
                        <div
                            class="bg-surface border-l-4 border-secondary rounded-r-lg p-stack-md flex items-center justify-between hover:bg-surface-container-low transition-colors cursor-pointer group">
                            <div class="flex items-center gap-stack-md">
                                <div
                                    class="w-12 h-12 bg-secondary-container rounded-full flex items-center justify-center flex-shrink-0">
                                    <span
                                        class="font-title-md text-title-md text-on-secondary-container font-bold">DT</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-title-md text-title-md font-medium text-on-surface">David
                                            Thompson</h4>
                                        <span
                                            class="bg-secondary text-on-secondary px-2 py-0.5 rounded-full text-[10px] uppercase font-bold tracking-wider">Urgent</span>
                                    </div>
                                    <p
                                        class="font-body-md text-body-md text-on-surface-variant mt-0.5 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">allergy</span>
                                        Severe allergic reaction (18 mins waiting)
                                    </p>
                                </div>
                            </div>
                            <button
                                class="bg-primary text-on-primary px-4 py-2 rounded-full font-label-lg text-label-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1 shadow-sm hover:bg-primary/90">
                                Action <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- RIGHT COLUMN (40%): Admin & Comms -->
            <div class="w-[40%] flex flex-col gap-margin-desktop h-full overflow-y-auto pr-2 custom-scrollbar">
                <!-- Quick Actions Panel -->
                <div
                    class="bg-primary-container rounded-xl p-stack-lg shadow-sm relative overflow-hidden flex-shrink-0">
                    <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 rounded-full blur-2xl -mr-10 -mt-10"></div>
                    <h3
                        class="font-title-lg text-title-lg font-medium text-on-primary-container mb-stack-md relative z-10 flex items-center gap-2">
                        <span class="material-symbols-outlined">bolt</span>
                        Administrative Actions
                    </h3>
                    <div class="grid grid-cols-2 gap-stack-sm relative z-10">
                        <button
                            class="bg-surface-container-lowest/80 backdrop-blur-sm hover:bg-white text-on-surface p-stack-md rounded-lg flex flex-col items-center justify-center gap-2 transition-all shadow-sm border border-white/20 hover:-translate-y-0.5">
                            <span class="material-symbols-outlined text-primary text-[28px]"
                                style="font-variation-settings: 'FILL' 1;">person_add</span>
                            <span class="font-label-lg text-label-lg">Register Walk-in</span>
                        </button>
                        <button
                            class="bg-surface-container-lowest/80 backdrop-blur-sm hover:bg-white text-on-surface p-stack-md rounded-lg flex flex-col items-center justify-center gap-2 transition-all shadow-sm border border-white/20 hover:-translate-y-0.5">
                            <span class="material-symbols-outlined text-secondary text-[28px]"
                                style="font-variation-settings: 'FILL' 1;">bed</span>
                            <span class="font-label-lg text-label-lg">Bed Assignment</span>
                        </button>
                        <button
                            class="bg-surface-container-lowest/80 backdrop-blur-sm hover:bg-white text-on-surface p-stack-md rounded-lg flex flex-col items-center justify-center gap-2 transition-all shadow-sm border border-white/20 hover:-translate-y-0.5 col-span-2">
                            <span
                                class="material-symbols-outlined text-tertiary text-[24px]">assignment_turned_in</span>
                            <span class="font-label-lg text-label-lg">Shift Handover Report</span>
                        </button>
                    </div>
                </div>
                <!-- Unread Communications -->
                <div
                    class="bg-surface-container-lowest rounded-xl shadow-sm border border-outline-variant/50 flex-grow flex flex-col overflow-hidden">
                    <div
                        class="p-stack-lg border-b border-surface-variant flex justify-between items-center bg-surface/50 backdrop-blur-sm sticky top-0 z-10">
                        <h3 class="font-title-lg text-title-lg font-medium text-on-surface flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary">forum</span>
                            Team Comms
                        </h3>
                        <span
                            class="bg-error text-on-error px-2.5 py-0.5 rounded-full text-label-sm font-label-sm font-bold">3
                            Unread</span>
                    </div>
                    <div class="flex-grow overflow-y-auto p-stack-md flex flex-col gap-stack-sm">
                        <!-- Comm Item 1 (Unread) -->
                        <div
                            class="bg-primary/5 rounded-lg p-stack-md border border-primary/20 cursor-pointer hover:bg-primary/10 transition-colors">
                            <div class="flex justify-between items-start mb-1">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-primary"></div>
                                    <span class="font-label-lg text-label-lg text-on-surface font-semibold">Dr. Alan
                                        Wright (ER)</span>
                                </div>
                                <span class="font-label-sm text-label-sm text-on-surface-variant">2m ago</span>
                            </div>
                            <p class="font-body-md text-body-md text-on-surface-variant pl-4 line-clamp-2">"Incoming
                                trauma prep: ETA 5 mins. Please ensure Bay 3 is cleared and ready for..."</p>
                        </div>
                        <!-- Comm Item 2 (Unread) -->
                        <div
                            class="bg-primary/5 rounded-lg p-stack-md border border-primary/20 cursor-pointer hover:bg-primary/10 transition-colors">
                            <div class="flex justify-between items-start mb-1">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-primary"></div>
                                    <span class="font-label-lg text-label-lg text-on-surface font-semibold">Lab
                                        Services</span>
                                </div>
                                <span class="font-label-sm text-label-sm text-on-surface-variant">15m ago</span>
                            </div>
                            <p class="font-body-md text-body-md text-on-surface-variant pl-4 line-clamp-2">"Stat results
                                for patient Elias Johnson are ready in the system. Troponin elevated."</p>
                        </div>
                        <!-- Comm Item 3 (Read) -->
                        <div
                            class="bg-surface rounded-lg p-stack-md border border-surface-variant cursor-pointer hover:bg-surface-container-low transition-colors opacity-75">
                            <div class="flex justify-between items-start mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-label-lg text-label-lg text-on-surface">Admin Desk</span>
                                </div>
                                <span class="font-label-sm text-label-sm text-on-surface-variant">1h ago</span>
                            </div>
                            <p class="font-body-md text-body-md text-on-surface-variant line-clamp-1">"System
                                maintenance scheduled for 0200 hrs."</p>
                        </div>
                    </div>
                    <div class="p-stack-md border-t border-surface-variant bg-surface-container-lowest">
                        <button
                            class="w-full py-2 text-primary font-label-lg text-label-lg hover:bg-primary/5 rounded-lg transition-colors">View
                            All Messages</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <!-- Page Scripts -->
    <script src="js/dashboard.js"></script>
</body>

</html>
