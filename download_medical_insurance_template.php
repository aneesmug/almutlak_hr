<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';

$allowed = ($is_system_admin ?? false)
    || user_has_special_access($conDB, $empid ?? '', 'access_import_medical_insurance', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$allowed) {
    header("Location: error403.php?page=" . urlencode(basename(__FILE__)));
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Vendor autoload not found. Run composer install.');
}
require $autoloadPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Medical Insurance');

$headers = ['emp_id', 'insurance_no', 'med_insurance', 'medical_expiry', 'medical_class'];
$sheet->fromArray($headers, null, 'A1');

$headerStyle = $sheet->getStyle('A1:E1');
$headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('343A40');

$sampleRows = [
    ['4020', 'POL-2027-0001', 1450.75, date('Y-m-d', strtotime('+1 year')), 'B'],
    ['4021', 'POL-2027-0002', 1200.00, date('Y-m-d', strtotime('+1 year')), 'CLT'],
];
$sheet->fromArray($sampleRows, null, 'A2');

foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
    $sheet->getColumnDimension($col)->setWidth(20);
}
$sheet->getStyle('A2:A2000')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
$sheet->getStyle('D2:D2000')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

// Dropdown validation for medical_class (rows 2-2000)
$medicalClasses = ['CLT', 'C', 'B', 'A', 'A+'];
$listFormula = '"' . implode(',', $medicalClasses) . '"';
for ($row = 2; $row <= 2000; $row++) {
    $cell = $sheet->getCell("E$row");
    $validation = $cell->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_STOP);
    $validation->setAllowBlank(true);
    $validation->setShowDropDown(true);
    $validation->setShowErrorMessage(true);
    $validation->setErrorTitle('Invalid medical class');
    $validation->setError('Please select a value from the list.');
    $validation->setFormula1($listFormula);
}

$sheet->freezePane('A2');

$notesSheet = $spreadsheet->createSheet();
$notesSheet->setTitle('Instructions');
$notes = [
    ['Column', 'Required', 'Notes'],
    ['emp_id', 'Yes', 'Must match an existing employee ID (e.g. 4020)'],
    ['insurance_no', 'No', 'Insurance policy number'],
    ['med_insurance', 'No', 'Medical insurance premium/amount (SAR)'],
    ['medical_expiry', 'No', 'Format YYYY-MM-DD'],
    ['medical_class', 'No', 'One of: CLT, C, B, A, A+'],
    [],
    ['Note: Each row you import becomes the employee\'s new ACTIVE medical insurance record.'],
    ['Their previous active record (if any) is automatically marked as Expired - it is not deleted, just kept in history.'],
    ['At least one of insurance_no / med_insurance / medical_expiry / medical_class must be filled in per row.'],
];
$notesSheet->fromArray($notes, null, 'A1');
$notesSheet->getStyle('A1:C1')->getFont()->setBold(true);
foreach (['A', 'B', 'C'] as $col) {
    $notesSheet->getColumnDimension($col)->setWidth(35);
}

$spreadsheet->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="medical_insurance_import_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
