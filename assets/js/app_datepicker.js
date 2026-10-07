/**
 * AppDate - project-wide date picker (wraps flatpickr, ./plugins/flatpickr).
 *
 * Replaces bootstrap-datepicker / bootstrap-daterangepicker. Works the same in LTR
 * (English) and RTL (Arabic): the language/direction is read from <html dir/lang>, so
 * callers never pass it. Values are always written to the input as Y-m-d (ranges as
 * "Y-m-d - Y-m-d"), whatever the UI language, so backend parsing never changes.
 *
 * Usage:
 *   AppDate.single('#el', { minDate: 'today', maxDate: '2026-12-31', onChange: fn });
 *   AppDate.range('#el', { defaultDate: [from, to], onClose: fn });
 *   AppDate.inline('#el', {...});            // always-open calendar (good inside Swal)
 *   AppDate.month('#el', {...});             // month picker, value Y-m (monthSelect plugin)
 *   AppDate.setMin('#el', '+0d') / AppDate.setMax('#el', date)
 *   $('#el').appDate({...}) / $('#el').appDate('setDate', '2026-01-01')
 *
 * Options: format (flatpickr tokens, default 'Y-m-d'), defaultDate, allowInput, inline,
 * static, position, onChange(dates, str, fp), onOpen, onClose, onReady.
 *
 * Blocking dates (any combination):
 *   minDate / maxDate      'today' | '+3d' / '-10d' / '+1m' | 'Y-m-d' | Date - block before / after
 *   disableDates           ['2026-01-01', ...]                  - block exact days
 *   disableRanges          [{ from: 'Y-m-d', to: 'Y-m-d' }]     - block periods
 *   disableWeekdays        [5, 6]  (0 = Sunday ... 6 = Saturday) - block week days
 *   disableFn              function(date) { return true to block }
 *   enableDates            ['Y-m-d', ...]                       - allow ONLY these days
 *   locked: true           - show the value but the calendar never opens
 *
 * Loaded from includes/main_menu.php, i.e. BEFORE jQuery on most pages, so nothing here
 * may need jQuery at load time; the $.fn.appDate bridge is installed once jQuery exists.
 */
