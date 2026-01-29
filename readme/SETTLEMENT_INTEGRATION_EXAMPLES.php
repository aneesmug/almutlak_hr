<?php
/**
 * Settlement Integration Examples
 * Copy these code snippets into your vacation and loan approval handlers
 */

// ============================================================================
// EXAMPLE 1: Integration in Vacation Approval Handler
// Add to your all_applied_vac.php or vacation approval page
// ============================================================================
?>

<?php
/*
// In all_applied_vac.php - After final approval is granted for a vacation request

// Get the approved vacation record
$vacationInvoiceNo = 'VAC-2026-0001';
$empId = '5160';
$approvedDays = 15;
$dailyRate = 350; // SAR per day

// Check if settlement already exists
$existingSettlement = $pdo->prepare("
    SELECT id FROM settlement_records 
    WHERE request_inv_no = ? AND request_type = 'annual_vacation'
");
$existingSettlement->execute([$vacationInvoiceNo]);
$exists = $existingSettlement->fetch();

if (!$exists) {
    // Create new settlement for this vacation
    require_once 'includes/SettlementManager.php';
    $settlementMgr = new SettlementManager($pdo, $conDB);
    
    $settlementAmount = $approvedDays * $dailyRate;
    
    $result = $settlementMgr->createSettlement(
        $vacationInvoiceNo,           // Request invoice number
        'annual_vacation',             // Request type
        $empId,                        // Employee ID
        $settlementAmount,             // Amount to be settled
        $_SESSION['emp_id']            // Created by (current user)
    );
    
    if ($result['success']) {
        // Settlement created successfully
        $_SESSION['message'] = 'Settlement created for vacation request';
        $_SESSION['message_type'] = 'success';
        
        // Optionally trigger notification to first approver
        // require_once 'includes/helper_functions.php';
        // create_and_show_notification(
        //     $firstApproverId,
        //     'Settlement Awaiting Approval',
        //     "Settlement of " . $settlementAmount . " SAR for " . $empName,
        //     'settlement_approvals.php?id=' . $result['settlement_id']
        // );
    } else {
        $_SESSION['message'] = 'Error creating settlement: ' . $result['message'];
        $_SESSION['message_type'] = 'error';
    }
}
*/
?>

<?php
// ============================================================================
// EXAMPLE 2: Integration in Loan Approval Handler
// Add to your all_applied_loan.php or loan approval page
// ============================================================================
?>

<?php
/*
// In all_applied_loan.php - After final approval is granted for a loan request

// Get the approved loan record
$loanInvoiceNo = 'LOAN-2026-0001';
$empId = '5160';
$loanAmount = 5000.00;
$approverRole = 'hr_manager'; // Role of person completing approval

// Check if settlement already exists
$existingSettlement = $pdo->prepare("
    SELECT id FROM settlement_records 
    WHERE request_inv_no = ? AND request_type = 'loan_request'
");
$existingSettlement->execute([$loanInvoiceNo]);
$exists = $existingSettlement->fetch();

if (!$exists) {
    // Create new settlement for this loan
    require_once 'includes/SettlementManager.php';
    $settlementMgr = new SettlementManager($pdo, $conDB);
    
    $result = $settlementMgr->createSettlement(
        $loanInvoiceNo,               // Request invoice number
        'loan_request',                // Request type
        $empId,                        // Employee ID
        $loanAmount,                   // Loan amount to disburse
        $_SESSION['emp_id']            // Created by (current user)
    );
    
    if ($result['success']) {
        // Settlement created successfully
        $_SESSION['message'] = 'Loan settlement created and awaiting approval';
        $_SESSION['message_type'] = 'success';
        
        // Update loan request with settlement reference (optional)
        // UPDATE emp_loan SET settlement_status = 'pending' WHERE inv_no = ?
    } else {
        $_SESSION['message'] = 'Error creating settlement: ' . $result['message'];
        $_SESSION['message_type'] = 'error';
    }
}
*/
?>

<?php
// ============================================================================
// EXAMPLE 3: Settlement Approvals Page
// Create new file: settlement_approvals.php
// ============================================================================
?>

<?php
/*
session_start();
require_once 'includes/header.php';
require_once 'includes/SettlementManager.php';

// Only allow if logged in
if (!isset($_SESSION['emp_id'])) {
    header('Location: login.php');
    exit;
}

$settlementMgr = new SettlementManager($pdo, $conDB);
$currentUserId = $_SESSION['emp_id'];
$currentUserName = $_SESSION['emp_name'] ?? 'User';

// Get pending settlements for this user as approver
$pendingSettlements = $pdo->prepare("
    SELECT sr.*, sa.approval_level, sa.approval_status
    FROM settlement_records sr
    JOIN settlement_approvals sa ON sr.id = sa.settlement_id
    WHERE sa.approver_id = ? AND sa.approval_status = 'pending'
    ORDER BY sr.created_at ASC
");
$pendingSettlements->execute([$currentUserId]);
$settlements = $pendingSettlements->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-wrapper">
    <div class="container-xl">
        <!-- Page title -->
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Settlement Approvals</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <?php if (!empty($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type'] ?? 'info' ?> alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Request #</th>
                                        <th>Type</th>
                                        <th>Employee</th>
                                        <th>Amount</th>
                                        <th>Level</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($settlements)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                No pending approvals
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($settlements as $settlement): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($settlement['request_inv_no']) ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $settlement['request_type'])) ?></td>
                                            <td><?= htmlspecialchars($settlement['emp_id']) ?></td>
                                            <td><?= number_format($settlement['settlement_amount'], 2) ?> SAR</td>
                                            <td><span class="badge badge-blue">Level <?= $settlement['approval_level'] ?></span></td>
                                            <td><?= date('Y-m-d H:i', strtotime($settlement['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-success" 
                                                            onclick="approveSettlement(<?= $settlement['id'] ?>)">
                                                        ✓ Approve
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" 
                                                            onclick="rejectSettlement(<?= $settlement['id'] ?>)">
                                                        ✗ Reject
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/settlement-manager.js"></script>
<script>
function approveSettlement(settlementId) {
    settlementManager.showApproveModal(settlementId);
}

function rejectSettlement(settlementId) {
    settlementManager.showRejectModal(settlementId);
}
</script>

<?php require_once 'includes/footer.php'; ?>
*/
?>

