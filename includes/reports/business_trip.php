<?php
/**
 * Business trip report - body part of request_report.php?type=business_trip&id=..&emp_id=..
 * Header, employee banner, approval chain, signatures and footer come from includes/report_layout.php.
 * (Built from business_trip_report_details.php.)
 */
/****************************************************************
 * Business Trip Report - Detail View
 * 
 * Displays comprehensive business trip request details with:
 * - Trip information (dates, destination, purpose, etc.)
 * - Employee and department info
 * - Approval chain timeline
 * - Print-friendly formatting
 * 
 * Similar structure to vacation_report_details.php
 ****************************************************************/


$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    $viewer_admin = mysqli_fetch_assoc($query);
    $viewer_user_type = strtolower((string)($viewer_admin['user_type'] ?? ''));
    $is_hr_payroll_viewer = (strpos($viewer_user_type, 'hr_payroll') !== false);
    include("./includes/avatar_select.php");

    // 1. Get and validate the IDs from the URL
    $trip_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $emp_id = isset($_GET['emp_id']) ? $_GET['emp_id'] : '';

    if ($trip_id === 0 || empty($emp_id)) {
        die("Invalid request parameters.");
    }

    // 2. Get Request Type ID for business_trip
    $type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'business_trip' LIMIT 1");
    if (!$type_query || mysqli_num_rows($type_query) == 0) {
        die("CRITICAL ERROR: 'business_trip' type not found in `approval_request_types` table.");
    }
    $request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];
    mysqli_free_result($type_query);
    $sql = "SELECT 
                bt.*,
                e.name as employee_name,
                e.avatar,
                e.emp_id,
                d.dep_nme AS dept_name,
                d.dep_nme_ar AS dept_name_ar,
                comp.comp_name,
                comp.comp_name_ar,
                fc.name_en as from_city_name_en,
                fc.name_ar as from_city_name_ar,
                tc.name_en as to_city_name_en,
                tc.name_ar as to_city_name_ar,
                c.name AS destination_country_name
            FROM emp_business_trip bt
            JOIN employees e ON bt.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN companies comp ON e.comp_no = comp.comp_id
            LEFT JOIN saudi_cities fc ON bt.from_city_id = fc.id
            LEFT JOIN saudi_cities tc ON bt.to_city_id = tc.id
            LEFT JOIN countries c ON bt.destination_country = c.name
            WHERE bt.id = ? AND bt.emp_id = ?";

    $stmt = $conDB->prepare($sql);
    $stmt->bind_param("is", $trip_id, $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if (!$request) {
        die("Business trip request not found.");
    }

    // Employees may only open their own trip report.
    if (strtolower((string)($user_type ?? '')) === 'employee' && (string)$request['emp_id'] !== (string)($empid ?? '')) {
        header("Location: ./profile.php");
        exit();
    }

    // Allowance math shared with business-trip settlement creation.
    require_once dirname(__DIR__) . '/business_trip_settlement_helper.php';
    $request['id'] = $trip_id;
    $request['emp_id'] = $emp_id;
    extract(calculateBusinessTripAllowanceBreakdown($conDB, $request));
    $report_grand_total = $grand_total;

    // Purpose/notes with real line breaks (older rows stored literal "\r\n" text).
    $bt_text = function ($value) {
        return nl2br(htmlspecialchars(cleanBusinessTripText($value)), false);
    };

    // 4. Fetch approval chain
    $approval_chain = [];
    $request_inv_no = $request['request_inv_no'] ?? '';
    
    if (!empty($request_inv_no)) {
        // Fetch the approval chain using the request_type_id we already retrieved
        $chain_sql = "SELECT ra.*,
                       e.name AS approver_name,
                       e.emp_id AS approver_emp_id,
                       al.user_type AS approver_role,
                       d2.dep_nme AS approver_dept_name,
                       e.avatar AS approver_avatar, e.sex AS approver_sex
                   FROM request_approvers ra
                   LEFT JOIN employees e ON ra.approver_id = e.emp_id
                   LEFT JOIN admin_login al ON e.emp_id = al.emp_id
                   LEFT JOIN department d2 ON e.dept = d2.id
                   WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
                   ORDER BY ra.approval_level ASC";
        $stmt_chain = $conDB->prepare($chain_sql);
        if ($stmt_chain) {
            $stmt_chain->bind_param("si", $request_inv_no, $request_type_id);
            $stmt_chain->execute();
            $chain_result = $stmt_chain->get_result();
            while ($chain_row = $chain_result->fetch_assoc()) {
                $approval_chain[] = $chain_row;
            }
            $stmt_chain->close();
        }
    }

    // 5. Format trip destination display
    $trip_type = $request['trip_type'] ?? 'domestic';
    $destination_display = '';
    
    if ($trip_type === 'international') {
        $destination_display = htmlspecialchars((string)($request['destination_country_name'] ?? $request['destination_country'] ?? 'N/A'));
    } else {
        $from_city = ($is_rtl ?? false) ? ($request['from_city_name_ar'] ?? '') : ($request['from_city_name_en'] ?? '');
        $to_city = ($is_rtl ?? false) ? ($request['to_city_name_ar'] ?? '') : ($request['to_city_name_en'] ?? '');
        $destination_display = htmlspecialchars(trim($from_city) . ' → ' . trim($to_city));
    }

    // 6. Get current request status details
    $current_status = $request['current_status'] ?? 'pending_approval';
    $status_badge_class = 'secondary';
    if ($current_status === 'pending_approval') $status_badge_class = 'warning';
    if ($current_status === 'approved') $status_badge_class = 'success';
    if ($current_status === 'rejected') $status_badge_class = 'danger';
    if ($current_status === 'completed') $status_badge_class = 'info';

    // ---- Shared report layout (includes/report_layout.php) ----
    $report = [
        'title'      => __('business_trip_report'),
        'request_no' => !empty($request['request_inv_no']) ? $request['request_inv_no'] : ('#' . (int)$request['id']),
        'status'     => (string)$current_status,
        'dates'      => [__('submitted_date', 'Submitted Date') => $request['created_at'] ?? null],
        'emp_id'     => (string)$request['emp_id'],
    ];
    include dirname(__DIR__) . '/report_open.php';
?>
                            <!-- Trip Information Section -->
                            <div class="report-section">
                                <h5 class="section-title">
                                    <i class="fa fa-briefcase"></i> <?= __('trip_information') ?>
                                </h5>
                                <div class="grid-details">
                                    <div class="detail-item">
                                        <div class="label"><?= __('trip_type') ?></div>
                                        <div class="value"><?= __((string)$trip_type) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('status') ?></div>
                                        <div class="value"><span class="badge badge-<?= $status_badge_class ?>"><?= __((string)$current_status) ?></span></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('submitted_on') ?></div>
                                        <div class="value"><?= htmlspecialchars((string)$request['created_at']) ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Trip Details Section -->
                            <div class="report-section">
                                <h5 class="section-title">
                                    <i class="fa fa-map-marker"></i> <?= __('trip_details') ?>
                                </h5>
                                <div class="grid-details">
                                    <div class="detail-item">
                                        <div class="label"><?= __('destination') ?></div>
                                        <div class="value"><?= $destination_display ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('start_date') ?></div>
                                        <div class="value"><?= htmlspecialchars((string)$request['trip_start_date']) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('end_date') ?></div>
                                        <div class="value"><?= htmlspecialchars((string)$request['trip_end_date']) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('duration') ?></div>
                                        <div class="value">
                                            <?php
                                            $start = new DateTime($request['trip_start_date']);
                                            $end = new DateTime($request['trip_end_date']);
                                            $diff = $start->diff($end);
                                            echo (int)($diff->format('%a') ?? 0) + 1 . ' ' . __('days');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('purpose') ?></div>
                                        <div class="value"><?= $bt_text(getDisplayName((string)$request['trip_purpose'])) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('transportation') ?></div>
                                        <div class="value">
                                            <?php 
                                            $transport_type = $request['transportation_type'] ?? '';
                                            if ($transport_type === 'own_car') {
                                                echo '<i class="fa fa-car" style="margin-right: 8px;"></i>' . __('own_car');
                                            } elseif ($transport_type === 'rental') {
                                                echo '<i class="fa fa-taxi" style="margin-right: 8px;"></i>' . __('rental_car');
                                            } elseif ($transport_type === 'by_air') {
                                                echo '<i class="fa fa-plane" style="margin-right: 8px;"></i>' . __('by_air');
                                            } else {
                                                echo __($transport_type);
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Details (International Trips) -->
                            <?php if ($trip_type === 'international'): ?>
                            <div class="report-section">
                                <h5 class="section-title">
                                    <i class="fa fa-plane"></i> <?= __('international_trip_details') ?>
                                </h5>
                                <div class="grid-details">
                                    <div class="detail-item">
                                        <div class="label"><?= __('destination_country') ?></div>
                                        <div class="value"><?= getDisplayName((string)$request['destination_country']) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('visa_required') ?></div>
                                        <div class="value"><?= ((int)($request['visa_required'] ?? 0) === 1) ? __('yes') : __('no') ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Allowances & Ticket Fares Section -->
                            <?php if (!empty($allowance_summary) || !empty($allowance_items) || $trip_allowance_days > 0): ?>
                            <div class="report-section">
                                <h5 class="section-title">
                                    <i class="fa fa-money-bill-wave"></i> <?= __('allowances_and_ticket_fares', 'Allowances and Ticket Fares') ?>
                                </h5>

                                <?php 
                                // Get transportation type to check if tickets should be shown
                                $transport_type_for_tickets = $request['transportation_type'] ?? '';
                                $show_ticket_fares = ($transport_type_for_tickets === 'by_air');
                                
                                // Filter out zero-value "others" items and ticket_fares if not by_air
                                $filtered_allowance_items = array_filter($allowance_items, function($item) use ($show_ticket_fares) {
                                    $type = (string)($item['allowance_type'] ?? 'others');
                                    $total = (float)($item['line_total'] ?? 0);
                                    // Hide zero-value others
                                    if ($type === 'others' && $total == 0) {
                                        return false;
                                    }
                                    // Hide ticket_fares if not by_air transportation
                                    if ($type === 'ticket_fares' && !$show_ticket_fares) {
                                        return false;
                                    }
                                    return true;
                                });
                                ?>
                                <?php if (!empty($filtered_allowance_items)): ?>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead style="background-color: var(--background-light);">
                                                <tr>
                                                    <th><?= __('allowance_type', 'Allowance Type') ?></th>
                                                    <th><?= __('comment', 'Comment') ?></th>
                                                    <th class="text-center"><?= __('amount', 'Amount') ?></th>
                                                    <th class="text-center"><?= __('qty', 'Qty') ?></th>
                                                    <th class="text-center"><?= __('total', 'Total') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($filtered_allowance_items as $allowance_item): ?>
                                                    <?php
                                                    $line_type = (string)($allowance_item['allowance_type'] ?? 'others');
                                                    $line_type_label_map = [
                                                        'ticket_fares' => __('ticket_fares', 'Ticket Fares'),
                                                        'hotel_allowance' => __('hotel_allowance', 'Hotel Allowance'),
                                                        'transportation_allowance' => __('transportation_allowance', 'Transportation Allowance'),
                                                        'others' => __('other_allowance', 'Others')
                                                    ];
                                                    $line_type_label = $line_type_label_map[$line_type] ?? ucwords(str_replace('_', ' ', $line_type));
                                                    $line_description = (string)($allowance_item['description'] ?? '');
                                                    $line_amount = (float)($allowance_item['unit_amount'] ?? 0);
                                                    $line_qty = (int)($allowance_item['qty'] ?? 0);
                                                    $line_total = (float)($allowance_item['line_total'] ?? 0);
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars((string)$line_type_label) ?></td>
                                                        <td><?= $line_description !== '' ? htmlspecialchars($line_description) : '&mdash;' ?></td>
                                                        <td class="text-center"><?= number_format($line_amount, 2) ?></td>
                                                        <td class="text-center"><?= $line_qty ?></td>
                                                        <td class="text-center"><strong><?= number_format($line_total, 2) ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>

                                <div class="grid-details">
                                    <?php if ($show_ticket_fares): ?>
                                    <div class="detail-item">
                                        <div class="label"><?= __('ticket_fares', 'Ticket Fares') ?></div>
                                        <div class="value\"><?= number_format($ticket_fares_total, 2) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="detail-item">
                                        <div class="label\"><?= __('hotel_allowance', 'Hotel Allowance') ?> (<?= __('calculated', 'Calculated') ?>)</div>
                                        <div class="value\"><?= number_format($calc_hotel_total, 2) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label\"><?= __('transportation_allowance', 'Transportation Allowance') ?> (<?= __('calculated', 'Calculated') ?>)</div>
                                        <div class="value\"><?= number_format($calc_transport_total, 2) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label\"><?= __('daily_allowance', 'Daily Allowance') ?> (<?= __('calculated', 'Calculated') ?>)</div>
                                        <div class="value\"><?= number_format($calc_daily_allowance_total, 2) ?></div>
                                    </div>
                                    <?php if ($other_allowance_total > 0): ?>
                                    <div class="detail-item">
                                        <div class="label"><?= __('other_allowance', 'Others') ?></div>
                                        <div class="value\"><?= number_format($other_allowance_total, 2) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="detail-item">
                                        <div class="label"><?= __('grand_total', 'Grand Total') ?></div>
                                        <div class="value highlight"><?= number_format($report_grand_total, 2) ?></div>
                                    </div>
                                </div>

                                <?php if (!empty($allowance_summary['notes'])): ?>
                                    <div class="info-box" style="margin-top: 1rem;">
                                        <strong><?= __('notes', 'Notes') ?>:</strong>
                                        <?= getDisplayName(htmlspecialchars((string)$allowance_summary['notes'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <?php if ($is_hr_payroll_viewer || $is_system_admin): ?>
                            <!-- hidden by default; printed only while shown -->
                            <div class="report-section rpt-toggle-section">
                                <h5 class="section-title">
                                    <i class="fa fa-calculator"></i> <?= __('troubleshooting_calculation', 'Troubleshooting Calculation (HR Payroll)') ?>
                                    <?= report_toggle_button_html() ?>
                                </h5>
                                <div class="info-box rpt-toggle-body" hidden>
                                    <div><strong><?= __('basic_salary', 'Basic Salary') ?>:</strong> <?= number_format($basic_salary, 2) ?></div>
                                    <div><strong><?= __('allowance_days', 'Allowance Days') ?>:</strong> <?= (int)$trip_allowance_days ?></div>
                                    <div><strong><?= __('salary_level', 'Salary Level') ?>:</strong> <?= $matched_scale ? ('Level ' . (int)$matched_scale['level'] . ' (' . (int)$matched_scale['min'] . ' - ' . (int)$matched_scale['max'] . ')') : __('out_of_scale', 'Out of defined scale') ?></div>
                                    <hr style="margin:8px 0;">
                                    <div><strong><?= __('hotel_allowance', 'Hotel Allowance') ?>:</strong> <?= number_format($calc_hotel_daily, 2) ?> × <?= (int)$hotel_allowance_days ?> (<?= __('days', 'days') ?> - 1) = <?= number_format($calc_hotel_total, 2) ?></div>
                                    <div><strong><?= __('transportation_allowance', 'Transportation Allowance') ?>:</strong> <?= number_format($calc_transport_daily, 2) ?> × <?= (int)$trip_allowance_days ?> = <?= number_format($calc_transport_total, 2) ?></div>
                                    <div><strong><?= __('daily_allowance', 'Daily Allowance') ?>:</strong> <?= number_format($calc_daily_allowance_total, 0) ?></div>
                                    <div style="padding-left: 12px;">
                                        1st week: (<?= number_format($basic_salary, 2) ?> / 60) × <?= (int)$week1_days ?> = <?= number_format($week1_daily_total, 0) ?>
                                    </div>
                                    <div style="padding-left: 12px;">
                                        2nd week: (<?= number_format($basic_salary, 2) ?> / 90) × <?= (int)$week2_days ?> = <?= number_format($week2_daily_total, 0) ?>
                                    </div>
                                    <div style="padding-left: 12px;">
                                        3rd week: (<?= number_format($basic_salary, 2) ?> / 120) × <?= (int)$week3_days ?> = <?= number_format($week3_daily_total, 0) ?>
                                    </div>
                                    <div><strong><?= __('other_allowance', 'Others') ?>:</strong> <?= number_format($other_allowance_total, 2) ?></div>
                                    <?php if ($show_ticket_fares): ?>
                                    <div><strong><?= __('ticket_fares', 'Ticket Fares') ?>:</strong> <?= number_format($ticket_fares_total, 2) ?></div>
                                    <?php endif; ?>
                                    <hr style="margin:8px 0;">
                                    <?php if ($show_ticket_fares): ?>
                                    <div><strong><?= __('grand_total', 'Grand Total') ?>:</strong> <?= number_format($ticket_fares_total, 2) ?> + <?= number_format($calc_hotel_total, 2) ?> + <?= number_format($calc_transport_total, 2) ?> + <?= number_format($calc_daily_allowance_total, 2) ?> + <?= number_format($other_allowance_total, 2) ?> = <?= number_format($report_grand_total, 2) ?></div>
                                    <?php else: ?>
                                    <div><strong><?= __('grand_total', 'Grand Total') ?>:</strong> <?= number_format($calc_hotel_total, 2) ?> + <?= number_format($calc_transport_total, 2) ?> + <?= number_format($calc_daily_allowance_total, 2) ?> + <?= number_format($other_allowance_total, 2) ?> = <?= number_format($report_grand_total, 2) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Notes Section -->
                            <?php if (!empty($request['request_notes'])): ?>
                            <div class="report-section">
                                <h5 class="section-title">
                                    <i class="fa fa-sticky-note"></i> <?= __('additional_notes') ?>
                                </h5>
                                <div class="info-box">
                                    <?= $bt_text(getDisplayName((string)$request['request_notes'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>

<?php
    $report_end = [
        'chain'        => $approval_chain,
        'chain_status' => (string)$current_status,
        'signatures'   => ['compensation_signature', 'hr_manager_signature', 'general_manager_signature'],
    ];
    include dirname(__DIR__) . '/report_close.php';
?>

<?php
    $conDB->close();
}
?>
