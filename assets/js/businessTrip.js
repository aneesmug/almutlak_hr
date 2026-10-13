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
 *  - AppDate (assets/js/app_datepicker.js, flatpickr)
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
    var strView = `<form id="submitBusinessTripForm" enctype="multipart/form-data">
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
                <div id="passportStatusMsg" class="alert alert-info" style="margin-bottom: 12px;">${__('passport_check_will_run_for_international_trip') || 'Passport check will run for international trip.'}</div>
                <div id="passportUploadSection" class="form-group d-none" style="margin-bottom: 12px;">
                    <label class="form-label" for="passport_file">${__('passport_attachment') || 'Passport Attachment'} <span class="text-danger">*</span></label>
                    <div class="sr-form">
                        <label class="sr-filepick" for="passport_file">
                            <input type="file" name="passport_file" id="passport_file" accept=".jpg,.jpeg,.png,.pdf,application/pdf,image/jpeg,image/png">
                            <i class="mdi mdi-cloud-upload"></i>
                            <span><b class="js-file-name">${__('choose_file', 'Choose a file or drop it here')}</b>
                            <small>${__('upload_passport_as_jpg_or_pdf') || 'Upload passport file as JPG, PNG or PDF'}</small></span>
                        </label>
                    </div>
                </div>
                <label class="sr-check" for="visa_required"><input type="checkbox" name="visa_required" id="visa_required"> ${__('visa_required') || 'Visa Required'}</label>
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
    if (typeof window.srEnsureCss === 'function') { window.srEnsureCss(); } else if (!document.querySelector('link[href*="smart_request.css"]')) { $('head').append('<link rel="stylesheet" href="assets/css/smart_request.css">'); }
    const normalizeStatus = (statusValue) => String(statusValue || '').toLowerCase().replace(/_/g, ' ').trim();
    const toTitle = (value) => String(value || '').replace(/\b\w/g, ch => ch.toUpperCase());
    const getStatusMeta = (statusValue) => {
        const normalized = normalizeStatus(statusValue);

        if (normalized.includes('approved')) {
            return { icon: 'fa-check-square', cls: 'text-success', tone: 'tone-green', label: __('approved', 'Approved') };
        }
        if (normalized.includes('pending')) {
            return { icon: 'fa-hourglass-half', cls: 'text-warning', tone: 'tone-amber', label: __('pending', 'Pending') };
        }
        if (normalized.includes('awaiting')) {
            return { icon: 'fa-pause-circle', cls: 'text-info', tone: 'tone-sky', label: __('awaiting', 'Awaiting') };
        }
        if (normalized.includes('rejected')) {
            return { icon: 'fa-times-circle', cls: 'text-danger', tone: 'tone-red', label: __('rejected', 'Rejected') };
        }

        return { icon: 'fa-circle', cls: 'text-secondary', tone: 'tone-slate', label: toTitle(normalized || __('unknown', 'Unknown')) };
    };

    const escHtml = (value) => $('<div>').text(value == null ? '' : String(value)).html();
    const kvRow = (label, valueHtml) => '<div class="row-kv"><dt>' + label + '</dt><dd>' + valueHtml + '</dd></div>';

    const buildActiveRequestHtml = (res) => {
        const req = (res && res.existing_request) ? res.existing_request : null;
        const chain = (res && Array.isArray(res.approval_chain)) ? res.approval_chain : [];

        if (!req) {
            return res && res.html ? res.html : ('<div class="sr-page"><div class="sr-form"><div class="sr-notice tone-sky"><i class="mdi mdi-information-outline"></i><div>' + escHtml(res && res.message) + '</div></div></div></div>');
        }

        const isInternational = String(req.trip_type || '').toLowerCase() === 'international';
        const fromRouteName = (req.from_city_route_name && String(req.from_city_route_name).trim() !== '') ? String(req.from_city_route_name).trim() : ('#' + (req.from_city_id || '-'));
        const toRouteName = (req.to_city_route_name && String(req.to_city_route_name).trim() !== '') ? String(req.to_city_route_name).trim() : ('#' + (req.to_city_id || '-'));
        const statusMeta = getStatusMeta(req.current_status);

        let chainHtml = '<span class="sr-fhint">' + __('no_data_found', 'No data found') + '</span>';
        if (chain.length > 0) {
            chainHtml = '<div class="sr-levels">' + chain.map(c => {
                const approverName = (c.approver_name && String(c.approver_name).trim() !== '') ? c.approver_name : ('Emp#' + (c.approver_id || '-'));
                const level = c.level || c.approval_level || '-';
                const meta = getStatusMeta(c.status);
                return '<div class="sr-level-row"><span><b>' + __('level', 'Level') + ' ' + escHtml(level) + '</b>' + escHtml(approverName) + '</span>'
                    + '<span class="sr-pill sr-pill-xs ' + meta.tone + '"><i class="fa ' + meta.icon + '"></i> ' + meta.label + '</span></div>';
            }).join('') + '</div>';
        }

        return `
            <div class="sr-page">
            <div class="sr-form">
                <div class="sr-notice ${res.overlap ? 'tone-amber' : 'tone-sky'}" style="margin-bottom:12px;">
                    <i class="mdi ${res.overlap ? 'mdi-alert' : 'mdi-information-outline'}"></i>
                    <div>${res.overlap
                        ? escHtml(res.message ? String(res.message).split('\n')[0] : __('business_trip_dates_overlap_existing', 'Your selected dates overlap with an existing business trip request. Please choose different dates.'))
                        : __('you_have_business_trip_in_progress', 'You already have a business trip request in progress. You can apply for another business trip with different dates.')}</div>
                </div>

                <div class="sr-fsec">
                    <div class="sr-fsec-head"><span><i class="mdi mdi-file-document"></i> ${__('applied_information', 'Applied Information')}</span></div>
                    <div class="sr-fsec-body">
                        <dl class="sr-kv">
                            ${kvRow(__('request_id', 'Request ID'), '<span class="sr-mono">' + escHtml(req.request_inv_no || '-') + '</span>')}
                            ${kvRow(__('status', 'Status'), '<span class="sr-pill sr-pill-xs ' + statusMeta.tone + '"><i class="fa ' + statusMeta.icon + '"></i> ' + escHtml(__(req.current_status) || '-') + '</span>')}
                            ${kvRow(__('trip_type', 'Trip Type'), escHtml(__(req.trip_type) || '-'))}
                            ${kvRow(__('trip_dates', 'Trip Dates'), escHtml(req.trip_start_date || '-') + ' ' + __('to', 'to') + ' ' + escHtml(req.trip_end_date || '-'))}
                            ${isInternational
                                ? kvRow(__('destination_country', 'Destination Country'), escHtml(req.destination_country || '-'))
                                : kvRow(__('route', 'Route'), escHtml(fromRouteName) + ' <i class="mdi mdi-arrow-right"></i> ' + escHtml(toRouteName))}
                        </dl>
                    </div>
                </div>

                <div class="sr-fsec mb-0">
                    <div class="sr-fsec-head"><span><i class="mdi mdi-sitemap"></i> ${__('request_chain', 'Request Chain')}</span></div>
                    <div class="sr-fsec-body">${chainHtml}</div>
                </div>
            </div>
            </div>
        `;
    };

    // Shows the employee's in-progress trip (status + approval chain). Like the vacation
    // modal, a button lets them apply for another trip - on different dates (the calendar
    // blocks booked days and the server rejects overlaps).
    const showActiveRequestModal = (res) => {
        const modalWidth = (window.innerWidth && window.innerWidth < 768) ? '95%' : '640px';
        Swal.fire({
            title: '<i class="fa fa-plane" style="margin-right: 8px;"></i> ' + (res.overlap
                ? (res.title || __('business_trip_date_conflict', 'Trip Dates Already Booked'))
                : (__('business_trip_in_progress', 'Business Trip In Progress'))),
            html: buildActiveRequestHtml(res),
            showConfirmButton: true,
            confirmButtonColor: APP_COLORS.primary,
            confirmButtonText: '<i class="fa fa-plus"></i> ' + (res.overlap
                ? __('choose_different_dates', 'Choose Different Dates')
                : __('apply_another_business_trip', 'Apply Another Business Trip')),
            showCancelButton: true,
            cancelButtonColor: APP_COLORS.danger_dark,
            cancelButtonText: '<i class="fa fa-times"></i> ' + (__('close', 'Close')),
            allowOutsideClick: false,
            width: modalWidth,
            scrollbarPadding: false,
            customClass: { popup: 'sr-addline-popup sr-page' }
        }).then((result) => {
            if (result.isConfirmed) {
                openApplyTripFormModal(true);
            }
        });
    };

    // Date ranges of the employee's existing trips (not rejected/cancelled) - blocked in the
    // calendar and re-checked on submit; the server enforces the same overlap rule.
    let bookedTripRanges = [];
    const findBookedOverlap = (start, end) => bookedTripRanges.find(r => start <= r.to && end >= r.from) || null;

    let passportEligibility = {
        passport_valid: true,
        has_attachment: true,
        requires_upload: false,
        message: ''
    };

    const openApplyTripFormModal = (isAnother) => Swal.fire({
        title: '<i class="fa fa-plane" style="margin-right: 8px;"></i> ' + (isAnother
            ? __('apply_another_business_trip', 'Apply Another Business Trip')
            : (__('apply_business_trip') || 'Apply Business Trip Request')),
        html: businessTripForm_HTML(),
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        confirmButtonText: '<i class="fa fa-check"></i> ' + (__('yes_register') || 'Register'),
        cancelButtonText: '<i class="fa fa-times"></i> ' + (__('cancel') || 'Cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: (window.innerWidth && window.innerWidth < 768) ? '95%' : '960px',
        scrollbarPadding: false,
        customClass: { popup: 'sr-addline-popup sr-page' },
        didRender: () => {
            if (typeof window.srBindFilePick === 'function') {
                const $f = $('#submitBusinessTripForm');
                if (!$f.data('srPickBound')) { $f.data('srPickBound', 1); window.srBindFilePick($f); }
            }
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
                                $('#passport_file').val('').trigger('change');
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
            if ($tripDateRange.length === 0 || !window.AppDate) {
                return;
            }

            // Value stays "MM/DD/YYYY - MM/DD/YYYY" - preConfirm parses that format.
            const showDays = function(dates) {
                if (dates.length === 2) {
                    const daysCount = moment(dates[1]).diff(moment(dates[0]), 'days') + 1;
                    $('#trip_days_count').val(daysCount + ' days');
                }
            };
            const defaultDate = moment().add(1, 'day').toDate();
            const fp = AppDate.range($tripDateRange, {
                format: 'm/d/Y',
                defaultDate: [defaultDate, defaultDate],
                onChange: function(dates) {
                    if (dates.length === 2) {
                        // A range may not jump over an already booked trip either
                        const hit = findBookedOverlap(moment(dates[0]).format('YYYY-MM-DD'), moment(dates[1]).format('YYYY-MM-DD'));
                        if (hit) {
                            fp.clear();
                            $('#trip_days_count').val('');
                            Swal.showValidationMessage((__('business_trip_dates_already_booked', 'These dates overlap your trip request') + ' ' + hit.request_inv_no + ' (' + hit.from + ' - ' + hit.to + ')'));
                            return;
                        }
                        Swal.resetValidationMessage();
                    }
                    showDays(dates);
                }
            });
            showDays(fp.selectedDates);

            // Block the days of the employee's existing trips
            $.ajax({
                url: './includes/ajaxFile/ajaxBusinessTrip.php',
                type: 'POST',
                dataType: 'JSON',
                data: { ajaxType: 'getBusinessTripBookedDates', emp_id: empid }
            }).done(function(res) {
                bookedTripRanges = (res && Array.isArray(res.ranges)) ? res.ranges : [];
                if (!bookedTripRanges.length) return;
                fp.set('disable', bookedTripRanges.map(r => ({ from: r.from, to: r.to })));
                // Default (tomorrow) may itself be booked - then start empty
                const sel = fp.selectedDates;
                if (sel.length === 2 && findBookedOverlap(moment(sel[0]).format('YYYY-MM-DD'), moment(sel[1]).format('YYYY-MM-DD'))) {
                    fp.clear();
                    $('#trip_days_count').val('');
                }
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

            const bookedHit = findBookedOverlap(startDate.format('YYYY-MM-DD'), endDate.format('YYYY-MM-DD'));
            if (bookedHit) {
                Swal.showValidationMessage(__('business_trip_dates_already_booked', 'These dates overlap your trip request') + ' ' + bookedHit.request_inv_no + ' (' + bookedHit.from + ' - ' + bookedHit.to + ')');
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

    // In-progress request? Show its status first, with a button to apply for another trip
    // on different dates (same flow as Apply Vacation). Any failure just opens the form -
    // submitBusinessTrip enforces the date-overlap rule anyway.
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

                if ($returnDate.length && window.AppDate) {
                    // Days before the trip start are blocked.
                    AppDate.single($returnDate, {
                        minDate: tripStartDate || null,
                        defaultDate: tripEndDate || tripStartDate || null
                    });
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
                        ${existingItems.map(it => `<tr><td>${$('<div>').text(it.description).html()}</td><td class="text-right"><i class="icon-saudi_riyal"></i> ${Number(it.amount || 0).toFixed(2)}</td></tr>`).join('')}
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

/**
 * Create Settlement for a business trip (HR Payroll, approved trips).
 * Shows the calculated allowances, lets HR add any number of payment rows
 * (earnings or deductions), then creates the settlement. The server recalculates
 * the amount and moves the trip to "completed".
 */
function createBusinessTripSettlement(tripId, employeeName) {
    // __() returns the key itself when a translation is missing - fall back to English then.
    const t = (key, fallback) => {
        const v = (typeof __ === 'function') ? __(key) : '';
        return (v && v !== key) ? v : fallback;
    };
    const esc = s => $('<div>').text(s == null ? '' : String(s)).html();
    const money = v => Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const sar = v => `<i class="icon-saudi_riyal"></i> ${money(v)}`;

    Swal.fire({
        title: t('loading', 'Loading...'),
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: './includes/ajaxFile/ajaxBusinessTrip.php',
        type: 'POST',
        dataType: 'JSON',
        data: { ajaxType: 'getBusinessTripSettlementDetails', trip_id: tripId }
    }).done(function(response) {
        if (response.status !== 'success') {
            Swal.fire(t('error', 'Error'), response.message || 'Failed to load business trip details.', 'error');
            return;
        }

        const d = response.data;
        const baseTotal = Number(d.grand_total || 0);

        const calcRow = (icon, label, detail, amount) => `
            <tr>
                <td><i class="mdi ${icon}" style="color:var(--sr-accent); margin-inline-end:6px;"></i>${esc(label)}${detail ? `<div style="font-size:11px; color:var(--sr-muted);">${detail}</div>` : ''}</td>
                <td class="num">${sar(amount)}</td>
            </tr>`;

        // One compact line per payment: # | type | description | amount | remove
        const lineHtml = () => `
            <div class="bt-setl-line">
                <span class="sr-addline-no">#<span class="bt-line-no"></span></span>
                <select class="form-control bt-line-type">
                    <option value="earning">${t('earning', 'Earning (+)')}</option>
                    <option value="deduction">${t('deduction', 'Deduction (−)')}</option>
                </select>
                <input type="text" class="form-control bt-line-desc" maxlength="200" placeholder="${esc(t('bt_payment_row_placeholder', 'Description, e.g. Taxi, Parking, Advance paid'))}">
                <input type="number" min="0" step="0.01" class="form-control bt-line-amount" placeholder="0.00">
                <button type="button" class="sr-addline-remove bt-line-remove" title="${esc(t('remove', 'Remove'))}"><i class="mdi mdi-close"></i></button>
            </div>`;

        const noSalary = Number(d.basic_salary || 0) <= 0;
        const initials = String(employeeName || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase();
        const html = `
            <style>
                .bt-setl-hero { display: flex; align-items: center; gap: 16px; padding: 14px 16px; margin-bottom: 14px; border: 1px solid var(--sr-border); border-radius: 12px; background: var(--sr-surface-2); }
                .bt-setl-who { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 0 1 320px; }
                .bt-setl-avatar { flex: 0 0 auto; width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 700; background: var(--sr-accent-soft); color: var(--sr-accent-strong); }
                .bt-setl-name { font-size: 15px; font-weight: 700; color: var(--sr-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                .bt-setl-ref { margin-top: 2px; font-size: 12px; color: var(--sr-muted); font-family: SFMono-Regular, Consolas, monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
                .bt-setl-stats { flex: 1 1 auto; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); border-inline-start: 1px solid var(--sr-border); }
                .bt-setl-stat { padding: 2px 16px; border-inline-end: 1px solid var(--sr-border); min-width: 0; }
                .bt-setl-stat:last-child { border-inline-end: 0; }
                .bt-setl-stat span:first-child { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); white-space: nowrap; }
                .bt-setl-stat span:first-child i { color: var(--sr-accent); }
                .bt-setl-stat b { display: block; margin-top: 4px; font-size: 14px; color: var(--sr-text); white-space: nowrap; font-variant-numeric: tabular-nums; }
                .bt-setl-stat .sr-pill { font-size: 12px; }
                .bt-setl-wrap { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 7fr); gap: 14px; align-items: start; }
                .bt-setl-wrap .sr-fsec { margin-bottom: 0; }
                .bt-setl-col { display: flex; flex-direction: column; gap: 12px; min-width: 0; }
                .bt-setl-lines { padding: 10px 12px; max-height: 300px; overflow-y: auto; }
                .bt-setl-line { display: grid; grid-template-columns: 36px 140px minmax(0, 1fr) 120px 28px; gap: 8px; align-items: center; margin-bottom: 8px; }
                .bt-setl-line:last-child { margin-bottom: 0; }
                .bt-setl-line .form-control { height: 36px; }
                .bt-setl-head { display: grid; grid-template-columns: 36px 140px minmax(0, 1fr) 120px 28px; gap: 8px; padding: 8px 12px 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); }
                .bt-setl-wrap table.sr-lines td { padding: 9px 12px; }
                .bt-setl-wrap .sr-addline-summary { margin-top: 0; }
                @media (max-width: 991px) {
                    .bt-setl-hero { flex-direction: column; align-items: stretch; }
                    .bt-setl-who { flex-basis: auto; }
                    .bt-setl-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 10px; border-inline-start: 0; }
                    .bt-setl-wrap { grid-template-columns: 1fr; }
                    .bt-setl-line, .bt-setl-head { grid-template-columns: 30px 110px minmax(0, 1fr) 90px 28px; }
                }
            </style>
            <div class="sr-form" style="text-align:start;">
                <div class="bt-setl-hero">
                    <div class="bt-setl-who">
                        <span class="bt-setl-avatar">${esc(initials)}</span>
                        <div style="min-width:0;">
                            <div class="bt-setl-name">${esc(employeeName)}</div>
                            <div class="bt-setl-ref"><i class="mdi mdi-pound"></i>${esc(d.request_inv_no)}</div>
                        </div>
                    </div>
                    <div class="bt-setl-stats">
                        <div class="bt-setl-stat">
                            <span><i class="mdi mdi-calendar"></i> ${t('trip_dates', 'Trip Dates')}</span>
                            <b>${esc(d.trip_start_date)} → ${esc(d.trip_end_date)}</b>
                        </div>
                        <div class="bt-setl-stat">
                            <span><i class="mdi mdi-calendar-clock"></i> ${t('duration', 'Duration')}</span>
                            <b>${Number(d.trip_days || 0)} ${t('days', 'Days')}</b>
                        </div>
                        <div class="bt-setl-stat">
                            <span><i class="mdi mdi-cash"></i> ${t('basic_salary', 'Basic Salary')}</span>
                            <b>${sar(d.basic_salary)}</b>
                        </div>
                        <div class="bt-setl-stat">
                            <span><i class="mdi mdi-chart-bar"></i> ${t('salary_level', 'Salary Level')}</span>
                            <b>${d.salary_level ? `<span class="sr-pill tone-indigo">${t('level', 'Level')} ${Number(d.salary_level)}</span>` : `<span class="sr-pill tone-amber">${t('out_of_scale', 'Out of scale')}</span>`}</b>
                        </div>
                    </div>
                </div>
                ${noSalary ? `
                <div class="sr-notice is-compact tone-amber">
                    <i class="mdi mdi-alert-outline"></i>
                    <div>${t('bt_no_salary_warning', 'No active salary record found for this employee, so the calculated allowances are 0. Add the salary first, or enter the payments as rows.')}</div>
                </div>` : ''}

                <div class="bt-setl-wrap">
                    <div class="bt-setl-col">
                        <div class="sr-fsec">
                            <div class="sr-fsec-head"><span><i class="mdi mdi-calculator"></i> ${t('calculated_allowances', 'Calculated Allowances')}</span></div>
                            <table class="sr-lines">
                                <tbody>
                                    ${calcRow('mdi-hotel', t('hotel_allowance', 'Hotel Allowance'), `${money(d.hotel_daily)} × ${Number(d.hotel_nights || 0)} ${t('nights', 'nights')}`, d.hotel_total)}
                                    ${calcRow('mdi-car', t('transportation_allowance', 'Transportation Allowance'), `${money(d.transport_daily)} × ${Number(d.trip_days || 0)} ${t('days', 'days')}`, d.transport_total)}
                                    ${calcRow('mdi-calendar-clock', t('daily_allowance', 'Daily Allowance'), '', d.daily_allowance_total)}
                                    ${calcRow('mdi-airplane', t('ticket_fares', 'Ticket Fares'), '', d.ticket_fares_total)}
                                    ${calcRow('mdi-receipt', t('other_allowance', 'Other Allowances'), '', d.other_allowance_total)}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bt-setl-col">
                        <div class="sr-fsec">
                            <div class="sr-fsec-head">
                                <span><i class="mdi mdi-cash-multiple"></i> ${t('additional_payments', 'Additional Payments')}</span>
                                <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm" id="btSetlAddRow"><i class="mdi mdi-plus"></i> ${t('add_row', 'Add Row')}</button>
                            </div>
                            <div class="bt-setl-head">
                                <span>#</span><span>${t('type', 'Type')}</span><span>${t('description', 'Description')} <span class="text-danger">*</span></span><span>${t('amount', 'Amount')} <span class="text-danger">*</span></span><span></span>
                            </div>
                            <div class="bt-setl-lines" id="btSetlLines">${lineHtml()}</div>
                        </div>

                        <div class="sr-fsec">
                            <div class="sr-fsec-head"><span><i class="mdi mdi-note-text"></i> ${t('notes', 'Notes')}</span></div>
                            <div style="padding:10px 12px;">
                                <textarea id="btSetlNotes" class="form-control" rows="2" style="height:auto;" placeholder="${esc(t('bt_settlement_notes_placeholder', 'Optional remarks for the approvers'))}"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sr-addline-summary" style="margin-top:14px;">
                    <div><span>${t('allowances', 'Allowances')}</span><b>${money(baseTotal)}</b></div>
                    <div><span>${t('additions', 'Additions')}</span><b id="btSumAdd" style="color:var(--tone-green-fg);">${money(0)}</b></div>
                    <div><span>${t('deductions', 'Deductions')}</span><b id="btSumDed" style="color:var(--tone-red-fg);">${money(0)}</b></div>
                    <div class="accent"><span>${t('total_payable', 'Total Payable')}</span><b id="btSumTotal">${money(baseTotal)}</b></div>
                </div>
            </div>
        `;

        const readLines = () => {
            const lines = [];
            $('#btSetlLines .bt-setl-line').each(function() {
                const $l = $(this);
                lines.push({
                    el: $l,
                    type: $l.find('.bt-line-type').val(),
                    description: $.trim($l.find('.bt-line-desc').val()),
                    amount: Math.max(0, parseFloat($l.find('.bt-line-amount').val()) || 0)
                });
            });
            return lines;
        };

        const refresh = () => {
            let add = 0, ded = 0;
            const $lines = $('#btSetlLines .bt-setl-line');
            $lines.each(function(i) { $(this).find('.bt-line-no').text(i + 1); });
            $lines.find('.bt-line-remove').toggle($lines.length > 1);
            readLines().forEach(l => {
                if (l.type === 'deduction') ded += l.amount; else add += l.amount;
                l.el.find('.bt-line-amount').css('color', l.type === 'deduction' ? 'var(--tone-red-fg)' : 'var(--tone-green-fg)');
            });
            const total = Math.round((baseTotal + add - ded) * 100) / 100;
            $('#btSumAdd').text(money(add));
            $('#btSumDed').text(money(ded));
            $('#btSumTotal').text(money(total)).css('color', total > 0 ? '' : 'var(--tone-red-fg)');
            return total;
        };

        Swal.fire({
            title: t('create_settlement', 'Create Settlement'),
            html,
            width: '1180px',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            confirmButtonText: '<i class="mdi mdi-check"></i> ' + esc(t('create_settlement', 'Create Settlement')),
            cancelButtonText: esc(t('cancel', 'Cancel')),
            showLoaderOnConfirm: true,
            allowOutsideClick: false,
            customClass: { popup: 'sr-addline-popup sr-page' },
            didOpen: () => {
                const $popup = $(Swal.getPopup());
                $popup.on('click', '#btSetlAddRow', function() {
                    $('#btSetlLines').append(lineHtml());
                    refresh();
                    $('#btSetlLines .bt-line-desc').last().trigger('focus');
                });
                $popup.on('click', '.bt-line-remove', function() {
                    $(this).closest('.bt-setl-line').remove();
                    refresh();
                });
                $popup.on('input change', '.bt-line-type, .bt-line-desc, .bt-line-amount', function() {
                    $(this).removeClass('is-invalid');
                    refresh();
                });
                refresh();
            },
            preConfirm: () => {
                const lines = readLines();
                for (const l of lines) {
                    if (l.amount > 0 && !l.description) {
                        l.el.find('.bt-line-desc').addClass('is-invalid').trigger('focus');
                        Swal.showValidationMessage(t('bt_row_description_required', 'Please enter a description for each payment row.'));
                        return false;
                    }
                    if (l.description && l.amount <= 0) {
                        l.el.find('.bt-line-amount').addClass('is-invalid').trigger('focus');
                        Swal.showValidationMessage(t('bt_row_amount_required', 'Please enter an amount for each payment row.'));
                        return false;
                    }
                }
                const total = refresh();
                if (total <= 0) {
                    Swal.showValidationMessage(t('settlement_amount_must_be_positive', 'Total payable must be greater than zero.'));
                    return false;
                }
                return $.ajax({
                    url: './includes/ajaxFile/settlement_handler.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        action: 'create_settlement',
                        request_inv_no: d.request_inv_no,
                        request_type: 'business_trip',
                        emp_id: d.emp_id,
                        settlement_amount: total,
                        lines: JSON.stringify(lines.filter(l => l.amount > 0).map(l => ({ type: l.type, description: l.description, amount: l.amount }))),
                        notes: $('#btSetlNotes').val()
                    }
                }).then(res => {
                    if (!res.success) throw new Error(res.message || 'Failed to create settlement');
                    return res;
                }).catch(err => {
                    Swal.showValidationMessage((err && (err.message || (err.responseJSON && err.responseJSON.message))) || 'Error creating settlement');
                });
            }
        }).then(result => {
            if (!result.isConfirmed || !result.value) return;
            Swal.fire({
                icon: 'success',
                title: t('success', 'Success'),
                html: `<p>${t('settlement_created_successfully', 'Settlement created successfully!')}</p>
                       <p><strong>${t('settlement_ref', 'Settlement Ref')}:</strong> ${esc(result.value.settlement_inv_no || ('SETL-' + d.request_inv_no))}</p>`,
                allowOutsideClick: false
            }).then(() => location.reload());
        });
    }).fail(function() {
        Swal.fire(t('error', 'Error'), 'Failed to load business trip details.', 'error');
    });
}
