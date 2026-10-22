<?php
/* Shared markup for the standalone history / report pages (new GUI, assets/css/smart_request.css):
   *_status_history.php (one request: hero, info cards, approval chain, status timeline) and
   employee_*_history.php (one employee: stats + records table).
   Pages keep their own queries and access checks; these helpers only render. */

require_once __DIR__ . '/sr_list_helpers.php';

/* Status -> [tone, mdi icon, label]. Works for request, approver and event statuses. */
if (!function_exists('srh_status')) {
    function srh_status($status)
    {
        $s = strtolower(trim((string)$status));
        $label = $s === '' ? '-' : ucwords(str_replace('_', ' ', $s));
        if ($s === '') return ['slate', 'mdi-help-circle', $label];
        if (strpos($s, 'reject') !== false || strpos($s, 'fail') !== false) return ['red', 'mdi-close-circle', $label];
        if (strpos($s, 'cancel') !== false || strpos($s, 'skip') !== false) return ['slate', 'mdi-cancel', $label];
        // 'pending approval' / 'awaiting approval' must win over the 'approv' match below.
        if (strpos($s, 'await') !== false || strpos($s, 'waiting') !== false) return ['sky', 'mdi-timer-sand', $label];
        if (strpos($s, 'pending') !== false || strpos($s, 'review') !== false || strpos($s, 'objection') !== false) return ['amber', 'mdi-clock', $label];
        if (strpos($s, 'approv') !== false || strpos($s, 'complet') !== false || strpos($s, 'paid') !== false
            || strpos($s, 'closed') !== false || strpos($s, 'settled') !== false || strpos($s, 'acknowledg') !== false
            || strpos($s, 'deliver') !== false || strpos($s, 'process') !== false) return ['green', 'mdi-check-circle', $label];
        if (strpos($s, 'submit') !== false || strpos($s, 'creat') !== false || strpos($s, 'applied') !== false) return ['indigo', 'mdi-send', $label];
        return ['slate', 'mdi-circle', $label];
    }
}

if (!function_exists('srh_status_pill')) {
    function srh_status_pill($status, $label = null)
    {
        [$tone, $icon, $auto] = srh_status($status);
        return sr_pill($tone, $label ?? (function_exists('getDisplayName') ? getDisplayName($auto) : $auto), false, $icon);
    }
}

/* <html>...<body> + logo strip. $extra_head: page-only <style>/<link>. */
if (!function_exists('srh_page_open')) {
    function srh_page_open($title, $extra_head = '')
    {
        global $conDB, $current_lang, $is_rtl, $site_title;
        $favicon = function_exists('get_setting') ? (get_setting($conDB, 'favicon') ?: 'assets/images/favicon.ico') : 'assets/images/favicon.ico';
        $logo = function_exists('get_setting') ? get_setting($conDB, 'logo') : '';
        $css = @filemtime(dirname(__DIR__) . '/assets/css/smart_request.css');
        echo '<!doctype html><html lang="' . sr_h($current_lang ?? 'en') . '"' . (!empty($is_rtl) ? ' dir="rtl"' : '') . '><head>'
            . '<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">'
            . '<title>' . sr_h(($site_title ?? 'Al-Mutlak') . ' - ' . $title) . '</title>'
            . '<link rel="shortcut icon" href="' . sr_h($favicon) . '">'
            . '<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">'
            . '<link href="assets/css/icons.css" rel="stylesheet" type="text/css">'
            . '<link href="assets/css/smart_request.css?v=' . $css . '" rel="stylesheet" type="text/css">'
            // Riyal glyph font (normally pulled in by style.css -> colors_fonts.css, which these pages don't load).
            . '<style>@font-face{font-family:"saudi_riyal";src:url("assets/fonts/saudi_riyal.woff2") format("woff2"),url("assets/fonts/saudi_riyal.woff") format("woff");}'
            . '.icon-saudi_riyal::before{content:"\e900";font-family:"saudi_riyal"!important;}</style>'
            . $extra_head
            . '</head><body class="sr-standalone"><div class="sr-page sr-standalone-wrap srh-page">'
            . '<div class="sr-standalone-brand"><a href="dashboard.php">' . ($logo ? '<img src="' . sr_h($logo) . '" alt="">' : '') . '</a></div>';
    }
}

if (!function_exists('srh_page_close')) {
    function srh_page_close($scripts = '')
    {
        echo '</div>'
            . '<script src="assets/js/jquery.min.js"></script>'
            . '<script src="assets/js/bootstrap.bundle.min.js"></script>'
            . $scripts
            . '</body></html>';
    }
}

