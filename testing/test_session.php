<?php
session_start();
echo "<pre>";
echo "Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? "ACTIVE" : "INACTIVE") . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "\nCookies:\n";
print_r($_COOKIE);
echo "</pre>";
?>
