<?php
/**
 * EXAMPLE: How to Integrate WPS File Upload into All Settlements Page
 * 
 * Add this code to all_settlements.php in the action buttons section
 * to enable WPS file upload for HR Payroll users
 * 
 * Location in all_settlements.php: In the card-footer with action buttons,
 * add a check for HR Payroll and WPS file requirement
 */

// This example shows how to add WPS file upload button in the settlement card actions

// In all_settlements.php, near the card-footer section (around line 450-500):

?>

<!-- EXAMPLE: Add this code to all_settlements.php settlement card -->

<?php foreach ($settlements as $settlement): ?>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card settlement-card h-100">
            <!-- ... existing card header and body ... -->
            
            <div class="card-footer">
                <a href="settlement_status_history.php?request_inv_no=<?= urlencode($settlement['request_inv_no']); ?>" 
                   target="_blank" class="btn btn-info btn-sm">
                    <i class="fa fa-history"></i> <?= __('history') ?>
                </a>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary dropdown-toggle btn-sm" 
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?= __('actions') ?> <span class="caret"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        
                        <!-- Existing approve action -->
                        <?php if ($settlement['current_approver_id'] == $empid && 
                                  $settlement['settlement_status'] === 'pending_approval'): ?>
                            <a class="dropdown-item" href="javascript:void(0);" 
                               onclick="approveSettlement(<?= (int)$settlement['id']; ?>, 
                                                         '<?= htmlspecialchars($settlement['request_inv_no'], ENT_QUOTES); ?>', 
                                                         '<?= htmlspecialchars($settlement['emp_id'], ENT_QUOTES); ?>')">
                                <i class="fa fa-check text-success"></i> <?= __('approve') ?>
                            </a>
                        <?php endif; ?>
                        
                        <!-- WPS FILE UPLOAD BUTTON - NEW -->
                        <?php
                        // Check if current user is HR Payroll
                        $currentUserType = $_SESSION['user_type'] ?? '';
                        $isHRPayroll = ($currentUserType === 'hr_payroll');
                        
                        // Check if settlement needs WPS file upload
                        if ($isHRPayroll && $settlement['settlement_status'] === 'pending_approval') {
                            $hasWPSFile = !empty($settlement['wps_file_path']) && 
                                        $settlement['wps_upload_status'] === 'uploaded';
                            
                            if (!$hasWPSFile) {
                                ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:void(0);" 
                                   onclick="showSettlementApprovalForHRPayroll(
                                       <?= (int)$settlement['id']; ?>,
                                       '<?= htmlspecialchars($settlement['request_inv_no'], ENT_QUOTES); ?>',
                                       '<?= htmlspecialchars($settlement['emp_id'], ENT_QUOTES); ?>',
                                       '<?= htmlspecialchars($settlement['emp_name'], ENT_QUOTES); ?>',
                                       <?= (float)$settlement['settlement_amount']; ?>,
                                       'HR Approval Chain'
                                   )">
                                    <i class="fa fa-upload text-primary"></i> 
                                    <?= __('upload_wps_file') ?: 'Upload WPS File' ?>
                                </a>
                                <?php
                            } else {
                                // File already uploaded - show download option
                                ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?= htmlspecialchars($settlement['wps_file_path']); ?>" 
                                   download>
                                    <i class="fa fa-download text-success"></i> 
                                    <?= __('download_wps_file') ?: 'Download WPS File' ?>
                                </a>
                                <span class="dropdown-item-text text-muted small">
                                    <?= __('uploaded') ?>: <?= date('d M Y', strtotime($settlement['wps_uploaded_at'])); ?>
                                </span>
                                <?php
                            }
                        }
                        ?>
                        <!-- END WPS FILE UPLOAD SECTION -->
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- ================================================================ -->

