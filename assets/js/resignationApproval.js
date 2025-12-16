/**
 * Resignation Approval System
 * Multi-step SweetAlert2 workflow for reviewing and approving/rejecting resignations
 */

// Global functions for onclick handlers
function approveResignation(resignationId, empId, empName, iqama, designation, department, lastDay) {
    // Use the new multi-step approval wizard
    openResignationApprovalWizard(resignationId, empId, empName, iqama, designation, department, lastDay);
}

/**
 * Open the appropriate approval wizard based on user role
 * Direct supervisors skip to replacement info
 * HR Operations sees full wizard with employee info, exit interview, and replacement
 */
function openResignationApprovalWizard(resignationId, empId, empName, iqama, designation, department, lastDay) {
    const data = {
        id: resignationId,
        empId: empId,
        name: empName,
        iqama: iqama,
        designation: designation,
        department: department,
        lastDay: lastDay
    };
    
    // Check if current user is direct supervisor (approval level 1)
    checkApprovalLevel(resignationId, function(approvalLevel) {
        if (approvalLevel === 1) {
            // Direct supervisor - skip to Step 2 (Replacement Info)
            showStep2ReplacementInfo(data);
        } else if (approvalLevel === 2) {
            // HR Operations - show full wizard from Step 1 with all info
            fetchExitInterviewData(resignationId, function(exitData) {
                data.exitInterview = exitData;
                showHRStep1EmployeeInfo(data);
            });
        } else if (approvalLevel === 3) {
            // HR Payroll - show summary with Approve & Create EOS button
            fetchExitInterviewData(resignationId, function(exitData) {
                data.exitInterview = exitData;
                showHRPayrollApprovalSummary(data);
            });
        } else {
            // Other approvers - show summary only
            showStep1EmployeeInfo(data);
        }
    });
}

/**
 * Check the current approval level for this resignation
 */
function checkApprovalLevel(resignationId, callback) {
    $.ajax({
        url: './includes/ajaxFile/ajaxResignation.php',
        type: 'POST',
        data: {
            ajaxType: 'get_approval_level',
            resignation_id: resignationId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                callback(response.approval_level);
            } else {
                // Default to level 1 if unable to determine
                callback(1);
            }
        },
        error: function() {
            // Default to level 1 if unable to determine
            callback(1);
        }
    });
}

/**
 * Fetch exit interview data for HR Operations view
 */
function fetchExitInterviewData(resignationId, callback) {
    $.ajax({
        url: './includes/ajaxFile/ajaxResignation.php',
        type: 'POST',
        data: {
            ajaxType: 'get_exit_interview',
            resignation_id: resignationId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                callback(response.data || {});
            } else {
                callback({});
            }
        },
        error: function() {
            callback({});
        }
    });
}

/**
 * HR Payroll Step: Display Summary and Approve & Create EOS Button
 */
function showHRPayrollApprovalSummary(data) {
    // First, fetch the resignation details to get HR's last working day
    $.ajax({
        url: './includes/ajaxFile/ajaxResignation.php',
        type: 'POST',
        data: {
            ajaxType: 'get_resignation_details',
            resignation_id: data.id
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.resignation) {
                const resignation = response.resignation;
                data.hrLastWorkingDay = resignation.hr_last_working_day;
            }
            displayHRPayrollSummary(data);
        },
        error: function() {
            displayHRPayrollSummary(data);
        }
    });
}

/**
 * Display HR Payroll Summary Modal
 */
