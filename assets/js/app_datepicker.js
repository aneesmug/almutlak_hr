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
 *   AppDate.hijri('#el', {...});             // Hijri (Umm al-Qura) picker, value iYYYY-iMM-iDD
 *   AppDate.hijriPair('#greg', '#hijri', {}) // Gregorian + Hijri inputs kept in sync
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

    // =====================================================================================
    // Hijri (Umm al-Qura) calendar - flatpickr has none, so AppDate.hijri() draws its own
    // calendar using flatpickr's markup/classes: it looks and behaves exactly like the other
    // AppDate pickers (same theme, dark mode, RTL fixes, blocked-day styling).
    // Conversion: moment-hijri's own Umm al-Qura table (embedded below, no dependency), so values
    // are identical to what the old picker stored and what the rest of the app computes.
    // Values are Latin digits, 'Y-m-d' Hijri by default (e.g. 1448-03-15 = iYYYY-iMM-iDD).
    // =====================================================================================
    var HIJRI_MONTHS = {
        ar: ['محرم', 'صفر', 'ربيع الأول', 'ربيع الآخر', 'جمادى الأولى', 'جمادى الآخرة', 'رجب', 'شعبان', 'رمضان', 'شوال', 'ذو القعدة', 'ذو الحجة'],
        en: ['Muharram', 'Safar', 'Rabi al-Awwal', 'Rabi al-Thani', 'Jumada al-Ula', 'Jumada al-Akhirah', 'Rajab', 'Shaban', 'Ramadan', 'Shawwal', 'Dhu al-Qadah', 'Dhu al-Hijjah']
    };
    var WEEKDAYS_EN = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    var ARROW_PREV = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 17 17"><g></g><path d="M5.207 8.471l7.146 7.147-0.707 0.707-7.853-7.854 7.854-7.853 0.707 0.707-7.147 7.146z"></path></svg>';
    var ARROW_NEXT = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 17 17"><g></g><path d="M13.207 8.472l-7.854 7.854-0.707-0.707 7.146-7.146-7.146-7.148 0.707-0.707 7.854 7.854z"></path></svg>';
    var intlHijri = null;

    // Umm al-Qura month table - the exact data moment-hijri uses (the old Hijri picker and the
    // rest of the app convert with it), so every conversion here matches existing stored values.
    // Month lengths from 1 Muharram 1356 AH (MJDN 28607); one digit per month: 9 = 29,
    // 0 = 30 (8 = 28, one quirk of the source data). Outside 1356-1500 AH the browser's
    // islamic-umalqura calendar is used.
    var UQ_FIRST_MJDN = 28607;
    var UQ_LENGTHS =
        '990909009909090909090900009099099009000909909909090909090900090909090909090909090909090909090900090909080009090909090900' +
        '090909090909090909090909090909090900090909009009090909090909090990909000909090990900909090909090090909009900090909099009' +
        '909900090909099090900900090909090909909090909090090909090909090090990909090090099090909009090909090909090909090099090009' +
        '009909090900990909090900900909090909090909090900090909009099090909090900990909090900090999090900090909099009090090990909' +
        '090009099090909009090909090909009090909090909009009099090900909090990909000909099090900090909909900900090990990090090909' +
        '090909090090909090909090090909099090090090909909090009090990909009009099099009000909909900900900990990900090909099090090' +
        '090909099090090900909099090900090909909090090090909909000090990999000090099099900090090909909090090090990909090090900909' +
        '909090090090990990009009099099000900909909900900900990909090900909090990900090909099090090090909909090090099090909090090' +
        '909090909090090099090990090009909909090009090990909000909099090900900909909090900909090909090900909009909090900090990990' +
        '900009099099090009090909909009009090990909009009090990909009009099090909000909909090900099090909900090909090990090900909' +
        '099090900090909909900900090990990090009099099009009090909900909009090909090909090090909909009009090990909000909099099000' +
        '090909909090090090990909090090909090990090090099099009090009909909009009090909909009090090990909009009099090909000909909' +
        '909000900990990900900099099090090090909909090090900990909090900900909909090090090990990090009099099009009009909900900900' +
        '990909090900909090990900900909099090900090909909090090090990909090090900990909090090099090990090009909909090009090990909' +
        '000909099090900900990909090900909090909090909009009909909000';
    var UQ_TABLE = (function () {
        var t = [UQ_FIRST_MJDN];
        for (var i = 0; i < UQ_LENGTHS.length; i++) {
            var c = UQ_LENGTHS.charAt(i);
            t.push(t[i] + (c === '0' ? 30 : c === '8' ? 28 : 29));
        }
        return t;
    })();
    var MJD_EPOCH_UTC = Date.UTC(1858, 10, 16); // moment-hijri: mjdn = JDN(noon) - 2400000

    function toMjdn(date) {
        return Math.round((Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()) - MJD_EPOCH_UTC) / 864e5);
    }
    function fromMjdn(mjdn) {
        var u = new Date(MJD_EPOCH_UTC + mjdn * 864e5);
        return new Date(u.getUTCFullYear(), u.getUTCMonth(), u.getUTCDate());
    }

    function intlToHijriParts(date) {
        if (!intlHijri) {
            intlHijri = new Intl.DateTimeFormat('en-u-ca-islamic-umalqura-nu-latn', { timeZone: 'UTC', year: 'numeric', month: 'numeric', day: 'numeric' });
        }
        var p = {};
        intlHijri.formatToParts(new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate())))
            .forEach(function (x) { p[x.type] = x.value; });
        return { y: parseInt(p.year, 10), m: parseInt(p.month, 10), d: parseInt(p.day, 10) };
    }

    // Gregorian Date -> {y, m, d} Hijri
    function toHijriParts(date) {
        date = toDateOnly(date);
        var mjdn = toMjdn(date);
        if (mjdn >= UQ_TABLE[0] && mjdn < UQ_TABLE[UQ_TABLE.length - 1]) {
            var i = 1;
            while (UQ_TABLE[i] <= mjdn) i++;
            var totalMonths = i + 16260, cYears = Math.floor((totalMonths - 1) / 12);
            return { y: cYears + 1, m: totalMonths - 12 * cYears, d: mjdn - UQ_TABLE[i - 1] + 1 };
        }
        return intlToHijriParts(date);
    }

    function hijriCmp(a, b) { return (a.y - b.y) || (a.m - b.m) || (a.d - b.d); }

    // {y, m, d} Hijri -> Gregorian Date (local midnight), or null when that day doesn't exist
    // (e.g. the 30th of a 29-day month). Estimates, then walks to the exact day with the
    // same converter used above, so both directions always agree.
    function fromHijriParts(y, m, d) {
        if (!y || !m || !d || m < 1 || m > 12 || d < 1 || d > 30) return null;
        var idx = (y - 1) * 12 + 1 + (m - 1) - 16260;
        if (idx >= 1 && idx < UQ_TABLE.length) {
            return d <= UQ_TABLE[idx] - UQ_TABLE[idx - 1] ? fromMjdn(UQ_TABLE[idx - 1] + d - 1) : null;
        }
        var target = { y: y, m: m, d: d };
        var est = new Date(Date.UTC(622, 6, 19) + ((y - 1) * 354.36667 + (m - 1) * 29.5306 + (d - 1)) * 864e5);
        var date = new Date(est.getUTCFullYear(), est.getUTCMonth(), est.getUTCDate());
        for (var i = 0; i < 40; i++) {
            var h = toHijriParts(date);
            var c = hijriCmp(h, target);
            if (c === 0) return date;
            var diff = (h.y - y) * 354 + (h.m - m) * 29 + (h.d - d);
            date.setDate(date.getDate() - (Math.abs(diff) > 2 ? diff : (c > 0 ? 1 : -1)));
        }
        return null;
    }

    function hijriMonthLength(y, m) {
        var a = fromHijriParts(y, m, 1);
        var b = m === 12 ? fromHijriParts(y + 1, 1, 1) : fromHijriParts(y, m + 1, 1);
        return a && b ? Math.round((b - a) / 864e5) : 30;
    }

    function pad2(n) { return (n < 10 ? '0' : '') + n; }

    // format tokens: Y (1448), m (03), n (3), d (05), j (5)
    function formatHijri(p, fmt) {
        return (fmt || 'Y-m-d').replace(/[Ymndj]/g, function (t) {
            return { Y: String(p.y), m: pad2(p.m), n: String(p.m), d: pad2(p.d), j: String(p.d) }[t];
        });
    }

    function parseHijri(str, fmt) {
        var nums = String(str || '').match(/\d+/g);
        if (!nums || nums.length < 3) return null;
        var order = (fmt || 'Y-m-d').replace(/[^Ymndj]/g, '').split('');
        var p = {};
        order.forEach(function (t, i) {
            var v = parseInt(nums[i], 10);
            if (t === 'Y') p.y = v; else if (t === 'm' || t === 'n') p.m = v; else p.d = v;
        });
        return fromHijriParts(p.y, p.m, p.d) ? p : null;
    }

    // 'today' / '+2y' / Gregorian 'Y-m-d' / Date, or a Hijri 'Y-m-d' (year < 1700) -> Date
    function resolveAnyDate(v) {
        if (!v) return null;
        if (typeof v === 'string') {
            var m = /^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/.exec(v.trim());
            if (m && +m[1] < 1700) return fromHijriParts(+m[1], +m[2], +m[3]);
            if (m) return parseYmd(m[1] + '-' + pad2(+m[2]) + '-' + pad2(+m[3]));
            if (v === 'today') return toDateOnly(new Date());
        }
        var r = resolveDate(v);
        return r instanceof Date ? toDateOnly(r) : null;
    }

    function HijriPicker(el, o) {
        this.el = el;
        this.o = o;
        this.isHijri = true;
        this.fmt = o.format || 'Y-m-d';
        this.rtl = isRTL();
        this.lang = isArabic() ? 'ar' : 'en';
        this.minDate = resolveAnyDate(o.minDate);
        this.maxDate = resolveAnyDate(o.maxDate);
        this.disable = buildDisable(o);
        this.selectedDates = [];
        this.isOpen = false;

        var start = o.defaultDate !== undefined ? o.defaultDate : el.value;
        if (start) this._select(start, false, false);
        var view = this.selectedDates[0] ? toHijriParts(this.selectedDates[0]) : toHijriParts(new Date());
        this.view = { y: view.y, m: view.m };

        if (!o.inline && !o.allowInput) el.setAttribute('readonly', 'readonly');
        el.setAttribute('autocomplete', 'off');
        el.classList.add('app-dp-input', 'app-dp-hijri-input');
        if (o.locked) el.classList.add('app-dp-locked');

        this._build();
        var self = this;
        this._onFocus = function () { if (!self.o.locked) self.open(); };
        this._onInput = function () {
            var p = parseHijri(el.value, self.fmt);
            if (p) self._select(formatHijri(p, 'Y-m-d'), true, false);
        };
        if (!o.inline) {
            el.addEventListener('click', this._onFocus);
            el.addEventListener('focus', this._onFocus);
        }
        if (o.allowInput) el.addEventListener('change', this._onInput);
        el._appHijri = this;
    }

    HijriPicker.prototype._isBlocked = function (date) {
        if (this.minDate && date < this.minDate) return true;
        if (this.maxDate && date > this.maxDate) return true;
        var ymd = AppDate.format(date);
        for (var i = 0; i < this.disable.length; i++) {
            var r = this.disable[i];
            if (typeof r === 'function') { if (r(date, toHijriParts(date))) return true; }
            else if (r && typeof r === 'object' && r.from) {
                var f = resolveAnyDate(r.from), t = resolveAnyDate(r.to);
                if (f && t && date >= f && date <= t) return true;
            } else {
                var d = resolveAnyDate(r);
                if (d && AppDate.format(d) === ymd) return true;
            }
        }
        return false;
    };

    // v: Hijri string (in this.fmt or Y-m-d) or a Gregorian Date
    HijriPicker.prototype._select = function (v, triggerChange, fromUser) {
        var date = null;
        if (v instanceof Date) date = toDateOnly(v);
        else if (v) {
            var p = parseHijri(v, this.fmt) || parseHijri(v, 'Y-m-d');
            date = p ? fromHijriParts(p.y, p.m, p.d) : resolveAnyDate(v);
        }
        if (!date) { this.selectedDates = []; this.el.value = ''; }
        else {
            // a preloaded (old) value outside the limits is kept; only new picks are blocked
            if (fromUser && this._isBlocked(date)) return false;
            this.selectedDates = [date];
            this.el.value = formatHijri(toHijriParts(date), this.fmt);
        }
        if (triggerChange) {
            this.el.dispatchEvent(new Event('change', { bubbles: true }));
            if (typeof this.o.onChange === 'function') {
                this.o.onChange.call(this, this.selectedDates.slice(), this.el.value, this);
            }
        }
        return true;
    };

    HijriPicker.prototype._build = function () {
        var self = this, cal = document.createElement('div');
        cal.className = 'flatpickr-calendar app-dp app-dp-hijri' + (this.rtl ? ' app-dp-rtl' : '') + (this.o.inline ? ' inline app-dp-inline' : ' animate');
        if (this.rtl) cal.setAttribute('dir', 'rtl');
        cal.tabIndex = -1;
        var months = HIJRI_MONTHS[this.lang].map(function (n, i) { return '<option value="' + (i + 1) + '">' + n + '</option>'; }).join('');
        var wd = this.lang === 'ar' ? arabic.weekdays.shorthand : WEEKDAYS_EN;
        cal.innerHTML =
            '<div class="flatpickr-months">' +
                '<span class="flatpickr-prev-month">' + ARROW_PREV + '</span>' +
                '<div class="flatpickr-month"><div class="flatpickr-current-month">' +
                    '<select class="flatpickr-monthDropdown-months app-dp-hijri-month" aria-label="month">' + months + '</select>' +
                    '<select class="flatpickr-monthDropdown-months app-dp-hijri-year" aria-label="year"></select>' +
                '</div></div>' +
                '<span class="flatpickr-next-month">' + ARROW_NEXT + '</span>' +
            '</div>' +
            '<div class="flatpickr-innerContainer"><div class="flatpickr-rContainer">' +
                '<div class="flatpickr-weekdays"><div class="flatpickr-weekdaycontainer">' +
                    wd.map(function (n) { return '<span class="flatpickr-weekday">' + n + '</span>'; }).join('') +
                '</div></div>' +
                '<div class="flatpickr-days"><div class="dayContainer"></div></div>' +
            '</div></div>' +
            '<div class="app-dp-hijri-foot"></div>';
        this.cal = cal;
        this.$month = cal.querySelector('.app-dp-hijri-month');
        this.$year = cal.querySelector('.app-dp-hijri-year');
        this.$days = cal.querySelector('.dayContainer');
        this.$foot = cal.querySelector('.app-dp-hijri-foot');

        cal.querySelector('.flatpickr-prev-month').addEventListener('click', function () { self._shift(-1); });
        cal.querySelector('.flatpickr-next-month').addEventListener('click', function () { self._shift(1); });
        this.$month.addEventListener('change', function () { self.view.m = +this.value; self._render(); });
        this.$year.addEventListener('change', function () { self.view.y = +this.value; self._render(); });
        this.$days.addEventListener('click', function (e) {
            var day = e.target.closest('.flatpickr-day');
            if (!day || day.classList.contains('flatpickr-disabled')) return;
            if (self._select(parseYmd(day.getAttribute('data-date')), true, true) && !self.o.inline) self.close();
            self._render();
        });

        if (this.o.inline) {
            this.el.parentNode.insertBefore(cal, this.el.nextSibling);
        } else if (this.o.static || (this.o.static === undefined && this.el.closest && this.el.closest('.modal'))) {
            // inside a Bootstrap modal the calendar stays in the modal's DOM (focus + scrolling)
            var wrap = document.createElement('div');
            wrap.className = 'flatpickr-wrapper';
            wrap.style.display = 'block';
            this.el.parentNode.insertBefore(wrap, this.el);
            wrap.appendChild(this.el);
            wrap.appendChild(cal);
            cal.classList.add('static');
            this.wrapper = wrap;
        } else {
            document.body.appendChild(cal);
        }
        this._render();
    };

    HijriPicker.prototype._shift = function (n) {
        var m = this.view.m + n, y = this.view.y;
        if (m < 1) { m = 12; y--; } else if (m > 12) { m = 1; y++; }
        this.view = { y: y, m: m };
        this._render();
    };

    HijriPicker.prototype._render = function () {
        var v = this.view, todayYmd = AppDate.format(new Date());
        var selYmd = this.selectedDates[0] ? AppDate.format(this.selectedDates[0]) : '';
        var years = '', from = Math.min(v.y - 80, 1356), to = Math.max(v.y + 30, toHijriParts(new Date()).y + 30);
        for (var y = to; y >= from; y--) years += '<option value="' + y + '">' + y + '</option>';
        this.$year.innerHTML = years;
        this.$year.value = String(v.y);
        this.$month.value = String(v.m);

        var first = fromHijriParts(v.y, v.m, 1);
        if (!first) return;
        var start = new Date(first);
        start.setDate(start.getDate() - ((first.getDay() - arabic.firstDayOfWeek + 7) % 7));
        var html = '';
        for (var i = 0; i < 42; i++) {
            var d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
            var h = toHijriParts(d), ymd = AppDate.format(d);
            if (i >= 35 && (h.m !== v.m || h.y !== v.y)) break; // drop an all-next-month last row
            var cls = 'flatpickr-day';
            if (h.m !== v.m || h.y !== v.y) cls += h.y * 12 + h.m < v.y * 12 + v.m ? ' prevMonthDay' : ' nextMonthDay';
            if (ymd === todayYmd) cls += ' today';
            if (ymd === selYmd) cls += ' selected';
            if (this._isBlocked(d)) cls += ' flatpickr-disabled';
            html += '<span class="' + cls + '" data-date="' + ymd + '" title="' + ymd + '" tabindex="-1">' + h.d + '</span>';
        }
        this.$days.innerHTML = html;
        this.$foot.textContent = this.selectedDates[0]
            ? (this.lang === 'ar' ? 'الميلادي: ' : 'Gregorian: ') + AppDate.format(this.selectedDates[0])
            : '';
        this.$foot.style.display = this.selectedDates[0] ? '' : 'none';
    };

    HijriPicker.prototype._place = function () {
        if (this.o.inline || this.wrapper) return;
        var r = this.el.getBoundingClientRect(), cal = this.cal;
        var w = cal.offsetWidth, h = cal.offsetHeight;
        var sx = window.pageXOffset, sy = window.pageYOffset;
        var top = r.bottom + 2;
        if (r.bottom + h + 4 > window.innerHeight && r.top - h - 2 > 0) top = r.top - h - 2;
        var left = this.rtl ? r.right - w : r.left;
        left = Math.max(4, Math.min(left, window.innerWidth - w - 4));
        cal.style.top = (top + sy) + 'px';
        cal.style.left = (left + sx) + 'px';
    };

    HijriPicker.prototype.open = function () {
        if (this.o.inline || this.isOpen || this.o.locked) return;
        var self = this;
        var sel = this.selectedDates[0] ? toHijriParts(this.selectedDates[0]) : null;
        if (sel) this.view = { y: sel.y, m: sel.m };
        this._render();
        this.cal.classList.add('open');
        this.isOpen = true;
        this._place();
        this._outside = function (e) {
            if (!self.cal.contains(e.target) && e.target !== self.el) self.close();
        };
        this._key = function (e) { if (e.key === 'Escape') self.close(); };
        this._reposition = function () { if (self.isOpen) self._place(); };
        document.addEventListener('mousedown', this._outside, true);
        document.addEventListener('touchstart', this._outside, true);
        document.addEventListener('keydown', this._key);
        window.addEventListener('resize', this._reposition);
        this._scrollers = scrollParents(this.el).concat([window]);
        this._scrollers.forEach(function (p) { p.addEventListener('scroll', self._reposition, { passive: true }); });
        if (typeof this.o.onOpen === 'function') this.o.onOpen.call(this, this.selectedDates.slice(), this.el.value, this);
    };

    HijriPicker.prototype.close = function () {
        if (!this.isOpen) return;
        var self = this;
        this.cal.classList.remove('open');
        this.isOpen = false;
        document.removeEventListener('mousedown', this._outside, true);
        document.removeEventListener('touchstart', this._outside, true);
        document.removeEventListener('keydown', this._key);
        window.removeEventListener('resize', this._reposition);
        (this._scrollers || []).forEach(function (p) { p.removeEventListener('scroll', self._reposition); });
        if (typeof this.o.onClose === 'function') this.o.onClose.call(this, this.selectedDates.slice(), this.el.value, this);
    };

    // flatpickr-compatible surface used around the app: setDate / clear / set / destroy
    HijriPicker.prototype.setDate = function (v, triggerChange) {
        this._select(v, !!triggerChange, false);
        var sel = this.selectedDates[0] ? toHijriParts(this.selectedDates[0]) : null;
        if (sel) this.view = { y: sel.y, m: sel.m };
        this._render();
    };
    HijriPicker.prototype.clear = function () { this.setDate(null, true); };
    HijriPicker.prototype.set = function (key, value) {
        if (key === 'minDate') this.minDate = resolveAnyDate(value);
        else if (key === 'maxDate') this.maxDate = resolveAnyDate(value);
        else if (key === 'disable') this.disable = buildDisable({ disable: value || [] });
        this._render();
    };
    HijriPicker.prototype.destroy = function () {
        this.close();
        this.el.removeEventListener('click', this._onFocus);
        this.el.removeEventListener('focus', this._onFocus);
        this.el.removeEventListener('change', this._onInput);
        if (this.wrapper) {
            this.wrapper.parentNode.insertBefore(this.el, this.wrapper);
            this.wrapper.parentNode.removeChild(this.wrapper);
        } else if (this.cal.parentNode) {
            this.cal.parentNode.removeChild(this.cal);
        }
        this.el.classList.remove('app-dp-hijri-input');
        delete this.el._appHijri;
    };

    function createHijri(target, opts) {
        var el = toElement(target);
        if (!el) return null;
        if (el._appHijri) el._appHijri.destroy();
        if (el._flatpickr) el._flatpickr.destroy();
        return new HijriPicker(el, extend(opts || {}));
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
            return el ? el._flatpickr || el._appHijri || null : null;
        },
        // Hijri (Umm al-Qura) picker. Value 'Y-m-d' Hijri (opts.format: Y m n d j). Same options as
        // single(); minDate/maxDate/disable* may be Gregorian ('today', '+1y', 'Y-m-d', Date) or
        // Hijri 'Y-m-d' strings. onChange(gregorianDates, hijriText, picker).
        hijri: function (target, opts) { return createHijri(target, opts); },
        // A Gregorian input and a Hijri input kept in sync both ways. Shared opts (minDate, maxDate,
        // disable*...) apply to both; opts.onChange(gregorianYmd, hijriYmd) fires after either changes.
        // Returns { gregorian: flatpickr, hijri: HijriPicker }.
        hijriPair: function (gregTarget, hijriTarget, opts) {
            opts = opts || {};
            var shared = extend(opts);
            delete shared.onChange;
            var hp = null;
            var notify = function () {
                if (typeof opts.onChange === 'function') {
                    opts.onChange(toElement(gregTarget).value, toElement(hijriTarget).value);
                }
            };
            var gp = create(gregTarget, extend(shared, opts.gregorian || {}, {
                onChange: function (dates) { if (hp) hp.setDate(dates[0] || null, false); notify(); }
            }), 'single');
            hp = createHijri(hijriTarget, extend(shared, opts.hijriOptions || {}, {
                defaultDate: toElement(hijriTarget).value || (gp && gp.selectedDates[0]) || null,
                onChange: function (dates) { if (gp) gp.setDate(dates[0] || null, false); notify(); }
            }));
            // a pair loaded with only the Gregorian value fills in the Hijri one (and vice versa)
            if (gp && hp && gp.selectedDates[0] && !hp.selectedDates[0]) hp.setDate(gp.selectedDates[0], false);
            if (gp && hp && hp.selectedDates[0] && !gp.selectedDates[0]) gp.setDate(hp.selectedDates[0], false);
            return { gregorian: gp, hijri: hp };
        },
        // Date | 'Y-m-d' Gregorian -> Hijri text ('Y-m-d' or fmt); '' when invalid
        toHijri: function (date, fmt) {
            var d = date instanceof Date ? date : parseYmd(date);
            return d ? formatHijri(toHijriParts(d), fmt) : '';
        },
        // Hijri text ('Y-m-d' or fmt) -> Gregorian 'Y-m-d'; '' when invalid
        fromHijri: function (text, fmt) {
            var p = parseHijri(text, fmt);
            var d = p ? fromHijriParts(p.y, p.m, p.d) : null;
            return d ? AppDate.format(d) : '';
        },
        // Change min/max of an existing picker (accepts the same values as the options).
        setMin: function (target, v) { var fp = AppDate.get(target); if (fp) fp.set('minDate', fp.isHijri ? v : resolveDate(v)); },
        setMax: function (target, v) { var fp = AppDate.get(target); if (fp) fp.set('maxDate', fp.isHijri ? v : resolveDate(v)); },
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
            if (method === 'hijri') {
                return this.each(function () { AppDate.hijri(this, value); });
            }
            return this.each(function () {
                var fp = this._flatpickr || this._appHijri;
                if (!fp) return;
                switch (method) {
                    case 'setDate': fp.setDate(value || null, false); break;
                    case 'setMin': fp.set('minDate', fp.isHijri ? value : resolveDate(value)); break;
                    case 'setMax': fp.set('maxDate', fp.isHijri ? value : resolveDate(value)); break;
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
