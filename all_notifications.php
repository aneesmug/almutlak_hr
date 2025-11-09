<?php
// File: all_notifications.php
// Displays a list of notifications from the current week for the logged-in user.
// MODIFICATION: Removed automatic mark-as-read on page load. Added JS to mark-as-read on click.

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php'; // Includes helper_functions.php now

// Calculate start and end of the current week (Sunday to Saturday)
$today = new DateTime('now', new DateTimeZone('Asia/Riyadh')); // Use appropriate timezone
$dayOfWeek = $today->format('w'); // 0 (Sunday) to 6 (Saturday)

// Calculate Sunday (start of the week)
$startOfWeek = clone $today;
$startOfWeek->modify('-' . $dayOfWeek . ' days');
$startOfWeek->setTime(0, 0, 0); // Set time to beginning of the day

// Calculate Saturday (end of the week)
$endOfWeek = clone $today;
$daysUntilSaturday = 6 - $dayOfWeek;
$endOfWeek->modify('+' . $daysUntilSaturday . ' days');
$endOfWeek->setTime(23, 59, 59); // Set time to end of the day

$startOfWeekStr = $startOfWeek->format('Y-m-d H:i:s');
$endOfWeekStr = $endOfWeek->format('Y-m-d H:i:s');


// Fetch notifications for the current user *within the current week*, newest first
$notifications = [];
if (isset($empid) && $empid > 0) {
    $emp_id_safe = (int)$empid;

    // Use prepared statement for security
    $sql = "SELECT * FROM `user_notifications`
            WHERE `emp_id` = ?
              AND `created_at` BETWEEN ? AND ?
            ORDER BY `created_at` DESC";

    $stmt = mysqli_prepare($conDB, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iss", $emp_id_safe, $startOfWeekStr, $endOfWeekStr);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $notifications[] = $row;
                }
                 mysqli_free_result($result);
            } else {
                 error_log("Error getting result for notifications: " . mysqli_stmt_error($stmt));
            }
        } else {
             error_log("Error executing notification query: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        // Handle potential prepare error
        error_log("Error preparing notification query: " . mysqli_error($conDB));
    }
}

// --- REMOVED: Automatic mark *fetched* unread notifications as read block ---
/*
if (!empty($notifications) && isset($empid) && $empid > 0) {
    // ... code to call mark_notifications_as_read removed ...
}
*/
// --- END REMOVAL ---


