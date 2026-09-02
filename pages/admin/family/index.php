<?php
/**
 * ============================================================================
 *   SeDaP 2.0 — Admin Family & Household Management Module (Pengurusan Isi Rumah)
 *   Matching SQL Schema:
 *   1. Household Table
 *   2. HeadOfHousehold Table
 *   3. Member Table
 *   4. HouseholdFinance Table
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
    fputcsv($output, ['Household ID', 'Head Name', 'Head IC', 'Head Phone', 'Head Email', 'Address', 'Postal Code', 'City', 'State', 'House Type', 'Total Residents', 'Gross Income (RM)', 'Rent/Mortgage', 'Utilities', 'Education', 'Medical Costs', 'Date Registered']);
    
    $stmt = $pdo->query("
        SELECT h.*, head.full_name as head_name, head.phone_number as head_phone, head.email as head_email, head.ic_number as head_ic,
               f.gross_household_income, f.rent_mortgage, f.utilities, f.education_fees, f.medical_costs
        FROM Household h
        LEFT JOIN HeadOfHousehold head ON h.household_id = head.household_id
        LEFT JOIN HouseholdFinance f ON h.household_id = f.household_id
        ORDER BY h.household_id DESC
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
            $row['total_residents'],
            $row['gross_household_income'] ?? '0.00',
            $row['rent_mortgage'] ?? '0.00',
            $row['utilities'] ?? '0.00',
            $row['education_fees'] ?? '0.00',
            $row['medical_costs'] ?? '0.00',
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
// 3. Handle New Household Registration Submission
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    try {
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

        $gross_income    = floatval($_POST['gross_income'] ?? 0.00);
        $rent_mortgage   = floatval($_POST['rent_mortgage'] ?? 0.00);
        $utilities       = floatval($_POST['utilities'] ?? 0.00);
        $education_fees  = floatval($_POST['education_fees'] ?? 0.00);
        $medical_costs   = floatval($_POST['medical_costs'] ?? 0.00);

        if (empty($street_address) || empty($postal_code) || empty($head_ic) || empty($head_name)) {
            throw new Exception("Please complete the required dwelling and head of household information.");
        }

        $pdo->beginTransaction();

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

        // 3. Insert Members
        if (!empty($membersData) && is_array($membersData)) {
            $stmtM = $pdo->prepare("
                INSERT INTO Member (
                    full_name, national_id, date_of_birth, gender, relationship_to_head,
                    marital_status, citizenship_status, education_level, employment_status,
                    chronic_condition, healthcare_coverage, vulnerable_dependent, household_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($membersData as $m) {
                $m_name       = trim($m['full_name'] ?? '');
                $m_ic         = trim($m['national_id'] ?? '');
                $m_dob        = !empty($m['date_of_birth']) ? $m['date_of_birth'] : null;
                $m_gender     = $m['gender'] ?? 'Lelaki';
                $m_relation   = $m['relationship_to_head'] ?? 'Ketua Keluarga';
                $m_marital    = $m['marital_status'] ?? 'Bujang';
                $m_citizen    = $m['citizenship_status'] ?? 'Warganegara';
                $m_edu        = $m['education_level'] ?? 'Menengah (SPM)';
                $m_job        = $m['employment_status'] ?? 'Bekerja';
                $m_chronic    = trim($m['chronic_condition'] ?? 'Tiada');
                $m_health_cov = $m['healthcare_coverage'] ?? 'KKM / Kerajaan';
                $m_vulnerable = $m['vulnerable_dependent'] ?? 'Tiada';

                if (!empty($m_name)) {
                    $stmtM->execute([
                        $m_name, $m_ic, $m_dob, $m_gender, $m_relation,
                        $m_marital, $m_citizen, $m_edu, $m_job,
                        $m_chronic, $m_health_cov, $m_vulnerable, $householdId
                    ]);
                }
            }
        }

        // 4. Insert Household Financial Info
        $stmtF = $pdo->prepare("
            INSERT INTO HouseholdFinance (
                household_id, gross_household_income, rent_mortgage,
                utilities, education_fees, medical_costs
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtF->execute([
            $householdId, $gross_income, $rent_mortgage,
            $utilities, $education_fees, $medical_costs
        ]);

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
// 4. Fetch All Households, Members & Stats
// ---------------------------------------------------------------------------
$households = [];
$totalMembersCount = 0;
$chronicCount = 0;
$b40Count = 0;

try {
    $hQuery = $pdo->query("
        SELECT h.*, head.full_name as head_name, head.phone_number as head_phone, head.email as head_email, head.ic_number as head_ic,
               f.gross_household_income, f.medical_costs, f.rent_mortgage, f.utilities, f.education_fees
        FROM Household h
        LEFT JOIN HeadOfHousehold head ON h.household_id = head.household_id
        LEFT JOIN HouseholdFinance f ON h.household_id = f.household_id
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
        if (!empty($m['chronic_condition']) && strtolower($m['chronic_condition']) !== 'tiada') {
            $chronicCount++;
        }
    }

    foreach ($households as $h) {
        if (($h['gross_household_income'] ?? 0) <= 5250) {
            $b40Count++;
        }
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
</style>

<div class="space-y-6 pb-12">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface flex items-center gap-3">
                <span class="material-symbols-outlined text-[32px] text-primary">family_restroom</span>
                <span>Family & Household Information</span>
            </h1>
            <p class="text-on-surface-variant text-sm mt-1">Community household profiling, emergency contact mapping, and vulnerability assessment</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php?export=csv" class="inline-flex items-center gap-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold px-4 py-2.5 rounded-[24px] border border-outline-variant/40 shadow-sm transition-all text-sm">
                <span class="material-symbols-outlined text-[20px]">download</span>
                <span>Export CSV</span>
            </a>
            <button onclick="openRegistrationModal()" class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-on-primary font-semibold px-5 py-2.5 rounded-[24px] shadow-sm transition-all duration-200 hover:shadow text-sm">
                <span class="material-symbols-outlined text-[20px]">add_circle</span>
                <span>New Household Registration</span>
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
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Chronic Illness Cases</span>
                <span class="w-10 h-10 rounded-2xl bg-rose-500/10 text-rose-600 flex items-center justify-center material-symbols-outlined text-[22px]">medical_services</span>
            </div>
            <div class="text-2xl font-bold font-headline text-rose-600 mt-2"><?php echo $chronicCount; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">High-risk medical tracking</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">B40 Low-Income</span>
                <span class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center material-symbols-outlined text-[22px]">savings</span>
            </div>
            <div class="text-2xl font-bold font-headline text-emerald-600 mt-2"><?php echo $b40Count; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">Eligible for special subsidies</div>
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
                        <th class="py-3.5 px-4">Gross Income</th>
                        <th class="py-3.5 px-4">Socio-Economic</th>
                        <th class="py-3.5 px-6 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-on-surface">
                    <?php if (empty($households)): ?>
                        <tr>
                            <td colspan="9" class="py-12 text-center text-on-surface-variant">
                                No registered households found. Click "New Household Registration" above to add one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($households as $h): 
                            $hId = $h['household_id'];
                            $mList = $membersByHousehold[$hId] ?? [];
                            $isB40 = ($h['gross_household_income'] ?? 0) <= 5250;
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
                                <td class="py-4 px-4">
                                    <span class="inline-block bg-surface-container px-2.5 py-1 rounded-full text-xs font-semibold">
                                        <?php echo htmlspecialchars($h['house_type']); ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 bg-primary/10 text-primary font-bold text-xs px-2.5 py-1 rounded-full">
                                        <span class="material-symbols-outlined text-[14px]">person</span>
                                        <span><?php echo count($mList); ?></span>
                                    </span>
                                </td>
                                <td class="py-4 px-4 font-semibold text-xs">
                                    RM <?php echo number_format($h['gross_household_income'] ?? 0, 2); ?>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $isB40 ? 'bg-emerald-500/10 text-emerald-600' : 'bg-blue-500/10 text-blue-600'; ?>">
                                        <?php echo $isB40 ? 'B40 Subsidized' : 'M40 / T20'; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" onclick='viewHouseholdModal(<?php echo htmlspecialchars(json_encode($h), ENT_QUOTES, 'UTF-8'); ?>, <?php echo htmlspecialchars(json_encode($mList), ENT_QUOTES, 'UTF-8'); ?>)'
                                                class="inline-flex items-center gap-1 text-primary hover:text-primary/80 font-semibold text-xs bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-full transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                            <span>View</span>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this household record? This will also remove all family members and financial records.');" class="inline">
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
<!-- MODAL: MULTI-STEP NEW HOUSEHOLD REGISTRATION -->
<!-- ============================================================= -->
<div id="registrationModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm hidden overflow-y-auto">
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] w-full max-w-4xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col my-auto">
        <!-- Modal Header -->
        <div class="px-6 py-5 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[24px]">family_restroom</span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-lg text-on-surface">Household & Family Registration</h3>
                    <p class="text-xs text-on-surface-variant">Step-by-step household demographics & financial intake</p>
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
                    <span id="stepLabel3" class="text-xs font-medium text-on-surface-variant mt-1.5 line-clamp-1">3. Members</span>
                </button>
                <button type="button" id="stepBtn4" onclick="jumpToStep(4)" class="group flex flex-col items-center p-2 rounded-2xl transition-all">
                    <div id="stepCircle4" class="w-10 h-10 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-sm transition-all">4</div>
                    <span id="stepLabel4" class="text-xs font-medium text-on-surface-variant mt-1.5 line-clamp-1">4. Finances</span>
                </button>
            </div>
            <div class="w-full bg-surface-container h-1.5 rounded-full mt-3 overflow-hidden">
                <div id="stepProgressBar" class="bg-primary h-full transition-all duration-300" style="width: 25%;"></div>
            </div>
        </div>

        <!-- Form Body -->
        <form method="POST" id="householdMultiStepForm" class="overflow-y-auto p-6 space-y-6 flex-1">
            <input type="hidden" name="action" value="add">

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
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">City <span class="text-rose-500">*</span></label>
                        <input type="text" name="city" id="adm_city" required placeholder="e.g. Kajang"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">State <span class="text-rose-500">*</span></label>
                        <select name="state" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
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
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">House / Dwelling Type <span class="text-rose-500">*</span></label>
                        <select name="house_type" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="Landed">Landed / Teres</option>
                            <option value="Apartment">Apartment / Kondominium</option>
                            <option value="Rental">Rental / Sewa</option>
                            <option value="Owned">Owned / Milik Sendiri</option>
                            <option value="Temporary/PPS">Pusat Pemindahan (PPS)</option>
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
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
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

            <!-- STEP 3: MEMBERS -->
            <div id="categoryStep3" class="space-y-4 hidden">
                <div class="bg-primary/5 border-l-4 border-primary p-4 rounded-2xl flex items-center justify-between">
                    <div>
                        <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">groups</span>
                            <span>Step 3: Household Members</span>
                        </h4>
                    </div>
                    <button type="button" onclick="addAdminMemberRow()" class="inline-flex items-center gap-1 bg-primary text-on-primary text-xs font-semibold px-3 py-1.5 rounded-full shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">add</span>
                        <span>Add Member</span>
                    </button>
                </div>
                <div id="adminMembersContainer" class="space-y-3">
                    <!-- Populated dynamically -->
                </div>
            </div>

            <!-- STEP 4: FINANCES -->
            <div id="categoryStep4" class="space-y-4 hidden">
                <div class="bg-primary/5 border-l-4 border-primary p-4 rounded-2xl">
                    <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">payments</span>
                        <span>Step 4: Household Financial Information</span>
                    </h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Gross Monthly Household Income (RM) <span class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" name="gross_income" required value="0.00"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Rent / Mortgage (RM)</label>
                        <input type="number" step="0.01" name="rent_mortgage" value="0.00"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Utilities (RM)</label>
                        <input type="number" step="0.01" name="utilities" value="0.00"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Education Fees (RM)</label>
                        <input type="number" step="0.01" name="education_fees" value="0.00"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1.5">Medical & Medication Costs (RM)</label>
                        <input type="number" step="0.01" name="medical_costs" value="0.00"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                </div>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="pt-4 border-t border-outline-variant/20 flex items-center justify-between">
                <button type="button" id="prevBtn" onclick="prevStep()" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-on-surface-variant hover:text-on-surface bg-surface-container hover:bg-surface-container-high rounded-full transition-colors hidden">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    <span>Back</span>
                </button>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" id="nextBtn" onclick="nextStep()" class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-semibold bg-primary hover:bg-primary/90 text-on-primary rounded-full shadow-sm transition-all">
                        <span>Continue</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </button>
                    <button type="submit" id="submitRegBtn" class="inline-flex items-center gap-1.5 px-6 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-full shadow-md transition-all hidden">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        <span>Register Household</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================= -->
<!-- MODAL: VIEW HOUSEHOLD DETAILS -->
<!-- ============================================================= -->
<div id="viewDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm hidden overflow-y-auto">
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] w-full max-w-3xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col my-auto">
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
            <button type="button" onclick="closeViewModal()" class="text-on-surface-variant hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <div class="overflow-y-auto p-6 space-y-6 flex-1 text-sm" id="viewModalBody">
            <!-- Injected dynamically -->
        </div>

        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/20 flex justify-end">
            <button type="button" onclick="closeViewModal()" class="px-5 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold rounded-full transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
let currentStepIdx = 1;
let adminMemberCount = 0;

function openRegistrationModal() {
    document.getElementById('registrationModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    jumpToStep(1);
    if (adminMemberCount === 0) {
        addAdminMemberRow(true);
    }
}

function closeRegistrationModal() {
    document.getElementById('registrationModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function jumpToStep(step) {
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

    document.getElementById('stepProgressBar').style.width = (step * 25) + '%';
    document.getElementById('prevBtn').classList.toggle('hidden', step === 1);
    document.getElementById('nextBtn').classList.toggle('hidden', step === 4);
    document.getElementById('submitRegBtn').classList.toggle('hidden', step !== 4);
}

function nextStep() {
    if (currentStepIdx < 4) jumpToStep(currentStepIdx + 1);
}

function prevStep() {
    if (currentStepIdx > 1) jumpToStep(currentStepIdx - 1);
}

function addAdminMemberRow(isHead = false) {
    const idx = adminMemberCount++;
    const container = document.getElementById('adminMembersContainer');
    const box = document.createElement('div');
    box.className = 'p-4 rounded-2xl bg-surface-container border border-outline-variant/30 space-y-3';
    box.id = `adminMem_${idx}`;

    box.innerHTML = `
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-primary">Member #${idx + 1} ${isHead ? '(Head of House)' : ''}</span>
            ${!isHead ? `<button type="button" onclick="document.getElementById('adminMem_${idx}').remove()" class="text-rose-500 text-xs font-semibold hover:underline">Remove</button>` : ''}
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Full Legal Name *</label>
                <input type="text" name="members[${idx}][full_name]" required class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">National IC / MyKid *</label>
                <input type="text" name="members[${idx}][national_id]" required class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Relationship</label>
                <select name="members[${idx}][relationship_to_head]" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                    <option value="Ketua Keluarga">Ketua Keluarga</option>
                    <option value="Pasangan">Pasangan (Spouse)</option>
                    <option value="Anak">Anak (Child)</option>
                    <option value="Ibu / Bapa">Ibu / Bapa (Parent)</option>
                    <option value="Adik-beradik">Adik-beradik (Sibling)</option>
                    <option value="Lain-lain">Lain-lain (Other)</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Gender</label>
                <select name="members[${idx}][gender]" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                    <option value="Lelaki">Male</option>
                    <option value="Perempuan">Female</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Chronic Disease</label>
                <input type="text" name="members[${idx}][chronic_condition]" value="Tiada" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-on-surface-variant mb-1">Vulnerable Tag</label>
                <select name="members[${idx}][vulnerable_dependent]" class="w-full bg-surface-container-lowest border border-outline-variant/40 text-on-surface text-xs input-24 focus:outline-none">
                    <option value="Tiada">None</option>
                    <option value="Warga Emas (60+)">Elderly (60+)</option>
                    <option value="OKU (Kurang Upaya)">Disabled (OKU)</option>
                    <option value="Kanak-kanak / Bayi">Infant / Child</option>
                    <option value="Pesakit Terlantar">Bedridden</option>
                </select>
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
    document.getElementById('viewModalTitle').innerText = `#HH-${String(h.household_id).padStart(5, '0')} — ${h.head_name || 'Household Details'}`;
    document.getElementById('viewModalSubtitle').innerText = `${h.street_address}, ${h.postal_code} ${h.city}, ${h.state}`;

    let membersHtml = '';
    if (members && members.length > 0) {
        membersHtml = members.map((m, i) => `
            <tr class="border-b border-outline-variant/10">
                <td class="py-2.5 px-3 font-semibold text-xs">${i + 1}</td>
                <td class="py-2.5 px-3 font-medium text-xs">${m.full_name}</td>
                <td class="py-2.5 px-3 text-xs font-mono">${m.national_id}</td>
                <td class="py-2.5 px-3 text-xs">${m.relationship_to_head}</td>
                <td class="py-2.5 px-3 text-xs">${m.gender}</td>
                <td class="py-2.5 px-3 text-xs"><span class="badge ${m.chronic_condition !== 'Tiada' ? 'bg-rose-500/10 text-rose-600' : 'bg-surface-container text-on-surface-variant'} px-2 py-0.5 rounded-full">${m.chronic_condition}</span></td>
                <td class="py-2.5 px-3 text-xs"><span class="badge ${m.vulnerable_dependent !== 'Tiada' ? 'bg-amber-500/10 text-amber-700' : 'bg-surface-container text-on-surface-variant'} px-2 py-0.5 rounded-full">${m.vulnerable_dependent}</span></td>
            </tr>
        `).join('');
    } else {
        membersHtml = `<tr><td colspan="7" class="py-4 text-center text-on-surface-variant">No members registered.</td></tr>`;
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
            <div class="p-4 rounded-2xl bg-surface-container border border-outline-variant/20 md:col-span-2">
                <h5 class="text-xs font-bold text-primary uppercase tracking-wider mb-2">Monthly Financial Profile</h5>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div><span class="text-on-surface-variant">Gross Income:</span> <div class="font-bold text-emerald-600">RM ${parseFloat(h.gross_household_income || 0).toFixed(2)}</div></div>
                    <div><span class="text-on-surface-variant">Rent / Mortgage:</span> <div class="font-bold text-rose-500">RM ${parseFloat(h.rent_mortgage || 0).toFixed(2)}</div></div>
                    <div><span class="text-on-surface-variant">Utilities:</span> <div class="font-bold">RM ${parseFloat(h.utilities || 0).toFixed(2)}</div></div>
                    <div><span class="text-on-surface-variant">Medical Costs:</span> <div class="font-bold text-rose-500">RM ${parseFloat(h.medical_costs || 0).toFixed(2)}</div></div>
                </div>
            </div>
        </div>

        <div class="border border-outline-variant/20 rounded-2xl overflow-hidden">
            <div class="p-3 bg-surface-container-low font-bold text-xs text-on-surface">Registered Members (${members.length})</div>
            <table class="w-full text-left">
                <thead class="bg-surface-container text-on-surface-variant text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-2 px-3">#</th>
                        <th class="py-2 px-3">Name</th>
                        <th class="py-2 px-3">IC / ID</th>
                        <th class="py-2 px-3">Relation</th>
                        <th class="py-2 px-3">Gender</th>
                        <th class="py-2 px-3">Chronic</th>
                        <th class="py-2 px-3">Vulnerable</th>
                    </tr>
                </thead>
                <tbody>${membersHtml}</tbody>
            </table>
        </div>
    `;

    document.getElementById('viewModalBody').innerHTML = html;
    document.getElementById('viewDetailsModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeViewModal() {
    document.getElementById('viewDetailsModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
