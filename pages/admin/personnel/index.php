<?php
/**
 * ============================================================================
 *   SeDaP 2.0 — Staff & Volunteer Management Module
 *   Normalized Schema Integration:
 *   - volunteers (Core applicant data)
 *   - clinical_profiles (Track A: Medical / Clinical 1-to-1)
 *   - non_clinical_profiles (Track B: Operations / Non-Medical 1-to-1)
 *   - stations & shifts (Operational lookups)
 *   - volunteer_deployments (Shift & station assignments)
 * ============================================================================
 */
$adminBase = '../';
$activeNav = 'personnel';
$pageTitle = 'Staff & Volunteers';
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
    header('Content-Disposition: attachment; filename=sedap_master_roster_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Volunteer ID', 'Track', 'Full Legal Name', 'Badge Name', 'Mobile Number', 'Languages Spoken',
        'Role / Profession', 'Council Reg Number', 'APC Expiry Date', 'Assigned Station', 'Shift Name',
        'Shift Date', 'T-Shirt Size', 'Dietary Preference', 'Emergency Contact', 'Vetting Status'
    ]);
    
    $exportStmt = $pdo->query("SELECT * FROM view_master_roster_export ORDER BY volunteer_id ASC");
    while ($row = $exportStmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['volunteer_id'],
            $row['track'] === 'clinical' ? 'Clinical (Track A)' : 'Non-Clinical (Track B)',
            $row['full_name'],
            $row['badge_name'],
            $row['phone_number'],
            $row['languages_spoken'],
            $row['role_or_profession'] ?? '—',
            $row['council_reg_number'] ?? '—',
            $row['apc_expiry_date'] ?? '—',
            $row['assigned_station'] ?? 'Unassigned',
            $row['shift_name'] ?? 'Unassigned',
            $row['shift_date'] ?? '—',
            $row['t_shirt_size'],
            $row['dietary_preference'],
            $row['emergency_contact'],
            ucfirst($row['vetting_status'])
        ]);
    }
    fclose($output);
    exit;
}

// ---------------------------------------------------------------------------
// 2. Handle Deletion
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = trim($_POST['volunteer_id'] ?? '');
    if (!empty($delId)) {
        try {
            $pdo->prepare("DELETE FROM volunteers WHERE volunteer_id = ?")->execute([$delId]);
            $msg = "Volunteer record " . htmlspecialchars($delId) . " has been removed.";
        } catch (Exception $e) {
            $err = "Failed to remove record: " . $e->getMessage();
        }
    }
}

// ---------------------------------------------------------------------------
// 3. Handle Vetting Status Update
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_vetting') {
    $vId = trim($_POST['volunteer_id'] ?? '');
    $newStatus = $_POST['vetting_status'] ?? 'pending';
    if (!empty($vId) && in_array($newStatus, ['pending', 'approved', 'rejected'], true)) {
        try {
            $pdo->prepare("UPDATE volunteers SET vetting_status = ? WHERE volunteer_id = ?")->execute([$newStatus, $vId]);
            $msg = "Vetting status for " . htmlspecialchars($vId) . " updated to " . ucfirst($newStatus) . ".";
        } catch (Exception $e) {
            $err = "Failed to update vetting status: " . $e->getMessage();
        }
    }
}

