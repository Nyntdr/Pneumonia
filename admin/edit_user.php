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

// Initialize variables
$errors = [];
$success = "";
$selected_user = null;

// Fetch users for table
try {
    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id != ?");
    $stmt->execute([$_SESSION['user_id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $errors[] = "Error fetching users: " . $e->getMessage();
}

// Handle form submission (edit or delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['edit_user_id']) && !isset($_POST['update_user']) && !isset($_POST['delete_user'])) {
        // Load selected user's details for editing
        $edit_user_id = $_POST['edit_user_id'];
        try {
            $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
            $stmt->execute([$edit_user_id]);
            $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$selected_user) {
                $errors[] = "Selected user not found.";
            }
        } catch(PDOException $e) {
            $errors[] = "Error fetching user details: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_user'])) {
        // Handle edit user
        $edit_user_id = $_POST['edit_user_id'];
        $input_username = trim($_POST['username']);
        $input_role = $_POST['role'];
        $input_password = $_POST['password'];
        $input_confirm_password = $_POST['confirm_password'];

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

        // Password validations (only if provided)
        if (!empty($input_password)) {
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
            if ($input_password !== $input_confirm_password) {
                $errors[] = "Passwords do not match.";
            }
        }

        // Role validation
        if (empty($input_role) || !in_array($input_role, ['admin', 'user'])) {
            $errors[] = "Invalid role selected.";
        }

        // Proceed only if no errors
        if (empty($errors)) {
            try {
                // Begin transaction
                $conn->beginTransaction();

                // Check if username exists for another user
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$input_username, $edit_user_id]);
                if ($stmt->fetch()) {
                    $errors[] = "Username already exists.";
                } else {
                    // Update user
                    if (!empty($input_password)) {
                        // Update with new password
                        $hashed_password = password_hash($input_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?");
                        $stmt->execute([$input_username, $input_role, $hashed_password, $edit_user_id]);
                    } else {
                        // Update without changing password
                        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                        $stmt->execute([$input_username, $input_role, $edit_user_id]);
                    }
                    // Commit transaction
                    $conn->commit();
                    $success = "User updated successfully!";
                    // Refresh selected user details
                    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
                    $stmt->execute([$edit_user_id]);
                    $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
                    // Refresh users list
                    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id != ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch(PDOException $e) {
                // Roll back transaction on error
                $conn->rollBack();
                $errors[] = "Error updating user: " . $e->getMessage();
            }
        }
        // Keep selected_user if there are errors
        if (!empty($errors)) {
            $selected_user = [
                'id' => $edit_user_id,
                'username' => $input_username,
                'role' => $input_role
            ];
        }
    } elseif (isset($_POST['delete_user'])) {
        // Handle delete user
        $delete_user_id = $_POST['delete_user_id'];
        if ($delete_user_id == $_SESSION['user_id']) {
            $errors[] = "You cannot delete your own account.";
        } else {
            try {
                // Begin transaction
                $conn->beginTransaction();
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$delete_user_id]);
                // Commit transaction
                $conn->commit();
                $success = "User deleted successfully!";
                $selected_user = null;
                // Refresh users list
                $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id != ?");
                $stmt->execute([$_SESSION['user_id']]);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                // Roll back transaction on error
                $conn->rollBack();
                $errors[] = "Error deleting user: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Pneumonia Detection System</title>
    <link rel="stylesheet" href="../style/common.css">
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
    <div class="navbar">
        <div class="brand">Pneumonia Detection System</div>
        <div class="nav-links">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="../login/logout.php" onclick="return confirmLogout()">Logout</a>
        </div>
    </div>
    <div class="dashboard-container">
        <div class="table-container">
            <h2>Edit Users</h2>
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="success">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>
            <?php if (empty($users)): ?>
                <div class="no-users">
                    <p>No users available to edit.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Edit</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td>
                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                        <input type="hidden" name="edit_user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                        <button type="submit" class="edit-btn">Edit</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="delete_user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                                        <button type="submit" name="delete_user" class="delete-btn">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php if ($selected_user): ?>
            <div class="form-container">
                <h2>Edit User: <?php echo htmlspecialchars($selected_user['username']); ?></h2>
                <div class="form-inner">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="editUserForm" onsubmit="return validateForm()">
                        <input type="hidden" name="edit_user_id" value="<?php echo htmlspecialchars($selected_user['id']); ?>">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($selected_user['username']); ?>" required oninput="validateUsername(this)">
                            <div id="usernameErrors" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required oninput="validateRole(this)">
                                <option value="user" <?php echo $selected_user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $selected_user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <div id="roleErrors" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="password">New Password (leave blank to keep unchanged)</label>
                            <input type="password" id="password" name="password" oninput="validatePassword(this)">
                            <div id="passwordErrors" class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" oninput="validateConfirmPassword(this)">
                            <div id="confirmPasswordErrors" class="error-message"></div>
                        </div>
                        <div class="show-password-container">
                            <input type="checkbox" id="showPassword" onclick="togglePassword()">
                            <label for="showPassword">Show Password</label>
                        </div>
                        <button type="submit" name="update_user" class="update-btn">Update User</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script>
        // Track if fields have been touched
        let touched = {
            any: false,
            username: false,
            password: false,
            confirm_password: false,
            role: false
        };

        function confirmLogout() {
            return confirm("Are you sure you want to log out?");
        }

        function confirmDelete() {
            return confirm("Are you sure you want to delete this user?");
        }

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

        function validateRole(input) {
            touched.any = true;
            touched.role = true;
            const role = input.value;
            const errors = [];
            if (touched.role && (!role || !['user', 'admin'].includes(role))) errors.push("Invalid role selected.");
            showErrors('roleErrors', errors);
            return errors.length === 0;
        }

        function validateForm() {
            touched.any = true;
            touched.username = true;
            touched.password = true;
            touched.confirm_password = true;
            touched.role = true;
            const isUsernameValid = validateUsername(document.getElementById('username'));
            const isPasswordValid = validatePassword(document.getElementById('password'));
            const isConfirmPasswordValid = validateConfirmPassword(document.getElementById('confirm_password'));
            const isRoleValid = validateRole(document.getElementById('role'));
            return isUsernameValid && isPasswordValid && isConfirmPasswordValid && isRoleValid;
        }
    </script>
</body>
</html>