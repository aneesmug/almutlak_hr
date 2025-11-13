<?php
/**
 * Show all user types in your system
 * This helps identify correct user_type values for approval chain
 */

require_once __DIR__ . '/includes/db.php';

echo "<h2>User Types in Your System</h2>";

$sql = "SELECT user_type, emp_id, fullname, username, dept, status 
        FROM admin_login 
        WHERE emp_id IS NOT NULL 
        ORDER BY user_type, fullname";

$result = mysqli_query($conDB, $sql);

$by_type = [];
while ($row = mysqli_fetch_assoc($result)) {
    $by_type[$row['user_type']][] = $row;
}

foreach ($by_type as $type => $users) {
    echo "<h3>user_type = '{$type}' (" . count($users) . " user" . (count($users) > 1 ? 's' : '') . ")</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Emp ID</th><th>Name</th><th>Username</th><th>Dept</th><th>Status</th></tr>";
    
    foreach ($users as $user) {
        $status_color = ($user['status'] == 1) ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$user['emp_id']}</td>";
        echo "<td>{$user['fullname']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['dept']}</td>";
        echo "<td style='color:{$status_color};'><strong>" . ($user['status'] == 1 ? 'Active' : 'Inactive') . "</strong></td>";
        echo "</tr>";
    }
    
    echo "</table><br>";
}

echo "<hr>";
echo "<h3>Approval Chain Mapping</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Level</th><th>Role</th><th>user_type to search</th><th>Found?</th></tr>";

$chain_map = [
    ['level' => 1, 'role' => 'Direct Supervisor/Manager', 'type' => '(from employees table)', 'found' => '✓'],
    ['level' => 2, 'role' => 'HR Payroll', 'type' => 'hr_payroll', 'found' => isset($by_type['hr_payroll']) ? '✓' : '✗'],
    ['level' => 3, 'role' => 'HR Manager/Supervisor', 'type' => 'hr_supervisor', 'found' => isset($by_type['hr_supervisor']) ? '✓' : '✗'],
    ['level' => 4, 'role' => 'Auditor', 'type' => 'auditor', 'found' => isset($by_type['auditor']) ? '✗ (optional)' : '✗ (optional)'],
    ['level' => 5, 'role' => 'GM', 'type' => 'gm', 'found' => isset($by_type['gm']) ? '✓' : '✗'],
    ['level' => 6, 'role' => 'Finance Manager', 'type' => 'finance', 'found' => isset($by_type['finance']) ? '✓' : '✗'],
    ['level' => 7, 'role' => 'Finance Officer (Payer)', 'type' => 'finance_officer', 'found' => isset($by_type['finance_officer']) ? '✓' : '✗'],
];

foreach ($chain_map as $item) {
    $color = (strpos($item['found'], '✓') !== false) ? 'green' : (strpos($item['found'], 'optional') !== false ? 'orange' : 'red');
    echo "<tr>";
    echo "<td><strong>{$item['level']}</strong></td>";
    echo "<td>{$item['role']}</td>";
    echo "<td><code>{$item['type']}</code></td>";
    echo "<td style='color:{$color};'><strong>{$item['found']}</strong></td>";
    echo "</tr>";
}

echo "</table>";

$conDB->close();
?>
