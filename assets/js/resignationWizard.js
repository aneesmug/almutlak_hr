/**
 * Resignation Wizard - SweetAlert2 Multi-Step Form
 * Handles employee resignation application and exit interview
 */

// ===== RESIGNATION WIZARD HANDLER =====
$(document).on('click', '.applyResignation', function(e) {
    e.preventDefault();
    const empId = $(this).data('emp_id');
    const empName = $(this).data('emp_name');
    openResignationWizard(empId, empName);
});


/**
 * Step 1: Resignation Information - Select Last Working Day
 */
function openResignationWizard(empId, empName) {
    Swal.fire({
        title: __('resignation_title') || 'Employee Resignation',
        html: resignationStep1_HTML(empName),
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: __('next') || 'Next',
        cancelButtonText: __('cancel') || 'Cancel',
        customClass: {
            container: 'resignation-wizard',
            popup: 'resignation-popup'
        },
        width: '600px',
        allowOutsideClick: false,
        preConfirm: () => {
            const lastWorkingDay = $('#last_working_day').val();
            
            // Validation
            if (!lastWorkingDay) {
                Swal.showValidationMessage(__('select_last_working_day') || 'Please select your last working day');
                return false;
            }
            
            // Check if date is in the future
            const selectedDate = new Date(lastWorkingDay);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate <= today) {
                Swal.showValidationMessage(__('last_working_day_must_be_future') || 'Last working day must be in the future');
                return false;
            }
            
            // Store data for next step
            window.resignationData = {
                empId: empId,
                empName: empName,
                lastWorkingDay: lastWorkingDay
            };
            
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Move to Step 2
            openExitInterviewWizard();
        }
    });
    
    // Initialize date picker for Step 1
    setTimeout(() => {
        $('#last_working_day').datepicker({
            format: "yyyy-mm-dd",
            todayHighlight: true,
            autoclose: true,
            startDate: new Date()
        });
    }, 300);
}


/**
 * Step 2: Exit Interview Questions
 */
function openExitInterviewWizard() {
    Swal.fire({
        title: __('exit_interview_questions'),
        html: exitInterviewStep2_HTML(),
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: __('submit') || 'Submit Resignation',
        cancelButtonText: __('back') || 'Back',
        customClass: {
            container: 'exit-interview-wizard',
            popup: 'exit-interview-popup'
        },
        width: '700px',
        allowOutsideClick: false,
        preConfirm: () => {
            // Validate all required fields
            const requiredFields = [
                'q1_reasons',
                'q2_support',
                'q3_resources',
                'q4_manager',
                'q5_growth',
                'q6_compensation',
                'q7_different',
                'q8_recommend',
                'q9_additional'
            ];
            
            let allValid = true;
            requiredFields.forEach(field => {
                const $field = $(`#${field}`);
                if (!$field.val() || $field.val().trim() === '') {
                    $field.addClass('is-invalid');
                    allValid = false;
                } else {
                    $field.removeClass('is-invalid');
                }
            });
            
            if (!allValid) {
                Swal.showValidationMessage(__('fill_all_fields') || 'Please fill in all fields');
                return false;
            }
            
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit the resignation data
            submitResignation();
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Go back to Step 1
            openResignationWizard(window.resignationData.empId, window.resignationData.empName);
        }
    });
    
    // Initialize character counters for textareas
    setTimeout(() => {
        initializeCharacterCounters();
    }, 300);
}


/**
 * Submit resignation with all collected data
 */
function submitResignation() {
    const formData = new FormData();
    formData.append('ajaxType', 'apply_resignation');
    formData.append('emp_id', window.resignationData.empId);
    formData.append('last_working_day', window.resignationData.lastWorkingDay);
    
    // Add exit interview answers
    const exitInterviewAnswers = {
        q1_reasons: $('#q1_reasons').val(),
        q2_support: $('#q2_support').val(),
        q3_resources: $('#q3_resources').val(),
        q4_manager: $('#q4_manager').val(),
        q5_growth: $('#q5_growth').val(),
        q6_compensation: $('#q6_compensation').val(),
        q7_different: $('#q7_different').val(),
        q8_recommend: $('#q8_recommend').val(),
        q9_additional: $('#q9_additional').val()
    };
    
    formData.append('exit_interview', JSON.stringify(exitInterviewAnswers));
    
    // Show loading
    Swal.fire({
        title: __('processing') || 'Processing...',
        icon: 'info',
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
                title: response.title || __('success') || 'Success',
                text: response.message || __('resignation_submitted') || 'Your resignation has been submitted',
                icon: response.type || 'success',
                allowOutsideClick: false,
                confirmButtonText: __('ok') || 'OK'
            }).then(() => {
                location.reload();
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            let errorMessage = __('failed_to_submit_resignation') || 'Failed to submit resignation';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMessage = jqXHR.responseJSON.message;
            }
            
            Swal.fire({
                title: __('error') || 'Error',
                text: errorMessage,
                icon: 'error',
                allowOutsideClick: false,
                confirmButtonText: __('ok') || 'OK'
            });
        }
    });
}


/**
 * Initialize character counters for textareas
 */
