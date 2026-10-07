/* Car forms (SweetAlert2) used by all_cars.php and view_car.php:
   - addCarFunc()          -> Add car          (ajaxCar.php ajaxType=add_car)
   - .editCarAttr click    -> Edit car         (ajaxType=edit_car, data-* on the button)
   - .addMaintAttr click   -> Add maintenance  (ajaxType=cars_maint_add, data-id = car id, data-caruser = current driver)
   - .addDrvrAtter click   -> Assign driver    (ajaxType=driver_add, data-id = car id)
   - .addDocuAtter click   -> Add document     (ajaxType=document_add, data-id = car id)
   New car models and maintenance types are added inline (ajaxType=model_add / maint_type_add).
   "Return car" (.addRtrnDrvrAtter) stays in jquery.app.js - emp_end_of_service.php uses it too.
   Moved here from assets/js/jquery.app.js. Needs sr_forms.js, select2 and AppDate (app_datepicker.js). */
(function (window, $) {
    'use strict';

    var F = window.SRForm, t = F.t, esc = F.esc;
    var CAR_URL = './includes/ajaxFile/ajaxCar.php';
    var CAR_TYPES = [
        { v: 'Bus', l: t('bus', 'Bus') }, { v: 'Car', l: t('car', 'Car') }, { v: 'Dyna', l: t('dyna', 'Dyna') },
        { v: 'Fork Lift', l: t('fork_lift', 'Fork lift') }, { v: 'Jeep', l: t('jeep', 'Jeep') }, { v: 'Pick Up', l: t('pick_up', 'Pick up') },
        { v: 'Truck', l: t('truck', 'Truck') }, { v: 'Van', l: t('van', 'Van') }
    ];

    /* "1234abc" -> "1234-ABC" (1-4 digits, up to 3 letters) */
    function formatPlate(v) {
        v = String(v || '').toUpperCase().replace(/[^0-9A-Z]/g, '');
        var digits = (v.match(/^\d{0,4}/) || [''])[0];
        var letters = v.slice(digits.length).replace(/[^A-Z]/g, '').slice(0, 3);
        return digits + (letters ? '-' + letters : '');
    }
    function platePreview(v) {
        var p = formatPlate(v).split('-');
        return '<span>' + esc(p[0] || '----') + '</span><span>' + esc(p[1] || '---') + '</span>';
    }

    function loadDrivers($sel, selected) {
        return $.ajax({ url: './includes/ajaxFile/hrHandler.php', dataType: 'JSON', type: 'POST', data: { ajaxType: 'car_driver_search' } })
            .done(function (res) {
                if (!res || res.status != 200 || !res.data) return;
                $sel.append(res.data.map(function (e) {
                    return '<option value="' + esc(e.emp_id) + '">' + esc(e.name) + ' (' + esc(e.emp_id) + ')</option>';
                }).join(''));
                if (selected) $sel.val(String(selected));
                $sel.trigger('change.select2');
            });
    }

    /* ---------------- Car (add / edit) ---------------- */

    function carFormHTML(isEdit) {
        var vehicle = F.section('mdi-car', t('vehicle_details', 'Vehicle'),
            F.field({ col: 6, name: 'maker_name', label: t('maker_name', 'Maker'), req: true,
                html: F.select({ name: 'maker_name', id: 'maker_name', req: true, msg: t('enter_car_maker_validation', 'Select the maker') }) }) +
            F.field({ col: 6, name: 'maker_model', label: t('model', 'Model'), req: true,
                html: '<div class="sr-inrow">' +
                        F.select({ name: 'maker_model', id: 'maker_model', req: true, msg: t('enter_car_model_validation', 'Select the model') }) +
                        '<button type="button" class="sr-btn sr-btn-icon js-new-model" title="' + esc(t('add_car_model', 'Add car model')) + '" disabled><i class="mdi mdi-plus"></i></button>' +
                      '</div>' +
                      '<div class="sr-inrow sr-inline-new js-new-model-row">' +
                        '<input type="text" class="form-control js-new-model-name" placeholder="' + esc(t('car_model_name', 'New model name')) + '">' +
                        '<button type="button" class="sr-btn sr-btn-primary js-new-model-save"><i class="mdi mdi-check"></i></button>' +
                        '<button type="button" class="sr-btn sr-btn-icon js-new-model-cancel"><i class="mdi mdi-close"></i></button>' +
                      '</div>' }) +
            F.field({ col: 4, name: 'made_year', id: 'made_year', label: t('made_year', 'Made year'), req: true, ph: String(new Date().getFullYear()),
                attrs: ' inputmode="numeric" maxlength="4"', msg: t('enter_car_made_year_validation', 'Enter the made year') }) +
            F.field({ col: 8, name: 'type', label: t('type_of_car', 'Type of car'), req: true,
                html: F.select({ name: 'type', id: 'type', req: true, options: CAR_TYPES, msg: t('select_car_type_validation', 'Select the car type') }) })
        );
        var registration = F.section('mdi-account-card-details', t('registration', 'Registration'),
            F.field({ col: isEdit ? 4 : 5, name: 'plate_no', id: 'plate_no', label: t('plate_no', 'Plate no.'), req: true, ph: '1234-ABC',
                cls: 'sr-mono js-plate', attrs: ' maxlength="8" style="text-transform: uppercase;"', msg: t('enter_car_plate_no_validation', 'Enter the plate no.'),
                hint: esc(t('plate_format_hint', '1-4 digits and up to 3 English letters')) }) +
            F.field({ col: isEdit ? 4 : 7, name: 'remarks', id: 'remarks', label: t('remarks', 'Remarks'), ph: t('enter_remarks_placeholder', '') }) +
            (isEdit ? F.field({ col: 4, name: 'status', label: t('status', 'Status'), html: F.statusSeg('status') }) : ''),
            '<span class="car-plate ad-keep js-plate-preview">' + platePreview('') + '</span>'
        );
        return '<form id="submitEditUserForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
            vehicle + registration + '<input type="hidden" id="carid" name="carid">' +
        '</form>';
    }

    function loadModels(makerId, selectModel) {
        var $model = $('#maker_model');
        $model.html('<option value="">' + esc(t('select', 'Select')) + '</option>').trigger('change.select2');
        $('.js-new-model').prop('disabled', !makerId);
        if (!makerId) return $.Deferred().resolve();
        return $.ajax({ url: CAR_URL, type: 'POST', data: { request: 1, maker_name: makerId, ajaxType: 'model_search' } })
            .done(function (html) {
                $model.append(html);
                if (selectModel) $model.val(String(selectModel));
                $model.trigger('change.select2');
            });
    }

    function openCarForm(isEdit, v) {
        F.open({
            title: isEdit ? t('update_car_info', 'Update car') : t('add_new_car_info', 'Add new car'),
            html: carFormHTML(isEdit),
            width: '820px',
            confirm: isEdit ? t('yes_update', 'Update') : t('yes_register', 'Register'),
            didOpen: function () {
                var $form = $('#submitEditUserForm');
                F.liveClear($form);
                F.select2($('#maker_name'), { placeholder: t('select', 'Select') });
                F.select2($('#maker_model'), { placeholder: t('select', 'Select') });
                F.select2($('#type'), { minimumResultsForSearch: Infinity });

                if (v) {
                    $('#carid').val(v.id);
                    $('#made_year').val(v.made_year);
                    $('#plate_no').val(formatPlate(v.plate_no));
                    $('#remarks').val(v.remarks);
                    $('#type').val(v.type).trigger('change.select2');
                    F.setSeg($form, 'status', v.status);
                }
                $form.find('.js-plate-preview').html(platePreview($('#plate_no').val()));

                $.ajax({ url: CAR_URL, dataType: 'JSON', type: 'POST', data: { ajaxType: 'maker_search' } })
                    .done(function (res) {
                        if (!res || res.status != 200 || !res.data) return;
                        $('#maker_name').append(res.data.map(function (m) {
                            return '<option value="' + esc(m.id) + '">' + esc(m.maker) + '</option>';
                        }).join(''));
                        if (v && v.maker_name) {
                            $('#maker_name').val(String(v.maker_name)).trigger('change.select2');
                            loadModels(v.maker_name, v.model);
                        }
                    })
                    .fail(function (j, e) { if (typeof window.errorHandling === 'function') window.errorHandling(j, e); });

                $('#maker_name').on('change', function () {
                    $form.find('.js-new-model-row').removeClass('show');
                    loadModels(this.value);
                });

                // Inline "new model"
                $form.on('click', '.js-new-model', function () {
                    $form.find('.js-new-model-row').addClass('show').find('input').val('').trigger('focus');
                });
                $form.on('click', '.js-new-model-cancel', function () { $form.find('.js-new-model-row').removeClass('show'); });
                $form.on('keydown', '.js-new-model-name', function (e) { if (e.key === 'Enter') { e.preventDefault(); $form.find('.js-new-model-save').trigger('click'); } });
                $form.on('click', '.js-new-model-save', function () {
                    var $btn = $(this), $name = $form.find('.js-new-model-name'), name = $.trim($name.val());
                    var maker = $('#maker_name').val();
                    if (!name) { $name.addClass('is-invalid').trigger('focus'); return; }
                    $btn.prop('disabled', true);
                    Swal.resetValidationMessage();
                    $.ajax({ url: CAR_URL, type: 'POST', dataType: 'JSON', data: { ajaxType: 'model_add', maker_name: maker, maker_model: name } })
                        .done(function (res) {
                            if (!res || res.type !== 'success') { Swal.showValidationMessage((res && res.message) || 'Error'); return; }
                            $form.find('.js-new-model-row').removeClass('show');
                            loadModels(maker, res.id).done(function () {
                                if (!res.id) {
                                    var $opt = $('#maker_model option').filter(function () { return $.trim($(this).text()) === name; }).last();
                                    if ($opt.length) $('#maker_model').val($opt.val()).trigger('change.select2');
                                }
                                $('#maker_model').removeClass('is-invalid');
                            });
                        })
                        .fail(function (j) { Swal.showValidationMessage((j.responseJSON && j.responseJSON.message) || t('request_failed', 'Request failed')); })
                        .always(function () { $btn.prop('disabled', false); });
                });

                $form.on('input', '.js-plate', function () {
                    var f = formatPlate(this.value);
                    if (f !== this.value) this.value = f;
                    $form.find('.js-plate-preview').html(platePreview(f));
                });
                $form.on('input', '#made_year', function () { this.value = this.value.replace(/\D/g, '').slice(0, 4); });
            },
            preConfirm: function () {
                var $form = $('#submitEditUserForm');
                var msg = F.validate($form, function () {
                    var y = parseInt($('#made_year').val(), 10), max = new Date().getFullYear() + 1;
                    if (!(y >= 1950 && y <= max)) return { el: $('#made_year')[0], msg: t('invalid_made_year', 'Made year must be between 1950 and ') + max + '.' };
                    if (!/^\d{1,4}-[A-Z]{1,3}$/.test($('#plate_no').val())) return { el: $('#plate_no')[0], msg: t('invalid_plate_no', 'Plate no. must look like 1234-ABC.') };
                    if (isEdit && !$form.find('input[name="status"]:checked').length) return { el: $form.find('.sr-seg')[0], msg: t('select_status', 'Select the status') };
                    return null;
                });
                if (msg) { Swal.showValidationMessage(msg); return false; }
                return F.post(CAR_URL, $form.serialize() + '&' + $.param({ ajaxType: isEdit ? 'edit_car' : 'add_car' }));
            }
        }).then(function (r) { F.done(r); });
    }

    // Kept as a global function - all_cars.php calls addCarFunc().
    window.addCarFunc = function () { openCarForm(false, null); };

    $(document).on('click', '.editCarAttr', function (e) {
        e.preventDefault();
        var d = $(this).data();
        openCarForm(true, {
            id: d.id, maker_name: d.maker_name, model: d.model, made_year: d.made_year,
            plate_no: d.plate_no, type: d.type, remarks: d.remarks, status: d.status
        });
    });

    /* ---------------- Maintenance ---------------- */

    $(document).on('click', '.addMaintAttr', function (e) {
        e.preventDefault();
        var cid = $(this).data('id'), caruser = $(this).data('caruser');

        var service = F.section('mdi-wrench', t('maintenance_details', 'Service'),
            F.field({ col: 6, name: 'car_user', label: t('select_driver', 'Driver'), req: true,
                html: F.select({ name: 'car_user', id: 'car_user', req: true, msg: t('select_car_driver_validation', 'Select the driver') }) }) +
            F.field({ col: 6, name: 'date', id: 'maint_date', label: t('select_date', 'Date'), req: true, ph: 'YYYY-MM-DD', value: F.today(),
                msg: t('select_maintenance_date_validation', 'Select the maintenance date') }) +
            F.field({ col: 12, name: 'type', label: t('select_type', 'Type of maintenance'), req: true,
                html: '<div class="sr-inrow">' +
                        F.select({ name: 'type', id: 'maint_type', req: true, msg: t('select_maintenance_type_validation', 'Select the maintenance type') }) +
                        '<button type="button" class="sr-btn sr-btn-icon js-new-type" title="' + esc(t('add_type', 'Add type')) + '"><i class="mdi mdi-plus"></i></button>' +
                      '</div>' +
                      '<div class="sr-inrow sr-inline-new js-new-type-row">' +
                        '<input type="text" class="form-control js-new-type-name" placeholder="' + esc(t('type_name', 'New type name')) + '">' +
                        '<button type="button" class="sr-btn sr-btn-primary js-new-type-save"><i class="mdi mdi-check"></i></button>' +
                        '<button type="button" class="sr-btn sr-btn-icon js-new-type-cancel"><i class="mdi mdi-close"></i></button>' +
                      '</div>' })
        );
        var meter = F.section('mdi-speedometer', t('meter_reading', 'Odometer'),
            F.field({ col: 5, name: 'meter', id: 'meter', label: t('new_meter_reading', 'New reading (km)'), req: true, ph: '12345678', cls: 'sr-mono',
                attrs: ' inputmode="numeric" maxlength="9"', msg: t('enter_meter_reading_validation', 'Enter the meter reading'), hint: '', hintCls: 'js-meter-hint' }) +
            F.field({ col: 7, name: '_stats', label: ' ',
                html: '<div class="sr-fstats">' +
                        '<div><span>' + esc(t('old_meter_reading', 'Previous')) + '</span><b class="js-old">&ndash;</b></div>' +
                        '<div><span>' + esc(t('new_meter_reading', 'New')) + '</span><b class="js-new">&ndash;</b></div>' +
                        '<div class="js-diff-box"><span>' + esc(t('diff_meter_reading', 'Difference')) + '</span><b class="js-diff">&ndash;</b></div>' +
                      '</div>' })
        );
        var details = F.section('mdi-note-outline', t('details', 'Details'),
            F.field({ col: 12, name: 'details', label: t('description_for_maintenance', 'Description'), req: true, type: 'textarea', rows: 2,
                ph: t('describe_the_work_done', 'What was done?'), msg: t('enter_maintenance_details_validation', 'Enter the maintenance details') }) +
            F.field({ col: 12, name: 'remarks', label: t('remarks', 'Remarks'), ph: t('enter_remarks_placeholder', '') })
        );
        var html = '<form id="submitMaintenanceForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
            service + meter + details +
            '<input type="hidden" name="cid"><input type="hidden" name="oldmeter"><input type="hidden" name="diffmeter">' +
        '</form>';

        function fmt(n) { return Number(n).toLocaleString('en-US') + ' km'; }

        function loadTypes(selected) {
            var $t = $('#maint_type');
            return $.ajax({ url: CAR_URL, dataType: 'JSON', type: 'POST', data: { ajaxType: 'maint_type' } })
                .done(function (res) {
                    $t.html('<option value="">' + esc(t('select', 'Select')) + '</option>');
                    if (res && res.data) {
                        $t.append(res.data.map(function (r) { return '<option value="' + esc(r.type) + '">' + esc(r.type) + '</option>'; }).join(''));
                    }
                    if (selected) $t.val(selected);
                    $t.trigger('change.select2');
                });
        }

        F.open({
            title: t('add_maintenance_info', 'Add maintenance'),
            html: html,
            width: '820px',
            confirm: t('yes_register', 'Register'),
            didOpen: function () {
                var $form = $('#submitMaintenanceForm');
                F.liveClear($form);
                $form.find('input[name="cid"]').val(cid);
                F.select2($('#car_user'), { placeholder: t('select', 'Select') });
                F.select2($('#maint_type'), { placeholder: t('select', 'Select') });
                F.datepicker($('#maint_date'), { maxDate: 'today' });
                loadDrivers($('#car_user'), caruser);
                loadTypes();

                var old = null;
                function recalc() {
                    var raw = $('#meter').val(), now = raw === '' ? null : parseInt(raw, 10);
                    var base = old === null ? now : old;
                    $form.find('.js-new').text(now === null ? '–' : fmt(now));
                    var diff = (now === null || base === null) ? null : now - base;
                    $form.find('.js-diff').text(diff === null ? '–' : (diff > 0 ? '+' : '') + fmt(diff));
                    $form.find('.js-diff-box').toggleClass('is-bad', diff !== null && diff < 0).toggleClass('is-good', diff !== null && diff > 0);
                    $form.find('.js-meter-hint').html(diff !== null && diff < 0 ? '<span class="text-danger">' + esc(t('meter_lower_than_previous', 'Lower than the previous reading.')) + '</span>' : '');
                    $form.find('input[name="oldmeter"]').val(base === null ? '' : base);
                    $form.find('input[name="diffmeter"]').val(diff === null ? '' : diff + 'KM');
                }
                $.ajax({ url: CAR_URL, dataType: 'JSON', type: 'POST', data: { id: cid, ajaxType: 'cars_maint' } })
                    .done(function (res) {
                        var n = res && res.data !== undefined && res.data !== null && res.data !== '' ? parseInt(res.data, 10) : NaN;
                        old = isNaN(n) ? null : n;
                        $form.find('.js-old').text(old === null ? t('none', 'None') : fmt(old));
                        recalc();
                    });
                $form.on('input', '#meter', function () { this.value = this.value.replace(/\D/g, '').slice(0, 9); recalc(); });

                // Inline "new maintenance type"
                $form.on('click', '.js-new-type', function () { $form.find('.js-new-type-row').addClass('show').find('input').val('').trigger('focus'); });
                $form.on('click', '.js-new-type-cancel', function () { $form.find('.js-new-type-row').removeClass('show'); });
                $form.on('keydown', '.js-new-type-name', function (ev) { if (ev.key === 'Enter') { ev.preventDefault(); $form.find('.js-new-type-save').trigger('click'); } });
                $form.on('click', '.js-new-type-save', function () {
                    var $btn = $(this), $name = $form.find('.js-new-type-name'), name = $.trim($name.val());
                    if (!name) { $name.addClass('is-invalid').trigger('focus'); return; }
                    $btn.prop('disabled', true);
                    Swal.resetValidationMessage();
                    $.ajax({ url: CAR_URL, type: 'POST', dataType: 'JSON', data: { ajaxType: 'maint_type_add', type: name } })
                        .done(function (res) {
                            if (!res || res.type !== 'success') { Swal.showValidationMessage((res && res.message) || 'Error'); return; }
                            $form.find('.js-new-type-row').removeClass('show');
                            loadTypes(name).done(function () { $('#maint_type').removeClass('is-invalid'); });
                        })
                        .fail(function (j) { Swal.showValidationMessage((j.responseJSON && j.responseJSON.message) || t('request_failed', 'Request failed')); })
                        .always(function () { $btn.prop('disabled', false); });
                });
            },
            preConfirm: function () {
                var $form = $('#submitMaintenanceForm');
                var msg = F.validate($form);
                if (msg) { Swal.showValidationMessage(msg); return false; }
                return F.post(CAR_URL, $form.serialize() + '&' + $.param({ ajaxType: 'cars_maint_add' }));
            }
        }).then(function (r) { F.done(r); });
    });

    /* ---------------- Assign driver ---------------- */

    $(document).on('click', '.addDrvrAtter', function (e) {
        e.preventDefault();
        var cid = $(this).data('id');
        var html = '<form id="submitDriverForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
            F.section('mdi-steering', t('driver_details', 'Driver'),
                F.field({ col: 7, name: 'car_user', label: t('select_driver_name', 'Driver'), req: true,
                    html: F.select({ name: 'car_user', id: 'car_user', req: true, msg: t('select_car_driver_validation', 'Select the driver') }) }) +
                F.field({ col: 5, name: 'rcv_date', id: 'rcv_date', label: t('receive_date_label', 'Receive date'), req: true, ph: 'YYYY-MM-DD', value: F.today(),
                    msg: t('select_issue_date_validation', 'Select the receive date') })
            ) +
            '<input type="hidden" name="cid">' +
        '</form>';
        F.open({
            title: t('add_driver_info', 'Assign driver'),
            html: html,
            width: '640px',
            icon: 'mdi-account-check',
            confirm: t('yes_register', 'Assign'),
            didOpen: function () {
                var $form = $('#submitDriverForm');
                F.liveClear($form);
                $form.find('input[name="cid"]').val(cid);
                F.select2($('#car_user'), { placeholder: t('select', 'Select') });
                F.datepicker($('#rcv_date'), { maxDate: 'today' });
                loadDrivers($('#car_user'));
            },
            preConfirm: function () {
                var $form = $('#submitDriverForm');
                var msg = F.validate($form);
                if (msg) { Swal.showValidationMessage(msg); return false; }
                return F.post(CAR_URL, $form.serialize() + '&' + $.param({ ajaxType: 'driver_add' }));
            }
        }).then(function (r) { F.done(r); });
    });

    /* ---------------- Documents ---------------- */

    $(document).on('click', '.addDocuAtter', function (e) {
        e.preventDefault();
        var cid = $(this).data('id');
        var TYPES = ['pdf', 'jpg', 'jpeg', 'png'], MAX_MB = 8;
        var html = '<form id="submitDocumentsForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
            F.section('mdi-file-document', t('document_details', 'Document'),
                F.field({ col: 12, name: 'doc_type', label: t('type_of_document', 'Type of document'), req: true,
                    html: F.choices('doc_type', [
                        { v: 'Licence', l: t('licence', 'Licence'), icon: 'mdi-account-card-details' },
                        { v: 'Insurance', l: t('insurance', 'Insurance'), icon: 'mdi-security' },
                        { v: 'MVPI', l: t('mvpi', 'MVPI'), icon: 'mdi-clipboard-check' }
                    ], true, t('select_documents_type_validation', 'Select the document type')) }) +
                F.field({ col: 6, name: 'issue_date', id: 'issue_date', label: t('issue_date', 'Issue date'), req: true, ph: 'YYYY-MM-DD',
                    msg: t('select_issue_date_validation', 'Select the issue date') }) +
                F.field({ col: 6, name: 'exp_date', id: 'exp_date', label: t('expiry_date', 'Expiry date'), req: true, ph: 'YYYY-MM-DD',
                    msg: t('select_expiry_date_validation', 'Select the expiry date') }),
                '<span class="sr-chip js-duration" style="display:none;"></span>'
            ) +
            F.section('mdi-paperclip', t('attachment', 'Attachment'),
                F.field({ col: 12, name: 'file', html: F.filePicker({ id: 'checkatt', name: 'file', accept: '.pdf,.jpg,.jpeg,.png',
                    hint: 'PDF, JPG, PNG · ' + t('max', 'max') + ' ' + MAX_MB + ' MB · ' + t('optional', 'optional') }) })
            ) +
        '</form>';
        F.open({
            title: t('add_documents_info', 'Add document'),
            html: html,
            width: '720px',
            confirm: t('yes_register', 'Register'),
            didOpen: function () {
                var $form = $('#submitDocumentsForm');
                F.liveClear($form);
                F.bindFilePicker($form);
                F.datepicker($('#issue_date'), { maxDate: 'today' });
                F.datepicker($('#exp_date'));
                $form.on('change input', '#issue_date, #exp_date', function () {
                    var s = new Date($('#issue_date').val()), en = new Date($('#exp_date').val()), $d = $form.find('.js-duration');
                    if (isNaN(s) || isNaN(en) || en <= s) { $d.hide(); return; }
                    var days = Math.round((en - new Date(F.today())) / 86400000);
                    $d.html('<i class="mdi mdi-calendar-range"></i> ' + esc(days >= 0
                        ? days + ' ' + t('days_left', 'days left')
                        : t('expired', 'Expired') + ' ' + (-days) + ' ' + t('days_ago', 'days ago'))).show();
                });
            },
            preConfirm: function () {
                var $form = $('#submitDocumentsForm');
                var msg = F.validate($form, function () {
                    if ($('#exp_date').val() <= $('#issue_date').val()) return { el: $('#exp_date')[0], msg: t('expiry_after_issue', 'Expiry date must be after the issue date.') };
                    var fileMsg = F.checkFile($('#checkatt')[0], TYPES, MAX_MB);
                    if (fileMsg) return { el: $form.find('.sr-filepick')[0], msg: fileMsg };
                    return null;
                });
                if (msg) { Swal.showValidationMessage(msg); return false; }
                var fd = new FormData();
                fd.append('ajaxType', 'document_add');
                fd.append('cid', cid);
                fd.append('doc_type', $form.find('input[name="doc_type"]:checked').val());
                fd.append('issue_date', $('#issue_date').val());
                fd.append('exp_date', $('#exp_date').val());
                var f = $('#checkatt')[0].files[0];
                if (f) fd.append('file', f);
                return F.post(CAR_URL, fd, true);
            }
        }).then(function (r) { F.done(r); });
    });
})(window, jQuery);
