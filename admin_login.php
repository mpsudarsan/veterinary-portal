<?php
// C:\xampp\htdocs\veterinary_portal\admin\admin_login.php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

$login_error = '';

// CAPTCHA Generation
if (!isset($_SESSION['admin_captcha'])) {
    $_SESSION['admin_captcha'] = substr(md5(rand()), 0, 6); // 6-character random string
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    // Verify CAPTCHA first
    if (empty($_POST['captcha']) || $_POST['captcha'] !== $_SESSION['admin_captcha']) {
        $login_error = "Invalid CAPTCHA code";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic validation
        if (empty($username) || empty($password)) {
            $login_error = "Username and password are required";
        } else {
            try {
                $db = getDatabaseConnection();
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($admin && password_verify($password, $admin['password_hash'])) {
                    // Update last login time
                    $update_stmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->execute([$admin['id']]);
                    
                    // Set admin session variables
                    $_SESSION['admin'] = $admin;
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    
                    header("Location: admin_dashboard.php");
                    exit;
                } else {
                    $login_error = "Invalid username or password";
                }
            } catch(PDOException $e) {
                $login_error = "Login error. Please try again.";
                error_log("Admin login error: " . $e->getMessage());
            }
        }
    }
    // Regenerate CAPTCHA after each attempt
    $_SESSION['admin_captcha'] = substr(md5(rand()), 0, 6);
}

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Veterinary Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
    body {
        background-color: #f8f9fa;
    }
    .admin-login-container {
        max-width: 500px;
        margin: 5rem auto;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        overflow: hidden;
    }
    .admin-login-header {
        background-color: var(--govt-blue); /* Matching the main portal color */
        color: white;
        padding: 1.5rem;
        text-align: center;
    }
    .admin-login-body {
        padding: 2rem;
        background-color: white;
    }
    .login-error {
        color: #dc3545;
        font-size: 0.9rem;
        margin-top: 5px;
    }
    .password-field {
        position: relative;
    }
    .password-toggle {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--govt-blue); /* Matching the main portal color */
    }
    .captcha-container {
        margin: 15px 0;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
        border: 1px solid #dee2e6;
    }
    .captcha-image {
        border: 1px solid #ddd;
        height: 50px;
        background-color: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: Arial, sans-serif;
        font-size: 24px;
        letter-spacing: 3px;
        font-weight: bold;
        color: var(--govt-blue); /* Matching the main portal color */
        padding: 5px;
        border-radius: 4px;
    }
    .captcha-refresh-btn {
        cursor: pointer;
        color: var(--govt-blue); /* Matching the main portal color */
    }
    .btn-dark {
        background-color: var(--govt-blue); /* Matching the main portal color */
        border-color: var(--govt-blue); /* Matching the main portal color */
    }
    .btn-dark:hover {
        background-color: #1a4b8c; /* Slightly darker shade for hover */
        border-color: #1a4b8c;
    }
</style>
</head>
<body>
    <div class="container">
        <div class="admin-login-container">
            <div class="admin-login-header">
                <h2><i class="bi bi-shield-lock"></i> Admin Portal</h2>
                <p class="mb-0">Veterinary Services Administration</p>
            </div>
            <div class="admin-login-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-field">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <span class="password-toggle" onclick="togglePassword('password')">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <!-- CAPTCHA Section -->
                    <div class="mb-3 captcha-container">
                        <label for="captcha" class="form-label">Enter CAPTCHA Code</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="captcha-image">
                                <?php echo $_SESSION['admin_captcha']; ?>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary captcha-refresh-btn" onclick="refreshCaptcha()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                        <input type="text" class="form-control" id="captcha" name="captcha" required>
                    </div>
                    
                    <?php if ($login_error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($login_error); ?></div>
                    <?php endif; ?>
                    
                    <button type="submit" name="admin_login" class="btn btn-dark w-100">Login</button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Back to Main Site
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
        
        // Refresh CAPTCHA
        function refreshCaptcha() {
            fetch('../refresh_captcha.php?admin=1')
                .then(response => response.json())
                .then(data => {
                    document.querySelector('.captcha-image').textContent = data.captcha;
                });
        }
    </script>
</body>
</html>
<?php include 'includes/footer.php'; ?>