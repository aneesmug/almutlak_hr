<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$allowed = ($is_system_admin ?? false) || ($isHR ?? false) || ($isFinance ?? false);
if (!$allowed) {
    http_response_code(403);
    die('Access denied');
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
$sheet->setTitle('Loan Opening Balance');

$headers = ['employee_id', 'opening_balance', 'loan_type', 'installments', 'start_date'];
$sheet->fromArray($headers, null, 'A1');

$headerStyle = $sheet->getStyle('A1:E1');
$headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
$headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('343A40');

$sampleRows = [
    ['4020', 15000.00, 'regular', 12, date('Y-m-d')],
    ['4021', 5000.00, 'advance_salary', 12, date('Y-m-d')],
    ['4022', 25000.00, 'housing', 24, date('Y-m-d')],
];
$sheet->fromArray($sampleRows, null, 'A2');

foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
    $sheet->getColumnDimension($col)->setWidth(18);
}
$sheet->getStyle('A2:A500')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

// Dropdown validation for loan_type (rows 2-500)
$loanTypes = ['regular', 'emergency', 'end_of_service', 'housing', 'advance_salary'];
$listFormula = '"' . implode(',', $loanTypes) . '"';
for ($row = 2; $row <= 500; $row++) {
    $cell = $sheet->getCell("C$row");
    $validation = $cell->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_STOP);
    $validation->setAllowBlank(true);
    $validation->setShowDropDown(true);
    $validation->setShowErrorMessage(true);
    $validation->setErrorTitle('Invalid loan type');
    $validation->setError('Please select a value from the list.');
    $validation->setFormula1($listFormula);
}

$sheet->freezePane('A2');

$notesSheet = $spreadsheet->createSheet();
$notesSheet->setTitle('Instructions');
$notes = [
    ['Column', 'Required', 'Notes'],
    ['employee_id', 'Yes', 'Must be a 4-digit numeric Employee ID matching an existing employee (e.g. 4020)'],
    ['opening_balance', 'Yes', 'Outstanding loan amount owed by the employee (must be > 0)'],
    ['loan_type', 'No', 'One of: regular, emergency, end_of_service, housing, advance_salary. Defaults to regular if left blank'],
    ['installments', 'No', 'Number of months to spread the remaining balance over. Defaults to 12'],
    ['start_date', 'No', 'Format YYYY-MM-DD. Defaults to today if left blank'],
    [],
    ['Note: Imported records are tagged as legacy (opening balance) loans and will not appear in the active approval workflow or pending-approval reports.'],
];
$notesSheet->fromArray($notes, null, 'A1');
$notesSheet->getStyle('A1:C1')->getFont()->setBold(true);
foreach (['A', 'B', 'C'] as $col) {
    $notesSheet->getColumnDimension($col)->setWidth(30);
}

$spreadsheet->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="loan_opening_balance_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
