<?php
/*********************************************************************************
 * MODIFICATION SUMMARY (010-end_of_service_print.php):
 *
 * 1. ADDED FOOTER SECTION: The signature block has been moved into a dedicated
 * footer section.
 * 2. FOOTER POSITIONING: The print CSS has been updated to use a flexbox layout,
 * which positions the main content at the top and fixes the signature section
 * to the bottom of the printed page.
 * 3. SINGLE-PAGE OPTIMIZATION: The layout is now structured with a main content
 * area and a footer, ensuring all elements are contained within a single A4 page
 * without compromising readability.
 * 4. RETAINED FUNCTIONALITY: All previous features, including the single-line
 * layout, bilingual text, and automatic printing, are preserved.
 *********************************************************************************/

	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	
    // Include the main employee query from the correct path
	require_once __DIR__ . '/includes/emp_query.php';

	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
	
	include("./includes/Hijri_GregorianConvert.php");
	$DateConv=new Hijri_GregorianConvert;
	$format="DD/MM/YYYY";
	
    $emprow = []; // Initialize emprow
	if (mysqli_num_rows($get_emp_data) > 0) {
		$allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}
	} else {
		//when the id not equals id show database
		header("Location: ./reg_employee.php");
        exit();
	}

    // New query to get EOS details specifically, as it's not in emp_query.php
    $get_eos_data = mysqli_query($conDB, "
        SELECT `emp_eos`.*, `eos_calc`.`details`
        FROM `emp_eos`
        LEFT JOIN `eos_calc` ON `eos_calc`.`cid` = `emp_eos`.`eos_reason`
        WHERE `emp_eos`.`emp_id`='".$_GET['emp_id']."'
    ");
    $eosrow = mysqli_fetch_assoc($get_eos_data);
    if (!$eosrow) {
        $eosrow = []; // Initialize as empty array if no EOS record found to prevent errors.
    }

    // Age Calculation
    $years = '';
    if (!empty($emprow['dob'])) {
        $birth_date = new DateTime(date('Y-m-d', strtotime(str_replace('/', '-', $emprow['dob']))));
        $current_date = new DateTime();
        $diff = $birth_date->diff($current_date);
        $years = $diff->y . " Years";
    }

?>
<!doctype html> 
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?=$site_title ?> - End of Service Print</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
    </head>
    <body class="enlarged" data-keep-enlarged="true" onload="printDiv()">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">
                <div class="slimscroll-menu" id="remove-scroll">
                    <!-- LOGO -->
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span><img src="assets/images/logo.png" alt="" height="22"></span>
                            <i><img src="assets/images/logo_sm.png" alt="" height="28"></i>
                        </a>
                    </div>
                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <!-- Sidebar -->
                    <div class="clearfix"></div>
                </div>
            </div>
            <!-- Left Sidebar End -->

            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->

            <div class="content-page">

                <!-- Top Bar Start -->
                <?php include("./includes/topbar.php"); ?>
                <!-- Top Bar End -->

                <!-- Start Page content -->
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12">
                                <!-- Printable Area -->
                                <div class="card-box" id="dvContents">
                                    <div class="print-container">
                                        <div class="main-content">
                                            <!-- Header -->
                                            <div class="header-section">
                                                <img src="assets/images/logo.png" alt="Company Logo" class="logo">
                                                <div class="header-titles">
                                                    <h2>FINAL SETTLEMENT</h2>
                                                    <h3 class="arabic-title">مخالصة نهائية</h3>
                                                </div>
                                            </div>

                                            <!-- Employee Details Section -->
                                            <div class="section">
                                                <h4 class="section-title"><span>Employee Information</span><span class="arabic-label">معلومات الموظف</span></h4>
                                                <div class="details-grid">
                                                    <div class="grid-item">
                                                        <p class="detail-line"><span><strong>Name of Employee:</strong> <?=$emprow['name']; ?></span><span class="arabic-label"><strong>اسم الموظف</strong></span></p>
                                                        <p class="detail-line"><span><strong>Iqama / ID:</strong> <?=$emprow['iqama']; ?></span><span class="arabic-label"><strong>رقم الإقامة</strong></span></p>
                                                        <p class="detail-line"><span><strong>Passport No:</strong> <?=$emprow['passport_number']; ?></span><span class="arabic-label"><strong>رقم الجواز</strong></span></p>
                                                        <p class="detail-line"><span><strong>Date of Birth:</strong> <?=(!empty($emprow['dob'])) ? date('M d, Y', strtotime(str_replace('/', '-', $emprow['dob']))) : "";?> (Age: <?=$years; ?>)</span><span class="arabic-label"><strong>تاريخ الميلاد</strong></span></p>
                                                        <p class="detail-line"><span><strong>Nationality:</strong> <?=$emprow['country_name']; ?></span><span class="arabic-label"><strong>الجنسية</strong></span></p>
                                                    </div>
                                                    <div class="grid-item">
                                                        <p class="detail-line"><span><strong>Employee ID:</strong> <?=$emprow['empid']; ?></span><span class="arabic-label"><strong>الرقم الوظيفي</strong></span></p>
                                                        <p class="detail-line"><span><strong>Department:</strong> <?=$emprow['deptnme']; ?></span><span class="arabic-label"><strong>القسم</strong></span></p>
                                                        <p class="detail-line"><span><strong>Section / Area:</strong> <?=$emprow['sectin_nme']; ?></span><span class="arabic-label"><strong>الشعبة</strong></span></p>
                                                        <p class="detail-line"><span><strong>Date Hired:</strong> <?=date('M d, Y', strtotime(str_replace('/', '-', $emprow['joining_date']))); ?></span><span class="arabic-label"><strong>تاريخ التعيين</strong></span></p>
                                                        <?php if($emprow['status'] == 0): ?>
                                                        <p class="detail-line"><span><strong>Termination Date:</strong> <?=date('M d, Y', strtotime(str_replace('/', '-', $emprow['ter_date'])));?></span><span class="arabic-label"><strong>تاريخ الإنهاء</strong></span></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Service Period Section -->
                                            <div class="section">
                                                <h4 class="section-title"><span>Service Period Summary</span><span class="arabic-label">ملخص فترة الخدمة</span></h4>
                                                <div class="details-grid-3">
                                                    <p class="detail-line"><span><strong>Years:</strong> <?=isset($eosrow['t_years']) ? $eosrow['t_years'] : 'N/A'?></span><span class="arabic-label"><strong>سنوات</strong></span></p>
                                                    <p class="detail-line"><span><strong>Months:</strong> <?=isset($eosrow['t_months']) ? $eosrow['t_months'] : 'N/A'?></span><span class="arabic-label"><strong>أشهر</strong></span></p>
                                                    <p class="detail-line"><span><strong>Days:</strong> <?=isset($eosrow['t_days']) ? $eosrow['t_days'] : 'N/A'?></span><span class="arabic-label"><strong>أيام</strong></span></p>
                                                </div>
                                                <p class="service-reason detail-line"><span><strong>End of Service Reason:</strong> <?=isset($eosrow['details']) ? $eosrow['details'] : 'N/A'?></span><span class="arabic-label"><strong>سبب نهاية الخدمة</strong></span></p>
                                            </div>

                                            <!-- Financial Settlement Section -->
                                            <div class="section">
                                                <h4 class="section-title"><span>Financial Settlement</span><span class="arabic-label">التسوية المالية</span></h4>
                                                <table class="financial-table">
                                                    <tbody>
                                                        <tr>
                                                            <td><span class="label-pair"><span>End of Service Amount (EOS)</span><span class="arabic-label">مبلغ نهاية الخدمة</span></span></td>
                                                            <td class="text-right"><?=number_format((float)(isset($eosrow['eos_amount']) ? $eosrow['eos_amount'] : 0), 2)?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><span class="label-pair"><span>Unpaid Salary (<?=isset($eosrow['curt_month_days']) ? $eosrow['curt_month_days'] : 'N/A'?> days)</span><span class="arabic-label">(أيام <?=isset($eosrow['curt_month_days']) ? $eosrow['curt_month_days'] : 'N/A'?>) رواتب غير مدفوعة</span></span></td>
                                                            <td class="text-right"><?=number_format((float)(isset($eosrow['curt_month_salry']) ? $eosrow['curt_month_salry'] : 0), 2)?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><span class="label-pair"><span>Vacation Balance (<?=isset($eosrow['anul_vac_days']) ? $eosrow['anul_vac_days'] : 'N/A'?> days)</span><span class="arabic-label">(أيام <?=isset($eosrow['anul_vac_days']) ? $eosrow['anul_vac_days'] : 'N/A'?>) رصيد الإجازات</span></span></td>
                                                            <td class="text-right"><?=number_format((float)(isset($eosrow['anul_vac_salry']) ? $eosrow['anul_vac_salry'] : 0), 2)?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><span class="label-pair"><span>Deductions (Absent / Loan)</span><span class="arabic-label">الخصومات (غياب / سلف)</span></span></td>
                                                            <td class="text-right text-danger">-<?=number_format((float)(isset($eosrow['deduct']) ? $eosrow['deduct'] : 0), 2)?></td>
                                                        </tr>
                                                        <tr class="net-payment-row">
                                                            <td><span class="label-pair"><strong>NET PAYMENT DUE</strong><strong class="arabic-label">صافي المبلغ المستحق</strong></span></td>
                                                            <td class="text-right"><strong><?=number_format((float)(isset($eosrow['net_payment']) ? $eosrow['net_payment'] : 0), 2)?> SAR</strong></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <!-- Acknowledgment Section -->
                                            <div class="section acknowledgment-section">
                                                <p>I acknowledge and undertake, the employee / <span class="text-danger"><?=$emprow['name']?></span> .ID No <span class="text-danger"><?=$emprow['iqama']?></span>. I have received all of my statutory dues from overtime and wages according to the Labor Law and the Workers from Al-Mutlak Trading from the beginning of their work until the date of leaving work.</p>
                                                <p class="arabic-text">أقر وأتعهد أنا الموظف / <span class="text-danger"><?=$emprow['name']?></span>  برقم هوية اقامة / <span class="text-danger"><?=$emprow['iqama']?></span> لقد استلمت كافة مستحقاتي القانونية من أجور العمل الإضافي والأجور حسب قانون العمل والعمال في شركة المطلق للتجارة منذ بداية عملهم حتى تاريخ ترك العمل. </p>
                                            </div>
                                        </div>
                                        <!-- Footer Section -->
                                        <div class="footer-section">
                                            <div class="signature-box">
                                                <p class="signature-line">_________________________</p>
                                                <p class="detail-line"><span><strong>Employee Signature</strong></span><span class="arabic-label"><strong>توقيع الموظف</strong></span></p>
                                                <p class="detail-line"><span><strong>Date</strong></span><span class="arabic-label"><strong>التاريخ</strong></span>:</p>
                                            </div>
                                            <div class="signature-box">
                                                <p class="signature-line">_________________________</p>
                                                <p class="detail-line"><span><strong>Company Representative</strong></span><span class="arabic-label"><strong>ممثل الشركة</strong></span></p>
                                                <p class="detail-line"><span><strong>Date</strong></span><span class="arabic-label"><strong>التاريخ</strong></span>:</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->
                    </div> <!-- container -->
                </div> <!-- content -->

                <footer class="footer">
                    <?=$site_footer ?>
                </footer>

            </div>
            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->
        </div>
        <!-- END wrapper -->
        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

		<script type="text/javascript">
function printDiv() {
    var divToPrint = document.getElementById('dvContents').innerHTML;
    var printFrame = document.createElement('iframe');

    printFrame.style.display = 'none';
    document.body.appendChild(printFrame);

    var printDocument = printFrame.contentWindow.document;
    printDocument.open();
    printDocument.write(`
        <html>
            <head>
                <title>Print</title>
                <style>
                    body {
                        -webkit-print-color-adjust: exact !important;
                        color-adjust: exact !important;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        color: #333;
                        line-height: 1.5;
                    }
                    .print-container {
                        display: flex;
                        flex-direction: column;
                        justify-content: space-between;
                        min-height: 267mm; /* A4 height (297mm) minus margins */
                    }
                    .main-content {
                        flex-grow: 1;
                    }
                    .header-section {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        border-bottom: 3px solid #4a4a4a;
                        padding-bottom: 10px;
                    }
                    .logo {
                        height: 60px;
                    }
                    .header-titles h2 {
                        margin: 0;
                        font-weight: bold;
                        font-size: 22px;
                        color: #000;
                        text-align: left;
                    }
                    .header-titles .arabic-title {
                        margin: 0;
                        direction: rtl;
                        font-size: 20px;
                        text-align: right;
                    }
                    .section {
                        margin-top: 20px;
                    }
                    .section-title {
                        display: flex;
                        justify-content: space-between;
                        align-items: baseline;
                        background-color: #343a40 !important;
                        color: #fff !important;
                        padding: 8px 15px;
                        border-radius: 5px;
                        font-weight: bold;
                        font-size: 15px;
                    }
                    .arabic-label {
                        direction: rtl;
                        text-align: right;
                    }
                    .details-grid, .details-grid-3 {
                        display: grid;
                        gap: 5px 20px;
                        margin-top: 10px;
                        font-size: 13px;
                    }
                    .details-grid {
                        grid-template-columns: 1fr 1fr;
                    }
                    .details-grid-3 {
                        grid-template-columns: repeat(3, 1fr);
                    }
                    .details-grid p, .details-grid-3 p {
                        margin-bottom: 3px;
                    }
                    .detail-line {
                        display: flex;
                        justify-content: space-between;
                        align-items: baseline;
                        border-bottom: 1px dotted #ccc;
                        padding-bottom: 3px;
                    }
                    .service-reason {
                        margin-top: 10px;
                        font-size: 13px;
                    }
                    .financial-table {
                        width: 100%;
                        margin-top: 10px;
                        border-collapse: collapse;
                        font-size: 13px;
                    }
                    .financial-table td {
                        padding: 8px;
                        border: 1px solid #dee2e6 !important;
                    }
                    .label-pair {
                        display: flex;
                        justify-content: space-between;
                        align-items: baseline;
                        width: 100%;
                    }
                    .financial-table tbody tr:nth-child(odd) {
                        background-color: #f8f9fa !important;
                    }
                    .net-payment-row {
                        font-weight: bold;
                        font-size: 15px;
                        background-color: #e9ecef !important;
                    }
                    .text-right { text-align: right; }
                    .text-danger { color: #dc3545 !important; }
                    .acknowledgment-section {
                        margin-top: 20px;
                        border-top: 1px solid #dee2e6;
                        padding-top: 10px;
                        font-size: 12px;
                        line-height: 1.4;
                    }
                    .arabic-text {
                        direction: rtl;
                        text-align: right;
                        margin-top: 10px;
                    }
                    .footer-section {
                        display: flex;
                        justify-content: space-between;
                        padding-top: 15px;
                        page-break-inside: avoid;
                        flex-shrink: 0;
                    }
                    .signature-box {
                        text-align: center;
                        width: 45%;
                        font-size: 13px;
                    }
                    .signature-line {
                        margin-bottom: 5px;
                    }
                    @page {
                        size: A4;
                        margin: 10mm;
                    }
                    @media print {
                        html, body {
                            width: 210mm;
                            height: 297mm;
                            margin: 0;
                            padding: 0;
                        }
                    }
                </style>
            </head>
            <body onload="window.focus(); window.print(); window.close();">
                ${divToPrint}
            </body>
        </html>
    `);
    printDocument.close();
}
</script>
    </body>
</html>
<?php } ?>
