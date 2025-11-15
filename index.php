<?php
session_start();
require_once 'C:\xampp\htdocs\veterinary_portal\includes\config.php';
require_once 'C:\xampp\htdocs\veterinary_portal\includes\csrf.php';
require_once 'C:\xampp\htdocs\veterinary_portal\includes\functions.php';

$login_error = '';

// CAPTCHA Generation
if (!isset($_SESSION['captcha'])) {
    $_SESSION['captcha'] = substr(md5(rand()), 0, 6);
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (empty($_POST['captcha']) || $_POST['captcha'] !== $_SESSION['captcha']) {
        $login_error = "Invalid CAPTCHA code";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $login_error = "Username and password are required";
        } else {
            try {
                $db = getDatabaseConnection();
                $stmt = $db->prepare("SELECT * FROM pet_owners WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user'] = $user;
                    $_SESSION['pet_owner_id'] = $user['pet_owner_id'];
                    $_SESSION['logged_in'] = true;
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $login_error = "Invalid username or password";
                }
            } catch(PDOException $e) {
                $login_error = "Login error. Please try again.";
            }
        }
    }
    $_SESSION['captcha'] = substr(md5(rand()), 0, 6); // Always change after submit
}

// Fetch only currently-active announcements (not expired)
$announcements = [];
try {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT
            announcement_id, title, content, priority,
            start_date, end_date, created_at, is_active
        FROM announcements
        WHERE is_active = 1
          AND start_date <= NOW()
          AND end_date >= NOW()
        ORDER BY announcement_id DESC
        LIMIT 10
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $announcements = [];
}
?>

<?php include 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinary Portal - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .login-error {
            color: #dc3545; font-size: 0.9rem; margin-top: 5px;
        }
        .password-field { position: relative; }
        .password-toggle {
            position: absolute; right: 10px; top: 50%;
            transform: translateY(-50%); cursor: pointer;
        }
        .captcha-container {
            margin: 15px 0; padding: 10px;
            background-color: #f8f9fa; border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .captcha-image {
            border: 1px solid #ddd; height: 50px;
            background-color: #f5f5f5; display: flex; align-items: center; justify-content: center;
            font-family: Arial, sans-serif; font-size: 24px; letter-spacing: 3px; font-weight: bold; color: #333;
            padding: 5px; border-radius: 4px;
        }
        .captcha-refresh-btn { cursor: pointer; color: var(--govt-blue); }
        .marquee-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px; border: 2px solid #dee2e6;
            min-height: 200px; max-height: 300px; overflow-y: auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); position: relative;
        }
        .marquee-line {
            min-height: 60px; display: flex; align-items: center;
            border-bottom: 1px solid rgba(222,226,230,0.8);
            background: rgba(255,255,255,0.7); margin: 5px;
            border-radius: 8px; padding: 5px;
        }
        .marquee-line:last-child { border-bottom: none; }
        .marquee-line:nth-child(even) { background: rgba(248,249,250,0.9);}
        .login-sidebar { position: sticky; top: 20px; height: fit-content; }
        .main-content-wrapper { padding-right: 15px; }
        .login-wrapper { padding-left: 15px; }
        @media (max-width: 991.98px) {
            .main-content-wrapper, .login-wrapper { padding-left:15px; padding-right:15px; }
            .login-sidebar { position: static; margin-bottom: 30px;}
        }
    </style>
