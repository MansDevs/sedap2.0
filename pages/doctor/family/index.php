<?php
$doctorBase = '../';
$activeNav = 'family';
$pageTitle = 'Maklumat Keluarga & Isi Rumah';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/header.php';

// Fetch all households with members, head, and finances
$households = [];
try {
    $stmt = $pdo->query("
        SELECT h.*, head.full_name as head_name, head.phone_number as head_phone, head.email as head_email, head.ic_number as head_ic,
               f.gross_household_income, f.medical_costs, f.rent_mortgage, f.utilities, f.education_fees
        FROM Household h
        LEFT JOIN HeadOfHousehold head ON h.household_id = head.household_id
        LEFT JOIN HouseholdFinance f ON h.household_id = f.household_id
        ORDER BY h.household_id DESC
    ");
    $households = $stmt->fetchAll();

    // Fetch all members grouped by household_id
    $membersStmt = $pdo->query("SELECT * FROM Member ORDER BY member_id ASC");
    $allMembers = $membersStmt->fetchAll();
    $membersByHousehold = [];
    foreach ($allMembers as $mem) {
        $membersByHousehold[$mem['household_id']][] = $mem;
    }
} catch (Exception $e) {
    $households = [];
    $membersByHousehold = [];
}
?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold font-headline text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[28px]">family_restroom</span>
                <span>Maklumat Isi Rumah & Rekod Keluarga</span>
            </h2>
            <p class="text-sm text-on-surface-variant">Senarai isi rumah komuniti berdaftar untuk pemantauan perubatan, pesakit berisiko, dan bantuan kesihatan.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                <span><?php echo count($households); ?> Isi Rumah Berdaftar</span>
            </span>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-surface-container-low border border-outline-variant/30 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="relative w-full sm:w-96">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">search</span>
            <input type="text" id="householdSearch" placeholder="Cari nama ketua, no. KP, alamat atau bandar..."
                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm pl-10 pr-4 py-2 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                   oninput="filterHouseholds()">
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <select id="vulnerabilityFilter" onchange="filterHouseholds()" class="bg-surface-container border border-outline-variant/40 text-on-surface text-xs px-3 py-2 rounded-xl focus:outline-none">
                <option value="all">Semua Kategori</option>
                <option value="chronic">Ada Penyakit Kronik</option>
                <option value="vulnerable">Ada Warga Emas / OKU</option>
                <option value="b40">Keluarga B40</option>
            </select>
        </div>
    </div>

    <!-- Households Grid / Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5" id="householdsGrid">
        <?php if (empty($households)): ?>
            <div class="col-span-full py-16 text-center bg-surface-container-low rounded-3xl border border-dashed border-outline-variant/40">
                <span class="material-symbols-outlined text-on-surface-variant text-[48px] mb-2 opacity-50">group_off</span>
                <p class="text-on-surface-variant font-medium text-sm">Tiada rekod isi rumah ditemui.</p>
                <p class="text-xs text-on-surface-variant/70 mt-1">Pendaftaran baru boleh dibuat melalui portal pendaftaran pesakit.</p>
            </div>
        <?php else: ?>
            <?php foreach ($households as $h): 
                $hId = $h['household_id'];
                $mList = $membersByHousehold[$hId] ?? [];
                $hasChronic = false;
                $hasVulnerable = false;
                foreach ($mList as $m) {
                    if (!empty($m['chronic_condition']) && strtolower($m['chronic_condition']) !== 'tiada') $hasChronic = true;
                    if (!empty($m['vulnerable_dependent']) && strtolower($m['vulnerable_dependent']) !== 'tiada') $hasVulnerable = true;
                }
                $isB40 = ($h['gross_household_income'] ?? 0) <= 5250;
            ?>
                <div class="household-card bg-surface-container-lowest border border-outline-variant/30 rounded-3xl p-5 shadow-sm hover:shadow-md transition-all space-y-4"
                     data-search="<?php echo strtolower(htmlspecialchars($h['head_name'] . ' ' . $h['head_ic'] . ' ' . $h['street_address'] . ' ' . $h['city'])); ?>"
                     data-chronic="<?php echo $hasChronic ? '1' : '0'; ?>"
                     data-vulnerable="<?php echo $hasVulnerable ? '1' : '0'; ?>"
                     data-b40="<?php echo $isB40 ? '1' : '0'; ?>">

                    <!-- Card Header -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-bold text-lg">
                                <span class="material-symbols-outlined text-[24px]">home</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-base text-on-surface"><?php echo htmlspecialchars($h['head_name'] ?? 'Tiada Nama'); ?></h4>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-mono">#HH-<?php echo str_pad($hId, 5, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="text-xs text-on-surface-variant flex items-center gap-2 mt-0.5">
                                    <span>KP: <?php echo htmlspecialchars($h['head_ic'] ?? '—'); ?></span>
                                    <span>•</span>
                                    <span>Tel: <?php echo htmlspecialchars($h['head_phone'] ?? '—'); ?></span>
                                </div>
                            </div>
                        </div>
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold <?php echo $isB40 ? 'bg-emerald-500/10 text-emerald-600' : 'bg-blue-500/10 text-blue-600'; ?>">
                            <?php echo $isB40 ? 'B40' : 'M40/T20'; ?>
                        </span>
                    </div>

                    <!-- Address & Dwelling -->
                    <div class="bg-surface-container-low rounded-2xl p-3 text-xs space-y-1">
                        <div class="text-on-surface font-medium"><?php echo htmlspecialchars($h['street_address']); ?></div>
                        <div class="text-on-surface-variant"><?php echo htmlspecialchars($h['postal_code'] . ' ' . $h['city'] . ', ' . $h['state']); ?> (<?php echo htmlspecialchars($h['house_type']); ?>)</div>
                    </div>

                    <!-- Members Summary Badges -->
                    <div>
                        <div class="flex items-center justify-between text-xs text-on-surface-variant mb-2">
                            <span class="font-semibold text-on-surface">Ahli Isi Rumah (<?php echo count($mList); ?> orang):</span>
                            <?php if ($hasChronic): ?>
                                <span class="text-[11px] text-rose-500 font-semibold flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Ada Penyakit Kronik</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach ($mList as $mem): ?>
                                <span class="inline-flex items-center gap-1 text-[11px] bg-surface-container px-2.5 py-1 rounded-lg border border-outline-variant/20">
                                    <span class="font-medium"><?php echo htmlspecialchars($mem['full_name']); ?></span>
                                    <span class="text-on-surface-variant">(<?php echo htmlspecialchars($mem['relationship_to_head']); ?>)</span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Financial & Triage Highlights -->
                    <div class="pt-3 border-t border-outline-variant/20 flex items-center justify-between text-xs">
                        <div>
                            <span class="text-on-surface-variant">Pendapatan:</span>
                            <span class="font-bold text-emerald-600 ml-1">RM <?php echo number_format($h['gross_household_income'] ?? 0, 2); ?></span>
                        </div>
                        <div>
                            <span class="text-on-surface-variant">Kos Perubatan:</span>
                            <span class="font-semibold text-rose-500 ml-1">RM <?php echo number_format($h['medical_costs'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function filterHouseholds() {
    const q = document.getElementById('householdSearch').value.toLowerCase();
    const vuln = document.getElementById('vulnerabilityFilter').value;
    const cards = document.querySelectorAll('.household-card');

    cards.forEach(card => {
        const text = card.getAttribute('data-search') || '';
        const matchQuery = text.includes(q);
        let matchFilter = true;

        if (vuln === 'chronic') matchFilter = card.getAttribute('data-chronic') === '1';
        else if (vuln === 'vulnerable') matchFilter = card.getAttribute('data-vulnerable') === '1';
        else if (vuln === 'b40') matchFilter = card.getAttribute('data-b40') === '1';

        card.style.display = (matchQuery && matchFilter) ? 'block' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
