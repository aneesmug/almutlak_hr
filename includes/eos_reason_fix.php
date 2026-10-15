<?php
/**
 * emp_eos.leaving_reason was VARCHAR(100) and leaving_reason_ar VARCHAR(150), so longer
 * End of Service reasons (up to ~145 chars in data/eos_reasons.json) were cut on save.
 * The columns are widened on first use because sql/ is git-ignored and never reaches the
 * live server, and rows that were already cut are refilled from the JSON by reason code.
 */

if (!function_exists('eos_ensure_reason_columns')) {
    function eos_ensure_reason_columns($conDB)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        foreach (['leaving_reason', 'leaving_reason_ar'] as $col) {
            $res = mysqli_query($conDB, "SHOW COLUMNS FROM `emp_eos` LIKE '{$col}'");
            $row = $res ? mysqli_fetch_assoc($res) : null;
            if ($row && preg_match('/^varchar\((\d+)\)/i', $row['Type'], $m) && (int) $m[1] < 255) {
                mysqli_query($conDB, "ALTER TABLE `emp_eos` MODIFY `{$col}` VARCHAR(255) NOT NULL DEFAULT ''");
            }
        }
    }
}

if (!function_exists('eos_reason_lookup')) {
    // Full EN/AR text for a contract type + reason code from data/eos_reasons.json, or null.
    function eos_reason_lookup($contractType, $reasonCode)
    {
        static $reasons = null;
        if ($reasons === null) {
            $reasons = [];
            $path = __DIR__ . '/../data/eos_reasons.json';
            $json = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
            $list = $json['EndOfServiceRewardLookUpRs']['Body']['EndOfServiceRewardLookUp']['ContractEndReason'] ?? [];
            foreach ($list as $r) {
                $reasons[($r['ContractTypeCode'] ?? '') . '|' . ($r['ContractEndReasonCode'] ?? '')] = [
                    'en' => trim($r['EnDescription'] ?? ''),
                    'ar' => trim($r['ArDescription'] ?? ''),
                ];
            }
        }
        return $reasons[$contractType . '|' . $reasonCode] ?? null;
    }
}

if (!function_exists('eos_repair_reason')) {
    // If the stored reason is a cut-off copy of the official text, store and return the full text.
    // $eosrow needs id, contract_type, eos_reason, leaving_reason, leaving_reason_ar.
    function eos_repair_reason($conDB, array &$eosrow)
    {
        if (empty($eosrow['id']) || !isset($eosrow['eos_reason'])) {
            return;
        }
        $full = eos_reason_lookup((string) ($eosrow['contract_type'] ?? ''), (string) $eosrow['eos_reason']);
        if (!$full) {
            return;
        }
        $isCut = function ($stored, $fullText) {
            $stored = (string) $stored;
            return $fullText !== '' && $stored !== $fullText
                && ($stored === '' || mb_strpos($fullText, rtrim($stored)) === 0);
        };
        $fixEn = $isCut($eosrow['leaving_reason'] ?? '', $full['en']);
        $fixAr = $isCut($eosrow['leaving_reason_ar'] ?? '', $full['ar']);
        if (!$fixEn && !$fixAr) {
            return;
        }
        eos_ensure_reason_columns($conDB);
        $en = $fixEn ? $full['en'] : (string) $eosrow['leaving_reason'];
        $ar = $fixAr ? $full['ar'] : (string) $eosrow['leaving_reason_ar'];
        $stmt = $conDB->prepare("UPDATE `emp_eos` SET `leaving_reason` = ?, `leaving_reason_ar` = ? WHERE `id` = ?");
        $id = (int) $eosrow['id'];
        $stmt->bind_param("ssi", $en, $ar, $id);
        $stmt->execute();
        $stmt->close();
        $eosrow['leaving_reason'] = $en;
        $eosrow['leaving_reason_ar'] = $ar;
    }
}
