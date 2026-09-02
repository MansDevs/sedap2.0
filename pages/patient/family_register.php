<?php
/**
 * ============================================================================
 *   SeDaP 2.0 — Family & Household Registration Module (Pendaftaran Isi Rumah)
 *   Multi-Step Wizard matching SQL Schema:
 *   1. Household Table (Dwelling & Location)
 *   2. HeadOfHousehold Table (Primary Contact)
 *   3. Member Table (Dynamic Household Members & Health Profile)
 *   4. HouseholdFinance Table (Income, Expenses & Aid Eligibility)
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

        // Step 4: Financial Profile Data
        $gross_income    = floatval($_POST['gross_income'] ?? 0.00);
        $rent_mortgage   = floatval($_POST['rent_mortgage'] ?? 0.00);
        $utilities       = floatval($_POST['utilities'] ?? 0.00);
        $education_fees  = floatval($_POST['education_fees'] ?? 0.00);
        $medical_costs   = floatval($_POST['medical_costs'] ?? 0.00);

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
               f.gross_household_income, f.medical_costs,
               (SELECT COUNT(*) FROM Member m WHERE m.household_id = h.household_id) as member_count
        FROM Household h
        LEFT JOIN HeadOfHousehold head ON h.household_id = head.household_id
        LEFT JOIN HouseholdFinance f ON h.household_id = f.household_id
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
  <title>Pendaftaran Isi Rumah & Keluarga — SeDaP 2.0</title>
  
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
    .step-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 9999px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.5px;
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
      padding: 20px;
      background: var(--sedap-surface-dim);
      position: relative;
      margin-bottom: 20px;
      transition: all 0.3s ease;
    }
    .member-box:hover {
      border-color: rgba(2, 132, 199, 0.4);
    }
    .calculator-stat {
      border-radius: 16px;
      padding: 16px;
      background: var(--sedap-surface);
      border: 1px solid var(--sedap-border);
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
              Sistem pendaftaran bersepadu mengikut piawaian data isi rumah SeDaP 2.0 untuk bantuan kecemasan dan saringan perubatan.
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
          
          <!-- Stepper Progress Bar -->
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
                <div class="small fw-bold text-nowrap d-none d-md-block">3. Ahli Rumah</div>
              </div>

              <!-- Step 4 Indicator -->
              <div class="stepper-step text-center position-relative" style="z-index: 3;" id="stepInd4" onclick="jumpToStep(4)">
                <div class="stepper-circle mx-auto mb-1">4</div>
                <div class="small fw-bold text-nowrap d-none d-md-block">4. Kewangan</div>
              </div>

              <!-- Step 5 Indicator -->
              <div class="stepper-step text-center position-relative" style="z-index: 3;" id="stepInd5" onclick="jumpToStep(5)">
                <div class="stepper-circle mx-auto mb-1">5</div>
                <div class="small fw-bold text-nowrap d-none d-md-block">5. Pengesahan</div>
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
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="goToStep(2)">
                  <span>Seterusnya: Ketua Keluarga</span>
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
                  <label class="form-label fw-semibold small text-secondary">No. Kad Pengenalan / MyKad (12 Digit) <span class="text-danger">*</span></label>
                  <input type="text" name="head_ic" id="head_ic" class="form-control" placeholder="Contoh: 850714105431" maxlength="14" required oninput="parseHeadIC(this.value)">
                  <div class="form-text small text-primary" id="head_ic_hint">Format: 12 digit tanpa sempang atau dengan sempang.</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">Nama Penuh Ketua Keluarga (Full Legal Name) <span class="text-danger">*</span></label>
                  <input type="text" name="head_name" id="head_name" class="form-control" value="<?= $userName ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">No. Telefon Bimbit (WhatsApp Aktif) <span class="text-danger">*</span></label>
                  <input type="tel" name="head_phone" id="head_phone" class="form-control" placeholder="0123456789" value="<?= $userPhone ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold small text-secondary">Alamat Emel (Opsional)</label>
                  <input type="email" name="head_email" id="head_email" class="form-control" placeholder="nama@email.com" value="<?= $userEmail ?>">
                </div>

                <div class="col-12">
                  <div class="p-3 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 d-flex align-items-start gap-3">
                    <span class="material-symbols-outlined text-primary fs-3">info</span>
                    <div class="small">
                      <strong>Maklumat Ketua Keluarga:</strong> Nama dan No. Kad Pengenalan ini akan menjadi kunci rujukan utama isi rumah bagi memudahkan semakan bantuan dan komunikasi perubatan berpusat.
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between mt-5">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="goToStep(1)">
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span>
                  <span>Kembali</span>
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="syncHeadToMember1(); goToStep(3)">
                  <span>Seterusnya: Ahli Rumah</span>
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
                </button>
              </div>
            </div>

            <!-- ============================================================= -->
            <!-- STEP 3: HOUSEHOLD MEMBERS (DYNAMIC LIST) -->
            <!-- ============================================================= -->
            <div id="stepSection3" class="step-content d-none">
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                  <span class="material-symbols-outlined text-primary fs-4">groups</span>
                  <div>
                    <h4 class="h5 fw-bold mb-0">Langkah 3: Senarai Ahli Isi Rumah (Members)</h4>
                    <span class="small text-secondary" id="totalMembersBadge">Jumlah Ahli: 1 Orang</span>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-2 d-flex align-items-center gap-1 shadow-sm" onclick="addMemberCard()">
                  <span class="material-symbols-outlined" style="font-size:18px;">person_add</span>
                  <span class="fw-semibold">Tambah Ahli Keluarga</span>
                </button>
              </div>

              <!-- Container for Repeatable Member Cards -->
              <div id="membersContainer">
                <!-- Member 1 (Head / Self) is injected dynamically or rendered by default -->
              </div>

              <div class="d-flex justify-content-between mt-5">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="goToStep(2)">
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span>
                  <span>Kembali</span>
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="goToStep(4)">
                  <span>Seterusnya: Maklumat Kewangan</span>
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
                </button>
              </div>
            </div>

            <!-- ============================================================= -->
            <!-- STEP 4: FINANCIAL INFORMATION & AID ELIGIBILITY -->
            <!-- ============================================================= -->
            <div id="stepSection4" class="step-content d-none">
              <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                <span class="material-symbols-outlined text-primary fs-4">payments</span>
                <h4 class="h5 fw-bold mb-0">Langkah 4: Maklumat Kewangan & Komitmen Isi Rumah</h4>
              </div>

              <div class="row g-4 mb-4">
                <div class="col-md-6">
                  <div class="p-4 rounded-4 bg-surface border h-100">
                    <h5 class="h6 fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                      <span class="material-symbols-outlined">account_balance_wallet</span>
                      <span>Pendapatan Bulanan</span>
                    </h5>
                    <div>
                      <label class="form-label fw-semibold small text-secondary">Pendapatan Kasar Isi Rumah (Gross Income) *</label>
                      <div class="input-group">
                        <span class="input-group-text fw-bold">RM</span>
                        <input type="number" step="0.01" name="gross_income" id="f_gross_income" class="form-control" value="0.00" oninput="calculateFinances()" required>
                      </div>
                      <div class="form-text small">Jumlah pendapatan semua ahli yang bekerja dalam isi rumah.</div>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="p-4 rounded-4 bg-surface border h-100">
                    <h5 class="h6 fw-bold text-danger mb-3 d-flex align-items-center gap-2">
                      <span class="material-symbols-outlined">receipt</span>
                      <span>Perbelanjaan & Komitmen Asas</span>
                    </h5>
                    <div class="row g-3">
                      <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Sewa / Pinjaman Perumahan (Rent/Mortgage)</label>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text fw-bold">RM</span>
                          <input type="number" step="0.01" name="rent_mortgage" id="f_rent" class="form-control" value="0.00" oninput="calculateFinances()">
                        </div>
                      </div>
                      <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Bil Utiliti (Elektrik, Air, Internet)</label>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text fw-bold">RM</span>
                          <input type="number" step="0.01" name="utilities" id="f_utilities" class="form-control" value="0.00" oninput="calculateFinances()">
                        </div>
                      </div>
                      <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Pendidikan & Yuran Anak (Education Fees)</label>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text fw-bold">RM</span>
                          <input type="number" step="0.01" name="education_fees" id="f_education" class="form-control" value="0.00" oninput="calculateFinances()">
                        </div>
                      </div>
                      <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Kos Perubatan & Ubat Berkala (Medical Costs)</label>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text fw-bold">RM</span>
                          <input type="number" step="0.01" name="medical_costs" id="f_medical" class="form-control" value="0.00" oninput="calculateFinances()">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Real-time Financial Health Card -->
              <div class="p-4 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 mb-4">
                <div class="row g-3 text-center text-md-start">
                  <div class="col-md-3">
                    <div class="small text-secondary fw-semibold">Jumlah Komitmen</div>
                    <div class="h5 fw-bold text-danger mb-0" id="stat_total_expenses">RM 0.00</div>
                  </div>
                  <div class="col-md-3">
                    <div class="small text-secondary fw-semibold">Baki Bersih (Net Disposable)</div>
                    <div class="h5 fw-bold text-success mb-0" id="stat_net_income">RM 0.00</div>
                  </div>
                  <div class="col-md-3">
                    <div class="small text-secondary fw-semibold">Pendapatan Per-Kapita</div>
                    <div class="h5 fw-bold text-primary mb-0" id="stat_per_capita">RM 0.00 / ahli</div>
                  </div>
                  <div class="col-md-3 text-md-end">
                    <div class="small text-secondary fw-semibold">Status Sosio-Ekonomi</div>
                    <div class="badge bg-primary px-3 py-2 fs-6 rounded-pill" id="stat_tier_badge">B40 (Layak Bantuan)</div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between mt-5">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="goToStep(3)">
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span>
                  <span>Kembali</span>
                </button>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="buildReviewSummary(); goToStep(5)">
                  <span>Seterusnya: Semakan & Pengesahan</span>
                  <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
                </button>
              </div>
            </div>

            <!-- ============================================================= -->
            <!-- STEP 5: REVIEW, CONFIRMATION & SUBMISSION -->
            <!-- ============================================================= -->
            <div id="stepSection5" class="step-content d-none">
              <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
                <span class="material-symbols-outlined text-success fs-4">fact_check</span>
                <h4 class="h5 fw-bold mb-0">Langkah 5: Semakan & Pengesahan Maklumat</h4>
              </div>

              <div class="row g-4 mb-4">
                <!-- Dwelling & Head Card -->
                <div class="col-md-6">
                  <div class="p-4 rounded-4 bg-surface border h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h6 class="fw-bold mb-0 d-flex align-items-center gap-2 text-primary">
                        <span class="material-symbols-outlined">home</span>
                        <span>Kediaman & Ketua Keluarga</span>
                      </h6>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="goToStep(1)">Kemaskini</button>
                    </div>
                    <div class="small space-y-2">
                      <div><strong>Alamat:</strong> <span id="rev_address">-</span></div>
                      <div><strong>Poskod & Bandar:</strong> <span id="rev_city_state">-</span></div>
                      <div><strong>Jenis Rumah:</strong> <span id="rev_house_type">-</span></div>
                      <hr class="my-2 opacity-25">
                      <div><strong>Ketua Keluarga:</strong> <span id="rev_head_name">-</span></div>
                      <div><strong>No. Kad Pengenalan:</strong> <span id="rev_head_ic">-</span></div>
                      <div><strong>No. Telefon:</strong> <span id="rev_head_phone">-</span></div>
                    </div>
                  </div>
                </div>

                <!-- Financial & Members Summary Card -->
                <div class="col-md-6">
                  <div class="p-4 rounded-4 bg-surface border h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h6 class="fw-bold mb-0 d-flex align-items-center gap-2 text-primary">
                        <span class="material-symbols-outlined">savings</span>
                        <span>Ringkasan Sosio-Ekonomi</span>
                      </h6>
                      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="goToStep(4)">Kemaskini</button>
                    </div>
                    <div class="small space-y-2">
                      <div><strong>Jumlah Ahli Isi Rumah:</strong> <span id="rev_total_members">1 Orang</span></div>
                      <div><strong>Pendapatan Kasar:</strong> <span id="rev_gross_income">RM 0.00</span></div>
                      <div><strong>Jumlah Komitmen Bulanan:</strong> <span id="rev_expenses">RM 0.00</span></div>
                      <div><strong>Baki Bersih Isi Rumah:</strong> <span id="rev_net_income">RM 0.00</span></div>
                      <div class="mt-2">
                        <span class="badge bg-success rounded-pill px-3 py-1.5" id="rev_badge">B40 / Subsidized</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Member Preview Table -->
                <div class="col-12">
                  <div class="p-4 rounded-4 bg-surface border">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h6 class="fw-bold mb-0 d-flex align-items-center gap-2 text-primary">
                        <span class="material-symbols-outlined">badge</span>
                        <span>Senarai Ahli Keluarga</span>
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
                            <th>Jantina</th>
                            <th>Penyakit Kronik</th>
                            <th>Kategori Khas</th>
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
                      Saya mengesahkan bahawa segala maklumat isi rumah, ahli keluarga, dan pendapatan yang diberikan adalah benar dan tepat untuk tujuan rekod perubatan dan agihan bantuan SeDaP 2.0.
                    </label>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between mt-5">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 d-flex align-items-center gap-2 fw-semibold" onclick="goToStep(4)">
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
                  <th>Pendapatan</th>
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
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($lh['house_type']) ?></span></td>
                  <td><span class="badge bg-primary rounded-pill"><?= (int)$lh['member_count'] ?> Orang</span></td>
                  <td class="fw-semibold text-success">RM <?= number_format($lh['gross_household_income'] ?? 0, 2) ?></td>
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
      if (step < 1 || step > 5) return;
      
      // Hide all steps
      for (let i = 1; i <= 5; i++) {
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

      // Update progress line
      const progressPercentage = ((step - 1) / 4) * 100;
      document.getElementById('wizardProgressLine').style.width = progressPercentage + '%';

      currentStep = step;
      window.scrollTo({ top: 120, behavior: 'smooth' });
    }

    function jumpToStep(step) {
      goToStep(step);
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
        if (p >= 43000 && p <= 48000) {
          stateSelect.value = 'Selangor';
        } else if (p >= 50000 && p <= 60000) {
          stateSelect.value = 'Kuala Lumpur';
        } else if (p >= 62000 && p <= 62988) {
          stateSelect.value = 'Putrajaya';
        } else if (p >= 80000 && p <= 86900) {
          stateSelect.value = 'Johor';
        } else if (p >= 15000 && p <= 18500) {
          stateSelect.value = 'Kelantan';
        }
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
      if (m1IC && !m1IC.value) m1IC.value = headIC;
      if (m1Rel) m1Rel.value = 'Ketua Keluarga';

      if (headIC.length === 12) {
        const yy = headIC.substring(0, 2);
        const mm = headIC.substring(2, 4);
        const dd = headIC.substring(4, 6);
        const year = parseInt(yy, 10) > 40 ? '19' + yy : '20' + yy;
        const m1Dob = document.getElementById('m_dob_0');
        if (m1Dob && !m1Dob.value) m1Dob.value = `${year}-${mm}-${dd}`;
      }
    }

    // Add Dynamic Member Card
    function addMemberCard(isHead = false) {
      const idx = memberCount++;
      const container = document.getElementById('membersContainer');

      const card = document.createElement('div');
      card.className = 'member-box';
      card.id = `memberCard_${idx}`;

      card.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
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

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">Nama Penuh <span class="text-danger">*</span></label>
            <input type="text" name="members[${idx}][full_name]" id="m_name_${idx}" class="form-control" required placeholder="Nama seperti dalam MyKad / MyKid">
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-semibold text-secondary">No. Kad Pengenalan / MyKid / Pasport <span class="text-danger">*</span></label>
            <input type="text" name="members[${idx}][national_id]" id="m_ic_${idx}" class="form-control" required placeholder="e.g. 950812105566" oninput="autoDetectMemberDOB(${idx}, this.value)">
          </div>

          <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">Tarikh Lahir</label>
            <input type="date" name="members[${idx}][date_of_birth]" id="m_dob_${idx}" class="form-control">
          </div>

          <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">Jantina</label>
            <select name="members[${idx}][gender]" id="m_gender_${idx}" class="form-select">
              <option value="Lelaki">Lelaki</option>
              <option value="Perempuan">Perempuan</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">Hubungan dgn Ketua</label>
            <select name="members[${idx}][relationship_to_head]" id="m_rel_${idx}" class="form-select">
              <option value="Ketua Keluarga">Ketua Keluarga</option>
              <option value="Pasangan (Isteri/Suami)">Pasangan (Isteri/Suami)</option>
              <option value="Anak Kandung">Anak Kandung</option>
              <option value="Anak Angkat/Tiri">Anak Angkat/Tiri</option>
              <option value="Ibu / Bapa">Ibu / Bapa</option>
              <option value="Adik-beradik">Adik-beradik</option>
              <option value="Saudara Mara">Saudara Mara</option>
              <option value="Lain-lain">Lain-lain</option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">Status Perkahwinan</label>
            <select name="members[${idx}][marital_status]" class="form-select">
              <option value="Bujang">Bujang</option>
              <option value="Berkahwin">Berkahwin</option>
              <option value="Duda">Duda</option>
              <option value="Janda">Janda</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Taraf Kerakyatan</label>
            <select name="members[${idx}][citizenship_status]" class="form-select">
              <option value="Warganegara">Warganegara</option>
              <option value="Pemastautin Tetap">Pemastautin Tetap</option>
              <option value="Bukan Warganegara">Bukan Warganegara</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Tahap Pendidikan Tertinggi</label>
            <select name="members[${idx}][education_level]" class="form-select">
              <option value="Menengah (SPM)">Menengah (SPM)</option>
              <option value="Rendah (UPSR/Sekolah Rendah)">Rendah (Sekolah Rendah)</option>
              <option value="Diploma / STPM">Diploma / STPM</option>
              <option value="Ijazah Sarjana Muda">Ijazah Sarjana Muda</option>
              <option value="Pascasiswazah">Pascasiswazah</option>
              <option value="Tiada Pendidikan Formal">Tiada Pendidikan Formal</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Status Pekerjaan</label>
            <select name="members[${idx}][employment_status]" class="form-select">
              <option value="Bekerja Sektor Swasta">Bekerja Sektor Swasta</option>
              <option value="Bekerja Sektor Awam">Bekerja Sektor Awam</option>
              <option value="Bekerja Sendiri / Peniaga">Bekerja Sendiri / Peniaga</option>
              <option value="Suri Rumah">Suri Rumah</option>
              <option value="Pelajar">Pelajar</option>
              <option value="Bersara">Bersara</option>
              <option value="Menganggur">Menganggur</option>
            </select>
          </div>

          <!-- Health & Vulnerability Profile -->
          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Penyakit Kronik (Jika Ada)</label>
            <input type="text" name="members[${idx}][chronic_condition]" id="m_chronic_${idx}" class="form-control" placeholder="e.g. Diabetes, Hipertensi, Tiada" value="Tiada">
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Liputan Kesihatan (Healthcare)</label>
            <select name="members[${idx}][healthcare_coverage]" class="form-select">
              <option value="KKM / Kerajaan">KKM / Kerajaan</option>
              <option value="Insurans Swasta">Insurans Swasta</option>
              <option value="PERKESO / SOCSO">PERKESO / SOCSO</option>
              <option value="Majikan">Ditanggung Majikan</option>
              <option value="Tiada">Tiada Liputan</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">Kategori Rentan (Vulnerable)</label>
            <select name="members[${idx}][vulnerable_dependent]" id="m_vul_${idx}" class="form-select">
              <option value="Tiada">Tiada / Normal</option>
              <option value="Warga Emas (60+)">Warga Emas (60+)</option>
              <option value="Kanak-kanak / Bayi">Kanak-kanak / Bayi</option>
              <option value="OKU (Kurang Upaya)">OKU (Kurang Upaya)</option>
              <option value="Pesakit Terlantar">Pesakit Terlantar</option>
              <option value="Ibu Mengandung">Ibu Mengandung</option>
            </select>
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
      calculateFinances();
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
        
        const lastDigit = parseInt(cleanIC.substring(11, 12), 10);
        const genderField = document.getElementById(`m_gender_${idx}`);
        if (genderField) genderField.value = (lastDigit % 2 === 0) ? 'Perempuan' : 'Lelaki';
      }
    }

    // Financial calculations & B40/M40 Tier classification
    function calculateFinances() {
      const gross = parseFloat(document.getElementById('f_gross_income').value) || 0;
      const rent = parseFloat(document.getElementById('f_rent').value) || 0;
      const utilities = parseFloat(document.getElementById('f_utilities').value) || 0;
      const education = parseFloat(document.getElementById('f_education').value) || 0;
      const medical = parseFloat(document.getElementById('f_medical').value) || 0;

      const totalExpenses = rent + utilities + education + medical;
      const net = gross - totalExpenses;
      const residents = Math.max(1, document.querySelectorAll('#membersContainer .member-box').length);
      const perCapita = net / residents;

      document.getElementById('stat_total_expenses').innerText = 'RM ' + totalExpenses.toFixed(2);
      document.getElementById('stat_net_income').innerText = 'RM ' + net.toFixed(2);
      document.getElementById('stat_per_capita').innerText = 'RM ' + perCapita.toFixed(2) + ' / ahli';

      const tierBadge = document.getElementById('stat_tier_badge');
      if (gross <= 5250) {
        tierBadge.className = 'badge bg-success px-3 py-2 fs-6 rounded-pill';
        tierBadge.innerText = 'B40 (Layak Bantuan Penuh)';
      } else if (gross <= 11819) {
        tierBadge.className = 'badge bg-primary px-3 py-2 fs-6 rounded-pill';
        tierBadge.innerText = 'M40 (Kumpulan Sederhana)';
      } else {
        tierBadge.className = 'badge bg-secondary px-3 py-2 fs-6 rounded-pill';
        tierBadge.innerText = 'T20 (Pendapatan Tinggi)';
      }
    }

    // Build Review Summary for Step 5
    function buildReviewSummary() {
      document.getElementById('rev_address').innerText = document.getElementById('h_street').value || '—';
      document.getElementById('rev_city_state').innerText = (document.getElementById('h_postal').value + ' ' + document.getElementById('h_city').value + ', ' + document.getElementById('h_state').value) || '—';
      document.getElementById('rev_house_type').innerText = document.getElementById('selected_house_type').value || '—';

      document.getElementById('rev_head_name').innerText = document.getElementById('head_name').value || '—';
      document.getElementById('rev_head_ic').innerText = document.getElementById('head_ic').value || '—';
      document.getElementById('rev_head_phone').innerText = document.getElementById('head_phone').value || '—';

      const gross = parseFloat(document.getElementById('f_gross_income').value) || 0;
      const expenses = (parseFloat(document.getElementById('f_rent').value) || 0) +
                       (parseFloat(document.getElementById('f_utilities').value) || 0) +
                       (parseFloat(document.getElementById('f_education').value) || 0) +
                       (parseFloat(document.getElementById('f_medical').value) || 0);
      const net = gross - expenses;

      document.getElementById('rev_gross_income').innerText = 'RM ' + gross.toFixed(2);
      document.getElementById('rev_expenses').innerText = 'RM ' + expenses.toFixed(2);
      document.getElementById('rev_net_income').innerText = 'RM ' + net.toFixed(2);

      const revBadge = document.getElementById('rev_badge');
      if (gross <= 5250) {
        revBadge.className = 'badge bg-success rounded-pill px-3 py-1.5';
        revBadge.innerText = 'Kategori B40 / Layak Subsidi Perubatan';
      } else {
        revBadge.className = 'badge bg-primary rounded-pill px-3 py-1.5';
        revBadge.innerText = 'Kategori M40 / T20';
      }

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
        const chronic = c.querySelector(`[name*="[chronic_condition]"]`)?.value || 'Tiada';
        const vul = c.querySelector(`[name*="[vulnerable_dependent]"]`)?.value || 'Tiada';

        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${i + 1}</td>
          <td class="fw-bold">${name}</td>
          <td>${ic}</td>
          <td>${rel}</td>
          <td>${gender}</td>
          <td><span class="badge ${chronic !== 'Tiada' ? 'bg-danger' : 'bg-light text-dark'}">${chronic}</span></td>
          <td><span class="badge ${vul !== 'Tiada' ? 'bg-warning text-dark' : 'bg-light text-dark'}">${vul}</span></td>
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
      calculateFinances();
    });
  </script>
</body>
</html>
