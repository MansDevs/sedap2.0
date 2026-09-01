<?php
session_start();
$dir = !empty($_SERVER['SCRIPT_NAME']) ? dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'])) : '';
$root = ($dir === '/' || $dir === '\\' || $dir === '.') ? '' : $dir;

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
    switch ($role) {
        case 'admin': $redirect = 'pages/admin/dashboard.php'; break;
        case 'doctor': $redirect = 'pages/doctor/dashboard.php'; break;
        case 'volunteer': $redirect = 'pages/volunteer/dashboard.php'; break;
        case 'user':
        case 'patient': $redirect = 'pages/dashboard/dashboard.php'; break;
        default: $redirect = 'pages/auth/login.php'; break;
    }
    header("Location: $root/$redirect"); exit;
}
header("Location: $root/pages/auth/login.php"); exit;
