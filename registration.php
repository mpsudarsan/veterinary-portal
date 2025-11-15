<?php
require_once 'includes/config.php';
require_once 'includes/csrf.php';
require_once 'includes/functions.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$errors = [];
$formData = [
    'name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'district' => '',
    'subdistrict' => '',
    'village' => '',
    'pincode' => '',
    'owner_type' => 'individual'
];

// Fetch districts for dropdown
$districts = fetchDistricts();
$subdistricts = [];
$villages = [];

// Pre-fill if editing (example)
if (!empty($formData['district'])) {
    $subdistricts = fetchSubdistricts($formData['district']);
}

if (!empty($formData['subdistrict'])) {
    $villages = fetchVillages($formData['subdistrict']);
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errors[] = "Invalid CSRF token. Please try again.";
    // Optionally regenerate token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
    
    // Sanitize inputs
    $formData = sanitizeFormInputs($_POST);
    
    // Validate inputs
    $errors = validateRegistrationForm($formData, $_POST['password'], $_POST['confirm_password']);
    
    // Proceed if no errors
    if (empty($errors)) {
        try {
            $db = getDatabaseConnection();
            $db->beginTransaction();
            
            // Insert location
            $locationId = insertLocation($db, $formData);
            
            // Insert pet owner
            $petOwnerId = insertPetOwner($db, $formData, $locationId, $_POST['password']);
            
            $db->commit();
            
            // Set success session and redirect
            setRegistrationSuccessSession(
    $petOwnerId, 
    $formData['name'], 
    $formData['username'],  
    $formData['phone']      
);
            header('Location: success.php');
            exit;
        } catch (PDOException $e) {
            if (isset($db)) $db->rollBack();
            $errors[] = "System error. Please try again later.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container py-4" id="main-content">
    <div class="registration-box">
        <h3 class="text-center mb-4">Pet Owner Registration</h3>
        
        <?php displayErrors($errors); ?>
        
        <form id="registrationForm" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <!-- Owner Type Selection -->
<div class="form-section">
    <h5 class="mb-3">Type of Owner</h5>
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="owner_type" id="ownerIndividual" 
                       value="individual" <?= $formData['owner_type'] === 'individual' ? 'checked' : '' ?>>
                <label class="form-check-label" for="ownerIndividual">
                    Individual
                </label>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="owner_type" id="ownerNGO" 
                       value="ngo" <?= $formData['owner_type'] === 'ngo' ? 'checked' : '' ?>>
                <label class="form-check-label" for="ownerNGO">
                    NGO
                </label>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="owner_type" id="ownerDepartment" 
                       value="department" <?= $formData['owner_type'] === 'department' ? 'checked' : '' ?>>
                <label class="form-check-label" for="ownerDepartment">
                    Department
                </label>
            </div>
        </div>
    </div>
</div>
            <!-- Personal Information Section -->
            <div class="form-section">
                <h5 class="mb-3">Personal Information</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label required-field">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= htmlspecialchars($formData['name']) ?>" required>
                    </div>
                        <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($formData['email']) ?>">
                </div>
            </div>
            
            <!-- Address Information Section -->
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
            
            <!-- Mobile Verification Section -->
<div class="form-section">
    <h5 class="mb-3">Mobile Verification</h5>
    <div class="otp-section">
        <input type="hidden" id="mobileVerified" name="mobileVerified" value="0">
        <div class="row">
            <div class="col-md-8">
                <label for="phone" class="form-label required-field">Mobile Number</label>
                <input type="tel" class="form-control" id="phone" name="phone" 
                       value="<?= htmlspecialchars($formData['phone']) ?>" maxlength="10" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" id="sendOtpBtn" class="btn btn-outline-primary w-100">Send OTP</button>
            </div>
        </div>
        <div id="otpVerification" style="display: none;">
            <div class="row mt-3">
                <div class="col-md-6">
                    <label for="otp" class="form-label">Enter OTP</label>
                    <input type="text" class="form-control" id="otp" name="otp" maxlength="6">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="button" id="verifyOtpBtn" class="btn btn-primary w-100">Verify OTP</button>
                </div>
            </div>
            <div id="otpStatus" class="mt-2"></div>
            <div id="verificationSuccess" class="text-success mt-2" style="display: none;">
                <i class="bi bi-check-circle-fill"></i> Mobile Verified
            </div>
        </div>
    </div>
</div>
            <!-- Account Security Section -->
            <div class="form-section">
                <h5 class="mb-3">Account Security</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label required-field">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= htmlspecialchars($formData['username']) ?>" required>
                        <div id="usernameFeedback" class="form-text"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label required-field">Password</label>
                        <div class="password-field">
                            <input type="password" class="form-control" id="password" name="password" required>
                            
                        </div>
                        <div class="password-requirements">
                            <small class="text-muted">Password must contain:</small>
                            <ul class="list-unstyled">
                                <li id="lengthReq">At least 8 characters</li>
                                <li id="upperReq">One uppercase letter</li>
                                <li id="lowerReq">One lowercase letter</li>
                                <li id="numberReq">One number</li>
                                <li id="specialReq">One special character (@#$%^&*!)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label required-field">Confirm Password</label>
                        <div class="password-field">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            
                        </div>
                        <small id="passwordMatchFeedback" class="form-text"></small>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" id="registerBtn" disabled>Register</button>
            </div>
            <div class="text-center mt-3">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>