<?php
session_start();
include 'includes/header.php';

$is_staff = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'];

// Form processing logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required = ['reporter_phone', 'incident_type', 'animal_type', 'incident_location'];
    $valid = true;
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $valid = false;
            break;
        }
    }
    
    if ($valid) {
        try {
            $db = new PDO('mysql:host=localhost;port=3307;dbname=veterinary_portal', 'root', '');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $db->prepare("INSERT INTO emergency_reports 
                                (reporter_phone, incident_type, animal_type, 
                                 location, description, status, created_at)
                                VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            
            $stmt->execute([
                $_POST['reporter_phone'],
                $_POST['incident_type'],
                $_POST['animal_type'],
                $_POST['incident_location'],
                $_POST['incident_description'] ?? '',
            ]);
            
            // Get the last inserted ID
            $report_id = $db->lastInsertId();
            
            // Send confirmation (in a real app, this would be an SMS/email)
            $_SESSION['emergency_report_id'] = $report_id;
            $_SESSION['emergency_submitted'] = true;
            
            header("Location: emergency_confirmation.php");
            exit;
            
        } catch(PDOException $e) {
            $error = "System error. Please call our emergency number directly.";
            error_log("Emergency report error: " . $e->getMessage());
        }
    } else {
        $error = "Please fill all required fields marked with *";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Animal Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .emergency-card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .emergency-form label {
            margin-bottom: 0.2rem;
            font-size: 0.9rem;
        }
        .emergency-form .form-control, 
        .emergency-form .form-select {
            padding: 0.35rem 0.5rem;
            font-size: 0.9rem;
            border-radius: 4px;
        }
        .card-header h5 {
            font-size: 1.1rem;
        }
        .hotline-alert {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .btn-emergency {
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .sidebar-collapsed main {
            margin-left: 0;
        }
        main {
            margin-left: 250px;
            transition: margin-left 0.3s;
        }
        @media (max-width: 768px) {
            main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/staff_sidebar.php'; ?>
        
    <main class="<?= $is_staff ? 'col-md-9 ms-sm-auto col-lg-10 px-md-4' : '' ?>">
        <div class="container py-3">

        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card emergency-card border-danger">
                    <div class="card-header bg-danger text-white py-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <h5 class="mb-0">Emergency Animal Report</h5>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger mb-3 py-2">
                                <i class="bi bi-exclamation-circle-fill me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <p class="small text-muted mb-3">
                            <i class="bi bi-info-circle"></i> Use this form to report injured, trapped, or abused animals needing immediate help.
                        </p>
                        
                        <form method="POST" class="emergency-form">
                            <!-- Reporter Contact -->
                            <div class="mb-3">
                                <label for="reporter_phone" class="form-label">
                                    <span class="text-danger">*</span> Your Phone Number
                                </label>
                                <input type="tel" class="form-control" id="reporter_phone" 
                                       name="reporter_phone" required 
                                       placeholder="We may call for details">
                            </div>
                            
                            <!-- Incident Type -->
                            <div class="mb-3">
                                <label for="incident_type" class="form-label">
                                    <span class="text-danger">*</span> Emergency Type
                                </label>
                                <select class="form-select" id="incident_type" name="incident_type" required>
                                    <option value="">Select type...</option>
                                    <option value="injured">Injured Animal</option>
                                    <option value="trapped">Trapped Animal</option>
                                    <option value="accident">Road Accident</option>
                                    <option value="abuse">Active Abuse</option>
                                    <option value="sick">Severely Sick Animal</option>
                                </select>
                            </div>
                            
                            <!-- Animal Type -->
                            <div class="mb-3">
                                <label for="animal_type" class="form-label">
                                    <span class="text-danger">*</span> Animal Type
                                </label>
                                <select class="form-select" id="animal_type" name="animal_type" required>
                                    <option value="">Select animal...</option>
                                    <option value="dog">Dog</option>
                                    <option value="cat">Cat</option>
                                    <option value="cow">Cow</option>
                                    <option value="bird">Bird</option>
                                    <option value="wildlife">Wild Animal</option>
                                </select>
                            </div>
                            
                            <!-- Location -->
                            <div class="mb-3">
                                <label for="incident_location" class="form-label">
                                    <span class="text-danger">*</span> Exact Location
                                </label>
                                <textarea class="form-control" id="incident_location" 
                                          name="incident_location" rows="2" required 
                                          placeholder="Street, landmark, or nearest address"></textarea>
                            </div>
                            
                            <!-- Description -->
                            <div class="mb-3">
                                <label for="incident_description" class="form-label">Brief Description</label>
                                <textarea class="form-control" id="incident_description" 
                                          name="incident_description" rows="2" 
                                          placeholder="Animal's condition, visible injuries, urgent needs"></textarea>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-danger btn-emergency py-2">
                                    <i class="bi bi-send-fill me-2"></i> Send Emergency Alert
                                </button>
                            </div>
                        </form>
                        
                        <!-- Emergency Hotline -->
                        <div class="alert hotline-alert mt-4 p-3">
                            <div class="d-flex">
                                <i class="bi bi-telephone-fill text-warning me-3 fs-4"></i>
                                <div>
                                    <h6 class="fw-bold mb-1">Immediate Danger?</h6>
                                    <p class="small mb-1">Call our 24/7 emergency hotline:</p>
                                    <p class="fw-bold text-danger mb-0">+91-XXX-XXX-XXXX</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus on first field
        document.getElementById('reporter_phone').focus();
        
        // Auto-detect current location if possible
        if (navigator.geolocation) {
            document.getElementById('incident_location').addEventListener('focus', function() {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const locationField = document.getElementById('incident_location');
                    if (locationField.value === '') {
                        locationField.value = `Near GPS: ${position.coords.latitude}, ${position.coords.longitude}`;
                    }
                });
            });
        }
    </script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php include 'includes/footer.php'; ?>