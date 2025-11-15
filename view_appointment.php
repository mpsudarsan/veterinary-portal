<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['pet_owner_id']) || empty($_SESSION['logged_in'])) {
    header('Location: login.php?error=not_logged_in');
    exit;
}

$pet_owner_id = $_SESSION['pet_owner_id'];
$appointment_id = $_GET['id'] ?? 0;

// Validate appointment ID
if (!$appointment_id || !is_numeric($appointment_id)) {
    include 'includes/header.php';
    echo '
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="alert alert-danger text-center">
                    <h4><i class="bi bi-exclamation-triangle-fill"></i> Invalid Appointment ID</h4>
                    <p>The appointment ID provided is not valid.</p>
                    <a href="my_appointments.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Appointments
                    </a>
                </div>
            </div>
        </div>
    </div>';
    include 'includes/footer.php';
    exit;
}

try {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT 
            a.*,
            p.pet_name, p.sex, p.age_value, p.age_unit, p.weight, p.neutered,
            s.species_name,
            b.breed_name,
            f.official_name AS facility_name,
            f.address_line1, f.address_line2,
            o.name AS owner_name, o.mobile, o.email
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN species s ON p.species_id = s.species_id
        JOIN breeds b ON p.breed_id = b.breed_id
        JOIN veterinary_facilities f ON a.facility_id = f.facility_id
        JOIN pet_owners o ON a.pet_owner_id = o.pet_owner_id
        WHERE a.appointment_id = ? AND a.pet_owner_id = ?
        LIMIT 1
    ");
    $stmt->execute([$appointment_id, $pet_owner_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$appointment) {
        throw new Exception("Appointment not found or access denied");
    }
} catch (Exception $e) {
    include 'includes/header.php';
    echo '
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="alert alert-danger text-center">
                    <h4><i class="bi bi-exclamation-triangle-fill"></i> Appointment Not Found</h4>
                    <p>We couldn\'t find the requested appointment or you don\'t have permission to view it.</p>
                    <a href="my_appointments.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Appointments
                    </a>
                </div>
            </div>
        </div>
    </div>';
    include 'includes/footer.php';
    exit;
}

// Handle PDF download
if (isset($_GET['download_pdf'])) {
    require_once 'libs/fpdf/fpdf.php';

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetMargins(15,10,15);

    // Add logo if available
    if (file_exists('assets/images/logo.png')) {
        $pdf->Image('assets/images/logo.png', 15, 10, 30);
    }

    $pdf->SetY(15);
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,$appointment['facility_name'],0,1,'C');
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,6,$appointment['address_line2'],0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,6,'Appointment Confirmation',0,1,'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial','B',36);
    $pdf->Cell(0,20,'TOKEN: ' . str_pad($appointment['token_number'], 3, '0', STR_PAD_LEFT),0,1,'C');
    $pdf->Ln(6);

    // Horizontal line
    $pdf->SetLineWidth(0.6);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(6);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Appointment Details:',0,1);
    $pdf->SetFont('Arial','',10);

    $pdf->Cell(45,7,'Date:',0,0);
    $pdf->Cell(60,7,date('d/m/Y', strtotime($appointment['preferred_date'])),0,0);
    $pdf->Cell(30,7,'Time:',0,0);
    $pdf->Cell(0,7,date('g:i A', strtotime($appointment['preferred_time'])),0,1);

    $pdf->Cell(45,7,'Type:',0,0);
    $pdf->Cell(60,7,$appointment['appointment_type'],0,0);
    $pdf->Cell(30,7,'Facility:',0,0);
    $pdf->Cell(0,7,$appointment['facility_name'],0,1);

    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Pet Information:',0,1);
    $pdf->SetFont('Arial','',10);

    $pdf->Cell(45,7,'Name:',0,0);
    $pdf->Cell(60,7,$appointment['pet_name'],0,0);
    $pdf->Cell(45,7,'Age:',0,0);
    $pdf->Cell(0,7,$appointment['age_value']. ' ' . $appointment['age_unit'],0,1);

    $pdf->Cell(45,7,'Sex:',0,0);
    $pdf->Cell(60,7,$appointment['sex'],0,0);
    $pdf->Cell(45,7,'Breed:',0,0);
    $pdf->Cell(0,7,$appointment['breed_name'],0,1);

    $pdf->Cell(45,7,'Weight:',0,0);
    $pdf->Cell(60,7,$appointment['weight'].' kg',0,0);
    $pdf->Cell(45,7,'Neutered:',0,0);
    $pdf->Cell(60,7,$appointment['neutered'] ? 'Yes' : 'No',0,1);

    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Owner Information:',0,1);
    $pdf->SetFont('Arial','',10);

    $pdf->Cell(45,7,'Name:',0,0);
    $pdf->Cell(60,7,$appointment['owner_name'],0,1);

    $pdf->Cell(45,7,'Mobile:',0,0);
    $pdf->Cell(60,7,$appointment['mobile'],0,1);

    $pdf->Cell(45,7,'Email:',0,0);
    $pdf->Cell(60,7,$appointment['email'],0,1);

    $pdf->Ln(5);
    if (!empty($appointment['symptoms'])) {
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10,'Symptoms:',0,1);
        $pdf->SetFont('Arial','',10);
        $pdf->MultiCell(0,7,strip_tags($appointment['symptoms']),0,'L');
        $pdf->Ln(5);
    }
    if (!empty($appointment['additional_notes'])) {
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10,'Additional Notes:',0,1);
        $pdf->SetFont('Arial','',10);
        $pdf->MultiCell(0,7,strip_tags($appointment['additional_notes']),0,'L');
        $pdf->Ln(5);
    }

    // Prescription Box (placeholder)
    $pdf->SetXY(15, $pdf->GetY());
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,"Doctor's Prescription:",0,1);
    $pdf->Rect(15, $pdf->GetY(), 180, 40);
    $pdf->Ln(45);

    $pdf->Output('D','Appointment_'.$appointment['token_number'].'.pdf');
    exit();
}

