<?php
session_start();
require_once 'C:\xampp\htdocs\veterinary_portal\includes\config.php';
require_once 'C:\xampp\htdocs\veterinary_portal\includes\functions.php';

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
                $stmt = $db->prepare("SELECT * FROM pet_owners WHERE mobile = ?");
                $stmt->execute([$mobile]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Generate 6-digit OTP (valid for 5 minutes)
                    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $_SESSION['reset_otp'] = $otp;
                    $_SESSION['reset_mobile'] = $mobile;
                    $_SESSION['otp_expires'] = time() + 300; // 5-minute expiry
                    
                    // Simulate OTP delivery (in production, use SMS API here)
                    $message = "Demo OTP sent to $mobile: <strong>$otp</strong> (Valid for 5 minutes)";
                    $show_otp_form = true;
                } else {
                    $error = "No account found with this mobile number";
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
        
        if ($otp_entered !== $_SESSION['reset_otp'] || time() > $_SESSION['otp_expires']) {
            $error = "Invalid or expired OTP";
        } elseif (empty($new_password) || $new_password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            try {
                $db = getDatabaseConnection();
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("UPDATE pet_owners SET password = ? WHERE mobile = ?");
                $stmt->execute([$hashed_password, $_SESSION['reset_mobile']]);
                
                // Clear session data
                unset($_SESSION['reset_otp'], $_SESSION['reset_mobile'], $_SESSION['otp_expires']);
                
                $message = "Password updated successfully! <a href='index.php'>Login now</a>";
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
    <title>Forgot Password - OTP Verification</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-phone"></i> Mobile OTP Verification</h4>
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
                        Send OTP
                    </button>
                    <div class="text-center mt-3">
                        <a href="index.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Back to Login
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
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-success w-100">
                        Reset Password
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
        document.querySelector('input[name="mobile"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>