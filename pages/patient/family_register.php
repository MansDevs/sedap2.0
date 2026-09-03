<?php
/**
 * ============================================================================
 *   SeDaP 2.0 — Family & Household Registration Module (Pendaftaran Isi Rumah)
 *   Multi-Step Wizard matching Updated SQL Schema:
 *   1. Household Table (Dwelling & Location)
 *   2. HeadOfHousehold Table (Primary Contact)
 *   3. Member Table (Demographics, Vulnerability, Chronic, Outbreak Screening, Food Exposure)
 * ============================================================================
 */
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId    = $_SESSION['user_id'];
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Pesakit');
$userEmail = htmlspecialchars($_SESSION['user_email'] ?? '');
$userPhone = htmlspecialchars($_SESSION['user_phone'] ?? '');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = sedap_root();

$successMsg = '';
$errorMsg   = '';

// ---------------------------------------------------------------------------
// 1. Process Household Registration (ACID Transaction)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_household') {
    try {
        // Step 1: Dwelling Data
        $street_address  = trim($_POST['street_address'] ?? '');
        $postal_code     = trim($_POST['postal_code'] ?? '');
        $city            = trim($_POST['city'] ?? '');
        $state           = trim($_POST['state'] ?? '');
        $house_type      = trim($_POST['house_type'] ?? 'Landed');
        
        // Step 2: Head of Household Data
        $head_ic         = trim($_POST['head_ic'] ?? '');
        $head_name       = trim($_POST['head_name'] ?? '');
        $head_phone      = trim($_POST['head_phone'] ?? '');
        $head_email      = trim($_POST['head_email'] ?? '');

        // Step 3: Members Data
        $membersData     = $_POST['members'] ?? [];
        $total_residents = max(1, count($membersData));

        if (empty($street_address) || empty($postal_code) || empty($head_ic) || empty($head_name)) {
            throw new Exception("Sila lengkapkan alamat kediaman dan maklumat ketua keluarga yang wajib.");
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
        $successMsg = "Pendaftaran Isi Rumah (ID: #HH-" . str_pad($householdId, 5, '0', STR_PAD_LEFT) . ") berjaya disimpan ke dalam pangkalan data SeDaP.";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorMsg = "Gagal mendaftar isi rumah: " . $e->getMessage();
    }
}

