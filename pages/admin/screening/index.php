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

$screenings = [];
try {
    $screenings = $pdo->query("SELECT * FROM screening_responses ORDER BY created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('page_screening_title', 'Saringan Kesihatan') ?> — SeDaP</title>
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/coreui.min.css?v=2.2">
  <link rel="stylesheet" href="<?= $_ROOT ?>/assets/css/sedap.css?v=2.5">
  <script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
</head>
<body class="layout-fixed">
  <?php include '../../shared/includes/sidebar_admin.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include '../../shared/includes/header.php'; ?>
    <div class="body flex-grow-1">
    <main class="container-fluid px-4 py-4">
      <div class="mb-4">
        <h1 class="page-title"><span class="material-symbols-outlined" style="color:var(--cui-primary);">fact_check</span>Respons Saringan Kesihatan Kendiri</h1>
        <p class="page-subtitle">Maklum balas borang saringan yang dihantar oleh pesakit secara dalam talian</p>
      </div>

      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr><th>#</th><th>Responden</th><th>Telefon</th><th>Kod Triaj</th><th>Tarikh Hantar</th></tr>
              </thead>
              <tbody>
                <?php if (empty($screenings)): ?>
                  <tr><td colspan="5" class="text-center text-muted py-4">Tiada respons saringan diterima setakat ini</td></tr>
                <?php else: ?>
                  <?php foreach ($screenings as $idx => $s): ?>
                    <tr>
                      <td><?= $idx + 1 ?></td>
                      <td class="fw-semibold"><?= htmlspecialchars($s['name'] ?? 'Pesakit') ?></td>
                      <td class="small"><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                      <td><span class="badge bg-info text-dark"><?= htmlspecialchars($s['triage_code'] ?? 'NORMAL') ?></span></td>
                      <td class="small text-muted"><?= !empty($s['created_at']) ? date('d/m/Y H:i', strtotime($s['created_at'])) : '—' ?></td>
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
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
</body>
</html>
