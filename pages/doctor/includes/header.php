<?php
require_once __DIR__ . '/nav_items.php';
?>
<!DOCTYPE html>
<html class="h-full" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Doctor'); ?> - SeDaP Doctor</title>
    <link rel="icon" type="image/jpeg" href="<?php echo $doctorBase; ?>../auth/logo.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo $doctorBase; ?>../../assets/js/theme-config.js"></script>
    <link rel="stylesheet" href="<?php echo $doctorBase; ?>../../assets/css/animations.css">
    
    <!-- Instant synchronous state check to prevent layout shift on page navigation -->
    <script>
        try {
            if (localStorage.getItem('doctor_sidebar_collapsed') === 'true' && window.innerWidth >= 768) {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch(e) {}
    </script>

    <style>
        .mesh-bg {
            background-color: #f7f9fb;
            background-image:
                radial-gradient(at 10% 20%, hsla(212, 100%, 37%, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(188, 100%, 75%, 0.12) 0px, transparent 50%);
        }
        aside::-webkit-scrollbar, main::-webkit-scrollbar { width: 6px; }
        aside::-webkit-scrollbar-thumb, main::-webkit-scrollbar-thumb { background: #c2c6d5; border-radius: 10px; }

        /* Navigation Icons: Outlined by default, Filled when Active */
        .sidebar-nav-icon {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            transition: all 0.2s ease-in-out;
        }
        .sidebar-nav-item.active .sidebar-nav-icon {
            font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 24 !important;
        }

        /* Default Expanded state: Rail labels hidden */
        .sidebar-rail-label {
            display: none;
        }
        .nav-icon-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* ============================================================ */
        /* NAVIGATION RAIL / COLLAPSED STATE (M3 Rail Specification)    */
        /* ============================================================ */
        #doctorSidebar.collapsed,
        html.sidebar-collapsed #doctorSidebar {
            width: 6.5rem !important; /* 104px Navigation Rail */
        }
        #doctorSidebar.collapsed .sidebar-text,
        html.sidebar-collapsed #doctorSidebar .sidebar-text {
            display: none !important;
        }
        #doctorSidebar.collapsed .sidebar-nav-item,
        html.sidebar-collapsed #doctorSidebar .sidebar-nav-item {
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0.45rem 0.25rem !important;
            text-align: center !important;
            gap: 2px !important;
            min-height: 58px !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        /* Active Indicator Pill behind the Icon in Collapsed Rail Mode */
        #doctorSidebar.collapsed .nav-icon-indicator,
        html.sidebar-collapsed #doctorSidebar .nav-icon-indicator {
            width: 56px !important;
            height: 32px !important;
            border-radius: 16px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            background-color: transparent;
            color: #64748b;
        }

        /* Hover on inactive item */
        #doctorSidebar.collapsed .sidebar-nav-item:not(.active):hover .nav-icon-indicator,
        html.sidebar-collapsed #doctorSidebar .sidebar-nav-item:not(.active):hover .nav-icon-indicator {
            background-color: rgba(8, 115, 131, 0.08) !important;
            color: #087383 !important;
        }

        /* Selected Active Indicator Pill */
        #doctorSidebar.collapsed .sidebar-nav-item.active .nav-icon-indicator,
        html.sidebar-collapsed #doctorSidebar .sidebar-nav-item.active .nav-icon-indicator {
            background-color: #d1f0f4 !important;
            color: #087383 !important;
        }

        /* Selected Active Label Text */
        #doctorSidebar.collapsed .sidebar-rail-label,
        html.sidebar-collapsed #doctorSidebar .sidebar-rail-label {
            display: block !important;
            font-size: 11px !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
            margin-top: 3px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 100% !important;
            letter-spacing: -0.01em !important;
            color: #64748b;
            transition: color 0.2s ease, font-weight 0.2s ease;
        }
        #doctorSidebar.collapsed .sidebar-nav-item.active .sidebar-rail-label,
        html.sidebar-collapsed #doctorSidebar .sidebar-nav-item.active .sidebar-rail-label {
            color: #087383 !important;
            font-weight: 700 !important;
        }

        #doctorSidebar.collapsed .sidebar-brand,
        html.sidebar-collapsed #doctorSidebar .sidebar-brand {
            justify-content: center !important;
            padding: 0.85rem 0.5rem !important;
        }
        #doctorSidebar.collapsed .sidebar-user,
        html.sidebar-collapsed #doctorSidebar .sidebar-user {
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            gap: 0 !important;
            margin: 0 auto !important;
        }
        #doctorSidebar.collapsed .sidebar-user > div:first-child,
        html.sidebar-collapsed #doctorSidebar .sidebar-user > div:first-child {
            margin: 0 auto !important;
        }

        /* Hover Logo-to-Toggle swap with smooth rotational cross-fade transition */
        #railMenuToggleBtn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        #railLogoIcon,
        #railToggleIcon {
            transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex !important;
        }

        /* Default / Expanded state */
        #railToggleIcon {
            opacity: 0;
            transform: rotate(90deg) scale(0.6);
            position: absolute;
            pointer-events: none;
        }
        #railLogoIcon {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }

        /* Collapsed state default */
        #doctorSidebar.collapsed #railLogoIcon,
        html.sidebar-collapsed #doctorSidebar #railLogoIcon {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }
        #doctorSidebar.collapsed #railToggleIcon,
        html.sidebar-collapsed #doctorSidebar #railToggleIcon {
            opacity: 0;
            transform: rotate(-90deg) scale(0.6);
            position: absolute;
            pointer-events: none;
        }

        /* Collapsed state on hover: animated swap */
        #doctorSidebar.collapsed #railMenuToggleBtn:hover #railLogoIcon,
        html.sidebar-collapsed #doctorSidebar #railMenuToggleBtn:hover #railLogoIcon {
            opacity: 0 !important;
            transform: rotate(90deg) scale(0.6) !important;
        }
        #doctorSidebar.collapsed #railMenuToggleBtn:hover #railToggleIcon,
        html.sidebar-collapsed #doctorSidebar #railMenuToggleBtn:hover #railToggleIcon {
            opacity: 1 !important;
            transform: rotate(0deg) scale(1) !important;
            pointer-events: auto;
        }
    </style>
