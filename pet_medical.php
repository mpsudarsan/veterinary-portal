<?php
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/csrf.php';
require_once __DIR__.'/includes/staff_functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Helper for AJAX vs normal
function returnAjaxOrRedirect($msg, $pet_id = null, $redirect = null, $success = false) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json');
        echo json_encode($success ? ['success' => true] : ['error' => $msg]);
        exit;
    }
    if ($success) $_SESSION['success_message'] = $msg;
    else $_SESSION['error_message'] = $msg;
    if ($redirect) header("Location: $redirect");
    elseif ($pet_id) header("Location: pet_medical.php?pet_id=$pet_id");
    exit;
}

// Staff login check
if (empty($_SESSION['staff_logged_in']) || !$_SESSION['staff_logged_in']) {
    returnAjaxOrRedirect('Not logged in', null, 'staff_login.php');
}

// Require pet_id
if (empty($_GET['pet_id'])) {
    returnAjaxOrRedirect('No Pet ID provided', null, 'staff_dashboard.php?error=no_pet_id');
}

$pet_id        = $_GET['pet_id'];
$appointment_id = $_GET['appointment_id'] ?? null;
$staff_id      = $_SESSION['staff_id'];
$facility_id   = $_SESSION['facility_id'];
$csrf_token    = generateCSRFToken();

// Initialise record_type to avoid undefined warning
$record_type = '';
$allowed_types = ['Checkup','Vaccination','Surgery','Emergency','Other'];
$map_types = [
    'general'     => 'Checkup',
    'checkup'     => 'Checkup',
    'vaccination' => 'Vaccination',
    'surgery'     => 'Surgery',
    'emergency'   => 'Emergency',
    'followup'    => 'Other'
];

// If appointment_id, try to pre-fill record_type
if (!empty($appointment_id)) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT appointment_type FROM appointments WHERE appointment_id=?");
        $stmt->execute([$appointment_id]);
        $appt_type_raw = strtolower($stmt->fetchColumn() ?: 'other');
        $record_type = $map_types[$appt_type_raw] ?? 'Other';
    } catch (PDOException $e) {
        error_log("Error fetching appointment type: ".$e->getMessage());
        $record_type = 'Other';
    }
}
if (empty($record_type)) {
    $record_type = 'Checkup'; // default
}

