/**
 * Resignation Approval Wizard - Multi-Step Form for HR Operations
 * Handles resignation approval process with employee info, exit interview, and replacement details
 */

// Global storage for wizard data
window.approvalWizardData = {};

/**
 * Open Resignation Approval Wizard - Step 1: Employee & Resignation Information
 */
function openResignationApprovalWizard(resignationId, empId, empName, iqama, designation, department, lastWorkingDay) {
    // Store data globally
    window.approvalWizardData = {
        resignationId: resignationId,
        empId: empId,
        empName: empName,
        iqama: iqama,
        designation: designation,
        department: department,
        originalLastWorkingDay: lastWorkingDay,
        currentStep: 1
    };
    
    Swal.fire({
        title: '📋 Employee & Resignation Information',
        html: approvalStep1_HTML(empId, empName, iqama, designation, department, lastWorkingDay),
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Next →',
        cancelButtonText: 'Cancel',
        customClass: {
            container: 'resignation-approval-wizard',
            popup: 'resignation-wizard-popup'
        },
        width: '700px',
        allowOutsideClick: false,
        preConfirm: () => {
            const hrLastWorkingDay = $('#hr_last_working_day').val();
            
            if (!hrLastWorkingDay) {
                Swal.showValidationMessage('Please select the last working day');
                return false;
            }
            
            // Validate date is in future
            const selectedDate = new Date(hrLastWorkingDay);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate <= today) {
                Swal.showValidationMessage('Last working day must be in the future');
                return false;
            }
            
            // Store HR's selected last working day
            window.approvalWizardData.hrLastWorkingDay = hrLastWorkingDay;
            return true;
        }
    ,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
        if (result.isConfirmed) {
            // Move to Step 2: Exit Interview
            showExitInterviewStep();
        }
    });
    
    // Initialize date picker
    setTimeout(() => {
        $('#hr_last_working_day').datepicker({
            format: "yyyy-mm-dd",
            todayHighlight: true,
            autoclose: true,
            startDate: new Date()
        });
    }, 300);
}


/**
 * Step 2: Show Exit Interview Questions & Answers
 */