</head>
<body class="h-full mesh-bg text-on-surface font-body antialiased">

<div class="flex h-screen overflow-hidden">

    <!-- Mobile overlay, shown behind the drawer when open -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-30 hidden"></div>

    <!-- Sidebar / Nav Rail -->
    <aside id="doctorSidebar" class="w-72 shrink-0 bg-surface-container-low border-r border-outline-variant/30 flex flex-col overflow-y-auto fixed inset-y-0 left-0 z-40 -translate-x-full transition-all duration-300 ease-in-out md:static md:translate-x-0 md:z-auto">
        <script>
            // Apply collapsed class immediately to aside element before rendering
            if (document.documentElement.classList.contains('sidebar-collapsed')) {
                document.getElementById('doctorSidebar').classList.add('collapsed');
            }
        </script>
        <!-- Top Menu & Brand section inside Nav Rail -->
        <div class="p-3.5 sm:p-4 flex items-center justify-between border-b border-outline-variant/20 shrink-0 sidebar-brand">
            <div class="flex items-center gap-3 min-w-0">
                <!-- Logo / Toggle Button -->
                <button type="button" id="railMenuToggleBtn" class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center shrink-0 shadow-sm text-on-primary transition-all duration-150 cursor-pointer overflow-hidden p-0.5" title="SeDaP">
                    <img src="<?php echo $doctorBase; ?>../auth/logo.jpg" alt="SeDaP Logo" class="w-full h-full object-cover rounded-[10px] transition-transform duration-200" id="railLogoIcon">
                    <span class="material-symbols-outlined text-[22px] transition-transform duration-200" id="railToggleIcon">menu_open</span>
                </button>
                <div class="min-w-0 flex-1 sidebar-text">
                    <p class="font-headline font-bold text-base text-primary leading-tight">SeDaP</p>
                    <p class="text-[11px] text-on-surface-variant capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $currentUser['role'] ?? 'Doctor')); ?> Panel</p>
                </div>
            </div>
            
            <!-- Quick Collapse Icon button inside Nav Rail header (desktop only, shown when expanded) -->
            <button type="button" id="railCollapseBtn" class="hidden md:flex text-on-surface-variant hover:text-on-surface p-1.5 rounded-xl hover:bg-surface-container transition-colors shrink-0 sidebar-text" title="Collapse into Nav Rail">
                <span class="material-symbols-outlined text-[20px]">dock_to_left</span>
            </button>

            <!-- Mobile Close Button -->
            <button id="sidebarCloseBtn" type="button" class="md:hidden text-on-surface-variant p-1.5 rounded-full hover:bg-surface-container transition-colors shrink-0">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <!-- Menu Navigation Items inside Nav Rail -->
        <nav class="flex-1 py-4 px-3 space-y-1.5">
            <?php foreach ($navItems as $item): ?>
                <?php $isActive = ($activeNav ?? '') === $item['key']; ?>
                <a href="<?php echo $doctorBase . $item['path']; ?>"
                   title="<?php echo htmlspecialchars($item['label']); ?>"
                   class="sidebar-nav-item flex items-center gap-3 px-3.5 py-3 rounded-2xl text-sm font-medium transition-all <?php echo $isActive ? 'active bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'; ?>">
                    <div class="nav-icon-indicator shrink-0">
                        <span class="material-symbols-outlined sidebar-nav-icon text-[22px]"><?php echo $item['icon']; ?></span>
                    </div>
                    <span class="sidebar-text truncate font-medium"><?php echo htmlspecialchars($item['label']); ?></span>
                    <span class="sidebar-rail-label truncate"><?php echo htmlspecialchars($item['short_label'] ?? $item['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- User Profile Info Footer inside Nav Rail -->
        <div class="p-3.5 border-t border-outline-variant/20 shrink-0">
            <div class="flex items-center gap-3 px-2 py-2 sidebar-user">
                <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary font-bold flex items-center justify-center text-sm font-headline shrink-0 ring-2 ring-primary/20">
                    <?php echo htmlspecialchars(mb_strtoupper(mb_substr($currentUser['name'] ?? '?', 0, 1))); ?>
                </div>
                <div class="flex-1 min-w-0 sidebar-text">
                    <p class="text-sm font-semibold text-on-surface truncate"><?php echo htmlspecialchars($currentUser['name'] ?? ''); ?></p>
                    <p class="text-[11px] text-on-surface-variant truncate capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $currentUser['role'] ?? '')); ?></p>
                </div>
                <a href="<?php echo $doctorBase; ?>../auth/logout.php" class="text-on-surface-variant hover:text-error p-1.5 rounded-xl hover:bg-error/10 transition-colors shrink-0 sidebar-text" title="Log out">
                    <span class="material-symbols-outlined text-[20px]">logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex items-center gap-3 px-4 md:px-10 py-4 md:py-5 border-b border-outline-variant/20 bg-surface-container-low/60 backdrop-blur shrink-0">
            <button id="sidebarOpenBtn" type="button" class="md:hidden text-on-surface p-2 -ml-2 rounded-full hover:bg-surface-container transition-colors shrink-0">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <div class="md:hidden w-8 h-8 rounded-lg overflow-hidden shrink-0 shadow-sm border border-outline-variant/30">
                <img src="<?php echo $doctorBase; ?>../auth/logo.jpg" alt="SeDaP Logo" class="w-full h-full object-cover">
            </div>
            <h1 class="font-headline text-xl md:text-2xl font-bold text-on-surface truncate"><?php echo htmlspecialchars($pageTitle ?? ''); ?></h1>
        </header>
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-10">
