
$("head").append($("<script type='text/javascript'></script>").attr("src", "./assets/js/translation.js"));

function __(key, defaultText = '') {
    // Check if the global language object has been defined by PHP.
    if (typeof window.lang === 'undefined' || window.lang === null) {
        // Log an error for easier debugging if the object is missing.
        console.error("Translation Error: The global 'lang' object is not defined. Make sure it's included correctly in your PHP template.");
        return defaultText || key;
    }
    // New check: Warn if the lang object seems empty.
    if (Object.keys(window.lang).length < 5) {
        console.warn("Translation Warning: The global 'lang' object is defined but appears to be empty or incomplete. Check the output of json_encode in your PHP template.", window.lang);
    }
    // Check if the specific key exists in the language object.
    if (typeof window.lang[key] !== 'undefined') {
        return window.lang[key];
    }
    // If the key is not found, return the default text or the key itself.
    return defaultText || key;
}

$(document).on('click', '.signout', function (e) {
    e.preventDefault();
    var action = $(this).data('action');
    Swal.fire({
        title: __("are_you_sure"),
        text: __("signout_warning"),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_signout"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    url: './includes/ajaxFile/signoutAjax.php',
                    type: 'POST',
                    data: {action:action},
                    cache: false,
                    dataType: "json",
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.applyvacationAtter', function (e) {
    e.preventDefault();
    var empid = $(this).data('empid');
    var deptId = $(this).data('dept');
    var country = $(this).data('country');
    var currentBalance = $(this).data('balance') || 0;

    // Quick pre-check: block opening the modal if there's already a pending request
    // Note: We'll allow emergency vacation even with pending requests
    try {
        $.ajax({
            url: './includes/ajaxFile/ajaxVacation.php',
            type: 'POST',
            dataType: 'json',
            data: { ajaxType: 'canApplyVacation', emp_id: empid, is_emergency: 0 },
        }).done(function(res) {
            if (!res || res.ok === false) {
                Swal.fire({ title: 'Error', text: (res && res.message) ? res.message : 'Unable to verify eligibility.', icon: 'error' ,allowOutsideClick:false});
                return;
            }
            if (res.can_apply === false) {
                // Build a richer status message if details are available, plus the full approval chain
                const esc = (s) => String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]));
                let lines = [];
                if (res.pending_inv) lines.push(`${__('request') || 'Request'}: ${esc(res.pending_inv)}`);
                if (res.current_status || res.current_level) {
                    const statusText = (function(){
                        if (res.current_status === 'approved') return __('approved') || 'Approved';
                        if (res.current_status === 'rejected') return __('rejected') || 'Rejected';
                        return __('pending_approval') || 'Pending approval';
                    })();
                    const levelText = res.current_level ? ` (${__('level') || 'Level'} ${esc(res.current_level)})` : '';
                    lines.push(`${__('current_status_label') || 'Current status'}: ${statusText}${levelText}`);
                }
                if (res.current_approver_name) {
                    lines.push(`${__('pending_with') || 'Pending with'}: ${esc(res.current_approver_name)}`);
                }

                // Build approval chain HTML
                let chainHtml = '';
                if (Array.isArray(res.chain) && res.chain.length) {
                    const labelRaw = __('approval_chain');
                    const label = (labelRaw && labelRaw !== 'approval_chain') ? labelRaw : 'Approval chain';
                    const statusLabel = (s) => {
                        if (s === 'approved') return __('approved') || 'Approved';
                        if (s === 'pending') return __('pending') || 'Pending';
                        if (s === 'awaiting') return __('awaiting') || 'Awaiting';
                        if (s === 'rejected') return __('rejected') || 'Rejected';
                        return esc(s);
                    };
                    const icon = (s) => s === 'approved' ? '✅' : (s === 'pending' ? '🟡' : (s === 'awaiting' ? '⏸️' : (s === 'rejected' ? '❌' : 'ℹ️')));
                    const rows = res.chain
                        .sort((a, b) => (a.level||0) - (b.level||0))
                        .map(step => `<div style="text-align:left;">${icon(step.status)} ${__('level') || 'Level'} ${esc(step.level)}: ${esc(step.name)} — ${statusLabel(step.status)}</div>`) 
                        .join('');
                    chainHtml = `<hr/><div style="text-align:left;"><strong>${label}:</strong></div>${rows}`;
                }

                const textMsg = res.message || lines.join('\n');
                
                // Add option to apply for emergency vacation with different dates
                const htmlTop = esc(textMsg).replace(/\n/g, '<br/>');
                const fullHtml = chainHtml 
                    ? `${htmlTop}${chainHtml}<hr/><p style="margin-top:15px;"><strong>${__('note') || 'Note'}:</strong> ${__('you_can_apply_for_emergency_vacation_with_different_dates') || 'You can apply for emergency vacation with different dates.'}</p>`
                    : `${htmlTop}<br/><br/><strong>${__('note') || 'Note'}:</strong> ${__('you_can_apply_for_emergency_vacation_with_different_dates') || 'You can apply for emergency vacation with different dates.'}`;
                
                Swal.fire({ 
                    title: __('cannot_apply_now') || 'Cannot Apply', 
                    html: fullHtml, 
                    icon: 'info', 
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonText: __('apply_emergency_vacation') || 'Apply Emergency Vacation',
                    cancelButtonText: __('cancel') || 'Cancel',
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Open the modal for emergency vacation
                        openVacationApplyModal(empid, deptId, country, currentBalance, true);
                    }
                });
                return;
            }

            // Proceed to open the modal as usual
            openVacationApplyModal(empid, deptId, country, currentBalance, false);
        }).fail(function(jqXHR){
            let msg = 'Unable to verify eligibility.';
            try { let j = JSON.parse(jqXHR.responseText); if (j.message) msg = j.message; } catch(e) {}
            Swal.fire({ title: 'Error', text: msg, icon: 'error' ,allowOutsideClick:false});
        });
    } catch(err) {
        Swal.fire({ title: 'Error', text: 'Unexpected error. Please try again.', icon: 'error' ,allowOutsideClick:false});
    }
});

