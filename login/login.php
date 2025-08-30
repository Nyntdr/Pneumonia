<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/admin_dashboard.php");
    } else {
        header("Location: ../user/user_dashboard.php");
    }
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

// Handle form submission
$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = trim($_POST['username']);
    $input_password = $_POST['password'];

    if (empty($input_username) || empty($input_password)) {
        $errors[] = "Username and password are required.";
    } else {
        // Prepare and execute query
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$input_username]);
        $user = $stmt->fetch();

        if ($user && password_verify($input_password, $user['password'])) {
            // Successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../admin/admin_dashboard.php");
            } else {
                header("Location: ../user/user_dashboard.php");
            }
            exit();
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pneumonia Detection System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url('../images/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative; /* For positioning stacked images */
        }
        .stacked-image {
            position: absolute;
            z-index: 1; /* Below form but above background */
            opacity: 0.8; /* Slight transparency for blending */
        }
        .stacked-image-1 {
            top: 20px;
            left: 20px;
            width: 550px; /* Adjust size as needed */
            height: auto;
        }
        .stacked-image-2 {
            bottom: 20px;
            right: 20px;
            width: 550px; /* Adjust size as needed */
            height: auto;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.9); /* Semi-transparent white for readability */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 300px;
            z-index: 2; /* Above background and stacked images */
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            background-color: rgba(255, 255, 255, 0.95); /* Slightly more opaque for inputs */
        }
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            font-size: 14px;
            text-align: center;
        }
        .register-link {
            text-align: center;
            margin-top: 10px;
        }
        .register-link a {
            color: #007bff;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- <img src="../images/pneu.png" alt="Pneumonia Icon 1" class="stacked-image stacked-image-1">
    <img src="../images/xxray.jpeg" alt="Pneumonia Icon 2" class="stacked-image stacked-image-2"> -->
    <div class="login-container">
        <h2>Pneumonia Detection System</h2>
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <input type="submit" value="Login">
        </form>
        <div class="register-link">
            <p>New here? <a href="register.php">Register</a></p>
        </div>
    </div>
</body>
</html>