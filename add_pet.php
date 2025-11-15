<?php
// add_pet.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

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

// Initialize form data with default values
$formData = [
    'pet_name' => '',
    'species_id' => '',
    'breed_id' => '',
    'sex' => 'Female', // Default to Female as shown in your screenshot
    'neutered' => 0,
    'age_value' => '',
    'age_unit' => 'years',
    'date_of_birth' => '',
    'color' => '',
    'weight' => '',
    'identification_mark' => '',
    'pet_image' => '' // Add image field to form data
];
$errors = [];

// Process form submission if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize pet name
    $pet_name = trim($_POST['pet_name'] ?? '');
    error_log("Form submitted with pet name: '{$pet_name}'");
    
    // Validate pet name - only show error if truly empty
    if (empty($pet_name)) {
        $errors[] = "Please provide a pet name.";
    } else {
        $formData['pet_name'] = $pet_name;
    }

    // Handle image upload FIRST
    $uploadedImagePath = '';
    if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/pet_images/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['pet_image'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB as per your form note
        
        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'pet_' . $_SESSION['pet_owner_id'] . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $uploadedImagePath = 'uploads/pet_images/' . $fileName; // Relative path for database
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image file. Max 2MB, JPG/PNG/GIF only.";
        }
    }

    // Only proceed with full processing if pet name is valid
    if (empty($errors)) {
        require_once __DIR__ . '/includes/handlers/pet_handler.php';
        
        // Prepare complete form data INCLUDING image path
        $formData = array_merge($formData, [
            'species_id' => $_POST['species_id'] ?? '',
            'breed_id' => $_POST['breed_id'] ?? '',
            'sex' => $_POST['sex'] ?? 'Female',
            'neutered' => isset($_POST['neutered']) ? 1 : 0,
            'age_value' => $_POST['age_value'] ?? '',
            'age_unit' => $_POST['age_unit'] ?? 'years',
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'color' => trim($_POST['color'] ?? ''),
            'weight' => $_POST['weight'] ?? '',
            'identification_mark' => trim($_POST['identification_mark'] ?? ''),
            'pet_image' => $uploadedImagePath // Pass image path to handler
        ]);

        $result = handlePetFormSubmission($_SESSION['pet_owner_id'], $formData);
        
        if ($result['success']) {
            $_SESSION['success_message'] = "Pet added successfully!";
            header("Location: pet_details.php?id=" . $result['pet_id']);
            exit;
        } else {
            $errors = array_merge($errors, $result['errors']);
        }
    }
}

