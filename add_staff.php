<?php
session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

// Check admin privileges
//if (!isset($_SESSION['admin_logged_in'])) {
 //   header("Location: admin_login.php");
 //   exit;
//}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
try {
    $db = getDatabaseConnection();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize variables
$errors = [];
$success_message = '';
$formData = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'facility_id' => '',
    'role_id' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Please try again.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Sanitize inputs
        $formData = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'facility_id' => $_POST['facility_id'] ?? '',
            'role_id' => $_POST['role_id'] ?? ''
        ];

        // Validate inputs
        if (empty($formData['full_name'])) {
            $errors[] = "Full name is required";
        }
        if (empty($formData['facility_id'])) {
            $errors[] = "Facility is required";
        }
        if (empty($formData['role_id'])) {
            $errors[] = "Role is required";
        }
        if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        if (!empty($formData['phone']) && !preg_match('/^[0-9]{10,15}$/', $formData['phone'])) {
            $errors[] = "Phone number must be 10-15 digits";
        }

        // Proceed if no errors
        if (empty($errors)) {
            try {
                // Generate username and password
                $username = strtolower(str_replace(' ', '', $formData['full_name'])) . rand(100, 999);
                $temp_password = substr(md5(time()), 0, 8);
                $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                
                // Set default values
                $is_active = 1;
                $auto_generated = 1;
                
                // Insert staff member
                $stmt = $db->prepare("INSERT INTO facility_staff (
                    facility_id, role_id, username, password, full_name, email, phone, 
                    is_active, auto_generated, initial_password, credentials_sent_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                $stmt->execute([
                    $formData['facility_id'],
                    $formData['role_id'],
                    $username,
                    $hashed_password,
                    $formData['full_name'],
                    !empty($formData['email']) ? $formData['email'] : null,
                    !empty($formData['phone']) ? $formData['phone'] : null,
                    $is_active,
                    $auto_generated,
                    $temp_password
                ]);
                
                $success_message = "Staff member registered successfully!<br>
                                  <strong>Username:</strong> $username<br>
                                  <strong>Temporary Password:</strong> $temp_password<br>
                                  <strong>Status:</strong> Active";
                
                // Clear form data on success
                $formData = [
                    'full_name' => '',
                    'email' => '',
                    'phone' => '',
                    'facility_id' => '',
                    'role_id' => ''
                ];
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
                error_log("Staff registration error: " . $e->getMessage());
            }
        }
    }
}

// Fetch facilities and roles for dropdowns
$facilities = [];
$roles = [];

try {
    $stmt = $db->query("
        SELECT f.facility_id, f.official_name, d.district_name 
        FROM veterinary_facilities f
        JOIN districts d ON f.district = d.district_code
        ORDER BY d.district_name, f.official_name
    ");
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT * FROM staff_roles ORDER BY permission_level DESC");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Failed to load required data. Please try again.";
    error_log("Dropdown data error: " . $e->getMessage());
}

include __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Staff | Veterinary Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .role-description {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: -5px;
            margin-bottom: 10px;
        }
        .permission-badge {
            font-size: 0.7rem;
            background-color: #0074c1;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .form-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .admin-card {
            border: 1px solid rgba(0, 0, 0, 0.125);
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .btn-govt {
            background-color: var(--govt-blue);
            color: white;
        }
        .btn-govt:hover {
            background-color: var(--govt-dark-blue);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar Menu -->
            <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
            
            <!-- Main Admin Content Area -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-person-plus"></i> Register New Staff Member</h1>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-1"><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?= $success_message ?>
                        <div class="mt-3">
                            <a href="add_staff.php" class="btn btn-govt me-2">Add Another Staff</a>
                            <a href="manage_staff.php" class="btn btn-outline-secondary">View All Staff</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card admin-card">
                        <div class="card-header" style="background-color: var(--govt-blue); color: white;">
                            <i class="bi bi-person-badge"></i> Staff Registration Form
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                
                                <!-- Personal Information Section -->
                                <div class="form-section">
                                    <h4 style="color: var(--govt-blue);">Personal Information</h4>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label required-field">Full Name</label>
                                            <input type="text" class="form-control" name="full_name" 
                                                   value="<?= htmlspecialchars($formData['full_name']) ?>" required>
                                            <div class="invalid-feedback">
                                                Please provide the staff member's full name.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?= htmlspecialchars($formData['email']) ?>">
                                            <div class="invalid-feedback">
                                                Please provide a valid email address.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" name="phone" 
                                                   value="<?= htmlspecialchars($formData['phone']) ?>">
                                            <div class="invalid-feedback">
                                                Please provide a valid phone number (10-15 digits).
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Facility and Role Assignment -->
                                <div class="form-section">
                                    <h4 style="color: var(--govt-blue);">Facility & Role Assignment</h4>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label required-field">Facility</label>
                                            <select class="form-select" name="facility_id" required>
                                                <option value="">Select Facility</option>
                                                <?php foreach ($facilities as $facility): ?>
                                                    <option value="<?= $facility['facility_id'] ?>" 
                                                        <?= $formData['facility_id'] == $facility['facility_id'] ? 'selected' : '' ?>>
                                                        [<?= htmlspecialchars($facility['district_name']) ?>] <?= htmlspecialchars($facility['official_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select a facility.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label required-field">Staff Role</label>
                                            <select class="form-select" name="role_id" required id="roleSelect">
                                                <option value="">Select Role</option>
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?= $role['role_id'] ?>" 
                                                        data-description="<?= htmlspecialchars($role['description']) ?>"
                                                        data-permission="<?= $role['permission_level'] ?>"
                                                        <?= $formData['role_id'] == $role['role_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($role['role_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div id="roleDescription" class="role-description">
                                                Select a role to see description
                                            </div>
                                            <div class="invalid-feedback">
                                                Please select a role.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Account Information -->
                                <div class="form-section">
                                    <h4 style="color: var(--govt-blue);">Account Information</h4>
                                    
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle-fill"></i> System will automatically generate:
                                        <ul class="mb-0">
                                            <li>Unique username based on staff name</li>
                                            <li>Secure temporary password</li>
                                            <li>Account will be set to active status</li>
                                            <li>Credentials will be marked as auto-generated</li>
                                            <li>Initial password will be stored temporarily</li>
                                            <li>Credential sent timestamp will be recorded</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-govt me-md-2">
                                        <i class="bi bi-person-plus"></i> Register Staff
                                    </button>
                                    <a href="manage_staff.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show role description when selected
        document.getElementById('roleSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const description = selectedOption.getAttribute('data-description') || 'No description available';
            const permissionLevel = selectedOption.getAttribute('data-permission') || '0';
            
            const descriptionElement = document.getElementById('roleDescription');
            descriptionElement.innerHTML = description + 
                ' <span class="permission-badge">Permission Level: ' + permissionLevel + '</span>';
        });

        // Form validation
        (function() {
            'use strict';
            
            var forms = document.querySelectorAll('.needs-validation');
            
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        
                        form.classList.add('was-validated');
                    }, false);
                });
        })();

        // Phone number validation
        document.querySelector('input[name="phone"]').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>