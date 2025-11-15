<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['pharmacy_logged_in']) && empty($_SESSION['staff_logged_in'])) {
    header('Location: pharmacy_login.php');
    exit;
}

$pet_id = '';
$prescriptions = [];
$error = '';
$pet_details = null;
$db = getDatabaseConnection();

// Handle AJAX save calls
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $med_name = trim($_POST['med_name'] ?? '');
    $pet = trim($_POST['pet_id'] ?? '');
    $status = $_POST['status'] ?? null;

    if ($action === 'save_status' && $pet && $med_name && in_array($status, ['available', 'unavailable'])) {
        try {
            $stmt = $db->prepare("INSERT INTO medicine_status (pet_id, medication_name, status) 
                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE
                status = VALUES(status), updated_at = NOW()");
            $stmt->execute([$pet, $med_name, $status]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

// Load pet and records for GET request
if (isset($_GET['pet_id']) && trim($_GET['pet_id']) !== '') {
    $pet_id = strtoupper(trim($_GET['pet_id']));
    try {
        $stmt = $db->prepare("SELECT p.pet_id, p.pet_name, s.species_name, b.breed_name 
                              FROM pets p
                              LEFT JOIN species s ON p.species_id = s.species_id
                              LEFT JOIN breeds b ON p.breed_id = b.breed_id
                              WHERE p.pet_id=?");
        $stmt->execute([$pet_id]);
        $pet_details = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pet_details) {
            $stmt = $db->prepare("SELECT mr.record_id, mr.record_date, mr.diagnosis, 
                                         mr.treatment, mr.medications, fs.full_name AS doctor_name
                                  FROM medical_records mr
                                  LEFT JOIN facility_staff fs ON mr.attending_staff_id=fs.staff_id
                                  WHERE mr.pet_id=?
                                  ORDER BY mr.record_date DESC");
            $stmt->execute([$pet_id]);
            $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = '❌ No pet found for ID: ' . htmlspecialchars($pet_id);
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

include __DIR__ . '/includes/header.php';
?>

<style>
.medicine-btn-group {
    display: flex;
    gap: 10px;
}
.medicine-toggle-btn {
    min-width: 120px;
    border-radius: 7px;
    border: 2px solid #198754;
    color: #198754;
    background: #fff;
    font-weight: 500;
    padding: 4px 14px;
    transition: all 0.18s;
    outline: none;
    cursor: pointer;
}
.medicine-toggle-btn.notav {
    border-color: #dc3545;
    color: #dc3545;
}
.medicine-toggle-btn.selected.av {
    background: #198754;
    color: #fff;
    border-color: #198754;
    box-shadow: 0 0 0 2px #d4edda99;
}
.medicine-toggle-btn.selected.notav {
    background: #dc3545;
    color: #fff;
    border-color: #dc3545;
    box-shadow: 0 0 0 2px #f8d7da99;
}
.medicine-toggle-btn:focus {
    box-shadow: 0 0 0 3px #ffe484;
    z-index: 2;
}
</style>

<div class="container mt-4 mb-5">
    <h2 class="mb-4"><i class="bi bi-journal-medical me-2"></i> Pet Prescription Lookup</h2>

    <form class="row g-3 mb-4 align-items-center" method="get">
        <div class="col-md-6">
            <label for="pet_id" class="form-label">Enter Pet ID</label>
            <input type="text" class="form-control form-control-lg" name="pet_id" id="pet_id"
                   value="<?= htmlspecialchars($pet_id) ?>" placeholder="e.g. PET12345" required>
        </div>
        <div class="col-md-auto d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-lg ms-2">
                <i class="bi bi-search"></i> Fetch Pet
            </button>
        </div>
    </form>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($pet_details): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-paw"></i> <?= htmlspecialchars($pet_details['pet_name']) ?>
                    <small class="text-muted"><?= htmlspecialchars($pet_details['species_name'] . ' / ' . $pet_details['breed_name']) ?></small>
                </h5>
                <p class="mb-0"><strong>Pet ID:</strong> <?= htmlspecialchars($pet_id) ?></p>
            </div>
        </div>

        <?php if (empty($prescriptions)): ?>
            <div class="alert alert-info">ℹ️ No prescriptions found for this pet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle shadow-sm">
                    <thead class="table-primary">
                        <tr>
                            <th style="width:70px;">Sl. No</th>
                            <th style="width:150px;">Date</th>
                            <th>Diagnosis</th>
                            <th>Treatment</th>
                            <th>Medications</th>
                            <th>Prescribed By</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($prescriptions as $i => $r):
                        $medList = preg_split('/[,;]+/', $r['medications']);
                    ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= date('d-M-Y', strtotime($r['record_date'])) ?></td>
                            <td><?= nl2br(htmlspecialchars($r['diagnosis'])) ?></td>
                            <td><?= nl2br(htmlspecialchars($r['treatment'])) ?></td>
                            <td>
                                <?php foreach ($medList as $medName):
                                    $medName = trim($medName);
                                    if ($medName === '') continue;

                                    // Get medicine status for this prescription/pet
                                    $stmt = $db->prepare('SELECT status FROM medicine_status WHERE pet_id = ? AND medication_name = ?');
                                    $stmt->execute([$pet_id, $medName]);
                                    $currentStatus = ($stmt->fetchColumn()) ?: '';
                                    $uniq = md5($pet_id . $medName . $i . microtime());
                                ?>
                                <div class="d-flex align-items-center mb-2">
                                    <strong class="me-3 flex-shrink-0" style="min-width:95px"><?= htmlspecialchars($medName) ?></strong>
                                    <div class="medicine-btn-group">
                                        <button type="button"
                                            class="medicine-toggle-btn av<?= ($currentStatus === 'available') ? ' selected' : '' ?>"
                                            onclick="setMedStatus('<?= addslashes($pet_id) ?>', '<?= addslashes($medName) ?>', 'available', this)">
                                            Available
                                        </button>
                                        <button type="button"
                                            class="medicine-toggle-btn notav<?= ($currentStatus === 'unavailable') ? ' selected' : '' ?>"
                                            onclick="setMedStatus('<?= addslashes($pet_id) ?>', '<?= addslashes($medName) ?>', 'unavailable', this)">
                                            Not Available
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </td>
                            <td><?= htmlspecialchars($r['doctor_name'] ?? 'Unknown') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function setMedStatus(pet_id, med_name, status, btn) {
    $.post(window.location.href, {
        action: 'save_status',
        pet_id: pet_id,
        med_name: med_name,
        status: status
    }, function(response) {
        if (response.success) {
            // Change styles for this group
            var btnGroup = $(btn).parent();
            btnGroup.find('.medicine-toggle-btn').removeClass('selected');
            // Set only this one as selected
            $(btn).addClass('selected');
        } else {
            alert('Failed to update status: ' + (response.error || 'Unknown error'));
        }
    }, 'json');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
