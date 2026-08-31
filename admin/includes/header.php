<?php
require_once __DIR__ . '/nav_items.php';
require_once __DIR__ . '/access.php';
?>
<!DOCTYPE html>
<html class="h-full" lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Admin'); ?> - SeDaP Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo $adminBase; ?>../assets/js/theme-config.js"></script>
    <style>
        .mesh-bg {
            background-color: #f7f9fb;
            background-image:
                radial-gradient(at 10% 20%, hsla(212, 100%, 37%, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(188, 100%, 75%, 0.12) 0px, transparent 50%);
        }
        aside::-webkit-scrollbar, main::-webkit-scrollbar { width: 6px; }
        aside::-webkit-scrollbar-thumb, main::-webkit-scrollbar-thumb { background: #c2c6d5; border-radius: 10px; }
    </style>
</head>
<body class="h-full mesh-bg text-on-surface font-body antialiased">

<div class="flex h-screen overflow-hidden">

    <!-- Mobile overlay, shown behind the drawer when open -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-30 hidden"></div>

    <!-- Sidebar -->
    <aside id="adminSidebar" class="w-72 shrink-0 bg-surface-container-low border-r border-outline-variant/30 flex flex-col overflow-y-auto fixed inset-y-0 left-0 z-40 -translate-x-full transition-transform duration-300 ease-in-out md:static md:translate-x-0 md:z-auto">
        <div class="p-6 flex items-center gap-3 border-b border-outline-variant/20 shrink-0">
            <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-on-primary">volunteer_activism</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-headline font-bold text-lg text-primary leading-tight">SeDaP</p>
                <p class="text-xs text-on-surface-variant capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $currentUser['role'])); ?> Panel</p>
            </div>
            <button id="sidebarCloseBtn" type="button" class="md:hidden text-on-surface-variant p-1.5 rounded-full hover:bg-surface-container transition-colors shrink-0">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <nav class="flex-1 py-4 px-3 space-y-1">
            <?php foreach ($navItems as $item): ?>
                <?php if (!navItemAllowed($item, $currentUser['role'])) continue; ?>
                <?php $isActive = ($activeNav ?? '') === $item['key']; ?>
                <a href="<?php echo $adminBase . $item['path']; ?>"
                   class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium transition-colors <?php echo $isActive ? 'bg-primary text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container'; ?>">
                    <span class="material-symbols-outlined text-[20px]"><?php echo $item['icon']; ?></span>
                    <?php echo htmlspecialchars($item['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="p-4 border-t border-outline-variant/20 shrink-0">
            <div class="flex items-center gap-3 px-2 py-2">
                <div class="w-9 h-9 rounded-full bg-primary/15 text-primary font-bold flex items-center justify-center text-sm font-headline shrink-0">
                    <?php echo htmlspecialchars(mb_strtoupper(mb_substr($currentUser['name'] ?? '?', 0, 1))); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-on-surface truncate"><?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?></p>
                    <p class="text-xs text-on-surface-variant truncate capitalize"><?php echo htmlspecialchars($currentUser['role'] ?? 'staff'); ?></p>
                </div>
                <a href="<?php echo $adminBase; ?>../auth/logout.php" class="text-on-surface-variant hover:text-error p-1.5 rounded-full transition-colors shrink-0" title="Log out">
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
            <h1 class="font-headline text-xl md:text-2xl font-bold text-on-surface truncate"><?php echo htmlspecialchars($pageTitle ?? ''); ?></h1>
        </header>
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-10">
