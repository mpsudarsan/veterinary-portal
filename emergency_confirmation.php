<?php
session_start();
if (!isset($_SESSION['emergency_submitted'])) {
    header("Location: emergency_report.php");
    exit;
}

$report_id = $_SESSION['emergency_report_id'] ?? 'N/A';
unset($_SESSION['emergency_submitted']);
unset($_SESSION['emergency_report_id']);

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card text-center border-success">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0"><i class="bi bi-check-circle-fill me-2"></i>Report Submitted</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <i class="bi bi-check2-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h4 class="mb-3">Emergency Alert Received</h4>
                    <p class="mb-3">Our response team has been notified and will take appropriate action.</p>
                    
                    <div class="alert alert-info text-start small">
                        <p class="fw-bold mb-2">Reference #: <?= htmlspecialchars($report_id) ?></p>
                        <p class="mb-1">• Team will assess within 15-30 minutes</p>
                        <p class="mb-1">• You may receive a call for details</p>
                        <p>• For updates, call: +91-XXX-XXX-XXXX</p>
                    </div>
                    
                    <a href="index.php" class="btn btn-outline-success mt-2">
                        <i class="bi bi-house-door-fill me-2"></i>Return Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>