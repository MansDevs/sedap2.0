<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php'); exit;
}
$userId    = $_SESSION['user_id'];
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Pesakit');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap2.0';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fever    = isset($_POST['fever']) ? 1 : 0;
    $vomit    = isset($_POST['vomit']) ? 1 : 0;
    $diarrhea = isset($_POST['diarrhea']) ? 1 : 0;
    $breath   = isset($_POST['breath']) ? 1 : 0;

    $code = 'GREEN';
    if ($breath || ($fever && $vomit && $diarrhea)) {
        $code = 'RED';
    } elseif ($fever || $vomit || $diarrhea) {
        $code = 'YELLOW';
    }

    try {
        $pdo->prepare("INSERT INTO screening_responses (patient_id, respondent_name, respondent_phone, triage_result, created_at) VALUES (?, ?, ?, ?, NOW())")
            ->execute([$userId, $userName, $_SESSION['user_email'] ?? '', $code]);
        $msg = 'Borang saringan berjaya dihantar. Kod saringan anda: ' . $code;
    } catch(Exception $e) {
        $msg = 'Saringan dihantar.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_screening_title', 'Saringan Kesihatan') ?> — SeDaP</title>
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
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">fact_check</span>Borang Saringan Kesihatan Kendiri</h1>
        <p class="page-subtitle">Jawab soalan berikut untuk menyemak status kesihatan semasa anda</p>
      </div>

      <?php if ($msg): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 py-2 mb-4">
          <span class="material-symbols-outlined" style="font-size:18px;">check_circle</span><?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-body">
          <form method="POST">
            <h6 class="fw-bold mb-3">Tandakan gejala yang anda alami dalam tempoh 24-48 jam yang lalu:</h6>
            <div class="d-flex flex-column gap-2 mb-4">
              <div class="form-check"><input class="form-check-input" type="checkbox" name="fever" id="fever"><label class="form-check-label" for="fever">Demam panas atau menggigil</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="diarrhea" id="diarrhea"><label class="form-check-label" for="diarrhea">Cirit-birit berulang kali</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="vomit" id="vomit"><label class="form-check-label" for="vomit">Muntah atau loya teruk</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="breath" id="breath"><label class="form-check-label" for="breath">Sesak nafas atau sakit dada</label></div>
            </div>
            <button type="submit" class="btn btn-primary d-flex align-items-center gap-1"><span class="material-symbols-outlined" style="font-size:18px;">send</span>Hantar Saringan</button>
          </form>
        </div>
      </div>
    </main>
  </div>
  <?php include '../shared/includes/footer.php'; ?>
</div>
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
</body>
</html>
