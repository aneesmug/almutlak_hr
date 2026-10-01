/* Shared helpers for the sectioned SweetAlert2 forms (car_forms.js, asset_inventory.php).
   Markup/styling: assets/css/smart_request.css (.sr-form, .sr-fsec, .sr-fgrid, .sr-fcol ...).
   Exposes window.SRForm. Needs jQuery and SweetAlert2 (select2 / bootstrap-datepicker optional). */
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
    function today() {
        var d = new Date();
        return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
    }
    function msgAttr(msg) {
        return msg ? ' data-msg="' + esc(msg) + '"' : '';
    }

    /* One labelled control. o: {name, label, req, ph, cls, id, type, attrs, col, html, hint, msg, value} */
    function field(o) {
        var id = o.id || ('f_' + o.name);
        var control = o.html;
        if (!control) {
            if (o.type === 'textarea') {
                control = '<textarea name="' + o.name + '" id="' + id + '" class="form-control ' + (o.cls || '') + '" rows="' + (o.rows || 3) + '"' +
                    ' placeholder="' + esc(o.ph || '') + '"' + (o.req ? ' data-required="1"' : '') + msgAttr(o.msg) + (o.attrs || '') + '>' + esc(o.value || '') + '</textarea>';
            } else {
                control = '<input type="' + (o.type || 'text') + '" name="' + o.name + '" id="' + id + '" class="form-control ' + (o.cls || '') + '"' +
                    ' placeholder="' + esc(o.ph || '') + '" autocomplete="off"' + (o.value != null ? ' value="' + esc(o.value) + '"' : '') +
                    (o.req ? ' data-required="1"' : '') + msgAttr(o.msg) + (o.attrs || '') + '>';
            }
        }
        return '<div class="sr-fcol c-' + (o.col || 4) + '">' +
            (o.label ? '<label for="' + id + '">' + esc(o.label) + (o.req ? ' <span class="text-danger">*</span>' : '') + '</label>' : '') +
            control +
            (o.hint ? '<small class="sr-fhint' + (o.hintCls ? ' ' + o.hintCls : '') + '">' + o.hint + '</small>' : '') +
        '</div>';
    }

    /* <select>. o: {name, id, req, msg, options:[{v,l}] | html string, placeholder} */
    function select(o) {
        var opts = '<option value="">' + esc(o.placeholder || t('select', 'Select')) + '</option>';
        if (typeof o.options === 'string') opts += o.options;
        else (o.options || []).forEach(function (op) { opts += '<option value="' + esc(op.v) + '">' + esc(op.l) + '</option>'; });
        return '<select name="' + o.name + '" id="' + (o.id || ('f_' + o.name)) + '" class="form-control ' + (o.cls || '') + '"' +
            (o.req ? ' data-required="1"' : '') + msgAttr(o.msg) + '>' + opts + '</select>';
    }

    function section(icon, title, body, aside) {
        return '<div class="sr-fsec">' +
            '<div class="sr-fsec-head"><span><i class="mdi ' + icon + '"></i> ' + esc(title) + '</span>' + (aside || '') + '</div>' +
            '<div class="sr-fgrid">' + body + '</div>' +
        '</div>';
    }

    /* Active / Inactive segmented control. */
    function statusSeg(name) {
        name = name || 'status';
        return '<div class="sr-seg">' +
            '<label class="sr-seg-opt is-on"><input type="radio" name="' + name + '" value="1"><span><i class="mdi mdi-check-circle"></i> ' + esc(t('active', 'Active')) + '</span></label>' +
            '<label class="sr-seg-opt is-off"><input type="radio" name="' + name + '" value="0"><span><i class="mdi mdi-close-circle"></i> ' + esc(t('inactive', 'Inactive')) + '</span></label>' +
        '</div>';
    }

    /* Card-style radio choices. items: [{v, l, icon}] */
    function choices(name, items, req, msg) {
        return '<div class="sr-attach-choice" style="grid-template-columns: repeat(' + items.length + ', minmax(0, 1fr));">' +
            items.map(function (it, i) {
                return '<label class="sr-choice"><input type="radio" name="' + name + '" value="' + esc(it.v) + '"' +
                    (req && i === 0 ? ' data-required="1"' + msgAttr(msg) : '') + '>' +
                    '<span><i class="mdi ' + (it.icon || 'mdi-checkbox-blank-circle-outline') + '"></i> ' + esc(it.l) + '</span></label>';
            }).join('') +
        '</div>';
    }

    /* Dashed file picker. o: {id, name, accept, title, hint, req} */
    function filePicker(o) {
        return '<label class="sr-filepick" for="' + o.id + '"' + (o.req ? ' data-required-file="1"' : '') + '>' +
            '<input type="file" id="' + o.id + '" name="' + (o.name || o.id) + '" accept="' + esc(o.accept || '') + '">' +
            '<i class="mdi mdi-cloud-upload"></i>' +
            '<span><b class="js-file-name">' + esc(o.title || t('choose_file', 'Choose a file or drop it here')) + '</b>' +
            '<small>' + esc(o.hint || '') + '</small></span>' +
        '</label>';
    }

    function bindFilePicker($form) {
        $form.find('.sr-filepick').each(function () {
            var $pick = $(this), $input = $pick.find('input[type=file]'), $name = $pick.find('.js-file-name');
            var emptyText = $name.text();
            function show() {
                var f = $input[0].files && $input[0].files[0];
                $pick.toggleClass('has-file', !!f).removeClass('is-invalid');
                $name.text(f ? f.name + ' (' + (f.size / 1048576).toFixed(2) + ' MB)' : emptyText);
            }
            $input.on('change', show);
            $pick.on('dragover dragenter', function (e) { e.preventDefault(); $pick.addClass('is-drag'); })
                .on('dragleave dragend drop', function () { $pick.removeClass('is-drag'); })
                .on('drop', function (e) {
                    e.preventDefault();
                    var dt = e.originalEvent.dataTransfer;
                    if (dt && dt.files && dt.files.length) { $input[0].files = dt.files; show(); }
                });
        });
    }

    /* Validates a chosen file. Returns '' when fine (or nothing chosen and not required). */
    function checkFile(input, types, maxMb) {
        var f = input.files && input.files[0];
        if (!f) return '';
        var ext = (f.name.split('.').pop() || '').toLowerCase();
        if (types && types.indexOf(ext) === -1) {
            return t('file_type_not_allowed', 'File type not allowed. Allowed: ') + types.join(', ').toUpperCase();
        }
        if (maxMb && f.size > maxMb * 1048576) {
            return t('file_too_large', 'File is too large. Max size: ') + maxMb + ' MB';
        }
        return '';
    }

    /* Marks empty required / invalid fields; returns the first error message or ''. */
    function validate($form, extraChecks) {
        var firstMsg = '', firstEl = null;
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[data-required]').each(function () {
            var $el = $(this);
            var empty = $el.is(':radio') ? !$form.find('[name="' + this.name + '"]:checked').length : $.trim($el.val() || '') === '';
            if (empty) {
                $el.add($el.closest('.sr-attach-choice')).addClass('is-invalid');
                if (!firstEl) { firstEl = this; firstMsg = $el.data('msg') || t('fill_mandatory_fields', 'Please fill all mandatory fields.'); }
            }
        });
        if (!firstEl && extraChecks) {
            var r = extraChecks();
            if (r) { firstEl = r.el; firstMsg = r.msg; $(r.el).addClass('is-invalid'); }
        }
        if (firstEl) {
            if ($(firstEl).is('select') && $(firstEl).data('select2')) $(firstEl).next('.select2-container').find('.select2-selection').trigger('focus');
            else if (!$(firstEl).is(':radio, label')) firstEl.focus();
        }
        return firstMsg;
    }

    /* Clear the red state as soon as the user fixes a field. */
    function liveClear($form) {
        $form.on('input change', '.is-invalid', function () { $(this).removeClass('is-invalid'); });
        $form.on('change', '.sr-attach-choice input', function () {
            $(this).closest('.sr-attach-choice').removeClass('is-invalid').find('.is-invalid').removeClass('is-invalid');
        });
        $form.on('change', '.sr-seg input', function () {
            $(this).closest('.sr-seg').find('.sr-seg-opt').removeClass('checked');
            $(this).closest('.sr-seg-opt').addClass('checked');
        });
    }

    function setSeg($form, name, value) {
        var $r = $form.find('input[name="' + name + '"][value="' + value + '"]');
        $r.prop('checked', true);
        $r.closest('.sr-seg').find('.sr-seg-opt').removeClass('checked');
        $r.closest('.sr-seg-opt').addClass('checked');
    }

    function select2($el, opts) {
        if (!$.fn.select2 || !$el.length) return $el;
        return $el.select2($.extend({ width: '100%', dropdownParent: $(Swal.getPopup()) }, opts || {}));
    }

    function datepicker($el, opts) {
        if (!$.fn.datepicker || !$el.length) return $el;
        return $el.datepicker($.extend({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true }, opts || {}));
    }

    /* POST that resolves with the server JSON or shows the error inside the popup (resolves undefined).
       Understands both {type:'success', title, message} and {success:true, message, data}. */
    function post(url, data, isMultipart) {
        var ajax = { url: url, type: 'POST', dataType: 'JSON', data: data };
        if (isMultipart) { ajax.processData = false; ajax.contentType = false; ajax.cache = false; }
        return $.ajax(ajax).then(function (res) {
            var ok = res && (res.type === 'success' || res.success === true);
            if (!ok) throw new Error((res && res.message) || 'Error');
            return res;
        }).catch(function (err) {
            var msg = (err && err.responseJSON && err.responseJSON.message) || (err && err.message) || (err && err.statusText) || 'Error';
            Swal.showValidationMessage(t('request_failed', 'Request failed') + ': ' + msg);
        });
    }

    /* Swal.fire with the shared look. o: {title, html, width, confirm, icon, didOpen, preConfirm, confirmColor} */
    function open(o) {
        return Swal.fire($.extend({
            title: o.title,
            html: o.html,
            width: o.width || '760px',
            showCancelButton: true,
            confirmButtonColor: o.confirmColor || (window.APP_COLORS && APP_COLORS.primary),
            cancelButtonColor: window.APP_COLORS && APP_COLORS.danger_dark,
            cancelButtonText: t('cancel', 'Cancel'),
            confirmButtonText: '<i class="mdi ' + (o.icon || 'mdi-content-save') + '"></i> ' + esc(o.confirm || t('save', 'Save')),
            showLoaderOnConfirm: true,
            allowOutsideClick: false,
            customClass: { popup: 'sr-addline-popup' },
            didOpen: o.didOpen,
            preConfirm: o.preConfirm
        }, o.extra || {}));
    }

    /* Success message from the server JSON, then reload (or run a callback). */
    function done(result, after) {
        if (!(result && result.isConfirmed && result.value)) return;
        var r = result.value;
        Swal.fire({ allowOutsideClick: false,
            title: r.title || t('success', 'Success'),
            text: (r.type || r.success === undefined) ? r.message : (r.message && r.message !== 'ok' ? r.message : ''),
            icon: r.type || 'success',
            confirmButtonText: t('ok', 'OK')
        }).then(function () {
            if (typeof after === 'function') after(r); else location.reload();
        });
    }

    window.SRForm = {
        t: t, esc: esc, num: num, today: today,
        field: field, select: select, section: section, statusSeg: statusSeg, choices: choices,
        filePicker: filePicker, bindFilePicker: bindFilePicker, checkFile: checkFile,
        validate: validate, liveClear: liveClear, setSeg: setSeg,
        select2: select2, datepicker: datepicker, post: post, open: open, done: done
    };
})(window, jQuery);
