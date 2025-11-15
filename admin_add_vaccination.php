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

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$errors = [];
$success = '';
$formData = [
    'vaccine_name' => '',
    'vaccine_type' => 'new',
    'description' => '',
    'species' => '',
    'initial_dose_time' => '',
    'initial_dose_unit' => 'days',
    'has_additional_doses' => 0,
    'dose_details' => [],
    'available_hospitals' => []
];

try {
    $db = getDatabaseConnection();
    
    // Check if vaccine_types table exists with proper structure
    $db->exec("CREATE TABLE IF NOT EXISTS vaccine_types (
        vaccine_id INT AUTO_INCREMENT PRIMARY KEY,
        vaccine_name VARCHAR(100) NOT NULL,
        type ENUM('new', 'variation') NOT NULL,
        description TEXT,
        species VARCHAR(10) NOT NULL,
        initial_dose_time INT NOT NULL,
        initial_dose_unit ENUM('days', 'weeks', 'months', 'years') NOT NULL,
        has_additional_doses BOOLEAN NOT NULL DEFAULT 0,
        dose_details JSON,
        available_hospitals JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        default_duration_months INT DEFAULT 12,
        FOREIGN KEY (species) REFERENCES species(species_id)
    )");
    
    // Fetch species from database
    $speciesOptions = $db->query("SELECT species_id, species_name FROM species ORDER BY species_name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch hospitals for dropdown
    $hospitals = $db->query("SELECT facility_id, official_name FROM veterinary_facilities WHERE is_active = 1 ORDER BY official_name")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
    error_log("Database error: " . $e->getMessage());
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Please try again.";
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Basic vaccine info
        $formData['vaccine_name'] = trim($_POST['vaccine_name'] ?? '');
        $formData['vaccine_type'] = $_POST['vaccine_type'] ?? 'new';
        $formData['description'] = trim($_POST['description'] ?? '');
        $formData['species'] = $_POST['species'] ?? '';
        $formData['initial_dose_time'] = intval($_POST['initial_dose_time'] ?? 0);
        $formData['initial_dose_unit'] = $_POST['initial_dose_unit'] ?? 'days';
        $formData['has_additional_doses'] = isset($_POST['has_additional_doses']) ? 1 : 0;
        $formData['available_hospitals'] = $_POST['available_hospital'] ?? [];
        
        // Validation
        if (empty($formData['vaccine_name'])) {
            $errors[] = "Vaccine name is required";
        }
        
        if (empty($formData['species'])) {
            $errors[] = "Species must be selected";
        }
        
        if ($formData['initial_dose_time'] <= 0) {
            $errors[] = "Initial dose time must be greater than 0";
        }
        
        if (empty($formData['available_hospitals'])) {
            $errors[] = "Please select at least one facility";
        }
        
        // Process dose details if additional doses exist
        if ($formData['has_additional_doses']) {
            $doseCount = 0;
            $i = 1;
            while (isset($_POST['dose_time_'.$i])) {
                $doseTime = intval($_POST['dose_time_'.$i] ?? 0);
                $doseUnit = $_POST['dose_unit_'.$i] ?? 'days';
                
                if ($doseTime > 0) {
                    $formData['dose_details'][$i] = [
                        'time' => $doseTime,
                        'unit' => $doseUnit,
                        'description' => trim($_POST['dose_description_'.$i] ?? '')
                    ];
                    $doseCount++;
                }
                $i++;
            }
            
            if ($formData['has_additional_doses'] && $doseCount == 0) {
                $errors[] = "Please add at least one additional dose";
            }
        }
        
        // If no errors, proceed to save
        if (empty($errors)) {
            try {
                // Prepare data for database
                $doseDetailsJson = $formData['has_additional_doses'] ? json_encode($formData['dose_details']) : json_encode([]);
                $hospitalsJson = json_encode((array)$formData['available_hospitals']);
                
                // Insert into database
                $stmt = $db->prepare("INSERT INTO vaccine_types (
                    vaccine_name, type, description, species, initial_dose_time, initial_dose_unit,
                    has_additional_doses, dose_details, available_hospitals, default_duration_months
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $formData['vaccine_name'],
                    $formData['vaccine_type'],
                    $formData['description'],
                    $formData['species'],
                    $formData['initial_dose_time'],
                    $formData['initial_dose_unit'],
                    $formData['has_additional_doses'],
                    $doseDetailsJson,
                    $hospitalsJson,
                    12 // Default duration
                ]);
                
                $vaccine_id = $db->lastInsertId();
                $_SESSION['success_message'] = "Vaccine added successfully! Vaccine ID: $vaccine_id";
                header("Location: admin_vaccine_success.php?id=$vaccine_id");
                exit;
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
                error_log("Database error: " . $e->getMessage());
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div class="btn-toolbar mb-2 mb-md-0">
                </div>
            </div>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-syringe"></i> Add New Vaccine</h1>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-1"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="card admin-card">
                <div class="card-header" style="background-color: var(--govt-blue); color: white;">
                    <i class="bi bi-syringe"></i> Vaccine Information
                </div>
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        
                        <!-- Basic Vaccine Information -->
                        <div class="form-section">
                            <h5 class="mb-3">Vaccine Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="vaccine_name" class="form-label required-field">Vaccine Name</label>
                                    <input type="text" class="form-control" id="vaccine_name" name="vaccine_name" 
                                           value="<?= htmlspecialchars($formData['vaccine_name']) ?>" required>
                                    <div class="invalid-feedback">
                                        Please provide the vaccine name.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="vaccine_type" class="form-label required-field">Vaccine Type</label>
                                    <select class="form-select" id="vaccine_type" name="vaccine_type" required>
                                        <option value="new" <?= $formData['vaccine_type'] == 'new' ? 'selected' : '' ?>>New Vaccine</option>
                                        <option value="variation" <?= $formData['vaccine_type'] == 'variation' ? 'selected' : '' ?>>Variation of Existing Vaccine</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($formData['description']) ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Species Information -->
                        <div class="form-section">
                            <h5 class="mb-3">Target Species</h5>
                            <div class="mb-3">
                                <label for="species" class="form-label required-field">Select species:</label>
                                <select class="form-select" id="species" name="species" required>
                                    <option value="">-- Select a species --</option>
                                    <?php foreach ($speciesOptions as $species): ?>
                                        <option value="<?= htmlspecialchars($species['species_id']) ?>"
                                            <?= $formData['species'] == $species['species_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($species['species_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a species.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Initial Dose Information -->
                        <div class="form-section">
                            <h5 class="mb-3">Initial Dose Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="initial_dose_time" class="form-label required-field">Time after birth for initial dose</label>
                                    <input type="number" class="form-control" id="initial_dose_time" name="initial_dose_time" 
                                           value="<?= htmlspecialchars($formData['initial_dose_time']) ?>" min="1" required>
                                    <div class="invalid-feedback">
                                        Please enter a valid time (greater than 0).
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="initial_dose_unit" class="form-label required-field">Time unit</label>
                                    <select class="form-select" id="initial_dose_unit" name="initial_dose_unit" required>
                                        <option value="days" <?= $formData['initial_dose_unit'] == 'days' ? 'selected' : '' ?>>Days</option>
                                        <option value="weeks" <?= $formData['initial_dose_unit'] == 'weeks' ? 'selected' : '' ?>>Weeks</option>
                                        <option value="months" <?= $formData['initial_dose_unit'] == 'months' ? 'selected' : '' ?>>Months</option>
                                        <option value="years" <?= $formData['initial_dose_unit'] == 'years' ? 'selected' : '' ?>>Years</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Doses -->
                        <div class="form-section">
                            <h5 class="mb-3">Additional Doses</h5>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="has_additional_doses" 
                                       name="has_additional_doses" value="1"
                                       <?= $formData['has_additional_doses'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="has_additional_doses">
                                    This vaccine requires additional doses
                                </label>
                            </div>
                            
                            <div id="additional_doses_section" style="display: <?= $formData['has_additional_doses'] ? 'block' : 'none' ?>;">
                                <div id="dose_details_container">
                                    <?php if ($formData['has_additional_doses'] && !empty($formData['dose_details'])): ?>
                                        <?php foreach ($formData['dose_details'] as $i => $dose): ?>
                                            <div class="dose-details mb-3" id="dose_<?= $i ?>">
                                                <div class="row">
                                                    <div class="col-md-4 mb-2">
                                                        <label class="form-label">Time after previous dose</label>
                                                        <input type="number" class="form-control" name="dose_time_<?= $i ?>" 
                                                               value="<?= $dose['time'] ?? '' ?>" min="1">
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <label class="form-label">Time unit</label>
                                                        <select class="form-select" name="dose_unit_<?= $i ?>">
                                                            <option value="days" <?= ($dose['unit'] ?? '') == 'days' ? 'selected' : '' ?>>Days</option>
                                                            <option value="weeks" <?= ($dose['unit'] ?? '') == 'weeks' ? 'selected' : '' ?>>Weeks</option>
                                                            <option value="months" <?= ($dose['unit'] ?? '') == 'months' ? 'selected' : '' ?>>Months</option>
                                                            <option value="years" <?= ($dose['unit'] ?? '') == 'years' ? 'selected' : '' ?>>Years</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <label class="form-label">Description (optional)</label>
                                                        <input type="text" class="form-control" name="dose_description_<?= $i ?>" 
                                                               value="<?= htmlspecialchars($dose['description'] ?? '') ?>">
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-danger btn-sm remove-dose" data-dose="<?= $i ?>">
                                                    <i class="bi bi-trash"></i> Remove
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" id="add_dose_btn" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus"></i> Add Dose
                                </button>
                            </div>
                        </div>
                        
                        <!-- Available Hospitals -->
                        <div class="form-section">
                            <h5 class="mb-3">Availability</h5>
                            <div class="mb-3">
                                <label class="form-label required-field">Select veterinary facility:</label>
                                <select class="form-select" name="available_hospital" required>
                                    <option value="">-- Select a facility --</option>
                                    <?php foreach ($hospitals as $hospital): ?>
                                        <option value="<?= $hospital['facility_id'] ?>"
                                            <?= $formData['available_hospitals'] == $hospital['facility_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($hospital['official_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a facility.
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Add Vaccine</button>
                        </div>
                    </form>
                </div>
            </div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Toggle additional doses section
    $('#has_additional_doses').change(function() {
        $('#additional_doses_section').toggle(this.checked);
        if (!this.checked) {
            $('#dose_details_container').empty();
        }
    });
    
    let doseCounter = <?= $formData['has_additional_doses'] ? count($formData['dose_details']) : 0 ?>;
    
    // Add new dose
    $('#add_dose_btn').click(function() {
        doseCounter++;
        const newDose = `
            <div class="dose-details mb-3" id="dose_${doseCounter}">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Time after previous dose</label>
                        <input type="number" class="form-control" name="dose_time_${doseCounter}" min="1" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Time unit</label>
                        <select class="form-select" name="dose_unit_${doseCounter}" required>
                            <option value="days">Days</option>
                            <option value="weeks">Weeks</option>
                            <option value="months">Months</option>
                            <option value="years">Years</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Description (optional)</label>
                        <input type="text" class="form-control" name="dose_description_${doseCounter}">
                    </div>
                </div>
                <button type="button" class="btn btn-danger btn-sm remove-dose" data-dose="${doseCounter}">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
        `;
        $('#dose_details_container').append(newDose);
    });
    
    // Remove dose
    $(document).on('click', '.remove-dose', function() {
        const doseId = $(this).data('dose');
        $(`#dose_${doseId}`).remove();
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
</script>
</body>
</html>