<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header('Location: ../auth/login.php'); exit;
}

$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Sukarelawan');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT = $_ROOT ?? sedap_root();

$msg = '';
$err = '';

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

            $msg = "Pesakit <strong>" . htmlspecialchars($fullName) . "</strong> ($regNumber) berjaya didaftarkan.";
        } catch (PDOException $e) {
            $err = 'Ralat: ' . $e->getMessage();
        }
    }
}

$patients = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pendaftaran Pesakit Komuniti — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    /* 24px corner radius for form controls */
    .form-control-24,
    .wizard-step-pane input.form-control,
    .wizard-step-pane select.form-select,
    .custom-modal-24 input.form-control,
    .custom-modal-24 select.form-select {
      border-radius: 24px !important;
      padding: 0.65rem 1.25rem;
      border: 1.5px solid #d1d5db;
      font-size: 0.92rem;
    }
    .wizard-step-pane textarea.form-control,
    .custom-modal-24 textarea.form-control {
      border-radius: 20px !important;
      padding: 0.85rem 1.25rem;
      border: 1.5px solid #d1d5db;
      font-size: 0.92rem;
    }
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
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #e2e8f0;
      color: #64748b;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      margin-bottom: 0.4rem;
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
      font-size: 0.78rem;
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
      top: 24px;
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
  <?php include '../shared/includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
          <h1 class="page-title d-flex align-items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-size:32px;">person_add</span>
            <span>Pendaftaran Pesakit Komuniti</span>
          </h1>
          <p class="page-subtitle">Pendaftaran berstruktur kategori demi kategori bagi pesakit komuniti</p>
        </div>
        <button class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2" style="border-radius:24px;" data-coreui-toggle="modal" data-coreui-target="#volunteerPatientModal">
          <span class="material-symbols-outlined" style="font-size:20px;">add_circle</span>
          <span class="fw-semibold">Daftar Pesakit Baharu</span>
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

      <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
          <div class="fw-bold d-flex align-items-center gap-2 text-dark">
            <span class="material-symbols-outlined text-primary">groups</span>
            Senarai Pesakit Berdaftar (<?= count($patients) ?>)
          </div>
          <div style="width:260px;">
            <input type="text" id="volSearch" class="form-control form-control-24" placeholder="Cari nama, IC...">
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="volPatientsTable">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">No. Pendaftaran</th>
                  <th>Nama Penuh</th>
                  <th>No. IC</th>
                  <th>Telefon</th>
                  <th>Kontak Kecemasan</th>
                  <th>Insurans</th>
                  <th>Tarikh Daftar</th>
                  <th class="text-center pe-4">Tindakan</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($patients)): ?>
                  <tr><td colspan="8" class="text-center text-muted py-5">Tiada rekod pesakit buat masa ini.</td></tr>
                <?php else: ?>
                  <?php foreach ($patients as $p): ?>
                    <tr class="vpatient-row">
                      <td class="ps-4 fw-semibold text-primary"><?= htmlspecialchars($p['registration_number'] ?? 'PT-' . $p['id']) ?></td>
                      <td class="fw-semibold"><?= htmlspecialchars($p['full_name']) ?></td>
                      <td class="small text-muted"><?= htmlspecialchars($p['ic_number'] ?? '—') ?></td>
                      <td class="small"><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
                      <td class="small"><?= !empty($p['emergency_contact_name']) ? htmlspecialchars($p['emergency_contact_name']) . ' (' . htmlspecialchars($p['emergency_contact_phone'] ?? '') . ')' : '—' ?></td>
                      <td class="small"><?= !empty($p['insurance_payer']) ? htmlspecialchars($p['insurance_payer']) : '—' ?></td>
                      <td class="small text-muted"><?= !empty($p['created_at']) ? date('d/m/Y H:i', strtotime($p['created_at'])) : '—' ?></td>
                      <td class="text-center pe-4">
                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 view-p-btn"
                                data-patient='<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>'
                                data-coreui-toggle="modal" data-coreui-target="#volViewModal">
                          <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;">visibility</span>
                          <span>Lihat</span>
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
  <?php include '../shared/includes/footer.php'; ?>