</head>
<body>
    <div class="container mt-4" id="main-content">
        <div class="row">
            <!-- Left Column: Main Content -->
            <div class="col-lg-9 order-lg-1 order-2 main-content-wrapper">
                <!-- Image Slider -->
                <div class="slider-section mb-4">
                    <div id="mainSlider" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="https://ahvs.andaman.gov.in/admin-pannel/sliderupload/pet%20walk.jpg" class="d-block w-100" alt="Veterinary Services" style="height: 400px; object-fit: cover;">
                            </div>
                            <div class="carousel-item">
                                <img src="https://ahvs.andaman.gov.in/admin-pannel/sliderupload/duck.jpg" class="d-block w-100" alt="Animal Care" style="height: 400px; object-fit: cover;">
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#mainSlider" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#mainSlider" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                </div>
                <!-- What's New Section -->
                <div class="mt-4">
                    <h4 class="mb-3" style="color: var(--govt-blue); border-bottom: 2px solid var(--govt-blue); padding-bottom: 5px;">
                        <span class="me-2" style="font-size: 1.6rem; color: #007bff;"><i class="bi bi-bell-fill"></i></span>
                        What's New?
                    </h4>
                    <div class="marquee-container">
                        <?php if (!empty($announcements)): ?>
                            <?php $counter = 1; foreach ($announcements as $announcement): ?>
                                <div class="marquee-line">
                                    <marquee scrollamount="4" direction="left"
                                        onmouseover="this.stop();" onmouseout="this.start();"
                                        style="font-size: 1.05rem; width: 100%; height: 100%;">
                                        <span style="display: inline-flex; align-items: center; padding: 8px 18px;">
                                            <strong style="color: #007bff;">[<?= $counter ?>]</strong>
                                            <strong style="margin-left: 15px; color: #2c3e50;"><?= htmlspecialchars($announcement['title']) ?>:</strong>
                                            <span style="margin-left: 10px; color: #34495e;"><?= htmlspecialchars($announcement['content']) ?></span>
                                            <span style="margin-left: 20px; font-size: 0.9rem; color: #6c757d;">
                                                (Valid: <?= date('d-m-Y H:i', strtotime($announcement['start_date'])) ?> –
                                                <?= date('d-m-Y H:i', strtotime($announcement['end_date'])) ?>)
                                            </span>
                                            <?php if ($announcement['priority'] === 'high'): ?>
                                                <span class="badge bg-danger" style="margin-left:15px;">URGENT</span>
                                            <?php elseif ($announcement['priority'] === 'medium'): ?>
                                                <span class="badge bg-warning" style="margin-left:15px;">IMPORTANT</span>
                                            <?php endif; ?>
                                        </span>
                                    </marquee>
                                </div>
                            <?php $counter++; endforeach; ?>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100" style="height:160px;">
                                <div class="text-center p-4">
                                    <i class="bi bi-info-circle" style="font-size: 2.5rem; color: #6c757d;"></i>
                                    <h5 class="mt-3 text-muted">No Announcements Found</h5>
                                    <p class="text-muted">No active announcements right now.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Right Column: Login + Quick Links -->
            <div class="col-lg-3 order-lg-2 order-1 login-wrapper">
                <div class="login-sidebar">
                    <!-- Login Box -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header" style="background-color: var(--govt-blue); color: white;">
                            <h5 class="mb-0 text-center"><i class="bi bi-person-circle"></i> Pet Owner Login</h5>
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
                                            <?php echo $_SESSION['captcha']; ?>
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
                                <button type="submit" name="login" class="btn w-100" style="background-color: var(--govt-blue); color: white;">Login</button>
                            </form>
                            <div class="text-center mt-3">
                                <a href="registration.php" class="small text-decoration-none">New User? Register Here</a>
                                <br>
                                <a href="forgot_password.php" class="small text-decoration-none">Forgot Password?</a>
                            </div>
                        </div>
                    </div>
                    <!-- Quick Links -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header" style="background-color: var(--govt-blue); color: white;">
                            <h6 class="mb-0"><i class="bi bi-link-45deg"></i> Quick Links</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><a href="registration.php" class="text-decoration-none"><i class="bi bi-chevron-right"></i> Pet Registration</a></li>
                                <li class="mb-2"><a href="Vaccination Schedule.php" class="text-decoration-none"><i class="bi bi-chevron-right"></i> Vaccination Schedule</a></li>
                                <li class="mb-2"><a href="locate.php" class="text-decoration-none"><i class="bi bi-chevron-right"></i> Find Veterinary Center</a></li>
                                <li class="mb-0"><a href="#" class="text-decoration-none"><i class="bi bi-chevron-right"></i> Acts & Rules</a></li>
                            </ul>
                        </div>
                    </div>
                </div> 
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>

<script>
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
function refreshCaptcha() {
    fetch('refresh_captcha.php')
        .then(response => response.json())
        .then(data => {
            if (data.captcha) {
                document.querySelector('.captcha-image').textContent = data.captcha;
            } else {
                location.reload();
            }
        })
        .catch(error => { location.reload(); });
}
// Optionally auto-refresh (to update announcements)
setInterval(function() { location.reload(); }, 120000);
</script>
</body>
</html>
