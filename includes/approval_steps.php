<?php
/**
 * Approval chain "stepper" for the request report pages (vacation_report_details.php, loan_report_details.php):
 * one small card per approval level (photo, first + last name, department, status, action date) joined by a line.
 *
 * Feed it the raw request_approvers rows (ra.* + approver_name, approver_dept_name, approver_role,
 * approver_avatar, approver_sex) through approval_steps_rows(), then print approval_steps_html().
 * Styles: assets/css/approval_steps.css. SweetAlert2 popups use the JS twin approvalStepsHtml() (assets/js/jquery.app.js)
 * with the rows from approval_steps_api_fields().
 */

require_once __DIR__ . '/sr_history_helpers.php'; // srh_status(): status -> [tone, mdi icon, label]

/** Request statuses that count as "approved" for the header badge and the approver filter. */
if (!function_exists('approval_steps_is_approved')) {
    function approval_steps_is_approved(string $status): bool
    {
        return in_array(strtolower($status), ['approved', 'paid', 'completed', 'closed', 'settled'], true);
    }
}

/**
 * request_approvers rows -> step rows. Approved request: only the approvers who signed;
 * rejected request: approvers who never acted (pending / awaiting) are left out.
 */
if (!function_exists('approval_steps_rows')) {
    function approval_steps_rows(array $chain, string $requestStatus): array
    {
        $requestStatus = strtolower($requestStatus);
        $rows = [];
        foreach ($chain as $ap) {
            $st = strtolower((string)($ap['status'] ?? ''));
            if (approval_steps_is_approved($requestStatus) && $st !== 'approved') continue;
            if ($requestStatus === 'rejected' && in_array($st, ['pending', 'awaiting'], true)) continue;
            $dept = !empty($ap['approver_dept_name']) ? getDisplayName($ap['approver_dept_name'])
                : (function_exists('getRoleLabel') ? getRoleLabel($ap['approver_role'] ?? '') : ucwords(str_replace('_', ' ', (string)($ap['approver_role'] ?? ''))));
            $rows[] = [
                'level'  => (int)($ap['approval_level'] ?? 0),
                'name'   => getDisplayName(parseName((string)($ap['approver_name'] ?? ''))) ?: __('pending_assignment', 'Pending Assignment'),
                'role'   => (string)$dept,
                'status' => $st,
                'date'   => $ap['action_date'] ?? null,
                'note'   => $st === 'rejected' && !empty($ap['note']) ? getDisplayName((string)$ap['note']) : '',
                'avatar' => getAvatarImagePath($ap['approver_avatar'] ?? '', $ap['approver_sex'] ?? 1),
            ];
        }
        return $rows;
    }
}