// Extracted function to open the Apply Vacation modal after eligibility check
function openVacationApplyModal(empid, deptId, country, currentBalance, forceEmergency) {
    currentBalance = currentBalance || 0;
    forceEmergency = forceEmergency || false;

    Swal.fire({
        title: '<i class="fa fa-umbrella-beach"></i> ' + (forceEmergency ? __('apply_emergency_vacation') : __('apply_vacation_info_title')),
        html: vacationApply_HTML(country),
        showCancelButton: true,
        confirmButtonColor: '#4e73df',
        cancelButtonColor: '#e74a3b',
        confirmButtonText: '<i class="fa fa-check"></i> ' + __('yes_register'),
        cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        customClass: {
            popup: 'vacation-modal-popup',
            title: 'vacation-modal-title',
            confirmButton: 'btn-modern-confirm',
            cancelButton: 'btn-modern-cancel'
        },
        width: '95%',
        padding: '20px',
        willOpen: () => {
            const swalModal = Swal.getHtmlContainer();
            
            // If forceEmergency is true, pre-select Fly and Emergency vacation
            if (forceEmergency) {
                setTimeout(() => {
                    // Select "Fly" vacation type
                    $('#inlineRadio1').prop('checked', true).trigger('change');
                    
                    // Show flyTypeSection and select "emergency"
                    setTimeout(() => {
                        $('#vac_type2').prop('checked', true).trigger('change');
                    }, 100);
                }, 100);
            }

            // Original date pickers
            $('#start_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true
            }).on('changeDate', function (e) {
                var startDate = e.date;
                $('#end_date').datepicker('setStartDate', startDate);
                // Update flight date pickers to respect new start date
                $('#departure_date').datepicker('setStartDate', startDate);
                $('#arrival_date').datepicker('setStartDate', startDate);
                // Calculate vacation days
                calculateVacationDays();
            });

            $('#end_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true
            }).on('changeDate', function (e) {
                var endDate = e.date;
                $('#start_date').datepicker('setEndDate', endDate);
                // Update flight date pickers to respect new end date
                $('#departure_date').datepicker('setEndDate', endDate);
                $('#arrival_date').datepicker('setEndDate', endDate);
                // Calculate vacation days
                calculateVacationDays();
            });

            // Function to calculate and display vacation days
            function calculateVacationDays() {
                var startDate = $('#start_date').datepicker('getDate');
                var endDate = $('#end_date').datepicker('getDate');
                
                if (startDate && endDate) {
                    // Calculate difference in days (inclusive)
                    var timeDiff = endDate.getTime() - startDate.getTime();
                    var daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                    
                    // Display the vacation days
                    $('#vacation_days_count').text(daysDiff);
                    $('#vacation_days_display').removeClass('d-none');
                } else {
                    $('#vacation_days_display').addClass('d-none');
                }
            }

            // Initialize departure and arrival date pickers
            $('#departure_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true
            }).on('changeDate', function (e) {
                var departureDate = e.date;
                $('#arrival_date').datepicker('setStartDate', departureDate);
            });

            $('#arrival_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true
            }).on('changeDate', function (e) {
                var arrivalDate = e.date;
                $('#departure_date').datepicker('setEndDate', arrivalDate);
            });

            // Original replacement person loader
            $("#replacement_per").select2({
                dropdownParent: $(swalModal) // Attach to modal
            });
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "emp_department", dept: deptId, exclude_emp_id: empid},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data) {
                            options += `<option value="${res.data[i].emp_id}">${res.data[i].name.split(' ')[0]+' '+res.data[i].name.split(' ')[1]}</option>`;
                        }
                        $('#replacement_per').append(options);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e);
                },
            });

            // Original emp_data loader
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "emp_data", empid: empid},
                success: function(res) {
                    if (res.status == 200) {
                        $('input[name="name"]').val(res.data[0].name);
                        $('input[name="empid"]').val(res.data[0].emp_id);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e);
                },
            });

            // ...existing code...
            
            // MODIFIED: Toggle Fields Logic - now includes salary type section, flight dates, remarks, and encashment
            function toggleVacationFields() {
                const selectedVac = document.querySelector('input[name="vac_type"]:checked');
                $('#flyTypeSection, #replacementSection, #date_select, #notesSection, #salaryTypeSection, #flightDatesSection, #encashSection').addClass('d-none');
                if (!selectedVac) return;
                const vacValue = selectedVac.value;
                if (vacValue === 'Encashed') {
                    // Show encashment section
                    $('#encashSection').removeClass('d-none');
                    
                    // Show loading state
                    $('#vacation_balance_display').text('Loading...');
                    
                    // Fetch current balance from server
                    $.ajax({
                        url: './includes/ajaxFile/ajaxVacation.php',
                        type: 'POST',
                        dataType: 'JSON',
                        data: {ajaxType: "getCurrentVacationBalance", empid: empid},
                        success: function(res) {
                            console.log('Balance Response:', res);
                            if (res && res.status == 200) {
                                var balance = parseFloat(res.balance) || 0;
                                $('#vacation_balance_display').text(balance.toFixed(2));
                                $('#encash_days').attr('max', balance.toFixed(2));
                            } else {
                                console.error('Failed to fetch balance:', res);
                                $('#vacation_balance_display').text('0.00');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Balance AJAX Error:', error);
                            console.error('Response Text:', xhr.responseText);
                            $('#vacation_balance_display').text('0.00');
                        }
                    });
                    
                    // Calculate salary on input - using 'off' first to prevent duplicate bindings
                    $('#encash_days').off('input').on('input', function() {
                        var days = parseFloat($(this).val()) || 0;
                        var balance = parseFloat($('#vacation_balance_display').text()) || 0;
                        
                        if (days > balance) {
                            $(this).val(balance.toFixed(2));
                            days = balance;
                        }
                        
                        if (days > 0) {
                            // Fetch salary from backend
                            $.ajax({
                                url: './includes/ajaxFile/ajaxEmployee.php',
                                type: 'POST',
                                dataType: 'JSON',
                                data: {ajaxType: "calculate_encash_salary", empid: empid, days: days},
                                success: function(res) {
                                    console.log('Salary Calculation Response:', res);
                                    if (res && res.status == 200) {
                                        $('#encashment_salary_display').text(res.salary);
                                    } else {
                                        $('#encashment_salary_display').text('0');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Salary Calculation Error:', error, xhr.responseText);
                                    $('#encashment_salary_display').text('0');
                                }
                            });
                        } else {
                            $('#encashment_salary_display').text('0');
                        }
                    });
                } else if (vacValue === 'Local Vacation' || vacValue === 'Fly') {
                    $('#flyTypeSection').removeClass('d-none');
                    const selectedFlyType = document.querySelector('input[name="fly_type"]:checked');
                    if (selectedFlyType) {
                        const flyVal = selectedFlyType.value;
                        if (flyVal === 'annual' || flyVal === 'emergency') {
                            $('#replacementSection, #date_select').removeClass('d-none');
                            // NEW: Show salary type selection for BOTH Fly + Annual AND Local Vacation + Annual
                            if (flyVal === 'annual') {
                                $('#salaryTypeSection').removeClass('d-none');
                                // Show flight dates AND remarks ONLY for Fly + Annual (NOT Local Vacation)
                                if (vacValue === 'Fly') {
                                    $('#flightDatesSection, #notesSection').removeClass('d-none');
                                } else {
                                    // Explicitly hide flight dates for Local Vacation
                                    $('#flightDatesSection, #notesSection').addClass('d-none');
                                }
                            }
                        }
                    }
                    document.querySelectorAll('input[name="fly_type"]').forEach(flyRadio => {
                        flyRadio.addEventListener('change', function () {
                            const flyVal = this.value;
                            // Re-check the current vacation type (don't use stale vacValue)
                            const currentVacType = document.querySelector('input[name="vac_type"]:checked');
                            const currentVacValue = currentVacType ? currentVacType.value : '';
                            if (flyVal === 'annual' || flyVal === 'emergency') {
                                $('#replacementSection, #date_select').removeClass('d-none');
                                // NEW: Show salary type selection for BOTH Fly + Annual AND Local Vacation + Annual
                                if (flyVal === 'annual') {
                                    $('#salaryTypeSection').removeClass('d-none');
                                    // Show flight dates AND remarks ONLY for Fly + Annual
                                    if (currentVacValue === 'Fly') {
                                        $('#flightDatesSection, #notesSection').removeClass('d-none');
                                    } else {
                                        // Explicitly hide for Local Vacation
                                        $('#flightDatesSection, #notesSection').addClass('d-none');
                                    }
                                } else {
                                    $('#salaryTypeSection, #flightDatesSection, #notesSection').addClass('d-none');
                                }
                            } else {
                                $('#replacementSection, #date_select, #salaryTypeSection, #flightDatesSection, #notesSection').addClass('d-none');
                            }
                        });
                    });
                }
            }
            function initVacationForm() {
                document.querySelectorAll('input[name="vac_type"]').forEach(radio => {
                    radio.addEventListener('change', toggleVacationFields);
                });
                toggleVacationFields();
            }
            initVacationForm();
        },
        preConfirm: function() {
            const formElement = document.getElementById('submitVacationApplyForm');
            const formData = new FormData(formElement);
            formData.append("ajaxType", "applyVacation");
            formData.append("emp_id", empid);
            formData.append("dept_id", deptId);


            const selectedRadio = $('input[name="vac_type"]:checked').val();
            if (!selectedRadio) {
                Swal.showValidationMessage(__('select_vacation_type_validation'));
                return false;
            }
            if (selectedRadio === 'Encashed') {
                const encashDays = parseFloat($('#encash_days').val()) || 0;
                const balance = parseFloat($('#vacation_balance_display').text()) || 0;
                const encashmentSalary = parseFloat($('#encashment_salary_display').text().replace(/,/g, '')) || 0;
                
                if (!encashDays || encashDays < 0.01) {
                    Swal.showValidationMessage(__('enter_days_to_encash_validation') || 'Please enter number of days to encash');
                    return false;
                }
                if (encashDays > balance) {
                    Swal.showValidationMessage(__('encash_days_exceeds_balance') || 'You cannot encash more than your balance');
                    return false;
                }
                if (encashmentSalary <= 0) {
                    Swal.showValidationMessage(__('encashment_salary_not_calculated') || 'Encashment salary not calculated. Please enter days first.');
                    return false;
                }
                
                // FormData already includes encash_days from the input field
                // Just need to append the calculated salary
                formData.set('encash_days', encashDays); // Ensure correct value
                formData.set('encashment_salary', encashmentSalary);
                
                console.log('Encashment submission - Days:', encashDays, 'Salary:', encashmentSalary);
            } else if (selectedRadio === 'Local Vacation' || selectedRadio === 'Fly') {
                const flyType = $('input[name="fly_type"]:checked').val();
                if (!flyType) {
                    Swal.showValidationMessage(__('select_vacation_type_validation'));
                    return false;
                }
                if (flyType === 'annual' || flyType === 'emergency') {
                    const startDate = $('#start_date').val();
                    const endDate = $('#end_date').val();
                    const replacement = $('#replacement_per').val();
                    if (!startDate || !endDate) {
                        Swal.showValidationMessage(__('start_return_date_required_validation'));
                        return false;
                    }
                    if (!replacement) {
                        Swal.showValidationMessage(__('replacement_person_required_validation'));
                        return false;
                    }
                    // Validate flight dates for Fly + Annual vacation
                    if (selectedRadio === 'Fly' && flyType === 'annual') {
                        const departureDate = $('#departure_date').val();
                        const arrivalDate = $('#arrival_date').val();
                        if (!departureDate || !arrivalDate) {
                            Swal.showValidationMessage(__('flight_dates_required_validation') || 'Please select departure and arrival dates');
                            return false;
                        }
                        // Validate that flight dates are within vacation period
                        const start = new Date(startDate);
                        const end = new Date(endDate);
                        const departure = new Date(departureDate);
                        const arrival = new Date(arrivalDate);
                        if (departure < start || departure > end) {
                            Swal.showValidationMessage(__('departure_date_must_be_between_vacation_dates') || 'Departure date must be between start date and return date');
                            return false;
                        }
                        if (arrival < start || arrival > end) {
                            Swal.showValidationMessage(__('arrival_date_must_be_between_vacation_dates') || 'Arrival date must be between start date and return date');
                            return false;
                        }
                    }
                    // NEW: Validate vacation salary type selection for annual vacations
                    if (flyType === 'annual') {
                        const salaryType = $('input[name="vacation_salary_type"]:checked').val();
                        if (!salaryType) {
                            Swal.showValidationMessage(__('vacation_salary_type_required') || 'Please select vacation salary payment option');
                            return false;
                        }
                    }
                }
            }

            // NEW: Automatically set direct supervisor as first approver
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: { ajaxType: 'get_direct_supervisor', emp_id: empid },
                }).done(function(res) {
                    if (res && res.supervisor_id) {
                        formData.append('first_approver_id', res.supervisor_id);
                    }
                    
                    // DEBUG: Log FormData contents
                    console.log('=== FormData Contents ===');
                    for (let pair of formData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }
                    console.log('departure_date value:', $('#departure_date').val());
                    console.log('arrival_date value:', $('#arrival_date').val());
                    console.log('vacation_salary_type checked:', $('input[name="vacation_salary_type"]:checked').val());
                    console.log('========================');
                    
                    $.ajax({
                        url: './includes/ajaxFile/ajaxVacation.php',
                        type: 'POST',
                        dataType: "JSON",
                        cache: false,
                        contentType: false,
                        processData: false,
                        data: formData,
                    })
                    .done(function (response) {
                        Swal.fire({
                            title: response.title,
                            text: response.message,
                            icon: response.type,
                            allowOutsideClick: false
                        }).then(function (isConfirm) {
                            if (isConfirm.value && response.type === 'success') {
                                location.reload();
                            }
                        });
                        resolve();
                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        let errorMsg = 'An error occurred. Please try again.';
                        try {
                            let jsonResponse = JSON.parse(jqXHR.responseText);
                            if (jsonResponse && jsonResponse.message) {
                                errorMsg = jsonResponse.message;
                            } else if (jqXHR.responseText) {
                                let responseText = jqXHR.responseText.split('<br />');
                                errorMsg = responseText[responseText.length - 1].replace(/<b>Warning<\/b>:|<b>Fatal error<\/b>:|Uncaught \(in promise\) Error!:/gi, '').trim();
                            }
                        } catch (e) {}
                        Swal.fire({
                            title: 'Error!',
                            text: errorMsg,
                            icon: 'error'
                        ,allowOutsideClick:false});
                        reject(errorMsg);
                    });
                }).fail(function() {
                    Swal.fire({
                        title: __('error'),
                        text: __('could_not_find_supervisor'),
                        icon: 'error',
                        allowOutsideClick: false
                    });
                    reject(__('could_not_find_supervisor'));
                });
            });
        }
    })
}


