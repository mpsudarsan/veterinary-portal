<?php
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/appointment_functions.php';

// Start session and validate
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['pet_owner_id']) || empty($_SESSION['logged_in'])) {
    header('Location: login.php?reason=not_logged_in');
    exit;
}

$pet_owner_id = $_SESSION['pet_owner_id'];
$appointments = [];
$filter_status = $_GET['status'] ?? 'all';
$filter_pet = $_GET['pet_id'] ?? 'all';
$pets = [];

// Fetch data
try {
    $db = getDatabaseConnection();
    
    // Get pets for filter dropdown
    $stmt = $db->prepare("SELECT pet_id, pet_name FROM pets WHERE pet_owner_id = ? ORDER BY pet_name");
    $stmt->execute([$pet_owner_id]);
    $pets = $stmt->fetchAll();
    
    // First, handle expired appointments (status is Pending or Confirmed but date/time has passed)
    $current_datetime = date('Y-m-d H:i:s');
    $update_stmt = $db->prepare("
        UPDATE appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        SET a.status = 'Expired'
        WHERE p.pet_owner_id = ?
        AND a.status IN ('Pending', 'Confirmed')
        AND CONCAT(a.preferred_date, ' ', a.preferred_time) < ?
    ");
    $update_stmt->execute([$pet_owner_id, $current_datetime]);
    
    // Base query for appointments
    $query = "
        SELECT a.*, f.official_name AS facility_name, p.pet_name 
        FROM appointments a
        JOIN veterinary_facilities f ON a.facility_id = f.facility_id
        JOIN pets p ON a.pet_id = p.pet_id
        WHERE p.pet_owner_id = ?
    ";
    
    $params = [$pet_owner_id];
    
    // Apply filters
    if ($filter_status !== 'all') {
        $query .= " AND a.status = ?";
        $params[] = $filter_status;
    }
    
    if ($filter_pet !== 'all') {
        $query .= " AND a.pet_id = ?";
        $params[] = $filter_pet;
    }
    
    $query .= " ORDER BY a.preferred_date DESC, a.preferred_time DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "A database error occurred. Please try again.";
}

// Handle PDF download
if (isset($_GET['download_pdf'])) {
    $appointment_id = $_GET['download_pdf'];
    
    try {
        // Verify appointment belongs to this owner
        $stmt = $db->prepare("
            SELECT a.* 
            FROM appointments a
            JOIN pets p ON a.pet_id = p.pet_id
            WHERE a.appointment_id = ? AND p.pet_owner_id = ?
        ");
        $stmt->execute([$appointment_id, $pet_owner_id]);
        $appointment = $stmt->fetch();
        
        if (!$appointment) {
            header('Location: appointments.php?error=invalid_appointment');
            exit;
        }
        
        // Get pet details
        $pet = getPetDetails($db, $appointment['pet_id']);
        $facility = getFacilityDetails($db, $appointment['facility_id']);
        $pet_owner = $db->prepare("SELECT * FROM pet_owners WHERE pet_owner_id = ?");
        $pet_owner->execute([$pet_owner_id]);
        $pet_owner = $pet_owner->fetch();
        
        // Generate PDF
        require_once __DIR__ . '/libs/fpdf/fpdf.php';
        
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
        error_log("PDF generation error: " . $e->getMessage());
        header('Location: appointments.php?error=pdf_error');
        exit;
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Appointments</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="book_appointment.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle"></i> Book New Appointment
                    </a>
                </div>
            </div>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    $error = htmlspecialchars($_GET['error']);
                    switch ($error) {
                        case 'invalid_appointment':
                            echo "Invalid appointment selected.";
                            break;
                        case 'pdf_error':
                            echo "Failed to generate PDF. Please try again.";
                            break;
                        default:
                            echo "An error occurred. Please try again.";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Filter by Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Confirmed" <?= $filter_status === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="Completed" <?= $filter_status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= $filter_status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="Expired" <?= $filter_status === 'Expired' ? 'selected' : '' ?>>Expired</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="pet_id" class="form-label">Filter by Pet</label>
                            <select class="form-select" id="pet_id" name="pet_id">
                                <option value="all" <?= $filter_pet === 'all' ? 'selected' : '' ?>>All Pets</option>
                                <?php foreach ($pets as $pet): ?>
                                    <option value="<?= htmlspecialchars($pet['pet_id']) ?>" 
                                        <?= $filter_pet == $pet['pet_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pet['pet_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                            <a href="appointments.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($appointments)): ?>
                <div class="alert alert-info">
                    No appointments found. <a href="book_appointment.php" class="alert-link">Book a new appointment</a>.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Token</th>
                                <th>Date & Time</th>
                                <th>Pet</th>
                                <th>Facility</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): 
                                $appointment_datetime = $appointment['preferred_date'] . ' ' . $appointment['preferred_time'];
                                $is_expired = strtotime($appointment_datetime) < time() && in_array($appointment['status'], ['Pending', 'Confirmed']);
                            ?>
                                <tr class="<?= $is_expired ? 'table-secondary' : '' ?>">
                                    <td><?= str_pad($appointment['token_number'], 3, '0', STR_PAD_LEFT) ?></td>
                                    <td>
                                        <?= date('M j, Y', strtotime($appointment['preferred_date'])) ?><br>
                                        <?= date('g:i A', strtotime($appointment['preferred_time'])) ?>
                                        <?php if ($is_expired): ?>
                                            <span class="badge bg-danger mt-1">Past Due</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($appointment['pet_name']) ?></td>
                                    <td><?= htmlspecialchars($appointment['facility_name']) ?></td>
                                    <td><?= htmlspecialchars($appointment['appointment_type']) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                            switch ($appointment['status']) {
                                                case 'Confirmed': echo 'bg-success'; break;
                                                case 'Pending': echo 'bg-warning text-dark'; break;
                                                case 'Completed': echo 'bg-info'; break;
                                                case 'Cancelled': echo 'bg-secondary'; break;
                                                case 'Expired': echo 'bg-danger'; break;
                                                default: echo 'bg-light text-dark';
                                            }
                                            ?>">
                                            <?= htmlspecialchars($appointment['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="appointments.php?download_pdf=<?= $appointment['appointment_id'] ?>" 
                                               class="btn btn-outline-primary" title="Download PDF">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <?php if (($appointment['status'] === 'Pending' || $appointment['status'] === 'Confirmed') && !$is_expired): ?>
                                                <button class="btn btn-outline-danger" title="Cancel Appointment" disabled>
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Space before footer -->
            <div style="margin-bottom: 100px;"></div>
        </main>
    </div>
</div>
<script>
function checkForUpdates() {
    $.ajax({
        url: 'api/check_appointment_updates.php',
        data: {
            last_check: localStorage.getItem('last_update_check') || 0,
            pet_owner_id: <?= $pet_owner_id ?>
        },
        success: function(response) {
            if (response.updated) {
                location.reload(); // Refresh if changes detected
            }
            localStorage.setItem('last_update_check', Date.now());
        }
    });
}

// Check every 30 seconds
setInterval(checkForUpdates, 30000);
</script>
<?php include 'includes/footer.php'; ?>