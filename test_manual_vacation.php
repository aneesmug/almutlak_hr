<?php
/**
 * Test File for Manual Vacation History Feature
 * Employee ID: 3928
 * Purpose: Test adding manual vacation history entries
 */

// Include session check which sets up database connection
require_once __DIR__ . '/includes/session_check.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    die('Not authenticated. Please login first.');
}

// Additional validation
if (!isset($conDB) || !$conDB) {
    die('ERROR: Database connection failed. $conDB is not available.');
}

$emp_id = 3928;

// Get employee details
$sql_emp = "SELECT `emp_id`, `name`, `dept` FROM `employees` WHERE `emp_id` = ? LIMIT 1";
$stmt_emp = mysqli_prepare($conDB, $sql_emp);
mysqli_stmt_bind_param($stmt_emp, "i", $emp_id);
mysqli_stmt_execute($stmt_emp);
$result_emp = mysqli_stmt_get_result($stmt_emp);
$employee = mysqli_fetch_assoc($result_emp);
mysqli_stmt_close($stmt_emp);

if (!$employee) {
    die('Employee not found');
}

// Get current vacation history
$sql_vac = "SELECT * FROM `emp_vacation` WHERE `emp_id` = ? ORDER BY `id` DESC LIMIT 10";
$stmt_vac = mysqli_prepare($conDB, $sql_vac);
mysqli_stmt_bind_param($stmt_vac, "i", $emp_id);
mysqli_stmt_execute($stmt_vac);
$result_vac = mysqli_stmt_get_result($stmt_vac);
$vacations = mysqli_fetch_all($result_vac, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_vac);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Manual Vacation History - Employee <?= $emp_id ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Bootstrap Datepicker -->
    <link href="assets/css/bootstrap-datepicker.min.css" rel="stylesheet">
    
    <style>
        body {
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .employee-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .vacation-table {
            margin-top: 30px;
        }
        .test-button {
            font-size: 16px;
            padding: 10px 25px;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            display: none;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="test-container">
        <h1 class="mb-4">🧪 Test Manual Vacation History Feature</h1>
        
        <!-- Employee Info -->
        <div class="employee-info">
            <h4>Employee Information</h4>
            <p><strong>ID:</strong> <?= $employee['emp_id'] ?></p>
            <p><strong>Name:</strong> <?= htmlspecialchars($employee['name']) ?></p>
            <p><strong>Department:</strong> <?= htmlspecialchars($employee['dept']) ?></p>
            <p><strong>Current User:</strong> <?= htmlspecialchars($_SESSION['user_id'] ?? 'Unknown') ?></p>
        </div>

        <!-- Test Button -->
        <div class="mb-4">
            <button type="button" class="btn btn-primary btn-lg test-button" onclick="testAddManualVacationHistory()">
                <i class="mdi mdi-plus-circle mr-2"></i>Test Add Manual Vacation History
            </button>
            <button type="button" class="btn btn-secondary btn-lg" onclick="location.reload()">
                <i class="mdi mdi-refresh mr-2"></i>Refresh Page
            </button>
        </div>

        <!-- Success Message -->
        <div class="success-box" id="successBox"></div>

        <!-- Current Vacation History -->
        <div class="vacation-table">
            <h4>Current Vacation History (Last 10 entries)</h4>
            <?php if (!empty($vacations)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Fly Type</th>
                                <th>Start Date</th>
                                <th>Return Date</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Permit No</th>
                                <th>Created At</th>
                                <th>Invoice No</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vacations as $vac): ?>
                            <tr>
                                <td><?= $vac['id'] ?></td>
                                <td>
                                    <span class="badge badge-<?= ($vac['vac_type'] == 'Fly') ? 'warning' : (($vac['vac_type'] == 'Encashed') ? 'success' : 'info') ?>">
                                        <?= htmlspecialchars($vac['vac_type'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($vac['fly_type'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($vac['start_date'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($vac['return_date'] ?? 'N/A') ?></td>
                                <td><?= number_format($vac['vacdays'] ?? 0, 1) ?></td>
                                <td>
                                    <span class="badge badge-<?= ($vac['current_status'] == 'approved') ? 'success' : (($vac['current_status'] == 'pending_approval') ? 'warning' : 'danger') ?>">
                                        <?= htmlspecialchars($vac['current_status'] ?? 'unknown') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($vac['permit_no'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($vac['created_at'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($vac['request_inv_no'] ?? 'N/A') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No vacation history found for this employee.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Required JS Libraries -->
<script src="assets/js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/bootstrap-datepicker.min.js"></script>

<script>
// Define translation function if not already defined
if (typeof window.__ === 'undefined') {
    window.__ = function(key, defaultValue) {
        return defaultValue || key;
    };
}

// Define APP_COLORS if not already defined
if (typeof window.APP_COLORS === 'undefined') {
    window.APP_COLORS = {
        primary: '#007bff',
        danger_dark: '#dc3545',
        success: '#28a745'
    };
}

// Define setupGlobalRTLDatepicker if not already defined
if (typeof window.setupGlobalRTLDatepicker === 'undefined') {
    window.setupGlobalRTLDatepicker = function() {
        // Placeholder for RTL setup if needed
    };
}

// Test function
function testAddManualVacationHistory() {
    console.log('Starting manual vacation history test...');
    
    // Call the function from jquery.app.js
    addManualVacationHistory(<?= $emp_id ?>, '<?= htmlspecialchars($employee['name'], ENT_QUOTES) ?>');
}

// Include the manual vacation history functions
// These are copied from jquery.app.js for testing purposes
function manualVacationHistory_HTML() {
    const vacationTypes = [
        { value: 'Fly', label: 'Fly' },
        { value: 'Local Vacation', label: 'Local Vacation' },
        { value: 'Encashed', label: 'Encashed' }
    ];

    const flyTypes = [
        { value: 'annual', label: 'Annual' },
        { value: 'emergency', label: 'Emergency' }
    ];

    let vacationTypeOptions = '<option value="">Select...</option>';
    vacationTypes.forEach(vt => {
        vacationTypeOptions += '<option value="' + vt.value + '">' + vt.label + '</option>';
    });

    let flyTypeOptions = '<option value="">Select...</option>';
    flyTypes.forEach(ft => {
        flyTypeOptions += '<option value="' + ft.value + '">' + ft.label + '</option>';
    });

    const html = `
    <form id="manualVacationHistoryForm">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="mvh_vac_type">Vacation Type</label>
                <select class="form-control" id="mvh_vac_type" name="vac_type" required>
                    ${vacationTypeOptions}
                </select>
            </div>
            <div class="form-group col-md-6" id="mvh_fly_type_group" style="display:none;">
                <label for="mvh_fly_type">Fly Type</label>
                <select class="form-control" id="mvh_fly_type" name="fly_type">
                    ${flyTypeOptions}
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="mvh_start_date">Start Date</label>
                <input type="text" class="form-control" id="mvh_start_date" name="start_date" placeholder="YYYY-MM-DD" required>
            </div>
            <div class="form-group col-md-6">
                <label for="mvh_return_date">Return Date</label>
                <input type="text" class="form-control" id="mvh_return_date" name="return_date" placeholder="YYYY-MM-DD" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="mvh_days">Days</label>
                <input type="number" class="form-control" id="mvh_days" name="days" step="0.5" min="0" readonly>
            </div>
            <div class="form-group col-md-6">
                <label for="mvh_permit_no">Permit No</label>
                <input type="text" class="form-control" id="mvh_permit_no" name="permit_no">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="mvh_remarks">Remarks</label>
                <textarea class="form-control" id="mvh_remarks" name="remarks" rows="3"></textarea>
            </div>
        </div>
    </form>
    `;
    return html;
}

function addManualVacationHistory(empid, empname) {
    if (!empid) {
        Swal.fire({
            title: 'Error',
            text: 'Invalid employee',
            icon: 'error'
        });
        return;
    }

    Swal.fire({
        title: '<i class="fa fa-plus-circle"></i> Add Manual Vacation History',
        html: manualVacationHistory_HTML(),
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        confirmButtonText: '<i class="fa fa-save"></i> Save',
        cancelButtonText: '<i class="fa fa-times"></i> Cancel',
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '600px',
        willOpen: function() {
            const swalModal = Swal.getHtmlContainer();

            // Setup event listeners for form fields
            $('#mvh_vac_type').on('change', function() {
                const vacType = $(this).val();
                if (vacType === 'Fly') {
                    $('#mvh_fly_type_group').show();
                    $('#mvh_fly_type').prop('required', true);
                } else {
                    $('#mvh_fly_type_group').hide();
                    $('#mvh_fly_type').prop('required', false);
                }
            });

            // Calculate days when dates change
            $('#mvh_start_date, #mvh_return_date').on('change', function() {
                const startDate = $('#mvh_start_date').val();
                const returnDate = $('#mvh_return_date').val();
                
                if (startDate && returnDate) {
                    const start = new Date(startDate);
                    const end = new Date(returnDate);
                    
                    if (end >= start) {
                        // Calculate days (inclusive of both start and end dates)
                        const days = (end - start) / (1000 * 60 * 60 * 24) + 1;
                        $('#mvh_days').val(days);
                    } else {
                        Swal.showValidationMessage('Return date must be after start date');
                        $('#mvh_days').val('');
                    }
                }
            });

            // Setup date pickers
            setupGlobalRTLDatepicker();
            $('#mvh_start_date').datepicker({
                format: 'yyyy-mm-dd',
                todayHighlight: false,
                autoclose: true
            });
            $('#mvh_return_date').datepicker({
                format: 'yyyy-mm-dd',
                todayHighlight: false,
                autoclose: true
            });

            $('#mvh_start_date').on('changeDate', function(e) {
                $('#mvh_return_date').datepicker('setStartDate', e.date);
            });
        },
        preConfirm: function() {
            const vacType = $('#mvh_vac_type').val();
            const startDate = $('#mvh_start_date').val();
            const returnDate = $('#mvh_return_date').val();
            const days = $('#mvh_days').val();
            const flyType = $('#mvh_fly_type').val();
            const permitNo = $('#mvh_permit_no').val();
            const remarks = $('#mvh_remarks').val();

            // Validation
            if (!vacType) {
                Swal.showValidationMessage('Select vacation type');
                return false;
            }
            if (!startDate) {
                Swal.showValidationMessage('Enter start date');
                return false;
            }
            if (!returnDate) {
                Swal.showValidationMessage('Enter return date');
                return false;
            }
            if (!days || days <= 0) {
                Swal.showValidationMessage('Invalid number of days');
                return false;
            }
            if (vacType === 'Fly' && !flyType) {
                Swal.showValidationMessage('Select fly type');
                return false;
            }

            // Submit to backend
            return $.ajax({
                url: './includes/ajaxFile/leaveHandler.php',
                type: 'POST',
                dataType: 'JSON',
                data: {
                    ajaxType: 'addManualVacationHistory',
                    emp_id: empid,
                    vac_type: vacType,
                    start_date: startDate,
                    return_date: returnDate,
                    vacdays: days,
                    fly_type: flyType || 'N/A',
                    permit_no: permitNo,
                    remarks: remarks
                },
                error: function(j, e) {
                    Swal.showValidationMessage('Error: ' + e);
                    console.error('Error:', j, e);
                }
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const response = result.value;
            if (response.type === 'success') {
                Swal.fire({
                    title: 'Success',
                    text: response.message,
                    icon: 'success',
                    confirmButtonColor: APP_COLORS.primary
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message,
                    icon: 'error',
                    confirmButtonColor: APP_COLORS.danger_dark
                });
            }
        }
    });
}
</script>

</body>
</html>
