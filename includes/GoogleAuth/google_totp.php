<?php
/********************************************************************************
 * Google Authenticator - TOTP (RFC 6238) codes, secret encryption and QR code.
 * Used by google_auth.php. No database access here.
 * - Secrets are encrypted with AES-256-GCM; the key lives in gauth_secret_key.php in this
 *   folder (created on first use, not in git). Back it up: if it is lost, every user has to
 *   scan a new QR code.
 * - The QR code is drawn locally with TCPDF, so the secret never leaves the server.
 ********************************************************************************/

if (!function_exists('gauth_key')) {

    define('GAUTH_PERIOD', 30);
    define('GAUTH_DIGITS', 6);
    define('GAUTH_WINDOW', 1);          // accept the previous / next 30s code too (clock drift)

    // ---------------------------------------------------------------- secret storage

    /** 32-byte key from includes/GoogleAuth/gauth_secret_key.php (created on first use, not in git). */
    function gauth_key()
    {
        static $key = null;
        if ($key !== null) {
            return $key;
        }
        $file = __DIR__ . '/gauth_secret_key.php';
        if (!is_file($file)) {
            $raw = base64_encode(random_bytes(32));
            $php = "<?php\n// Google Authenticator secret encryption key - generated automatically.\n"
                . "// BACK THIS FILE UP. If it is lost, every user must scan a new QR code.\n"
                . "// Never commit it or share it.\nreturn '" . $raw . "';\n";
            if (@file_put_contents($file, $php, LOCK_EX) === false) {
                throw new RuntimeException('Google Authenticator cannot create its key file (includes/GoogleAuth/gauth_secret_key.php). Please check folder permissions.');
            }
        }
        $decoded = base64_decode((string) (include $file), true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException('Google Authenticator key file is invalid.');
        }
        return $key = $decoded;
    }

    function gauth_encrypt($plain)
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', gauth_key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new RuntimeException('Could not encrypt the authenticator secret.');
        }
        return 'v1:' . base64_encode($iv . $tag . $cipher);
    }

    /** Returns the plain base32 secret, or null when missing / unreadable. */
    function gauth_decrypt($stored)
    {
        $stored = (string) $stored;
        if (strpos($stored, 'v1:') !== 0) {
            return null;
        }
        $bin = base64_decode(substr($stored, 3), true);
        if ($bin === false || strlen($bin) < 29) {
            return null;
        }
        try {
            $plain = openssl_decrypt(substr($bin, 28), 'aes-256-gcm', gauth_key(), OPENSSL_RAW_DATA, substr($bin, 0, 12), substr($bin, 12, 16));
        } catch (RuntimeException $e) {
            error_log('[GAUTH] ' . $e->getMessage());
            return null;
        }
        return $plain === false ? null : $plain;
    }

    // ---------------------------------------------------------------- TOTP

    function gauth_base32_encode($bytes)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($bytes) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }
        return $out;
    }

    function gauth_base32_decode($text)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $text = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', (string) $text));
        $bits = '';
        foreach (str_split($text) as $c) {
            $bits .= str_pad(decbin(strpos($alphabet, $c)), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $out .= chr(bindec($chunk));
            }
        }
        return $out;
    }

    function gauth_new_secret()
    {
        return gauth_base32_encode(random_bytes(20)); // 160-bit, 32 base32 chars
    }

    function gauth_code_at($secret, $step)
    {
        $hash = hash_hmac('sha1', pack('J', $step), gauth_base32_decode($secret), true);
        $offset = ord($hash[19]) & 0x0f;
        $value = ((ord($hash[$offset]) & 0x7f) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);
        return str_pad((string) ($value % (10 ** GAUTH_DIGITS)), GAUTH_DIGITS, '0', STR_PAD_LEFT);
    }

    /** Returns the matching time step, or null. */
    function gauth_match_step($secret, $code)
    {
        $code = preg_replace('/\D/', '', (string) $code);
        if (strlen($code) !== GAUTH_DIGITS || $secret === '') {
            return null;
        }
        $now = intdiv(time(), GAUTH_PERIOD);
        for ($i = -GAUTH_WINDOW; $i <= GAUTH_WINDOW; $i++) {
            if (hash_equals(gauth_code_at($secret, $now + $i), $code)) {
                return $now + $i;
            }
        }
        return null;
    }

    function gauth_otpauth_uri($conDB, $secret, $accountLabel)
    {
        $issuer = gauth_settings($conDB)['issuer'];
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $accountLabel)
            . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . GAUTH_DIGITS . '&period=' . GAUTH_PERIOD;
    }

    /** QR code as inline SVG, generated locally (the secret never leaves the server). */
    function gauth_qr_svg($uri)
    {
        require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php';
        $qr = new TCPDF2DBarcode($uri, 'QRCODE,M');
        $svg = $qr->getBarcodeSVGcode(5, 5, '#000');
        $svg = substr($svg, strpos($svg, '<svg'));
        $svg = preg_replace('/<svg width="([\d.]+)" height="([\d.]+)"/', '<svg viewBox="0 0 $1 $2" width="$1" height="$2" shape-rendering="crispEdges"', $svg, 1);
        return preg_replace('/<desc>.*?<\/desc>/s', '', $svg); // the desc holds the secret in plain text
    }
}
