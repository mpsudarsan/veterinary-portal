<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/libs/fpdf/fpdf.php';

// Check staff login
if (!isset($_SESSION['staff_logged_in'])) {
    header('Location: staff_login.php');
    exit;
}

// Get appointment ID
if (!isset($_GET['id'])) {
    header('Location: staff_dashboard.php');
    exit;
}

$appointment_id = $_GET['id'];
$facility_id = $_SESSION['facility_id'];

try {
    $db = getDatabaseConnection();
    
    // Get appointment details
    $stmt = $db->prepare("
        SELECT 
            a.*,
            p.pet_name,
            p.pet_id,
            po.name AS owner_name,
            po.mobile AS owner_phone,
            po.email AS owner_email,
            f.official_name AS facility_name,
            f.address_line2 AS facility_address
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN pet_owners po ON a.created_by = po.pet_owner_id
        JOIN veterinary_facilities f ON a.facility_id = f.facility_id
        WHERE a.appointment_id = ? AND a.facility_id = ?
    ");
    $stmt->execute([$appointment_id, $facility_id]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        header('Location: staff_dashboard.php?error=invalid_appointment');
        exit;
    }
    
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
    
    // Generate PDF
    $pdf = new FPDF('P','mm','A4');
    $pdf->AddPage();
    $pdf->SetMargins(15, 10, 15);
    
    // Add logo if available
    if (file_exists('assets/images/logo.png')) {
        $pdf->Image('assets/images/logo.png', 15, 10, 30);
    }
    
    // Header with hospital details
    $pdf->SetY(15);
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,8,$appointment['facility_name'],0,1,'C');
    
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,6,$appointment['facility_address'],0,1,'C');
    
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,6,'Appointment Details',0,1,'C');
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
    $pdf->Cell(45,6,$appointment['facility_name'],0,1);
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
    $pdf->Ln(5);
    
    // Owner Information
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,7,'Owner Information:',0,1);
    $pdf->SetFont('Arial','',10);
    
    $pdf->Cell(30,6,'Name:',0,0);
    $pdf->Cell(60,6,$appointment['owner_name'],0,0);
    $pdf->Cell(30,6,'Mobile:',0,0);
    $pdf->Cell(60,6,$appointment['owner_phone'],0,1);
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
    
    // Prescription Box
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,7,'Doctor\'s Prescription:',0,1);
    
    $pdf->SetDrawColor(150,150,150);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect(15, $pdf->GetY(), 180, 100);
    $pdf->Ln(42);

    $pdf->Output('D','Appointment_'.$appointment['token_number'].'.pdf');
    exit;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: staff_dashboard.php?error=pdf_error');
    exit;
}