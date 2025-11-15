<?php
session_start();
require_once 'C:\xampp\htdocs\veterinary_portal\includes\config.php';
require_once 'C:\xampp\htdocs\veterinary_portal\includes\csrf.php';
require_once 'C:\xampp\htdocs\veterinary_portal\includes\functions.php';

$login_error = '';

// CAPTCHA Generation
if (!isset($_SESSION['doctor_captcha'])) {
    $_SESSION['doctor_captcha'] = substr(md5(rand()), 0, 6); // 6-character random string
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Verify CAPTCHA first
    if (empty($_POST['captcha']) || $_POST['captcha'] !== $_SESSION['doctor_captcha']) {
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
                
                // Check if user exists and is a veterinarian
                $stmt = $db->prepare("SELECT fs.*, sr.role_name 
                                    FROM facility_staff fs
                                    JOIN staff_roles sr ON fs.role_id = sr.role_id
                                    WHERE fs.username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // var_dump(hash('sha256', $password));
                    // exit();
                    // Check if user has an allowed role
                    $allowed_roles = ['Veterinarian', 'Senior Veterinarian', 'Veterinary Surgeon'];

                    if (in_array($user['role_name'], $allowed_roles)) {
    if (password_verify($password, $user['password'])) {
                                                        // Update last login
                            $update_stmt = $db->prepare("UPDATE facility_staff SET last_login = NOW() WHERE staff_id = ?");
                            $update_stmt->execute([$user['staff_id']]);
                            
                            // Set session variables
                            $_SESSION['doctor'] = $user;
                            $_SESSION['staff_id'] = $user['staff_id'];
                            $_SESSION['full_name'] = $user['full_name'];
                            $_SESSION['role'] = $user['role_name'];
                            $_SESSION['facility_id'] = $user['facility_id'];
                            $_SESSION['doctor_logged_in'] = true;

                            // Set permission level based on role
$permission_level = 2; // Default doctor level
if ($user['role_name'] === 'Senior Veterinarian') {
    $permission_level = 3;
} elseif ($user['role_name'] === 'Veterinary Surgeon') {
    $permission_level = 4;
}
$_SESSION['permission_level'] = $permission_level;

                            $stmt = $db->prepare("SELECT official_name FROM veterinary_facilities WHERE facility_id = ?");
$stmt->execute([$user['facility_id']]);
$facility = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['facility_name'] = $facility['official_name'] ?? 'Unknown Facility';
                            
                            header("Location: doctor_dashboard.php");
                            exit;
                        } else {
                            $login_error = "Invalid username or password";
                        }
                    } else {
                        $login_error = "Access restricted to veterinary medical staff only";
                    }
                } else {
                    $login_error = "Invalid username or password";
                }
            } catch(PDOException $e) {
                $login_error = "Login error. Please try again.";
                error_log("Doctor login error: " . $e->getMessage());
            }
        }
    }
    // Regenerate CAPTCHA after each attempt
    $_SESSION['doctor_captcha'] = substr(md5(rand()), 0, 6);
}

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Veterinary Portal - Doctor Login</title>
    <style>  
    .doctor-login-form .form-control {
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
        }
        .doctor-login-form .form-label {
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .doctor-login-form .mb-3 {
            margin-bottom: 1rem !important;
        }   
        .doctor-login-card {
            border-top: 4px solid #0d6efd; /* Blue instead of green */
        }
        .doctor-login-header {
            background-color: #0d6efd !important; /* Bootstrap primary blue */
        }
        .btn-doctor {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .captcha-refresh-btn {
            color: #0d6efd !important;
        }
        /* Login Error Styles */
        .login-error {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        /* Password Field Styles */
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        
        /* CAPTCHA Styles */
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
            color: #333;
            padding: 5px;
            border-radius: 4px;
        }
        .captcha-refresh-btn {
            cursor: pointer;
            color: var(--govt-blue);
        }
        
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="container mt-4" id="main-content">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <!-- Doctor Login Box -->
                <div class="card mb-4 shadow-sm doctor-login-card">
                    <div class="card-header doctor-login-header text-white">
                        <h5 class="mb-0 text-center"><i class="bi bi-shield-lock"></i> Veterinary Medical Staff Login</h5>
                    </div>
                    <div class="card-body">
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
                                        <?php echo $_SESSION['doctor_captcha']; ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary captcha-refresh-btn" onclick="refreshDoctorCaptcha()">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                </div>
                                <input type="text" class="form-control" id="captcha" name="captcha" required>
                            </div>
                            
                            <?php if ($login_error): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($login_error); ?></div>
                            <?php endif; ?>
                            
                            <button type="submit" name="login" class="btn btn-success w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="forgot_password.php?type=doctor" class="small">Forgot Password?</a>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <h5><i class="bi bi-info-circle"></i> Access Notice</h5>
                    <p class="mb-0">This portal is restricted to licensed veterinary medical professionals only. If you are a pet owner, please use the <a href="login.php">pet owner login</a>.</p>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

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
       function refreshDoctorCaptcha() {
        fetch('refresh_captcha.php?type=doctor&rand=' + Math.random()) // Cache-buster
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                document.querySelector('.captcha-image').textContent = data.captcha;
            })
            .catch(error => {
                console.error('CAPTCHA refresh failed:', error);
            });
    }
    </script>
</body>
</html>