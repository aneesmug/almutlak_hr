<?php
/**
 * Global Maintenance Mode Checker
 * Include this at the TOP of every PHP file that should respect maintenance mode
 * Or call it once in a central bootstrap file
 */

// Check if maintenance mode is enabled
$maintenanceFile = __DIR__ . '/maintenance/MAINTENANCE_ON';

if (file_exists($maintenanceFile)) {
    $maintenanceStatus = trim(file_get_contents($maintenanceFile));
    
    // Only redirect if file contains "ON"
    if (strtoupper($maintenanceStatus) === 'ON') {
        // Don't redirect these paths (admin/health check tools)
        $excludedPaths = [
            '/almutlak/system/maintenance/',
            '/almutlak/system/error/',
            '/almutlak/system/db_check_admin/',
        ];
        
        $currentUri = $_SERVER['REQUEST_URI'];
        $shouldExclude = false;
        
        foreach ($excludedPaths as $path) {
            if (strpos($currentUri, $path) === 0) {
                $shouldExclude = true;
                break;
            }
        }
        
        // Redirect to maintenance page if not excluded
        if (!$shouldExclude) {
            header('HTTP/1.1 302 Found');
            header('Location: /almutlak/system/maintenance/index.php');
            exit;
        }
    }
}
?>
