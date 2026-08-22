<?php
/**
 * Database Connection Configuration
 * 
 * Connects to the local MySQL server via XAMPP.
 * All PHP files include this to get a shared $pdo instance.
 */

$host = 'localhost';
$dbname = 'sedap';       // The database name you created in phpMyAdmin
$username = 'root';       // Default XAMPP MySQL user
$password = '';           // Default XAMPP MySQL password (empty)
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
