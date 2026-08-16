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
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('import_medical_insurance', 'Import Medical Insurance') ?></title>
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
                                <h4 class="header-title m-t-0 m-b-30"><?= __('import_medical_insurance', 'Import Medical Insurance') ?></h4>

                                <div class="info-box">
                                    <?= __('import_medical_insurance_desc', 'Bulk-import medical insurance details (Insurance No, amount, expiry, class) for the yearly renewal. Download the template, fill in a row per employee, then upload it here. Each row you import becomes that employee\'s new ACTIVE record - their previous active record (if any) is automatically marked Expired, not deleted.') ?>
                                </div>

                                <a href="download_medical_insurance_template.php" class="btn btn-secondary mb-3">
                                    <i class="fa fa-download"></i> <?= __('download_template', 'Download Excel Template') ?>
                                </a>

                                <form id="importInsuranceForm" enctype="multipart/form-data">
                                    <input type="hidden" name="ajaxType" value="bulk_import_employee_medical_insurance">
                                    <div class="form-group col-md-6 px-0">
                                        <label><?= __('excel_file', 'Excel / CSV File') ?></label>
                                        <input type="file" name="insurance_file" id="insurance_file" class="form-control" accept=".csv,.xlsx,.xls" required>
                                        <small class="form-text text-muted"><?= __('supported_formats_csv_xlsx_xls', 'Supported formats: .csv, .xlsx, .xls') ?></small>
                                    </div>
                                    <button type="submit" class="btn btn-primary waves-effect waves-light" id="importBtn">
                                        <i class="fa fa-upload"></i> <?= __('import_medical_insurance_button', 'Import Medical Insurance') ?>
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
                                            <td><code>emp_id</code></td>
                                            <td><?= __('yes', 'Yes') ?></td>
                                            <td><?= __('emp_id_must_match_note', 'Must match an existing employee ID (e.g. 4020)') ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>insurance_no</code></td>
                                            <td><?= __('no', 'No') ?></td>
                                            <td><?= __('insurance_policy_number_note', 'Insurance policy number') ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>med_insurance</code></td>
                                            <td><?= __('no', 'No') ?></td>
                                            <td><?= __('med_insurance_amount_note', 'Medical insurance premium/amount (SAR)') ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>medical_expiry</code></td>
                                            <td><?= __('no', 'No') ?></td>
                                            <td><?= __('format_yyyy_mm_dd', 'Format YYYY-MM-DD') ?></td>
                                        </tr>
                                        <tr>
                                            <td><code>medical_class</code></td>
                                            <td><?= __('no', 'No') ?></td>
                                            <td><?= __('medical_class_options_note', 'One of: CLT, C, B, A, A+') ?></td>
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
            $('#importInsuranceForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const $btn = $('#importBtn');
                $btn.prop('disabled', true);

                Swal.fire({
                    title: '<?= __('importing', 'Importing...') ?>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: './includes/ajaxFile/employeeMedicalInsuranceHandler.php',
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
                            $('#insertedCount').text('<?= __('imported', 'Imported') ?>: ' + response.inserted);
                            $('#skippedCount').text('<?= __('skipped', 'Skipped') ?>: ' + response.skipped);

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
