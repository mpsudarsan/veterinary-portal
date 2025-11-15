<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/csrf.php';

// Start session if not already started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Generate CAPTCHA if not already set
if (empty($_SESSION['captcha'])) {
    $_SESSION['captcha'] = generateRandomString(6);
}

// Redirect to dashboard if already logged in
if (isset($_SESSION['pet_owner_id']) && isset($_SESSION['logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

// Initialize variables
$errors = [];
$username = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Please try again.";
        // Regenerate token
        $csrfToken = generateCsrfToken();
    }

    // Validate CAPTCHA
    if (empty($_POST['captcha']) || strtolower($_POST['captcha']) !== strtolower($_SESSION['captcha'])) {
        $errors[] = "Invalid CAPTCHA. Please try again.";
        // Regenerate CAPTCHA
        $_SESSION['captcha'] = generateRandomString(6);
    }

    // Sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    // Proceed if no errors
    if (empty($errors)) {
        try {
            $db = getDatabaseConnection();
            
            // Get user by username
            $user = getPetOwnerByUsername($username);
            
            // Verify password
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['pet_owner_id'] = $user['pet_owner_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['logged_in'] = true;
                $_SESSION['user'] = [
                    'id' => $user['pet_owner_id'],
                    'name' => $user['name'],
                    'username' => $user['username']
                ];

                // Update last login
                updateLastLogin($user['pet_owner_id']);

                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);

                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = "Invalid username or password";
                // Regenerate CAPTCHA on failed attempt
                $_SESSION['captcha'] = generateRandomString(6);
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $errors[] = "System error. Please try again later.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5" style="max-width: 500px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0 text-center">Pet Owner Login</h4>
        </div>
        
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-1"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['reason'])): ?>
                <?php if ($_GET['reason'] === 'not_logged_in'): ?>
                    <div class="alert alert-warning">
                        Please login to access that page.
                    </div>
                <?php elseif ($_GET['reason'] === 'invalid_user'): ?>
                    <div class="alert alert-danger">
                        Invalid user session. Please login again.
                    </div>
                <?php elseif ($_GET['reason'] === 'registration_success'): ?>
                    <div class="alert alert-success">
                        Registration successful! Please login with your credentials.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <form method="post" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($username) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="mb-3">
                    <label for="captcha" class="form-label">CAPTCHA: <span id="captchaText" class="fw-bold"><?= htmlspecialchars($_SESSION['captcha']) ?></span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="captcha" name="captcha" required>
                        <button type="button" class="btn btn-outline-secondary" id="refreshCaptcha">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                    </div>
                    <small class="text-muted">Enter the characters shown above</small>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            
            <div class="mt-3 text-center">
                <p class="mb-1">Don't have an account? <a href="registration.php">Register here</a></p>
                <p class="mb-0"><a href="forgot_password.php">Forgot password?</a></p>
            </div>
        </div>
    </div>
</div>

<script>
// AJAX CAPTCHA refresh
document.getElementById('refreshCaptcha').addEventListener('click', function() {
    fetch('refresh_captcha.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('captchaText').textContent = data.captcha;
        })
        .catch(error => console.error('Error refreshing CAPTCHA:', error));
});
</script>

<?php include 'includes/footer.php'; ?>