/* Page title row with Back + Print. */
if (!function_exists('srh_header')) {
    function srh_header($title, $sub = '', $back_url = '', $actions = '')
    {
        $html = '<div class="sr-head"><div><h1>' . sr_h($title) . '</h1>' . ($sub !== '' ? '<p>' . sr_h($sub) . '</p>' : '') . '</div>'
            . '<div class="sr-head-actions">' . $actions;
        if ($back_url !== '') {
            $html .= '<a href="' . sr_h($back_url) . '" class="sr-btn"><i class="mdi mdi-arrow-left"></i> ' . sr_h(__('back', 'Back')) . '</a>';
        }
        $html .= '<button type="button" class="sr-btn sr-btn-primary" onclick="window.print()"><i class="mdi mdi-printer"></i> ' . sr_h(__('print', 'Print')) . '</button>';
        return $html . '</div></div>';
    }
}

/* Employee / request banner. $meta: [[label, value, mdi icon], ...] shown as chips, each chip in its own tone. */
if (!function_exists('srh_hero')) {
    function srh_hero($name, $avatar_url, array $meta = [], $right_html = '')
    {
        $avatar = $avatar_url
            ? '<img class="srh-hero-avatar" src="' . sr_h($avatar_url) . '" alt="" onerror="this.outerHTML=\'<span class=&quot;sr-avatar srh-hero-avatar&quot;>' . sr_h(sr_initials($name)) . '</span>\'">'
            : '<span class="sr-avatar srh-hero-avatar">' . sr_h(sr_initials($name)) . '</span>';
        $tones = ['blue', 'violet', 'teal', 'amber', 'rose'];
        $chips = '';
        $i = 0;
        foreach ($meta as $m) {
            if (!isset($m[1]) || $m[1] === '' || $m[1] === null) continue;
            $chips .= '<span class="srh-hero-chip is-' . $tones[$i++ % count($tones)] . '">' . (isset($m[2]) ? '<i class="mdi ' . sr_h($m[2]) . '"></i>' : '') . '<small>' . sr_h($m[0]) . '</small> ' . sr_h($m[1]) . '</span>';
        }
        return '<div class="srh-hero">' . $avatar
            . '<div class="srh-hero-body"><div class="srh-hero-name">' . sr_h($name) . '</div><div class="srh-hero-meta">' . $chips . '</div></div>'
            . ($right_html !== '' ? '<div class="srh-hero-right">' . $right_html . '</div>' : '')
            . '</div>';
    }
}

/* Key/value list. Values are raw HTML (escape before passing); null/'' rows are skipped. */
if (!function_exists('srh_kv')) {
    function srh_kv(array $rows)
    {
        $html = '<dl class="sr-kv srh-kv">';
        foreach ($rows as $r) {
            if (!isset($r[1]) || $r[1] === null || $r[1] === '') continue;
            $html .= '<div class="row-kv"><dt>' . (isset($r[2]) ? '<i class="mdi ' . sr_h($r[2]) . '"></i>' : '') . sr_h($r[0]) . '</dt><dd>' . $r[1] . '</dd></div>';
        }
        return $html . '</dl>';
    }
}

/* Card with header. The header icon sits in a coloured chip; each card on the page takes the next tone. */
if (!function_exists('srh_card')) {
    function srh_card($icon, $title, $body_html, $aside = '', $body_class = 'sr-card-body')
    {
        static $n = 0;
        $tones = ['indigo', 'teal', 'violet', 'amber', 'sky', 'rose'];
        return '<div class="sr-card srh-card is-' . $tones[$n++ % count($tones)] . '"><div class="sr-card-head"><h2 class="sr-card-title"><span class="srh-card-icon"><i class="mdi ' . sr_h($icon) . '"></i></span> ' . sr_h($title) . '</h2>'
            . ($aside !== '' ? '<span class="sr-card-sub">' . $aside . '</span>' : '') . '</div>'
            . '<div class="' . sr_h($body_class) . '">' . $body_html . '</div></div>';
    }
}

/* Highlight tiles - dashboard KPI look: separate cards with a coloured accent bar and icon chip; the number rolls up
   (assets/js/sr_count_up.js: .srh-tile-value) and the cards rise in one after another.
   $tiles: [[label, value_html, tone('green'|'indigo'|'amber'|'red'|''), sub_html, mdi icon], ...]
   An empty tone takes the next colour of the palette; a missing icon falls back per position. */
if (!function_exists('srh_tiles')) {
    function srh_tiles(array $tiles)
    {
        $palette = ['blue', 'violet', 'sky', 'teal'];
        $icons = ['mdi-chart-bar', 'mdi-calendar', 'mdi-calendar-check', 'mdi-counter'];
        $html = '<div class="srh-tiles" style="--srh-cols: ' . max(1, min(4, count($tiles))) . ';">';
        foreach (array_values($tiles) as $i => $t) {
            $tone = !empty($t[2]) ? $t[2] : $palette[$i % count($palette)];
            $icon = !empty($t[4]) ? $t[4] : $icons[$i % count($icons)];
            $html .= '<div class="srh-tile is-' . sr_h($tone) . '"><div class="srh-tile-top"><span class="srh-tile-label">' . sr_h($t[0]) . '</span>'
                . '<span class="srh-tile-icon"><i class="mdi ' . sr_h($icon) . '"></i></span></div>'
                . '<b class="srh-tile-value">' . $t[1] . '</b>'
                . (!empty($t[3]) ? '<small>' . $t[3] . '</small>' : '') . '</div>';
        }
        return $html . '</div>';
    }
}

