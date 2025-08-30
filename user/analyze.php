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

// Get image name from query parameter
$image_name = isset($_GET['image']) ? basename($_GET['image']) : '';
$scan = null;

if ($image_name) {
    $stmt = $conn->prepare("SELECT id, image_path, upload_date, outcome, Confidence FROM scans WHERE user_id = ? AND image_path LIKE ? ORDER BY upload_date DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], "%" . $image_name]);
    $scan = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$scan) {
    die("Scan not found or access denied.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scan Analysis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style/common.css">
    <style>
        .analysis-container {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            text-align: center;
        }
        .analysis-image {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
            border: 1px solid #eee;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        .analysis-details {
            font-size: 1.1rem;
            color: var(--dark-color);
        }
        .analysis-details p {
            margin: 10px 0;
        }
        .outcome {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        .outcome.normal {
            background-color: #28a745;
            color: white;
        }
        .outcome.pneumonia {
            background-color: #dc3545;
            color: white;
        }
        .back-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: var(--transition);
            display: inline-block;
            margin-top: 20px;
        }
        .back-btn:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="brand">Pneumonia Detection System</div>
        <div>
            <span class="welcome-msg">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../login/logout.php" onclick="return confirmLogout()">Logout</a>
        </div>
    </div>

    <div class="analysis-container">
        <h2>Scan Analysis</h2>
        <img src="<?php echo htmlspecialchars($scan['image_path']); ?>" alt="Scan Image" class="analysis-image">
        <div class="analysis-details">
            <p><strong>Upload Date:</strong> <?php echo htmlspecialchars(date("M d, Y H:i", strtotime($scan['upload_date']))); ?></p>
            <p><strong>Prediction:</strong> <span class="outcome <?php echo strtolower(htmlspecialchars($scan['outcome'] ?? 'unknown')); ?>"><?php echo htmlspecialchars($scan['outcome'] ?? 'Unknown'); ?></span></p>
            <p><strong>Confidence:</strong> <?php echo htmlspecialchars($scan['Confidence'] ?? 'Unknown'); ?>%</p>
        </div>
        <a href="user_dashboard.php" class="back-btn">Back to Dashboard</a>
    </div>

    <script>
        function confirmLogout() {
            return confirm("Are you sure you want to logout?");
        }
    </script>
</body>
</html>