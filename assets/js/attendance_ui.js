/* Shared attendance UI behaviour (attendance.php + view_employee.php Attendance tab).
   Markup comes from includes/attendance_ui.php. Exposes window.AttUI. Needs jQuery; AppDate optional. */
(function (window, $) {
    'use strict';

    function ymd(d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }

    // [from, to] for a quick-range key ('' = no limit). Week starts Saturday.
    function rangeDates(r) {
        var now = new Date();
        if (r === 'today') return [ymd(now), ymd(now)];
        if (r === 'week') { var s = new Date(now); s.setDate(now.getDate() - ((now.getDay() + 1) % 7)); return [ymd(s), ymd(now)]; }
        if (r === 'month') return [ymd(new Date(now.getFullYear(), now.getMonth(), 1)), ymd(now)];
        return ['', ''];
    }

    // Tile rules - same as list_attendance() state groups in includes/ajaxFile/attendanceAjax.php
    function matches(group, state) {
        var s = String(state || '');
        switch (group) {
            case 'present': return s === 'Present';
            case 'late': return s.indexOf('Late') === 0;
            case 'early': return s.indexOf('Early Leave') > -1;
            case 'incomplete': return s.indexOf('Incomplete') > -1;
            case 'dayoff': return s === 'Day Off' || s === 'Leave' || s === 'Absent';
            default: return true;
        }
    }

    // Counts per tile for client-side rows ({state: ...}).
    function count(rows) {
        var c = { all: rows.length, present: 0, late: 0, early: 0, incomplete: 0, dayoff: 0 };
        rows.forEach(function (r) {
            ['present', 'late', 'early', 'incomplete', 'dayoff'].forEach(function (g) { if (matches(g, r.state)) c[g]++; });
        });
        return c;
    }

    function setCounts(tilesSel, counts) {
        $(tilesSel).find('[data-count]').each(function () {
            var v = counts[$(this).data('count')];
            $(this).text(v == null ? 0 : Number(v).toLocaleString('en-US'));
        });
    }

    // Status tiles: click = filter, click the active tile again (or All) = back to All. onChange(group).
    function bindTiles(tilesSel, onChange) {
        var current = '';
        var api = {
            get: function () { return current; },
            set: function (g, silent) {
                current = g || '';
                $(tilesSel).find('.sr-tile').removeClass('active').filter('[data-group="' + current + '"]').addClass('active');
                if (!silent && onChange) onChange(current);
            }
        };
        $(tilesSel).on('click', '.sr-tile', function () {
            var g = $(this).data('group') || '';
            api.set(g && g === current ? '' : g);
        });
        return api;
    }

    /* Quick ranges + From/To AppDate pickers (no future days, To never before From).
       o: { range, from, to, active: ''|'today'|'week'|'month', onChange(from, to) } */
    function bindDateFilter(o) {
        var $range = $(o.range), from = o.from, to = o.to;
        var fpOf = function (sel) { return window.AppDate ? AppDate.get(sel) : null; };
        var setDate = function (sel, val) {
            var fp = fpOf(sel);
            if (fp) { if (val) fp.setDate(val, false); else fp.clear(false); } else $(sel).val(val);
        };
        var link = function () {
            if (!window.AppDate) return;
            AppDate.setMin(to, $(from).val() || null);
            AppDate.setMax(from, $(to).val() || 'today');
        };
        var fire = function () { if (o.onChange) o.onChange($(from).val() || '', $(to).val() || ''); };
        var apply = function (r) {
            var d = rangeDates(r);
            setDate(from, d[0]);
            setDate(to, d[1]);
            link();
        };

        if (window.AppDate) {
            AppDate.single(from, { maxDate: 'today' });
            AppDate.single(to, { maxDate: 'today' });
        }
        apply(o.active || '');
        $range.find('button').removeClass('active').filter('[data-range="' + (o.active || '') + '"]').addClass('active');

        $range.on('click', 'button', function () {
            $range.find('button').removeClass('active');
            $(this).addClass('active');
            apply($(this).data('range') || '');
            fire();
        });
        // A picked day (AppDate fires 'change') = custom range
        $(from + ', ' + to).on('change', function () {
            $range.find('button').removeClass('active');
            link();
            fire();
        });
        return {
            values: function () { return [$(from).val() || '', $(to).val() || '']; }
        };
    }

    window.AttUI = { ymd: ymd, rangeDates: rangeDates, matches: matches, count: count, setCounts: setCounts, bindTiles: bindTiles, bindDateFilter: bindDateFilter };
})(window, jQuery);
