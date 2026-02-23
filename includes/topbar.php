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
// This must run early so jquery.app.js?t=<?= time() ?> can access the window variables
include(__DIR__ . '/session_config_js.php');
?>
<div class="topbar">
    <nav class="navbar-custom">
        <ul class="list-unstyled topbar-right-menu float-right mb-0">

            <li class="hide-phone app-search d-none d-sm-block">
                <form action="search.php" method="get">
                    <input type="text" name="search" placeholder="<?=__('search'); ?>" class="form-control" required>
                    <button type="submit"><i class="fa fa-search"></i></button>
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
                <a href="<?= $switch_url ?>" class="nav-link waves-effect">
                    <i class="fad fa-language mr-2 <?=($is_rtl ?? false ? 'duotone-success':'duotone-info')?>"></i><?= $button_text ?>
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
                <a class="nav-link dropdown-toggle arrow-none" data-toggle="dropdown" href="#" role="button"
                   aria-haspopup="false" aria-expanded="false">
                    <i class="fa fa-light fa-bell noti-icon"></i>
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
                <a class="nav-link dropdown-toggle nav-user" data-toggle="dropdown" href="#" role="button"
                   aria-haspopup="false" aria-expanded="false">
                    <img src="<?=$avatar ?>" alt="<?=$fname ?>" class="rounded-circle"> <span class="ml-1"><?=$userwel ?><i class="mdi mdi-chevron-down"></i> </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right dropdown-menu-animated profile-dropdown">
                    <!-- item-->
                    <div class="dropdown-item noti-title">
                        <h6 class="text-overflow m-0"><?=__('welcome_message'); ?></h6>
                    </div>

                    <!-- item-->
                    <a href="profile.php" class="dropdown-item notify-item">
                        <i class="fi-head"></i> <span><?=__('my_account'); ?></span>
                    </a>

                    <!-- item-->
                     <?php if($is_system_admin ?? false){ // Added check if variable exists ?>
                    <a href="app_settings.php" target="_blank" id="editAllBtnX" class="dropdown-item notify-item">
                        <i class="fi-cog"></i> <span><?=__('settings'); ?></span>
                    </a>
                    <?php } ?>

                    <!-- NEW Enable Notifications Link -->
                    <a href="javascript:void(0);" class="dropdown-item notify-item" id="enable-notifications-link" style="display: none;">
                        <i class="fi-bell"></i> <span><?=__('enable_notifications'); ?></span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fi-help"></i> <span><?=__('supporter_option'); ?></span>
                    </a>

                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item signout" data-action="signout">
                        <i class="fi-power"></i> <span><?=__('logout_button'); ?></span>
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
                    <h4 class="page-title"><?=__('human_resource_system'); ?></h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item active"><?=__('welcome_to_al-mutlak_co._admin_panel'); ?></li>
                    </ol>
                </div>
            </li>
        </ul>
    </nav>
</div>