// Main click event handler for the "Apply for Leave" button
$(document).on('click', '.applyLeaveRequest', function(e) {
    e.preventDefault();
    const empid = $(this).data('empid');
    let employeeGender = null;

    // First, fetch employee data to get gender
    $.ajax({
        url: './includes/ajaxFile/ajaxEmployee.php',
        type: 'POST',
        dataType: 'json',
        async: false,
        data: {
            ajaxType: "emp_data",
            empid: empid
        },
        success: function(res) {
            if (res.status == 200 && res.data.length > 0) {
                employeeGender = parseInt(res.data[0].sex) || null;
            }
        }
    });

    Swal.fire({
        title: __('loading_employee_info'),
        html: generateLeaveFormHTML(employeeGender),
        width: '50rem',
        showCancelButton: true,
        confirmButtonText: __('submit_request'),
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        willOpen: () => {
            // Show a loading state while fetching employee data
            Swal.showLoading();

            // Fetch employee data to get the name
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    ajaxType: "emp_data",
                    empid: empid
                },
                success: function(res) {
                    if (res.status == 200 && res.data.length > 0) {
                        const employeeName = res.data[0].name;
                        // Update the modal title with the employee's name
                        $('.swal2-title').html(`${__('leave_application_for')} <br><span style="color:#3085d6;">${employeeName}</span>`);
                        Swal.hideLoading();
                    } else {
                        // Handle case where employee is not found
                        $('.swal2-title').text(__('employee_not_found'));
                        Swal.hideLoading();
                    }
                },
                error: function() {
                    $('.swal2-title').text(__('error_fetching_data'));
                    Swal.hideLoading();
                }
            });
        },
        didOpen: () => {
            // Initialize Select2
            $('#leave_type_select').select2({
                placeholder: __("select_leave_type_placeholder"),
                dropdownParent: $('.swal2-container') // Important for positioning
            });

            // Initialize datepickers and add event listeners
            $('#start_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true,
                startDate: '-10d'
            }).on('changeDate', function(e) {
                $('#end_date').datepicker('setStartDate', e.date);
                if ($('#leave_type_select').val() === 'Compensatory Leave') {
                    $('#end_date').val($(this).val()).datepicker('update');
                }
                calculateTotalDays();
            });

            $('#end_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true,
                startDate: '+0d'
            }).on('changeDate', function(e) {
                calculateTotalDays();
            });

            // Add event listener for the Select2 dropdown
            $('#leave_type_select').on('change', function() {
                toggleLeaveFields();
                
                // Initialize Dropzone after attachment section becomes visible
                setTimeout(function() {
                    if (!$('#attachmentSection').hasClass('d-none') && $('#leaveDropzone').length > 0 && !window.leaveDropzoneInstance) {
                        initializeLeaveDropzone();
                    }
                }, 200);
            });

            // Function to initialize Dropzone
            function initializeLeaveDropzone() {
                // Double-check element exists
                const dropzoneElement = document.getElementById('leaveDropzone');
                if (!dropzoneElement) {
                    console.error('Dropzone element #leaveDropzone not found in DOM');
                    return;
                }

                // Check if already initialized
                if (window.leaveDropzoneInstance) {
                    console.log('Dropzone already initialized');
                    return;
                }

                try {
                    Dropzone.autoDiscover = false;
                    const leaveDropzone = new Dropzone(dropzoneElement, {
                        url: '#', // Dummy URL since we'll handle submission via AJAX
                        autoProcessQueue: false,
                        uploadMultiple: true,
                        parallelUploads: 10,
                        maxFiles: 10,
                        maxFilesize: 5, // MB
                        acceptedFiles: '.pdf,.jpg,.jpeg,.png',
                        addRemoveLinks: true,
                        dictDefaultMessage: __('drag_drop_files') || 'Drag & Drop files here or click to browse',
                        dictRemoveFile: __('remove_file') || 'Remove',
                        dictMaxFilesExceeded: __('maximum_10_files_allowed') || 'Maximum 10 files allowed',
                        dictFileTooBig: __('file_too_large_dropzone') || 'File is too large ({{filesize}}MB). Max: {{maxFilesize}}MB',
                        dictInvalidFileType: __('invalid_file_type') || 'Invalid file type. Only PDF, JPG, PNG allowed',
                        init: function() {
                            this.on('addedfile', function(file) {
                                console.log('File added:', file.name);
                            });
                            this.on('removedfile', function(file) {
                                console.log('File removed:', file.name);
                            });
                            this.on('maxfilesexceeded', function(file) {
                                this.removeFile(file);
                                Swal.showValidationMessage(__('maximum_10_files_allowed') || 'Maximum 10 files allowed');
                                // Clear validation message after 3 seconds
                                setTimeout(() => {
                                    const validationMsg = document.querySelector('.swal2-validation-message');
                                    if (validationMsg) {
                                        validationMsg.style.display = 'none';
                                    }
                                }, 3000);
                            });
                            this.on('error', function(file, errorMessage) {
                                console.log('Error:', errorMessage);
                                this.removeFile(file);
                                if (typeof errorMessage === 'string') {
                                    Swal.showValidationMessage(errorMessage);
                                    // Clear validation message after 3 seconds
                                    setTimeout(() => {
                                        const validationMsg = document.querySelector('.swal2-validation-message');
                                        if (validationMsg) {
                                            validationMsg.style.display = 'none';
                                        }
                                    }, 3000);
                                }
                            });
                        }
                    });

                    // Store dropzone instance for later access
                    window.leaveDropzoneInstance = leaveDropzone;
                    console.log('Dropzone initialized successfully');
                } catch (error) {
                    console.error('Error initializing Dropzone:', error);
                }
            }
        },
        preConfirm: () => {
            const form = document.getElementById('applyLeaveForm');
            const formData = new FormData(form);
            formData.append("ajaxType", "applyLeave");
            formData.append("empid", empid);

            // --- UPDATED Validation Logic - ALL fields required for ALL leave types ---
            const leaveType = formData.get('leave_type');
            if (!leaveType) {
                Swal.showValidationMessage(__('select_leave_type_validation'));
                return false;
            }

            // Start date is REQUIRED for all leave types
            const startDate = formData.get('start_date');
            if (!startDate) {
                Swal.showValidationMessage(__('start_date_required'));
                return false;
            }
            
            // End date is REQUIRED for all leave types
            const endDate = formData.get('end_date');
            if (!endDate) {
                Swal.showValidationMessage(__('end_date_required'));
                return false;
            }

            // Validate date range
            if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                Swal.showValidationMessage(__('end_date_before_start_date_validation'));
                return false;
            }

            // Destination required for Business Trip
            const destination = formData.get('trip_destination');
            if (leaveType === 'Business Trip' && !destination.trim()) {
                Swal.showValidationMessage(__('destination_required_validation'));
                return false;
            }

            // Accommodation provided required for Business Trip
            const accommodationProvided = formData.get('accommodation_provided');
            if (leaveType === 'Business Trip' && !accommodationProvided) {
                Swal.showValidationMessage(__('accommodation_required_validation'));
                return false;
            }

            // Transportation provided required for Business Trip
            const transportationProvided = formData.get('transportation_provided');
            if (leaveType === 'Business Trip' && !transportationProvided) {
                Swal.showValidationMessage(__('transportation_required_validation'));
                return false;
            }

            // Reason/Notes is REQUIRED for ALL leave types
            const reason = formData.get('reason');
            if (!reason || !reason.trim()) {
                Swal.showValidationMessage(__('reason_required_validation'));
                return false;
            }

            // Validate Dropzone attachments
            const dropzone = window.leaveDropzoneInstance;
            if (!dropzone || dropzone.files.length === 0) {
                Swal.showValidationMessage(__('at_least_one_file_required') || 'At least one file is required');
                return false;
            }

            if (dropzone.files.length > 10) {
                Swal.showValidationMessage(__('max_10_files_allowed') || 'Maximum 10 files allowed');
                return false;
            }

            // Add Dropzone files to FormData
            dropzone.files.forEach((file, index) => {
                formData.append('attachments[]', file);
            });


            // --- AJAX Submission ---
            return $.ajax({
                url: './includes/ajaxFile/ajaxVacation.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).catch(error => {
                if (error.responseJSON && error.responseJSON.message) {
                        Swal.showValidationMessage(error.responseJSON.message);
                } else {
                        Swal.showValidationMessage(`${__('request_failed_status')} ${error.statusText || 'Unknown error'}`);
                }
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: result.value.title,
                text: result.value.message,
                icon: result.value.type,
                allowOutsideClick:false}).then(() => {
                if (result.value.type === 'success') {
                    location.reload();
                }
            });
        }
    });
});


