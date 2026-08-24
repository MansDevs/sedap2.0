<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php'); exit;
}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');

// ── Stats ──
$patientCount      = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$personnelCount    = $pdo->query("SELECT COUNT(*) FROM personnel")->fetchColumn();
$annCount          = $pdo->query("SELECT COUNT(*) FROM announcements WHERE status='published'")->fetchColumn();
$triageTodayRows   = $pdo->query("SELECT triage_level, COUNT(*) cnt FROM triage_records WHERE DATE(triaged_at)=CURDATE() GROUP BY triage_level")->fetchAll(PDO::FETCH_KEY_PAIR);
$triageRed         = $triageTodayRows['red']    ?? 0;
$triageYellow      = $triageTodayRows['yellow'] ?? 0;
$triageGreen       = $triageTodayRows['green']  ?? 0;

// ── Recent triage (last 10) ──
$recentTriage = $pdo->query(
    "SELECT tr.*, p.full_name AS patient_name, u.name AS staff_name
     FROM triage_records tr
     JOIN patients p ON tr.patient_id = p.id
     JOIN users u ON tr.triaged_by = u.id
     ORDER BY tr.triaged_at DESC LIMIT 10"
)->fetchAll();

// ── Recent announcements ──
$recentAnn = $pdo->query(
    "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard — SeDaP</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script>
    tailwind.config = {
      darkMode:'class',
      theme:{extend:{
        colors:{'primary':'#0058bd','primary-dark':'#004494','primary-light':'#2771df','primary-container':'#2771df','secondary':'#3d6185','tertiary':'#006673','surface':'#f7f9fb','surface-container':'#eceef0','surface-container-low':'#f2f4f6','surface-container-lowest':'#ffffff','on-primary':'#ffffff','on-surface':'#191c1e','on-surface-muted':'#424753','outline':'#727785','triage-red':'#ba1a1a','triage-yellow':'#d4a017','triage-green':'#1e8449'},
        fontFamily:{sans:['Roboto Flex','sans-serif']},
        borderRadius:{'DEFAULT':'1rem','lg':'2rem','xl':'3rem','full':'9999px'}
      }}
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../shared/css/sedap.css">
  <link rel="stylesheet" href="css/dashboard.css">
</head>
<body class="font-sans bg-surface text-on-surface">
<div class="sedap-layout">
  <?php include '../shared/includes/sidebar_admin.php'; ?>
  <div class="sedap-main">
    <?php include '../shared/includes/header.php'; ?>
    <div class="sedap-content">

      <!-- Page Header -->
      <div class="flex-between mb-6">
        <div>
          <h1 class="page-title">Admin Dashboard</h1>
          <p class="page-subtitle">Overview for <?php echo date('l, d F Y'); ?></p>
        </div>
        <div class="flex gap-3 flex-wrap">
          <a href="triage/add.php" class="btn btn-primary">
            <span class="material-symbols-outlined">add_circle</span> New Triage
          </a>
          <a href="patients/index.php" class="btn btn-outline">
            <span class="material-symbols-outlined">person_add</span> Register Patient
          </a>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="grid-stats">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(8,115,131,0.1)">
            <span class="material-symbols-outlined" style="color:#0058bd">person</span>
          </div>
          <div class="stat-value"><?php echo number_format((int)$patientCount); ?></div>
          <div class="stat-label">Total Patients</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(192,57,43,0.1)">
            <span class="material-symbols-outlined" style="color:#C0392B">emergency</span>
          </div>
          <div class="stat-value" style="color:#C0392B"><?php echo $triageRed; ?></div>
          <div class="stat-label">Critical (Red) Today</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(212,160,23,0.1)">
            <span class="material-symbols-outlined" style="color:#D4A017">warning</span>
          </div>
          <div class="stat-value" style="color:#D4A017"><?php echo $triageYellow; ?></div>
          <div class="stat-label">Urgent (Yellow) Today</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(30,132,73,0.1)">
            <span class="material-symbols-outlined" style="color:#1E8449">check_circle</span>
          </div>
          <div class="stat-value" style="color:#1E8449"><?php echo $triageGreen; ?></div>
          <div class="stat-label">Standard (Green) Today</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(8,115,131,0.1)">
            <span class="material-symbols-outlined" style="color:#0058bd">campaign</span>
          </div>
          <div class="stat-value"><?php echo (int)$annCount; ?></div>
          <div class="stat-label">Active Announcements</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(8,115,131,0.1)">
            <span class="material-symbols-outlined" style="color:#0058bd">badge</span>
          </div>
          <div class="stat-value"><?php echo (int)$personnelCount; ?></div>
          <div class="stat-label">Total Personnel</div>
        </div>
      </div>

      <!-- Two-column layout -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Recent Triage (spans 2 cols) -->
        <div class="xl:col-span-2 card">
          <div class="card-header">
            <h3><span class="material-symbols-outlined">emergency</span> Recent Triage Records</h3>
            <a href="triage/index.php" class="btn btn-sm btn-outline">View All</a>
          </div>
          <div class="table-wrap">
            <table class="sedap-table">
              <thead>
                <tr>
                  <th>Patient</th>
                  <th>Level</th>
                  <th>Complaint</th>
                  <th>Temp</th>
                  <th>Time</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentTriage)): ?>
                <tr><td colspan="6" class="text-center py-8" style="color:var(--on-muted)">No triage records today</td></tr>
                <?php else: foreach ($recentTriage as $t): ?>
                <tr class="triage-<?php echo htmlspecialchars($t['triage_level']); ?>">
                  <td>
                    <div style="font-weight:600"><?php echo htmlspecialchars($t['patient_name']); ?></div>
                    <div style="font-size:0.75rem;color:var(--on-muted)">by <?php echo htmlspecialchars($t['staff_name']); ?></div>
                  </td>
                  <td>
                    <?php
                      $lvl = $t['triage_level'];
                      $cls = ['red'=>'badge-red','yellow'=>'badge-yellow','green'=>'badge-green'][$lvl] ?? 'badge-muted';
                      echo "<span class='badge $cls'>".strtoupper($lvl)."</span>";
                    ?>
                  </td>
                  <td><?php echo htmlspecialchars(substr($t['chief_complaint'] ?? '—', 0, 35)); ?></td>
                  <td><?php echo htmlspecialchars($t['temperature'] ?? '—'); ?>°C</td>
                  <td style="white-space:nowrap;font-size:0.8rem"><?php echo date('H:i d/m', strtotime($t['triaged_at'])); ?></td>
                  <td><span class="badge badge-muted"><?php echo htmlspecialchars($t['status']); ?></span></td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Right column -->
        <div class="flex flex-col gap-6">

          <!-- Quick Actions -->
          <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined">bolt</span> Quick Actions</h3></div>
            <div class="card-body flex flex-col gap-3">
              <a href="triage/add.php"          class="btn btn-primary w-full"><span class="material-symbols-outlined">emergency</span>New Triage Entry</a>
              <a href="announcements/index.php"  class="btn btn-outline w-full"><span class="material-symbols-outlined">campaign</span>New Announcement</a>
              <a href="patients/index.php"       class="btn btn-surface w-full"><span class="material-symbols-outlined">person</span>Register Patient</a>
              <a href="personnel/index.php"      class="btn btn-surface w-full"><span class="material-symbols-outlined">badge</span>Add Staff/Volunteer</a>
              <a href="posters/index.php"        class="btn btn-surface w-full"><span class="material-symbols-outlined">image</span>Create Poster</a>
            </div>
          </div>

          <!-- Recent Announcements -->
          <div class="card">
            <div class="card-header">
              <h3><span class="material-symbols-outlined">campaign</span> Announcements</h3>
              <a href="announcements/index.php" class="btn btn-sm btn-outline">Manage</a>
            </div>
            <div style="padding:0">
              <?php if (empty($recentAnn)): ?>
              <p style="text-align:center;padding:1.5rem;color:var(--on-muted);font-size:0.875rem">No announcements yet</p>
              <?php else: foreach ($recentAnn as $a): ?>
              <div style="padding:0.85rem 1.25rem;border-bottom:1px solid var(--outline)">
                <div class="flex-between">
                  <span style="font-weight:600;font-size:0.875rem"><?php echo htmlspecialchars($a['title']); ?></span>
                  <?php $sc = $a['status']==='published'?'badge-green':'badge-muted'; ?>
                  <span class="badge <?php echo $sc; ?>"><?php echo $a['status']; ?></span>
                </div>
                <p style="font-size:0.78rem;color:var(--on-muted);margin-top:0.25rem"><?php echo htmlspecialchars(substr($a['content'],0,75)).'…'; ?></p>
              </div>
              <?php endforeach; endif; ?>
            </div>
          </div>

        </div><!-- end right col -->
      </div><!-- end grid -->
    </div>
  </div>
</div>
<script src="js/dashboard.js"></script>
</body>
</html>