// ---------------------------------------------------------------------------
// 4. Handle Registration Form Submission (ACID Transaction)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_volunteer') {
    try {
        // Core Identity & Contact
        $fullName        = trim($_POST['full_name'] ?? '');
        $badgeName       = trim($_POST['badge_name'] ?? '');
        $idOrPassport    = trim($_POST['id_or_passport'] ?? '');
        $phoneNumber     = trim($_POST['phone_number'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $tShirtSize      = $_POST['t_shirt_size'] ?? 'M';
        $dietaryPref     = trim($_POST['dietary_preference'] ?? 'None');
        $hasOwnTransport = !empty($_POST['has_own_transport']) ? 1 : 0;
        $languagesArr    = $_POST['languages_spoken'] ?? [];
        $languagesSpoken = is_array($languagesArr) ? implode(', ', $languagesArr) : trim($languagesArr);

        // Emergency Contact
        $emName          = trim($_POST['emergency_contact_name'] ?? '');
        $emPhone         = trim($_POST['emergency_contact_phone'] ?? '');
        $emRelation      = trim($_POST['emergency_contact_relation'] ?? '');

        // Triage Track
        $track           = $_POST['track'] ?? 'non_clinical'; // 'clinical' or 'non_clinical'

        // Track A: Clinical Profile Fields
        $cadre           = trim($_POST['cadre'] ?? '');
        $councilReg      = trim($_POST['council_reg_number'] ?? '');
        $apcExpiry       = !empty($_POST['apc_expiry_date']) ? $_POST['apc_expiry_date'] : null;
        $specialty       = trim($_POST['specialty'] ?? '');
        $isLifeSupport   = !empty($_POST['is_life_support_certified']) ? 1 : 0;
        $lifeSupportExp  = !empty($_POST['life_support_expiry']) ? $_POST['life_support_expiry'] : null;

        // Track B: Non-Clinical Profile Fields
        $occupation      = trim($_POST['occupation'] ?? '');
        $skillsArr       = $_POST['key_skills'] ?? [];
        $keySkills       = is_array($skillsArr) ? implode(', ', $skillsArr) : trim($skillsArr);
        $physicalLimits  = trim($_POST['physical_limitations'] ?? '');

        // Upload APC Document (if any)
        $apcDocUrl = null;
        if (!empty($_FILES['apc_proof']['name'])) {
            $uploadDir = __DIR__ . '/../../../uploads/apc_documents/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['apc_proof']['name'], PATHINFO_EXTENSION));
            $filename = 'apc_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['apc_proof']['tmp_name'], $uploadDir . $filename)) {
                $apcDocUrl = 'uploads/apc_documents/' . $filename;
            }
        }

        if (empty($fullName) || empty($badgeName) || empty($idOrPassport) || empty($phoneNumber) || empty($email)) {
            throw new Exception("Please complete all required identity and contact fields.");
        }

        // Generate Volunteer ID (e.g. VOL-2026-001)
        $year = date('Y');
        $countStmt = $pdo->query("SELECT COUNT(*) FROM volunteers");
        $nextNum = ((int)$countStmt->fetchColumn()) + 1;
        $volunteerId = "VOL-{$year}-" . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);

        $pdo->beginTransaction();

        // 1. Insert Core Volunteer Record
        $stmtV = $pdo->prepare("
            INSERT INTO volunteers (
                volunteer_id, full_name, badge_name, id_or_passport, phone_number, email,
                t_shirt_size, dietary_preference, has_own_transport, languages_spoken,
                emergency_contact_name, emergency_contact_phone, emergency_contact_relation,
                track, vetting_status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, 'pending', NOW()
            )
        ");
        $stmtV->execute([
            $volunteerId, $fullName, $badgeName, $idOrPassport, $phoneNumber, $email,
            $tShirtSize, $dietaryPref, $hasOwnTransport, $languagesSpoken,
            $emName, $emPhone, $emRelation,
            $track
        ]);

        // 2. Insert 1-to-1 Profile based on Track
        if ($track === 'clinical') {
            $stmtC = $pdo->prepare("
                INSERT INTO clinical_profiles (
                    volunteer_id, cadre, council_reg_number, apc_expiry_date,
                    apc_document_url, specialty, is_life_support_certified, life_support_expiry
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtC->execute([
                $volunteerId, $cadre, $councilReg, $apcExpiry,
                $apcDocUrl, $specialty, $isLifeSupport, $lifeSupportExp
            ]);
        } else {
            $stmtNC = $pdo->prepare("
                INSERT INTO non_clinical_profiles (
                    volunteer_id, occupation, key_skills, physical_limitations
                ) VALUES (?, ?, ?, ?)
            ");
            $stmtNC->execute([
                $volunteerId, $occupation, $keySkills, $physicalLimits
            ]);
        }

        // 3. Optional initial deployment/station registration
        $prefStationId = !empty($_POST['preferred_station_id']) ? (int)$_POST['preferred_station_id'] : null;
        $prefShiftId   = !empty($_POST['preferred_shift_id']) ? (int)$_POST['preferred_shift_id'] : null;
        if ($prefStationId && $prefShiftId) {
            $stmtDep = $pdo->prepare("
                INSERT INTO volunteer_deployments (volunteer_id, shift_id, station_id, attendance_status)
                VALUES (?, ?, ?, 'scheduled')
                ON DUPLICATE KEY UPDATE station_id = VALUES(station_id)
            ");
            $stmtDep->execute([$volunteerId, $prefShiftId, $prefStationId]);
        }

        $pdo->commit();
        $msg = "Volunteer registration for {$fullName} ({$volunteerId}) submitted successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $err = "Registration error: " . $e->getMessage();
    }
}

// ---------------------------------------------------------------------------
// 5. Fetch Lookup Data (Stations & Shifts) and Master Volunteer Roster
// ---------------------------------------------------------------------------
$stations = [];
$shifts = [];
$volunteers = [];
$totalVolunteers = 0;
$clinicalCount = 0;
$nonClinicalCount = 0;
$approvedCount = 0;

try {
    $stations = $pdo->query("SELECT * FROM stations ORDER BY track_required, station_name")->fetchAll(PDO::FETCH_ASSOC);
    $shifts = $pdo->query("SELECT * FROM shifts ORDER BY shift_date, start_time")->fetchAll(PDO::FETCH_ASSOC);

    $vStmt = $pdo->query("
        SELECT v.*,
               c.cadre, c.council_reg_number, c.apc_expiry_date, c.apc_document_url, c.specialty, c.is_life_support_certified, c.life_support_expiry,
               nc.occupation, nc.key_skills, nc.physical_limitations,
               dep.station_id, dep.shift_id, dep.attendance_status,
               st.station_name, sh.shift_name
        FROM volunteers v
        LEFT JOIN clinical_profiles c ON v.volunteer_id = c.volunteer_id
        LEFT JOIN non_clinical_profiles nc ON v.volunteer_id = nc.volunteer_id
        LEFT JOIN volunteer_deployments dep ON v.volunteer_id = dep.volunteer_id
        LEFT JOIN stations st ON dep.station_id = st.station_id
        LEFT JOIN shifts sh ON dep.shift_id = sh.shift_id
        ORDER BY v.created_at DESC
    ");
    $volunteers = $vStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalVolunteers = count($volunteers);

    foreach ($volunteers as $vl) {
        if ($vl['track'] === 'clinical') $clinicalCount++;
        else $nonClinicalCount++;
        if ($vl['vetting_status'] === 'approved') $approvedCount++;
    }
} catch (Exception $e) {
    $volunteers = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
  .input-24 { border-radius: 24px !important; padding: 0.65rem 1.25rem !important; }
  .chip-check {
    cursor: pointer;
    border: 1.5px solid rgba(var(--color-outline-variant), 0.4);
    border-radius: 9999px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    user-select: none;
  }
  .chip-check input { display: none; }
  .chip-check.selected {
    background: rgba(2, 132, 199, 0.12);
    border-color: #0284c7;
    color: #0284c7;
  }
</style>

<div class="space-y-6 pb-12">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface flex items-center gap-3">
                <span class="material-symbols-outlined text-[32px] text-primary">groups</span>
                <span>Volunteer & Staff Roster</span>
            </h1>
            <p class="text-on-surface-variant text-sm mt-1">Structured medical credentialing, operational vetting, and station deployments</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php?export=csv" class="inline-flex items-center gap-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold px-4 py-2.5 rounded-[24px] border border-outline-variant/40 shadow-sm transition-all text-sm">
                <span class="material-symbols-outlined text-[20px]">download</span>
                <span>Export CSV</span>
            </a>
            <button onclick="openRegistrationModal()" class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-on-primary font-semibold px-5 py-2.5 rounded-[24px] shadow-sm transition-all duration-200 hover:shadow text-sm">
                <span class="material-symbols-outlined text-[20px]">person_add</span>
                <span>Register Volunteer</span>
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-[20px] flex items-center gap-3 shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <div class="text-sm font-medium"><?php echo $msg; ?></div>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-[20px] flex items-center gap-3 shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-rose-600">error</span>
            <div class="text-sm font-medium"><?php echo htmlspecialchars($err); ?></div>
        </div>
    <?php endif; ?>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Volunteers</span>
                <span class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center material-symbols-outlined text-[22px]">badge</span>
            </div>
            <div class="text-2xl font-bold font-headline text-on-surface mt-2"><?php echo $totalVolunteers; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">Enrolled applicants</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Clinical Track A</span>
                <span class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center material-symbols-outlined text-[22px]">stethoscope</span>
            </div>
            <div class="text-2xl font-bold font-headline text-blue-600 mt-2"><?php echo $clinicalCount; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">Doctors, Nurses, MO, PPP</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Non-Clinical Track B</span>
                <span class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-700 flex items-center justify-center material-symbols-outlined text-[22px]">handshake</span>
            </div>
            <div class="text-2xl font-bold font-headline text-amber-700 mt-2"><?php echo $nonClinicalCount; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">Logistics, Crowd, Registration</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Approved Vetting</span>
                <span class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center material-symbols-outlined text-[22px]">verified</span>
            </div>
            <div class="text-2xl font-bold font-headline text-emerald-600 mt-2"><?php echo $approvedCount; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">Vetted & cleared for duty</div>
        </div>
    </div>

    <!-- Master Roster Directory Card -->
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[28px] overflow-hidden shadow-sm">
        <div class="p-5 sm:p-6 border-b border-outline-variant/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">assignment_ind</span>
                <h2 class="font-headline font-bold text-lg text-on-surface">Registered Volunteer Directory (<?php echo $totalVolunteers; ?>)</h2>
            </div>
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <div class="w-full sm:w-72">
                    <input type="text" id="volunteerSearchInput" placeholder="Search name, badge, IC, phone..."
                           class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm px-4 py-2 input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                           oninput="searchVolunteerTable()">
                </div>
                <select id="trackFilter" onchange="searchVolunteerTable()" class="bg-surface-container border border-outline-variant/40 text-on-surface text-xs px-3 py-2 rounded-2xl focus:outline-none">
                    <option value="">All Tracks</option>
                    <option value="clinical">Track A: Clinical</option>
                    <option value="non_clinical">Track B: Non-Clinical</option>
                </select>
                <select id="vettingFilter" onchange="searchVolunteerTable()" class="bg-surface-container border border-outline-variant/40 text-on-surface text-xs px-3 py-2 rounded-2xl focus:outline-none">
                    <option value="">All Vetting Status</option>
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" id="volunteerTable">
                <thead class="bg-surface-container text-on-surface-variant text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">ID & Track</th>
                        <th class="py-3.5 px-4">Applicant Name</th>
                        <th class="py-3.5 px-4">Role / Cadre</th>
                        <th class="py-3.5 px-4">WhatsApp Contact</th>
                        <th class="py-3.5 px-4">Transport & Shirt</th>
                        <th class="py-3.5 px-4">Assigned Station</th>
                        <th class="py-3.5 px-4 text-center">Vetting</th>
                        <th class="py-3.5 px-6 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-on-surface">
                    <?php if (empty($volunteers)): ?>
                        <tr>
                            <td colspan="8" class="py-12 text-center text-on-surface-variant">
                                No registered volunteers found. Click "Register Volunteer" above to add applicants.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($volunteers as $v): 
                            $isClinical = $v['track'] === 'clinical';
                            $roleCadre = $isClinical ? ($v['cadre'] ?: 'Clinical Practitioner') : ($v['occupation'] ?: 'Operations Volunteer');
                            $stationLabel = $v['station_name'] ?: 'Not Assigned';
                        ?>
                            <tr class="hover:bg-surface-container/50 transition-colors volunteer-row" 
                                data-track="<?php echo htmlspecialchars($v['track']); ?>"
                                data-vetting="<?php echo htmlspecialchars($v['vetting_status']); ?>">
                                <td class="py-4 px-6">
                                    <div class="font-bold text-primary font-mono text-xs"><?php echo htmlspecialchars($v['volunteer_id']); ?></div>
                                    <div class="text-[11px] font-medium text-on-surface-variant mt-0.5">
                                        <?php echo $isClinical ? 'Clinical (Track A)' : 'Non-Clinical (Track B)'; ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="font-semibold text-on-surface"><?php echo htmlspecialchars($v['full_name']); ?></div>
                                    <div class="text-xs text-primary font-medium flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[14px]">badge</span>
                                        <span>Badge: "<?php echo htmlspecialchars($v['badge_name']); ?>"</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-xs">
                                    <div class="font-medium text-on-surface"><?php echo htmlspecialchars($roleCadre); ?></div>
                                    <?php if ($isClinical && !empty($v['council_reg_number'])): ?>
                                        <div class="text-[11px] text-on-surface-variant font-mono">Reg: <?php echo htmlspecialchars($v['council_reg_number']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 text-xs font-mono">
                                    <?php echo htmlspecialchars($v['phone_number']); ?>
                                    <div class="text-[11px] text-on-surface-variant"><?php echo htmlspecialchars($v['email']); ?></div>
                                </td>
                                <td class="py-4 px-4 text-xs">
                                    <div>Transport: <strong><?php echo $v['has_own_transport'] ? 'Yes' : 'No'; ?></strong></div>
                                    <div class="text-on-surface-variant text-[11px]">Size: <?php echo htmlspecialchars($v['t_shirt_size']); ?> • Diet: <?php echo htmlspecialchars($v['dietary_preference']); ?></div>
                                </td>
                                <td class="py-4 px-4 text-xs font-medium text-on-surface">
                                    <?php echo htmlspecialchars($stationLabel); ?>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="update_vetting">
                                        <input type="hidden" name="volunteer_id" value="<?php echo htmlspecialchars($v['volunteer_id']); ?>">
                                        <select name="vetting_status" onchange="this.form.submit()" class="text-xs font-semibold px-2.5 py-1 rounded-full border-0 focus:ring-2 focus:ring-primary <?php 
                                            if ($v['vetting_status'] === 'approved') echo 'bg-emerald-500/10 text-emerald-600';
                                            elseif ($v['vetting_status'] === 'rejected') echo 'bg-rose-500/10 text-rose-600';
                                            else echo 'bg-amber-500/10 text-amber-700';
                                        ?>">
                                            <option value="pending" <?php echo $v['vetting_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo $v['vetting_status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="rejected" <?php echo $v['vetting_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" onclick='viewVolunteerDossier(<?php echo htmlspecialchars(json_encode($v), ENT_QUOTES, 'UTF-8'); ?>)'
                                                class="inline-flex items-center gap-1 text-primary hover:text-primary/80 font-semibold text-xs bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-full transition-colors">
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                            <span>View</span>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Delete volunteer <?php echo htmlspecialchars($v['volunteer_id']); ?>?');" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="volunteer_id" value="<?php echo htmlspecialchars($v['volunteer_id']); ?>">
                                            <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-full transition-colors" title="Delete record">
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
<!-- MODAL: NORMALIZED SCHEMA VOLUNTEER REGISTRATION -->
<!-- ============================================================= -->
<div id="registrationModal" class="fixed inset-0 z-[99999] hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6" style="display: none;">
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col h-[90vh] max-h-[820px] text-left transform transition-all animate-scale-up" onclick="event.stopPropagation()">
        
        <!-- Modal Header -->
        <div class="px-6 py-5 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[24px]">assignment_ind</span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-lg text-on-surface">Volunteer & Staff Intake Form</h3>
                    <p class="text-xs text-on-surface-variant">Core volunteer demographics with branching Clinical Track A & Operations Track B</p>
                </div>
            </div>
            <button type="button" onclick="closeRegistrationModal()" class="text-on-surface-variant hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <!-- Stepper Header -->
        <div class="px-6 pt-4 pb-2 border-b border-outline-variant/10 bg-surface-container-lowest shrink-0">
            <div class="grid grid-cols-3 gap-2 text-center">
                <button type="button" id="stepBtn1" onclick="jumpToStep(1)" class="group flex flex-col items-center p-2 rounded-2xl">
                    <div id="stepCircle1" class="w-9 h-9 rounded-full bg-primary text-on-primary font-bold flex items-center justify-center text-xs shadow-md">1</div>
                    <span id="stepLabel1" class="text-xs font-bold text-primary mt-1">1. Core Data</span>
                </button>
                <button type="button" id="stepBtn2" onclick="jumpToStep(2)" class="group flex flex-col items-center p-2 rounded-2xl">
                    <div id="stepCircle2" class="w-9 h-9 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-xs">2</div>
                    <span id="stepLabel2" class="text-xs font-medium text-on-surface-variant mt-1">2. Track Verification</span>
                </button>
                <button type="button" id="stepBtn3" onclick="jumpToStep(3)" class="group flex flex-col items-center p-2 rounded-2xl">
                    <div id="stepCircle3" class="w-9 h-9 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-xs">3</div>
                    <span id="stepLabel3" class="text-xs font-medium text-on-surface-variant mt-1">3. Review & Submit</span>
                </button>
            </div>
            <div class="w-full bg-surface-container h-1.5 rounded-full mt-2 overflow-hidden">
                <div id="stepProgressBar" class="bg-primary h-full transition-all duration-300" style="width: 33.3%;"></div>
            </div>
        </div>

        <!-- Form Body -->
        <form method="POST" enctype="multipart/form-data" id="volunteerRegForm" class="overflow-y-auto p-6 space-y-6 flex-1 text-sm">
            <input type="hidden" name="action" value="add_volunteer">

            <!-- ============================================== -->
            <!-- STEP 1: CORE VOLUNTEERS DATA -->
            <!-- ============================================== -->
            <div id="vStep1" class="space-y-5">
                
                <!-- 1. Identity & Contact -->
                <div class="bg-surface-container-low rounded-2xl p-4 border border-outline-variant/30 space-y-4">
                    <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">badge</span>
                        <span>Basic Identity & Contact (Core Information)</span>
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Full Legal Name (per NRIC / Passport) <span class="text-rose-500">*</span></label>
                            <input type="text" name="full_name" id="v_full_name" required placeholder="e.g. Dr. Siti Aminah binti Yusof"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Preferred Call Name (for event badge) <span class="text-rose-500">*</span></label>
                            <input type="text" name="badge_name" id="v_badge_name" required placeholder="e.g. Dr. Siti"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">NRIC / Passport Number <span class="text-rose-500">*</span></label>
                            <input type="text" name="id_or_passport" id="v_id_or_passport" required placeholder="e.g. 910412-10-5678"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">WhatsApp Mobile Number <span class="text-rose-500">*</span></label>
                            <input type="tel" name="phone_number" id="v_phone_number" required placeholder="e.g. 012-345 6789"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Email Address <span class="text-rose-500">*</span></label>
                            <input type="email" name="email" id="v_email" required placeholder="e.g. siti@example.com"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                        </div>
                    </div>
                </div>

                <!-- 2. Emergency Contact -->
                <div class="bg-surface-container-low rounded-2xl p-4 border border-outline-variant/30 space-y-4">
                    <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">contact_emergency</span>
                        <span>Emergency Contact Information</span>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Emergency Contact Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="emergency_contact_name" required placeholder="e.g. Yusof bin Ahmad"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Relationship <span class="text-rose-500">*</span></label>
                            <input type="text" name="emergency_contact_relation" required placeholder="e.g. Parent / Spouse / Sibling"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Emergency Contact Phone <span class="text-rose-500">*</span></label>
                            <input type="tel" name="emergency_contact_phone" required placeholder="e.g. 019-876 5432"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- 3. Logistics & Preferences -->
                <div class="bg-surface-container-low rounded-2xl p-4 border border-outline-variant/30 space-y-4">
                    <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">tune</span>
                        <span>Logistics & Operational Preferences</span>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">T-Shirt Size <span class="text-rose-500">*</span></label>
                            <select name="t_shirt_size" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                                <option value="XS">XS</option>
                                <option value="S">S</option>
                                <option value="M" selected>M</option>
                                <option value="L">L</option>
                                <option value="XL">XL</option>
                                <option value="2XL">2XL</option>
                                <option value="3XL">3XL</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Dietary Preference</label>
                            <select name="dietary_preference" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                                <option value="None">None (Standard)</option>
                                <option value="Halal">Halal</option>
                                <option value="Vegetarian">Vegetarian</option>
                                <option value="Food Allergies">Food Allergies</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Has Own Transport?</label>
                            <select name="has_own_transport" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                                <option value="1">Yes (Own Car/Motorcycle)</option>
                                <option value="0">No (Public Transit / Carpool)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Languages Spoken -->
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-2">Spoken Languages & Local Dialects (Select all that apply):</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (['Malay', 'English', 'Mandarin', 'Tamil', 'Cantonese', 'Hokkien', 'Kelantanese', 'Sabahan', 'Sarawakian'] as $lang): ?>
                                <label class="chip-check" onclick="this.classList.toggle('selected')">
                                    <input type="checkbox" name="languages_spoken[]" value="<?php echo $lang; ?>">
                                    <span><?php echo $lang; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 4. Triage Track Branching Selection -->
                <div class="p-5 rounded-3xl bg-primary/10 border-2 border-primary/40 space-y-3">
                    <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px]">alt_route</span>
                        <span>Volunteer Category (Branching Point)</span>
                    </h4>
                    <p class="text-xs text-on-surface-variant">Choose your volunteer classification to complete the corresponding track verification:</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                        <label class="relative flex items-center gap-3 p-4 rounded-2xl bg-surface-container-lowest border-2 border-outline-variant/40 cursor-pointer hover:border-primary transition-all has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input type="radio" name="track" value="clinical" onchange="toggleTrackView('clinical')" class="text-primary focus:ring-primary h-4 w-4">
                            <div>
                                <div class="font-bold text-xs text-on-surface flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-primary text-[18px]">medical_services</span>
                                    <span>Option A: Medical / Clinical Background</span>
                                </div>
                                <div class="text-[11px] text-on-surface-variant mt-0.5">Proceed to Clinical Profile (Section 2A)</div>
                            </div>
                        </label>

                        <label class="relative flex items-center gap-3 p-4 rounded-2xl bg-surface-container-lowest border-2 border-outline-variant/40 cursor-pointer hover:border-primary transition-all has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                            <input type="radio" name="track" value="non_clinical" checked onchange="toggleTrackView('non_clinical')" class="text-primary focus:ring-primary h-4 w-4">
                            <div>
                                <div class="font-bold text-xs text-on-surface flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-primary text-[18px]">handshake</span>
                                    <span>Option B: Non-Medical Background</span>
                                </div>
                                <div class="text-[11px] text-on-surface-variant mt-0.5">Proceed to Non-Clinical Profile (Section 2B)</div>
                            </div>
                        </label>
                    </div>
                </div>

            </div>

            <!-- ============================================== -->
            <!-- STEP 2: BRANCHING TRACKS (clinical vs non_clinical) -->
            <!-- ============================================== -->
            <div id="vStep2" class="space-y-5 hidden">
                
                <!-- TRACK A: clinical_profiles -->
                <div id="trackClinicalContainer" class="space-y-4">
                    <div class="bg-blue-500/10 border-l-4 border-blue-600 p-4 rounded-2xl">
                        <h4 class="font-bold text-sm text-blue-700 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[22px]">stethoscope</span>
                            <span>Track A: Clinical Profile (clinical_profiles)</span>
                        </h4>
                        <p class="text-xs text-on-surface-variant mt-1">
                            Proof of practice and council credentials for patient safety and clinical compliance.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Professional Cadre <span class="text-rose-500">*</span></label>
                            <select name="cadre" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                                <option value="Medical Doctor">Medical Doctor (General Practitioner)</option>
                                <option value="Medical Specialist">Medical Specialist</option>
                                <option value="Registered Nurse">Registered Nurse (RN)</option>
                                <option value="Assistant Medical Officer">Assistant Medical Officer (AMO / PPP)</option>
                                <option value="Pharmacist">Pharmacist</option>
                                <option value="Physiotherapist">Physiotherapist</option>
                                <option value="Medical Student">Medical Student</option>
                                <option value="Nursing Student">Nursing Student</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Council Registration Number (MMC / LJM / LBM) <span class="text-rose-500">*</span></label>
                            <input type="text" name="council_reg_number" placeholder="e.g. MMC-12345"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">APC Expiry Date</label>
                            <input type="date" name="apc_expiry_date" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Clinical Specialty</label>
                            <input type="text" name="specialty" placeholder="e.g. Emergency, Pediatrics, Wound Care"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Upload APC Credential Proof (PDF or Photo)</label>
                            <input type="file" name="apc_proof" accept=".pdf,.jpg,.jpeg,.png"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-xs p-2.5 rounded-2xl focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Life Support Certified?</label>
                            <select name="is_life_support_certified" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                                <option value="1">Yes (BLS / ACLS / ATLS)</option>
                                <option value="0">No / Expired</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Life Support Expiry Date</label>
                            <input type="date" name="life_support_expiry" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- TRACK B: non_clinical_profiles -->
                <div id="trackNonClinicalContainer" class="space-y-4">
                    <div class="bg-amber-500/10 border-l-4 border-amber-600 p-4 rounded-2xl">
                        <h4 class="font-bold text-sm text-amber-700 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[22px]">handshake</span>
                            <span>Track B: Non-Clinical Profile (non_clinical_profiles)</span>
                        </h4>
                        <p class="text-xs text-on-surface-variant mt-1">
                            Focus on operational strengths, physical capabilities, and crowd handling abilities.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Current Occupation / Area of Study <span class="text-rose-500">*</span></label>
                            <input type="text" name="occupation" placeholder="e.g. IT Support, Accountant, University Student"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-on-surface mb-2">Key Non-Clinical Skills:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <?php foreach (['Crowd control / Ushering', 'Patient registration / Data entry', 'Logistics, lifting, and setup', 'Language interpretation / Translation', 'First Aid certified (St. John/CPR)', 'Photography / Media documentation'] as $sk): ?>
                                    <label class="chip-check" onclick="this.classList.toggle('selected')">
                                        <input type="checkbox" name="key_skills[]" value="<?php echo $sk; ?>">
                                        <span><?php echo $sk; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Physical Limitations / Role Readiness</label>
                            <input type="text" name="physical_limitations" placeholder="e.g. Comfortable standing, Requires seated role, Cannot lift heavy items"
                                   class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Station & Shift Preferences (Lookup tables) -->
                <div class="bg-surface-container-low rounded-2xl p-4 border border-outline-variant/30 space-y-4">
                    <h4 class="font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">room_preferences</span>
                        <span>Preferred Station & Shift Assignment</span>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Preferred Station (stations table)</label>
                            <select name="preferred_station_id" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                                <option value="">-- Choose Assigned Station --</option>
                                <?php foreach ($stations as $st): ?>
                                    <option value="<?php echo $st['station_id']; ?>"><?php echo htmlspecialchars($st['station_name']); ?> (<?php echo ucfirst($st['track_required']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-on-surface mb-1.5">Preferred Shift (shifts table)</label>
                            <select name="preferred_shift_id" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none">
                                <option value="">-- Choose Assigned Shift --</option>
                                <?php foreach ($shifts as $sh): ?>
                                    <option value="<?php echo $sh['shift_id']; ?>"><?php echo htmlspecialchars($sh['shift_name']); ?> (<?php echo date('d M', strtotime($sh['shift_date'])); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ============================================== -->
            <!-- STEP 3: REVIEW & CONFIRM -->
            <!-- ============================================== -->
            <div id="vStep3" class="space-y-4 hidden">
                <div class="bg-emerald-500/10 border-l-4 border-emerald-600 p-4 rounded-2xl">
                    <h4 class="font-bold text-sm text-emerald-700 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[22px]">fact_check</span>
                        <span>Step 3: Review Applicant Summary</span>
                    </h4>
                    <p class="text-xs text-on-surface-variant mt-1">Please confirm the normalized schema profile before saving.</p>
                </div>

                <div class="p-5 rounded-3xl bg-surface-container border border-outline-variant/30 space-y-3 text-xs" id="reviewSummaryBox">
                    <!-- Populated dynamically via JS -->
                </div>

                <div class="p-4 rounded-2xl bg-surface-container-low border border-outline-variant/30 flex items-center gap-3">
                    <input type="checkbox" id="confirmConsent" required class="rounded text-primary focus:ring-primary h-4 w-4">
                    <label for="confirmConsent" class="text-xs font-semibold text-on-surface">
                        I verify that all volunteer details, emergency contacts, and professional credentials have been validated according to event regulations.
                    </label>
                </div>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="pt-4 border-t border-outline-variant/20 flex items-center justify-between">
                <button type="button" id="vPrevBtn" onclick="prevVolunteerStep()" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-on-surface-variant hover:text-on-surface bg-surface-container hover:bg-surface-container-high rounded-full transition-colors hidden">
                    <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                    <span>Back</span>
                </button>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" id="vNextBtn" onclick="nextVolunteerStep()" class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-semibold bg-primary hover:bg-primary/90 text-on-primary rounded-full shadow-sm transition-all">
                        <span>Continue</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </button>
                    <button type="submit" id="vSubmitBtn" class="inline-flex items-center gap-1.5 px-6 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-full shadow-md transition-all hidden">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        <span>Confirm & Register Volunteer</span>
                    </button>
                </div>
            </div>

        </form>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- MODAL: VIEW VOLUNTEER DOSSIER -->
<!-- ============================================================= -->
<div id="viewDossierModal" class="fixed inset-0 z-[99999] hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6" style="display: none;">
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] w-full max-w-3xl shadow-2xl overflow-hidden flex flex-col h-[85vh] max-h-[750px] text-left transform transition-all animate-scale-up" onclick="event.stopPropagation()">
        <div class="px-6 py-5 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[24px]">contact_page</span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-lg text-on-surface" id="dossierTitle">Volunteer Dossier</h3>
                    <p class="text-xs text-on-surface-variant" id="dossierSubtitle">Full application profile & assignment capabilities</p>
                </div>
            </div>
            <button type="button" onclick="closeDossierModal()" class="text-on-surface-variant hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <div class="overflow-y-auto p-6 space-y-5 flex-1 min-h-0 text-sm text-left" id="dossierBody">
            <!-- Injected dynamically -->
        </div>

        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/20 flex justify-end shrink-0">
            <button type="button" onclick="closeDossierModal()" class="px-5 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold rounded-full transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
let currentVStep = 1;

// Teleport modals to document.body so they are never trapped in scrolling <main>
document.addEventListener('DOMContentLoaded', function() {
    ['viewDossierModal', 'registrationModal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
    });
});

function openRegistrationModal() {
    var modal = document.getElementById('registrationModal');
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    var formEl = document.getElementById('volunteerRegForm');
    if (formEl) formEl.scrollTop = 0;
    jumpToStep(1);
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'hidden';
}

function closeRegistrationModal() {
    var modal = document.getElementById('registrationModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'auto';
}

function toggleTrackView(type) {
    const clinBox = document.getElementById('trackClinicalContainer');
    const nonClinBox = document.getElementById('trackNonClinicalContainer');
    if (type === 'clinical') {
        clinBox.classList.remove('hidden');
        nonClinBox.classList.add('hidden');
    } else {
        clinBox.classList.add('hidden');
        nonClinBox.classList.remove('hidden');
    }
}

function jumpToStep(step) {
    currentVStep = step;
    for (let i = 1; i <= 3; i++) {
        const sec = document.getElementById('vStep' + i);
        const circle = document.getElementById('stepCircle' + i);
        const label = document.getElementById('stepLabel' + i);
        if (sec) sec.classList.toggle('hidden', i !== step);
        if (circle) {
            if (i === step) {
                circle.className = 'w-9 h-9 rounded-full bg-primary text-on-primary font-bold flex items-center justify-center text-xs shadow-md';
                label.className = 'text-xs font-bold text-primary mt-1';
            } else if (i < step) {
                circle.className = 'w-9 h-9 rounded-full bg-emerald-600 text-white font-bold flex items-center justify-center text-xs';
                label.className = 'text-xs font-medium text-emerald-600 mt-1';
            } else {
                circle.className = 'w-9 h-9 rounded-full bg-surface-container text-on-surface-variant font-bold flex items-center justify-center text-xs';
                label.className = 'text-xs font-medium text-on-surface-variant mt-1';
            }
        }
    }

    if (step === 2) {
        const trk = document.querySelector('input[name="track"]:checked')?.value || 'non_clinical';
        toggleTrackView(trk);
    }

    if (step === 3) {
        buildReviewBox();
    }

    document.getElementById('stepProgressBar').style.width = ((step / 3) * 100) + '%';
    document.getElementById('vPrevBtn').classList.toggle('hidden', step === 1);
    document.getElementById('vNextBtn').classList.toggle('hidden', step === 3);
    document.getElementById('vSubmitBtn').classList.toggle('hidden', step !== 3);
}

function nextVolunteerStep() {
    if (currentVStep < 3) jumpToStep(currentVStep + 1);
}

function prevVolunteerStep() {
    if (currentVStep > 1) jumpToStep(currentVStep - 1);
}

function buildReviewBox() {
    const fullName = document.getElementById('v_full_name').value || '—';
    const badgeName = document.getElementById('v_badge_name').value || '—';
    const phone = document.getElementById('v_phone_number').value || '—';
    const email = document.getElementById('v_email').value || '—';
    const track = document.querySelector('input[name="track"]:checked')?.value || 'non_clinical';
    const isClinical = track === 'clinical';

    const cadre = document.querySelector('[name="cadre"]')?.value || '—';
    const council = document.querySelector('[name="council_reg_number"]')?.value || '—';
    const occ = document.querySelector('[name="occupation"]')?.value || '—';

    const html = `
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div><strong>Full Name:</strong> ${fullName}</div>
            <div><strong>Badge Name:</strong> <span class="text-primary font-bold">"${badgeName}"</span></div>
            <div><strong>WhatsApp:</strong> ${phone}</div>
            <div><strong>Email:</strong> ${email}</div>
            <div class="sm:col-span-2 pt-2 border-t border-outline-variant/20">
                <strong>Track Category:</strong> <span class="font-semibold text-on-surface">${isClinical ? 'Track A: Clinical (clinical_profiles)' : 'Track B: Non-Clinical (non_clinical_profiles)'}</span>
            </div>
            ${isClinical ? `
                <div><strong>Professional Cadre:</strong> ${cadre}</div>
                <div><strong>Council Reg No:</strong> ${council}</div>
            ` : `
                <div class="sm:col-span-2"><strong>Occupation / Study:</strong> ${occ}</div>
            `}
        </div>
    `;
    document.getElementById('reviewSummaryBox').innerHTML = html;
}

function searchVolunteerTable() {
    const q = document.getElementById('volunteerSearchInput').value.toLowerCase();
    const trk = document.getElementById('trackFilter').value;
    const vet = document.getElementById('vettingFilter').value;
    const rows = document.querySelectorAll('.volunteer-row');

    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const rTrack = r.getAttribute('data-track') || '';
        const rVetting = r.getAttribute('data-vetting') || '';
        const matchQ = text.includes(q);
        const matchTrk = !trk || rTrack === trk;
        const matchVet = !vet || rVetting === vet;
        r.style.display = (matchQ && matchTrk && matchVet) ? '' : 'none';
    });
}

function viewVolunteerDossier(v) {
    const isClin = v.track === 'clinical';
    document.getElementById('dossierTitle').innerText = `${v.volunteer_id} — ${v.full_name}`;
    document.getElementById('dossierSubtitle').innerText = `Badge: "${v.badge_name}" • ${isClin ? 'Clinical Track A' : 'Non-Clinical Track B'}`;

    const html = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 rounded-2xl bg-surface-container border border-outline-variant/20">
                <h5 class="text-xs font-bold text-primary uppercase tracking-wider mb-2">Core Contact & Identity</h5>
                <div class="text-xs space-y-1">
                    <div><strong>Legal Name:</strong> ${v.full_name}</div>
                    <div><strong>Badge Call Name:</strong> "${v.badge_name}"</div>
                    <div><strong>NRIC / Passport:</strong> ${v.id_or_passport}</div>
                    <div><strong>WhatsApp Phone:</strong> ${v.phone_number}</div>
                    <div><strong>Email:</strong> ${v.email}</div>
                    <div><strong>Vetting Status:</strong> <span class="badge ${v.vetting_status === 'approved' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-amber-500/10 text-amber-700'} px-2 py-0.5 rounded-full font-bold">${v.vetting_status.toUpperCase()}</span></div>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-surface-container border border-outline-variant/20">
                <h5 class="text-xs font-bold text-primary uppercase tracking-wider mb-2">Emergency Contact</h5>
                <div class="text-xs space-y-1">
                    <div><strong>Name:</strong> ${v.emergency_contact_name}</div>
                    <div><strong>Relationship:</strong> ${v.emergency_contact_relation}</div>
                    <div><strong>Phone:</strong> ${v.emergency_contact_phone}</div>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-surface-container border border-outline-variant/20">
                <h5 class="text-xs font-bold text-primary uppercase tracking-wider mb-2">Logistics & Operations</h5>
                <div class="text-xs space-y-1">
                    <div><strong>T-Shirt Size:</strong> Size ${v.t_shirt_size}</div>
                    <div><strong>Dietary Preference:</strong> ${v.dietary_preference}</div>
                    <div><strong>Has Own Transport:</strong> ${v.has_own_transport == 1 ? 'Yes' : 'No'}</div>
                    <div><strong>Languages Spoken:</strong> ${v.languages_spoken}</div>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-surface-container border border-outline-variant/20">
                <h5 class="text-xs font-bold text-primary uppercase tracking-wider mb-2">Track Profile (${isClin ? 'clinical_profiles' : 'non_clinical_profiles'})</h5>
                <div class="text-xs space-y-1">
                    ${isClin ? `
                        <div><strong>Cadre:</strong> ${v.cadre || '—'}</div>
                        <div><strong>Council No:</strong> ${v.council_reg_number || '—'}</div>
                        <div><strong>APC Expiry:</strong> ${v.apc_expiry_date || '—'}</div>
                        <div><strong>Specialty:</strong> ${v.specialty || '—'}</div>
                        <div><strong>Life Support Certified:</strong> ${v.is_life_support_certified == 1 ? 'Yes (Exp: ' + (v.life_support_expiry || '—') + ')' : 'No'}</div>
                    ` : `
                        <div><strong>Occupation:</strong> ${v.occupation || '—'}</div>
                        <div><strong>Skills:</strong> ${v.key_skills || '—'}</div>
                        <div><strong>Physical Limitations:</strong> ${v.physical_limitations || '—'}</div>
                    `}
                    <div class="pt-2 border-t border-outline-variant/20">
                        <strong>Deployment:</strong> ${v.station_name ? v.station_name + ' (' + (v.shift_name || '') + ')' : '<span class="text-on-surface-variant">Not deployed yet</span>'}
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('dossierBody').innerHTML = html;
    var modal = document.getElementById('viewDossierModal');
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    var bodyEl = document.getElementById('dossierBody');
    if (bodyEl) bodyEl.scrollTop = 0;
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'hidden';
}

function closeDossierModal() {
    var modal = document.getElementById('viewDossierModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'auto';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
