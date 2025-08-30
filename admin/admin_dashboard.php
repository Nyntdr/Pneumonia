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

// Fetch user statistics
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_users, 
                            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                            SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count 
                            FROM users");
    $stmt->execute();
    $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error fetching user statistics: " . $e->getMessage();
}

// Fetch scan statistics
try {
    // Total scans
    $stmt = $conn->prepare("SELECT COUNT(*) as total_scans FROM scans");
    $stmt->execute();
    $scan_stats['total_scans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_scans'];

    // Pneumonia scans
    $stmt = $conn->prepare("SELECT COUNT(*) as pneumonia_scans FROM scans WHERE outcome = 'Pneumonia'");
    $stmt->execute();
    $scan_stats['pneumonia_scans'] = $stmt->fetch(PDO::FETCH_ASSOC)['pneumonia_scans'];

    // Normal scans
    $stmt = $conn->prepare("SELECT COUNT(*) as normal_scans FROM scans WHERE outcome = 'Normal'");
    $stmt->execute();
    $scan_stats['normal_scans'] = $stmt->fetch(PDO::FETCH_ASSOC)['normal_scans'];

    // Pneumonia percentage
    $scan_stats['pneumonia_percentage'] = $scan_stats['total_scans'] > 0 
        ? number_format(($scan_stats['pneumonia_scans'] / $scan_stats['total_scans']) * 100, 2) 
        : 0;
} catch(PDOException $e) {
    $error = isset($error) ? $error . "<br>Error fetching scan statistics: " . $e->getMessage() : "Error fetching scan statistics: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../style/common.css">
    <style>
        .error-message { color: red; font-size: 0.9em; margin-bottom: 10px; }
        .stats-container { margin-top: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stat-card { background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-value { font-size: 2em; color: #007bff; font-weight: bold; }
        .stat-label { font-size: 1em; color: #555; margin-top: 10px; }
        .action-buttons { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; max-width: 100%; }
        .action-button { text-align: center; text-decoration: none; color: #333; background: #f9f9f9; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.2s; flex: 1 1 200px; max-width: 250px; }
        .action-button:hover { transform: scale(1.05); }
        .action-button img { width: 50px; height: 50px; margin-bottom: 10px; }
        .logout-btn { text-decoration: none; color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="brand">Pneumonia Detection System</div>
        <div class="nav-links">
            <span class="welcome-msg">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../login/logout.php" class="logout-btn" onclick="return confirmLogout()">Logout</a>
        </div>
    </div>
    <div class="dashboard-container">
        <div class="main-content">
            <h1>Admin Dashboard</h1>
            <div class="action-buttons">
                <a href="add_user.php" class="action-button">
                    <img src="../icons/adduser.png" alt="Add User">
                    <span>Add New User</span>
                </a>
                <a href="view_users.php" class="action-button">
                    <img src="../icons/viewuser.png" alt="View Users">
                    <span>View Users</span>
                </a>
                <a href="edit_user.php" class="action-button">
                    <img src="../icons/edituser.png" alt="Edit User">
                    <span>Edit User</span>
                </a>
                <a href="view_scans.php" class="action-button">
                    <img src="../icons/scan.png" alt="View Scans"> 
                    <span>View Scans</span>
                </a>
            </div>
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <div class="stats-container">
                <h2>User Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo isset($user_stats['total_users']) ? htmlspecialchars($user_stats['total_users']) : '0'; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo isset($user_stats['admin_count']) ? htmlspecialchars($user_stats['admin_count']) : '0'; ?></div>
                        <div class="stat-label">Administrators</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo isset($user_stats['user_count']) ? htmlspecialchars($user_stats['user_count']) : '0'; ?></div>
                        <div class="stat-label">Regular Users</div>
                    </div>
                </div>
            </div>
            <div class="stats-container">
                <h2>Scan Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo isset($scan_stats['total_scans']) ? htmlspecialchars($scan_stats['total_scans']) : '0'; ?></div>
                        <div class="stat-label">Total Scans</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo isset($scan_stats['pneumonia_scans']) ? htmlspecialchars($scan_stats['pneumonia_scans']) : '0'; ?></div>
                        <div class="stat-label">Pneumonia Scans</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo isset($scan_stats['normal_scans']) ? htmlspecialchars($scan_stats['normal_scans']) : '0'; ?></div>
                        <div class="stat-label">Normal Scans</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo isset($scan_stats['pneumonia_percentage']) ? htmlspecialchars($scan_stats['pneumonia_percentage']) . '%' : '0%'; ?></div>
                        <div class="stat-label">Pneumonia Percentage</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function confirmLogout() {
            return confirm("Are you sure you want to logout?");
        }
    </script>
    <script src="js/admin_dashboard.js"></script>
</body>
</html>