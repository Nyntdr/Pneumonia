<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_id'])) {
    $scan_id = $_POST['scan_id'];

    try {
        $stmt = $conn->prepare("SELECT image_path FROM scans WHERE id = ?");
        $stmt->execute([$scan_id]);
        $scan = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($scan) {
            $image_path = $scan['image_path'];
            $stmt = $conn->prepare("DELETE FROM scans WHERE id = ?");
            $stmt->execute([$scan_id]);

            if (file_exists($image_path)) {
                unlink($image_path);
            }
            header("Location: view_scans.php"); // Redirect to view scans page after deletion
            exit();
        } else {
            $error = "Scan not found.";
        }
    } catch(PDOException $e) {
        $error = "Error deleting scan: " . $e->getMessage();
    }
} else {
    $error = "Invalid request.";
}

if (isset($error)) {
    echo "<p style='color: red; text-align: center;'>$error</p>";
    echo "<a href='view_scans.php' style='display: block; text-align: center; margin-top: 10px;'>Back to View Scans</a>";
}
?>