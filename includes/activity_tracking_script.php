<!-- User Activity Tracking Script -->
<script type="text/javascript">
$(document).ready(function() {
    // Send screen resolution to server if activity_log_id exists
    <?php if (isset($_SESSION['activity_log_id'])): ?>
    var activityId = <?= $_SESSION['activity_log_id'] ?>;
    var screenWidth = window.screen.width;
    var screenHeight = window.screen.height;
    
    $.ajax({
        url: './includes/ajaxFile/ajaxUserActivity.php',
        type: 'POST',
        data: {
            ajaxType: 'update_screen_resolution',
            activity_id: activityId,
            width: screenWidth,
            height: screenHeight
        },
        dataType: 'json'
    });
    <?php endif; ?>
});
</script>
