/*
    ======================================
    ==  NOTIFICATION LOGIC (Standalone) ==
    ======================================
    This file handles:
    1. Requesting user permission for notifications.
    2. Polling the server (notification.php) for new notifications.
    3. Updating the UI (bell icon badge and dropdown) in topbar.php.
    4. Showing browser-native pop-up notifications.
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
                    startNotificationPolling();
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

    // Function to fetch and display notifications
    function fetchAndShowNotifications() {
        logDebug("Polling... Making AJAX request to notification.php"); // Updated Log

        $.ajax({
            // --- MODIFIED: Point to includes directory ---
            url: 'includes/notification.php',
            // --- END MODIFICATION ---
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function(response) {
                logDebug("Response from notification.php:", response);

                if (response && response.status === 'success' && Array.isArray(response.notifications)) {
                    var notifications = response.notifications;
                    var count = notifications.length;
                    var $badge = $('#notification-badge');
                    var $dropdownMenu = $('#notification-dropdown-menu .slimscroll');
                    var $placeholder = $('#notification-placeholder');

                    logDebug(`Found ${count} new notification(s).`);

                    // Clear previous items from dropdown (except placeholder)
                    $dropdownMenu.find('.notify-item:not(#notification-placeholder)').remove();

                    // Update badge
                    if (count > 0) {
                        $badge.text(count).fadeIn(); // Show smoothly
                        $placeholder.hide();

                        // Populate dropdown and trigger browser notifications
                        notifications.forEach(function(notification) {
                            // 1. Show browser notification
                            logDebug("Calling showNotification() for:", notification);
                            showNotification(notification);

                            // 2. Populate dropdown
                            var iconHtml = '<div class="notify-icon bg-primary"><i class="mdi mdi-comment-account-outline"></i></div>';
                            var messageSnippet = notification.message ? notification.message.substring(0, 50) + (notification.message.length > 50 ? '...' : '') : '';
                            var notificationHtml = `
                                <a href="${notification.url || '#'}" target="_blank" class="dropdown-item notify-item" data-id="${notification.id}">
                                    ${iconHtml}
                                    <p class="notify-details">${notification.title || 'No Title'}
                                        <small class="text-muted">${messageSnippet}</small>
                                    </p>
                                </a>`;
                            $dropdownMenu.prepend(notificationHtml); // Add new ones to the top
                        });

                    } else {
                        $badge.text('0').fadeOut(); // Hide smoothly
                        $placeholder.show();
                    }

                } else if (response && response.status === 'error') {
                     logError("Error message from notification.php:", response.message);
                     if(response.message === 'User not authenticated.') {
                        logError("Polling failed. The PHP session seems to be lost. Please log in again.");
                        stopNotificationPolling();
                     }
                } else {
                    logError("Received invalid response structure from notification.php", response);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // Check if the URL now points to includes/
                const requestedUrl = this.url; // 'this.url' inside $.ajax refers to the URL used
                logError(`AJAX error fetching notifications from '${requestedUrl}'. Status: ${textStatus}`, errorThrown);
                logError("Full XHR Response:", jqXHR);

                 if (jqXHR.status === 404) {
                    logError(`CRITICAL ERROR - '${requestedUrl}' was not found (404). Verify the path is correct and the file exists.`);
                    stopNotificationPolling();
                 } else if (jqXHR.status === 500) {
                    logError(`CRITICAL ERROR - '${requestedUrl}' crashed (500 Server Error). Check the PHP error logs on the server.`);
                    stopNotificationPolling();
                 } else if (jqXHR.status === 0) {
                     logWarn("AJAX error: Could not connect to server. Check network or if server is running.");
                 }
            }
        });
    }

    // Function to display a single browser notification
    function showNotification(notificationData) {
        const permission = Notification.permission;
        if (permission === "granted") {
            logDebug("Permission granted. Attempting to create 'new Notification()' pop-up.");

            if (!notificationData || !notificationData.title || !notificationData.message) {
                logError("Notification failed: Title or message is missing.", notificationData);
                return;
            }

            var options = {
                body: notificationData.message,
                icon: 'assets/images/bell.svg', // Default icon path
                tag: 'request-notification-' + notificationData.id, // Prevents duplicates for same ID
                silent: false, // Ensure it makes sound/vibrates
                requireInteraction: true // Keep notification until interacted with
            };

            const iconUrl = options.icon;
            checkIconExists(iconUrl, function(exists) {
                if (!exists) {
                    logWarn("Notification icon not found at '" + iconUrl + "'. Notification will show without icon.");
                    delete options.icon; // Remove icon if it doesn't exist
                }

                // --- More Robust Notification Creation ---
                try {
                    // Check Service Worker registration (though not strictly required for basic notifications)
                     if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                       logDebug("ServiceWorker active, attempting notification via SW (if implemented there).");
                       // If you have SW notifications: navigator.serviceWorker.controller.postMessage({ type: 'show-notification', data: notificationData });
                       // For now, we'll stick to basic Notification API
                     }

                    const notification = new Notification(notificationData.title, options);
                    logDebug("Pop-up notification object created successfully:", notification);

                    notification.onclick = function(event) {
                        logDebug("Notification clicked:", event);
                        event.preventDefault();
                        if (notificationData.url) {
                            // --- MODIFIED: Ensure URL starts correctly ---
                            let urlToOpen = notificationData.url;
                             // Check if it's already a full URL or starts with '/'
                            if (!urlToOpen.startsWith('http') && !urlToOpen.startsWith('/')) {
                                // Assume it's relative to the site root if not specified otherwise
                                // This might need adjustment based on your site structure
                                urlToOpen = '/' + urlToOpen;
                                logDebug("Relative URL detected, prefixing with /:", urlToOpen);
                            }
                            window.open(urlToOpen, '_blank');
                            // --- END MODIFICATION ---
                        } else {
                            logWarn("No URL provided for notification click.");
                        }
                        notification.close();
                    };

                    notification.onerror = function(event) {
                        logError("Error occurred during notification display:", event);
                         // Specific check for Firefox issue where empty icon can cause 'NotAllowedError'
                         if (!options.icon && navigator.userAgent.includes("Firefox")) {
                             logError("Firefox might require an icon. Check if 'assets/images/bell.svg' exists or remove the icon option entirely.");
                         }
                    };

                    notification.onshow = function(event) {
                         logDebug("Notification shown successfully:", event);
                    };

                    notification.onclose = function(event) {
                         logDebug("Notification closed:", event);
                    };

                } catch (err) {
                    logError("EXCEPTION caught creating 'new Notification()': ", err);
                     if (err.name === 'NotAllowedError') {
                       logError("Could be due to browser settings (e.g., Focus Assist on Windows, Do Not Disturb on macOS) or potentially incorrect Service Worker interaction.");
                     } else if (err.name === 'TypeError' && err.message.includes("does not have required properties")) {
                        logError("This might indicate an issue with the notification options format.");
                     }
                }
                // --- End Robust Notification Creation ---
            });

        } else {
            logWarn("Permission not granted ('" + permission + "'). Pop-up will not be shown.");
        }
    }

    // Function to start polling
    function startNotificationPolling() {
        if (!notificationInterval) {
            logDebug("Starting notification polling (every 30 seconds)...");
            fetchAndShowNotifications(); // Fetch immediately
            notificationInterval = setInterval(fetchAndShowNotifications, 30000);
        } else {
            logDebug("Polling already running.");
        }
    }

    // Function to stop polling
    function stopNotificationPolling() {
        if (notificationInterval) {
            logWarn("Stopping notification polling due to critical error or session issue.");
            clearInterval(notificationInterval);
            notificationInterval = null;
        }
    }

    // Initialize notification system on document ready
    $(document).ready(function() {
        logDebug("Document ready. Initializing notification check.");

        // Use a very short delay just to ensure the rest of the page JS (like jQuery UI/plugins) might be ready
        setTimeout(function() {
            const initialPermission = Notification.permission;
            logDebug("Checking permission on document ready (after short delay): " + initialPermission);

            if (initialPermission === "granted") {
                logDebug("Permission was already granted on load.");
                startNotificationPolling();
                $('#enable-notifications-link').hide();
            } else if (initialPermission !== 'denied') {
                logDebug("Permission not granted ('" + initialPermission + "'). Showing 'Enable Notifications' link.");
                 $('#enable-notifications-link').show().on('click', function(e) {
                    e.preventDefault();
                    requestNotificationPermission(); // Ask when user clicks
                });
            } else {
                 logWarn("Permission was 'denied' on load. Cannot ask again via script. User must change browser settings.");
                 $('#enable-notifications-link').hide(); // Hide the link, it won't work
            }
        }, 100); // 100ms delay

        // UI Clear button
        $('#clear-all-notifications').on('click', function(e) {
            e.preventDefault();
            logDebug("Clearing notifications from UI dropdown.");
            $('#notification-badge').text('0').fadeOut();
            $('#notification-dropdown-menu .slimscroll').find('.notify-item:not(#notification-placeholder)').remove();
            $('#notification-placeholder').show();
            // Note: This does NOT mark as read in DB. That happens server-side.
        });
    });

}(window.jQuery));

