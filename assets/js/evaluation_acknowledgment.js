/**
 * Manager Evaluation Acknowledgment/Objection JavaScript Handler
 * Handles SweetAlert2 modals for acknowledging or objecting to evaluations
 */

/**
 * Show acknowledgment modal
 * @param {int} evalId - Evaluation ID
 * @param {string} employeeName - Employee name
 * @param {function} onSuccess - Callback function on success
 */
function showAcknowledgmentModal(evalId, employeeName, onSuccess) {
    Swal.fire({
        title: window.lang['manager_acknowledgment_title'] || 'Manager Evaluation Acknowledgment',
        html: `<p>${window.lang['acknowledge_prompt'] || 'You are about to acknowledge this evaluation. Do you want to proceed?'}</p>
               <p><strong>Employee:</strong> ${employeeName}</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.success,
        cancelButtonColor: APP_COLORS.secondary,
        confirmButtonText: window.lang['acknowledge_evaluation'] || 'Acknowledge',
        cancelButtonText: window.lang['cancel'] || 'Cancel',
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            submitManagerAcknowledgment(evalId, 'acknowledged', '', onSuccess);
        }
    });
}

/**
 * Show objection modal
 * @param {int} evalId - Evaluation ID
 * @param {string} employeeName - Employee name
 * @param {function} onSuccess - Callback function on success
 */
function showObjectionModal(evalId, employeeName, onSuccess) {
    Swal.fire({
        title: window.lang['manager_objection_title'] || 'Manager Evaluation Objection',
        html: `<p><strong>Employee:</strong> ${employeeName}</p>
               <textarea id="swal_objection_note" class="form-control" rows="5" 
                         placeholder="${window.lang['objection_prompt'] || 'Please provide your objection note/reason:'}"
                         style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; margin-top: 10px;"></textarea>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.danger,
        cancelButtonColor: APP_COLORS.secondary,
        confirmButtonText: window.lang['submit'] || 'Submit Objection',
        cancelButtonText: window.lang['cancel'] || 'Cancel',
        allowOutsideClick: false,
        didOpen: () => {
            // Focus on the textarea
            document.getElementById('swal_objection_note').focus();
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const objectionNote = document.getElementById('swal_objection_note').value.trim();
            
            // Validate that note is provided
            if (objectionNote.length === 0) {
                Swal.fire({
                    title: 'Objection Note Required',
                    text: window.lang['objection_required'] || 'Objection note is required when objecting to an evaluation',
                    icon: 'error'
                });
                return;
            }
            
            submitManagerAcknowledgment(evalId, 'objected', objectionNote, onSuccess);
        }
    });
}

/**
 * Submit manager acknowledgment/objection via AJAX
 * @param {int} evalId - Evaluation ID
 * @param {string} status - 'acknowledged' or 'objected'
 * @param {string} objectionNote - Objection note (if applicable)
 * @param {function} onSuccess - Callback function on success
 */
