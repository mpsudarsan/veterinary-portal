<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';


// Initialize variables
$facilities = [];
$facilityStats = [];
$error = '';

try {
    $db = getDatabaseConnection();

    // Fetch all facilities with their type names
    $query = "SELECT vf.*, ft.full_name AS facility_type_name 
              FROM veterinary_facilities vf
              JOIN facility_types ft ON vf.facility_type = ft.short_code
              ORDER BY vf.official_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count facilities by type for the stats cards
    $statsQuery = "SELECT ft.full_name, COUNT(vf.facility_id) AS count 
                   FROM facility_types ft
                   LEFT JOIN veterinary_facilities vf ON ft.short_code = vf.facility_type AND vf.is_active = 1
                   GROUP BY ft.full_name";
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute();
    $facilityStats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . (DEBUG_MODE ? $e->getMessage() : "Please try again later.");
    error_log("Admin Hospitals Error: " . $e->getMessage());
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>        
        <div class="row">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Veterinary Facilities Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="admin_add_hospital.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> Add New Facility
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Facility Statistics Cards -->
            <div class="row mb-4">
                <?php foreach ($facilityStats as $stat): ?>
                <div class="col-md-4">
                    <div class="stat-card blue p-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6><?= htmlspecialchars($stat['full_name']) ?></h6>
                                <h2><?= $stat['count'] ?></h2>
                            </div>
                            <i class="bi bi-hospital" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Facilities Table -->
            <div class="card">
                <div class="card-header" style="background-color: var(--admin-red); color: white;">
                    <i class="bi bi-hospital"></i> All Veterinary Facilities
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="facilitiesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Facility Name</th>
                                    <th>Type</th>
                                    <th>Address</th>
                                    <th>District</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($facilities as $facility): ?>
                                <tr>
                                    <td><?= $facility['facility_id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($facility['official_name']) ?></strong>
                                        <?php if (!empty($facility['landmark'])): ?>
                                        <br><small class="text-muted">Near <?= htmlspecialchars($facility['landmark']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($facility['facility_type_name']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($facility['address_line1']) ?>
                                        <?php if (!empty($facility['address_line2'])): ?>
                                        <br><?= htmlspecialchars($facility['address_line2']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($facility['pincode'])): ?>
                                        <br>PIN: <?= htmlspecialchars($facility['pincode']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= !empty($facility['district']) ? htmlspecialchars($facility['district']) : 'N/A' ?></td>
                                    <td>
                                        <span class="badge bg-<?= $facility['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $facility['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="admin_edit_hospital.php?id=<?= $facility['facility_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($facility['is_active']): ?>
                                        <a href="admin_deactivate_hospital.php?id=<?= $facility['facility_id'] ?>" class="btn btn-sm btn-outline-danger" title="Deactivate">
                                            <i class="bi bi-eye-slash"></i>
                                        </a>
                                        <?php else: ?>
                                        <a href="admin_activate_hospital.php?id=<?= $facility['facility_id'] ?>" class="btn btn-sm btn-outline-success" title="Activate">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
 

<!-- Include DataTables for enhanced table functionality -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script>
    $(document).ready(function() {
        $('#facilitiesTable').DataTable({
            "pageLength": 25,
            "order": [[1, 'asc']], // Sort by facility name by default
            "responsive": true
        });
    });
</script>

<style>
    /* Match the dashboard styling */
    .stat-card.blue {
        background: rgb(35, 96, 176);
        border-left: 4px solid rgb(12, 49, 105);
        color: white;
    }
    
    .stat-card h6 {
        font-size: 14px;
        opacity: 0.9;
    }
    
    .stat-card h2 {
        font-weight: 700;
        margin: 5px 0;
    }
    
    .table th {
        background-color: #f8f9fa;
    }
    
    .badge {
        font-size: 0.85em;
        padding: 0.35em 0.65em;
    }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>