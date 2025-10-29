/*
    ======================================
    ==  NOTIFICATION LOGIC (Standalone) ==
    ======================================
    This file handles:
    1. Requesting user permission for notifications.
    2. Polling the server (notification.php) for new notifications.
    3. Updating the UI (bell icon badge and dropdown) in topbar.php.
    4. Showing browser-native pop-up notifications.
    5. Sending AJAX request to mark notification as read on click.
    6. Optional: Stopping polling on critical errors (401, 404, 500).
*/

(function ($) {
    "use strict";

    // --- Enhanced Logging ---
    function logDebug(message, data) {
        console.log("DEBUG: " + message, data !== undefined ? data : '');
    }
    function logWarn(message, data) {
        console.warn("WARN: " + message, data !== undefined ? data : '');
    }
    function logError(message, error) {
        console.error("ERROR: " + message, error !== undefined ? error : '');
    }
    // --- End Enhanced Logging ---

    logDebug("notifications.js loaded successfully.");

    var notificationInterval; // Keep track of the interval timer
    var iconCheckCache = {}; // Cache for icon checks
    var lastNotificationTimestamp = 0; // Track the timestamp of the last processed notification
    var pollingStopped = false; // Flag to indicate if polling has been stopped due to errors


    // Function: Check if the icon file exists
    function checkIconExists(url, callback) {
        if (iconCheckCache[url] !== undefined) {
            callback(iconCheckCache[url]);
            return;
        }
        $.ajax({
            url: url, type: 'HEAD', timeout: 1000,
            success: function() { iconCheckCache[url] = true; callback(true); },
            error: function() { iconCheckCache[url] = false; callback(false); }
        });
    }

    // Function to request notification permission
    function requestNotificationPermission() {
        logDebug("Checking notification permission...");
        if (!("Notification" in window)) {
            logError("This browser does not support desktop notification.");
            return false;
        }

        const currentPermission = Notification.permission;
        logDebug("Current notification permission state: " + currentPermission);

        if (currentPermission === "granted") {
            logDebug("Permission is already granted.");
            return true;
        } else if (currentPermission !== 'denied') {
            logDebug("Requesting permission from user...");
            Notification.requestPermission().then(function (permission) {
                if (permission === "granted") {
                    logDebug("Notification permission granted by user.");
                    $('#enable-notifications-link').fadeOut(); // Hide smoothly
                    pollingStopped = false; // Reset flag if permission granted now
                    startNotificationPolling(); // Start polling immediately
                    return true;
                } else {
                    logWarn("Notification permission denied by user.");
                    return false;
                }
            }).catch(err => {
                 logError("Error requesting notification permission:", err);
                 return false;
            });
        } else {
            logWarn("Notification permission has been permanently denied. Browser settings must be changed to re-enable.");
            return false;
        }
        return false; // Should not be reached if promise handles correctly
    }

    // Function: Mark notification as read via AJAX
    function markNotificationReadAJAX(notificationId) {
        if (!notificationId) return;
        logDebug("Sending AJAX request to mark notification ID as read:", notificationId);
        $.ajax({
            url: 'includes/mark_notification_read.php', // *** Verify this path is correct ***
            type: 'POST',
            data: { id: notificationId },
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    logDebug("Notification marked as read successfully on server:", notificationId);
                    // Update count immediately after successful mark as read
                    updateBadgeCountBasedOnUI();
                } else {
                    logError("Server failed to mark notification as read:", response ? response.message : 'Unknown error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                logError(`AJAX error marking notification read (${notificationId}). Status: ${textStatus}`, errorThrown);
            }
        });
    }

    // Function: Update badge based on current dropdown items
    function updateBadgeCountBasedOnUI() {
         // Count items excluding the placeholder
        var currentCount = $('#notification-dropdown-menu .slimscroll').find('.notify-item:not(#notification-placeholder)').length;
        var $badge = $('#notification-badge');
        logDebug("Updating badge count based on UI. Current items in dropdown:", currentCount);
        if (currentCount > 0) {
            $badge.text(currentCount).fadeIn();
        } else {
            $badge.text('0').fadeOut();
        }
    }

    // Function to fetch and display notifications
    function fetchAndShowNotifications() {
        if (pollingStopped) {
            logWarn("Polling is stopped due to previous critical errors.");
            return; // Don't make the request if stopped
        }

        logDebug("Polling... Making AJAX request to includes/notification.php");

        $.ajax({
            url: 'includes/notification.php', // *** Verify this path is correct ***
            type: 'GET',
            dataType: 'json',
            cache: false, // Prevent browser caching of the API response
            success: function(response) {
                logDebug("Response from includes/notification.php:", response);

                if (response && response.status === 'success' && Array.isArray(response.notifications)) {
                    var notifications = response.notifications;
                    var count = notifications.length;
                    var $badge = $('#notification-badge');
                    var $dropdownMenu = $('#notification-dropdown-menu .slimscroll');
                    var $placeholder = $('#notification-placeholder');

                    // Check if UI elements exist (important sanity check)
                    if ($badge.length === 0) {
                        logError("CRITICAL: Badge element '#notification-badge' not found in the HTML!");
                        stopNotificationPolling(); // Stop if UI element is missing
                        return;
                    }
                    if ($dropdownMenu.length === 0) {
                         logError("CRITICAL: Dropdown container '#notification-dropdown-menu .slimscroll' not found!");
                        // Don't necessarily stop polling, but log it
                    }

                    logDebug(`Found ${count} unread notification(s) from server.`);

                    // Clear previous items from dropdown (excluding placeholder)
                    $dropdownMenu.find('.notify-item:not(#notification-placeholder)').remove();

                    // Update Badge Logic FIRST
                    logDebug("Attempting to update badge count to:", count);
                    if (count > 0) {
                        $badge.text(count).fadeIn(); // Show smoothly
                        $placeholder.hide();
                         logDebug("Badge updated and shown.");
                    } else {
                        $badge.text('0').fadeOut(); // Hide smoothly
                        $placeholder.show();
                        logDebug("Badge updated and hidden.");
                    }

                    // Populate dropdown and trigger browser notifications only if count > 0
                    if (count > 0) {
                        let latestTimestampInBatch = 0; // Track latest timestamp *within this batch*
                        notifications.forEach(function(notification) {
                            // Ensure created_at exists and is valid before parsing
                            let notificationTimestamp = 0;
                            if (notification.created_at) {
                                try {
                                    // Replace dashes for broader browser compatibility with Date parsing
                                    notificationTimestamp = new Date(notification.created_at.replace(/-/g, '/')).getTime();
                                } catch (e) {
                                    logError("Error parsing notification timestamp:", notification.created_at, e);
                                }
                            } else {
                                logWarn("Notification missing created_at timestamp:", notification.id);
                            }


                            // 1. Show browser notification ONLY if it's NEW (timestamp > last known)
                             if (notificationTimestamp > 0 && notificationTimestamp > lastNotificationTimestamp) {
                                logDebug("Calling showNotification() for NEW notification:", notification.id);
                                showNotification(notification);
                                // Update latest timestamp found in this batch
                                if (notificationTimestamp > latestTimestampInBatch) {
                                    latestTimestampInBatch = notificationTimestamp;
                                }
                             } else {
                                 logDebug("Skipping pop-up for already processed or timestamp-less notification ID:", notification.id);
                             }

                            // 2. Populate dropdown
                            var iconHtml = '<div class="notify-icon bg-primary"><i class="mdi mdi-comment-account-outline"></i></div>'; // Default icon
                            var messageSnippet = notification.message ? notification.message.substring(0, 50) + (notification.message.length > 50 ? '...' : '') : '';
                            var titleDisplay = notification.title || __('no_title'); // Use translation for fallback
                            var itemUrl = notification.url || '#'; // Default to '#' if no URL

                            var notificationHtml = `
                                <a href="${itemUrl}" target="_blank" class="dropdown-item notify-item" style="margin: 10px !important" data-id="${notification.id}">
                                    ${iconHtml}
                                    <p class="notify-details">${titleDisplay}
                                        <small class="text-muted">${messageSnippet}</small>
                                    </p>
                                </a>`;
                            $dropdownMenu.prepend(notificationHtml); // Add new items to the top
                        });

                        // Update the global last timestamp *only if* new notifications were found in this batch
                        if (latestTimestampInBatch > lastNotificationTimestamp) {
                            lastNotificationTimestamp = latestTimestampInBatch;
                             logDebug("Updated lastNotificationTimestamp to:", new Date(lastNotificationTimestamp));
                        }

                        logDebug("Dropdown populated.");
                    } else {
                        // lastNotificationTimestamp = 0; // Reset if no notifications? Maybe not, keep last known.
                        logDebug("Dropdown cleared, placeholder shown.");
                    }

                } else if (response && response.status === 'error') {
                     logError("Error message from includes/notification.php:", response.message);
                     if(response.message === 'User not authenticated.') {
                        logError("Polling failed: User session seems invalid. Stopping polling.");
                        stopNotificationPolling(); // Stop on authentication error
                     }
                } else {
                    logError("Received invalid response structure from includes/notification.php", response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                const requestedUrl = this.url;
                logError(`AJAX error fetching notifications from '${requestedUrl}'. Status: ${textStatus}`, errorThrown);
                // logError("Full XHR Response:", jqXHR); // Uncomment for deep debugging

                 // *** Stop polling on critical errors ***
                 if (jqXHR.status === 401 || jqXHR.status === 404 || jqXHR.status === 500) {
                    logError(`CRITICAL ERROR (${jqXHR.status}). Stopping notification polling.`);
                    stopNotificationPolling();
                    // Optionally, update UI to indicate polling stopped
                    // $('#notification-badge').css('background-color', 'grey').text('!').fadeIn();
                 } else if (jqXHR.status === 0) {
                     logWarn("AJAX error: Could not connect to server. Check network or if server is running. Polling continues.");
                 }
            }
        });
    }

    // Function to display a single browser notification
    function showNotification(notificationData) {
        const permission = Notification.permission;
        if (permission === "granted") {
            logDebug("Permission granted. Attempting to create 'new Notification()' pop-up for ID:", notificationData.id);

            if (!notificationData || !notificationData.title || !notificationData.message) {
                logError("Notification failed: Title or message is missing.", notificationData);
                return;
            }

            var options = {
                body: notificationData.message,
                icon: 'assets/images/bell.svg', // *** Verify this path is correct ***
                tag: 'request-notification-' + notificationData.id, // Prevents duplicates for same ID
                silent: false, // Ensure it makes sound/vibrates (browser/OS settings may override)
                requireInteraction: false // ** Set to false initially, true can be annoying ** Let user decide to keep it open
                // Consider adding 'renotify: true' if you want a notification with the same tag to reappear
            };

            const iconUrl = options.icon;
            checkIconExists(iconUrl, function(exists) {
                if (!exists) {
                    logWarn("Notification icon not found at '" + iconUrl + "'. Using default or no icon.");
                    delete options.icon; // Remove icon if it doesn't exist
                }

                try {
                     // Check for active Service Worker - useful for more complex notification handling later
                     if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                       logDebug("ServiceWorker active. Note: Standard 'new Notification' used here.");
                       // If using SW for notifications, the logic would differ significantly.
                     }

                    const notification = new Notification(notificationData.title, options);
                    logDebug("Pop-up notification object created successfully:", notification);

                    notification.onclick = function(event) {
                        logDebug("Notification clicked:", event);
                        event.preventDefault(); // Prevent default action (like focusing browser) initially
                        markNotificationReadAJAX(notificationData.id); // Mark as read on server
                        if (notificationData.url) {
                            // Ensure the URL is absolute or correctly relative from the root
                            let urlToOpen = notificationData.url;
                             // Basic check if it's potentially relative and needs root path
                            if (!urlToOpen.startsWith('http') && !urlToOpen.startsWith('/')) {
                                urlToOpen = '/' + urlToOpen; // Assuming relative from root
                                logDebug("Relative URL detected, prefixing with /:", urlToOpen);
                            }
                            window.open(urlToOpen, '_blank'); // Open in new tab
                        } else {
                            logWarn("No URL provided for notification click.");
                        }
                        notification.close(); // Close the notification after click
                    };

                    notification.onerror = function(event) {
                        logError("Error occurred during browser notification display:", event);
                         // Specific hint for Firefox icon issue
                         if (!options.icon && navigator.userAgent.includes("Firefox")) {
                             logError("Firefox might require an icon for notifications to display correctly. Check if 'assets/images/bell.svg' exists or remove the icon option.");
                         }
                    };

                    notification.onshow = function(event) {
                         logDebug("Browser notification shown successfully:", event);
                    };

                    notification.onclose = function(event) {
                         logDebug("Browser notification closed:", event);
                         // Note: This also fires after clicking if notification.close() is called.
                    };

                    // Auto-close notification after a timeout (e.g., 10 seconds) if requireInteraction is false
                    if (!options.requireInteraction) {
                        setTimeout(() => notification.close(), 10000);
                    }

                } catch (err) {
                    logError("EXCEPTION caught creating 'new Notification()': ", err);
                     if (err.name === 'NotAllowedError') {
                       logError("Could be due to browser settings (e.g., Focus Assist on Windows, Do Not Disturb on macOS), Service Worker issues, or iframe restrictions.");
                     } else if (err.name === 'TypeError' && err.message.includes("does not have required properties")) {
                        logError("This might indicate an issue with the notification options format.");
                     }
                }
            });

        } else {
            logWarn("Browser notification permission not granted ('" + permission + "'). Pop-up will not be shown.");
        }
    }

    // Function to start polling
    function startNotificationPolling() {
        if (!notificationInterval) {
             lastNotificationTimestamp = 0; // Reset timestamp when starting
             pollingStopped = false; // Ensure flag is reset
            logDebug("Starting notification polling (every 30 seconds)...");
            fetchAndShowNotifications(); // Fetch immediately
            notificationInterval = setInterval(fetchAndShowNotifications, 30000); // Poll every 30 seconds
        } else {
            logDebug("Polling already running.");
        }
    }

    // Function to stop polling
    function stopNotificationPolling() {
        if (notificationInterval) {
            logWarn("Stopping notification polling.");
            clearInterval(notificationInterval);
            notificationInterval = null;
            pollingStopped = true; // Set the flag
        }
    }

    // Initialize notification system on document ready
    $(document).ready(function() {
        logDebug("Document ready. Initializing notification check.");

        // Use a small delay to ensure other scripts/UI elements might be ready
        setTimeout(function() {
            const initialPermission = Notification.permission;
            logDebug("Checking permission on document ready (after delay): " + initialPermission);

            if (initialPermission === "granted") {
                logDebug("Permission was already granted on load.");
                startNotificationPolling();
                $('#enable-notifications-link').hide();
            } else if (initialPermission === 'denied') {
                 logWarn("Permission was 'denied' on load. Cannot ask again via script. User must change browser settings.");
                 $('#enable-notifications-link').hide(); // Hide the link, it won't work
            } else { // 'default'
                logDebug("Permission not granted ('" + initialPermission + "'). Showing 'Enable Notifications' link.");
                 $('#enable-notifications-link').show().off('click').on('click', function(e) { // Use .off('click') to prevent multiple bindings
                    e.preventDefault();
                    requestNotificationPermission(); // Ask when user clicks
                });
            }
        }, 500); // 500ms delay

        // UI: Clear All button - Clears UI only
        $('#clear-all-notifications').off('click').on('click', function(e) {
            e.preventDefault();
            logDebug("Clearing notifications from UI dropdown.");
            $('#notification-badge').text('0').fadeOut();
            $('#notification-dropdown-menu .slimscroll').find('.notify-item:not(#notification-placeholder)').remove();
            $('#notification-placeholder').show();
            // Optional: Add AJAX call here to mark ALL currently displayed as read if desired
            // Example:
            // var visibleIds = [];
            // $('#notification-dropdown-menu .slimscroll .notify-item[data-id]').each(function() {
            //     visibleIds.push($(this).data('id'));
            // });
            // if (visibleIds.length > 0) markMultipleNotificationsRead(visibleIds); // Need a new AJAX endpoint/function
        });

        // UI: Click handler for dropdown items to mark as read and potentially open link
        $('#notification-dropdown-menu').off('click', '.notify-item[data-id]').on('click', '.notify-item[data-id]', function(e) {
            var $item = $(this);
            var notificationId = $item.data('id');
            var itemUrl = $item.attr('href');

            logDebug("Notification item clicked in dropdown, ID:", notificationId);

            // 1. Mark as read on the server
            markNotificationReadAJAX(notificationId);

            // 2. Visually update and remove the item
            $item.find('p.notify-details').css('font-weight', 'normal'); // Make it look read
            $item.fadeOut(300, function() {
                $(this).remove(); // Remove from DOM
                updateBadgeCountBasedOnUI(); // Update count after removal
                // Show placeholder if dropdown is now empty
                if ($('#notification-dropdown-menu .slimscroll').find('.notify-item:not(#notification-placeholder)').length === 0) {
                     $('#notification-placeholder').show();
                }
            });

            // 3. Handle navigation *after* AJAX and UI updates are initiated
            if (itemUrl && itemUrl !== '#') {
                e.preventDefault(); // Prevent default link behavior immediately
                // Open in new tab after a short delay to allow UI updates
                setTimeout(function() {
                     window.open(itemUrl, '_blank');
                }, 100); // Small delay
            } else {
                 e.preventDefault(); // Also prevent if URL is '#'
            }
        });

    }); // End document ready

}(window.jQuery));
