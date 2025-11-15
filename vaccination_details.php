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

// Check if vaccination ID is provided
if (empty($_GET['id'])) {
    header('Location: vaccinations.php?error=no_vaccination_selected');
    exit;
}

$vaccinationId = $_GET['id'];
$vaccination = null;
$pet = null;
$error = null;

try {
    $db = getDatabaseConnection();
    
    // Get vaccination details with pet information
    $stmt = $db->prepare("
        SELECT v.*, vt.vaccine_name, vt.default_duration_months, p.pet_name, p.pet_id, po.name as owner_name
        FROM vaccinations v
        JOIN vaccine_types vt ON v.vaccine_type_id = vt.vaccine_id
        JOIN pets p ON v.pet_id = p.pet_id
        JOIN pet_owners po ON p.pet_owner_id = po.pet_owner_id
        WHERE v.vaccination_id = ? AND p.pet_owner_id = ?
        LIMIT 1
    ");
    $stmt->execute([$vaccinationId, $_SESSION['pet_owner_id']]);
    $vaccination = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vaccination) {
        header('Location: vaccinations.php?error=vaccination_not_found');
        exit;
    }
    
    // Get pet details for the sidebar
    $stmt = $db->prepare("
        SELECT p.*, s.species_name, b.breed_name
        FROM pets p
        JOIN species s ON p.species_id = s.species_id
        JOIN breeds b ON p.breed_id = b.breed_id
        WHERE p.pet_id = ? AND p.pet_owner_id = ?
        LIMIT 1
    ");
    $stmt->execute([$vaccination['pet_id'], $_SESSION['pet_owner_id']]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Vaccination details error: " . $e->getMessage());
    $error = "Database error. Please try again later.";
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar with pet info -->
        <?php if ($pet): ?>
        <div class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-heart-pulse" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="card-title"><?= htmlspecialchars($pet['pet_name']) ?></h5>
                        <p class="card-text">
                            <span class="badge bg-primary"><?= htmlspecialchars($pet['species_name']) ?></span>
                            <span class="badge bg-secondary"><?= htmlspecialchars($pet['breed_name']) ?></span>
                        </p>
                        <a href="pet_details.php?id=<?= $pet['pet_id'] ?>" class="btn btn-sm btn-outline-primary">
                            View Pet Profile
                        </a>
                    </div>
                </div>
                
                <div class="list-group">
                    <a href="vaccinations.php?pet_id=<?= $pet['pet_id'] ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-shield-plus me-2"></i>All Vaccinations
                    </a>
                    <a href="medical_records.php?pet_id=<?= $pet['pet_id'] ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-medical me-2"></i>Medical Records
                    </a>
                    <a href="appointments.php?pet_id=<?= $pet['pet_id'] ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar-check me-2"></i>Appointments
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Vaccination Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="vaccinations.php?pet_id=<?= $vaccination['pet_id'] ?>" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Vaccinations
                    </a>
                    <a href="edit_vaccination.php?id=<?= $vaccination['vaccination_id'] ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($vaccination): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?= htmlspecialchars($vaccination['vaccine_name']) ?></h5>
                            <span class="badge bg-<?= strtotime($vaccination['next_due_date']) < time() ? 'danger' : 'success' ?>">
                                <?= strtotime($vaccination['next_due_date']) < time() ? 'Expired' : 'Active' ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6>Pet Information</h6>
                                    <p>
                                        <strong>Name:</strong> <?= htmlspecialchars($vaccination['pet_name']) ?><br>
                                        <strong>Owner:</strong> <?= htmlspecialchars($vaccination['owner_name']) ?>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <h6>Vaccination Details</h6>
                                    <p>
                                        <strong>Administered On:</strong> <?= date('F j, Y', strtotime($vaccination['date_administered'])) ?><br>
                                        <strong>Administered By:</strong> <?= htmlspecialchars($vaccination['administered_by']) ?><br>
                                        <strong>Duration:</strong> <?= $vaccination['default_duration_months'] ?> months
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6>Next Due Date</h6>
                                    <p>
                                        <strong>Date:</strong> 
                                        <?= date('F j, Y', strtotime($vaccination['next_due_date'])) ?>
                                        <?php 
                                            $daysLeft = floor((strtotime($vaccination['next_due_date']) - time()) / (60 * 60 * 24));
                                            if ($daysLeft <= 30): 
                                        ?>
                                            <span class="badge bg-<?= $daysLeft <= 7 ? 'danger' : 'warning' ?> ms-2">
                                                <?= $daysLeft ?> days left
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($vaccination['notes'])): ?>
                            <div class="mt-4">
                                <h6>Additional Notes</h6>
                                <div class="p-3 bg-light rounded">
                                    <?= nl2br(htmlspecialchars($vaccination['notes'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted small">
                        Record created on <?= date('F j, Y \a\t g:i a', strtotime($vaccination['created_at'])) ?>
                        <?php if ($vaccination['created_at'] != $vaccination['updated_at']): ?>
                            <br>Last updated on <?= date('F j, Y \a\t g:i a', strtotime($vaccination['updated_at'])) ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Action buttons -->
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
                    <a href="book_appointment.php?pet_id=<?= $vaccination['pet_id'] ?>&type=vaccination" class="btn btn-success me-md-2">
                        <i class="bi bi-calendar-plus"></i> Schedule Next Dose
                    </a>
                    <a href="edit_vaccination.php?id=<?= $vaccination['vaccination_id'] ?>" class="btn btn-primary me-md-2">
                        <i class="bi bi-pencil"></i> Edit Record
                    </a>
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="bi bi-trash"></i> Delete
                    </button>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this vaccination record? This action cannot be undone.</p>
                <p><strong>Vaccine:</strong> <?= htmlspecialchars($vaccination['vaccine_name'] ?? '') ?></p>
                <p><strong>Date:</strong> <?= !empty($vaccination['date_administered']) ? date('F j, Y', strtotime($vaccination['date_administered'])) : '' ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="delete_vaccination.php" method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="vaccination_id" value="<?= $vaccination['vaccination_id'] ?? '' ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>