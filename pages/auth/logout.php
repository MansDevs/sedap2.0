<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session completely
session_destroy();

// Redirect back to the login page (adjust the path if necessary)
header("Location: login.php");
exit();
?>