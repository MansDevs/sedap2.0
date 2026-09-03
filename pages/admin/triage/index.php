<?php
/**
 * ============================================================================
 *   SeDaP 2.0 — Triage Assessment & Urgency Classification Management
 *   Section 1: Personal Information & Background (Demographics)
 *   Section 2.1: Vital Signs Screening & Basic Laboratory Tests
 *   Section 2.2: Medical History & Volunteer Interview Findings
 *   Section 2.3: Urgency Rating & Triage System (Green / Yellow / Red Single-Select)
 * ============================================================================
 */
$adminBase = '../';
$activeNav = 'triage';
$pageTitle = 'Triage Management';
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
    header('Content-Disposition: attachment; filename=sedap_triage_records_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Triage ID', 'Patient Name', 'IC Number / ID', 'Phone',
        'Age', 'Gender', 'Occupation', 'Education Level',
        'Temperature (°C)', 'Blood Pressure', 'Glucose (mmol/L)', 'Lipid (mmol/L)',
        'Acute Symptoms', 'Medical History', 'Interview Notes',
        'Triage Category', 'Status', 'Triaged At'
    ]);

    $stmt = $pdo->query("
        SELECT tr.*, p.full_name AS patient_name, p.ic_number AS patient_ic, p.phone AS patient_phone,
               p.gender AS patient_gender, p.registration_number AS sedap_id
        FROM triage_records tr
        LEFT JOIN patients p ON tr.patient_id = p.id
        ORDER BY tr.triaged_at DESC
    ");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($records as $r) {
        $symptomsText = $r['symptoms'] ?? '';
        if (str_starts_with(trim($symptomsText), '[')) {
            $dec = json_decode($symptomsText, true);
            $symptomsText = is_array($dec) ? implode(', ', $dec) : $symptomsText;
        }

        fputcsv($output, [
            $r['triage_id'] ?: ('TI-' . str_pad($r['id'], 3, '0', STR_PAD_LEFT)),
            $r['full_name'] ?: ($r['patient_name'] ?? '—'),
            $r['ic_number'] ?: ($r['patient_ic'] ?: ($r['sedap_id'] ?: '—')),
            $r['phone_number'] ?: ($r['patient_phone'] ?? '—'),
            $r['age'] ?? '—',
            ucfirst($r['gender'] ?: ($r['patient_gender'] ?? '')),
            $r['occupation'] ?? '—',
            $r['education_level'] ?? '—',
            $r['temperature'] ?? '—',
            $r['blood_pressure'] ?? '—',
            $r['glucose_level'] ?? '—',
            $r['lipid_profile'] ?? '—',
            $symptomsText,
            $r['medical_history'] ?? '—',
            $r['interview_notes'] ?? '—',
            ucfirst($r['triage_level'] ?? ''),
            ucfirst($r['status'] ?? ''),
            $r['triaged_at']
        ]);
    }
    fclose($output);
    exit;
}

// ---------------------------------------------------------------------------
// 2. Handle Triage Record Deletion
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_triage') {
    $delId = (int)($_POST['triage_id'] ?? 0);
    if ($delId > 0) {
        try {
            $pdo->prepare("DELETE FROM triage_records WHERE id = ?")->execute([$delId]);
            $msg = "Triage record #TRG-" . str_pad($delId, 5, '0', STR_PAD_LEFT) . " has been removed.";
        } catch (Exception $e) {
            $err = "Failed to delete triage record: " . $e->getMessage();
        }
    }
}

