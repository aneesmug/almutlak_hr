/**
 * Shared AJAX Error Handling Utilities
 * Used across multiple JavaScript files for consistent error message handling
 * Includes: employee_profile.js, loanHandling.js, resignationWizard.js
 */

/**
 * Handle AJAX failures with proper error message parsing
 * Returns an object with title, message, and type for consistency
 * @param {jqXHR} jqXHR - jQuery XMLHttpRequest object
 * @param {string} textStatus - Status text (timeout, error, etc.)
 * @param {string} defaultTitle - Default title if error title not found
 * @param {string} defaultMsg - Default message if error message not found
 * @returns {object} Object with title, message, and type properties
 */
function handleAjaxFailure(jqXHR, textStatus, defaultTitle = __('request_failed_title'), defaultMsg = __('server_or_network_error')) {
    let message = __('unknown_error_occurred') || 'An unknown error occurred';
    
    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
        message = jqXHR.responseJSON.message;
    } else if (textStatus === 'timeout') {
        message = __('request_timed_out') || 'Request timed out. Please try again.';
    } else if (jqXHR.status === 0) {
        message = __('error_no_connection') || 'No connection available.';
    } else if (jqXHR.statusText === 'error') {
        message = defaultMsg;
    }
    
    return { 
        title: defaultTitle, 
        message: message, 
        type: 'error' 
    };
}

/**
 * Handle general AJAX errors with SweetAlert popup
 * Displays detailed error information based on HTTP status code
 * @param {jqXHR} jqXHR - jQuery XMLHttpRequest object
 * @param {string} exception - Exception message
 * @returns {boolean} True after displaying error
 */
function errorHandling(jqXHR, exception) {
    var error_msg = '';
    
    if (jqXHR.status === 0) {
        error_msg = __('error_no_connection') || 'No connection';
    } else if (jqXHR.status == 404) {
        error_msg = __('error_404_not_found') || '404 Not Found';
    } else if (jqXHR.status == 500) {
        error_msg = __('error_500_server_error') || '500 Server Error';
    } else if (exception === 'parsererror') {
        error_msg = __('error_parsing_json') || 'Error parsing JSON response';
    } else if (exception === 'timeout') {
        error_msg = __('error_request_timeout') || 'Request timeout';
    } else if (exception === 'error') {
        error_msg = __('error_ajax_request') || 'AJAX request error';
    } else if (exception === 'abort') {
        error_msg = __('error_request_aborted') || 'Request aborted';
    } else {
        error_msg = __('unknown_error_occurred') || 'An unknown error occurred';
    }
    
    // Show error alert with SweetAlert
    Swal.fire({
        title: __('oops') || 'Oops!',
        text: error_msg,
        icon: 'error',
        allowOutsideClick: false,
        confirmButtonClass: 'btn btn-lg',
        buttonsStyling: false,
    });
    
    return true;
}