function submitManagerAcknowledgment(evalId, status, objectionNote, onSuccess) {
    // Show loading state
    Swal.fire({
        title: 'Processing...',
        html: 'Submitting your ' + status + '...',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Prepare AJAX data
    const ajaxData = {
        ajaxType: 'submit_acknowledgment',
        eval_id: evalId,
        acknowledgment_status: status,
        objection_note: objectionNote
    };
    
    // Send AJAX request
    $.ajax({
        url: './includes/ajaxFile/ajaxEvaluationAcknowledgment.php',
        type: 'POST',
        dataType: 'json',
        data: ajaxData,
        success: function(response) {
            if (response.status === 200) {
                // Success
                Swal.fire({
                    title: 'Success!',
                    text: response.message || (status === 'acknowledged' ? 'Evaluation acknowledged successfully' : 'Objection submitted successfully'),
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Call success callback if provided
                    if (typeof onSuccess === 'function') {
                        onSuccess(response);
                    } else {
                        // Default: reload page
                        location.reload();
                    }
                });
            } else {
                // Error
                Swal.fire({
                    title: 'Error',
                    text: response.message || 'An error occurred while processing your ' + status,
                    icon: 'error'
                });
            }
        },
        error: function() {
            Swal.fire({
                title: 'Error',
                text: 'Failed to submit your ' + status + '. Please try again.',
                icon: 'error'
            });
        }
    });
}

/**
 * Display acknowledgment status badge
 * @param {string} status - 'pending', 'acknowledged', or 'objected'
 * @returns {string} HTML badge
 */
function getAcknowledgmentBadge(status) {
    const badges = {
        'pending': '<span class="badge badge-warning"><i class="fa fa-hourglass-half"></i> ' + (window.lang['manager_acknowledgment_pending'] || 'Pending') + '</span>',
        'acknowledged': '<span class="badge badge-success"><i class="fa fa-check"></i> ' + (window.lang['manager_acknowledgment'] || 'Acknowledged') + '</span>',
        'objected': '<span class="badge badge-danger"><i class="fa fa-exclamation-circle"></i> ' + (window.lang['manager_objection'] || 'Objected') + '</span>'
    };
    
    return badges[status] || '<span class="badge badge-secondary">Unknown</span>';
}

/**
 * Load and display acknowledgment report for management
 * @param {string} filter - 'pending', 'acknowledged', or 'objected'
 */
function loadAcknowledgmentReport(filter = 'pending') {
    // Show loading state
    const loadingHtml = '<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading report...</p></div>';
    document.getElementById('acknowledgment-report-container').innerHTML = loadingHtml;
    
    $.ajax({
        url: './includes/ajaxFile/ajaxEvaluationAcknowledgment.php',
        type: 'POST',
        dataType: 'json',
        data: {
            ajaxType: 'get_acknowledgment_report',
            filter: filter
        },
        success: function(response) {
            if (response.status === 200) {
                displayAcknowledgmentReport(response.data, filter);
            } else {
                document.getElementById('acknowledgment-report-container').innerHTML = 
                    '<div class="alert alert-danger">' + (response.message || 'Error loading report') + '</div>';
            }
        },
        error: function() {
            document.getElementById('acknowledgment-report-container').innerHTML = 
                '<div class="alert alert-danger">Failed to load acknowledgment report</div>';
        }
    });
}

/**
 * Display acknowledgment report data
 * @param {array} data - Report data
 * @param {string} filter - Current filter
 */
function displayAcknowledgmentReport(data, filter) {
    if (data.length === 0) {
        document.getElementById('acknowledgment-report-container').innerHTML = 
            '<div class="alert alert-info">No ' + filter + ' acknowledgments found</div>';
        return;
    }
    
    let html = '<table class="table table-striped table-hover">' +
               '<thead>' +
               '<tr>' +
               '<th>Employee</th>' +
               '<th>Position</th>' +
               '<th>Evaluation Date</th>' +
               '<th>Manager</th>' +
               '<th>Status</th>' +
               '<th>Date</th>';
    
    if (filter === 'objected') {
        html += '<th>Objection Reason</th>';
    }
    
    html += '<th>Actions</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>';
    
    data.forEach(function(row) {
        html += '<tr>' +
                '<td>' + (row.employee_name || 'N/A') + '</td>' +
                '<td>' + (row.employee_position || 'N/A') + '</td>' +
                '<td>' + (row.evaluation_date || 'N/A') + '</td>' +
                '<td>' + (row.manager_name || 'N/A') + '</td>' +
                '<td>' + getAcknowledgmentBadge(row.manager_acknowledgment_status) + '</td>' +
                '<td>' + (row.manager_acknowledgment_date || 'N/A') + '</td>';
        
        if (filter === 'objected') {
            html += '<td><button class="btn btn-sm btn-info" onclick="showObjectionDetail(\'' + row.manager_objection_note + '\')"><i class="fa fa-eye"></i> View</button></td>';
        }
        
        html += '<td><button class="btn btn-sm btn-primary"><i class="fa fa-download"></i> Export</button></td>' +
                '</tr>';
    });
    
    html += '</tbody></table>';
    
    document.getElementById('acknowledgment-report-container').innerHTML = html;
}

/**
 * Show objection detail in modal
 * @param {string} note - Objection note
 */
function showObjectionDetail(note) {
    Swal.fire({
        title: 'Objection Reason',
        text: note,
        icon: 'info'
    });
}
