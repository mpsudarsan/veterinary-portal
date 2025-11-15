<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Redirect if already logged in
if (isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in']) {
    header('Location: staff_dashboard.php');
    exit;
}

$login_error = '';
$username = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $login_error = "Username and password are required";
    } else {
        try {
            $db = getDatabaseConnection();
            
            // Get staff member with facility and role info
            $stmt = $db->prepare("
                SELECT s.*, r.role_name, r.permission_level, f.official_name AS facility_name
                FROM facility_staff s
                JOIN staff_roles r ON s.role_id = r.role_id
                JOIN veterinary_facilities f ON s.facility_id = f.facility_id
                WHERE s.username = ? AND s.is_active = 1
            ");
            $stmt->execute([$username]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($staff && password_verify($password, $staff['password'])) {
                // Successful login
                $_SESSION['staff_logged_in'] = true;
                $_SESSION['staff_id'] = $staff['staff_id'];
                $_SESSION['staff_name'] = $staff['full_name'];
                $_SESSION['staff_role'] = $staff['role_name'];
                $_SESSION['facility_id'] = $staff['facility_id'];
                $_SESSION['facility_name'] = $staff['facility_name'];
                $_SESSION['permission_level'] = $staff['permission_level'];
                
                // Update last login
                $stmt = $db->prepare("UPDATE facility_staff SET last_login = NOW() WHERE staff_id = ?");
                $stmt->execute([$staff['staff_id']]);
                
                // Check if password needs to be changed (for auto-generated accounts)
                if ($staff['auto_generated'] && !empty($staff['initial_password']) && 
                    password_verify($password, $staff['initial_password'])) {
                    $_SESSION['force_password_change'] = true;
                    header("Location: change_password.php");
                    exit;
                }
                
                // Redirect to dashboard
                header("Location: staff_dashboard.php");
                exit;
            } else {
                $login_error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $login_error = "Login error. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <h4><i class="bi bi-person-badge"></i> Staff Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($login_error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($login_error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($username); ?>" required autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="login" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="staff_forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div style="margin-bottom: 50px;"></div>
<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});
</script>

<?php include 'includes/footer.php'; ?>