<?php
/**
 * INTEGRATION GUIDE
 * 
 * The code above shows:
 * 
 * 1. Check if current user is HR Payroll
 *    $isHRPayroll = ($currentUserType === 'hr_payroll');
 * 
 * 2. Check if settlement needs WPS file (not yet uploaded)
 *    if (!$hasWPSFile) {
 *        Show "Upload WPS File" button
 *    }
 * 
 * 3. When user clicks button, it triggers:
 *    showSettlementApprovalForHRPayroll()
 *    ↓
 *    Shows modal with file upload
 *    ↓
 *    uploadWPSFileToSettlement()
 *    ↓
 *    Posts to settlement_handler.php with 'upload_wps_file' action
 * 
 * TRANSLATION KEYS TO ADD:
 * 
 * Add these to your language files:
 * 'upload_wps_file' => 'Upload WPS File'
 * 'download_wps_file' => 'Download WPS File'
 * 'wps_file_uploaded' => 'WPS File Uploaded'
 * 'settlement_processing' => 'Settlement Processing'
 * 'select_wps_file' => 'Select WPS File (Payroll System)'
 * 'upload_notes_optional' => 'Upload Notes (Optional)'
 * 'wps_file_formats' => 'Accepted formats: Excel (.xlsx, .xls), CSV (.csv), Text (.txt)'
 * 
 * STYLING TIPS:
 * 
 * For better UX, you can add custom CSS for the WPS upload button:
 * 
 * .wps-upload-btn {
 *     background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
 *     border: none;
 * }
 * 
 * .wps-uploaded-badge {
 *     background: #28a745;
 *     color: white;
 *     padding: 4px 8px;
 *     border-radius: 4px;
 *     font-size: 0.8rem;
 * }
 * 
 * JAVASCRIPT INTEGRATION:
 * 
 * Make sure these functions are available in your page:
 * - showSettlementApprovalForHRPayroll() [ADDED in all_applied_vac.php]
 * - uploadWPSFileToSettlement() [ADDED in all_applied_vac.php]
 * - __() translation function
 * 
 * ERROR HANDLING:
 * 
 * The upload functions include error handling for:
 * - Missing parameters
 * - Invalid file types
 * - File size exceeds limit
 * - Server errors
 * 
 * All errors are displayed to the user via SweetAlert2 modals
 * 
 * AUDIT TRAIL:
 * 
 * When a file is uploaded:
 * 1. settlement_records table is updated with file info and timestamp
 * 2. wps_uploaded_by stores employee ID
 * 3. smt_request_status gets a new record with status 'wps_file_uploaded'
 * 
 * This creates a complete audit trail of who uploaded what file and when.
 */
?>

<!-- ALTERNATE: Simpler Inline Integration -->

<?php
// Alternative: Simpler version without all the comments
// Use this if you want a minimal integration

// In your settlement card actions, add this:
?>

<?php
$currentUserType = $_SESSION['user_type'] ?? '';
$isHRPayroll = ($currentUserType === 'hr_payroll');
$needsWPS = $isHRPayroll && 
            $settlement['settlement_status'] === 'pending_approval' && 
            empty($settlement['wps_file_path']);

if ($needsWPS) {
    ?>
    <a class="dropdown-item" href="javascript:void(0);" 
       onclick="showSettlementApprovalForHRPayroll(
           <?= (int)$settlement['id']; ?>,
           '<?= htmlspecialchars($settlement['request_inv_no'], ENT_QUOTES); ?>',
           '<?= htmlspecialchars($settlement['emp_id'], ENT_QUOTES); ?>',
           '<?= htmlspecialchars($settlement['emp_name'], ENT_QUOTES); ?>',
           <?= (float)$settlement['settlement_amount']; ?>,
           'Settlement Approval'
       )">
        <i class="fa fa-upload text-primary"></i> Upload WPS File
    </a>
    <?php
}
?>

<!-- ================================================================ -->

<?php
/**
 * MINIMUM SETUP CHECKLIST FOR ALL_SETTLEMENTS.PHP
 * 
 * To fully integrate WPS functionality into all_settlements.php:
 * 
 * 1. ✓ Ensure SettlementManager_Corrected is loaded
 *       (should already be in the PHP includes at top)
 * 
 * 2. ✓ Include the JavaScript functions from all_applied_vac.php
 *       (copy showSettlementApprovalForHRPayroll and uploadWPSFileToSettlement)
 *    
 * 3. ✓ Check payment fields in SELECT query
 *       Make sure this columns are selected:
 *       - wps_file_path
 *       - wps_file_name
 *       - wps_upload_status
 *       - wps_uploaded_at
 * 
 * 4. ✓ Add the settlement card action code from this file
 *       In the dropdown-menu of actions
 * 
 * 5. ✓ Optional: Add translation keys to your language files
 * 
 * KEY VARIABLES NEEDED IN SETTLEMENT ARRAY:
 * - id (settlement record ID)
 * - request_inv_no (settlement invoice number)
 * - emp_id (employee ID)
 * - emp_name (employee name)
 * - settlement_amount (amount in SAR)
 * - settlement_status (pending_approval, approved, etc)
 * - wps_file_path (path to WPS file if uploaded)
 * - wps_upload_status (pending, uploaded, approved)
 * - wps_uploaded_at (timestamp of upload)
 * 
 * TESTING CHECKLIST:
 * 
 * After setup, verify:
 * [ ] HR Payroll user sees "Upload WPS File" button for pending settlements
 * [ ] Clicking button opens modal with file input
 * [ ] Can select Excel/CSV/Text files
 * [ ] File upload completes successfully
 * [ ] File stored in /uploads/wps_files/YYYY/MM/
 * [ ] settlement_records updated with file info
 * [ ] Status record created in smt_request_status
 * [ ] WPS file button changes to "Download WPS File" after upload
 * [ ] Other users don't see WPS upload button
 */
?>
