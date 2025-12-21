<?php
/**
 * BROWSER NOTIFICATION TEST & DEMO PAGE
 * 
 * This page demonstrates the browser notification system
 * and allows testing notifications with actual working code
 * 
 * Access: /includes/notification_example.php
 * Restricted to: Administrators only
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_check.php';

// Only admins can access this test page
if ($user_type !== 'administrator') {
    header('Location: ../dashboard.php');
    exit;
}

$emp_id = $empid ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browser Notification Test</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px; margin: 20px 0; }
        .btn { padding: 10px 20px; margin: 10px 5px 10px 0; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .form-group { margin: 15px 0; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { resize: vertical; min-height: 80px; }
        .status { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .status.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .status.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .status.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .test-results { background: #f9f9f9; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0; }
        pre { background: #f9f9f9; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔔 Browser Notification Test Center</h1>
            <p>Test the notification system and send test notifications</p>
        </div>

        <!-- Permission Status -->
        <div class="card">
            <h3>Notification Permission Status</h3>
            <div id="permission-status" class="status info">
                Checking notification permission...
            </div>
            <button class="btn btn-primary" onclick="requestNotificationPermission()">
                Request Permission
            </button>
        </div>

        <!-- Quick Test Buttons -->
        <div class="card">
            <h3>Quick Test Notifications</h3>
            <button class="btn btn-success" onclick="sendQuickNotification('success', 'Request Approved', 'Your vacation request has been approved!')">
                ✓ Success Notification
            </button>
            <button class="btn btn-warning" onclick="sendQuickNotification('warning', 'Pending Review', 'Your request is waiting for manager review')">
                ⚠ Warning Notification
            </button>
            <button class="btn btn-danger" onclick="sendQuickNotification('error', 'Request Rejected', 'Your request was rejected by HR')">
                ✗ Error Notification
            </button>
            <button class="btn btn-primary" onclick="sendQuickNotification('info', 'New Message', 'You have a new notification from the system')">
                ℹ Info Notification
            </button>
            <div id="quick-test-result"></div>
        </div>

        <!-- Custom Notification Form -->
        <div class="card">
            <h3>Send Custom Notification</h3>
            <form id="notification-form">
                <div class="form-group">
                    <label for="title">Notification Title *</label>
                    <input type="text" id="title" name="title" placeholder="e.g., Request Approved" required>
                </div>
                <div class="form-group">
                    <label for="message">Notification Message *</label>
                    <textarea id="message" name="message" placeholder="e.g., Your vacation request has been approved" required></textarea>
                </div>
                <div class="form-group">
                    <label for="url">Redirect URL (optional)</label>
                    <input type="text" id="url" name="url" placeholder="e.g., my_vacations.php or all_applied_vac.php">
                </div>
                <div class="form-group">
                    <label for="icon">Icon URL (optional)</label>
                    <input type="text" id="icon" name="icon" placeholder="e.g., assets/images/logo.png">
                </div>
                <button type="button" class="btn btn-primary" onclick="sendCustomNotification()">
                    Send Notification
                </button>
                <div id="custom-result"></div>
            </form>
        </div>

        <!-- Documentation -->
        <div class="card">
            <h3>📚 Implementation Guide</h3>
            <h4>For AJAX Handlers (Recommended):</h4>
            <pre>// In your AJAX handler (e.g., approveVacation.php)
$result = create_and_show_notification(
    $conDB,
    $emp_id,
    "Vacation Approved",
    "Your vacation has been approved",
    "my_vacations.php"
);

send_json_response(
    "Success",
    "Approved",
    "success",
    200,
    ['notification_js' => $result['js_code']]
);</pre>

            <h4>In JavaScript/AJAX Response:</h4>
            <pre>$.ajax({
    url: 'approveVacation.php',
    type: 'POST',
    success: function(response) {
        // Execute notification JavaScript
        if (response.data.notification_js) {
            eval(response.data.notification_js);
        }
        Swal.fire('Success', response.message, 'success');
    }
});</pre>
        </div>
    </div>

    <script src="../assets/js/jquery.min.js"></script>
    <script>
        // Check notification permission status
        function updatePermissionStatus() {
            const statusDiv = document.getElementById('permission-status');
            
            if ('Notification' in window) {
                if (Notification.permission === 'granted') {
                    statusDiv.innerHTML = '✓ Notifications are <strong>ENABLED</strong> - You will see popups';
                    statusDiv.className = 'status success';
                } else if (Notification.permission === 'denied') {
                    statusDiv.innerHTML = '✗ Notifications are <strong>DISABLED</strong> - Please enable in browser settings';
                    statusDiv.className = 'status error';
                } else {
                    statusDiv.innerHTML = '⚠ Notifications not requested yet - Click "Request Permission" to enable';
                    statusDiv.className = 'status info';
                }
            } else {
                statusDiv.innerHTML = '✗ Your browser does not support notifications';
                statusDiv.className = 'status error';
            }
        }

        // Request notification permission
        function requestNotificationPermission() {
            if ('Notification' in window && Notification.permission !== 'granted') {
                Notification.requestPermission().then(function(permission) {
                    updatePermissionStatus();
                });
            } else {
                updatePermissionStatus();
            }
        }

        // Send quick test notification
        function sendQuickNotification(type, title, message) {
            if ('Notification' in window && Notification.permission === 'granted') {
                const options = {
                    body: message,
                    icon: '../assets/images/logo.png',
                    tag: 'test-' + Date.now()
                };
                
                const notification = new Notification(title, options);
                notification.onclick = function() {
                    window.focus();
                    notification.close();
                };
                
                setTimeout(() => notification.close(), 5000);
                
                showResult('quick-test-result', 'success', '✓ ' + title + ' notification sent!');
            } else {
                showResult('quick-test-result', 'error', '✗ Notification permission not granted');
                requestNotificationPermission();
            }
        }

        // Send custom notification via AJAX
        function sendCustomNotification() {
            const title = document.getElementById('title').value;
            const message = document.getElementById('message').value;
            const url = document.getElementById('url').value || 'dashboard.php';
            const icon = document.getElementById('icon').value || '../assets/images/logo.png';

            if (!title || !message) {
                showResult('custom-result', 'error', 'Title and message are required');
                return;
            }

            $.ajax({
                url: 'ajaxFile/test_notification.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    title: title,
                    message: message,
                    url: url,
                    icon: icon,
                    emp_id: <?php echo (int)$emp_id; ?>
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showResult('custom-result', 'success', '✓ Notification sent! Check for popup');
                        document.getElementById('notification-form').reset();
                    } else {
                        showResult('custom-result', 'error', '✗ Error: ' + response.message);
                    }
                },
                error: function() {
                    showResult('custom-result', 'error', '✗ Failed to send notification');
                }
            });
        }

        // Helper to show result messages
        function showResult(elementId, type, message) {
            const element = document.getElementById(elementId);
            element.className = 'status ' + type;
            element.innerHTML = message;
            setTimeout(() => {
                element.innerHTML = '';
            }, 5000);
        }

        // Initialize on page load
        window.addEventListener('load', function() {
            updatePermissionStatus();
        });
    </script>
</body>
</html>
