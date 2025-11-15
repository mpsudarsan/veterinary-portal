<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Doctor/staff authentication check (customize as needed)
if (empty($_SESSION['diagnostics_id'])) {
    header('Location: staff_login.php');
    exit;
}

$diagnostics_id = $_SESSION['diagnostics_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pet_id = trim($_POST['pet_id'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // File upload handling:
    if (isset($_FILES['photocopy']) && $_FILES['photocopy']['error'] == 0) {
        $allowed = ['pdf'];
        $file_ext = strtolower(pathinfo($_FILES['photocopy']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, $allowed)) {
            $upload_dir = __DIR__ . '/uploads/diagnostics/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = uniqid('surg_') . '.' . $file_ext;
            $path = $upload_dir . $filename;
            $db_path = '/uploads/diagnostics/' . $filename;
            if (move_uploaded_file($_FILES['photocopy']['tmp_name'], $path)) {
                // Insert record
                $db = getDatabaseConnection();
                $stmt = $db->prepare("INSERT INTO diagnostic_surgery_photocopies 
                    (diagnostics_id, pet_id, photocopy_path, description, uploaded_at)
                    VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$diagnostics_id, $pet_id, $db_path, $description]);
                $success = "Report uploaded and saved!";
            } else {
                $error = "Failed to move file.";
            }
        } else {
            $error = "Only PDF files are allowed.";
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <h2>Upload Surgery Report (PDF)</h2>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="pet_id" class="form-label">Pet ID</label>
            <input type="text" class="form-control" id="pet_id" name="pet_id" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description (Optional)</label>
            <textarea class="form-control" id="description" name="description"></textarea>
        </div>
        <div class="mb-3">
            <label for="photocopy" class="form-label">Upload PDF</label>
            <input type="file" class="form-control" id="photocopy" name="photocopy" accept="application/pdf" required>
        </div>
        <button class="btn btn-primary" type="submit">Upload</button>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