// ---------------------------------------------------------------------------
// 2. Fetch Latest Household Records for Overview / History
// ---------------------------------------------------------------------------
$latestHouseholds = [];
try {
    $hQuery = $pdo->query("
        SELECT h.*, head.full_name as head_name, head.phone_number as head_phone, head.ic_number as head_ic,
               (SELECT COUNT(*) FROM Member m WHERE m.household_id = h.household_id) as member_count,
               (SELECT COUNT(*) FROM Member m WHERE m.household_id = h.household_id AND (m.has_diarrhea = 1 OR m.has_vomiting = 1 OR m.has_fever = 1 OR m.is_affected_member = 1)) as symptom_count
        FROM Household h
        LEFT JOIN HeadOfHousehold head ON h.household_id = head.household_id
        ORDER BY h.household_id DESC
        LIMIT 5
    ");
    $latestHouseholds = $hQuery->fetchAll();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pendaftaran Isi Rumah & Profil Keluarga — SeDaP 2.0</title>
  
  <!-- CoreUI & SeDaP Styles -->
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

  <style>
    :root {
      --sedap-brand: #0284c7;
      --sedap-brand-dark: #0369a1;
      --sedap-surface: #ffffff;
      --sedap-surface-dim: #f8fafc;
      --sedap-border: #e2e8f0;
      --sedap-text-main: #0f172a;
      --sedap-text-muted: #64748b;
    }
    [data-coreui-theme="dark"] {
      --sedap-brand: #38bdf8;
      --sedap-brand-dark: #0284c7;
      --sedap-surface: #1e293b;
      --sedap-surface-dim: #0f172a;
      --sedap-border: #334155;
      --sedap-text-main: #f8fafc;
      --sedap-text-muted: #94a3b8;
    }
    body {
      font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
      background-color: var(--sedap-surface-dim);
      color: var(--sedap-text-main);
    }
    .wizard-card {
      background: var(--sedap-surface);
      border: 1px solid var(--sedap-border);
      border-radius: 24px;
      box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }
    .stepper-circle {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 15px;
      transition: all 0.3s ease;
      border: 2px solid var(--sedap-border);
      background: var(--sedap-surface);
      color: var(--sedap-text-muted);
    }
    .stepper-step.active .stepper-circle {
      background: var(--sedap-brand);
      color: #ffffff;
      border-color: var(--sedap-brand);
      box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.2);
    }
    .stepper-step.completed .stepper-circle {
      background: #10b981;
      color: #ffffff;
      border-color: #10b981;
    }
    .dwelling-card {
      cursor: pointer;
      border: 2px solid var(--sedap-border);
      border-radius: 16px;
      padding: 16px;
      text-align: center;
      transition: all 0.2s ease;
      background: var(--sedap-surface);
    }
    .dwelling-card:hover {
      border-color: var(--sedap-brand);
      transform: translateY(-2px);
    }
    .dwelling-card.selected {
      border-color: var(--sedap-brand);
      background: rgba(2, 132, 199, 0.08);
    }
    .member-box {
      border: 1px solid var(--sedap-border);
      border-radius: 20px;
      padding: 24px;
      background: var(--sedap-surface-dim);
      position: relative;
      margin-bottom: 24px;
      transition: all 0.3s ease;
    }
    .member-box:hover {
      border-color: rgba(2, 132, 199, 0.4);
    }
    .form-control, .form-select {
      border-radius: 12px;
      padding: 10px 14px;
      border-color: var(--sedap-border);
      font-size: 14px;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--sedap-brand);
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
  </style>
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    
    <div class="body flex-grow-1">
      <main class="container-fluid px-3 px-md-4 py-4 max-w-7xl mx-auto">
        
        <!-- Header Banner -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="material-symbols-outlined text-primary fs-3">family_restroom</span>
              <h1 class="h3 fw-bold mb-0">Pendaftaran Isi Rumah & Profil Keluarga</h1>
            </div>
            <p class="text-secondary small mb-0">
              Sistem pendaftaran data demografi, golongan rentan, saringan wabak (gastroenteritis/cirit-birit), dan rekod pendedahan makanan SeDaP 2.0.
            </p>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-2 d-flex align-items-center gap-1 shadow-sm" onclick="toggleRecordsModal()">
              <span class="material-symbols-outlined" style="font-size:18px;">history</span>
              <span class="small fw-semibold">Rekod Isi Rumah</span>
            </button>
          </div>
        </div>

        <?php if ($successMsg): ?>
          <div class="alert alert-success d-flex align-items-center gap-3 py-3 px-4 rounded-4 shadow-sm mb-4 border-0 bg-success text-white">
            <span class="material-symbols-outlined fs-2">verified</span>
            <div>
              <div class="fw-bold">Pendaftaran Berjaya!</div>
              <div class="small opacity-90"><?= htmlspecialchars($successMsg) ?></div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
          <div class="alert alert-danger d-flex align-items-center gap-3 py-3 px-4 rounded-4 shadow-sm mb-4 border-0 bg-danger text-white">
            <span class="material-symbols-outlined fs-2">error</span>
            <div>
              <div class="fw-bold">Ralat Pendaftaran</div>
              <div class="small opacity-90"><?= htmlspecialchars($errorMsg) ?></div>
            </div>
          </div>
        <?php endif; ?>

        <!-- ================================================================= -->
        <!-- MAIN STEPPER WIZARD CARD -->
        <!-- ================================================================= -->
        <div class="wizard-card p-4 p-md-5 mb-5">
          
          <!-- Stepper Progress Bar (4 Steps) -->
          <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center position-relative mb-3">
              <div class="position-absolute start-0 end-0 top-50 translate-middle-y bg-secondary bg-opacity-25" style="height: 3px; z-index: 1;"></div>
              <div class="position-absolute start-0 top-50 translate-middle-y bg-primary" id="wizardProgressLine" style="height: 3px; width: 0%; z-index: 2; transition: all 0.4s ease;"></div>

              <!-- Step 1 Indicator -->
              <div class="stepper-step active text-center position-relative" style="z-index: 3;" id="stepInd1" onclick="jumpToStep(1)">
                <div class="stepper-circle mx-auto mb-1">1</div>
                <div class="small fw-bold text-nowrap d-none d-md-block">1. Kediaman</div>
              </div>

              <!-- Step 2 Indicator -->
              <div class="stepper-step text-center position-relative" style="z-index: 3;" id="stepInd2" onclick="jumpToStep(2)">
                <div class="stepper-circle mx-auto mb-1">2</div>
                <div class="small fw-bold text-nowrap d-none d-md-block">2. Ketua Keluarga</div>
              </div>

              <!-- Step 3 Indicator -->
              <div class="stepper-step text-center position-relative" style="z-index: 3;" id="stepInd3" onclick="jumpToStep(3)">
                <div class="stepper-circle mx-auto mb-1">3</div>
                <div class="small fw-bold text-nowrap d-none d-md-block">3. Ahli & Kesihatan</div>
              </div>

              <!-- Step 4 Indicator -->
              <div class="stepper-step text-center position-relative" style="z-index: 3;" id="stepInd4" onclick="jumpToStep(4)">
                <div class="stepper-circle mx-auto mb-1">4</div>
                <div class="small fw-bold text-nowrap d-none d-md-block">4. Pengesahan</div>
              </div>
            </div>
          </div>

          <!-- Form Container -->
          <form method="POST" id="householdRegistrationForm">
            <input type="hidden" name="action" value="register_household">
            
            <!-- ============================================================= -->
            <!-- STEP 1: HOUSEHOLD & DWELLING INFO -->
            <!-- ============================================================= -->
            <div id="stepSection1" class="step-content">
              <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                <span class="material-symbols-outlined text-primary fs-4">home_pin</span>
                <h4 class="h5 fw-bold mb-0">Langkah 1: Maklumat Kediaman & Alamat Isi Rumah</h4>
              </div>

              <div class="row g-4">
                <div class="col-12">
                  <label class="form-label fw-semibold small text-secondary">Alamat Rumah (Street Address) <span class="text-danger">*</span></label>
                  <textarea name="street_address" id="h_street" class="form-control" rows="2" placeholder="Contoh: No. 18, Jalan Melati 4/2, Taman Sri Saujana" required></textarea>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold small text-secondary">Poskod (Postal Code) <span class="text-danger">*</span></label>
                  <input type="text" name="postal_code" id="h_postal" class="form-control" placeholder="43000" maxlength="10" required oninput="autoFillCityState(this.value)">
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold small text-secondary">Bandar (City) <span class="text-danger">*</span></label>
                  <input type="text" name="city" id="h_city" class="form-control" placeholder="Kajang" required>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold small text-secondary">Negeri (State) <span class="text-danger">*</span></label>
                  <select name="state" id="h_state" class="form-select" required>
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

                <div class="col-12">
                  <label class="form-label fw-semibold small text-secondary mb-3">Jenis Kediaman (House Type) <span class="text-danger">*</span></label>
                  <input type="hidden" name="house_type" id="selected_house_type" value="Landed">
                  <div class="row g-3">
                    <div class="col-6 col-md-3">
                      <div class="dwelling-card selected" onclick="selectDwelling(this, 'Landed')">
                        <span class="material-symbols-outlined text-primary fs-2 mb-2">cottage</span>
                        <div class="fw-bold small">Rumah Teres / Landed</div>
                      </div>
                    </div>
                    <div class="col-6 col-md-3">
                      <div class="dwelling-card" onclick="selectDwelling(this, 'Apartment')">
                        <span class="material-symbols-outlined text-primary fs-2 mb-2">apartment</span>
                        <div class="fw-bold small">Pangsapuri / Kondominium</div>
                      </div>
                    </div>
                    <div class="col-6 col-md-3">
                      <div class="dwelling-card" onclick="selectDwelling(this, 'Rental')">
                        <span class="material-symbols-outlined text-primary fs-2 mb-2">receipt_long</span>
                        <div class="fw-bold small">Rumah Sewa / Bilik</div>
                      </div>
                    </div>
                    <div class="col-6 col-md-3">
                      <div class="dwelling-card" onclick="selectDwelling(this, 'Owned')">
                        <span class="material-symbols-outlined text-primary fs-2 mb-2">roofing</span>
                        <div class="fw-bold small">Milik Sendiri / Pusaka</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-end mt-5">
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="validateStep1() && goToStep(2)">
                  <span>Seterusnya (Ketua Keluarga)</span>
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
                </button>
              </div>
            </div>

            <!-- ============================================================= -->
            <!-- STEP 2: HEAD OF HOUSEHOLD INFO -->
            <!-- ============================================================= -->
            <div id="stepSection2" class="step-content d-none">
              <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                <span class="material-symbols-outlined text-primary fs-4">person</span>
                <h4 class="h5 fw-bold mb-0">Langkah 2: Maklumat Ketua Keluarga (Head of Household)</h4>
              </div>

              <div class="row g-4">
                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">Nama Penuh (Mengikut MyKad) <span class="text-danger">*</span></label>
                  <input type="text" name="head_name" id="head_name" class="form-control" placeholder="Ahmad bin Abdullah" value="<?= $userName ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">No. Kad Pengenalan (MyKad) <span class="text-danger">*</span></label>
                  <input type="text" name="head_ic" id="head_ic" class="form-control" placeholder="Contoh: 850714105431" maxlength="14" required oninput="parseHeadIC(this.value)">
                  <div class="form-text small" id="head_ic_hint">12 digit tanpa sempang atau dengan sempang.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">Nombor Telefon Bimbit <span class="text-danger">*</span></label>
                  <input type="tel" name="head_phone" id="head_phone" class="form-control" placeholder="012-3456789" value="<?= $userPhone ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">Alamat Emel</label>
                  <input type="email" name="head_email" id="head_email" class="form-control" placeholder="ahmad@example.com" value="<?= $userEmail ?>">
                </div>
              </div>

              <div class="d-flex justify-content-between mt-5">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="goToStep(1)">
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span>
                  <span>Kembali</span>
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="validateStep2() && goToStep(3)">
                  <span>Seterusnya (Ahli Keluarga & Kesihatan)</span>
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
                </button>
              </div>
            </div>

            <!-- ============================================================= -->
            <!-- STEP 3: HOUSEHOLD MEMBERS & HEALTH INTAKE -->
            <!-- ============================================================= -->
            <div id="stepSection3" class="step-content d-none">
              <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 pb-2 border-bottom">
                <div>
                  <h4 class="h5 fw-bold mb-1 d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined text-primary">groups</span>
                    <span>Langkah 3: Profil Ahli Rumah, Kesihatan & Saringan Wabak</span>
                  </h4>
                  <p class="text-secondary small mb-0">
                    Sila daftarkan maklumat setiap ahli keluarga yang menetap bersama, termasuk kategori rentan, rekod kronik, dan gejala cirit-birit/muntah terkini.
                  </p>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-primary rounded-pill px-3 py-2 fs-6" id="totalMembersBadge">Jumlah Ahli: 0 Orang</span>
                  <button type="button" class="btn btn-sm btn-primary rounded-pill d-flex align-items-center gap-1 px-3 py-2 shadow-sm" onclick="addMemberCard()">
                    <span class="material-symbols-outlined" style="font-size:18px;">add</span>
                    <span class="small fw-bold">Tambah Ahli</span>
                  </button>
                </div>
              </div>

              <div id="membersContainer">
                <!-- Member Cards injected dynamically via JS -->
              </div>

              <div class="d-flex justify-content-between mt-5">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="goToStep(2)">
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span>
                  <span>Kembali</span>
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="validateStep3() && goToStep(4)">
                  <span>Seterusnya (Semakan & Pengesahan)</span>
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
                </button>
              </div>
            </div>

            <!-- ============================================================= -->
            <!-- STEP 4: REVIEW & CONFIRMATION -->
            <!-- ============================================================= -->
            <div id="stepSection4" class="step-content d-none">
              <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                <span class="material-symbols-outlined text-success fs-4">verified</span>
                <h4 class="h5 fw-bold mb-0">Langkah 4: Semakan & Pengesahan Pendaftaran</h4>
              </div>

              <div class="row g-4">
                <!-- Household Info Card -->
                <div class="col-md-6">
                  <div class="p-4 rounded-4 bg-surface border h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h6 class="fw-bold mb-0 d-flex align-items-center gap-2 text-primary">
                        <span class="material-symbols-outlined">home</span>
                        <span>Maklumat Kediaman</span>
                      </h6>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="goToStep(1)">Kemaskini</button>
                    </div>
                    <div class="small space-y-2">
                      <div><span class="text-secondary">Alamat:</span> <span class="fw-semibold" id="rev_address">—</span></div>
                      <div><span class="text-secondary">Bandar & Negeri:</span> <span class="fw-semibold" id="rev_city_state">—</span></div>
                      <div><span class="text-secondary">Jenis Kediaman:</span> <span class="fw-semibold" id="rev_house_type">—</span></div>
                    </div>
                  </div>
                </div>

                <!-- Head of Household Info Card -->
                <div class="col-md-6">
                  <div class="p-4 rounded-4 bg-surface border h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h6 class="fw-bold mb-0 d-flex align-items-center gap-2 text-primary">
                        <span class="material-symbols-outlined">person</span>
                        <span>Ketua Keluarga</span>
                      </h6>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="goToStep(2)">Kemaskini</button>
                    </div>
                    <div class="small space-y-2">
                      <div><span class="text-secondary">Nama Penuh:</span> <span class="fw-semibold" id="rev_head_name">—</span></div>
                      <div><span class="text-secondary">No. MyKad:</span> <span class="fw-semibold font-monospace" id="rev_head_ic">—</span></div>
                      <div><span class="text-secondary">No. Telefon:</span> <span class="fw-semibold" id="rev_head_phone">—</span></div>
                    </div>
                  </div>
                </div>

                <!-- Member Preview Table -->
                <div class="col-12">
                  <div class="p-4 rounded-4 bg-surface border">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h6 class="fw-bold mb-0 d-flex align-items-center gap-2 text-primary">
                        <span class="material-symbols-outlined">badge</span>
                        <span>Senarai Ahli Keluarga (<span id="rev_total_members">0 Orang</span>)</span>
                      </h6>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="goToStep(3)">Kemaskini</button>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-sm table-hover align-middle mb-0" id="reviewMembersTable">
                        <thead class="table-light small">
                          <tr>
                            <th>#</th>
                            <th>Nama Penuh</th>
                            <th>No. KP / MyKid</th>
                            <th>Hubungan</th>
                            <th>Jantina / Umur</th>
                            <th>Golongan Rentan</th>
                            <th>Saringan Gejala Wabak</th>
                          </tr>
                        </thead>
                        <tbody class="small" id="rev_members_body">
                          <!-- Injected via JS -->
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Confirmation Disclaimer -->
                <div class="col-12">
                  <div class="form-check p-3 rounded-3 border bg-surface-dim d-flex align-items-center gap-3">
                    <input class="form-check-input ms-0 me-2" type="checkbox" id="confirmDataCheck" required>
                    <label class="form-check-label small fw-semibold" for="confirmDataCheck">
                      Saya mengesahkan bahawa segala maklumat isi rumah, profil kesihatan, dan pendedahan makanan yang diberikan adalah benar dan tepat untuk tujuan rekod perubatan dan pemantauan wabak SeDaP 2.0.
                    </label>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between mt-5">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="goToStep(3)">
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span>
                  <span>Kembali</span>
                </button>
                <button type="submit" class="btn btn-success text-white rounded-pill px-5 py-2.5 d-flex align-items-center gap-2 fw-bold shadow-lg" id="submitBtn">
                  <span class="material-symbols-outlined">save</span>
                  <span>Sahkan & Simpan Pendaftaran Isi Rumah</span>
                </button>
              </div>
            </div>

          </form>
        </div>

        <!-- ================================================================= -->
        <!-- RECENTLY REGISTERED HOUSEHOLDS LIST -->
        <!-- ================================================================= -->
        <?php if (!empty($latestHouseholds)): ?>
        <div class="wizard-card p-4 p-md-5">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-2">
              <span class="material-symbols-outlined text-primary fs-4">history_edu</span>
              <h4 class="h5 fw-bold mb-0">Rekod Isi Rumah Terkini</h4>
            </div>
            <span class="badge bg-secondary rounded-pill px-3 py-1.5"><?= count($latestHouseholds) ?> Rekod Disimpan</span>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light small">
                <tr>
                  <th>ID Rumah</th>
                  <th>Ketua Keluarga</th>
                  <th>Alamat & Lokasi</th>
                  <th>Jenis Kediaman</th>
                  <th>Ahli</th>
                  <th>Status Saringan</th>
                  <th>Tarikh Daftar</th>
                </tr>
              </thead>
              <tbody class="small">
                <?php foreach ($latestHouseholds as $lh): ?>
                <tr>
                  <td class="fw-bold text-primary">#HH-<?= str_pad($lh['household_id'], 5, '0', STR_PAD_LEFT) ?></td>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($lh['head_name'] ?? '—') ?></div>
                    <div class="text-secondary" style="font-size:11px;"><?= htmlspecialchars($lh['head_phone'] ?? '—') ?></div>
                  </td>
                  <td>
                    <div class="text-truncate" style="max-width:250px;"><?= htmlspecialchars($lh['street_address']) ?></div>
                    <div class="text-secondary" style="font-size:11px;"><?= htmlspecialchars($lh['postal_code'] . ' ' . $lh['city'] . ', ' . $lh['state']) ?></div>
                  </td>
                  <td class="text-secondary"><?= htmlspecialchars($lh['house_type']) ?></td>
                  <td><span class="badge bg-primary rounded-pill"><?= (int)$lh['member_count'] ?> Orang</span></td>
                  <td>
                    <?php if (($lh['symptom_count'] ?? 0) > 0): ?>
                      <span class="badge bg-danger rounded-pill px-2.5 py-1"><?= (int)$lh['symptom_count'] ?> Bergejala</span>
                    <?php else: ?>
                      <span class="badge bg-success rounded-pill px-2.5 py-1">Tiada Gejala</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-secondary"><?= date('d M Y', strtotime($lh['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>

      </main>
    </div>
    <?php include '../shared/includes/footer.php'; ?>
  </div>

  <!-- CoreUI Bundle Scripts -->
  <script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>

  <!-- Dynamic Wizard & Members JS Engine -->
  <script>
    let currentStep = 1;
    let memberCount = 0;

    // Step navigation controller
    function goToStep(step) {
      if (step < 1 || step > 4) return;
      
      // Hide all steps
      for (let i = 1; i <= 4; i++) {
        const sec = document.getElementById('stepSection' + i);
        const ind = document.getElementById('stepInd' + i);
        if (sec) sec.classList.add('d-none');
        if (ind) {
          ind.classList.remove('active');
          if (i < step) ind.classList.add('completed');
          else ind.classList.remove('completed');
        }
      }

      // Show target step
      const targetSec = document.getElementById('stepSection' + step);
      const targetInd = document.getElementById('stepInd' + step);
      if (targetSec) targetSec.classList.remove('d-none');
      if (targetInd) targetInd.classList.add('active');

      if (step === 4) {
        buildReviewSummary();
      }

      // Update progress line
      const progressPercentage = ((step - 1) / 3) * 100;
      document.getElementById('wizardProgressLine').style.width = progressPercentage + '%';

      currentStep = step;
      window.scrollTo({ top: 120, behavior: 'smooth' });
    }

    function jumpToStep(step) {
      if (step > currentStep) {
        if (currentStep === 1 && !validateStep1()) return;
        if (currentStep === 2 && !validateStep2()) return;
        if (currentStep === 3 && !validateStep3()) return;
      }
      goToStep(step);
    }

    function validateStep1() {
      const street = document.getElementById('h_street').value.trim();
      const postal = document.getElementById('h_postal').value.trim();
      const city = document.getElementById('h_city').value.trim();
      if (!street || !postal || !city) {
        alert('Sila lengkapkan alamat kediaman, poskod, dan bandar.');
        return false;
      }
      return true;
    }

    function validateStep2() {
      const name = document.getElementById('head_name').value.trim();
      const ic = document.getElementById('head_ic').value.trim();
      const phone = document.getElementById('head_phone').value.trim();
      if (!name || !ic || !phone) {
        alert('Sila lengkapkan nama penuh, no. MyKad, dan telefon ketua keluarga.');
        return false;
      }
      syncHeadToMember1();
      return true;
    }

    function validateStep3() {
      const cards = document.querySelectorAll('#membersContainer .member-box');
      if (cards.length === 0) {
        alert('Sila daftarkan sekurang-kurangnya seorang ahli isi rumah.');
        return false;
      }
      for (let i = 0; i < cards.length; i++) {
        const nameInput = cards[i].querySelector(`[name*="[full_name]"]`);
        const icInput = cards[i].querySelector(`[name*="[national_id]"]`);
        if (!nameInput?.value.trim() || !icInput?.value.trim()) {
          alert(`Sila lengkapkan nama dan no. KP bagi Ahli #${i + 1}.`);
          return false;
        }
      }
      return true;
    }

    // Dwelling selection card handler
    function selectDwelling(element, type) {
      document.querySelectorAll('.dwelling-card').forEach(c => c.classList.remove('selected'));
      element.classList.add('selected');
      document.getElementById('selected_house_type').value = type;
    }

    // Postal code auto-lookup helper for Malaysia
    function autoFillCityState(postal) {
      if (postal.length === 5) {
        const p = parseInt(postal, 10);
        const stateSelect = document.getElementById('h_state');
        if (p >= 43000 && p <= 48000) stateSelect.value = 'Selangor';
        else if (p >= 50000 && p <= 60000) stateSelect.value = 'Kuala Lumpur';
        else if (p >= 62000 && p <= 62988) stateSelect.value = 'Putrajaya';
        else if (p >= 80000 && p <= 86900) stateSelect.value = 'Johor';
        else if (p >= 15000 && p <= 18500) stateSelect.value = 'Kelantan';
        else if (p >= 10000 && p <= 14400) stateSelect.value = 'Pulau Pinang';
        else if (p >= 30000 && p <= 36810) stateSelect.value = 'Perak';
      }
    }

    // Parse IC into date of birth & gender
    function parseHeadIC(ic) {
      const cleanIC = ic.replace(/[^0-9]/g, '');
      const hint = document.getElementById('head_ic_hint');
      if (cleanIC.length === 12) {
        const yy = cleanIC.substring(0, 2);
        const mm = cleanIC.substring(2, 4);
        const dd = cleanIC.substring(4, 6);
        const year = parseInt(yy, 10) > 40 ? '19' + yy : '20' + yy;
        const lastDigit = parseInt(cleanIC.substring(11, 12), 10);
        const gender = (lastDigit % 2 === 0) ? 'Perempuan' : 'Lelaki';
        hint.innerHTML = `<span class="text-success fw-bold">✓ MyKad Sah:</span> Lahir pada ${dd}/${mm}/${year} (${gender})`;
      } else {
        hint.innerHTML = `Format: 12 digit tanpa sempang atau dengan sempang.`;
      }
    }

    // Sync Head of Household to Member #1
    function syncHeadToMember1() {
      const headName = document.getElementById('head_name').value;
      const headIC = document.getElementById('head_ic').value.replace(/[^0-9]/g, '');
      
      if (memberCount === 0) {
        addMemberCard(true);
      }

      const m1Name = document.getElementById('m_name_0');
      const m1IC = document.getElementById('m_ic_0');
      const m1Rel = document.getElementById('m_rel_0');
      
      if (m1Name && !m1Name.value) m1Name.value = headName;
      if (m1IC && !m1IC.value) {
        m1IC.value = headIC;
        autoDetectMemberDOB(0, headIC);
      }
      if (m1Rel) m1Rel.value = 'Head';
    }

    // Add Dynamic Member Card (Updated Schema)
    function addMemberCard(isHead = false) {
      const idx = memberCount++;
      const container = document.getElementById('membersContainer');

      const card = document.createElement('div');
      card.className = 'member-box';
      card.id = `memberCard_${idx}`;

      card.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-pill px-3 py-1.5">Ahli #${idx + 1}</span>
            <span class="fw-bold small text-secondary">${isHead ? '(Ketua Keluarga)' : ''}</span>
          </div>
          ${!isHead ? `
            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill d-flex align-items-center gap-1 py-1 px-3" onclick="removeMemberCard(${idx})">
              <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
              <span class="small">Padam</span>
            </button>
          ` : ''}
        </div>

        <!-- 1. Demographics & Identification -->
        <div class="mb-4">
          <div class="small fw-bold text-primary text-uppercase tracking-wider mb-2">1. Demografi & Pengenalan Diri</div>
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label small fw-semibold text-secondary">Nama Penuh (Mengikut MyKad/MyKid) <span class="text-danger">*</span></label>
              <input type="text" name="members[${idx}][full_name]" id="m_name_${idx}" class="form-control" placeholder="Nama penuh ahli" required>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">No. MyKad / MyKid <span class="text-danger">*</span></label>
              <input type="text" name="members[${idx}][national_id]" id="m_ic_${idx}" class="form-control" placeholder="12 digit tanpa sempang" required oninput="autoDetectMemberDOB(${idx}, this.value)">
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-semibold text-secondary">Tarikh Lahir</label>
              <input type="date" name="members[${idx}][date_of_birth]" id="m_dob_${idx}" class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label small fw-semibold text-secondary">Umur (Tahun)</label>
              <input type="number" name="members[${idx}][age]" id="m_age_${idx}" class="form-control" placeholder="Umur">
            </div>

            <div class="col-md-2">
              <label class="form-label small fw-semibold text-secondary">Jantina <span class="text-danger">*</span></label>
              <select name="members[${idx}][gender]" id="m_gender_${idx}" class="form-select" required>
                <option value="Male">Lelaki (Male)</option>
                <option value="Female">Perempuan (Female)</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-semibold text-secondary">Hubungan dengan Ketua <span class="text-danger">*</span></label>
              <select name="members[${idx}][relationship_to_head]" id="m_rel_${idx}" class="form-select" required>
                <option value="Head" ${isHead ? 'selected' : ''}>Ketua Keluarga (Head)</option>
                <option value="Spouse">Pasangan (Spouse)</option>
                <option value="Child" ${!isHead ? 'selected' : ''}>Anak (Child)</option>
                <option value="Mother">Ibu (Mother)</option>
                <option value="Father">Bapa (Father)</option>
                <option value="Grandfather">Datuk (Grandfather)</option>
                <option value="Grandmother">Nenek (Grandmother)</option>
                <option value="Relative">Saudara (Relative)</option>
                <option value="Others">Lain-lain (Others)</option>
              </select>
            </div>

            <div class="col-md-2">
              <label class="form-label small fw-semibold text-secondary">Taraf Perkahwinan</label>
              <select name="members[${idx}][marital_status]" class="form-select">
                <option value="Single">Bujang (Single)</option>
                <option value="Married" ${isHead ? 'selected' : ''}>Berkahwin (Married)</option>
                <option value="Divorced">Bercerai (Divorced)</option>
                <option value="Widowed">Balu / Duda (Widowed)</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label small fw-semibold text-secondary">Taraf Kerakyatan</label>
              <input type="text" name="members[${idx}][citizenship_status]" value="Warganegara" class="form-control">
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Tahap Pendidikan</label>
              <select name="members[${idx}][education_level]" class="form-select">
                <option value="Secondary">Menengah (Secondary)</option>
                <option value="Primary">Rendah (Primary)</option>
                <option value="Tertiary">Diploma / Ijazah (Tertiary)</option>
                <option value="Post-Graduate">Pascasiswazah (Post-Graduate)</option>
                <option value="No Formal Education">Tiada Pendidikan Formal</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Status Pekerjaan</label>
              <select name="members[${idx}][employment_status]" class="form-select">
                <option value="Employed">Bekerja (Employed)</option>
                <option value="Self-Employed">Bekerja Sendiri / Peniaga</option>
                <option value="Unemployed">Menganggur (Unemployed)</option>
                <option value="Student">Pelajar (Student)</option>
                <option value="Retired">Bersara (Retired)</option>
                <option value="Homemaker">Suri Rumah (Homemaker)</option>
                <option value="Informal">Sektor Tidak Formal</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Liputan Kesihatan</label>
              <select name="members[${idx}][healthcare_coverage]" class="form-select">
                <option value="KKM / Kerajaan">KKM / Kerajaan</option>
                <option value="Insurans Swasta">Insurans Swasta</option>
                <option value="PERKESO / SOCSO">PERKESO / SOCSO</option>
                <option value="Ditanggung Majikan">Ditanggung Majikan</option>
                <option value="Tiada">Tiada Liputan</option>
              </select>
            </div>
          </div>
        </div>

        <!-- 2. Vulnerable / High-Risk Categories -->
        <div class="mb-4 pt-3 border-top">
          <div class="small fw-bold text-warning text-dark text-uppercase tracking-wider mb-2 d-flex align-items-center gap-1">
            <span class="material-symbols-outlined fs-6 text-warning">shield</span>
            <span>2. Golongan Rentan & Berisiko Tinggi</span>
          </div>
          <div class="row g-2">
            <div class="col-6 col-md">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][vulnerable_infant_under5]" id="m_v_infant_${idx}" value="1">
                <label class="form-check-label small" for="m_v_infant_${idx}">Kanak-kanak (<5 thn)</label>
              </div>
            </div>
            <div class="col-6 col-md">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][vulnerable_senior_60plus]" id="m_v_senior_${idx}" value="1">
                <label class="form-check-label small" for="m_v_senior_${idx}">Warga Emas (60+)</label>
              </div>
            </div>
            <div class="col-6 col-md">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][vulnerable_pregnant_mother]" id="m_v_preg_${idx}" value="1">
                <label class="form-check-label small" for="m_v_preg_${idx}">Ibu Mengandung</label>
              </div>
            </div>
            <div class="col-6 col-md">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][vulnerable_disability_oku]" id="m_v_oku_${idx}" value="1">
                <label class="form-check-label small" for="m_v_oku_${idx}">OKU (Kurang Upaya)</label>
              </div>
            </div>
            <div class="col-6 col-md">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][vulnerable_bedridden]" id="m_v_bed_${idx}" value="1">
                <label class="form-check-label small" for="m_v_bed_${idx}">Pesakit Terlantar</label>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Chronic Diseases & Allergies -->
        <div class="mb-4 pt-3 border-top">
          <div class="small fw-bold text-danger text-uppercase tracking-wider mb-2 d-flex align-items-center gap-1">
            <span class="material-symbols-outlined fs-6 text-danger">medical_services</span>
            <span>3. Penyakit Kronik & Alahan</span>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][chronic_diabetes]" id="m_c_diab_${idx}" value="1">
                <label class="form-check-label small" for="m_c_diab_${idx}">Diabetes (Kencing Manis)</label>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][chronic_hypertension]" id="m_c_hyper_${idx}" value="1">
                <label class="form-check-label small" for="m_c_hyper_${idx}">Darah Tinggi (Hypertension)</label>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][chronic_kidney_disease]" id="m_c_kidney_${idx}" value="1">
                <label class="form-check-label small" for="m_c_kidney_${idx}">Buah Pinggang (Kidney)</label>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][chronic_gastric_intestinal]" id="m_c_gastric_${idx}" value="1">
                <label class="form-check-label small" for="m_c_gastric_${idx}">Gastrik / Usus (Gastric)</label>
              </div>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Penyakit Kronik Lain</label>
              <input type="text" name="members[${idx}][chronic_other]" class="form-control" placeholder="e.g. Asma, Jantung, Tiada">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Alahan Ubat (Drug Allergies)</label>
              <input type="text" name="members[${idx}][drug_allergies]" class="form-control" placeholder="e.g. Penicillin, Tiada">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Alahan Makanan (Food Allergies)</label>
              <input type="text" name="members[${idx}][food_allergies]" class="form-control" placeholder="e.g. Makanan Laut, Kacang, Tiada">
            </div>
          </div>
        </div>

        <!-- 4. Acute Health Screening (Past 3 Days) -->
        <div class="mb-4 pt-3 border-top">
          <div class="small fw-bold text-danger text-uppercase tracking-wider mb-2 d-flex align-items-center gap-1">
            <span class="material-symbols-outlined fs-6 text-danger">coronavirus</span>
            <span>4. Saringan Gejala Wabak Akut (Dalam Tempoh 3 Hari Lepas)</span>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][has_diarrhea]" id="m_s_diar_${idx}" value="1">
                <label class="form-check-label small text-danger fw-semibold" for="m_s_diar_${idx}">Cirit-Birit (Diarrhea)</label>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][has_vomiting]" id="m_s_vom_${idx}" value="1">
                <label class="form-check-label small text-danger fw-semibold" for="m_s_vom_${idx}">Muntah (Vomiting)</label>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][has_fever]" id="m_s_fev_${idx}" value="1">
                <label class="form-check-label small text-danger fw-semibold" for="m_s_fev_${idx}">Demam (Fever)</label>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][is_affected_member]" id="m_s_aff_${idx}" value="1">
                <label class="form-check-label small text-danger fw-semibold" for="m_s_aff_${idx}">Ahli Terjejas Gejala</label>
              </div>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Tarikh Mula Gejala (Onset Date)</label>
              <input type="date" name="members[${idx}][symptom_onset_date]" class="form-control">
            </div>
          </div>
        </div>

        <!-- 5. Food Exposure & Meal History -->
        <div class="pt-3 border-top">
          <div class="small fw-bold text-primary text-uppercase tracking-wider mb-2 d-flex align-items-center gap-1">
            <span class="material-symbols-outlined fs-6 text-primary">restaurant</span>
            <span>5. Pendedahan Makanan & Sejarah Hidangan (Epidemiologi)</span>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Makan Makanan Luar Bersama?</label>
              <select name="members[${idx}][shared_outside_food]" class="form-select">
                <option value="Not Applicable">Tidak Berkaitan</option>
                <option value="Yes">Ya (Makan Luar)</option>
                <option value="No">Tidak (Makan di Rumah Sahaja)</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Lokasi / Premis Makanan Luar</label>
              <input type="text" name="members[${idx}][outside_food_notes]" class="form-control" placeholder="Contoh: Gerai Pasar Malam, Kedai Makan ABC">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Jenis Hidangan / Menu</label>
              <input type="text" name="members[${idx}][meal_type]" class="form-control" placeholder="Contoh: Nasi Ayam, Sambal Sotong">
            </div>
            <div class="col-md-6">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][shared_feast_meal]" id="m_f_feast_${idx}" value="1">
                <label class="form-check-label small" for="m_f_feast_${idx}">Menghadiri Kenduri / Jamuan / Majlis Makan</label>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-check p-2.5 rounded-3 bg-surface border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="members[${idx}][shared_same_meal_before_symptom]" id="m_f_same_${idx}" value="1">
                <label class="form-check-label small" for="m_f_same_${idx}">Berkongsi makanan yang sama sebelum timbul gejala</label>
              </div>
            </div>
          </div>
        </div>
      `;

      container.appendChild(card);
      updateTotalMembersBadge();
    }

    function removeMemberCard(idx) {
      const card = document.getElementById(`memberCard_${idx}`);
      if (card) {
        card.remove();
        updateTotalMembersBadge();
      }
    }

    function updateTotalMembersBadge() {
      const activeCount = document.querySelectorAll('#membersContainer .member-box').length;
      const badge = document.getElementById('totalMembersBadge');
      if (badge) badge.innerText = `Jumlah Ahli: ${activeCount} Orang`;
    }

    function autoDetectMemberDOB(idx, ic) {
      const cleanIC = ic.replace(/[^0-9]/g, '');
      if (cleanIC.length === 12) {
        const yy = cleanIC.substring(0, 2);
        const mm = cleanIC.substring(2, 4);
        const dd = cleanIC.substring(4, 6);
        const year = parseInt(yy, 10) > 40 ? '19' + yy : '20' + yy;
        const dobField = document.getElementById(`m_dob_${idx}`);
        if (dobField) dobField.value = `${year}-${mm}-${dd}`;

        const birthYear = parseInt(year, 10);
        const currentYear = new Date().getFullYear();
        const ageField = document.getElementById(`m_age_${idx}`);
        if (ageField) ageField.value = Math.max(0, currentYear - birthYear);

        const lastDigit = parseInt(cleanIC.substring(11, 12), 10);
        const genderField = document.getElementById(`m_gender_${idx}`);
        if (genderField) genderField.value = (lastDigit % 2 === 0) ? 'Female' : 'Male';

        const age = currentYear - birthYear;
        const infantCb = document.getElementById(`m_v_infant_${idx}`);
        const seniorCb = document.getElementById(`m_v_senior_${idx}`);
        if (infantCb) infantCb.checked = (age <= 5);
        if (seniorCb) seniorCb.checked = (age >= 60);
      }
    }

    // Build Review Summary for Step 4
    function buildReviewSummary() {
      document.getElementById('rev_address').innerText = document.getElementById('h_street').value || '—';
      document.getElementById('rev_city_state').innerText = (document.getElementById('h_postal').value + ' ' + document.getElementById('h_city').value + ', ' + document.getElementById('h_state').value) || '—';
      document.getElementById('rev_house_type').innerText = document.getElementById('selected_house_type').value || '—';

      document.getElementById('rev_head_name').innerText = document.getElementById('head_name').value || '—';
      document.getElementById('rev_head_ic').innerText = document.getElementById('head_ic').value || '—';
      document.getElementById('rev_head_phone').innerText = document.getElementById('head_phone').value || '—';

      // Populate Members Table
      const tbody = document.getElementById('rev_members_body');
      tbody.innerHTML = '';
      const cards = document.querySelectorAll('#membersContainer .member-box');
      document.getElementById('rev_total_members').innerText = `${cards.length} Orang`;

      cards.forEach((c, i) => {
        const name = c.querySelector(`[name*="[full_name]"]`)?.value || '—';
        const ic = c.querySelector(`[name*="[national_id]"]`)?.value || '—';
        const rel = c.querySelector(`[name*="[relationship_to_head]"]`)?.value || '—';
        const gender = c.querySelector(`[name*="[gender]"]`)?.value || '—';
        const age = c.querySelector(`[name*="[age]"]`)?.value || '—';

        const vulns = [];
        if (c.querySelector(`[name*="[vulnerable_infant_under5]"]`)?.checked) vulns.push('Kanak-kanak <5');
        if (c.querySelector(`[name*="[vulnerable_senior_60plus]"]`)?.checked) vulns.push('Warga Emas');
        if (c.querySelector(`[name*="[vulnerable_pregnant_mother]"]`)?.checked) vulns.push('Mengandung');
        if (c.querySelector(`[name*="[vulnerable_disability_oku]"]`)?.checked) vulns.push('OKU');
        if (c.querySelector(`[name*="[vulnerable_bedridden]"]`)?.checked) vulns.push('Terlantar');

        const symptoms = [];
        if (c.querySelector(`[name*="[has_diarrhea]"]`)?.checked) symptoms.push('Cirit-birit');
        if (c.querySelector(`[name*="[has_vomiting]"]`)?.checked) symptoms.push('Muntah');
        if (c.querySelector(`[name*="[has_fever]"]`)?.checked) symptoms.push('Demam');
        if (c.querySelector(`[name*="[is_affected_member]"]`)?.checked && symptoms.length === 0) symptoms.push('Terjejas');

        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${i + 1}</td>
          <td class="fw-bold">${name}</td>
          <td class="font-monospace">${ic}</td>
          <td>${rel}</td>
          <td>${gender} (${age} thn)</td>
          <td>${vulns.length > 0 ? vulns.map(v => `<span class="badge bg-warning text-dark me-1">${v}</span>`).join('') : '<span class="text-muted">Tiada</span>'}</td>
          <td>${symptoms.length > 0 ? symptoms.map(s => `<span class="badge bg-danger me-1">${s}</span>`).join('') : '<span class="badge bg-success">Sihat / Tiada Gejala</span>'}</td>
        `;
        tbody.appendChild(row);
      });
    }

    // Modal toggler
    function toggleRecordsModal() {
      window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    }

    // Initialize Default Member on page load
    document.addEventListener('DOMContentLoaded', () => {
      syncHeadToMember1();
    });
  </script>
</body>
</html>
