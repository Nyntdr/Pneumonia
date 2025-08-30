<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pneumonia";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insert admin
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(["admin", $admin_password, "admin"]);
    
    // Insert user
    $user_password = password_hash("user123", PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(["user", $user_password, "user"]);
    
    echo "Users created successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>