function displayHRPayrollSummary(data) {
    // Build exit interview HTML
    let exitInterviewHTML = '';
    if (data.exitInterview && Object.keys(data.exitInterview).length > 0) {
        exitInterviewHTML = `<div class="exit-interview-responses">`;
        
        // Map of question indices to default labels
        const questionLabels = {
            'q1_reasons': '1. What are the main reasons behind your decision to leave the company?',
            'q2_support': '2. Did you feel supported and appreciated by management and colleagues?',
            'q3_resources': '3. Were you provided with sufficient tools and resources to perform your job effectively?',
            'q4_manager': '4. How would you evaluate your direct manager\'s leadership style?',
            'q5_growth': '5. Were the available growth and development opportunities suitable for you?',
            'q6_compensation': '6. How do you evaluate the compensation and benefits you received?',
            'q7_different': '7. What do you wish had been different during your time here?',
            'q8_recommend': '8. Would you recommend the company as a workplace to others? Why or why not?',
            'q9_additional': '9. Is there anything else you would like to share before you leave?'
        };
        
        // Display each question and answer
        for (const [key, label] of Object.entries(questionLabels)) {
            const answer = data.exitInterview[key] || 'No response provided';
            exitInterviewHTML += `
                <div class="question-response mb-3 pb-3 border-bottom">
                    <p class="font-weight-bold mb-2">${label}</p>
                    <p class="text-muted">${answer}</p>
                </div>
            `;
        }
        
        exitInterviewHTML += `</div>`;
    }
    
    Swal.fire({
        title: __('resignation_final_approval') || 'Resignation - Final Approval',
        html: `
            <div class="resignation-approval-wizard hr-operations-view">
                <div class="wizard-section">
                    <h4 class="section-title" style="color: #28a745;"><i class="fas fa-check-circle"></i> ${__('all_approvals_completed') || 'All Prior Approvals Completed'}</h4>
                    <p class="text-muted mt-3">${__('hr_payroll_final_review') || 'As HR Payroll, you are conducting the final review before creating the End of Service record.'}</p>
                </div>
                
                <div class="wizard-section mt-4">
                    <h4 class="section-title"><i class="fas fa-user"></i> ${__('employee_information') || 'Employee Information'}</h4>
                    <table class="info-table">
                        <tr>
                            <td class="label">${__('emp_id') || 'Employee ID'}:</td>
                            <td class="value">${data.empId}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('full_name')}:</td>
                            <td class="value">${data.name}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('designation') || 'Designation'}:</td>
                            <td class="value">${data.designation}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('department') || 'Department'}:</td>
                            <td class="value">${data.department}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('last_working_day_employee') || 'Last Workday by Employee'}:</td>
                            <td class="value text-muted">${formatDate(data.lastDay)}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('last_working_day_hr') || 'Last Workday by HR'} <i class="fas fa-star text-warning"></i>:</td>
                            <td class="value text-success font-weight-bold">${data.hrLastWorkingDay ? formatDate(data.hrLastWorkingDay) : 'N/A'}</td>
                        </tr>
                    </table>
                </div>
                
                

            </div>
        `,
        /*<div class="wizard-section mt-4">
            <h4 class="section-title"><i class="fas fa-comment-dots"></i> ${__('exit_interview_summary') || 'Exit Interview Summary'}</h4>
            ${exitInterviewHTML}
        </div>*/
        icon: 'success',
        width: '800px',
        showCancelButton: true,
        confirmButtonText: __('approve_create_eos') || 'Approve & Create EOS',
        cancelButtonText: __('reject') || 'REJECT',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        allowOutsideClick: false,
        customClass: {
            popup: 'resignation-wizard-popup',
            confirmButton: 'btn-lg',
            cancelButton: 'btn-lg'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit HR Payroll approval and create EOS
            submitHRPayrollApprovalWithEOS(data.id, data.empId, data.lastDay);
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Reject button clicked
            promptRejection(data.id, data.name);
        }
    });
}

/**
 * Submit HR Payroll Approval and Create End of Service
 */
function submitHRPayrollApprovalWithEOS(resignationId, empId, lastWorkingDay) {
    // Fetch the full resignation to get HR's last working day
    $.ajax({
        url: './includes/ajaxFile/ajaxResignation.php',
        type: 'POST',
        data: {
            ajaxType: 'get_resignation_details',
            resignation_id: resignationId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.resignation) {
                const resignation = response.resignation;
                // Use HR's selected date if it exists, otherwise use employee's date
                const finalLastWorkingDate = resignation.hr_last_working_day || resignation.last_working_day || lastWorkingDay;
                submitEOSApproval(resignationId, empId, finalLastWorkingDate);
            } else {
                // Fallback to original date
                submitEOSApproval(resignationId, empId, lastWorkingDay);
            }
        },
        error: function() {
            // Fallback to original date
            submitEOSApproval(resignationId, empId, lastWorkingDay);
        }
    });
}

/**
 * Execute the actual approval and EOS creation
 */
function submitEOSApproval(resignationId, empId, finalLastWorkingDate) {
    Swal.fire({
        title: __('processing') || 'Processing...',
        html: __('please_wait') || 'Please wait while we approve the resignation and create the End of Service record',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const formData = new FormData();
    formData.append('ajaxType', 'approve_resignation');
    formData.append('resignation_id', resignationId);
    formData.append('approval_level', '3'); // HR Payroll level
    formData.append('emp_id', empId);
    formData.append('last_working_date', finalLastWorkingDate);
    formData.append('create_eos', '1'); // Flag to create EOS
    
    $.ajax({
        url: './includes/ajaxFile/ajaxResignation.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            Swal.fire({
                title: response.title || __('success') || 'Success',
                text: response.message || __('resignation_approved_eos_created') || 'Resignation has been approved and End of Service record has been created',
                icon: response.type || 'success',
                confirmButtonText: __('ok') || 'OK',
                allowOutsideClick: false
            }).then(() => {
                // Redirect to EOS page
                const eosUrl = `./emp_end_of_service.php?emp_id=${empId}&end_date=${finalLastWorkingDate}`;
                window.location.href = eosUrl;
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            let errorMessage = __('failed_to_approve') || 'Failed to approve resignation';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMessage = jqXHR.responseJSON.message;
            }
            
            Swal.fire({
                title: __('error') || 'Error',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: __('ok') || 'OK'
            ,allowOutsideClick:false});
        }
    });
}

