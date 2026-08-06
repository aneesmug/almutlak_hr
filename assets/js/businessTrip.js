/**
 * Business Trip Request Management
 * 
 * This module handles all Business Trip request functionality:
 * - Form generation with dynamic field visibility
 * - Modal presentation and validation
 * - AJAX submission and approval workflow
 * - Support for both domestic and international trips
 * 
 * Dependencies:
 *  - jQuery
 *  - SweetAlert2 (Swal)
 *  - jquery.datepicker
 *  - translation.js (for __ function)
 *  - APP_COLORS (global)
 * 
 * Usage:
 *  openBusinessTripApplyModal(empid, deptId, country)
 */

/**
 * Business Trip Request Form HTML
 * Generates the complete form with all required fields for business trip requests
 * Supports both domestic and international trip types
 */
function businessTripForm_HTML() {
    var strView = `<style>
        .trip-days-readonly {
            background-color: #f0f0f0;
        }
    </style>
    
    <form id="submitBusinessTripForm" enctype="multipart/form-data">
        <div class="vacation-form-container">
            
            <!-- Employee Information -->
            <div class="info-row">
                <div class="info-field flex-2">
                    <label>${__('employee_name')}</label>
                    <input type="text" name="name" id="bt_name" readonly>
                </div>
                <div class="info-field flex-1">
                    <label>${__('employee_id')}</label>
                    <input type="text" name="empid" id="bt_empid" readonly>
                </div>
            </div>

            <!-- Trip Type Selection -->
            <div class="vacation-card">
                <div class="vacation-card-header">
                    <i class="fa fa-plane"></i>
                    ${__('trip_type') || 'Trip Type'}<span class="text-danger">*</span>
                </div>
                <div class="vac-radio-group">
                    <div class="vac-radio-option">
                        <input type="radio" id="bt_trip_domestic" name="trip_type" value="domestic" checked>
                        <label for="bt_trip_domestic" class="vac-radio-label">
                            <i class="fa fa-map-marker-alt"></i>
                            <span>${__('domestic_trip') || 'Domestic Trip'}</span>
                        </label>
                    </div>
                    <div class="vac-radio-option">
                        <input type="radio" id="bt_trip_international" name="trip_type" value="international">
                        <label for="bt_trip_international" class="vac-radio-label">
                            <i class="fa fa-globe"></i>
                            <span>${__('international_trip') || 'International Trip'}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Domestic Trip Options -->
            <div id="domesticTripSection" class="vacation-card">
                <div class="vacation-card-header">
                    <i class="fa fa-car"></i>
                    ${__('transportation_type') || 'Transportation Type'}<span class="text-danger">*</span>
                </div>
                <div class="vac-radio-group">
                    <div class="vac-radio-option">
                        <input type="radio" id="bt_transport_own" name="transportation_type" value="own_car" checked>
                        <label for="bt_transport_own" class="vac-radio-label">
                            <i class="fa fa-car"></i>
                            <span>${__('own_car') || 'Own Car'}</span>
                        </label>
                    </div>
                    <div class="vac-radio-option">
                        <input type="radio" id="bt_transport_rental" name="transportation_type" value="rental">
                        <label for="bt_transport_rental" class="vac-radio-label">
                            <i class="fa fa-taxi"></i>
                            <span>${__('rental_car') || 'Rental Car'}</span>
                        </label>
                    </div>
                    <div class="vac-radio-option">
                        <input type="radio" id="bt_transport_air" name="transportation_type" value="by_air">
                        <label for="bt_transport_air" class="vac-radio-label">
                            <i class="fa fa-plane"></i>
                            <span>${__('by_air') || 'By Air'}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Trip Purpose -->
            <div class="vacation-card">
                <div class="vacation-card-header">
                    <i class="fa fa-pencil"></i>
                    ${__('trip_purpose') || 'Trip Purpose'}<span class="text-danger">*</span>
                </div>
                <textarea name="trip_purpose" id="trip_purpose" class="form-control" rows="3" placeholder="${__('enter_trip_purpose') || 'Enter trip purpose and objectives'}" required></textarea>
            </div>

            <!-- Trip Dates -->
            <div class="vacation-card">
                <div class="vacation-card-header">
                    <i class="fa fa-calendar"></i>
                    ${__('trip_dates')}<span class="text-danger">*</span>
                </div>
                <div class="info-row">
                    <div class="info-field">
                        <label class="form-label">${__('trip_dates') || 'Trip Dates'}</label>
                        <input type="text" name="trip_date_range" id="trip_date_range" class="form-control" placeholder="Select date range" required>
                    </div>
                    <div class="info-field">
                        <label class="form-label">${__('trip_days') || 'Trip Days'}</label>
                        <input type="text" id="trip_days_count" class="form-control trip-days-readonly" readonly>
                    </div>
                </div>
            </div>

            <!-- Travel Route -->
            <div id="citiesSection" class="vacation-card">
                <div class="vacation-card-header">
                    <i class="fa fa-map-marker"></i>
                    ${__('travel_route')}<span class="text-danger">*</span>
                </div>
                <div class="info-row">
                    <div class="info-field flex-1">
                        <label class="form-label">${__('from')}</label>
                        <select name="from_city_id" id="from_city_id" class="form-control" required>
                            <option value="">${__('select_from_city') || 'Select departure city'}</option>
                            <option value="2">Jeddah</option>
                            <option value="1">Riyadh</option>
                            <option value="7">Dammam</option>
                        </select>
                    </div>
                    <div class="info-field flex-1">
                        <label class="form-label">${__('to')}</label>
                        <select name="to_city_id" id="to_city_id" class="form-control" required>
                            <option value="">${__('select_to_city') || 'Select destination city'}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- International Trip Details -->
            <div id="internationalTripSection" class="vacation-card d-none">
                <div class="vacation-card-header">
                    <i class="fa fa-globe"></i>
                    ${__('international_details') || 'International Trip Details'}<span class="text-danger">*</span>
                </div>
                <div id="passportStatusMsg" class="alert alert-info" style="margin-bottom: 12px; padding: 10px 12px; font-size: 13px;">${__('passport_check_will_run_for_international_trip') || 'Passport check will run for international trip.'}</div>
                <div id="passportUploadSection" class="form-group d-none" style="margin-bottom: 12px;">
                    <label class="form-label" for="passport_file">${__('passport_attachment') || 'Passport Attachment'} <span class="text-danger">*</span></label>
                    <input type="file" name="passport_file" id="passport_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf,application/pdf,image/jpeg,image/png">
                    <small class="text-muted">${__('upload_passport_as_jpg_or_pdf') || 'Upload passport file as JPG, PNG or PDF'}</small>
                </div>
                <div style="margin-top: 10px;">
                    <label><input type="checkbox" name="visa_required" id="visa_required"> ${__('visa_required') || 'Visa Required'}</label>
                </div>
            </div>

            <!-- Additional Notes -->
            <div class="vacation-card">
                <div class="vacation-card-header">
                    <i class="fa fa-sticky-note"></i>
                    ${__('additional_notes') || 'Additional Notes'}
                </div>
                <textarea name="request_notes" id="request_notes" class="form-control" rows="3" placeholder="${__('enter_any_special_requirements') || 'Enter any special requirements or notes'}"></textarea>
            </div>

            <input type="hidden" name="emp_id" id="bt_emp_id">
            <input type="hidden" name="first_approver_id" id="bt_first_approver_id">
        </div>
    </form>`;
    return strView;
}

