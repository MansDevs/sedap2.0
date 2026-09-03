<?php
/**
 * ============================================================================
 *   SeDaP 2.0 — Admin Family & Household Management Module (Pengurusan Isi Rumah)
 *   Matching Updated SQL Schema:
 *   1. Household Table (Dwelling & Location)
 *   2. HeadOfHousehold Table (Primary Contact)
 *   3. Member Table (Demographics, Vulnerability, Chronic, Outbreak Screening, Food Exposure)
 * ============================================================================
 */
$adminBase = '../';
$activeNav = 'family';
$pageTitle = 'Family Information';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/access.php';
requireRole($currentUser, [], $adminBase);

$msg = '';
$err = '';

// ---------------------------------------------------------------------------
// 1. Handle CSV Export
// ---------------------------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sedap_family_records_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Household ID', 'Head Name', 'Head IC', 'Head Phone', 'Head Email',
        'Address', 'Postal Code', 'City', 'State', 'House Type', 'Total Residents',
        'Symptomatic Cases', 'Vulnerable Members', 'Chronic Cases', 'Date Registered'
    ]);
    
    $stmt = $pdo->query("
        SELECT h.*, head.full_name as head_name, head.phone_number as head_phone, head.email as head_email, head.ic_number as head_ic
        FROM Household h
        LEFT JOIN HeadOfHousehold head ON h.household_id = head.household_id
        ORDER BY h.household_id DESC
    ");
    $householdsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $allMembersData = $pdo->query("SELECT * FROM Member ORDER BY member_id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $membersByH = [];
    foreach ($allMembersData as $m) {
        $membersByH[$m['household_id']][] = $m;
    }

    foreach ($householdsData as $row) {
        $hId = $row['household_id'];
        $mList = $membersByH[$hId] ?? [];
        
        $symptomCount = 0;
        $vulnCount = 0;
        $chronicCount = 0;
        foreach ($mList as $m) {
            if (!empty($m['is_affected_member']) || !empty($m['has_diarrhea']) || !empty($m['has_vomiting']) || !empty($m['has_fever'])) {
                $symptomCount++;
            }
            if (!empty($m['vulnerable_infant_under5']) || !empty($m['vulnerable_senior_60plus']) || !empty($m['vulnerable_pregnant_mother']) || !empty($m['vulnerable_disability_oku']) || !empty($m['vulnerable_bedridden'])) {
                $vulnCount++;
            }
            if (!empty($m['chronic_diabetes']) || !empty($m['chronic_hypertension']) || !empty($m['chronic_kidney_disease']) || !empty($m['chronic_gastric_intestinal']) || (!empty($m['chronic_other']) && strtolower($m['chronic_other']) !== 'tiada')) {
                $chronicCount++;
            }
        }

        fputcsv($output, [
            'HH-' . str_pad($row['household_id'], 5, '0', STR_PAD_LEFT),
            $row['head_name'],
            $row['head_ic'],
            $row['head_phone'],
            $row['head_email'],
            $row['street_address'],
            $row['postal_code'],
            $row['city'],
            $row['state'],
            $row['house_type'],
            count($mList) > 0 ? count($mList) : $row['total_residents'],
            $symptomCount,
            $vulnCount,
            $chronicCount,
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// ---------------------------------------------------------------------------
// 2. Handle Household Deletion
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = (int)($_POST['household_id'] ?? 0);
    if ($delId > 0) {
        try {
            $pdo->prepare("DELETE FROM Household WHERE household_id = ?")->execute([$delId]);
            $msg = "Household record #HH-" . str_pad($delId, 5, '0', STR_PAD_LEFT) . " has been deleted successfully.";
        } catch (Exception $e) {
            $err = "Failed to delete household: " . $e->getMessage();
        }
    }
}

// ---------------------------------------------------------------------------
// 3. Handle Household Registration & Editing Submission
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add', 'edit'])) {
    try {
        $isEdit          = (($_POST['action'] ?? '') === 'edit');
        $editHouseholdId = !empty($_POST['household_id']) ? (int)$_POST['household_id'] : null;

        $street_address  = trim($_POST['street_address'] ?? '');
        $postal_code     = trim($_POST['postal_code'] ?? '');
        $city            = trim($_POST['city'] ?? '');
        $state           = trim($_POST['state'] ?? '');
        $house_type      = trim($_POST['house_type'] ?? 'Landed');
        
        $head_ic         = trim($_POST['head_ic'] ?? '');
        $head_name       = trim($_POST['head_name'] ?? '');
        $head_phone      = trim($_POST['head_phone'] ?? '');
        $head_email      = trim($_POST['head_email'] ?? '');

        $membersData     = $_POST['members'] ?? [];
        $total_residents = max(1, count($membersData));

        if (empty($street_address) || empty($postal_code) || empty($head_ic) || empty($head_name)) {
            throw new Exception("Please complete the required dwelling and head of household information.");
        }

        $pdo->beginTransaction();

        if ($isEdit && $editHouseholdId) {
            // 1. Update Household
            $stmtH = $pdo->prepare("
                UPDATE Household 
                SET street_address = ?, postal_code = ?, city = ?, state = ?, house_type = ?, total_residents = ?
                WHERE household_id = ?
            ");
            $stmtH->execute([$street_address, $postal_code, $city, $state, $house_type, $total_residents, $editHouseholdId]);
            $householdId = $editHouseholdId;

            // 2. Update HeadOfHousehold
            $stmtHead = $pdo->prepare("
                UPDATE HeadOfHousehold 
                SET ic_number = ?, full_name = ?, phone_number = ?, email = ?
                WHERE household_id = ?
            ");
            $stmtHead->execute([$head_ic, $head_name, $head_phone, $head_email, $householdId]);

            // 3. Delete existing members to re-sync
            $pdo->prepare("DELETE FROM Member WHERE household_id = ?")->execute([$householdId]);
        } else {
            // 1. Insert Household
            $stmtH = $pdo->prepare("
                INSERT INTO Household (street_address, postal_code, city, state, house_type, total_residents, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtH->execute([$street_address, $postal_code, $city, $state, $house_type, $total_residents]);
            $householdId = (int)$pdo->lastInsertId();

            // 2. Insert Head of Household
            $stmtHead = $pdo->prepare("
                INSERT INTO HeadOfHousehold (ic_number, full_name, phone_number, email, household_id)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), phone_number = VALUES(phone_number), email = VALUES(email), household_id = VALUES(household_id)
            ");
            $stmtHead->execute([$head_ic, $head_name, $head_phone, $head_email, $householdId]);
        }

        // 3. Insert Members
        if (!empty($membersData) && is_array($membersData)) {
            $stmtM = $pdo->prepare("
                INSERT INTO Member (
                    full_name, national_id, date_of_birth, age, gender, relationship_to_head,
                    marital_status, citizenship_status, education_level, employment_status,
                    vulnerable_infant_under5, vulnerable_senior_60plus, vulnerable_pregnant_mother,
                    vulnerable_disability_oku, vulnerable_bedridden,
                    chronic_diabetes, chronic_hypertension, chronic_kidney_disease,
                    chronic_gastric_intestinal, chronic_other,
                    drug_allergies, food_allergies,
                    has_diarrhea, has_vomiting, has_fever, is_affected_member, symptom_onset_date,
                    shared_outside_food, outside_food_notes, shared_feast_meal, shared_same_meal_before_symptom, meal_type,
                    healthcare_coverage, household_id, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?,
                    ?, ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, NOW()
                )
            ");

            foreach ($membersData as $m) {
                $m_name       = trim($m['full_name'] ?? '');
                $m_ic         = trim($m['national_id'] ?? '');
                $m_dob        = !empty($m['date_of_birth']) ? $m['date_of_birth'] : null;
                $m_age        = !empty($m['age']) ? (int)$m['age'] : null;
                
                // Calculate age if not provided and DOB exists
                if ($m_age === null && $m_dob) {
                    $birthDate = new DateTime($m_dob);
                    $today = new DateTime();
                    $m_age = $today->diff($birthDate)->y;
                }

                $m_gender     = in_array($m['gender'] ?? '', ['Male', 'Female']) ? $m['gender'] : 'Male';
                $validRelations = ['Head', 'Spouse', 'Child', 'Mother', 'Father', 'Grandfather', 'Grandmother', 'Relative', 'Others'];
                $m_relation   = in_array($m['relationship_to_head'] ?? '', $validRelations) ? $m['relationship_to_head'] : 'Head';
                
                $validMarital = ['Single', 'Married', 'Divorced', 'Widowed'];
                $m_marital    = in_array($m['marital_status'] ?? '', $validMarital) ? $m['marital_status'] : 'Single';
                
                $m_citizen    = trim($m['citizenship_status'] ?? 'Warganegara');
                
                $validEdu     = ['No Formal Education', 'Primary', 'Secondary', 'Tertiary', 'Post-Graduate'];
                $m_edu        = in_array($m['education_level'] ?? '', $validEdu) ? $m['education_level'] : 'Secondary';
                
                $validEmp     = ['Employed', 'Self-Employed', 'Unemployed', 'Student', 'Retired', 'Homemaker', 'Informal'];
                $m_job        = in_array($m['employment_status'] ?? '', $validEmp) ? $m['employment_status'] : 'Employed';

                // Vulnerabilities
                $v_infant     = !empty($m['vulnerable_infant_under5']) ? 1 : 0;
                $v_senior     = !empty($m['vulnerable_senior_60plus']) ? 1 : 0;
                $v_pregnant   = !empty($m['vulnerable_pregnant_mother']) ? 1 : 0;
                $v_oku        = !empty($m['vulnerable_disability_oku']) ? 1 : 0;
                $v_bedridden  = !empty($m['vulnerable_bedridden']) ? 1 : 0;

                // Chronic
                $c_diab       = !empty($m['chronic_diabetes']) ? 1 : 0;
                $c_hyper      = !empty($m['chronic_hypertension']) ? 1 : 0;
                $c_kidney     = !empty($m['chronic_kidney_disease']) ? 1 : 0;
                $c_gastric    = !empty($m['chronic_gastric_intestinal']) ? 1 : 0;
                $c_other      = trim($m['chronic_other'] ?? '');

                // Allergies
                $drug_allergies = trim($m['drug_allergies'] ?? '');
                $food_allergies = trim($m['food_allergies'] ?? '');

                // Health Screening (Past 3 Days)
                $has_diarrhea = !empty($m['has_diarrhea']) ? 1 : 0;
                $has_vomiting = !empty($m['has_vomiting']) ? 1 : 0;
                $has_fever    = !empty($m['has_fever']) ? 1 : 0;
                $is_affected  = (!empty($m['is_affected_member']) || $has_diarrhea || $has_vomiting || $has_fever) ? 1 : 0;
                $symptom_date = !empty($m['symptom_onset_date']) ? $m['symptom_onset_date'] : null;

                // Food Exposure
                $shared_out_food = in_array($m['shared_outside_food'] ?? '', ['Yes', 'No', 'Not Applicable']) ? $m['shared_outside_food'] : 'Not Applicable';
                $out_food_notes  = trim($m['outside_food_notes'] ?? '');
                $shared_feast    = !empty($m['shared_feast_meal']) ? 1 : 0;
                $shared_same     = !empty($m['shared_same_meal_before_symptom']) ? 1 : 0;
                $meal_type       = trim($m['meal_type'] ?? '');

                $m_health_cov    = trim($m['healthcare_coverage'] ?? 'KKM / Kerajaan');

                if (!empty($m_name) && !empty($m_ic)) {
                    $stmtM->execute([
                        $m_name, $m_ic, $m_dob, $m_age, $m_gender, $m_relation,
                        $m_marital, $m_citizen, $m_edu, $m_job,
                        $v_infant, $v_senior, $v_pregnant, $v_oku, $v_bedridden,
                        $c_diab, $c_hyper, $c_kidney, $c_gastric, $c_other,
                        $drug_allergies, $food_allergies,
                        $has_diarrhea, $has_vomiting, $has_fever, $is_affected, $symptom_date,
                        $shared_out_food, $out_food_notes, $shared_feast, $shared_same, $meal_type,
                        $m_health_cov, $householdId
                    ]);
                }
            }
        }

        $pdo->commit();
        $msg = "Household record #HH-" . str_pad($householdId, 5, '0', STR_PAD_LEFT) . " registered successfully.";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $err = "Registration error: " . $e->getMessage();
    }
}

// ---------------------------------------------------------------------------
// 4. Fetch All Households, Members & Epidemiological Stats
// ---------------------------------------------------------------------------
$households = [];
$totalMembersCount = 0;
$chronicCount = 0;
$vulnerableCount = 0;
$symptomaticCasesCount = 0;

try {
    $hQuery = $pdo->query("
        SELECT h.*, head.full_name as head_name, head.phone_number as head_phone, head.email as head_email, head.ic_number as head_ic
        FROM Household h
        LEFT JOIN HeadOfHousehold head ON h.household_id = head.household_id
        ORDER BY h.household_id DESC
    ");
    $households = $hQuery->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all members
    $mQuery = $pdo->query("SELECT * FROM Member ORDER BY member_id ASC");
    $allMembers = $mQuery->fetchAll(PDO::FETCH_ASSOC);
    $membersByHousehold = [];
    foreach ($allMembers as $m) {
        $membersByHousehold[$m['household_id']][] = $m;
        $totalMembersCount++;
        
        $hasC = !empty($m['chronic_diabetes']) || !empty($m['chronic_hypertension']) || !empty($m['chronic_kidney_disease']) || !empty($m['chronic_gastric_intestinal']) || (!empty($m['chronic_other']) && strtolower($m['chronic_other']) !== 'tiada');
        if ($hasC) $chronicCount++;

        $hasV = !empty($m['vulnerable_infant_under5']) || !empty($m['vulnerable_senior_60plus']) || !empty($m['vulnerable_pregnant_mother']) || !empty($m['vulnerable_disability_oku']) || !empty($m['vulnerable_bedridden']);
        if ($hasV) $vulnerableCount++;

        $hasS = !empty($m['is_affected_member']) || !empty($m['has_diarrhea']) || !empty($m['has_vomiting']) || !empty($m['has_fever']);
        if ($hasS) $symptomaticCasesCount++;
    }
} catch (Exception $e) {
    $households = [];
    $membersByHousehold = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
  .rounded-24 { border-radius: 24px !important; }
  .input-24 { border-radius: 24px !important; padding: 0.65rem 1.25rem !important; }
  .textarea-24 { border-radius: 20px !important; padding: 0.75rem 1.25rem !important; }
  .member-card-box {
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 24px;
    transition: all 0.2s ease;
  }
</style>

<div class="space-y-6 pb-12">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface flex items-center gap-3">
                <span class="material-symbols-outlined text-[32px] text-primary">family_restroom</span>
                <span>Family & Household Management</span>
            </h1>
        </div>
        <div class="flex items-center gap-3 shrink-0 flex-wrap sm:flex-nowrap">
            <a href="index.php?export=csv" class="inline-flex items-center gap-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold px-4 py-2.5 rounded-[24px] border border-outline-variant/40 shadow-sm transition-all text-sm whitespace-nowrap shrink-0">
                <span class="material-symbols-outlined text-[20px]">download</span>
                <span class="whitespace-nowrap">Export CSV</span>
            </a>
            <button onclick="openRegistrationModal()" class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-on-primary font-semibold px-5 py-2.5 rounded-[24px] shadow-sm transition-all duration-200 hover:shadow text-sm whitespace-nowrap shrink-0">
                <span class="material-symbols-outlined text-[20px]">add_circle</span>
                <span class="whitespace-nowrap">New Family Registration</span>
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-[20px] flex items-center gap-3 shadow-sm">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <div><?php echo htmlspecialchars($msg); ?></div>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-[20px] flex items-center gap-3 shadow-sm">
            <span class="material-symbols-outlined text-rose-600">error</span>
            <div><?php echo htmlspecialchars($err); ?></div>
        </div>
    <?php endif; ?>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Households</span>
                <span class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center material-symbols-outlined text-[22px]">home</span>
            </div>
            <div class="text-2xl font-bold font-headline text-on-surface mt-2"><?php echo count($households); ?></div>
            <div class="text-xs text-on-surface-variant mt-1">Registered family dwellings</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Residents</span>
                <span class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center material-symbols-outlined text-[22px]">groups</span>
            </div>
            <div class="text-2xl font-bold font-headline text-on-surface mt-2"><?php echo $totalMembersCount; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">Individual family members</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Acute Outbreak Cases</span>
                <span class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-600 flex items-center justify-center material-symbols-outlined text-[22px]">coronavirus</span>
            </div>
            <div class="text-2xl font-bold font-headline text-rose-600 mt-2"><?php echo $symptomaticCasesCount; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">Diarrhea / vomiting / fever</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Vulnerable & Chronic</span>
                <span class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center material-symbols-outlined text-[22px]">accessible</span>
            </div>
            <div class="text-2xl font-bold font-headline text-amber-600 mt-2"><?php echo $vulnerableCount + $chronicCount; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">OKU, elderly, infants, chronic</div>
        </div>
    </div>

    <!-- Master Directory Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[28px] overflow-hidden shadow-sm">
        <div class="p-5 sm:p-6 border-b border-outline-variant/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">lists</span>
                <h2 class="font-headline font-bold text-lg text-on-surface">Registered Households Directory</h2>
            </div>
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <div class="w-full sm:w-72">
                    <input type="text" id="householdSearchInput" placeholder="Search Head, IC, Address, City..."
                           class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm px-4 py-2 input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                           oninput="searchHouseholdTable()">
                </div>
                <select id="typeFilter" onchange="searchHouseholdTable()" class="bg-surface-container border border-outline-variant/40 text-on-surface text-xs px-3 py-2 rounded-2xl focus:outline-none">
                    <option value="">All House Types</option>
                    <option value="Landed">Landed / Teres</option>
                    <option value="Apartment">Apartment / Kondominium</option>
                    <option value="Rental">Rental / Sewa</option>
                    <option value="Owned">Owned / Sendiri</option>
                    <option value="PPS">Pusat Pemindahan (PPS)</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" id="householdTable">
                <thead class="bg-surface-container text-on-surface-variant text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Household ID</th>
                        <th class="py-3.5 px-4">Head of Household</th>
                        <th class="py-3.5 px-4">National IC</th>
                        <th class="py-3.5 px-4">Address & Location</th>
                        <th class="py-3.5 px-4">House Type</th>
                        <th class="py-3.5 px-4 text-center">Residents</th>
                        <th class="py-3.5 px-4">Outbreak Screening</th>
                        <th class="py-3.5 px-4">Vulnerability Profile</th>
                        <th class="py-3.5 px-6 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-on-surface">
                    <?php if (empty($households)): ?>
                        <tr>
                            <td colspan="9" class="py-12 text-center text-on-surface-variant">
                                No registered households found. <button type="button" onclick="openRegistrationModal()" class="text-primary font-semibold hover:underline">Click here to register a new family</button>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($households as $h): 
                            $hId = $h['household_id'];
                            $mList = $membersByHousehold[$hId] ?? [];
                            
                            $symptomMembers = 0;
                            $hasElderly = false;
                            $hasOku = false;
                            $hasInfant = false;
                            $hasChronic = false;

                            foreach ($mList as $mem) {
                                if (!empty($mem['is_affected_member']) || !empty($mem['has_diarrhea']) || !empty($mem['has_vomiting']) || !empty($mem['has_fever'])) {
                                    $symptomMembers++;
                                }
                                if (!empty($mem['vulnerable_senior_60plus'])) $hasElderly = true;
                                if (!empty($mem['vulnerable_disability_oku']) || !empty($mem['vulnerable_bedridden'])) $hasOku = true;
                                if (!empty($mem['vulnerable_infant_under5'])) $hasInfant = true;
                                if (!empty($mem['chronic_diabetes']) || !empty($mem['chronic_hypertension']) || !empty($mem['chronic_kidney_disease']) || !empty($mem['chronic_gastric_intestinal'])) $hasChronic = true;
                            }
                        ?>
                            <tr class="hover:bg-surface-container/50 transition-colors household-table-row" data-type="<?php echo htmlspecialchars($h['house_type']); ?>">
                                <td class="py-4 px-6 font-semibold text-primary">#HH-<?php echo str_pad($hId, 5, '0', STR_PAD_LEFT); ?></td>
                                <td class="py-4 px-4 font-medium">
                                    <?php echo htmlspecialchars($h['head_name'] ?? '—'); ?>
                                    <div class="text-xs text-on-surface-variant"><?php echo htmlspecialchars($h['head_phone'] ?? '—'); ?></div>
                                </td>
                                <td class="py-4 px-4 text-on-surface-variant font-mono"><?php echo htmlspecialchars($h['head_ic'] ?? '—'); ?></td>
                                <td class="py-4 px-4 text-xs">
                                    <div class="font-medium truncate max-w-xs"><?php echo htmlspecialchars($h['street_address']); ?></div>
                                    <div class="text-on-surface-variant"><?php echo htmlspecialchars($h['postal_code'] . ' ' . $h['city'] . ', ' . $h['state']); ?></div>
                                </td>
                                <td class="py-4 px-4 text-xs font-medium text-on-surface">
                                    <?php echo htmlspecialchars($h['house_type']); ?>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 bg-primary/10 text-primary font-bold text-xs px-2.5 py-1 rounded-full">
                                        <span class="material-symbols-outlined text-[14px]">person</span>
                                        <span><?php echo count($mList); ?></span>
                                    </span>
                                </td>
                                <td class="py-4 px-4">
                                    <?php if ($symptomMembers > 0): ?>
                                        <span class="inline-flex items-center gap-1 bg-rose-500/10 text-rose-600 font-bold text-xs px-2.5 py-1 rounded-full">
                                            <span class="material-symbols-outlined text-[14px]">sick</span>
                                            <span>Symptomatic</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 bg-emerald-500/10 text-emerald-600 font-semibold text-xs px-2.5 py-1 rounded-full">
                                            <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                            <span>Clear</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex flex-col gap-1 text-[11px]">
                                        <?php if ($hasInfant): ?><span class="text-blue-600 font-semibold">• Infant</span><?php endif; ?>
                                        <?php if ($hasElderly): ?><span class="text-amber-700 font-semibold">• Senior</span><?php endif; ?>
                                        <?php if ($hasOku): ?><span class="text-purple-700 font-semibold">• OKU</span><?php endif; ?>
                                        <?php if ($hasChronic): ?><span class="text-rose-600 font-semibold">• Chronic</span><?php endif; ?>
                                        <?php if (!$hasInfant && !$hasElderly && !$hasOku && !$hasChronic): ?>
                                            <span class="text-on-surface-variant font-medium">General</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" onclick='viewHouseholdModal(<?php echo htmlspecialchars(json_encode($h), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($mList), ENT_QUOTES, 'UTF-8'); ?>)'
                                                class="inline-flex items-center gap-1 text-primary hover:text-primary/80 font-semibold text-xs bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-full transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                            <span>View</span>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this household record? This will also remove all family members.');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="household_id" value="<?php echo $hId; ?>">
                                            <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-full transition-colors" title="Delete Household">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- MODAL: MULTI-STEP NEW HOUSEHOLD REGISTRATION (UPDATED SCHEMA) -->
<!-- ============================================================= -->
<div id="registrationModal" class="fixed inset-0 z-[99999] hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6" style="display: none;" onclick="closeRegistrationModal()">
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col h-[92vh] max-h-[860px] text-left transform transition-all animate-scale-up" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="px-6 py-5 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[24px]">family_restroom</span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-lg text-on-surface" id="regModalTitle">Household & Family Registration</h3>
                    <p class="text-xs text-on-surface-variant">Comprehensive demographics, vulnerability, and outbreak food-exposure screening</p>
                </div>
            </div>
            <button type="button" onclick="closeRegistrationModal()" class="text-on-surface-variant hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <!-- Stepper Navigation -->
        <div class="px-6 pt-5 pb-3 border-b border-outline-variant/10 bg-surface-container-lowest shrink-0">
            <div class="grid grid-cols-4 gap-2 text-center relative">
                <button type="button" id="stepBtn1" onclick="jumpToStep(1)" class="group flex flex-col items-center p-2 rounded-2xl transition-all">
                    <div id="stepCircle1" class="w-10 h-10 rounded-full bg-primary text-on-primary font-bold flex items-center justify-center text-sm shadow-md transition-all">1</div>
                    <span id="stepLabel1" class="text-xs font-bold text-primary mt-1.5 line-clamp-1">1. Dwelling</span>
                </button>
                <button type="button" id="stepBtn2" onclick="jumpToStep(2)" class="group flex flex-col items-center p-2 rounded-2xl transition-all">
                    <div id="stepCircle2" class="w-10 h-10 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-sm transition-all">2</div>
                    <span id="stepLabel2" class="text-xs font-medium text-on-surface-variant mt-1.5 line-clamp-1">2. Head of House</span>
                </button>
                <button type="button" id="stepBtn3" onclick="jumpToStep(3)" class="group flex flex-col items-center p-2 rounded-2xl transition-all">
                    <div id="stepCircle3" class="w-10 h-10 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-sm transition-all">3</div>
                    <span id="stepLabel3" class="text-xs font-medium text-on-surface-variant mt-1.5 line-clamp-1">3. Members & Health</span>
                </button>
                <button type="button" id="stepBtn4" onclick="jumpToStep(4)" class="group flex flex-col items-center p-2 rounded-2xl transition-all">
                    <div id="stepCircle4" class="w-10 h-10 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-sm transition-all">4</div>
                    <span id="stepLabel4" class="text-xs font-medium text-on-surface-variant mt-1.5 line-clamp-1">4. Confirmation</span>
                </button>
            </div>
            <div class="w-full bg-surface-container h-1.5 rounded-full mt-3 overflow-hidden">
                <div id="stepProgressBar" class="bg-primary h-full transition-all duration-300" style="width: 25%;"></div>
            </div>
        </div>

        <!-- Form Body -->
        <form method="POST" id="householdMultiStepForm" class="overflow-y-auto p-6 space-y-6 flex-1">
            <input type="hidden" name="action" id="household_form_action" value="add">
            <input type="hidden" name="household_id" id="adm_household_id" value="">

            <!-- STEP 1: DWELLING -->
            <div id="categoryStep1" class="space-y-4">
                <div class="bg-primary/5 border-l-4 border-primary p-4 rounded-2xl">
                    <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">home_pin</span>
                        <span>Step 1: Household Dwelling & Location</span>
                    </h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Street Address <span class="text-rose-500">*</span></label>
                        <textarea name="street_address" id="adm_street" required placeholder="e.g. No. 45, Jalan Kemuning 3, Taman Desa Bakti"
                                  class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm textarea-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20" rows="2"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Postal Code <span class="text-rose-500">*</span></label>
                        <input type="text" name="postal_code" id="adm_postal" required placeholder="e.g. 43000" maxlength="10"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                               oninput="autoDetectPostal(this.value)">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">City <span class="text-rose-500">*</span></label>
                        <input type="text" name="city" id="adm_city" required placeholder="e.g. Kajang"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">State <span class="text-rose-500">*</span></label>
                        <select name="state" id="adm_state" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="Selangor">Selangor</option>
                            <option value="Kuala Lumpur">WP Kuala Lumpur</option>
                            <option value="Putrajaya">WP Putrajaya</option>
                            <option value="Johor">Johor</option>
                            <option value="Kedah">Kedah</option>
                            <option value="Kelantan">Kelantan</option>
                            <option value="Melaka">Melaka</option>
                            <option value="Negeri Sembilan">Negeri Sembilan</option>
                            <option value="Pahang">Pahang</option>
                            <option value="Perak">Perak</option>
                            <option value="Perlis">Perlis</option>
                            <option value="Pulau Pinang">Pulau Pinang</option>
                            <option value="Sabah">Sabah</option>
                            <option value="Sarawak">Sarawak</option>
                            <option value="Terengganu">Terengganu</option>
                            <option value="Labuan">WP Labuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">House / Dwelling Type <span class="text-rose-500">*</span></label>
                        <select name="house_type" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="Landed">Landed / Teres</option>
                            <option value="Apartment">Apartment / Kondominium</option>
                            <option value="Rental">Rental / Sewa</option>
                            <option value="Owned">Owned / Milik Sendiri</option>
                            <option value="PPS">Pusat Pemindahan (PPS)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- STEP 2: HEAD OF HOUSEHOLD -->
            <div id="categoryStep2" class="space-y-4 hidden">
                <div class="bg-primary/5 border-l-4 border-primary p-4 rounded-2xl">
                    <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">person</span>
                        <span>Step 2: Head of Household Profile</span>
                    </h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Full Legal Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="head_name" id="adm_head_name" required placeholder="e.g. Ahmad bin Abdullah"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">National ID / MyKad (12 Digit) <span class="text-rose-500">*</span></label>
                        <input type="text" name="head_ic" id="adm_head_ic" required placeholder="e.g. 850714105431" maxlength="14"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                               oninput="autoDetectHeadIC(this.value)">
                        <div class="text-[11px] text-on-surface-variant mt-1" id="head_ic_feedback">12 digits without hyphen</div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Phone Number <span class="text-rose-500">*</span></label>
                        <input type="tel" name="head_phone" id="adm_head_phone" required placeholder="e.g. 0123456789"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Email Address</label>
                        <input type="email" name="head_email" id="adm_head_email" placeholder="e.g. ahmad@example.com"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                </div>
            </div>

            <!-- STEP 3: MEMBERS, VULNERABILITY, CHRONIC & SCREENING -->
            <div id="categoryStep3" class="space-y-4 hidden">
                <div class="bg-primary/5 border-l-4 border-primary p-4 rounded-2xl flex items-center justify-between">
                    <div>
                        <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">groups</span>
                            <span>Step 3: Individual Household Members & Health Profiles</span>
                        </h4>
                        <p class="text-xs text-on-surface-variant mt-0.5">Demographics, chronic diseases, acute gastro symptoms, and food history</p>
                    </div>
                    <button type="button" onclick="addAdminMemberRow()" class="inline-flex items-center gap-1 bg-primary text-on-primary text-xs font-semibold px-4 py-2 rounded-full shadow-sm hover:bg-primary/90 transition-all">
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        <span>Add Member</span>
                    </button>
                </div>
                <div id="adminMembersContainer" class="space-y-4">
                    <!-- Dynamic Member Rows will be appended here -->
                </div>
            </div>

            <!-- STEP 4: REVIEW & CONFIRMATION -->
            <div id="categoryStep4" class="space-y-5 hidden">
                <div class="bg-emerald-500/10 border-l-4 border-emerald-600 p-4 rounded-2xl">
                    <h4 class="font-headline font-bold text-sm text-emerald-700 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">verified</span>
                        <span>Step 4: Review Registration Summary</span>
                    </h4>
                    <p class="text-xs text-emerald-800/80 mt-0.5">Please verify all demographic, vulnerability, and outbreak screening data before saving.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="p-4 rounded-2xl bg-surface-container border border-outline-variant/20 space-y-1.5">
                        <div class="font-bold text-primary uppercase tracking-wider mb-2">Dwelling Information</div>
                        <div><strong>Address:</strong> <span id="rev_dwelling_addr">—</span></div>
                        <div><strong>Location:</strong> <span id="rev_dwelling_city">—</span></div>
                        <div><strong>House Type:</strong> <span id="rev_dwelling_type">—</span></div>
                    </div>
                    <div class="p-4 rounded-2xl bg-surface-container border border-outline-variant/20 space-y-1.5">
                        <div class="font-bold text-primary uppercase tracking-wider mb-2">Head of Household</div>
                        <div><strong>Name:</strong> <span id="rev_head_name">—</span></div>
                        <div><strong>National IC:</strong> <span id="rev_head_ic">—</span></div>
                        <div><strong>Phone:</strong> <span id="rev_head_phone">—</span></div>
                    </div>
                </div>

                <div class="border border-outline-variant/20 rounded-2xl overflow-hidden">
                    <div class="p-3.5 bg-surface-container-low flex items-center justify-between font-bold text-xs">
                        <span>Members to Register (<span id="rev_mem_count">0</span>)</span>
                        <span class="text-primary cursor-pointer hover:underline" onclick="jumpToStep(3)">Edit Members</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-surface-container text-on-surface-variant text-[11px] uppercase tracking-wider">
                                <tr>
                                    <th class="py-2.5 px-3">#</th>
                                    <th class="py-2.5 px-3">Name</th>
                                    <th class="py-2.5 px-3">IC / ID</th>
                                    <th class="py-2.5 px-3">Relation</th>
                                    <th class="py-2.5 px-3">Gender / Age</th>
                                    <th class="py-2.5 px-3">Vulnerabilities</th>
                                    <th class="py-2.5 px-3">Outbreak Symptoms</th>
                                </tr>
                            </thead>
                            <tbody id="rev_members_table_body" class="divide-y divide-outline-variant/10">
                                <!-- Injected by buildReviewStep() -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="pt-4 border-t border-outline-variant/20 flex items-center justify-between shrink-0">
                <button type="button" id="prevBtn" onclick="prevStep()" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-on-surface-variant hover:text-on-surface bg-surface-container hover:bg-surface-container-high rounded-full transition-colors hidden">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    <span>Back</span>
                </button>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" id="nextBtn" onclick="nextStep()" class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-semibold bg-primary hover:bg-primary/90 text-on-primary rounded-full shadow-sm transition-all">
                        <span>Continue</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </button>
                    <button type="submit" id="submitRegBtn" class="inline-flex items-center gap-1.5 px-6 py-2.5 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-full shadow-md transition-all hidden">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        <span id="submitRegBtnText">Register Household</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================= -->
<!-- MODAL: VIEW HOUSEHOLD DETAILS (CENTERED & UPDATED SCHEMA) -->
<!-- ============================================================= -->
<div id="viewDetailsModal" class="fixed inset-0 z-[99999] hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6" style="display: none;" onclick="closeViewModal()">
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] w-full max-w-4xl shadow-2xl overflow-hidden max-h-[92vh] flex flex-col m-auto text-left transform transition-all animate-scale-up" onclick="event.stopPropagation()">
        <div class="px-6 py-5 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[24px]">visibility</span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-lg text-on-surface" id="viewModalTitle">Household Details</h3>
                    <p class="text-xs text-on-surface-variant" id="viewModalSubtitle">Comprehensive household profile</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="editHouseholdFromModal()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary text-xs font-bold rounded-full transition-colors" title="Edit Household Information">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                    <span>Edit</span>
                </button>
                <button type="button" onclick="closeViewModal()" class="text-on-surface-variant hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors">
                    <span class="material-symbols-outlined text-[22px]">close</span>
                </button>
            </div>
        </div>

        <div class="overflow-y-auto p-6 space-y-6 flex-1 text-sm" id="viewModalBody">
            <!-- Injected dynamically -->
        </div>

        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/20 flex items-center justify-between shrink-0">
            <button type="button" onclick="editHouseholdFromModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary/90 text-on-primary text-xs font-bold rounded-full shadow-sm transition-colors">
                <span class="material-symbols-outlined text-[16px]">edit</span>
                <span>Edit Household Information</span>
            </button>
            <button type="button" onclick="closeViewModal()" class="px-5 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold rounded-full transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
let currentStepIdx = 1;
let adminMemberCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    ['registrationModal', 'viewDetailsModal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
    });

    const form = document.getElementById('householdMultiStepForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            for (let s = 1; s <= 2; s++) {
                if (!validateAdminStep(s)) {
                    e.preventDefault();
                    jumpToStep(s);
                    return false;
                }
            }
        });
    }
});

let currentViewedHousehold = null;
let currentViewedMembers = null;

function openRegistrationModal() {
    currentViewedHousehold = null;
    currentViewedMembers = null;

    var form = document.getElementById('householdMultiStepForm');
    if (form) form.reset();

    document.getElementById('regModalTitle').innerText = 'Household & Family Registration';
    document.getElementById('household_form_action').value = 'add';
    document.getElementById('adm_household_id').value = '';
    document.getElementById('submitRegBtnText').innerText = 'Register Household';

    document.getElementById('adminMembersContainer').innerHTML = '';
    adminMemberCount = 0;
    addAdminMemberRow(true);

    var modal = document.getElementById('registrationModal');
    if (!modal) return;
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'hidden';
    var formEl = document.getElementById('householdMultiStepForm');
    if (formEl) formEl.scrollTop = 0;
    jumpToStep(1);
}

function closeRegistrationModal() {
    var modal = document.getElementById('registrationModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'auto';
}

function autoDetectPostal(postal) {
    if (postal.length === 5) {
        const p = parseInt(postal, 10);
        const stateSelect = document.getElementById('adm_state');
        if (p >= 43000 && p <= 48000) stateSelect.value = 'Selangor';
        else if (p >= 50000 && p <= 60000) stateSelect.value = 'Kuala Lumpur';
        else if (p >= 62000 && p <= 62988) stateSelect.value = 'Putrajaya';
        else if (p >= 80000 && p <= 86900) stateSelect.value = 'Johor';
        else if (p >= 15000 && p <= 18500) stateSelect.value = 'Kelantan';
        else if (p >= 10000 && p <= 14400) stateSelect.value = 'Pulau Pinang';
        else if (p >= 30000 && p <= 36810) stateSelect.value = 'Perak';
    }
}

function autoDetectHeadIC(ic) {
    const clean = ic.replace(/[^0-9]/g, '');
    const feedback = document.getElementById('head_ic_feedback');
    if (clean.length === 12) {
        const yy = clean.substring(0, 2);
        const mm = clean.substring(2, 4);
        const dd = clean.substring(4, 6);
        const year = parseInt(yy, 10) > 40 ? '19' + yy : '20' + yy;
        const lastDigit = parseInt(clean.substring(11, 12), 10);
        const gender = (lastDigit % 2 === 0) ? 'Female' : 'Male';
        feedback.innerHTML = `<span class="text-emerald-600 font-semibold">✓ DOB: ${dd}/${mm}/${year} • Gender: ${gender}</span>`;
    } else {
        feedback.innerHTML = `12 digits without hyphen`;
    }
}

function autoDetectMemberIC(idx, ic) {
    const clean = ic.replace(/[^0-9]/g, '');
    if (clean.length === 12) {
        const yy = clean.substring(0, 2);
        const mm = clean.substring(2, 4);
        const dd = clean.substring(4, 6);
        const year = parseInt(yy, 10) > 40 ? '19' + yy : '20' + yy;
        const dobField = document.getElementById(`m_dob_${idx}`);
        if (dobField) dobField.value = `${year}-${mm}-${dd}`;

        const birthYear = parseInt(year, 10);
        const currentYear = new Date().getFullYear();
        const ageField = document.getElementById(`m_age_${idx}`);
        if (ageField) ageField.value = Math.max(0, currentYear - birthYear);

        const lastDigit = parseInt(clean.substring(11, 12), 10);
        const genderSelect = document.getElementById(`m_gender_${idx}`);
        if (genderSelect) genderSelect.value = (lastDigit % 2 === 0) ? 'Female' : 'Male';

        // Auto-check infant or senior checkboxes based on age
        const age = currentYear - birthYear;
        const infantCb = document.getElementById(`m_v_infant_${idx}`);
        const seniorCb = document.getElementById(`m_v_senior_${idx}`);
        if (infantCb) infantCb.checked = (age <= 5);
        if (seniorCb) seniorCb.checked = (age >= 60);
    }
}

function validateAdminStep(step) {
    if (step === 1) {
        const street = document.getElementById('adm_street')?.value.trim();
        const postal = document.getElementById('adm_postal')?.value.trim();
        const city = document.getElementById('adm_city')?.value.trim();
        if (!street || !postal || !city) {
            alert('Please fill in required dwelling information (Street Address, Postal Code, and City).');
            return false;
        }
    } else if (step === 2) {
        const headName = document.getElementById('adm_head_name')?.value.trim();
        const headIC = document.getElementById('adm_head_ic')?.value.trim();
        const headPhone = document.getElementById('adm_head_phone')?.value.trim();
        if (!headName || !headIC || !headPhone) {
            alert('Please complete required Head of Household information (Full Name, National ID/MyKad, and Phone Number).');
            return false;
        }
    }
    return true;
}

function buildReviewStep() {
    document.getElementById('rev_dwelling_addr').innerText = document.getElementById('adm_street')?.value || '—';
    document.getElementById('rev_dwelling_city').innerText = (document.getElementById('adm_postal')?.value + ' ' + document.getElementById('adm_city')?.value + ', ' + document.getElementById('adm_state')?.value) || '—';
    document.getElementById('rev_dwelling_type').innerText = document.querySelector('select[name="house_type"]')?.value || '—';

    document.getElementById('rev_head_name').innerText = document.getElementById('adm_head_name')?.value || '—';
    document.getElementById('rev_head_ic').innerText = document.getElementById('adm_head_ic')?.value || '—';
    document.getElementById('rev_head_phone').innerText = document.getElementById('adm_head_phone')?.value || '—';

    const tbody = document.getElementById('rev_members_table_body');
    tbody.innerHTML = '';
    const boxes = document.querySelectorAll('#adminMembersContainer .member-card-box');
    document.getElementById('rev_mem_count').innerText = boxes.length;

    boxes.forEach((box, i) => {
        const name = box.querySelector(`[name*="[full_name]"]`)?.value || '—';
        const ic = box.querySelector(`[name*="[national_id]"]`)?.value || '—';
        const relation = box.querySelector(`[name*="[relationship_to_head]"]`)?.value || '—';
        const gender = box.querySelector(`[name*="[gender]"]`)?.value || '—';
        const age = box.querySelector(`[name*="[age]"]`)?.value || '—';
        
        // Checkboxes check
        const vulns = [];
        if (box.querySelector(`[name*="[vulnerable_infant_under5]"]`)?.checked) vulns.push('Infant <5');
        if (box.querySelector(`[name*="[vulnerable_senior_60plus]"]`)?.checked) vulns.push('Senior 60+');
        if (box.querySelector(`[name*="[vulnerable_pregnant_mother]"]`)?.checked) vulns.push('Pregnant');
        if (box.querySelector(`[name*="[vulnerable_disability_oku]"]`)?.checked) vulns.push('OKU');
        if (box.querySelector(`[name*="[vulnerable_bedridden]"]`)?.checked) vulns.push('Bedridden');

        const symptoms = [];
        if (box.querySelector(`[name*="[has_diarrhea]"]`)?.checked) symptoms.push('Diarrhea');
        if (box.querySelector(`[name*="[has_vomiting]"]`)?.checked) symptoms.push('Vomiting');
        if (box.querySelector(`[name*="[has_fever]"]`)?.checked) symptoms.push('Fever');
        if (box.querySelector(`[name*="[is_affected_member]"]`)?.checked && symptoms.length === 0) symptoms.push('Affected');

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="py-2 px-3 font-semibold">${i + 1}</td>
            <td class="py-2 px-3 font-medium">${name}</td>
            <td class="py-2 px-3 font-mono">${ic}</td>
            <td class="py-2 px-3">${relation}</td>
            <td class="py-2 px-3">${gender} (${age} yrs)</td>
            <td class="py-2 px-3">
                ${vulns.length > 0 ? vulns.map(v => `<span class="inline-block bg-amber-500/10 text-amber-700 px-1.5 py-0.5 rounded text-[10px] font-semibold mr-1">${v}</span>`).join('') : '<span class="text-on-surface-variant">None</span>'}
            </td>
            <td class="py-2 px-3">
                ${symptoms.length > 0 ? symptoms.map(s => `<span class="inline-block bg-rose-500/10 text-rose-600 px-1.5 py-0.5 rounded text-[10px] font-bold mr-1">${s}</span>`).join('') : '<span class="text-emerald-600 font-semibold">✓ Clear</span>'}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function jumpToStep(step) {
    if (step > currentStepIdx && !validateAdminStep(currentStepIdx)) return;
    currentStepIdx = step;
    for (let i = 1; i <= 4; i++) {
        const sec = document.getElementById('categoryStep' + i);
        const circle = document.getElementById('stepCircle' + i);
        const label = document.getElementById('stepLabel' + i);
        if (sec) sec.classList.toggle('hidden', i !== step);
        if (circle) {
            if (i === step) {
                circle.className = 'w-10 h-10 rounded-full bg-primary text-on-primary font-bold flex items-center justify-center text-sm shadow-md transition-all';
                label.className = 'text-xs font-bold text-primary mt-1.5 line-clamp-1';
            } else if (i < step) {
                circle.className = 'w-10 h-10 rounded-full bg-emerald-600 text-white font-bold flex items-center justify-center text-sm transition-all';
                label.className = 'text-xs font-medium text-emerald-600 mt-1.5 line-clamp-1';
            } else {
                circle.className = 'w-10 h-10 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-sm transition-all';
                label.className = 'text-xs font-medium text-on-surface-variant mt-1.5 line-clamp-1';
            }
        }
    }

    if (step === 4) {
        buildReviewStep();
    }

    document.getElementById('stepProgressBar').style.width = (step * 25) + '%';
    document.getElementById('prevBtn').classList.toggle('hidden', step === 1);
    document.getElementById('nextBtn').classList.toggle('hidden', step === 4);
    document.getElementById('submitRegBtn').classList.toggle('hidden', step !== 4);
}

function nextStep() {
    if (!validateAdminStep(currentStepIdx)) return;
    if (currentStepIdx === 2) {
        // Automatically sync Head of Household info to Member #1 if empty
        const headName = document.getElementById('adm_head_name')?.value.trim() || '';
        const headIC = document.getElementById('adm_head_ic')?.value.trim() || '';
        const m1Name = document.querySelector('input[name="members[0][full_name]"]');
        const m1IC = document.querySelector('input[name="members[0][national_id]"]');
        if (m1Name && !m1Name.value.trim()) m1Name.value = headName;
        if (m1IC && !m1IC.value.trim()) {
            m1IC.value = headIC;
            autoDetectMemberIC(0, headIC);
        }
    }
    if (currentStepIdx < 4) jumpToStep(currentStepIdx + 1);
}

function prevStep() {
    if (currentStepIdx > 1) jumpToStep(currentStepIdx - 1);
}

function addAdminMemberRow(isHead = false) {
    const idx = adminMemberCount++;
    const container = document.getElementById('adminMembersContainer');
    const box = document.createElement('div');
    box.className = 'member-card-box p-5 rounded-[24px] bg-surface-container-low border border-outline-variant/30 space-y-4';
    box.id = `adminMem_${idx}`;

    box.innerHTML = `
        <div class="flex items-center justify-between pb-3 border-b border-outline-variant/20">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-full bg-primary text-on-primary text-xs font-bold flex items-center justify-center">${idx + 1}</span>
                <span class="text-xs font-bold text-on-surface">Member #${idx + 1} ${isHead ? '<span class="text-primary font-semibold">(Head of Household)</span>' : ''}</span>
            </div>
            ${!isHead ? `<button type="button" onclick="document.getElementById('adminMem_${idx}').remove()" class="text-rose-500 text-xs font-semibold hover:underline flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">delete</span><span>Remove</span></button>` : ''}
        </div>

        <!-- Section 1: Demographics -->
        <div class="space-y-3">
            <div class="text-[11px] font-bold text-primary uppercase tracking-wider">1. Personal Demographics & Identification</div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Full Legal Name *</label>
                    <input type="text" name="members[${idx}][full_name]" required class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">National ID / MyKad / MyKid *</label>
                    <input type="text" name="members[${idx}][national_id]" required class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none" oninput="autoDetectMemberIC(${idx}, this.value)">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Date of Birth</label>
                    <input type="date" name="members[${idx}][date_of_birth]" id="m_dob_${idx}" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Age (Years)</label>
                    <input type="number" name="members[${idx}][age]" id="m_age_${idx}" placeholder="e.g. 35" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Gender *</label>
                    <select name="members[${idx}][gender]" id="m_gender_${idx}" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Relationship to Head *</label>
                    <select name="members[${idx}][relationship_to_head]" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                        <option value="Head" ${isHead ? 'selected' : ''}>Head (Ketua)</option>
                        <option value="Spouse">Spouse (Pasangan)</option>
                        <option value="Child" ${!isHead ? 'selected' : ''}>Child (Anak)</option>
                        <option value="Mother">Mother (Ibu)</option>
                        <option value="Father">Father (Bapa)</option>
                        <option value="Grandfather">Grandfather (Datuk)</option>
                        <option value="Grandmother">Grandmother (Nenek)</option>
                        <option value="Relative">Relative (Saudara)</option>
                        <option value="Others">Others (Lain-lain)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Marital Status</label>
                    <select name="members[${idx}][marital_status]" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                        <option value="Single">Single (Bujang)</option>
                        <option value="Married" ${isHead ? 'selected' : ''}>Married (Berkahwin)</option>
                        <option value="Divorced">Divorced (Bercerai)</option>
                        <option value="Widowed">Widowed (Balu/Duda)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Citizenship</label>
                    <input type="text" name="members[${idx}][citizenship_status]" value="Warganegara" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Education Level</label>
                    <select name="members[${idx}][education_level]" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                        <option value="Secondary">Secondary (Menengah)</option>
                        <option value="Primary">Primary (Rendah)</option>
                        <option value="Tertiary">Tertiary (Diploma / Ijazah)</option>
                        <option value="Post-Graduate">Post-Graduate (Master / PhD)</option>
                        <option value="No Formal Education">No Formal Education</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Employment Status</label>
                    <select name="members[${idx}][employment_status]" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                        <option value="Employed">Employed (Bekerja)</option>
                        <option value="Self-Employed">Self-Employed (Peniaga/Sendiri)</option>
                        <option value="Unemployed">Unemployed (Menganggur)</option>
                        <option value="Student">Student (Pelajar)</option>
                        <option value="Retired">Retired (Bersara)</option>
                        <option value="Homemaker">Homemaker (Suri Rumah)</option>
                        <option value="Informal">Informal Sector</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Healthcare Coverage</label>
                    <select name="members[${idx}][healthcare_coverage]" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                        <option value="KKM / Kerajaan">KKM / Kerajaan</option>
                        <option value="Insurans Swasta">Insurans Swasta</option>
                        <option value="PERKESO / SOCSO">PERKESO / SOCSO</option>
                        <option value="Ditanggung Majikan">Ditanggung Majikan</option>
                        <option value="Tiada">Tiada Liputan</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 2: Vulnerability Profile -->
        <div class="space-y-2 pt-3 border-t border-outline-variant/15">
            <div class="text-[11px] font-bold text-amber-700 uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">shield</span>
                <span>2. Vulnerable & High-Risk Categories</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2.5 text-xs">
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][vulnerable_infant_under5]" id="m_v_infant_${idx}" value="1" class="rounded text-primary focus:ring-primary">
                    <span class="text-[11px] font-medium">Infant (<5 yrs)</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][vulnerable_senior_60plus]" id="m_v_senior_${idx}" value="1" class="rounded text-primary focus:ring-primary">
                    <span class="text-[11px] font-medium">Senior (60+)</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][vulnerable_pregnant_mother]" value="1" class="rounded text-primary focus:ring-primary">
                    <span class="text-[11px] font-medium">Pregnant</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][vulnerable_disability_oku]" value="1" class="rounded text-primary focus:ring-primary">
                    <span class="text-[11px] font-medium">OKU / Disability</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][vulnerable_bedridden]" value="1" class="rounded text-primary focus:ring-primary">
                    <span class="text-[11px] font-medium">Bedridden</span>
                </label>
            </div>
        </div>

        <!-- Section 3: Chronic Conditions & Allergies -->
        <div class="space-y-2 pt-3 border-t border-outline-variant/15">
            <div class="text-[11px] font-bold text-rose-600 uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">medical_services</span>
                <span>3. Chronic Diseases & Allergies</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][chronic_diabetes]" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                    <span class="text-[11px] font-medium">Diabetes</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][chronic_hypertension]" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                    <span class="text-[11px] font-medium">Hypertension</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][chronic_kidney_disease]" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                    <span class="text-[11px] font-medium">Kidney Disease</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][chronic_gastric_intestinal]" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                    <span class="text-[11px] font-medium">Gastric / Intestinal</span>
                </label>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Other Chronic Illness</label>
                    <input type="text" name="members[${idx}][chronic_other]" placeholder="e.g. Asma, Jantung, Tiada" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Drug Allergies (Ubat)</label>
                    <input type="text" name="members[${idx}][drug_allergies]" placeholder="e.g. Penicillin, Tiada" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Food Allergies (Makanan)</label>
                    <input type="text" name="members[${idx}][food_allergies]" placeholder="e.g. Makanan laut, Kacang, Tiada" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Section 4: Acute Outbreak Symptoms (Past 3 Days) -->
        <div class="space-y-2 pt-3 border-t border-outline-variant/15">
            <div class="text-[11px] font-bold text-red-600 uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">coronavirus</span>
                <span>4. Health Screening & Acute Gastro Symptoms (Past 3 Days)</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][has_diarrhea]" value="1" class="rounded text-red-600 focus:ring-red-500">
                    <span class="text-[11px] font-medium text-red-700">Diarrhea (Cirit-birit)</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][has_vomiting]" value="1" class="rounded text-red-600 focus:ring-red-500">
                    <span class="text-[11px] font-medium text-red-700">Vomiting (Muntah)</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][has_fever]" value="1" class="rounded text-red-600 focus:ring-red-500">
                    <span class="text-[11px] font-medium text-red-700">Fever (Demam)</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container">
                    <input type="checkbox" name="members[${idx}][is_affected_member]" value="1" class="rounded text-red-600 focus:ring-red-500">
                    <span class="text-[11px] font-medium text-red-700">Is Affected Member</span>
                </label>
            </div>
            <div class="pt-1">
                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Symptom Onset Date</label>
                <input type="date" name="members[${idx}][symptom_onset_date]" class="w-full sm:w-1/2 bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
            </div>
        </div>

        <!-- Section 5: Food Exposure & Meal History -->
        <div class="space-y-2 pt-3 border-t border-outline-variant/15">
            <div class="text-[11px] font-bold text-primary uppercase tracking-wider flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[16px]">restaurant</span>
                <span>5. Food Exposure & Meal History (Epidemiological Tracking)</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Shared Outside Food?</label>
                    <select name="members[${idx}][shared_outside_food]" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                        <option value="Not Applicable">Not Applicable</option>
                        <option value="Yes">Yes (Makan Luar)</option>
                        <option value="No">No (Makan di Rumah)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Outside Food Location / Notes</label>
                    <input type="text" name="members[${idx}][outside_food_notes]" placeholder="e.g. Gerai Pasar Malam, Restoran ABC" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Meal Type / Menu</label>
                    <input type="text" name="members[${idx}][meal_type]" placeholder="e.g. Nasi Ayam, Sambal Sotong" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container text-xs">
                    <input type="checkbox" name="members[${idx}][shared_feast_meal]" value="1" class="rounded text-primary focus:ring-primary">
                    <span class="text-[11px] font-medium">Attended Kenduri / Feast / Banquet</span>
                </label>
                <label class="flex items-center gap-2 p-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 cursor-pointer hover:bg-surface-container text-xs">
                    <input type="checkbox" name="members[${idx}][shared_same_meal_before_symptom]" value="1" class="rounded text-primary focus:ring-primary">
                    <span class="text-[11px] font-medium">Shared same meal before symptom onset</span>
                </label>
            </div>
        </div>
    `;
    container.appendChild(box);
}

function searchHouseholdTable() {
    const q = document.getElementById('householdSearchInput').value.toLowerCase();
    const type = document.getElementById('typeFilter').value;
    const rows = document.querySelectorAll('.household-table-row');

    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const rType = r.getAttribute('data-type') || '';
        const matchQ = text.includes(q);
        const matchT = !type || rType === type;
        r.style.display = (matchQ && matchT) ? '' : 'none';
    });
}

function viewHouseholdModal(h, members) {
    currentViewedHousehold = h;
    currentViewedMembers = members;

    document.getElementById('viewModalTitle').innerText = `#HH-${String(h.household_id).padStart(5, '0')} — ${h.head_name || 'Household Details'}`;
    document.getElementById('viewModalSubtitle').innerText = `${h.street_address}, ${h.postal_code} ${h.city}, ${h.state}`;

    let membersHtml = '';
    if (members && members.length > 0) {
        membersHtml = members.map((m, i) => {
            const vTags = [];
            if (m.vulnerable_infant_under5 == 1) vTags.push('<span class="bg-blue-500/10 text-blue-600 px-1.5 py-0.5 rounded text-[10px] font-semibold">Infant</span>');
            if (m.vulnerable_senior_60plus == 1) vTags.push('<span class="bg-amber-500/10 text-amber-700 px-1.5 py-0.5 rounded text-[10px] font-semibold">Senior 60+</span>');
            if (m.vulnerable_pregnant_mother == 1) vTags.push('<span class="bg-pink-500/10 text-pink-600 px-1.5 py-0.5 rounded text-[10px] font-semibold">Pregnant</span>');
            if (m.vulnerable_disability_oku == 1) vTags.push('<span class="bg-purple-500/10 text-purple-700 px-1.5 py-0.5 rounded text-[10px] font-semibold">OKU</span>');
            if (m.vulnerable_bedridden == 1) vTags.push('<span class="bg-rose-500/10 text-rose-700 px-1.5 py-0.5 rounded text-[10px] font-semibold">Bedridden</span>');

            const cTags = [];
            if (m.chronic_diabetes == 1) cTags.push('Diabetes');
            if (m.chronic_hypertension == 1) cTags.push('Hypertension');
            if (m.chronic_kidney_disease == 1) cTags.push('Kidney');
            if (m.chronic_gastric_intestinal == 1) cTags.push('Gastric');
            if (m.chronic_other && m.chronic_other !== 'Tiada') cTags.push(m.chronic_other);

            const sTags = [];
            if (m.has_diarrhea == 1) sTags.push('Diarrhea');
            if (m.has_vomiting == 1) sTags.push('Vomiting');
            if (m.has_fever == 1) sTags.push('Fever');
            if (m.is_affected_member == 1 && sTags.length === 0) sTags.push('Affected');

            return `
                <div class="p-4 rounded-2xl bg-surface-container border border-outline-variant/30 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-outline-variant/20 pb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-primary text-on-primary text-xs font-bold flex items-center justify-center">${i + 1}</span>
                            <span class="font-bold text-xs text-on-surface">${m.full_name}</span>
                            <span class="font-mono text-xs text-on-surface-variant">(${m.national_id})</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="px-2 py-0.5 rounded-full bg-surface-container-lowest font-semibold">${m.relationship_to_head}</span>
                            <span class="text-on-surface-variant">${m.gender} • ${m.age || '—'} yrs</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                        <div><span class="text-on-surface-variant">Status:</span> ${m.marital_status} • ${m.employment_status}</div>
                        <div><span class="text-on-surface-variant">Education:</span> ${m.education_level}</div>
                        <div><span class="text-on-surface-variant">Coverage:</span> ${m.healthcare_coverage || 'KKM'}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs pt-1 border-t border-outline-variant/15">
                        <div>
                            <div class="font-semibold text-amber-700 mb-1">Vulnerabilities:</div>
                            <div>${vTags.length > 0 ? vTags.join(' ') : '<span class="text-on-surface-variant">None</span>'}</div>
                        </div>
                        <div>
                            <div class="font-semibold text-rose-600 mb-1">Chronic & Allergies:</div>
                            <div class="text-[11px]">${cTags.length > 0 ? cTags.join(', ') : 'None'} ${m.drug_allergies ? '<br><span class="text-rose-500">Ubat: ' + m.drug_allergies + '</span>' : ''}</div>
                        </div>
                        <div>
                            <div class="font-semibold text-red-600 mb-1">Outbreak Symptoms:</div>
                            <div>
                                ${sTags.length > 0 ? `<span class="bg-red-500/10 text-red-600 font-bold px-2 py-0.5 rounded">${sTags.join(', ')}</span>` : '<span class="text-emerald-600 font-semibold">✓ Clear</span>'}
                                ${m.symptom_onset_date ? `<div class="text-[11px] text-on-surface-variant mt-0.5">Onset: ${m.symptom_onset_date}</div>` : ''}
                            </div>
                        </div>
                    </div>

                    ${(m.shared_outside_food !== 'Not Applicable' || m.shared_feast_meal == 1 || m.shared_same_meal_before_symptom == 1 || m.meal_type) ? `
                        <div class="p-2.5 rounded-xl bg-surface-container-lowest text-xs text-on-surface-variant space-y-1">
                            <div class="font-semibold text-primary text-[11px]">Food Exposure Record:</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 text-[11px]">
                                <div>Outside Food: <strong>${m.shared_outside_food}</strong> ${m.outside_food_notes ? '(' + m.outside_food_notes + ')' : ''}</div>
                                <div>Meal Type: <strong>${m.meal_type || '—'}</strong></div>
                                <div>Kenduri / Feast: <strong>${m.shared_feast_meal == 1 ? 'Yes' : 'No'}</strong></div>
                                <div>Shared Meal: <strong>${m.shared_same_meal_before_symptom == 1 ? 'Yes' : 'No'}</strong></div>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');
    } else {
        membersHtml = `<div class="p-6 text-center text-on-surface-variant text-xs">No individual member records found.</div>`;
    }

    const html = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 rounded-2xl bg-surface-container border border-outline-variant/20">
                <h5 class="text-xs font-bold text-primary uppercase tracking-wider mb-2">Dwelling & Location</h5>
                <div class="text-xs space-y-1">
                    <div><strong>Address:</strong> ${h.street_address}</div>
                    <div><strong>Postal & City:</strong> ${h.postal_code} ${h.city}, ${h.state}</div>
                    <div><strong>House Type:</strong> ${h.house_type}</div>
                    <div><strong>Total Residents:</strong> ${members.length} persons</div>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-surface-container border border-outline-variant/20">
                <h5 class="text-xs font-bold text-primary uppercase tracking-wider mb-2">Head of Household</h5>
                <div class="text-xs space-y-1">
                    <div><strong>Name:</strong> ${h.head_name || '—'}</div>
                    <div><strong>National IC:</strong> ${h.head_ic || '—'}</div>
                    <div><strong>Phone:</strong> ${h.head_phone || '—'}</div>
                    <div><strong>Email:</strong> ${h.head_email || '—'}</div>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <h5 class="text-xs font-bold text-on-surface uppercase tracking-wider">Registered Household Members (${members.length})</h5>
            <div class="space-y-3">
                ${membersHtml}
            </div>
        </div>
    `;

    document.getElementById('viewModalBody').innerHTML = html;
    var modal = document.getElementById('viewDetailsModal');
    if (!modal) return;
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    var bodyEl = document.getElementById('viewModalBody');
    if (bodyEl) bodyEl.scrollTop = 0;
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'hidden';
}

function editHouseholdFromModal() {
    if (currentViewedHousehold) {
        openEditHouseholdModal(currentViewedHousehold, currentViewedMembers);
    }
}

function openEditHouseholdModal(h, members) {
    if (!h) return;
    currentViewedHousehold = h;
    currentViewedMembers = members;
    closeViewModal();

    var form = document.getElementById('householdMultiStepForm');
    if (form) form.reset();

    const hhCode = '#HH-' + String(h.household_id).padStart(5, '0');
    document.getElementById('regModalTitle').innerText = `Edit Household (${hhCode} — ${h.head_name || ''})`;
    document.getElementById('household_form_action').value = 'edit';
    document.getElementById('adm_household_id').value = h.household_id;
    document.getElementById('submitRegBtnText').innerText = 'Update Household';

    // Step 1: Dwelling
    document.getElementById('adm_street').value = h.street_address || '';
    document.getElementById('adm_postal').value = h.postal_code || '';
    document.getElementById('adm_city').value = h.city || '';
    document.getElementById('adm_state').value = h.state || 'Selangor';
    const houseTypeSelect = document.querySelector('select[name="house_type"]');
    if (houseTypeSelect) houseTypeSelect.value = h.house_type || 'Landed';

    // Step 2: Head of Household
    document.getElementById('adm_head_name').value = h.head_name || '';
    document.getElementById('adm_head_ic').value = h.head_ic || '';
    document.getElementById('adm_head_phone').value = h.head_phone || '';
    document.getElementById('adm_head_email').value = h.head_email || '';

    // Step 3: Members
    document.getElementById('adminMembersContainer').innerHTML = '';
    adminMemberCount = 0;

    if (members && members.length > 0) {
        members.forEach((m, i) => {
            addAdminMemberRow(i === 0);
            const idx = i;
            
            // Personal
            const nameEl = document.querySelector(`input[name="members[${idx}][full_name]"]`);
            if (nameEl) nameEl.value = m.full_name || '';
            const icEl = document.querySelector(`input[name="members[${idx}][national_id]"]`);
            if (icEl) icEl.value = m.national_id || '';
            const dobEl = document.getElementById(`m_dob_${idx}`);
            if (dobEl) dobEl.value = m.date_of_birth || '';
            const ageEl = document.getElementById(`m_age_${idx}`);
            if (ageEl) ageEl.value = m.age || '';
            const genEl = document.getElementById(`m_gender_${idx}`);
            if (genEl) genEl.value = m.gender || 'Male';
            const relEl = document.querySelector(`select[name="members[${idx}][relationship_to_head]"]`);
            if (relEl) relEl.value = m.relationship_to_head || (i === 0 ? 'Head' : 'Child');
            const marEl = document.querySelector(`select[name="members[${idx}][marital_status]"]`);
            if (marEl) marEl.value = m.marital_status || 'Single';
            const citEl = document.querySelector(`select[name="members[${idx}][citizenship_status]"]`);
            if (citEl) citEl.value = m.citizenship_status || 'Warganegara';
            const eduEl = document.querySelector(`select[name="members[${idx}][education_level]"]`);
            if (eduEl) eduEl.value = m.education_level || 'Secondary';
            const empEl = document.querySelector(`select[name="members[${idx}][employment_status]"]`);
            if (empEl) empEl.value = m.employment_status || 'Employed';
            const covEl = document.querySelector(`input[name="members[${idx}][healthcare_coverage]"]`);
            if (covEl) covEl.value = m.healthcare_coverage || 'KKM / Kerajaan';

            // Vulnerabilities
            const setCheck = (name, val) => {
                const cb = document.querySelector(`input[name="members[${idx}][${name}]"]`);
                if (cb) cb.checked = (val == 1 || val === true || val === '1');
            };
            setCheck('vulnerable_infant_under5', m.vulnerable_infant_under5);
            setCheck('vulnerable_senior_60plus', m.vulnerable_senior_60plus);
            setCheck('vulnerable_pregnant_mother', m.vulnerable_pregnant_mother);
            setCheck('vulnerable_disability_oku', m.vulnerable_disability_oku);
            setCheck('vulnerable_bedridden', m.vulnerable_bedridden);

            // Chronic & Allergies
            setCheck('chronic_diabetes', m.chronic_diabetes);
            setCheck('chronic_hypertension', m.chronic_hypertension);
            setCheck('chronic_kidney_disease', m.chronic_kidney_disease);
            setCheck('chronic_gastric_intestinal', m.chronic_gastric_intestinal);
            const chrOther = document.querySelector(`input[name="members[${idx}][chronic_other]"]`);
            if (chrOther) chrOther.value = m.chronic_other || '';
            const drugAll = document.querySelector(`input[name="members[${idx}][drug_allergies]"]`);
            if (drugAll) drugAll.value = m.drug_allergies || '';
            const foodAll = document.querySelector(`input[name="members[${idx}][food_allergies]"]`);
            if (foodAll) foodAll.value = m.food_allergies || '';

            // Symptoms
            setCheck('has_diarrhea', m.has_diarrhea);
            setCheck('has_vomiting', m.has_vomiting);
            setCheck('has_fever', m.has_fever);
            setCheck('is_affected_member', m.is_affected_member);
            const onsetEl = document.querySelector(`input[name="members[${idx}][symptom_onset_date]"]`);
            if (onsetEl) onsetEl.value = m.symptom_onset_date || '';

            // Food Exposure
            const outFood = document.querySelector(`select[name="members[${idx}][shared_outside_food]"]`);
            if (outFood) outFood.value = m.shared_outside_food || 'Not Applicable';
            const outNotes = document.querySelector(`input[name="members[${idx}][outside_food_notes]"]`);
            if (outNotes) outNotes.value = m.outside_food_notes || '';
            const mealType = document.querySelector(`input[name="members[${idx}][meal_type]"]`);
            if (mealType) mealType.value = m.meal_type || '';
            setCheck('shared_feast_meal', m.shared_feast_meal);
            setCheck('shared_same_meal_before_symptom', m.shared_same_meal_before_symptom);
        });
    } else {
        addAdminMemberRow(true);
    }

    var modal = document.getElementById('registrationModal');
    if (!modal) return;
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'hidden';
    var formEl = document.getElementById('householdMultiStepForm');
    if (formEl) formEl.scrollTop = 0;
    jumpToStep(1);
}

function closeViewModal() {
    var modal = document.getElementById('viewDetailsModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'auto';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
