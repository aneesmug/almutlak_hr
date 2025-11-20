<?php
    require_once("./includes/init.php");
    require_once("./includes/session_check.php");
    include('./includes/MainClass.php');
    include("./includes/avatar_select.php");
    include("./includes/Hijri_GregorianConvert.php");
    $DateConv = new Hijri_GregorianConvert;
    $format = "YYYY-MM-DD";

    require("./includes/emp_query.php");
    $emprow = mysqli_fetch_assoc($get_emp_data);

    if (!$emprow) {
        die("Employee data not found.");
    }

    // Compute age in years from DOB for header and personal info
    $years = '';
    if (!empty($emprow['dob']) && $emprow['dob'] !== '0000-00-00') {
        try {
            $dobDate = new DateTime($emprow['dob']);
            $today = new DateTime();
            $years = $dobDate->diff($today)->y;
        } catch (Exception $e) {
            $years = '';
        }
    }

    // Build More Actions HTML for SweetAlert2
    $moreActionsHtml = '';
    if ($emprow['status'] == 1) {
        $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item edit\" id=\"startUpdateRequest\" data-avatar=\"" . htmlspecialchars($emprow['avatar']) . "\" data-empid=\"" . htmlspecialchars($emprow['empid']) . "\" data-mobile=\"" . htmlspecialchars($emprow['mobile']) . "\" data-email=\"" . htmlspecialchars($emprow['email']) . "\" data-address=\"" . htmlspecialchars($emprow['address']) . "\" data-passport_number=\"" . htmlspecialchars($emprow['passport_number']) . "\" data-passport_exp=\"" . htmlspecialchars($emprow['passport_exp']) . "\"><i class=\"fa fa-edit\"></i><span>" . __('update_information') . "</span></a>";
        $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item annual-vac applyvacationAtter\" data-empid=\"{$emprow['empid']}\" data-dept=\"{$emprow['dept']}\" data-country=\"{$emprow['country']}\" data-balance=\"{$displayBalance}\"><i class=\"fa fa-plane\"></i><span>" . __('apply_annual_vacation') . "</span></a>";
        $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item apply-leave applyLeaveRequest\" data-empid=\"{$emprow['empid']}\"><i class=\"fa fa-hourglass-end\"></i><span>" . __('excuse_leave') . "</span></a>";
        // $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item apply-loan applyLoan\" data-emp_id=\"{$emprow['empid']}\" data-user_type=\"" . htmlspecialchars($_SESSION['user_type'] ?? '') . "\"><i class=\"fa fa-money-bill-wave\"></i><span>" . __('apply_loan') . "</span></a>";
    } else {
        $moreActionsHtml .= '<div style="padding:24px; text-align:center; color: var(--secondary);"><p>' . __('employee_is_inactive') . '</p></div>';
    }
    // Add HR and Sign Out button
    $moreActionsHtml .= '<hr style="margin: 0; border-color: var(--light);">';
    $moreActionsHtml .= "<a href=\"javascript:void(0);\" class=\"menu-item signout\" data-action=\"signout\"><i class=\"fa fa-sign-out\"></i><span>" . __('logout_button') . "</span></a>";
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Employee Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

    <!-- App CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <!-- Latest compiled and minified CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker.min.css" rel="stylesheet" />

    <!-- Plugins CSS -->
    
    <!-- Additional Plugins -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        /* ===== HEADER SECTION ===== */
        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            border-radius: 16px;
            padding: 40px;
            color: white;
            margin-bottom: 40px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            position: relative;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Inactive employee header color */
        .profile-header.inactive {
            background: linear-gradient(135deg, var(--danger) 0%, #b02a37 100%);
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .profile-header .container-custom {
            display: grid;
            grid-template-columns: auto 1fr auto auto auto;
            gap: 40px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            background: rgba(255, 255, 255, 0.1);
        }

        .profile-header-info h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .profile-header-info p {
            font-size: 14px;
            opacity: 0.9;
            margin: 4px 0;
        }

        .profile-quick-stats {
            display: flex;
            gap: 30px;
            justify-content: flex-end;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .qr-code {
            width: 130px;
            height: 130px;
        }

        /* ===== MAIN CONTENT ===== */
        .profile-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ===== CARDS GRID ===== */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* ===== INFO CARD ===== */
        .info-card {
            background: var(--white);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .info-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--light);
        }

        .info-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .info-card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--light);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 13px;
            color: var(--secondary);
            font-weight: 500;
        }

        .info-value {
            font-size: 14px;
            color: var(--dark);
            font-weight: 500;
            text-align: right;
        }

        /* ===== ACTION CARDS ===== */
        .action-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 40px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .action-card {
            background: var(--white);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            cursor: pointer;
            border-top: 4px solid var(--primary);
        }

        .action-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-8px);
        }

        .action-card.blue {
            border-top-color: var(--primary);
        }

        .action-card.green {
            border-top-color: var(--success);
        }

        .action-card.info {
            border-top-color: var(--info);
        }

        .action-card.warning {
            border-top-color: var(--warning);
        }

        .action-card.danger {
            border-top-color: var(--danger);
        }

        .action-icon {
            font-size: 36px;
            margin-bottom: 12px;
            display: block;
        }

        .action-card.blue .action-icon {
            color: var(--primary);
        }

        .action-card.green .action-icon {
            color: var(--success);
        }

        .action-card.info .action-icon {
            color: var(--info);
        }

        .action-card.warning .action-icon {
            color: var(--warning);
        }

        .action-card.danger .action-icon {
            color: var(--danger);
        }

        .action-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .action-desc {
            font-size: 12px;
            color: var(--secondary);
            margin-bottom: 12px;
        }

        .action-btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            background: var(--light);
            color: var(--dark);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .action-card.blue .action-btn:hover {
            background: var(--primary);
            color: white;
        }

        .action-card.green .action-btn:hover {
            background: var(--success);
            color: white;
        }

        .action-card.info .action-btn:hover {
            background: var(--info);
            color: white;
        }

        .action-card.warning .action-btn:hover {
            background: var(--warning);
            color: white;
        }

        .action-card.danger .action-btn:hover {
            background: var(--danger);
            color: white;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .profile-header {
                padding: 24px;
            }

            .profile-header .container-custom {
                grid-template-columns: auto 1fr auto;
                gap: 20px;
            }

            .profile-quick-stats {
                grid-column: 1 / -1;
                justify-content: space-around;
                gap: 15px;
            }

            .header-buttons {
                grid-column: 1 / -1;
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .buttons-left {
                flex-direction: column;
                gap: 10px;
            }

            .more-actions-btn {
                width: 100%;
                justify-content: center;
            }

            .qr-code {
                width: 120px;
                height: 120px;
            }

            .profile-avatar {
                width: 80px;
                height: 80px;
            }

            .profile-header-info h1 {
                font-size: 22px;
            }

            .cards-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .action-cards-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .action-card {
                padding: 16px;
            }

            .action-icon {
                font-size: 28px;
            }

            .action-title {
                font-size: 13px;
            }

            .action-desc {
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .profile-header {
                padding: 16px;
                margin-bottom: 20px;
            }

            .profile-header .container-custom {
                grid-template-columns: auto;
                gap: 16px;
            }

            .profile-quick-stats {
                flex-direction: column;
                gap: 12px;
            }

            .qr-code {
                width: 120px;
                height: 120px;
                justify-self: center;
            }

            .cards-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .action-cards-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .info-card {
                padding: 16px;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .info-value {
                text-align: left;
            }
        }

        /* ===== UTILITY ===== */
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .section-title i {
            font-size: 24px;
            color: var(--primary);
        }

        .copy-btn {
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .copy-btn:hover {
            opacity: 1;
        }

        /* ===== MORE ACTIONS MODAL ===== */
        .more-actions-wrapper {
            position: relative;
            display: inline-block;
        }

        .more-actions-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .more-actions-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }

        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-overlay.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        /* Modal Window */
        .more-actions-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            min-width: 380px;
            max-width: 95vw;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease-out;
        }

        .more-actions-modal.active {
            display: block;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translate(-50%, -40%) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        .modal-header {
            padding: 24px;
            border-bottom: 2px solid var(--light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }

        .modal-close-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--secondary);
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .modal-close-btn:hover {
            background: var(--light);
            color: var(--dark);
        }

        .modal-content {
            padding: 0;
        }

        .menu-item {
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            color: var(--dark);
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
            border-bottom: 1px solid var(--light);
            transition: background-color 0.2s ease;
        }

        .menu-item:last-child {
            border-bottom: none;
        }

        .menu-item:hover {
            background-color: var(--light);
        }

        .menu-item i {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }

        .menu-item.add-documents i {
            color: var(--primary);
        }

        .menu-item.assign-asset i {
            color: var(--secondary);
        }

        .menu-item.apply-loan i {
            color: var(--warning);
        }

        .menu-item.annual-vac i {
            color: var(--info);
        }

        .menu-item.apply-leave i {
            color: var(--success);
        }

        .menu-item.edit i {
            color: var(--primary);
        }

        .menu-item.note i {
            color: #ffc107;
        }

        .menu-item.end-service i {
            color: var(--danger);
        }

        /* RTL Support */
        [dir="rtl"] .profile-header .container-custom {
            grid-template-columns: auto auto 1fr auto;
        }

        [dir="rtl"] .profile-quick-stats {
            justify-content: flex-start;
        }

        [dir="rtl"] .info-row {
            flex-direction: row-reverse;
        }

        [dir="rtl"] .info-label,
        [dir="rtl"] .info-value {
            text-align: left;
        }

        /* Select2 alignment with Bootstrap form controls */
        .select2-container { width: 100% !important; }
        .select2-container .select2-selection--single { 
            height: 38px; 
            border: 1px solid #ced4da; 
            border-radius: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered { 
            line-height: 36px; 
            padding-left: 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { 
            height: 36px; 
        }

        /* Ensure dropdowns appear above SweetAlert2 */
        .select2-container--open { z-index: 99999 !important; }
        .select2-dropdown { 
            z-index: 99999 !important; 
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .select2-results__option { 
            padding: 8px 12px;
            font-size: 14px;
        }
        .select2-results__option--highlighted { 
            background-color: #4e73df !important;
            color: white !important;
        }
        .select2-results__option[aria-selected="true"] {
            background-color: #e9ecef;
        }
        .select2-search--dropdown {
            padding: 8px;
        }
        .select2-search__field {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px 12px;
            width: 100% !important;
        }
        .datepicker-dropdown { z-index: 99999 !important; }
        .daterangepicker { z-index: 99999 !important; }
        .datepicker table tr td.disabled,
        .datepicker table tr td.disabled:hover {
            color: #ff0000;
            background: #ffeeee;
        }
    </style>

    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>

</head>

<body>
    <div class="profile-container">
        <!-- HEADER SECTION -->
        <div class="profile-header<?= ((int)($emprow['status'] ?? 1) === 0 ? ' inactive' : '') ?>">
            <div class="container-custom">
                <img src="<?= $emprow['avatar'] ?>" alt="<?= $emprow['name'] ?>" class="profile-avatar">

                <div class="profile-header-info">
                    <h1><?= htmlspecialchars($emprow['name']) ?></h1>
                    <p><strong><?= __('employee_id') ?>:</strong> <?= htmlspecialchars($emprow['empid']) ?></p>
                    <p><strong><?= __('department') ?>:</strong> <?= ($is_rtl ?? false) ? $emprow["deptnme_ar"] : $emprow["deptnme"] ?></p>
                    <p><strong><?= __('actual_job_label') ?>:</strong> <?= ($is_rtl ?? false) ? $emprow["jobname_ar"] : $emprow["jobname"] ?></p>
                </div>

                <div class="profile-quick-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?= $years ?></div>
                        <div class="stat-label"><?= __('age') ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="liveVacationDays" data-empid="<?= htmlspecialchars($emprow['empid']) ?>"><?= htmlspecialchars($emprow['vacation_days']) ?></div>
                        <div class="stat-label"><?= __('vacation_days') ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= number_format($emprow['salary'], 0) ?></div>
                        <div class="stat-label"><?= __('salary') ?> (SAR)</div>
                    </div>
                </div>

                <?php if (file_exists("./assets/qrcodes/" . $emprow['eid'] . $emprow['empid'] . ".png")): ?>
                    <img src="./assets/qrcodes/<?= $emprow['eid'] . $emprow['empid'] . ".png" ?>" alt="QR Code" class="qr-code">
                <?php endif; ?>

                <!-- LANGUAGE SWITCHER -->
                <?php
                $switch_to_lang = ($current_lang == 'en') ? 'ar' : 'en';
                $button_text = ($current_lang == 'en') ? 'العربية' : 'English';
                $query_params = [];
                if (!empty($_SERVER['QUERY_STRING'])) {
                    parse_str($_SERVER['QUERY_STRING'], $query_params);
                }
                $query_params['change_lang'] = $switch_to_lang;
                $base_path = strtok($_SERVER['REQUEST_URI'], '?');
                $new_query_string = http_build_query($query_params);
                $switch_url = htmlspecialchars($base_path . '?' . $new_query_string);
                ?>
                <a href="<?= $switch_url ?>" class="more-actions-btn" style="text-decoration: none;">
                    <i class="fa fa-language"></i> <?= $button_text ?>
                </a>

                <!-- MORE ACTIONS BUTTON -->
                <div class="more-actions-wrapper">
                    <button class="more-actions-btn" id="moreActionsBtn">
                        <i class="fa fa-ellipsis-v"></i> <?= __('more') ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- More Actions handled by SweetAlert2 -->

        <!-- PERSONAL INFORMATION -->
        <div style="margin-bottom: 40px;">
            <h3 class="section-title"><i class="fa fa-user-circle"></i> <?= __('personal_information') ?></h3>
            <div class="cards-grid">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: var(--primary);"><i class="fa fa-id-card"></i></div>
                        <div class="info-card-title"><?= __('identity') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('iqama_id_label') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['iqama']) ?> <i class="fa fa-copy copy-btn"></i></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('iqama_id_expiry') ?></span>
                        <span class="info-value">
                            <?php
                                $iqama_h = trim($emprow['iqama_exp'] ?? '');
                                $iqama_g = trim($emprow['iqama_exp_g'] ?? '');
                                if ($iqama_h) {
                                    echo htmlspecialchars($iqama_h) . ' (' . __('hijri') . ')';
                                    try {
                                        $convG = $DateConv->HijriToGregorian($iqama_h, $format);
                                        if (!empty($convG)) echo ' / ' . htmlspecialchars($convG) . ' (' . __('gregorian') . ')';
                                    } catch (Exception $e) { /* ignore */ }
                                } elseif ($iqama_g) {
                                    echo htmlspecialchars($iqama_g) . ' (' . __('gregorian') . ')';
                                    try {
                                        $convH = $DateConv->GregorianToHijri($iqama_g, $format);
                                        if (!empty($convH)) echo ' / ' . htmlspecialchars($convH) . ' (' . __('hijri') . ')';
                                    } catch (Exception $e) { /* ignore */ }
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('passport_no_label') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['passport_number']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('passport_expiry') ?></span>
                        <span class="info-value">
                            <?php if (!empty($emprow['passport_exp'])): 
                                $pexp_g = $emprow['passport_exp'];
                                echo htmlspecialchars($pexp_g) . ' (' . __('gregorian') . ')';
                                try {
                                    $pexp_h = $DateConv->GregorianToHijri($pexp_g, $format);
                                    if (!empty($pexp_h)) echo ' / ' . htmlspecialchars($pexp_h) . ' (' . __('hijri') . ')';
                                } catch (Exception $e) { /* ignore */ }
                              else: echo 'N/A'; endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('dob_label') ?></span>
                        <span class="info-value"><?= $emprow['dob'] ?> (<?= $years ?> <?= __('yrs') ?>)</span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: var(--success);"><i class="fa fa-envelope"></i></div>
                        <div class="info-card-title"><?= __('contact') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('email') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['email']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('mobile') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['mobile']) ?> <i class="fa fa-copy copy-btn"></i></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('address') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['address'] ?? '') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('country') ?></span>
                        <span class="info-value"><?= ($is_rtl ?? false) ? $emprow["country_name_ar"] : $emprow["country_name"] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('emergency_contact') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['emg_name'] ?? '') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('emergency_mobile_no_label') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['emg_mobile'] ?? '') ?> <i class="fa fa-copy copy-btn"></i></span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: var(--info);"><i class="fa fa-briefcase"></i></div>
                        <div class="info-card-title"><?= __('employment') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('joining_date_label') ?></span>
                        <span class="info-value"><?= $emprow['joining_date'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('section_name_header') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['sectin_nme']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('contract_period_label') ?></span>
                        <span class="info-value"><?= formatPeriod($emprow["period"]) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- EMPLOYMENT DETAILS -->
        <div style="margin-bottom: 40px;">
            <h3 class="section-title"><i class="fa fa-file-contract"></i> <?= __('employment_details') ?></h3>
            <div class="cards-grid">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: var(--warning);"><i class="fa fa-money-bill"></i></div>
                        <div class="info-card-title"><?= __('salary') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('total_salary') ?></span>
                        <span class="info-value"><?= number_format($emprow['salary'], 2) ?> SAR</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('basic') ?></span>
                        <span class="info-value"><?= number_format($emprow['basic'], 2) ?> SAR</span>
                    </div>
                    <?php
                        // Dynamically list all additional salary components (allowances/benefits)
                        $salary_components = [
                            'housing'   => __('housing', 'Housing Allowance'),
                            'transport' => __('transport_allowance', 'Transport Allowance'),
                            'food'      => __('food_allowance', 'Food Allowance'),
                            'fuel'      => __('fuel_allowance', 'Fuel Allowance'),
                            'tel'       => __('telephone_allowance', 'Telephone Allowance'),
                            'cashier'   => __('cashier_allowance', 'Cashier Allowance'),
                            'misc'      => __('misc_allowance', 'Misc Allowance'),
                            'other'     => __('other_allowance', 'Other Allowance'),
                            'guard'     => __('guard_allowance', 'Guard Allowance'),
                        ];

                        foreach ($salary_components as $field => $label) {
                            if (isset($emprow[$field]) && floatval($emprow[$field]) > 0) {
                                echo '<div class="info-row">'
                                    . '<span class="info-label">' . htmlspecialchars($label) . '</span>'
                                    . '<span class="info-value">' . number_format((float)$emprow[$field], 2) . ' SAR</span>'
                                    . '</div>';
                            }
                        }
                    ?>
                </div>

                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: var(--danger);"><i class="fa fa-shield"></i></div>
                        <div class="info-card-title"><?= __('gosi_label') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('status') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['gosi']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('gosi_no') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['gosi_no']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('bank_name_label') ?></span>
                        <span class="info-value"><?= ($is_rtl ?? false) ? $emprow["b_name_ar"] : $emprow["b_name"] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('iban', 'IBAN') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['iban'] ?? '') ?> <i class="fa fa-copy copy-btn"></i></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('insurance_no', 'Insurance No.') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['insurance_no'] ?? '') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('insurance_class', 'Insurance Class') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['insurance_class'] ?? '') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('insurance_expiry', 'Insurance Expiry') ?></span>
                        <span class="info-value"><?= !empty($emprow['insurance_exp']) ? htmlspecialchars($emprow['insurance_exp']) : 'N/A' ?></span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon" style="background: #6f42c1;"><i class="fa fa-car"></i></div>
                        <div class="info-card-title"><?= __('assets') ?></div>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('salary_payment_type_label') ?></span>
                        <span class="info-value"><?= ($emprow['payment_type'] == 1 ? __('bank_option') : __('cash_option')) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('sponsorship_label') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['sponsor']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('status') ?></span>
                        <span class="info-value">
                            <?php if ($emprow['status'] == 1): ?>
                                <span style="color: var(--success); font-weight: 600;"><?= __('active') ?></span>
                            <?php else: ?>
                                <span style="color: var(--danger); font-weight: 600;"><?= __('inactive') ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('fly_trips', 'Fly Trips') ?></span>
                        <span class="info-value"><?= (int)($emprow['flystus'] ?? 0) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('encashed') ?></span>
                        <span class="info-value"><?= (int)($emprow['encashstus'] ?? 0) ?></span>
                    </div>
                    <?php 
                        $carInfo = null;
                        if (!empty($emprow['car_id']) && function_exists('car_get_info')) {
                            $carInfo = car_get_info($emprow['car_id']);
                        }
                    ?>
                    <div class="info-row">
                        <span class="info-label"><?= __('assigned_car', 'Assigned Car') ?></span>
                        <span class="info-value">
                            <?php if ($carInfo): ?>
                                <?= htmlspecialchars(trim(($carInfo['maker_name'] ?? '') . ' ' . ($carInfo['model'] ?? ''))) ?>
                                <?php if (!empty($carInfo['plate_no'])): ?> - <?= htmlspecialchars($carInfo['plate_no']) ?><?php endif; ?>
                            <?php else: ?>
                                <?= __('none', 'None') ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (!empty($emprow['leaving_reason']) || !empty($emprow['end_date'])): ?>
                    <div class="info-row">
                        <span class="info-label"><?= __('eos_leaving_reason', 'Leaving Reason') ?></span>
                        <span class="info-value"><?= htmlspecialchars(($is_rtl ?? false) ? ($emprow['leaving_reason_ar'] ?? $emprow['leaving_reason']) : ($emprow['leaving_reason'] ?? '')) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('eos_end_date', 'End Date') ?></span>
                        <span class="info-value"><?= htmlspecialchars($emprow['end_date'] ?? '') ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ACTION CARDS -->
        <div>
            <h3 class="section-title"><i class="fa fa-folder-open"></i> <?= __('employee_records') ?></h3>
            <div class="action-cards-grid">
                <a href="employee_vacation_history.php?emp_id=<?= $emprow['empid'] ?>" class="action-card blue">
                    <i class="fa fa-calendar-check action-icon"></i>
                    <div class="action-title"><?= __('vacation_history') ?></div>
                    <div class="action-desc"><?= __('view_all_vacation_records') ?></div>
                    <button class="action-btn"><?= __('view') ?></button>
                </a>

                <a href="employee_loan_history.php?emp_id=<?= $emprow['empid'] ?>" class="action-card green">
                    <i class="fa fa-money-bill action-icon"></i>
                    <div class="action-title"><?= __('loan_history') ?></div>
                    <div class="action-desc"><?= __('view_loan_applications') ?></div>
                    <button class="action-btn"><?= __('view') ?></button>
                </a>

                <a href="employee_assigned_assets.php?emp_id=<?= $emprow['empid'] ?>" class="action-card info">
                    <i class="fa fa-car action-icon"></i>
                    <div class="action-title"><?= __('assigned_assets') ?></div>
                    <div class="action-desc"><?= __('view_equipment_vehicles') ?></div>
                    <button class="action-btn"><?= __('view') ?></button>
                </a>

                <a href="employee_payroll_slip.php?emp_id=<?= $emprow['empid'] ?>" class="action-card warning">
                    <i class="fa fa-file-invoice action-icon"></i>
                    <div class="action-title"><?= __('payroll_slips') ?></div>
                    <div class="action-desc"><?= __('download_salary_slips') ?></div>
                    <button class="action-btn"><?= __('view') ?></button>
                </a>

                <a href="employee_warnings.php?emp_id=<?= $emprow['empid'] ?>" class="action-card danger">
                    <i class="fa fa-exclamation-circle action-icon"></i>
                    <div class="action-title"><?= __('warnings') ?></div>
                    <div class="action-desc"><?= __('view_disciplinary_records') ?></div>
                    <button class="action-btn"><?= __('view') ?></button>
                </a>
            </div>
        </div>
    </div>

    <!-- Hidden file input for image cropping -->
    <input type="file" id="img-crop-input" accept="image/*" style="display: none;">

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.js"></script>
    <!-- Moment.js for date manipulation -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <!-- Date Pickers -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>
    <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Main App JS -->
    <script src="assets/js/employee_profile.js"></script>
    <script src="assets/js/loanHandling.js"></script>
    <script>
        $(document).ready(function() {
            var moreActionsHtml = <?= json_encode($moreActionsHtml); ?>;
                // Fetch and render live vacation days balance in header
                (function() {
                    var $vacEl = $('#liveVacationDays');
                    if ($vacEl.length === 0) return;
                    var empId = $vacEl.data('empid');
                    if (!empId) return;
                    $vacEl.text('Loading…');
                    $.ajax({
                        url: 'includes/ajaxFile/ajaxVacation.php',
                        type: 'POST',
                        dataType: 'json',
                        data: { ajaxType: 'getCurrentVacationBalance', empid: empId },
                        success: function(resp){
                            if (resp && resp.status === 200) {
                                var bal = parseFloat(resp.balance);
                                if (isNaN(bal)) { $vacEl.text('—'); return; }
                                var display = (Math.floor(bal) === bal) ? bal.toFixed(0) : bal.toFixed(2);
                                $vacEl.text(display);
                            } else {
                                $vacEl.text('—');
                            }
                        },
                        error: function(){ $vacEl.text('—'); }
                    });
                })();

            $('#moreActionsBtn').on('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '<?= __('more_actions') ?>',
                    html: '<div class="swal-more-actions">' + moreActionsHtml + '</div>',
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: '<?= __('close') ?>',
                    cancelButtonColor: '#d33',
                    width: 600,
                    padding: '0 0 10px',
                    customClass: {
                        popup: 'more-actions-swal'
                    },
                    allowOutsideClick: false
                });
            });

            // Close SweetAlert when selecting an action (except for startUpdateRequest and signout)
            $(document).on('click', '.swal-more-actions .menu-item', function() {
                // Don't auto-close for startUpdateRequest, applyLeaveRequest, or signout (they handle their own logic)
                if (
                    $(this).attr('id') !== 'startUpdateRequest' && 
                    !$(this).hasClass('applyLeaveRequest') && 
                    !$(this).hasClass('applyLoan') && 
                    !$(this).hasClass('signout') 
                ) {
                    Swal.close();
                }
            });

            // Copy to clipboard functionality (robust + works on mobile)
            $(document).on('click', '.copy-btn', function(e) {
                e.preventDefault();
                var $icon = $(this);
                var $container = $icon.closest('.info-value');
                // Get text content excluding the icon itself
                var text = $container.clone().find('.copy-btn').remove().end().text().trim();

                if (!text) return;

                function showSuccess() {
                    $icon.removeClass('fa-copy').addClass('fa-check').css('color', 'var(--success)');
                    setTimeout(function() {
                        $icon.removeClass('fa-check').addClass('fa-copy').removeAttr('style');
                    }, 2000);
                }

                function fallbackCopy(t) {
                    var $temp = $('<textarea>').css({ position: 'fixed', left: '-9999px', top: '-9999px' }).val(t).appendTo('body');
                    $temp[0].focus();
                    $temp[0].select();
                    try {
                        document.execCommand('copy');
                        showSuccess();
                    } catch (err) {
                        console.error('Copy failed:', err);
                    } finally {
                        $temp.remove();
                    }
                }

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(showSuccess).catch(function() { fallbackCopy(text); });
                } else {
                    fallbackCopy(text);
                }
            });
        });
    </script>
</body>

</html>