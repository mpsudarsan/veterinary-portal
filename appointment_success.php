<?php
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/libs/fpdf/fpdf.php'; 

// Start session and validate
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['pet_owner_id']) || empty($_SESSION['logged_in'])) {
    header('Location: login.php?reason=not_logged_in');
    exit;
}

// Check if appointment data exists in session
if (empty($_SESSION['last_appointment'])) {
    header('Location: appointments.php');
    exit;
}

$appointment = $_SESSION['last_appointment'];
$pet_owner_id = $_SESSION['pet_owner_id'];

// Fetch additional details
try {
    $db = getDatabaseConnection();
    
    // Get pet owner details
    $stmt = $db->prepare("SELECT * FROM pet_owners WHERE pet_owner_id = ?");
    $stmt->execute([$pet_owner_id]);
    $pet_owner = $stmt->fetch();
    
    // Get pet details
    $stmt = $db->prepare("
        SELECT p.*, s.species_name, b.breed_name 
        FROM pets p
        JOIN species s ON p.species_id = s.species_id
        JOIN breeds b ON p.breed_id = b.breed_id
        WHERE p.pet_id = ?
    ");
    $stmt->execute([$appointment['pet_id']]);
    $pet = $stmt->fetch();
    
    // Get facility details
    $stmt = $db->prepare("SELECT * FROM veterinary_facilities WHERE facility_id = ?");
    $stmt->execute([$appointment['facility_id']]);
    $facility = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: appointments.php?error=db_error');
    exit;
}

// Generate PDF when requested
if (isset($_GET['download_pdf'])) {
    require_once __DIR__ . '/libs/fpdf/fpdf.php';
    
    // Create PDF with smaller margins
    $pdf = new FPDF('P','mm','A4');
    $pdf->AddPage();
    $pdf->SetMargins(15, 10, 15); // Left, Top, Right
    
    // Fetch complete facility details
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT official_name, address_line2 FROM veterinary_facilities WHERE facility_id = ?");
        $stmt->execute([$appointment['facility_id']]);
        $facility = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Facility details error: " . $e->getMessage());
        $facility = ['official_name' => 'Veterinary Hospital', 'address' => '', 'phone' => ''];
    }
    
    // Add logo (replace 'assets/images/logo.png' with your actual logo path)
    if (file_exists('assets/images/logo.png')) {
        $pdf->Image('assets/images/logo.png', 15, 10, 30);
    }
    
    // Header with hospital details
    $pdf->SetY(15); // Position below logo
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,8,$facility['official_name'],0,1,'C');
    
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,6,$facility['address_line2'],0,1,'C');
    
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,6,'Appointment Confirmation',0,1,'C');
    $pdf->Ln(5);
    
    // Big Token Number
    $pdf->SetFont('Arial','B',36);
    $pdf->Cell(0,15,'TOKEN: '.str_pad($appointment['token_number'], 3, '0', STR_PAD_LEFT),0,1,'C');
    $pdf->Ln(5);
    
    // Horizontal line
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(8);
    
    // Appointment Details (2 columns)
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,7,'Appointment Details:',0,1);
    $pdf->SetFont('Arial','',10);
    
    $pdf->Cell(45,6,'Date:',0,0);
    $pdf->Cell(45,6,date('d/m/Y', strtotime($appointment['preferred_date'])),0,0);
    $pdf->Cell(45,6,'Time:',0,0);
    $pdf->Cell(45,6,date('g:i A', strtotime($appointment['preferred_time'])),0,1);
    
    $pdf->Cell(45,6,'Appointment Type:',0,0);
    $pdf->Cell(45,6,$appointment['appointment_type'],0,0);
    $pdf->Cell(45,6,'Facility:',0,0);
    $pdf->Cell(45,6,$facility['official_name'],0,1);
    $pdf->Ln(5);

    // Pet Information
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,7,'Pet Information:',0,1);
    $pdf->SetFont('Arial','',10);
    
    $pdf->Cell(30,6,'Name:',0,0);
    $pdf->Cell(60,6,$pet['pet_name'],0,0);
    $pdf->Cell(30,6,'Age:',0,0);
    $pdf->Cell(60,6,$pet['age_value'].' '.$pet['age_unit'],0,1);
    
    $pdf->Cell(30,6,'Weight:',0,0);
    $pdf->Cell(60,6,$pet['weight'].' kg',0,0);
    $pdf->Cell(30,6,'Sex:',0,0);
    $pdf->Cell(60,6,$pet['sex'],0,1);
    
    $pdf->Cell(30,6,'Species:',0,0);
    $pdf->Cell(60,6,$pet['species_name'],0,0);
    $pdf->Cell(30,6,'Breed:',0,0);
    $pdf->Cell(60,6,$pet['breed_name'],0,1);
    
    // Vaccinations (compact display)
    $pdf->Cell(30,6,'Vaccinations:',0,0);
    $pdf->Cell(0,6,'[List of vaccinations would appear here]',0,1); // You would query vaccinations here
    $pdf->Ln(5);
    
    // Owner Information
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,7,'Owner Information:',0,1);
    $pdf->SetFont('Arial','',10);
    
    $pdf->Cell(30,6,'Name:',0,0);
    $pdf->Cell(60,6,$pet_owner['name'],0,0);
    $pdf->Cell(30,6,'Mobile:',0,0);
    $pdf->Cell(60,6,$pet_owner['mobile'],0,1);
    $pdf->Ln(5);
    
    // Symptoms (if any)
    if (!empty($appointment['symptoms'])) {
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,'Reported Symptoms:',0,1);
        $pdf->SetFont('Arial','',10);
        $pdf->MultiCell(0,6,$appointment['symptoms'],0,'L');
        $pdf->Ln(3);
    }
    
    // Additional Notes (if any)
    if (!empty($appointment['additional_notes'])) {
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,'Additional Notes:',0,1);
        $pdf->SetFont('Arial','',10);
        $pdf->MultiCell(0,6,$appointment['additional_notes'],0,'L');
        $pdf->Ln(3);
    }
    
   // Single Prescription Box
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,7,'Doctor\'s Prescription:',0,1);
    
    $pdf->SetDrawColor(150,150,150);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect(15, $pdf->GetY(), 180, 100); // Single box (width: 180mm, height: 40mm)
    $pdf->Ln(42); // Move below the box

    $pdf->Output('D','Appointment_'.$appointment['token_number'].'.pdf');
    exit;
}