/**
 * HR Operations Step 1: Display Employee and Resignation Information
 */
function showHRStep1EmployeeInfo(data) {
    Swal.fire({
        title: __('resignation_review') || 'Resignation Review',
        html: `
            <div class="resignation-approval-wizard hr-operations-view">
                <div class="wizard-section">
                    <h4 class="section-title"><i class="fas fa-user"></i> ${__('employee_information') || 'Employee Information'}</h4>
                    <table class="info-table">
                        <tr>
                            <td class="label">${__('emp_id') || 'Employee ID'}:</td>
                            <td class="value">${data.empId}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('id_iqama') || 'ID/Iqama'}:</td>
                            <td class="value">${data.iqama}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('emp_name') || 'Employee Name'}:</td>
                            <td class="value">${data.name}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('designation') || 'Designation'}:</td>
                            <td class="value">${data.designation}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('department') || 'Department'}:</td>
                            <td class="value">${data.department}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="wizard-section mt-4">
                    <h4 class="section-title"><i class="fas fa-calendar-times"></i> ${__('resignation_information') || 'Resignation Information'}</h4>
                    <table class="info-table">
                        <tr>
                            <td class="label">${__('last_working_day_employee') || 'Last Workday by Employee'}:</td>
                            <td class="value text-danger font-weight-bold">${formatDate(data.lastDay)}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('last_working_day_hr') || 'Last Workday by HR'} <span class="text-danger">*</span>:</td>
                            <td class="value">
                                <input type="text" id="hr_last_working_day" class="form-control datepicker" placeholder="YYYY-MM-DD" style="max-width: 200px;" required autocomplete="off">
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        `,
        icon: 'question',
        width: '800px',
        showCancelButton: true,
        confirmButtonText: __('next') || 'NEXT',
        cancelButtonText: __('reject') || 'REJECT',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        customClass: {
            popup: 'resignation-wizard-popup',
            confirmButton: 'btn-lg',
            cancelButton: 'btn-lg'
        },
        didOpen: () => {
            // Initialize datepicker
            $('#hr_last_working_day').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true,
                startDate: new Date()
            });
        },
        preConfirm: () => {
            const hrLastWorkingDay = $('#hr_last_working_day').val().trim();
            if (!hrLastWorkingDay) {
                Swal.showValidationMessage(__('last_working_day_hr_required') || 'Last Workday by HR is required');
                return false;
            }
            return true;
        }
    ,allowOutsideClick:false}).then((result) => {
        if (result.isConfirmed) {
            // Store HR last working day in data object for later use
            data.hrLastWorkingDay = $('#hr_last_working_day').val().trim();
            // Move to Step 2: Exit Interview Questions
            showHRStep2ExitInterview(data);
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Reject button clicked
            promptRejection(data.id, data.name);
        }
    });
}

/**
 * HR Operations Step 2: Display Exit Interview Questions
 */
function showHRStep2ExitInterview(data) {
    // Build exit interview HTML
    let exitInterviewHTML = '';
    if (data.exitInterview && Object.keys(data.exitInterview).length > 0) {
        exitInterviewHTML = `<div class="exit-interview-responses">`;
        
        // Map of question indices to default labels
        const questionLabels = {
            'q1_reasons': '1. What are the main reasons behind your decision to leave the company?',
            'q2_support': '2. Did you feel supported and appreciated by management and colleagues?',
            'q3_resources': '3. Were you provided with sufficient tools and resources to perform your job effectively?',
            'q4_manager': '4. How would you evaluate your direct manager\'s leadership style?',
            'q5_growth': '5. Were the available growth and development opportunities suitable for you?',
            'q6_compensation': '6. How do you evaluate the compensation and benefits you received?',
            'q7_different': '7. What do you wish had been different during your time here?',
            'q8_recommend': '8. Would you recommend the company as a workplace to others? Why or why not?',
            'q9_additional': '9. Is there anything else you would like to share before you leave?'
        };
        
        // Display each question and answer
        for (const [key, label] of Object.entries(questionLabels)) {
            const answer = data.exitInterview[key] || 'No response provided';
            exitInterviewHTML += `
                <div class="question-response mb-3 pb-3 border-bottom">
                    <p class="font-weight-bold mb-2">${label}</p>
                    <p class="text-muted">${answer}</p>
                </div>
            `;
        }
        
        exitInterviewHTML += `</div>`;
    }
    
    Swal.fire({
        title: __('exit_interview_questions') || 'Exit Interview Questions',
        html: `
            <div class="resignation-approval-wizard hr-operations-view">
                <div class="wizard-section">
                    ${exitInterviewHTML}
                </div>
            </div>
        `,
        icon: 'info',
        width: '800px',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: __('next') || 'NEXT',
        denyButtonText: __('back') || 'BACK',
        cancelButtonText: __('reject') || 'REJECT',
        confirmButtonColor: '#28a745',
        denyButtonColor: '#6c757d',
        cancelButtonColor: '#dc3545',
        customClass: {
            popup: 'resignation-wizard-popup',
            confirmButton: 'btn-lg',
            cancelButton: 'btn-lg'
        }
    ,allowOutsideClick:false}).then((result) => {
        if (result.isConfirmed) {
            // Move to Step 3: Replacement Information from Direct Manager
            fetchReplacementData(data.id, function(replacementData) {
                data.managerReplacementData = replacementData;
                showHRStep3ReplacementSummary(data);
            });
        } else if (result.isDenied) {
            // Back button - return to Step 1
            showHRStep1EmployeeInfo(data);
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Reject button clicked
            promptRejection(data.id, data.name);
        }
    });
}

