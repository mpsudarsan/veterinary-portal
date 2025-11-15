<?php
session_start();
require_once 'includes/config.php';    // your DB connection info
require_once 'includes/functions.php'; // your DB connection helper, etc.

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $login_error = 'Please enter both username and password.';
    } else {
        try {
            $db = getDatabaseConnection();

            // --- 1. Try Admin Table ---
            $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Admin Login Success
                $_SESSION['user_type'] = 'admin';
                $_SESSION['user'] = [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'name' => $admin['name'] ?? '',
                    'email' => $admin['email'] ?? ''
                ];
                $_SESSION['admin_logged_in'] = true;
                header("Location: admin_dashboard.php");
                exit;
            }

            // --- 2. Try Staff/Doctor Table ---
            // Assuming facility_staff with roles contains both staff and doctors.
            $stmt = $db->prepare("
                SELECT fs.*, sr.role_name, sr.permission_level 
                FROM facility_staff fs
                JOIN staff_roles sr ON fs.role_id = sr.role_id
                WHERE fs.username = ? AND fs.is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($staff && password_verify($password, $staff['password'])) {
                // Staff/Doctor Login Success
                $_SESSION['user_type'] = 'staff'; // default to staff

                // Define doctors by role name - adjust your roles as needed
                $doctor_roles = ['veterinarian', 'senior veterinarian', 'veterinary surgeon'];

                if (in_array(strtolower($staff['role_name']), $doctor_roles, true)) {
                    $_SESSION['user_type'] = 'doctor';
                    $_SESSION['doctor_logged_in'] = true;
                } else {
                    $_SESSION['staff_logged_in'] = true;
                }

                $_SESSION['user'] = [
                    'staff_id' => $staff['staff_id'],
                    'username' => $staff['username'],
                    'name' => $staff['staff_name'] ?? '',
                    'role' => $staff['role_name'] ?? '',
                    'permission_level' => $staff['permission_level'] ?? 0
                    // add more fields as needed
                ];

                // Redirect based on user type
                if ($_SESSION['user_type'] === 'doctor') {
                    header("Location: doctor_dashboard.php");
                } else {
                    header("Location: staff_dashboard.php");
                }
                exit;
            }

            // If no match found:
            $login_error = 'Invalid username or password.';

        } catch (PDOException $e) {
            // Log error internally, don't expose sensitive info
            error_log('Login error: ' . $e->getMessage());
            $login_error = 'An error occurred during login. Please try again later.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Login - Veterinary Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f8f9fa; }
        .login-container {
            max-width: 400px;
            margin: 80px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h3 class="mb-4 text-center">Veterinary Portal Login</h3>

        <?php if ($login_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input autofocus type="text" id="username" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required />
            </div>

            <button type="submit" class="btn btn-primary w-100">Log In</button>
        </form>

        <div class="mt-3 text-center">
            <a href="forgot_password.php">Forgot Password?</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
