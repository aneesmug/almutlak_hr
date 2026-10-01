/* Smart Request line editor used inside SweetAlert2 modals:
   - all_requests.php  > New Request
   - open_request.php  > Add Line
   Needs window.SR_LINES_CONFIG (printed by includes/smart_request_lines_js.php):
   { vatRate, t: {...labels} }. Styling: assets/css/smart_request.css (.sr-addline*).
   Amounts shown here are a preview only - the server recalculates them on save. */
(function (window, $) {
    'use strict';

    var cfg = window.SR_LINES_CONFIG || { vatRate: 15, t: {} };
    var T = cfg.t;
    var VAT_RATE = parseFloat(cfg.vatRate) || 0;
    var locationOptions = null;

    function money(n) {
        return (parseFloat(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }
    function label(key, fallback) {
        return esc(T[key] || fallback || key);
    }

    function loadLocations() {
        if (locationOptions !== null) {
            return $.Deferred().resolve(locationOptions).promise();
        }
        return $.ajax({
            url: './includes/ajaxFile/ajaxLocation.php',
            dataType: 'JSON', type: 'POST',
            data: { ajaxType: 'section_view' }
        }).then(function (res) {
            var opts = '<option value="">' + label('select', 'Select') + '</option>';
            if (res && res.status == 200 && res.data) {
                opts += res.data.map(function (item) {
                    return '<option value="' + esc(item.section_name) + '">' + esc(item.section_name) + '</option>';
                }).join('');
            }
            locationOptions = opts;
            return opts;
        }, function () {
            locationOptions = '<option value="">' + label('select', 'Select') + '</option>';
            return $.Deferred().resolve(locationOptions).promise();
        });
    }

    function lineHTML() {
        return '' +
        '<div class="sr-addline">' +
            '<div class="sr-addline-head">' +
                '<span class="sr-addline-no"></span>' +
                '<span class="sr-addline-total">' + label('lineTotal', 'Total') + ': <strong class="js-line-total">0.00</strong> <i class="icon-saudi_riyal"></i></span>' +
                '<button type="button" class="sr-addline-remove" title="' + label('remove', 'Remove') + '"><i class="mdi mdi-close"></i></button>' +
            '</div>' +
            '<div class="sr-addline-grid">' +
                '<div class="g-item"><label>' + label('item', 'Item') + ' <span class="text-danger">*</span></label><input type="text" name="item_name[]" class="form-control js-item" autocomplete="off" required></div>' +
                '<div class="g-ref"><label>' + label('reference', 'Reference') + '</label><input type="text" name="reference[]" class="form-control" autocomplete="off"></div>' +
                '<div class="g-loc"><label>' + label('location', 'Location') + ' <span class="text-danger">*</span></label><select name="location[]" class="form-control js-loc" required>' + (locationOptions || '') + '</select></div>' +
                '<div class="g-qty"><label>' + label('qty', 'Quantity') + ' <span class="text-danger">*</span></label><input type="number" step="any" min="0" name="quantity[]" class="form-control js-qty" value="1" required></div>' +
                '<div class="g-price"><label>' + label('price', 'Unit cost') + ' <span class="text-danger">*</span></label><input type="number" step="0.01" min="0" name="product_price[]" class="form-control js-price" placeholder="0.00" required></div>' +
                '<div class="g-vat"><label>' + label('vatOpt', 'VAT') + '</label>' +
                    '<select name="vat_option[]" class="form-control js-vat">' +
                        '<option value="exclude" selected>' + label('exclude', 'Exclude') + ' ' + VAT_RATE + '%</option>' +
                        '<option value="include">' + label('include', 'Include') + ' ' + VAT_RATE + '%</option>' +
                        '<option value="no_vat">' + label('noVat', 'No VAT') + '</option>' +
                    '</select>' +
                '</div>' +
                '<div class="g-disc"><label>' + label('discount', 'Discount') + '</label><input type="number" step="0.01" min="0" name="idiscount[]" class="form-control js-disc" value="0"></div>' +
            '</div>' +
            '<div class="sr-addline-calc js-line-calc"></div>' +
        '</div>';
    }

    function calcLine($line) {
        var qty = parseFloat($line.find('.js-qty').val()) || 0;
        var price = parseFloat($line.find('.js-price').val()) || 0;
        var disc = parseFloat($line.find('.js-disc').val()) || 0;
        var opt = $line.find('.js-vat').val();
        var rate = opt === 'no_vat' ? 0 : VAT_RATE;
        var gross = qty * price;
        var net, vat, amount;
        if (opt === 'include') {
            amount = gross; net = gross / (1 + rate / 100); vat = amount - net;
        } else {
            net = gross; vat = net * rate / 100; amount = net + vat;
        }
        var total = amount - disc;
        $line.find('.js-line-total').text(money(total));
        $line.find('.js-line-calc').html(
            label('net', 'Net') + ': <b>' + money(net) + '</b> &middot; ' + label('vat', 'VAT') + ' (' + rate + '%): <b>' + money(vat) + '</b>' +
            (disc > 0 ? ' &middot; ' + label('discount', 'Discount') + ': <b>&minus;' + money(disc) + '</b>' : '')
        );
        return { net: net, vat: vat, total: total };
    }

    /* Wire the editor inside $form. $list is the container for the line cards.
       onChange(sum) gets { net, vat, total, count } after every change. */
    function bind($form, $list, onChange) {
        function recalc() {
            var sum = { net: 0, vat: 0, total: 0, count: 0 };
            var $lines = $list.children('.sr-addline');
            $lines.each(function (i) {
                $(this).find('.sr-addline-no').text('#' + (i + 1));
                var r = calcLine($(this));
                sum.net += r.net; sum.vat += r.vat; sum.total += r.total;
            });
            sum.count = $lines.length;
            $list.find('.sr-addline-remove').toggle($lines.length > 1);
            if (onChange) onChange(sum);
        }

        function addLine(focus) {
            var $line = $(lineHTML());
            $list.append($line);
            recalc();
            if (focus) {
                $line.find('.js-item').trigger('focus');
                $list.scrollTop($list[0].scrollHeight);
            }
        }

        $form.on('input change', '.js-qty, .js-price, .js-disc, .js-vat, .js-recalc', recalc);
        $form.on('input change', '.is-invalid', function () { $(this).removeClass('is-invalid'); });
        $form.on('click', '.js-add-line', function () { addLine(true); });
        $form.on('click', '.sr-addline-remove', function () {
            $(this).closest('.sr-addline').remove();
            recalc();
        });
        $form.on('keydown', function (ev) {
            if (ev.key === 'Enter' && (ev.ctrlKey || ev.metaKey)) { ev.preventDefault(); addLine(true); }
            else if (ev.key === 'Enter' && ev.target.tagName !== 'TEXTAREA') { ev.preventDefault(); }
        });

        return { addLine: addLine, recalc: recalc };
    }

    /* Marks invalid fields; returns the first invalid element or null. */
    function validate($form) {
        var firstBad = null;
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[required], input[type="number"]').each(function () {
            var v = $.trim($(this).val() || '');
            var isNum = $(this).attr('type') === 'number';
            var bad = ($(this).is('[required]') && v === '')
                || ($(this).hasClass('js-qty') && !(parseFloat(v) > 0))
                || (isNum && v !== '' && (isNaN(parseFloat(v)) || parseFloat(v) < 0));
            if (bad) { $(this).addClass('is-invalid'); firstBad = firstBad || this; }
        });
        if (firstBad) firstBad.focus();
        return firstBad;
    }

    /* Shared markup for the "add line" button + shortcut hint under the line list. */
    function addButtonHTML() {
        return '<div class="d-flex align-items-center flex-wrap" style="gap: 10px;">' +
            '<button type="button" class="sr-btn sr-btn-sm js-add-line"><i class="mdi mdi-plus"></i> ' + label('addAnother', 'Add another line') + '</button>' +
            '<span class="sr-card-sub">' + label('hint', 'Tip: press Ctrl + Enter to add another line.') + '</span>' +
        '</div>';
    }

    window.SRLines = {
        money: money,
        esc: esc,
        label: label,
        loadLocations: loadLocations,
        bind: bind,
        validate: validate,
        addButtonHTML: addButtonHTML
    };
})(window, jQuery);