function initializeCharacterCounters() {
    const fields = ['q1_reasons', 'q7_different', 'q8_recommend', 'q9_additional'];
    fields.forEach(fieldId => {
        const $textarea = $(`#${fieldId}`);
        if ($textarea.length) {
            $textarea.on('input', function() {
                const count = $(this).val().length;
                const maxLength = 500;
                $(`#${fieldId}_count`).text(`${count}/${maxLength}`);
            });
        }
    });
}


/**
 * HTML for Step 1: Resignation Information
 */
function resignationStep1_HTML(empName) {
    return `
        <div class="resignation-step1" style="text-align: left; padding: 20px;">
            <h5 style="color: #2c3e50; margin-bottom: 20px; font-weight: 600;">
                ${__('resignation_notice_header') || 'Employee: ' + empName}
            </h5>
            
            <form id="resignationStep1Form">
                <div class="form-group mb-3">
                    <label for="last_working_day" class="form-label" style="font-weight: 500; color: #34495e;">
                        ${__('last_working_day') || 'Last Working Day'} <span style="color: red;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="last_working_day" 
                        name="last_working_day" 
                        class="form-control" 
                        placeholder="YYYY-MM-DD"
                        style="padding: 12px; border: 1px solid #bdc3c7; border-radius: 5px; font-size: 14px;"
                        required
                    />
                    <small class="form-text text-muted" style="margin-top: 5px;">
                        ${__('select_your_last_working_date') || 'Please select your last working date'}
                    </small>
                </div>
                
                <div class="alert alert-info" style="margin-top: 20px; padding: 15px; border-radius: 5px; background-color: #e3f2fd; border-left: 4px solid #2196f3; color: #1565c0;">
                    <strong>${__('info') || 'Info'}:</strong> 
                    ${__('resignation_info_message') || 'After submitting this form, you will be asked to complete an exit interview. Please answer the questions honestly.'}
                </div>
            </form>
        </div>
    `;
}


/**
 * HTML for Step 2: Exit Interview Questions
 */
function exitInterviewStep2_HTML() {
    const questions = [
        { id: 'q1_reasons', label: __('q1_reasons') || 'What are the main reasons behind your decision to leave the company?' },
        { id: 'q2_support', label: __('q2_support') || 'Did you feel supported and appreciated by management and colleagues?' },
        { id: 'q3_resources', label: __('q3_resources') || 'Were you provided with the needed tools and resources to perform your job effectively?' },
        { id: 'q4_manager', label: __('q4_manager') || 'How would you evaluate your direct manager\'s leadership style?' },
        { id: 'q5_growth', label: __('q5_growth') || 'Were the available growth and development opportunities suitable for you?' },
        { id: 'q6_compensation', label: __('q6_compensation') || 'How do you evaluate the compensation and benefits you received?' },
        { id: 'q7_different', label: __('q7_different') || 'What do you wish had been different during your stay in the company?' },
        { id: 'q8_recommend', label: __('q8_recommend') || 'Would you recommend the company to other candidates? (Why yes - why not)' },
        { id: 'q9_additional', label: __('q9_additional') || 'Do you need to add anything else you would like to share before you leave?' }
    ];
    
    let html = `
        <div class="exit-interview-step2" style="text-align: left; padding: 20px; max-height: 500px; overflow-y: auto;">
            <form id="exitInterviewForm">
    `;
    
    questions.forEach((question, index) => {
        const isLongAnswer = ['q1_reasons', 'q7_different', 'q8_recommend', 'q9_additional'].includes(question.id);
        
        html += `
            <div class="form-group mb-4">
                <label for="${question.id}" class="form-label" style="font-weight: 600; color: #2c3e50; margin-bottom: 10px;">
                    <span style="display: inline-block; background: #3498db; color: white; width: 28px; height: 28px; border-radius: 50%; text-align: center; line-height: 28px; margin-right: 8px; font-size: 12px; font-weight: bold;">
                        ${index + 1}
                    </span>
                    ${question.label}
                </label>
        `;
        
        if (isLongAnswer) {
            html += `
                <textarea 
                    id="${question.id}" 
                    name="${question.id}" 
                    class="form-control" 
                    rows="3" 
                    placeholder="${__('enter_your_answer') || 'Enter your answer here...'}"
                    style="padding: 12px; border: 1px solid #bdc3c7; border-radius: 5px; font-size: 14px; resize: vertical;"
                    maxlength="500"
                    required
                ></textarea>
                <small class="form-text text-muted" style="display: block; margin-top: 5px;">
                    <span id="${question.id}_count">0/500</span> ${__('characters') || 'characters'}
                </small>
            `;
        } else {
            html += `
                <textarea 
                    id="${question.id}" 
                    name="${question.id}" 
                    class="form-control" 
                    rows="2" 
                    placeholder="${__('enter_your_answer') || 'Enter your answer here...'}"
                    style="padding: 12px; border: 1px solid #bdc3c7; border-radius: 5px; font-size: 14px; resize: vertical;"
                    required
                ></textarea>
            `;
        }
        
        html += `</div>`;
    });
    
    html += `
            </form>
            
            <div class="alert alert-warning" style="margin-top: 20px; padding: 15px; border-radius: 5px; background-color: #fff3cd; border-left: 4px solid #ffc107; color: #856404;">
                <strong>${__('important') || 'Important'}:</strong> 
                ${__('exit_interview_importance') || 'Your honest feedback is valuable and will help us improve our workplace environment.'}
            </div>
        </div>
    `;
    
    return html;
}
