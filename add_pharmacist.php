<?php
session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

// (Optional) Check admin login
// if (!isset($_SESSION['admin_logged_in'])) {
//     header("Location: admin_login.php");
//     exit;
// }

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $db = getDatabaseConnection();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$errors = [];
$success_message = '';
$formData = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'facility_id' => ''
];

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Please try again.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        $formData = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'facility_id' => $_POST['facility_id'] ?? ''
        ];

        // Validation
        if (empty($formData['full_name'])) $errors[] = "Full name is required";
        if (empty($formData['facility_id'])) $errors[] = "Facility is required";
        if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        if (!empty($formData['phone']) && !preg_match('/^[0-9]{10,15}$/', $formData['phone'])) {
            $errors[] = "Phone number must be 10-15 digits";
        }

        if (empty($errors)) {
            try {
                // Auto-generate username & password
                $username = strtolower(str_replace(' ', '', $formData['full_name'])) . rand(100, 999);
                $temp_password = substr(md5(time()), 0, 8);
                $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

                $is_active = 1;
                $auto_generated = 1;
                $role_name = "Pharmacist"; // fixed role

                // Insert pharmacist
                $stmt = $db->prepare("INSERT INTO pharmacy_staff (
                    facility_id, username, password, full_name, email, phone, role_name,
                    is_active, auto_generated, initial_password, credentials_sent_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                $stmt->execute([
                    $formData['facility_id'],
                    $username,
                    $hashed_password,
                    $formData['full_name'],
                    !empty($formData['email']) ? $formData['email'] : null,
                    !empty($formData['phone']) ? $formData['phone'] : null,
                    $role_name,
                    $is_active,
                    $auto_generated,
                    $temp_password
                ]);

                $success_message = "Pharmacist registered successfully!<br>
                                    <strong>Username:</strong> $username<br>
                                    <strong>Temporary Password:</strong> $temp_password<br>
                                    <strong>Status:</strong> Active";

                // Clear form
                $formData = ['full_name' => '', 'email' => '', 'phone' => '', 'facility_id' => ''];

            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
                error_log("Pharmacist registration error: " . $e->getMessage());
            }
        }
    }
}

// Fetch facilities
$facilities = [];
try {
    $stmt = $db->query("
        SELECT f.facility_id, f.official_name, d.district_name 
        FROM veterinary_facilities f
        JOIN districts d ON f.district = d.district_code
        ORDER BY d.district_name, f.official_name
    ");
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Failed to load facilities.";
}

include __DIR__ . '/includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-capsule"></i> Register New Pharmacist</h1>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $err): ?>
                        <p class="mb-1"><?= htmlspecialchars($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?= $success_message ?>
                    <div class="mt-3">
                        <a href="add_pharmacist.php" class="btn btn-primary">Add Another</a>
                        <a href="manage_pharmacists.php" class="btn btn-outline-secondary">View All Pharmacists</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-person-plus"></i> Pharmacist Registration Form
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                            <h4>Personal Information</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required>
                                    <div class="invalid-feedback">Full name is required.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($formData['email']) ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($formData['phone']) ?>">
                                </div>
                            </div>

                            <h4>Facility Assignment</h4>
                            <div class="mb-3">
                                <label class="form-label">Facility *</label>
                                <select name="facility_id" class="form-select" required>
                                    <option value="">Select Facility</option>
                                    <?php foreach ($facilities as $f): ?>
                                        <option value="<?= $f['facility_id'] ?>" <?= $formData['facility_id'] == $f['facility_id'] ? 'selected' : '' ?>>
                                            [<?= htmlspecialchars($f['district_name']) ?>] <?= htmlspecialchars($f['official_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="alert alert-info">
                                The system will auto-generate a username & temporary password. The account will be active immediately with the role <strong>Pharmacist</strong>.
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Register Pharmacist
                                </button>
                                <a href="manage_pharmacists.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelector('input[name="phone"]').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
