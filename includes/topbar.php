<?php
// File: includes/topbar.php (Updated)
// This file contains the top navigation bar with the new language switcher
// and the structure for the redesigned notification dropdown.
// Added: Server-side unread notification count initialization.

/**************************************************************************************************
 * MODIFICATION SUMMARY
 *
 * 1.  **Language Switcher Integrated**: Dynamic language switcher button added.
 * 2.  **Full Translation**: Static text elements use `__()` for multilingual support.
 * 3.  **Notification Dropdown Added**: Includes bell icon, badge (`#notification-badge`),
 * and dropdown menu (`#notification-dropdown-menu`).
 * 4.  **Redesigned Notification Structure**: The `.slimscroll` div inside the notification
 * dropdown now contains only the placeholder (`#notification-placeholder`), ready for
 * `notifications.js` to populate with styled items.
 * 5.  **Enable Notifications Link**: Link added in user dropdown (`#enable-notifications-link`).
 *
 **************************************************************************************************/

// Output session configuration JavaScript variables from session_check.php
// This must run early so jquery.app.js can access the window variables
include(__DIR__ . '/session_config_js.php');
?>
<div class="topbar">
    <nav class="navbar-custom">
        <ul class="list-unstyled topbar-right-menu tbx float-right mb-0">

            <li class="tbx-search hide-phone d-none d-sm-block">
                <form action="search.php" method="get" id="tbxSearchForm">
                    <input type="text" name="search" placeholder="<?=__('search'); ?>" class="tbx-search-input" required>
                    <button type="submit" class="tbx-btn" id="tbxSearchBtn" aria-label="<?=__('search'); ?>"><i class="fa-duotone fa-magnifying-glass"></i></button>
                </form>
            </li>

            <!-- =================================== -->
            <!-- == Language Switcher Button      == -->
            <!-- =================================== -->
            <li class="notification-list">
                <?php
                    // Determine target language + button text
                    $switch_to_lang = ($current_lang == 'en') ? 'ar' : 'en';
                    $button_text    = ($current_lang == 'en') ? 'العربية' : 'English';
                    // Preserve all existing query params
                    $query_params = [];
                    if (!empty($_SERVER['QUERY_STRING'])) {
                        parse_str($_SERVER['QUERY_STRING'], $query_params);
                    }
                    // Set change_lang param (instead of lang)
                    $query_params['change_lang'] = $switch_to_lang;
                    // Build new URL
                    $base_path = strtok($_SERVER['REQUEST_URI'], '?');
                    $new_query_string = http_build_query($query_params);
                    $switch_url = htmlspecialchars($base_path . '?' . $new_query_string);
                ?>
                <a href="<?= $switch_url ?>" class="tbx-btn tbx-lang" title="<?= $button_text ?>">
                    <i class="fa-duotone fa-language"></i><span><?= $button_text ?></span>
                </a>
            </li>

            <!-- =================================== -->
            <!-- == Notification Dropdown         == -->
            <!-- =================================== -->
            <?php
                // Prepare unread notification count (server-side fallback / initial state)
                $unread_notifications = [];
                $unread_count = 0;
                if (function_exists('get_unread_notifications') && isset($empid) && $empid) {
                    $unread_notifications = get_unread_notifications($conDB, $empid);
                    $unread_count = is_array($unread_notifications) ? count($unread_notifications) : 0;
                }
                $badge_style = ($unread_count > 0) ? '' : 'display: none;';
            ?>
            <li class="dropdown notification-list">
                <a class="tbx-btn dropdown-toggle arrow-none" data-toggle="dropdown" href="#" role="button"
                   aria-haspopup="false" aria-expanded="false">
                    <i class="fa-duotone fa-bell noti-icon"></i>
                    <!-- Notification Badge -->
                    <span class="badge badge-danger badge-pill noti-icon-badge" id="notification-badge" style="<?= $badge_style ?>"><?= (int)$unread_count ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated dropdown-lg" id="notification-dropdown-menu">
                    <!-- item-->
                    <div class="dropdown-item noti-title">
                        <h6 class="m-0">
                            <span class="float-right"><a href="#" class="text-dark" id="clear-all-notifications">
                                <small><?= (function_exists('__') ? __('mark_all_read') : 'Mark all read') ?></small></a>
                            </span><?=__('notifications'); ?>
                        </h6>
                    </div>

                    <div class="slimscroll" style="max-height: 230px;">

                        <!-- Placeholder for when there are no notifications (initial server state) -->
                        <div class="text-center text-muted p-3" id="notification-placeholder" style="<?= ($unread_count === 0 ? '' : 'display: none;') ?>">
                            <?=__('no_new_notifications'); ?>
                        </div>

                        <!-- Notification items are dynamically inserted by notifications.js.
                             Server-side pre-render (optional): -->
                        <?php if ($unread_count > 0): ?>
                            <?php foreach ($unread_notifications as $notif): ?>
                                <a href="<?= $notif['url'] ?>" class="dropdown-item notify-item" data-id="<?= (int)$notif['id'] ?>">
                                    <div class="notify-icon bg-primary"> <i class="fa fa-info"></i> </div>
                                    <p class="notify-details">
                                        <strong><?= $notif['title'] ?></strong>
                                        <small class="text-muted mb-0 d-block" style="white-space: normal;"><?= $notif['message'] ?></small>
                                    </p>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>

                    <!-- All-->
                    <a href="all_notifications.php" class="dropdown-item text-center text-primary notify-item notify-all border-top pt-2">
                        <?=__('view_all'); ?>
                    </a>
                </div>
            </li>


            <li class="dropdown notification-list">
                <a class="tbx-user dropdown-toggle nav-user" data-toggle="dropdown" href="#" role="button"
                   aria-haspopup="false" aria-expanded="false">
                    <img src="<?=$avatar ?>" alt="<?=$fname ?>" class="rounded-circle">
                    <span class="tbx-user-name"><?=$userwel ?></span><i class="fa-solid fa-chevron-down tbx-chev"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated profile-dropdown">
                    <!-- item-->
                    <div class="dropdown-item noti-title">
                        <h6 class="text-overflow m-0"><?=__('welcome_message'); ?></h6>
                    </div>

                    <!-- item-->
                    <a href="profile.php" class="dropdown-item notify-item">
                        <i class="fa fa-user"></i> <span><?=__('my_account'); ?></span>
                    </a>

                    <!-- item-->
                     <?php if($is_system_admin ?? false){ // Added check if variable exists ?>
                    <a href="app_settings.php" target="_blank" id="editAllBtnX" class="dropdown-item notify-item">
                        <i class="fa fa-gear"></i> <span><?=__('settings'); ?></span>
                    </a>
                    <?php } ?>

                    <!-- NEW Enable Notifications Link -->
                    <a href="javascript:void(0);" class="dropdown-item notify-item" id="enable-notifications-link" style="display: none;">
                        <i class="fa fa-bell"></i> <span><?=__('enable_notifications'); ?></span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fa fa-headset"></i> <span><?=__('supporter_option'); ?></span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item signout" data-action="signout">
                        <i class="fa fa-right-from-bracket"></i> <span><?=__('logout_button'); ?></span>
                    </a>

                </div>
            </li>
        </ul>

        <ul class="list-inline menu-left mb-0">
            <li class="float-left">
                <button class="button-menu-mobile open-left disable-btn">
                    <i class="fa fa-bars"></i>
                </button>
            </li>
            <li>
                <div class="page-title-box">
                    <h4 class="page-title"><?= htmlspecialchars(trim((string)get_setting($conDB, 'company_name') . ' ' . __('business_suite')), ENT_QUOTES, 'UTF-8') ?></h4>
                    <ol class="breadcrumb">
                        <?php
                        // Signed-in user's companies / departments / role shown under the page title.
                        // Own company/department first, then any extra ones granted via
                        // admin_login.allowed_companies / allowed_departments (no duplicates).
                        $tbIsAr = (($current_lang ?? 'en') === 'ar');
                        $tbCompanies = [];
                        $tbDepts = [];
                        if (!empty($conDB)) {
                            $tbCompIds = array_values(array_unique(array_filter(array_map('intval', array_merge([(int)($user_company ?? 0)], (array)($allowed_companies ?? []))))));
                            if ($tbCompIds) {
                                $tbIn = implode(',', $tbCompIds);
                                $tbRes = mysqli_query($conDB, "SELECT id, comp_id, comp_name, comp_name_ar FROM companies WHERE comp_id IN ($tbIn) OR id IN ($tbIn)");
                                $tbFound = [];
                                while ($tbRes && ($tbRow = mysqli_fetch_assoc($tbRes))) {
                                    $tbFound[(int)$tbRow['comp_id']] = ($tbIsAr && $tbRow['comp_name_ar'] !== '') ? $tbRow['comp_name_ar'] : $tbRow['comp_name'];
                                }
                                // own company first (matched by comp_id), then the rest
                                $tbOwn = (int)($user_company ?? 0);
                                if (isset($tbFound[$tbOwn])) { $tbCompanies[] = $tbFound[$tbOwn]; unset($tbFound[$tbOwn]); }
                                foreach ($tbFound as $tbName) { $tbCompanies[] = $tbName; }
                            }
                            $tbDeptIds = array_values(array_unique(array_filter(array_map('intval', array_merge([(int)($user_dept ?? 0)], (array)($allowed_departments_array ?? []))))));
                            if ($tbDeptIds) {
                                $tbIn = implode(',', $tbDeptIds);
                                $tbRes = mysqli_query($conDB, "SELECT id, dep_nme, dep_nme_ar FROM department WHERE id IN ($tbIn)");
                                $tbFound = [];
                                while ($tbRes && ($tbRow = mysqli_fetch_assoc($tbRes))) {
                                    $tbFound[(int)$tbRow['id']] = ($tbIsAr && $tbRow['dep_nme_ar'] !== '') ? $tbRow['dep_nme_ar'] : $tbRow['dep_nme'];
                                }
                                $tbOwn = (int)($user_dept ?? 0);
                                if (isset($tbFound[$tbOwn])) { $tbDepts[] = $tbFound[$tbOwn]; unset($tbFound[$tbOwn]); }
                                foreach ($tbFound as $tbName) { $tbDepts[] = $tbName; }
                            }
                        }
                        $tbRoleMap = ['administrator' => 'Administrator', 'hr' => 'Human Resource', 'dephead' => 'Department Head', 'dept_user' => 'Department Head', 'user' => 'Employee'];
                        $tbRoleKey = (string)($user_type ?? '');
                        $tbRole = $tbRoleMap[$tbRoleKey] ?? ucwords(str_replace('_', ' ', $tbRoleKey));
                        if (!empty($is_temp_role_active)) { $tbRole .= ' (Temp)'; }
                        $tbRole = getDisplayName($tbRole);
                        // [icon, names[], text colour, background]; more than 2 names collapse into a "+N" badge with a tooltip.
                        $tbGroups = [
                            ['fa-building', $tbCompanies, '#0e7490', '#cffafe'],
                            ['fa-sitemap', $tbDepts, '#6d28d9', '#ede9fe'],
                            ['fa-user-shield', $tbRole !== '' ? [$tbRole] : [], '#15803d', '#dcfce7'],
                        ];
                        ?>
                        <li class="breadcrumb-item active tb-user-badges">
                            <?php foreach ($tbGroups as $tbG) {
                                $tbShow = array_slice($tbG[1], 0, 2);
                                $tbMore = array_slice($tbG[1], 2);
                                foreach ($tbShow as $tbName) { ?>
                                <span class="badge tb-user-badge" style="color:<?= $tbG[2] ?>;background:<?= $tbG[3] ?>;"><i class="fa-duotone <?= $tbG[0] ?>"></i> <?= htmlspecialchars($tbName, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php }
                                if ($tbMore) { ?>
                                <span class="badge tb-user-badge" style="color:<?= $tbG[2] ?>;background:<?= $tbG[3] ?>;" title="<?= htmlspecialchars(implode(', ', $tbMore), ENT_QUOTES, 'UTF-8') ?>">+<?= count($tbMore) ?></span>
                                <?php }
                            } ?>
                        </li>
                    </ol>
                    <style>
                        .tb-user-badges { min-width:0; max-width:100%; display:flex; flex-wrap:nowrap; align-items:center; gap:4px; white-space:nowrap; }
                        .page-title-box .breadcrumb { flex-wrap:nowrap; }
                        .tb-user-badge { flex:0 0 auto; white-space:nowrap; font-size:12px; font-weight:600; padding:3px 8px; border-radius:20px; line-height:1.3; }
                        .tb-user-badge i { margin-inline-end:3px; }
                        @media (max-width: 1400px) { .tb-user-badge i { display:none; } }
                        .breadcrumb-item.tb-user-badges::before { display:none; }
                    </style>
                </div>
            </li>
        </ul>
                        <style>
                        /* ---- Top bar actions (search / language / notifications / user) ---- */
                        .topbar-right-menu.tbx { display:flex; align-items:center; gap:8px; height:70px; }
                        .topbar-right-menu.tbx > li { float:none; position:relative; }
                        .tbx-btn { position:relative; display:inline-flex; align-items:center; justify-content:center; gap:6px; height:40px; min-width:40px; padding:0 12px; border-radius:20px; background:#eef2f6; color:#475569 !important; font-size:13px; font-weight:600; line-height:1; text-decoration:none !important; border:0; cursor:pointer; transition:background .15s, color .15s, box-shadow .15s; }
                        .tbx-btn:hover { background:#e2e8f0; color:#0f172a !important; }
                        .tbx-btn i { font-size:17px; line-height:1; --fa-primary-color:#0e7490; --fa-secondary-color:#0e7490; --fa-secondary-opacity:.4; }
                        .tbx-lang i { --fa-primary-color:#16a34a; --fa-secondary-color:#16a34a; }
                        .tbx-btn .noti-icon { --fa-primary-color:#f59e0b; --fa-secondary-color:#f59e0b; }
                        .tbx-btn .noti-icon-badge { position:absolute; top:-3px; inset-inline-end:-3px; }
                        /* search: icon that expands into a field */
                        .tbx-search form { display:flex; align-items:center; background:#eef2f6; border-radius:20px; }
                        .tbx-search .tbx-btn { background:transparent; }
                        .tbx-search-input { width:0; padding:0; border:0; outline:0; background:transparent; font-size:13px; color:#0f172a; transition:width .2s ease, padding .2s ease; }
                        .tbx-search.open .tbx-search-input, .tbx-search:focus-within .tbx-search-input { width:190px; padding-inline-start:14px; }
                        /* user chip */
                        .tbx-user { display:inline-flex; align-items:center; gap:8px; height:44px; padding:0 12px 0 4px; border-radius:22px; background:#eef2f6; color:#1e293b !important; text-decoration:none !important; transition:background .15s; }
                        .tbx-user:hover { background:#e2e8f0; }
                        .tbx-user img { width:36px; height:36px; object-fit:cover; border:2px solid #fff; box-shadow:0 0 0 2px #22c55e; }
                        .tbx-user-name { font-size:13px; font-weight:600; max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
                        .tbx-chev { font-size:10px; opacity:.6; }
                        [dir="rtl"] .tbx-user { padding:0 4px 0 12px; }
                        @media (max-width: 991px) { .tbx-user-name, .tbx-lang span, .tbx-chev { display:none; } .tbx-user { padding:0 4px; } .tbx-lang { padding:0; } }
                    </style>
                    <script>
                        (function () {
                            var tb = document.querySelector('.content-page > .topbar'), cp = document.querySelector('.content-page');
                            if (tb && cp && window.ResizeObserver) {
                                new ResizeObserver(function () { cp.style.setProperty('--tb-h', tb.offsetHeight + 'px'); }).observe(tb);
                            }
                            var li = document.querySelector('.tbx-search'), btn = document.getElementById('tbxSearchBtn'), form = document.getElementById('tbxSearchForm');
                            if (!li || !btn || !form) return;
                            var input = form.querySelector('input');
                            btn.addEventListener('click', function (e) {
                                if (!li.classList.contains('open') && !input.value) { e.preventDefault(); li.classList.add('open'); input.focus(); }
                            });
                            input.addEventListener('blur', function () { if (!input.value) li.classList.remove('open'); });
                        })();
                    </script>
    </nav>
</div>