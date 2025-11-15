<?php
// my_pets.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Start session and validate user
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/includes/auth/validate_session.php';

// Get pet owner data
$petOwner = getPetOwnerByPetOwnerId($_SESSION['pet_owner_id']);
if (!$petOwner) {
    session_unset();
    session_destroy();
    header('Location: login.php?reason=invalid_user');
    exit;
}

// Get all pets for this owner
try {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT p.*, s.species_name, b.breed_name 
        FROM pets p
        JOIN species s ON p.species_id = s.species_id
        JOIN breeds b ON p.breed_id = b.breed_id
        WHERE p.pet_owner_id = ?
        ORDER BY p.pet_name ASC  
    ");
    $stmt->execute([$_SESSION['pet_owner_id']]);
    $pets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Database error. Please try again later.";
}

// Include header
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Pets</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="add_pet.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus"></i> Add New Pet
                    </a>
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
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($pets)): ?>
                <div class="alert alert-info">
                    You haven't registered any pets yet. <a href="add_pet.php">Add your first pet</a>.
                </div>
            <?php else: ?>
                <style>
                    .pet-card {
                        max-width: 280px;
                        margin-bottom: 20px;
                    }
                    .pet-thumbnail {
                        height: 160px;
                        object-fit: cover;
                    }
                    .card-body {
                        padding: 1rem;
                    }
                    .card-title {
                        font-size: 1.1rem;
                        margin-bottom: 0.5rem;
                    }
                    .card-text {
                        font-size: 0.9rem;
                        margin-bottom: 0.5rem;
                    }
                    .card-text strong {
                        font-weight: 500;
                    }
                    .card-footer {
                        padding: 0.75rem 1rem;
                    }
                </style>
                
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($pets as $pet): ?>
                        <div class="card pet-card">
                            <img src="<?= htmlspecialchars($pet['profile_picture']) ?>" 
                                 class="card-img-top pet-thumbnail" 
                                 alt="<?= htmlspecialchars($pet['pet_name']) ?>"
                                 onerror="this.src='assets/images/default-pet.jpg'">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($pet['pet_name']) ?></h5>
                                <p class="card-text">
                                    <strong>Species:</strong> <?= htmlspecialchars($pet['species_name']) ?><br>
                                    <strong>Breed:</strong> <?= htmlspecialchars($pet['breed_name']) ?><br>
                                    <strong>Gender:</strong> <?= htmlspecialchars($pet['sex']) ?>
                                    <?= $pet['neutered'] ? ' (Neutered)' : '' ?>
                                </p>
                            </div>
                            <div class="card-footer bg-transparent d-flex justify-content-between">
                            <a href="pet_details.php?id=<?= $pet['pet_id'] ?>" class="btn btn-info">
                                 <i class="bi bi-eye"></i> View Details
                            </a>

                                 <a href="edit_pet.php?id=<?= $pet['pet_id'] ?>" class="btn btn-primary">
                        <i class="bi bi-pencil-square"></i> Edit Pet
                    </a>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>