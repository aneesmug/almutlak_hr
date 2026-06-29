<?php
/**
 * Fetch end-of-service reasons from local JSON file.
 */

header('Content-Type: application/json');

try {
    $jsonPath = __DIR__ . '/../../data/eos_reasons.json';

    if (!file_exists($jsonPath)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Local reasons file not found',
            'path' => 'data/eos_reasons.json'
        ]);
        exit;
    }

    $raw = file_get_contents($jsonPath);
    if ($raw === false || trim($raw) === '') {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Local reasons file is empty or unreadable'
        ]);
        exit;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON in local reasons file'
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
