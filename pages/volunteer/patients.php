<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'volunteer') {
    header('Location: ../auth/login.php'); exit;
}
$userName  = htmlspecialchars($_SESSION['user_name'] ?? 'Sukarelawan');
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap/sedap2.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $ic   = trim($_POST['ic_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($name) {
        $pdo->prepare("INSERT INTO patients (full_name, ic_number, phone, created_at) VALUES (?,?,?,NOW())")->execute([$name, $ic, $phone]);
        header("Location: patients.php?success=1"); exit;
    }
}
$patients = $pdo->query("SELECT * FROM patients ORDER BY full_name LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pendaftaran Pesakit — SeDaP</title>
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
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">person_add</span>Pendaftaran Pesakit Komuniti</h1>
      </div>
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="card">
            <div class="card-header"><strong>Daftar Pesakit</strong></div>
            <div class="card-body">
              <form method="POST">
                <div class="mb-3"><label class="form-label small fw-semibold">Nama Penuh *</label><input type="text" name="name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">No. IC</label><input type="text" name="ic_number" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Telefon</label><input type="tel" name="phone" class="form-control"></div>
                <button type="submit" class="btn btn-primary w-100">Daftar</button>
              </form>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header"><strong>Senarai Pesakit</strong></div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light"><tr><th>Nama</th><th>No. IC</th><th>Telefon</th></tr></thead>
                  <tbody>
                    <?php foreach ($patients as $p): ?>
                      <tr><td class="fw-semibold"><?= htmlspecialchars($p['full_name']) ?></td><td class="small text-muted"><?= htmlspecialchars($p['ic_number'] ?? '—') ?></td><td class="small"><?= htmlspecialchars($p['phone'] ?? '—') ?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
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
