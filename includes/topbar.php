<?php
// File: includes/topbar.php (Updated)
// This file contains the top navigation bar with the new language switcher.

/**************************************************************************************************
 * MODIFICATION SUMMARY
 *
 * 1.  **Language Switcher Integrated**: The dynamic language switcher button has been added to your
 * top navigation bar, allowing users to toggle between English and Arabic.
 * 2.  **Full Translation**: All static text elements in the top bar have been replaced with the `__()`
 * function to make them multilingual. This includes the search placeholder, dropdown menu items
 * (My Account, Settings, etc.), and the page title/breadcrumb.
 * 3.  **Preserved Structure**: Your original layout, including the search bar, user dropdown items,
 * and page title section, has been fully preserved.
 *
 **************************************************************************************************/
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
            <!-- == NEW Language Switcher Button  == -->
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
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fi-head"></i> <span><?=__('my_account'); ?></span>
                    </a>
                    
                    <!-- item-->
                    <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fi-cog"></i> <span><?=__('settings'); ?></span>
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
