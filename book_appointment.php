<?php
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/appointment_functions.php';

// Start session and validate
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['pet_owner_id']) || empty($_SESSION['logged_in'])) {
    header('Location: login.php?reason=not_logged_in');
    exit;
}

$pet_owner_id = $_SESSION['pet_owner_id'];
$errors = [];
$pets = [];
$facilities = [];

// Fetch data
try {
    $db = getDatabaseConnection();
    
    // Get pets
    $stmt = $db->prepare("SELECT pet_id, pet_name FROM pets WHERE pet_owner_id = ? ORDER BY pet_name");
    $stmt->execute([$pet_owner_id]);
    $pets = $stmt->fetchAll();
    
    if (empty($pets)) $errors[] = "You need to register a pet before booking an appointment.";
    
    // Get facilities
    $stmt = $db->prepare("SELECT facility_id, official_name FROM veterinary_facilities WHERE facility_type = 'VH' ORDER BY official_name");
    $stmt->execute();
    $facilities = $stmt->fetchAll();
    
    if (empty($facilities)) $errors[] = "No veterinary facilities available for appointments.";
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "A database error occurred. Please try again.";
}

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'])) {
    $formData = [
        'pet_id' => $_POST['pet_id'] ?? '',
        'facility_id' => $_POST['facility_id'] ?? '',
        'appointment_type' => $_POST['appointment_type'] ?? '',
        'preferred_date' => $_POST['preferred_date'] ?? '',
        'preferred_time' => $_POST['preferred_time'] ?? '',
        'symptoms' => $_POST['symptoms'] ?? '',
        'additional_notes' => $_POST['additional_notes'] ?? ''
    ];
    
    $errors = validateAppointmentForm($formData, $pets, $facilities);
    
    if (empty($errors)) {
       try {
            // Check if appointment is for today
            $isToday = (new DateTime($formData['preferred_date']))->format('Y-m-d') == (new DateTime())->format('Y-m-d');
            
            // Use the stored procedure
//             $sql = "CALL sp_create_appointment_with_token($pet_owner_id, "
//      . $formData['pet_id'] . ", "
//      . $formData['facility_id'] . ", "
//      . $formData['appointment_type'] . ", '"
//      . $formData['preferred_date'] . "', '"
//      . $formData['preferred_time'] . "', '"
//      . $formData['symptoms'] . "', '"
//      . $formData['additional_notes'] . "', @app_id, @token)";

// echo $sql;

                // exit();
            $stmt = $db->prepare("CALL sp_create_appointment_with_token(?, ?, ?, ?, ?, ?, ?, ?, ?, @app_id, @token)");
            $stmt->execute([
                $pet_owner_id,
                $formData['pet_id'],
                $formData['facility_id'],
                $formData['appointment_type'],
                $formData['preferred_date'],
                $formData['preferred_time'],
                $formData['symptoms'],
                $formData['additional_notes'],
                $pet_owner_id
            ]);
            
            // Get the outputs
            $result = $db->query("SELECT @app_id AS appointment_id, @token AS token_number")->fetch();
            
            if ($result && $result['appointment_id']) {
                // Store appointment data in session for success page
                $_SESSION['last_appointment'] = [
                    'appointment_id' => $result['appointment_id'],
                    'pet_id' => $formData['pet_id'],
                    'facility_id' => $formData['facility_id'],
                    'appointment_type' => $formData['appointment_type'],
                    'preferred_date' => $formData['preferred_date'],
                    'preferred_time' => $formData['preferred_time'],
                    'token_number' => $result['token_number'],
                    'status' => 'Pending',
                    'symptoms' => $formData['symptoms'],
                    'additional_notes' => $formData['additional_notes']
                ];
                
                header('Location: appointment_success.php');
                exit;
            } else {
                $errors[] = "Failed to create appointment. Please try again.";
            }
        } catch (PDOException $e) {
            error_log("Appointment booking error: " . $e->getMessage());
            $errors[] = "An error occurred while booking the appointment. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Book New Appointment</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="appointments.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Appointments
                    </a>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($pets)): ?>
                        <div class="alert alert-warning">
                            You need to register a pet before booking an appointment. 
                            <a href="add_pet.php" class="alert-link">Add a pet now</a>.
                        </div>
                    <?php elseif (empty($facilities)): ?>
                        <div class="alert alert-warning">
                            No veterinary facilities are currently available for appointments. 
                            Please check back later.
                        </div>
                    <?php else: ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <form method="POST" action="book_appointment.php">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    
                                    <div class="mb-3">
                                        <label for="pet_id" class="form-label">Select Pet</label>
                                        <select class="form-select" id="pet_id" name="pet_id" required>
                                            <option value="">-- Select Pet --</option>
                                            <?php foreach ($pets as $pet): ?>
                                                <option value="<?= htmlspecialchars($pet['pet_id']) ?>" 
                                                    <?= ($_POST['pet_id'] ?? '') == $pet['pet_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($pet['pet_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="facility_id" class="form-label">Veterinary Facility</label>
                                        <select class="form-select" id="facility_id" name="facility_id" required>
                                            <option value="">-- Select Facility --</option>
                                            <?php foreach ($facilities as $facility): ?>
                                                <option value="<?= htmlspecialchars($facility['facility_id']) ?>" 
                                                    <?= ($_POST['facility_id'] ?? '') == $facility['facility_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($facility['official_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="appointment_type" class="form-label">Appointment Type</label>
                                        <select class="form-select" id="appointment_type" name="appointment_type" required>
                                            <option value="">-- Select Type --</option>
                                            <?php 
                                            $types = ['Checkup', 'Vaccination', 'Surgery', 'Emergency', 'Other'];
                                            foreach ($types as $type): ?>
                                                <option value="<?= $type ?>" 
                                                    <?= ($_POST['appointment_type'] ?? '') == $type ? 'selected' : '' ?>>
                                                    <?= $type ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="preferred_date" class="form-label">Preferred Date</label>
                                            <input type="date" class="form-control" id="preferred_date" name="preferred_date" 
                                                   value="<?= htmlspecialchars($_POST['preferred_date'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="preferred_time" class="form-label">Preferred Time</label>
                                            <input type="time" class="form-control" id="preferred_time" name="preferred_time" 
                                                   value="<?= htmlspecialchars($_POST['preferred_time'] ?? '09:00') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="symptoms" class="form-label">Symptoms (if any)</label>
                                        <textarea class="form-control" id="symptoms" name="symptoms" rows="3"><?= htmlspecialchars($_POST['symptoms'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="additional_notes" class="form-label">Additional Notes</label>
                                        <textarea class="form-control" id="additional_notes" name="additional_notes" rows="3"><?= htmlspecialchars($_POST['additional_notes'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-calendar-plus"></i> Book Appointment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Space before footer -->
            <div style="margin-bottom: 100px;"></div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>