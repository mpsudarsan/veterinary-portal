<?php
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/libs/fpdf/fpdf.php';

// Start session and validate
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Check if appointment_id is provided
if (empty($_GET['appointment_id'])) {
    die("No appointment ID provided");
}

$appointment_id = $_GET['appointment_id'];

try {
    $db = getDatabaseConnection();
    
    // Get appointment details
    $stmt = $db->prepare("
        SELECT a.*, 
               p.*, 
               po.name AS owner_name, po.mobile AS owner_phone, po.email AS owner_email,
               s.species_name, b.breed_name,
               f.official_name AS facility_name, f.address_line1, f.address_line2
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN pet_owners po ON p.pet_owner_id = po.pet_owner_id
        JOIN species s ON p.species_id = s.species_id
        JOIN breeds b ON p.breed_id = b.breed_id
        JOIN veterinary_facilities f ON a.facility_id = f.facility_id
        WHERE a.appointment_id = ?
    ");
    $stmt->execute([$appointment_id]);
    $appointment = $stmt->fetch();
    
    if (!$appointment) {
        die("Appointment not found");
    }
    
    // Create PDF with smaller margins
    $pdf = new FPDF('P','mm','A4');
    $pdf->AddPage();
    $pdf->SetMargins(15, 10, 15); // Left, Top, Right
    
    // Add logo (replace 'assets/images/logo.png' with your actual logo path)
    if (file_exists('assets/images/logo.png')) {
        $pdf->Image('assets/images/logo.png', 15, 10, 30);
    }
    
    // Header with hospital details
    $pdf->SetY(15); // Position below logo
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,8,$appointment['facility_name'],0,1,'C');
    
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,6,$appointment['address_line2'],0,1,'C');
    
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
    $pdf->Cell(45,6,$appointment['facility_name'],0,1);
    $pdf->Ln(5);

    // Pet Information
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,7,'Pet Information:',0,1);
    $pdf->SetFont('Arial','',10);
    
    $pdf->Cell(30,6,'Name:',0,0);
    $pdf->Cell(60,6,$appointment['pet_name'],0,0);
    $pdf->Cell(30,6,'Age:',0,0);
    $pdf->Cell(60,6,$appointment['age_value'].' '.$appointment['age_unit'],0,1);
    
    $pdf->Cell(30,6,'Weight:',0,0);
    $pdf->Cell(60,6,$appointment['weight'].' kg',0,0);
    $pdf->Cell(30,6,'Sex:',0,0);
    $pdf->Cell(60,6,$appointment['sex'],0,1);
    
    $pdf->Cell(30,6,'Species:',0,0);
    $pdf->Cell(60,6,$appointment['species_name'],0,0);
    $pdf->Cell(30,6,'Breed:',0,0);
    $pdf->Cell(60,6,$appointment['breed_name'],0,1);
    
    // Vaccinations (compact display)
    $pdf->Cell(30,6,'Vaccinations:',0,0);
    $pdf->Cell(0,6,'[List of vaccinations would appear here]',0,1);
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
    
    // Single Prescription Box
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,7,'Doctor\'s Prescription:',0,1);
    
    $pdf->SetDrawColor(150,150,150);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect(15, $pdf->GetY(), 180, 100); // Single box (width: 180mm, height: 40mm)
    $pdf->Ln(42); // Move below the box

    // Output the PDF
    $pdf->Output('D','Appointment_'.$appointment['token_number'].'.pdf');
    exit;

} catch (PDOException $e) {
    error_log("PDF generation error: " . $e->getMessage());
    die("Error generating PDF. Please try again later.");
}