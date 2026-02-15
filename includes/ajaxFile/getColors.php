<?php
/**
 * GET COLORS ENDPOINT
 * Returns application color constants as JSON
 * Usage: fetch('./includes/ajaxFile/getColors.php').then(r => r.json()).then(colors => window.APP_COLORS = colors)
 */

header('Content-Type: application/json');
header('Cache-Control: public, max-age=86400'); // Cache for 24 hours

$colors = array(
    // PRIMARY BRAND COLORS
    'primary' => '#3085d6',        // Primary blue - main actions
    'secondary' => '#6c757d',      // Gray/muted - secondary elements
    'success' => '#28a745',        // Green - approval/success actions
    'danger' => '#dc3545',         // Red - errors/danger/reject
    'danger_dark' => '#d33',       // Dark red - alternative danger
    'warning' => '#ffc107',        // Yellow - warnings/alerts
    'info' => '#17a2b8',           // Cyan/teal - info/notices
    'white' => '#ffffff',          // White background
    
    // BACKGROUND COLORS
    'bg_light' => '#e9ecef',       // Light gray background
    'bg_lighter' => '#f8f9fa',     // Even lighter background
    'bg_white' => '#ffffff',       // White background
    'bg_dark' => '#343a40',        // Dark background
    
    // BORDER COLORS
    'border_default' => '#ced4da', // Standard border
    'border_light' => '#d1d5db',   // Light border
    'border_lighter' => '#e5e7eb', // Very light border
    'border_dark' => '#495057',    // Dark border
    
    // TEXT COLORS
    'text_dark' => '#333333',      // Dark text - main content
    'text_muted' => '#6c757d',     // Muted/secondary text
    'text_white' => '#ffffff',     // White text
    'text_danger' => '#dc3545',    // Red text - errors
    'text_warning' => '#ffc107',   // Yellow text - warnings
    'text_success' => '#28a745',   // Green text - success
    
    // STATUS SPECIFIC
    'pending' => '#ffc107',        // Yellow - pending/waiting status
    'active' => '#28a745',         // Green - active status
    'inactive' => '#dc3545',       // Red - inactive status
    'processing' => '#3085d6',     // Blue - processing status
    
    // SPECIAL USES
    'overlay' => 'rgba(0, 0, 0, 0.5)',  // Dark overlay
    'overlay_light' => 'rgba(0, 0, 0, 0.1)', // Light overlay
    'shadow' => 'rgba(0, 0, 0, 0.2)',   // Shadow color
);

echo json_encode($colors);
?>
