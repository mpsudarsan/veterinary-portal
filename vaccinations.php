<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/vaccination_functions.php';
require_once __DIR__ . '/includes/csrf.php';

// Start session and validate user
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['pet_owner_id']) || empty($_SESSION['logged_in'])) {
    header('Location: login.php?reason=not_logged_in');
    exit;
}

$petOwner = getPetOwnerByPetOwnerId($_SESSION['pet_owner_id']);
if (!$petOwner) {
    session_unset();
    session_destroy();
    header('Location: login.php?reason=invalid_user');
    exit;
}

// Initialize variables
$pets = [];
$vaccineTypes = [];
$petVaccinations = [];
$errors = [];
$success_message = '';

// Get pets and vaccine types
try {
    $db = getDatabaseConnection();
    
    // Get all pets
    $stmt = $db->prepare("SELECT pet_id, pet_name FROM pets WHERE pet_owner_id = ? ORDER BY pet_name");
    $stmt->execute([$_SESSION['pet_owner_id']]);
    $pets = $stmt->fetchAll();
    
    // Get vaccine types
    $vaccineTypes = getAllVaccineTypes();
    
    // Get existing vaccinations if pet_id is provided
    if (!empty($_GET['pet_id'])) {
        $petVaccinations = getPetVaccinations($_GET['pet_id'], $_SESSION['pet_owner_id']);
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'])) {
        $formData = [
            'pet_id' => $_POST['pet_id'] ?? '',
            'vaccine_type_id' => $_POST['vaccine_type_id'] ?? '',
            'date_administered' => $_POST['date_administered'] ?? '',
            'administered_by' => $_POST['administered_by'] ?? '',
            'notes' => $_POST['notes'] ?? ''
        ];
        
        $errors = validateVaccinationForm($formData);
        
        if (empty($errors)) {
            $result = addVaccinationRecord($formData, $_SESSION['pet_owner_id']);
            if ($result['success']) {
                $success_message = "Vaccination record added successfully!";
                $petVaccinations = getPetVaccinations($formData['pet_id'], $_SESSION['pet_owner_id']);
            } else {
                $errors[] = $result['error'] ?? "Failed to add vaccination record";
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Vaccinations error: " . $e->getMessage());
    $errors[] = "Failed to load vaccination data. Please try again later.";
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Vaccination Records</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="book_vaccination.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle"></i> Book Vaccination
                    </a>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Pet Selection -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Select Pet</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="vaccinations.php">
                        <div class="row">
                            <div class="col-md-6">
                                <select name="pet_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Select a Pet --</option>
                                    <?php foreach ($pets as $pet): ?>
                                        <option value="<?= $pet['pet_id'] ?>" <?= (!empty($_GET['pet_id']) && $_GET['pet_id'] == $pet['pet_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($pet['pet_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (!empty($_GET['pet_id'])): ?>
                <!-- Vaccination Records -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Vaccination History</h5>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addVaccinationModal">
                            <i class="bi bi-plus-circle"></i> Add Record
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($petVaccinations)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Vaccine</th>
                                            <th>Date Administered</th>
                                            <th>Next Due Date</th>
                                            <th>Administered By</th>
                                            <th>Notes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($petVaccinations as $vax): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($vax['vaccine_name']) ?></td>
                                                <td><?= date('M j, Y', strtotime($vax['date_administered'])) ?></td>
                                                <td>
                                                    <?= date('M j, Y', strtotime($vax['next_due_date'])) ?>
                                                    <?php 
                                                        $daysLeft = floor((strtotime($vax['next_due_date']) - time()) / (60 * 60 * 24));
                                                        if ($daysLeft <= 30): 
                                                    ?>
                                                        <span class="badge bg-<?= $daysLeft <= 7 ? 'danger' : 'warning' ?> ms-2">
                                                            <?= $daysLeft ?> days
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($vax['administered_by']) ?></td>
                                                <td><?= htmlspecialchars($vax['notes']) ?></td>
                                                <td>
                                                    <a href="edit_vaccination.php?id=<?= $vax['vaccination_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No vaccination records found for this pet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Add Vaccination Modal -->
<div class="modal fade" id="addVaccinationModal" tabindex="-1" aria-labelledby="addVaccinationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="vaccinations.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="pet_id" value="<?= !empty($_GET['pet_id']) ? htmlspecialchars($_GET['pet_id']) : '' ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addVaccinationModalLabel">Add Vaccination Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="vaccine_type_id" class="form-label">Vaccine Type *</label>
                        <select class="form-select" id="vaccine_type_id" name="vaccine_type_id" required>
                            <option value="">-- Select Vaccine --</option>
                            <?php foreach ($vaccineTypes as $type): ?>
                                <option value="<?= $type['vaccine_id'] ?>" <?= (!empty($_POST['vaccine_type_id'])) && $_POST['vaccine_type_id'] == $type['vaccine_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['vaccine_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="date_administered" class="form-label">Date Administered *</label>
                        <input type="date" class="form-control" id="date_administered" name="date_administered" 
                               required max="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($_POST['date_administered'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="administered_by" class="form-label">Administered By *</label>
                        <input type="text" class="form-control" id="administered_by" name="administered_by" 
                               required placeholder="Veterinary clinic or doctor name"
                               value="<?= htmlspecialchars($_POST['administered_by'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>