$(document).on('click', '#startUpdateRequest', function() {
    const empid = $(this).data('empid');
    const avatarLoad = $(this).data('avatar');
    const mobile = $(this).data('mobile');
    const email = $(this).data('email');
    const address = $(this).data('address');
    const passport_number = $(this).data('passport_number');
    const passport_exp = $(this).data('passport_exp');
    
    // Show field selection modal - pending check happens after field selection
    showUpdateRequestModal(empid, avatarLoad, mobile, email, address, passport_number, passport_exp);
});

// Extracted function to show the update request modal
function showUpdateRequestModal(empid, avatarLoad, mobile, email, address, passport_number, passport_exp) {
    // --- First Modal: Ask WHAT to update ---
    Swal.fire({
        title: '<i class="fa fa-edit"></i> ' + __('what_to_update_title'),
        html: `
            <style>
                .update-request-form {
                    padding: 20px 10px;
                    text-align: left;
                }
                .update-form-group {
                    margin-bottom: 20px;
                }
                .update-label {
                    display: block;
                    font-weight: 600;
                    color: #2c3e50;
                    margin-bottom: 8px;
                    font-size: 14px;
                }
                .update-select {
                    width: 100%;
                    padding: 12px 15px;
                    border: 2px solid #e0e6ed;
                    border-radius: 8px;
                    font-size: 14px;
                    transition: all 0.3s ease;
                    background: #f8f9fa;
                    cursor: pointer;
                }
                .update-select:focus {
                    border-color: #4e73df;
                    background: #fff;
                    outline: none;
                    box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
                }
                .update-select option {
                    padding: 10px;
                }
                .update-help-text {
                    display: flex;
                    align-items: center;
                    margin-top: 10px;
                    padding: 10px 12px;
                    background: #e8f4f8;
                    border-left: 4px solid #3498db;
                    border-radius: 4px;
                    font-size: 12px;
                    color: #2980b9;
                }
                .update-help-text i {
                    margin-right: 8px;
                    font-size: 14px;
                }
            </style>
            <form class="update-request-form">
                <div class="update-form-group">
                    <label class="update-label">
                        <i class="fa fa-list-ul"></i> ${__('select_field_to_update')}<span class="text-danger"> *</span>
                    </label>
                    <select class="update-select" id="field_select" name="field_select" required>
                        <option value="">${__('select_an_item_placeholder')}</option>
                        <option value="Mobile">${__('mobile')}</option>
                        <option value="Email">${__('email')}</option>
                        <option value="Address">${__('address')}</option>
                        <option value="Passport No">${__('passport_number')}</option>
                        <option value="Passport Exp">${__('passport_expiry_date')}</option>
                        <option value="Profile Picture">${__('profile_picture')}</option>
                        <option value="Upload Documents">${__('upload_documents')}</option>
                    </select>
                    <div class="update-help-text">
                        <i class="fa fa-info-circle"></i>
                        <span>${__('select_what_you_want_to_update') || 'Select the field you want to update'}</span>
                    </div>
                </div>
            </form>
        `,
        width: '550px',
        allowOutsideClick: false,
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-arrow-right"></i> ' + __('next'),
        cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel'),
        customClass: {
            confirmButton: 'btn btn-primary waves-effect waves-light',
            cancelButton: 'btn btn-secondary waves-effect waves-light ml-2',
            popup: 'update-modal-popup'
        },
        allowOutsideClick: false,
        buttonsStyling: false,
        inputValidator: (value) => {
            const selectedField = document.getElementById('field_select').value;
            if (!selectedField) {
                return __('you_need_to_select_something_validation')
            }
        },
        preConfirm: () => {
            return document.getElementById('field_select').value;
        }
    }).then((result) => {
        // If the user clicked "Next" and selected a field
        if (result.isConfirmed && result.value) {
            const field = result.value;
            
            // Check if there's a pending request for THIS specific field type
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                type: 'POST',
                dataType: 'JSON',
                data: { ajaxType: 'check_pending_update', empid: empid, type: field },
                success: function(response) {
                    if (response.has_pending) {
                        Swal.fire({
                            title: __('pending_request_title', 'Request Pending'),
                            html: __('pending_request_message', 'You already have a modification request for this field sent and waiting for approval.') + 
                                  '<br><br><strong>' + __('pending_type', 'Field') + ':</strong> ' + response.pending_type + 
                                  '<br><strong>' + __('submitted_on', 'Submitted On') + ':</strong> ' + response.created_at,
                            icon: 'info',
                            confirmButtonText: __('ok'),
                            allowOutsideClick: false
                        }).then(() => {
                            // Return to field selection modal
                            showUpdateRequestModal(empid, avatarLoad, mobile, email, address, passport_number, passport_exp);
                        });
                        return;
                    }
                    
                    // No pending request for this field, proceed with the update modal
                    proceedWithFieldUpdate(field, empid, avatarLoad, mobile, email, address, passport_number, passport_exp);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    handleAjaxFailure(jqXHR, textStatus);
                }
            });
        }
    });
}

