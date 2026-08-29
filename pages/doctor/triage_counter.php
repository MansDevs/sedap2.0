<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'admin', 'volunteer'])) {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Doktor');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap/sedap2.0';

$msg = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $ic_number    = trim($_POST['ic_number'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $age          = (int)($_POST['age'] ?? 0);
    $gender       = $_POST['gender'] ?? 'male';
    $temp         = (float)($_POST['temp'] ?? 36.5);
    $bp           = trim($_POST['bp'] ?? '');
    $glucose      = trim($_POST['glucose'] ?? '');
    $complaint    = trim($_POST['chief_complaint'] ?? '');
    $level        = strtolower($_POST['triage_level'] ?? 'green');

    if (empty($patient_name)) {
        $error = __('error_enter_name', 'Sila masukkan nama pesakit.');
    } else {
        try {
            $pStmt = $pdo->prepare("SELECT id FROM patients WHERE ic_number=? AND ic_number != ''");
            $pStmt->execute([$ic_number]);
            $pId = $pStmt->fetchColumn();
            if (!$pId) {
                $insP = $pdo->prepare("INSERT INTO patients (full_name, ic_number, phone, gender, created_at) VALUES (?, ?, ?, ?, NOW())");
                $insP->execute([$patient_name, $ic_number, $phone, $gender]);
                $pId = $pdo->lastInsertId();
            }

            $tIns = $pdo->prepare("INSERT INTO triage_records (patient_id, triaged_by, triage_level, chief_complaint, temperature, blood_pressure, status, triaged_at) VALUES (?, ?, ?, ?, ?, ?, 'waiting', NOW())");
            $tIns->execute([$pId, $_SESSION['user_id'], $level, $complaint, $temp, $bp]);
            header("Location: triage_list.php?success=1"); exit;
        } catch (Exception $e) {
            $error = __('error_registration', 'Ralat pendaftaran: ') . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_triage_add_title', 'Kaunter Triaj') ?> — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">add_circle</span><?= __('page_triage_add_title', 'Kaunter Triaj & Saringan') ?></h1>
        <p class="page-subtitle"><?= __('page_triage_add_sub', 'Borang kemasukan saringan pesakit dan pengesanan tahap kesihatan') ?></p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2">
          <span class="material-symbols-outlined" style="font-size:18px;">error</span><?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="row g-4">
          <div class="col-lg-8">
            <!-- Section 1: Maklumat Pesakit -->
            <div class="card mb-4">
              <div class="card-header"><span class="material-symbols-outlined">person</span><strong>1. <?= __('sec_patient_info', 'Maklumat Pesakit') ?></strong></div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= __('col_patient_name', 'Nama Penuh') ?> <span class="text-danger">*</span></label>
                    <input type="text" name="patient_name" class="form-control" placeholder="<?= __('ph_patient_name', 'Nama pesakit') ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold small"><?= __('col_ic', 'No. Kad Pengenalan / Pasport') ?></label>
                    <input type="text" name="ic_number" class="form-control" placeholder="Contoh: 900101-01-1234">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= __('col_phone', 'No. Telefon') ?></label>
                    <input type="tel" name="phone" class="form-control" placeholder="01X-XXXXXXXX">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= __('col_age', 'Umur (Tahun)') ?></label>
                    <input type="number" name="age" class="form-control" placeholder="<?= __('col_age', 'Umur') ?>" min="0" max="130">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= __('col_gender', 'Jantina') ?></label>
                    <select name="gender" class="form-select">
                      <option value="male"><?= __('gender_male', 'Lelaki') ?></option>
                      <option value="female"><?= __('gender_female', 'Perempuan') ?></option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section 2: Tanda Vital & Gejala -->
            <div class="card mb-4">
              <div class="card-header"><span class="material-symbols-outlined">vital_signs</span><strong>2. <?= __('sec_vitals_symptoms', 'Tanda Vital & Gejala Akut') ?></strong></div>
              <div class="card-body">
                <div class="row g-3 mb-3">
                  <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= __('vital_temp', 'Suhu Badan') ?> (&deg;C)</label>
                    <input type="number" step="0.1" name="temp" id="tempInput" class="form-control" value="36.8" oninput="calculateTriage()">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= __('vital_bp', 'Tekanan Darah (BP)') ?></label>
                    <input type="text" name="bp" class="form-control" placeholder="Contoh: 120/80">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold small"><?= __('vital_glucose', 'Glukosa Darah (mmol/L)') ?></label>
                    <input type="text" name="glucose" class="form-control" placeholder="Contoh: 5.4">
                  </div>
                </div>

                <label class="form-label fw-semibold small"><?= __('lbl_symptoms_experienced', 'Gejala Yang Dialami:') ?></label>
                <div class="d-flex flex-wrap gap-3">
                  <div class="form-check">
                    <input class="form-check-input symptom-cb" type="checkbox" id="sym_fever" value="fever" onchange="calculateTriage()">
                    <label class="form-check-label small" for="sym_fever"><?= __('sym_fever', 'Demam Tinggi') ?></label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input symptom-cb" type="checkbox" id="sym_diarrhea" value="diarrhea" onchange="calculateTriage()">
                    <label class="form-check-label small" for="sym_diarrhea"><?= __('sym_diarrhea', 'Cirit-birit Kerap') ?></label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input symptom-cb" type="checkbox" id="sym_vomit" value="vomit" onchange="calculateTriage()">
                    <label class="form-check-label small" for="sym_vomit"><?= __('sym_vomit', 'Muntah Berulang') ?></label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input symptom-cb" type="checkbox" id="sym_breath" value="breath" onchange="calculateTriage()">
                    <label class="form-check-label small" for="sym_breath"><?= __('sym_breath', 'Sesak Nafas') ?></label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input symptom-cb" type="checkbox" id="sym_pain" value="pain" onchange="calculateTriage()">
                    <label class="form-check-label small" for="sym_pain"><?= __('sym_pain', 'Sakit Perut Kuat') ?></label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section 3: Aduan Utama -->
            <div class="card mb-4">
              <div class="card-header"><span class="material-symbols-outlined">notes</span><strong>3. <?= __('sec_complaint_notes', 'Aduan & Catatan Klinikal') ?></strong></div>
              <div class="card-body">
                <textarea name="chief_complaint" class="form-control" rows="3" placeholder="<?= __('ph_complaint_notes', 'Catatan aduan utama pesakit, sejarah alahan, atau ubat yang diambil...') ?>"></textarea>
              </div>
            </div>
          </div>

          <!-- Section 4: Triage Result Card -->
          <div class="col-lg-4">
            <div class="card position-sticky" style="top:1rem;">
              <div class="card-header"><span class="material-symbols-outlined">health_and_safety</span><strong><?= __('triage_result_title', 'Keputusan Triaj') ?></strong></div>
              <div class="card-body text-center p-4">
                <div id="triageBadgeBox" class="p-3 rounded-3 mb-3" style="background:rgba(30,132,73,.15);">
                  <div class="fs-2 fw-bold text-success" id="triageTitle"><?= __('triage_green_short', 'HIJAU') ?></div>
                  <div class="small text-muted" id="triageDesc"><?= __('triage_green_desc', 'Kes Biasa / Bukan Kecemasan') ?></div>
                </div>

                <div class="mb-3 text-start">
                  <label class="form-label fw-semibold small"><?= __('lbl_manual_override', 'Pengubahsuaian Manual:') ?></label>
                  <select name="triage_level" id="triageSelect" class="form-select" onchange="updateBadgeFromSelect()">
                    <option value="green" selected><?= __('triage_green', 'Hijau (Standard / Biasa)') ?></option>
                    <option value="yellow"><?= __('triage_yellow', 'Kuning (Separa Kritikal)') ?></option>
                    <option value="red"><?= __('triage_red', 'Merah (Kritikal / Kecemasan)') ?></option>
                  </select>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
                  <span class="material-symbols-outlined" style="font-size:18px;">save</span>
                  <?= __('btn_submit_triage', 'Daftar & Hantar Triaj') ?>
                </button>
              </div>
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
const isEn = "<?= ($_SESSION['lang'] ?? 'ms') ?>" === 'en';
function calculateTriage() {
  const temp = parseFloat(document.getElementById('tempInput').value) || 36.5;
  const breath = document.getElementById('sym_breath').checked;
  const vomit = document.getElementById('sym_vomit').checked;
  const diarrhea = document.getElementById('sym_diarrhea').checked;
  const fever = document.getElementById('sym_fever').checked || temp >= 38.5;

  let level = 'green';
  if (breath || (fever && vomit && diarrhea) || temp >= 39.5) {
    level = 'red';
  } else if (fever || vomit || diarrhea) {
    level = 'yellow';
  }

  document.getElementById('triageSelect').value = level;
  updateBadgeFromSelect();
}
function updateBadgeFromSelect() {
  const val = document.getElementById('triageSelect').value;
  const box = document.getElementById('triageBadgeBox');
  const title = document.getElementById('triageTitle');
  const desc = document.getElementById('triageDesc');

  if (val === 'red') {
    box.style.background = 'rgba(192,57,43,.15)';
    title.className = 'fs-2 fw-bold text-danger';
    title.textContent = isEn ? 'RED' : 'MERAH';
    desc.textContent = isEn ? 'Critical — Immediate Emergency Care Required' : 'Kritikal — Perlu Rawatan Segera';
  } else if (val === 'yellow') {
    box.style.background = 'rgba(212,160,23,.15)';
    title.className = 'fs-2 fw-bold text-warning';
    title.textContent = isEn ? 'YELLOW' : 'KUNING';
    desc.textContent = isEn ? 'Semi-Critical — Prompt Medical Attention Needed' : 'Separa Kritikal — Rawatan Diperlukan Segera';
  } else {
    box.style.background = 'rgba(30,132,73,.15)';
    title.className = 'fs-2 fw-bold text-success';
    title.textContent = isEn ? 'GREEN' : 'HIJAU';
    desc.textContent = isEn ? 'Standard — Mild / Stable Non-Emergency Case' : 'Biasa — Kes Ringan / Stabil';
  }
}
</script>
</body>
</html>