// Get species and breeds for dropdowns
try {
    $db = getDatabaseConnection();
    $species = $db->query("SELECT species_id, species_name FROM species ORDER BY species_name")->fetchAll();
    $breeds = [];
    
    if (!empty($formData['species_id'])) {
        $stmt = $db->prepare("SELECT breed_id, breed_name FROM breeds WHERE species_id = ? ORDER BY breed_name");
        $stmt->execute([$formData['species_id']]);
        $breeds = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Database error. Please try again later.";
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Add New Pet</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="my_pets.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to My Pets
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                
                <!-- Basic Information Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="pet_name" class="form-label">Pet Name *</label>
                                <input type="text" class="form-control <?= in_array('Please provide a pet name.', $errors) ? 'is-invalid' : '' ?>" 
                                       id="pet_name" name="pet_name" 
                                       value="<?= htmlspecialchars($formData['pet_name']) ?>" required>
                                <div class="invalid-feedback">Please provide a pet name.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="species_id" class="form-label">Species *</label>
                                <select class="form-select" id="species_id" name="species_id" required>
                                    <option value="">Select Species</option>
                                    <?php foreach ($species as $specie): ?>
                                        <option value="<?= $specie['species_id'] ?>" <?= $formData['species_id'] == $specie['species_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($specie['species_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="breed_id" class="form-label">Breed *</label>
                                <select class="form-select" id="breed_id" name="breed_id" required>
                                    <option value="">Select Breed</option>
                                    <?php foreach ($breeds as $breed): ?>
                                        <option value="<?= $breed['breed_id'] ?>" <?= $formData['breed_id'] == $breed['breed_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($breed['breed_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Sex *</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sex" id="sexMale" value="Male" <?= $formData['sex'] === 'Male' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="sexMale">Male</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sex" id="sexFemale" value="Female" <?= $formData['sex'] === 'Female' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="sexFemale">Female</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="neutered" id="neutered" value="1" <?= $formData['neutered'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="neutered">Neutered/Spayed</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="age_value" class="form-label">Age *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="age_value" name="age_value" value="<?= htmlspecialchars($formData['age_value']) ?>" required>
                                    <select class="form-select" name="age_unit" style="max-width: 120px;">
                                        <option value="years" <?= $formData['age_unit'] === 'years' ? 'selected' : '' ?>>Years</option>
                                        <option value="months" <?= $formData['age_unit'] === 'months' ? 'selected' : '' ?>>Months</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">Date of Birth (Optional)</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($formData['date_of_birth']) ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="color" class="form-label">Color (Optional)</label>
                                <input type="text" class="form-control" id="color" name="color" value="<?= htmlspecialchars($formData['color']) ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="weight" class="form-label">Weight (kg) (Optional)</label>
                                <input type="number" step="0.1" class="form-control" id="weight" name="weight" value="<?= htmlspecialchars($formData['weight']) ?>">
                            </div>
                            
                            <div class="col-12">
                                <label for="identification_mark" class="form-label">Identification Mark (Optional)</label>
                                <input type="text" class="form-control" id="identification_mark" name="identification_mark" value="<?= htmlspecialchars($formData['identification_mark']) ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Picture Section - ENHANCED -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Picture</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <!-- Image Preview -->
                                <div class="mb-3">
                                    <img id="imagePreview" 
                                         src="https://via.placeholder.com/200x200/e9ecef/6c757d?text=Pet+Photo" 
                                         class="img-thumbnail rounded" 
                                         style="width: 200px; height: 200px; object-fit: cover;"
                                         alt="Pet Image Preview">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="pet_image" class="form-label">Upload Photo</label>
                                    <input class="form-control" type="file" id="pet_image" name="pet_image" 
                                           accept="image/jpeg,image/png,image/gif" onchange="previewImage(this)">
                                    <div class="form-text">Max size: 2MB, Formats: JPG, PNG, GIF.</div>
                                </div>
                                <div class="alert alert-info">
                                    <small>
                                        <i class="bi bi-info-circle"></i> 
                                        This photo will be displayed in the pet details page and used for identification purposes.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="my_pets.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Pet</button>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
// Image preview function
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Dynamic breed loading based on species selection
document.getElementById('species_id').addEventListener('change', function() {
    const speciesId = this.value;
    const breedSelect = document.getElementById('breed_id');
    
    if (speciesId) {
        fetch(`includes/get_breeds.php?species_id=${speciesId}`)
            .then(response => response.json())
            .then(data => {
                breedSelect.innerHTML = '<option value="">Select Breed</option>';
                if (data.success && data.data) {
                    data.data.forEach(breed => {
                        const option = document.createElement('option');
                        option.value = breed.breed_id;
                        option.textContent = breed.breed_name;
                        breedSelect.appendChild(option);
                    });
                } else {
                    // Fallback for old format
                    data.forEach(breed => {
                        const option = document.createElement('option');
                        option.value = breed.breed_id;
                        option.textContent = breed.breed_name;
                        breedSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading breeds:', error);
                breedSelect.innerHTML = '<option value="">Error loading breeds</option>';
            });
    } else {
        breedSelect.innerHTML = '<option value="">Select Breed</option>';
    }
});
</script>

<?php 
include 'includes/footer.php'; 
?>
