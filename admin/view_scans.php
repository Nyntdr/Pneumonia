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

// Fetch all scans with username
$stmt = $conn->prepare("SELECT s.id, s.image_path, s.upload_date, s.outcome, u.username 
                        FROM scans s 
                        LEFT JOIN users u ON s.user_id = u.id 
                        ORDER BY s.upload_date DESC");
$stmt->execute();
$scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Scans</title>
    <link rel="stylesheet" href="../style/common.css">
    <style>
        .scans-container {
            background-color:white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 1400px;
            margin: 20px auto;
        }
        .scans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .scan-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 15px;
            text-align: center;
            display: flex;
            flex-direction: column;
            height: 100%; /* Ensure full height usage */
        }
        .scan-card img {
            max-height: 200px; /* Limit image height to prevent overwhelming text */
            width: 100%;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .scan-details {
            flex-grow: 1; /* Allow text section to grow and fill space */
            font-size: 0.9rem;
            color: var(--dark-color);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .scan-details p {
            margin: 5px 0; /* Uniform spacing between text lines */
            line-height: 1.4; /* Consistent text line spacing */
        }
        .scan-details .outcome {
            font-weight: bold;
        }
        .scan-details .outcome.pneumonia {
            color: #dc3545; /* Red for Pneumonia */
        }
        .scan-details .outcome.normal {
            color: #28a745; /* Green for Normal */
        }
        .delete-form {
            margin-top: 10px;
        }
        .delete-form button {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 0.9rem;
            transition: var(--transition);
        }
        .delete-form button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="brand">Pneumonia Detection System</div>
        <div class="nav-links">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="../login/logout.php" class="logout-btn" onclick="return confirmLogout()">Logout</a>
        </div>
    </div>
    <div class="scans-container">
        <h2>View Scans</h2>
        <div class="scans-grid">
            <?php foreach ($scans as $scan): ?>
                <div class="scan-card">
                    <img src="<?php echo htmlspecialchars($scan['image_path']); ?>" alt="Scan Image">
                    <div class="scan-details">
                        <p><strong>Date:</strong> <?php echo htmlspecialchars(date("M d, Y H:i", strtotime($scan['upload_date']))); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($scan['username'] ?? 'Unknown'); ?></p>
                        <p><strong>Outcome:</strong> <span class="outcome <?php echo strtolower(htmlspecialchars($scan['outcome'] ?? 'pending')); ?>"><?php echo htmlspecialchars($scan['outcome'] ?? 'Pending'); ?></span></p>
                    </div>
                    <form class="delete-form" method="post" action="delete_scan.php" onsubmit="return confirmDelete()">
                        <input type="hidden" name="scan_id" value="<?php echo htmlspecialchars($scan['id']); ?>">
                        <button type="submit">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <?php if (empty($scans)): ?>
                <div class="empty-history">
                    <p>No scans available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function confirmLogout() {
            return confirm("Are you sure you want to logout?");
        }
        function confirmDelete() {
            return confirm("Are you sure you want to delete this scan?");
        }
    </script>
</body>
</html>