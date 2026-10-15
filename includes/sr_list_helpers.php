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
            'my_requests' => 'mdi-send',
            'submitted_by_me' => 'mdi-send',
            'pending_approval' => 'mdi-clock',
            'pending' => 'mdi-clock',
            'pending_payment' => 'mdi-credit-card',
            'pending_deduction' => 'mdi-calculator',
            'approved' => 'mdi-check-circle',
            'completed' => 'mdi-check-circle',
            'rejected' => 'mdi-close-circle',
            'cancelled' => 'mdi-cancel',
            'all' => 'mdi-format-list-bulleted',
            '' => 'mdi-format-list-bulleted',
            'draft' => 'mdi-pencil',
            'paid' => 'mdi-cash-multiple',
            'waiting_for_delivery' => 'mdi-truck-delivery',
        ];
        return $icons[$key] ?? 'mdi-format-list-bulleted';
    }
}

/* Card tone per status key (sr-scard st-<tone>). */
if (!function_exists('sr_status_tone')) {
    function sr_status_tone($key)
    {
        $tones = [
            'my_pending' => 'amber', 'my_team' => 'indigo', 'my_dept' => 'sky', 'my_requests' => 'indigo', 'submitted_by_me' => 'indigo',
            'pending_approval' => 'amber', 'pending' => 'amber', 'pending_payment' => 'sky', 'pending_deduction' => 'sky',
            'approved' => 'green', 'completed' => 'green', 'rejected' => 'red', 'cancelled' => 'slate', 'all' => 'indigo',
            '' => 'indigo', 'draft' => 'slate', 'paid' => 'green', 'waiting_for_delivery' => 'sky',
        ];
        return $tones[$key] ?? 'slate';
    }
}

/* One-line hint under the card label. */
if (!function_exists('sr_status_hint')) {
    function sr_status_hint($key)
    {
        $hints = [
            'my_pending' => __('status_hint_my_pending', 'Waiting for your action'),
            'my_team' => __('status_hint_my_team', 'Your direct reports'),
            'my_dept' => __('status_hint_my_dept', 'Your department'),
            'my_requests' => __('status_hint_my_requests', 'Submitted by you'),
            'submitted_by_me' => __('status_hint_my_requests', 'Submitted by you'),
            'pending_approval' => __('status_hint_pending', 'In the approval chain'),
            'pending' => __('status_hint_pending', 'In the approval chain'),
            'pending_payment' => __('status_hint_pending_payment', 'Waiting for payment'),
            'pending_deduction' => __('status_hint_pending_deduction', 'Waiting for deduction'),
            'approved' => __('status_hint_approved', 'Approved requests'),
            'completed' => __('status_hint_completed', 'Fully processed'),
            'rejected' => __('status_hint_rejected', 'Declined requests'),
            'cancelled' => __('status_hint_cancelled', 'Cancelled requests'),
            'all' => __('status_hint_all', 'Everything you can see'),
            '' => __('status_hint_all', 'Everything you can see'),
            'draft' => __('status_hint_draft', 'Not submitted yet'),
            'paid' => __('status_hint_paid', 'Payment done'),
            'waiting_for_delivery' => __('status_hint_waiting_delivery', 'Approved, to be delivered'),
        ];
        return $hints[$key] ?? '';
    }
}

/* Variables of a page scope without the superglobals - for sr_status_counts() closures. */
if (!function_exists('sr_scope_vars')) {
    function sr_scope_vars(array $vars)
    {
        return array_diff_key($vars, array_flip(['GLOBALS', '_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', '_ENV', '_REQUEST', '_SESSION', 'this']));
    }
}

/* Count for every status card. $build($status) runs the page's own filter + count block for
   that status and returns its variables; $totalVar names the count variable it sets. The
   current status reuses the count the page already has. */
if (!function_exists('sr_status_counts')) {
    function sr_status_counts(array $statuses, $current, $currentTotal, callable $build, $totalVar)
    {
        $counts = [];
        foreach (array_keys($statuses) as $key) {
            if ((string)$key === (string)$current) {
                $counts[$key] = (int)$currentTotal;
                continue;
            }
            $vars = $build($key);
            $counts[$key] = (int)($vars[$totalVar] ?? 0);
        }
        return $counts;
    }
}

/* Share bar for a card: $count as % of $base (the "All" count), capped at 100. */
if (!function_exists('sr_scard_foot')) {
    function sr_scard_foot($count, $base, $key = null)
    {
        $pct = $base > 0 ? min(100, (int)round($count / $base * 100)) : 0;
        $attr = $key === null ? '' : ' data-bar="' . sr_h($key) . '"';
        $pattr = $key === null ? '' : ' data-pct="' . sr_h($key) . '"';
        return '<span class="sr-scard-foot"><span class="sr-scard-bar"><i' . $attr . ' style="width: ' . $pct . '%"></i></span>'
            . '<span class="sr-scard-pct"' . $pattr . '>' . $pct . '%</span></span>';
    }
}

/* Cards per row: up to 5 in one line, more split evenly over two lines so labels stay readable. */
if (!function_exists('sr_stat_cols')) {
    function sr_stat_cols($count)
    {
        $count = (int)$count;
        return $count > 5 ? (int)ceil($count / 2) : max(1, $count);
    }
}

/* Status cards (or the older tab bar when no $counts are given). Clicking one sets the
   hidden #statusFilter and calls applyFilters(). */
if (!function_exists('sr_status_tabs')) {
    function sr_status_tabs(array $statuses, $current, $total, $counts = null)
    {
        $html = '<input type="hidden" id="statusFilter" value="' . sr_h($current) . '">';
        if (is_array($counts)) {
            $html .= '<div class="sr-scards" id="srStatusTabs" role="tablist" style="--sr-scard-cols: ' . sr_stat_cols(count($statuses)) . ';">';
            // Share base: the "All" card, else the biggest card
            $base = isset($counts['all']) ? (int)$counts['all'] : (int)max(array_map('intval', $counts ?: [0]));
            foreach ($statuses as $key => $label) {
                $on = ((string)$current === (string)$key);
                $n = (int)($counts[$key] ?? 0);
                $hint = sr_status_hint($key);
                // "My pending" with items = needs action: count in tone + pulsing marker
                $alert = ($key === 'my_pending' && $n > 0) ? ' sr-scard-alert' : '';
                $html .= '<button type="button" class="sr-scard st-' . sr_status_tone($key) . ($on ? ' active' : '') . $alert . '"'
                    . ' data-status="' . sr_h($key) . '" role="tab" aria-selected="' . ($on ? 'true' : 'false') . '" title="' . sr_h($hint ?: $label) . '">'
                    . '<span class="sr-scard-ico"><i class="mdi ' . sr_status_icon($key) . '"></i></span>'
                    . '<span class="sr-scard-body"><span class="sr-scard-label">' . sr_h($label) . '</span>'
                    . ($hint !== '' ? '<span class="sr-scard-hint">' . sr_h($hint) . '</span>' : '') . '</span>'
                    . '<span class="sr-scard-value">' . number_format($n) . '</span>'
                    . sr_scard_foot($n, $base)
                    . '</button>';
            }
            return $html . '</div>';
        }
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
        $('#srStatusTabs').on('click', '.nav-link, .sr-scard', function() {
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
