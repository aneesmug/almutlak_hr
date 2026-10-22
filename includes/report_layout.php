<?php
/**
 * Shared layout for the request report pages (vacation, loan, business trip, resignation ...).
 *
 *   $report = ['title' => ..., 'request_no' => ..., 'status' => ..., 'dates' => [label => date], 'emp_id' => ...];
 *   include __DIR__ . '/includes/report_open.php';     page shell + report header + employee banner
 *   ... the report's own body (sections: .report-section / .section-title / .grid-details / .detail-item ...)
 *   $report_end = ['chain' => $approval_chain, 'chain_status' => ..., 'signatures' => [...]];
 *   include __DIR__ . '/includes/report_close.php';    approval chain + signatures + "generated on" footer + scripts
 *
 * The two templates run at file scope (main_menu.php / topbar.php need the page's globals); the markup comes
 * from the functions below. Pages keep their own access checks and queries - the layout only renders.
 * Styles: assets/css/report_layout.css (+ assets/css/approval_steps.css for the chain).
 */

require_once __DIR__ . '/approval_steps.php';

$GLOBALS['report_layout_title'] = $GLOBALS['report_layout_title'] ?? '';

/** Employee row for the banner: name, ids, photo, job, department, company, joining date. */
if (!function_exists('report_employee')) {
    function report_employee($conDB, string $empId): array
    {
        $stmt = $conDB->prepare("SELECT e.emp_id, e.name, e.iqama, e.avatar, e.sex, e.joining_date, e.status,
                d.dep_nme, d.dep_nme_ar, c.comp_name, c.comp_name_ar, j.job, j.job_ar
            FROM employees e
            LEFT JOIN department d ON d.id = e.dept
            LEFT JOIN companies c ON c.comp_id = e.comp_no
            LEFT JOIN ac_jobs j ON j.id = e.actual_job
            WHERE e.emp_id = ? LIMIT 1");
        $stmt->bind_param('s', $empId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        return $row;
    }
}

/** Report status -> [tone, mdi icon, label] for the header badge. */
if (!function_exists('report_status_meta')) {
    function report_status_meta(string $status): array
    {
        $s = strtolower(trim($status));
        $labels = [
            'pending_approval' => __('pending_approval'), 'pending' => __('pending', 'Pending'), 'awaiting' => __('awaiting', 'Awaiting'),
            'approved' => __('approved', 'Approved'), 'paid' => __('paid', 'Paid'), 'completed' => __('completed', 'Completed'),
            'rejected' => __('rejected', 'Rejected'), 'cancelled' => __('cancelled', 'Cancelled'),
        ];
        [$tone, $icon, $auto] = srh_status($s);
        return [$tone, $icon, $labels[$s] ?? getDisplayName($auto)];
    }
}

/** <!doctype> ... </head>. $o: title, extra_head (page-only <style>/<link>). */
if (!function_exists('report_head_html')) {
    function report_head_html(array $o): string
    {
        global $conDB, $current_lang, $is_rtl, $site_title;
        $GLOBALS['report_layout_title'] = (string)($o['title'] ?? '');
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $cssV = @filemtime(dirname(__DIR__) . '/assets/css/report_layout.css');
        return '<!doctype html><html lang="' . $e($current_lang ?? 'en') . '"' . (!empty($is_rtl) ? ' dir="rtl"' : '') . '><head>'
            . '<meta charset="utf-8" /><title>' . $e(($site_title ?? '') . ' - ' . ($o['title'] ?? '')) . '</title>'
            . '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"><meta http-equiv="X-UA-Compatible" content="IE=edge" />'
            . '<link rel="shortcut icon" href="' . $e(get_setting($conDB, 'favicon')) . '">'
            . '<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />'
            . '<link href="assets/css/icons.css" rel="stylesheet" type="text/css" />'
            . '<link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />'
            . '<link href="assets/css/style.css" rel="stylesheet" type="text/css" />'
            . '<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />'
            . (!empty($is_rtl) ? '<link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />' : '')
            . approval_steps_css()
            . '<link href="assets/css/report_layout.css?v=' . $cssV . '" rel="stylesheet" type="text/css" />'
            . '<script src="assets/js/modernizr.min.js"></script>'
            . '<script>window.lang = ' . json_encode($GLOBALS['translations'] ?? []) . ';</script>'
            . ($o['extra_head'] ?? '')
            . '</head>';
    }
}

/**
 * Screen header bar (Back, title, actions such as attachments, Print - never printed), then the document:
 * letterhead (logo, company, report title, status), reference/dates strip, employee panel, and opens .rpt-body.
 * $o: title, request_no, status, dates [label => date], badges [[label, value], ...] (extra strip cells),
 *     emp_id (or employee = report_employee() row), back_url,
 *     actions [['label' => .., 'icon' => 'mdi-paperclip', 'href' => .. | 'onclick' => .., 'count' => n], ...] - shown next to Print.
 */
if (!function_exists('report_card_open_html')) {
    function report_card_open_html(array $o): string
    {
        global $conDB, $is_rtl;
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $isRtl = !empty($is_rtl);
        $ar = fn($en, $arv) => ($isRtl && !empty($arv)) ? $arv : (string)$en;
        $emp = $o['employee'] ?? (!empty($o['emp_id']) ? report_employee($conDB, (string)$o['emp_id']) : []);
        $logo = get_setting($conDB, 'logo');
        $company = trim((string)get_setting($conDB, 'company_name'));

        // ---- screen header bar: Back | title | actions + Print ----
        $h = '<div class="rpt-bar no-print">'
            . '<a href="' . $e(!empty($o['back_url']) ? $o['back_url'] : 'javascript:history.back()') . '" class="rpt-btn rpt-btn-ghost" title="' . $e(__('back', 'Back')) . '"><i class="mdi mdi-arrow-left"></i> <span>' . $e(__('back', 'Back')) . '</span></a>'
            . '<div class="rpt-bar-title"><b>' . $e($o['title'] ?? '') . '</b>' . (!empty($o['request_no']) ? '<span>' . $e($o['request_no']) . '</span>' : '') . '</div>'
            . '<div class="rpt-bar-actions">';
        $actions = $o['actions'] ?? [];
        if (count($actions) > 1) {
            // 2+ actions: one "More Actions" button opening a tile popup (same look as view_employee.php More Actions)
            $tones = ['mdi-paperclip' => 'text-info', 'mdi-download' => 'text-success'];
            $tiles = '';
            foreach ($actions as $act) {
                $tone = $act['tone'] ?? ($tones[$act['icon'] ?? ''] ?? 'text-primary');
                $inner = '<i class="mdi ' . $e($act['icon'] ?? 'mdi-paperclip') . '"></i><span>' . $e($act['label'] ?? '')
                    . (!empty($act['count']) ? ' (' . (int)$act['count'] . ')' : '') . '</span>';
                if (!empty($act['href'])) {
                    $tiles .= '<a class="menu-item ' . $tone . '" href="' . $e($act['href']) . '" target="_blank" rel="noopener" onclick="Swal.close();">' . $inner . '</a>';
                } else {
                    $tiles .= '<div class="menu-item ' . $tone . '" role="button" onclick="' . $e('Swal.close(); setTimeout(function () { ' . ($act['onclick'] ?? '') . '; }, 150);') . '">' . $inner . '</div>';
                }
            }
            $h .= '<button type="button" class="rpt-btn" onclick="rptMoreActions()"><i class="mdi mdi-view-grid"></i> ' . $e(__('more_actions', 'More Actions'))
                . '<span class="rpt-btn-count">' . count($actions) . '</span></button>'
                . '<template id="rptActionsTpl"><div class="ma-group"><div class="ma-group-title"><i class="mdi mdi-view-grid"></i>' . $e(__('actions', 'Actions')) . '</div>'
                . '<div class="ma-grid">' . $tiles . '</div></div></template>';
        } else {
            foreach ($actions as $act) {
                $label = $e($act['label'] ?? '');
                $icon = '<i class="mdi ' . $e($act['icon'] ?? 'mdi-paperclip') . '"></i>';
                $count = !empty($act['count']) ? '<span class="rpt-btn-count">' . (int)$act['count'] . '</span>' : '';
                if (!empty($act['href'])) {
                    $h .= '<a class="rpt-btn" href="' . $e($act['href']) . '" target="_blank" rel="noopener">' . $icon . ' ' . $label . $count . '</a>';
                } else {
                    $h .= '<button type="button" class="rpt-btn" onclick="' . $e($act['onclick'] ?? '') . '">' . $icon . ' ' . $label . $count . '</button>';
                }
            }
        }
        $h .= '<button type="button" class="rpt-btn rpt-btn-primary" onclick="window.print()"><i class="mdi mdi-printer"></i> ' . $e(__('print_report', 'Print Report')) . '</button>'
            . '</div></div>';

        // ---- letterhead: logo + company | report title + status ----
        $h .= '<div class="rpt report-wrapper"><header class="rpt-head">'
            . '<div class="rpt-brand">' . ($logo ? '<img src="' . $e($logo) . '" alt="">' : '')
            . '<div class="rpt-brand-text">' . ($company !== '' ? '<b>' . $e($company) . '</b>' : '') . '<small>' . $e(__('human_resources', 'Human Resources')) . '</small></div></div>'
            . '<div class="rpt-doc"><h2>' . $e($o['title'] ?? '') . '</h2>';
        if (isset($o['status']) && $o['status'] !== '') {
            [$tone, $icon, $label] = report_status_meta((string)$o['status']);
            $h .= '<span class="rpt-status vr-tone-' . $tone . '"><i class="mdi ' . $icon . '"></i> ' . $e($label) . '</span>';
        }
        $h .= '</div></header>';

        // ---- reference / dates strip ----
        $cells = [];
        if (!empty($o['request_no'])) $cells[] = [__('reference_no', 'Reference No.'), $o['request_no'], true];
        foreach (($o['dates'] ?? []) as $label => $date) {
            if (!empty($date) && strpos((string)$date, '0000-00-00') !== 0) {
                $cells[] = [$label, format_safe_date($date, strlen((string)$date) > 10 ? 'd M Y, H:i' : 'd M Y'), false];
            }
        }
        foreach (($o['badges'] ?? []) as $b) {
            if (isset($b[1]) && $b[1] !== '') $cells[] = [$b[0], $b[1], false];
        }
        if ($cells) {
            $h .= '<dl class="rpt-meta">';
            foreach ($cells as $c) {
                $h .= '<div><dt>' . $e($c[0]) . '</dt><dd' . ($c[2] ? ' class="rpt-mono"' : '') . '>' . $e($c[1]) . '</dd></div>';
            }
            $h .= '</dl>';
        }

        // ---- employee panel ----
        if ($emp) {
            $facts = [[__('employee_id', 'Employee ID'), $emp['emp_id'] ?? '-']];
            if (!empty($emp['iqama'])) $facts[] = [__('iqama_id', 'Iqama / ID'), $emp['iqama']];
            $dept = $ar($emp['dep_nme'] ?? '', $emp['dep_nme_ar'] ?? '');
            if ($dept !== '') $facts[] = [__('department', 'Department'), getDisplayName($dept)];
            $comp = $ar($emp['comp_name'] ?? '', $emp['comp_name_ar'] ?? '');
            if ($comp !== '') $facts[] = [__('company', 'Company'), getDisplayName($comp)];
            if (!empty($emp['joining_date']) && $emp['joining_date'] !== '0000-00-00') $facts[] = [__('joining_date', 'Joining Date'), format_safe_date($emp['joining_date'], 'd M Y')];
            $job = $ar($emp['job'] ?? '', $emp['job_ar'] ?? '');

            $h .= '<section class="rpt-emp">'
                . '<div class="rpt-emp-who"><img class="rpt-emp-photo" src="' . $e(getAvatarImagePath($emp['avatar'] ?? '', $emp['sex'] ?? 1)) . '" alt="">'
                . '<div><div class="rpt-emp-name">' . $e(getDisplayName((string)($emp['name'] ?? ''))) . '</div>'
                . ($job !== '' ? '<div class="rpt-emp-job">' . $e(getDisplayName($job)) . '</div>' : '') . '</div></div>'
                . '<dl class="rpt-emp-facts">';
            foreach ($facts as $f) {
                $h .= '<div><dt>' . $e($f[0]) . '</dt><dd>' . $e($f[1]) . '</dd></div>';
            }
            $h .= '</dl></section>';
        }
        return $h . '<div class="rpt-body report-body">';
    }
}

/**
 * Closes .rpt-body, then approval chain (Show / Hide details toggle; printed only while shown), signatures, "generated on" line.
 * $o: chain (request_approvers rows; leave out = no chain section), chain_status, chain_notice (trusted HTML),
 *     signatures (translation keys; [] = none).
 */
if (!function_exists('report_card_close_html')) {
    function report_card_close_html(array $o): string
    {
        global $site_title;
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $h = '</div>';
        if (array_key_exists('chain', $o)) {
            $st = (string)($o['chain_status'] ?? '');
            $h .= '<div class="report-section rpt-toggle-section rpt-chain">'
                . '<h5 class="section-title"><i class="mdi mdi-sitemap"></i> ' . $e(__('approval_status', 'Approval Status')) . report_toggle_button_html() . '</h5>'
                . '<div class="rpt-toggle-body rpt-chain-body" id="approvalStatusContainer" hidden>'
                . approval_steps_html(approval_steps_rows((array)$o['chain'], $st), $st, (string)($o['chain_notice'] ?? ''))
                . '</div></div>';
        }
        $signatures = $o['signatures'] ?? ['compensation_signature', 'hr_manager_signature', 'general_manager_signature'];
        if ($signatures) {
            $h .= '<div class="rpt-sign" style="--n: ' . count($signatures) . ';">';
            foreach ($signatures as $key) {
                $h .= '<div class="rpt-sign-box"><span>' . $e(__($key)) . '</span></div>';
            }
            $h .= '</div>';
        }
        $h .= '<div class="rpt-foot">' . $e(__('automatically_generated_on', 'Automatically generated on')) . ' ' . date('Y-m-d H:i:s')
            . ' &middot; ' . $e($site_title ?? '') . ' &middot; ' . $e($GLOBALS['report_layout_title'] ?? '') . '</div>';
        return $h . '</div>';
    }
}

/** Show / Hide button for a body block: .rpt-toggle-section > title + this button, body .rpt-toggle-body[hidden] (printed only while shown). */
if (!function_exists('report_toggle_button_html')) {
    function report_toggle_button_html(): string
    {
        return '<button type="button" class="rpt-sec-toggle no-print" aria-expanded="false" onclick="rptToggleSection(this)"><i class="mdi mdi-eye"></i> <span>'
            . htmlspecialchars(__('show_details', 'Show details'), ENT_QUOTES, 'UTF-8') . '</span></button>';
    }
}

/** App scripts + the chain / section toggles. $o: extra_scripts (HTML after the app scripts). */
if (!function_exists('report_scripts_html')) {
    function report_scripts_html(array $o): string
    {
        $secShow = json_encode(__('show_details', 'Show details'));
        $secHide = json_encode(__('hide_details', 'Hide details'));
        $maTitle = json_encode(htmlspecialchars(__('more_actions', 'More Actions'), ENT_QUOTES, 'UTF-8'));
        return '<script src="assets/js/jquery.min.js"></script>'
            . '<script src="assets/js/bootstrap.bundle.min.js"></script>'
            . '<script src="assets/js/metisMenu.min.js"></script>'
            . '<script src="assets/js/waves.js"></script>'
            . '<script src="assets/js/jquery.slimscroll.js"></script>'
            . '<script src="assets/js/jquery.core.js"></script>'
            . '<script src="assets/js/jquery.app.js?t=' . time() . '"></script>'
            . "<script>\nfunction rptMoreActions() {\n"
            . "    var sub = Array.prototype.map.call(document.querySelectorAll('.rpt-bar-title > *'), function (el) { return el.textContent.trim(); }).join(' \u{00B7} ');\n"
            . "    Swal.fire({\n"
            . "        title: '<div class=\"ma-head\"><div class=\"ma-title\">' + $maTitle + '</div><p></p></div>',\n"
            . "        html: '<div class=\"menu-items-container\">' + document.getElementById('rptActionsTpl').innerHTML + '</div>',\n"
            . "        showConfirmButton: false, showCloseButton: true, width: '560px', padding: '0', allowOutsideClick: false,\n"
            . "        customClass: { container: 'more-actions-modal' },\n"
            . "        didOpen: function () { Swal.getTitle().querySelector('p').textContent = sub; }\n"
            . "    });\n"
            . "}\n"
            . "function rptToggleSection(btn) {\n"
            . "    var body = btn.closest('.rpt-toggle-section').querySelector('.rpt-toggle-body'), open = body.hidden;\n"
            . "    body.hidden = !open;\n"
            . "    btn.setAttribute('aria-expanded', open ? 'true' : 'false');\n"
            . "    btn.querySelector('i').className = 'mdi ' + (open ? 'mdi-eye-off' : 'mdi-eye');\n"
            . "    btn.querySelector('span').textContent = open ? $secHide : $secShow;\n"
            . "}\n</script>"
            . ($o['extra_scripts'] ?? '');
    }
}
