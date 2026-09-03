<?php
/**
 * ============================================================================
 *   SeDaP 2.0 — Volunteer Field Triage Counter & Assessment
 *   Section 1: Personal Information & Background (Demographics)
 *   Section 2.1: Vital Signs Screening & Basic Laboratory Tests
 *   Section 2.2: Medical History & Volunteer Interview Findings
 *   Section 2.3: Urgency Rating & System (Green / Yellow / Red Single-Select)
 * ============================================================================
 */
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['volunteer', 'doctor', 'nurse', 'medical_assistant', 'admin'])) {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Petugas Triaj');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = $_ROOT ?? sedap_root();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Section 1: Demographics
        $fullName       = trim($_POST['patient_name'] ?? '');
        $icNumber       = trim($_POST['ic_number'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');
        $age            = !empty($_POST['age']) ? (int)$_POST['age'] : null;
        $gender         = in_array($_POST['gender'] ?? '', ['male', 'female']) ? $_POST['gender'] : 'male';
        $occupation     = trim($_POST['occupation'] ?? '');
        $educationLevel = trim($_POST['education_level'] ?? 'Secondary School');

        // Section 2.1: Vital Signs & Lab Tests
        $temperature    = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
        $bloodPressure  = trim($_POST['blood_pressure'] ?? '');
        $glucoseLevel   = !empty($_POST['glucose_level']) ? floatval($_POST['glucose_level']) : null;
        $lipidProfile   = !empty($_POST['lipid_profile']) ? floatval($_POST['lipid_profile']) : null;
        $rawSymptoms    = $_POST['symptoms'] ?? '';
        if (is_array($rawSymptoms)) {
            $symptoms = trim(implode(', ', array_filter($rawSymptoms)));
        } else {
            $symptoms = trim((string)$rawSymptoms);
        }

        // Section 2.2: Medical History & Interview Notes
        $medicalHistory = trim($_POST['medical_history'] ?? '');
        $interviewNotes = trim($_POST['interview_notes'] ?? '');

        // Section 2.3: Urgency Rating (Green / Yellow / Red Single-Select)
        $triageLevel    = in_array($_POST['triage_level'] ?? '', ['green', 'yellow', 'red']) ? $_POST['triage_level'] : 'green';

        if (empty($fullName)) {
            throw new Exception("Sila isikan Nama Penuh Pesakit.");
        }

        if (empty($symptoms)) {
            throw new Exception("Sila tuliskan Gejala Utama / Akut pesakit.");
        }

        $pdo->beginTransaction();

        // 1. Find or create patient
        $patientId = null;
        if (!empty($icNumber)) {
            $pCheck = $pdo->prepare("SELECT id FROM patients WHERE ic_number = ? LIMIT 1");
            $pCheck->execute([$icNumber]);
            $patientId = $pCheck->fetchColumn();
        }

        if (!$patientId) {
            $nextNum = $pdo->query("SELECT IFNULL(MAX(id), 0) + 1 FROM patients")->fetchColumn();
            $regNumber = 'PT-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

            $stmtP = $pdo->prepare("
                INSERT INTO patients (registration_number, full_name, ic_number, gender, phone, status, registered_by, created_at)
                VALUES (?, ?, ?, ?, ?, 'in_triage', ?, NOW())
            ");
            $stmtP->execute([$regNumber, $fullName, $icNumber, $gender, $phone, $_SESSION['user_id'] ?? null]);
            $patientId = (int)$pdo->lastInsertId();
        }

        // 2. Insert Triage Record
        $chiefComplaint = mb_substr($symptoms, 0, 250);
        $nextTriageNum = (int)$pdo->query("SELECT IFNULL(MAX(id), 0) + 1 FROM triage_records")->fetchColumn();
        $triageCustomId = 'TI-' . str_pad($nextTriageNum, 3, '0', STR_PAD_LEFT);
        
        // Ensure 12-digit IC format without hyphens
        $cleanIC = substr(preg_replace('/[^0-9]/', '', $icNumber), 0, 12);

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

        $pdo->commit();
        header("Location: triage_list.php?success=1");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $err = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kaunter Triaj & Saringan Klinikal — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  
  <style>
    body { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
    .triage-card-opt {
      border: 2px solid var(--cui-border-color, #e2e8f0);
      border-radius: 16px;
      padding: 16px;
      cursor: pointer;
      transition: all 0.2s ease;
      background: var(--cui-body-bg, #ffffff);
    }
    .triage-card-opt:hover { transform: translateY(-2px); }
    .triage-card-opt.active-green { border-color: #10b981 !important; background: rgba(16, 185, 129, 0.08) !important; }
    .triage-card-opt.active-yellow { border-color: #f59e0b !important; background: rgba(245, 158, 11, 0.08) !important; }
    .triage-card-opt.active-red { border-color: #ef4444 !important; background: rgba(239, 68, 68, 0.08) !important; }
  </style>
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
      <main class="container-fluid px-3 px-md-4 py-4 max-w-6xl mx-auto">
        
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="material-symbols-outlined text-primary fs-3">emergency</span>
              <h1 class="h3 fw-bold mb-0">Kaunter Triaj & Saringan Lapangan</h1>
            </div>
            <p class="text-secondary small mb-0">Saringan tanda vital, penilaian gejala cirit-birit/muntah, dan klasifikasi keutamaan rawatan</p>
          </div>
          <div class="badge bg-primary rounded-pill px-3 py-2 d-flex align-items-center gap-1.5 fs-6">
            <span class="material-symbols-outlined" style="font-size:18px;">schedule</span>
            <span class="font-monospace"><?= date('d M Y, H:i') ?></span>
          </div>
        </div>

        <?php if ($err): ?>
          <div class="alert alert-danger py-3 px-4 rounded-4 shadow-sm mb-4 border-0 bg-danger text-white">
            <div class="fw-bold">Ralat Pendaftaran Triaj</div>
            <div class="small opacity-90"><?= htmlspecialchars($err) ?></div>
          </div>
        <?php endif; ?>

        <form method="POST">
          <div class="card border rounded-4 shadow-sm mb-4">
            <div class="card-body p-4 p-md-5 space-y-4">
              
              <!-- SECTION 1: DEMOGRAPHICS -->
              <div class="mb-4">
                <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom text-primary">
                  <span class="material-symbols-outlined">person</span>
                  <h5 class="fw-bold mb-0">Section 1: Personal Information & Background (Demographics)</h5>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold small">Nama Penuh Pesakit (Full Name) <span class="text-danger">*</span></label>
                    <input type="text" name="patient_name" class="form-control" required placeholder="Ahmad bin Ali">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold small">No. Kad Pengenalan / SeDaP ID</label>
                    <input type="text" name="ic_number" id="v_ic" class="form-control" placeholder="12 digit tanpa sempang atau PT-0001" oninput="autoDetectAgeGender(this.value)">
                    <div class="form-text small" id="v_ic_hint">12 digit tanpa sempang</div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold small">No. Telefon</label>
                    <input type="tel" name="phone" class="form-control" placeholder="012-3456789">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold small">Umur (Age)</label>
                    <input type="number" name="age" id="v_age" class="form-control" placeholder="35">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold small">Jantina (Gender)</label>
                    <select name="gender" id="v_gender" class="form-select">
                      <option value="male">Lelaki (Male)</option>
                      <option value="female">Perempuan (Female)</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold small">Pekerjaan (Occupation)</label>
                    <input type="text" name="occupation" class="form-control" placeholder="Guru / Peniaga / Suri Rumah">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold small">Tahap Pendidikan (Education Level)</label>
                    <select name="education_level" class="form-select">
                      <option value="Secondary School">Sekolah Menengah (Secondary School)</option>
                      <option value="Primary School">Sekolah Rendah (Primary School)</option>
                      <option value="Diploma / Degree">Diploma / Ijazah (Diploma / Degree)</option>
                      <option value="No Formal Education">Tiada Pendidikan Formal (No Formal Education)</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- SECTION 2.1: VITALS & LAB -->
              <div class="mb-4 pt-3 border-top">
                <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom text-primary">
                  <span class="material-symbols-outlined">vital_signs</span>
                  <h5 class="fw-bold mb-0">Section 2.1: Vital Signs Screening & Basic Laboratory Tests</h5>
                </div>
                <div class="row g-3 mb-3">
                  <div class="col-md-3">
                    <label class="form-label fw-semibold small">Suhu Badan (°C)</label>
                    <input type="number" step="0.1" name="temperature" id="v_temp" class="form-control" value="36.8">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold small">Tekanan Darah (BP)</label>
                    <input type="text" name="blood_pressure" class="form-control" placeholder="120/80 mmHg">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold small">Paras Glukosa (mmol/L)</label>
                    <input type="number" step="0.1" name="glucose_level" class="form-control" placeholder="5.4">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold small">Profil Lipid (mmol/L)</label>
                    <input type="number" step="0.1" name="lipid_profile" class="form-control" placeholder="4.6">
                  </div>
                </div>

                <div class="mb-2">
                  <label class="form-label fw-bold text-danger small text-uppercase d-flex align-items-center justify-content-between">
                    <span>Senarai Gejala Akut (Main Symptoms) <span class="text-danger">*</span></span>
                    <span class="text-danger lowercase fw-normal" style="font-size: 11px;">(Wajib — tuliskan gejala pesakit)</span>
                  </label>
                  <textarea name="symptoms" class="form-control rounded-3" rows="3" required placeholder="Tuliskan gejala utama / akut pesakit di sini (cth: Cirit-birit berulang kali, muntah, demam panas, sakit perut...)"></textarea>
                  <div class="form-text small">Tuliskan ringkasan simptom utama yang dialami pesakit.</div>
                </div>
              </div>

              <!-- SECTION 2.2: MEDICAL HISTORY & INTERVIEW -->
              <div class="mb-4 pt-3 border-top">
                <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom text-primary">
                  <span class="material-symbols-outlined">history_edu</span>
                  <h5 class="fw-bold mb-0">Section 2.2: Medical History & Interview Findings</h5>
                </div>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold small">Penyakit Sedia Ada / Alahan Ubat</label>
                    <textarea name="medical_history" rows="2" class="form-control" placeholder="Contoh: Diabetes, Darah Tinggi, Alahan Penicillin..."></textarea>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold small">Catatan Temu Bual Sukarelawan</label>
                    <textarea name="interview_notes" rows="2" class="form-control" placeholder="Punca pendedahan, masa mula gejala, aduan tambahan..."></textarea>
                  </div>
                </div>
              </div>

              <!-- SECTION 2.3: URGENCY RATING -->
              <div class="pt-3 border-top">
                <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom text-primary">
                  <span class="material-symbols-outlined">traffic</span>
                  <h5 class="fw-bold mb-0">Section 2.3: Urgency Rating & System (Triage Category)</h5>
                </div>
                <div class="row g-3">
                  <div class="col-md-4">
                    <div class="triage-card-opt active-green" id="v_card_green" onclick="selectVolTriage('green')">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="triage_level" id="v_radio_green" value="green" checked>
                        <label class="form-check-label fw-bold text-success" for="v_radio_green">
                          🟢 HIJAU (Green)
                        </label>
                      </div>
                      <div class="small text-secondary mt-1">Non-urgent / stable / general screening</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="triage-card-opt" id="v_card_yellow" onclick="selectVolTriage('yellow')">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="triage_level" id="v_radio_yellow" value="yellow">
                        <label class="form-check-label fw-bold text-warning" for="v_radio_yellow">
                          🟡 KUNING (Yellow)
                        </label>
                      </div>
                      <div class="small text-secondary mt-1">Requires treatment / moderate symptoms</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="triage-card-opt" id="v_card_red" onclick="selectVolTriage('red')">
                      <div class="form-check">
                        <input class="form-check-input" type="radio" name="triage_level" id="v_radio_red" value="red">
                        <label class="form-check-label fw-bold text-danger" for="v_radio_red">
                          🔴 MERAH (Red)
                        </label>
                      </div>
                      <div class="small text-secondary mt-1">Emergency / severe dehydration</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 d-flex align-items-center gap-2 fw-bold shadow-sm">
                  <span class="material-symbols-outlined">save</span>
                  <span>Simpan & Hantar Saringan Triaj</span>
                </button>
              </div>

            </div>
          </div>
        </form>
      </main>
    </div>
    <?php include '../shared/includes/footer.php'; ?>
  </div>

  <script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
  <script>
    function selectVolTriage(lvl) {
      ['green', 'yellow', 'red'].forEach(l => {
        const card = document.getElementById('v_card_' + l);
        const radio = document.getElementById('v_radio_' + l);
        if (card && radio) {
          card.className = 'triage-card-opt';
          if (l === lvl) {
            radio.checked = true;
            card.classList.add('active-' + lvl);
          }
        }
      });
    }

    function autoDetectAgeGender(ic) {
      const clean = ic.replace(/[^0-9]/g, '');
      const hint = document.getElementById('v_ic_hint');
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

        document.getElementById('v_age').value = age;
        document.getElementById('v_gender').value = gender;
        hint.innerHTML = `<span class="text-success fw-bold">✓ Lahir: ${dd}/${mm}/${year} (${age} thn, ${gender === 'female' ? 'Perempuan' : 'Lelaki'})</span>`;
      } else {
        hint.innerHTML = `12 digit tanpa sempang`;
      }
    }
  </script>
</body>
</html>