// Handle form save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        returnAjaxOrRedirect('Invalid CSRF token', $pet_id);
    }

    if (!$appointment_id && isset($_POST['record_type'])) {
        $raw_type = strtolower($_POST['record_type']);
        $record_type = $map_types[$raw_type] ?? 'Other';
    }

    $record_data = [
        'diagnosis'       => trim($_POST['diagnosis'] ?? ''),
        'treatment'       => trim($_POST['treatment'] ?? ''),
        'medications'     => trim($_POST['medications'] ?? ''),
        'notes'           => trim($_POST['notes'] ?? ''),
        'attending_staff' => $_POST['attending_staff'] ?? null,
        'record_type'     => $record_type
    ];

    if ($record_data['record_type'] !== 'Vaccination' && empty($record_data['diagnosis'])) {
        returnAjaxOrRedirect('Diagnosis is required for non-vaccination records.', $pet_id);
    }

    try {
        $db = getDatabaseConnection();

        // Validate pet exists
        $chk = $db->prepare("SELECT COUNT(*) FROM pets WHERE pet_id=?");
        $chk->execute([$pet_id]);
        if (!$chk->fetchColumn()) {
            returnAjaxOrRedirect('Invalid pet ID', $pet_id);
        }
        // Validate attending staff exists
        if (!empty($record_data['attending_staff'])) {
            $chk = $db->prepare("SELECT COUNT(*) FROM facility_staff 
                                 WHERE staff_id=? AND is_active=1 AND facility_id=?");
            $chk->execute([$record_data['attending_staff'], $facility_id]);
            if (!$chk->fetchColumn()) {
                returnAjaxOrRedirect('Invalid attending staff ID', $pet_id);
            }
        }

        $db->beginTransaction();
        $stmt = $db->prepare("
            INSERT INTO medical_records
              (pet_id, staff_id, attending_staff_id, diagnosis, treatment, medications, notes, record_date, record_type, appointment_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)
        ");
        $stmt->execute([
            $pet_id,
            $staff_id,
            $record_data['attending_staff'],
            $record_data['diagnosis'],
            $record_data['treatment'],
            $record_data['medications'],
            $record_data['notes'],
            $record_data['record_type'],
            $appointment_id
        ]);
        $db->prepare("UPDATE pets SET last_medical_update = NOW() WHERE pet_id=?")->execute([$pet_id]);
        $db->commit();

        returnAjaxOrRedirect('Medical record added successfully!', $pet_id, null, true);

    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log("Medical record insert error: ".$e->getMessage());
        returnAjaxOrRedirect('Database error while adding record.', $pet_id);
    }
}

// Fetch pet, staff, history
try {
    $db = getDatabaseConnection();

    $stmt = $db->prepare("
        SELECT p.*, s.species_name, b.breed_name, p.age_unit, p.age_value, p.weight, po.name AS owner_name
        FROM pets p
        JOIN species s ON p.species_id = s.species_id
        JOIN breeds b ON p.breed_id = b.breed_id
        JOIN pet_owners po ON p.pet_owner_id = po.pet_owner_id
        WHERE p.pet_id=?
    ");
    $stmt->execute([$pet_id]);
    $pet = $stmt->fetch();
    if (!$pet) { header('Location: staff_dashboard.php?error=pet_not_found'); exit; }

    $staff_stmt = $db->prepare("
        SELECT s.staff_id, s.full_name, r.role_name
        FROM facility_staff s
        JOIN staff_roles r ON s.role_id = r.role_id
        WHERE s.facility_id=? 
          AND r.role_name IN ('Veterinarian','Senior Veterinarian','Resident Veterinarian',
                              'Veterinary Surgeon','Veterinary Technician','Laboratory Technician')
          AND s.is_active=1
        ORDER BY r.permission_level DESC, s.full_name ASC
    ");
    $staff_stmt->execute([$facility_id]);
    $medical_staff = $staff_stmt->fetchAll();

    $history_stmt = $db->prepare("
        SELECT mr.*, fs.full_name AS staff_name
        FROM medical_records mr
        LEFT JOIN facility_staff fs ON mr.attending_staff_id=fs.staff_id
        WHERE mr.pet_id=?
        ORDER BY mr.record_date DESC
    ");
    $history_stmt->execute([$pet_id]);
    $medical_history = $history_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Database fetch error: ".$e->getMessage());
    header('Location: staff_dashboard.php?error=db_error');
    exit;
}

include 'includes/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <?php include 'includes/staff_sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
      <?php endif; ?>
      <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
      <?php endif; ?>

      <div class="card mb-4">
        <div class="card-header"><h5>Pet Information</h5></div>
        <div class="card-body">
          <strong>Name:</strong> <?= htmlspecialchars($pet['pet_name']) ?><br>
          <strong>Species:</strong> <?= htmlspecialchars($pet['species_name']) ?><br>
          <strong>Breed:</strong> <?= htmlspecialchars($pet['breed_name']) ?><br>
          <strong>Owner:</strong> <?= htmlspecialchars($pet['owner_name']) ?>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5>Add New Medical Record</h5></div>
        <div class="card-body">
          <form method="POST" action="pet_medical.php?pet_id=<?= htmlspecialchars($pet_id) ?><?= $appointment_id ? '&appointment_id=' . htmlspecialchars($appointment_id) : '' ?>" id="medicalRecordForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Record Type *</label>
                <?php if ($appointment_id): ?>
                  <input type="text" class="form-control" value="<?= htmlspecialchars($record_type) ?>" disabled>
                  <input type="hidden" name="record_type" value="<?= htmlspecialchars($record_type) ?>">
                <?php else: ?>
                  <select class="form-select" id="record_type" name="record_type" required>
                    <?php foreach ($allowed_types as $type): ?>
                      <option value="<?= $type ?>"><?= $type ?></option>
                    <?php endforeach; ?>
                  </select>
                <?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label">Attending Professional *</label>
                <select class="form-select" id="attending_staff" name="attending_staff" required>
                  <option value="">Select</option>
                  <?php foreach ($medical_staff as $staff): ?>
                    <option value="<?= $staff['staff_id'] ?>">
                      <?= htmlspecialchars($staff['full_name']) ?> (<?= htmlspecialchars($staff['role_name']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="mb-3" id="diagnosisField">
              <label class="form-label">Diagnosis *</label>
              <input type="text" class="form-control" id="diagnosis" name="diagnosis">
            </div>
            <div class="mb-3">
              <label class="form-label">Treatment *</label>
              <textarea class="form-control" name="treatment" rows="2"></textarea>
            </div>
            <div class="mb-3" id="medicationsField">
              <label class="form-label">Medications</label>
              <textarea class="form-control" name="medications" rows="2"></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label">Additional Notes</label>
              <textarea class="form-control" name="notes" rows="3"></textarea>
            </div>
            <button type="submit" name="add_record" class="btn btn-primary">Save Record</button>
          </form>
        </div>
      </div>

      <div id="medical-records-container">
        <?php include 'includes/partials/pet_medical_records.php'; ?>
      </div>
    </main>
  </div>
</div>

<script>
document.getElementById('record_type')?.addEventListener('change', function() {
  const rt = this.value.toLowerCase();
  const diag = document.getElementById('diagnosisField');
  const meds = document.getElementById('medicationsField');
  document.getElementById('diagnosis').required = (rt !== 'vaccination');
  diag.style.display = meds.style.display = (rt === 'vaccination') ? 'none' : 'block';
});

document.getElementById('medicalRecordForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const form = this;
  const fd = new FormData(form);
  form.querySelectorAll('.ajax-alert').forEach(el => el.remove());
  fetch(form.action, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(res => res.json())
  .then(data => {
    const alert = document.createElement('div');
    alert.className = 'ajax-alert alert mt-2 ' + (data.success ? 'alert-success' : 'alert-danger');
    alert.textContent = data.success ? 'Medical record added successfully!' : (data.error || 'Failed to add record.');
    form.prepend(alert);
    if (data.success) {
      form.reset();
      loadMedicalRecords();
      setTimeout(() => alert.remove(), 4000);
    } else {
      setTimeout(() => alert.remove(), 6000);
    }
  })
  .catch(err => {
    console.error(err);
    const alert = document.createElement('div');
    alert.className = 'ajax-alert alert alert-danger mt-2';
    alert.textContent = 'Unexpected error from server.';
    form.prepend(alert);
    setTimeout(() => alert.remove(), 6000);
  });
});

function loadMedicalRecords() {
  fetch(`api/get_medical_records.php?pet_id=<?= $pet_id ?>`)
    .then(r => r.text())
    .then(html => document.getElementById('medical-records-container').innerHTML = html);
}
setInterval(loadMedicalRecords, 30000);
</script>

<?php include 'includes/footer.php'; ?>