// Separate function to handle the actual field update after validation
function proceedWithFieldUpdate(field, empid, avatarLoad, mobile, email, address, passport_number, passport_exp) {
            // --- Handle Profile Picture with Croppie ---
            if (field === 'Profile Picture') {
                const empData = {
                    emp_id: empid,
                    img: avatarLoad
                };
                Swal.fire({
                    title: __('change_profile_picture_title'),
                    html: `
                        <div class="row" >
                            <div class="col-md-12 text-center">
                                <p>${__('current_picture')}</p>
                                <img src="${empData.img}" class="img-fluid rounded-circle mb-3" style="width:150px;height:150px" />
                                <p>${__('new_picture')}</p>
                                <div id="emp-img-cropper" style="width:300px; height:300px; margin:auto;"></div>
                            </div>
                        </div>`,
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    cancelButtonText: __('cancel'),
                    confirmButtonText: __('yes_update'),
                    showLoaderOnConfirm: true,
                    allowOutsideClick: false,
                    width: '500px',
                    didOpen: () => {
                        // This variable will hold the Croppie instance, as per your snippet.
                        let $uploadCrop;
                        // Initialize Croppie on the correct element from the modal's HTML.
                        const el = document.getElementById('emp-img-cropper');
                        $uploadCrop = $(el).croppie({
                            enableExif: true,
                            viewport: {
                                width: 300,
                                height: 300,
                                type: 'circle',
                            },
                            boundary: {
                                width: 350,
                                height: 350,
                            }
                        });
                        // Handle file selection
                        $('#img-crop-input').on('change', function () {
                            var reader = new FileReader();
                            reader.onload = function (e) {
                                // Use the correct method to bind the image to the Croppie instance
                                $uploadCrop.croppie('bind', { 
                                    url: e.target.result 
                                });
                            };
                            reader.readAsDataURL(this.files[0]);
                        });
                        // Trigger the hidden file input
                        $('#img-crop-input').trigger('click');
                        // Store instance for preConfirm
                        Swal.getContainer().croppieInstance = $uploadCrop;
                    },
                    preConfirm: () => {
                        // CORRECTED: Call the 'result' method on the Croppie instance stored in the container
                        return Swal.getContainer().croppieInstance.croppie('result', {
                            type: 'canvas',
                            size: 'viewport',
                            format: 'png'
                        }).then(function (resp) {
                            return $.ajax({
                                url: "./includes/ajaxFile/ajaxEmployee.php",
                                type: "POST",
                                dataType: "JSON",
                                data: {
                                    "image_base64": resp,
                                    "emp_id": empData.emp_id,
                                    "type": "Profile Picture",
                                    ajaxType: 'create_update_request'
                                }
                            }).fail(function() {
                                Swal.showValidationMessage(__("request_failed_try_again"));
                            });
                        });
                    },
                }).then((croppieResult) => {
                    if (croppieResult.isConfirmed && croppieResult.value) {
                        Swal.fire({
                            title: croppieResult.value.title,
                            text: croppieResult.value.message,
                            icon: croppieResult.value.type
                        }).then(() => location.reload());
                    }
                });

            } 
            // --- Handle all other fields ---
            else {
                let inputType = 'text';
                let currentValue = '';
                switch(field) {
                    case 'Mobile': currentValue = mobile; break;
                    case 'Email': inputType = 'email'; currentValue = email; break;
                    case 'Address': currentValue = address; break;
                    case 'Passport No': currentValue = passport_number; break;
                    case 'Passport Exp': inputType = 'date'; currentValue = passport_exp; break;
                }
                Swal.fire({
                    title: `${__('update_field_title')} ${field}`,
                    html: `
                        <p class="text-muted">${__('your_current_value_is')} <strong>${currentValue}</strong></p>
                        <form id="updateRequestForm" class="mt-3">
                                <input type="hidden" name="type" value="${field}">
                                <input type="hidden" name="emp_id" value="${empid}">
                                <input type="${inputType}" id="swal-input" name="new_value" class="form-control" placeholder="${__('enter_new_field_placeholder')} ${field.toLowerCase()}" required>
                        </form>`,
                    confirmButtonText: __('submit_request'),
                    customClass: {
                        confirmButton: 'btn btn-success waves-effect waves-light',
                        cancelButton: 'btn btn-danger waves-effect waves-light ml-2'
                    },
                    buttonsStyling: false,
                    showCancelButton: true,
                    focusConfirm: false,
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading(),
                    preConfirm: () => {
                        const form = document.getElementById('updateRequestForm');
                        const formData = new FormData(form);
                        formData.append('ajaxType', 'create_update_request');
                        return $.ajax({
                            url: './includes/ajaxFile/ajaxEmployee.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json'
                        }).fail(function() {
                            Swal.showValidationMessage(__("request_failed"));
                        });
                    }
                }).then((finalResult) => {
                    if (finalResult.isConfirmed) {
                        Swal.fire({
                            title: finalResult.value.title,
                            text: finalResult.value.message,
                            icon: finalResult.value.type
                        });
                    }
                });
            }
            if (field === 'Upload Documents') {
                // Fetch document types from database
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: { ajaxType: 'get_document_types' },
                    success: function(response) {
                        if (response.status == 200 && response.data.length > 0) {
                            let docTypeOptions = '<option value="">' + __('select_document_type') + '</option>';
                            response.data.forEach(function(doc) {
                                docTypeOptions += '<option value="' + doc.duc_type + '">' + doc.duc_type + '</option>';
                            });
                            
                            Swal.fire({
                                title: '<i class="fa fa-upload"></i> ' + __('upload_documents'),
                                html: `
                                    <style>
                                        .upload-document-form {
                                            padding: 20px 10px;
                                            text-align: left;
                                        }
                                        .upload-form-group {
                                            margin-bottom: 25px;
                                        }
                                        .upload-label {
                                            display: block;
                                            font-weight: 600;
                                            color: #2c3e50;
                                            margin-bottom: 8px;
                                            font-size: 14px;
                                        }
                                        .upload-select, .upload-file-input {
                                            width: 100%;
                                            padding: 12px 15px;
                                            border: 2px solid #e0e6ed;
                                            border-radius: 8px;
                                            font-size: 14px;
                                            transition: all 0.3s ease;
                                            background: #f8f9fa;
                                        }
                                        .upload-select:focus, .upload-file-input:focus {
                                            border-color: #4e73df;
                                            background: #fff;
                                            outline: none;
                                            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
                                        }
                                        .upload-file-wrapper {
                                            position: relative;
                                            overflow: hidden;
                                            display: inline-block;
                                            width: 100%;
                                        }
                                        .upload-file-input {
                                            cursor: pointer;
                                        }
                                        .upload-file-input::-webkit-file-upload-button {
                                            padding: 8px 16px;
                                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                            color: white;
                                            border: none;
                                            border-radius: 6px;
                                            cursor: pointer;
                                            font-weight: 600;
                                            margin-right: 10px;
                                            transition: all 0.3s ease;
                                        }
                                        .upload-file-input::-webkit-file-upload-button:hover {
                                            transform: translateY(-2px);
                                            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                                        }
                                        .upload-help-text {
                                            display: flex;
                                            align-items: center;
                                            margin-top: 8px;
                                            padding: 10px 12px;
                                            background: #e8f4f8;
                                            border-left: 4px solid #3498db;
                                            border-radius: 4px;
                                            font-size: 12px;
                                            color: #2980b9;
                                        }
                                        .upload-help-text i {
                                            margin-right: 8px;
                                            font-size: 14px;
                                        }
                                        .file-type-badges {
                                            display: flex;
                                            gap: 6px;
                                            margin-top: 10px;
                                            flex-wrap: wrap;
                                        }
                                        .file-badge {
                                            padding: 4px 10px;
                                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                            color: white;
                                            border-radius: 12px;
                                            font-size: 11px;
                                            font-weight: 600;
                                            letter-spacing: 0.5px;
                                        }
                                    </style>
                                    <form id="uploadDocumentForm" enctype="multipart/form-data" class="upload-document-form">
                                        <div class="upload-form-group">
                                            <label class="upload-label">
                                                <i class="fa fa-file-alt"></i> ${__('document_type')}<span class="text-danger"> *</span>
                                            </label>
                                            <select class="upload-select" id="document_type_select" name="document_type" required>
                                                ${docTypeOptions}
                                            </select>
                                        </div>
                                        <div class="upload-form-group">
                                            <label class="upload-label">
                                                <i class="fa fa-paperclip"></i> ${__('select_file')}<span class="text-danger"> *</span>
                                            </label>
                                            <div class="upload-file-wrapper">
                                                <input type="file" class="upload-file-input" id="document_file_input" name="document_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                            </div>
                                            <div class="upload-help-text">
                                                <i class="fa fa-info-circle"></i>
                                                <span>${__('allowed_formats')}: <strong>PDF, JPG, JPEG, PNG</strong> (Max 5MB)</span>
                                            </div>
                                            <div class="file-type-badges">
                                                <span class="file-badge">📄 PDF</span>
                                                <span class="file-badge">🖼️ JPG</span>
                                                <span class="file-badge">🖼️ JPEG</span>
                                                <span class="file-badge">🖼️ PNG</span>
                                            </div>
                                        </div>
                                        <input type="hidden" name="emp_id" value="${empid}">
                                        <input type="hidden" name="emptype" value="employee">
                                        <input type="hidden" name="type" value="Upload Documents">
                                    </form>
                                `,
                                width: '550px',
                                confirmButtonText: '<i class="fa fa-check"></i> ' + __('submit_request'),
                                cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel'),
                                customClass: {
                                    confirmButton: 'btn btn-success waves-effect waves-light',
                                    cancelButton: 'btn btn-danger waves-effect waves-light ml-2',
                                    popup: 'upload-modal-popup'
                                },
                                buttonsStyling: false,
                                showCancelButton: true,
                                focusConfirm: false,
                                showLoaderOnConfirm: true,
                                allowOutsideClick: () => !Swal.isLoading(),
                                preConfirm: () => {
                                    const docType = document.getElementById('document_type_select').value;
                                    const fileInput = document.getElementById('document_file_input');
                                    
                                    if (!docType) {
                                        Swal.showValidationMessage(__('select_document_type_validation') || 'Please select a document type');
                                        return false;
                                    }
                                    
                                    if (!fileInput.files || fileInput.files.length === 0) {
                                        Swal.showValidationMessage(__('select_file_validation') || 'Please select a file to upload');
                                        return false;
                                    }
                                    
                                    const file = fileInput.files[0];
                                    
                                    // Validate file type
                                    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
                                    const fileExtension = file.name.split('.').pop().toLowerCase();
                                    if (!allowedExtensions.includes(fileExtension)) {
                                        Swal.showValidationMessage(__('invalid_file_type') || 'Only PDF, JPG, JPEG, and PNG files are allowed');
                                        return false;
                                    }
                                    
                                    const maxSize = 5 * 1024 * 1024; // 5MB
                                    if (file.size > maxSize) {
                                        Swal.showValidationMessage(__('file_too_large_5') || 'File size should not exceed 5MB');
                                        return false;
                                    }
                                    
                                    const form = document.getElementById('uploadDocumentForm');
                                    const formData = new FormData(form);
                                    formData.append('ajaxType', 'upload_employee_document');
                                    // Ensure emptype is present in FormData for PHP
                                    if (!formData.has('emptype')) {
                                        formData.append('emptype', 'employee');
                                    }
                                    
                                    return $.ajax({
                                        url: './includes/ajaxFile/ajaxEmployee.php',
                                        type: 'POST',
                                        data: formData,
                                        processData: false,
                                        contentType: false,
                                        dataType: 'json'
                                    }).fail(function() {
                                        Swal.showValidationMessage(__("request_failed"));
                                    });
                                }
                                ,cancelButtonColor:'#d33',
                                cancelButtonText:__('cancel')
                            }).then((finalResult) => {
                                if (finalResult.isConfirmed) {
                                    Swal.fire({
                                        title: finalResult.value.title,
                                        text: finalResult.value.message,
                                        icon: finalResult.value.type
                                    ,allowOutsideClick:false}).then(() => {
                                        if (finalResult.value.type === 'success') {
                                            location.reload();
                                        }
                                    });
                                }
                            });
                        } else {
                            Swal.fire({
                                title: __('error'),
                                text: __('no_document_types_available'),
                                icon: 'error',
                                allowOutsideClick: false
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: __('error'),
                            text: __('failed_to_load_document_types'),
                            icon: 'error',
                            allowOutsideClick: false
                        });
                    }
                });
            }
        
}


