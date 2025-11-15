<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['staff_logged_in'])) {
    header('Location: staff_dashboard.php');
    exit;
}

$error = '';
$message = '';
$show_otp_form = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_otp'])) {
        // Step 1: Verify mobile number
        $mobile = preg_replace('/[^0-9]/', '', $_POST['mobile'] ?? '');
        
        if (empty($mobile)) {
            $error = "Mobile number is required";
        } else {
            try {
                $db = getDatabaseConnection();
                $stmt = $db->prepare("SELECT * FROM facility_staff WHERE phone = ? AND is_active = 1");
                $stmt->execute([$mobile]);
                $staff = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($staff) {
                    // Generate 6-digit OTP (valid for 5 minutes)
                    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $_SESSION['reset_otp'] = $otp;
                    $_SESSION['reset_mobile'] = $mobile;
                    $_SESSION['otp_expires'] = time() + 300; // 5-minute expiry
                    $_SESSION['reset_staff_id'] = $staff['staff_id'];
                    
                    // Simulate OTP delivery (in production, use SMS API here)
                    $message = "Demo OTP sent to $mobile: <strong>$otp</strong> (Valid for 5 minutes)";
                    $show_otp_form = true;
                } else {
                    $error = "No active staff account found with this mobile number";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } 
    elseif (isset($_POST['reset_password'])) {
        // Step 2: Verify OTP and update password
        $otp_entered = $_POST['otp'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_staff_id'])) {
            $error = "OTP verification required";
        } elseif ($otp_entered !== $_SESSION['reset_otp'] || time() > $_SESSION['otp_expires']) {
            $error = "Invalid or expired OTP";
        } elseif (empty($new_password) || $new_password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            try {
                $db = getDatabaseConnection();
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("UPDATE facility_staff SET password = ?, initial_password = NULL WHERE staff_id = ?");
                $stmt->execute([$hashed_password, $_SESSION['reset_staff_id']]);
                
                // Clear session data
                unset(
                    $_SESSION['reset_otp'], 
                    $_SESSION['reset_mobile'], 
                    $_SESSION['otp_expires'],
                    $_SESSION['reset_staff_id']
                );
                
                $message = "Password updated successfully! <a href='staff_login.php'>Login now</a>";
            } catch (PDOException $e) {
                $error = "Error updating password: " . $e->getMessage();
            }
        }
    }
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Password Recovery</title>
    <style>
        .card {
            max-width: 500px;
            margin: 2rem auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .otp-form {
            display: <?= $show_otp_form ? 'block' : 'none' ?>;
        }
        .alert {
            margin-bottom: 1rem;
        }
        .staff-login-header {
            background-color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header staff-login-header text-white">
                <h4 class="mb-0"><i class="bi bi-person-badge"></i> Staff Password Recovery</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                
                <!-- Step 1: Mobile Number Input -->
                <form method="POST" id="mobileForm" <?= $show_otp_form ? 'style="display:none"' : '' ?>>
                    <div class="mb-3">
                        <label class="form-label">Registered Mobile Number</label>
                        <input type="tel" name="mobile" class="form-control" placeholder="e.g., 9876543210" required>
                    </div>
                    <button type="submit" name="send_otp" class="btn btn-primary w-100">
                        <i class="bi bi-shield-lock"></i> Send OTP
                    </button>
                    <div class="text-center mt-3">
                        <a href="staff_login.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Back to Staff Login
                        </a>
                    </div>
                </form>
                
                <!-- Step 2: OTP Verification -->
                <form method="POST" id="otpForm" class="otp-form">
                    <div class="mb-3">
                        <label class="form-label">Enter OTP</label>
                        <input type="text" name="otp" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                        <div class="form-text">Minimum 8 characters with at least one number and special character</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-success w-100">
                        <i class="bi bi-key"></i> Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-switch forms when OTP is sent
        <?php if ($show_otp_form): ?>
            document.getElementById('mobileForm').style.display = 'none';
            document.getElementById('otpForm').style.display = 'block';
        <?php endif; ?>
        
        // Format mobile number input
        document.querySelector('input[name="mobile"]')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Password strength validation
        document.querySelector('input[name="new_password"]')?.addEventListener('input', function(e) {
            const password = this.value;
            const regex = /^(?=.*[0-9])(?=.*[!@#$%^&*])[a-zA-Z0-9!@#$%^&*]{8,}$/;
            if (!regex.test(password)) {
                this.setCustomValidity("Password must be at least 8 characters with one number and one special character");
            } else {
                this.setCustomValidity("");
            }
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>