include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Appointment Confirmation</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="appointments.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Appointments
                    </a>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center">
                            <div class="alert alert-success">
                                <h4 class="alert-heading">
                                    <i class="bi bi-check-circle-fill"></i> Appointment Booked Successfully!
                                </h4>
                                <p>Your appointment has been confirmed. Please note your token number:</p>
                                
                                <div class="token-number my-4">
                                    <span class="badge bg-primary fs-1 p-3">
                                        TOKEN: <?= str_pad($appointment['token_number'], 3, '0', STR_PAD_LEFT) ?>
                                    </span>
                                </div>
                                
                                <hr>
                                <p class="mb-0">
                                    <a href="?download_pdf=1" class="btn btn-primary">
                                        <i class="bi bi-download"></i> Download Confirmation
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Appointment Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Date:</strong> <?= date('F j, Y', strtotime($appointment['preferred_date'])) ?></p>
                                    <p><strong>Time:</strong> <?= date('g:i A', strtotime($appointment['preferred_time'])) ?></p>
                                    <p><strong>Type:</strong> <?= htmlspecialchars($appointment['appointment_type']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Facility:</strong> <?= htmlspecialchars($facility['official_name']) ?></p>
                                    <p><strong>Status:</strong> <span class="badge bg-primary"><?= htmlspecialchars($appointment['status']) ?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Pet Owner Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Owner ID:</strong> <?= htmlspecialchars($pet_owner['pet_owner_id']) ?></p>
                                    <p><strong>Name:</strong> <?= htmlspecialchars($pet_owner['name']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Phone:</strong> <?= htmlspecialchars($pet_owner['mobile']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($pet_owner['email']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Pet Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Pet ID:</strong> <?= htmlspecialchars($pet['pet_id']) ?></p>
                                    <p><strong>Name:</strong> <?= htmlspecialchars($pet['pet_name']) ?></p>
                                    <p><strong>Species:</strong> <?= htmlspecialchars($pet['species_name']) ?></p>
                                    <p><strong>Breed:</strong> <?= htmlspecialchars($pet['breed_name']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Sex:</strong> <?= htmlspecialchars($pet['sex']) ?></p>
                                    <p><strong>Age:</strong> <?= htmlspecialchars($pet['age_value'] . ' ' . $pet['age_unit']) ?></p>
                                    <p><strong>Weight:</strong> <?= htmlspecialchars($pet['weight']) ?> kg</p>
                                    <p><strong>Neutered:</strong> <?= $pet['neutered'] ? 'Yes' : 'No' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Space before footer -->
            <div style="margin-bottom: 100px;"></div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>