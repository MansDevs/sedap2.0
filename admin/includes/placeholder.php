<?php
/**
 * Expects, set by the including page:
 *   $moduleIcon        string  Material Symbols icon name
 *   $moduleTitle       string
 *   $moduleDescription string
 *   $moduleFeatures    array   list of short feature strings
 */
?>
<div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[28px] sm:rounded-[32px] p-6 sm:p-10 md:p-16 text-center max-w-2xl mx-auto shadow-sm">
    <div class="w-16 h-16 mx-auto mb-6 bg-primary/10 rounded-full flex items-center justify-center">
        <span class="material-symbols-outlined text-[32px] text-primary"><?php echo htmlspecialchars($moduleIcon); ?></span>
    </div>

    <h2 class="font-headline text-2xl font-bold text-on-surface mb-2"><?php echo htmlspecialchars($moduleTitle); ?></h2>
    <p class="text-on-surface-variant mb-6"><?php echo htmlspecialchars($moduleDescription); ?></p>

    <?php if (!empty($moduleFeatures)): ?>
        <div class="text-left bg-surface-container-low rounded-2xl p-5 space-y-2.5">
            <p class="text-xs font-semibold uppercase tracking-wide text-secondary mb-1">Planned features</p>
            <?php foreach ($moduleFeatures as $feature): ?>
                <div class="flex items-center gap-2.5 text-sm text-on-surface">
                    <span class="material-symbols-outlined text-[18px] text-primary/60">radio_button_unchecked</span>
                    <?php echo htmlspecialchars($feature); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <span class="inline-block mt-6 text-xs font-semibold bg-secondary/10 text-secondary px-3 py-1.5 rounded-full">Coming soon</span>
</div>