/**
 * Open Business Trip Apply Modal
 * Presents a SweetAlert2 modal for business trip request submission
 * 
 * @param {number} empid - Employee ID making the request
 * @param {number} deptId - Department ID
 * @param {string} country - Country (for context)
 */
function openBusinessTripApplyModal(empid, deptId, country) {
    const normalizeStatus = (statusValue) => String(statusValue || '').toLowerCase().replace(/_/g, ' ').trim();
    const toTitle = (value) => String(value || '').replace(/\b\w/g, ch => ch.toUpperCase());
    const getStatusMeta = (statusValue) => {
        const normalized = normalizeStatus(statusValue);

        if (normalized.includes('approved')) {
            return { icon: 'fa-check-square', cls: 'text-success', label: __('approved', 'Approved') };
        }
        if (normalized.includes('pending')) {
            return { icon: 'fa-hourglass-half', cls: 'text-warning', label: __('pending', 'Pending') };
        }
        if (normalized.includes('awaiting')) {
            return { icon: 'fa-pause-circle', cls: 'text-info', label: __('awaiting', 'Awaiting') };
        }
        if (normalized.includes('rejected')) {
            return { icon: 'fa-times-circle', cls: 'text-danger', label: __('rejected', 'Rejected') };
        }

        return { icon: 'fa-circle', cls: 'text-secondary', label: toTitle(normalized || __('unknown', 'Unknown')) };
    };

    const buildActiveRequestHtml = (res) => {
        const req = (res && res.existing_request) ? res.existing_request : null;
        const chain = (res && Array.isArray(res.approval_chain)) ? res.approval_chain : [];

        if (!req) {
            return res && res.html ? res.html : ('<div class="vacation-form-container"><div class="vacation-card"><div class="vacation-card-header"><i class="fa fa-info-circle"></i>' + __('applied_information', 'Applied Information') + '</div><div style="padding:8px 0;">' + ((res && res.message)) + '</div></div></div>');
        }

        const isInternational = String(req.trip_type || '').toLowerCase() === 'international';
        const fromRouteName = (req.from_city_route_name && String(req.from_city_route_name).trim() !== '') ? String(req.from_city_route_name).trim() : ('#' + (req.from_city_id || '-'));
        const toRouteName = (req.to_city_route_name && String(req.to_city_route_name).trim() !== '') ? String(req.to_city_route_name).trim() : ('#' + (req.to_city_id || '-'));
        const routeText = isInternational
            ? (__('destination_country', 'Destination Country') + ': ' + (req.destination_country || '-'))
            : (__('route', 'Route') + ': ' + fromRouteName + ' -> ' + toRouteName);

        let chainHtml = '<div style="padding:8px 0; color:#6c757d;">' + __('no_data_found', 'No data found') + '</div>';
        if (chain.length > 0) {
            chainHtml = '<ul style="margin:0; padding-left:0; list-style:none;">';
            chain.forEach(c => {
                const approverName = (c.approver_name && String(c.approver_name).trim() !== '') ? c.approver_name : ('Emp#' + (c.approver_id || '-'));
                const level = c.level || c.approval_level || '-';
                const statusMeta = getStatusMeta(c.status);
                chainHtml += '<li style="margin:2px 0;"><i class="fa ' + statusMeta.icon + ' ' + statusMeta.cls + '" style="margin-right:6px;"></i>(' + __('level', 'Level') + ' ' + level + '): ' + approverName + ' — ' + statusMeta.label + '</li>';
            });
            chainHtml += '</ul>';
        }

        return `
            <div class="vacation-form-container">
                <div class="vacation-card">
                    <div class="vacation-card-header">
                        <i class="fa fa-info-circle"></i>
                        ${__('active_request_exists')}
                    </div>
                    <div style="padding:8px 0;">
                        ${__('you_already_have_active_business_trip', 'You already have an active business trip request. New request is not allowed until current one is completed.')}
                    </div>
                </div>

                <div class="vacation-card">
                    <div class="vacation-card-header">
                        <i class="fa fa-file-alt"></i>
                        ${__('applied_information', 'Applied Information')}
                    </div>
                    <ul style="margin:0; padding-left:20px;">
                        <li>${__('request_id', 'Request ID')}: ${req.request_inv_no || '-'}</li>
                        <li>${__('status', 'Status')}: ${__(req.current_status) || '-'}</li>
                        <li>${__('trip_type', 'Trip Type')}: ${__(req.trip_type) || '-'}</li>
                        <li>${__('trip_dates', 'Trip Dates')}: ${(req.trip_start_date || '-')} ${__('to', 'to')} ${(req.trip_end_date || '-')}</li>
                        <li>${routeText}</li>
                    </ul>
                </div>

                <div class="vacation-card">
                    <div class="vacation-card-header">
                        <i class="fa fa-sitemap"></i>
                        ${__('request_chain', 'Request Chain')}
                    </div>
                    ${chainHtml}
                </div>
            </div>
        `;
    };

    const showActiveRequestModal = (res) => {
        const modalWidth = (window.innerWidth && window.innerWidth < 768) ? '95%' : '56rem';
        Swal.fire({
            title: '<i class="fa fa-plane" style="margin-right: 8px;"></i> ' + (res.title || __('active_request_exists')),
            html: buildActiveRequestHtml(res),
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonColor: APP_COLORS.danger_dark,
            cancelButtonText: '<i class="fa fa-times"></i> ' + (__('close', 'Close')),
            allowOutsideClick: false,
            width: modalWidth,
            padding: '20px',
            scrollbarPadding: false,
            customClass: {
                popup: 'vacation-modal-popup',
                title: 'vacation-modal-title',
                confirmButton: 'btn-modern-confirm',
                cancelButton: 'btn-modern-cancel'
            }
        });
    };

    let passportEligibility = {
        passport_valid: true,
        has_attachment: true,
        requires_upload: false,
        message: ''
    };

    const openApplyTripFormModal = () => Swal.fire({
        title: '<i class="fa fa-plane" style="margin-right: 8px;"></i> ' + (__('apply_business_trip') || 'Apply Business Trip Request'),
        html: businessTripForm_HTML(),
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        confirmButtonText: '<i class="fa fa-check"></i> ' + (__('yes_register') || 'Register'),
        cancelButtonText: '<i class="fa fa-times"></i> ' + (__('cancel') || 'Cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '95%',
        padding: '20px',
        scrollbarPadding: false,
        customClass: {
            popup: 'vacation-modal-popup',
            title: 'vacation-modal-title',
            confirmButton: 'btn-modern-confirm',
            cancelButton: 'btn-modern-cancel'
        },
        willOpen: () => {
            
            const isRTL = (typeof is_rtl !== 'undefined' && is_rtl);
            let saudiCities = [];
            let countriesList = [];

            const getSelect2Parent = () => {
                const popup = Swal.getPopup();
                return popup ? $(popup) : $(document.body);
            };

            const initRouteSelect2 = () => {
                const $from = $('#from_city_id');
                const $to = $('#to_city_id');
                const parent = getSelect2Parent();

                if ($from.hasClass('select2-hidden-accessible')) {
                    $from.select2('destroy');
                }
                if ($to.hasClass('select2-hidden-accessible')) {
                    $to.select2('destroy');
                }

                $from.select2({
                    placeholder: __('select_from_city') || 'Select departure city',
                    width: '100%',
                    dropdownParent: parent
                });

                $to.select2({
                    placeholder: __('select_to_city') || 'Select destination city',
                    width: '100%',
                    dropdownParent: parent
                });
            };

            const renderToOptions = (options, placeholderText) => {
                const $to = $('#to_city_id');
                let html = '<option value="">' + placeholderText + '</option>';

                options.forEach(item => {
                    html += '<option value="' + item.id + '">' + item.name + '</option>';
                });

                $to.html(html).val('');
                initRouteSelect2();
            };

            const applyTripRouteOptions = (tripType) => {
                if (tripType === 'international') {
                    renderToOptions(
                        countriesList,
                        __('select_destination_country') || __('select_to_country') || 'Select destination country'
                    );
                } else {
                    renderToOptions(saudiCities, __('select_to_city') || 'Select destination city');
                }
            };

            const checkPassportEligibility = () => {
                const $msg = $('#passportStatusMsg');
                const $upload = $('#passportUploadSection');

                $msg.removeClass('alert-warning alert-danger alert-success').addClass('alert-info');
                $msg.text(__('checking_passport_eligibility') || 'Checking passport eligibility...');
                $upload.addClass('d-none');

                $.ajax({
                    url: './includes/ajaxFile/ajaxBusinessTrip.php',
                    dataType: 'JSON',
                    type: 'POST',
                    data: { ajaxType: 'checkPassportEligibility', emp_id: empid },
                    success: function(res) {
                        if (res && res.status === 'success') {
                            passportEligibility = {
                                passport_valid: !!res.passport_valid,
                                has_attachment: !!res.has_attachment,
                                requires_upload: !!res.requires_upload,
                                message: res.message || ''
                            };

                            $msg.removeClass('alert-info alert-warning alert-danger alert-success');

                            if (!passportEligibility.passport_valid) {
                                $msg.addClass('alert-danger');
                            } else if (passportEligibility.requires_upload) {
                                $msg.addClass('alert-warning');
                            } else {
                                $msg.addClass('alert-success');
                            }

                            $msg.text(passportEligibility.message || '');

                            if (passportEligibility.requires_upload) {
                                $upload.removeClass('d-none');
                            } else {
                                $upload.addClass('d-none');
                                $('#passport_file').val('');
                            }
                        } else {
                            passportEligibility = {
                                passport_valid: false,
                                has_attachment: false,
                                requires_upload: true,
                                message: (res && res.message) ? res.message : 'Unable to check passport eligibility.'
                            };

                            $msg.removeClass('alert-info alert-warning alert-success').addClass('alert-danger');
                            $msg.text(passportEligibility.message);
                            $upload.removeClass('d-none');
                        }
                    },
                    error: function() {
                        passportEligibility = {
                            passport_valid: false,
                            has_attachment: false,
                            requires_upload: true,
                            message: 'Unable to check passport eligibility right now. Please upload passport file and try again.'
                        };

                        $msg.removeClass('alert-info alert-warning alert-success').addClass('alert-danger');
                        $msg.text(passportEligibility.message);
                        $upload.removeClass('d-none');
                    }
                });
            };

            // Load employee info - using same approach as vacation form
            $.ajax({
                url: './includes/ajaxFile/hrHandler.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "emp_data", empid: empid},
                success: function(res) {
                    if (res.status == 200 && res.data && res.data.length > 0) {
                        $('input[name="name"]').val(res.data[0].name);
                        $('input[name="empid"]').val(res.data[0].emp_id);
                    }
                },
                error: function(j, e) {
                    console.log('Error loading employee info:', j, e);
                }
            });

            // Load cities (Saudi)
            $.ajax({
                url: './includes/ajaxFile/ajaxBusinessTrip.php',
                dataType: 'JSON',
                type: 'POST',
                data: { ajaxType: "getSaudiCities", is_rtl: isRTL },
                success: function(res) {
                    if (res.status === 'success' && res.cities) {
                        saudiCities = res.cities;
                    }
                    applyTripRouteOptions($('input[name="trip_type"]:checked').val() || 'domestic');
                }
            });

            // Load countries for international destination
            $.ajax({
                url: './includes/ajaxFile/ajaxBusinessTrip.php',
                dataType: 'JSON',
                type: 'POST',
                data: { ajaxType: 'getCountries', is_rtl: isRTL },
                success: function(res) {
                    if (res.status === 'success' && res.countries) {
                        countriesList = res.countries;
                    }
                    applyTripRouteOptions($('input[name="trip_type"]:checked').val() || 'domestic');
                }
            });

            // Trip type change handler
            $('input[name="trip_type"]').on('change', function() {
                if ($(this).val() === 'domestic') {
                    $('#domesticTripSection').removeClass('d-none').show();
                    $('#citiesSection').removeClass('d-none').show();
                    $('#internationalTripSection').addClass('d-none').hide();
                    $('#from_city_id, #to_city_id').prop('required', true);
                    applyTripRouteOptions('domestic');
                } else {
                    $('#domesticTripSection').addClass('d-none').hide();
                    $('#citiesSection').removeClass('d-none').show();
                    $('#internationalTripSection').removeClass('d-none').show();
                    $('#from_city_id, #to_city_id').prop('required', true);
                    $('#passportStatusMsg').removeClass('d-none');
                    applyTripRouteOptions('international');
                    checkPassportEligibility();
                }
            });

            // Get direct supervisor
            $.ajax({
                url: './includes/ajaxFile/hrHandler.php',
                dataType: 'JSON',
                type: 'POST',
                data: { ajaxType: 'get_direct_supervisor', emp_id: empid },
                success: function(res) {
                    if (res && res.supervisor_id) {
                        $('#bt_first_approver_id').val(res.supervisor_id);
                    }
                }
            });

            $('#bt_emp_id').val(empid);

            if ($('input[name="trip_type"]:checked').val() === 'international') {
                checkPassportEligibility();
            }
        },
        didOpen: () => {
            const $tripDateRange = $('#trip_date_range');
            if ($tripDateRange.length === 0 || typeof $.fn.daterangepicker !== 'function') {
                return;
            }

            if ($tripDateRange.data('daterangepicker')) {
                $tripDateRange.data('daterangepicker').remove();
            }

            const defaultDate = moment().add(1, 'day').format('MM/DD/YYYY');
            $tripDateRange.daterangepicker({
                locale: {
                    format: 'MM/DD/YYYY'
                },
                startDate: defaultDate,
                endDate: defaultDate,
                autoUpdateInput: true
            });

            const picker = $tripDateRange.data('daterangepicker');
            if (picker) {
                const initialDays = picker.endDate.diff(picker.startDate, 'days') + 1;
                $('#trip_days_count').val(initialDays + ' days');
            }

            $tripDateRange
                .off('apply.daterangepicker.businessTrip')
                .on('apply.daterangepicker.businessTrip', function(ev, selectedPicker) {
                    const daysCount = selectedPicker.endDate.diff(selectedPicker.startDate, 'days') + 1;
                    $('#trip_days_count').val(daysCount + ' days');
                });
        },
        preConfirm: () => {
            const trip_type = $('input[name="trip_type"]:checked').val();
            const trip_purpose = $('#trip_purpose').val();
            const trip_date_range = $('#trip_date_range').val();
            
            if (!trip_purpose || !trip_date_range) {
                Swal.showValidationMessage(`${__('please_fill_all_required_fields') || 'Please fill in all required fields'}`);
                return false;
            }

            // Parse date range (format: "MM/DD/YYYY - MM/DD/YYYY")
            const dates = trip_date_range.split(' - ');
            if (dates.length !== 2) {
                Swal.showValidationMessage(`${__('invalid_date_range') || 'Invalid date range format'}`);
                return false;
            }

            let startDate = moment(dates[0].trim(), 'MM/DD/YYYY');
            let endDate = moment(dates[1].trim(), 'MM/DD/YYYY');

            if (!startDate.isValid() || !endDate.isValid()) {
                Swal.showValidationMessage(`${__('invalid_dates') || 'Invalid dates'}`);
                return false;
            }

            if (startDate > endDate) {
                Swal.showValidationMessage(`${__('end_date_must_be_after_start_date') || 'End date must be after or equal to start date'}`);
                return false;
            }

            if (trip_type === 'domestic') {
                const from_city = $('#from_city_id').val();
                const to_city = $('#to_city_id').val();
                if (!from_city || !to_city) {
                    Swal.showValidationMessage(`${__('select_departure_and_destination_cities') || 'Please select departure and destination cities'}`);
                    return false;
                }
                if (from_city === to_city) {
                    Swal.showValidationMessage(`${__('departure_and_destination_must_be_different') || 'Departure city and destination must be different'}`);
                    return false;
                }
            } else if (trip_type === 'international') {
                const from_city = $('#from_city_id').val();
                const destination_country_id = $('#to_city_id').val();
                if (!from_city || !destination_country_id) {
                    Swal.showValidationMessage(`${__('please_select_departure_and_destination') || 'Please select departure and destination'}`);
                    return false;
                }

                if (!passportEligibility.passport_valid) {
                    Swal.showValidationMessage(passportEligibility.message || (__('passport_must_be_valid_for_3_months') || 'Passport must be valid for more than 3 months for international trip.'));
                    return false;
                }

                if (passportEligibility.requires_upload) {
                    const passportFileInput = document.getElementById('passport_file');
                    if (!passportFileInput || !passportFileInput.files || passportFileInput.files.length === 0) {
                        Swal.showValidationMessage(`${__('passport_attachment_required') || 'Passport attachment is required. Please upload JPG/PNG/PDF file.'}`);
                        return false;
                    }

                    const fileName = passportFileInput.files[0].name || '';
                    const ext = fileName.split('.').pop().toLowerCase();
                    if (!['jpg', 'jpeg', 'png', 'pdf'].includes(ext)) {
                        Swal.showValidationMessage(`${__('invalid_passport_file_type') || 'Invalid passport file type. Allowed: JPG, PNG, PDF.'}`);
                        return false;
                    }
                }
            }

            // Manually create FormData with collected field values
            const formData = new FormData();
            formData.append("ajaxType", "submitBusinessTrip");
            formData.append('is_rtl', (typeof is_rtl !== 'undefined' && is_rtl) ? 'true' : 'false');

            // Collect employee info
            formData.append("emp_id", $('#bt_emp_id').val());
            formData.append("name", $('input[name="name"]').val());
            formData.append("empid", $('input[name="empid"]').val());
            formData.append("first_approver_id", $('#bt_first_approver_id').val());

            // Convert parsed moment dates to YYYY-MM-DD format for backend compatibility
            const startDateFormatted = startDate.format('YYYY-MM-DD');
            const endDateFormatted = endDate.format('YYYY-MM-DD');
            
            formData.append('trip_start_date', startDateFormatted);
            formData.append('trip_end_date', endDateFormatted);

            // Collect trip details
            formData.append('trip_type', trip_type);
            formData.append('trip_purpose', trip_purpose);

            // Collect location/country data
            if (trip_type === 'domestic') {
                formData.append('from_city_id', $('#from_city_id').val());
                formData.append('to_city_id', $('#to_city_id').val());
                formData.append('transportation_type', $('input[name="transportation_type"]:checked').val());
            } else {
                formData.append('from_city_id', $('#from_city_id').val());
                formData.append('to_city_id', $('#to_city_id').val());
                formData.append('destination_country', $('#to_city_id option:selected').text());
                formData.append('visa_required', $('#visa_required').is(':checked') ? 1 : 0);

                const passportFileInput = document.getElementById('passport_file');
                if (passportFileInput && passportFileInput.files && passportFileInput.files.length > 0) {
                    formData.append('passport_file', passportFileInput.files[0]);
                }
            }

            // Collect additional notes
            formData.append('request_notes', $('#request_notes').val());

            return new Promise((resolve, reject) => {
                $.ajax({
                    url: './includes/ajaxFile/ajaxBusinessTrip.php',
                    type: 'POST',
                    dataType: 'JSON',
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: formData,
                })
                .done(function(response) {
                    resolve(response);
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject({
                        status: 'error',
                        title: 'Submit Failed',
                        message: 'Error: ' + errorThrown,
                        type: 'error'
                    });
                });
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            if (result.value.existing_request) {
                showActiveRequestModal(result.value);
                return;
            }
            const hasHtml = !!result.value.html;
            Swal.fire({
                title: result.value.title || 'Success',
                text: hasHtml ? undefined : result.value.message,
                html: hasHtml ? result.value.html : undefined,
                icon: result.value.type || 'success',
                allowOutsideClick: false,
                confirmButtonColor: APP_COLORS.primary
            }).then(() => {
                if (result.value.type === 'success') {
                    location.reload();
                }
            });
        }
    });

    // Duplicate active-request check disabled intentionally.
    // Backend submitBusinessTrip already enforces the active request guard.
    // If you want pre-open blocking UX again, uncomment the AJAX block below.
    openApplyTripFormModal();

    /*
    $.ajax({
        url: './includes/ajaxFile/ajaxBusinessTrip.php',
        dataType: 'JSON',
        type: 'POST',
        data: {
            ajaxType: 'checkActiveBusinessTrip',
            emp_id: empid,
            is_rtl: (typeof is_rtl !== 'undefined' && is_rtl) ? 'true' : 'false'
        },
        success: function(res) {
            if (res && res.status === 'success' && res.has_active_request) {
                showActiveRequestModal(res);
                return;
            }

            openApplyTripFormModal();
        },
        error: function() {
            openApplyTripFormModal();
        }
    });
    */
}

function cancelBusinessTripRequest(tripInvNo) {
    Swal.fire({
        title: __('cancel_business_trip_request') || 'Cancel Business Trip Request',
        html: `<p>${__('are_you_sure_cancel_request') || 'Are you sure you want to cancel this request?'}</p><strong>${tripInvNo}</strong>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: __('yes_cancel_request') || 'Yes, Cancel It',
        cancelButtonText: __('keep_request') || 'No',
        allowOutsideClick: false
    }).then((result) => {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: __('cancelling_request') || 'Cancelling...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: './includes/ajaxFile/ajaxBusinessTrip.php',
            type: 'POST',
            dataType: 'JSON',
            data: { ajaxType: 'cancelBusinessTripSelf', trip_id: tripInvNo },
            success: function(response) {
                Swal.fire({
                    title: response.title || __('success'),
                    text: response.message,
                    icon: response.type || 'success',
                    confirmButtonText: __('ok')
                }).then(() => location.reload());
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                Swal.fire({
                    title: response.title || __('error'),
                    text: response.message || 'Failed to cancel business trip request.',
                    icon: 'error',
                    confirmButtonText: __('ok')
                });
            }
        });
    });
}

function openBusinessTripAllowanceModal(tripId, employeeName) {
    Swal.fire({
        title: __('loading') || 'Loading...',
        html: __('loading_allowance_details') || 'Loading allowance details...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: './includes/ajaxFile/ajaxBusinessTrip.php',
        type: 'POST',
        dataType: 'JSON',
        data: {
            ajaxType: 'getBusinessTripAllowances',
            trip_id: tripId
        }
    }).done(function(response) {
        if (response.status !== 'success') {
            Swal.fire(__('error') || 'Error', response.message || 'Failed to load allowance details.', 'error');
            return;
        }

        const allowance = response.data || {};
        const tripStartDate = (allowance.trip_start_date || '').toString();
        const tripEndDate = (allowance.trip_end_date || '').toString();
        const otherAllowanceAmount = Number(allowance.other_allowance || 0).toFixed(2);

        const html = `
            <div style="text-align:left; max-height:520px; overflow-y:auto;">
                <div style="border:1px solid #e9ecef; border-radius:8px; padding:12px; background:#f8f9fa; margin-bottom:12px;">
                    <div class="form-row">
                        <div class="form-group col-6 mb-0">
                            <label class="mb-1"><strong>${__('start_date') || 'Start Date'}</strong></label>
                            <input type="date" id="allowance_start_date" class="form-control" value="${tripStartDate}" readonly>
                        </div>
                        <div class="form-group col-6 mb-0">
                            <label class="mb-1"><strong>${__('return_date') || __('end_date') || 'Return Date'}</strong></label>
                            <input type="text" id="allowance_return_date" class="form-control" value="${tripEndDate}" placeholder="YYYY-MM-DD" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div style="border:1px solid #e9ecef; border-radius:8px; padding:12px; background:#fff; margin-bottom:12px;">
                    <div class="form-group mb-0">
                        <label class="mb-1"><strong>${__('other_allowance') || 'Other Allowance'}</strong></label>
                        <input type="number" min="0" step="0.01" id="other_allowance_amount" class="form-control" value="${otherAllowanceAmount}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="allowance_notes_input"><strong>${__('notes') || 'Notes'}</strong></label>
                    <textarea id="allowance_notes_input" class="form-control" rows="3" placeholder="${__('optional_notes') || 'Optional notes...'}">${allowance.notes || ''}</textarea>
                </div>
            </div>
        `;

        Swal.fire({
            title: __('add_other_amount') || 'Add Other Amount',
            html,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: __('save') || 'Save',
            cancelButtonText: __('cancel') || 'Cancel',
            confirmButtonColor: APP_COLORS.success,
            cancelButtonColor: APP_COLORS.danger,
            allowOutsideClick: false,
            didOpen: () => {
                const $returnDate = $('#allowance_return_date');

                if ($returnDate.length && typeof $returnDate.datepicker === 'function') {
                    $returnDate.datepicker({
                        format: 'yyyy-mm-dd',
                        autoclose: true,
                        todayHighlight: true
                    });

                    if (tripStartDate) {
                        $returnDate.datepicker('setStartDate', tripStartDate);
                    }

                    $returnDate.datepicker('update', tripEndDate || tripStartDate || '');
                }
            },
            preConfirm: () => {
                const returnDateInput = document.getElementById('allowance_return_date');
                const returnDate = (returnDateInput?.value || '').trim();
                if (!returnDate) {
                    Swal.showValidationMessage(__('return_date_required') || 'Return date is required.');
                    return false;
                }
                if (tripStartDate && returnDate < tripStartDate) {
                    Swal.showValidationMessage(__('end_date_must_be_after_start_date') || 'Return date must be after or equal to start date.');
                    return false;
                }

                const otherAmount = parseFloat(document.getElementById('other_allowance_amount')?.value || '0');
                if (isNaN(otherAmount) || otherAmount < 0) {
                    Swal.showValidationMessage(__('invalid_amount') || 'Invalid amount');
                    return false;
                }

                const lineItems = [
                    { allowance_type: 'others', unit_amount: otherAmount, qty: 1 }
                ];

                return $.ajax({
                    url: './includes/ajaxFile/ajaxBusinessTrip.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        ajaxType: 'saveBusinessTripAllowances',
                        trip_id: tripId,
                        line_items: JSON.stringify(lineItems),
                        return_date: returnDate,
                        notes: document.getElementById('allowance_notes_input')?.value || ''
                    }
                }).then(function(saveResponse) {
                    if (saveResponse.status !== 'success') {
                        throw new Error(saveResponse.message || 'Failed to save allowances.');
                    }
                    return saveResponse;
                }).catch(function(err) {
                    Swal.showValidationMessage(err.message || 'Failed to save allowances.');
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: result.value.title || 'Saved',
                    text: result.value.message || 'Allowances saved successfully.',
                    icon: 'success',
                    confirmButtonColor: APP_COLORS.success
                }).then(() => location.reload());
            }
        });
    }).fail(function() {
        Swal.fire(__('error') || 'Error', __('error_loading_allowances') || 'An error occurred while loading allowance details.', 'error');
    });
}

// Lets HR/admin add ad-hoc allowance entries (taxi, parking, etc.) with a
// required comment explaining what each amount is for. Unlike the ticket/other
// amount modal above, this can be used repeatedly over the life of the trip.
function openBusinessTripOtherAllowanceModal(tripId, employeeName) {
    Swal.fire({
        title: __('loading') || 'Loading...',
        html: __('loading_allowance_details') || 'Loading allowance details...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: './includes/ajaxFile/ajaxBusinessTrip.php',
        type: 'POST',
        dataType: 'JSON',
        data: {
            ajaxType: 'getBusinessTripOtherAllowanceItems',
            trip_id: tripId
        }
    }).done(function(response) {
        if (response.status !== 'success') {
            Swal.fire(__('error') || 'Error', response.message || 'Failed to load allowance details.', 'error');
            return;
        }

        const existingItems = (response.data && response.data.items) || [];
        const existingHtml = existingItems.length ? `
            <div style="border:1px solid #e9ecef; border-radius:8px; padding:10px; background:#f8f9fa; margin-bottom:12px; max-height:150px; overflow-y:auto;">
                <table class="table table-sm mb-0">
                    <thead><tr><th>${__('comment') || 'Comment'}</th><th class="text-right">${__('amount') || 'Amount'}</th></tr></thead>
                    <tbody>
                        ${existingItems.map(it => `<tr><td>${$('<div>').text(it.description).html()}</td><td class="text-right">${Number(it.amount || 0).toFixed(2)}</td></tr>`).join('')}
                    </tbody>
                </table>
            </div>
        ` : '';

        const rowHtml = () => `
            <div class="manual-allowance-row form-row align-items-center mb-2" style="gap: 6px;">
                <div class="col">
                    <input type="text" class="form-control form-control-sm manual-allowance-desc" placeholder="${__('comment_placeholder') || 'e.g. Taxi, Parking'}">
                </div>
                <div class="col-4">
                    <input type="number" min="0" step="0.01" class="form-control form-control-sm manual-allowance-amount" placeholder="${__('amount') || 'Amount'}">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-outline-danger manual-allowance-remove"><i class="fa fa-times"></i></button>
                </div>
            </div>
        `;

        const html = `
            <div style="text-align:left;">
                ${existingHtml}
                <div id="manual-allowance-rows">${rowHtml()}</div>
                <button type="button" id="manual-allowance-add-row" class="btn btn-sm btn-light mt-1"><i class="fa fa-plus"></i> ${__('add_row') || 'Add Row'}</button>
            </div>
        `;

        Swal.fire({
            title: __('add_manual_allowance') || 'Add Manual Allowance',
            html,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: __('save') || 'Save',
            cancelButtonText: __('cancel') || 'Cancel',
            confirmButtonColor: APP_COLORS.success,
            cancelButtonColor: APP_COLORS.danger,
            allowOutsideClick: false,
            didOpen: () => {
                const container = document.getElementById('manual-allowance-rows');
                container.addEventListener('click', function(e) {
                    const removeBtn = e.target.closest('.manual-allowance-remove');
                    if (removeBtn && container.children.length > 1) {
                        removeBtn.closest('.manual-allowance-row').remove();
                    }
                });
                document.getElementById('manual-allowance-add-row').addEventListener('click', function() {
                    container.insertAdjacentHTML('beforeend', rowHtml());
                });
            },
            preConfirm: () => {
                const rows = document.querySelectorAll('#manual-allowance-rows .manual-allowance-row');
                const items = [];
                for (const row of rows) {
                    const description = row.querySelector('.manual-allowance-desc').value.trim();
                    const amount = parseFloat(row.querySelector('.manual-allowance-amount').value || '0');
                    if (description === '' && (!amount || amount <= 0)) {
                        continue; // skip fully empty rows
                    }
                    if (description === '') {
                        Swal.showValidationMessage(__('comment_required') || 'Please add a comment for every allowance entry.');
                        return false;
                    }
                    if (isNaN(amount) || amount <= 0) {
                        Swal.showValidationMessage(__('invalid_amount') || 'Invalid amount');
                        return false;
                    }
                    items.push({ description, amount });
                }

                if (items.length === 0) {
                    Swal.showValidationMessage(__('at_least_one_allowance_entry') || 'Add at least one allowance entry.');
                    return false;
                }

                return $.ajax({
                    url: './includes/ajaxFile/ajaxBusinessTrip.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        ajaxType: 'addBusinessTripOtherAllowances',
                        trip_id: tripId,
                        items: JSON.stringify(items)
                    }
                }).then(function(saveResponse) {
                    if (saveResponse.status !== 'success') {
                        throw new Error(saveResponse.message || 'Failed to save allowances.');
                    }
                    return saveResponse;
                }).catch(function(err) {
                    Swal.showValidationMessage(err.message || 'Failed to save allowances.');
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: result.value.title || 'Saved',
                    text: result.value.message || 'Manual allowance entries saved successfully.',
                    icon: 'success',
                    confirmButtonColor: APP_COLORS.success
                }).then(() => location.reload());
            }
        });
    }).fail(function() {
        Swal.fire(__('error') || 'Error', __('error_loading_allowances') || 'An error occurred while loading allowance details.', 'error');
    });
}

function openHRPayrollTicketFareModal(tripId, employeeName) {
    Swal.fire({
        title: __('loading') || 'Loading...',
        html: __('loading_ticket_fare') || 'Loading ticket fare details...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: './includes/ajaxFile/ajaxBusinessTrip.php',
        type: 'POST',
        dataType: 'JSON',
        data: {
            ajaxType: 'getBusinessTripTicketFareOnly',
            trip_id: tripId
        }
    }).done(function(response) {
        if (response.status !== 'success') {
            Swal.fire(__('error') || 'Error', response.message || 'Failed to load ticket fare.', 'error');
            return;
        }

        const data = response.data || {};
        const fareAmount = Number(data.ticket_unit_amount || 0).toFixed(2);
        const fareQty = 1;

        const html = `
            <div style="text-align:left;">
                <div style="background:#f8f9fa; padding:12px; border-radius:8px; margin-bottom:12px; border:1px solid #e9ecef;">
                    <strong>${__('employee') || 'Employee'}:</strong> ${employeeName}<br>
                    <strong>${__('trip_id') || 'Trip ID'}:</strong> ${tripId}
                </div>
                <div class="form-row">
                    <div class="form-group col-12">
                        <label><strong>${__('ticket_fare_amount') || 'Ticket Fare Amount'}</strong></label>
                        <input type="number" min="0" step="0.01" id="hr_ticket_amount" class="form-control" value="${fareAmount}">
                    </div>
                </div>
            </div>
        `;

        Swal.fire({
            title: __('hr_payroll_ticket_fare') || 'HR Payroll Ticket Fare',
            html,
            width: '520px',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: __('save') || 'Save',
            cancelButtonText: __('cancel') || 'Cancel',
            confirmButtonColor: APP_COLORS.success,
            cancelButtonColor: APP_COLORS.danger,
            preConfirm: () => {
                const amount = parseFloat(document.getElementById('hr_ticket_amount')?.value || '0');
                if (isNaN(amount) || amount < 0) {
                    Swal.showValidationMessage(__('invalid_amount') || 'Invalid amount.');
                    return false;
                }

                return $.ajax({
                    url: './includes/ajaxFile/ajaxBusinessTrip.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        ajaxType: 'saveBusinessTripTicketFareOnly',
                        trip_id: tripId,
                        ticket_unit_amount: amount,
                        ticket_qty: fareQty
                    }
                }).then(function(saveResponse) {
                    if (saveResponse.status !== 'success') {
                        throw new Error(saveResponse.message || 'Failed to save ticket fare.');
                    }
                    return saveResponse;
                }).catch(function(err) {
                    Swal.showValidationMessage(err.message || 'Failed to save ticket fare.');
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: result.value.title || 'Saved',
                    text: result.value.message || 'Ticket fare saved successfully.',
                    icon: 'success',
                    confirmButtonColor: APP_COLORS.success
                }).then(() => location.reload());
            }
        });
    }).fail(function() {
        Swal.fire(__('error') || 'Error', __('error_loading_ticket_fare') || 'An error occurred while loading ticket fare details.', 'error');
    });
}
