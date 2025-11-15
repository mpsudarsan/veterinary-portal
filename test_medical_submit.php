<?php 
// Create a new file test_medical_submit.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

try {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("INSERT INTO medical_records 
        (pet_id, staff_id, attending_staff_id, diagnosis, treatment, medications, notes, record_date, record_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)");
    
    $result = $stmt->execute([
        1, // Test pet_id
        1, // Test staff_id
        1, // Test attending_staff_id
        'Test diagnosis',
        'Test treatment',
        'Test meds',
        'Test notes',
        'general'
    ]);
    
    echo json_encode(['success' => $result, 'insertId' => $db->lastInsertId()]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>