(function (window) {
    'use strict';

    if (window.AppDate) return; // included twice
    if (!window.flatpickr) {
        console.warn('AppDate: flatpickr is not loaded');
        return;
    }

    var VALUE_FORMAT = 'Y-m-d';
    var RANGE_SEPARATOR = ' - ';

    var arabic = {
        weekdays: {
            shorthand: ['أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'],
            longhand: ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']
        },
        months: {
            shorthand: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
            longhand: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر']
        },
        firstDayOfWeek: 0,
        rangeSeparator: RANGE_SEPARATOR,
        weekAbbreviation: 'أسبوع',
        scrollTitle: 'قم بالتمرير للزيادة',
        toggleTitle: 'اضغط للتبديل',
        amPM: ['ص', 'م'],
        yearAriaLabel: 'سنة',
        monthAriaLabel: 'شهر',
        hourAriaLabel: 'ساعة',
        minuteAriaLabel: 'دقيقة',
        time_24hr: true
    };
    var english = { firstDayOfWeek: 0, rangeSeparator: RANGE_SEPARATOR };

    function extend() {
        return Object.assign.apply(Object, [{}].concat([].slice.call(arguments)));
    }

    function toElement(target) {
        if (target && target.jquery) return target[0];
        return typeof target === 'string' ? document.querySelector(target) : target;
    }

    function isRTL() {
        var html = document.documentElement;
        return (html.getAttribute('dir') || (document.body && document.body.getAttribute('dir')) || '').toLowerCase() === 'rtl';
    }

    function isArabic() {
        return isRTL() || (document.documentElement.getAttribute('lang') || '').toLowerCase().indexOf('ar') === 0;
    }

    function toDateOnly(d) {
        return new Date(d.getFullYear(), d.getMonth(), d.getDate());
    }

    function parseYmd(value) {
        if (value instanceof Date) return toDateOnly(value);
        var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(value || '');
        return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null;
    }

    // 'today', bootstrap-datepicker style offsets ('+0d', '-10d', '+1m', '-1y'), 'Y-m-d' or Date.
    function resolveDate(v) {
        if (!v) return null;
        var m = typeof v === 'string' && /^([+-]\d+)([dmy])$/.exec(v.trim());
        if (m) {
            var d = toDateOnly(new Date()), n = parseInt(m[1], 10);
            if (m[2] === 'd') d.setDate(d.getDate() + n);
            else if (m[2] === 'm') d.setMonth(d.getMonth() + n);
            else d.setFullYear(d.getFullYear() + n);
            return d;
        }
        return v;
    }

    // Collect the friendly disable* options into flatpickr's `disable` list.
    function buildDisable(o) {
        var list = (o.disable || []).slice();
        (o.disableDates || []).forEach(function (d) { list.push(d); });
        (o.disableRanges || []).forEach(function (r) { list.push({ from: r.from, to: r.to }); });
        if (o.disableWeekdays && o.disableWeekdays.length) {
            var days = o.disableWeekdays.map(Number);
            list.push(function (date) { return days.indexOf(date.getDay()) !== -1; });
        }
        if (typeof o.disableFn === 'function') list.push(o.disableFn);
        return list;
    }

    // Scrollable parents (Bootstrap modal body, Swal html container, ...) - the calendar
    // lives on <body>, so it is re-positioned whenever one of them scrolls.
    function scrollParents(el) {
        var out = [];
        for (var p = el.parentElement; p && p !== document.body; p = p.parentElement) {
            var oy = window.getComputedStyle(p).overflowY;
            if (oy === 'auto' || oy === 'scroll') out.push(p);
        }
        return out;
    }

    function create(target, opts, mode) {
        var el = toElement(target);
        if (!el) return null;
        if (el._flatpickr) el._flatpickr.destroy();

        var o = extend(opts || {});
        var rtl = isRTL();
        // Inside a Bootstrap modal the calendar must stay in the modal's DOM: Bootstrap
        // pulls focus back into the modal on any focusin outside it (breaks the month
        // dropdown / year field), and the calendar has to scroll with the modal.
        if (o.static === undefined && el.closest && el.closest('.modal')) o.static = true;
        var userOnChange = o.onChange, userOnClose = o.onClose, userOnOpen = o.onOpen, userOnReady = o.onReady;

        var config = {
            dateFormat: o.format || VALUE_FORMAT,
            locale: extend(isArabic() ? arabic : english, o.locale || {}),
            mode: mode,
            inline: mode !== 'range' && !!o.inline,
            defaultDate: o.defaultDate !== undefined ? o.defaultDate : (el.value || null),
            minDate: resolveDate(o.minDate),
            maxDate: resolveDate(o.maxDate),
            disable: buildDisable(o),
            // An existing value that is now blocked (e.g. an already-expired date on an
            // edit form) is kept as-is; the blocking only applies to new picks.
            allowInvalidPreload: true,
            allowInput: !!o.allowInput,
            clickOpens: !o.locked,
            disableMobile: true,
            monthSelectorType: 'dropdown',
            position: o.position || (rtl ? 'auto right' : 'auto left'),
            static: !!o.static,
            appendTo: o.appendTo,
            onReady: function (selected, str, fp) {
                fp.calendarContainer.classList.add('app-dp');
                if (rtl) {
                    fp.calendarContainer.classList.add('app-dp-rtl');
                    fp.calendarContainer.setAttribute('dir', 'rtl');
                }
                if (o.inline) fp.calendarContainer.classList.add('app-dp-inline');
                if (userOnReady) userOnReady.apply(this, arguments);
            },
            onOpen: function (selected, str, fp) {
                var reposition = function () { fp.isOpen && fp._positionCalendar(); };
                fp._appDpScroll = scrollParents(fp.input);
                fp._appDpScroll.forEach(function (p) { p.addEventListener('scroll', reposition, { passive: true }); });
                fp._appDpReposition = reposition;
                if (userOnOpen) userOnOpen.apply(this, arguments);
            },
            onClose: function (selected, str, fp) {
                (fp._appDpScroll || []).forEach(function (p) { p.removeEventListener('scroll', fp._appDpReposition); });
                fp._appDpScroll = [];
                // Range picker closed after only the first click: keep it as a 1-day range.
                if (mode === 'range' && selected.length === 1) {
                    fp.setDate([selected[0], selected[0]], true);
                }
                if (userOnClose) userOnClose.apply(this, arguments);
            },
            onChange: function (selected, str, fp) {
                fp.input.dispatchEvent(new Event('change', { bubbles: true }));
                if (userOnChange) userOnChange.apply(this, arguments);
            }
        };
        if (o.onMonthChange) config.onMonthChange = o.onMonthChange;
        if (o.onDayCreate) config.onDayCreate = o.onDayCreate;
        if (o.enableDates) config.enable = o.enableDates;
        if (o.plugins) config.plugins = o.plugins;

        if (!o.inline && !o.allowInput) el.setAttribute('readonly', 'readonly');
        el.setAttribute('autocomplete', 'off');
        el.classList.add('app-dp-input');
        if (o.locked) el.classList.add('app-dp-locked');

        var fp = window.flatpickr(el, config);
        if (config.static && fp && fp.calendarContainer.parentNode) {
            fp.calendarContainer.parentNode.style.display = 'block';
        }
        return fp;
    }

    var AppDate = {
        VALUE_FORMAT: VALUE_FORMAT,
        RANGE_SEPARATOR: RANGE_SEPARATOR,
        isRTL: isRTL,
        parse: parseYmd,
        resolve: resolveDate,
        format: function (date) { return date ? window.flatpickr.formatDate(date, VALUE_FORMAT) : ''; },
        single: function (target, opts) { return create(target, opts, 'single'); },
        range: function (target, opts) { return create(target, opts, 'range'); },
        inline: function (target, opts) { return create(target, extend(opts || {}, { inline: true }), 'single'); },
        // Month picker (value 'Y-m' unless opts.format is given); needs plugins/flatpickr/plugins/monthSelect.
        month: function (target, opts) {
            opts = opts || {};
            if (!window.monthSelectPlugin) {
                console.warn('AppDate.month: monthSelect plugin is not loaded');
                return create(target, extend(opts, { format: opts.format || 'Y-m' }), 'single');
            }
            var format = opts.format || 'Y-m';
            return create(target, extend(opts, {
                format: format,
                plugins: [new window.monthSelectPlugin({ shorthand: false, dateFormat: format, altFormat: format })]
            }), 'single');
        },
        // Same as single() for every element matching a selector / jQuery set.
        all: function (selector, opts) {
            var els = selector && selector.jquery ? selector.toArray() : [].slice.call(document.querySelectorAll(selector));
            return els.map(function (el) { return create(el, opts, 'single'); });
        },
        get: function (target) {
            var el = toElement(target);
            return el ? el._flatpickr || null : null;
        },
        // Change min/max of an existing picker (accepts the same values as the options).
        setMin: function (target, v) { var fp = AppDate.get(target); if (fp) fp.set('minDate', resolveDate(v)); },
        setMax: function (target, v) { var fp = AppDate.get(target); if (fp) fp.set('maxDate', resolveDate(v)); },
        // [from, to] as Y-m-d strings for a range input (empty strings when unset).
        rangeValues: function (target) {
            var fp = AppDate.get(target);
            if (fp && fp.selectedDates.length) {
                var d = fp.selectedDates;
                return [AppDate.format(d[0]), AppDate.format(d[d.length - 1])];
            }
            var el = toElement(target);
            var parts = ((el && el.value) || '').split(RANGE_SEPARATOR);
            return [parts[0] || '', parts[1] || parts[0] || ''];
        },
        destroy: function (target) {
            var fp = AppDate.get(target);
            if (fp) fp.destroy();
        }
    };

    // jQuery bridge: $(el).appDate(opts) | $(el).appDate('range', opts) |
    // $(el).appDate('setDate'|'setMin'|'setMax'|'clear'|'open'|'close'|'destroy', value)
    function installBridge() {
        var $ = window.jQuery;
        if (!$ || !$.fn || $.fn.appDate) return;
        $.fn.appDate = function (method, value) {
            if (typeof method === 'object' || method === undefined) {
                return this.each(function () { AppDate.single(this, method); });
            }
            if (method === 'range') {
                return this.each(function () { AppDate.range(this, value); });
            }
            return this.each(function () {
                var fp = this._flatpickr;
                if (!fp) return;
                switch (method) {
                    case 'setDate': fp.setDate(value || null, false); break;
                    case 'setMin': fp.set('minDate', resolveDate(value)); break;
                    case 'setMax': fp.set('maxDate', resolveDate(value)); break;
                    case 'clear': fp.clear(); break;
                    case 'open': fp.open(); break;
                    case 'close': fp.close(); break;
                    case 'destroy': fp.destroy(); break;
                }
            });
        };
    }
    AppDate.installBridge = installBridge;
    installBridge();
    document.addEventListener('DOMContentLoaded', installBridge);

    window.AppDate = AppDate;
})(window);
