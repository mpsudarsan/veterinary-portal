<?php
session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

// Check admin privileges
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}


// Initialize variables
$errors = [];
$success = '';
$formData = [
    'name' => '',
    'type' => '',
    'address' => '',
    'district' => '',
    'district_name' => '',
    'subdistrict' => '',
    'subdistrict_name' => '',
    'village' => '',
    'village_name' => '',
    'pincode' => '',
    'phone' => '',
    'email' => '',
    'is_vaccination_center' => 1
];

// Fetch districts for dropdown
$districts = fetchDistricts();
$subdistricts = [];
$villages = [];
// Handle form submission to get subdistricts and villages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_locations') {
    if (!empty($_POST['district'])) {
        $subdistricts = fetchSubdistricts($_POST['district']);
    }
    if (!empty($_POST['subdistrict'])) {
        $villages = fetchVillages($_POST['subdistrict']);
    }
    exit;
}

// Pre-fill subdistricts and villages if district/subdistrict is selected
if (!empty($_POST['district'])) {
    $subdistricts = fetchSubdistricts($_POST['district']);
    $formData['district'] = $_POST['district'];
}

if (!empty($_POST['subdistrict'])) {
    $villages = fetchVillages($_POST['subdistrict']);
    $formData['subdistrict'] = $_POST['subdistrict'];
}

if (!empty($_POST['village'])) {
    $formData['village'] = $_POST['village'];
}


// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errors[] = "Invalid CSRF token. Please try again.";
    // Optionally regenerate token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'type' => $_POST['type'] ?? '',
        'address' => trim($_POST['address'] ?? ''),
        'district' => $_POST['district'] ?? '',
        'district_name' => '',
        'subdistrict' => $_POST['subdistrict'] ?? '',
        'subdistrict_name' => '',
        'village' => $_POST['village'] ?? '',
        'village_name' => '',
        'pincode' => trim($_POST['pincode'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'is_vaccination_center' => isset($_POST['is_vaccination_center']) ? 1 : 0
    ];

    // Validation rules
    if (empty($formData['name'])) $errors[] = "Hospital name is required";
    if (empty($formData['type'])) $errors[] = "Hospital type is required";
    if (!in_array($formData['type'], ['Veterinary Hospital', 'Veterinary Center', 'Mobile Clinic'])) {
        $errors[] = "Invalid hospital type selected";
    }
    if (empty($formData['address'])) $errors[] = "Address is required";
    if (empty($formData['district'])) $errors[] = "District is required";
    if (empty($formData['subdistrict'])) $errors[] = "Subdistrict is required";
    if (empty($formData['village'])) $errors[] = "Village is required";
    if (empty($formData['pincode'])) $errors[] = "Pincode is required";
    elseif (!preg_match('/^[0-9]{6}$/', $formData['pincode'])) $errors[] = "Pincode must be 6 digits";
    if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (!empty($formData['phone']) && !preg_match('/^[0-9]{10,15}$/', $formData['phone'])) {
        $errors[] = "Phone number must be 10-15 digits";
    }

    // Proceed if no errors
    if (empty($errors)) {
        try {
            // Get location names for the codes
            $stmt = $db->prepare("SELECT district_name FROM districts WHERE district_code = ?");
            $stmt->execute([$formData['district']]);
            $formData['district_name'] = $stmt->fetchColumn();
            
            $stmt = $db->prepare("SELECT subdistrict_name FROM subdistricts WHERE subdistrict_code = ?");
            $stmt->execute([$formData['subdistrict']]);
            $formData['subdistrict_name'] = $stmt->fetchColumn();
            
            $stmt = $db->prepare("SELECT village_name FROM villages WHERE village_code = ? AND subdistrict_code = ?");
            $stmt->execute([$formData['village'], $formData['subdistrict']]);
            $formData['village_name'] = $stmt->fetchColumn();
            
            // Generate hospital ID (format: HOS-YYYYMMDD-XXXXXX)
            $datePart = date('Ymd');
            $randomPart = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $hospital_id = 'HOS-' . $datePart . '-' . $randomPart;
            
            // Insert into database
            $stmt = $db->prepare("INSERT INTO hospitals (
                hospital_id, name, type, address, district_code, district_name,
                subdistrict_code, subdistrict_name, pincode, phone, email, is_vaccination_center
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $hospital_id,
                $formData['name'],
                $formData['type'],
                $formData['address'],
                $formData['district'],
                $formData['district_name'],
                $formData['subdistrict'],
                $formData['subdistrict_name'],
                $formData['pincode'],
                $formData['phone'],
                $formData['email'],
                $formData['is_vaccination_center']
            ]);
            
            $success = "Hospital added successfully! Hospital ID: " . $hospital_id;
            // Clear form data
            $formData = array_fill_keys(array_keys($formData), '');
        } catch (PDOException $e) {
            $errors[] = "System error. Please try again later.";
            error_log("Database error: " . $e->getMessage());
        }
    }
}

