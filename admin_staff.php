<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . BASE_URL . 'admin_login.php');
    exit;
}

// Handle staff status toggle and deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDatabaseConnection();
        
        if (isset($_POST['toggle_status'])) {
            $stmt = $db->prepare("UPDATE facility_staff SET is_active = NOT is_active WHERE staff_id = ?");
            $stmt->execute([$_POST['staff_id']]);
            $action = $_POST['is_active'] ? 'deactivated' : 'activated';
            $_SESSION['message'] = ['type' => 'success', 'text' => "Staff member $action successfully"];
        }
        
        if (isset($_POST['delete_staff'])) {
            $stmt = $db->prepare("DELETE FROM facility_staff WHERE staff_id = ?");
            $stmt->execute([$_POST['staff_id']]);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Staff member deleted successfully'];
        }
        
    } catch (PDOException $e) {
        error_log("Staff operation error: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error processing staff operation'];
    }
    header('Location: ' . BASE_URL . 'admin_staff.php');
    exit;
}

// Get all staff with their roles and facility info
$staff_members = [];
try {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("
        SELECT 
            fs.staff_id,
            fs.full_name,
            fs.email,
            fs.phone,
            fs.is_active,
            fs.last_login,
            fs.created_at,
            sr.role_name,
            sr.permission_level,
            vf.official_name AS facility_name
        FROM facility_staff fs
        JOIN staff_roles sr ON fs.role_id = sr.role_id
        JOIN veterinary_facilities vf ON fs.facility_id = vf.facility_id
        ORDER BY fs.is_active DESC, sr.permission_level DESC, fs.full_name ASC
    ");
    $stmt->execute();
    $staff_members = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Staff fetch error: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error loading staff data'];
}

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Facility Staff Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= BASE_URL ?>add_staff.php"class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Add New Staff
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
                    <i class="bi bi-people-fill"></i> All Staff Members
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Staff ID</th>
                                    <th>Full Name</th>
                                    <th>Role</th>
                                    <th>Facility</th>
                                    <th>Contact</th>
                                    <th>Last Login</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff_members as $staff): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($staff['staff_id']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($staff['full_name']) ?>
                                            <?php if ($staff['permission_level'] >= 4): ?>
                                                <span class="badge bg-info ms-2">Vet</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($staff['role_name']) ?></td>
                                        <td><?= htmlspecialchars($staff['facility_name']) ?></td>
                                        <td>
                                            <div><?= htmlspecialchars($staff['email']) ?></div>
                                            <div><?= htmlspecialchars($staff['phone']) ?></div>
                                        </td>
                                        <td>
                                            <?= $staff['last_login'] ? date('M j, Y g:i a', strtotime($staff['last_login'])) : 'Never' ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $staff['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= $staff['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="<?= BASE_URL ?>admin_edit_staff.php?id=<?= $staff['staff_id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                
                                                <form method="POST" action="">
                                                    <input type="hidden" name="staff_id" value="<?= $staff['staff_id'] ?>">
                                                    <input type="hidden" name="is_active" value="<?= $staff['is_active'] ?>">
                                                    <button type="submit" name="toggle_status" 
                                                            class="btn btn-sm btn-<?= $staff['is_active'] ? 'warning' : 'success' ?>">
                                                        <i class="bi bi-power"></i>
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" action="">
                                                    <input type="hidden" name="staff_id" value="<?= $staff['staff_id'] ?>">
                                                    <button type="submit" name="delete_staff" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Permanently delete this staff member?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<style>
    .table-responsive {
        overflow-x: auto;
    }
    .badge {
        font-size: 0.85em;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
    }
</style>