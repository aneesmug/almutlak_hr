<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$allowed = ($is_system_admin ?? false) || ($isHR ?? false) || ($isFinance ?? false);
if (!$allowed) {
    header("Location: ./dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('import_loan_opening_balance', 'Import Loan Opening Balance') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .result-summary { background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; padding:16px; margin-top:20px; }
        .result-summary span { display:inline-block; margin-right:20px; font-weight:600; }
        .error-table-wrap { max-height:320px; overflow-y:auto; margin-top:10px; }
        .error-table-wrap td.reason-cell { color:#dc3545; }
        .info-box { background:#e7f3ff; border-left:4px solid #007bff; padding:12px; margin-bottom:16px; }
    </style>
    <?php if ($is_rtl) : ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                        <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>

        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <h4 class="header-title m-t-0 m-b-30"><?= __('import_loan_opening_balance', 'Import Loan Opening Balance') ?></h4>

                                <div class="info-box">
                                    <?= __('import_loan_opening_balance_desc', 'Bulk-import outstanding loan balances carried over from the old system. Download the template, fill in each employee\'s remaining balance, then upload it here. Imported records are marked as legacy history and will not enter the active loan approval workflow.') ?>
                                </div>

                                <a href="download_loan_opening_balance_template.php" class="btn btn-secondary mb-3">
                                    <i class="fa fa-download"></i> <?= __('download_template', 'Download Excel Template') ?>
                                </a>

                                <form id="importBalanceForm" enctype="multipart/form-data">
                                    <input type="hidden" name="ajaxType" value="import_loan_opening_balance">
                                    <div class="form-group col-md-6 px-0">
                                        <label><?= __('excel_file', 'Excel / CSV File') ?></label>
                                        <input type="file" name="balance_file" id="balance_file" class="form-control" accept=".csv,.xlsx,.xls" required>
                                        <small class="form-text text-muted"><?= __('supported_formats_csv_xlsx_xls', 'Supported formats: .csv, .xlsx, .xls') ?></small>
                                    </div>
                                    <button type="submit" class="btn btn-primary waves-effect waves-light" id="importBtn">
                                        <i class="fa fa-upload"></i> <?= __('import_loans', 'Import Loans') ?>
                                    </button>
                                </form>

                                <div id="resultBox" class="result-summary" style="display:none;">
                                    <span class="text-success" id="insertedCount"></span>
                                    <span class="text-danger" id="skippedCount"></span>
                                    <div class="error-table-wrap" id="errorTableWrap" style="display:none;">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th><?= __('row', 'Row') ?></th>
                                                    <th><?= __('employee_id', 'Employee ID') ?></th>
                                                    <th><?= __('reason', 'Reason') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody id="errorTableBody"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <hr>
                                <h6><?= __('column_reference', 'Column Reference') ?></h6>
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th><?= __('column', 'Column') ?></th>
                                            <th><?= __('required', 'Required') ?></th>
                                            <th><?= __('notes', 'Notes') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>employee_id</code></td>
                                            <td><?= __('yes', 'Yes') ?></td>
                                            <td><?= __('emp_id_4_digit_note', '4-digit numeric Employee ID matching an existing employee (e.g. 4020)') ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>opening_balance</code></td>
                                            <td><?= __('yes', 'Yes') ?></td>
                                            <td><?= __('outstanding_amount_must_be_gt_0', 'Outstanding amount owed by the employee (must be > 0)') ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>loan_type</code></td>
                                            <td><?= __('no', 'No') ?></td>
                                            <td><?= __('loan_type_default_regular', 'One of: regular, emergency, end_of_service, housing, advance_salary. Defaults to regular') ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>installments</code></td>
                                            <td><?= __('no', 'No') ?></td>
                                            <td><?= __('installments_default_12', 'Number of months used to calculate the monthly deduction. Defaults to 12') ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>start_date</code></td>
                                            <td><?= __('no', 'No') ?></td>
                                            <td><?= __('start_date_default_today', 'Format YYYY-MM-DD. Defaults to today') ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer"><?= $site_footer ?></footer>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
        $(document).ready(function() {
            $('#importBalanceForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const $btn = $('#importBtn');
                $btn.prop('disabled', true);

                Swal.fire({
                    title: 'Importing...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: './includes/ajaxFile/ajaxLoan.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire({
                            title: response.title || 'Result',
                            text: response.message,
                            icon: response.type || 'info'
                        });

                        if (typeof response.inserted !== 'undefined') {
                            $('#insertedCount').text('Imported: ' + response.inserted);
                            $('#skippedCount').text('Skipped: ' + response.skipped +
                                (response.skipped_emp_ids && response.skipped_emp_ids.length ? ' (Employee IDs: ' + response.skipped_emp_ids.join(', ') + ')' : ''));

                            const $errBody = $('#errorTableBody').empty();
                            const errs = response.errors || [];
                            if (errs.length) {
                                errs.forEach(function(err) {
                                    const $tr = $('<tr></tr>');
                                    $('<td></td>').text(err.row).appendTo($tr);
                                    $('<td></td>').text(err.emp_id).appendTo($tr);
                                    $('<td></td>').addClass('reason-cell').text(err.reason).appendTo($tr);
                                    $tr.appendTo($errBody);
                                });
                                $('#errorTableWrap').show();
                            } else {
                                $('#errorTableWrap').hide();
                            }
                            $('#resultBox').show();
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {};
                        Swal.fire({
                            title: response.title || 'Error',
                            text: response.message || 'An unexpected error occurred.',
                            icon: 'error'
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html>
