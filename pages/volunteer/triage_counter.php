<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Sukarelawan');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['patient_name'] ?? '');
    $ic   = trim($_POST['ic_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $temp = (float)($_POST['temp'] ?? 36.5);
    $complaint = trim($_POST['chief_complaint'] ?? '');
    $level = strtolower($_POST['triage_level'] ?? 'green');

    if ($name) {
        $pStmt = $pdo->prepare("SELECT id FROM patients WHERE ic_number=? AND ic_number != ''");
        $pStmt->execute([$ic]);
        $pId = $pStmt->fetchColumn();
        if (!$pId) {
            $pdo->prepare("INSERT INTO patients (full_name, ic_number, phone, created_at) VALUES (?, ?, ?, NOW())")->execute([$name, $ic, $phone]);
            $pId = $pdo->lastInsertId();
        }
        $pdo->prepare("INSERT INTO triage_records (patient_id, triaged_by, triage_level, chief_complaint, temperature, status, triaged_at) VALUES (?, ?, ?, ?, ?, 'waiting', NOW())")
            ->execute([$pId, $_SESSION['user_id'], $level, $complaint, $temp]);
        header("Location: triage_list.php?success=1"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kaunter Triaj — SeDaP Sukarelawan</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../shared/includes/sidebar_volunteer.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">add_circle</span>Pendaftaran Triaj Lapangan</h1>
        <p class="page-subtitle">Saringan awal pesakit di kaunter komuniti</p>
      </div>

      <form method="POST">
        <div class="card mb-4">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label fw-semibold small">Nama Pesakit *</label><input type="text" name="patient_name" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label fw-semibold small">No. IC</label><input type="text" name="ic_number" class="form-control"></div>
              <div class="col-md-4"><label class="form-label fw-semibold small">No. Telefon</label><input type="tel" name="phone" class="form-control"></div>
              <div class="col-md-4"><label class="form-label fw-semibold small">Suhu Badan (&deg;C)</label><input type="number" step="0.1" name="temp" class="form-control" value="36.8"></div>
              <div class="col-md-4"><label class="form-label fw-semibold small">Tahap Triaj</label><select name="triage_level" class="form-select"><option value="green">Hijau (Biasa)</option><option value="yellow">Kuning (Separa Kritikal)</option><option value="red">Merah (Kritikal)</option></select></div>
              <div class="col-12"><label class="form-label fw-semibold small">Aduan / Gejala</label><textarea name="chief_complaint" class="form-control" rows="3" placeholder="Contoh: Demam 2 hari, cirit-birit..."></textarea></div>
            </div>
            <button type="submit" class="btn btn-primary mt-3 d-flex align-items-center gap-1"><span class="material-symbols-outlined" style="font-size:18px;">save</span>Hantar Saringan</button>
          </div>
        </div>
      </form>
    </main>
  </div>
  <?php include '../shared/includes/footer.php'; ?>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
</body>
</html>
