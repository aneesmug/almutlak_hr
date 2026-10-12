<?php
/**
 * Signed upload links for the smart request QR code (attach_smt_request.php).
 *
 * The QR link carries ?id=<inv_no>&t=<token>, where token = HMAC(inv_no) with a secret kept in
 * app_settings (qr_upload_secret, created on first use). Whoever holds the printed QR can attach
 * files to that one request without logging in; a token cannot be built for another request
 * by guessing its number.
 */

if (!function_exists('smt_qr_secret')) {
    function smt_qr_secret(mysqli $conDB): string
    {
        static $secret = null;
        if ($secret !== null) return $secret;

        $read = function () use ($conDB) {
            $st = mysqli_prepare($conDB, "SELECT `setting_value` FROM `app_settings` WHERE `setting_name` = 'qr_upload_secret' LIMIT 1");
            mysqli_stmt_execute($st);
            $row = mysqli_fetch_row(mysqli_stmt_get_result($st));
            return $row ? (string)$row[0] : '';
        };
        $secret = $read();
        if (strlen($secret) < 32) {
            // INSERT IGNORE + re-read: two first requests at the same time end up with the same secret
            $new = bin2hex(random_bytes(32));
            $st = mysqli_prepare($conDB, "INSERT IGNORE INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`) VALUES ('qr_upload_secret', ?, 'internal', 'Secret for signed QR upload links (changing it invalidates every printed QR code)', 'hidden')");
            mysqli_stmt_bind_param($st, 's', $new);
            mysqli_stmt_execute($st);
            $secret = $read();
        }
        return $secret;
    }

    function smt_qr_token(mysqli $conDB, string $invNo): string
    {
        return substr(hash_hmac('sha256', 'smt-attach|' . $invNo, smt_qr_secret($conDB)), 0, 32);
    }

    function smt_qr_verify(mysqli $conDB, string $invNo, string $token): bool
    {
        return $invNo !== '' && $token !== '' && hash_equals(smt_qr_token($conDB, $invNo), $token);
    }
}
