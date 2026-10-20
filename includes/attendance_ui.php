<?php
// Shared attendance UI (attendance.php + view_employee.php Attendance tab) so both pages show the
// same status tiles and the same date filter. Styles: assets/css/smart_request.css ("Attendance tiles
// + date filter"); behaviour: assets/js/attendance_ui.js (window.AttUI).
// Tile rules match list_attendance() in includes/ajaxFile/attendanceAjax.php and AttUI.matches().

if (!function_exists('attendance_tile_defs')) {
    /** key => [dot class, label, tone, FA icon]; '' = All */
    function attendance_tile_defs() {
        return [
            ''           => ['dot-all', __('all', 'All'), 'indigo', 'fa-calendar-days'],
            'present'    => ['dot-green', __('present', 'Present'), 'green', 'fa-user-check'],
            'late'       => ['dot-amber', __('late', 'Late'), 'amber', 'fa-hourglass-end'],
            'early'      => ['dot-sky', __('early_leave', 'Early leave'), 'sky', 'fa-person-walking-arrow-right'],
            'incomplete' => ['dot-red', __('incomplete', 'Incomplete'), 'red', 'fa-circle-exclamation'],
            'dayoff'     => ['dot-slate', __('day_off', 'Day off'), 'slate', 'fa-mug-hot'],
        ];
    }
}

if (!function_exists('attendance_tiles_html')) {
    /** Clickable status tiles; counts go into [data-count="all|present|..."] */
    function attendance_tiles_html($id) {
        $html = '<div class="sr-tiles att-tiles" id="' . htmlspecialchars($id) . '">';
        foreach (attendance_tile_defs() as $key => $t) {
            $html .= '<button type="button" class="sr-tile' . ($key === '' ? ' active' : '') . '" data-group="' . $key . '">'
                . '<span class="sr-tile-label"><span class="sr-dot ' . $t[0] . '"></span>' . htmlspecialchars($t[1]) . '</span>'
                . '<span class="sr-tile-value" data-count="' . ($key === '' ? 'all' : $key) . '">&ndash;</span>'
                . '<span class="att-tile-ico tone-' . $t[2] . '"><i class="fa-solid ' . $t[3] . '"></i></span>'
                . '</button>';
        }
        return $html . '</div>';
    }
}

if (!function_exists('attendance_datefilter_html')) {
    /** Today / This week / This month / All + From - To pickers. $ids: range, from, to; $active: '', today, week, month */
    function attendance_datefilter_html(array $ids, $active = '') {
        $btn = function ($range, $label) use ($active) {
            return '<button type="button" data-range="' . $range . '"' . ($range === $active ? ' class="active"' : '') . '>' . htmlspecialchars($label) . '</button>';
        };
        $date = function ($id, $label) {
            return '<span class="att-date"><i class="mdi mdi-calendar"></i><input type="text" id="' . htmlspecialchars($id) . '" placeholder="'
                . htmlspecialchars($label) . '" aria-label="' . htmlspecialchars($label) . '" autocomplete="off"></span>';
        };
        return '<div class="att-datefilter">'
            . '<div class="att-range" id="' . htmlspecialchars($ids['range']) . '">'
            . $btn('today', __('today', 'Today')) . $btn('week', __('this_week', 'This week'))
            . $btn('month', __('this_month', 'This month')) . $btn('', __('all', 'All'))
            . '</div>'
            . $date($ids['from'], __('from', 'From')) . '<span class="att-sep">&ndash;</span>' . $date($ids['to'], __('to', 'To'))
            . '</div>';
    }
}
