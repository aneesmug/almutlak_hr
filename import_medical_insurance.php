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
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
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
            <div class="content sr-page">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div>
                                <div class="sr-head">
                                    <div>
                                        <h1><?= __('import_medical_insurance', 'Import Medical Insurance') ?></h1>
                                        <p><?= __('import_medical_insurance_sub', 'Yearly renewal of employee medical insurance records') ?></p>
                                    </div>
                                    <div class="sr-head-actions">
                                        <a href="download_medical_insurance_template.php" class="sr-btn"><i class="mdi mdi-download"></i> <?= __('download_template', 'Download Excel Template') ?></a>
                                    </div>
                                </div>

                                <div class="sr-notice tone-sky"><i class="mdi mdi-information-outline"></i><div><?= __('import_medical_insurance_desc', 'Bulk-import medical insurance details (Insurance No, amount, expiry, class) for the yearly renewal. Download the template, fill in a row per employee, then upload it here. Each row you import becomes that employee\'s new ACTIVE record - their previous active record (if any) is automatically marked Expired, not deleted.') ?></div></div>

                                <div class="sr-import">
                                    <div>
                                        <div class="sr-card">
                                            <div class="sr-card-head">
                                                <h2 class="sr-card-title"><i class="mdi mdi-hospital"></i> <?= __('upload_file', 'Upload file') ?></h2>
                                            </div>
                                            <div class="sr-card-body">
                                                <form id="importInsuranceForm" class="sr-form" enctype="multipart/form-data" novalidate>
                                                    <input type="hidden" name="ajaxType" value="bulk_import_employee_medical_insurance">
                                                    <label class="sr-filepick is-lg" for="insurance_file">
                                                        <input type="file" name="insurance_file" id="insurance_file" accept=".csv,.xlsx,.xls">
                                                        <i class="mdi mdi-cloud-upload"></i>
                                                        <span><b class="js-file-name"><?= __('choose_file', 'Choose a file or drop it here') ?></b>
                                                        <small><?= __('supported_formats_csv_xlsx_xls', 'Supported formats: .csv, .xlsx, .xls') ?></small></span>
                                                    </label>
                                                    <div class="sr-import-actions">
                                                        <button type="submit" class="sr-btn sr-btn-primary" id="importBtn"><i class="mdi mdi-upload"></i> <?= __('import_medical_insurance_button', 'Import Medical Insurance') ?></button>
                                                    </div>
                                                </form>

                                                <div id="resultBox" class="sr-result" style="display:none;">
                                                    <div class="sr-stats">
                                                        <div class="sr-stat is-green">
                                                            <div class="sr-stat-label"><?= __('imported', 'Imported') ?> <i class="mdi mdi-check-circle-outline"></i></div>
                                                            <div class="sr-stat-value" id="insertedCount">0</div>
                                                        </div>
                                                        <div class="sr-stat is-red">
                                                            <div class="sr-stat-label"><?= __('skipped', 'Skipped') ?> <i class="mdi mdi-alert-circle-outline"></i></div>
                                                            <div class="sr-stat-value" id="skippedCount">0</div>
                                                            <div class="sr-stat-sub" id="skippedIds"></div>
                                                        </div>
                                                    </div>
                                                    <div class="sr-table-wrap" id="errorTableWrap" style="display:none;">
                                                        <table class="sr-table">
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
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sr-card">
                                        <div class="sr-card-head">
                                            <h2 class="sr-card-title"><i class="mdi mdi-table"></i> <?= __('column_reference', 'Column Reference') ?></h2>
                                        </div>
                                        <div class="sr-table-wrap">
                                            <table class="sr-table">
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
                                                        <td><span class="sr-pill sr-pill-xs tone-red"><?= __('required', 'Required') ?></span></td>
                                                        <td><?= __('emp_id_must_match_note', 'Must match an existing employee ID (e.g. 4020)') ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>insurance_no</code></td>
                                                        <td><span class="sr-pill sr-pill-xs tone-slate"><?= __('optional', 'Optional') ?></span></td>
                                                        <td><?= __('insurance_policy_number_note', 'Insurance policy number') ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>med_insurance</code></td>
                                                        <td><span class="sr-pill sr-pill-xs tone-slate"><?= __('optional', 'Optional') ?></span></td>
                                                        <td><?= __('med_insurance_amount_note', 'Medical insurance premium/amount (SAR)') ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>medical_expiry</code></td>
                                                        <td><span class="sr-pill sr-pill-xs tone-slate"><?= __('optional', 'Optional') ?></span></td>
                                                        <td><?= __('format_yyyy_mm_dd', 'Format YYYY-MM-DD') ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>medical_class</code></td>
                                                        <td><span class="sr-pill sr-pill-xs tone-slate"><?= __('optional', 'Optional') ?></span></td>
                                                        <td><?= __('medical_class_options_note', 'One of: CLT, C, B, A, A+') ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
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
    <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
        $(document).ready(function() {
            SRForm.bindFilePicker($('#importInsuranceForm'));
            $('#importInsuranceForm').on('submit', function(e) {
                e.preventDefault();
                if (!$('#insurance_file')[0].files.length) {
                    $(this).find('.sr-filepick').addClass('is-invalid');
                    return;
                }
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
                            $('#insertedCount').text(response.inserted);
                            $('#skippedCount').text(response.skipped);
                            $('#skippedIds').text(response.skipped_emp_ids && response.skipped_emp_ids.length ? '<?= __('employee_ids', 'Employee IDs') ?>: ' + response.skipped_emp_ids.join(', ') : '');

                            const $errBody = $('#errorTableBody').empty();
                            const errs = response.errors || [];
                            if (errs.length) {
                                errs.forEach(function(err) {
                                    const $tr = $('<tr></tr>');
                                    $('<td></td>').text(err.row).appendTo($tr);
                                    $('<td></td>').text(err.emp_id).appendTo($tr);
                                    $('<td></td>').addClass('is-bad').text(err.reason).appendTo($tr);
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
