<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// --- Check staff login ---
if (empty($_SESSION['staff_logged_in']) || empty($_SESSION['staff_id'])) {
    header('Location: staff_login.php');
    exit;
}

try {
    $db = getDatabaseConnection();

    // Get staff's assigned facility_id (hospital)
    $staffStmt = $db->prepare("SELECT facility_id FROM facility_staff WHERE staff_id = ?");
    $staffStmt->execute([$_SESSION['staff_id']]);
    $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff || empty($staff['facility_id'])) {
        die("Staff is not assigned to a valid facility/hospital.");
    }
    $facility_id = (int)$staff['facility_id'];

    // Get facility name for header display
    $facNameStmt = $db->prepare("SELECT official_name FROM veterinary_facilities WHERE facility_id = ?");
    $facNameStmt->execute([$facility_id]);
    $facility_row = $facNameStmt->fetch(PDO::FETCH_ASSOC);
    $facility_name = $facility_row['official_name'] ?? 'Facility';

    // Fetch appointments for today & future only, filtering by facility, statuses 'Pending' or 'Confirmed' (feel free to add/remove statuses)
    $today = date('Y-m-d');
    $apptStmt = $db->prepare("
        SELECT 
            a.appointment_id,
            a.token_number,
            a.preferred_date,
            a.preferred_time,
            a.appointment_type,
            a.status,
            a.symptoms,
            a.additional_notes,
            p.pet_id,
            p.pet_name,
            s.species_name,
            b.breed_name,
            po.name AS owner_name,
            po.mobile AS owner_phone
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        LEFT JOIN species s ON p.species_id = s.species_id
        LEFT JOIN breeds b ON p.breed_id = b.breed_id
        JOIN pet_owners po ON a.pet_owner_id = po.pet_owner_id
        WHERE a.facility_id = ?
          AND a.preferred_date >= ?
          AND a.status IN ('Pending', 'Confirmed')
        ORDER BY a.preferred_date ASC, a.preferred_time ASC
    ");
    $apptStmt->execute([$facility_id, $today]);
    $appointments = $apptStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

include 'includes/header.php';
?>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-calendar-check"></i> Today's & Upcoming Appointments - <?= date('F j, Y') ?>
            <span class="badge bg-primary float-end"><?= htmlspecialchars($facility_name) ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($appointments)): ?>
            <div class="alert alert-info">No appointments scheduled for today or upcoming dates.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Token</th>
                            <th>Date</th>
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
                                <td><?= date('M j, Y', strtotime($appt['preferred_date'])) ?></td>
                                <td><?= date('h:i A', strtotime($appt['preferred_time'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($appt['pet_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($appt['species_name'] ?? '') ?>/<?= htmlspecialchars($appt['breed_name'] ?? '') ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($appt['owner_name']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($appt['owner_phone']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($appt['appointment_type']) ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'Pending' => 'bg-warning text-dark',
                                        'Confirmed' => 'bg-success',
                                        'Completed' => 'bg-primary',
                                        'Cancelled' => 'bg-danger'
                                    ][$appt['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?= $status_class ?>">
                                        <?= htmlspecialchars($appt['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Trigger Modal -->
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $appt['appointment_id'] ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>

                                    <!-- Modal -->
                                    <div class="modal fade" id="viewModal<?= $appt['appointment_id'] ?>" tabindex="-1" aria-labelledby="viewModalLabel<?= $appt['appointment_id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="viewModalLabel<?= $appt['appointment_id'] ?>">Appointment Details - Token <?= str_pad($appt['token_number'], 3, '0', STR_PAD_LEFT) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Appointment Info</h6>
                                                            <p><strong>Date:</strong> <?= date('F j, Y', strtotime($appt['preferred_date'])) ?></p>
                                                            <p><strong>Time:</strong> <?= date('h:i A', strtotime($appt['preferred_time'])) ?></p>
                                                            <p><strong>Type:</strong> <?= htmlspecialchars($appt['appointment_type']) ?></p>
                                                            <p><strong>Status:</strong> <span class="badge <?= $status_class ?>"><?= htmlspecialchars($appt['status']) ?></span></p>
                                                            <p><strong>Symptoms/Reason:</strong><br><?= nl2br(htmlspecialchars($appt['symptoms'] ?? '')) ?></p>
                                                            <p><strong>Additional Notes:</strong><br><?= nl2br(htmlspecialchars($appt['additional_notes'] ?? '')) ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Pet Info</h6>
                                                            <p><strong>Name:</strong> <?= htmlspecialchars($appt['pet_name']) ?></p>
                                                            <p><strong>Species/Breed:</strong> <?= htmlspecialchars($appt['species_name'] ?? '') ?>/<?= htmlspecialchars($appt['breed_name'] ?? '') ?></p>
                                                            <p><strong>Owner:</strong> <?= htmlspecialchars($appt['owner_name']) ?></p>
                                                            <p><strong>Phone:</strong> <?= htmlspecialchars($appt['owner_phone']) ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <!-- You can add more actions like Edit, Cancel here -->
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

<?php include 'includes/footer.php'; ?>
