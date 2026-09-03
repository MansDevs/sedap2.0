<?php
$adminBase = '';
$activeNav = 'dashboard';
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/bootstrap.php';

// ---------------------------------------------------------------------------
// 1. Fetch Real-Time Operations, Crisis, and Triage Analytics from Database
// ---------------------------------------------------------------------------
try {
    // 1. Triage Assessments & Urgency Breakdown
    $totalTriage = (int)$pdo->query("SELECT COUNT(*) FROM triage_records")->fetchColumn();
    $redTriage   = (int)$pdo->query("SELECT COUNT(*) FROM triage_records WHERE LOWER(TRIM(triage_level)) = 'red'")->fetchColumn();
    $yellowTriage= (int)$pdo->query("SELECT COUNT(*) FROM triage_records WHERE LOWER(TRIM(triage_level)) = 'yellow'")->fetchColumn();
    $greenTriage = (int)$pdo->query("SELECT COUNT(*) FROM triage_records WHERE LOWER(TRIM(triage_level)) = 'green'")->fetchColumn();
    $todayTriage = (int)$pdo->query("SELECT COUNT(*) FROM triage_records WHERE DATE(triaged_at) = CURDATE()")->fetchColumn();

    // 2. Households & Community Reach
    $totalHouseholds = (int)$pdo->query("SELECT COUNT(*) FROM Household")->fetchColumn();
    $totalResidents  = (int)$pdo->query("SELECT COALESCE(NULLIF(SUM(total_residents), 0), (SELECT COUNT(*) FROM Member) + (SELECT COUNT(*) FROM HeadOfHousehold), 0) FROM Household")->fetchColumn();
    if ($totalResidents === 0) {
        $totalResidents = (int)$pdo->query("SELECT COUNT(*) FROM Member")->fetchColumn();
    }

    // 3. High-Risk Vulnerabilities (Calculated from registered family members)
    $vuln = $pdo->query("
        SELECT 
            COALESCE(SUM(CASE WHEN vulnerable_infant_under5 = 1 OR age < 5 THEN 1 ELSE 0 END), 0) as infants,
            COALESCE(SUM(CASE WHEN vulnerable_senior_60plus = 1 OR age >= 60 THEN 1 ELSE 0 END), 0) as seniors,
            COALESCE(SUM(CASE WHEN vulnerable_pregnant_mother = 1 THEN 1 ELSE 0 END), 0) as pregnant,
            COALESCE(SUM(CASE WHEN vulnerable_disability_oku = 1 THEN 1 ELSE 0 END), 0) as oku,
            COALESCE(SUM(CASE WHEN vulnerable_bedridden = 1 THEN 1 ELSE 0 END), 0) as bedridden
        FROM Member
    ")->fetch(PDO::FETCH_ASSOC);
    $totalVuln = (int)$vuln['infants'] + (int)$vuln['seniors'] + (int)$vuln['pregnant'] + (int)$vuln['oku'] + (int)$vuln['bedridden'];

    // 4. Outbreak Gastro Screening Cases
    $memberOutbreak = (int)$pdo->query("SELECT COUNT(*) FROM Member WHERE has_diarrhea = 1 OR has_vomiting = 1 OR has_fever = 1 OR is_affected_member = 1")->fetchColumn();
    $triageOutbreak = (int)$pdo->query("SELECT COUNT(*) FROM triage_records WHERE symptoms LIKE '%diarrhea%' OR symptoms LIKE '%cirit%' OR symptoms LIKE '%muntah%' OR symptoms LIKE '%vomit%' OR symptoms LIKE '%gastro%' OR symptoms LIKE '%demam%' OR temperature >= 37.8")->fetchColumn();
    $outbreak = max($memberOutbreak, $triageOutbreak);

    // 5. Food Exposure Outbreak Sources
    $foodExp = $pdo->query("
        SELECT 
            COALESCE(SUM(CASE WHEN shared_feast_meal = 1 THEN 1 ELSE 0 END), 0) as kenduri,
            COALESCE(SUM(CASE WHEN shared_outside_food = 'Yes' THEN 1 ELSE 0 END), 0) as outside_food,
            COALESCE(SUM(CASE WHEN shared_outside_food = 'No' THEN 1 ELSE 0 END), 0) as home_food
        FROM Member
    ")->fetch(PDO::FETCH_ASSOC);

    // 6. Staff/Volunteers & Registered Patients
    // Query 'volunteers' table — this is the same source the Personnel page uses
    $personnelCount = (int)$pdo->query("SELECT COUNT(*) FROM volunteers")->fetchColumn();
    $patientCount   = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

    // 7-Day Triage Severity Trend Data
    $trendLabels = [];
    $trendRed = [];
    $trendYellow = [];
    $trendGreen = [];
    $trendMap = [];

    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $lbl = date('j/n', strtotime("-$i days"));
        $trendLabels[] = $lbl;
        $trendMap[$d] = ['red' => 0, 'yellow' => 0, 'green' => 0];
    }

    $stmtTrend = $pdo->query("
        SELECT DATE(triaged_at) as t_date, LOWER(triage_level) as lvl, COUNT(*) as cnt
        FROM triage_records
        WHERE triaged_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(triaged_at), LOWER(triage_level)
    ");
    while ($r = $stmtTrend->fetch(PDO::FETCH_ASSOC)) {
        if (isset($trendMap[$r['t_date']])) {
            $l = $r['lvl'];
            if (isset($trendMap[$r['t_date']][$l])) {
                $trendMap[$r['t_date']][$l] = (int)$r['cnt'];
            }
        }
    }

    foreach ($trendMap as $d => $counts) {
        $trendRed[] = $counts['red'];
        $trendYellow[] = $counts['yellow'];
        $trendGreen[] = $counts['green'];
    }

    // Recent 5 Triage Assessments
    $recentTriage = $pdo->query("
        SELECT tr.*, 
               COALESCE(NULLIF(tr.full_name, ''), p.full_name, 'Unknown') as patient_name,
               COALESCE(NULLIF(tr.ic_number, ''), p.ic_number, '—') as patient_ic
        FROM triage_records tr
        LEFT JOIN patients p ON tr.patient_id = p.id
        ORDER BY tr.triaged_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $totalTriage = $redTriage = $yellowTriage = $greenTriage = $todayTriage = 0;
    $totalHouseholds = $totalResidents = $totalVuln = $outbreak = $personnelCount = $patientCount = 0;
    $vuln = ['infants' => 0, 'seniors' => 0, 'pregnant' => 0, 'oku' => 0, 'bedridden' => 0];
    $foodExp = ['kenduri' => 0, 'outside_food' => 0, 'home_food' => 0];
    $trendLabels = $trendRed = $trendYellow = $trendGreen = [];
    $recentTriage = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Chart.js CDN for interactive visual analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-8 pb-12">
    
    <!-- ============================================================= -->
    <!-- 1. HEADER BANNER & QUICK ACTION LAUNCHER                      -->
    <!-- ============================================================= -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] p-6 shadow-sm">
        <div>
            <h1 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface">
                Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>
            </h1>
            <p class="text-sm text-on-surface-variant mt-1">
                Senyum dari Perut: Usus Sihat, Komuniti Bahagia
            </p>
        </div>

        <!-- Quick Action Buttons -->
        <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
            <a href="<?php echo $adminBase; ?>triage/index.php" class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-on-primary font-semibold px-4 py-2.5 rounded-full shadow-sm text-xs transition-all whitespace-nowrap">
                <span class="material-symbols-outlined text-[18px]">emergency</span>
                <span>New Triage</span>
            </a>
            <a href="<?php echo $adminBase; ?>family/index.php" class="inline-flex items-center gap-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold px-4 py-2.5 rounded-full border border-outline-variant/40 text-xs transition-all whitespace-nowrap">
                <span class="material-symbols-outlined text-[18px]">family_restroom</span>
                <span>Register Household</span>
            </a>
            <a href="<?php echo $adminBase; ?>patients/index.php" class="inline-flex items-center gap-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold px-4 py-2.5 rounded-full border border-outline-variant/40 text-xs transition-all whitespace-nowrap">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                <span>Patient Intake</span>
            </a>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- 2. REAL-TIME OPERATIONS & OUTBREAK KPI CARDS (2x2 GRID)      -->
    <!-- ============================================================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        
        <!-- CARD 1: TRIAGE URGENCY -->
        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[28px] p-5 shadow-sm space-y-3 flex flex-col justify-between hover:border-primary/40 transition-all">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">medical_services</span>
                </div>
                <span class="text-[11px] font-bold text-primary">+<?php echo $todayTriage; ?> today</span>
            </div>
            <div>
                <div class="font-headline text-3xl font-extrabold text-on-surface"><?php echo $totalTriage; ?></div>
                <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mt-0.5">Clinical Triage Assessments</div>
            </div>
            <div class="pt-2 border-t border-outline-variant/20 grid grid-cols-3 gap-1 text-center text-xs">
                <div class="text-red-600 dark:text-red-400 p-1">
                    <div class="font-bold font-mono"><?php echo $redTriage; ?></div>
                    <div class="text-[10px] font-medium">Emergency</div>
                </div>
                <div class="text-amber-700 dark:text-amber-400 p-1">
                    <div class="font-bold font-mono"><?php echo $yellowTriage; ?></div>
                    <div class="text-[10px] font-medium">Moderate</div>
                </div>
                <div class="text-emerald-700 dark:text-emerald-400 p-1">
                    <div class="font-bold font-mono"><?php echo $greenTriage; ?></div>
                    <div class="text-[10px] font-medium">Stable</div>
                </div>
            </div>
        </div>

        <!-- CARD 2: OUTBREAK & GASTRO SCREENING -->
        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[28px] p-5 shadow-sm space-y-3 flex flex-col justify-between hover:border-red-400 transition-all">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-2xl bg-red-500/10 text-red-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">coronavirus</span>
                </div>
                <?php if ($outbreak > 0): ?>
                    <span class="text-[11px] font-bold text-red-600">Alert Active</span>
                <?php else: ?>
                    <span class="text-[11px] font-bold text-emerald-600">Clear</span>
                <?php endif; ?>
            </div>
            <div>
                <div class="font-headline text-3xl font-extrabold text-red-600"><?php echo $outbreak; ?></div>
                <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mt-0.5">Acute Gastro Cases (72h)</div>
            </div>
            <div class="pt-2 border-t border-outline-variant/20 flex items-center justify-between text-xs text-on-surface-variant">
                <span>Feast / Kenduri: <strong class="text-red-600"><?php echo (int)$foodExp['kenduri']; ?></strong></span>
                <span>Outside Food: <strong class="text-on-surface"><?php echo (int)$foodExp['outside_food']; ?></strong></span>
            </div>
        </div>

        <!-- CARD 3: HIGH-RISK VULNERABLE POPULATION -->
        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[28px] p-5 shadow-sm space-y-3 flex flex-col justify-between hover:border-amber-400 transition-all">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-700 dark:text-amber-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">accessible</span>
                </div>
                <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400">High-Risk Watch</span>
            </div>
            <div>
                <div class="font-headline text-3xl font-extrabold text-amber-700 dark:text-amber-400"><?php echo $totalVuln; ?></div>
                <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mt-0.5">Vulnerable Individuals</div>
            </div>
            <div class="pt-2 border-t border-outline-variant/20 flex items-center justify-between text-[11px] text-on-surface-variant">
                <span>Infants: <strong><?php echo (int)$vuln['infants']; ?></strong></span>
                <span>Seniors: <strong><?php echo (int)$vuln['seniors']; ?></strong></span>
                <span>OKU: <strong><?php echo (int)$vuln['oku']; ?></strong></span>
                <span>Bedridden: <strong><?php echo (int)$vuln['bedridden']; ?></strong></span>
            </div>
        </div>

        <!-- CARD 4: REGISTERED HOUSEHOLDS & PPS -->
        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[28px] p-5 shadow-sm space-y-3 flex flex-col justify-between hover:border-primary/40 transition-all">
            <div class="flex items-center justify-between">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">home_pin</span>
                </div>
                <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400"><?php echo $totalHouseholds; ?> Families</span>
            </div>
            <div>
                <div class="font-headline text-3xl font-extrabold text-on-surface"><?php echo $totalResidents; ?></div>
                <div class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider mt-0.5">Total Sheltered Residents</div>
            </div>
            <div class="pt-2 border-t border-outline-variant/20 flex items-center justify-between text-xs text-on-surface-variant">
                <span>Staff/Volunteers: <strong><?php echo $personnelCount; ?></strong></span>
                <span>Registered Patients: <strong><?php echo $patientCount; ?></strong></span>
            </div>
        </div>

    </div>

    <!-- ============================================================= -->
    <!-- 3. VISUAL ANALYTICS & OUTBREAK PROGRESSION CHARTS             -->
    <!-- ============================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- 7-DAY TRIAGE SEVERITY TREND CHART -->
        <div class="lg:col-span-2 bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] p-6 shadow-sm flex flex-col justify-between">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div>
                    <h3 class="font-headline font-bold text-lg text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px] text-primary">monitoring</span>
                        <span>7-Day Clinical Triage Severity Flow</span>
                    </h3>
                    <p class="text-xs text-on-surface-variant">Daily volume distribution across Emergency, Moderate, and Stable categories</p>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> Emergency</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Moderate</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Stable</span>
                </div>
            </div>
            <div class="relative w-full h-[280px]">
                <canvas id="triageSeverityChart"></canvas>
            </div>
        </div>

        <!-- OUTBREAK & VULNERABILITY DISTRIBUTION DOUGHNUT -->
        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] p-6 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="font-headline font-bold text-lg text-on-surface flex items-center gap-2 mb-1">
                    <span class="material-symbols-outlined text-[22px] text-amber-600">pie_chart</span>
                    <span>High-Risk Demographics</span>
                </h3>
                <p class="text-xs text-on-surface-variant mb-4">Vulnerability distribution across registered evacuation zones</p>
            </div>
            <div class="relative w-full h-[230px] flex items-center justify-center">
                <canvas id="vulnerabilityDoughnutChart"></canvas>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs pt-3 border-t border-outline-variant/15 text-on-surface-variant">
                <div>Infants (&lt;5): <strong class="text-on-surface"><?php echo (int)$vuln['infants']; ?></strong></div>
                <div>Seniors (60+): <strong class="text-on-surface"><?php echo (int)$vuln['seniors']; ?></strong></div>
                <div>Pregnant: <strong class="text-on-surface"><?php echo (int)$vuln['pregnant']; ?></strong></div>
                <div>Bedridden / OKU: <strong class="text-on-surface"><?php echo (int)$vuln['bedridden'] + (int)$vuln['oku']; ?></strong></div>
            </div>
        </div>

    </div>

    <!-- ============================================================= -->
    <!-- 4. RECENT TRIAGE ASSESSMENTS (ACTIVITY STREAM)                -->
    <!-- ============================================================= -->
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-headline font-bold text-lg text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[22px] text-primary">history</span>
                    <span>Recent Clinical Triage Assessments</span>
                </h3>
                <p class="text-xs text-on-surface-variant">Real-time live assessment stream from medical stations</p>
            </div>
            <a href="<?php echo $adminBase; ?>triage/index.php" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                <span>View Full Register</span>
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-outline-variant/20 text-xs font-bold text-on-surface-variant uppercase tracking-wider bg-surface-container-low/50">
                        <th class="py-3 px-4">Triage ID</th>
                        <th class="py-3 px-4">Patient Name & IC</th>
                        <th class="py-3 px-4">Vitals Summary</th>
                        <th class="py-3 px-4">Main / Acute Symptoms</th>
                        <th class="py-3 px-4 text-center">Urgency</th>
                        <th class="py-3 px-4">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-on-surface text-xs">
                    <?php if (empty($recentTriage)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-on-surface-variant">
                                No triage assessments recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentTriage as $tr): 
                            $lvl = strtolower($tr['triage_level']);
                            $displayName = !empty($tr['full_name']) ? $tr['full_name'] : ($tr['patient_name'] ?? '—');
                            $displayIC = !empty($tr['ic_number']) ? $tr['ic_number'] : ($tr['patient_ic'] ?? '—');
                            $sympText = $tr['symptoms'] ?? '';
                            if (str_starts_with(trim($sympText), '[')) {
                                $dec = json_decode($sympText, true);
                                $sympText = is_array($dec) ? implode(', ', $dec) : $sympText;
                            }
                        ?>
                            <tr class="hover:bg-surface-container/50 transition-colors">
                                <td class="py-3.5 px-4 font-bold text-primary"><?php echo htmlspecialchars($tr['triage_id'] ?: 'TI-' . str_pad($tr['id'], 3, '0', STR_PAD_LEFT)); ?></td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-on-surface line-clamp-2"><?php echo htmlspecialchars($displayName); ?></div>
                                    <div class="text-[11px] text-on-surface-variant font-mono whitespace-nowrap"><?php echo htmlspecialchars($displayIC); ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div>Temp: <strong><?php echo $tr['temperature'] ? $tr['temperature'] . '°C' : '—'; ?></strong></div>
                                    <div class="text-on-surface-variant">BP: <?php echo htmlspecialchars($tr['blood_pressure'] ?: '—'); ?></div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="text-red-700 dark:text-red-400 font-medium max-w-xs line-clamp-2 break-words" title="<?php echo htmlspecialchars($sympText); ?>">
                                        <?php echo htmlspecialchars($sympText ?: '—'); ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <?php if ($lvl === 'red'): ?>
                                        <span class="inline-flex items-center gap-1 bg-red-500/10 text-red-600 font-bold text-[11px] px-2.5 py-0.5 rounded-full border border-red-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></span>
                                            <span>Emergency</span>
                                        </span>
                                    <?php elseif ($lvl === 'yellow'): ?>
                                        <span class="inline-flex items-center gap-1 bg-amber-500/10 text-amber-700 dark:text-amber-400 font-bold text-[11px] px-2.5 py-0.5 rounded-full border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            <span>Moderate</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-bold text-[11px] px-2.5 py-0.5 rounded-full border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>Stable</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-on-surface-variant whitespace-nowrap">
                                    <?php echo date('d M, h:i A', strtotime($tr['triaged_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- 5. SYSTEM MODULES & NAVIGATION LAUNCHER GRID                   -->
    <!-- ============================================================= -->
    <div class="space-y-4">
        <div>
            <h3 class="font-headline font-bold text-lg text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-[22px] text-primary">apps</span>
                <span>System Modules & Administration</span>
            </h3>
            <p class="text-xs text-on-surface-variant">Direct access to core SeDaP 2.0 operational workspaces</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($navItems as $item): ?>
                <?php if ($item['key'] === 'dashboard') continue; ?>
                <?php if (!navItemAllowed($item, $currentUser['role'])) continue; ?>
                <a href="<?php echo $adminBase . $item['path']; ?>"
                   class="interactive-card bg-surface-container-lowest border border-outline-variant/40 rounded-[28px] p-6 hover:border-primary/40 group transition-all duration-300">
                    <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-primary group-hover:text-on-primary transition-all duration-300 text-primary group-hover:scale-110">
                        <span class="material-symbols-outlined text-[24px]"><?php echo $item['icon']; ?></span>
                    </div>
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-headline font-bold text-lg text-on-surface"><?php echo htmlspecialchars($item['label']); ?></h3>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="text-[10px] font-black tracking-wider uppercase px-2 py-0.5 rounded-full bg-primary/10 text-primary dark:bg-primary/20 dark:text-[#38bdf8] border border-primary/20">
                                <?php echo htmlspecialchars($item['badge']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm text-on-surface-variant"><?php echo htmlspecialchars($item['description']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- ============================================================= -->
<!-- CHART.JS INITIALIZATION SCRIPTS                                -->
<!-- ============================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';

    // 1. Triage Severity Chart (Stacked Bar)
    const ctxTriage = document.getElementById('triageSeverityChart');
    if (ctxTriage) {
        new Chart(ctxTriage, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($trendLabels); ?>,
                datasets: [
                    {
                        label: 'Emergency (Red)',
                        data: <?php echo json_encode($trendRed); ?>,
                        backgroundColor: '#ef4444',
                        borderRadius: 6,
                    },
                    {
                        label: 'Moderate (Yellow)',
                        data: <?php echo json_encode($trendYellow); ?>,
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                    },
                    {
                        label: 'Stable (Green)',
                        data: <?php echo json_encode($trendGreen); ?>,
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 11, family: 'Inter' } }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: textColor, font: { size: 11, family: 'Inter' } },
                        grid: { color: gridColor }
                    }
                }
            }
        });
    }

    // 2. Vulnerability Demographics Doughnut Chart
    const ctxVuln = document.getElementById('vulnerabilityDoughnutChart');
    if (ctxVuln) {
        new Chart(ctxVuln, {
            type: 'doughnut',
            data: {
                labels: ['Infants (<5)', 'Seniors (60+)', 'Pregnant Mothers', 'Disability (OKU)', 'Bedridden'],
                datasets: [{
                    data: [
                        <?php echo (int)$vuln['infants']; ?>,
                        <?php echo (int)$vuln['seniors']; ?>,
                        <?php echo (int)$vuln['pregnant']; ?>,
                        <?php echo (int)$vuln['oku']; ?>,
                        <?php echo (int)$vuln['bedridden']; ?>
                    ],
                    backgroundColor: [
                        '#3b82f6', // Blue (Infants)
                        '#f59e0b', // Amber (Seniors)
                        '#ec4899', // Pink (Pregnant)
                        '#8b5cf6', // Purple (OKU)
                        '#ef4444'  // Rose (Bedridden)
                    ],
                    borderWidth: 2,
                    borderColor: isDark ? '#0f172a' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            boxWidth: 10,
                            font: { size: 10, family: 'Inter' },
                            padding: 10
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
