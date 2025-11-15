<?php
session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

// Check admin privileges
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Initialize variables
$errors = [];
$success = false;
$species = '';
$breeds = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Please try again.";
    } else {
        $species = trim($_POST['species'] ?? '');
        $breeds = array_filter(array_map('trim', $_POST['breeds'] ?? []));
        
        // Validate inputs
        if (empty($species)) {
            $errors[] = "Species name is required";
        } elseif (strlen($species) > 50) {
            $errors[] = "Species name must be less than 50 characters";
        }
        
        if (empty($breeds)) {
            $errors[] = "At least one breed is required";
        } else {
            foreach ($breeds as $breed) {
                if (strlen($breed) > 50) {
                    $errors[] = "Breed names must be less than 50 characters";
                    break;
                }
            }
        }
        
        // If no errors, save to database
        if (empty($errors)) {
            try {
                $db = getDatabaseConnection();
                $db->beginTransaction();
                
                // Insert species
                $stmt = $db->prepare("INSERT INTO animal_species (species_name) VALUES (?)");
                $stmt->execute([$species]);
                $speciesId = $db->lastInsertId();
                
                // Insert breeds
                $stmt = $db->prepare("INSERT INTO animal_breeds (species_id, breed_name) VALUES (?, ?)");
                foreach ($breeds as $breed) {
                    $stmt->execute([$speciesId, $breed]);
                }
                
                $db->commit();
                $success = true;
                
                // Clear form on success
                $species = '';
                $breeds = [];
                
                // Regenerate CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("Database error: " . $e->getMessage());
                $errors[] = "Failed to save species and breeds. Please try again.";
            }
        }
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<style>
    .admin-card {
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .admin-card .card-header {
        font-weight: 600;
        border-radius: 8px 8px 0 0 !important;
    }
    .breed-input-container .input-group {
        margin-bottom: 8px;
    }
    .remove-breed {
        transition: all 0.2s ease;
    }
    .remove-breed:hover {
        transform: scale(1.1);
    }
    .btn-govt {
        background-color: var(--govt-blue);
        color: white;
    }
    .btn-govt:hover {
        background-color: #0d3a7a;
        color: white;
    }
    .is-invalid {
        border-color: #dc3545;
    }
    .invalid-feedback {
        color: #dc3545;
        font-size: 0.875em;
    }
</style>
</head>
<body>
    <div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">

            </div>
    <!-- Main Content -->
        
            <!-- Main Admin Content Area -->
                <div class="admin-header mb-4">
                    <h3><i class="bi bi-tags"></i> Add New Species/Breed</h3>
                    <p class="mb-0">Register new animal species and their breeds in the system</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> 
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error) ?></div>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i> Species and breeds have been successfully registered!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card admin-card mb-4">
                    <div class="card-header" style="background-color: var(--govt-blue); color: white;">
                        <i class="bi bi-plus-circle"></i> Species & Breed Information
                    </div>
                    <div class="card-body">
                        <form id="speciesForm" method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            
                            <div class="mb-3">
                                <label for="species" class="form-label">Species Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="species" name="species" 
                                       value="<?= htmlspecialchars($species) ?>" required 
                                       placeholder="Enter species name (e.g., Canine, Feline, Bovine)">
                                <div class="invalid-feedback">Please enter a valid species name</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Breeds <span class="text-danger">*</span></label>
                                <div id="breedsContainer">
                                    <!-- Breed fields will be added here -->
                                    <div class="breed-input-container mb-2">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="breeds[]" 
                                                   value="<?= isset($breeds[0]) ? htmlspecialchars($breeds[0]) : '' ?>" 
                                                   required placeholder="Enter breed name">
                                            <button type="button" class="btn btn-danger remove-breed" title="Remove breed" <?= count($breeds) <= 1 ? 'disabled' : '' ?>>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">Please enter a breed name</div>
                                    </div>
                                    
                                    <?php for ($i = 1; $i < count($breeds); $i++): ?>
                                        <div class="breed-input-container mb-2">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="breeds[]" 
                                                       value="<?= htmlspecialchars($breeds[$i]) ?>" 
                                                       required placeholder="Enter breed name">
                                                <button type="button" class="btn btn-danger remove-breed" title="Remove breed">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback">Please enter a breed name</div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <button type="button" id="addBreedBtn" class="btn btn-primary btn-sm mt-2" title="Add another breed">
                                    <i class="bi bi-plus-lg"></i> Add Breed
                                </button>
                                <small class="text-muted d-block mt-1">Maximum 20 breeds allowed</small>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="reset" class="btn btn-outline-secondary me-md-2">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-govt">
                                    <i class="bi bi-save"></i> Register Species & Breeds
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card admin-card">
                    <div class="card-header" style="background-color: var(--govt-blue); color: white;">
                        <i class="bi bi-info-circle"></i> Instructions
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>Enter the species name (e.g., Dog, Cat, Cow)</li>
                            <li>Click "Add Breed" button to add more breed fields</li>
                            <li>Fill in all breed names (at least one required)</li>
                            <li>Click the trash icon to remove a breed field if needed</li>
                            <li>Click "Register Species & Breeds" to save the information</li>
                        </ol>
                        <div class="alert alert-info">
                            <i class="bi bi-lightbulb"></i> <strong>Tip:</strong> You can add more breeds later by editing the species record.
                        </div>
                    </div>
                </div>
            
        
    

    <!-- Footer -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add breed field
            $('#addBreedBtn').click(function() {
                const maxBreeds = 20;
                const breedCount = $('.breed-input-container').length;
                
                if (breedCount >= maxBreeds) {
                    alert(`Maximum ${maxBreeds} breeds allowed`);
                    return;
                }
                
                const newBreed = `
                    <div class="breed-input-container mb-2">
                        <div class="input-group">
                            <input type="text" class="form-control" name="breeds[]" required placeholder="Enter breed name">
                            <button type="button" class="btn btn-danger remove-breed" title="Remove breed">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">Please enter a breed name</div>
                    </div>
                `;
                $('#breedsContainer').append(newBreed);
                
                // Enable all remove buttons if we have more than one breed
                if ($('.breed-input-container').length > 1) {
                    $('.remove-breed').prop('disabled', false);
                }
                
                // Scroll to the newly added breed
                $('html, body').animate({
                    scrollTop: $('#breedsContainer').children().last().offset().top
                }, 500);
            });
            
            // Remove breed field
            $(document).on('click', '.remove-breed', function() {
                // Don't allow removing the last breed input
                if ($('.breed-input-container').length > 1) {
                    $(this).closest('.breed-input-container').remove();
                    
                    // Disable remove button if only one breed left
                    if ($('.breed-input-container').length === 1) {
                        $('.remove-breed').prop('disabled', true);
                    }
                }
            });
            
            // Form validation
            $('#speciesForm').submit(function(e) {
                let valid = true;
                
                // Validate species name
                const speciesInput = $('#species');
                if (speciesInput.val().trim() === '') {
                    speciesInput.addClass('is-invalid');
                    valid = false;
                } else {
                    speciesInput.removeClass('is-invalid');
                }
                
                // Validate breeds
                $('input[name="breeds[]"]').each(function() {
                    if ($(this).val().trim() === '') {
                        $(this).addClass('is-invalid');
                        valid = false;
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields correctly');
                    $('html, body').animate({
                        scrollTop: $('.is-invalid').first().offset().top - 100
                    }, 500);
                }
                
                return valid;
            });
            
            // Clear validation on input
            $('input').on('input', function() {
                $(this).removeClass('is-invalid');
            });
        });
    </script>
</body>
</html>