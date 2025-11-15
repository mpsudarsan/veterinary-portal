<?php
// Only for staff - not owners!
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['staff_logged_in']) || empty($_SESSION['staff_id'])) {
    header('Location: staff_login.php');
    exit;
}

$db = getDatabaseConnection();

// Get pet_id from URL (MUST be a string, e.g. PET-1008)
$pet_id = $_GET['pet_id'] ?? '';
if (!$pet_id || !preg_match('/^PET-\w+$/i', $pet_id)) {
    die("Invalid Pet ID.");
}

// Fetch pet details (adapt fields as per your table)
$petStmt = $db->prepare("
    SELECT p.pet_id, p.pet_name, s.species_name, b.breed_name, p.date_of_birth, po.name AS owner_name, po.phone AS owner_phone
    FROM pets p
    LEFT JOIN species s ON p.species_id = s.species_id
    LEFT JOIN breeds b ON p.breed_id = b.breed_id
    JOIN pet_owners po ON p.pet_owner_id = po.pet_owner_id
    WHERE p.pet_id = ?
");
$petStmt->execute([$pet_id]);
$pet = $petStmt->fetch(PDO::FETCH_ASSOC);

if (!$pet) {
    die("Pet not found.");
}

$dob = !empty($pet['date_of_birth']) ? new DateTime($pet['date_of_birth']) : null;
$age = $dob ? $dob->diff(new DateTime())->y . " years" : "N/A";

// Get all medical records for this pet
$medStmt = $db->prepare("
    SELECT record_id, visit_date, diagnosis, treatment, notes, vet_name
    FROM medical_records
    WHERE pet_id = ?
    ORDER BY visit_date DESC
");
$medStmt->execute([$pet_id]);
$medical_records = $medStmt->fetchAll(PDO::FETCH_ASSOC);

// Get all vaccinations for this pet
$vaxStmt = $db->prepare("
    SELECT v.vaccination_id, vt.vaccine_name, v.date_administered, v.administered_by
    FROM vaccinations v
    JOIN vaccine_types vt ON v.vaccine_type_id = vt.vaccine_id
    WHERE v.pet_id = ?
    ORDER BY v.date_administered DESC
");
$vaxStmt->execute([$pet_id]);
$vaccinations = $vaxStmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between">
            <div>
                <h4><?= htmlspecialchars($pet['pet_name']) ?> 
                    <small class="text-muted">(<?= htmlspecialchars($pet['species_name']) ?>/<?= htmlspecialchars($pet['breed_name']) ?>)</small>
                </h4>
                <p><strong>Pet ID:</strong> <?= htmlspecialchars($pet['pet_id']) ?><br>
                   <strong>Age:</strong> <?= htmlspecialchars($age) ?><br>
                   <strong>Owner:</strong> <?= htmlspecialchars($pet['owner_name']) ?> (<?= htmlspecialchars($pet['owner_phone']) ?>)
                </p>
            </div>
            <div>
                <a href="generate_medical_pdf.php?pet_id=<?= urlencode($pet_id) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-download"></i> Export PDF
                </a>
                <a href="staff_dashboard.php" class="btn btn-sm btn-secondary ms-2">Back</a>
            </div>
        </div>
    </div>

    <!-- Add record buttons -->
    <div class="mb-3">
        <a href="add_medical_record.php?pet_id=<?= urlencode($pet_id) ?>" class="btn btn-success btn-sm me-2">
            <i class="bi bi-plus-circle"></i> Add Medical Record
        </a>
        <a href="add_vaccination.php?pet_id=<?= urlencode($pet_id) ?>" class="btn btn-info btn-sm">
            <i class="bi bi-plus-circle"></i> Add Vaccination
        </a>
    </div>

    <ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#medicalTab">Medical History</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#vaxTab">Vaccination History</a></li>
    </ul>

    <div class="tab-content mt-3">
        <!-- Medical records -->
        <div class="tab-pane fade show active" id="medicalTab">
            <?php if (empty($medical_records)): ?>
                <div class="alert alert-warning">No medical records yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Diagnosis</th>
                                <th>Treatment</th>
                                <th>Veterinarian</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($medical_records as $rec): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($rec['visit_date'])) ?></td>
                                <td><?= htmlspecialchars($rec['diagnosis']) ?></td>
                                <td><?= htmlspecialchars($rec['treatment']) ?></td>
                                <td><?= htmlspecialchars($rec['vet_name']) ?></td>
                                <td><?= nl2br(htmlspecialchars($rec['notes'])) ?></td>
                                <td>
                                    <a href="edit_medical_record.php?id=<?= $rec['record_id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                                    <a href="delete_medical_record.php?id=<?= $rec['record_id'] ?>" onclick="return confirm('Delete this record?')" class="btn btn-sm btn-outline-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <!-- Vaccination history -->
        <div class="tab-pane fade" id="vaxTab">
            <?php if (empty($vaccinations)): ?>
                <div class="alert alert-warning">No vaccination records yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Vaccine</th>
                                <th>Date Administered</th>
                                <th>Administered By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($vaccinations as $vax): ?>
                            <tr>
                                <td><?= htmlspecialchars($vax['vaccine_name']) ?></td>
                                <td><?= date('M j, Y', strtotime($vax['date_administered'])) ?></td>
                                <td><?= htmlspecialchars($vax['administered_by']) ?></td>
                                <td>
                                    <a href="edit_vaccination.php?id=<?= $vax['vaccination_id'] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                                    <a href="delete_vaccination.php?id=<?= $vax['vaccination_id'] ?>" onclick="return confirm('Delete this vaccination?')" class="btn btn-sm btn-outline-danger">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