/**
 * Fetch replacement data entered by direct manager
 */
function fetchReplacementData(resignationId, callback) {
    $.ajax({
        url: './includes/ajaxFile/ajaxResignation.php',
        type: 'POST',
        data: {
            ajaxType: 'get_replacement_data',
            resignation_id: resignationId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                callback(response.data || {});
            } else {
                callback({});
            }
        },
        error: function() {
            callback({});
        }
    });
}

/**
 * HR Operations Step 3: Display Replacement Information from Direct Manager
 */
function showHRStep3ReplacementSummary(data) {
    // Build replacement information HTML based on manager's entry
    let replacementHTML = '';
    
    if (data.managerReplacementData) {
        const repData = data.managerReplacementData;
        
        if (repData.needs_replacement === 1 || repData.needs_replacement === '1') {
            // Replacement needed - show details
            replacementHTML = `
                <div class="replacement-summary-view">
                    <div class="alert alert-info">
                        <strong><i class="fas fa-info-circle"></i> ${__('replacement_required') || 'Replacement Required'}: YES</strong>
                    </div>
                    
                    <div class="replacement-details-card">
                        <h5 class="card-title">${__('replacement_job_details') || 'Replacement Job Details'}</h5>
                        <table class="info-table">
                            <tr>
                                <td class="label">${__('job_title') || 'Job Title of the Replacement'}:</td>
                                <td class="value">${repData.job_title || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td class="label">${__('job_description') || 'Job Description'}:</td>
                                <td class="value"><pre>${repData.job_description || 'N/A'}</pre></td>
                            </tr>
                            <tr>
                                <td class="label">${__('experience') || 'Experience'}:</td>
                                <td class="value">${repData.experience || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td class="label">${__('certificate') || 'Certificate'}:</td>
                                <td class="value">${repData.certificate || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td class="label">${__('academic_achievement') || 'Academic Achievement'}:</td>
                                <td class="value">${repData.academic_achievement || 'N/A'}</td>
                            </tr>
                            <tr>
                                <td class="label">${__('date_of_joining') || 'Date of Joining'}:</td>
                                <td class="value">${formatDate(repData.date_of_joining) || 'N/A'}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            `;
        } else {
            // No replacement needed
            replacementHTML = `
                <div class="replacement-summary-view">
                    <div class="alert alert-warning">
                        <strong><i class="fas fa-times-circle"></i> ${__('replacement_required') || 'Replacement Required'}: NO</strong>
                    </div>
                    <p class="text-muted text-center">${__('no_replacement_needed') || 'No replacement employee is needed for this position.'}</p>
                </div>
            `;
        }
    } else {
        // No replacement data found
        replacementHTML = `
            <div class="replacement-summary-view">
                <div class="alert alert-secondary">
                    <strong><i class="fas fa-question-circle"></i> ${__('replacement_info_not_available') || 'Replacement Information Not Available'}</strong>
                </div>
                <p class="text-muted text-center">${__('no_replacement_info') || 'Replacement information was not provided by the direct manager.'}</p>
            </div>
        `;
    }
    
    Swal.fire({
        title: __('replacement_information') || 'Replacement Information',
        html: `
            <div class="resignation-approval-wizard hr-operations-view">
                <div class="wizard-section">
                    <p class="text-muted text-left mb-3"><small><i class="fas fa-info-circle"></i> ${__('replacement_info_from_manager') || 'Information provided by Direct Manager'}</small></p>
                    ${replacementHTML}
                </div>
            </div>
        `,
        icon: 'info',
        width: '800px',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: __('approve') || 'APPROVE',
        denyButtonText: __('back') || 'BACK',
        cancelButtonText: __('reject') || 'REJECT',
        confirmButtonColor: '#28a745',
        denyButtonColor: '#6c757d',
        cancelButtonColor: '#dc3545',
        customClass: {
            popup: 'resignation-wizard-popup',
            confirmButton: 'btn-lg',
            cancelButton: 'btn-lg'
        }
    ,allowOutsideClick:false}).then((result) => {
        if (result.isConfirmed) {
            // Approve resignation - pass the stored HR last working day
            submitHRApproval(data.id, null, data.hrLastWorkingDay);
        } else if (result.isDenied) {
            // Back button - return to Step 2
            showHRStep2ExitInterview(data);
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Reject button clicked
            promptRejection(data.id, data.name);
        }
    });
}

