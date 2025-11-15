<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if registration was successful
if (empty($_SESSION['registration_success'])) {
    header('Location: registration.php');
    exit;
}

// Get pet owner details from session
$petOwnerId = $_SESSION['pet_owner_id'] ?? '';
$name = $_SESSION['name'] ?? '';
$username = $_SESSION['username'] ?? '';
$mobile = $_SESSION['mobile'] ?? '';

// Generate PDF if requested
if (isset($_POST['download_pdf'])) {
    require_once 'C:/xampp/htdocs/veterinary_portal/libs/fpdf/fpdf.php';
    
    // Create new PDF document
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Set document properties
    $pdf->SetTitle('Pet Owner Registration Details');
    $pdf->SetAuthor('Veterinary Portal');
    
    // Add logo
    $logoPath = 'C:/xampp/htdocs/veterinary_portal/assets/images/logo.png';
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 10, 30);
    }
    
    // Title
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 20, 'Pet Owner Registration Details', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Set font for details
    $pdf->SetFont('Arial', '', 12);
    
    // Registration details
    $pdf->Cell(50, 10, 'Pet Owner ID:', 0, 0);
    $pdf->Cell(0, 10, $petOwnerId, 0, 1);
    
    $pdf->Cell(50, 10, 'Full Name:', 0, 0);
    $pdf->Cell(0, 10, $name, 0, 1);
    
    $pdf->Cell(50, 10, 'Username:', 0, 0);
    $pdf->Cell(0, 10, $username, 0, 1);
    
    $pdf->Cell(50, 10, 'Mobile Number:', 0, 0);
    $pdf->Cell(0, 10, $mobile, 0, 1);
    
    $pdf->Cell(50, 10, 'Registration Date:', 0, 0);
    $pdf->Cell(0, 10, date('F j, Y'), 0, 1);
    
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Thank you for registering with our veterinary portal.');
    
    // Output PDF as download
    $pdf->Output('D', 'pet_owner_registration_'.$petOwnerId.'.pdf');
    exit;
}

include 'includes/header.php';
?>

<div class="container mt-4" style="max-width: 650px;">
    <div class="card border-success">
        <div class="card-header bg-success text-white py-2">
            <h5 class="mb-0 text-center">
                <i class="bi bi-check-circle-fill me-2"></i>Registration Successful
            </h5>
        </div>
        
        <div class="card-body py-3">
            <div class="alert alert-success py-2 mb-3">
                Thank you for registering with Veterinary Portal.
            </div>
            
            <div class="registration-details px-3">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Pet Owner ID</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($petOwnerId) ?></dd>
                    
                    <dt class="col-sm-3">Full Name</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($name) ?></dd>
                    
                    <dt class="col-sm-3">Username</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($username) ?></dd>
                    
                    <dt class="col-sm-3">Mobile Number</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($mobile) ?></dd>
                    
                    <dt class="col-sm-3">Registration Date</dt>
                    <dd class="col-sm-9"><?= date('F j, Y') ?></dd>
                </dl>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <form method="post">
                    <button type="submit" name="download_pdf" class="btn btn-primary">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Download Receipt
                    </button>
                </form>
                
                <div>
                    <a href="login.php" class="btn btn-success me-2">  
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-house me-1"></i>Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-5"></div>

<?php include 'includes/footer.php'; ?>