function showExitInterviewStep() {
    const resignationId = window.approvalWizardData.resignationId;
    
    // Show loading while fetching exit interview data
    Swal.fire({
        title: 'Loading Exit Interview...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Fetch exit interview data via AJAX
    $.ajax({
        url: './includes/ajaxFile/ajaxResignation.php',
        type: 'POST',
        data: {
            ajaxType: 'get_exit_interview',
            resignation_id: resignationId
        },
        dataType: 'json',
        success: function(response) {
            if (response.type === 'success' && response.data) {
                Swal.fire({
                    title: '📝 Exit Interview Questions & Answers',
                    html: approvalStep2_HTML(response.data),
                    icon: 'info',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: 'Next →',
                    denyButtonText: '← Back',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        container: 'resignation-approval-wizard',
                        popup: 'resignation-wizard-popup'
                    },
                    width: '800px',
                    allowOutsideClick: false
                ,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
                    if (result.isConfirmed) {
                        // Move to Step 3: Replacement
                        showReplacementStep();
                    } else if (result.isDenied) {
                        // Go back to Step 1
                        openResignationApprovalWizard(
                            window.approvalWizardData.resignationId,
                            window.approvalWizardData.empId,
                            window.approvalWizardData.empName,
                            window.approvalWizardData.iqama,
                            window.approvalWizardData.designation,
                            window.approvalWizardData.department,
                            window.approvalWizardData.originalLastWorkingDay
                        );
                    }
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message || 'Failed to load exit interview data',
                    icon: 'error'
                ,allowOutsideClick:false});
            }
        },
        error: function() {
            Swal.fire({
                title: 'Error',
                text: 'Failed to fetch exit interview data',
                icon: 'error'
            ,allowOutsideClick:false});
        }
    });
}


/**
 * Step 3: Replacement Information or Summary (based on approval level)
 */
function showReplacementStep() {
    // Fetch approval chain to determine current level
    $.ajax({
        url: './includes/ajaxFile/ajaxResignation.php',
        type: 'POST',
        data: {
            ajaxType: 'get_approval_level',
            resignation_id: window.approvalWizardData.resignationId
        },
        dataType: 'json',
        success: function(response) {
            const isHRPayroll = response.data && response.data.current_level === 3;
            
            Swal.fire({
                title: isHRPayroll ? '✓ Resignation Approval Summary' : '👥 Replacement Information',
                html: isHRPayroll ? approvalStep3_Summary_HTML() : approvalStep3_HTML(),
                icon: 'question',
                showCancelButton: true,
                showDenyButton: !isHRPayroll,
                confirmButtonText: isHRPayroll ? '✓ Final Approve' : '✓ Approve',
                denyButtonText: !isHRPayroll ? '← Back' : undefined,
                cancelButtonText: '✗ Reject',
                customClass: {
                    container: 'resignation-approval-wizard',
                    popup: 'resignation-wizard-popup',
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger',
                    denyButton: 'btn btn-secondary'
                },
                width: '750px',
                allowOutsideClick: false,
                preConfirm: () => {
                    if (isHRPayroll) {
                        // HR Payroll just approves - no replacement data needed
                        window.approvalWizardData.replacementData = {
                            needs_replacement: false,
                            job_title: '',
                            job_description: '',
                            experience: '',
                            certificate: '',
                            academic_achievement: '',
                            joining_date: ''
                        };
                        return true;
                    }
                    
                    // HR Operations - validate replacement
                    const needsReplacement = $('input[name="needs_replacement"]:checked').val();
                    
                    if (!needsReplacement) {
                        Swal.showValidationMessage('Please select whether replacement is needed');
                        return false;
                    }
                    
                    // If replacement is needed, validate required fields
                    if (needsReplacement === 'yes') {
                        const requiredFields = {
                            'replacement_job_title': 'Job Title',
                            'replacement_job_description': 'Job Description',
                            'replacement_experience': 'Experience',
                            'replacement_certificate': 'Certificate',
                            'replacement_academic': 'Academic Achievement',
                            'replacement_join_date': 'Date of Joining'
                        };
                        
                        let missingFields = [];
                        for (let [fieldId, fieldName] of Object.entries(requiredFields)) {
                            const value = $(`#${fieldId}`).val();
                            if (!value || value.trim() === '') {
                                missingFields.push(fieldName);
                            }
                        }
                        
                        if (missingFields.length > 0) {
                            Swal.showValidationMessage(`Please fill in: ${missingFields.join(', ')}`);
                            return false;
                        }
                    }
                    
                    // Collect replacement data
                    const replacementData = {
                        needs_replacement: needsReplacement === 'yes',
                        job_title: $('#replacement_job_title').val() || '',
                        job_description: $('#replacement_job_description').val() || '',
                        experience: $('#replacement_experience').val() || '',
                        certificate: $('#replacement_certificate').val() || '',
                        academic_achievement: $('#replacement_academic').val() || '',
                        joining_date: $('#replacement_join_date').val() || ''
                    };
                    
                    window.approvalWizardData.replacementData = replacementData;
                    return true;
                }
            ,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
                if (result.isConfirmed) {
                    // Approve resignation
                    submitApprovalDecision('approve');
                } else if (result.isDenied) {
                    // Go back to Step 2
                    showExitInterviewStep();
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // Reject - ask for reason
                    askRejectionReason();
                }
            });
            
            if (!isHRPayroll) {
                // Initialize replacement fields toggle for HR Operations
                setTimeout(() => {
                    initializeReplacementToggle();
                    
                    // Initialize date picker for joining date
                    $('#replacement_join_date').datepicker({
                        format: "yyyy-mm-dd",
                        todayHighlight: true,
                        autoclose: true,
                        startDate: new Date()
                    });
                }, 300);
            }
        },
        error: function() {
            Swal.fire({
                title: 'Error',
                text: 'Failed to fetch approval level',
                icon: 'error'
            ,allowOutsideClick:false});
        }
    });
}


/**
 * Ask for rejection reason
 */
function askRejectionReason() {
    Swal.fire({
        title: '✗ Reject Resignation',
        html: `
            <div style="text-align: left;">
                <p style="color: #555; margin-bottom: 15px;">Please provide a reason for rejecting this resignation:</p>
                <textarea id="rejection_reason" class="form-control" rows="4" 
                    placeholder="Enter rejection reason..." 
                    style="border: 1px solid #dc3545; font-size: 14px;"></textarea>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Submit Rejection',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        preConfirm: () => {
            const reason = $('#rejection_reason').val();
            if (!reason || reason.trim() === '') {
                Swal.showValidationMessage('Please enter a rejection reason');
                return false;
            }
            window.approvalWizardData.rejectionReason = reason;
            return true;
        }
    ,allowOutsideClick:false,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
        if (result.isConfirmed) {
            submitApprovalDecision('reject');
        } else {
            // Go back to replacement step
            showReplacementStep();
        }
    });
}


/**
 * Submit approval or rejection decision
 */
function submitApprovalDecision(action) {
    const formData = new FormData();
    formData.append('ajaxType', action === 'approve' ? 'approve_resignation' : 'reject_resignation');
    formData.append('resignation_id', window.approvalWizardData.resignationId);
    
    if (action === 'approve') {
        formData.append('hr_last_working_day', window.approvalWizardData.hrLastWorkingDay);
        formData.append('needs_replacement', window.approvalWizardData.replacementData.needs_replacement ? '1' : '0');
        
        if (window.approvalWizardData.replacementData.needs_replacement) {
            formData.append('replacement_data', JSON.stringify(window.approvalWizardData.replacementData));
        }
    } else {
        formData.append('rejection_reason', window.approvalWizardData.rejectionReason);
    }
    
    // Show loading
    Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: './includes/ajaxFile/ajaxResignation.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            Swal.fire({
                title: response.title || 'Success',
                text: response.message,
                icon: response.type || 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                location.reload();
                allowOutsideClick:false});
        },
        error: function(jqXHR) {
            let errorMessage = 'Failed to process request';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMessage = jqXHR.responseJSON.message;
            }
            
            Swal.fire({
                title: 'Error',
                text: errorMessage,
                icon: 'error'
            ,allowOutsideClick:false});
        }
    });
}


/**
 * Initialize replacement fields toggle
 */
function initializeReplacementToggle() {
    $('input[name="needs_replacement"]').on('change', function() {
        const needsReplacement = $(this).val() === 'yes';
        $('#replacement_fields').toggle(needsReplacement);
        
        if (!needsReplacement) {
            // Clear fields when "No" is selected
            $('#replacement_fields input, #replacement_fields textarea').val('');
        }
    });
}


// ===== HTML TEMPLATES =====

/**
 * Step 1 HTML: Employee & Resignation Information
 */
function approvalStep1_HTML(empId, empName, iqama, designation, department, originalLastWorkingDay) {
    return `
        <div class="resignation-approval-wizard">
            <div class="wizard-section">
                <h5 class="section-title"><i class="fas fa-user"></i> Employee Information</h5>
                <table class="info-table">
                    <tr>
                        <td class="label">Employee ID:</td>
                        <td class="value">${empId || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td class="label">ID/Iqama:</td>
                        <td class="value">${iqama || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td class="label">Employee Name:</td>
                        <td class="value">${empName || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td class="label">Designation:</td>
                        <td class="value">${designation || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td class="label">Department:</td>
                        <td class="value">${department || 'N/A'}</td>
                    </tr>
                </table>
            </div>
            
            <div class="wizard-section">
                <h5 class="section-title"><i class="fas fa-calendar-times"></i> Resignation Information</h5>
                <div class="form-group">
                    <label for="hr_last_working_day" style="font-weight: 600; color: #495057; display: block; margin-bottom: 8px;">
                        Last Working Day (Selected by HR Operations): <span style="color: #dc3545;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="hr_last_working_day" 
                        class="form-control datepicker" 
                        placeholder="YYYY-MM-DD"
                        value="${originalLastWorkingDay || ''}"
                        style="font-size: 14px; padding: 10px;"
                        required
                    />
                    <small class="form-text text-muted" style="margin-top: 5px;">
                        Employee requested: ${originalLastWorkingDay || 'N/A'}
                    </small>
                </div>
            </div>
        </div>
    `;
}


/**
 * Step 2 HTML: Exit Interview Q&A
 */
function approvalStep2_HTML(exitInterviews) {
    const questionLabels = [
        'What are the main reasons behind your decision to leave the company?',
        'Did you feel supported and appreciated by management and colleagues?',
        'Were you provided with sufficient tools and resources to perform your job effectively?',
        'How would you evaluate your direct manager\'s leadership style?',
        'Were the available growth and development opportunities suitable for you?',
        'How do you evaluate the compensation and benefits you received?',
        'What do you wish had been different during your time here?',
        'Would you recommend the company as a workplace to others? Why or why not?',
        'Is there anything else you would like to share before you leave?'
    ];
    
    let html = '<div class="resignation-approval-wizard" style="max-height: 500px; overflow-y: auto; padding-right: 10px;">';
    
    if (exitInterviews && exitInterviews.length > 0) {
        exitInterviews.forEach((interview, index) => {
            html += `
                <div class="wizard-section" style="margin-bottom: 20px;">
                    <h6 style="color: #007bff; font-weight: 600; margin-bottom: 10px;">
                        <span style="background: #007bff; color: white; padding: 4px 10px; border-radius: 50%; margin-right: 8px; font-size: 12px;">
                            ${index + 1}
                        </span>
                        ${interview.question || questionLabels[index] || 'Question'}
                    </h6>
                    <div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #28a745; border-radius: 4px;">
                        <p style="margin: 0; color: #212529; white-space: pre-wrap; line-height: 1.6;">
                            ${interview.answer || 'No answer provided'}
                        </p>
                    </div>
                </div>
            `;
        });
    } else {
        html += `
            <div class="alert alert-warning" style="margin: 20px 0;">
                <i class="fas fa-exclamation-triangle"></i> No exit interview data available
            </div>
        `;
    }
    
    html += '</div>';
    return html;
}


/**
 * Step 3 HTML: Replacement Information
 */
function approvalStep3_HTML() {
    return `
        <div class="resignation-approval-wizard">
            <div class="wizard-section">
                <h5 class="section-title"><i class="fas fa-user-plus"></i> Replacement Employee</h5>
                
                <div class="form-group mb-3">
                    <label style="font-weight: 600; color: #495057; display: block; margin-bottom: 10px;">
                        Do you need a replacement employee? <span style="color: #dc3545;">*</span>
                    </label>
                    
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="replacement_no" name="needs_replacement" value="no" class="custom-control-input">
                        <label class="custom-control-label" for="replacement_no">
                            <i class="fas fa-times-circle text-danger"></i> No
                        </label>
                    </div>
                    
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" id="replacement_yes" name="needs_replacement" value="yes" class="custom-control-input">
                        <label class="custom-control-label" for="replacement_yes">
                            <i class="fas fa-check-circle text-success"></i> Yes
                        </label>
                    </div>
                </div>
                
                <div id="replacement_fields" style="display: none; margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <p style="color: #007bff; font-weight: 600; margin-bottom: 15px;">
                        <i class="fas fa-info-circle"></i> Please fill in the required fields below:
                    </p>
                    
                    <div class="form-group mb-3">
                        <label for="replacement_job_title" style="font-weight: 500;">1. Job Title of the Replacement: <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="replacement_job_title" class="form-control" placeholder="Enter job title" style="font-size: 14px;" />
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="replacement_job_description" style="font-weight: 500;">2. Job Description: <span style="color: #dc3545;">*</span></label>
                        <textarea id="replacement_job_description" class="form-control" rows="3" placeholder="Enter job description" style="font-size: 14px;"></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="replacement_experience" style="font-weight: 500;">3. Experience: <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="replacement_experience" class="form-control" placeholder="e.g., 3-5 years" style="font-size: 14px;" />
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="replacement_certificate" style="font-weight: 500;">4. Certificate: <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="replacement_certificate" class="form-control" placeholder="Enter required certificates" style="font-size: 14px;" />
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="replacement_academic" style="font-weight: 500;">5. Academic Achievement: <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="replacement_academic" class="form-control" placeholder="e.g., Bachelor's Degree" style="font-size: 14px;" />
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="replacement_join_date" style="font-weight: 500;">6. Date of Joining: <span style="color: #dc3545;">*</span></label>
                        <input type="text" id="replacement_join_date" class="form-control datepicker" placeholder="YYYY-MM-DD" style="font-size: 14px;" />
                    </div>
                </div>
            </div>
        </div>
    `;
}


/**
 * Step 3 HTML: Approval Summary (Read-Only for HR Payroll)
 */
function approvalStep3_Summary_HTML() {
    return `
        <div class="resignation-approval-wizard">
            <div class="wizard-section">
                <h5 class="section-title"><i class="fas fa-check-circle text-success"></i> Approval Summary - Ready for Final Approval</h5>
                <p style="color: #666; margin-bottom: 20px; font-style: italic;">
                    All required information has been reviewed and is ready for final approval by HR Payroll.
                </p>
            </div>
            
            <div class="wizard-section">
                <h6 style="color: #007bff; font-weight: 600; margin-bottom: 15px;">
                    <i class="fas fa-user"></i> Employee Information
                </h6>
                <table class="info-table" style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 10px 5px; font-weight: 600; color: #495057; width: 40%;">Employee ID:</td>
                        <td style="padding: 10px 5px; color: #212529; width: 60%;">${window.approvalWizardData.empId || 'N/A'}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 10px 5px; font-weight: 600; color: #495057; width: 40%;">Employee Name:</td>
                        <td style="padding: 10px 5px; color: #212529; width: 60%;">${window.approvalWizardData.empName || 'N/A'}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 10px 5px; font-weight: 600; color: #495057; width: 40%;">Department:</td>
                        <td style="padding: 10px 5px; color: #212529; width: 60%;">${window.approvalWizardData.department || 'N/A'}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 10px 5px; font-weight: 600; color: #495057; width: 40%;">Designation:</td>
                        <td style="padding: 10px 5px; color: #212529; width: 60%;">${window.approvalWizardData.designation || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 5px; font-weight: 600; color: #495057; width: 40%;">ID/Iqama:</td>
                        <td style="padding: 10px 5px; color: #212529; width: 60%;">${window.approvalWizardData.iqama || 'N/A'}</td>
                    </tr>
                </table>
            </div>
            
            <div class="wizard-section">
                <h6 style="color: #007bff; font-weight: 600; margin-bottom: 15px;">
                    <i class="fas fa-calendar-times"></i> Resignation Details
                </h6>
                <table class="info-table" style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 10px 5px; font-weight: 600; color: #495057; width: 40%;">Last Working Day:</td>
                        <td style="padding: 10px 5px; color: #212529; width: 60%;">${window.approvalWizardData.hrLastWorkingDay || window.approvalWizardData.originalLastWorkingDay || 'N/A'}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 5px; font-weight: 600; color: #495057; width: 40%;">Status:</td>
                        <td style="padding: 10px 5px; color: #212529; width: 60%;"><span style="background: #ffc107; color: white; padding: 4px 8px; border-radius: 4px; font-weight: 600;">Awaiting Final Approval</span></td>
                    </tr>
                </table>
            </div>
            
            <div class="alert alert-info" style="margin-top: 20px; padding: 15px; border-radius: 5px; background-color: #e3f2fd; border-left: 4px solid #2196f3; color: #1565c0;">
                <i class="fas fa-info-circle"></i> <strong>Final Step:</strong> Click "Final Approve" to complete the resignation approval process. Notifications will be sent to all HR team members upon completion.
            </div>
        </div>
    `;
}
