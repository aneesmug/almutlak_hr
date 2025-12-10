<?php
/**
 * QUICK SETUP GUIDE - EMPLOYEE REJOIN APPROVAL SYSTEM
 * 
 * Follow these steps to activate the new rejoin approval system
 */

echo "============================================================================\n";
echo "EMPLOYEE REJOIN APPROVAL SYSTEM - SETUP GUIDE\n";
echo "============================================================================\n\n";

echo "STEP 1: Run Database Migration\n";
echo "------------------------------\n";
echo "Execute this file in your browser to create the necessary tables:\n";
echo "URL: http://your-domain/system/includes/migrations/add_rejoin_approval_system.php\n\n";

echo "Or run via command line:\n";
echo "php includes/migrations/add_rejoin_approval_system.php\n\n";

echo "Expected output:\n";
echo "{\n";
echo '  "status": "success",';
echo "\n";
echo '  "message": "Rejoin approval system database migration completed successfully"';
echo "\n";
echo "}\n\n";

echo "STEP 2: Verify Database Schema\n";
echo "--------------------------------\n";
echo "Check that these tables were created:\n";
echo "- rejoin_requests\n";
echo "- rejoin_notifications\n\n";

echo "Check that emp_vacation table has these new columns:\n";
echo "- rejoin_request_status\n";
echo "- rejoin_requested_date\n";
echo "- rejoin_requested_at\n";
echo "- rejoin_approved_date\n";
echo "- rejoin_approved_by\n";
echo "- rejoin_approved_at\n";
echo "- rejoin_adjustment_allowed\n";
echo "- rejoin_adjustment_from_date\n";
echo "- rejoin_adjustment_to_date\n";
echo "- rejoin_adjustment_reason\n";
echo "- rejoin_final_date\n";
echo "- rejoin_final_confirmed_at\n\n";

echo "STEP 3: Verify File Updates\n";
echo "----------------------------\n";
echo "Files that have been modified:\n";
echo "✓ view_employee.php - Added rejoin approval functions\n";
echo "✓ includes/emp_top_info.php - Updated rejoin button call\n";
echo "✓ includes/ajaxFile/ajaxVacation.php - Added AJAX handlers\n\n";

echo "Files that have been created:\n";
echo "✓ rejoin_approvals.php - Supervisor dashboard\n";
echo "✓ includes/api/get_rejoin_requests.php - Get requests API\n";
echo "✓ includes/migrations/add_rejoin_approval_system.php - Database migration\n\n";

echo "STEP 4: Test the System\n";
echo "-----------------------\n";
echo "1. Login as an employee who has completed a vacation\n";
echo "2. Go to view_employee.php for that employee\n";
echo "3. In the 'More Actions' menu, click 'Rejoin'\n";
echo "4. Select a rejoin date and submit\n";
echo "5. Login as the supervisor\n";
echo "6. Go to rejoin_approvals.php\n";
echo "7. Review the pending request\n";
echo "8. Approve, adjust, or reject the request\n\n";

echo "STEP 5: Add Translation Keys (if using translations)\n";
echo "-----------------------------------------------------\n";
echo "Add these keys to your translation file:\n\n";

$translation_keys = [
    'rejoin_request_title' => 'Submit Rejoin Request',
    'rejoin_request_subtitle' => 'Your rejoin request will be sent to your direct supervisor for approval',
    'rejoin_date_label' => 'Actual Rejoin Date',
    'planned_return_text' => 'Planned Return',
    'rejoin_reason_label' => 'Reason for Date Change (if applicable)',
    'submit_request_button' => 'Submit Request',
    'rejoin_date_required_validation' => 'Please select a rejoin date',
    'rejoin_date_range_validation' => 'Rejoin date cannot be more than 3 days after the planned return date',
    'review_rejoin_request_title' => 'Review Rejoin Request',
    'approval_action_label' => 'Action',
    'approve_button' => 'Approve',
    'approve_text' => 'Accept the rejoin date as submitted',
    'adjust_button' => 'Adjust',
    'adjust_3days_text' => 'Allow employee to adjust date (±3 days from submitted date)',
    'adjustment_explanation' => 'Employee can modify the date within 3 days before or after the submitted date',
    'reject_button' => 'Reject',
    'reject_text' => 'Request changes or requires HR review',
    'explain_rejection' => 'Explain why you are rejecting this request',
    'approval_note_label' => 'Approval Note (Optional)',
    'rejection_reason_required' => 'Please provide a reason for rejection',
    'processing_title' => 'Processing...',
    'please_wait_text' => 'Please wait while we process your request',
    'rejoin_request_submitted_text' => 'Your rejoin request has been submitted for approval',
    'rejoin_request_approved_text' => 'Rejoin request has been approved',
    'rejoin_adjustment_allowed_text' => 'Employee has been allowed to adjust rejoin date within 3 days',
    'rejoin_request_rejected_text' => 'Rejoin request has been rejected',
];

foreach ($translation_keys as $key => $value) {
    echo "  '$key' => '$value',\n";
}

echo "\nSTEP 6: Configure Supervisor Access\n";
echo "------------------------------------\n";
echo "Ensure employees have 'reports_to' field set:\n";
echo "- Go to employee records\n";
echo "- Set 'reports_to' to the supervisor's emp_id\n";
echo "- This determines who can approve rejoin requests\n\n";

echo "STEP 7: Access Supervisor Dashboard\n";
echo "------------------------------------\n";
echo "Supervisors can access the rejoin approval dashboard at:\n";
echo "URL: /system/rejoin_approvals.php\n\n";

echo "============================================================================\n";
echo "SYSTEM IS NOW READY!\n";
echo "============================================================================\n\n";

echo "KEY FEATURES:\n";
echo "✓ Employees submit rejoin dates when returning from vacation\n";
echo "✓ Supervisor can approve, adjust (3-day window), or reject\n";
echo "✓ If date chosen wrong, supervisor allows ±3 days adjustment\n";
echo "✓ Complete audit trail of all requests and approvals\n";
echo "✓ Dedicated supervisor dashboard for easy management\n\n";

echo "For detailed documentation, see: REJOIN_SYSTEM_DOCUMENTATION.md\n";
?>