?>
<?php include __DIR__ . '/includes/header.php'; ?>
 <style> 
 /* Main layout structure */
    .admin-container {
        display: flex;
        min-height: 100vh;
    }
    
    /* Sidebar styling - matches your admin_sidebar.php */
    .sidebar-wrapper {
        width: 250px; /* Matches Bootstrap's col-md-3 col-lg-2 */
        min-height: 100vh;
        background-color: #1a4b8c; /* Government blue */
        position: sticky;
        top: 0;
    }
    
    /* Main content area */
    #main-content {
        flex: 1;
        padding: 20px;
        background-color: #f8f9fa; /* Light background */
    }
         
        .form-box {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 800px;
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
    </style>
</head>
<body>
    <div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                    </div>
                </div>
            </div>
    <!-- Main Content -->
    <div class="container py-4" id="main-content">
        <div class="form-box">
            <h3 class="text-center mb-4" style="color: var(--govt-blue);">Add New Hospital/Center</h3>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-1"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-section">
                    <h5 class="mb-3">Hospital Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label required-field">Hospital/Center Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($formData['name']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label required-field">Hospital Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="Veterinary Hospital" <?= $formData['type'] == 'Veterinary Hospital' ? 'selected' : '' ?>>Veterinary Hospital</option>
                                <option value="Veterinary Center" <?= $formData['type'] == 'Veterinary Center' ? 'selected' : '' ?>>Veterinary Center</option>
                                <option value="Mobile Clinic" <?= $formData['type'] == 'Mobile Clinic' ? 'selected' : '' ?>>Mobile Clinic</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_vaccination_center" name="is_vaccination_center" value="1" <?= $formData['is_vaccination_center'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_vaccination_center">
                            Vaccination Center
                        </label>
                    </div>
                </div>
                
            <div class="form-section">
                <h5 class="mb-3">Address Information</h5>
                <div class="mb-3">
                    <label for="address" class="form-label required-field">Full Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2" required><?= 
                        htmlspecialchars($formData['address']) ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="district" class="form-label required-field">District</label>
                        <select class="form-select" id="district" name="district" required>
                            <option value="">Select District</option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?= $district['district_code'] ?>" <?= 
                                    $formData['district'] == $district['district_code'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($district['district_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="subdistrict" class="form-label required-field">Subdistrict</label>
                        <select class="form-select" id="subdistrict" name="subdistrict" required <?= empty($subdistricts) ? 'disabled' : '' ?>>
    <option value="">Select Subdistrict</option>
    <?php foreach ($subdistricts as $subdistrict): ?>
        <option value="<?= $subdistrict['subdistrict_code'] ?>" <?= 
            $formData['subdistrict'] == $subdistrict['subdistrict_code'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($subdistrict['subdistrict_name']) ?>
        </option>
    <?php endforeach; ?>
</select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="village" class="form-label required-field">Village</label>
                        <select class="form-select" id="village" name="village" required <?= empty($villages) ? 'disabled' : '' ?>>
    <option value="">Select Village</option>
    <?php foreach ($villages as $village): ?>
        <option value="<?= $village['village_code'] ?>" <?= 
            $formData['village'] == $village['village_code'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($village['village_name']) ?>
        </option>
    <?php endforeach; ?>
</select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="pincode" class="form-label required-field">Pincode</label>
                        <input type="text" class="form-control" id="pincode" name="pincode" 
                               value="<?= htmlspecialchars($formData['pincode']) ?>" maxlength="6" required>
                    </div>
                </div>
            </div>
            
                
                <div class="form-section">
                    <h5 class="mb-3">Contact Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?= htmlspecialchars($formData['phone']) ?>" maxlength="15">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($formData['email']) ?>">
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Add Hospital</button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // When district changes, enable subdistrict and submit form
    $('#district').change(function() {
        $('#subdistrict').prop('disabled', false);
        $('<input>').attr({
            type: 'hidden',
            name: 'get_locations',
            value: '1'
        }).appendTo('form');
        $('form').submit();
    });

    // When subdistrict changes, enable village and submit form
    $('#subdistrict').change(function() {
        $('#village').prop('disabled', false);
        $('<input>').attr({
            type: 'hidden',
            name: 'get_locations',
            value: '1'
        }).appendTo('form');
        $('form').submit();
    });

    // Input validation
    $('#phone, #pincode').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9]/g, ''));
    });

    // Disable form submission for location updates
    $('form').submit(function(e) {
        if ($('input[name="get_locations"]').length > 0) {
            e.preventDefault();
            $('input[name="get_locations"]').remove();
            this.submit();
        }
    });
});
</script>
</body>
</html>