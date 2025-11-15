<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/staff_functions.php';
require_once __DIR__ . '/includes/csrf.php';

// Start session and validate staff login
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['staff_logged_in']) || !$_SESSION['staff_logged_in']) {
    header('Location: staff_login.php');
    exit;
}

$staff_id = $_SESSION['staff_id'];
$facility_id = $_SESSION['facility_id'];
$facility_name = $_SESSION['facility_name'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['new_status'];
    
    if (updateAppointmentStatus($appointment_id, $new_status, $staff_id)) {
        // Automatically create medical record when status is set to Completed
        if ($new_status === 'Completed') {
            createMedicalRecordFromAppointment($appointment_id, $staff_id);
        }
        
        $_SESSION['success_message'] = "Appointment status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update appointment status.";
    }
    header("Location: staff_dashboard.php");
    exit;
}

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
// Helper function for status badge classes
function getEmergencyStatusClass($status) {
    $classes = [
        'pending' => 'bg-warning text-dark',
        'dispatched' => 'bg-info text-dark',
        'resolved' => 'bg-success',
        'cancelled' => 'bg-secondary'
    ];
    return $classes[strtolower($status)] ?? 'bg-secondary';
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/staff_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Staff Dashboard</h1>
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

            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

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
                                                                        
                                                                        <!-- Status Change Dropdown -->
                                                                        <div class="mt-3">
                                                                            <label class="form-label"><strong>Change Status:</strong></label>
                                                                            <select class="form-select status-select" 
                                                                                    data-appointment-id="<?= $appt['appointment_id'] ?>"
                                                                                    <?= $appt['status'] === 'Completed' ? 'disabled' : '' ?>>
                                                                                <option value="Pending" <?= $appt['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                                                <option value="Confirmed" <?= $appt['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                                                <option value="Completed" <?= $appt['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                                                <option value="Cancelled" <?= $appt['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                                            </select>
                                                                            <div class="status-feedback mt-2 small text-muted"></div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6>Pet Info</h6>
                                                                        <p><strong>Name:</strong> <?= htmlspecialchars($appt['pet_name']) ?></p>
                                                                        <p><strong>Species/Breed:</strong> <?= htmlspecialchars($appt['species_name']) ?>/<?= htmlspecialchars($appt['breed_name']) ?></p>
                                                                        <p><strong>Age:</strong> <?= htmlspecialchars($appt['age_value'] . ' ' . $appt['age_unit']) ?></p>
                                                                        <p><strong>Owner:</strong> <?= htmlspecialchars($appt['owner_name']) ?></p>
                                                                        
                                                                        <!-- Quick Actions -->
                                                                        <?php if ($appt['status'] !== 'Completed'): ?>
                                                                        <div class="mt-3">
                                                                            <button class="btn btn-sm btn-outline-primary complete-now-btn" 
                                                                                    data-appointment-id="<?= $appt['appointment_id'] ?>">
                                                                                <i class="bi bi-check-circle"></i> Mark as Completed
                                                                            </button>
                                                                        </div>
                                                                        <?php endif; ?>
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
            
            <!-- Section 4: Footer Actions (PDF/Medical Record) -->
         <div class="modal-footer">
    <a href="generate_appointment_pdf.php?appointment_id=<?= urlencode($appt['appointment_id']) ?>" 
       class="btn btn-primary">
        <i class="bi bi-download"></i> Download PDF
    </a>
    <a href="pet_medical.php?pet_id=<?= urlencode($appt['pet_id']) ?>&appointment_id=<?= urlencode($appt['appointment_id']) ?>" 
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
            <!-- Emergency Reports Section -->
<div class="card border-danger shadow-sm mb-4">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-semibold">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Emergency Reports
        </h5>
        <a href="emergency_report.php" class="btn btn-sm btn-light">
            <i class="bi bi-plus-circle me-1"></i>New Report
        </a>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($emergency_reports)): ?>
            <div class="alert alert-info m-3">No emergency reports in the last 24 hours.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Reported</th>
                            <th>Emergency Type</th>
                            <th>Animal</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emergency_reports as $report): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-medium"><?= date('h:i A', strtotime($report['created_at'])) ?></div>
                                    <div class="text-muted small"><?= date('M j, Y', strtotime($report['created_at'])) ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="fw-medium"><?= ucfirst(htmlspecialchars($report['incident_type'])) ?></span>
                                        <?php if (!empty($report['description'])): ?>
                                            <i class="bi bi-info-circle text-primary ms-2" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top"
                                               title="<?= htmlspecialchars($report['description']) ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= ucfirst(htmlspecialchars($report['animal_type'])) ?></td>
                                <td class="text-truncate" style="max-width: 180px;">
                                    <?= htmlspecialchars($report['location']) ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill py-1 px-2 <?= getEmergencyStatusClass($report['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($report['status'])) ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-outline-primary px-3"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#emergencyModal<?= $report['id'] ?>">
                                        <i class="bi bi-eye me-1"></i>View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Emergency Report Modals -->
<?php foreach ($emergency_reports as $report): ?>
<div class="modal fade" id="emergencyModal<?= $report['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Emergency Report #<?= $report['id'] ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <div class="row g-3">
                    <!-- Incident Details -->
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold mb-3 text-danger">Incident Details</h6>
                            <div class="mb-3">
                                <span class="text-muted small d-block">Emergency Type</span>
                                <p class="fw-medium mb-0"><?= ucfirst($report['incident_type']) ?></p>
                            </div>
                            <div class="mb-3">
                                <span class="text-muted small d-block">Animal Type</span>
                                <p class="fw-medium mb-0"><?= ucfirst($report['animal_type']) ?></p>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Current Status</span>
                                <p class="fw-medium mb-0">
                                    <span class="badge <?= getEmergencyStatusClass($report['status']) ?>">
                                        <?= ucfirst($report['status']) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Information -->
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold mb-3 text-danger">Contact Information</h6>
                            <div class="mb-3">
                                <span class="text-muted small d-block">Reporter Phone</span>
                                <p class="fw-medium mb-0"><?= htmlspecialchars($report['reporter_phone']) ?></p>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Reported At</span>
                                <p class="fw-medium mb-0"><?= date('M j, Y \a\t h:i A', strtotime($report['created_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Location Details -->
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h6 class="fw-bold mb-2 text-danger">Location Details</h6>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($report['location'])) ?></p>
                        </div>
                    </div>
                    
                    <!-- Description (if available) -->
                    <?php if (!empty($report['description'])): ?>
                    <div class="col-12">
                        <div class="border rounded p-3">
                            <h6 class="fw-bold mb-2 text-danger">Additional Details</h6>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($report['description'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Status Update Section -->
                    <div class="col-12">
                        <div class="border rounded p-3 bg-light">
                            <h6 class="fw-bold mb-3">Update Emergency Status</h6>
                            <div class="row g-2 align-items-center">
                                <div class="col-md-9">
                                    <select class="form-select emergency-status-select" 
                                            data-report-id="<?= $report['id'] ?>">
                                        <option value="pending" <?= $report['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="dispatched" <?= $report['status'] === 'dispatched' ? 'selected' : '' ?>>Dispatched</option>
                                        <option value="resolved" <?= $report['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                        <option value="cancelled" <?= $report['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-danger w-100 update-status-btn" 
                                            data-report-id="<?= $report['id'] ?>">
                                        Update
                                    </button>
                                </div>
                            </div>
                            <div class="emergency-status-feedback mt-2 small"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>


<!-- JavaScript for Status Updates -->
<script>
// Emergency report status update handler
$(document).ready(function() {
    // Merge both document.ready handlers into one
    handleEmergencyStatusUpdates();
    handleAppointmentStatusUpdates();
});

function handleEmergencyStatusUpdates() {
    $('.emergency-status-select').change(function() {
        const $select = $(this);
        const reportId = $select.data('report-id');
        const newStatus = $select.val();
        const $feedbackEl = $select.siblings('.emergency-status-feedback');
        
        showLoadingFeedback($feedbackEl);
        
        updateEmergencyStatus(reportId, newStatus)
            .then(response => {
                if (response.success) {
                    updateEmergencyStatusUI(reportId, newStatus, $feedbackEl);
                } else {
                    showErrorFeedback($feedbackEl, response.error || 'Update failed');
                }
            })
            .catch(() => {
                showErrorFeedback($feedbackEl, 'Network error');
            });
    });
}

function handleAppointmentStatusUpdates() {
    $('.status-select').change(function() {
        const $select = $(this);
        const appointmentId = $select.data('appointment-id');
        const newStatus = $select.val();
        const $feedbackEl = $select.siblings('.status-feedback');
        
        showLoadingFeedback($feedbackEl);
        
        updateAppointmentStatus(appointmentId, newStatus)
            .then(response => {
                if (response.success) {
                    updateAppointmentStatusUI(appointmentId, newStatus, $feedbackEl);
                    if (newStatus === 'Completed') {
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    showErrorFeedback($feedbackEl, response.error || 'Update failed');
                }
            })
            .catch(() => {
                showErrorFeedback($feedbackEl, 'Network error');
            });
    });

    $('.complete-now-btn').click(function() {
        const appointmentId = $(this).data('appointment-id');
        $(`.status-select[data-appointment-id="${appointmentId}"]`)
            .val('Completed')
            .trigger('change');
    });
}

// Helper functions
function updateEmergencyStatus(reportId, newStatus) {
    return $.ajax({
        url: 'api/update_emergency_status.php',
        method: 'POST',
        data: {
            report_id: reportId,
            new_status: newStatus,
            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
        }
    });
}

function updateAppointmentStatus(appointmentId, newStatus) {
    return $.ajax({
        url: 'api/update_appointment_status.php',
        method: 'POST',
        data: {
            appointment_id: appointmentId,
            new_status: newStatus,
            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
        }
    });
}

function updateEmergencyStatusUI(reportId, newStatus, $feedbackEl) {
    // Update feedback
    $feedbackEl.html('<span class="text-success"><i class="bi bi-check-circle"></i> Status updated</span>');
    
    // Update badge in modal
    const $modalBadge = $(`.emergency-status-select[data-report-id="${reportId}"]`)
        .closest('.modal-body')
        .find('.badge');
    
    updateBadge($modalBadge, newStatus, getEmergencyStatusBadgeClass);
    
    // Update badge in table
    const $tableBadge = $(`button[data-bs-target="#emergencyModal${reportId}"]`)
        .closest('tr')
        .find('.badge');
    
    updateBadge($tableBadge, newStatus, getEmergencyStatusBadgeClass);
}

function updateAppointmentStatusUI(appointmentId, newStatus, $feedbackEl) {
    $feedbackEl.html('<span class="text-success"><i class="bi bi-check-circle"></i> Status updated</span>');
    
    const $badge = $(`.status-select[data-appointment-id="${appointmentId}"]`)
        .closest('.modal-body')
        .find('.badge');
    
    updateBadge($badge, newStatus, getStatusBadgeClass);
    
    if (newStatus === 'Completed') {
        $(`.status-select[data-appointment-id="${appointmentId}"]`).prop('disabled', true);
        $(`.complete-now-btn[data-appointment-id="${appointmentId}"]`).remove();
    }
}

function updateBadge($badge, status, statusClassFn) {
    $badge.removeClass('bg-warning bg-info bg-success bg-primary bg-danger bg-secondary')
         .addClass(statusClassFn(status))
         .text(status.charAt(0).toUpperCase() + status.slice(1));
}

function showLoadingFeedback($element) {
    $element.html('<i class="bi bi-arrow-repeat spinner"></i> Updating...');
}

function showErrorFeedback($element, message) {
    $element.html(`<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> ${message}</span>`);
}

function getEmergencyStatusBadgeClass(status) {
    const statusClasses = {
        'pending': 'bg-warning',
        'dispatched': 'bg-info',
        'resolved': 'bg-success',
        'cancelled': 'bg-secondary'
    };
    return statusClasses[status] || 'bg-secondary';
}

function getStatusBadgeClass(status) {
    const statusClasses = {
        'Pending': 'bg-warning',
        'Confirmed': 'bg-success',
        'Completed': 'bg-primary',
        'Cancelled': 'bg-danger'
    };
    return statusClasses[status] || 'bg-secondary';
}
</script>
<?php include 'includes/footer.php'; ?>
