<?php
require_once 'includes/config.php';

// Redirect if user not logged in
if (!isset($_SESSION['pet_owner_id']) || !isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

$successMsg = $errorMsg = "";
$userId = $_SESSION['pet_owner_id'];

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
    die("Failed to connect to MySQL: " . $conn->connect_error);
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $uploadDir = 'uploads/profile_images/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['profile_image'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = $userId . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Update database with image path
                $stmt = $conn->prepare("UPDATE pet_owners SET profile_image = ? WHERE pet_owner_id = ?");
                $stmt->bind_param('ss', $uploadPath, $userId);
                
                if ($stmt->execute()) {
                    $successMsg = "Profile image updated successfully!";
                } else {
                    $errorMsg = "Failed to save image path to database.";
                    unlink($uploadPath); // Delete uploaded file if database update fails
                }
                $stmt->close();
            } else {
                $errorMsg = "Failed to upload image.";
            }
        } else {
            $errorMsg = "Invalid file type or size too large. Please upload JPG, PNG, or GIF under 5MB.";
        }
    } else {
        $errorMsg = "Error uploading file.";
    }
}

// Handle profile update when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($email) || empty($mobile)) {
        $errorMsg = "Please fill in all required fields.";
    } else {
        try {
            // Check if additional columns exist, if not add them
            $checkColumns = $conn->query("SHOW COLUMNS FROM pet_owners LIKE 'first_name'");
            if ($checkColumns->num_rows == 0) {
                $conn->query("ALTER TABLE pet_owners ADD COLUMN first_name VARCHAR(100)");
                $conn->query("ALTER TABLE pet_owners ADD COLUMN last_name VARCHAR(100)");
                $conn->query("ALTER TABLE pet_owners ADD COLUMN gender VARCHAR(10)");
                $conn->query("ALTER TABLE pet_owners ADD COLUMN address TEXT");
                $conn->query("ALTER TABLE pet_owners ADD COLUMN profile_image VARCHAR(255)");
            }
            
            // Update user data
            $stmt = $conn->prepare("UPDATE pet_owners SET first_name=?, last_name=?, email=?, mobile=?, gender=?, address=? WHERE pet_owner_id=?");
            $stmt->bind_param('sssssss', $first_name, $last_name, $email, $mobile, $gender, $address, $userId);
            
            if ($stmt->execute()) {
                $successMsg = "Profile updated successfully!";
                
                // Update name field for consistency
                $full_name = $first_name . ' ' . $last_name;
                $name_stmt = $conn->prepare("UPDATE pet_owners SET name=? WHERE pet_owner_id=?");
                $name_stmt->bind_param('ss', $full_name, $userId);
                $name_stmt->execute();
                $name_stmt->close();
            } else {
                $errorMsg = "Error updating profile: " . $stmt->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $errorMsg = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch current user data including profile image
try {
    $stmt = $conn->prepare("SELECT name, email, mobile, first_name, last_name, gender, address, profile_image FROM pet_owners WHERE pet_owner_id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $stmt->bind_result($name, $email, $mobile, $first_name, $last_name, $gender, $address, $profile_image);
    $stmt->fetch();
    $stmt->close();
} catch (Exception $e) {
    $errorMsg = "Error fetching profile data: " . $e->getMessage();
    $name = $email = $mobile = $first_name = $last_name = $gender = $address = $profile_image = '';
}

// If first_name/last_name are empty, split the name field
if (empty($first_name) && empty($last_name) && !empty($name)) {
    $nameParts = explode(' ', trim($name), 2);
    $first_name = $nameParts[0] ?? '';
    $last_name = $nameParts[1] ?? '';
}

// Set default profile image if none exists
$displayImage = !empty($profile_image) && file_exists($profile_image) ? $profile_image : 'https://via.placeholder.com/150';

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Profile Page | Department of Animal Husbandry & Veterinary Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        .profile-btn-edit {
            background-color: var(--govt-gold) !important;
            color: #333 !important;
            border: none !important;
            box-shadow: none !important;
        }
        .profile-btn-edit:hover, .profile-btn-edit:focus {
            background-color: #e6b800 !important;
            color: #111 !important;
        }
        .profile-btn-save, .profile-btn-cancel {
            background-color: #28a745 !important;
            color: white !important;
            border: none !important;
            box-shadow: none !important;
        }
        .profile-btn-save:hover, .profile-btn-cancel:hover {
            background-color: #218838 !important;
            color: white !important;
        }

        :root {
            --govt-blue: #0066b3;
            --govt-gold: #ffcc00;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
        }

        .profile-main-container {
            flex-grow: 1;
            padding: 20px 0;
        }

        .profile-container {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .profile-sidebar {
            background-color: #f8f9fa;
            padding: 20px;
            border-right: 1px solid #dee2e6;
        }

        .profile-info {
            text-align: center;
        }

        .profile-img-container {
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            position: relative;
        }

        .profile-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--govt-blue);
            cursor: pointer;
        }

        .profile-img-overlay {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background-color: var(--govt-blue);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .profile-nav-menu {
            list-style: none;
            padding: 0;
        }

        .profile-nav-menu li {
            margin-bottom: 5px;
        }

        .profile-nav-menu a {
            display: block;
            padding: 10px 15px;
            color: #495057;
            text-decoration: none;
            border-radius: 4px;
        }

        .profile-nav-menu a:hover, .profile-nav-menu a.active {
            background-color: var(--govt-blue);
            color: white;
        }

        .profile-main-content {
            padding: 30px;
        }

        .profile-section-title {
            color: var(--govt-blue);
            margin-bottom: 25px;
            border-bottom: 2px solid var(--govt-gold);
            padding-bottom: 10px;
        }

        .profile-info-row {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .profile-info-label {
            font-weight: 600;
            color: var(--govt-blue);
            margin-right: 10px;
        }

        .profile-editable-form {
            display: none;
        }

        .profile-edit-mode .profile-info-display {
            display: none;
        }

        .profile-edit-mode .profile-editable-form {
            display: block;
        }

        .profile-btn-gender {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            background-color: white;
            color: #495057;
        }

        .profile-btn-gender.active {
            background-color: var(--govt-blue);
            color: white;
            border-color: var(--govt-blue);
        }

        #profileImageUpload {
            display: none;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
    </style>
</head>
<body class="profile-view-mode">

    <?php include 'includes/header.php'; ?>

    <div class="profile-main-container">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door"></i> Home</a></li>
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Profile</li>
                </ol>
            </nav>

            <!-- Success/Error Messages -->
            <?php if ($successMsg): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo $successMsg; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $errorMsg; ?>
                </div>
            <?php endif; ?>

            <div class="profile-container row">
                <!-- Sidebar -->
                <div class="col-md-3 profile-sidebar">
                    <div class="profile-info">
                        <div class="profile-img-container">
                            <!-- Hidden form for image upload -->
                            <form id="imageUploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
                                <input type="file" id="profileImageUpload" name="profile_image" accept="image/*">
                            </form>
                            
                            <img src="<?php echo htmlspecialchars($displayImage); ?>" id="profileImageDisplay" class="profile-img" alt="Profile">
                            <div class="profile-img-overlay" onclick="document.getElementById('profileImageUpload').click();">
                                <i class="bi bi-camera"></i>
                            </div>
                        </div>
                        <h4 id="profileDisplayName"><?php echo htmlspecialchars(trim(($first_name ?? '') . ' ' . ($last_name ?? '')) ?: $name ?? 'Pet Owner'); ?></h4>
                        <p class="text-muted">Pet Owner</p>
                        <p class="text-muted" id="profileDisplayLocation"><?php echo htmlspecialchars($address ?? 'Not specified'); ?></p>
                    </div>

                    <ul class="profile-nav-menu">
                        <li><a href="#" class="active"><i class="bi bi-person"></i> Personal Information</a></li>
                        <li><a href="my_pets.php"><i class="bi bi-list"></i> My Pets</a></li>
                        <li><a href="forgot_password.php"><i class="bi bi-lock"></i> Forget Password</a></li>
                        <li><a href="appointments.php"><i class="bi bi-calendar-check"></i> Appointments</a></li>
                        <li><a href="about.php"><i class="bi bi-question-circle"></i> Help & Support</a></li>
                        <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>

                <!-- Main Content -->
                <div class="col-md-9 profile-main-content">
                    <h2 class="profile-section-title"><i class="bi bi-person-lines-fill"></i> Personal Information</h2>

                    <!-- View Mode -->
                    <div class="profile-info-display">
                        <div class="profile-info-row">
                            <span class="profile-info-label">First Name:</span> 
                            <span class="info-value"><?php echo htmlspecialchars($first_name ?? 'Not specified'); ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span class="profile-info-label">Last Name:</span> 
                            <span class="info-value"><?php echo htmlspecialchars($last_name ?? 'Not specified'); ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span class="profile-info-label">Email Address:</span> 
                            <span class="info-value"><?php echo htmlspecialchars($email ?? 'Not specified'); ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span class="profile-info-label">Phone Number:</span> 
                            <span class="info-value"><?php echo htmlspecialchars($mobile ?? 'Not specified'); ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span class="profile-info-label">Gender:</span> 
                            <span class="info-value"><?php echo htmlspecialchars($gender ?? 'Not specified'); ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span class="profile-info-label">Address:</span> 
                            <span class="info-value"><?php echo htmlspecialchars($address ?? 'Not specified'); ?></span>
                        </div>

                        <div class="text-end mt-3">
                            <button class="btn profile-btn-edit" id="profileEditButton">
                                <i class="bi bi-pencil"></i> Edit Profile
                            </button>
                        </div>
                    </div>

                    <!-- Edit Mode -->
                    <form class="profile-editable-form" method="POST" action="">
                        <input type="hidden" name="save_profile" value="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="mobile" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="mobile" name="mobile" 
                                       value="<?php echo htmlspecialchars($mobile ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="profile-btn-gender <?php echo ($gender === 'Male') ? 'active' : ''; ?>" data-gender="Male">Male</button>
                                    <button type="button" class="profile-btn-gender <?php echo ($gender === 'Female') ? 'active' : ''; ?>" data-gender="Female">Female</button>
                                    <button type="button" class="profile-btn-gender <?php echo ($gender === 'Other') ? 'active' : ''; ?>" data-gender="Other">Other</button>
                                </div>
                                <input type="hidden" name="gender" id="selectedGender" value="<?php echo htmlspecialchars($gender ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="button" id="profileCancelButton" class="btn profile-btn-cancel me-2">Cancel</button>
                                <button type="submit" class="btn profile-btn-save"><i class="bi bi-check-circle"></i> Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let currentGender = "<?php echo htmlspecialchars($gender ?? ''); ?>";

            // Gender selection
            document.querySelectorAll(".profile-btn-gender").forEach(button => {
                button.addEventListener("click", function () {
                    document.querySelectorAll(".profile-btn-gender").forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");
                    currentGender = this.dataset.gender;
                    document.getElementById('selectedGender').value = currentGender;
                });
            });

            // Image upload handling
            document.getElementById("profileImageUpload").addEventListener("change", function (e) {
                if (e.target.files && e.target.files[0]) {
                    // Show preview immediately
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        document.getElementById("profileImageDisplay").src = event.target.result;
                    };
                    reader.readAsDataURL(e.target.files[0]);
                    
                    // Submit form to upload image
                    document.getElementById('imageUploadForm').submit();
                }
            });

            // Edit button click
            document.getElementById("profileEditButton").addEventListener("click", function () {
                document.body.classList.remove("profile-view-mode");
                document.body.classList.add("profile-edit-mode");
            });

            // Cancel button click
            document.getElementById("profileCancelButton").addEventListener("click", function () {
                document.body.classList.remove("profile-edit-mode");
                document.body.classList.add("profile-view-mode");
                location.reload();
            });
        });
    </script>

</body>
</html>
