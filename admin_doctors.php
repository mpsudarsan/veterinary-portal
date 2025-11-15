<?php
session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check admin privileges
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Initialize variables
$searchTerm = '';
$filterRole = '';
$filterStatus = '';
$filterFacility = '';
$doctors = [];
$errors = [];

// Process search/filter form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $searchTerm = trim($_GET['search'] ?? '');
    $filterRole = $_GET['role'] ?? '';
    $filterStatus = $_GET['status'] ?? '';
    $filterFacility = $_GET['facility'] ?? '';
    
    try {
        $db = getDatabaseConnection();
        
        // Build query with joins for all related data
        $query = "SELECT 
                    fs.*, 
                    fr.role_name, 
                    fr.permission_level,
                    vf.facility_name,
                    vf.facility_id,
                    CASE 
                        WHEN fs.auto_generated = 1 AND fs.credentials_sent_at IS NULL THEN 'Pending Activation'
                        WHEN fs.is_active = 1 THEN 'Active'
                        ELSE 'Inactive'
                    END as account_status
                  FROM facility_staff fs
                  JOIN staff_roles fr ON fs.role_id = fr.role_id
                  JOIN veterinary_facilities vf ON fs.facility_id = vf.facility_id
                  WHERE fr.permission_level >= 3"; // Medical staff only
        
        $params = [];
        
        // Add search conditions
        if (!empty($searchTerm)) {
            $query .= " AND (fs.full_name LIKE ? OR fs.email LIKE ? OR fs.phone LIKE ? OR fs.username LIKE ?)";
            $searchParam = "%$searchTerm%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        // Add role filter
        if (!empty($filterRole) && $filterRole !== 'all') {
            $query .= " AND fr.role_id = ?";
            $params[] = $filterRole;
        }
        
        // Add status filter
        if (!empty($filterStatus) && $filterStatus !== 'all') {
            if ($filterStatus === 'pending') {
                $query .= " AND fs.auto_generated = 1 AND fs.credentials_sent_at IS NULL";
            } else {
                $query .= " AND fs.is_active = ?";
                $params[] = ($filterStatus === 'active') ? 1 : 0;
            }
        }
        
        // Add facility filter
        if (!empty($filterFacility) && $filterFacility !== 'all') {
            $query .= " AND vf.facility_id = ?";
            $params[] = $filterFacility;
        }
        
        $query .= " ORDER BY fs.is_active DESC, fs.full_name ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $errors[] = "Error fetching staff data. Please try again.";
    }
}

// Fetch data for filters
try {
    $db = getDatabaseConnection();
    
    // Get roles for filter dropdown (medical staff only)
    $stmt = $db->prepare("SELECT role_id, role_name FROM staff_roles WHERE permission_level >= 3 ORDER BY role_name");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active facilities for filter dropdown
    $stmt = $db->prepare("SELECT facility_id, facility_name FROM veterinary_facilities WHERE is_active = 1 ORDER BY facility_name");
    $stmt->execute();
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "Error loading filter options.";
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<style>
    .status-badge {
        font-size: 0.8rem;
        padding: 3px 8px;
        border-radius: 10px;
        font-weight: 500;
    }
    .status-active {
        background-color: #d4edda;
        color: #155724;
    }
    .status-inactive {
        background-color: #f8d7da;
        color: #721c24;
    }
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .account-type-badge {
        font-size: 0.7rem;
        vertical-align: middle;
    }
    .filter-section {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .action-buttons .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
</style>
</head>
<body>
    <div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            </div>
    
    <div class="container py-4" id="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Medical Staff Management</h2>
            <a href="admin_add_doctor.php" class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Add New Staff
            </a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p class="mb-1"><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="filter-section mb-4">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Name, email, phone or username">
                </div>
                <div class="col-md-2">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="all">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['role_id'] ?>" <?= 
                                $filterRole == $role['role_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all">All Statuses</option>
                        <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending Activation</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="facility" class="form-label">Facility</label>
                    <select class="form-select" id="facility" name="facility">
                        <option value="all">All Facilities</option>
                        <?php foreach ($facilities as $facility): ?>
                            <option value="<?= $facility['facility_id'] ?>" <?= 
                                $filterFacility == $facility['facility_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($facility['facility_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>
        
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Medical Staff Directory</h5>
                <div class="text-muted small">
                    Showing <?= count($doctors) ?> records
                </div>
            </div>
            
            <div class="card-body">
                <?php if (empty($doctors)): ?>
                    <div class="alert alert-info">No staff members found matching your criteria.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Staff ID</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Facility</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Last Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $staff): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($staff['staff_id']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($staff['full_name']) ?></strong>
                                            <?php if ($staff['auto_generated']): ?>
                                                <span class="badge bg-warning account-type-badge" title="Auto-generated account">Auto</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($staff['role_name']) ?></td>
                                        <td><?= htmlspecialchars($staff['facility_name']) ?></td>
                                        <td>
                                            <div><?= htmlspecialchars($staff['email']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($staff['phone']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($staff['auto_generated'] && $staff['credentials_sent_at'] === null): ?>
                                                <span class="status-badge status-pending">Pending</span>
                                            <?php elseif ($staff['is_active']): ?>
                                                <span class="status-badge status-active">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($staff['last_login']): ?>
                                                <div><?= date('M j, Y', strtotime($staff['last_login'])) ?></div>
                                                <div class="text-muted small"><?= date('g:i a', strtotime($staff['last_login'])) ?></div>
                                            <?php else: ?>
                                                Never logged in
                                            <?php endif; ?>
                                        </td>
                                        <td class="action-buttons">
                                            <div class="d-flex gap-1">
                                                <a href="admin_edit_doctor.php?id=<?= $staff['staff_id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                
                                                <?php if ($staff['auto_generated'] && $staff['credentials_sent_at'] === null): ?>
                                                    <a href="admin_send_credentials.php?id=<?= $staff['staff_id'] ?>" 
                                                       class="btn btn-sm btn-outline-info" title="Send Credentials">
                                                        <i class="bi bi-envelope"></i>
                                                    </a>
                                                <?php elseif ($staff['is_active']): ?>
                                                    <a href="admin_deactivate_doctor.php?id=<?= $staff['staff_id'] ?>" 
                                                       class="btn btn-sm btn-outline-danger" title="Deactivate">
                                                        <i class="bi bi-person-x"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="admin_activate_doctor.php?id=<?= $staff['staff_id'] ?>" 
                                                       class="btn btn-sm btn-outline-success" title="Activate">
                                                        <i class="bi bi-person-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <script>
        // Confirmation dialogs for actions
        document.querySelectorAll('[href*="admin_deactivate_doctor"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to deactivate this staff member?')) {
                    e.preventDefault();
                }
            });
        });
        
        document.querySelectorAll('[href*="admin_activate_doctor"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to activate this staff member?')) {
                    e.preventDefault();
                }
            });
        });
        
        document.querySelectorAll('[href*="admin_send_credentials"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Send login credentials to this staff member?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>