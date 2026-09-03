<?php
$doctorBase = '../';
$activeNav = 'family';
$pageTitle = 'Maklumat Keluarga & Isi Rumah';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/header.php';

// Fetch all households with head and members (New Schema)
$households = [];
try {
    $stmt = $pdo->query("
        SELECT h.*, head.full_name as head_name, head.phone_number as head_phone, head.email as head_email, head.ic_number as head_ic
        FROM Household h
        LEFT JOIN HeadOfHousehold head ON h.household_id = head.household_id
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
                <span>Maklumat Isi Rumah & Rekod Kesihatan Komuniti</span>
            </h2>
            <p class="text-sm text-on-surface-variant">Senarai isi rumah komuniti berdaftar untuk pemantauan wabak (gejala cirit-birit/muntah), pesakit rentan, dan pendedahan makanan.</p>
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
            <input type="text" id="householdSearch" placeholder="Cari nama ketua, no. KP, alamat, bandar..."
                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm pl-10 pr-4 py-2 rounded-xl focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                   oninput="filterHouseholds()">
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <select id="vulnerabilityFilter" onchange="filterHouseholds()" class="bg-surface-container border border-outline-variant/40 text-on-surface text-xs px-3 py-2 rounded-xl focus:outline-none">
                <option value="all">Semua Kategori</option>
                <option value="symptoms">Ada Gejala Wabak (Cirit/Muntah/Demam)</option>
                <option value="vulnerable">Ada Golongan Rentan (OKU/Warga Emas/Kanak-kanak)</option>
                <option value="chronic">Ada Penyakit Kronik</option>
                <option value="outside_food">Ada Pendedahan Makanan Luar / Kenduri</option>
            </select>
        </div>
    </div>

    <!-- Households Grid / Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5" id="householdsGrid">
        <?php if (empty($households)): ?>
            <div class="col-span-full py-16 text-center bg-surface-container-low rounded-3xl border border-dashed border-outline-variant/40">
                <span class="material-symbols-outlined text-on-surface-variant text-[48px] mb-2 opacity-50">group_off</span>
                <p class="text-on-surface-variant font-medium text-sm">Tiada rekod isi rumah ditemui.</p>
                <p class="text-xs text-on-surface-variant/70 mt-1">Pendaftaran baru boleh dibuat melalui portal pesakit atau pengurusan admin.</p>
            </div>
        <?php else: ?>
            <?php foreach ($households as $h): 
                $hId = $h['household_id'];
                $mList = $membersByHousehold[$hId] ?? [];
                
                $hasChronic = false;
                $hasVulnerable = false;
                $hasSymptoms = false;
                $hasFoodExposure = false;
                $symptomCount = 0;

                foreach ($mList as $m) {
                    if (!empty($m['is_affected_member']) || !empty($m['has_diarrhea']) || !empty($m['has_vomiting']) || !empty($m['has_fever'])) {
                        $hasSymptoms = true;
                        $symptomCount++;
                    }
                    if (!empty($m['vulnerable_infant_under5']) || !empty($m['vulnerable_senior_60plus']) || !empty($m['vulnerable_pregnant_mother']) || !empty($m['vulnerable_disability_oku']) || !empty($m['vulnerable_bedridden'])) {
                        $hasVulnerable = true;
                    }
                    if (!empty($m['chronic_diabetes']) || !empty($m['chronic_hypertension']) || !empty($m['chronic_kidney_disease']) || !empty($m['chronic_gastric_intestinal']) || (!empty($m['chronic_other']) && strtolower($m['chronic_other']) !== 'tiada')) {
                        $hasChronic = true;
                    }
                    if ($m['shared_outside_food'] === 'Yes' || !empty($m['shared_feast_meal']) || !empty($m['shared_same_meal_before_symptom'])) {
                        $hasFoodExposure = true;
                    }
                }
            ?>
                <div class="household-card bg-surface-container-lowest border border-outline-variant/30 rounded-3xl p-5 shadow-sm hover:shadow-md transition-all space-y-4"
                     data-search="<?php echo strtolower(htmlspecialchars($h['head_name'] . ' ' . $h['head_ic'] . ' ' . $h['street_address'] . ' ' . $h['city'])); ?>"
                     data-symptoms="<?php echo $hasSymptoms ? '1' : '0'; ?>"
                     data-vulnerable="<?php echo $hasVulnerable ? '1' : '0'; ?>"
                     data-chronic="<?php echo $hasChronic ? '1' : '0'; ?>"
                     data-food="<?php echo $hasFoodExposure ? '1' : '0'; ?>">

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
                        <?php if ($hasSymptoms): ?>
                            <span class="text-xs px-2.5 py-1 rounded-full font-bold bg-rose-500/10 text-rose-600 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">coronavirus</span>
                                <span><?php echo $symptomCount; ?> Bergejala</span>
                            </span>
                        <?php else: ?>
                            <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-emerald-500/10 text-emerald-600">
                                Tiada Gejala
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Address & Dwelling -->
                    <div class="bg-surface-container-low rounded-2xl p-3 text-xs space-y-1">
                        <div class="text-on-surface font-medium"><?php echo htmlspecialchars($h['street_address']); ?></div>
                        <div class="text-on-surface-variant"><?php echo htmlspecialchars($h['postal_code'] . ' ' . $h['city'] . ', ' . $h['state']); ?> (<?php echo htmlspecialchars($h['house_type']); ?>)</div>
                    </div>

                    <!-- Members Summary Badges -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs text-on-surface-variant">
                            <span class="font-semibold text-on-surface">Ahli Isi Rumah (<?php echo count($mList); ?> orang):</span>
                            <?php if ($hasChronic): ?>
                                <span class="text-[11px] text-rose-500 font-semibold flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Kronik</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach ($mList as $mem): 
                                $isSymp = !empty($mem['has_diarrhea']) || !empty($mem['has_vomiting']) || !empty($mem['has_fever']) || !empty($mem['is_affected_member']);
                            ?>
                                <span class="inline-flex items-center gap-1 text-[11px] <?php echo $isSymp ? 'bg-rose-500/10 text-rose-700 border-rose-200' : 'bg-surface-container text-on-surface'; ?> px-2.5 py-1 rounded-lg border border-outline-variant/20">
                                    <span class="font-medium"><?php echo htmlspecialchars($mem['full_name']); ?></span>
                                    <span class="text-on-surface-variant text-[10px]">(<?php echo htmlspecialchars($mem['relationship_to_head']); ?>)</span>
                                    <?php if ($isSymp): ?>
                                        <span class="material-symbols-outlined text-[12px] text-rose-600">sick</span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Health & Food Highlights -->
                    <?php if ($hasFoodExposure || $hasVulnerable): ?>
                        <div class="pt-3 border-t border-outline-variant/20 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <div class="flex flex-wrap gap-1">
                                <?php if ($hasFoodExposure): ?>
                                    <span class="bg-amber-500/10 text-amber-700 px-2 py-0.5 rounded text-[10px] font-semibold flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px]">restaurant</span>
                                        <span>Pendedahan Makanan Luar</span>
                                    </span>
                                <?php endif; ?>
                                <?php if ($hasVulnerable): ?>
                                    <span class="bg-purple-500/10 text-purple-700 px-2 py-0.5 rounded text-[10px] font-semibold flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px]">accessible</span>
                                        <span>Golongan Rentan / OKU</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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

        if (vuln === 'symptoms') matchFilter = card.getAttribute('data-symptoms') === '1';
        else if (vuln === 'vulnerable') matchFilter = card.getAttribute('data-vulnerable') === '1';
        else if (vuln === 'chronic') matchFilter = card.getAttribute('data-chronic') === '1';
        else if (vuln === 'outside_food') matchFilter = card.getAttribute('data-food') === '1';

        card.style.display = (matchQuery && matchFilter) ? 'block' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
