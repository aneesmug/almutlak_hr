<?php
/**
 * Proxy to fetch end-of-service reasons from Qiwa API
 * This avoids CORS issues by making the request server-side
 */

header('Content-Type: application/json');

try {
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, 'https://knowledge-center-be.qiwa.sa/api/v1/end-of-service');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    
    // Execute cURL request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Check for cURL errors
    if ($error) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch data from external API',
            'message' => $error
        ]);
        exit;
    }
    
    // Check HTTP response code
    if ($httpCode !== 200) {
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'error' => 'External API returned error',
            'http_code' => $httpCode
        ]);
        exit;
    }
    
    // Decode and re-encode to validate JSON
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON response from external API'
        ]);
        exit;
    }
    
    // Extract the ContractEndReason array from nested structure
    $reasons = [];
    if (isset($data['EndOfServiceRewardLookUpRs']['Body']['EndOfServiceRewardLookUp']['ContractEndReason'])) {
        $allReasons = $data['EndOfServiceRewardLookUpRs']['Body']['EndOfServiceRewardLookUp']['ContractEndReason'];
        
        // Filter to only include ContractTypeCode = 1
        foreach ($allReasons as $reason) {
            if (isset($reason['ContractTypeCode']) && $reason['ContractTypeCode'] == '1') {
                $reasons[] = $reason;
            }
        }
    }
    
    // Return the data
    echo json_encode([
        'success' => true,
        'data' => $reasons
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