<?php
// ============================================================================
// EXAMPLE 4: Settlement Payment Processing Page
// Create new file: settlement_payment.php
// ============================================================================
?>

<?php
/*
session_start();
require_once 'includes/header.php';
require_once 'includes/SettlementManager.php';

// Only allow Finance users
if (!isset($_SESSION['emp_id'])) {
    header('Location: login.php');
    exit;
}

$settlementMgr = new SettlementManager($pdo, $conDB);

// Get all approved settlements ready for payment
$approvedSettlements = $pdo->prepare("
    SELECT DISTINCT sr.*
    FROM settlement_records sr
    WHERE sr.settlement_status = 'approved'
    AND sr.id NOT IN (
        SELECT DISTINCT settlement_id FROM settlement_approvals 
        WHERE approval_status = 'rejected'
    )
    ORDER BY sr.created_at ASC
");
$approvedSettlements->execute();
$settlements = $approvedSettlements->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">Settlement Payments</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Request #</th>
                                        <th>Employee</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($settlements)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                No approved settlements pending payment
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($settlements as $settlement): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($settlement['request_inv_no']) ?></td>
                                            <td><?= htmlspecialchars($settlement['emp_id']) ?></td>
                                            <td><?= number_format($settlement['settlement_amount'], 2) ?> SAR</td>
                                            <td><span class="badge badge-info">Approved</span></td>
                                            <td><?= date('Y-m-d H:i', strtotime($settlement['created_at'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="processPayment(<?= $settlement['id'] ?>)">
                                                    Process Payment
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/settlement-manager.js"></script>
<script>
function processPayment(settlementId) {
    settlementManager.showPaymentModal(settlementId);
}
</script>

<?php require_once 'includes/footer.php'; ?>
*/
?>

<?php
// ============================================================================
// EXAMPLE 5: Add Settlement Status to Vacation List
// Add this code to all_applied_vac.php in the table loop
// ============================================================================
?>

<?php
/*
// In all_applied_vac.php table loop:

<tr>
    <td><?= htmlspecialchars($record['inv_no']) ?></td>
    <td><?= htmlspecialchars($record['emp_name']) ?></td>
    <td><?= $record['vacdays'] ?></td>
    <td><?= $record['status'] ?></td>
    
    <!-- Add Settlement Status Column -->
    <td>
        <?php
        $settlementStatus = $pdo->prepare("
            SELECT settlement_status FROM settlement_records 
            WHERE request_inv_no = ? AND request_type = 'annual_vacation'
            LIMIT 1
        ");
        $settlementStatus->execute([$record['inv_no']]);
        $settlement = $settlementStatus->fetch(PDO::FETCH_ASSOC);
        
        if ($settlement) {
            $status = $settlement['settlement_status'];
            $badge = 'badge-secondary';
            if ($status === 'pending') $badge = 'badge-warning';
            elseif ($status === 'approved') $badge = 'badge-info';
            elseif ($status === 'processed') $badge = 'badge-success';
            elseif ($status === 'rejected') $badge = 'badge-danger';
            
            echo '<span class="badge ' . $badge . '">' . ucfirst($status) . '</span>';
        } else {
            echo '<span class="badge badge-secondary">Not Settled</span>';
        }
        ?>
    </td>
    
    <td>
        <a href="vacation_detail.php?id=<?= $record['inv_no'] ?>" class="btn btn-sm btn-info">
            View
        </a>
    </td>
</tr>
*/
?>

<?php
// ============================================================================
// EXAMPLE 6: Display Settlement Summary in Dashboard
// Add to dashboard.php or dashboardgm.php
// ============================================================================
?>

<?php
/*
// In dashboard.php - Add settlement stats section:

<?php
$settlementStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN settlement_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN settlement_status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN settlement_status = 'processed' THEN 1 ELSE 0 END) as processed,
        SUM(settlement_amount) as total_amount
    FROM settlement_records
");
$settlementStats->execute();
$stats = $settlementStats->fetch(PDO::FETCH_ASSOC);
?>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Pending Settlements</h6>
                <h2><?= $stats['pending'] ?? 0 ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Approved Settlements</h6>
                <h2><?= $stats['approved'] ?? 0 ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Processed Settlements</h6>
                <h2><?= $stats['processed'] ?? 0 ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6>Total Settlement Amount</h6>
                <h2><?= number_format($stats['total_amount'] ?? 0, 0) ?> SAR</h2>
            </div>
        </div>
    </div>
</div>
*/
?>
