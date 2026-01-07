<?php
echo "=== ANALYZING THE EXACT ISSUE ===\n\n";

$vac_start = new DateTime('2026-01-02');
$vac_end = new DateTime('2026-01-10');

// Count vacation days manually
$temp = clone $vac_start;
$vac_count = 0;
while ($temp <= $vac_end) {
    $vac_count++;
    $temp->modify('+1 day');
}

echo "Vacation period: 2026-01-02 to 2026-01-10\n";
echo "Actual vacation days (inclusive): $vac_count\n";
echo "Database says vacation days: 9\n";
echo "Match: " . ($vac_count === 9 ? "YES" : "NO - MISMATCH!") . "\n\n";

// Holiday overlap
$h_start = new DateTime('2026-01-01');
$h_end = new DateTime('2026-01-05');

$overlap_start = $vac_start > $h_start ? $vac_start : $h_start;
$overlap_end = $vac_end < $h_end ? $vac_end : $h_end;

$temp = clone $overlap_start;
$overlap_count = 0;
while ($temp <= $overlap_end) {
    $overlap_count++;
    $temp->modify('+1 day');
}

echo "Holiday period: 2026-01-01 to 2026-01-05\n";
echo "Overlap: " . $overlap_start->format('Y-m-d') . " to " . $overlap_end->format('Y-m-d') . "\n";
echo "Overlap days: $overlap_count\n";
echo "Database calculation: 4 days\n";
echo "Match: " . ($overlap_count === 4 ? "YES" : "NO") . "\n\n";

// Expected deduction
$expected = $vac_count - $overlap_count;
echo "Expected deduction: $vac_count - $overlap_count = $expected days\n";
echo "Actual deduction: 6 days\n";
echo "DIFFERENCE: " . (6 - $expected) . " days extra deducted!\n";