include 'includes/header.php';
?>

<!-- Bootstrap 5 icons (optional) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<style>
@media print { .no-print { display:none!important; } }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="alert alert-success text-center">
                <h4><i class="bi bi-check-circle-fill"></i> Appointment Booked Successfully</h4>
                <p>Your appointment is confirmed with token number:</p>
                <h1 class="fw-bold"><?= str_pad($appointment['token_number'], 3, '0', STR_PAD_LEFT) ?></h1>
            </div>

            <div class="mb-3 text-center">
                <a href="?download_pdf=1&id=<?= $appointment_id ?>" class="btn btn-primary me-2">
                    <i class="bi bi-file-earmark-pdf"></i> Download Confirmation (PDF)
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="bi bi-printer"></i> Print This Page
                </button>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Appointment Details</span>
                    <span class="badge bg-<?= $appointment['status'] === 'Completed' ? 'success' : ($appointment['status'] === 'Pending' ? 'warning' : 'info') ?>">
                        <?= ucfirst(htmlspecialchars($appointment['status'])) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Date:</strong> <?= date('F j, Y', strtotime($appointment['preferred_date'])) ?></p>
                            <p><strong>Time:</strong> <?= date('g:i A', strtotime($appointment['preferred_time'])) ?></p>
                            <p><strong>Type:</strong> <?= htmlspecialchars($appointment['appointment_type']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Facility:</strong> <?= htmlspecialchars($appointment['facility_name']) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($appointment['address_line1']) ?>, <?= htmlspecialchars($appointment['address_line2']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Owner Information</div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($appointment['owner_name']) ?></p>
                    <p><strong>Mobile:</strong> <?= htmlspecialchars($appointment['mobile']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($appointment['email']) ?></p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Pet Information</div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($appointment['pet_name']) ?></p>
                    <p><strong>Species:</strong> <?= htmlspecialchars($appointment['species_name']) ?></p>
                    <p><strong>Breed:</strong> <?= htmlspecialchars($appointment['breed_name']) ?></p>
                    <p><strong>Sex:</strong> <?= htmlspecialchars($appointment['sex']) ?></p>
                    <p><strong>Age:</strong> <?= htmlspecialchars($appointment['age_value']) ?> <?= htmlspecialchars($appointment['age_unit']) ?></p>
                    <p><strong>Weight:</strong> <?= htmlspecialchars($appointment['weight']) ?> kg</p>
                    <p><strong>Neutered:</strong> <?= $appointment['neutered'] ? 'Yes' : 'No' ?></p>
                </div>
            </div>

            <?php if (!empty($appointment['symptoms'])): ?>
                <div class="card mb-3">
                    <div class="card-header">Reported Symptoms</div>
                    <div class="card-body">
                        <p><?= nl2br(htmlspecialchars($appointment['symptoms'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($appointment['additional_notes'])): ?>
                <div class="card mb-3">
                    <div class="card-header">Additional Notes</div>
                    <div class="card-body">
                        <p><?= nl2br(htmlspecialchars($appointment['additional_notes'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-5"></div>

            <div class="text-center no-print">
                <a href="my_appointments.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Appointments
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>