<?php
require_once(__DIR__ . "/includes/init.php");
require_once(__DIR__ . "/includes/session_check.php");
require_once(__DIR__ . "/includes/screenshot_helper.php");

// Fetch screenshots from database
$screenshots_by_section = [];
try {
    // Get current language (fallback to 'en' if not set)
    $user_lang = $current_lang ?? 'en';
    
    $stmt = $pdo->prepare("
        SELECT section, step_number, title, file_path, display_order 
        FROM guide_screenshots 
        WHERE is_active = 1 AND language = :lang
        ORDER BY section, step_number, display_order
    ");
    $stmt->execute(['lang' => $user_lang]);
    $all_screenshots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by section and step
    foreach ($all_screenshots as $shot) {
        $key = $shot['section'] . '_' . $shot['step_number'];
        if (!isset($screenshots_by_section[$key])) {
            $screenshots_by_section[$key] = [];
        }
        $screenshots_by_section[$key][] = $shot;
    }
} catch (PDOException $e) {
    // Silently fail if table doesn't exist
}

// Helper function to get screenshots for a section and step
function get_section_screenshots($section, $step) {
    global $screenshots_by_section;
    $key = $section . '_' . $step;
    return $screenshots_by_section[$key] ?? [];
}
?>

<!DOCTYPE html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('system_guide') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
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
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .guide-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .guide-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .guide-header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .guide-header p {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
        }

        .nav-tabs-guide {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
        }

        .nav-tabs-guide .nav-link {
            flex: 1;
            min-width: 200px;
            padding: 16px 20px;
            border: none;
            background: white;
            color: var(--dark);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            text-align: center;
        }

        .nav-tabs-guide .nav-link:hover {
            background: var(--light);
            color: var(--primary);
        }

        .nav-tabs-guide .nav-link.active {
            background: var(--primary);
            color: white;
            border-bottom-color: var(--primary);
        }

        .tab-content-guide {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .guide-section {
            display: none;
        }

        .guide-section.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .guide-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .guide-title i {
            font-size: 28px;
        }

        .step-container {
            background: var(--light);
            border-left: 4px solid var(--primary);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            font-weight: 700;
            margin-right: 12px;
            margin-bottom: 10px;
        }

        .step-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .step-description {
            color: var(--secondary);
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .step-details {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-top: 12px;
            border: 1px solid #e0e0e0;
        }

        .detail-point {
            margin: 8px 0;
            color: var(--dark);
            line-height: 1.6;
        }

        .detail-point strong {
            color: var(--primary);
        }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid var(--info);
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            color: #01579b;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid var(--warning);
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            color: #856404;
        }

        .success-box {
            background: #d4edda;
            border-left: 4px solid var(--success);
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            color: #155724;
        }

        .screenshot-container {
            margin: 20px 0;
            text-align: center;
        }

        .screenshot-container img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 2px solid var(--light);
        }

        .screenshot-caption {
            margin-top: 10px;
            font-size: 14px;
            color: var(--secondary);
            font-style: italic;
        }

        .list-styled {
            margin: 15px 0;
            padding-left: 20px;
        }

        .list-styled li {
            margin: 8px 0;
            color: var(--dark);
            line-height: 1.6;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .feature-card {
            background: white;
            border: 2px solid var(--light);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }

        .feature-card i {
            font-size: 36px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .feature-card h5 {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .feature-card p {
            font-size: 14px;
            color: var(--secondary);
            margin: 0;
        }

        .back-button {
            margin-bottom: 20px;
        }

        .back-button a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--secondary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .back-button a:hover {
            background: var(--dark);
            transform: translateX(-4px);
        }

        @media (max-width: 768px) {
            .nav-tabs-guide {
                flex-direction: column;
            }

            .nav-tabs-guide .nav-link {
                min-width: auto;
                text-align: left;
                padding: 12px 16px;
            }

            .guide-header {
                padding: 24px 20px;
            }

            .guide-header h1 {
                font-size: 24px;
            }

            .tab-content-guide {
                padding: 24px 16px;
            }

            .guide-title {
                font-size: 20px;
            }
        }

        [dir="rtl"] .step-number {
            margin-right: 0;
            margin-left: 12px;
        }

        [dir="rtl"] .step-title {
            flex-direction: row-reverse;
        }

        [dir="rtl"] .step-details {
            text-align: right;
        }

        [dir="rtl"] .list-styled {
            padding-left: 0;
            padding-right: 20px;
        }

        .screenshot-wrapper {
            margin: 25px 0;
            text-align: center;
            background: var(--light);
            padding: 15px;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }

        .screenshot-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            min-height: 300px;
            color: #6c757d;
            font-size: 16px;
            margin: 10px 0;
            position: relative;
        }

        .screenshot-placeholder i {
            font-size: 48px;
            opacity: 0.3;
            margin-right: 15px;
        }

        .screenshot-label {
            font-size: 13px;
            color: var(--secondary);
            margin-top: 10px;
            font-style: italic;
        }

        /* Zoom Modal Styles */
        .zoom-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .zoom-modal-content {
            position: relative;
            width: 90%;
            height: 90%;
            display: flex;
            flex-direction: column;
            background: #1a1a1a;
            border-radius: 8px;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.8);
        }

        .zoom-close {
            position: absolute;
            top: 10px;
            right: 20px;
            color: white;
            font-size: 40px;
            cursor: pointer;
            z-index: 10000;
            transition: all 0.2s ease;
        }

        .zoom-close:hover {
            color: #ff6b6b;
            transform: scale(1.2);
        }

        .zoom-toolbar {
            background: #2a2a2a;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #444;
            flex-wrap: wrap;
        }

        .zoom-btn {
            background: #444;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .zoom-btn:hover {
            background: #555;
            transform: scale(1.05);
        }

        .zoom-level {
            color: #aaa;
            font-size: 14px;
            font-weight: bold;
            margin: 0 10px;
        }

        .zoom-container {
            flex: 1;
            overflow: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        #zoomImage {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.1s ease;
            cursor: grab;
        }

        #zoomImage:active {
            cursor: grabbing;
        }

        .zoom-title {
            background: #2a2a2a;
            color: #aaa;
            padding: 12px 20px;
            border-top: 1px solid #444;
            font-size: 14px;
            text-align: center;
            max-height: 60px;
            overflow: auto;
        }

        @media (max-width: 768px) {
            .zoom-modal-content {
                width: 95%;
                height: 95%;
            }

            .zoom-toolbar {
                padding: 8px 12px;
                gap: 6px;
            }

            .zoom-btn {
                padding: 6px 10px;
                font-size: 12px;
            }

            .zoom-level {
                font-size: 12px;
                margin: 0 5px;
            }
        }

        .screenshot-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .gallery-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .gallery-item:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            transform: translateY(-4px);
        }

        .gallery-item-img {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            overflow: hidden;
            position: relative;
        }

        .gallery-item-img img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            display: block;
        }

        .gallery-item-title {
            padding: 12px;
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
            text-align: center;
        }
        
        /* Zoom Navigation Buttons */
        .zoom-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            z-index: 10001;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .zoom-nav-btn:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: translateY(-50%) scale(1.1);
        }
        
        .zoom-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        .zoom-nav-left {
            left: 20px;
        }
        
        .zoom-nav-right {
            right: 20px;
        }
        
        @media (max-width: 768px) {
            .zoom-nav-btn {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .zoom-nav-left {
                left: 10px;
            }
            
            .zoom-nav-right {
                right: 10px;
            }
        }
    </style>
</head>
<body dir="<?= ($is_rtl ?? false) ? 'rtl' : 'ltr' ?>">
    <div class="guide-container">
        <!-- Back Button -->
        <div class="back-button" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="profile.php">
                <i class="fa fa-arrow-left"></i>
                <span><?= __('back_to_profile') ?></span>
            </a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): ?>
                <a href="manage_guide_screenshots.php" class="btn btn-sm btn-primary">
                    <i class="fa fa-upload"></i> Manage Screenshots
                </a>
            <?php endif; ?>
        </div>

        <!-- Header -->
        <div class="guide-header">
            <h1><i class="fa fa-book"></i> <?= __('system_guide') ?></h1>
            <p><?= __('complete_guide_to_using_almutlak_system') ?></p>
            
            <!-- Language Switcher -->
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
            <div style="margin-top: 20px;">
                <a href="<?= $switch_url ?>" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: rgba(255, 255, 255, 0.2); color: white; text-decoration: none; border-radius: 6px; transition: all 0.3s ease; border: 2px solid rgba(255, 255, 255, 0.3);" onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                    <i class="fa fa-language"></i> <?= $button_text ?>
                </a>
                <?php if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'administrator' || $isItManager == true)): ?>
                <a href="manage_guide_screenshots.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: rgba(255, 255, 255, 0.2); color: white; text-decoration: none; border-radius: 6px; transition: all 0.3s ease; border: 2px solid rgba(255, 255, 255, 0.3);" onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                    <i class="fa fa-square-dashed"></i> <?= __('screenshot_admin_page') ?>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs-guide">
            <button class="nav-link active" onclick="showTab('vacations')">
                <i class="fa fa-plane"></i> <?= __('vacations_and_leaves') ?>
            </button>
            <button class="nav-link" onclick="showTab('loans')">
                <i class="fa fa-money-bill"></i> <?= __('loans') ?>
            </button>
            <button class="nav-link" onclick="showTab('excuse')">
                <i class="fa fa-calendar-times"></i> <?= __('excuse_leave') ?>
            </button>
            <button class="nav-link" onclick="showTab('resignation')">
                <i class="fa fa-sign-out"></i> <?= __('resignation') ?>
            </button>
            <button class="nav-link" onclick="showTab('rejoin')">
                <i class="fa fa-plane-arrival"></i> <?= __('rejoin_request') ?>
            </button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content-guide">

            <!-- VACATIONS & LEAVES TAB -->
            <div id="vacations" class="guide-section active">
                <h2 class="guide-title"><i class="fa fa-plane"></i> <?= __('vacations_and_leaves') ?></h2>

                <!-- Annual Leave -->
                <div class="step-container">
                    <div class="step-number">1</div>
                    <div class="step-title">
                        <i class="fa fa-calendar-alt"></i> <?= __('apply_annual_vacation') ?>
                    </div>
                    <p class="step-description"><?= __('learn_how_to_apply_for_annual_leave') ?></p>
                    
                    <div class="step-details">
                        <div class="detail-point"><strong><?= __('step_1') ?>:</strong> <?= __('go_to_your_profile_page') ?></div>
                        <div class="detail-point"><strong><?= __('step_2') ?>:</strong> <?= __('click_on_more_button_in_header') ?></div>
                        <div class="detail-point"><strong><?= __('step_3') ?>:</strong> <?= __('select_apply_annual_vacation_option') ?></div>
                        <div class="detail-point"><strong><?= __('step_4') ?>:</strong> <?= __('fill_vacation_details_form') ?></div>
                        <div class="detail-point"><strong><?= __('step_5') ?>:</strong> <?= __('choose_vacation_start_and_end_dates') ?></div>
                        <div class="detail-point"><strong><?= __('step_6') ?>:</strong> <?= __('select_vacation_type_annual_or_fly') ?></div>
                        <div class="detail-point"><strong><?= __('step_7') ?>:</strong> <?= __('click_submit_button_to_send_request') ?></div>
                    </div>

                    <div class="info-box">
                        <strong><?= __('important') ?>:</strong> <?= __('ensure_sufficient_vacation_balance_before_applying') ?>
                    </div>

                    <div class="warning-box">
                        <strong><?= __('note') ?>:</strong> <?= __('vacation_requires_department_head_approval') ?>
                    </div>

                    <!-- Screenshots Section -->
                    <div class="screenshot-wrapper">
                        <h6 style="color: var(--primary); margin-bottom: 15px;"><i class="fa fa-image"></i> <?= __('screenshot') ?? 'Screenshots' ?></h6>
                        <?php 
                        $vacations_shots = get_section_screenshots('vacations', 1);
                        if (!empty($vacations_shots)): ?>
                            <div class="screenshot-gallery">
                                <?php foreach ($vacations_shots as $shot): ?>
                                    <div class="gallery-item" onclick="openZoomModal('<?= htmlspecialchars($shot['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($shot['title'], ENT_QUOTES) ?>')">
                                        <div class="gallery-item-img" style="height: auto; background: #f9f9f9; overflow: hidden;">
                                            <img src="<?= htmlspecialchars($shot['file_path']) ?>" alt="<?= htmlspecialchars($shot['title']) ?>" style="width: 100%; height: auto; min-height: 150px; object-fit: cover; cursor: pointer;" onerror="this.style.display='none'; this.parentElement.innerHTML += '<i class=\"fa fa-image\" style=\"font-size: 40px; color: #999; margin: 40px;\"></i>';">
                                        </div>
                                        <div class="gallery-item-title"><?= htmlspecialchars($shot['title']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="screenshot-gallery">
                                <div class="gallery-item">
                                    <div class="gallery-item-img"><i class="fa fa-user-circle"></i></div>
                                    <div class="gallery-item-title">Profile Page</div>
                                </div>
                                <div class="gallery-item">
                                    <div class="gallery-item-img"><i class="fa fa-calendar"></i></div>
                                    <div class="gallery-item-title">Vacation Form</div>
                                </div>
                                <div class="gallery-item">
                                    <div class="gallery-item-img"><i class="fa fa-check-circle"></i></div>
                                    <div class="gallery-item-title">Confirmation</div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="screenshot-label"><?= __('visual_guide_screenshots') ?? 'Visual guide showing each step of the process' ?></div>
                    </div>
                </div>

                <!-- Emergency Leave -->
                <div class="step-container">
                    <div class="step-number">2</div>
                    <div class="step-title">
                        <i class="fa fa-exclamation-circle"></i> <?= __('apply_emergency_leave') ?>
                    </div>
                    <p class="step-description"><?= __('apply_for_urgent_unplanned_leave') ?></p>
                    
                    <div class="step-details">
                        <div class="detail-point"><strong><?= __('step_1') ?>:</strong> <?= __('go_to_profile_page') ?></div>
                        <div class="detail-point"><strong><?= __('step_2') ?>:</strong> <?= __('click_more_button') ?></div>
                        <div class="detail-point"><strong><?= __('step_3') ?>:</strong> <?= __('choose_apply_vacation') ?></div>
                        <div class="detail-point"><strong><?= __('step_4') ?>:</strong> <?= __('select_emergency_as_vacation_type') ?></div>
                        <div class="detail-point"><strong><?= __('step_5') ?>:</strong> <?= __('provide_reason_for_emergency_leave') ?></div>
                        <div class="detail-point"><strong><?= __('step_6') ?>:</strong> <?= __('submit_your_request') ?></div>
                    </div>

                    <div class="success-box">
                        <strong><?= __('tip') ?>:</strong> <?= __('emergency_leave_expedited_approval') ?>
                    </div>

                    <!-- Screenshots Section -->
                    <div class="screenshot-wrapper">
                        <h6 style="color: var(--primary); margin-bottom: 15px;"><i class="fa fa-image"></i> <?= __('screenshot') ?? 'Screenshots' ?></h6>
                        <?php 
                        $emergency_shots = get_section_screenshots('vacations', 2);
                        if (!empty($emergency_shots)): ?>
                            <div class="screenshot-gallery">
                                <?php foreach ($emergency_shots as $shot): ?>
                                    <div class="gallery-item" onclick="openZoomModal('<?= htmlspecialchars($shot['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($shot['title'], ENT_QUOTES) ?>')">
                                        <div class="gallery-item-img" style="height: auto; background: #f9f9f9; overflow: hidden;">
                                            <img src="<?= htmlspecialchars($shot['file_path']) ?>" alt="<?= htmlspecialchars($shot['title']) ?>" style="width: 100%; height: auto; min-height: 150px; object-fit: cover; cursor: pointer;" onerror="this.style.display='none'; this.parentElement.innerHTML += '<i class=\"fa fa-image\" style=\"font-size: 40px; color: #999; margin: 40px;\"></i>';">
                                        </div>
                                        <div class="gallery-item-title"><?= htmlspecialchars($shot['title']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="screenshot-gallery">
                                <div class="gallery-item">
                                    <div class="gallery-item-img"><i class="fa fa-exclamation-triangle"></i></div>
                                    <div class="gallery-item-title">Emergency Selection</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Encashment -->
                <div class="step-container">
                    <div class="step-number">3</div>
                    <div class="step-title">
                        <i class="fa fa-money-bill-wave"></i> <?= __('apply_encashment') ?>
                    </div>
                    <p class="step-description"><?= __('convert_unused_vacation_days_to_cash') ?></p>
                    
                    <div class="step-details">
                        <div class="detail-point"><strong><?= __('step_1') ?>:</strong> <?= __('access_vacation_management_section') ?></div>
                        <div class="detail-point"><strong><?= __('step_2') ?>:</strong> <?= __('find_encashment_option') ?></div>
                        <div class="detail-point"><strong><?= __('step_3') ?>:</strong> <?= __('enter_number_of_days_to_encash') ?></div>
                        <div class="detail-point"><strong><?= __('step_4') ?>:</strong> <?= __('review_calculated_amount') ?></div>
                        <div class="detail-point"><strong><?= __('step_5') ?>:</strong> <?= __('submit_encashment_request') ?></div>
                        <div class="detail-point"><strong><?= __('step_6') ?>:</strong> <?= __('wait_for_hr_approval_and_payment') ?></div>
                    </div>

                    <div class="info-box">
                        <strong><?= __('important') ?>:</strong> <?= __('encashment_calculated_based_on_daily_salary') ?>
                    </div>

                    <!-- Screenshots Section -->
                    <div class="screenshot-wrapper">
                        <h6 style="color: var(--primary); margin-bottom: 15px;"><i class="fa fa-image"></i> <?= __('screenshot') ?? 'Screenshots' ?></h6>
                        <?php 
                        $encashment_shots = get_section_screenshots('vacations', 3);
                        if (!empty($encashment_shots)): ?>
                            <div class="screenshot-gallery">
                                <?php foreach ($encashment_shots as $shot): ?>
                                    <div class="gallery-item" onclick="openZoomModal('<?= htmlspecialchars($shot['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($shot['title'], ENT_QUOTES) ?>')">
                                        <div class="gallery-item-img" style="height: auto; background: #f9f9f9; overflow: hidden;">
                                            <img src="<?= htmlspecialchars($shot['file_path']) ?>" alt="<?= htmlspecialchars($shot['title']) ?>" style="width: 100%; height: auto; min-height: 150px; object-fit: cover; cursor: pointer;" onerror="this.style.display='none'; this.parentElement.innerHTML += '<i class=\"fa fa-image\" style=\"font-size: 40px; color: #999; margin: 40px;\"></i>';">
                                        </div>
                                        <div class="gallery-item-title"><?= htmlspecialchars($shot['title']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="screenshot-gallery">
                                <div class="gallery-item">
                                    <div class="gallery-item-img"><i class="fa fa-money-bill-wave"></i></div>
                                    <div class="gallery-item-title">Encashment Option</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- LOANS TAB -->
            <div id="loans" class="guide-section">
                <h2 class="guide-title"><i class="fa fa-money-bill"></i> <?= __('loans') ?></h2>

                <!-- EOS Loan -->
                <div class="step-container">
                    <div class="step-number">1</div>
                    <div class="step-title">
                        <i class="fa fa-graduation-cap"></i> <?= __('end_of_service_loan') ?>
                    </div>
                    <p class="step-description"><?= __('apply_for_eos_loan_before_retirement') ?></p>
                    
                    <div class="step-details">
                        <div class="detail-point"><strong><?= __('eligibility') ?>:</strong> <?= __('employees_nearing_end_of_service') ?></div>
                        <div class="detail-point"><strong><?= __('step_1') ?>:</strong> <?= __('go_to_profile_page') ?></div>
                        <div class="detail-point"><strong><?= __('step_2') ?>:</strong> <?= __('click_more_button') ?></div>
                        <div class="detail-point"><strong><?= __('step_3') ?>:</strong> <?= __('select_apply_loan_option') ?></div>
                        <div class="detail-point"><strong><?= __('step_4') ?>:</strong> <?= __('choose_eos_loan_type') ?></div>
                        <div class="detail-point"><strong><?= __('step_5') ?>:</strong> <?= __('enter_loan_amount_requested') ?></div>
                        <div class="detail-point"><strong><?= __('step_6') ?>:</strong> <?= __('select_monthly_installment_amount') ?></div>
                        <div class="detail-point"><strong><?= __('step_7') ?>:</strong> <?= __('submit_for_approval') ?></div>
                    </div>

                    <div class="warning-box">
                        <strong><?= __('note') ?>:</strong> <?= __('eos_loan_max_amount_based_on_tenure') ?>
                    </div>

                    <!-- Screenshots Section -->
                    <div class="screenshot-wrapper">
                        <h6 style="color: var(--primary); margin-bottom: 15px;"><i class="fa fa-image"></i> <?= __('screenshot') ?? 'Screenshots' ?></h6>
                        <?php 
                        $eos_shots = get_section_screenshots('loans', 1);
                        if (!empty($eos_shots)): ?>
                            <div class="screenshot-gallery">
                                <?php foreach ($eos_shots as $shot): ?>
                                    <div class="gallery-item" onclick="openZoomModal('<?= htmlspecialchars($shot['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($shot['title'], ENT_QUOTES) ?>')">
                                        <div class="gallery-item-img" style="height: auto; background: #f9f9f9; overflow: hidden;">
                                            <img src="<?= htmlspecialchars($shot['file_path']) ?>" alt="<?= htmlspecialchars($shot['title']) ?>" style="width: 100%; height: auto; min-height: 150px; object-fit: cover; cursor: pointer;" onerror="this.style.display='none'; this.parentElement.innerHTML += '<i class=\"fa fa-image\" style=\"font-size: 40px; color: #999; margin: 40px;\"></i>';">
                                        </div>
                                        <div class="gallery-item-title"><?= htmlspecialchars($shot['title']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="screenshot-gallery">
                                <div class="gallery-item">
                                    <div class="gallery-item-img"><i class="fa fa-graduation-cap"></i></div>
                                    <div class="gallery-item-title">EOS Selection</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- House Loan -->
                <div class="step-container">
                    <div class="step-number">2</div>
                    <div class="step-title">
                        <i class="fa fa-home"></i> <?= __('house_loan') ?>
                    </div>
                    <p class="step-description"><?= __('apply_for_housing_assistance_loan') ?></p>
                    
                    <div class="step-details">
                        <div class="detail-point"><strong><?= __('eligibility') ?>:</strong> <?= __('active_employees_with_minimum_tenure') ?></div>
                        <div class="detail-point"><strong><?= __('requirement') ?>:</strong> <?= __('must_have_real_estate_contract_ready') ?></div>
                        <div class="detail-point"><strong><?= __('step_1') ?>:</strong> <?= __('go_to_profile_page') ?></div>
                        <div class="detail-point"><strong><?= __('step_2') ?>:</strong> <?= __('click_more_button') ?></div>
                        <div class="detail-point"><strong><?= __('step_3') ?>:</strong> <?= __('select_apply_loan') ?></div>
                        <div class="detail-point"><strong><?= __('step_4') ?>:</strong> <?= __('choose_house_loan_type') ?></div>
                        <div class="detail-point"><strong><?= __('step_5') ?>:</strong> <?= __('enter_property_details') ?></div>
                        <div class="detail-point"><strong><?= __('step_6') ?>:</strong> <?= __('upload_real_estate_contract') ?></div>
                        <div class="detail-point"><strong><?= __('step_7') ?>:</strong> <?= __('specify_loan_amount_and_tenure') ?></div>
                        <div class="detail-point"><strong><?= __('step_8') ?>:</strong> <?= __('submit_for_approval') ?></div>
                    </div>

                    <div class="success-box">
                        <strong><?= __('tip') ?>:</strong> <?= __('house_loan_requires_1_year_wait_after_previous_loan') ?>
                    </div>

                    <!-- Screenshots Section -->
                    <div class="screenshot-wrapper">
                        <h6 style="color: var(--primary); margin-bottom: 15px;"><i class="fa fa-image"></i> <?= __('screenshot') ?? 'Screenshots' ?></h6>
                        <?php 
                        $house_shots = get_section_screenshots('loans', 2);
                        if (!empty($house_shots)): ?>
                            <div class="screenshot-gallery">
                                <?php foreach ($house_shots as $shot): ?>
                                    <div class="gallery-item" onclick="openZoomModal('<?= htmlspecialchars($shot['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($shot['title'], ENT_QUOTES) ?>')">
                                        <div class="gallery-item-img" style="height: auto; background: #f9f9f9; overflow: hidden;">
                                            <img src="<?= htmlspecialchars($shot['file_path']) ?>" alt="<?= htmlspecialchars($shot['title']) ?>" style="width: 100%; height: auto; min-height: 150px; object-fit: cover; cursor: pointer;" onerror="this.style.display='none'; this.parentElement.innerHTML += '<i class=\"fa fa-image\" style=\"font-size: 40px; color: #999; margin: 40px;\"></i>';">
                                        </div>
                                        <div class="gallery-item-title"><?= htmlspecialchars($shot['title']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="screenshot-gallery">
                                <div class="gallery-item">
                                    <div class="gallery-item-img"><i class="fa fa-home"></i></div>
                                    <div class="gallery-item-title">House Loan Type</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Advance Salary -->
                <div class="step-container">
                    <div class="step-number">3</div>
                    <div class="step-title">
                        <i class="fa fa-fast-forward"></i> <?= __('advance_salary_loan') ?>
                    </div>
                    <p class="step-description"><?= __('get_advance_payment_on_your_salary') ?></p>
                    
                    <div class="step-details">
                        <div class="detail-point"><strong><?= __('eligibility') ?>:</strong> <?= __('all_active_employees') ?></div>
                        <div class="detail-point"><strong><?= __('limit') ?>:</strong> <?= __('advance_limited_to_percentage_of_salary') ?></div>
                        <div class="detail-point"><strong><?= __('step_1') ?>:</strong> <?= __('go_to_profile_page') ?></div>
                        <div class="detail-point"><strong><?= __('step_2') ?>:</strong> <?= __('click_more_button') ?></div>
                        <div class="detail-point"><strong><?= __('step_3') ?>:</strong> <?= __('select_apply_loan') ?></div>
                        <div class="detail-point"><strong><?= __('step_4') ?>:</strong> <?= __('choose_advance_salary_option') ?></div>
                        <div class="detail-point"><strong><?= __('step_5') ?>:</strong> <?= __('enter_advance_amount_needed') ?></div>
                        <div class="detail-point"><strong><?= __('step_6') ?>:</strong> <?= __('select_repayment_months') ?></div>
                        <div class="detail-point"><strong><?= __('step_7') ?>:</strong> <?= __('submit_for_approval') ?></div>
                    </div>

                    <div class="info-box">
                        <strong><?= __('important') ?>:</strong> <?= __('advance_deducted_from_salary_automatically') ?>
                    </div>

                    <!-- Screenshots Section -->
                    <div class="screenshot-wrapper">
                        <h6 style="color: var(--primary); margin-bottom: 15px;"><i class="fa fa-image"></i> <?= __('screenshot') ?? 'Screenshots' ?></h6>
                        <?php 
                        $advance_shots = get_section_screenshots('loans', 3);
                        if (!empty($advance_shots)): ?>
                            <div class="screenshot-gallery">
                                <?php foreach ($advance_shots as $shot): ?>
                                    <div class="gallery-item" onclick="openZoomModal('<?= htmlspecialchars($shot['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($shot['title'], ENT_QUOTES) ?>')">
                                        <div class="gallery-item-img" style="height: auto; background: #f9f9f9; overflow: hidden;">
                                            <img src="<?= htmlspecialchars($shot['file_path']) ?>" alt="<?= htmlspecialchars($shot['title']) ?>" style="width: 100%; height: auto; min-height: 150px; object-fit: cover; cursor: pointer;" onerror="this.style.display='none'; this.parentElement.innerHTML += '<i class=\"fa fa-image\" style=\"font-size: 40px; color: #999; margin: 40px;\"></i>';">
                                        </div>
                                        <div class="gallery-item-title"><?= htmlspecialchars($shot['title']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="screenshot-gallery">
                                <div class="gallery-item">
                                    <div class="gallery-item-img"><i class="fa fa-fast-forward"></i></div>
                                    <div class="gallery-item-title">Advance Selection</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Loan Payment Methods -->
                <div class="step-container" style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border-left-color: var(--info);">
                    <h5 style="margin-bottom: 15px; color: var(--primary);">
                        <i class="fa fa-info-circle"></i> <?= __('loan_payment_methods') ?>
                    </h5>
                    <div class="feature-grid">
                        <div class="feature-card">
                            <i class="fa fa-university"></i>
                            <h5><?= __('automatic_payroll_deduction') ?></h5>
                            <p><?= __('loan_amount_deducted_monthly') ?></p>
                        </div>
                        <div class="feature-card">
                            <i class="fa fa-hand-holding-usd"></i>
                            <h5><?= __('manual_payment') ?></h5>
                            <p><?= __('pay_loan_manually_anytime') ?></p>
                        </div>
                        <div class="feature-card">
                            <i class="fa fa-chart-line"></i>
                            <h5><?= __('track_progress') ?></h5>
                            <p><?= __('view_loan_balance_anytime') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EXCUSE LEAVE TAB -->
            <div id="excuse" class="guide-section">
                <h2 class="guide-title"><i class="fa fa-calendar-times"></i> <?= __('excuse_leave') ?></h2>

                <div class="step-container">
                    <div class="step-number">1</div>
                    <div class="step-title">
                        <i class="fa fa-question-circle"></i> <?= __('what_is_excuse_leave') ?>
                    </div>
                    <p class="step-description"><?= __('excuse_leave_is_unplanned_absences') ?></p>
                </div>

                <div class="step-container">
                    <div class="step-number">2</div>
                    <div class="step-title">
                        <i class="fa fa-tasks"></i> <?= __('how_to_apply_excuse_leave') ?>
                    </div>
                    
                    <div class="step-details">
                        <div class="detail-point"><strong><?= __('step_1') ?>:</strong> <?= __('go_to_profile_page') ?></div>
                        <div class="detail-point"><strong><?= __('step_2') ?>:</strong> <?= __('click_more_button_in_header') ?></div>
                        <div class="detail-point"><strong><?= __('step_3') ?>:</strong> <?= __('select_excuse_leave_option') ?></div>
                        <div class="detail-point"><strong><?= __('step_4') ?>:</strong> <?= __('enter_leave_date') ?></div>
                        <div class="detail-point"><strong><?= __('step_5') ?>:</strong> <?= __('provide_reason_for_absence') ?></div>
                        <div class="detail-point"><strong><?= __('step_6') ?>:</strong> <?= __('provide_supporting_documents_if_any') ?></div>
                        <div class="detail-point"><strong><?= __('step_7') ?>:</strong> <?= __('click_submit_button') ?></div>
                    </div>

                    <div class="info-box">
                        <strong><?= __('note') ?>:</strong> <?= __('excuse_leave_subject_to_manager_discretion') ?>
                    </div>

                    <div class="warning-box">
                        <strong><?= __('important') ?>:</strong> <?= __('submit_excuse_leave_within_specified_days') ?>
                    </div>
                </div>

                <div class="step-container">
                    <div class="step-number">3</div>
                    <div class="step-title">
                        <i class="fa fa-check-double"></i> <?= __('approval_process') ?>
                    </div>
                    <p class="step-description"><?= __('after_submission_follows_approval_chain') ?></p>
                    
                    <div class="step-details">
                        <div class="detail-point">• <?= __('submitted_to_department_head') ?></div>
                        <div class="detail-point">• <?= __('reviewed_by_manager') ?></div>
                        <div class="detail-point">• <?= __('approved_or_rejected_by_hr') ?></div>
                        <div class="detail-point">• <?= __('status_updated_in_system') ?></div>
                    </div>

                    <div class="success-box">
                        <strong><?= __('tip') ?>:</strong> <?= __('check_application_status_regularly') ?>
                    </div>

                    <!-- Screenshots Section -->
                    <div class="screenshot-wrapper">
                        <h6 style="color: var(--primary); margin-bottom: 15px;"><i class="fa fa-image"></i> <?= __('screenshot') ?? 'Screenshots' ?></h6>
                        <?php 
                        $excuse_shots = get_section_screenshots('excuse', 3);
                        if (!empty($excuse_shots)): ?>
                            <div class="screenshot-gallery">
                                <?php foreach ($excuse_shots as $shot): ?>
                                    <div class="gallery-item" onclick="openZoomModal('<?= htmlspecialchars($shot['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($shot['title'], ENT_QUOTES) ?>')">
                                        <div class="gallery-item-img" style="height: auto; background: #f9f9f9; overflow: hidden;">
                                            <img src="<?= htmlspecialchars($shot['file_path']) ?>" alt="<?= htmlspecialchars($shot['title']) ?>" style="width: 100%; height: auto; min-height: 150px; object-fit: cover; cursor: pointer;" onerror="this.style.display='none'; this.parentElement.innerHTML += '<i class=\"fa fa-image\" style=\"font-size: 40px; color: #999; margin: 40px;\"></i>';">
                                        </div>
                                        <div class="gallery-item-title"><?= htmlspecialchars($shot['title']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="screenshot-gallery">
                                <div class="gallery-item">
                                    <div class="gallery-item-img"><i class="fa fa-calendar-times"></i></div>
                                    <div class="gallery-item-title">Excuse Selection</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RESIGNATION TAB -->
            <div id="resignation" class="guide-section">
                <h2 class="guide-title"><i class="fa fa-sign-out"></i> <?= __('resignation') ?></h2>

                <div class="warning-box" style="border-left-color: var(--danger);">
                    <strong><?= __('important') ?>:</strong> <?= __('resignation_is_irreversible_process') ?>
                </div>

                <div class="step-container">
                    <div class="step-number">1</div>
                    <div class="step-title">
                        <i class="fa fa-file-text"></i> <?= __('initiate_resignation_request') ?>
                    </div>
                    
                    <div class="step-details">
                        <div class="detail-point"><strong><?= __('step_1') ?>:</strong> <?= __('go_to_profile_page') ?></div>
                        <div class="detail-point"><strong><?= __('step_2') ?>:</strong> <?= __('click_more_button') ?></div>
                        <div class="detail-point"><strong><?= __('step_3') ?>:</strong> <?= __('select_apply_resignation') ?></div>
                        <div class="detail-point"><strong><?= __('step_4') ?>:</strong> <?= __('resignation_form_appears') ?></div>
                        <div class="detail-point"><strong><?= __('step_5') ?>:</strong> <?= __('fill_resignation_reason') ?></div>
                        <div class="detail-point"><strong><?= __('step_6') ?>:</strong> <?= __('select_last_working_day') ?></div>
                        <div class="detail-point"><strong><?= __('step_7') ?>:</strong> <?= __('confirm_submission') ?></div>
                    </div>
                </div>

                <div class="step-container">
                    <div class="step-number">2</div>
                    <div class="step-title">
                        <i class="fa fa-comments"></i> <?= __('exit_interview') ?>
                    </div>
                    <p class="step-description"><?= __('complete_exit_interview_form') ?></p>
                    
                    <div class="step-details">
                        <div class="detail-point"><strong><?= __('step_1') ?>:</strong> <?= __('after_resignation_submit_exit_interview') ?></div>
                        <div class="detail-point"><strong><?= __('step_2') ?>:</strong> <?= __('answer_feedback_questions') ?></div>
                        <div class="detail-point"><strong><?= __('step_3') ?>:</strong> <?= __('provide_suggestions') ?></div>
                        <div class="detail-point"><strong><?= __('step_4') ?>:</strong> <?= __('fill_contact_information') ?></div>
                        <div class="detail-point"><strong><?= __('step_5') ?>:</strong> <?= __('submit_interview_form') ?></div>
                    </div>

                    <div class="info-box">
                        <strong><?= __('note') ?>:</strong> <?= __('exit_interview_help_company_improve') ?>
                    </div>
                </div>

                <div class="step-container">
                    <div class="step-number">3</div>
                    <div class="step-title">
                        <i class="fa fa-cog"></i> <?= __('post_resignation_process') ?>
                    </div>
                    <p class="step-description"><?= __('what_happens_after_resignation') ?></p>
                    
                    <div class="step-details">
                        <div class="detail-point">• <?= __('resignation_sent_to_hr_for_approval') ?></div>
                        <div class="detail-point">• <?= __('exit_clearance_initiated') ?></div>
                        <div class="detail-point">• <?= __('final_settlement_calculated') ?></div>
                        <div class="detail-point">• <?= __('benefits_and_dues_prepared') ?></div>
                        <div class="detail-point">• <?= __('final_payment_processed') ?></div>
                    </div>

                    <div class="success-box">
                        <strong><?= __('tip') ?>:</strong> <?= __('settlement_includes_eos_unpaid_vacation_benefits') ?>
                    </div>
                </div>
            </div>

            <!-- REJOIN TAB -->
            <div id="rejoin" class="guide-section">
                <h2 class="guide-title"><i class="fa fa-plane-arrival"></i> <?= __('rejoin_request') ?></h2>

                <div class="step-container">
                    <div class="step-number">1</div>
                    <div class="step-title">
                        <i class="fa fa-info-circle"></i> <?= __('what_is_rejoin_request') ?>
                    </div>
                    <p class="step-description"><?= __('rejoin_request_notify_return_from_vacation') ?></p>
                    
                    <div class="step-details">
                        <div class="detail-point"><?= __('used_when_returning_from_approved_vacation') ?></div>
                        <div class="detail-point"><?= __('confirms_your_return_to_work') ?></div>
                        <div class="detail-point"><?= __('updates_your_status_in_system') ?></div>
                    </div>
                </div>

                <div class="step-container">
                    <div class="step-number">2</div>
                    <div class="step-title">
                        <i class="fa fa-tasks"></i> <?= __('how_to_submit_rejoin_request') ?>
                    </div>
                    
                    <div class="step-details">
                        <div class="detail-point"><strong><?= __('step_1') ?>:</strong> <?= __('go_to_profile_page') ?></div>
                        <div class="detail-point"><strong><?= __('step_2') ?>:</strong> <?= __('click_more_button_in_header') ?></div>
                        <div class="detail-point"><strong><?= __('step_3') ?>:</strong> <?= __('look_for_rejoin_request_option') ?></div>
                        <div class="detail-point"><strong><?= __('note') ?>:</strong> <?= __('rejoin_option_appears_after_vacation_return_date') ?></div>
                        <div class="detail-point"><strong><?= __('step_4') ?>:</strong> <?= __('click_rejoin_request_button') ?></div>
                        <div class="detail-point"><strong><?= __('step_5') ?>:</strong> <?= __('confirm_your_return_date') ?></div>
                        <div class="detail-point"><strong><?= __('step_6') ?>:</strong> <?= __('submit_the_request') ?></div>
                    </div>

                    <div class="info-box">
                        <strong><?= __('note') ?>:</strong> <?= __('rejoin_request_only_available_after_vacation_ends') ?>
                    </div>
                </div>

                <div class="step-container">
                    <div class="step-number">3</div>
                    <div class="step-title">
                        <i class="fa fa-check-circle"></i> <?= __('what_happens_after_rejoin') ?>
                    </div>
                    
                    <div class="step-details">
                        <div class="detail-point">• <?= __('system_updates_your_status') ?></div>
                        <div class="detail-point">• <?= __('vacation_marked_as_completed') ?></div>
                        <div class="detail-point">• <?= __('you_are_back_in_active_payroll') ?></div>
                        <div class="detail-point">• <?= __('hr_receives_notification') ?></div>
                    </div>

                    <div class="success-box">
                        <strong><?= __('tip') ?>:</strong> <?= __('submit_rejoin_on_actual_return_date') ?>
                    </div>
                </div>

                <div class="step-container" style="background: linear-gradient(135deg, #f3e5f5 0%, #e1f5fe 100%); border-left-color: var(--info);">
                    <h5 style="margin-bottom: 15px; color: var(--primary);">
                        <i class="fa fa-lightbulb"></i> <?= __('quick_tips') ?>
                    </h5>
                    <ul class="list-styled">
                        <li><?= __('submit_rejoin_within_24_hours_of_return') ?></li>
                        <li><?= __('ensure_correct_return_date') ?></li>
                        <li><?= __('check_status_after_submission') ?></li>
                        <li><?= __('contact_hr_if_issues') ?></li>
                    </ul>

                    <!-- Screenshots Section -->
                    <div class="screenshot-wrapper" style="margin-top: 20px;">
                        <h6 style="color: var(--primary); margin-bottom: 15px;"><i class="fa fa-image"></i> <?= __('screenshot') ?? 'Screenshots' ?></h6>
                        <?php 
                        $rejoin_shots = get_section_screenshots('rejoin', 3);
                        if (!empty($rejoin_shots)): ?>
                            <div class="screenshot-gallery">
                                <?php foreach ($rejoin_shots as $shot): ?>
                                    <div class="gallery-item" onclick="openZoomModal('<?= htmlspecialchars($shot['file_path'], ENT_QUOTES) ?>', '<?= htmlspecialchars($shot['title'], ENT_QUOTES) ?>')">
                                        <div class="gallery-item-img" style="height: auto; background: #f9f9f9; overflow: hidden;">
                                            <img src="<?= htmlspecialchars($shot['file_path']) ?>" alt="<?= htmlspecialchars($shot['title']) ?>" style="width: 100%; height: auto; min-height: 150px; object-fit: cover; cursor: pointer;" onerror="this.style.display='none'; this.parentElement.innerHTML += '<i class=\"fa fa-image\" style=\"font-size: 40px; color: #999; margin: 40px;\"></i>';">
                                        </div>
                                        <div class="gallery-item-title"><?= htmlspecialchars($shot['title']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="screenshot-gallery">
                                <div class="gallery-item">
                                    <div class="gallery-item-img"><i class="fa fa-plane-arrival"></i></div>
                                    <div class="gallery-item-title">Rejoin Option</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Zoom Modal -->
    <div id="zoomModal" class="zoom-modal" style="display: none;">
        <div class="zoom-modal-content">
            <span class="zoom-close" onclick="closeZoomModal()">&times;</span>
            
            <!-- Left Arrow -->
            <button class="zoom-nav-btn zoom-nav-left" onclick="navigateImage(-1)" title="Previous Image (←)">
                <i class="fa fa-chevron-left"></i>
            </button>
            
            <!-- Right Arrow -->
            <button class="zoom-nav-btn zoom-nav-right" onclick="navigateImage(1)" title="Next Image (→)">
                <i class="fa fa-chevron-right"></i>
            </button>
            
            <div class="zoom-toolbar">
                <button class="zoom-btn" onclick="zoomIn()" title="Zoom In (Ctrl++)">
                    <i class="fa fa-plus"></i>
                </button>
                <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out (Ctrl+-)">
                    <i class="fa fa-minus"></i>
                </button>
                <button class="zoom-btn" onclick="resetZoom()" title="Reset Zoom (Ctrl+0)">
                    <i class="fa fa-arrows-alt"></i>
                </button>
                <span class="zoom-level" id="zoomLevel">100%</span>
                <button class="zoom-btn" onclick="downloadImage()" title="Download">
                    <i class="fa fa-download"></i>
                </button>
                <span class="zoom-level" id="imageCounter" style="margin-left: 20px;"></span>
            </div>
            <div class="zoom-container" id="zoomContainer">
                <img id="zoomImage" src="" alt="Zoomed image">
            </div>
            <div class="zoom-title" id="zoomTitle"></div>
        </div>
    </div>

    <script>
        // Global variables for image navigation
        let currentImageIndex = 0;
        let currentImageSet = [];
        
        function showTab(tabName) {
            // Hide all sections
            const sections = document.querySelectorAll('.guide-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });

            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.nav-link');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected section
            document.getElementById(tabName).classList.add('active');

            // Add active class to clicked tab
            event.target.closest('.nav-link').classList.add('active');

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Zoom Functionality
        let currentZoom = 100;
        const minZoom = 50;
        const maxZoom = 300;
        const zoomStep = 10;

        function openZoomModal(imagePath, title) {
            // Get all images in the current gallery
            const clickedElement = event.target.closest('.gallery-item');
            const gallery = clickedElement ? clickedElement.closest('.screenshot-gallery') : null;
            
            if (gallery) {
                const galleryItems = gallery.querySelectorAll('.gallery-item');
                currentImageSet = Array.from(galleryItems).map(item => {
                    const img = item.querySelector('img');
                    const titleEl = item.querySelector('.gallery-item-title');
                    return {
                        path: img ? img.src : imagePath,
                        title: titleEl ? titleEl.textContent : title
                    };
                }).filter(item => item.path && !item.path.includes('undefined'));
                
                // Find current index
                currentImageIndex = currentImageSet.findIndex(item => item.path === imagePath);
                if (currentImageIndex === -1) currentImageIndex = 0;
            } else {
                // Single image mode
                currentImageSet = [{ path: imagePath, title: title }];
                currentImageIndex = 0;
            }
            
            const modal = document.getElementById('zoomModal');
            const zoomImage = document.getElementById('zoomImage');
            const zoomTitle = document.getElementById('zoomTitle');
            
            zoomImage.src = imagePath;
            zoomTitle.textContent = title || 'Screenshot';
            currentZoom = 100;
            updateZoomLevel();
            updateNavigationButtons();
            updateImageCounter();
            modal.style.display = 'flex';
            
            // Add keyboard listeners
            document.addEventListener('keydown', handleZoomKeys);
        }

        function navigateImage(direction) {
            if (currentImageSet.length <= 1) return;
            
            currentImageIndex += direction;
            
            // Loop around
            if (currentImageIndex < 0) {
                currentImageIndex = currentImageSet.length - 1;
            } else if (currentImageIndex >= currentImageSet.length) {
                currentImageIndex = 0;
            }
            
            const currentImage = currentImageSet[currentImageIndex];
            const zoomImage = document.getElementById('zoomImage');
            const zoomTitle = document.getElementById('zoomTitle');
            
            zoomImage.src = currentImage.path;
            zoomTitle.textContent = currentImage.title;
            currentZoom = 100;
            updateZoomLevel();
            updateNavigationButtons();
            updateImageCounter();
        }

        function updateNavigationButtons() {
            const leftBtn = document.querySelector('.zoom-nav-left');
            const rightBtn = document.querySelector('.zoom-nav-right');
            
            if (currentImageSet.length <= 1) {
                leftBtn.style.display = 'none';
                rightBtn.style.display = 'none';
            } else {
                leftBtn.style.display = 'flex';
                rightBtn.style.display = 'flex';
            }
        }

        function updateImageCounter() {
            const counter = document.getElementById('imageCounter');
            if (currentImageSet.length > 1) {
                counter.textContent = `${currentImageIndex + 1} / ${currentImageSet.length}`;
                counter.style.display = 'inline';
            } else {
                counter.style.display = 'none';
            }
        }

        function closeZoomModal() {
            const modal = document.getElementById('zoomModal');
            modal.style.display = 'none';
            document.removeEventListener('keydown', handleZoomKeys);
            currentImageSet = [];
            currentImageIndex = 0;
        }

        function zoomIn() {
            if (currentZoom < maxZoom) {
                currentZoom = Math.min(currentZoom + zoomStep, maxZoom);
                updateZoomLevel();
            }
        }

        function zoomOut() {
            if (currentZoom > minZoom) {
                currentZoom = Math.max(currentZoom - zoomStep, minZoom);
                updateZoomLevel();
            }
        }

        function resetZoom() {
            currentZoom = 100;
            updateZoomLevel();
        }

        function updateZoomLevel() {
            const zoomImage = document.getElementById('zoomImage');
            const zoomLevel = document.getElementById('zoomLevel');
            
            zoomImage.style.transform = `scale(${currentZoom / 100})`;
            zoomLevel.textContent = currentZoom + '%';
        }

        function downloadImage() {
            const zoomImage = document.getElementById('zoomImage');
            const zoomTitle = document.getElementById('zoomTitle');
            const link = document.createElement('a');
            link.href = zoomImage.src;
            link.download = zoomTitle.textContent.replace(/\s+/g, '_') + '.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function handleZoomKeys(event) {
            if (!document.getElementById('zoomModal').style.display || document.getElementById('zoomModal').style.display === 'none') {
                return;
            }
            
            switch(event.key) {
                case '+':
                case '=':
                    event.preventDefault();
                    zoomIn();
                    break;
                case '-':
                case '_':
                    event.preventDefault();
                    zoomOut();
                    break;
                case '0':
                    if (event.ctrlKey || event.metaKey) {
                        event.preventDefault();
                        resetZoom();
                    }
                    break;
                case 'ArrowLeft':
                    event.preventDefault();
                    navigateImage(-1);
                    break;
                case 'ArrowRight':
                    event.preventDefault();
                    navigateImage(1);
                    break;
                case 'Escape':
                    closeZoomModal();
                    break;
            }
        }

        // Close modal on outside click
        document.getElementById('zoomModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeZoomModal();
            }
        });
    </script>
</body>
</html>
<?php
