<?php
// --- Configuration & Initial Values ---
session_start(); // Optional: if you want to persist some values across requests differently

// --- Translations (Simplified) ---
$translations = [
    'en' => [
        'pageTitle' => 'End of Service Reward Calculator',
        'typeOfContract' => 'Type of Contract',
        'limitedPeriod' => 'Limited Period',
        'unlimitedPeriod' => 'Unlimited Period',
        'endOfContractReason' => 'End of Contract Reason',
        'chooseReason' => 'Choose a reason',
        'salary' => 'Salary',
        'currency' => 'SAR',
        'contractStartDate' => 'Contract Start Date',
        'contractEndDate' => 'Contract End Date',
        'selectDate' => 'Select Date', // Used in JS, placeholder here
        'calcButtonLabel' => 'Calculate Reward',
        'required' => 'This field is required.',
        'incorrectData' => 'Incorrect data provided.',
        'salaryMin' => 'Salary must be at least 1.',
        'defaultError' => 'An error occurred. Please check your inputs and try again.',
        'resultsTitle' => 'Calculation Results',
        'apiFetchError' => 'Could not fetch initial data from the server.',
        'apiCalcError' => 'Could not calculate the reward. Please try again.',
        'rewardAmount' => 'Reward Amount',
    ],
    'ar' => [ // You can add Arabic translations here
        'pageTitle' => 'حاسبة مكافأة نهاية الخدمة',
        'typeOfContract' => 'نوع العقد',
        'limitedPeriod' => 'محدد المدة',
        'unlimitedPeriod' => 'غير محدد المدة',
        'endOfContractReason' => 'سبب انتهاء العقد',
        'chooseReason' => 'اختر السبب',
        'salary' => 'الراتب',
        'currency' => 'ريال سعودي',
        'contractStartDate' => 'تاريخ بداية العقد',
        'contractEndDate' => 'تاريخ نهاية العقد',
        'selectDate' => 'اختر التاريخ',
        'calcButtonLabel' => 'احسب المكافأة',
        'required' => 'هذا الحقل مطلوب.',
        'incorrectData' => 'البيانات المقدمة غير صحيحة.',
        'salaryMin' => 'يجب أن يكون الراتب 1 على الأقل.',
        'defaultError' => 'حدث خطأ. يرجى التحقق من المدخلات والمحاولة مرة أخرى.',
        'resultsTitle' => 'نتائج الحساب',
        'apiFetchError' => 'لم نتمكن من جلب البيانات الأولية من الخادم.',
        'apiCalcError' => 'لم نتمكن من حساب المكافأة. يرجى المحاولة مرة أخرى.',
        'rewardAmount' => 'مبلغ المكافأة',
    ]
];

// Determine language (simple example, you might use cookies or URL param)
$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $translations)) {
    $current_lang = $_GET['lang'];
    $_SESSION['lang'] = $current_lang;
}

$t = function ($key) use ($translations, $current_lang) {
    return $translations[$current_lang][$key] ?? $key;
};

// --- State Variables ---
$contractType = $_POST['contractType'] ?? '1'; // Default to "1" (Limited)
$selectedReasonId = $_POST['endOfContractReason'] ?? '';
$salary = $_POST['salary'] ?? '';
$startDateStr = $_POST['contractStartDate'] ?? '';
$endDateStr = $_POST['contractEndDate'] ?? '';

$allReasons = [];
$calculationResult = null;
$displayData = null;
$errors = [];
$general_error_message = ''; // For API fetch errors initially

// --- API Communication Functions ---
function makeCurlRequest($url, $method = 'POST', $payload = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 15 seconds timeout
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("cURL Error for $url: $curl_error");
        return ['error' => 'Curl error: ' . $curl_error, 'http_code' => 0, 'data' => null];
    }
    if ($http_code != 200) {
         error_log("HTTP Error $http_code for $url. Response: " . mb_substr($response, 0, 500));
    }

    return ['error' => null, 'http_code' => $http_code, 'data' => json_decode($response, true)];
}

// Fetch initial end-of-service reasons (simulates `he()`)
function fetchEndOfServiceReasons() {
    global $t;
    $url = "https://knowledge-center-be.qiwa.sa/api/v1/end-of-service";
    $result = makeCurlRequest($url, 'POST', []);

    if ($result['error'] || $result['http_code'] !== 200 || empty($result['data'])) {
        return ['error' => $t('apiFetchError') . ( $result['error'] ? ' ('.$result['error'].')' : ($result['http_code'] !== 200 ? ' (HTTP '.$result['http_code'].')' : '') ), 'reasons' => []];
    }

    $api_reasons_data = $result['data']['EndOfServiceRewardLookUpRs']['Body']['EndOfServiceRewardLookUp']['ContractEndReason'] ?? [];
    $processed_reasons = [];
    foreach ($api_reasons_data as $index => $reason) {
        $processed_reasons[] = array_merge($reason, ['id' => 'reason-' . $index]); // Add a unique ID like in JS
    }
    return ['error' => null, 'reasons' => $processed_reasons];
}

