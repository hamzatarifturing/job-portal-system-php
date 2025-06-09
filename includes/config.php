<?php
/**
 * Database Configuration File
 * Contains database connection parameters
 * Compatible with PHP 5
 */

// Define database parameters
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'job_portal');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false) {
    die("ERROR: Could not connect to the database. " . mysqli_connect_error());
}

// Set charset to ensure proper encoding
mysqli_set_charset($conn, "utf8");

// Uncomment to display error messages (for development environment only)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
?>