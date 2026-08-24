<?php
$adminBase = '';
$activeNav = 'dashboard';
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/dashboard_functions.php';
require_once __DIR__ . '/includes/header.php';

$stats = getDashboardStats($pdo, $currentUserId);
$activity = getRecentActivity($pdo, 4);
$firstName = trim(explode(' ', $currentUser['name'])[0]);

$triageColors = ['red' => '#c5221f', 'yellow' => '#ea8600', 'green' => '#34a853', 'black' => '#202124'];
$triageLabels = ['red' => 'Critical', 'yellow' => 'Urgent', 'green' => 'Standard', 'black' => 'Deceased'];
$triageTotal = array_sum($stats['triage_breakdown']);
?>

<div class="flex flex-col lg:flex-row gap-8 pb-10">

    <!-- Left Content Column -->
    <div class="flex-1 flex flex-col gap-6 min-w-0">

        <!-- Welcome Header -->
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
            <div>
                <h2 class="text-3xl lg:text-[38px] font-normal text-on-surface tracking-tight leading-tight">
                    <?php echo dashboardGreeting(); ?>, <?php echo htmlspecialchars($firstName); ?>
                </h2>
                <p class="text-sm text-on-surface-variant font-normal mt-1">Here is the current operational overview.</p>
            </div>
            <a href="patients/index.php"
               class="inline-flex items-center gap-2 bg-primary text-white text-xs font-semibold px-4 py-2.5 rounded-full shadow-sm hover:opacity-90 active:scale-95 transition-all shrink-0">
                <span class="material-symbols-outlined !text-[16px]">add</span>
                New Patient Log
            </a>
        </div>

        <!-- 2x2 Stat Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <!-- Total Patients -->
            <div class="bg-[#1a73e8] text-white rounded-[24px] p-6 flex flex-col justify-between h-[190px] relative overflow-hidden shadow-sm">
                <div class="absolute -right-6 -bottom-6 w-36 h-36 bg-white/10 rounded-full pointer-events-none"></div>
                <div class="flex items-center justify-between relative z-10">
                    <span class="text-sm font-medium">Total Patients</span>
                    <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center">
                        <span class="material-symbols-outlined !text-[16px]">group</span>
                    </div>
                </div>
                <div class="relative z-10">
                    <span class="text-4xl font-normal tracking-tight block mb-2"><?php echo number_format($stats['total_patients']); ?></span>
                    <?php if ($stats['patients_change_pct'] !== null): ?>
                        <div class="inline-flex items-center gap-1.5 bg-white/20 px-2.5 py-1 rounded-full text-[11px] font-medium backdrop-blur-sm">
                            <span class="material-symbols-outlined !text-[12px]"><?php echo $stats['patients_change_pct'] >= 0 ? 'arrow_upward' : 'arrow_downward'; ?></span>
                            <span><?php echo abs($stats['patients_change_pct']); ?>% from last week</span>
                        </div>
                    <?php else: ?>
                        <div class="inline-flex items-center gap-1 bg-white/20 px-2.5 py-1 rounded-full text-[11px] font-medium">
                            <span>No change data yet</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Staff -->
            <div class="bg-[#d3e3fd] text-[#041e49] rounded-[24px] p-6 flex flex-col justify-between h-[190px] relative overflow-hidden shadow-sm">
                <div class="absolute -right-6 -top-6 w-36 h-36 bg-white/30 rounded-full pointer-events-none"></div>
                <div class="flex items-center justify-between relative z-10">
                    <span class="text-sm font-medium text-[#1f1f1f]">Active Staff</span>
                    <div class="w-8 h-8 rounded-full bg-white/40 flex items-center justify-center text-[#1f1f1f]">
                        <span class="material-symbols-outlined !text-[16px]">badge</span>
                    </div>
                </div>
                <div class="relative z-10">
                    <span class="text-4xl font-normal tracking-tight block mb-2 text-[#1f1f1f]"><?php echo number_format($stats['active_staff']); ?></span>
                    <div class="inline-flex items-center gap-1 bg-white/50 px-2.5 py-1 rounded-full text-[11px] font-medium text-[#1f1f1f]">
                        <span><?php echo $stats['pending_staff']; ?> currently on shift</span>
                    </div>
                </div>
            </div>

            <!-- Today's Triage -->
            <div class="bg-[#007a87] text-white rounded-[24px] p-6 flex flex-col justify-between h-[190px] relative overflow-hidden shadow-sm">
                <div class="absolute -right-6 -bottom-6 w-36 h-36 bg-white/10 rounded-full pointer-events-none"></div>
                <div class="flex items-center justify-between relative z-10">
                    <span class="text-sm font-medium">Today's Triage</span>
                    <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center">
                        <span class="material-symbols-outlined !text-[16px]">emergency</span>
                    </div>
                </div>
                <div class="relative z-10">
                    <span class="text-4xl font-normal tracking-tight block mb-2"><?php echo number_format($stats['today_triage']); ?></span>
                    <div class="inline-flex items-center gap-1 bg-white/20 px-2.5 py-1 rounded-full text-[11px] font-medium">
                        <span>Avg wait: 14 mins</span>
                    </div>
                </div>
            </div>

            <!-- Open Chats -->
            <div class="bg-[#e9eef6] text-[#1f1f1f] rounded-[24px] p-6 flex flex-col justify-between h-[190px] relative overflow-hidden shadow-sm">
                <div class="flex items-center justify-between relative z-10">
                    <span class="text-sm font-medium">Open Chats</span>
                    <div class="w-8 h-8 rounded-full bg-white/50 flex items-center justify-center text-on-surface-variant">
                        <span class="material-symbols-outlined !text-[16px]">chat</span>
                    </div>
                </div>
                <div class="relative z-10">
                    <span class="text-4xl font-normal tracking-tight block mb-2"><?php echo number_format($stats['total_conversations']); ?></span>
                    <?php if ($stats['conversations_needing_attention'] > 0): ?>
                        <div class="inline-flex items-center gap-1.5 bg-[#fce8e6] text-[#c5221f] px-2.5 py-1 rounded-full text-[11px] font-semibold">
                            <span class="material-symbols-outlined !text-[12px]">priority_high</span>
                            <span><?php echo $stats['conversations_needing_attention']; ?> require attention</span>
                        </div>
                    <?php else: ?>
                        <div class="inline-flex items-center gap-1 bg-white/60 text-on-surface-variant px-2.5 py-1 rounded-full text-[11px] font-medium">
                            <span>All caught up</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Live Triage Status Card -->
        <div class="bg-white rounded-[24px] shadow-sm p-6 flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-[#1f1f1f]">Live Triage Status</h3>
                <a href="triage/index.php" class="text-primary text-xs font-semibold hover:underline">View All</a>
            </div>

            <?php if ($triageTotal === 0): ?>
                <p class="text-on-surface-variant text-xs py-4 text-center">No active triage records right now.</p>
            <?php else: ?>
                <div class="flex flex-col gap-4">
                    <div class="h-9 w-full rounded-full overflow-hidden flex">
                        <?php foreach (['red', 'yellow', 'green', 'black'] as $level): ?>
                            <?php $count = $stats['triage_breakdown'][$level]; if ($count === 0) continue; ?>
                            <?php $pct = round(($count / $triageTotal) * 100, 1); ?>
                            <div class="h-full flex items-center justify-center px-2 text-xs font-bold transition-all" 
                                 style="width: <?php echo $pct; ?>%; background-color: <?php echo $triageColors[$level]; ?>; color: <?php echo $level === 'yellow' ? '#1f1f1f' : '#ffffff'; ?>;">
                                <?php echo $count; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex items-center gap-6 px-1">
                        <?php foreach (['red', 'yellow', 'green'] as $level): ?>
                            <div class="flex items-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: <?php echo $triageColors[$level]; ?>;"></div>
                                <span class="text-xs text-on-surface-variant font-medium"><?php echo $triageLabels[$level]; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column (Quick Actions & Recent Activity) -->
    <aside class="w-full lg:w-[320px] flex flex-col gap-6 flex-shrink-0">

        <!-- Quick Actions Card -->
        <div class="bg-white rounded-[24px] shadow-sm p-5 flex flex-col gap-3">
            <h3 class="text-sm font-semibold text-[#1f1f1f] mb-1">Quick Actions</h3>
            
            <a href="appointments/index.php"
               class="w-full bg-white text-primary text-xs font-semibold py-3 px-4 rounded-full flex items-center gap-3 border border-outline-variant hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined !text-[18px]">calendar_today</span>
                Schedule Appointment
            </a>
            <a href="patients/index.php"
               class="w-full bg-white text-primary text-xs font-semibold py-3 px-4 rounded-full flex items-center gap-3 border border-outline-variant hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined !text-[18px]">person_add</span>
                Register Patient
            </a>
            <a href="announcements/index.php"
               class="w-full bg-white text-primary text-xs font-semibold py-3 px-4 rounded-full flex items-center gap-3 border border-outline-variant hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined !text-[18px]">chat_bubble_outline</span>
                Broadcast Message
            </a>
        </div>

        <!-- Recent Updates Card -->
        <div class="bg-white rounded-[24px] shadow-sm p-5 flex flex-col gap-4">
            <div class="flex items-center justify-between pb-1">
                <h3 class="text-sm font-semibold text-[#1f1f1f]">Recent Updates</h3>
                <span class="material-symbols-outlined text-on-surface-variant !text-[18px] cursor-pointer">filter_list</span>
            </div>

            <?php if (empty($activity)): ?>
                <p class="text-on-surface-variant text-xs py-6 text-center">No recent updates yet.</p>
            <?php else: ?>
                <div class="flex flex-col gap-4">
                    <?php foreach ($activity as $item): ?>
    <?php
        $tone = $item['tone'] ?? '';
        if ($tone === 'error') {
            $toneBg = 'bg-[#fce8e6] text-[#c5221f]';
        } elseif ($tone === 'tertiary') {
            $toneBg = 'bg-[#e0f2f1] text-[#007a87]';
        } else {
            $toneBg = 'bg-[#e8f0fe] text-primary';
        }
    ?>
    <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 <?php echo $toneBg; ?>">
                                <span class="material-symbols-outlined icon-fill !text-[18px]"><?php echo $item['icon']; ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-baseline gap-2">
                                    <p class="text-xs font-semibold text-[#1f1f1f] truncate"><?php echo htmlspecialchars($item['title']); ?></p>
                                    <span class="text-[10px] text-on-surface-variant whitespace-nowrap"><?php echo dashboardTimeAgo($item['time']); ?></span>
                                </div>
                                <p class="text-[11px] text-on-surface-variant leading-snug line-clamp-2 mt-0.5"><?php echo htmlspecialchars($item['subtitle']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>