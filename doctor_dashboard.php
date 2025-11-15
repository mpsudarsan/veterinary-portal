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

// Get today's appointments
$appointments = getTodaysAppointments($facility_id);

$emergency_reports = getRecentEmergencyReports($facility_id);

// Count appointments by status
$status_counts = [
    'Pending' => 0,
    'Confirmed' => 0,
    'Completed' => 0,
    'Cancelled' => 0
];

foreach ($appointments as $appt) {
    if (!empty($appt['status']) && array_key_exists($appt['status'], $status_counts)) {
        $status_counts[$appt['status']]++;
    } else {
        error_log("Unexpected appointment status: " . $appt['status'] . " for appointment ID: " . $appt['appointment_id']);
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/doctor_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                </div>
            </div>

            <!-- Status Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Appointments</h5>
                            <h2 class="card-text"><?= count($appointments) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Pending</h5>
                            <h2 class="card-text"><?= $status_counts['Pending'] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Confirmed</h5>
                            <h2 class="card-text"><?= $status_counts['Confirmed'] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Cancelled</h5>
                            <h2 class="card-text"><?= $status_counts['Cancelled'] ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Appointments -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-calendar-check"></i> Today's Appointments - <?= date('F j, Y') ?>
                        <span class="badge bg-primary float-end"><?= $facility_name ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($appointments)): ?>
                        <div class="alert alert-info">No appointments scheduled for today.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Token</th>
                                        <th>Time</th>
                                        <th>Pet</th>
                                        <th>Owner</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appt): ?>
                                        <tr>
                                            <td><?= str_pad($appt['token_number'], 3, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= date('h:i A', strtotime($appt['preferred_time'])) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($appt['pet_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($appt['species_name']) ?>/<?= htmlspecialchars($appt['breed_name']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($appt['owner_name']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($appt['owner_phone']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($appt['appointment_type']) ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?= $appt['status'] === 'Pending' ? 'bg-warning' : '' ?>
                                                    <?= $appt['status'] === 'Confirmed' ? 'bg-success' : '' ?>
                                                    <?= $appt['status'] === 'Completed' ? 'bg-primary' : '' ?>
                                                    <?= $appt['status'] === 'Cancelled' ? 'bg-danger' : '' ?>
                                                ">
                                                    <?= htmlspecialchars($appt['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-outline-primary btn-sm" 
                                                        data-bs-toggle="modal" data-bs-target="#viewModal<?= $appt['appointment_id'] ?>">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                                
                                                <!-- View Modal -->
                                                <div class="modal fade" id="viewModal<?= $appt['appointment_id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Appointment Details - Token <?= str_pad($appt['token_number'], 3, '0', STR_PAD_LEFT) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6>Appointment Info</h6>
                                                                        <p><strong>Date:</strong> <?= date('F j, Y', strtotime($appt['preferred_date'])) ?></p>
                                                                        <p><strong>Time:</strong> <?= date('h:i A', strtotime($appt['preferred_time'])) ?></p>
                                                                        <p><strong>Type:</strong> <?= htmlspecialchars($appt['appointment_type']) ?></p>
                                                                        <p><strong>Status:</strong> 
                                                                            <span class="badge 
                                                                                <?= $appt['status'] === 'Pending' ? 'bg-warning' : '' ?>
                                                                                <?= $appt['status'] === 'Confirmed' ? 'bg-success' : '' ?>
                                                                                <?= $appt['status'] === 'Completed' ? 'bg-primary' : '' ?>
                                                                                <?= $appt['status'] === 'Cancelled' ? 'bg-danger' : '' ?>">
                                                                                <?= htmlspecialchars($appt['status']) ?>
                                                                            </span>
                                                                        </p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6>Pet Info</h6>
                                                                        <p><strong>Name:</strong> <?= htmlspecialchars($appt['pet_name']) ?></p>
                                                                        <p><strong>Species/Breed:</strong> <?= htmlspecialchars($appt['species_name']) ?>/<?= htmlspecialchars($appt['breed_name']) ?></p>
                                                                        <p><strong>Age:</strong> <?= htmlspecialchars($appt['age_value'] . ' ' . $appt['age_unit']) ?></p>
                                                                        <p><strong>Owner:</strong> <?= htmlspecialchars($appt['owner_name']) ?></p>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Section 3: Additional Details -->
                                                                <div class="row mt-3">
                                                                    <div class="col-12">
                                                                        <h6>Details</h6>
                                                                        <p><strong>Symptoms/Reason:</strong> <?= nl2br(htmlspecialchars($appt['symptoms'])) ?></p>
                                                                        <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($appt['additional_notes'])) ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Section 4: Footer Actions (Medical Record only) -->
                                                            <div class="modal-footer">
                                                                <a href="pet_medical.php?pet_id=<?= $appt['pet_id'] ?>" 
                                                                   class="btn btn-success">
                                                                    <i class="bi bi-clipboard-plus"></i> Medical Record
                                                                </a>
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                                    </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>