<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check staff login
if (empty($_SESSION['staff_logged_in'])) {
    header('Location: staff_login.php');
    exit;
}

try {
    $db = getDatabaseConnection();

    // Optionally, get staff ID or diagnostics_staff ID from session if available
    $staffUsername = $_SESSION['user']['username'] ?? '';

    // Join diagnostic photocopies with diagnostics_staff to get details
    $stmt = $db->prepare("
        SELECT dsp.id, dsp.pet_id, dsp.photocopy_path, dsp.uploaded_at, dsp.description,
               ds.full_name AS diagnostic_staff_name
        FROM diagnostic_surgery_photocopies dsp
        LEFT JOIN diagnostics_staff ds ON dsp.diagnostics_id = ds.diagnostics_id
        ORDER BY dsp.uploaded_at DESC
    ");
    $stmt->execute();
    $photocopies = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <h2>Diagnostic Surgery Photocopies</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php elseif (empty($photocopies)): ?>
        <div class="alert alert-info">No surgery photocopies found.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($photocopies as $copy): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <img src="<?= htmlspecialchars($copy['photocopy_path']) ?>" alt="Surgery Photocopy" 
                             class="card-img-top" style="max-height: 200px; object-fit: contain;">
                        <div class="card-body">
                            <h5 class="card-title">Pet ID: <?= htmlspecialchars($copy['pet_id']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($copy['description'] ?? '') ?></p>
                            <p class="card-text">
                                <small class="text-muted">Uploaded by: <?= htmlspecialchars($copy['diagnostic_staff_name']) ?></small><br>
                                <small class="text-muted">Date: <?= date('d-M-Y', strtotime($copy['uploaded_at'])) ?></small>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