/** Card with header badge + steps. $noticeHtml (trusted HTML) replaces the steps, e.g. for a cancelled request. */
if (!function_exists('approval_steps_html')) {
    function approval_steps_html(array $rows, string $requestStatus, string $noticeHtml = ''): string
    {
        static $cssDone = false;
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $requestStatus = strtolower($requestStatus);

        if (approval_steps_is_approved($requestStatus)) {
            [$headTone, $headIcon] = srh_status('approved');
            $headLabel = __('request_approved');
        } elseif ($requestStatus === 'rejected') {
            [$headTone, $headIcon] = srh_status('rejected');
            $headLabel = __('request_rejected');
        } elseif ($requestStatus === 'cancelled') {
            [$headTone, $headIcon] = srh_status('cancelled');
            $headLabel = __('cancelled', 'Cancelled');
        } else {
            [$headTone, $headIcon] = srh_status('pending');
            $headLabel = __('pending_approval');
        }

        $html = '';
        if (!$cssDone) {
            $cssDone = true;
            $html .= approval_steps_css();
        }
        $html .= '<div class="vr-flow"><div class="vr-flow-head">'
            . '<span class="vr-flow-icon"><i class="mdi mdi-sitemap"></i></span>'
            . '<div class="vr-flow-title"><b>' . $e(__('approval_chain', 'Approval chain')) . '</b>'
            . ($rows && $noticeHtml === '' ? '<small>' . $e(count($rows) . ' ' . __('levels', 'levels')) . '</small>' : '') . '</div>'
            . '<span class="vr-badge vr-tone-' . $headTone . '"><i class="mdi ' . $headIcon . '"></i> ' . $e($headLabel) . '</span>'
            . '</div><div class="vr-flow-body">';

        if ($noticeHtml !== '') {
            $html .= $noticeHtml;
        } elseif ($rows) {
            $html .= '<ol class="vr-steps" style="--n: ' . max(3, min(6, count($rows))) . ';">';
            foreach ($rows as $r) {
                [$tone, $icon, $label] = srh_status($r['status']);
                $label = getDisplayName($label);
                $html .= '<li class="vr-step vr-tone-' . $tone . '">'
                    . '<div class="vr-step-mark"><span class="vr-step-num" title="' . $e($label) . '"><i class="mdi ' . $icon . '"></i></span></div>'
                    . '<div class="vr-step-card">'
                    . '<span class="vr-step-level">' . $e(__('level', 'Level')) . ' ' . (int)$r['level'] . '</span>'
                    . '<img class="vr-step-avatar" src="' . $e($r['avatar']) . '" alt="">'
                    . '<span class="vr-step-name" title="' . $e($r['name']) . '">' . $e($r['name']) . '</span>'
                    . ($r['role'] !== '' ? '<span class="vr-step-dept">' . $e($r['role']) . '</span>' : '')
                    . '<span class="vr-badge"><i class="mdi ' . $icon . '"></i> ' . $e($label) . '</span>'
                    . (!empty($r['date']) ? '<span class="vr-step-date"><i class="mdi mdi-clock-outline"></i>' . $e(format_safe_date($r['date'], 'd M Y · H:i')) . '</span>' : '')
                    . ($r['note'] !== '' ? '<div class="vr-step-note"><i class="mdi mdi-comment-text-outline"></i> ' . nl2br($e($r['note'])) . '</div>' : '')
                    . '</div></li>';
            }
            $html .= '</ol>';
        } else {
            $html .= '<div class="vr-flow-notice vr-tone-' . $headTone . '"><i class="mdi ' . $headIcon . '"></i><div>' . $e($headLabel) . '</div></div>';
        }
        return $html . '</div></div>';
    }
}

/** Notice box (trusted HTML inside) for approval_steps_html()'s $noticeHtml. */
if (!function_exists('approval_steps_notice')) {
    function approval_steps_notice(string $tone, string $icon, string $innerHtml): string
    {
        return '<div class="vr-flow-notice vr-tone-' . $tone . '"><i class="mdi ' . $icon . '"></i><div>' . $innerHtml . '</div></div>';
    }
}

if (!function_exists('approval_steps_css')) {
    function approval_steps_css(): string
    {
        $v = @filemtime(dirname(__DIR__) . '/assets/css/approval_steps.css');
        return '<link href="assets/css/approval_steps.css?v=' . $v . '" rel="stylesheet" type="text/css">';
    }
}

/**
 * Adds the fields the JS stepper (approvalStepsHtml) needs to request_approvers rows sent to the browser:
 * approver_short_name (first + last), approver_dept, approver_avatar (resolved URL).
 * Rows need approver_name and, when selected, approver_dept_name / approver_avatar_raw / approver_sex.
 */
if (!function_exists('approval_steps_api_fields')) {
    function approval_steps_api_fields(array $chain): array
    {
        foreach ($chain as &$row) {
            $row['approver_short_name'] = getDisplayName(parseName((string)($row['approver_name'] ?? '')));
            $row['approver_dept'] = !empty($row['approver_dept_name']) ? getDisplayName((string)$row['approver_dept_name']) : '';
            $row['approver_avatar'] = getAvatarImagePath($row['approver_avatar_raw'] ?? '', $row['approver_sex'] ?? 1);
            unset($row['approver_avatar_raw'], $row['approver_sex']);
        }
        unset($row);
        return $chain;
    }
}
