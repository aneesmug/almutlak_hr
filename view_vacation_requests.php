<?php
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/session_check.php';
    $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
    if(mysqli_num_rows($query) == 1){
    include("./includes/avatar_select.php");




// --- Search, Pagination & Filtering Logic ---
$user_role = $_SESSION['user_role'] ?? 'guest';

$all_statuses = [
    'pending' => 'Pending',
    'hr_assistant_approved' => 'HR Assistant Approved',
    'hr_manager_approved' => 'HR Manager Approved',
    'gm_approved' => 'GM Approved',
    'rejected' => 'Rejected'
];

// 1. Set up variables
$search_term = $_GET['search'] ?? '';
$limit_options = [6, 16, 32, 64]; // Added 6 to the options
$items_per_page = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : 3; // Default to 6
$show_all = isset($_GET['limit']) && $_GET['limit'] == 'all';
if ($show_all) {
    $items_per_page = -1;
}

$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$current_filter = $_GET['status'] ?? null;
if ($current_filter === null) {
    switch ($user_role) {
        case 'HR_Assistant': $current_filter = 'pending'; break;
        case 'HR_Manager': $current_filter = 'hr_assistant_approved'; break;
        case 'GM': $current_filter = 'hr_manager_approved'; break;
        default: $current_filter = 'none'; break;
    }
}

$page_title = isset($all_statuses[$current_filter]) ? $all_statuses[$current_filter] : 'All Requests';

// 2. Build the dynamic WHERE clause
$where_clauses = [];
$params = [];
$types = "";

// Status filter
if ($current_filter && $current_filter !== 'all' && $current_filter !== 'none') {
    if (array_key_exists($current_filter, $all_statuses)) {
        $where_clauses[] = "v.approval_status = ?";
        $params[] = $current_filter;
        $types .= "s";
    }
}

// Search filter
if (!empty($search_term)) {
    $where_clauses[] = "(e.name LIKE ? OR v.emp_id LIKE ?)";
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// 3. Get total item count
$count_sql = "SELECT COUNT(v.id) as total FROM emp_vacation v JOIN employees e ON v.emp_id = e.emp_id" . $where_sql;
$total_items = 0;
if ($current_filter !== 'none' || !empty($search_term)) {
    $stmt_count = $conDB->prepare($count_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_items = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_count->close();
}
$total_pages = $show_all ? 1 : ceil($total_items / $items_per_page);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// 4. Fetch requests for the current page
$requests = [];
if (($current_filter !== 'none' || !empty($search_term)) && $total_items > 0) {
    $sql = "SELECT v.*, e.name as employee_name FROM emp_vacation v JOIN employees e ON v.emp_id = e.emp_id" . $where_sql;
    $sql .= " ORDER BY v.created_at DESC";
    
    if (!$show_all) {
        $offset = ($current_page - 1) * $items_per_page;
        $sql .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $items_per_page;
        $types .= "ii";
    }

    $stmt = $conDB->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Vacation Requests</title>
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- SweetAlert2 for popups -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- Bootstrap 4 JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --primary-color: #4a90e2;
            --success-color: #50e3c2;
            --danger-color: #e35050;
            --light-gray: #f8f9fa;
            --dark-gray: #8a94a6;
            --text-color: #333;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-gray);
        }
        .header-title {
            font-weight: 600;
            color: var(--primary-color);
        }
        .header-subtitle {
            color: var(--dark-gray);
        }
        .filter-controls {
            max-width: 800px;
        }
        .request-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        .request-card .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eef;
            font-weight: 600;
            font-size: 1.1em;
        }
        .request-card .card-header span {
             font-size: 0.9em;
             color: var(--dark-gray);
        }
        .request-card .card-body {
            padding: 1.5rem;
        }
        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.95em;
        }
        .detail-item i {
            color: var(--primary-color);
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        .detail-item strong {
            color: var(--dark-gray);
            min-width: 100px;
            display: inline-block;
        }
        .request-card .card-footer {
            background-color: #fafbff;
            border-top: 1px solid #eef;
        }
        .btn-custom {
            border-radius: 8px;
            font-weight: 600;
            padding: .5rem 1.25rem;
        }
        .btn-approve-custom {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        .btn-approve-custom:hover {
            background-color: #45d1b5;
            border-color: #45d1b5;
            color: white;
        }
        .btn-reject-custom {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            color: white;
        }
        .btn-reject-custom:hover {
            background-color: #d14545;
            border-color: #d14545;
            color: white;
        }
        .no-requests {
            padding: 3rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
        }
        .swal2-html-container {
            overflow: hidden !important;
        }
        .swal2-input {
            margin-left: auto;
            margin-right: auto;
            width: 90% !important;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="text-center mb-4">
            <h1 class="header-title">Vacation Approval Center</h1>
            <p class="lead header-subtitle">Your Role: <strong><?php echo htmlspecialchars($user_role); ?></strong></p>
        </div>

        <!-- Filter Controls -->
        <div class="row filter-controls mx-auto mb-5">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="form-group">
                    <label for="statusFilter" class="font-weight-bold">Filter by Status</label>
                    <select class="form-control" id="statusFilter" onchange="applyFilters()">
                        <option value="all" <?php if ($current_filter == 'all') echo 'selected'; ?>>All Requests</option>
                        <?php foreach ($all_statuses as $status_key => $status_value): ?>
                            <option value="<?php echo $status_key; ?>" <?php if ($current_filter == $status_key) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($status_value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="searchFilter" class="font-weight-bold">Search by Name / ID</label>
                    <div class="input-group">
                        <input type="search" class="form-control" id="searchFilter" placeholder="Enter search term..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 text-muted">Showing: <?php echo htmlspecialchars($page_title); ?> Requests</h4>
            <span class="badge badge-light p-2">Total Found: <?php echo $total_items; ?></span>
        </div>


        <?php if (!empty($requests)): ?>
            <div class="row">
                <?php foreach ($requests as $req): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card request-card h-100">
                            <div class="card-header">
                                <?php echo parseName($req['employee_name']); ?>
                                <span class="float-right">ID: <?php echo htmlspecialchars($req['emp_id']); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="detail-item">
                                    <i class="fas fa-paper-plane"></i>
                                    <strong>Applied:</strong> <?php echo htmlspecialchars(date('d M Y', strtotime($req['created_at']))); ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-suitcase-rolling"></i>
                                    <strong>Type:</strong> <?php echo htmlspecialchars($req['vac_type']); ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <strong>Start:</strong> <?php echo htmlspecialchars($req['start_date'] ?? 'N/A'); ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <strong>Return:</strong> <?php echo htmlspecialchars($req['return_date'] ?? 'N/A'); ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-sun"></i>
                                    <strong>Days:</strong> <?php echo htmlspecialchars($req['vacdays']); ?>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <!-- Only show buttons if the request is in a state that can be actioned by the current user -->
                                <?php if ($req['approval_status'] == 'pending' && $user_role == 'HR_Assistant' ||
                                          $req['approval_status'] == 'hr_assistant_approved' && $user_role == 'HR_Manager' ||
                                          $req['approval_status'] == 'hr_manager_approved' && $user_role == 'GM'): ?>
                                    <button class="btn btn-danger" onclick="rejectVacationRequest(<?php echo $req['id']; ?>, '<?php echo $user_role; ?>')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    <button class="btn btn-success" onclick="approveRequest(<?php echo $req['id']; ?>, '<?php echo $user_role; ?>')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination Controls -->
            <?php 
                $pagination_params = ['status' => $current_filter, 'limit' => $show_all ? 'all' : $items_per_page, 'search' => $search_term];
                echo generate_pagination_controls($current_page, $total_pages, $items_per_page, $limit_options, $show_all, $pagination_params);
            ?>

        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="text-center no-requests">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h2>No Requests Found</h2>
                        <?php if ($current_filter && $current_filter !== 'all' && $current_filter !== 'none' || !empty($search_term)): ?>
                            <p class="text-muted">There are no requests matching your current filters.</p>
                        <?php else: ?>
                            <p class="text-muted">There are no requests to display at this time.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

<script>
// Function to handle filtering via JavaScript redirection
function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const limitElement = document.getElementById('limitFilter');
    const limit = limitElement ? limitElement.value : '3'; // Default to 6 if it doesn't exist
    const search = document.getElementById('searchFilter').value;
    
    const baseUrl = window.location.href.split('?')[0];
    // Always reset to page 1 when a filter is changed
    window.location.href = `${baseUrl}?status=${status}&limit=${limit}&search=${encodeURIComponent(search)}&page=1`;
}

// Add event listener to search on Enter key press
document.getElementById('searchFilter').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});

// Function to handle the approval action.
function approveRequest(vacationId, role) {
    if (role === 'HR_Assistant') {
        Swal.fire({
            title: 'Approval Details',
            html: `
                <input type="number" id="ticket_pay" class="swal2-input" placeholder="Ticket Payment (optional)">
                <input type="number" id="permit_fee" class="swal2-input" placeholder="Permit Fee (optional)">
            `,
            confirmButtonText: 'Submit Approval',
            showCancelButton: true,
            preConfirm: () => {
                return {
                    ticket_pay: document.getElementById('ticket_pay').value,
                    permit_fee: document.getElementById('permit_fee').value
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                sendApproval(vacationId, role, result.value.ticket_pay, result.value.permit_fee);
            }
        });
    } else {
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to approve this vacation request?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#50e3c2',
            cancelButtonColor: '#e35050',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                sendApproval(vacationId, role);
            }
        })
    }
}

