<?php
// Database configuration
$host = 'localhost'; 
$dbname = 'pneumonia'; 
$username = 'root'; 
$password = ''; 

try {
    $conn = new mysqli($host, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // Log error (in production, consider logging to a file instead of displaying)
    error_log($e->getMessage());
    die("Database connection error. Please try again later.");
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
?>