/**
 * Submit HR Operations Approval to Backend
 */
function submitHRApproval(resignationId, replacementData, hrLastWorkingDay) {
    // Note: hrLastWorkingDay is passed from the data object stored in Step 1
    
    Swal.fire({
        title: __('processing') || 'Processing...',
        html: __('please_wait') || 'Please wait while we process your approval',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const formData = new FormData();
    formData.append('ajaxType', 'approve_resignation');
    formData.append('resignation_id', resignationId);
    formData.append('approval_level', '2'); // HR Operations level
    
    if (hrLastWorkingDay) {
        formData.append('hr_last_working_day', hrLastWorkingDay);
    }
    
    if (replacementData) {
        formData.append('needs_replacement', '1');
        formData.append('replacement_data', JSON.stringify(replacementData));
    } else {
        formData.append('needs_replacement', '0');
    }
    
    $.ajax({
        url: './includes/ajaxFile/ajaxResignation.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            Swal.fire({
                title: response.title || __('success') || 'Success',
                text: response.message || __('resignation_approved') || 'Resignation has been approved successfully',
                icon: response.type || 'success',
                confirmButtonText: __('ok') || 'OK'
            }).then(() => {
                location.reload();
                allowOutsideClick:false});
        },
        error: function(jqXHR, textStatus, errorThrown) {
            let errorMessage = __('failed_to_approve') || 'Failed to approve resignation';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMessage = jqXHR.responseJSON.message;
            }
            
            Swal.fire({
                title: __('error') || 'Error',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: __('ok') || 'OK'
            ,allowOutsideClick:false});
        }
    });
}