/* Approval chain - the shared stepper card (includes/approval_steps.php, same design as the reports and popups).
   $chain: request_approvers rows (approval_level, status, action_date, note, approver_name, plus optional
   approver_dept_name / approver_avatar / approver_sex; approver_role or user_type for the role fallback).
   $requestStatus: the request's own status (approved request shows approved steps only, rejected hides pending ones). */
if (!function_exists('srh_approval_chain')) {
    function srh_approval_chain(array $chain, $requestStatus)
    {
        require_once __DIR__ . '/approval_steps.php';
        foreach ($chain as &$row) {
            $row['approver_role'] = $row['approver_role'] ?? ($row['user_type'] ?? '');
        }
        unset($row);
        $rows = approval_steps_rows($chain, (string)$requestStatus);
        if (empty($rows)) return srh_card('mdi-sitemap', __('approval_chain'), sr_empty_state(__('no_approvers', 'No approvers')));
        return approval_steps_html($rows, (string)$requestStatus);
    }
}

if (!function_exists('srh_role_label')) {
    function srh_role_label($role)
    {
        $map = ['gm' => 'GM', 'hr' => 'HR', 'hr_payroll' => 'HR Payroll', 'administrator' => 'Administrator', 'finance_officer' => 'Finance', 'dept_user' => 'Manager'];
        $r = strtolower(trim((string)$role));
        return $map[$r] ?? ucwords(str_replace('_', ' ', $r));
    }
}

/* Status history timeline. $rows: [['status'=>, 'by'=>, 'date'=>, 'note'=>], ...] */
if (!function_exists('srh_timeline')) {
    function srh_timeline(array $rows)
    {
        if (empty($rows)) return sr_empty_state(__('no_history', 'No history'));
        $html = '<ul class="sr-activity srh-steps">';
        foreach ($rows as $r) {
            [$tone, $icon, $label] = srh_status($r['status'] ?? '');
            $html .= '<li><span class="sr-activity-icon tone-' . $tone . '"><i class="mdi ' . $icon . '"></i></span><div class="sr-activity-body">'
                . '<div class="sr-activity-title">' . sr_h(function_exists('getDisplayName') ? getDisplayName($label) : $label) . '</div>'
                . (!empty($r['note']) ? '<div class="sr-activity-note">' . nl2br(sr_h($r['note'])) . '</div>' : '')
                . '<div class="sr-activity-meta">'
                . (!empty($r['by']) ? '<span><i class="mdi mdi-account"></i> ' . sr_h($r['by']) . '</span>' : '')
                . (!empty($r['date']) ? '<span><i class="mdi mdi-clock"></i> ' . sr_h(format_safe_date($r['date'], 'd M Y H:i')) . '</span>' : '')
                . '</div></div></li>';
        }
        return $html . '</ul>';
    }
}

/* Money with riyal icon. */
if (!function_exists('srh_money')) {
    function srh_money($v, $decimals = 2)
    {
        return '<span class="srh-money"><i class="icon-saudi_riyal"></i> ' . number_format((float)$v, $decimals) . '</span>';
    }
}

/* Standalone-page error / access-denied box (replaces the old inline red <div>s). */
if (!function_exists('srh_die')) {
    function srh_die($message, $code = 0)
    {
        if ($code) http_response_code($code);
        srh_page_open(__('error', 'Error'));
        echo '<div class="sr-notice tone-red"><i class="mdi mdi-alert-circle"></i><div>' . sr_h($message) . '</div></div>';
        srh_page_close();
        exit;
    }
}

/* ---------- Employee self-service history pages (employee_*_history.php) ---------- */

/* Employee banner from an `employees` row: avatar, name, ID, job/department chips, QR on the right. */
if (!function_exists('srh_emp_hero')) {
    function srh_emp_hero(array $emprow, array $extra_meta = [])
    {
        $qr = './assets/qrcodes/' . (($emprow['eid'] ?? '') . ($emprow['emp_id'] ?? '')) . '.png';
        $right = (!empty($emprow['emp_id']) && file_exists($qr)) ? '<img class="srh-qr" src="' . sr_h($qr) . '" alt="QR">' : '';
        $avatar = function_exists('getAvatarImagePath') ? getAvatarImagePath($emprow['avatar'] ?? '', $emprow['sex'] ?? 1) : ($emprow['avatar'] ?? '');
        $meta = array_merge([[__('employee_id', 'Employee ID'), $emprow['emp_id'] ?? '', 'mdi-account-card-details']], $extra_meta);
        return srh_hero(function_exists('getDisplayName') ? getDisplayName($emprow['name'] ?? '') : ($emprow['name'] ?? ''), $avatar, $meta, $right);
    }
}

