<?php
echo "=== CLARIFYING THE EXACT OVERLAP ===\n\n";

// Holiday: 2026-01-01 to 2026-01-05 (5 days)
// Vacation: 2026-01-02 to 2026-01-10 (9 days)

$holiday_dates = [
    '2026-01-01',
    '2026-01-02',
    '2026-01-03',
    '2026-01-04',
    '2026-01-05'
];

$vacation_start = new DateTime('2026-01-02');
$vacation_end = new DateTime('2026-01-10');

echo "Holiday dates (5 days total):\n";
$holiday_overlap_count = 0;
foreach ($holiday_dates as $h_date) {
    $h = new DateTime($h_date);
    $within_vacation = ($h >= $vacation_start && $h <= $vacation_end);
    $status = $within_vacation ? "✓ WITHIN" : "✗ OUTSIDE";
    echo "  - " . $h_date . " $status vacation\n";
    if ($within_vacation) $holiday_overlap_count++;
}

echo "\n=== RESULTS ===\n";
echo "Total holiday days: 5\n";
echo "Holiday days within vacation: $holiday_overlap_count\n";
echo "Holiday days BEFORE/AFTER vacation: " . (5 - $holiday_overlap_count) . "\n\n";

echo "Vacation calculation:\n";
echo "  Vacation days: 9\n";
echo "  Holiday overlap: $holiday_overlap_count days\n";
echo "  Deductible days: 9 - $holiday_overlap_count = " . (9 - $holiday_overlap_count) . " days\n\n";

echo "Question: Should we deduct based on:\n";
echo "  Option A: Overlap only ($holiday_overlap_count days) → Deduction = " . (9 - $holiday_overlap_count) . " days\n";
echo "  Option B: All holidays (5 days) → Deduction = " . (9 - 5) . " days\n";
echo "  Option C: Something else?\n";
