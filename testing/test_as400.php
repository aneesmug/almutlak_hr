<?php
require_once './includes/db.php';

header('Content-Type: application/json');

$emp_id = isset($_GET['emp_id']) ? trim($_GET['emp_id']) : '';
if ($emp_id === '') {
    echo json_encode(['error' => 'emp_id is required']);
    exit;
}

// Fetch employee joining_date and contract info
$emp_stmt = $conDB->prepare("SELECT e.joining_date, e.vac_period, cp.period, cp.vac_period AS total_period_days
                              FROM employees e JOIN contract_period cp ON e.vac_period = cp.id
                              WHERE e.emp_id = ? LIMIT 1");
$emp_stmt->bind_param('s', $emp_id);
$emp_stmt->execute();
$emp = $emp_stmt->get_result()->fetch_assoc();
$emp_stmt->close();

if (!$emp) {
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

$join = new DateTime(str_replace('/', '-', $emp['joining_date']));
$join->setTime(0,0,0);
$today = new DateTime();
$today->setTime(0,0,0);

// Parse contract years from string like "2 Years - 30"
preg_match('/(\d+)/', $emp['period'], $m);
$years = isset($m[1]) ? (int)$m[1] : 1;
$total_period_days = (float)$emp['total_period_days'];
$annual_rate = ($years == 2) ? ($total_period_days / 2.0) : $total_period_days;

// Helper calculators
function round2($v){ return round($v, 2); }
function floor2($v){ return floor($v * 100.0) / 100.0; }

$days_inclusive = $join->diff($today)->days;               // inclusive of start date effect via diff
$yesterday = (clone $today)->sub(new DateInterval('P1D'));
$days_exclusive = $join->diff($yesterday)->days;           // exclude current day

$rates = [
  '365'    => $annual_rate / 365.0,
  '365.25' => $annual_rate / 365.25,
  '360'    => $annual_rate / 360.0,
];

$variants = [];
foreach ($rates as $label => $rate) {
    $variants["rate_$label"] = [
        'daily_rate' => $rate,
        'inclusive_round' => round2($days_inclusive * $rate),
        'inclusive_floor' => floor2($days_inclusive * $rate),
        'exclusive_round' => round2($days_exclusive * $rate),
        'exclusive_floor' => floor2($days_exclusive * $rate),
    ];
}

// Also compute month-based approximation (2.5 days/month) with partial months prorated by day/30
$months_based = (function() use ($join, $today, $annual_rate) {
    $per_month = $annual_rate / 12.0; // e.g., 2.5 for 30 days/year
    // Count full months boundary from join-day to today-day
    $start = clone $join;
    $end = clone $today;
    $full_months = 0;
    $cur = clone $start;
    while (true) {
        $next = (clone $cur)->add(new DateInterval('P1M'));
        if ($next > $end) break;
        $full_months++;
        $cur = $next;
    }
    $rem_days = $end->diff($cur)->days;
    $approx = ($full_months * $per_month) + ($rem_days / 30.0) * $per_month;
    return round($approx, 2);
})();

// Collect result
$res = [
    'emp_id' => $emp_id,
    'joining_date' => $join->format('Y-m-d'),
    'today' => $today->format('Y-m-d'),
    'annual_rate' => $annual_rate,
    'inclusive_days' => $days_inclusive,
    'exclusive_days' => $days_exclusive,
    'variants' => $variants,
    'month_based_prorated' => $months_based,
];

echo json_encode($res);