// --- HTML Structure ---
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('all_notifications_page_title') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .notification-item {
            display: block;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            text-decoration: none;
            color: #495057;
            transition: background-color 0.2s ease-in-out;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
            text-decoration: none;
            color: #495057;
        }
        .notification-item.unread {
            background-color: #f0faff; /* Light blue background for unread */
            font-weight: bold;
        }
        .notification-item .notification-title {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        .notification-item .notification-message {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        .notification-item .notification-time {
            font-size: 0.75rem;
            color: #adb5bd;
        }
        /* Style to indicate processing */
        .notification-item.processing {
             opacity: 0.6;
             pointer-events: none; /* Prevent clicking again while processing */
        }
    </style>
     <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
     <?php endif; ?>
     <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
     </script>
</head>

<body class="enlarged" data-keep-enlarged="true">

    <!-- Begin page -->
    <div id="wrapper">

        <!-- ========== Left Sidebar Start ========== -->
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <!-- LOGO -->
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22"></span>
                        <i><img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28"></i>
                    </a>
                </div>
                <!--- Sidemenu -->
                <?php include("./includes/main_menu.php"); ?>
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
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title"><?= __('all_notifications_page_title') ?></h4>
                                <ol class="breadcrumb p-0 m-0">
                                     <li class="breadcrumb-item"><a href="dashboard.php"><?=__('dashboard'); ?></a></li>
                                     <li class="breadcrumb-item active"><?= __('all_notifications_page_title') ?></li>
                                </ol>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                    </div>
                    <!-- end row -->

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="m-t-0 header-title mb-0"><?= __('your_notifications') ?> <small>(<?= __('current_week_only') ?>)</small></h4>
                                        <button type="button" id="mark-all-read-page" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-check-double mr-1"></i><?= function_exists('__') ? __('mark_all_read') : 'Mark all read' ?>
                                        </button>
                                    </div>

                                    <div class="notification-list-wrapper">
                                        <?php if (empty($notifications)): ?>
                                            <p class="text-center text-muted"><?= __('no_notifications_found_this_week') ?></p>
                                        <?php else: ?>
                                            <?php foreach ($notifications as $notification):
                                                // Determine class based on read status
                                                $item_class = ($notification['is_read'] == 0) ? 'notification-item unread' : 'notification-item';
                                                // Format the timestamp
                                                $time_display = ($current_lang == 'ar') ? timeAgoAr($notification['created_at']) : timeAgo($notification['created_at']);
                                            ?>
                                                <!-- ADDED: data-id attribute -->
                                                <a href="<?= htmlspecialchars($notification['url']) ?>"
                                                   class="<?= $item_class ?>"
                                                   data-id="<?= $notification['id'] ?>"
                                                   target="_blank">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                         <h5 class="notification-title"><?= htmlspecialchars($notification['title']) ?></h5>
                                                         <span class="notification-time"><?= $time_display ?></span>
                                                    </div>
                                                    <p class="notification-message"><?= htmlspecialchars($notification['message']) ?></p>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div> <!-- end notification-list-wrapper -->

                                </div> <!-- end card-body -->
                            </div> <!-- end card -->
                        </div> <!-- end col -->
                    </div> <!-- end row -->

                </div> <!-- container -->
            </div> <!-- content -->

            <footer class="footer"><?= $site_footer ?></footer>

        </div>
        <!-- End content-page -->

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
    <script src="assets/js/jquery.app.js"></script> <!-- Includes __() -->

    <!-- Notifications Script (for topbar updates if needed, though not strictly required here now) -->
    <!-- <script src="assets/js/notifications.js"></script> -->

    <!-- NEW: JavaScript to handle marking as read on click -->
    <script>
        $(document).ready(function() {
            // Use event delegation for dynamically added items (though here items are static on load)
            $('.notification-list-wrapper').on('click', 'a.notification-item.unread', function(e) {
                e.preventDefault(); // Prevent the link from navigating immediately

                var $link = $(this);
                var notificationId = $link.data('id');
                var targetUrl = $link.attr('href');
                var openInNewTab = $link.attr('target') === '_blank';

                if (!notificationId) {
                    console.error("Notification ID not found on clicked item.");
                     // Allow navigation if ID is missing for some reason
                     if (openInNewTab) { window.open(targetUrl, '_blank'); } else { window.location.href = targetUrl; }
                    return;
                }

                // Add visual feedback (optional)
                $link.addClass('processing');

                // AJAX call to mark as read
                $.ajax({
                    url: 'includes/mark_notification_read.php', // Path to your PHP script
                    type: 'POST',
                    data: { id: notificationId },
                    dataType: 'json',
                    success: function(response) {
                        if (response && response.status === 'success') {
                            console.log("Notification " + notificationId + " marked as read.");
                            // Remove unread styling immediately
                            $link.removeClass('unread');
                        } else {
                            console.error("Failed to mark notification as read:", response ? response.message : 'Unknown error');
                            // Optionally show an error message to the user
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("AJAX error marking notification read:", textStatus, errorThrown);
                        // Optionally show an error message to the user
                    },
                    complete: function() {
                        // Remove processing feedback
                        $link.removeClass('processing');
                        // Navigate to the target URL after AJAX completes (success or error)
                         if (openInNewTab) {
                             window.open(targetUrl, '_blank');
                         } else {
                             window.location.href = targetUrl;
                         }
                    }
                });
            });

             // Handle clicks on already read items (just navigate)
             $('.notification-list-wrapper').on('click', 'a.notification-item:not(.unread)', function(e) {
                 // Allow default navigation for already read items
                 console.log("Clicked on an already read notification, navigating directly.");
             });

            // Mark all read button (page-level)
            $('#mark-all-read-page').on('click', function(e){
                e.preventDefault();
                var $btn = $(this);
                $btn.prop('disabled', true).text('...');
                $.ajax({
                    url: 'includes/notification.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { action: 'mark_all_read' },
                    success: function(resp){
                        if(resp && resp.status === 'success') {
                            // Remove unread styling from all items and optionally fade
                            $('a.notification-item.unread').removeClass('unread');
                            // Provide lightweight feedback
                            $btn.removeClass('btn-outline-primary').addClass('btn-success').html('<i class="fa fa-check"></i> '+(function_exists('__') ? __('marked_all_read') : 'Marked')); // translation fallback
                            setTimeout(function(){
                                $btn.prop('disabled', false).removeClass('btn-success').addClass('btn-outline-primary').html('<i class="fa fa-check-double mr-1"></i>'+ (function_exists('__') ? __('mark_all_read') : 'Mark all read') );
                            }, 2500);
                        } else {
                            $btn.removeClass('btn-outline-primary').addClass('btn-danger').text((function_exists('__') ? __('error_short') : 'Error'));
                            setTimeout(function(){
                                $btn.prop('disabled', false).removeClass('btn-danger').addClass('btn-outline-primary').html('<i class="fa fa-check-double mr-1"></i>'+ (function_exists('__') ? __('mark_all_read') : 'Mark all read') );
                            }, 2500);
                        }
                    },
                    error: function(){
                        $btn.removeClass('btn-outline-primary').addClass('btn-danger').text((function_exists('__') ? __('error_short') : 'Error'));
                        setTimeout(function(){
                            $btn.prop('disabled', false).removeClass('btn-danger').addClass('btn-outline-primary').html('<i class="fa fa-check-double mr-1"></i>'+ (function_exists('__') ? __('mark_all_read') : 'Mark all read') );
                        }, 2500);
                    }
                });
            });
        });
    </script>
    <!-- END NEW SCRIPT -->

</body>
</html>