<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['pet_owner_id']) || !isset($_SESSION['logged_in'])) {
    header('Location: login.php');
    exit();
}

$petId = $_GET['id'] ?? null;
if (!$petId) {
    header('Location: my_pets.php?error=invalid_pet');
    exit();
}

$successMsg = $errorMsg = "";
$errors = [];
$pet = null;

// Database connection
try {
    $db = getDatabaseConnection();
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    $errorMsg = "Database connection failed. Please try again later.";
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pet_image'])) {
    $uploadDir = 'uploads/pet_images/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['pet_image'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'pet_' . $petId . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                try {
                    $stmt = $db->prepare("UPDATE pets SET pet_image = ? WHERE pet_id = ? AND pet_owner_id = ?");
                    if ($stmt->execute([$uploadPath, $petId, $_SESSION['pet_owner_id']])) {
                        $successMsg = "Pet image updated successfully!";
                    } else {
                        $errorMsg = "Failed to save image path to database.";
                        unlink($uploadPath);
                    }
                } catch (PDOException $e) {
                    $errorMsg = "Database error while saving image.";
                    unlink($uploadPath);
                }
            } else {
                $errorMsg = "Failed to upload image.";
            }
        } else {
            $errorMsg = "Invalid file type or size too large. Please upload JPG, PNG, or GIF under 5MB.";
        }
    } else {
        $errorMsg = "Error uploading file.";
    }
}

// Handle pet information update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pet'])) {
    // Sanitize and validate input
    $pet_name = trim($_POST['pet_name'] ?? '');
    $species_id = $_POST['species_id'] ?? '';
    $breed_id = $_POST['breed_id'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $neutered = isset($_POST['neutered']) ? 1 : 0;
    $age_value = $_POST['age_value'] ?? '';
    $age_unit = $_POST['age_unit'] ?? 'years';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $color = trim($_POST['color'] ?? '');
    $weight = $_POST['weight'] ?? '';
    $identification_mark = trim($_POST['identification_mark'] ?? '');

    // Validation
    if (empty($pet_name)) {
        $errors[] = "Pet name is required.";
    }
    if (empty($species_id)) {
        $errors[] = "Please select a species.";
    }
    if (empty($breed_id)) {
        $errors[] = "Please select a breed.";
    }
    if (empty($sex)) {
        $errors[] = "Please select pet's sex.";
    }

    // If no errors, update the pet
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE pets SET 
                    pet_name = ?, species_id = ?, breed_id = ?, sex = ?, 
                    neutered = ?, age_value = ?, age_unit = ?, date_of_birth = ?, 
                    color = ?, weight = ?, identification_mark = ?, updated_at = NOW()
                WHERE pet_id = ? AND pet_owner_id = ?
            ");
            
            if ($stmt->execute([
                $pet_name, $species_id, $breed_id, $sex,
                $neutered, $age_value, $age_unit, $date_of_birth,
                $color, $weight, $identification_mark, $petId, $_SESSION['pet_owner_id']
            ])) {
                $_SESSION['success_message'] = "Pet information updated successfully!";
                header("Location: pet_details.php?id=" . $petId);
                exit();
            } else {
                $errorMsg = "Failed to update pet information.";
            }
        } catch (PDOException $e) {
            error_log("Update error: " . $e->getMessage());
            $errorMsg = "Database error occurred while updating.";
        }
    }
}

