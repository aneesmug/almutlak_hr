<?php
/**
 * Business Trip allowance calculation + settlement helpers.
 *
 * Single source for the allowance math shown on business_trip_report_details.php
 * and the amount stored when a business-trip settlement is created.
 */

/**
 * Salary bands for hotel/transportation daily rates (by basic salary).
 */
function getBusinessTripSalaryScales()
{
    return [
        ['level' => 1, 'min' => 500, 'max' => 2700, 'hotel' => 125, 'transport' => 50],
        ['level' => 2, 'min' => 2850, 'max' => 5950, 'hotel' => 200, 'transport' => 75],
        ['level' => 3, 'min' => 6150, 'max' => 7950, 'hotel' => 350, 'transport' => 100],
        ['level' => 4, 'min' => 8300, 'max' => 11450, 'hotel' => 450, 'transport' => 150],
        ['level' => 5, 'min' => 11450, 'max' => 20450, 'hotel' => 650, 'transport' => 200],
    ];
}

/**
 * Calculate all allowances for a business trip.
 *
 * @param mysqli $conDB
 * @param array  $trip Row from emp_business_trip (needs id, emp_id, trip_start_date, trip_end_date)
 * @return array Breakdown incl. grand_total
 */
function calculateBusinessTripAllowanceBreakdown($conDB, array $trip)
{
    $trip_id = (int)($trip['id'] ?? 0);
    $emp_id = (string)($trip['emp_id'] ?? '');

    $trip_allowance_days = 0;
    try {
        $trip_start = new DateTime((string)($trip['trip_start_date'] ?? ''));
        $trip_end = new DateTime((string)($trip['trip_end_date'] ?? ''));
        if ($trip_end >= $trip_start) {
            $trip_allowance_days = (int)$trip_start->diff($trip_end)->format('%a') + 1;
        }
    } catch (Exception $e) {
        $trip_allowance_days = 0;
    }

    $basic_salary = 0.0;
    $stmt_salary = $conDB->prepare("SELECT basic FROM emp_salary WHERE emp_id = ? AND status = 1 ORDER BY id DESC LIMIT 1");
    if ($stmt_salary) {
        $stmt_salary->bind_param("s", $emp_id);
        $stmt_salary->execute();
        $salary_result = $stmt_salary->get_result();
        if ($salary_result && ($salary_row = $salary_result->fetch_assoc())) {
            $basic_salary = (float)($salary_row['basic'] ?? 0);
        }
        $stmt_salary->close();
    }

    $matched_scale = null;
    foreach (getBusinessTripSalaryScales() as $scale) {
        if ($basic_salary >= $scale['min'] && $basic_salary <= $scale['max']) {
            $matched_scale = $scale;
            break;
        }
    }

    $calc_hotel_daily = (float)($matched_scale['hotel'] ?? 0);
    $calc_transport_daily = (float)($matched_scale['transport'] ?? 0);
    // Hotel nights = trip days minus 1 (no overnight stay needed on the last day).
    $hotel_allowance_days = max(0, $trip_allowance_days - 1);
    $calc_hotel_total = $calc_hotel_daily * $hotel_allowance_days;
    $calc_transport_total = $calc_transport_daily * max(0, $trip_allowance_days);

    $week1_days = min(max($trip_allowance_days, 0), 7);
    $week2_days = min(max($trip_allowance_days - 7, 0), 7);
    $week3_days = max($trip_allowance_days - 14, 0);

    $week1_daily_rate = ($basic_salary > 0) ? ($basic_salary / 60) : 0;
    $week2_daily_rate = ($basic_salary > 0) ? ($basic_salary / 90) : 0;
    $week3_daily_rate = ($basic_salary > 0) ? ($basic_salary / 120) : 0;

    $week1_daily_total = round($week1_daily_rate * $week1_days);
    $week2_daily_total = round($week2_daily_rate * $week2_days);
    $week3_daily_total = round($week3_daily_rate * $week3_days);
    $calc_daily_allowance_total = round($week1_daily_total + $week2_daily_total + $week3_daily_total);

    $allowance_summary = null;
    $allowance_items = [];
    if ($trip_id > 0) {
        $stmt_allowance = $conDB->prepare("SELECT * FROM emp_business_trip_allowances WHERE trip_id = ? LIMIT 1");
        if ($stmt_allowance) {
            $stmt_allowance->bind_param("i", $trip_id);
            $stmt_allowance->execute();
            $allowance_result = $stmt_allowance->get_result();
            $allowance_summary = $allowance_result ? $allowance_result->fetch_assoc() : null;
            $stmt_allowance->close();
        }
    }

    if (!empty($allowance_summary['id'])) {
        $allowance_id = (int)$allowance_summary['id'];
        $stmt_items = $conDB->prepare("SELECT allowance_type, description, unit_amount, qty, line_total
                                       FROM emp_business_trip_allowance_items
                                       WHERE allowance_id = ?
                                       ORDER BY line_order ASC, id ASC");
        if ($stmt_items) {
            $stmt_items->bind_param("i", $allowance_id);
            $stmt_items->execute();
            $items_result = $stmt_items->get_result();
            while ($items_result && ($item_row = $items_result->fetch_assoc())) {
                $allowance_items[] = $item_row;
            }
            $stmt_items->close();
        }
    }

    $ticket_fares_total = (float)($allowance_summary['ticket_fares'] ?? 0);
    $other_allowance_total = (float)($allowance_summary['other_allowance'] ?? 0);
    $grand_total = $ticket_fares_total + $calc_hotel_total + $calc_transport_total + $calc_daily_allowance_total + $other_allowance_total;

    return [
        'trip_allowance_days' => $trip_allowance_days,
        'basic_salary' => $basic_salary,
        'matched_scale' => $matched_scale,
        'calc_hotel_daily' => $calc_hotel_daily,
        'calc_transport_daily' => $calc_transport_daily,
        'hotel_allowance_days' => $hotel_allowance_days,
        'calc_hotel_total' => $calc_hotel_total,
        'calc_transport_total' => $calc_transport_total,
        'week1_days' => $week1_days,
        'week2_days' => $week2_days,
        'week3_days' => $week3_days,
        'week1_daily_rate' => $week1_daily_rate,
        'week2_daily_rate' => $week2_daily_rate,
        'week3_daily_rate' => $week3_daily_rate,
        'week1_daily_total' => $week1_daily_total,
        'week2_daily_total' => $week2_daily_total,
        'week3_daily_total' => $week3_daily_total,
        'calc_daily_allowance_total' => $calc_daily_allowance_total,
        'allowance_summary' => $allowance_summary,
        'allowance_items' => $allowance_items,
        'ticket_fares_total' => $ticket_fares_total,
        'other_allowance_total' => $other_allowance_total,
        'grand_total' => $grand_total,
    ];
}

/**
 * Older trips were double-escaped on submit, so line breaks/quotes were stored as literal
 * backslash sequences (backslash-r backslash-n, backslash-quote). Return the real text.
 */
if (!function_exists('cleanBusinessTripText')) {
    function cleanBusinessTripText($value)
    {
        return trim(str_replace(['\\r\\n', '\\n', '\\r', "\\'", '\\"'], ["\n", "\n", "\n", "'", '"'], (string)$value));
    }
}

/**
 * Load a business trip by request_inv_no.
 */
function getBusinessTripByInvNo($conDB, $requestInvNo)
{
    $stmt = $conDB->prepare("SELECT * FROM emp_business_trip WHERE request_inv_no = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("s", $requestInvNo);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

/**
 * Why a settlement cannot be created for this trip ('' = eligible).
 * Rule: trip fully approved. Creating the settlement moves the trip to completed.
 */
function getBusinessTripSettlementBlocker(array $trip)
{
    if (($trip['current_status'] ?? '') !== 'approved') {
        return 'Settlement can be created only for an approved business trip.';
    }
    return '';
}
