<?php
/****************************************************************
 * MODIFICATION SUMMARY (001-manual_vacation.php):
 * 1. NEW FILE CREATION: This is a new page dedicated to the manual entry of historical vacation data.
 * 2. UI/UX: Includes a simple interface with a button to initiate the manual entry process using SweetAlert2 popups.
 * 3. JAVASCRIPT LOGIC: Contains all the client-side JavaScript to handle the multi-step popup process, fetch employee data, calculate vacation days, and submit the final data for saving.
 ****************************************************************/
require_once __DIR__ . '/includes/session_check.php';
if (!$is_system_admin && !$isHR && !$isDeptHr) {
    header("Location: ./dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
	<meta charset="utf-8" />
	<title><?= $site_title ?> - All Employees</title>
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta content="Anees Afzal" name="author" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
	<link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
	<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
	<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
	<script src="assets/js/modernizr.min.js"></script>
	<?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
	<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
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
				<?//php include("./includes/main_menu.php"); ?>
				<div class="clearfix"></div>
			</div>
		</div>

        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title float-left"><?= __('manual_vacation_entry') ?></h4>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <h4 class="m-t-0 header-title"><b><?= __('instructions') ?></b></h4>
                                <p class="text-muted m-b-30 font-14">
                                    <?= __('manual_vacation_instruction_text') ?>
                                </p>
                                <div class="text-center">
                                    <button class="btn btn-primary waves-effect waves-light" id="addManualHistoryBtn">
                                        <i class="fa fa-plus"></i> <?= __('add_manual_history') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer">
                <?= $site_footer ?>
            </footer>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src="assets/js/jquery.app.js"></script>

    <script>
        $(document).ready(function() {
            $('#addManualHistoryBtn').on('click', addManualVacation);
        });

        function addManualVacation() {
            Swal.fire({
                title: __('enter_employee_id'),
                input: 'text',
                inputAttributes: {
                    autocapitalize: 'off'
                },
                showCancelButton: true,
                confirmButtonText: __('fetch_details'),
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                preConfirm: (empId) => {
                    if (!empId) {
                        Swal.showValidationMessage(`<?= __('employee_id_is_required') ?>`);
                        return;
                    }
                    return $.ajax({
                        url: './includes/ajaxFile/ajaxEmployee.php',
                        type: 'POST',
                        dataType: 'JSON',
                        data: {
                            ajaxType: 'get_emp_vacation_details',
                            empid: empId
                        }
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        Swal.showValidationMessage(
                            `<?= __('request_failed') ?>: ${textStatus}`
                        );
                    });
                },
                // allowOutsideClick: () => !Swal.isLoading()
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value.status === 200) {
                        showManualVacationForm(result.value.data);
                    } else {
                        Swal.fire({
                            title: __('error'),
                            text: result.value.message,
                            icon: 'error'
                        });
                    }
                }
            });
        }

        function showManualVacationForm(employeeData) {
            Swal.fire({
                title: __('add_manual_vacation_history_for') + ` ${employeeData.name}`,
                html: manualVacationHTML(employeeData),
                showCancelButton: true,
                confirmButtonText: __('save_history'),
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                width: '800px',
                didOpen: () => {
                    $('#period_start, #period_end').datepicker({
                        format: "yyyy-mm-dd",
                        todayHighlight: true,
                        autoclose: true
                    });
                    $('#period_start, #period_end').on('changeDate', calculateDays);
                },
                preConfirm: () => {
                    const period_start = $('#period_start').val();
                    const period_end = $('#period_end').val();
                    const used_days = $('#used_days').val();

                    if (!period_start || !period_end) {
                        Swal.showValidationMessage('<?= __('period_start_end_dates_required') ?>');
                        return false;
                    }
                    if (parseInt(used_days) <= 0) {
                        Swal.showValidationMessage('<?= __('used_days_must_be_greater_than_zero') ?>');
                        return false;
                    }
                    
                    const formData = $('#manualVacationForm').serializeArray();
                    let postData = { ajaxType: 'addManualHistory' };
                    formData.forEach(item => {
                        postData[item.name] = item.value;
                    });


                    return $.ajax({
                        url: './includes/ajaxFile/ajaxVacation.php',
                        type: 'POST',
                        dataType: 'JSON',
                        data: postData
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        Swal.showValidationMessage(`<?= __('request_failed') ?>: ${textStatus}`);
                    });
                },
                // allowOutsideClick: () => !Swal.isLoading()
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                     Swal.fire({
                        title: result.value.title,
                        text: result.value.message,
                        icon: result.value.type
                    }).then(() => {
                        if(result.value.type === 'success') {
                            location.reload();
                        }
                    });
                }
            });
        }

        function calculateDays() {
            const startDateStr = $('#period_start').val();
            const endDateStr = $('#period_end').val();

            if (startDateStr && endDateStr) {
                const startDate = new Date(startDateStr);
                const endDate = new Date(endDateStr);

                if (endDate < startDate) {
                    $('#used_days').val(0);
                    $('#remaining_balance').val($('#total_days').val());
                    return;
                }

                const diffTime = Math.abs(endDate - startDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                
                const totalDays = parseFloat($('#total_days').val());
                const usedDays = diffDays;
                const remainingBalance = totalDays - usedDays;

                $('#used_days').val(usedDays);
                $('#remaining_balance').val(remainingBalance.toFixed(2));
            }
        }

        function manualVacationHTML(data) {
            return `
                <form id="manualVacationForm" class="text-left">
                    <input type="hidden" name="emp_id" value="${data.emp_id}">
                    <input type="hidden" name="name" value="${data.name}">
                    <input type="hidden" name="contract_id" value="${data.vac_period_id}">
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="name"><?= __('employee_name') ?></label>
                            <input type="text" class="form-control" value="${data.name}" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="total_days"><?= __('total_vacation_days_per_period') ?></label>
                            <input type="number" step="0.01" class="form-control" id="total_days" name="total_days" value="${data.vac_period_days}" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="period_start"><?= __('period_start') ?></label>
                            <input type="text" class="form-control" id="period_start" name="period_start" placeholder="YYYY-MM-DD" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="period_end"><?= __('period_end') ?></label>
                            <input type="text" class="form-control" id="period_end" name="period_end" placeholder="YYYY-MM-DD" readonly>
                        </div>
                    </div>
                    <hr>
                     <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="used_days"><?= __('used_days') ?></label>
                            <input type="number" class="form-control" id="used_days" name="used_days" value="0" readonly style="background-color: #e9ecef;">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="remaining_balance"><?= __('remaining_balance') ?></label>
                            <input type="number" step="0.01" class="form-control" id="remaining_balance" name="remaining_balance" value="${data.vac_period_days}" readonly style="background-color: #e9ecef;">
                        </div>
                    </div>
                </form>
            `;
        }
    </script>
</body>
</html>