/* "Back to profile" link for the employee portal. */
if (!function_exists('srh_profile_url')) {
    function srh_profile_url(array $emprow)
    {
        // Viewing another employee's records (includes/profile_target.php) -> back to their profile
        if (!empty($GLOBALS['is_viewing_other_employee'])) {
            return 'profile.php?emp_id=' . urlencode((string)($emprow['emp_id'] ?? ''));
        }
        return 'profile.php?hashcode=' . urlencode((string)($emprow['emp_id'] ?? '')) . '&verification=' . urlencode((string)($emprow['id'] ?? ''));
    }
}

/* DataTables css for srh_page_open($title, srh_dt_head()). */
if (!function_exists('srh_dt_head')) {
    function srh_dt_head()
    {
        return '<link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">';
    }
}

/* Employee-portal <head> extras: DataTables css, SweetAlert2 and the JS translation table
   used by employee_profile.js / the request JS files (their __() reads window.lang). */
if (!function_exists('srh_portal_head')) {
    function srh_portal_head()
    {
        return srh_dt_head()
            . '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>'
            . '<script>window.lang = ' . json_encode($GLOBALS['translations'] ?? [], JSON_UNESCAPED_UNICODE) . ';</script>';
    }
}

/* Row action menu: primary link + optional ⋮ dropdown items (raw HTML <a>/<button class="dropdown-item">). */
if (!function_exists('srh_row_actions')) {
    function srh_row_actions($primary_html, array $menu_items = [])
    {
        $html = '<div class="sr-actions">' . $primary_html;
        if ($menu_items) {
            $html .= '<div class="btn-group dropdown"><a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>'
                . '<div class="dropdown-menu dropdown-menu-right">' . implode('', $menu_items) . '</div></div>';
        }
        return $html . '</div>';
    }
}

/* Search box for a records card header (drives the DataTable via srh_dt_js). */
if (!function_exists('srh_search_box')) {
    function srh_search_box($id = 'srhSearch')
    {
        return '<div class="sr-search srh-search no-print"><i class="mdi mdi-magnify"></i><input type="search" id="' . sr_h($id) . '" placeholder="' . sr_h(__('search', 'Search')) . '..." autocomplete="off"></div>';
    }
}

/* DataTables scripts + init in the new GUI style (own search box, chevron pager, sr-empty states). */
if (!function_exists('srh_dt_js')) {
    function srh_dt_js($table_sel, $order_col = 0, $order_dir = 'desc', $empty_icon = 'mdi-inbox', $search_id = 'srhSearch', $non_orderable = [])
    {
        $lang = [
            'info' => __('showing', 'Showing') . ' _START_ ' . __('to', 'to') . ' _END_ ' . __('of', 'of') . ' _TOTAL_ ' . __('entries', 'entries'),
            'infoEmpty' => __('showing', 'Showing') . ' 0 ' . __('to', 'to') . ' 0 ' . __('of', 'of') . ' 0 ' . __('entries', 'entries'),
            'infoFiltered' => '',
            'paginate' => ['first' => __('first', 'First'), 'last' => __('last', 'Last'), 'next' => '<i class="mdi mdi-chevron-right"></i>', 'previous' => '<i class="mdi mdi-chevron-left"></i>'],
            'emptyTable' => '<div class="sr-empty"><i class="mdi ' . sr_h($empty_icon) . '"></i>' . sr_h(__('no_data_available_in_table', 'No data available')) . '</div>',
            'zeroRecords' => '<div class="sr-empty"><i class="mdi mdi-magnify"></i>' . sr_h(__('no_matching_records_found', 'No matching records found')) . '</div>',
        ];
        $opts = [
            'dom' => 'rtip',
            'pageLength' => 10,
            'order' => [[(int)$order_col, $order_dir]],
            'columnDefs' => $non_orderable ? [['targets' => array_values($non_orderable), 'orderable' => false]] : [],
            'language' => $lang,
        ];
        return '<script src="./plugins/datatables/jquery.dataTables.min.js"></script>'
            . '<script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>'
            . '<script>$(function () {'
            . 'var t = $(' . json_encode($table_sel) . ');'
            . 'if (!t.length || !t.find("tbody tr").length) return;'
            . 'var dt = t.DataTable(' . json_encode($opts, JSON_UNESCAPED_UNICODE) . ');'
            . '$(' . json_encode('#' . $search_id) . ').on("input", function () { dt.search(this.value).draw(); });'
            . '});</script>';
    }
}