// --- Initial Data Fetch ---
$reasonsResult = fetchEndOfServiceReasons();
if ($reasonsResult['error']) {
    $general_error_message = $reasonsResult['error'];
} else {
    $allReasons = $reasonsResult['reasons'];
}


// --- Form Submission Logic ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['calculate_reward'])) {
    // Sanitize and Validate inputs
    $contractType = trim($_POST['contractType'] ?? '1');
    $selectedReasonId = trim($_POST['endOfContractReason'] ?? '');
    $salary = filter_input(INPUT_POST, 'salary', FILTER_VALIDATE_FLOAT);
    $startDateStr = trim($_POST['contractStartDate'] ?? '');
    $endDateStr = trim($_POST['contractEndDate'] ?? '');

    if (empty($contractType)) $errors['contractType'] = $t('required');
    if (empty($selectedReasonId)) $errors['endOfContractReason'] = $t('required');
    if ($salary === false) {
        $errors['salary'] = $t('incorrectData');
    } elseif ($salary < 1) {
        $errors['salary'] = $t('salaryMin');
    }
    if (empty($startDateStr)) $errors['contractStartDate'] = $t('required');
    if (empty($endDateStr)) $errors['contractEndDate'] = $t('required');

    if (!empty($startDateStr) && !empty($endDateStr)) {
        $startDateTime = new DateTime($startDateStr);
        $endDateTime = new DateTime($endDateStr);
        if ($startDateTime >= $endDateTime) {
            $errors['contractEndDate'] = "End date must be after start date.";
        }
    }

    if (empty($errors)) {
        $selectedReasonDetails = null;
        foreach ($allReasons as $r) {
            if ($r['id'] === $selectedReasonId) {
                $selectedReasonDetails = $r;
                break;
            }
        }

        if (!$selectedReasonDetails) {
            $errors['endOfContractReason'] = "Invalid reason selected.";
        } else {
            $api_params = [
                'StartDate' => $startDateStr, // HTML date input format YYYY-MM-DD
                'EndDate' => $endDateStr,     // HTML date input format YYYY-MM-DD
                'Salary' => $salary,
                'ContractTypeCode' => $contractType,
                'ContractEndReasonCode' => $selectedReasonDetails['ContractEndReasonCode']
            ];
            $calc_url = "https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup?" . http_build_query($api_params);
            $calcApiResult = makeCurlRequest($calc_url, 'POST', []); // Empty body for POST

            if ($calcApiResult['error'] || $calcApiResult['http_code'] !== 200 || empty($calcApiResult['data'])) {
                 $errors['api_calculation'] = $t('apiCalcError') . ($calcApiResult['error'] ? ' ('.$calcApiResult['error'].')' : ($calcApiResult['http_code'] !== 200 ? ' (HTTP '.$calcApiResult['http_code'].')' : ' - Empty response'));
                 if(isset($calcApiResult['data']['errors'])) { // Check for specific API errors
                    $apiErrorMessages = [];
                    foreach($calcApiResult['data']['errors'] as $err) {
                        $apiErrorMessages[] = $err['message'] ?? 'Unknown API error detail';
                    }
                     $errors['api_calculation'] .= ': ' . implode(', ', $apiErrorMessages);
                 } elseif (isset($calcApiResult['data']['message'])) {
                     $errors['api_calculation'] .= ': ' . $calcApiResult['data']['message'];
                 }

            } else {
                $calculationResult = $calcApiResult['data'];
                $displayData = [
                    'endOfContractReason' => $current_lang === 'ar' ? ($selectedReasonDetails['ArDescription'] ?? '') : ($selectedReasonDetails['EnDescription'] ?? ''),
                    'salary' => $calculationResult['Salary'] ?? 'N/A',
                    'typeOfContract' => $contractType == '1' ? $t('limitedPeriod') : $t('unlimitedPeriod'),
                    'contractStartDate' => $calculationResult['StartDate'] ?? 'N/A',
                    'contractEndDate' => $calculationResult['EndDate'] ?? 'N/A',
                    'RewardAmount' => $calculationResult['RewardAmount'] ?? 'N/A',
                ];
            }
        }
    }
}
// Filter reasons for the dropdown based on the current $contractType
$filteredReasons = [];
if (!empty($allReasons)) {
    foreach ($allReasons as $reason) {
        if (isset($reason['ContractTypeCode']) && $reason['ContractTypeCode'] == $contractType) {
            $filteredReasons[] = $reason;
        }
    }
    // Sort reasons by ContractEndReasonCode as in JS
    usort($filteredReasons, function($a, $b) {
        return intval($a['ContractEndReasonCode'] ?? 0) - intval($b['ContractEndReasonCode'] ?? 0);
    });
}

