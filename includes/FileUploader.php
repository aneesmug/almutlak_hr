<?php
/**
 * FileUploader - single place that stores every uploaded file in the app.
 *
 * Why: storing a file with the extension/name the browser sent lets anyone upload
 * "shell.php" into a web folder. Hosting malware scanners (Imunify360, ClamAV...)
 * detect that pattern and delete the script. Every upload must go through here.
 *
 * What it does on every store:
 *   - extension whitelist per profile + global block-list (php, phtml, html, svg, js...)
 *   - rejects double extensions like "photo.php.jpg"
 *   - is_uploaded_file() check, size limit, content check (finfo MIME + "<?php" sniff)
 *   - creates the folder with 0755 and drops an .htaccess that blocks script execution
 *
 * Quick use:
 *   require_once __DIR__ . '/FileUploader.php';
 *
 *   // 1) Full helper: validates + builds a safe name + stores
 *   $up = FileUploader::save($_FILES['attachment'], __DIR__ . '/../assets/loan_receipts/', [
 *       'types'  => 'document',              // profile name or array of extensions
 *       'prefix' => 'disbursement_' . $id . '_',
 *   ]);
 *   if (!$up['ok']) { echo json_encode(['status' => 'error', 'message' => $up['error']]); return; }
 *   $fileName = $up['filename'];  // also: path, ext, original_name, size, mime
 *
 *   // 2) Drop-in replacement for move_uploaded_file() (existing naming code kept)
 *   $ext = FileUploader::safeExt($_FILES['file']['name'], 'image');   // null when not allowed
 *   if (!FileUploader::moveTo($_FILES['file']['tmp_name'], $dest, 'image')) { ... FileUploader::lastError() ... }
 *
 *   // 3) Multiple files input (name="files[]")
 *   foreach (FileUploader::normalize($_FILES['files']) as $file) { FileUploader::save($file, $dir); }
 */