function rejectResignation(resignationId, empName) {
    Swal.fire({
        title: __('reject_resignation') || 'Reject Resignation',
        html: `
            <p class="mb-3">${__('confirm_reject_resignation_for') || 'Are you sure you want to reject the resignation request for'} <strong>${empName}</strong>?</p>
            <div class="form-group text-left">
                <label for="rejection_reason" class="font-weight-bold">${__('rejection_reason') || 'Rejection Reason'} <span class="text-danger">*</span></label>
                <textarea id="rejection_reason" class="form-control" rows="4" placeholder="${__('enter_rejection_reason') || 'Please provide a reason for rejection...'}" required></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: __('yes_reject') || 'Yes, Reject',
        cancelButtonText: __('cancel') || 'Cancel',
        allowOutsideClick: false,
        preConfirm: () => {
            const reason = document.getElementById('rejection_reason').value.trim();
            if (!reason) {
                Swal.showValidationMessage(__('rejection_reason_required_validation') || 'Please provide a reason for rejection');
                return false;
            }
            return { reason: reason };
        }
    ,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
        if (result.isConfirmed && result.value) {
            submitRejection(resignationId, result.value.reason);
        }
    });
}

$(document).ready(function() {
    
    // View Resignation Details
    $(document).on('click', '.viewResignation', function() {
        const $btn = $(this);
        const data = {
            id: $btn.data('id'),
            empId: $btn.data('emp-id'),
            iqama: $btn.data('iqama'),
            name: $btn.data('name'),
            designation: $btn.data('designation'),
            department: $btn.data('department'),
            lastDay: $btn.data('last-day'),
            status: $btn.data('status')
        };
        
        Swal.fire({
            title: __('resignation_details') || 'Resignation Details',
            html: generateResignationDetailsHTML(data),
            icon: 'info',
            width: '700px',
            confirmButtonText: __('close') || 'Close',
            customClass: {
                popup: 'resignation-details-popup'
            }
        ,allowOutsideClick:false});
    });
    
    // Approve Resignation - Start Multi-Step Wizard (backup for class-based triggers)
    $(document).on('click', '.approveResignation', function() {
        const data = $(this).data();
        window.resignationApprovalData = data;
        showStep1EmployeeInfo(data);
    });
    
    // Reject Resignation (backup for class-based triggers)
    $(document).on('click', '.rejectResignation', function() {
        const resignationId = $(this).data('id');
        const empName = $(this).data('name');
        rejectResignation(resignationId, empName);
    });
    
});

/**
 * Step 1: Display Employee and Resignation Information
 */
function showStep1EmployeeInfo(data) {
    Swal.fire({
        title: __('resignation_review') || 'Resignation Review',
        html: `
            <div class="resignation-approval-wizard">
                <div class="wizard-section">
                    <h4 class="section-title"><i class="fas fa-user"></i> ${__('employee_information') || 'Employee Information'}</h4>
                    <table class="info-table">
                        <tr>
                            <td class="label">${__('emp_id') || 'Employee ID'}:</td>
                            <td class="value">${data.empId}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('id_iqama') || 'ID/Iqama'}:</td>
                            <td class="value">${data.iqama}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('emp_name') || 'Employee Name'}:</td>
                            <td class="value">${data.name}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('designation') || 'Designation'}:</td>
                            <td class="value">${data.designation}</td>
                        </tr>
                        <tr>
                            <td class="label">${__('department') || 'Department'}:</td>
                            <td class="value">${data.department}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="wizard-section mt-4">
                    <h4 class="section-title"><i class="fas fa-file-signature"></i> ${__('resignation_information') || 'Resignation Information'}</h4>
                    <table class="info-table">
                        <tr>
                            <td class="label">${__('last_working_day') || 'Last Workday by Employee'}:</td>
                            <td class="value text-danger font-weight-bold">${formatDate(data.lastDay)}</td>
                        </tr>
                    </table>
                </div>
            </div>
        `,
        icon: 'question',
        width: '700px',
        showCancelButton: true,
        confirmButtonText: __('next') || 'NEXT',
        cancelButtonText: __('reject') || 'REJECT',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        customClass: {
            popup: 'resignation-wizard-popup',
            confirmButton: 'btn-lg',
            cancelButton: 'btn-lg'
        }
    ,allowOutsideClick:false}).then((result) => {
        if (result.isConfirmed) {
            // Move to Step 2: Replacement Information
            showStep2ReplacementInfo(data);
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Reject button clicked
            promptRejection(data.id, data.name);
        }
    });
}

/**
 * Step 2: Replacement Information Question
 */
function showStep2ReplacementInfo(data) {
    Swal.fire({
        title: __('replacement_information') || 'Replacement Information',
        html: `
            <div class="resignation-approval-wizard">
                <div class="form-group text-left">
                    <label class="font-weight-bold mb-3 d-block" style="font-size: 16px;">
                        ${__('need_replacement_employee') || 'Do you need a replacement employee?'}
                    </label>
                    <div class="custom-control custom-radio mb-2">
                        <input type="radio" id="replacement_no" name="need_replacement" class="custom-control-input" value="no" checked>
                        <label class="custom-control-label" for="replacement_no">
                            <i class="fas fa-times-circle text-danger"></i> ${__('no') || 'NO'}
                        </label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="replacement_yes" name="need_replacement" class="custom-control-input" value="yes">
                        <label class="custom-control-label" for="replacement_yes">
                            <i class="fas fa-check-circle text-success"></i> ${__('yes') || 'YES'}
                        </label>
                    </div>
                </div>
            </div>
        `,
        icon: 'question',
        width: '600px',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: __('next') || 'NEXT',
        denyButtonText: __('back') || 'BACK',
        cancelButtonText: __('cancel') || 'Cancel',
        confirmButtonColor: '#28a745',
        denyButtonColor: '#6c757d',
        customClass: {
            popup: 'resignation-wizard-popup'
        }
    ,allowOutsideClick:false,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
        if (result.isConfirmed) {
            const needsReplacement = $('input[name="need_replacement"]:checked').val() === 'yes';
            
            if (needsReplacement) {
                // Show replacement details form
                showStep3ReplacementDetails(data);
            } else {
                // No replacement needed, go to final approval
                showFinalApprovalConfirmation(data, null);
            }
        } else if (result.isDenied) {
            // Back button - return to Step 1
            showStep1EmployeeInfo(data);
        }
    });
}

/**
 * Step 3: Replacement Details Form
 */
function showStep3ReplacementDetails(data) {
    Swal.fire({
        title: __('replacement_details') || 'Replacement Details',
        html: `
            <div class="resignation-approval-wizard">
                <p class="text-left mb-4">${__('fill_replacement_fields') || 'Please fill in the required fields below:'}</p>
                
                <div class="form-group text-left mb-3">
                    <label for="job_title" class="font-weight-bold">${__('job_title') || '1. Job Title of the Replacement'} <span class="text-danger">*</span></label>
                    <input type="text" id="job_title" class="form-control" placeholder="${__('enter_job_title') || 'Enter job title'}" required>
                </div>
                
                <div class="form-group text-left mb-3">
                    <label for="job_description" class="font-weight-bold">${__('job_description') || '2. Job Description'} <span class="text-danger">*</span></label>
                    <textarea id="job_description" class="form-control" rows="3" placeholder="${__('enter_job_description') || 'Enter job description'}" required></textarea>
                </div>
                
                <div class="form-group text-left mb-3">
                    <label for="experience" class="font-weight-bold">${__('experience') || '3. Experience'} <span class="text-danger">*</span></label>
                    <input type="text" id="experience" class="form-control" placeholder="${__('enter_experience_required') || 'e.g., 3-5 years'}" required>
                </div>
                
                <div class="form-group text-left mb-3">
                    <label for="certificate" class="font-weight-bold">${__('certificate') || '4. Certificate'} <span class="text-danger">*</span></label>
                    <input type="text" id="certificate" class="form-control" placeholder="${__('enter_required_certificates') || 'Enter required certificates'}" required>
                </div>
                
                <div class="form-group text-left mb-3">
                    <label for="academic_achievement" class="font-weight-bold">${__('academic_achievement') || '5. Academic Achievement'} <span class="text-danger">*</span></label>
                    <input type="text" id="academic_achievement" class="form-control" placeholder="${__('enter_academic_requirements') || 'e.g., Bachelor degree in...'}" required>
                </div>
                
                <div class="form-group text-left mb-3">
                    <label for="date_of_joining" class="font-weight-bold">${__('date_of_joining') || '6. Date of Joining'} <span class="text-danger">*</span></label>
                    <input type="text" id="date_of_joining" class="form-control datepicker" placeholder="YYYY-MM-DD" required autocomplete="off">
                </div>
            </div>
        `,
        icon: 'info',
        width: '700px',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: __('approve') || 'APPROVE',
        denyButtonText: __('back') || 'BACK',
        cancelButtonText: __('cancel') || 'Cancel',
        confirmButtonColor: '#28a745',
        denyButtonColor: '#6c757d',
        didOpen: () => {
            // Initialize datepicker
            $('#date_of_joining').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true,
                startDate: new Date()
            ,cancelButtonColor:'#d33',cancelButtonText:__('cancel')});
        },
        preConfirm: () => {
            // Validate all fields
            const jobTitle = $('#job_title').val().trim();
            const jobDescription = $('#job_description').val().trim();
            const experience = $('#experience').val().trim();
            const certificate = $('#certificate').val().trim();
            const academicAchievement = $('#academic_achievement').val().trim();
            const dateOfJoining = $('#date_of_joining').val().trim();
            
            if (!jobTitle || !jobDescription || !experience || !certificate || !academicAchievement || !dateOfJoining) {
                Swal.showValidationMessage(__('fill_all_required_fields') || 'Please fill in all required fields');
                return false;
            }
            
            return {
                job_title: jobTitle,
                job_description: jobDescription,
                experience: experience,
                certificate: certificate,
                academic_achievement: academicAchievement,
                date_of_joining: dateOfJoining
            };
        }
    ,allowOutsideClick:false,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
        if (result.isConfirmed) {
            // Proceed with approval including replacement data
            submitApproval(data.id, result.value);
        } else if (result.isDenied) {
            // Back to Step 2
            showStep2ReplacementInfo(data);
        }
    });
}

/**
 * Final Approval Confirmation (No Replacement)
 */
function showFinalApprovalConfirmation(data, replacementData) {
    Swal.fire({
        title: __('confirm_approval') || 'Confirm Approval',
        html: `
            <p class="mb-3">${__('approve_resignation_for') || 'Are you sure you want to approve the resignation for'} <strong>${data.name}</strong>?</p>
            <p class="text-muted">${__('replacement_required') || 'Replacement Required'}: <strong class="text-danger">${__('no') || 'NO'}</strong></p>
        `,
        icon: 'warning',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: __('approve') || 'APPROVE',
        denyButtonText: __('back') || 'BACK',
        cancelButtonText: __('cancel') || 'Cancel',
        confirmButtonColor: '#28a745',
        denyButtonColor: '#6c757d'
    ,allowOutsideClick:false,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
        if (result.isConfirmed) {
            submitApproval(data.id, null);
        } else if (result.isDenied) {
            showStep2ReplacementInfo(data);
        }
    });
}

/**
 * Submit Approval to Backend
 */
function submitApproval(resignationId, replacementData) {
    // Ask for optional approval comment before submitting
    Swal.fire({
        title: __('add_approval_comment') || 'Add Approval Comment (Optional)',
        html: `
            <div style="text-align: left;">
                <p style="color: #555; margin-bottom: 15px;">${__('you_can_add_an_optional_comment_about_your_approval_decision') || 'Add Approval Comment (Optional)'}:</p>
                <textarea id="approval_comment" class="form-control" rows="4" 
                          placeholder="${__('enter_approval_comment') || 'Enter your approval comment here...'}" 
                          style="font-size: 14px; resize: vertical; min-height: 100px;"></textarea>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: __('approve_submit') || 'Approve & Submit',
        cancelButtonText: __('cancel') || 'Cancel',
        customClass: {
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-secondary'
        },
        allowOutsideClick: false,
        preConfirm: () => {
            return {
                comment: document.getElementById('approval_comment').value.trim()
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const approvalComment = result.value.comment;
            
            Swal.fire({
                title: __('processing') || 'Processing...',
                html: __('please_wait') || 'Please wait while we process your approval',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            const formData = new FormData();
            formData.append('ajaxType', 'approve_resignation');
            formData.append('resignation_id', resignationId);
            
            if (approvalComment) {
                formData.append('approval_comment', approvalComment);
            }
            
            if (replacementData) {
                formData.append('needs_replacement', '1');
                formData.append('replacement_data', JSON.stringify(replacementData));
            } else {
                formData.append('needs_replacement', '0');
            }
            
            $.ajax({
                url: './includes/ajaxFile/ajaxResignation.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    Swal.fire({
                        title: response.title || __('success') || 'Success',
                        text: response.message || __('resignation_approved') || 'Resignation has been approved successfully',
                        icon: response.type || 'success',
                        confirmButtonText: __('ok') || 'OK'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMessage = __('failed_to_approve') || 'Failed to approve resignation';
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                    }
                    
                    Swal.fire({
                        title: __('error') || 'Error',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: __('ok') || 'OK'
                    });
                }
            });
        }
    });
}

