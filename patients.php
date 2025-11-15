<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/staff_functions.php';
require_once __DIR__ . '/includes/csrf.php';

// Start session and validate doctor login
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['doctor_logged_in']) || !$_SESSION['doctor_logged_in']) {
    header('Location: doctor_login.php');
    exit;
}

$staff_id = $_SESSION['staff_id'];
$facility_id = $_SESSION['facility_id'];
$facility_name = $_SESSION['facility_name'];

// Get filter parameters
$search = $_GET['search'] ?? '';
$pet_name = $_GET['pet_name'] ?? '';
$owner_name = $_GET['owner_name'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$record_type = $_GET['record_type'] ?? '';
$species = $_GET['species'] ?? '';

// Get patient visits with medical records
$patients = getPatientVisitsWithRecords($facility_id, $search, $pet_name, $owner_name, $date_from, $date_to, $record_type, $species);

// Get species for filter dropdown
$speciesList = getSpeciesList();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/doctor_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Patient Medical History</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                    <a href="add_medical_record.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Record
                    </a>
                </div>
            </div>

            <!-- Filter Modal -->
            <div class="modal fade" id="filterModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filter Patients</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="get" action="patients.php">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Search (Pet/Owner/Diagnosis)</label>
                                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Species</label>
                                        <select class="form-select" name="species">
                                            <option value="">All Species</option>
                                            <?php foreach ($speciesList as $spec): ?>
                                                <option value="<?= $spec['species_id'] ?>" <?= $species === $spec['species_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($spec['species_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Pet Name</label>
                                        <input type="text" class="form-control" name="pet_name" value="<?= htmlspecialchars($pet_name) ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Owner Name</label>
                                        <input type="text" class="form-control" name="owner_name" value="<?= htmlspecialchars($owner_name) ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date From</label>
                                        <input type="date" class="form-control" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Date To</label>
                                        <input type="date" class="form-control" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Record Type</label>
                                        <select class="form-select" name="record_type">
                                            <option value="">All Types</option>
                                            <option value="Checkup" <?= $record_type === 'Checkup' ? 'selected' : '' ?>>Checkup</option>
                                            <option value="Vaccination" <?= $record_type === 'Vaccination' ? 'selected' : '' ?>>Vaccination</option>
                                            <option value="Surgery" <?= $record_type === 'Surgery' ? 'selected' : '' ?>>Surgery</option>
                                            <option value="Emergency" <?= $record_type === 'Emergency' ? 'selected' : '' ?>>Emergency</option>
                                            <option value="Other" <?= $record_type === 'Other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <a href="patients.php" class="btn btn-secondary">Reset</a>
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Patient List -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-people"></i> Patient Medical Records
                        </h5>
                        <span class="badge bg-primary"><?= count($patients) ?> records</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($patients)): ?>
                        <div class="alert alert-info">No medical records found matching your criteria.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Pet Details</th>
                                        <th>Owner Info</th>
                                        <th>Type</th>
                                        <th>Diagnosis</th>
                                        <th>Medications</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $record): ?>
                                        <tr>
                                            <td>
                                                <?= date('M j, Y', strtotime($record['record_date'])) ?>
                                                <?php if (!empty($record['appointment_id'])): ?>
                                                    <br><small class="text-muted">Token: <?= str_pad($record['token_number'], 3, '0', STR_PAD_LEFT) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($record['profile_picture'])): ?>
                                                        <img src="<?= htmlspecialchars($record['profile_picture']) ?>" class="rounded-circle me-2" width="40" height="40" alt="<?= htmlspecialchars($record['pet_name']) ?>">
                                                    <?php else: ?>
                                                        <div class="rounded-circle bg-secondary me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                            <i class="bi bi-heart-fill text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?= htmlspecialchars($record['pet_name']) ?></strong>
                                                        <div class="text-muted small">
                                                            <?= htmlspecialchars($record['species_name']) ?> | 
                                                            <?= htmlspecialchars($record['breed_name']) ?> | 
                                                            <?= htmlspecialchars($record['sex']) ?>
                                                            <?= $record['neutered'] ? ' (Neutered)' : '' ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($record['owner_name']) ?></strong>
                                                <div class="text-muted small">
                                                    <?= htmlspecialchars($record['owner_phone']) ?>
                                                    <?php if (!empty($record['owner_email'])): ?>
                                                        <br><?= htmlspecialchars($record['owner_email']) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getRecordTypeColor($record['record_type']) ?>">
                                                    <?= htmlspecialchars($record['record_type']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars(truncateText($record['diagnosis'], 30)) ?></td>
                                            <td>
                                                <?php if (!empty($record['medications'])): ?>
                                                    <?= htmlspecialchars(truncateText($record['medications'], 30)) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">None</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#recordModal<?= $record['record_id'] ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Record Details Modal -->
                                        <div class="modal fade" id="recordModal<?= $record['record_id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-xl">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title">
                                                            Medical Record - <?= htmlspecialchars($record['pet_name']) ?> (<?= htmlspecialchars($record['species_name']) ?>)
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row mb-4">
                                                            <div class="col-md-4">
                                                                <div class="card h-100">
                                                                    <div class="card-header bg-light">
                                                                        <h6 class="mb-0">Patient Information</h6>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        <div class="text-center mb-3">
                                                                            <?php if (!empty($record['profile_picture'])): ?>
                                                                                <img src="<?= htmlspecialchars($record['profile_picture']) ?>" class="rounded-circle mb-2" width="100" height="100" alt="<?= htmlspecialchars($record['pet_name']) ?>">
                                                                            <?php else: ?>
                                                                                <div class="rounded-circle bg-secondary mb-2 d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px;">
                                                                                    <i class="bi bi-heart-fill text-white" style="font-size: 2rem;"></i>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <h5><?= htmlspecialchars($record['pet_name']) ?></h5>
                                                                            <p class="text-muted">
                                                                                <?= htmlspecialchars($record['species_name']) ?> | 
                                                                                <?= htmlspecialchars($record['breed_name']) ?>
                                                                            </p>
                                                                        </div>
                                                                        
                                                                        <ul class="list-group list-group-flush">
                                                                            <li class="list-group-item d-flex justify-content-between">
                                                                                <span>Sex:</span>
                                                                                <span><?= htmlspecialchars($record['sex']) ?></span>
                                                                            </li>
                                                                            <li class="list-group-item d-flex justify-content-between">
                                                                                <span>Neutered:</span>
                                                                                <span><?= $record['neutered'] ? 'Yes' : 'No' ?></span>
                                                                            </li>
                                                                            <li class="list-group-item d-flex justify-content-between">
                                                                                <span>Age:</span>
                                                                                <span><?= htmlspecialchars($record['age_value'] . ' ' . $record['age_unit']) ?></span>
                                                                            </li>
                                                                            <li class="list-group-item d-flex justify-content-between">
                                                                                <span>Weight:</span>
                                                                                <span><?= !empty($record['weight']) ? htmlspecialchars($record['weight']) . ' kg' : 'N/A' ?></span>
                                                                            </li>
                                                                            <li class="list-group-item d-flex justify-content-between">
                                                                                <span>Color:</span>
                                                                                <span><?= !empty($record['color']) ? htmlspecialchars($record['color']) : 'N/A' ?></span>
                                                                            </li>
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="col-md-8">
                                                                <div class="card h-100">
                                                                    <div class="card-header bg-light">
                                                                        <h6 class="mb-0">Medical Details</h6>
                                                                    </div>
                                                                    <div class="card-body">
                                                                        <div class="mb-4">
                                                                            <h6 class="border-bottom pb-2">Visit Information</h6>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <p><strong>Date:</strong> <?= date('F j, Y', strtotime($record['record_date'])) ?></p>
                                                                                    <p><strong>Record Type:</strong> <span class="badge bg-<?= getRecordTypeColor($record['record_type']) ?>"><?= htmlspecialchars($record['record_type']) ?></span></p>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <p><strong>Attending Staff:</strong> <?= !empty($record['staff_name']) ? htmlspecialchars($record['staff_name']) : 'N/A' ?></p>
                                                                                    <?php if (!empty($record['appointment_id'])): ?>
                                                                                        <p><strong>Token Number:</strong> <?= str_pad($record['token_number'], 3, '0', STR_PAD_LEFT) ?></p>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="mb-4">
                                                                            <h6 class="border-bottom pb-2">Owner Information</h6>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <p><strong>Name:</strong> <?= htmlspecialchars($record['owner_name']) ?></p>
                                                                                    <p><strong>Phone:</strong> <?= htmlspecialchars($record['owner_phone']) ?></p>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <?php if (!empty($record['owner_email'])): ?>
                                                                                        <p><strong>Email:</strong> <?= htmlspecialchars($record['owner_email']) ?></p>
                                                                                    <?php endif; ?>
                                                                                    <p><strong>Registered Since:</strong> <?= date('M j, Y', strtotime($record['registration_date'])) ?></p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div class="mb-4">
                                                                            <h6 class="border-bottom pb-2">Medical Information</h6>
                                                                            <p><strong>Diagnosis:</strong></p>
                                                                            <div class="bg-light p-3 rounded mb-3">
                                                                                <?= nl2br(htmlspecialchars($record['diagnosis'])) ?>
                                                                            </div>
                                                                            
                                                                            <p><strong>Treatment:</strong></p>
                                                                            <div class="bg-light p-3 rounded mb-3">
                                                                                <?= nl2br(htmlspecialchars($record['treatment'])) ?>
                                                                            </div>
                                                                            
                                                                            <p><strong>Notes:</strong></p>
                                                                            <div class="bg-light p-3 rounded mb-3">
                                                                                <?= nl2br(htmlspecialchars($record['notes'])) ?>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <div>
                                                                            <h6 class="border-bottom pb-2">Prescribed Medications</h6>
                                                                            <?php if (!empty($record['medications'])): ?>
                                                                                <div class="bg-light p-3 rounded">
                                                                                    <?= nl2br(htmlspecialchars($record['medications'])) ?>
                                                                                </div>
                                                                            <?php else: ?>
                                                                                <p class="text-muted">No medications prescribed</p>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <a href="edit_medical_record.php?id=<?= $record['record_id'] ?>" class="btn btn-primary">
                                                            <i class="bi bi-pencil"></i> Edit Record
                                                        </a>
                                                        <a href="pet_medical.php?pet_id=<?= $record['pet_id'] ?>" class="btn btn-info">
                                                            <i class="bi bi-clipboard-pulse"></i> Full History
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination would go here -->
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>