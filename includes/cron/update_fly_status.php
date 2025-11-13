<?php
// Purpose: Reset employees.fly to 0 when no active approved vacation/leave exists
// Handles both Leave Requests (LV-*) and regular vacation (VAC-*) automatically
// Run daily via scheduler/Task Scheduler or call manually.

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

try {
    // Reset fly=0 for employees with fly=1 who have NO active approved vacation/leave covering today
    // This works for both vacation and leave requests
    $sql = "
        UPDATE employees e
        SET e.fly = 0
        WHERE e.fly = 1
          AND NOT EXISTS (
              SELECT 1
              FROM emp_vacation v
              WHERE v.emp_id = e.emp_id
                AND v.current_status = 'approved'
                AND v.start_date <= CURDATE()
                AND v.return_date >= CURDATE()
          )
    ";

    if (!mysqli_query($conDB, $sql)) {
        throw new Exception('Failed to update fly statuses: ' . mysqli_error($conDB));
    }

    $affected = mysqli_affected_rows($conDB);
    echo json_encode([
        'status' => 'ok', 
        'message' => "Fly status updated. $affected employee(s) affected.",
        'affected_rows' => $affected
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