/**
 * Submit Rejection to Backend
 */
function submitRejection(resignationId, reason) {
    Swal.fire({
        title: __('processing') || 'Processing...',
        html: __('please_wait') || 'Please wait while we process your rejection',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const formData = new FormData();
    formData.append('ajaxType', 'reject_resignation');
    formData.append('resignation_id', resignationId);
    formData.append('rejection_reason', reason);
    
    $.ajax({
        url: './includes/ajaxFile/ajaxResignation.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            Swal.fire({
                title: response.title || __('rejected') || 'Rejected',
                text: response.message || __('resignation_rejected') || 'Resignation has been rejected',
                icon: response.type || 'success',
                confirmButtonText: __('ok') || 'OK'
            }).then(() => {
                location.reload();
                allowOutsideClick:false});
        },
        error: function(jqXHR, textStatus, errorThrown) {
            let errorMessage = __('failed_to_reject') || 'Failed to reject resignation';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMessage = jqXHR.responseJSON.message;
            }
            
            Swal.fire({
                title: __('error') || 'Error',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: __('ok') || 'OK'
            ,allowOutsideClick:false});
        }
    });
}

/**
 * Prompt for Rejection Reason
 */
function promptRejection(resignationId, empName) {
    Swal.fire({
        title: __('reject_resignation') || 'Reject Resignation',
        html: `
            <p class="mb-3">${__('confirm_reject_resignation_for') || 'Provide a reason for rejecting the resignation request for'} <strong>${empName}</strong>:</p>
            <div class="form-group text-left">
                <label for="rejection_reason" class="font-weight-bold">${__('rejection_reason') || 'Rejection Reason'} <span class="text-danger">*</span></label>
                <textarea id="rejection_reason" class="form-control" rows="4" placeholder="${__('enter_rejection_reason') || 'Please provide a reason for rejection...'}" required></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: __('submit_rejection') || 'Submit Rejection',
        cancelButtonText: __('cancel') || 'Cancel',
        confirmButtonColor: '#d33',
        allowOutsideClick: false,
        preConfirm: () => {
            const reason = $('#rejection_reason').val().trim();
            if (!reason) {
                Swal.showValidationMessage(__('please_provide_rejection_reason') || 'Please provide a reason for rejection');
                return false;
            }
            return { reason };
        }
    ,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
        if (result.isConfirmed) {
            submitRejection(resignationId, result.value.reason);
        }
    });
}

/**
 * Generate HTML for resignation details view
 */
function generateResignationDetailsHTML(data) {
    return `
        <div class="resignation-details text-left">
            <div class="details-section mb-3">
                <h5 class="text-primary"><i class="fas fa-user"></i> ${__('employee_information') || 'Employee Information'}</h5>
                <table class="table table-bordered">
                    <tr><td><strong>${__('emp_id') || 'Employee ID'}:</strong></td><td>${data.empId}</td></tr>
                    <tr><td><strong>${__('id_iqama') || 'ID/Iqama'}:</strong></td><td>${data.iqama}</td></tr>
                    <tr><td><strong>${__('name') || 'Name'}:</strong></td><td>${data.name}</td></tr>
                    <tr><td><strong>${__('designation') || 'Designation'}:</strong></td><td>${data.designation}</td></tr>
                    <tr><td><strong>${__('department') || 'Department'}:</strong></td><td>${data.department}</td></tr>
                </table>
            </div>
            <div class="details-section">
                <h5 class="text-danger"><i class="fas fa-calendar-times"></i> ${__('resignation_information') || 'Resignation Information'}</h5>
                <table class="table table-bordered">
                    <tr><td><strong>${__('last_working_day') || 'Last Working Day'}:</strong></td><td class="text-danger font-weight-bold">${formatDate(data.lastDay)}</td></tr>
                    <tr><td><strong>${__('status') || 'Status'}:</strong></td><td><span class="badge badge-${getStatusBadgeClass(data.status)}">${data.status.toUpperCase()}</span></td></tr>
                </table>
            </div>
        </div>
    `;
}

/**
 * Utility: Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
}

/**
 * Utility: Get status badge class
 */
function getStatusBadgeClass(status) {
    const statusMap = {
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'danger',
        'cancelled': 'secondary'
    };
    return statusMap[status] || 'info';
}
