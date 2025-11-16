<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>Current SMTP Settings</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .password { color: red; font-weight: bold; }
</style>";

$settings = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_encryption', 'from_email'];

echo "<table>";
echo "<tr><th>Setting</th><th>Current Value</th></tr>";

foreach ($settings as $setting) {
    $query = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name='$setting'");
    if ($query && $row = mysqli_fetch_assoc($query)) {
        $value = $row['setting_value'];
        
        // Mask password
        if ($setting === 'smtp_pass') {
            $display = str_repeat('*', strlen($value)) . " (length: " . strlen($value) . ")";
        } else {
            $display = htmlspecialchars($value);
        }
        
        echo "<tr><td><strong>$setting</strong></td><td>$display</td></tr>";
    } else {
        echo "<tr><td><strong>$setting</strong></td><td class='password'>NOT SET</td></tr>";
    }
}

echo "</table>";

echo "<hr>";
echo "<h2>Common Office 365 SMTP Issues</h2>";
echo "<ul>";
echo "<li><strong>Authentication Error</strong> - Usually means wrong password or account requires app-specific password</li>";
echo "<li><strong>Office 365 Modern Auth</strong> - Some accounts require OAuth2 instead of basic auth</li>";
echo "<li><strong>Security Defaults</strong> - Microsoft may have disabled basic authentication</li>";
echo "</ul>";

echo "<h3>Solutions:</h3>";
echo "<ol>";
echo "<li><strong>Test with Gmail (easier):</strong><br>";
echo "smtp_host: smtp.gmail.com<br>";
echo "smtp_port: 587<br>";
echo "smtp_encryption: tls<br>";
echo "smtp_user: your-email@gmail.com<br>";
echo "smtp_pass: [App-specific password from Google]</li>";
echo "<li><strong>For Office 365:</strong><br>";
echo "- Enable SMTP AUTH in Exchange Admin Center<br>";
echo "- Use app-specific password if MFA is enabled<br>";
echo "- Verify account is not blocked</li>";
echo "</ol>";

echo "<h3>Update SMTP Settings SQL:</h3>";
echo "<pre>";
echo "-- Update to use Gmail (for testing):\n";
echo "UPDATE app_settings SET setting_value = 'smtp.gmail.com' WHERE setting_name = 'smtp_host';\n";
echo "UPDATE app_settings SET setting_value = '587' WHERE setting_name = 'smtp_port';\n";
echo "UPDATE app_settings SET setting_value = 'tls' WHERE setting_name = 'smtp_encryption';\n";
echo "UPDATE app_settings SET setting_value = 'your-email@gmail.com' WHERE setting_name = 'smtp_user';\n";
echo "UPDATE app_settings SET setting_value = 'your-email@gmail.com' WHERE setting_name = 'from_email';\n";
echo "UPDATE app_settings SET setting_value = 'your-app-password' WHERE setting_name = 'smtp_pass';\n";
echo "</pre>";
?>
