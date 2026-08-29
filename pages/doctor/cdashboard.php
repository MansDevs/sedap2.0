<?php
session_start();
require_once '../config/db.php';
require_once '../shared/includes/lang.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['doctor', 'admin'])) {
    header('Location: ../auth/login.php'); exit;
}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Doktor');
$userRole = $_SESSION['user_role'] ?? 'doctor';
$_cuiTheme = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
$_ROOT     = '/sedap/sedap2.0';

// Live Triage Queue Counts
$waitCounts = ['red' => 0, 'yellow' => 0, 'green' => 0];
try {
    $stmt = $pdo->query("SELECT triage_level, COUNT(*) as cnt FROM triage_records WHERE status='waiting' GROUP BY triage_level");
    while ($row = $stmt->fetch()) {
        $lvl = strtolower($row['triage_level']);
        if (isset($waitCounts[$lvl])) {
            $waitCounts[$lvl] = (int)$row['cnt'];
        }
    }
} catch (Exception $e) {}

// Urgent Action Queue (Waiting patients, Critical first)
$urgentQueue = [];
try {
    $stmt = $pdo->query("SELECT tr.*, p.name AS patient_name, p.ic_number 
                         FROM triage_records tr 
                         LEFT JOIN patients p ON tr.patient_id = p.id 
                         WHERE tr.status = 'waiting' 
                         ORDER BY FIELD(tr.triage_level, 'red', 'yellow', 'green'), tr.triaged_at ASC 
                         LIMIT 10");
    $urgentQueue = $stmt->fetchAll();
} catch (Exception $e) {}

// Patient Count
$patientCount = 0;
try {
    $patientCount = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
} catch (Exception $e) {}

// Announcements Count
$annCount = 0;
try {
    $annCount = (int)$pdo->query("SELECT COUNT(*) FROM announcements WHERE status='published'")->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'ms' ?>" data-coreui-theme="<?= $_cuiTheme ?>">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('doc_dash_title', 'Dashboard Doktor') ?> — SeDaP</title>
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

      <!-- Page Header -->
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
          <h1 class="page-title">
            <span class="material-symbols-outlined" style="color:var(--cui-primary);">dashboard</span>
            <?= __('doc_dash_title', 'Dashboard Doktor') ?>
          </h1>
          <p class="page-subtitle"><?= __('doc_dash_welcome', 'Selamat bertugas') ?>, <strong><?= $userName ?></strong>. <?= __('doc_dash_subtitle', 'Pantau barisan giliran triaj & tindakan klinikal.') ?></p>
        </div>
        <div class="d-flex gap-2">
          <a href="triage_counter.php" class="btn btn-primary d-flex align-items-center gap-1 shadow-sm">
            <span class="material-symbols-outlined" style="font-size:18px;">add_circle</span>
            <?= __('doc_btn_new_triage', 'Daftar Triaj Baharu') ?>
          </a>
          <a href="livechat.php" class="btn btn-outline-primary d-flex align-items-center gap-1">
            <span class="material-symbols-outlined" style="font-size:18px;">chat</span>
            <?= __('nav_livechat', 'Live Chat') ?>
          </a>
        </div>
      </div>

      <!-- Live Triage Status Cards -->
      <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
          <div class="stat-card stat-red shadow-sm">
            <div>
              <div class="stat-value"><?= $waitCounts['red'] ?></div>
              <div class="stat-label"><?= __('doc_stat_red', 'Kritikal (Red) Menunggu') ?></div>
            </div>
            <span class="material-symbols-outlined stat-icon">emergency</span>
          </div>
        </div>

        <div class="col-sm-6 col-xl-3">
          <div class="stat-card stat-amber shadow-sm">
            <div>
              <div class="stat-value"><?= $waitCounts['yellow'] ?></div>
              <div class="stat-label"><?= __('doc_stat_yellow', 'Urgent (Yellow) Menunggu') ?></div>
            </div>
            <span class="material-symbols-outlined stat-icon">warning</span>
          </div>
        </div>

        <div class="col-sm-6 col-xl-3">
          <div class="stat-card stat-green shadow-sm">
            <div>
              <div class="stat-value"><?= $waitCounts['green'] ?></div>
              <div class="stat-label"><?= __('doc_stat_green', 'Standard (Green) Menunggu') ?></div>
            </div>
            <span class="material-symbols-outlined stat-icon">check_circle</span>
          </div>
        </div>

        <div class="col-sm-6 col-xl-3">
          <div class="stat-card stat-teal shadow-sm">
            <div>
              <div class="stat-value"><?= $patientCount ?></div>
              <div class="stat-label"><?= __('doc_stat_patients', 'Jumlah Pesakit Berdaftar') ?></div>
            </div>
            <span class="material-symbols-outlined stat-icon">groups</span>
          </div>
        </div>
      </div>

      <!-- Main Dashboard Content Grid -->
      <div class="row g-4">
        
        <!-- Left: Urgent Action Queue -->
        <div class="col-12 col-xl-8">
          <div class="card h-100 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
              <span class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined text-danger">schedule</span>
                <strong><?= __('doc_urgent_queue', 'Barisan Tindakan Segera (Urgent Action Queue)') ?></strong>
              </span>
              <a href="triage_list.php" class="btn btn-sm btn-outline-primary"><?= __('btn_view_all', 'Lihat Semua Triaj') ?> &rarr;</a>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-3"><?= __('col_patient_name', 'Nama Pesakit') ?></th>
                      <th><?= __('col_ic', 'No. IC / ID') ?></th>
                      <th><?= __('col_level', 'Tahap') ?></th>
                      <th><?= __('col_vitals', 'Tanda Vital') ?></th>
                      <th><?= __('col_complaint', 'Aduan') ?></th>
                      <th><?= __('col_time', 'Masa Triaj') ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($urgentQueue)): ?>
                      <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                          <span class="material-symbols-outlined d-block mb-1" style="font-size:36px;opacity:.3;">check_circle</span>
                          <?= __('doc_no_records', 'Tiada pesakit sedang menunggu dalam barisan triaj.') ?>
                        </td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($urgentQueue as $item):
                        $lvl = strtolower($item['triage_level'] ?? 'green');
                        $badgeMap = ['red' => 'badge-triage-red', 'yellow' => 'badge-triage-yellow', 'green' => 'badge-triage-green'];
                        $badgeClass = $badgeMap[$lvl] ?? 'badge-triage-green';
                        $lvlMap = [
                            'red' => __('triage_red', 'Merah (Kritikal)'),
                            'yellow' => __('triage_yellow', 'Kuning (Separa)'),
                            'green' => __('triage_green', 'Hijau (Biasa)')
                        ];
                        $lvlText = $lvlMap[$lvl] ?? __('triage_green', 'Hijau (Biasa)');
                      ?>
                      <tr>
                        <td class="ps-3 fw-semibold text-truncate" style="max-width:160px;">
                          <?= htmlspecialchars($item['patient_name'] ?? 'Pesakit #' . $item['patient_id']) ?>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($item['ic_number'] ?? '—') ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= $lvlText ?></span></td>
                        <td class="small">
                          <?php if (!empty($item['temperature'])): ?>
                            <span class="badge bg-light text-dark me-1"><?= htmlspecialchars($item['temperature']) ?>&deg;C</span>
                          <?php endif; ?>
                          <?php if (!empty($item['blood_pressure'])): ?>
                            <span class="badge bg-light text-dark"><?= htmlspecialchars($item['blood_pressure']) ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="small text-muted text-truncate" style="max-width:200px;">
                          <?= htmlspecialchars($item['chief_complaint'] ?? '—') ?>
                        </td>
                        <td class="small text-muted">
                          <?= !empty($item['triaged_at']) ? date('h:i A', strtotime($item['triaged_at'])) : '—' ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Admin Actions & Team Comms -->
        <div class="col-12 col-xl-4 d-flex flex-column gap-4">
          
          <!-- Actions Card -->
          <div class="card shadow-sm">
            <div class="card-header">
              <span class="material-symbols-outlined text-primary">bolt</span>
              <strong><?= __('btn_quick_actions', 'Tindakan Pantas') ?></strong>
            </div>
            <div class="card-body d-flex flex-column gap-2">
              <a href="triage_counter.php" class="btn btn-primary d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="font-size:18px;">person_add</span>
                <?= __('doc_btn_register_walkin', 'Daftar Pesakit / Walk-in') ?>
              </a>
              <a href="patientfamily.php" class="btn btn-outline-primary d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="font-size:18px;">groups</span>
                <?= __('doc_btn_check_family', 'Semak Maklumat Keluarga') ?>
              </a>
              <a href="announcements.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-between">
                <span class="d-flex align-items-center gap-2">
                  <span class="material-symbols-outlined" style="font-size:18px;">campaign</span>
                  <?= __('doc_btn_announcements', 'Pengumuman Komuniti') ?>
                </span>
                <span class="badge bg-secondary"><?= $annCount ?></span>
              </a>
            </div>
          </div>

          <!-- Team Comms Card -->
          <div class="card shadow-sm flex-grow-1">
            <div class="card-header d-flex justify-content-between align-items-center">
              <span class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined text-success">forum</span>
                <strong><?= __('doc_team_comms', 'Komunikasi Pasukan') ?></strong>
              </span>
              <a href="livechat.php" class="btn btn-sm btn-link p-0 text-decoration-none"><?= __('doc_open_chat', 'Buka Chat') ?></a>
            </div>
            <div class="card-body d-flex flex-column gap-2">
              <div class="p-2 rounded-3 bg-light border-start border-primary border-3 small">
                <div class="fw-semibold text-primary">Dr. Sarah (Klinik 1)</div>
                <div class="text-muted"><?= ($_SESSION['lang'] ?? 'ms') === 'en' ? 'Please review zone C patients needing additional ORS doses.' : 'Sila semak pesakit zon C yang memerlukan dos ORS tambahan.' ?></div>
              </div>
              <div class="p-2 rounded-3 bg-light border-start border-warning border-3 small">
                <div class="fw-semibold text-warning">MA John (Kaunter Triaj)</div>
                <div class="text-muted"><?= ($_SESSION['lang'] ?? 'ms') === 'en' ? 'Triage updated for 3 new admitted patients.' : 'Triaj telah dikemas kini untuk 3 pesakit baru.' ?></div>
              </div>
              <div class="p-2 rounded-3 bg-light border-start border-success border-3 small">
                <div class="fw-semibold text-success">Sukarelawan Komuniti</div>
                <div class="text-muted"><?= ($_SESSION['lang'] ?? 'ms') === 'en' ? 'Zone B clean water supplies have been distributed.' : 'Bekalan air bersih zon B telah diagihkan.' ?></div>
              </div>
              <a href="livechat.php" class="btn btn-sm btn-outline-primary mt-auto d-flex align-items-center justify-content-center gap-1">
                <span class="material-symbols-outlined" style="font-size:16px;">send</span>
                <?= __('doc_send_message', 'Hantar Mesej Pasukan') ?>
              </a>
            </div>
          </div>

        </div>

      </div>

    </main>
  </div>

  <!-- Footer -->
  <?php include '../shared/includes/footer.php'; ?>

</div>

<!-- CoreUI Bundle + SeDaP JS -->
<script src="<?= $_ROOT ?>/assets/js/coreui.bundle.min.js?v=2.2"></script>
<script src="<?= $_ROOT ?>/assets/js/sedap-app.js?v=<?= time() ?>"></script>
</body>
</html>