</div>

<!-- VOLUNTEER REGISTRATION MODAL WIZARD -->
<div class="modal fade custom-modal-24" id="volunteerPatientModal" tabindex="-1" aria-hidden="true" data-coreui-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <form method="POST" id="volWizardForm" class="wizard-form">
        <input type="hidden" name="action" value="add">
        <div class="modal-header bg-light py-3 px-4 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-size:28px;">person_add</span>
            <div>
              <h5 class="modal-title fw-bold mb-0">Borang Pendaftaran Pesakit Komuniti</h5>
              <small class="text-muted">Isi maklumat kategori demi kategori secara terperinci</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>

        <div class="modal-body p-4">
          <!-- Stepper -->
          <div class="category-step-nav">
            <div class="stepper-line"><div class="stepper-progress" id="vStepperProgress"></div></div>
            <button type="button" class="category-step-btn active" data-step="1" onclick="vGoToStep(1)">
              <div class="category-badge-num">1</div>
              <span class="category-label">1. Demographics & Identification</span>
            </button>
            <button type="button" class="category-step-btn" data-step="2" onclick="vGoToStep(2)">
              <div class="category-badge-num">2</div>
              <span class="category-label">2. Emergency Contacts</span>
            </button>
            <button type="button" class="category-step-btn" data-step="3" onclick="vGoToStep(3)">
              <div class="category-badge-num">3</div>
              <span class="category-label">3. Insurance & Billing</span>
            </button>
            <button type="button" class="category-step-btn" data-step="4" onclick="vGoToStep(4)">
              <div class="category-badge-num">4</div>
              <span class="category-label">4. Initial Clinical Screening</span>
            </button>
          </div>

          <!-- Category 1 -->
          <div class="wizard-step-pane" id="vStepPane1">
            <div class="category-purpose-card">
              <strong class="d-block text-primary">Kategori 1: Demographics & Identification</strong>
              <span class="small text-secondary"><strong>Tujuan:</strong> Prevents duplicate records, ensures accurate patient verification, and enables direct communication.</span>
            </div>
            <div class="row g-3">
              <div class="col-md-8"><label class="form-label fw-semibold small">Nama Penuh (Full Legal Name) *</label><input type="text" name="full_name" id="v_name" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label fw-semibold small">No. IC / Pasport *</label><input type="text" name="ic_number" id="v_ic" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label fw-semibold small">Tarikh Lahir *</label><input type="date" name="date_of_birth" id="v_dob" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label fw-semibold small">Jantina Biologikal</label><select name="gender" class="form-select"><option value="male">Lelaki</option><option value="female">Perempuan</option></select></div>
              <div class="col-md-4"><label class="form-label fw-semibold small">Identiti Jantina</label><input type="text" name="gender_identity" class="form-control" placeholder="Pilihan"></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">No. Telefon *</label><input type="tel" name="phone" id="v_phone" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">Emel Utama</label><input type="email" name="email" class="form-control"></div>
              <div class="col-12"><label class="form-label fw-semibold small">Alamat Kediaman</label><textarea name="address" rows="2" class="form-control"></textarea></div>
            </div>
          </div>

          <!-- Category 2 -->
          <div class="wizard-step-pane d-none" id="vStepPane2">
            <div class="category-purpose-card">
              <strong class="d-block text-primary">Kategori 2: Emergency Contacts</strong>
              <span class="small text-secondary"><strong>Tujuan:</strong> Crucial for urgent medical situations or clinical updates when the patient is unable to communicate.</span>
            </div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label fw-semibold small">Nama Kontak Kecemasan *</label><input type="text" name="emergency_contact_name" class="form-control"></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">Hubungan</label><select name="emergency_contact_relationship" class="form-select"><option value="Spouse">Pasangan</option><option value="Parent">Ibu/Bapa</option><option value="Child">Anak</option><option value="Sibling">Adik-beradik</option><option value="Other">Lain-lain</option></select></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">No. Telefon Kecemasan *</label><input type="tel" name="emergency_contact_phone" class="form-control"></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">Telefon Alternatif</label><input type="tel" name="emergency_contact_alt_phone" class="form-control"></div>
            </div>
          </div>

          <!-- Category 3 -->
          <div class="wizard-step-pane d-none" id="vStepPane3">
            <div class="category-purpose-card">
              <strong class="d-block text-primary">Kategori 3: Insurance & Billing</strong>
              <span class="small text-secondary"><strong>Tujuan:</strong> Validates coverage active status, facilitates claim submission, and clarifies financial responsibility.</span>
            </div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label fw-semibold small">Syarikat Penanggung (Payer)</label><input type="text" name="insurance_payer" class="form-control" placeholder="MySalam / KKM / Insurans Sendiri"></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">Jenis Perlindungan</label><select name="insurance_coverage_type" class="form-select"><option value="Primary">Utama (Primary)</option><option value="Secondary">Sekunder</option><option value="Self-Pay">Bayaran Sendiri</option></select></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">No. Polisi / ID Ahli</label><input type="text" name="insurance_policy_id" class="form-control"></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">No. Kumpulan</label><input type="text" name="insurance_group_number" class="form-control"></div>
              <div class="col-12"><label class="form-label fw-semibold small">Maklumat Pemegang Polisi</label><input type="text" name="insurance_subscriber_details" class="form-control"></div>
              <div class="col-12"><label class="form-label fw-semibold small">Alamat Pengebilan</label><textarea name="billing_address" rows="2" class="form-control"></textarea></div>
            </div>
          </div>

          <!-- Category 4 -->
          <div class="wizard-step-pane d-none" id="vStepPane4">
            <div class="category-purpose-card">
              <strong class="d-block text-primary">Kategori 4: Initial Clinical Screening</strong>
              <span class="small text-secondary"><strong>Tujuan:</strong> Ensures basic patient safety prior to consultation (e.g., preventing adverse drug interactions).</span>
            </div>
            <div class="row g-3">
              <div class="col-12"><label class="form-label fw-semibold small">Sebab Utama Lawatan (Reason for Visit) *</label><textarea name="clinical_reason_for_visit" rows="2" class="form-control" required placeholder="Aduan utama, simptom"></textarea></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">Ubat-ubatan Aktif</label><textarea name="clinical_active_medications" rows="2" class="form-control"></textarea></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">Alahan Ubat / Makanan</label><textarea name="clinical_allergies" rows="2" class="form-control"></textarea></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">Sejarah Pembedahan</label><textarea name="clinical_surgical_history" rows="2" class="form-control"></textarea></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">Sejarah Perubatan Keluarga</label><textarea name="clinical_family_history" rows="2" class="form-control"></textarea></div>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light py-3 px-4 d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary px-4" id="vBtnPrev" style="border-radius:24px;display:none;" onclick="vPrevStep()">Kategori Sebelumnya</button>
          <button type="button" class="btn btn-secondary px-4" style="border-radius:24px;" data-coreui-dismiss="modal">Batal</button>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary px-4" id="vBtnNext" style="border-radius:24px;" onclick="vNextStep()">Seterusnya</button>
            <button type="submit" class="btn btn-success px-4 text-white" id="vBtnSubmit" style="border-radius:24px;display:none;">Daftar Pesakit</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="volViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
      <div class="modal-header bg-primary text-white py-3 px-4">
        <h5 class="modal-title fw-bold mb-0" id="volViewTitle">Profil Pesakit</h5>
        <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="volViewBody"></div>
      <div class="modal-footer bg-light"><button type="button" class="btn btn-secondary rounded-pill px-4" data-coreui-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script>
  let vCurrentStep = 1;
  const vTotalSteps = 4;

  function vUpdateUI() {
    for (let i = 1; i <= vTotalSteps; i++) {
      const pane = document.getElementById('vStepPane' + i);
      if (pane) pane.classList.toggle('d-none', i !== vCurrentStep);
      const btn = document.querySelector(`#volunteerPatientModal .category-step-btn[data-step="${i}"]`);
      if (btn) {
        btn.classList.toggle('active', i === vCurrentStep);
        btn.classList.toggle('completed', i < vCurrentStep);
      }
    }
    const prog = ((vCurrentStep - 1) / (vTotalSteps - 1)) * 100;
    document.getElementById('vStepperProgress').style.width = prog + '%';

    document.getElementById('vBtnPrev').style.display = vCurrentStep > 1 ? 'inline-block' : 'none';
    document.getElementById('vBtnNext').style.display = vCurrentStep === vTotalSteps ? 'none' : 'inline-block';
    document.getElementById('vBtnSubmit').style.display = vCurrentStep === vTotalSteps ? 'inline-block' : 'none';
  }

  function vValidate(step) {
    if (step === 1) {
      const n = document.getElementById('v_name').value.trim();
      const ic = document.getElementById('v_ic').value.trim();
      const ph = document.getElementById('v_phone').value.trim();
      const dob = document.getElementById('v_dob').value;
      if (!n || !ic || !ph || !dob) {
        alert('Sila lengkapkan maklumat wajib dalam Kategori 1.');
        return false;
      }
    }
    return true;
  }

  function vNextStep() {
    if (!vValidate(vCurrentStep)) return;
    if (vCurrentStep < vTotalSteps) {
      vCurrentStep++;
      vUpdateUI();
    }
  }

  function vPrevStep() {
    if (vCurrentStep > 1) {
      vCurrentStep--;
      vUpdateUI();
    }
  }

  function vGoToStep(s) {
    if (s > vCurrentStep && !vValidate(vCurrentStep)) return;
    vCurrentStep = s;
    vUpdateUI();
  }

  document.getElementById('volSearch')?.addEventListener('keyup', function(e) {
    const val = e.target.value.toLowerCase();
    document.querySelectorAll('#volPatientsTable tbody tr.vpatient-row').forEach(r => {
      r.style.display = r.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
  });

  document.querySelectorAll('.view-p-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const p = JSON.parse(this.getAttribute('data-patient'));
      document.getElementById('volViewTitle').innerText = p.full_name + ' (' + (p.registration_number || 'PT-' + p.id) + ')';
      document.getElementById('volViewBody').innerHTML = `
        <div class="row g-3">
          <div class="col-12 p-3 bg-light rounded-3">
            <h6 class="fw-bold text-primary">1. Demographics & Identification</h6>
            <div class="small">IC: ${p.ic_number||'—'} | Tel: ${p.phone||'—'} | DOB: ${p.date_of_birth||'—'} | Jantina: ${p.gender||'—'}</div>
            <div class="small mt-1">Alamat: ${p.address||'—'}</div>
          </div>
          <div class="col-12 p-3 bg-light rounded-3">
            <h6 class="fw-bold text-primary">2. Emergency Contacts</h6>
            <div class="small">Kontak: ${p.emergency_contact_name||'—'} (${p.emergency_contact_relationship||'—'}) | Tel: ${p.emergency_contact_phone||'—'}</div>
          </div>
          <div class="col-12 p-3 bg-light rounded-3">
            <h6 class="fw-bold text-primary">3. Insurance & Billing</h6>
            <div class="small">Penanggung: ${p.insurance_payer||'—'} | Polisi: ${p.insurance_policy_id||'—'} | Jenis: ${p.insurance_coverage_type||'—'}</div>
          </div>
          <div class="col-12 p-3 bg-light rounded-3">
            <h6 class="fw-bold text-primary">4. Initial Clinical Screening</h6>
            <div class="small text-danger fw-semibold">Sebab Lawatan: ${p.clinical_reason_for_visit||'—'}</div>
            <div class="small mt-1">Ubat Semasa: ${p.clinical_active_medications||'Tiada'} | Alahan: ${p.clinical_allergies||'Tiada'}</div>
          </div>
        </div>
      `;
    });
  });
</script>
</body>
</html>
