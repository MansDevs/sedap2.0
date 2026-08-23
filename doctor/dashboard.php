<?php
$doctorBase = '';
$activeNav = 'dashboard';
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="mb-8">
    <p class="text-lg text-on-surface-variant">
        Welcome back, <strong class="text-on-surface"><?php echo htmlspecialchars($currentUser['name']); ?></strong>.
        Here's everything you have access to.
    </p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($navItems as $item): ?>
        <?php if ($item['key'] === 'dashboard') continue; ?>
        <a href="<?php echo $doctorBase . $item['path']; ?>"
           class="bg-surface-container-low border border-[#e7d8c1] rounded-[28px] p-6 hover:shadow-md hover:border-primary/40 transition-all group">
            <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-primary group-hover:text-on-primary transition-colors text-primary">
                <span class="material-symbols-outlined text-[24px]"><?php echo $item['icon']; ?></span>
            </div>
            <h3 class="font-headline font-bold text-lg text-on-surface mb-1"><?php echo htmlspecialchars($item['label']); ?></h3>
            <p class="text-sm text-on-surface-variant"><?php echo htmlspecialchars($item['description']); ?></p>
        </a>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