// AJAX call for sending the approval
function sendApproval(vacationId, role, ticketPay = null, permitFee = null) {
    $.ajax({
        url: './includes/ajaxFile/ajaxVacation.php',
        type: 'POST',
        dataType: 'JSON',
        data: {
            ajaxType: 'approveVacation',
            vacation_id: vacationId,
            approver_role: role,
            ticket_pay: ticketPay,
            permit_fee: permitFee
        },
        success: function(response) {
            Swal.fire('Approved!', 'The request has been approved.', 'success')
                .then(() => location.reload());
        },
        error: function(jqXHR, textStatus, errorThrown) {
            Swal.fire('Error!', 'Something went wrong with the approval.', 'error');
        }
    });
}

/**
 * Function to handle vacation rejection.
 */
function rejectVacationRequest(vacationId, role) {
    Swal.fire({
        title: 'Reject Vacation Request',
        input: 'textarea',
        inputLabel: 'Reason for Rejection',
        inputPlaceholder: 'Enter the reason for rejection here...',
        inputAttributes: {
            'aria-label': 'Type your rejection reason here'
        },
        showCancelButton: true,
        confirmButtonText: 'Submit Rejection',
        confirmButtonColor: '#e35050',
        showLoaderOnConfirm: true,
        inputValidator: (value) => {
            if (!value) {
                return 'You need to provide a reason for rejection!'
            }
        },
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: (reason) => {
            return $.ajax({
                url: './includes/ajaxFile/ajaxVacation.php',
                type: 'POST',
                dataType: 'JSON',
                data: {
                    ajaxType: 'rejectVacation',
                    vacation_id: vacationId,
                    rejection_note: reason,
                    approver_role: role
                }
            })
            .fail(function() {
                Swal.showValidationMessage('Request failed. Please try again.');
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value.success) {
            Swal.fire({
                title: 'Rejected!',
                text: 'The vacation request has been rejected.',
                icon: 'success'
            }).then(() => { 
                // Reload with the current filter preserved
                window.location.href = window.location.href;
            });
        } else if (result.isConfirmed) {
             Swal.fire('Error!', result.value.message || 'Could not reject the request.', 'error');
        }
    });
}
</script>

</body>
</html>
<?php 
    $conDB->close();
} 
?>
