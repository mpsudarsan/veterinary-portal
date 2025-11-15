<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/staff_functions.php';
require_once __DIR__ . '/includes/csrf.php';

// Add this at the top of the file
function getDatabaseConnection() {
    global $conn;
    return $conn; // Or implement PDO connection if preferred
}

// Start session and validate doctor login
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['doctor_logged_in'])) {
    header('Location: doctor_login.php');
    exit;
}

$staff_id = $_SESSION['staff_id'];
$facility_id = $_SESSION['facility_id'];

// Check if record ID is provided
if (!isset($_GET['id'])) {
    header('Location: patients.php');
    exit;
}

$record_id = $_GET['id'];

// Fetch the medical record details
$record = getMedicalRecordById($record_id, $facility_id);
if (!$record) {
    $_SESSION['error_message'] = "Medical record not found or you don't have permission to access it.";
    header('Location: patients.php');
    exit;
}

// Fetch pet details
$pet = getPetById($record['pet_id']);
if (!$pet) {
    $_SESSION['error_message'] = "Pet not found.";
    header('Location: patients.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header("Location: edit_medical_record.php?id=$record_id");
        exit;
    }

    // Sanitize and validate input
    $diagnosis = trim($_POST['diagnosis']);
    $treatment = trim($_POST['treatment']);
    $notes = trim($_POST['notes']);
    $medications = trim($_POST['medications']);
    $record_type = $_POST['record_type'];
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    $temperature = !empty($_POST['temperature']) ? floatval($_POST['temperature']) : null;
    $heart_rate = !empty($_POST['heart_rate']) ? intval($_POST['heart_rate']) : null;
    $respiratory_rate = !empty($_POST['respiratory_rate']) ? intval($_POST['respiratory_rate']) : null;

    // Basic validation
    if (empty($diagnosis)) {
        $_SESSION['error_message'] = "Diagnosis is required.";
        header("Location: edit_medical_record.php?id=$record_id");
        exit;
    }

    // Update the medical record
    $success = updateMedicalRecord(
        $record_id,
        $diagnosis,
        $treatment,
        $notes,
        $medications,
        $record_type,
        $weight,
        $temperature,
        $heart_rate,
        $respiratory_rate,
        $staff_id
    );

    if ($success) {
        $_SESSION['success_message'] = "Medical record updated successfully!";
        header("Location: patients.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Failed to update medical record. Please try again.";
        header("Location: edit_medical_record.php?id=$record_id");
        exit;
    }
}

// Generate new CSRF token
$csrf_token = generateCsrfToken();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/doctor_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Medical Record</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="patients.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Patients
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-file-medical"></i> Edit Record for <?= htmlspecialchars($pet['pet_name']) ?>
                        </h5>
                        <span class="badge bg-primary">Record ID: <?= $record_id ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" action="edit_medical_record.php?id=<?= $record_id ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Patient Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center mb-3">
                                            <?php if (!empty($pet['profile_picture'])): ?>
                                                <img src="<?= htmlspecialchars($pet['profile_picture']) ?>" class="rounded-circle mb-2" width="80" height="80" alt="<?= htmlspecialchars($pet['pet_name']) ?>">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-secondary mb-2 d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                                                    <i class="bi bi-heart-fill text-white" style="font-size: 1.5rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <h5><?= htmlspecialchars($pet['pet_name']) ?></h5>
                                            <p class="text-muted">
                                                <?= htmlspecialchars($pet['species_name']) ?> | 
                                                <?= htmlspecialchars($pet['breed_name']) ?>
                                            </p>
                                        </div>
                                        
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Sex:</span>
                                                <span><?= htmlspecialchars($pet['sex']) ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Age:</span>
                                                <span><?= htmlspecialchars($pet['age_value'] . ' ' . $pet['age_unit']) ?></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Vital Signs</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Weight (kg)</label>
                                                <input type="number" step="0.1" class="form-control" name="weight" 
                                                       value="<?= !empty($record['weight']) ? htmlspecialchars($record['weight']) : '' ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Temperature (°C)</label>
                                                <input type="number" step="0.1" class="form-control" name="temperature" 
                                                       value="<?= !empty($record['temperature']) ? htmlspecialchars($record['temperature']) : '' ?>">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Heart Rate (bpm)</label>
                                                <input type="number" class="form-control" name="heart_rate" 
                                                       value="<?= !empty($record['heart_rate']) ? htmlspecialchars($record['heart_rate']) : '' ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Respiratory Rate (bpm)</label>
                                                <input type="number" class="form-control" name="respiratory_rate" 
                                                       value="<?= !empty($record['respiratory_rate']) ? htmlspecialchars($record['respiratory_rate']) : '' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Record Details</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Record Type</label>
                                    <select class="form-select" name="record_type" required>
                                        <option value="Checkup" <?= $record['record_type'] === 'Checkup' ? 'selected' : '' ?>>Checkup</option>
                                        <option value="Vaccination" <?= $record['record_type'] === 'Vaccination' ? 'selected' : '' ?>>Vaccination</option>
                                        <option value="Surgery" <?= $record['record_type'] === 'Surgery' ? 'selected' : '' ?>>Surgery</option>
                                        <option value="Emergency" <?= $record['record_type'] === 'Emergency' ? 'selected' : '' ?>>Emergency</option>
                                        <option value="Other" <?= $record['record_type'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Diagnosis <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="diagnosis" rows="3" required><?= htmlspecialchars($record['diagnosis']) ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Treatment</label>
                                    <textarea class="form-control" name="treatment" rows="3"><?= htmlspecialchars($record['treatment']) ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes" rows="2"><?= htmlspecialchars($record['notes']) ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Medications & Prescriptions</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Prescribed Medications</label>
                                    <textarea class="form-control" name="medications" rows="4" placeholder="Enter medications, dosages, and instructions..."><?= htmlspecialchars($record['medications']) ?></textarea>
                                    <small class="text-muted">Format: Medication Name - Dosage - Frequency - Duration (e.g., Amoxicillin - 10mg/kg - BID - 7 days)</small>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> For complex prescriptions, consider using the prescription module for more detailed tracking.
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="patients.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Medical Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>