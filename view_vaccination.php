<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';


$pet_owner_id = $_SESSION['pet_owner_id'];
$db = getDatabaseConnection();

// Get vaccination appointments for this pet owner
$stmt = $db->prepare("
    SELECT 
        a.preferred_date, 
        a.preferred_time, 
        a.status, 
        a.additional_notes,
        p.pet_name, 
        v.vaccine_name, 
        f.official_name AS facility_name
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN vaccine_types v ON a.vaccine_id = v.vaccine_id
    LEFT JOIN veterinary_facilities f ON a.facility_id = f.facility_id
    WHERE a.pet_owner_id = ?
    AND a.appointment_type = 'Vaccination'
    ORDER BY a.preferred_date DESC
");
$stmt->execute([$pet_owner_id]);
$vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include 'includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">My Vaccination Appointments</h2>
    
    <?php if (empty($vaccinations)): ?>
        <div class="alert alert-info">You have no vaccination appointments scheduled.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Pet Name</th>
                        <th>Vaccine</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Facility</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vaccinations as $v): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($v['pet_name']); ?></td>
                            <td><?php echo htmlspecialchars($v['vaccine_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($v['preferred_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($v['preferred_time'])); ?></td>
                            <td><?php echo htmlspecialchars($v['facility_name'] ?? 'Not specified'); ?></td>
                            <td>
                                <?php 
                                $statusClass = '';
                                switch(strtolower($v['status'])) {
                                    case 'pending':
                                        $statusClass = 'bg-warning';
                                        break;
                                    case 'confirmed':
                                        $statusClass = 'bg-success';
                                        break;
                                    case 'completed':
                                        $statusClass = 'bg-primary';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'bg-danger';
                                        break;
                                    default:
                                        $statusClass = 'bg-secondary';
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($v['status']); ?>
                                </span>
                            </td>
                            <td><?php echo nl2br(htmlspecialchars($v['additional_notes'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php 
// Include footer
include 'includes/footer.php';
?>