// ---------------------------------------------------------------------------
// 3. Handle New Triage Assessment Submission
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add_triage', 'edit_triage'])) {
    try {
        $editRecordId = !empty($_POST['triage_record_id']) ? (int)$_POST['triage_record_id'] : null;
        $isEdit = (($_POST['action'] ?? '') === 'edit_triage' && $editRecordId !== null);

        // Section 1: Demographics
        $fullName       = trim($_POST['full_name'] ?? '');
        $icNumber       = trim($_POST['ic_number'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');
        $age            = !empty($_POST['age']) ? (int)$_POST['age'] : null;
        $gender         = in_array($_POST['gender'] ?? '', ['male', 'female']) ? $_POST['gender'] : 'male';
        $occupation     = trim($_POST['occupation'] ?? '');
        $educationLevel = trim($_POST['education_level'] ?? 'Secondary School');

        // Section 2.1: Vital Signs & Lab Tests
        $temperature    = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
        $bloodPressure  = trim($_POST['blood_pressure'] ?? '');
        $rawSymptoms    = $_POST['symptoms'] ?? '';
        if (is_array($rawSymptoms)) {
            $symptoms = trim(implode(', ', array_filter($rawSymptoms)));
        } else {
            $symptoms = trim((string)$rawSymptoms);
        }

        // Section 2.2: Medical History & Volunteer Interview Notes
        $medicalHistory = trim($_POST['medical_history'] ?? '');
        $interviewNotes = trim($_POST['interview_notes'] ?? '');

        // Section 2.3: Urgency Rating (Green / Yellow / Red Single-Select)
        $triageLevel    = in_array($_POST['triage_level'] ?? '', ['green', 'yellow', 'red']) ? $_POST['triage_level'] : 'green';

        if (empty($fullName)) {
            throw new Exception("Full Name of Patient is required.");
        }

        if (empty($symptoms)) {
            throw new Exception("Please write the patient's Main / Acute Symptoms.");
        }

        $cleanIC = substr(preg_replace('/[^0-9]/', '', $icNumber), 0, 12);
        $chiefComplaint = mb_substr($symptoms, 0, 250);

        $pdo->beginTransaction();

        if ($isEdit && $editRecordId) {
            $curr = $pdo->prepare("SELECT * FROM triage_records WHERE id = ?");
            $curr->execute([$editRecordId]);
            $existingTr = $curr->fetch(PDO::FETCH_ASSOC);

            if (!$existingTr) {
                throw new Exception("Triage assessment record not found.");
            }

            $patientId = $existingTr['patient_id'];
            if ($patientId) {
                $stmtPUpdate = $pdo->prepare("
                    UPDATE patients 
                    SET full_name = ?, ic_number = IF(? != '', ?, ic_number), phone = ?, gender = ?
                    WHERE id = ?
                ");
                $stmtPUpdate->execute([$fullName, $cleanIC, $cleanIC, $phone, $gender, $patientId]);
            }

            $stmtTUpdate = $pdo->prepare("
                UPDATE triage_records SET
                    full_name = ?, ic_number = ?, phone_number = ?,
                    age = ?, gender = ?, occupation = ?, education_level = ?,
                    triage_level = ?, chief_complaint = ?, blood_pressure = ?, temperature = ?,
                    glucose_level = ?, lipid_profile = ?, symptoms = ?, medical_history = ?, interview_notes = ?
                WHERE id = ?
            ");
            $stmtTUpdate->execute([
                $fullName, $cleanIC, $phone,
                $age, ucfirst($gender), $occupation, $educationLevel,
                $triageLevel, $chiefComplaint, $bloodPressure, $temperature,
                $glucoseLevel, $lipidProfile, $symptoms, $medicalHistory, $interviewNotes,
                $editRecordId
            ]);

            $pdo->commit();
            $customId = $existingTr['triage_id'] ?: ('TI-' . str_pad($editRecordId, 3, '0', STR_PAD_LEFT));
            $msg = "Triage assessment {$customId} for " . htmlspecialchars($fullName) . " updated successfully.";
        } else {
            // 1. Find or create Patient record
            $patientId = null;
            if (!empty($icNumber)) {
                $pCheck = $pdo->prepare("SELECT id FROM patients WHERE ic_number = ? LIMIT 1");
                $pCheck->execute([$icNumber]);
                $patientId = $pCheck->fetchColumn();
            }

            if (!$patientId) {
                // Generate SeDaP Registration Number (e.g. PT-00001)
                $nextNum = $pdo->query("SELECT IFNULL(MAX(id), 0) + 1 FROM patients")->fetchColumn();
                $regNumber = 'PT-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

                $stmtP = $pdo->prepare("
                    INSERT INTO patients (registration_number, full_name, ic_number, gender, phone, status, registered_by, created_at)
                    VALUES (?, ?, ?, ?, ?, 'in_triage', ?, NOW())
                ");
                $stmtP->execute([$regNumber, $fullName, $icNumber, $gender, $phone, $_SESSION['user_id'] ?? null]);
                $patientId = (int)$pdo->lastInsertId();
            } else {
                // Update existing patient basic info if empty
                $stmtPUpdate = $pdo->prepare("
                    UPDATE patients 
                    SET full_name = ?, phone = IF(phone IS NULL OR phone = '', ?, phone), gender = ?, status = 'in_triage'
                    WHERE id = ?
                ");
                $stmtPUpdate->execute([$fullName, $phone, $gender, $patientId]);
            }

            // 2. Insert Triage Record
            $nextTriageNum = (int)$pdo->query("SELECT IFNULL(MAX(id), 0) + 1 FROM triage_records")->fetchColumn();
            $triageCustomId = 'TI-' . str_pad($nextTriageNum, 3, '0', STR_PAD_LEFT);

            $stmtT = $pdo->prepare("
                INSERT INTO triage_records (
                    triage_id, patient_id, full_name, ic_number, phone_number,
                    age, gender, occupation, education_level,
                    triage_level, chief_complaint, blood_pressure, temperature,
                    glucose_level, lipid_profile, symptoms, medical_history, interview_notes,
                    status, triaged_by, triaged_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    'waiting', ?, NOW()
                )
            ");
            $stmtT->execute([
                $triageCustomId, $patientId, $fullName, $cleanIC, $phone,
                $age, ucfirst($gender), $occupation, $educationLevel,
                $triageLevel, $chiefComplaint, $bloodPressure, $temperature,
                $glucoseLevel, $lipidProfile, $symptoms, $medicalHistory, $interviewNotes,
                $_SESSION['user_id'] ?? 1
            ]);
            $triageId = (int)$pdo->lastInsertId();

            $pdo->commit();
            $msg = "Triage assessment {$triageCustomId} for " . htmlspecialchars($fullName) . " saved successfully.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $err = "Triage submission failed: " . $e->getMessage();
    }
}

// ---------------------------------------------------------------------------
// 4. Fetch Triage Records & KPI Metrics
// ---------------------------------------------------------------------------
$triageRecords = [];
$totalToday = 0;
$redCount = 0;
$yellowCount = 0;
$greenCount = 0;

try {
    $tStmt = $pdo->query("
        SELECT tr.*, p.full_name AS patient_name, p.ic_number AS patient_ic, p.phone AS patient_phone,
               p.gender AS patient_gender, p.registration_number AS sedap_id
        FROM triage_records tr
        LEFT JOIN patients p ON tr.patient_id = p.id
        ORDER BY tr.triaged_at DESC
    ");
    $triageRecords = $tStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($triageRecords as $tr) {
        $level = strtolower($tr['triage_level']);
        if ($level === 'red') $redCount++;
        elseif ($level === 'yellow') $yellowCount++;
        elseif ($level === 'green') $greenCount++;
        
        if (date('Y-m-d', strtotime($tr['triaged_at'])) === date('Y-m-d')) {
            $totalToday++;
        }
    }
} catch (Exception $e) {
    $triageRecords = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
  .input-24 { border-radius: 24px !important; padding: 0.65rem 1.25rem !important; }
  .textarea-24 { border-radius: 20px !important; padding: 0.75rem 1.25rem !important; }
  .triage-radio-card {
    border: 2px solid transparent;
    transition: all 0.25s ease;
    cursor: pointer;
  }
  .triage-radio-card:hover {
    transform: translateY(-2px);
  }
  .triage-radio-card.active-green {
    border-color: #10b981 !important;
    background-color: rgba(16, 185, 129, 0.08) !important;
  }
  .triage-radio-card.active-yellow {
    border-color: #f59e0b !important;
    background-color: rgba(245, 158, 11, 0.08) !important;
  }
  .triage-radio-card.active-red {
    border-color: #ef4444 !important;
    background-color: rgba(239, 68, 68, 0.08) !important;
  }
</style>

<div class="space-y-6 pb-12">
    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="font-headline text-2xl sm:text-3xl font-bold text-on-surface flex items-center gap-3">
                <span class="material-symbols-outlined text-[32px] text-primary">emergency</span>
                <span>Clinical Triage & Urgency Classification</span>
            </h1>
        </div>
        <div class="flex items-center gap-3 shrink-0 flex-wrap sm:flex-nowrap">
            <a href="index.php?export=csv" class="inline-flex items-center gap-2 bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold px-4 py-2.5 rounded-[24px] border border-outline-variant/40 shadow-sm transition-all text-sm whitespace-nowrap shrink-0">
                <span class="material-symbols-outlined text-[20px]">download</span>
                <span class="whitespace-nowrap">Export CSV</span>
            </a>
            <button onclick="openTriageModal()" class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-on-primary font-semibold px-5 py-2.5 rounded-[24px] shadow-sm transition-all duration-200 hover:shadow text-sm whitespace-nowrap shrink-0">
                <span class="material-symbols-outlined text-[20px]">add_circle</span>
                <span class="whitespace-nowrap">New Triage Assessment</span>
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($msg): ?>
        <div id="alertSuccess" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-[20px] flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                <div><?php echo htmlspecialchars($msg); ?></div>
            </div>
            <button type="button" onclick="this.closest('#alertSuccess').remove()" class="text-emerald-600 hover:text-emerald-800 p-1.5 rounded-full hover:bg-emerald-100 transition-colors">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div id="alertError" class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-[20px] flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-rose-600">error</span>
                <div><?php echo htmlspecialchars($err); ?></div>
            </div>
            <button type="button" onclick="this.closest('#alertError').remove()" class="text-rose-600 hover:text-rose-800 p-1.5 rounded-full hover:bg-rose-100 transition-colors">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Triaged</span>
                <span class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center material-symbols-outlined text-[22px]">assignment_turned_in</span>
            </div>
            <div class="text-2xl font-bold font-headline text-on-surface mt-2"><?php echo count($triageRecords); ?></div>
            <div class="text-xs text-on-surface-variant mt-1"><?php echo $totalToday; ?> triaged today</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-red-600 uppercase tracking-wider">Emergency</span>
                <span class="w-10 h-10 rounded-2xl bg-red-500/10 text-red-600 flex items-center justify-center material-symbols-outlined text-[22px]">warning</span>
            </div>
            <div class="text-2xl font-bold font-headline text-red-600 mt-2"><?php echo $redCount; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">Severe dehydration / critical</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Moderate</span>
                <span class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center material-symbols-outlined text-[22px]">pending</span>
            </div>
            <div class="text-2xl font-bold font-headline text-amber-600 mt-2"><?php echo $yellowCount; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">Requires medical treatment</div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[24px] p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Stable</span>
                <span class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center material-symbols-outlined text-[22px]">check_circle</span>
            </div>
            <div class="text-2xl font-bold font-headline text-emerald-600 mt-2"><?php echo $greenCount; ?></div>
            <div class="text-xs text-on-surface-variant mt-1">Non-urgent / general screening</div>
        </div>
    </div>

    <!-- Master Triage Board Table -->
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[28px] overflow-hidden shadow-sm">
        <div class="p-5 sm:p-6 border-b border-outline-variant/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">view_timeline</span>
                <h2 class="font-headline font-bold text-lg text-on-surface">Live Triage Assessment Board</h2>
            </div>
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <div class="w-full sm:w-72">
                    <input type="text" id="triageSearchInput" placeholder="Search Patient, IC, Zone..."
                           class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm px-4 py-2 input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                           oninput="searchTriageTable()">
                </div>
                <select id="urgencyFilter" onchange="searchTriageTable()" class="bg-surface-container border border-outline-variant/40 text-on-surface text-xs px-3 py-2 rounded-2xl focus:outline-none">
                    <option value="">All Categories</option>
                    <option value="red">Emergency</option>
                    <option value="yellow">Moderate</option>
                    <option value="green">Stable</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" id="triageTable">
                <thead class="bg-surface-container text-on-surface-variant text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">Triage ID</th>
                        <th class="py-3.5 px-4">Patient Name & IC</th>
                        <th class="py-3.5 px-4">Vitals (Temp / BP / Glucose)</th>
                        <th class="py-3.5 px-4">Main / Acute Symptoms</th>
                        <th class="py-3.5 px-4 text-center">Urgency Rating</th>
                        <th class="py-3.5 px-4">Timestamp</th>
                        <th class="py-3.5 px-6 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20 text-on-surface">
                    <?php if (empty($triageRecords)): ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center text-on-surface-variant">
                                No triage assessments recorded yet. <button type="button" onclick="openTriageModal()" class="text-primary font-semibold hover:underline">Click here to start a new assessment</button>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($triageRecords as $tr): 
                            $lvl = strtolower($tr['triage_level']);
                            $displayName = !empty($tr['full_name']) ? $tr['full_name'] : ($tr['patient_name'] ?? '—');
                            $displayIC = !empty($tr['ic_number']) ? $tr['ic_number'] : ($tr['patient_ic'] ?: ($tr['sedap_id'] ?: '—'));
                            $displayPhone = !empty($tr['phone_number']) ? $tr['phone_number'] : ($tr['patient_phone'] ?? '');
                            $displayAge = !empty($tr['age']) ? $tr['age'] : '';
                            $displayGender = !empty($tr['gender']) ? $tr['gender'] : ($tr['patient_gender'] ?? '');
                            
                            $sympText = $tr['symptoms'] ?? '';
                            if (str_starts_with(trim($sympText), '[')) {
                                $dec = json_decode($sympText, true);
                                $sympText = is_array($dec) ? implode(', ', $dec) : $sympText;
                            }
                        ?>
                            <tr class="hover:bg-surface-container/50 transition-colors triage-table-row" data-level="<?php echo $lvl; ?>">
                                <td class="py-4 px-6 font-semibold text-primary"><?php echo htmlspecialchars($tr['triage_id'] ?: 'TI-' . str_pad($tr['id'], 3, '0', STR_PAD_LEFT)); ?></td>
                                <td class="py-4 px-4 font-medium">
                                    <div class="font-bold text-on-surface"><?php echo htmlspecialchars($displayName); ?></div>
                                    <div class="text-xs text-on-surface-variant font-mono mt-0.5">
                                        <span><?php echo htmlspecialchars($displayIC); ?></span>
                                        <?php if (!empty($displayAge)): ?> • <?php echo $displayAge; ?> yrs<?php endif; ?>
                                        <?php if (!empty($displayGender)): ?> (<?php echo ucfirst($displayGender); ?>)<?php endif; ?>
                                    </div>
                                    <?php if (!empty($displayPhone)): ?>
                                        <div class="text-[11px] text-primary/80 font-mono flex items-center gap-1 mt-0.5">
                                            <span class="material-symbols-outlined text-[13px]">call</span>
                                            <span><?php echo htmlspecialchars($displayPhone); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 text-xs">
                                    <div><strong>Temp:</strong> <?php echo $tr['temperature'] ? $tr['temperature'] . ' °C' : '—'; ?></div>
                                    <div class="text-on-surface-variant"><strong>BP:</strong> <?php echo htmlspecialchars($tr['blood_pressure'] ?: '—'); ?> | <strong>Gluc:</strong> <?php echo $tr['glucose_level'] ? $tr['glucose_level'] . ' mmol/L' : '—'; ?></div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="text-xs font-semibold text-red-700 max-w-xs break-words">
                                        <?php echo nl2br(htmlspecialchars($sympText ?: '—')); ?>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <?php if ($lvl === 'red'): ?>
                                        <span class="inline-flex items-center gap-1.5 bg-red-500/10 text-red-600 border border-red-200 font-bold text-xs px-3 py-1 rounded-full">
                                            <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                                            <span>Emergency</span>
                                        </span>
                                    <?php elseif ($lvl === 'yellow'): ?>
                                        <span class="inline-flex items-center gap-1.5 bg-amber-500/10 text-amber-700 border border-amber-200 font-bold text-xs px-3 py-1 rounded-full">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            <span>Moderate</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 bg-emerald-500/10 text-emerald-700 border border-emerald-200 font-bold text-xs px-3 py-1 rounded-full">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span>Stable</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-4 text-xs text-on-surface-variant">
                                    <div><?php echo date('d M Y', strtotime($tr['triaged_at'])); ?></div>
                                    <div class="font-mono text-[11px]"><?php echo date('h:i A', strtotime($tr['triaged_at'])); ?></div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" onclick='viewTriageModal(<?php echo htmlspecialchars(json_encode($tr), ENT_QUOTES, 'UTF-8'); ?>)'
                                                class="inline-flex items-center gap-1 text-primary hover:text-primary/80 font-semibold text-xs bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-full transition-colors" title="View Assessment Details">
                                            <span class="material-symbols-outlined text-[16px]">visibility</span>
                                            <span>View</span>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this triage assessment record?');" class="inline">
                                            <input type="hidden" name="action" value="delete_triage">
                                            <input type="hidden" name="triage_id" value="<?php echo $tr['id']; ?>">
                                            <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-full transition-colors" title="Delete Record">
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
<!-- MODAL: NEW TRIAGE ASSESSMENT (SECTIONS 1 & 2 AS SPECIFIED)     -->
<!-- ============================================================= -->
<div id="triageModal" class="fixed inset-0 z-[99999] hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6" style="display: none;" onclick="closeTriageModal()">
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col h-[92vh] max-h-[880px] text-left transform transition-all animate-scale-up" onclick="event.stopPropagation()">
        
        <!-- Modal Header with Live Timestamp -->
        <div class="px-6 py-5 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[24px]">emergency</span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-lg text-on-surface" id="triageModalTitle">Clinical Triage & Screening Assessment</h3>
                </div>
            </div>
            <button type="button" onclick="closeTriageModal()" class="text-on-surface-variant hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <!-- Triage Assessment Form -->
        <form method="POST" id="triageForm" class="overflow-y-auto p-6 space-y-6 flex-1">
            <input type="hidden" name="action" id="triage_form_action" value="add_triage">
            <input type="hidden" name="triage_record_id" id="triage_record_id" value="">

            <!-- ============================================================= -->
            <!-- SECTION 1: PERSONAL INFORMATION & BACKGROUND (DEMOGRAPHICS)    -->
            <!-- ============================================================= -->
            <div class="space-y-4">
                <div class="bg-primary/5 border-l-4 border-primary p-3.5 rounded-2xl">
                    <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">person</span>
                        <span>Section 1: Personal Information & Background (Demographics)</span>
                    </h4>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1">Full Name of Patient <span class="text-rose-500">*</span></label>
                        <input type="text" name="full_name" id="trg_full_name" required placeholder="e.g. Siti Nurhaliza binti Tarudin"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1">Identity Card (IC) No. / SeDaP ID</label>
                        <input type="text" name="ic_number" id="trg_ic_number" placeholder="e.g. 920512105432 or PT-0012" maxlength="20"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                               oninput="autoDetectTriageIC(this.value)">
                        <div class="text-[11px] text-on-surface-variant mt-1" id="trg_ic_hint">12 digits without hyphen or Patient ID</div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Phone Number</label>
                        <input type="tel" name="phone" id="trg_phone" placeholder="e.g. 012-3456789"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Age</label>
                        <input type="number" name="age" id="trg_age" placeholder="e.g. 32" min="0" max="120"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Gender</label>
                        <select name="gender" id="trg_gender" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="male">Male (Lelaki)</option>
                            <option value="female">Female (Perempuan)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1">Occupation</label>
                        <input type="text" name="occupation" id="trg_occupation" placeholder="e.g. Guru, Peniaga, Suri Rumah, Pelajar"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-on-surface mb-1">Education Level</label>
                        <select name="education_level" class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="Secondary School">Secondary School (Sekolah Menengah)</option>
                            <option value="Primary School">Primary School (Sekolah Rendah)</option>
                            <option value="Diploma / Degree">Diploma / Degree (Diploma / Ijazah)</option>
                            <option value="No Formal Education">No Formal Education (Tiada Pendidikan Formal)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- SECTION 2.1: VITAL SIGNS & BASIC LABORATORY TESTS             -->
            <!-- ============================================================= -->
            <div class="space-y-4 pt-4 border-t border-outline-variant/20">
                <div class="bg-blue-500/5 border-l-4 border-blue-600 p-3.5 rounded-2xl">
                    <h4 class="font-headline font-bold text-sm text-blue-700 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">vital_signs</span>
                        <span>Section 2.1: Vital Signs Screening & Basic Laboratory Tests</span>
                    </h4>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Body Temperature (°C)</label>
                        <input type="number" step="0.1" name="temperature" id="trg_temp" placeholder="36.8"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                               oninput="evaluateTriageUrgency()">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Blood Pressure (BP)</label>
                        <input type="text" name="blood_pressure" placeholder="e.g. 120/80 mmHg"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Blood Glucose Level (mmol/L)</label>
                        <input type="number" step="0.1" name="glucose_level" id="trg_glucose" placeholder="e.g. 5.6"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Lipid Profile Reading (mmol/L)</label>
                        <input type="number" step="0.1" name="lipid_profile" placeholder="e.g. 4.8"
                               class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm input-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                </div>

                <!-- Main / Acute Symptoms Written Area -->
                <div>
                    <label class="block text-xs font-bold text-red-600 uppercase tracking-wider mb-2 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px]">sick</span>
                            <span>Main / Acute Symptoms (Catatan Gejala Utama & Akut) <span class="text-red-500 font-black">*</span></span>
                        </span>
                        <span class="text-[11px] font-normal text-red-500 lowercase">(Required — write symptoms)</span>
                    </label>
                    <textarea name="symptoms" id="trg_symptoms" rows="3" required placeholder="Tulis gejala utama / akut pesakit di sini (cth: Cirit-birit 3 kali sehari, muntah berterusan, demam panas 38.5°C, sakit perut memulas, pening kepala...)"
                              class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-sm textarea-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"
                              oninput="evaluateTriageUrgency()"></textarea>
                    <p class="text-[11px] text-on-surface-variant mt-1">Admin / petugas menulis butiran simptom pesakit secara terus.</p>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- SECTION 2.2: MEDICAL HISTORY & INTERVIEW FINDINGS             -->
            <!-- ============================================================= -->
            <div class="space-y-4 pt-4 border-t border-outline-variant/20">
                <div class="bg-amber-500/5 border-l-4 border-amber-600 p-3.5 rounded-2xl">
                    <h4 class="font-headline font-bold text-sm text-amber-700 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">history_edu</span>
                        <span>Section 2.2: Medical History & Interview Findings</span>
                    </h4>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Pre-existing Medical Conditions</label>
                        <textarea name="medical_history" rows="2" placeholder="e.g. Diabetes, Hypertension, Gastritis, Drug Allergies (Penicillin)..."
                                  class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-xs textarea-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-on-surface mb-1">Volunteer Interview Notes</label>
                        <textarea name="interview_notes" rows="2" placeholder="Written details regarding suspected cause, symptom onset time, dietary intake, or additional complaints..."
                                  class="w-full bg-surface-container border border-outline-variant/40 text-on-surface text-xs textarea-24 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20"></textarea>
                    </div>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- SECTION 2.3: URGENCY RATING & SYSTEM (SINGLE SELECT ONLY)     -->
            <!-- ============================================================= -->
            <div class="space-y-4 pt-4 border-t border-outline-variant/20">
                <div class="bg-primary/5 border-l-4 border-primary p-3.5 rounded-2xl flex items-center justify-between">
                    <div>
                        <h4 class="font-headline font-bold text-sm text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">traffic</span>
                            <span>Section 2.3: Urgency Rating & System (Triage Category)</span>
                        </h4>
                        <p class="text-xs text-on-surface-variant mt-0.5">Please select exactly ONE triage urgency level for this patient assessment</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <!-- GREEN TICKET -->
                    <label class="triage-radio-card active-green p-4 rounded-2xl bg-surface-container border border-emerald-300 relative flex items-start gap-3" id="card_green" onclick="selectTriageCard('green')">
                        <input type="radio" name="triage_level" id="radio_green" value="green" checked class="mt-1 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                                <span class="font-bold text-xs text-emerald-800 uppercase tracking-wider">Green (Hijau)</span>
                            </div>
                            <div class="text-xs text-emerald-900/80 font-medium mt-1">Non-urgent / stable / general screening</div>
                        </div>
                    </label>

                    <!-- YELLOW TICKET -->
                    <label class="triage-radio-card p-4 rounded-2xl bg-surface-container border border-outline-variant/40 relative flex items-start gap-3" id="card_yellow" onclick="selectTriageCard('yellow')">
                        <input type="radio" name="triage_level" id="radio_yellow" value="yellow" class="mt-1 text-amber-600 focus:ring-amber-500">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                                <span class="font-bold text-xs text-amber-800 uppercase tracking-wider">Yellow (Kuning)</span>
                            </div>
                            <div class="text-xs text-amber-900/80 font-medium mt-1">Requires treatment / moderate symptoms</div>
                        </div>
                    </label>

                    <!-- RED TICKET -->
                    <label class="triage-radio-card p-4 rounded-2xl bg-surface-container border border-outline-variant/40 relative flex items-start gap-3" id="card_red" onclick="selectTriageCard('red')">
                        <input type="radio" name="triage_level" id="radio_red" value="red" class="mt-1 text-red-600 focus:ring-red-500">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                <span class="font-bold text-xs text-red-800 uppercase tracking-wider">Red (Merah)</span>
                            </div>
                            <div class="text-xs text-red-900/80 font-medium mt-1">Emergency / severe dehydration</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="pt-4 border-t border-outline-variant/20 flex items-center justify-between shrink-0">
                <button type="button" onclick="closeTriageModal()" class="px-5 py-2.5 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold rounded-full transition-colors">
                    Cancel
                </button>
                <button type="submit" class="inline-flex items-center gap-1.5 px-6 py-2.5 text-xs font-bold bg-primary hover:bg-primary/90 text-on-primary rounded-full shadow-md transition-all">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    <span id="triageSubmitBtnText">Save & Submit Triage Record</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================= -->
<!-- MODAL: VIEW TRIAGE ASSESSMENT DETAILS                         -->
<!-- ============================================================= -->
<div id="viewTriageModal" class="fixed inset-0 z-[99999] hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6" style="display: none;" onclick="closeViewTriageModal()">
    <div class="bg-surface-container-lowest border border-outline-variant/40 rounded-[32px] w-full max-w-3xl shadow-2xl overflow-hidden max-h-[92vh] flex flex-col m-auto text-left transform transition-all animate-scale-up" onclick="event.stopPropagation()">
        <div class="px-6 py-5 bg-surface-container-low border-b border-outline-variant/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[24px]">assignment_turned_in</span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-lg text-on-surface" id="viewTrgTitle">Triage Assessment Record</h3>
                    <p class="text-xs text-on-surface-variant" id="viewTrgSubtitle">Comprehensive clinical breakdown</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="editTriageFromModal()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary text-xs font-bold rounded-full transition-colors" title="Edit Assessment">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                    <span>Edit</span>
                </button>
                <button type="button" onclick="closeViewTriageModal()" class="text-on-surface-variant hover:text-on-surface p-1.5 rounded-full hover:bg-surface-container transition-colors">
                    <span class="material-symbols-outlined text-[22px]">close</span>
                </button>
            </div>
        </div>

        <div class="overflow-y-auto p-6 space-y-5 flex-1 text-sm" id="viewTrgBody">
            <!-- Injected dynamically -->
        </div>

        <div class="px-6 py-4 bg-surface-container-low border-t border-outline-variant/20 flex items-center justify-between shrink-0">
            <button type="button" onclick="editTriageFromModal()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary/90 text-on-primary text-xs font-bold rounded-full shadow-sm transition-colors">
                <span class="material-symbols-outlined text-[16px]">edit</span>
                <span>Edit This Assessment</span>
            </button>
            <button type="button" onclick="closeViewTriageModal()" class="px-5 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface text-xs font-semibold rounded-full transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    ['triageModal', 'viewTriageModal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && el.parentElement !== document.body) {
            document.body.appendChild(el);
        }
    });
});

let currentViewedTriage = null;

function openTriageModal() {
    currentViewedTriage = null;
    const form = document.getElementById('triageForm');
    if (form) form.reset();
    
    document.getElementById('triageModalTitle').innerText = 'Clinical Triage & Screening Assessment';
    document.getElementById('triage_form_action').value = 'add_triage';
    document.getElementById('triage_record_id').value = '';
    document.getElementById('triageSubmitBtnText').innerText = 'Save & Submit Triage Record';
    document.getElementById('trg_ic_hint').innerHTML = '12 digits without hyphen or Patient ID';

    var modal = document.getElementById('triageModal');
    if (!modal) return;
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'hidden';
    selectTriageCard('green');
}

function openEditTriageModal(tr) {
    if (!tr) return;
    currentViewedTriage = tr;
    closeViewTriageModal();

    const form = document.getElementById('triageForm');
    if (form) form.reset();

    const trgCode = tr.triage_id || ('TI-' + String(tr.id).padStart(3, '0'));
    document.getElementById('triageModalTitle').innerText = `Edit Triage Assessment (${trgCode})`;
    document.getElementById('triage_form_action').value = 'edit_triage';
    document.getElementById('triage_record_id').value = tr.id;
    document.getElementById('triageSubmitBtnText').innerText = 'Update Triage Record';

    // Section 1: Demographics
    const nameEl = document.querySelector('input[name="full_name"]');
    if (nameEl) nameEl.value = tr.full_name || tr.patient_name || '';

    const icEl = document.getElementById('trg_ic_number');
    if (icEl) icEl.value = tr.ic_number || tr.patient_ic || tr.sedap_id || '';

    const phoneEl = document.getElementById('trg_phone');
    if (phoneEl) phoneEl.value = tr.phone_number || tr.patient_phone || '';

    const ageEl = document.getElementById('trg_age');
    if (ageEl) ageEl.value = tr.age || '';

    const genderEl = document.getElementById('trg_gender');
    if (genderEl) genderEl.value = (tr.gender || tr.patient_gender || 'male').toLowerCase();

    const occEl = document.getElementById('trg_occupation');
    if (occEl) occEl.value = tr.occupation || '';

    const eduEl = document.querySelector('select[name="education_level"]');
    if (eduEl) eduEl.value = tr.education_level || 'Secondary School';

    // Section 2.1: Vitals & Lab Tests
    const tempEl = document.getElementById('trg_temp');
    if (tempEl) tempEl.value = tr.temperature || '';

    const bpEl = document.querySelector('input[name="blood_pressure"]');
    if (bpEl) bpEl.value = tr.blood_pressure || '';

    const glucEl = document.querySelector('input[name="glucose_level"]');
    if (glucEl) glucEl.value = tr.glucose_level || '';

    const lipEl = document.querySelector('input[name="lipid_profile"]');
    if (lipEl) lipEl.value = tr.lipid_profile || '';

    // Symptoms written field
    let sympVal = tr.symptoms || '';
    if (typeof sympVal === 'string' && sympVal.startsWith('[')) {
        try {
            const dec = JSON.parse(sympVal);
            if (Array.isArray(dec)) sympVal = dec.join(', ');
        } catch(e) {}
    }
    const sympEl = document.getElementById('trg_symptoms');
    if (sympEl) sympEl.value = sympVal;

    // Section 2.2: Medical History & Interview Notes
    const medEl = document.querySelector('textarea[name="medical_history"]');
    if (medEl) medEl.value = tr.medical_history || '';

    const noteEl = document.querySelector('textarea[name="interview_notes"]');
    if (noteEl) noteEl.value = tr.interview_notes || '';

    // Section 2.3: Urgency Level
    selectTriageCard((tr.triage_level || 'green').toLowerCase());

    var modal = document.getElementById('triageModal');
    if (!modal) return;
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'hidden';
}

function editTriageFromModal() {
    if (currentViewedTriage) {
        openEditTriageModal(currentViewedTriage);
    }
}

function closeTriageModal() {
    var modal = document.getElementById('triageModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'auto';
}

function selectTriageCard(level) {
    ['green', 'yellow', 'red'].forEach(l => {
        const card = document.getElementById('card_' + l);
        const radio = document.getElementById('radio_' + l);
        if (card && radio) {
            card.className = 'triage-radio-card p-4 rounded-2xl bg-surface-container border border-outline-variant/40 relative flex items-start gap-3';
            if (l === level) {
                radio.checked = true;
                card.classList.add('active-' + level);
                if (level === 'green') card.style.borderColor = '#10b981';
                else if (level === 'yellow') card.style.borderColor = '#f59e0b';
                else if (level === 'red') card.style.borderColor = '#ef4444';
            }
        }
    });
}

function evaluateTriageUrgency() {
    const temp = parseFloat(document.getElementById('trg_temp')?.value || 0);
    const symp = (document.getElementById('trg_symptoms')?.value || '').toLowerCase();
    
    // Auto recommendation if temperature high or severe keywords detected
    if (temp >= 39.0 || symp.includes('severe') || symp.includes('dehydration') || symp.includes('kritikal') || symp.includes('emergency')) {
        selectTriageCard('red');
    } else if (temp >= 38.0 || symp.length > 5) {
        selectTriageCard('yellow');
    }
}

function autoDetectTriageIC(ic) {
    const clean = ic.replace(/[^0-9]/g, '');
    const hint = document.getElementById('trg_ic_hint');
    if (clean.length === 12) {
        const yy = clean.substring(0, 2);
        const mm = clean.substring(2, 4);
        const dd = clean.substring(4, 6);
        const year = parseInt(yy, 10) > 40 ? '19' + yy : '20' + yy;
        const lastDigit = parseInt(clean.substring(11, 12), 10);
        const gender = (lastDigit % 2 === 0) ? 'female' : 'male';
        
        const birthYear = parseInt(year, 10);
        const currentYear = new Date().getFullYear();
        const age = Math.max(0, currentYear - birthYear);

        document.getElementById('trg_age').value = age;
        document.getElementById('trg_gender').value = gender;
        hint.innerHTML = `<span class="text-emerald-600 font-semibold">✓ Auto-detected DOB: ${dd}/${mm}/${year} • Age: ${age} yrs • Gender: ${gender === 'female' ? 'Female' : 'Male'}</span>`;
    } else {
        hint.innerHTML = `12 digits without hyphen or Patient ID`;
    }
}

function searchTriageTable() {
    const q = document.getElementById('triageSearchInput').value.toLowerCase();
    const lvl = document.getElementById('urgencyFilter').value.toLowerCase();
    const rows = document.querySelectorAll('.triage-table-row');

    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const rLvl = r.getAttribute('data-level') || '';
        const matchQ = text.includes(q);
        const matchLvl = !lvl || rLvl === lvl;
        r.style.display = (matchQ && matchLvl) ? '' : 'none';
    });
}

function viewTriageModal(tr) {
    currentViewedTriage = tr;
    const trgCode = tr.triage_id || ('TI-' + String(tr.id).padStart(3, '0'));
    const displayName = tr.full_name || tr.patient_name || 'Patient Assessment';
    document.getElementById('viewTrgTitle').innerText = `${trgCode} — ${displayName}`;
    document.getElementById('viewTrgSubtitle').innerText = `Registered: ${tr.triaged_at}`;

    let symptomsText = tr.symptoms || 'None Reported';
    if (typeof symptomsText === 'string' && symptomsText.startsWith('[')) {
        try {
            const dec = JSON.parse(symptomsText);
            if (Array.isArray(dec)) symptomsText = dec.join(', ');
        } catch(e) {}
    }
    const symptomsHtml = `<div class="p-3 bg-red-500/10 border border-red-200 rounded-2xl text-xs font-semibold text-red-700 leading-relaxed break-words">${symptomsText.replace(/\n/g, '<br>')}</div>`;

    const lvl = (tr.triage_level || 'green').toLowerCase();
    let lvlBadge = '';
    if (lvl === 'red') {
        lvlBadge = '<span class="px-3 py-1 rounded-full bg-red-500 text-white font-bold text-xs uppercase tracking-wider">🔴 Emergency</span>';
    } else if (lvl === 'yellow') {
        lvlBadge = '<span class="px-3 py-1 rounded-full bg-amber-500 text-white font-bold text-xs uppercase tracking-wider">🟡 Moderate</span>';
    } else {
        lvlBadge = '<span class="px-3 py-1 rounded-full bg-emerald-600 text-white font-bold text-xs uppercase tracking-wider">🟢 Stable</span>';
    }

    const html = `
        <div class="flex items-center justify-between bg-surface-container p-4 rounded-2xl">
            <div>
                <span class="text-xs text-on-surface-variant uppercase tracking-wider font-semibold">Triage Urgency Category:</span>
                <div class="mt-1">${lvlBadge}</div>
            </div>
            <div class="text-right">
                <span class="text-xs text-on-surface-variant uppercase tracking-wider font-semibold">Status:</span>
                <div class="font-bold text-sm text-primary uppercase mt-1">${tr.status || 'Waiting'}</div>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-surface-container space-y-2 text-xs">
            <h5 class="font-bold text-primary uppercase tracking-wider text-[11px]">Section 1: Demographics & Background</h5>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div><strong>Full Name:</strong> ${tr.full_name || tr.patient_name || '—'}</div>
                <div><strong>IC / SeDaP ID:</strong> ${tr.ic_number || tr.patient_ic || tr.sedap_id || '—'}</div>
                <div><strong>Phone Number:</strong> ${tr.phone_number || tr.patient_phone || '—'}</div>
                <div><strong>Age / Gender:</strong> ${tr.age || '—'} yrs (${tr.gender || tr.patient_gender || '—'})</div>
                <div><strong>Occupation:</strong> ${tr.occupation || '—'}</div>
                <div><strong>Education Level:</strong> ${tr.education_level || '—'}</div>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-surface-container space-y-2 text-xs">
            <h5 class="font-bold text-blue-700 uppercase tracking-wider text-[11px]">Section 2.1: Vital Signs & Laboratory Readings</h5>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div><strong>Temperature:</strong> ${tr.temperature ? tr.temperature + ' °C' : '—'}</div>
                <div><strong>Blood Pressure:</strong> ${tr.blood_pressure || '—'}</div>
                <div><strong>Blood Glucose:</strong> ${tr.glucose_level ? tr.glucose_level + ' mmol/L' : '—'}</div>
                <div><strong>Lipid Profile:</strong> ${tr.lipid_profile ? tr.lipid_profile + ' mmol/L' : '—'}</div>
            </div>
            <div class="pt-2 border-t border-outline-variant/15">
                <div class="font-semibold text-red-600 mb-1">Acute Symptoms Checklist:</div>
                <div>${symptomsHtml}</div>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-surface-container space-y-2 text-xs">
            <h5 class="font-bold text-amber-700 uppercase tracking-wider text-[11px]">Section 2.2: Medical History & Volunteer Interview</h5>
            <div>
                <strong>Pre-existing Conditions:</strong>
                <p class="mt-0.5 text-on-surface-variant">${tr.medical_history || 'None recorded'}</p>
            </div>
            <div class="pt-2 border-t border-outline-variant/15">
                <strong>Volunteer Interview Notes:</strong>
                <p class="mt-0.5 text-on-surface-variant">${tr.interview_notes || 'None recorded'}</p>
            </div>
        </div>
    `;

    document.getElementById('viewTrgBody').innerHTML = html;
    var modal = document.getElementById('viewTriageModal');
    if (!modal) return;
    if (modal.parentElement !== document.body) document.body.appendChild(modal);
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    var bodyEl = document.getElementById('viewTrgBody');
    if (bodyEl) bodyEl.scrollTop = 0;
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'hidden';
}

function closeViewTriageModal() {
    var modal = document.getElementById('viewTriageModal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    var mainEl = document.querySelector('main');
    if (mainEl) mainEl.style.overflow = 'auto';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
