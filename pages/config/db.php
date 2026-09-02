<?php
/**
 * Database Connection Configuration
 * 
 * Connects to the local MySQL server via XAMPP.
 * All PHP files include this to get a shared $pdo instance.
 */

if (!function_exists('sedap_root')) {
    function sedap_root(): string {
        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
            $pos = strpos($script, '/pages/');
            if ($pos !== false) {
                return substr($script, 0, $pos);
            }
            $dir = dirname($script);
            return ($dir === '/' || $dir === '\\') ? '' : str_replace('\\', '/', $dir);
        }
        return '/sedap/sedap2.0';
    }
}
if (!isset($_ROOT)) {
    $_ROOT = sedap_root();
}

$host = 'localhost';
$dbname = 'sedap';       // The database name created in phpMyAdmin
$username = 'sedap';        // Default XAMPP MySQL user
$password = 'sedapupnm';            // XAMPP MySQL password (empty)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Return associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                    // Use real prepared statements
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // In production, log this instead of displaying it
    die("Database connection failed: " . $e->getMessage());
}
