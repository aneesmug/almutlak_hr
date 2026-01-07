<?php
// Test the holiday day calculation logic

echo "=== Testing Holiday Day Calculation ===\n\n";

// Employee 5430 case:
// Vacation: 9 days
// Balance before: 17.53
// Balance after: 11.53
// Deducted: 6 days
// Holiday period: 1-1-2025 to 1-5-2025

$vac_start_str = '2025-01-01';
$vac_end_str = '2025-01-09'; // 9 days inclusive
$holiday_start_str = '2025-01-01';
$holiday_end_str = '2025-01-05'; // 5 days inclusive

$vac_start = new DateTime($vac_start_str);
$vac_end = new DateTime($vac_end_str);
$holiday_start = new DateTime($holiday_start_str);
$holiday_end = new DateTime($holiday_end_str);

echo "Vacation period: $vac_start_str to $vac_end_str\n";
echo "Holiday period: $holiday_start_str to $holiday_end_str\n\n";

// Count vacation days manually
$temp = clone $vac_start;
$vac_days_count = 0;
while ($temp <= $vac_end) {
    $vac_days_count++;
    $temp->modify('+1 day');
}
echo "Actual vacation days (inclusive): $vac_days_count\n";

// Count holiday days manually
$temp = clone $holiday_start;
$holiday_days_count = 0;
while ($temp <= $holiday_end) {
    $holiday_days_count++;
    $temp->modify('+1 day');
}
echo "Actual holiday days (inclusive): $holiday_days_count\n\n";

// --- Current Function Logic ---
echo "--- Current Function Logic ---\n";
$overlap_start = $vac_start > $holiday_start ? $vac_start : $holiday_start;
$overlap_end = $vac_end < $holiday_end ? $vac_end : $holiday_end;

echo "Overlap start: " . $overlap_start->format('Y-m-d') . "\n";
echo "Overlap end: " . $overlap_end->format('Y-m-d') . "\n";

$interval = $overlap_start->diff($overlap_end);
echo "DateTime->diff() returns: " . $interval->days . " days\n";
echo "Current code adds +1: " . ($interval->days + 1) . " days\n\n";

// Manual count of overlap
$temp = clone $overlap_start;
$overlap_count = 0;
while ($temp <= $overlap_end) {
    $overlap_count++;
    $temp->modify('+1 day');
}
echo "Manual overlap count (inclusive): $overlap_count\n\n";

// Calculate what should be deducted
echo "=== Deduction Calculation ===\n";
echo "Total vacation days: " . $vac_days_count . "\n";
echo "Holiday days in vacation: " . $overlap_count . "\n";
echo "Expected deduction: " . ($vac_days_count - $overlap_count) . " days\n";
echo "Starting balance: 17.53\n";
echo "Expected ending balance: " . (17.53 - ($vac_days_count - $overlap_count)) . "\n\n";

echo "=== Employee 5430 Actual Result ===\n";
echo "Starting balance: 17.53\n";
echo "Ending balance: 11.53\n";
echo "Actual deduction: " . (17.53 - 11.53) . " days\n";
echo "DISCREPANCY: Should deduct " . ($vac_days_count - $overlap_count) . " but deducted " . (17.53 - 11.53) . "\n";
