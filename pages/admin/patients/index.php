<?php
session_start();
require_once '../../config/db.php';
require_once '../../shared/includes/lang.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php'); exit;
}

$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';

$msg = '';
$err = '';

// Handle Patient Registration Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    // 1. Demographics & Identification
    $fullName        = trim($_POST['full_name'] ?? '');
    $dob             = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $gender          = $_POST['gender'] ?? 'male';
    $genderIdentity  = trim($_POST['gender_identity'] ?? '');
    $icNumber        = trim($_POST['ic_number'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $address         = trim($_POST['address'] ?? '');

    // 2. Emergency Contacts
    $emName          = trim($_POST['emergency_contact_name'] ?? '');
    $emRelationship  = trim($_POST['emergency_contact_relationship'] ?? '');
    $emPhone         = trim($_POST['emergency_contact_phone'] ?? '');
    $emAltPhone      = trim($_POST['emergency_contact_alt_phone'] ?? '');

    // 3. Insurance & Billing
    $insPayer        = trim($_POST['insurance_payer'] ?? '');
    $insPolicyId     = trim($_POST['insurance_policy_id'] ?? '');
    $insGroupNum     = trim($_POST['insurance_group_number'] ?? '');
    $insSubscriber   = trim($_POST['insurance_subscriber_details'] ?? '');
    $insCoverageType = trim($_POST['insurance_coverage_type'] ?? 'Primary');
    $billingAddress  = trim($_POST['billing_address'] ?? '');

    // 4. Initial Clinical Screening
    $reasonForVisit  = trim($_POST['clinical_reason_for_visit'] ?? '');
    $activeMeds      = trim($_POST['clinical_active_medications'] ?? '');
    $allergies       = trim($_POST['clinical_allergies'] ?? '');
    $surgicalHistory = trim($_POST['clinical_surgical_history'] ?? '');
    $familyHistory   = trim($_POST['clinical_family_history'] ?? '');

    if (empty($fullName)) {
        $err = 'Sila masukkan Nama Penuh pesakit.';
    } else {
        try {
            // Auto generate registration number if needed
            $maxId = (int)$pdo->query("SELECT MAX(id) FROM patients")->fetchColumn();
            $regNumber = 'PT-' . str_pad((string)($maxId + 1), 6, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("INSERT INTO patients (
                registration_number, full_name, date_of_birth, gender, gender_identity, ic_number, phone, email, address,
                emergency_contact_name, emergency_contact_relationship, emergency_contact_phone, emergency_contact_alt_phone,
                insurance_payer, insurance_policy_id, insurance_group_number, insurance_subscriber_details, insurance_coverage_type, billing_address,
                clinical_reason_for_visit, clinical_active_medications, clinical_allergies, clinical_surgical_history, clinical_family_history,
                registered_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, NOW()
            )");

            $stmt->execute([
                $regNumber, $fullName, $dob, $gender, $genderIdentity, $icNumber, $phone, $email, $address,
                $emName, $emRelationship, $emPhone, $emAltPhone,
                $insPayer, $insPolicyId, $insGroupNum, $insSubscriber, $insCoverageType, $billingAddress,
                $reasonForVisit, $activeMeds, $allergies, $surgicalHistory, $familyHistory,
                $_SESSION['user_id']
            ]);

            $msg = "Pesakit <strong>" . htmlspecialchars($fullName) . "</strong> ($regNumber) berjaya didaftarkan dengan maklumat lengkap.";
        } catch (PDOException $e) {
            $err = 'Ralat pendaftaran: ' . $e->getMessage();
        }
    }
}

$patients = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pendaftaran & Pengurusan Pesakit — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    /* 24px rounded corners for all input text fields, selects, and textareas */
    .form-control-24,
    .wizard-step-pane input.form-control,
    .wizard-step-pane select.form-select,
    .custom-modal-24 input.form-control,
    .custom-modal-24 select.form-select {
      border-radius: 24px !important;
      padding: 0.65rem 1.25rem;
      border: 1.5px solid #d1d5db;
      font-size: 0.92rem;
      transition: all 0.2s ease;
    }
    .wizard-step-pane textarea.form-control,
    .custom-modal-24 textarea.form-control {
      border-radius: 20px !important;
      padding: 0.85rem 1.25rem;
      border: 1.5px solid #d1d5db;
      font-size: 0.92rem;
    }
    .wizard-step-pane input.form-control:focus,
    .wizard-step-pane select.form-select:focus,
    .wizard-step-pane textarea.form-control:focus {
      border-color: #087383 !important;
      box-shadow: 0 0 0 4px rgba(8, 115, 131, 0.15) !important;
    }
    
    /* Stepper UI */
    .category-step-nav {
      display: flex;
      justify-content: space-between;
      position: relative;
      margin-bottom: 2rem;
    }
    .category-step-btn {
      flex: 1;
      background: none;
      border: none;
      text-align: center;
      position: relative;
      z-index: 2;
      cursor: pointer;
      padding: 0.5rem 0.25rem;
    }
    .category-badge-num {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: #e2e8f0;
      color: #64748b;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      margin-bottom: 0.5rem;
      transition: all 0.25s ease;
    }
    .category-step-btn.active .category-badge-num {
      background: #087383;
      color: #ffffff;
      box-shadow: 0 0 0 5px rgba(8, 115, 131, 0.2);
    }
    .category-step-btn.completed .category-badge-num {
      background: #10b981;
      color: #ffffff;
    }
    .category-label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: #64748b;
      line-height: 1.2;
    }
    .category-step-btn.active .category-label {
      color: #087383;
      font-weight: 700;
    }
    .stepper-line {
      position: absolute;
      top: 25px;
      left: 8%;
      right: 8%;
      height: 3px;
      background: #e2e8f0;
      z-index: 1;
    }
    .stepper-progress {
      height: 100%;
      background: #087383;
      transition: width 0.35s ease;
      width: 0%;
    }
    .category-purpose-card {
      background: rgba(8, 115, 131, 0.07);
      border-left: 4px solid #087383;
      border-radius: 16px;
      padding: 1rem 1.25rem;
      margin-bottom: 1.5rem;
    }
    [data-coreui-theme="dark"] .category-purpose-card {
      background: rgba(8, 115, 131, 0.22);
      border-left-color: #20c997;
    }
    [data-coreui-theme="dark"] .category-badge-num {
      background: #2e3235;
      color: #94a3b8;
    }
    [data-coreui-theme="dark"] .stepper-line {
      background: #2e3235;
    }
  </style>
</head>
<body class="layout-fixed">
  <?php include '../../shared/includes/sidebar_admin.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
          <h1 class="page-title d-flex align-items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-size:32px;">how_to_reg</span>
            <span>Pendaftaran & Pengurusan Pesakit</span>
          </h1>
          <p class="page-subtitle">Sistem pendaftaran pesakit kategori demi kategori dengan rekod komprehensif</p>
        </div>
        <button class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2" style="border-radius:24px;" data-coreui-toggle="modal" data-coreui-target="#patientWizardModal">
          <span class="material-symbols-outlined" style="font-size:20px;">person_add</span>
          <span class="fw-semibold">Daftar Pesakit Baharu (Multi-Category)</span>
        </button>
      </div>

      <?php if ($msg): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 py-3 mb-4 rounded-4 shadow-sm">
          <span class="material-symbols-outlined text-success" style="font-size:24px;">check_circle</span>
          <div><?= $msg ?></div>
        </div>
      <?php endif; ?>

      <?php if ($err): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-3 mb-4 rounded-4 shadow-sm">
          <span class="material-symbols-outlined text-danger" style="font-size:24px;">error</span>
          <div><?= htmlspecialchars($err) ?></div>
        </div>
      <?php endif; ?>

      <!-- Patient Table Card -->
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
          <div class="fw-bold d-flex align-items-center gap-2 text-dark">
            <span class="material-symbols-outlined text-primary">patient_list</span>
            Senarai Rekod Pesakit Terkini (<?= count($patients) ?>)
          </div>
          <div style="width:280px;">
            <input type="text" id="tableSearch" class="form-control form-control-24" placeholder="Cari nama, IC, no. telefon...">
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="patientsTable">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">No. Pendaftaran</th>
                  <th>Nama Penuh</th>
                  <th>No. IC / Pengenalan</th>
                  <th>Jantina</th>
                  <th>No. Telefon</th>
                  <th>Kontak Kecemasan</th>
                  <th>Insurans</th>
                  <th>Tarikh Daftar</th>
                  <th class="text-center pe-4">Tindakan</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($patients)): ?>
                  <tr><td colspan="9" class="text-center text-muted py-5">Tiada rekod pesakit buat masa ini. Sila daftar pesakit baharu.</td></tr>
                <?php else: ?>
                  <?php foreach ($patients as $p): ?>
                    <tr class="patient-row">
                      <td class="ps-4 fw-semibold text-primary"><?= htmlspecialchars($p['registration_number'] ?? 'PT-' . $p['id']) ?></td>
                      <td class="fw-semibold">
                        <?= htmlspecialchars($p['full_name']) ?>
                        <?php if (!empty($p['clinical_reason_for_visit'])): ?>
                          <div class="small text-muted text-truncate" style="max-width:200px;"><?= htmlspecialchars($p['clinical_reason_for_visit']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="small text-muted"><?= htmlspecialchars($p['ic_number'] ?? '—') ?></td>
                      <td>
                        <span class="badge rounded-pill bg-light text-dark border">
                          <?= ucfirst(htmlspecialchars($p['gender'] ?? '—')) ?>
                          <?= !empty($p['gender_identity']) ? ' (' . htmlspecialchars($p['gender_identity']) . ')' : '' ?>
                        </span>
                      </td>
                      <td class="small"><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                      <td class="small">
                        <?= !empty($p['emergency_contact_name']) ? htmlspecialchars($p['emergency_contact_name']) . ' (' . htmlspecialchars($p['emergency_contact_phone'] ?? '') . ')' : '<span class="text-muted">—</span>' ?>
                      </td>
                      <td class="small">
                        <?= !empty($p['insurance_payer']) ? '<span class="badge bg-info-subtle text-info-emphasis border">' . htmlspecialchars($p['insurance_payer']) . '</span>' : '<span class="text-muted">—</span>' ?>
                      </td>
                      <td class="small text-muted"><?= !empty($p['created_at']) ? date('d/m/Y H:i', strtotime($p['created_at'])) : '—' ?></td>
                      <td class="text-center pe-4">
                        <button class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 rounded-pill px-3 view-patient-btn"
                                data-patient='<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>'
                                data-coreui-toggle="modal" data-coreui-target="#viewPatientModal">
                          <span class="material-symbols-outlined" style="font-size:16px;">visibility</span>
                          <span>Lihat Profil</span>
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
  <?php include '../../shared/includes/footer.php'; ?>
</div>

<!-- ======================================================= -->
<!-- MULTI-CATEGORY PATIENT REGISTRATION MODAL WIZARD -->
<!-- ======================================================= -->
<div class="modal fade custom-modal-24" id="patientWizardModal" tabindex="-1" aria-labelledby="patientWizardLabel" aria-hidden="true" data-coreui-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <form method="POST" id="patientWizardForm" class="wizard-form">
        <input type="hidden" name="action" value="add">

        <div class="modal-header bg-light py-3 px-4 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-size:28px;">person_add</span>
            <div>
              <h5 class="modal-title fw-bold mb-0" id="patientWizardLabel">Borang Pendaftaran Pesakit Baharu</h5>
              <small class="text-muted">Lengkapkan maklumat mengikut kategori berturutan</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Tutup"></button>
        </div>

        <div class="modal-body p-4">
          <!-- Stepper Header Navigation -->
          <div class="category-step-nav">
            <div class="stepper-line"><div class="stepper-progress" id="stepperProgress"></div></div>
            
            <button type="button" class="category-step-btn active" data-step="1" onclick="goToStep(1)">
              <div class="category-badge-num">1</div>
              <span class="category-label">1. Demographics & Identification</span>
            </button>
            <button type="button" class="category-step-btn" data-step="2" onclick="goToStep(2)">
              <div class="category-badge-num">2</div>
              <span class="category-label">2. Emergency Contacts</span>
            </button>
            <button type="button" class="category-step-btn" data-step="3" onclick="goToStep(3)">
              <div class="category-badge-num">3</div>
              <span class="category-label">3. Insurance & Billing</span>
            </button>
            <button type="button" class="category-step-btn" data-step="4" onclick="goToStep(4)">
              <div class="category-badge-num">4</div>
              <span class="category-label">4. Initial Clinical Screening</span>
            </button>
          </div>

          <!-- ============================================== -->
          <!-- CATEGORY 1: Demographics & Identification -->
          <!-- ============================================== -->
          <div class="wizard-step-pane" id="stepPane1">
            <div class="category-purpose-card">
              <div class="d-flex align-items-start gap-2">
                <span class="material-symbols-outlined text-primary mt-1">verified_user</span>
                <div>
                  <strong class="d-block text-primary">Kategori 1: Demographics & Identification</strong>
                  <span class="small text-secondary">
                    <strong>Tujuan Utama:</strong> Prevents duplicate records, ensures accurate patient verification, and enables direct communication.
                  </span>
                </div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label fw-semibold small text-dark">Nama Penuh Mengikut Dokumen Rasmi (Full Legal Name) <span class="text-danger">*</span></label>
                <input type="text" name="full_name" id="reg_full_name" class="form-control" placeholder="Contoh: Muhammad Ali bin Ahmad" required>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold small text-dark">No. Kad Pengenalan / SSN / Pasport <span class="text-danger">*</span></label>
                <input type="text" name="ic_number" id="reg_ic_number" class="form-control" placeholder="Contoh: 901012-10-5432" required>
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold small text-dark">Tarikh Lahir (Date of Birth) <span class="text-danger">*</span></label>
                <input type="date" name="date_of_birth" id="reg_dob" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold small text-dark">Jantina Biologikal (Biological Sex)</label>
                <select name="gender" class="form-select">
                  <option value="male">Lelaki (Male)</option>
                  <option value="female">Perempuan (Female)</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold small text-dark">Identiti Jantina (Gender Identity)</label>
                <input type="text" name="gender_identity" class="form-control" placeholder="Pilihan (cth: Cisgender, dll)">
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">No. Telefon Utama (Phone Number) <span class="text-danger">*</span></label>
                <input type="tel" name="phone" id="reg_phone" class="form-control" placeholder="Contoh: 012-3456789" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Emel Utama (Primary Email)</label>
                <input type="email" name="email" class="form-control" placeholder="nama@domain.com">
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold small text-dark">Alamat Kediaman (Residential Address)</label>
                <textarea name="address" rows="2" class="form-control" placeholder="No. Rumah, Jalan, Taman / Kampung, Poskod, Bandar, Negeri"></textarea>
              </div>
            </div>
          </div>

          <!-- ============================================== -->
          <!-- CATEGORY 2: Emergency Contacts -->
          <!-- ============================================== -->
          <div class="wizard-step-pane d-none" id="stepPane2">
            <div class="category-purpose-card">
              <div class="d-flex align-items-start gap-2">
                <span class="material-symbols-outlined text-primary mt-1">emergency</span>
                <div>
                  <strong class="d-block text-primary">Kategori 2: Emergency Contacts</strong>
                  <span class="small text-secondary">
                    <strong>Tujuan Utama:</strong> Crucial for urgent medical situations or clinical updates when the patient is unable to communicate.
                  </span>
                </div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Nama Kontak Kecemasan (Contact Name) <span class="text-danger">*</span></label>
                <input type="text" name="emergency_contact_name" id="reg_em_name" class="form-control" placeholder="Nama waris / pasangan / penjaga">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Hubungan Dengan Pesakit (Relationship)</label>
                <select name="emergency_contact_relationship" class="form-select">
                  <option value="Spouse">Pasangan (Spouse)</option>
                  <option value="Parent">Ibu / Bapa (Parent)</option>
                  <option value="Child">Anak (Child)</option>
                  <option value="Sibling">Adik-beradik (Sibling)</option>
                  <option value="Legal Guardian">Penjaga Sah (Legal Guardian)</option>
                  <option value="Friend/Neighbor">Rakan / Jiran (Friend/Neighbor)</option>
                  <option value="Other">Lain-lain (Other)</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">No. Telefon Kecemasan (Phone Number) <span class="text-danger">*</span></label>
                <input type="tel" name="emergency_contact_phone" id="reg_em_phone" class="form-control" placeholder="Contoh: 013-9876543">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">No. Telefon Alternatif (Alternate Phone)</label>
                <input type="tel" name="emergency_contact_alt_phone" class="form-control" placeholder="Telefon rumah / pejabat (pilihan)">
              </div>
            </div>
          </div>

          <!-- ============================================== -->
          <!-- CATEGORY 3: Insurance & Billing -->
          <!-- ============================================== -->
          <div class="wizard-step-pane d-none" id="stepPane3">
            <div class="category-purpose-card">
              <div class="d-flex align-items-start gap-2">
                <span class="material-symbols-outlined text-primary mt-1">receipt_long</span>
                <div>
                  <strong class="d-block text-primary">Kategori 3: Insurance & Billing</strong>
                  <span class="small text-secondary">
                    <strong>Tujuan Utama:</strong> Validates coverage active status, facilitates claim submission, and clarifies financial responsibility.
                  </span>
                </div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Nama Syarikat Penanggung / Pembayar (Payer / Carrier Name)</label>
                <input type="text" name="insurance_payer" class="form-control" placeholder="Contoh: MySalam / AIA / Great Eastern / Kerajaan (KKM) / Self-Pay">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Jenis Perlindungan (Coverage Type)</label>
                <select name="insurance_coverage_type" class="form-select">
                  <option value="Primary">Perlindungan Utama (Primary)</option>
                  <option value="Secondary">Perlindungan Sekunder (Secondary)</option>
                  <option value="Self-Pay">Bayaran Sendiri (Self-Pay / Cash)</option>
                  <option value="Government Subsidy">Subsidi Kerajaan / Kebajikan</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">No. Polisi / ID Ahli (Policy / Member ID)</label>
                <input type="text" name="insurance_policy_id" class="form-control" placeholder="Contoh: POL-889912">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">No. Kumpulan (Group Number)</label>
                <input type="text" name="insurance_group_number" class="form-control" placeholder="Contoh: GRP-004">
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold small text-dark">Maklumat Pemegang Polisi (Subscriber / Policyholder Details)</label>
                <input type="text" name="insurance_subscriber_details" class="form-control" placeholder="Nama penuh pemegang polisi & hubungan (jika berbeza dari pesakit)">
              </div>

              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label fw-semibold small text-dark mb-0">Alamat Pengebilan (Billing Address)</label>
                  <button type="button" class="btn btn-link btn-sm text-decoration-none p-0" onclick="copyAddressToBilling()">
                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">content_copy</span>
                    Sama seperti alamat kediaman
                  </button>
                </div>
                <textarea name="billing_address" id="reg_billing_address" rows="2" class="form-control" placeholder="Alamat tuntutan invois / resit rasmi"></textarea>
              </div>
            </div>
          </div>

          <!-- ============================================== -->
          <!-- CATEGORY 4: Initial Clinical Screening -->
          <!-- ============================================== -->
          <div class="wizard-step-pane d-none" id="stepPane4">
            <div class="category-purpose-card">
              <div class="d-flex align-items-start gap-2">
                <span class="material-symbols-outlined text-primary mt-1">medical_services</span>
                <div>
                  <strong class="d-block text-primary">Kategori 4: Initial Clinical Screening</strong>
                  <span class="small text-secondary">
                    <strong>Tujuan Utama:</strong> Ensures basic patient safety prior to consultation (e.g., preventing adverse drug interactions).
                  </span>
                </div>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-semibold small text-dark">Sebab Utama Lawatan / Aduan Utama (Primary Reason for Visit) <span class="text-danger">*</span></label>
                <textarea name="clinical_reason_for_visit" id="reg_clinical_reason" rows="2" class="form-control" placeholder="Gejala yang dialami, tempoh masa sakit (cth: Demam panas sejak 3 hari, cirit-birit berterusan)" required></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Ubat-ubatan Semasa (Active Medications)</label>
                <textarea name="clinical_active_medications" rows="2" class="form-control" placeholder="Senarai ubat yang sedang diambil (cth: Metformin 500mg, Amlodipine 5mg, suplemen)"></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Alahan Ubat / Makanan (Known Drug / Food Allergies)</label>
                <textarea name="clinical_allergies" rows="2" class="form-control" placeholder="Cth: Penicillin, Makanan Laut, Kacang, Tiada"></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Sejarah Pembedahan / Rawatan Lalu (Past Surgical History)</label>
                <textarea name="clinical_surgical_history" rows="2" class="form-control" placeholder="Pembedahan lampau, kemasukan wad sebelum ini"></textarea>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold small text-dark">Sejarah Perubatan Keluarga (Family Medical History)</label>
                <textarea name="clinical_family_history" rows="2" class="form-control" placeholder="Kencing manis, darah tinggi, penyakit jantung, kanser, dll"></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Wizard Navigation Footer -->
        <div class="modal-footer bg-light py-3 px-4 d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary px-4 d-flex align-items-center gap-1" id="btnPrevStep" style="border-radius:24px;display:none;" onclick="prevStep()">
            <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span>
            <span>Kategori Sebelumnya</span>
          </button>
          
          <button type="button" class="btn btn-secondary px-4" style="border-radius:24px;" data-coreui-dismiss="modal" id="btnCancelModal">Batal</button>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary px-4 d-flex align-items-center gap-1" id="btnNextStep" style="border-radius:24px;" onclick="nextStep()">
              <span>Seterusnya: Kategori 2</span>
              <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
            </button>
            <button type="submit" class="btn btn-success px-4 d-flex align-items-center gap-1 text-white" id="btnSubmitForm" style="border-radius:24px;display:none;">
              <span class="material-symbols-outlined" style="font-size:18px;">save</span>
              <span>Daftar Pesakit Lengkap</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================= -->
<!-- VIEW PATIENT FULL DETAILS MODAL -->
<!-- ======================================================= -->
<div class="modal fade" id="viewPatientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
      <div class="modal-header bg-primary text-white py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <span class="material-symbols-outlined" style="font-size:28px;">badge</span>
          <div>
            <h5 class="modal-title fw-bold mb-0" id="viewPatientName">Profil Pesakit</h5>
            <small id="viewPatientRegNo" class="opacity-75"></small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="viewPatientBody">
        <!-- Dynamic Patient Content Loaded via JS -->
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-coreui-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script>
  let currentStep = 1;
  const totalSteps = 4;
  const stepTitles = [
    "Seterusnya: Kategori 2 (Emergency Contacts)",
    "Seterusnya: Kategori 3 (Insurance & Billing)",
    "Seterusnya: Kategori 4 (Initial Clinical Screening)",
    "Daftar Pesakit Lengkap"
  ];

  function updateWizardUI() {
    // Hide all step panes
    for (let i = 1; i <= totalSteps; i++) {
      const pane = document.getElementById('stepPane' + i);
      if (pane) {
        if (i === currentStep) {
          pane.classList.remove('d-none');
        } else {
          pane.classList.add('d-none');
        }
      }
      
      const btn = document.querySelector(`.category-step-btn[data-step="${i}"]`);
      if (btn) {
        btn.classList.remove('active');
        if (i === currentStep) {
          btn.classList.add('active');
        } else if (i < currentStep) {
          btn.classList.add('completed');
        } else {
          btn.classList.remove('completed');
        }
      }
    }

    // Update progress bar
    const progressPercent = ((currentStep - 1) / (totalSteps - 1)) * 100;
    const bar = document.getElementById('stepperProgress');
    if (bar) bar.style.width = progressPercent + '%';

    // Update buttons
    const btnPrev = document.getElementById('btnPrevStep');
    const btnNext = document.getElementById('btnNextStep');
    const btnSubmit = document.getElementById('btnSubmitForm');
    const btnCancel = document.getElementById('btnCancelModal');

    if (currentStep > 1) {
      btnPrev.style.display = 'inline-flex';
      btnCancel.style.display = 'none';
    } else {
      btnPrev.style.display = 'none';
      btnCancel.style.display = 'inline-block';
    }

    if (currentStep === totalSteps) {
      btnNext.style.display = 'none';
      btnSubmit.style.display = 'inline-flex';
    } else {
      btnNext.style.display = 'inline-flex';
      btnNext.querySelector('span:first-child').innerText = stepTitles[currentStep - 1];
      btnSubmit.style.display = 'none';
    }
  }

  function validateStep(step) {
    if (step === 1) {
      const name = document.getElementById('reg_full_name').value.trim();
      const ic = document.getElementById('reg_ic_number').value.trim();
      const phone = document.getElementById('reg_phone').value.trim();
      const dob = document.getElementById('reg_dob').value;
      if (!name || !ic || !phone || !dob) {
        alert('Sila lengkapkan maklumat wajib dalam Kategori 1 (Nama, No. IC, Tarikh Lahir & No Telefon).');
        return false;
      }
    }
    if (step === 2) {
      const emName = document.getElementById('reg_em_name').value.trim();
      const emPhone = document.getElementById('reg_em_phone').value.trim();
      if (!emName || !emPhone) {
        if (!confirm('Maklumat kontak kecemasan belum lengkap. Adakah anda ingin teruskan?')) {
          return false;
        }
      }
    }
    return true;
  }

  function nextStep() {
    if (!validateStep(currentStep)) return;
    if (currentStep < totalSteps) {
      currentStep++;
      updateWizardUI();
    }
  }

  function prevStep() {
    if (currentStep > 1) {
      currentStep--;
      updateWizardUI();
    }
  }

  function goToStep(targetStep) {
    if (targetStep > currentStep) {
      if (!validateStep(currentStep)) return;
    }
    currentStep = targetStep;
    updateWizardUI();
  }

  function copyAddressToBilling() {
    const addr = document.querySelector('textarea[name="address"]').value;
    document.getElementById('reg_billing_address').value = addr;
  }

  // Filter Table
  document.getElementById('tableSearch')?.addEventListener('keyup', function(e) {
    const val = e.target.value.toLowerCase();
    document.querySelectorAll('#patientsTable tbody tr.patient-row').forEach(row => {
      row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
  });

  // View Patient Details Modal Population
  document.querySelectorAll('.view-patient-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const p = JSON.parse(this.getAttribute('data-patient'));
      document.getElementById('viewPatientName').innerText = p.full_name || 'Profil Pesakit';
      document.getElementById('viewPatientRegNo').innerText = (p.registration_number || 'PT-' + p.id) + ' • Didaftarkan: ' + (p.created_at || '—');

      const body = document.getElementById('viewPatientBody');
      body.innerHTML = `
        <div class="row g-4">
          <!-- 1. Demographics -->
          <div class="col-12">
            <div class="p-3 rounded-4 bg-light">
              <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                <span class="material-symbols-outlined">badge</span> 1. Demographics & Identification
              </h6>
              <div class="row g-2 small">
                <div class="col-sm-6"><strong>Nama Penuh:</strong> ${p.full_name || '—'}</div>
                <div class="col-sm-6"><strong>No. IC / ID:</strong> ${p.ic_number || '—'}</div>
                <div class="col-sm-6"><strong>Tarikh Lahir:</strong> ${p.date_of_birth || '—'}</div>
                <div class="col-sm-6"><strong>Jantina:</strong> ${p.gender ? p.gender.toUpperCase() : '—'} ${p.gender_identity ? '(' + p.gender_identity + ')' : ''}</div>
                <div class="col-sm-6"><strong>Telefon:</strong> ${p.phone || '—'}</div>
                <div class="col-sm-6"><strong>Emel:</strong> ${p.email || '—'}</div>
                <div class="col-12 mt-2"><strong>Alamat Kediaman:</strong> ${p.address || '—'}</div>
              </div>
            </div>
          </div>

          <!-- 2. Emergency Contacts -->
          <div class="col-12">
            <div class="p-3 rounded-4 bg-light">
              <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                <span class="material-symbols-outlined">emergency</span> 2. Emergency Contacts
              </h6>
              <div class="row g-2 small">
                <div class="col-sm-6"><strong>Nama Kontak:</strong> ${p.emergency_contact_name || '—'}</div>
                <div class="col-sm-6"><strong>Hubungan:</strong> ${p.emergency_contact_relationship || '—'}</div>
                <div class="col-sm-6"><strong>No. Telefon:</strong> ${p.emergency_contact_phone || '—'}</div>
                <div class="col-sm-6"><strong>Telefon Alternatif:</strong> ${p.emergency_contact_alt_phone || '—'}</div>
              </div>
            </div>
          </div>

          <!-- 3. Insurance & Billing -->
          <div class="col-12">
            <div class="p-3 rounded-4 bg-light">
              <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                <span class="material-symbols-outlined">receipt_long</span> 3. Insurance & Billing
              </h6>
              <div class="row g-2 small">
                <div class="col-sm-6"><strong>Penanggung / Syarikat:</strong> ${p.insurance_payer || '—'}</div>
                <div class="col-sm-6"><strong>Jenis Liputan:</strong> ${p.insurance_coverage_type || '—'}</div>
                <div class="col-sm-6"><strong>No. Polisi / ID Ahli:</strong> ${p.insurance_policy_id || '—'}</div>
                <div class="col-sm-6"><strong>No. Kumpulan:</strong> ${p.insurance_group_number || '—'}</div>
                <div class="col-12"><strong>Maklumat Pemegang:</strong> ${p.insurance_subscriber_details || '—'}</div>
                <div class="col-12"><strong>Alamat Pengebilan:</strong> ${p.billing_address || '—'}</div>
              </div>
            </div>
          </div>

          <!-- 4. Initial Clinical Screening -->
          <div class="col-12">
            <div class="p-3 rounded-4 bg-light">
              <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                <span class="material-symbols-outlined">medical_services</span> 4. Initial Clinical Screening
              </h6>
              <div class="row g-2 small">
                <div class="col-12"><strong>Sebab Lawatan (Reason for Visit):</strong><br><span class="text-danger fw-semibold">${p.clinical_reason_for_visit || '—'}</span></div>
                <div class="col-md-6 mt-2"><strong>Ubat-ubatan Aktif:</strong><br>${p.clinical_active_medications || 'Tiada'}</div>
                <div class="col-md-6 mt-2"><strong>Alahan Diketahui:</strong><br>${p.clinical_allergies || 'Tiada'}</div>
                <div class="col-md-6 mt-2"><strong>Sejarah Pembedahan:</strong><br>${p.clinical_surgical_history || 'Tiada'}</div>
                <div class="col-md-6 mt-2"><strong>Sejarah Perubatan Keluarga:</strong><br>${p.clinical_family_history || 'Tiada'}</div>
              </div>
            </div>
          </div>
        </div>
      `;
    });
  });
</script>
</body>
</html>
