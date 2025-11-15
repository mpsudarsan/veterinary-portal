<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDatabaseConnection();
$errors = [];
$success = false;

// Fetch all pet owners to select from
try {
    $ownersStmt = $db->query("SELECT pet_owner_id, owner_code, name FROM pet_owners ORDER BY name");
    $owners = $ownersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Could not load pet owners: " . $e->getMessage();
}

// Fetch vaccines
try {
    $vacStmt = $db->query("SELECT vaccine_id, vaccine_name FROM vaccine_types ORDER BY vaccine_name");
    $vaccines = $vacStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Could not load vaccines: " . $e->getMessage();
}

$pets = [];
if (!empty($_POST['pet_owner_id'])) {
    $stmt = $db->prepare("SELECT pet_id, pet_name FROM pets WHERE pet_owner_id = ? ORDER BY pet_name");
    $stmt->execute([$_POST['pet_owner_id']]);
    $pets = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_vaccination'])) {
    $pet_owner_id = $_POST['pet_owner_id'] ?? '';
    $pet_id = $_POST['pet_id'] ?? '';
    $vaccine_id = $_POST['vaccine_id'] ?? '';
    $date = $_POST['preferred_date'] ?? '';
    $time = $_POST['preferred_time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    $token_num = date('dmYis');
    
    if (!$pet_owner_id || !$pet_id || !$vaccine_id || !$date || !$time) {
        $errors[] = "All required fields must be selected.";
    }
    if ($date < date('Y-m-d')) {
        $errors[] = "Date cannot be in the past.";
    }

    $facility_id = 1; // fallback

    if (empty($errors)) {
        try {
            $insert = $db->prepare("
                INSERT INTO appointments
                (pet_owner_id, pet_id, vaccine_id, facility_id, appointment_type, preferred_date, preferred_time, additional_notes, token_number, status, created_by)
                VALUES (?, ?, ?, ?, 'Vaccination', ?, ?, ?, ?, 'Pending', ?)
            ");
            $insert->execute([
                $pet_owner_id,
                $pet_id,
                $vaccine_id,
                $facility_id,
                $date,
                $time,
                $notes,
                $token_num,
                $pet_owner_id
            ]);
            $success = true;
        } catch (PDOException $e) {
            $errors[] = "Booking failed: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($success): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Vaccination Booked!',
    text: 'The appointment is pending confirmation.',
    confirmButtonText: 'Back to Staff Dashboard'
}).then(() => {
    window.location.href = 'staff_dashboard.php';
});
</script>
<?php endif; ?>

<div class="container py-4">
    <h2>Book Vaccination Appointment</h2>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?>
            <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="post" id="bookingForm" class="needs-validation" novalidate>
        <div class="row g-3 mb-3">
            <!-- First row: Pet Owner + Pet -->
            <div class="col-md-6">
                <label for="pet_owner_id" class="form-label">Select Pet Owner <span class="text-danger">*</span></label>
                <select name="pet_owner_id" id="pet_owner_id" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Select Owner --</option>
                    <?php foreach ($owners as $owner): ?>
                        <option value="<?= htmlspecialchars($owner['pet_owner_id']) ?>" <?= (($_POST['pet_owner_id'] ?? '') == $owner['pet_owner_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($owner['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please select a pet owner.</div>
            </div>

            <div class="col-md-6">
                <label for="pet_id" class="form-label">Select Pet <span class="text-danger">*</span></label>
                <select name="pet_id" id="pet_id" class="form-select" required <?= empty($pets) ? 'disabled' : '' ?>>
                    <option value="">-- Select Pet --</option>
                    <?php foreach ($pets as $pet): ?>
                        <option value="<?= htmlspecialchars($pet['pet_id']) ?>" <?= (($_POST['pet_id'] ?? '') == $pet['pet_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pet['pet_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please select a pet.</div>
            </div>
        </div>
        <div class="row g-3 mb-3">
            <!-- Second row: Vaccine + Preferred Date -->
            <div class="col-md-6">
                <label for="vaccine_id" class="form-label">Select Vaccine <span class="text-danger">*</span></label>
                <select name="vaccine_id" id="vaccine_id" class="form-select" required>
                    <option value="">-- Select Vaccine --</option>
                    <?php foreach ($vaccines as $vac): ?>
                        <option value="<?= htmlspecialchars($vac['vaccine_id']) ?>" <?= (($_POST['vaccine_id'] ?? '') == $vac['vaccine_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vac['vaccine_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please select a vaccine.</div>
            </div>

            <div class="col-md-6">
                <label for="preferred_date" class="form-label">Preferred Date <span class="text-danger">*</span></label>
                <input type="date" name="preferred_date" id="preferred_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['preferred_date'] ?? '') ?>" required>
                <div class="invalid-feedback">Please select a valid date.</div>
            </div>
        </div>
        <div class="row g-3">
            <!-- Third row: Preferred Time + Notes -->
            <div class="col-md-6">
                <label for="preferred_time" class="form-label">Preferred Time <span class="text-danger">*</span></label>
                <input type="time" name="preferred_time" id="preferred_time" class="form-control" value="<?= htmlspecialchars($_POST['preferred_time'] ?? '') ?>" required>
                <div class="invalid-feedback">Please select a time.</div>
            </div>

            <div class="col-md-6">
                <label for="notes" class="form-label">Additional Notes</label>
                <textarea name="notes" id="notes" class="form-control" rows="1"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="mt-3 text-end">
            <button type="submit" name="book_vaccination" class="btn btn-success">Book Vaccination</button>
        </div>
    </form>
</div>

<script>
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', ev => {
            if (!form.checkValidity()) {
                ev.preventDefault();
                ev.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