function vacationApply_HTML(country) {
    var strView = 
    `<style>
        
    </style>
    
    <form id="submitVacationApplyForm" enctype="multipart/form-data">
        <div class="vacation-form-container">
            
            <!-- Employee Information -->
            <div class="info-row">
                <div class="info-field" style="flex: 2;">
                    <label>${__('employee_name')}</label>
                    <input type="text" name="name" id="name" readonly>
                </div>
                <div class="info-field" style="flex: 1;">
                    <label>${__('employee_id')}</label>
                    <input type="text" name="empid" id="empid" readonly>
                </div>
            </div>


            <!-- Vacation Type Selection -->
            <div class="vacation-card">
                <div class="vacation-card-header">
                    <i class="fa fa-clipboard-list"></i>
                    ${__('remarks')}<span class="text-danger">*</span>
                </div>
                <div class="vac-radio-group">
                    <div class="vac-radio-option">
                        <input type="radio" id="inlineRadio3" value="Local Vacation" name="vac_type">
                        <label for="inlineRadio3" class="vac-radio-label">
                            <i class="fa fa-map-marker-alt"></i>
                            <span>${__('local_vacation')}</span>
                        </label>
                    </div>
                    ${(country != 191 && country != 150) ? `
                    <div class="vac-radio-option">
                        <input type="radio" id="inlineRadio1" value="Fly" name="vac_type">
                        <label for="inlineRadio1" class="vac-radio-label">
                            <i class="fa fa-plane-departure"></i>
                            <span>${__('fly')}</span>
                        </label>
                    </div>` : ''}
                    <div class="vac-radio-option">
                        <input type="radio" id="inlineRadio2" value="Encashed" name="vac_type">
                        <label for="inlineRadio2" class="vac-radio-label">
                            <i class="fa fa-money-bill-wave"></i>
                            <span>${__('encashed')}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Encashment Section -->
            <div class="vacation-card d-none" id="encashSection">
                <div class="vacation-card-header">
                    <i class="fa fa-coins"></i> ${__('vacation_balance') || 'Vacation Balance'}
                </div>
                <div style="margin-bottom:8px; color:#4e73df; font-weight:600;">
                    <span id="vacation_balance_display">0</span> ${__('days') || 'days'}
                </div>
                <div class="form-group">
                    <label for="encash_days">${__('enter_days_to_encash') || 'Enter number of days to encash'}<span class="text-danger">*</span></label>
                    <input type="number" min="0.01" step="0.01" max="999" class="form-control" id="encash_days" name="encash_days" placeholder="${__('enter_days_to_encash_placeholder') || 'Days'}">
                </div>
                <div class="form-group">
                    <label>${__('encashment_salary_label') || 'Encashment Salary'}:</label>
                    <div style="font-weight:600; color:#28a745;" id="encashment_salary_display">0</div>
                </div>
            </div>

            <!-- Fly Type Selection -->
            <div class="vacation-card d-none" id="flyTypeSection">
                <div class="vacation-card-header">
                    <i class="fa fa-tags"></i>
                    ${__('select_vacation_type')}<span class="text-danger">*</span>
                </div>
                <div class="vac-radio-group">
                    <div class="vac-radio-option">
                        <input type="radio" id="vac_type1" value="annual" name="fly_type">
                        <label for="vac_type1" class="vac-radio-label">
                            <i class="fa fa-calendar-check"></i>
                            <span>${__('annual_vacation')}</span>
                        </label>
                    </div>
                    <div class="vac-radio-option">
                        <input type="radio" id="vac_type2" value="emergency" name="fly_type">
                        <label for="vac_type2" class="vac-radio-label">
                            <i class="fa fa-exclamation-triangle"></i>
                            <span>${__('emergency_vacation')}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Date Selection -->
            <div class="vacation-card d-none" id="date_select">
                <div class="vacation-card-header">
                    <i class="fa fa-calendar-alt"></i>
                    ${__('start_date')} & ${__('return_date')}
                </div>
                <div class="date-range-container">
                    <div class="date-field">
                        <label class="form-label-modern">${__('start_date')}<span class="text-danger">*</span></label>
                        <input type="text" name="start_date" placeholder="${__('select_start_date_placeholder')}" class="form-control form-control-modern" id="start_date">
                    </div>
                    <div class="date-field">
                        <label class="form-label-modern">${__('return_date')}<span class="text-danger">*</span></label>
                        <input type="text" name="end_date" placeholder="${__('select_return_date_placeholder')}" class="form-control form-control-modern" id="end_date">
                    </div>
                </div>
                <div id="vacation_days_display" class="d-none" style="margin-top: 15px; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; text-align: center;">
                    <div style="color: white; font-size: 14px; font-weight: 500; margin-bottom: 5px;">
                        <i class="fa fa-calendar-check"></i> ${__('vacation_days') || 'Vacation Days'}
                    </div>
                    <div style="color: white; font-size: 24px; font-weight: 700;" id="vacation_days_count">0</div>
                </div>
            </div>

            <!-- Flight Dates (Departure & Arrival) -->
            <div class="vacation-card d-none" id="flightDatesSection">
                <div class="vacation-card-header">
                    <i class="fa fa-plane"></i>
                    ${__('flight_dates') || 'Flight Dates'}
                </div>
                <div class="date-range-container">
                    <div class="date-field">
                        <label class="form-label-modern">${__('departure_date') || 'Departure Date'}<span class="text-danger">*</span></label>
                        <input type="text" name="departure_date" placeholder="${__('select_departure_date') || 'Select departure date'}" class="form-control form-control-modern" id="departure_date">
                    </div>
                    <div class="date-field">
                        <label class="form-label-modern">${__('arrival_date') || 'Arrival Date'}<span class="text-danger">*</span></label>
                        <input type="text" name="arrival_date" placeholder="${__('select_arrival_date') || 'Select arrival date'}" class="form-control form-control-modern" id="arrival_date">
                    </div>
                </div>
            </div>

            <!-- Replacement Person -->
            <div class="vacation-card d-none" id="replacementSection">
                <div class="vacation-card-header">
                    <i class="fa fa-user-friends"></i>
                    ${__('replacement_person')}<span class="text-danger">*</span>
                </div>
                <select class="form-control form-control-modern" name="replacement_per" id="replacement_per">
                    <option value="">${__('select')}</option>
                </select>
            </div>

            <!-- Notes Section -->
            <div class="vacation-card d-none" id="notesSection">
                <div class="vacation-card-header">
                    <i class="fa fa-sticky-note"></i>
                    ${__('notes')}
                </div>
                <input type="text" name="remarks" class="form-control form-control-modern" id="remarks" autocomplete="off" placeholder="${__('enter_notes_placeholder') || 'Enter additional notes...'}">
            </div>

            <!-- Vacation Salary Type Selection -->
            <div class="vacation-card d-none" id="salaryTypeSection" style="margin-top: 20px;">
                <div class="vacation-card-header">
                    <i class="fa fa-wallet"></i>
                    ${__('vacation_salary_payment')} <span class="text-danger">*</span>
                </div>
                <div class="vac-radio-group">
                    <div class="vac-radio-option">
                        <input type="radio" id="salary_with_payroll" value="payroll" name="vacation_salary_type">
                        <label for="salary_with_payroll" class="vac-radio-label">
                            <i class="fa fa-money-check-alt"></i>
                            <span>${__('yes')}</span>
                        </label>
                    </div>
                    <div class="vac-radio-option">
                        <input type="radio" id="salary_with_eos" value="end_of_service" name="vacation_salary_type">
                        <label for="salary_with_eos" class="vac-radio-label">
                            <i class="fa fa-piggy-bank"></i>
                            <span>${__('no')}</span>
                        </label>
                    </div>
                </div>
                <small class="form-text text-muted" style="margin-top: 10px; display: block; font-size: 12px; color: #858796;">
                    ${__('vacation_salary_type_help')}
                </small>
            </div>

            <input type="hidden" class="cid" name="cid">
        </div>
    </form>`;
    return strView;
}

