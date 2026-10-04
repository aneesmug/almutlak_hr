<?php
/* Shared markup for the server-paginated approval list pages (new GUI, assets/css/smart_request.css):
   all_applied_vac.php, all_applied_loan.php, all_applied_business_trip.php, all_applied_employee_transfers.php,
   all_applied_salary_increment.php, all_settlements.php, all_payroll_approvals.php, all_resignations.php.
   The pages keep their own applyFilters() that reads #statusFilter / #searchFilter / #limitFilter. */

if (!function_exists('sr_h')) {
    function sr_h($v)
    {
        return htmlspecialchars((string)$v, ENT_QUOTES);
    }
}

if (!function_exists('sr_initials')) {
    function sr_initials($name)
    {
        $p = preg_split('/\s+/', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
        $i = mb_substr($p[0] ?? '', 0, 1) . (count($p) > 1 ? mb_substr($p[count($p) - 1], 0, 1) : '');
        return mb_strtoupper($i ?: '?');
    }
}

if (!function_exists('sr_status_icon')) {
    function sr_status_icon($key)
    {
        $icons = [
            'my_pending' => 'mdi-timer-sand',
            'my_team' => 'mdi-account-multiple',
            'my_dept' => 'mdi-domain',
            'pending_approval' => 'mdi-clock',
            'pending' => 'mdi-clock',
            'pending_payment' => 'mdi-credit-card',
            'pending_deduction' => 'mdi-calculator',
            'approved' => 'mdi-check-circle',
            'completed' => 'mdi-check-circle',
            'rejected' => 'mdi-close-circle',
            'cancelled' => 'mdi-cancel',
            'all' => 'mdi-format-list-bulleted',
        ];
        return $icons[$key] ?? 'mdi-format-list-bulleted';
    }
}

/* Status tabs. Clicking one sets the hidden #statusFilter and calls applyFilters(). */
if (!function_exists('sr_status_tabs')) {
    function sr_status_tabs(array $statuses, $current, $total)
    {
        $html = '<input type="hidden" id="statusFilter" value="' . sr_h($current) . '">';
        $html .= '<ul class="sr-tabs" id="srStatusTabs">';
        foreach ($statuses as $key => $label) {
            $on = ((string)$current === (string)$key);
            $html .= '<li><a class="nav-link' . ($on ? ' active' : '') . '" data-status="' . sr_h($key) . '">'
                . '<i class="mdi ' . sr_status_icon($key) . '"></i>' . sr_h($label)
                . ($on ? '<span class="sr-count">' . (int)$total . '</span>' : '')
                . '</a></li>';
        }
        return $html . '</ul>';
    }
}

/* Search box + optional reset button + extra right-side html. */
if (!function_exists('sr_list_toolbar')) {
    function sr_list_toolbar($search, $reset_js = '', $right_html = '')
    {
        $ph = __('search_by_name_id');
        $html = '<div class="sr-toolbar">'
            . '<div class="sr-search"><i class="mdi mdi-magnify"></i>'
            . '<input type="search" id="searchFilter" placeholder="' . sr_h($ph) . '..." value="' . sr_h($search) . '" autocomplete="off" aria-label="' . sr_h($ph) . '">'
            . '</div><div class="sr-toolbar-right">' . $right_html;
        if ($reset_js !== '') {
            $html .= '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" onclick="' . sr_h($reset_js) . '"><i class="mdi mdi-filter-remove"></i> ' . sr_h(__('reset', 'Reset')) . '</button>';
        }
        return $html . '</div></div>';
    }
}

/* Employee cell: avatar initials + name + id. */
if (!function_exists('sr_person_cell')) {
    function sr_person_cell($name, $sub)
    {
        return '<div class="sr-person"><span class="sr-avatar">' . sr_h(sr_initials($name)) . '</span>'
            . '<div style="min-width: 0;"><span class="sr-cell-title">' . sr_h($name) . '</span>'
            . '<span class="sr-cell-sub sr-mono">' . sr_h($sub) . '</span></div></div>';
    }
}

/* Applied/created cell: date + "time ago". */
if (!function_exists('sr_time_cell')) {
    function sr_time_cell($datetime)
    {
        global $current_lang;
        if (empty($datetime)) return '<span class="text-muted">&ndash;</span>';
        $ago = (($current_lang ?? 'en') === 'ar') ? timeAgoAr($datetime) : timeAgo($datetime);
        return '<span class="sr-date">' . sr_h(format_safe_date($datetime, 'd M Y'))
            . '<small title="' . sr_h(format_safe_date($datetime, 'Y-m-d H:i:s')) . '">' . sr_h($ago) . '</small></span>';
    }
}

/* Pill with dot. */
if (!function_exists('sr_pill')) {
    function sr_pill($tone, $text, $xs = false, $icon = '')
    {
        return '<span class="sr-pill' . ($xs ? ' sr-pill-xs' : '') . ' tone-' . sr_h($tone) . '">'
            . ($icon ? '<i class="mdi ' . sr_h($icon) . '"></i>' : '<span class="sr-dot"></span>')
            . sr_h($text) . '</span>';
    }
}

/* Empty state inside the card. */
if (!function_exists('sr_empty_state')) {
    function sr_empty_state($title, $text = '')
    {
        return '<div class="sr-empty"><i class="mdi mdi-inbox"></i>'
            . '<strong style="display: block; color: var(--sr-text); font-size: 15px;">' . sr_h($title) . '</strong>'
            . sr_h($text) . '</div>';
    }
}

/* JS wiring for tabs + Enter-to-search (call after applyFilters() exists). */
if (!function_exists('sr_list_js')) {
    function sr_list_js()
    {
        return <<<'JS'
<script>
    $(function() {
        $('#srStatusTabs').on('click', '.nav-link', function() {
            $('#statusFilter').val($(this).data('status'));
            applyFilters();
        });
        $('#searchFilter').on('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); applyFilters(); }
        });
        $('#srFilters').on('keydown', 'input', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); applyFilters(); }
        });
        $('#srFilterToggle').on('click', function() {
            $('#srFilters').slideToggle(150);
        });
    });
</script>
JS;
    }
}
