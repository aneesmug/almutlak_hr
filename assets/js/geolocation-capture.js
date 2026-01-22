/**
 * Precise Geolocation Capture Script
 * Captures exact GPS coordinates from browser and sends to server
 * Include this after login success
 * 
 * NOTE: Browser requires HTTPS for geolocation. HTTP will not work.
 * User can check "Remember this decision" to prevent prompt on future logins.
 */

(function() {
    'use strict';
    
    /**
     * Request permission and capture GPS coordinates
     */
    function captureUserLocation() {
        if (!navigator.geolocation) {
            console.log('Geolocation not supported by browser');
            return;
        }
        
        // Check if page is HTTPS (required for geolocation)
        if (window.location.protocol !== 'https:') {
            console.warn('Geolocation requires HTTPS. Current protocol: ' + window.location.protocol);
            return;
        }
        
        // Request high accuracy GPS coordinates
        var options = {
            enableHighAccuracy: true,  // Request GPS instead of WiFi triangulation
            timeout: 15000,            // 15 seconds timeout
            maximumAge: 0              // Don't use cached position
        };
        
        console.log('Requesting precise GPS coordinates from browser...');
        
        navigator.geolocation.getCurrentPosition(
            function onSuccess(position) {
                var coords = position.coords;
                console.log('✓ GPS Location captured:', {
                    latitude: coords.latitude,
                    longitude: coords.longitude,
                    accuracy: coords.accuracy + ' meters',
                    altitude: coords.altitude,
                    heading: coords.heading,
                    speed: coords.speed
                });
                
                // Send precise coordinates to server
                sendLocationToServer(
                    coords.latitude,
                    coords.longitude,
                    coords.accuracy
                );
            },
            function onError(error) {
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        console.warn('⚠ User denied geolocation permission. Tip: Click "Allow" and check "Remember this decision" to prevent future prompts.');
                        break;
                    case error.POSITION_UNAVAILABLE:
                        console.warn('⚠ Position information is unavailable (GPS signal lost, check your location settings)');
                        break;
                    case error.TIMEOUT:
                        console.warn('⚠ Geolocation request timed out (GPS took too long to respond)');
                        break;
                    default:
                        console.warn('⚠ Geolocation error:', error.message);
                }
                // Not critical - server already has IP-based location
            },
            options
        );
    }
    
    /**
     * Send GPS coordinates to server
     */
    function sendLocationToServer(latitude, longitude, accuracy) {
        $.ajax({
            url: './includes/ajaxFile/ajaxPreciseLocation.php',
            type: 'POST',
            dataType: 'json',
            data: {
                ajaxType: 'update_precise_location',
                latitude: latitude,
                longitude: longitude,
                accuracy: accuracy
            },
            success: function(response) {
                if (response.status === 200) {
                    console.log('✓ GPS coordinates saved to server (Accuracy: ±' + response.data.accuracy.toFixed(1) + 'm)');
                } else {
                    console.warn('Server response:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to send location:', error);
            }
        });
    }
    
    /**
     * Auto-run immediately on script load
     */
    function init() {
        // Add small delay to ensure page is fully loaded
        if (window.$ && window.$.ajax) {
            captureUserLocation();
        } else {
            // If jQuery not loaded yet, wait for it
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', captureUserLocation);
            }
        }
    }
    
    // Run immediately
    init();
})();
