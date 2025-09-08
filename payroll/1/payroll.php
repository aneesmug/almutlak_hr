<?php
// Database connection
include("./../includes/db.php");

// Check connection
if ($conDB->connect_error) {
    die("Connection failed: " . $conDB->connect_error);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_payroll'])) {
        $month_year = $_POST['month_year'];
        $selected_employees = $_POST['selected_employees'] ?? [];
        
        foreach ($selected_employees as $emp_id) {
            generatePayroll($emp_id, $month_year, $conDB);
        }
        
        $success_message = "Payroll generated successfully for selected employees!";
    }
}

function generatePayroll($emp_id, $month_year, $conDB) {
    // Get employee salary details
    $salary_stmt = $conDB->prepare("SELECT * FROM emp_salary WHERE emp_id = ?");
    $salary_stmt->bind_param("s", $emp_id);
    $salary_stmt->execute();
    $salary_result = $salary_stmt->get_result();
    $salary_data = $salary_result->fetch_assoc();
    
    if (!$salary_data) return false;
    
    // Calculate benefits
    $benefits_stmt = $conDB->prepare("SELECT SUM(benefit) as total FROM payroll_benefits WHERE emp_id = ? AND month = ? AND status = 1");
    $benefits_stmt->bind_param("ss", $emp_id, $month_year);
    $benefits_stmt->execute();
    $benefits_result = $benefits_stmt->get_result();
    $benefits_data = $benefits_result->fetch_assoc();
    $total_benefits = $benefits_data['total'] ?? 0;
    
    // Calculate deductions
    $deductions_stmt = $conDB->prepare("SELECT SUM(deduction) as total FROM payroll_deductions WHERE emp_id = ? AND month = ? AND status = 1");
    $deductions_stmt->bind_param("ss", $emp_id, $month_year);
    $deductions_stmt->execute();
    $deductions_result = $deductions_stmt->get_result();
    $deductions_data = $deductions_result->fetch_assoc();
    $total_deductions = $deductions_data['total'] ?? 0;
    
    // Calculate total salary components
    $basic_salary = $salary_data['basic'] ?? 0;
    $housing = $salary_data['housing'] ?? 0;
    $transport = $salary_data['transport'] ?? 0;
    $food = $salary_data['food'] ?? 0;
    $other_allowances = $salary_data['other'] ?? 0;
    
    $total_earnings = $basic_salary + $housing + $transport + $food + $other_allowances + $total_benefits;
    $net_salary = $total_earnings - $total_deductions;
    
    // Insert or update payroll record
    $stmt = $conDB->prepare("INSERT INTO payroll (emp_id, month_year, basic_salary, housing, transport, food, other_allowances, total_benefits, total_deductions, net_salary, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                         ON DUPLICATE KEY UPDATE 
                         basic_salary = VALUES(basic_salary),
                         housing = VALUES(housing),
                         transport = VALUES(transport),
                         food = VALUES(food),
                         other_allowances = VALUES(other_allowances),
                         total_benefits = VALUES(total_benefits),
                         total_deductions = VALUES(total_deductions),
                         net_salary = VALUES(net_salary),
                         updated_at = CURRENT_TIMESTAMP");
    
    $stmt->bind_param("ssdddddddd", $emp_id, $month_year, $basic_salary, $housing, $transport, $food, $other_allowances, $total_benefits, $total_deductions, $net_salary);
    $stmt->execute();
    
    return $stmt->affected_rows > 0;
}

// Get all departments with LEFT JOIN to department table
$departments = [];
$dept_result = $conDB->query("SELECT DISTINCT d.dep_nme, d.id 
                          FROM employees e 
                          LEFT JOIN department d ON e.dept = d.id 
                          WHERE e.status = 1 
                          ORDER BY d.dep_nme");
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row;
}

// Get employees by selected department with LEFT JOIN to department table
$selected_dept = $_GET['department'] ?? '';
$employees = [];

if (!empty($selected_dept)) {
    $emp_stmt = $conDB->prepare("SELECT e.id, e.emp_id, e.name, d.dep_nme AS `dept`, es.basic, ac.job 
                             FROM employees e 
                             LEFT JOIN emp_salary es ON e.emp_id = es.emp_id 
                             LEFT JOIN department d ON e.dept = d.id 
                             LEFT JOIN ac_jobs ac ON e.actual_job = ac.id 
                             WHERE e.dept = ? AND e.status = 1 
                             ORDER BY e.name");
    $emp_stmt->bind_param("s", $selected_dept);
    $emp_stmt->execute();
    $emp_result = $emp_stmt->get_result();
    
    while ($row = $emp_result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Get current month and year for default selection
$current_month_year = date('Y-m');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .department-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .employee-table {
            margin-top: 20px;
        }
        .select-all-checkbox {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="mb-4">Payroll Management</h2>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?=$success_message; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Generate Payroll</h5>
            </div>
            <div class="card-body">
                <form method="get" class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="department" class="form-label">Select Department</label>
                            <select name="department" id="department" class="form-select" onchange="this.form.submit()">
                                <option value="">-- All Departments --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?=htmlspecialchars($dept['id']); ?>" <?=$selected_dept === $dept['id'] ? 'selected' : ''; ?>>
                                        <?=htmlspecialchars($dept['dep_nme']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
                
                <?php if (!empty($selected_dept)): ?>
                    <form method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="month_year" class="form-label">Month/Year</label>
                                <input type="month" id="month_year" name="month_year" class="form-control" 
                                       value="<?=$current_month_year; ?>" required>
                            </div>
                        </div>
                        
                        <div class="table-responsive employee-table">
                            <table id="employeesTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th width="50px">
                                            <input type="checkbox" id="selectAll" class="select-all-checkbox">
                                        </th>
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                        <th>Basic Salary</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_employees[]" 
                                                       value="<?=htmlspecialchars($employee['emp_id']); ?>" 
                                                       class="employee-checkbox">
                                            </td>
                                            <td><?=htmlspecialchars($employee['emp_id']); ?></td>
                                            <td><?=htmlspecialchars($employee['name']); ?></td>
                                            <td><?=htmlspecialchars($employee['dept']); ?></td>
                                            <td><?=htmlspecialchars($employee['job'] ?? 'N/A'); ?></td>
                                            <td><?=number_format($employee['basic'] ?? 0, 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <button type="submit" name="generate_payroll" class="btn btn-primary mt-3">
                            Generate Payroll for Selected Employees
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Payroll Records Section -->
        <div class="card">
            <div class="card-header">
                <h5>Payroll Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="payrollTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Month/Year</th>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>Basic Salary</th>
                                <th>Benefits</th>
                                <th>Deductions</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $payroll_query = "SELECT p.*, e.name, d.dep_nme AS `dept`, ac.job 
                                            FROM payroll p
                                            JOIN employees e ON p.emp_id = e.emp_id
                                            LEFT JOIN department d ON e.dept = d.id
                                            LEFT JOIN ac_jobs ac ON e.actual_job = ac.id 
                                            ORDER BY p.month_year DESC, e.name";
                            $payroll_result = $conDB->query($payroll_query);
                            
                            while ($row = $payroll_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?=htmlspecialchars($row['month_year']); ?></td>
                                    <td><?=htmlspecialchars($row['emp_id']); ?></td>
                                    <td><?=htmlspecialchars($row['name']); ?></td>
                                    <td><?=htmlspecialchars($row['dept']); ?></td>
                                    <td><?=htmlspecialchars($row['job'] ?? 'N/A'); ?></td>
                                    <td><?=number_format($row['basic_salary'], 2); ?></td>
                                    <td><?=number_format($row['total_benefits'], 2); ?></td>
                                    <td><?=number_format($row['total_deductions'], 2); ?></td>
                                    <td><?=number_format($row['net_salary'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?=$row['status'] === 'approved' ? 'success' : ($row['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                            <?=ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="payroll_details.php?emp_id=<?=$row['emp_id']; ?>&month=<?=$row['month_year']; ?>" 
                                           class="btn btn-sm btn-info">View</a>
                                        <a href="edit_payroll.php?emp_id=<?=$row['emp_id']; ?>&month=<?=$row['month_year']; ?>" 
                                           class="btn btn-sm btn-warning">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#employeesTable, #payrollTable').DataTable();
            
            // Select all checkbox functionality
            $('#selectAll').change(function() {
                $('.employee-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            // If any employee checkbox is unchecked, uncheck the select all checkbox
            $('.employee-checkbox').change(function() {
                if (!$(this).prop('checked')) {
                    $('#selectAll').prop('checked', false);
                } else {
                    // Check if all checkboxes are checked
                    let allChecked = true;
                    $('.employee-checkbox').each(function() {
                        if (!$(this).prop('checked')) {
                            allChecked = false;
                            return false;
                        }
                    });
                    $('#selectAll').prop('checked', allChecked);
                }
            });
        });
    </script>
</body>
</html>
<?php
$conDB->close();
?>