?>
<!DOCTYPE html>
<html lang="<?=htmlspecialchars($current_lang); ?>" dir="<?=$current_lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=htmlspecialchars($t('pageTitle')); ?></title>    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
        h1 { color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="radio"], .form-group input[type="checkbox"] { margin-right: 5px; }
        .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="date"], .form-group select {
            width: calc(100% - 22px); padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        .form-group .currency-span { margin-left: 5px; }
        .date-container { display: flex; justify-content: space-between; gap: 10px; }
        .date-container .form-group { flex: 1; }
        button[type="submit"] {
            background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; display: block; width: 100%;
        }
        button[type="submit"]:hover { background-color: #0056b3; }
        .error-text { color: red; font-size: 0.9em; margin-top: 3px; }
        .general-error { background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; }
        .results { margin-top: 30px; padding: 15px; background-color: #e9f7ef; border: 1px solid #a7d7c5; border-radius: 4px; }
        .results h2 { margin-top: 0; color: #1e7e34; }
        .results p { margin: 8px 0; }
        .lang-switcher { text-align: center; margin-bottom: 20px; }
        .lang-switcher a { margin: 0 5px; text-decoration: none; color: #007bff; }
        html[dir="rtl"] .form-group input[type="radio"], html[dir="rtl"] .form-group input[type="checkbox"] { margin-left: 5px; margin-right:0; }
        html[dir="rtl"] .currency-span { margin-right: 5px; margin-left: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="lang-switcher">
            <a href="?lang=en" <?php if($current_lang == 'en') echo 'style="font-weight:bold;"'; ?>>English</a> |
            <a href="?lang=ar" <?php if($current_lang == 'ar') echo 'style="font-weight:bold;"'; ?>>العربية</a>
        </div>
        <h1><?=htmlspecialchars($t('pageTitle')); ?></h1>
        <?php if ($general_error_message): ?>
            <div class="general-error"><?=htmlspecialchars($general_error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors['api_calculation'])): ?>
            <div class="general-error"><?=htmlspecialchars($errors['api_calculation']); ?></div>
        <?php endif; ?>
        <form method="POST" action="<?=htmlspecialchars($_SERVER["PHP_SELF"]); ?>?lang=<?=$current_lang; ?>" data-testid="end-of-service-reward-calc-form">
            <div class="form-group">
                <label><?=htmlspecialchars($t('typeOfContract')); ?></label>
                <div>
                    <input type="radio" name="contractType" value="1" id="contract-limited"
                           <?=($contractType == "1") ? "checked" : ""; ?> onchange="this.form.submit()">
                    <label for="contract-limited"><?=htmlspecialchars($t('limitedPeriod')); ?></label>
                </div>
                <div>
                    <input type="radio" name="contractType" value="2" id="contract-unlimited"
                           <?=($contractType == "2") ? "checked" : ""; ?> onchange="this.form.submit()">
                    <label for="contract-unlimited"><?=htmlspecialchars($t('unlimitedPeriod')); ?></label>
                </div>
                <?php if (!empty($errors['contractType'])): ?><div class="error-text"><?=htmlspecialchars($errors['contractType']); ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="calc-content-select"><?=htmlspecialchars($t('endOfContractReason')); ?></label>
                <select name="endOfContractReason" id="calc-content-select" <?php if(empty($filteredReasons) && empty($general_error_message)) echo 'disabled';?>>
                    <option value=""><?=htmlspecialchars($t('chooseReason')); ?></option>
                    <?php if (!empty($filteredReasons)): ?>
                        <?php foreach ($filteredReasons as $reason): ?>
                            <option value="<?=htmlspecialchars($reason['id']); ?>"
                                    <?=($selectedReasonId == $reason['id']) ? "selected" : ""; ?>>
                                <?=htmlspecialchars($current_lang === 'ar' ? ($reason['ArDescription'] ?? '') : ($reason['EnDescription'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php elseif(empty($general_error_message)): ?>
                         <option value="" disabled>No reasons available for this contract type.</option>
                    <?php endif; ?>
                </select>
                <?php if (!empty($errors['endOfContractReason'])): ?><div class="error-text"><?=htmlspecialchars($errors['endOfContractReason']); ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label for="salary"><?=htmlspecialchars($t('salary')); ?></label>
                <input type="number" name="salary" id="salary" value="<?=htmlspecialchars($salary); ?>"
                       min="1" max="1000000000000000" step="any">
                <span class="currency-span"><?=htmlspecialchars($t('currency')); ?></span>
                <?php if (!empty($errors['salary'])): ?><div class="error-text"><?=htmlspecialchars($errors['salary']); ?></div><?php endif; ?>
            </div>

            <div class="date-container">
                <div class="form-group">
                    <label for="contractStartDate"><?=htmlspecialchars($t('contractStartDate')); ?></label>
                    <input type="date" name="contractStartDate" id="contractStartDate" value="<?=htmlspecialchars($startDateStr); ?>"
                           max="<?=htmlspecialchars($endDateStr ?: date('Y-m-d')); ?>">
                    <?php if (!empty($errors['contractStartDate'])): ?><div class="error-text"><?=htmlspecialchars($errors['contractStartDate']); ?></div><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="contractEndDate"><?=htmlspecialchars($t('contractEndDate')); ?></label>
                    <input type="date" name="contractEndDate" id="contractEndDate" value="<?=htmlspecialchars($endDateStr); ?>"
                           min="<?=htmlspecialchars($startDateStr); ?>" max="<?=date('Y-m-d'); // Cannot be future date ?>">
                    <?php if (!empty($errors['contractEndDate'])): ?><div class="error-text"><?=htmlspecialchars($errors['contractEndDate']); ?></div><?php endif; ?>
                </div>
            </div>
            <input type="hidden" name="calculate_reward" value="1">
            <button type="submit" data-testid="end-of-service-reward-submit"><?=htmlspecialchars($t('calcButtonLabel')); ?></button>
        </form>

        <?php if ($calculationResult && empty($errors['api_calculation']) && $displayData): ?>
            <div class="results" data-testid="end-of-service-reward-results">
                <h2><?=htmlspecialchars($t('resultsTitle')); ?></h2>
                <p><strong><?=htmlspecialchars($t('typeOfContract')); ?>:</strong> <?=htmlspecialchars($displayData['typeOfContract']); ?></p>
                <p><strong><?=htmlspecialchars($t('endOfContractReason')); ?>:</strong> <?=htmlspecialchars($displayData['endOfContractReason']); ?></p>
                <p><strong><?=htmlspecialchars($t('salary')); ?>:</strong> <?=htmlspecialchars(number_format(floatval($displayData['salary']), 2)); ?> <?=htmlspecialchars($t('currency')); ?></p>
                <p><strong><?=htmlspecialchars($t('contractStartDate')); ?>:</strong> <?=htmlspecialchars($displayData['contractStartDate']); ?></p>
                <p><strong><?=htmlspecialchars($t('contractEndDate')); ?>:</strong> <?=htmlspecialchars($displayData['contractEndDate']); ?></p>
                <p><strong><?=htmlspecialchars($t('rewardAmount')); ?>:</strong> <?=htmlspecialchars(number_format(floatval($displayData['RewardAmount']), 2)); ?> <?=htmlspecialchars($t('currency')); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Basic client-side date validation enhancements (server-side validation is primary)
        const startDateInput = document.getElementById('contractStartDate');
        const endDateInput = document.getElementById('contractEndDate');
        const today = new Date().toISOString().split('T')[0];

        if (startDateInput && endDateInput) {
            endDateInput.max = today; // End date cannot be in the future

            startDateInput.addEventListener('change', function() {
                if (this.value) {
                    endDateInput.min = this.value;
                    // If start date is set after end date was already set, and start > end, clear end.
                    if (endDateInput.value && new Date(this.value) > new Date(endDateInput.value)) {
                        endDateInput.value = '';
                    }
                }
                 // Ensure start date is not after today
                if (new Date(this.value) > new Date(today)) {
                    this.value = today;
                }
                endDateInput.max = today; // Re-apply max for end date if start date changes
                 if (endDateInput.value && new Date(endDateInput.value) > new Date(today)) {
                     endDateInput.value = today;
                 }
            });

            endDateInput.addEventListener('change', function() {
                if (this.value) {
                    startDateInput.max = this.value;
                     // Ensure end date is not after today
                    if (new Date(this.value) > new Date(today)) {
                        this.value = today;
                        startDateInput.max = today;
                    }
                } else {
                     startDateInput.max = today; // If end date is cleared, start date can be up to today
                }
            });
            // Initial check on page load if values are pre-filled
            if (startDateInput.value) endDateInput.min = startDateInput.value;
            if (endDateInput.value) startDateInput.max = endDateInput.value;

            if (endDateInput.value && new Date(endDateInput.value) > new Date(today)) {
                 endDateInput.value = today;
             }
             if (startDateInput.value && new Date(startDateInput.value) > new Date(endDateInput.value)) {
                startDateInput.value = endDateInput.value; // or clear it, depending on desired UX
            }
             if (startDateInput.value && new Date(startDateInput.value) > new Date(today)) {
                startDateInput.value = today;
            }

        }
    </script>
</body>
</html>