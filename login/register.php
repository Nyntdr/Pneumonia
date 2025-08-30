<?php
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
$success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_username = trim($_POST['username']);
    $input_password = $_POST['password'];
    $input_confirm_password = $_POST['confirm_password'];
    $input_role = $_POST['role'] ?? 'user';

    // Username validations
    if (empty($input_username)) {
        $errors[] = "Username is required.";
    } else {
        if (strlen($input_username) < 5 || strlen($input_username) > 8) {
            $errors[] = "Username must be 5-8 characters long.";
        }
        if (!preg_match('/[A-Z]/', $input_username)) {
            $errors[] = "Username must contain at least one uppercase letter.";
        }
        if (!preg_match('/\d/', $input_username)) {
            $errors[] = "Username must contain at least one number.";
        }
        if (!preg_match('/^[A-Za-z\d]+$/', $input_username)) {
            $errors[] = "Username must not contain special symbols.";
        }
    }

    // Password validations
    if (empty($input_password)) {
        $errors[] = "Password is required.";
    } else {
        if (strlen($input_password) !== 8) {
            $errors[] = "Password must be exactly 8 characters long.";
        }
        if (!preg_match('/[A-Z]/', $input_password)) {
            $errors[] = "Password must contain at least one uppercase letter.";
        }
        if (!preg_match('/\d/', $input_password)) {
            $errors[] = "Password must contain at least one number.";
        }
        if (!preg_match('/[!@#$%^&*]/', $input_password)) {
            $errors[] = "Password must contain at least one special symbol (!@#$%^&*).";
        }
    }

    // Confirm password validation
    if (empty($input_confirm_password)) {
        $errors[] = "Confirm password is required.";
    } elseif ($input_password !== $input_confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Proceed only if no errors
    if (empty($errors)) {
        try {
            // Begin transaction
            $conn->beginTransaction();

            // Check if username exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$input_username]);
            if ($stmt->fetch()) {
                $errors[] = "Username already exists.";
            } else {
                // Hash password and insert user
                $hashed_password = password_hash($input_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt->execute([$input_username, $hashed_password, $input_role]);
                // Commit transaction
                $conn->commit();
                $success = "Registration successful! <br> You can now <a href='login.php'>log in</a>.";
            }
        } catch(PDOException $e) {
            // Roll back transaction on error
            $conn->rollBack();
            $errors[] = "Error registering user: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDS Register</title>
    <link rel="stylesheet" href="../style/register.css">
    <style>
        .error-message { color: red; font-size: 0.9em; margin-top: 5px; display: none; }
        .error-message.active { display: block; }
        .error { border: 1px solid red; padding: 10px; margin-bottom: 10px; background-color: #ffe6e6; border-radius: 5px; }
        .success { border: 1px solid #28a745; padding: 10px; margin-bottom: 10px; background-color: #d4edda; color: #155724; border-radius: 5px; text-align: center; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        .show-password-container { display: flex; align-items: center; margin-top: 10px; }
        .show-password-container input[type="checkbox"] { order: 1; margin-right: 5px; }
        .show-password-container label { order: 2; }
    </style>
</head>
<body>
    <img src="../images/pneu.png" alt="Pneumonia Icon 1" class="stacked-image stacked-image-1">
    <img src="../images/xxray.jpeg" alt="Pneumonia Icon 2" class="stacked-image stacked-image-2">
    <div class="register-container">
        <h2>Pneumonia Detection System</h2>
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success">
                <p><?php echo $success; ?></p>
            </div>
        <?php else: ?>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($input_username) ? htmlspecialchars($input_username) : ''; ?>" required oninput="validateUsername(this)">
                    <div id="usernameErrors" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required oninput="validatePassword(this)">
                    <div id="passwordErrors" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required oninput="validateConfirmPassword(this)">
                    <div id="confirmPasswordErrors" class="error-message"></div>
                </div>
                <div class="show-password-container">
                    <input type="checkbox" id="showPassword" onclick="togglePassword()">
                    <label for="showPassword">Show Password</label>
                </div>
                <input type="submit" value="Register">
            </form>
            <div class="login-link">
                <p>Already registered? <a href='login.php'>Login</a></p>
            </div>
        <?php endif; ?>
    </div>
    <script>
        // Track if fields have been touched
        let touched = {
            any: false,
            username: false,
            password: false,
            confirm_password: false
        };

        function togglePassword() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const isChecked = document.getElementById('showPassword').checked;
            password.type = isChecked ? 'text' : 'password';
            confirmPassword.type = isChecked ? 'text' : 'password';
        }

        function showErrors(id, errors) {
            const errorDiv = document.getElementById(id);
            if (touched.any && errors.length > 0) {
                errorDiv.classList.add('active');
                errorDiv.innerHTML = errors.join('<br>');
            } else {
                errorDiv.classList.remove('active');
                errorDiv.innerHTML = '';
            }
        }

        function validateUsername(input) {
            touched.any = true;
            touched.username = true;
            const username = input.value;
            const errors = [];
            if (username && touched.username) {
                if (username.length < 5 || username.length > 8) errors.push("Username must be 5-8 characters long.");
                if (!/[A-Z]/.test(username)) errors.push("Username must contain at least one uppercase letter.");
                if (!/\d/.test(username)) errors.push("Username must contain at least one number.");
                if (!/^[A-Za-z\d]+$/.test(username)) errors.push("Username must not contain special symbols.");
            }
            showErrors('usernameErrors', errors);
            return errors.length === 0;
        }

        function validatePassword(input) {
            touched.any = true;
            touched.password = true;
            const password = input.value;
            const errors = [];
            if (password && touched.password) {
                if (password.length !== 8) errors.push("Password must be exactly 8 characters long.");
                if (!/[A-Z]/.test(password)) errors.push("Password must contain at least one uppercase letter.");
                if (!/\d/.test(password)) errors.push("Password must contain at least one number.");
                if (!/[!@#$%^&*]/.test(password)) errors.push("Password must contain at least one special symbol (!@#$%^&*).");
            }
            showErrors('passwordErrors', errors);
            validateConfirmPassword(document.getElementById('confirm_password')); // Re-validate confirm password
            return errors.length === 0;
        }

        function validateConfirmPassword(input) {
            touched.any = true;
            touched.confirm_password = true;
            const password = document.getElementById('password').value;
            const confirmPassword = input.value;
            const errors = [];
            if (touched.confirm_password && password && !confirmPassword) errors.push("Confirm password is required when password is provided.");
            else if (password !== confirmPassword && touched.confirm_password) errors.push("Passwords do not match.");
            showErrors('confirmPasswordErrors', errors);
            return errors.length === 0;
        }

        function validateForm() {
            touched.any = true;
            touched.username = true;
            touched.password = true;
            touched.confirm_password = true;
            const isUsernameValid = validateUsername(document.getElementById('username'));
            const isPasswordValid = validatePassword(document.getElementById('password'));
            const isConfirmPasswordValid = validateConfirmPassword(document.getElementById('confirm_password'));
            return isUsernameValid && isPasswordValid && isConfirmPasswordValid;
        }
    </script>
</body>
</html>