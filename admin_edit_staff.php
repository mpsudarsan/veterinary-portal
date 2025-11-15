<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . BASE_URL . 'admin_login.php');
    exit;
}

// Get staff member details
$staff = [];
if (isset($_GET['id'])) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT 
                fs.staff_id,
                fs.full_name,
                fs.email,
                fs.phone,
                fs.is_active,
                sr.role_name,
                vf.official_name AS facility_name
            FROM facility_staff fs
            JOIN staff_roles sr ON fs.role_id = sr.role_id
            JOIN veterinary_facilities vf ON fs.facility_id = vf.facility_id
            WHERE fs.staff_id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $staff = $stmt->fetch();
        
        if (!$staff) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Staff member not found'];
            header('Location: ' . BASE_URL . 'admin_staff.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Staff fetch error: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error loading staff data'];
        header('Location: ' . BASE_URL . 'admin_staff.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("UPDATE facility_staff SET phone = ? WHERE staff_id = ?");
        $stmt->execute([$_POST['phone'], $_POST['staff_id']]);
        
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Staff contact number updated successfully'];
        header('Location: ' . BASE_URL . 'admin_staff.php');
        exit;
    } catch (PDOException $e) {
        error_log("Staff update error: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error updating staff information'];
    }
}

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Edit Staff Member</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= BASE_URL ?>admin_staff.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Staff List
                    </a>
                </div>
            </div>

            <!-- Display messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message']['type'] ?>">
                    <?= $_SESSION['message']['text'] ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-person-lines-fill"></i> Staff Information
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="staff_id" value="<?= $staff['staff_id'] ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($staff['full_name']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($staff['role_name']) ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Facility</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($staff['facility_name']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <input type="text" class="form-control" value="<?= $staff['is_active'] ? 'Active' : 'Inactive' ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($staff['phone']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" name="update_staff" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Contact Number
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>