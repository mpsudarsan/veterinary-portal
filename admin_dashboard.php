<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . BASE_URL . 'admin/admin_login.php');
    exit;
}

// Initialize variables
$stats = [
    'veterinarians' => 0,
    'hospitals' => 0,
    'announcements' => 0
];
$recent_activity = [];
$system_alerts = [];

// Handle Announcement Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    try {
        $db = getDatabaseConnection();
        
        // Get form data - FIXED: Correct syntax and field names
// Get form data - FIXED SYNTAX
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$priority = $_POST['priority'] ?? 'medium';
$is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validation
        if (empty($title) || empty($content) || empty($start_date) || empty($end_date)) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'All required fields must be filled!'];
        } else {
            // Insert announcement into database
            $stmt = $db->prepare("
                INSERT INTO announcements (title, content, start_date, end_date, priority, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            if ($stmt->execute([$title, $content, $start_date, $end_date, $priority, $is_active])) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Announcement added successfully!'];
            } else {
                $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error adding announcement. Please try again.'];
            }
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
        
    } catch (PDOException $e) {
        error_log("Announcement insertion error: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Database error occurred.'];
    }
}

try {
    $db = getDatabaseConnection();
    
    // Get veterinarian count (from facility_staff with high permission roles)
    $stmt = $db->prepare("SELECT COUNT(*) FROM facility_staff fs
                         JOIN staff_roles sr ON fs.role_id = sr.role_id
                         WHERE sr.permission_level >= 3 
                         AND fs.is_active = 1");
    $stmt->execute();
    $stats['veterinarians'] = $stmt->fetchColumn();
    
    // Get hospital count
    $stmt = $db->prepare("SELECT COUNT(*) FROM veterinary_facilities WHERE is_active = 1");
    $stmt->execute();
    $stats['hospitals'] = $stmt->fetchColumn();
    
    // Get active announcements
    $stmt = $db->prepare("SELECT COUNT(*) FROM announcements 
                         WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW()");
    $stmt->execute();
    $stats['announcements'] = $stmt->fetchColumn();
    
    // Get recent activity
    $stmt = $db->prepare("SELECT * FROM admin_activity_log 
                         ORDER BY activity_date DESC LIMIT 5");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll();
    
    // Get system alerts
    $stmt = $db->prepare("SELECT * FROM system_alerts 
                         WHERE is_resolved = 0 
                         ORDER BY alert_level DESC, created_at DESC LIMIT 3");
    $stmt->execute();
    $system_alerts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error loading dashboard data'];
}

// Include header
include __DIR__ . '/includes/header.php';
?>
<link href="<?php echo BASE_URL; ?>assets/css/ad_dashboard.css" rel="stylesheet">
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/staff_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Administrator Dashboard</h1>
            </div>

            <!-- Display messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message']['type'] ?> alert-dismissible fade show">
                    <?= $_SESSION['message']['text'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <!-- Quick Stats Row -->
          <div  class="row g-3 mb-4">
                <div class="col-md-4">
                <div style="background-color: #f10f1eff; border-radius: 5%;border: 2px solid black " class="stat-card blue p-3">
                        <div  class="d-flex justify-content-between">
                            <div>
                                <h6>Veterinary Staff</h6>
                                <h2><?= $stats['veterinarians'] ?? 0 ?></h2>
                            </div>
                            <i class="bi bi-people-fill" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                        <a href="<?= BASE_URL ?>admin_staff.php" class="text-white">View All <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div style="background-color: yellow; border-radius: 5%;border: 2px solid black" class="stat-card blue p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Hospitals/Centers</h6>
                                <h2><?= $stats['hospitals'] ?? 0 ?></h2>
                            </div>
                            <i class="bi bi-hospital" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                        <a href="<?= BASE_URL ?>admin_hospitals.php" class="text-white">Manage <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <div class="col-md-4">
                    <div style="background-color: green; border-radius: 5%;border: 2px solid black" class="stat-card blue p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Active Announcements</h6>
                                <h2><?= $stats['announcements'] ?? 0 ?></h2>
                            </div>
                            <i class="bi bi-megaphone" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                        <a href="<?= BASE_URL ?>admin_announcements.php" class="text-white">Manage <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card admin-card">
                        <div class="card-header">
                            <i class="bi bi-lightning"></i> Quick Actions
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="<?= BASE_URL ?>add_staff.php" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-person-plus"></i> Add Staff Member
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="<?= BASE_URL ?>admin_add_hospital.php" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-hospital"></i> Add Hospital/Center
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="<?= BASE_URL ?>admin_add_vaccination.php" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-plus-circle"></i> Add Vaccination
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="<?= BASE_URL ?>admin_add_species.php" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-plus-square"></i> Add Species
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="<?= BASE_URL ?>admin_transfer.php" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-hospital"></i>Process Transfer
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#announcementModal">
                                        <i class="bi bi-megaphone"></i> What's New
                                    </button>
                                </div>
                                 <div class="col-md-4 mb-3">
    <a href="<?= BASE_URL ?>add_pharmacist.php" class="btn btn-outline-danger w-100">
        <i class="bi bi-capsule"></i> Add Pharmacist
    </a>
</div>
<div class="col-md-4 mb-3">
    <a href="<?= BASE_URL ?>add_veterinary_surgeon.php" class="btn btn-outline-danger w-100">
        <i class="bi bi-scissors"></i> Add Veterinary Surgeon
    </a></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Announcement Modal -->
            <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="announcementModalLabel">Add New Announcement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title*</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="content" class="form-label">Content*</label>
                                    <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label">Start Date*</label>
                                        <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="form-label">End Date*</label>
                                        <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="add_announcement" class="btn btn-primary">Save Announcement</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Activity and System Alerts -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card admin-card mb-4">
                        <div class="card-header">
                            <i class="bi bi-clock-history"></i> Recent Activity
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php if (empty($recent_activity)): ?>
                                    <div class="list-group-item">
                                        <p class="text-muted">No recent activity found.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recent_activity as $activity): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    <i class="bi bi-<?= $activity['activity_icon'] ?? 'info-circle' ?> text-primary"></i> 
                                                    <?= htmlspecialchars($activity['activity_type']) ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?= date('M j, H:i', strtotime($activity['activity_date'])) ?>
                                                </small>
                                            </div>
                                            <p><?= htmlspecialchars($activity['activity_details']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card admin-card mb-4">
                        <div class="card-header">
                            <i class="bi bi-exclamation-triangle"></i> System Alerts
                        </div>
                        <div class="card-body">
                            <?php if (empty($system_alerts)): ?>
                                <p class="text-muted">No system alerts.</p>
                            <?php else: ?>
                                <?php foreach ($system_alerts as $alert): ?>
                                    <div class="alert alert-<?= 
                                        $alert['alert_level'] === 'high' ? 'danger' : 
                                        ($alert['alert_level'] === 'medium' ? 'warning' : 'info') 
                                    ?>">
                                        <i class="bi bi-<?= 
                                            $alert['alert_level'] === 'high' ? 'exclamation-triangle-fill' : 
                                            ($alert['alert_level'] === 'medium' ? 'exclamation-circle-fill' : 'info-circle-fill') 
                                        ?>"></i>
                                        <strong><?= htmlspecialchars($alert['alert_title']) ?>:</strong> 
                                        <?= htmlspecialchars($alert['alert_message']) ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Auto-dismiss alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        }, 5000);
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
