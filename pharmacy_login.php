<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Redirect if already logged in
if (isset($_SESSION['pharmacy_logged_in']) && $_SESSION['pharmacy_logged_in']) {
    header('Location: pharmacy_dashboard.php');
    exit;
}

$login_error = '';
$username = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $login_error = "Username and password are required";
    } else {
        try {
            $db = getDatabaseConnection();

            // Get pharmacy staff member
            $stmt = $db->prepare("
                SELECT p.*, f.official_name AS facility_name
                FROM pharmacy_staff p
                JOIN veterinary_facilities f ON p.facility_id = f.facility_id
                WHERE p.username = ? AND p.is_active = 1
            ");
            $stmt->execute([$username]);
            $pharmacy_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pharmacy_user && password_verify($password, $pharmacy_user['password'])) {
                // Successful login
                $_SESSION['pharmacy_logged_in'] = true;
                $_SESSION['pharmacy_id'] = $pharmacy_user['pharmacy_id'];
                $_SESSION['pharmacy_name'] = $pharmacy_user['full_name'];
                $_SESSION['facility_id'] = $pharmacy_user['facility_id'];
                $_SESSION['facility_name'] = $pharmacy_user['facility_name'];

                // Update last login
                $stmt = $db->prepare("UPDATE pharmacy_staff SET last_login = NOW() WHERE pharmacy_id = ?");
                $stmt->execute([$pharmacy_user['pharmacy_id']]);

                header("Location: pharmacy_dashboard.php");
                exit;
            } else {
                $login_error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            error_log("Pharmacy login error: " . $e->getMessage());
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
                    <h4><i class="bi bi-capsule"></i> Pharmacy Login</h4>
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
                            <button type="submit" name="login" class="btn bg-primary text-white">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <a href="pharmacy_forgot_password.php" class="text-decoration-none">Forgot Password?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div style="margin-bottom: 50px;"></div>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
