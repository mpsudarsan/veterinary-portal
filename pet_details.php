<?php
// pet_details.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Start session and validate user
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/includes/auth/validate_session.php';

// Initialize variables
$pet = null;
$error = null;
$petId = null;

// Check if pet ID is provided and valid
if (empty($_GET['id']) || !is_string($_GET['id'])) {
    header('Location: my_pets.php?error=no_pet_selected');
    exit;
}

$petId = trim($_GET['id']);

try {
    // Verify database connection
    $db = getDatabaseConnection();
    if (!$db) {
        throw new PDOException("Could not connect to database");
    }

    // Get pet details
    $stmt = $db->prepare("
        SELECT p.*, s.species_name, b.breed_name, po.name as owner_name
        FROM pets p
        JOIN species s ON p.species_id = s.species_id
        JOIN breeds b ON p.breed_id = b.breed_id
        JOIN pet_owners po ON p.pet_owner_id = po.pet_owner_id
        WHERE p.pet_id = ? AND p.pet_owner_id = ?
        LIMIT 1
    ");
    
    if (!$stmt->execute([$petId, $_SESSION['pet_owner_id']])) {
        throw new PDOException("Failed to execute query");
    }
    
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pet) {
        header('Location: my_pets.php?error=pet_not_found');
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error in pet_details.php: " . $e->getMessage());
    $error = "Database error. Please try again later. If the problem persists, contact support.";
}
$stmt = $db->prepare("
    SELECT p.*,
          s.species_name,
          b.breed_name,
          po.name AS owner_name
    FROM pets p
    JOIN species  s ON p.species_id = s.species_id
    JOIN breeds   b ON p.breed_id  = b.breed_id
    JOIN pet_owners po ON p.pet_owner_id = po.pet_owner_id
    WHERE p.pet_id = ?
      AND p.pet_owner_id = ?
    LIMIT 1
");
$stmt->execute([$petId, $_SESSION['pet_owner_id']]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pet) {
    header('Location: my_pets.php?error=pet_not_found');
    exit;
}


/* ──────────────────────────────────────────────────────────────
   1.  Decide which image to show
   ─────────────────────────────────────────────────────────────── */
$defaultImg = 'https://via.placeholder.com/200x200/e9ecef/6c757d?text=No+Photo';
$petImgPath = $pet['pet_image'] ?? '';
if ($petImgPath && file_exists(__DIR__ . '/' . $petImgPath)) {
    $imgSrc = $petImgPath;                  // show the uploaded photo
} else {
    $imgSrc = $defaultImg;                  // fall back to placeholder
}


// Include header
include 'includes/header.php';
?>



<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Pet Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="my_pets.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to My Pets
                    </a>
                    <?php if ($pet): ?>
                    <a href="edit_pet.php?id=<?= $pet['pet_id'] ?>" class="btn btn-primary">
                        <i class="bi bi-pencil-square"></i> Edit Pet
                    </a>


                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
                    <?php if (isset($e) && DEBUG_MODE): ?>
                        <div class="mt-2 small">Technical details: <?= htmlspecialchars($e->getMessage()) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$pet && !$error): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-circle-fill"></i> Pet not found or you don't have permission to view this pet.
                </div>
            <?php elseif ($pet): ?>
                <div class="row">
                    
        
                    <?php include 'includes/partials/pet_details_content.php'; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php if ($pet): ?>
    <script>
    let lastUpdateTime = null;
    const petId = '<?= $pet['pet_id'] ?>';


    function checkForUpdates() {
        fetch(`api/get_medical_update.php?pet_id=${petId}`)
            .then(response => response.json())
            .then(data => {
                if (data.last_update !== lastUpdateTime) {
                    lastUpdateTime = data.last_update;
                    loadMedicalRecords();
                    // Visual feedback
                    document.getElementById('last-update-badge').classList.add('bg-success');
                    setTimeout(() => {
                        document.getElementById('last-update-badge').classList.remove('bg-success');
                    }, 2000);
                }
            })
            .catch(error => console.error('Update check failed:', error));
    }


    function loadMedicalRecords() {
        fetch(`api/get_medical_records.php?pet_id=${petId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('medical-records-container').innerHTML = html;
            });
    }


    // Check every 10 seconds
    setInterval(checkForUpdates, 10000);


    // Initial load
    document.addEventListener('DOMContentLoaded', function() {
        loadMedicalRecords();
        checkForUpdates(); // Start checking immediately
    });
    </script>
<?php endif; ?>
<?php include 'includes/footer.php'; ?> 