// Fetch current pet data
try {
    $stmt = $db->prepare("
        SELECT p.*, s.species_name, b.breed_name 
        FROM pets p 
        JOIN species s ON p.species_id = s.species_id 
        JOIN breeds b ON p.breed_id = b.breed_id 
        WHERE p.pet_id = ? AND p.pet_owner_id = ?
    ");
    $stmt->execute([$petId, $_SESSION['pet_owner_id']]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pet) {
        header('Location: my_pets.php?error=pet_not_found');
        exit();
    }
} catch (PDOException $e) {
    error_log("Fetch error: " . $e->getMessage());
    $errorMsg = "Error loading pet information.";
}

// Get species and breeds for dropdowns
try {
    $species = $db->query("SELECT species_id, species_name FROM species ORDER BY species_name")->fetchAll();
    
    $breeds = [];
    if ($pet && $pet['species_id']) {
        $stmt = $db->prepare("SELECT breed_id, breed_name FROM breeds WHERE species_id = ? ORDER BY breed_name");
        $stmt->execute([$pet['species_id']]);
        $breeds = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Dropdown data error: " . $e->getMessage());
    $species = [];
    $breeds = [];
}

// Set default image
$petImage = (!empty($pet['pet_image']) && file_exists($pet['pet_image'])) ? $pet['pet_image'] : 'https://via.placeholder.com/200';

include 'includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="my_pets.php">My Pets</a></li>
            <?php if ($pet): ?>
                <li class="breadcrumb-item"><a href="pet_details.php?id=<?= $petId ?>"><?= htmlspecialchars($pet['pet_name']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active">Edit Pet</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-pencil-square"></i> Edit Pet Information
                        <?php if ($pet): ?>
                            - <?= htmlspecialchars($pet['pet_name']) ?>
                        <?php endif; ?>
                    </h4>
                </div>

                <div class="card-body">
                    <!-- Success/Error Messages -->
                    <?php if ($successMsg): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorMsg): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($pet): ?>
                        <!-- Pet Image Section -->
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <img src="<?= htmlspecialchars($petImage) ?>" 
                                     id="petImageDisplay" 
                                     class="img-thumbnail rounded-circle" 
                                     style="width: 200px; height: 200px; object-fit: cover; cursor: pointer;"
                                     alt="Pet Image">
                                <div class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2" 
                                     style="cursor: pointer;" 
                                     onclick="document.getElementById('petImageUpload').click();">
                                    <i class="bi bi-camera"></i>
                                </div>
                            </div>
                            <p class="text-muted mt-2">Click on the image or camera icon to change photo</p>
                            
                            <!-- Hidden Image Upload Form -->
                            <form id="imageUploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
                                <input type="file" id="petImageUpload" name="pet_image" accept="image/*">
                            </form>
                        </div>

                        <!-- Pet Information Form -->
                        <form method="POST" id="editPetForm">
                            <input type="hidden" name="update_pet" value="1">
                            
                            <div class="row g-3">
                                <!-- Pet Name -->
                                <div class="col-md-6">
                                    <label for="pet_name" class="form-label">Pet Name *</label>
                                    <input type="text" class="form-control" id="pet_name" name="pet_name" 
                                           value="<?= htmlspecialchars($pet['pet_name']) ?>" required>
                                </div>

                                <!-- Species -->
                                <div class="col-md-6">
                                    <label for="species_id" class="form-label">Species *</label>
                                    <select class="form-select" id="species_id" name="species_id" required>
                                        <option value="">Select Species</option>
                                        <?php foreach ($species as $s): ?>
                                            <option value="<?= $s['species_id'] ?>" <?= $pet['species_id'] == $s['species_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($s['species_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Breed -->
                                <div class="col-md-6">
                                    <label for="breed_id" class="form-label">Breed *</label>
                                    <select class="form-select" id="breed_id" name="breed_id" required>
                                        <option value="">Select Breed</option>
                                        <?php foreach ($breeds as $b): ?>
                                            <option value="<?= $b['breed_id'] ?>" <?= $pet['breed_id'] == $b['breed_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($b['breed_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Sex -->
                                <div class="col-md-6">
                                    <label for="sex" class="form-label">Sex *</label>
                                    <select class="form-select" id="sex" name="sex" required>
                                        <option value="">Select Sex</option>
                                        <option value="Male" <?= $pet['sex'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= $pet['sex'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                    </select>
                                </div>

                                <!-- Neutered -->
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="neutered" name="neutered" 
                                               <?= $pet['neutered'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="neutered">
                                            Neutered/Spayed
                                        </label>
                                    </div>
                                </div>

                                <!-- Age -->
                                <div class="col-md-6">
                                    <label for="age_value" class="form-label">Age</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="age_value" name="age_value" 
                                               value="<?= htmlspecialchars($pet['age_value']) ?>" min="0" max="50">
                                        <select class="form-select" id="age_unit" name="age_unit" style="max-width: 120px;">
                                            <option value="years" <?= $pet['age_unit'] == 'years' ? 'selected' : '' ?>>Years</option>
                                            <option value="months" <?= $pet['age_unit'] == 'months' ? 'selected' : '' ?>>Months</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Date of Birth -->
                                <div class="col-md-6">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                           value="<?= htmlspecialchars($pet['date_of_birth']) ?>">
                                </div>

                                <!-- Color -->
                                <div class="col-md-6">
                                    <label for="color" class="form-label">Color</label>
                                    <input type="text" class="form-control" id="color" name="color" 
                                           value="<?= htmlspecialchars($pet['color']) ?>" 
                                           placeholder="e.g., Brown, Black, White">
                                </div>

                                <!-- Weight -->
                                <div class="col-md-6">
                                    <label for="weight" class="form-label">Weight (kg)</label>
                                    <input type="number" class="form-control" id="weight" name="weight" 
                                           value="<?= htmlspecialchars($pet['weight']) ?>" 
                                           step="0.1" min="0" placeholder="e.g., 15.5">
                                </div>

                                <!-- Identification Mark -->
                                <div class="col-12">
                                    <label for="identification_mark" class="form-label">Identification Mark</label>
                                    <textarea class="form-control" id="identification_mark" name="identification_mark" 
                                              rows="3" placeholder="Any distinctive marks, scars, or identifying features"><?= htmlspecialchars($pet['identification_mark']) ?></textarea>
                                </div>

                                <!-- Form Buttons -->
                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="pet_details.php?id=<?= $petId ?>" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-check-lg"></i> Update Pet Information
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Pet not found or you don't have permission to edit this pet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Dynamic Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image upload handling
    document.getElementById('petImageUpload').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            // Show preview immediately
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('petImageDisplay').src = event.target.result;
            };
            reader.readAsDataURL(e.target.files[0]);
            
            // Submit form to upload image
            document.getElementById('imageUploadForm').submit();
        }
    });

    // Dynamic breed loading based on species selection
    document.getElementById('species_id').addEventListener('change', function() {
        const speciesId = this.value;
        const breedSelect = document.getElementById('breed_id');
        
        // Clear current breeds
        breedSelect.innerHTML = '<option value="">Loading breeds...</option>';
        
        if (speciesId) {
            fetch(`includes/get_breeds.php?species_id=${speciesId}`)
                .then(response => response.json())
                .then(data => {
                    breedSelect.innerHTML = '<option value="">Select Breed</option>';
                    data.forEach(breed => {
                        breedSelect.innerHTML += `<option value="${breed.breed_id}">${breed.breed_name}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error loading breeds:', error);
                    breedSelect.innerHTML = '<option value="">Error loading breeds</option>';
                });
        } else {
            breedSelect.innerHTML = '<option value="">Select Breed</option>';
        }
    });

    // Form validation
    document.getElementById('editPetForm').addEventListener('submit', function(e) {
        const requiredFields = ['pet_name', 'species_id', 'breed_id', 'sex'];
        let isValid = true;
        
        requiredFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
});
</script>

<style>
.form-control.is-invalid, .form-select.is-invalid {
    border-color: #dc3545;
}

.img-thumbnail:hover {
    transform: scale(1.02);
    transition: transform 0.2s ease-in-out;
}

.position-absolute .bi-camera {
    font-size: 1.2rem;
}
</style>

<?php include 'includes/footer.php'; ?>
