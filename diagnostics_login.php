<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Redirect if already logged in
if (isset($_SESSION['diagnostics_logged_in']) && $_SESSION['diagnostics_logged_in']) {
    header('Location: diagnostics_dashboard.php');
    exit;
}

$login_error = '';
$username = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $login_error = "Username and password are required";
    } else {
        try {
            $db = getDatabaseConnection();

            // Fetch diagnostics staff
            $stmt = $db->prepare("
                SELECT d.*, f.official_name AS facility_name
                FROM diagnostics_staff d
                JOIN veterinary_facilities f ON d.facility_id = f.facility_id
                WHERE d.username = ? AND d.is_active = 1
            ");
            $stmt->execute([$username]);
            $diag_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($diag_user && password_verify($password, $diag_user['password'])) {
                // Success
                $_SESSION['diagnostics_logged_in'] = true;
                $_SESSION['diagnostics_id'] = $diag_user['diagnostics_id'];
                $_SESSION['diagnostics_name'] = $diag_user['full_name'];
                $_SESSION['facility_id'] = $diag_user['facility_id'];
                $_SESSION['facility_name'] = $diag_user['facility_name'];

                // Update last login
                $stmt = $db->prepare("UPDATE diagnostics_staff SET last_login = NOW() WHERE diagnostics_id = ?");
                $stmt->execute([$diag_user['diagnostics_id']]);

                header("Location: diagnostics_dashboard.php");
                exit;
            } else {
                $login_error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            error_log("Diagnostics login error: " . $e->getMessage());
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
                <div class="card-header bg-primary text-whitetext-center">
                    <h4><i class="bi bi-activity"></i> Animalis Diagnostics Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($login_error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?= htmlspecialchars($username) ?>" required autofocus>
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
                            <a href="diagnostics_forgot_password.php" class="text-decoration-none">Forgot Password?</a>
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
    const fld = document.getElementById('password');
    const icon = this.querySelector('i');
    if (fld.type === 'password') {
        fld.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        fld.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
