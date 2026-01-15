<?php
/**
 * Session Configuration JavaScript
 * 
 * This file outputs JavaScript configuration variables from PHP session data.
 * It should be included AFTER session_check.php has run.
 * 
 * Variables set in session_check.php:
 * - $_SESSION['timeout_duration']
 * - $_SESSION['last_activity_time']
 * - $_SESSION['server_time']
 */

// Only output if session is active and timeout values are set
if (session_status() == PHP_SESSION_ACTIVE && isset($_SESSION['timeout_duration'])) {
    $timeout_duration = $_SESSION['timeout_duration'] ?? 1800;
    $last_activity_time = $_SESSION['last_activity_time'] ?? time();
    $current_server_time = $_SESSION['server_time'] ?? time();
    ?>
<!-- Session timeout configuration for jQuery.app.js -->
<script>
// Session timeout settings from server (used by jquery.app.js)
window.SESSION_TIMEOUT_SECONDS = <?php echo intval($timeout_duration); ?>; // in seconds
window.SESSION_TIMEOUT_MS = window.SESSION_TIMEOUT_SECONDS * 1000; // Convert to milliseconds
window.SERVER_LAST_ACTIVITY = <?php echo $last_activity_time; ?>; // Unix timestamp in seconds
window.SERVER_TIME = <?php echo $current_server_time; ?>; // Current server time in seconds

// ✅ Console verification - Test if session variables loaded successfully
console.info('%c✅ SESSION CONFIG LOADED SUCCESSFULLY', 'color: #4CAF50; font-weight: bold; font-size: 14px;');
console.info('%cSession Timeout:', 'color: #2196F3; font-weight: bold;', window.SESSION_TIMEOUT_SECONDS + ' seconds');
console.info('%cTimeout in Milliseconds:', 'color: #2196F3; font-weight: bold;', window.SESSION_TIMEOUT_MS + ' ms');
console.info('%cServer Last Activity:', 'color: #FF9800; font-weight: bold;', new Date(window.SERVER_LAST_ACTIVITY * 1000).toLocaleString());
console.info('%cServer Current Time:', 'color: #FF9800; font-weight: bold;', new Date(window.SERVER_TIME * 1000).toLocaleString());
// Calculate time difference
var timeSinceLastActivity = window.SERVER_TIME - window.SERVER_LAST_ACTIVITY;
console.info('%cTime Since Last Activity:', 'color: #9C27B0; font-weight: bold;', timeSinceLastActivity + ' seconds (' + Math.round(timeSinceLastActivity/60) + ' minutes)');
console.info('%c━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'color: #4CAF50;');

// ✅ Live Session Countdown in Console
(function() {
    var lastLogged = -1;
    var loopTime = 120;
    // Log every 15 seconds with full H:M:S format
    setInterval(function() {
        var now = Math.floor(Date.now() / 1000);
        var elapsedSeconds = now - (window.SERVER_LAST_ACTIVITY || now);
        var remainingSeconds = window.SESSION_TIMEOUT_SECONDS - elapsedSeconds;
        if (remainingSeconds > 0) {
            if (remainingSeconds !== lastLogged && (remainingSeconds % loopTime === 0 || lastLogged === -1)) {
                var h = Math.floor(remainingSeconds / 3600);
                var m = Math.floor((remainingSeconds % 3600) / 60);
                var s = remainingSeconds % 60;
                // Format: 00h 00m 00s (only show hours if > 0)
                var timeStr = (h > 0 ? h + 'h ' : '') + 
                              (m < 10 && h > 0 ? '0' : '') + m + 'm ' + 
                              (s < 10 ? '0' : '') + s + 's';
                
                var color = remainingSeconds <= 60 ? '#f44336' : (remainingSeconds <= 300 ? '#FF9800' : '#2196F3');
                console.info('%c[Session] %cRemaining: ' + timeStr, 'color: #9E9E9E;', 'color: ' + color + '; font-weight: bold;');
                lastLogged = remainingSeconds;
            }
        } else if (remainingSeconds <= 0 && lastLogged > 0) {
            console.info('%c[Session] %cEXPIRED', 'color: #9E9E9E;', 'color: #f44336; font-weight: bold;');
            lastLogged = 0;
        }
    }, 1000);
})();
</script>
<?php
} else {
    // Debug info if session variables are not set
    ?>
    <script>
    console.warn('%c⚠️ SESSION CONFIG NOT LOADED', 'color: #FF5722; font-weight: bold; font-size: 14px;');
    console.warn('Session status:', '<?php echo session_status(); ?>');
    console.warn('Timeout duration isset:', '<?php echo isset($_SESSION["timeout_duration"]) ? "YES" : "NO"; ?>');
    </script>
    <?php
}
?>
