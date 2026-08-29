<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? '';
    switch ($role) {
        case 'admin': $redirect = 'pages/admin/dashboard.php'; break;
        case 'doctor': $redirect = 'pages/doctor/cdashboard.php'; break;
        case 'volunteer': $redirect = 'pages/volunteer/dashboard.php'; break;
        case 'user': $redirect = 'pages/patient/dashboard.php'; break;
        default: $redirect = 'pages/auth/login.php'; break;
    }
    header("Location: /sedap/sedap2.0/$redirect"); exit;
}
header('Location: /sedap/sedap2.0/pages/auth/login.php'); exit;
