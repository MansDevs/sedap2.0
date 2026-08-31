<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
    switch ($role) {
        case 'admin': $redirect = 'admin/dashboard.php'; break;
        case 'doctor': $redirect = 'doctor/dashboard.php'; break;
        case 'volunteer': $redirect = 'pages/volunteer/dashboard.php'; break;
        case 'user':
        case 'patient': $redirect = 'pages/patient/dashboard.php'; break;
        default: $redirect = 'auth/login.php'; break;
    }
    header("Location: /sedap2.0/$redirect"); exit;
}
header('Location: /sedap2.0/auth/login.php'); exit;
