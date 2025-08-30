<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login/login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pneumonia";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['image_name']) && isset($_POST['scan_id'])) {
    $user_id = $_SESSION['user_id'];
    $image_name = basename($_POST['image_name']);
    $scan_id = $_POST['scan_id'];
    $filePath = 'Uploads/' . $image_name;

    // Verify the scan belongs to the user
    $stmt = $conn->prepare("SELECT id FROM scans WHERE id = ? AND user_id = ?");
    $stmt->execute([$scan_id, $user_id]);
    if ($stmt->fetch()) {
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM scans WHERE id = ?");
        $stmt->execute([$scan_id]);

        // Delete file
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        header("Location: user_dashboard.php?success=Image deleted successfully");
        exit();
    } else {
        header("Location: user_dashboard.php?error=Unauthorized or invalid scan");
        exit();
    }
} else {
    header("Location: user_dashboard.php?error=Invalid request");
    exit();
}
?>