if (!class_exists('FileUploader')) {

final class FileUploader
{
    /** Allowed extensions per profile. */
    const PROFILES = [
        'image'       => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'],
        'pdf'         => ['pdf'],
        'spreadsheet' => ['xlsx', 'xls', 'csv'],
        'document'    => ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
                          'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rtf', 'odt', 'ods'],
        'archive'     => ['zip', 'rar', '7z'],
        'any'         => ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
                          'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'rtf', 'odt', 'ods',
                          'zip', 'rar', '7z', 'ico', 'mp4', 'mp3'],
    ];

    /** Never stored, whatever the profile says. Also checked on inner name segments. */
    const BLOCKED = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar', 'inc',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'asp', 'aspx', 'ascx', 'jsp', 'jspx', 'cfm',
        'exe', 'dll', 'bat', 'cmd', 'com', 'msi', 'vbs', 'ps1', 'jar',
        'js', 'mjs', 'html', 'htm', 'xhtml', 'shtml', 'svg', 'svgz', 'xml', 'hta', 'swf',
        'htaccess', 'htpasswd', 'ini', 'user',
    ];

    /** Script handlers: also rejected as an inner segment ("x.php.jpg" runs as PHP on some Apache setups). */
    const SCRIPT_SEGMENTS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar',
        'cgi', 'pl', 'py', 'asp', 'aspx', 'jsp', 'shtml', 'sh',
    ];

    /** Accepted MIME prefixes per extension (server-side finfo result). Unknown ext = only the php sniff. */
    const MIME_MAP = [
        'jpg'  => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
        'gif'  => ['image/gif'], 'webp' => ['image/webp'], 'bmp' => ['image/bmp', 'image/x-ms-bmp'],
        'ico'  => ['image/vnd.microsoft.icon', 'image/x-icon'],
        'pdf'  => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument', 'application/zip', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument', 'application/zip', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument', 'application/zip', 'application/octet-stream'],
        'doc'  => ['application/msword', 'application/x-ole-storage', 'application/cdfv2', 'application/vnd.ms-office', 'application/octet-stream'],
        'xls'  => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/cdfv2', 'application/vnd.ms-office', 'application/octet-stream'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/x-ole-storage', 'application/cdfv2', 'application/vnd.ms-office', 'application/octet-stream'],
        'odt'  => ['application/vnd.oasis.opendocument', 'application/zip'],
        'ods'  => ['application/vnd.oasis.opendocument', 'application/zip'],
        'csv'  => ['text/', 'application/csv', 'application/vnd.ms-excel'],
        'txt'  => ['text/'],
        'rtf'  => ['text/rtf', 'application/rtf'],
        'zip'  => ['application/zip', 'application/x-zip'],
        'rar'  => ['application/x-rar', 'application/vnd.rar'],
        '7z'   => ['application/x-7z-compressed'],
    ];

    /** Image extensions that must also pass getimagesize(). */
    const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    /** OOXML containers: verified as a real Office package when ZipArchive is available. */
    const OOXML_EXT = ['docx', 'xlsx', 'pptx'];

    const DEFAULT_MAX = 10485760; // 10 MB

    private static $lastError = '';

    public static function lastError()
    {
        return self::$lastError;
    }

    /** Extension list for a profile name or a custom array. */
    public static function allowedList($types)
    {
        if (is_array($types)) {
            return array_values(array_diff(array_map('strtolower', $types), self::BLOCKED));
        }
        return self::PROFILES[$types] ?? self::PROFILES['document'];
    }

    /** Human list for error messages: "PDF, JPG, PNG". */
    public static function allowedLabel($types)
    {
        return strtoupper(implode(', ', self::allowedList($types)));
    }

    /**
     * Safe lowercase extension of an original file name, or null when not allowed.
     * Also null for names like "x.php.jpg" (blocked inner segment).
     */
    public static function safeExt($originalName, $types = 'document')
    {
        $name = strtolower(trim(basename(str_replace('\\', '/', (string)$originalName))));
        $name = rtrim($name, " .");
        $parts = explode('.', $name);
        if (count($parts) < 2) {
            return null;
        }
        $ext = array_pop($parts);
        array_shift($parts); // first part is the real base name
        foreach ($parts as $segment) {
            if (in_array($segment, self::SCRIPT_SEGMENTS, true)) {
                return null;
            }
        }
        if ($ext === '' || in_array($ext, self::BLOCKED, true)) {
            return null;
        }
        return in_array($ext, self::allowedList($types), true) ? $ext : null;
    }

    /**
     * Clean an original name so it can be stored as-is: only [A-Za-z0-9_-], one dot before
     * the extension. Returns null when the extension is not allowed.
     */
    public static function cleanName($originalName, $types = 'document', $maxBase = 80)
    {
        $ext = self::safeExt($originalName, $types);
        if ($ext === null) {
            return null;
        }
        $base = pathinfo(basename(str_replace('\\', '/', (string)$originalName)), PATHINFO_FILENAME);
        $base = preg_replace('/[^A-Za-z0-9_-]+/', '_', $base);
        $base = trim(substr($base, 0, $maxBase), '_');
        if ($base === '') {
            $base = 'file';
        }
        return $base . '.' . $ext;
    }

    /** Turns a multi-file $_FILES entry (name="x[]") into a list of single-file arrays. */
    public static function normalize($field)
    {
        if (!is_array($field) || !isset($field['name'])) {
            return [];
        }
        if (!is_array($field['name'])) {
            return [$field];
        }
        $out = [];
        foreach ($field['name'] as $i => $name) {
            $out[$i] = [
                'name'     => $name,
                'type'     => $field['type'][$i] ?? '',
                'tmp_name' => $field['tmp_name'][$i] ?? '',
                'error'    => $field['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $field['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    /** True when a $_FILES entry holds a successfully uploaded file. */
    public static function has($file)
    {
        return is_array($file) && isset($file['error'], $file['tmp_name'])
            && !is_array($file['error']) && (int)$file['error'] === UPLOAD_ERR_OK
            && $file['tmp_name'] !== '';
    }

    /**
     * Validate a $_FILES entry without storing it (e.g. imports read straight from tmp_name).
     * Returns the safe lowercase extension, or null + lastError().
     */
    public static function validate($file, $types = 'document', $maxBytes = self::DEFAULT_MAX)
    {
        self::$lastError = '';
        if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
            return self::fail('No file was uploaded.') ?: null;
        }
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            return self::fail(self::uploadErrorText((int)$file['error'])) ?: null;
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return self::fail('Invalid upload.') ?: null;
        }
        $size = (int)@filesize($tmp);
        if ($size <= 0) {
            return self::fail('The uploaded file is empty.') ?: null;
        }
        if ($maxBytes > 0 && $size > $maxBytes) {
            return self::fail('File is too large. Maximum size is ' . round($maxBytes / 1048576, 1) . ' MB.') ?: null;
        }
        $ext = self::safeExt($file['name'] ?? '', $types);
        if ($ext === null) {
            return self::fail('File type not allowed. Allowed: ' . self::allowedLabel($types) . '.') ?: null;
        }
        return self::checkContent($tmp, $ext) ? $ext : null;
    }

    /** Non-guessable file name: "<prefix>_<32 hex>.<ext>". */
    public static function uniqueName($prefix, $ext)
    {
        $prefix = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$prefix);
        return ($prefix !== '' ? $prefix . '_' : '') . bin2hex(random_bytes(16)) . '.' . strtolower((string)$ext);
    }

    /**
     * Validate + store a $_FILES entry under $dir with a generated safe name.
     *
     * Options:
     *   types     profile name or extension array (default 'document')
     *   prefix    string put before the generated part (sanitized)
     *   name      exact base name without extension (sanitized); default prefix + time + random
     *   keep_name true = keep the cleaned original name (prefix still applied)
     *   max_size  bytes (default 10 MB)
     *   compress  run compress_stored_file() when available (default false)
     *
     * @return array ok, error, filename, path, ext, original_name, size, mime
     */
    public static function save($file, $dir, array $opts = [])
    {
        $types = $opts['types'] ?? 'document';
        $result = ['ok' => false, 'error' => '', 'filename' => '', 'path' => '', 'ext' => '',
                   'original_name' => '', 'size' => 0, 'mime' => ''];

        if (!is_array($file) || !isset($file['error'])) {
            $result['error'] = 'No file was uploaded.';
            return $result;
        }
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            $result['error'] = self::uploadErrorText((int)$file['error']);
            return $result;
        }

        $original = (string)($file['name'] ?? '');
        $result['original_name'] = basename(str_replace('\\', '/', $original));
        $ext = self::safeExt($original, $types);
        if ($ext === null) {
            $result['error'] = 'File type not allowed. Allowed: ' . self::allowedLabel($types) . '.';
            return $result;
        }

        $prefix = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)($opts['prefix'] ?? ''));
        if (isset($opts['name']) && $opts['name'] !== '') {
            $base = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)$opts['name']);
        } elseif (!empty($opts['keep_name'])) {
            $base = $prefix . pathinfo((string)self::cleanName($original, $types), PATHINFO_FILENAME);
        } else {
            $base = $prefix . date('YmdHis') . '_' . bin2hex(random_bytes(4));
        }
        $filename = $base . '.' . $ext;
        $path = rtrim(str_replace('\\', '/', (string)$dir), '/') . '/' . $filename;

        $opts['max_size'] = $opts['max_size'] ?? self::DEFAULT_MAX;
        if (!self::moveTo($file['tmp_name'], $path, $types, $opts)) {
            $result['error'] = self::$lastError;
            return $result;
        }

        $result['ok'] = true;
        $result['filename'] = $filename;
        $result['path'] = $path;
        $result['ext'] = $ext;
        $result['size'] = (int)@filesize($path);
        $result['mime'] = self::detectMime($path);
        return $result;
    }

    /**
     * Drop-in for move_uploaded_file(): validates the destination extension, the uploaded
     * content and size, prepares the folder, then moves. Returns false + lastError() on failure.
     * Options: max_size (bytes, default 0 = only php.ini limit), compress (bool).
     */
    public static function moveTo($tmpPath, $destPath, $types = 'document', array $opts = [])
    {
        self::$lastError = '';
        $tmpPath = (string)$tmpPath;
        $destPath = (string)$destPath;

        if ($tmpPath === '' || $destPath === '' || !is_uploaded_file($tmpPath)) {
            return self::fail('Invalid upload.');
        }

        $ext = self::safeExt(basename($destPath), $types);
        if ($ext === null) {
            return self::fail('File type not allowed. Allowed: ' . self::allowedLabel($types) . '.');
        }

        $max = (int)($opts['max_size'] ?? 0);
        if ($max > 0 && (int)@filesize($tmpPath) > $max) {
            return self::fail('File is too large. Maximum size is ' . round($max / 1048576, 1) . ' MB.');
        }

        if (!self::checkContent($tmpPath, $ext)) {
            return false;
        }

        $dir = dirname($destPath);
        if (!self::prepareDir($dir)) {
            return self::fail('Upload folder is not writable.');
        }

        if (!@move_uploaded_file($tmpPath, $destPath)) {
            return self::fail('Failed to save the uploaded file.');
        }
        @chmod($destPath, 0644);

        if (!empty($opts['compress']) && function_exists('compress_stored_file')) {
            compress_stored_file($destPath);
        }
        return true;
    }

    /**
     * Move an upload into a private folder OUTSIDE the web app (e.g. chunk parts in sys_get_temp_dir()).
     * No type check, because parts are not real files yet - run verifyStored() on the assembled file.
     */
    public static function moveTemp($tmpPath, $destPath)
    {
        self::$lastError = '';
        if ((string)$tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return self::fail('Invalid upload.');
        }
        $dir = dirname((string)$destPath);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            return self::fail('Temp folder is not writable.');
        }
        if (self::isInsideAppRoot($dir)) {
            return self::fail('Temp uploads must be stored outside the web folder.');
        }
        return @move_uploaded_file($tmpPath, $destPath) ? true : self::fail('Failed to save the uploaded part.');
    }

    /**
     * Validate a file that was built on the server (e.g. assembled chunks) and guard its folder.
     * Deletes the file and returns false when it is not acceptable.
     */
    public static function verifyStored($path, $types = 'document')
    {
        self::$lastError = '';
        $ext = self::safeExt(basename((string)$path), $types);
        if ($ext === null) {
            @unlink($path);
            return self::fail('File type not allowed. Allowed: ' . self::allowedLabel($types) . '.');
        }
        if (!self::checkContent($path, $ext)) {
            @unlink($path);
            return false;
        }
        self::prepareDir(dirname($path));
        return true;
    }

    /** Create the folder (0755) and drop an .htaccess blocking script execution inside it. */
    public static function prepareDir($dir)
    {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }
        $guard = rtrim($dir, '/\\') . '/.htaccess';
        if (!file_exists($guard) && self::isInsideAppRoot($dir)) {
            @file_put_contents($guard, self::htaccessRules());
        }
        return is_writable($dir);
    }

    // ------------------------------------------------------------------ internals

    private static function checkContent($path, $ext)
    {
        $mime = strtolower(self::detectMime($path));
        if ($mime !== '' && isset(self::MIME_MAP[$ext])) {
            $ok = false;
            foreach (self::MIME_MAP[$ext] as $expected) {
                if (strpos($mime, $expected) === 0) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                return self::fail('File content does not match its type (.' . $ext . ').');
            }
        }

        if (in_array($ext, self::IMAGE_EXT, true) && @getimagesize($path) === false) {
            return self::fail('The image file is damaged or not a real image.');
        }

        if (in_array($ext, self::OOXML_EXT, true) && class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            $isOffice = false;
            if ($zip->open($path) === true) {
                $isOffice = ($zip->locateName('[Content_Types].xml') !== false);
                $zip->close();
            }
            if (!$isOffice) {
                return self::fail('The file is not a valid Office document (.' . $ext . ').');
            }
        }

        // Look for embedded PHP in the first 2 MB (image/PDF polyglots).
        $fh = @fopen($path, 'rb');
        if ($fh) {
            $chunk = (string)fread($fh, 2097152);
            fclose($fh);
            if (stripos($chunk, '<?php') !== false || preg_match('/<script\b[^>]*language\s*=\s*["\']?php/i', $chunk)) {
                return self::fail('File content is not allowed.');
            }
        }
        return true;
    }

    private static function detectMime($path)
    {
        if (!function_exists('finfo_open') || !is_file($path)) {
            return '';
        }
        $fi = @finfo_open(FILEINFO_MIME_TYPE);
        if (!$fi) {
            return '';
        }
        $mime = (string)@finfo_file($fi, $path);
        finfo_close($fi);
        return $mime;
    }

    private static function isInsideAppRoot($dir)
    {
        $root = realpath(dirname(__DIR__));
        $real = realpath($dir);
        if ($root === false || $real === false) {
            return false;
        }
        return stripos(str_replace('\\', '/', $real), str_replace('\\', '/', $root)) === 0;
    }

    private static function htaccessRules()
    {
        $ext = 'php\d*|phtml|pht|phps|phar|inc|cgi|pl|py|rb|sh|asp|aspx|jsp|shtml|html?|svgz?|js|hta';
        return "# Added by FileUploader: uploads may never run as scripts\n"
            . "<FilesMatch \"(?i)\\.($ext)$\">\n"
            . "  <IfModule mod_authz_core.c>\n    Require all denied\n  </IfModule>\n"
            . "  <IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n  </IfModule>\n"
            . "</FilesMatch>\n"
            . "<IfModule mod_headers.c>\n  Header set X-Content-Type-Options \"nosniff\"\n</IfModule>\n";
    }

    private static function uploadErrorText($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE: return 'File is too large.';
            case UPLOAD_ERR_PARTIAL:   return 'File was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:   return 'No file was uploaded.';
            default:                   return 'File upload failed.';
        }
    }

    private static function fail($message)
    {
        self::$lastError = $message;
        return false;
    }
}

}
