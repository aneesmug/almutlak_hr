/* Location forms (SweetAlert2) used by all_locations.php and view_location.php:
   - addlocarionFunc()          -> Add Location            (ajaxLocation.php ajaxType=add_location)
   - .editLocationAttr click    -> Edit Location           (ajaxType=edit_location, data-* on the button)
   - .addLocContractAttr click  -> Add Contract            (ajaxType=add_contract, data-id = location id)
   Moved here from assets/js/jquery.app.js. Styling: assets/css/smart_request.css (.sr-form*).
   Needs jQuery, SweetAlert2, autoNumeric, bootstrap-datepicker and the hijri date picker. */
(function (window, $) {
    'use strict';

    function t(key, fallback) {
        var v = (typeof window.__ === 'function') ? window.__(key) : '';
        return (v && v !== key) ? v : (fallback || key);
    }
    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }
    function num(v) {
        var n = parseFloat(String(v == null ? '' : v).replace(/,/g, ''));
        return isNaN(n) ? 0 : n;
    }
    function money(n) {
        return (parseFloat(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /* One labelled input. opts: {name, label, req, ph, cls, id, type, extra, col, attrs} */
    function field(o) {
        var id = o.id || ('f_' + o.name);
        return '<div class="sr-fcol c-' + (o.col || 4) + '">' +
            '<label for="' + id + '">' + esc(o.label) + (o.req ? ' <span class="text-danger">*</span>' : '') + '</label>' +
            (o.html ? o.html :
                '<input type="' + (o.type || 'text') + '" name="' + o.name + '" id="' + id + '" class="form-control ' + (o.cls || '') + '"' +
                ' placeholder="' + esc(o.ph || '') + '" autocomplete="off"' + (o.req ? ' data-required="1"' : '') + (o.attrs || '') + '>') +
            (o.hint ? '<small class="sr-fhint">' + o.hint + '</small>' : '') +
        '</div>';
    }
    function section(icon, title, body, aside) {
        return '<div class="sr-fsec">' +
            '<div class="sr-fsec-head"><span><i class="mdi ' + icon + '"></i> ' + esc(title) + '</span>' + (aside || '') + '</div>' +
            '<div class="sr-fgrid">' + body + '</div>' +
        '</div>';
    }

    /* Marks empty required / invalid fields; returns first error message or ''. */
    function validate($form, extraChecks) {
        var firstMsg = '', firstEl = null;
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[data-required]').each(function () {
            if ($.trim($(this).val() || '') === '') {
                $(this).addClass('is-invalid');
                if (!firstEl) { firstEl = this; firstMsg = $(this).data('msg') || t('fill_mandatory_fields', 'Please fill all mandatory fields.'); }
            }
        });
        if (!firstEl && extraChecks) {
            var r = extraChecks();
            if (r) { firstEl = r.el; firstMsg = r.msg; $(r.el).addClass('is-invalid'); }
        }
        if (firstEl) { firstEl.focus(); }
        return firstMsg;
    }

    function submit(data, $form) {
        return $.ajax({
            url: './includes/ajaxFile/ajaxLocation.php',
            type: 'POST',
            dataType: 'JSON',
            data: data
        }).then(function (response) {
            if (!response || response.type !== 'success') {
                throw new Error((response && response.message) || 'Error');
            }
            return response;
        }).catch(function (err) {
            var msg = err && err.message ? err.message : (err && err.statusText) || 'Error';
            if (err && err.responseJSON && err.responseJSON.message) msg = err.responseJSON.message;
            Swal.showValidationMessage(t('request_failed', 'Request failed') + ': ' + msg);
        });
    }

    function done(result) {
        if (result.isConfirmed && result.value) {
            Swal.fire({ allowOutsideClick: false, title: result.value.title, text: result.value.message, icon: result.value.type, confirmButtonText: t('ok', 'OK') })
                .then(function () { location.reload(); });
        }
    }

    /* ---------------- Location (add / edit) ---------------- */

    function locationFormHTML(isEdit) {
        var statusField = isEdit ? field({
            col: 4, name: 'status', label: t('status', 'Status'),
            html: '<div class="sr-seg">' +
                '<label class="sr-seg-opt is-on"><input type="radio" name="status" value="1"><span><i class="mdi mdi-check-circle"></i> ' + esc(t('active', 'Active')) + '</span></label>' +
                '<label class="sr-seg-opt is-off"><input type="radio" name="status" value="0"><span><i class="mdi mdi-close-circle"></i> ' + esc(t('inactive', 'Inactive')) + '</span></label>' +
            '</div>'
        }) : '';

        var basic = section('mdi-store', t('basic_information', 'Basic information'),
            field({ col: isEdit ? 5 : 7, name: 'section_name', label: t('location_name', 'Location name'), req: true, ph: t('enter_section_name_placeholder', ''), attrs: ' data-msg="' + esc(t('enter_section_name_validation', 'Enter the location name')) + '"' }) +
            field({ col: isEdit ? 3 : 5, name: 'dept', label: t('select_department', 'Department'), req: true,
                html: '<select class="form-control" name="dept" id="dept" data-required="1" data-msg="' + esc(t('select_department_validation', 'Select a department')) + '"><option value="">' + esc(t('select', 'Select')) + '</option></select>' }) +
            statusField
        );

        var mapAside = '<span class="sr-fsec-tools">' +
            '<button type="button" class="sr-btn sr-btn-sm js-geo"><i class="mdi mdi-crosshairs-gps"></i> ' + esc(t('use_my_location', 'Use my location')) + '</button>' +
            '<a href="#" target="_blank" rel="noopener" class="sr-btn sr-btn-sm js-map-preview" style="display:none;"><i class="mdi mdi-google-maps"></i> ' + esc(t('preview_map', 'Preview')) + '</a>' +
        '</span>';
        var address = section('mdi-map-marker', t('address_and_map', 'Address & map'),
            field({ col: 12, name: 'loc_address', label: t('location_address', 'Address'), ph: t('enter_location_address_placeholder', '') }) +
            field({ col: 4, name: 'location_dist', label: t('district', 'District'), req: true, ph: t('enter_district_placeholder', ''), attrs: ' data-msg="' + esc(t('enter_location_district_validation', 'Enter the district')) + '"' }) +
            field({ col: 4, name: 'municipality', label: t('municipality', 'Municipality'), ph: t('enter_municipality_placeholder', '') }) +
            field({ col: 4, name: 'sub_municipality', label: t('sub_municipality', 'Sub municipality'), ph: t('enter_sub_municipality_placeholder', '') }) +
            field({ col: 6, name: 'latitude', label: t('latitude', 'Latitude'), req: true, ph: '21.593358', cls: 'js-lat', attrs: ' inputmode="decimal" data-msg="' + esc(t('enter_latitude_validation', 'Enter the latitude')) + '"',
                hint: esc(t('paste_coordinates_hint', 'Tip: paste "lat, long" here to fill both.')) }) +
            field({ col: 6, name: 'longitude', label: t('longitude', 'Longitude'), req: true, ph: '39.105934', cls: 'js-lng', attrs: ' inputmode="decimal" data-msg="' + esc(t('enter_longitude_validation', 'Enter the longitude')) + '"' }),
            mapAside
        );

        var building = section('mdi-domain', t('building_and_license', 'Building & license'),
            field({ col: 6, name: 'b_license_no', label: t('balady_license_no', 'Balady license no.'), req: true, ph: t('enter_balady_license_no_placeholder', ''), attrs: ' data-msg="' + esc(t('enter_baladya_license_no_validation', 'Enter the Balady license no.')) + '"' }) +
            field({ col: 6, name: 'b_license_exp', id: 'b_license_exp_hijri', label: t('balady_license_exp', 'Balady license expiry'), req: true, ph: t('enter_balady_license_exp_placeholder', 'Hijri date'), cls: 'b_license_exp_hijri', attrs: ' data-msg="' + esc(t('select_balady_license_expiry_validation', 'Select the license expiry')) + '"' }) +
            field({ col: 4, name: 't_bulding_size', label: t('total_building_size_m', 'Total building size (m)'), ph: t('enter_total_building_size_placeholder', '') }) +
            field({ col: 4, name: 'bulding_base', label: t('building_base', 'Building base'), ph: t('enter_building_base_placeholder', '') }) +
            field({ col: 4, name: 'bulding_size', label: t('building_size_l_w', 'Building size (L x W)'), ph: t('enter_building_size_l_w_placeholder', '') }) +
            field({ col: 6, name: 'camera_in', label: t('camera_in', 'Cameras inside'), ph: t('enter_camera_in_placeholder', '') }) +
            field({ col: 6, name: 'camera_out', label: t('camera_out', 'Cameras outside'), ph: t('enter_camera_out_placeholder', '') })
        );

        return '<form id="submitlocationForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
            basic + address + building +
            '<input type="hidden" name="smid">' +
        '</form>';
    }

    function initLocationForm(selectedDept) {
        var $form = $('#submitlocationForm');

        $.ajax({
            url: './includes/ajaxFile/ajaxLocation.php',
            dataType: 'JSON', type: 'POST',
            data: { ajaxType: 'loc_department' }
        }).done(function (res) {
            if (res && res.status == 200 && res.data) {
                var options = res.data.map(function (d) {
                    return '<option value="' + esc(d.dep_nme) + '">' + esc(d.dep_nme) + '</option>';
                }).join('');
                $('#dept').append(options);
                if (selectedDept) { $('#dept').val(String(selectedDept)); }
            }
        }).fail(function (j, e) {
            if (typeof window.errorHandling === 'function') window.errorHandling(j, e);
        });

        if ($.fn.hijriDatePicker) {
            $('#b_license_exp_hijri').hijriDatePicker({
                locale: 'ar-sa', hijri: true, showSwitcher: false,
                hijriFormat: 'iYYYY-iMM-iDD', hijriDayViewHeaderFormat: 'iMMMM iYYYY', showTodayButton: true
            });
        }

        function refreshMapLink() {
            var lat = num($form.find('.js-lat').val()), lng = num($form.find('.js-lng').val());
            var ok = $.trim($form.find('.js-lat').val()) !== '' && $.trim($form.find('.js-lng').val()) !== '';
            $form.find('.js-map-preview').toggle(ok).attr('href', 'https://www.google.com/maps?q=' + lat + ',' + lng);
        }
        // Paste "21.59, 39.10" (or a Google Maps link containing @lat,lng / q=lat,lng) into latitude to fill both.
        $form.on('input', '.js-lat', function () {
            var v = $(this).val();
            var m = v.match(/(-?\d{1,3}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
            if (m) {
                $(this).val(m[1]);
                $form.find('.js-lng').val(m[2]).removeClass('is-invalid');
            }
            refreshMapLink();
        });
        $form.on('input', '.js-lng', refreshMapLink);
        $form.on('input change', '.is-invalid', function () { $(this).removeClass('is-invalid'); });
        $form.on('click', '.js-geo', function () {
            var $btn = $(this);
            if (!navigator.geolocation) return;
            $btn.prop('disabled', true);
            navigator.geolocation.getCurrentPosition(function (pos) {
                $form.find('.js-lat').val(pos.coords.latitude.toFixed(6)).removeClass('is-invalid');
                $form.find('.js-lng').val(pos.coords.longitude.toFixed(6)).removeClass('is-invalid');
                refreshMapLink();
                $btn.prop('disabled', false);
            }, function () { $btn.prop('disabled', false); }, { enableHighAccuracy: true, timeout: 10000 });
        });
        $form.on('change', '.sr-seg input', function () {
            $(this).closest('.sr-seg').find('.sr-seg-opt').removeClass('checked');
            $(this).closest('.sr-seg-opt').addClass('checked');
        });
        refreshMapLink();
        return $form;
    }

    function coordsCheck($form) {
        return function () {
            var $lat = $form.find('.js-lat'), $lng = $form.find('.js-lng');
            var lat = parseFloat($lat.val()), lng = parseFloat($lng.val());
            if (isNaN(lat) || lat < -90 || lat > 90) return { el: $lat[0], msg: t('invalid_latitude', 'Latitude must be a number between -90 and 90.') };
            if (isNaN(lng) || lng < -180 || lng > 180) return { el: $lng[0], msg: t('invalid_longitude', 'Longitude must be a number between -180 and 180.') };
            return null;
        };
    }

    function openLocationForm(isEdit, values) {
        Swal.fire({
            title: isEdit ? t('update_location_info', 'Update location') : t('add_new_location', 'Add new location'),
            html: locationFormHTML(isEdit),
            width: '980px',
            showCancelButton: true,
            confirmButtonColor: window.APP_COLORS && APP_COLORS.primary,
            cancelButtonColor: window.APP_COLORS && APP_COLORS.danger_dark,
            cancelButtonText: t('cancel', 'Cancel'),
            confirmButtonText: '<i class="mdi mdi-content-save"></i> ' + esc(isEdit ? t('yes_update', 'Update') : t('yes_register', 'Register')),
            showLoaderOnConfirm: true,
            allowOutsideClick: false,
            customClass: { popup: 'sr-addline-popup' },
            didOpen: function () {
                var $form = $('#submitlocationForm');
                if (values) {
                    $.each(values, function (name, val) {
                        if (name === 'status') return;
                        $form.find('[name="' + name + '"]').not('select').val(val == null ? '' : val);
                    });
                    var $st = $form.find('input[name="status"][value="' + values.status + '"]');
                    $st.prop('checked', true).closest('.sr-seg-opt').addClass('checked');
                }
                initLocationForm(values ? values.dept : '');
                $form.find('[name="section_name"]').trigger('focus');
            },
            preConfirm: function () {
                var $form = $('#submitlocationForm');
                var msg = validate($form, coordsCheck($form));
                if (msg) { Swal.showValidationMessage(msg); return false; }
                return submit($form.serialize() + '&' + $.param({ ajaxType: isEdit ? 'edit_location' : 'add_location' }), $form);
            }
        }).then(done);
    }

    // Kept as a global function - all_locations.php calls addlocarionFunc().
    window.addlocarionFunc = function () { openLocationForm(false, null); };

    $(document).on('click', '.editLocationAttr', function (e) {
        e.preventDefault();
        var d = $(this).data();
        openLocationForm(true, {
            smid: d.id,
            section_name: d.section_name,
            dept: d.dept,
            camera_in: d.camera_in,
            camera_out: d.camera_out,
            b_license_exp: d.b_license_exp,
            b_license_no: d.b_license_no,
            location_dist: d.location_dist,
            bulding_base: d.bulding_base,
            bulding_size: d.bulding_size,
            t_bulding_size: d.t_bulding_size,
            latitude: d.latitude,
            longitude: d.longitude,
            loc_address: d.location_name,
            municipality: d.municipality,
            sub_municipality: d.sub_municipality,
            status: d.status
        });
    });

    /* ---------------- Contract ---------------- */

    function contractFormHTML() {
        var owner = section('mdi-account-card-details', t('owner_details', 'Owner'),
            field({ col: 4, name: 'owner_name', label: t('location_owner_name', 'Owner name'), req: true, ph: t('enter_owner_name_placeholder', ''), attrs: ' data-msg="' + esc(t('enter_owner_name_validation', 'Enter the owner name')) + '"' }) +
            field({ col: 4, name: 'owner_number', label: t('owner_number', 'Owner mobile'), req: true, ph: '05XXXXXXXX', attrs: ' inputmode="tel" maxlength="10" data-msg="' + esc(t('enter_owner_contact_validation', 'Enter the owner mobile')) + '"' }) +
            field({ col: 4, name: 'owner_email', type: 'email', label: t('owner_email', 'Owner email'), req: true, ph: 'name@example.com', attrs: ' data-msg="' + esc(t('enter_owner_email_validation', 'Enter the owner email')) + '"' })
        );
        var contract = section('mdi-file-document', t('contract_details', 'Contract'),
            field({ col: 4, name: 'contract_no', label: t('contract_no', 'Contract no.'), req: true, ph: t('enter_contract_no_placeholder', ''), attrs: ' data-msg="' + esc(t('enter_contract_number_validation', 'Enter the contract number')) + '"' }) +
            field({ col: 4, name: 'start_cont_date', id: 'start_cont_date', label: t('contract_starting_date', 'Start date'), req: true, ph: 'YYYY-MM-DD', attrs: ' data-msg="' + esc(t('select_start_contract_date_validation', 'Select the start date')) + '"' }) +
            field({ col: 4, name: 'end_cont_date', id: 'end_cont_date', label: t('contract_ending_date', 'End date'), req: true, ph: 'YYYY-MM-DD', attrs: ' data-msg="' + esc(t('select_end_contract_date_validation', 'Select the end date')) + '"' }),
            '<span class="sr-chip js-duration" style="display:none;"></span>'
        );
        function amount(name, label, req, msg) {
            return field({ col: 4, name: name, id: name, label: label, req: req, ph: '0.00', cls: 'autonumber js-amount', attrs: ' inputmode="decimal"' + (msg ? ' data-msg="' + esc(msg) + '"' : '') });
        }
        var amounts = section('mdi-cash-multiple', t('contract_amounts', 'Amounts (SAR)'),
            amount('rent', t('amount_of_rent', 'Rent'), true, t('enter_rent_amount_validation', 'Enter the rent amount')) +
            amount('service', t('amount_of_services', 'Services')) +
            amount('elect_prc', t('amount_of_electricity', 'Electricity')) +
            amount('water_prc', t('amount_of_water', 'Water')) +
            amount('incuranse_prc', t('amount_of_insurance', 'Insurance'), true, t('enter_insurance_amount_validation', 'Enter the insurance amount')) +
            amount('others', t('others', 'Others'))
        );
        return '<form id="submitlocationContractForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
            owner + contract + amounts +
            '<div class="sr-ftotal"><span>' + esc(t('contract_total', 'Contract total')) + '</span><b><span class="js-total">0.00</span> <i class="icon-saudi_riyal"></i></b></div>' +
            '<input type="hidden" name="locid">' +
        '</form>';
    }

    $(document).on('click', '.addLocContractAttr', function (e) {
        e.preventDefault();
        var locid = $(this).data('id');
        Swal.fire({
            title: t('add_location_contract_info', 'Add contract'),
            html: contractFormHTML(),
            width: '920px',
            showCancelButton: true,
            confirmButtonColor: window.APP_COLORS && APP_COLORS.primary,
            cancelButtonColor: window.APP_COLORS && APP_COLORS.danger_dark,
            cancelButtonText: t('cancel', 'Cancel'),
            confirmButtonText: '<i class="mdi mdi-content-save"></i> ' + esc(t('yes_register', 'Register')),
            showLoaderOnConfirm: true,
            allowOutsideClick: false,
            customClass: { popup: 'sr-addline-popup' },
            didOpen: function () {
                var $form = $('#submitlocationContractForm');
                $form.find('input[name="locid"]').val(locid);
                if ($.fn.autoNumeric) { $form.find('.autonumber').autoNumeric('init'); }
                if ($.fn.datepicker) {
                    $('#start_cont_date, #end_cont_date').datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true });
                }

                function recalc() {
                    var total = 0;
                    $form.find('.js-amount').each(function () { total += num($(this).val()); });
                    $form.find('.js-total').text(money(total));

                    var s = new Date($('#start_cont_date').val()), en = new Date($('#end_cont_date').val());
                    var $d = $form.find('.js-duration');
                    if (!isNaN(s) && !isNaN(en) && en > s) {
                        var months = (en.getFullYear() - s.getFullYear()) * 12 + (en.getMonth() - s.getMonth());
                        if (en.getDate() < s.getDate()) months--;
                        var y = Math.floor(months / 12), m = months % 12;
                        var txt = (y ? y + ' ' + t('years', 'yr') + ' ' : '') + (m ? m + ' ' + t('months', 'mo') : '');
                        $d.html('<i class="mdi mdi-calendar-range"></i> ' + esc($.trim(txt) || ('< 1 ' + t('months', 'mo')))).show();
                    } else {
                        $d.hide();
                    }
                }
                $form.on('input change keyup', '.js-amount, #start_cont_date, #end_cont_date', recalc);
                $form.on('input change', '.is-invalid', function () { $(this).removeClass('is-invalid'); });
                // Mobile: digits only
                $form.on('input', '[name="owner_number"]', function () { this.value = this.value.replace(/\D/g, '').slice(0, 10); });
                recalc();
                $form.find('[name="owner_name"]').trigger('focus');
            },
            preConfirm: function () {
                var $form = $('#submitlocationContractForm');
                var msg = validate($form, function () {
                    var $email = $form.find('[name="owner_email"]');
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($.trim($email.val()))) return { el: $email[0], msg: t('invalid_email', 'Enter a valid email address.') };
                    var $end = $('#end_cont_date');
                    if ($end.val() && $('#start_cont_date').val() && $end.val() < $('#start_cont_date').val()) return { el: $end[0], msg: t('end_date_before_start', 'End date must be after the start date.') };
                    return null;
                });
                if (msg) { Swal.showValidationMessage(msg); return false; }
                return submit($form.serialize() + '&' + $.param({ ajaxType: 'add_contract' }), $form);
            }
        }).then(done);
    });
})(window, jQuery);
