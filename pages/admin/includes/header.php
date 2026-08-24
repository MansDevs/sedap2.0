<?php
require_once __DIR__ . '/nav_items.php';
require_once __DIR__ . '/access.php';
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Admin'); ?> - SeDaP</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-secondary-fixed": "#001d35",
                        "inverse-surface": "#2d3133",
                        "surface-dim": "#d8dadc",
                        "tertiary-fixed": "#a1efff",
                        "on-surface-variant": "#5f6368",
                        "surface-tint": "#005ac1",
                        "surface-container": "#eceef0",
                        "surface-variant": "#e0e3e5",
                        "on-primary-container": "#fefcff",
                        "surface": "#f8fafd",
                        "surface-bright": "#f7f9fb",
                        "on-primary-fixed-variant": "#004494",
                        "on-primary-fixed": "#001a41",
                        "inverse-primary": "#adc6ff",
                        "on-secondary": "#ffffff",
                        "secondary-fixed": "#d0e4ff",
                        "error-container": "#ffdad6",
                        "primary-container": "#1a73e8",
                        "secondary": "#3d6185",
                        "tertiary": "#007a87",
                        "error": "#ba1a1a",
                        "on-tertiary-container": "#f8fdff",
                        "surface-container-high": "#e6e8ea",
                        "primary": "#1a73e8",
                        "on-primary": "#ffffff",
                        "on-background": "#191c1e",
                        "surface-container-low": "#f1f4f9",
                        "secondary-container": "#d3e3fd",
                        "surface-container-highest": "#e0e3e5",
                        "primary-fixed": "#d8e2ff",
                        "surface-container-lowest": "#ffffff",
                        "on-surface": "#1f1f1f",
                        "on-tertiary": "#ffffff",
                        "secondary-fixed-dim": "#a6caf3",
                        "background": "#f8fafd",
                        "inverse-on-surface": "#eff1f3",
                        "on-secondary-container": "#041e49",
                        "on-tertiary-fixed": "#001f25",
                        "on-error": "#ffffff",
                        "primary-fixed-dim": "#adc6ff",
                        "on-tertiary-fixed-variant": "#004e59",
                        "tertiary-fixed-dim": "#7bd3e5",
                        "on-error-container": "#93000a",
                        "outline": "#727785",
                        "on-secondary-fixed-variant": "#24496c",
                        "outline-variant": "#e1e3e1",
                        "tertiary-container": "#007a87"
                    },
                    fontFamily: { display: ["Roboto Flex", "sans-serif"], body: ["Roboto Flex", "sans-serif"] }
                }
            }
        };
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 20; font-size: 20px; }
        .icon-fill { font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 20; }
    </style>
</head>
<body class="bg-surface text-on-surface font-body antialiased selection:bg-primary/20">

<div class="flex h-screen overflow-hidden">

    <!-- Mobile overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-30 hidden md:hidden"></div>

    <!-- M3 Navigation Drawer -->
    <nav id="adminSidebar" class="w-[260px] h-full bg-surface flex flex-col p-4 z-40 flex-shrink-0 fixed inset-y-0 left-0 -translate-x-full transition-transform duration-300 ease-in-out md:static md:translate-x-0 md:z-auto">
        <!-- Logo -->
        <div class="flex items-center gap-3 px-3 py-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-on-primary shadow-sm shrink-0">
                <span class="material-symbols-outlined icon-fill !text-[22px]">health_and_safety</span>
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold text-primary tracking-tight leading-none">SeDaP</h1>
                <p class="text-xs text-on-surface-variant font-normal mt-0.5">Community Platform</p>
            </div>
            <button id="sidebarCloseBtn" type="button" class="md:hidden text-on-surface-variant p-1 rounded-full hover:bg-surface-variant/40 transition-colors shrink-0">
                <span class="material-symbols-outlined !text-[18px]">close</span>
            </button>
        </div>

        <!-- Links -->
        <div class="flex-1 space-y-1 overflow-y-auto">
            <?php foreach ($navItems as $item): ?>
                <?php if (!navItemAllowed($item, $currentUser['role'])) continue; ?>
                <?php $isActive = ($activeNav ?? '') === $item['key']; ?>
                <a href="<?php echo $adminBase . $item['path']; ?>"
                   class="flex items-center gap-3.5 px-4 py-2.5 rounded-full text-sm font-medium transition-colors duration-150 <?php echo $isActive ? 'bg-primary text-white font-semibold shadow-sm' : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'; ?>">
                    <span class="material-symbols-outlined <?php echo $isActive ? 'icon-fill !text-white' : ''; ?> !text-[20px]"><?php echo $item['icon']; ?></span>
                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Bottom Settings / Profile -->
        <div class="mt-auto pt-4 border-t border-outline-variant/60">
            <a href="<?php echo $adminBase; ?>settings.php" class="flex items-center gap-3.5 px-4 py-2.5 rounded-full text-sm font-medium text-on-surface-variant hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined !text-[20px]">settings</span>
                <span>Settings</span>
            </a>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-[#f8fafd]">
        <!-- Top App Bar -->
        <header class="flex justify-between items-center gap-4 h-16 px-6 lg:px-10 w-full shrink-0">
            <div class="flex items-center gap-3 flex-1 max-w-xl">
                <button id="sidebarOpenBtn" type="button" class="md:hidden text-on-surface-variant p-1.5 rounded-full hover:bg-surface-variant/50 transition-colors shrink-0">
                    <span class="material-symbols-outlined !text-[22px]">menu</span>
                </button>
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-on-surface-variant/70 !text-[18px]">search</span>
                    <input class="w-full pl-10 pr-4 py-2 rounded-full bg-[#edf1f7] border-none text-xs text-on-surface placeholder:text-on-surface-variant/70 focus:ring-2 focus:ring-primary outline-none transition-all"
                           placeholder="Search patients, staff, or IDs..." type="text">
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <a href="<?php echo $adminBase; ?>../chat/index.php" class="text-on-surface-variant hover:bg-surface-container-low p-2 rounded-full transition-colors relative">
                    <span class="material-symbols-outlined !text-[20px]">notifications</span>
                    <?php if (!empty($unreadChatCount)): ?>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-error rounded-full ring-2 ring-white"></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo $adminBase; ?>../dashboard/tetapan.php" class="p-0.5 rounded-full transition-colors text-primary">
                    <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined !text-[20px]">account_circle</span>
                    </div>
                </a>
            </div>
        </header>

        <!-- Canvas -->
        <div class="flex-1 overflow-y-auto px-6 lg:px-10 py-4">