function generateLeaveFormHTML(employeeGender) {
    // Define all leave types with gender requirements
    // employeeGender: 1 = Male, 2 = Female
    const allLeaveTypes = [
        { value: 'Sick Leave', label: __('sick_leave'), gender: null },
        { value: 'Exam Leave', label: __('exam_leave'), gender: null },
        { value: 'Hajj Leave', label: __('hajj_leave'), gender: null },
        { value: 'Maternity Leave', label: __('maternity_leave'), gender: 2 },
        { value: 'Marriage Leave', label: __('marriage_leave'), gender: null },
        { value: 'Newborn Leave', label: __('newborn_leave'), gender: 1 },
        { value: 'Death Leave', label: __('death_leave'), gender: null },
        { value: 'Business Trip', label: __('business_trip'), gender: null }
    ];

    // Filter leave types based on employee gender
    const leaveTypes = allLeaveTypes.filter(type => 
        type.gender === null || type.gender === employeeGender
    );
    
    let leaveOptions = leaveTypes.map(type => 
        `<option value="${type.value}">${type.label}</option>`
    ).join('');

    return `
        <form id="applyLeaveForm" class="text-left" enctype="multipart/form-data">
            <div class="form-group">
                <label for="leave_type_select">${__('leave_type')} <span class="text-danger">*</span></label>
                <select id="leave_type_select" name="leave_type" class="form-control" style="width: 100%;" required>
                    <option value="" selected disabled>${__('select_leave_type_placeholder')}</option>
                    ${leaveOptions}
                </select>
            </div>

            <!-- Date Section - Always shown for all leave types -->
            <div id="dateSection" class="d-none">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="start_date">${__('start_date')} <span class="text-danger">*</span></label>
                        <input type="text" name="start_date" id="start_date" class="form-control datepicker" placeholder="YYYY-MM-DD" readonly required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="end_date">${__('end_date')} <span class="text-danger">*</span></label>
                        <input type="text" name="end_date" id="end_date" class="form-control datepicker" placeholder="YYYY-MM-DD" readonly required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="total_days">${__('total_days')}</label>
                        <input type="text" name="total_days" id="total_days" class="form-control" placeholder="${__('auto_calculated_placeholder')}" readonly style="cursor: not-allowed; background-color: #e9ecef;">
                    </div>
                </div>
            </div>

            <!-- Trip Destination - Only for Business Trip -->
            <div id="tripSection" class="form-group d-none">
                <label for="trip_destination">${__('destination')} <span class="text-danger">*</span></label>
                <input type="text" name="trip_destination" id="trip_destination" class="form-control" placeholder="${__('destination_placeholder')}" required>
            </div>

            <!-- Accommodation Question - Only for Business Trip -->
            <div id="accommodationSection" class="form-group d-none">
                <label>${__('accommodation_provided')} <span class="text-danger">*</span></label>
                <div class="d-flex" style="gap: 20px;">
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" id="accommodation_yes" name="accommodation_provided" value="yes" required>
                        <label class="custom-control-label" for="accommodation_yes">${__('yes')}</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" id="accommodation_no" name="accommodation_provided" value="no" required>
                        <label class="custom-control-label" for="accommodation_no">${__('no')}</label>
                    </div>
                </div>
            </div>

            <!-- Transportation Question - Only for Business Trip -->
            <div id="transportationSection" class="form-group d-none">
                <label>${__('transportation_provided')} <span class="text-danger">*</span></label>
                <div class="d-flex" style="gap: 20px;">
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" id="transportation_yes" name="transportation_provided" value="yes" required>
                        <label class="custom-control-label" for="transportation_yes">${__('yes')}</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" id="transportation_no" name="transportation_provided" value="no" required>
                        <label class="custom-control-label" for="transportation_no">${__('no')}</label>
                    </div>
                </div>
            </div>
            
            <!-- Reason/Notes - Required for ALL leave types -->
            <div id="reasonSection" class="form-group d-none">
                <label for="reason">${__('reason_notes')} <span class="text-danger">*</span></label>
                <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="${__('reason_placeholder')}" required></textarea>
            </div>

            <!-- Attachment - Required for ALL leave types -->
            <div id="attachmentSection" class="form-group d-none">
                <label for="attachment">${__('attach_document_required')} <span class="text-danger">*</span></label>
                <div id="leaveDropzone" class="dropzone" style="border: 2px dashed #4e73df; border-radius: 8px; padding: 20px; min-height: 150px; background: #f8f9fc; cursor: pointer; transition: all 0.3s ease;">
                    <div class="dz-message" style="margin: 20px 0; text-align: center;">
                        <i class="fa fa-cloud-upload-alt" style="font-size: 48px; color: #4e73df; margin-bottom: 15px; display: block;"></i>
                        <h4 style="margin: 15px 0 10px 0; color: #495057; font-weight: 600;">${__('drag_drop_files') || 'Drag & Drop files here'}</h4>
                        <p style="color: #6c757d; margin: 10px 0; font-size: 14px;">${__('or_click_to_browse') || 'or click to browse'}</p>
                        <small style="color: #858796; display: block; margin-top: 10px; font-size: 12px;">
                            <i class="fa fa-info-circle"></i> ${__('attachment_dropzone_help') || '1-10 files • Max 5MB each • PDF, JPG, PNG'}
                        </small>
                    </div>
                </div>
                <small class="form-text text-muted mt-2" style="display: block; margin-top: 8px;">
                    <i class="fa fa-info-circle"></i> ${__('attachment_multiple_help') || 'You can upload 1-10 files. Each file must be less than 5MB. Accepted formats: PDF, JPG, PNG'}
                </small>
                <style>
                    #leaveDropzone:hover {
                        border-color: #2e59d9;
                        background: #eef2ff;
                    }
                    #leaveDropzone .dz-preview {
                        margin: 10px;
                    }
                    #leaveDropzone .dz-preview .dz-image {
                        border-radius: 8px;
                    }
                    #leaveDropzone .dz-preview .dz-details {
                        background: #fff;
                        padding: 8px;
                        border-radius: 4px;
                    }
                    #leaveDropzone .dz-preview .dz-remove {
                        color: #e74a3b;
                        font-size: 12px;
                        text-decoration: none;
                        cursor: pointer;
                    }
                    #leaveDropzone .dz-preview .dz-remove:hover {
                        color: #c9302c;
                        text-decoration: underline;
                    }
                    #leaveDropzone.dz-drag-hover {
                        border-color: #2e59d9;
                        background: #e3f2fd;
                    }
                </style>
            </div>
        </form>
    `;
}



/**
 * Toggles the visibility of form fields based on the selected leave type.
 */
/**
 * Toggles the visibility of form fields based on the selected leave type.
 * ALL leave types now require: dates, reason/notes, and attachment
 */
function toggleLeaveFields() {
    const selectedType = $('#leave_type_select').val();
    
    // Hide all sections first
    $('#dateSection, #reasonSection, #attachmentSection, #tripSection, #accommodationSection, #transportationSection').addClass('d-none');
    calculateTotalDays();

    if (!selectedType) return;

    // ALL leave types show: dates, reason, and attachment
    $('#dateSection, #reasonSection, #attachmentSection').removeClass('d-none');
    
    // Business Trip also needs destination, accommodation, and transportation
    if (selectedType === 'Business Trip') {
        $('#tripSection, #accommodationSection, #transportationSection').removeClass('d-none');
    }
}

function calculateTotalDays() {
    const startDateStr = $('#start_date').val();
    const endDateStr = $('#end_date').val();
    
    if (startDateStr && endDateStr) {
        const startDate = new Date(startDateStr);
        const endDate = new Date(endDateStr);

        if (endDate >= startDate) {
            // Calculate the difference in time (milliseconds) and convert to days
            const timeDiff = endDate.getTime() - startDate.getTime();
            const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
            $('#total_days').val(dayDiff + (dayDiff > 1 ? __('days_suffix') : __('day_suffix')));
        } else {
            $('#total_days').val(''); // Clear if end date is before start date
        }
    } else if (startDateStr && $('#leave_type_select').val() === 'Compensatory Leave') {
            $('#total_days').val('1' + __('day_suffix'));
    } else {
        $('#total_days').val(''); // Clear if one or both dates are missing
    }
}


////////////////////////////////////////////////////////////////////
////////////      Start Rejoin Request Handling       //////////////
////////////////////////////////////////////////////////////////////

/**
 * Handle rejoin request submission
 * Prevents duplicate active requests and shows active request status
 */
$(document).on('click', '.submitRejoinRequest', function(e) {
    e.preventDefault();
    
    const vacationId = $(this).data('vacation-id');
    const empId = $(this).data('emp-id');
    
    if (!vacationId || !empId) {
        Swal.fire({
            icon: 'error',
            title: __('error'),
            text: __('required_information_missing'),
            confirmButtonText: __('ok')
        });
        return;
    }

    // First, check if there's an active rejoin request
    $.ajax({
        url: './includes/ajaxFile/ajaxVacation.php',
        type: 'POST',
        dataType: 'JSON',
        data: {
            ajaxType: 'checkActiveRejoinRequest',
            vacation_id: vacationId,
            emp_id: empId
        },
        success: function(checkResponse) {
            // If there's an active request, show warning immediately
            if(checkResponse.type === 'warning' && checkResponse.active_request) {
                Swal.fire({
                    icon: 'warning',
                    title: checkResponse.title || __('active_rejoin_request_exists'),
                    html: `
                        <div style="background-color: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; text-align: left; margin-top: 15px; border-radius: 4px;">
                            <div style="margin-bottom: 10px;">
                                <strong>${__('request_number')}:</strong> 
                                <span style="color: #d32f2f;">${checkResponse.active_request.request_inv_no}</span>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>${__('status')}:</strong> 
                                <span class="status" style="color: #d32f2f; font-weight: bold;">${checkResponse.active_request.status.toUpperCase()}</span>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>${__('requested_rejoin_date')}:</strong> 
                                <span>${checkResponse.active_request.requested_rejoin_date}</span>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>${__('submitted_at')}:</strong> 
                                <span>${checkResponse.active_request.requested_at}</span>
                            </div>
                            <hr style="margin: 10px 0;">
                            <div style="margin-bottom: 10px;">
                                <strong>${__('associated_vacation')}:</strong> 
                                <span>${checkResponse.active_request.vacation_inv_no}</span>
                            </div>
                            <div>
                                <strong>${__('vacation_type')}:</strong> 
                                <span class="vacType">${checkResponse.active_request.vac_type}</span>
                            </div>
                        </div>
                    `,
                    didOpen: () => {
                        var vacType = checkResponse.active_request.vac_type;
                        var statusCkh = checkResponse.active_request.status.toUpperCase();
                        var currentLang = getCurrentLanguage();
                        if (vacType && currentLang === 'ar') {
                            translateName(vacType, 'en', 'ar', function(translated) {
                                const vacTypeEl = document.querySelector('.vacType');
                                if (vacTypeEl) vacTypeEl.textContent = translated;
                            });
                        }
                        if (statusCkh && currentLang === 'ar') {
                            translateName(statusCkh, 'en', 'ar', function(translated) {
                                const statusEl = document.querySelector('.status');
                                if (statusEl) statusEl.textContent = translated;
                            });
                        }
                    },
                    allowOutsideClick: false,
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: __('ok')
                });
                return;
            }
            
            // No active request - show the confirmation dialog
            Swal.fire({
                icon: 'question',
                title: '<i class="fa fa-redo-alt"></i> ' + __('rejoin_request'),
                html: `
                    <div style="text-align: left; padding: 20px 0;">
                        <p style="font-size: 16px; margin-bottom: 15px;">
                            ${__('are_you_sure_rejoin')}
                        </p>
                        <div style="background-color: #e8f4f8; border-left: 4px solid #17a2b8; padding: 12px; border-radius: 4px;">
                            <p style="margin: 0; color: #555;">
                                <strong>${__('this_action_will_submit_rejoin_request')}</strong>
                            </p>
                        </div>
                    </div>
                `,
                width: '450px',
                showCancelButton: true,
                confirmButtonText: '<i class="fa fa-check"></i> ' + __('confirm'),
                cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel'),
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                allowOutsideClick: false,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return { rejoinDate: null, rejoinReason: '' };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading dialog while sending email
                    Swal.fire({
                        title: __('sending_request'),
                        html: `
                            <div style="text-align: center; padding: 30px 0;">
                                <div style="margin-bottom: 20px;">
                                    <i class="fa fa-envelope" style="font-size: 48px; color: #4e73df; animation: pulse 1.5s infinite;"></i>
                                </div>
                                <p style="font-size: 16px; color: #555; margin-bottom: 10px;">
                                    ${__('sending_email_to_supervisor')}
                                </p>
                                <div style="position: relative; width: 200px; height: 4px; background: #e9ecef; border-radius: 2px; margin: 20px auto; overflow: hidden;">
                                    <div style="position: absolute; top: 0; left: 0; height: 100%; width: 100%; background: linear-gradient(90deg, #4e73df, #224abe); animation: slideLoader 1.5s infinite;"></div>
                                </div>
                                <small style="color: #858796; display: block; margin-top: 15px;">
                                    ${__('please_wait')}
                                </small>
                            </div>
                            <style>
                                @keyframes pulse {
                                    0%, 100% { opacity: 1; }
                                    50% { opacity: 0.5; }
                                }
                                @keyframes slideLoader {
                                    0% { transform: translateX(-100%); }
                                    100% { transform: translateX(100%); }
                                }
                            </style>
                        `,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            // Submit rejoin request via AJAX (without date field)
                            $.ajax({
                                url: './includes/ajaxFile/ajaxVacation.php',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    ajaxType: 'submitRejoinRequest',
                                    vacation_id: vacationId,
                                    emp_id: empId,
                                    rejoin_date: null,
                                    rejoin_reason: ''
                                },
                                success: function(response) {
                                    if (response.type === 'success') {
                                        // Success response
                                        Swal.fire({
                                            icon: 'success',
                                            title: response.title || __('success'),
                                            text: response.message,
                                            confirmButtonText: __('ok'),
                                            allowOutsideClick: false
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        // Error response
                                        Swal.fire({
                                            icon: response.type || 'error',
                                            title: response.title || __('error'),
                                            text: response.message,
                                            confirmButtonText: __('ok'),
                                            allowOutsideClick: false
                                        });
                                    }
                                },
                                error: function(jqXHR, textStatus, errorThrown) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: __('error'),
                                        text: __('request_failed_please_try_again'),
                                        confirmButtonText: __('ok'),
                                        allowOutsideClick: false
                                    });
                                }
                            });
                        }
                    });
                }
            });
        },
        error: function(jqXHR, textStatus) {
            Swal.fire({
                icon: 'error',
                title: __('error'),
                text: __('request_failed_status') + ' - ' + textStatus,
                confirmButtonColor: '#d32f2f',
                confirmButtonText: __('ok')
            });
        }
    });
});

////////////////////////////////////////////////////////////////////
////////////       End Rejoin Request Handling        //////////////
///////////////////////////////////////////////////////////////////;