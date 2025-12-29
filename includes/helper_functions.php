<?php

// Ensure PHP error logging is configured so we can capture exceptions from this module
try {
    $errorLogDir = __DIR__ . '/../../logs';
    if (!is_dir($errorLogDir)) {
        @mkdir($errorLogDir, 0755, true);
    }
    @ini_set('log_errors', '1');
    @ini_set('error_log', $errorLogDir . '/php_error.log');
} catch (\Throwable $e) {
    // Non-fatal: continue without changing error_log if filesystem prevents it
}

// --- PHPMailer ---
// Try loading PHPMailer from includes/vendor first, then system/vendor
$mailerAutoloadPaths = [
    __DIR__ . '/vendor/autoload.php',      // includes/vendor (has PHPMailer)
    __DIR__ . '/../vendor/autoload.php'     // system/vendor (backup)
];

$phpmailerLoaded = false;
foreach ($mailerAutoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $phpmailerLoaded = true;
        break;
    }
}

if (!$phpmailerLoaded) {
}
// --- End PHPMailer ---

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$formatter = new NumberFormatter('en_SA',  NumberFormatter::CURRENCY);

/**
 * Escapes special characters in a string for use in an SQL statement, using mysqli_real_escape_string if available.
 * Handles arrays recursively. Provides a basic fallback if DB connection is unavailable.
 *
 * @param mixed $param The string or array to escape.
 * @return mixed The escaped string or array, or the original variable if not a string or array.
 */
if (!function_exists('escape_string')) {
    function escape_string($param)
    {
        global $conDB; // Ensure connection is available
        if (!$conDB) {
            // Fallback basic escaping
            if (is_array($param)) return array_map(__FUNCTION__, $param); // Use __FUNCTION__ for recursion
            if (!empty($param) && is_string($param)) return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $param);
            return $param;
        }

        if (is_array($param)) {
            // Recursively apply escape_string to array elements
            // Using array_map is generally preferred over __METHOD__ for non-class contexts
            return array_map('escape_string', $param);
        }

        if (!empty($param) && is_string($param)) {
            return mysqli_real_escape_string($conDB, $param);
        }
        return $param;
    }
}


// --- Time Ago Functions ---
if (!function_exists('timeAgo')) {
    function timeAgo($time_ago)
    {
        $time_ago = strtotime($time_ago);
        if ($time_ago === false) return "Invalid date"; // Handle invalid date input
        $cur_time   = time();
        $time_elapsed   = $cur_time - $time_ago;
        $seconds    = $time_elapsed;
        $minutes    = round($time_elapsed / 60);
        $hours      = round($time_elapsed / 3600);
        $days       = round($time_elapsed / 86400);
        $weeks      = round($time_elapsed / 604800);
        $months     = round($time_elapsed / 2600640);
        $years      = round($time_elapsed / 31207680);
        // Seconds
        if ($seconds <= 60) {
            return "just now";
        }
        //Minutes
        else if ($minutes <= 60) {
            return ($minutes == 1) ? "one minute ago" : "$minutes minutes ago";
        }
        //Hours
        else if ($hours <= 24) {
            return ($hours == 1) ? "an hour ago" : "$hours hrs ago";
        }
        //Days
        else if ($days <= 7) {
            return ($days == 1) ? "yesterday" : "$days days ago";
        }
        //Weeks
        else if ($weeks <= 4.3) {
            return ($weeks == 1) ? "a week ago" : "$weeks weeks ago";
        }
        //Months
        else if ($months <= 12) {
            return ($months == 1) ? "a month ago" : "$months months ago";
        }
        //Years
        else {
            return ($years == 1) ? "one year ago" : "$years years ago";
        }
    }
}
if (!function_exists('timeAgoAr')) {
    function timeAgoAr($time_ago)
    {
        $time_ago = strtotime($time_ago);
        if ($time_ago === false) return "تاريخ غير صالح"; // Handle invalid date input
        $cur_time   = time();
        $time_elapsed   = $cur_time - $time_ago;
        $seconds    = $time_elapsed;
        $minutes    = round($time_elapsed / 60);
        $hours      = round($time_elapsed / 3600);
        $days       = round($time_elapsed / 86400);
        $weeks      = round($time_elapsed / 604800);
        $months     = round($time_elapsed / 2600640);
        $years      = round($time_elapsed / 31207680);
        // Seconds
        if ($seconds <= 60) {
            return "الآن";
        }
        //Minutes
        else if ($minutes <= 60) {
            return ($minutes == 1) ? "قبل دقيقة واحدة" : "$minutes دقائق مضت";
        }
        //Hours
        else if ($hours <= 24) {
            return ($hours == 1) ? "قبل ساعة" : "$hours قبل ساعات";
        }
        //Days
        else if ($days <= 7) {
            return ($days == 1) ? "أمس" : "$days قبل أيام";
        }
        //Weeks
        else if ($weeks <= 4.3) {
            return ($weeks == 1) ? "قبل أسبوع" : "$weeks قبل أسابيع";
        }
        //Months
        else if ($months <= 12) {
            return ($months == 1) ? "قبل شهر" : "$months قبل شهور";
        }
        //Years
        else {
            return ($years == 1) ? "قبل عام" : "$years منذ سنوات";
        }
    }
}

// --- String & Number Utilities ---
// Expands a string to a specified number of characters without cutting words, appending a separator if truncated.
// ex: split_words("This is a test string", 10, '...') => "This is..."
if (!function_exists('split_words')) {
    function split_words($string, $nb_caracs, $separator)
    {
        $string = strip_tags(html_entity_decode($string));
        if (mb_strlen($string) <= $nb_caracs) { // Use mb_strlen for multi-byte strings
            $final_string = $string;
        } else {
            $final_string = "";
            $words = explode(" ", $string);
            foreach ($words as $value) {
                if (mb_strlen($final_string . " " . $value) < $nb_caracs) {
                    if (!empty($final_string)) $final_string .= " ";
                    $final_string .= $value;
                } else {
                    break;
                }
            }
            $final_string .= $separator;
        }
        return $final_string;
    }
}

if (!function_exists('number_pad')) {
    function number_pad($number, $n)
    {
        return str_pad((int) $number, $n, "0", STR_PAD_LEFT);
    }
}

// --- Date & Time Utilities ---
if (!function_exists('dateDiffDays')) {
    function dateDiffDays($startDate, $endDate)
    {
        try {
            $date1 = new DateTime($startDate);
            $date2 = new DateTime($endDate);
            $diff = $date1->diff($date2);
            // Return a formatted string or the DateInterval object
            // return $diff->format('%y years, %m months, %d days'); // Example format
            return $diff->days; // Just total days
        } catch (Exception $e) {
            return "Error calculating difference";
        }
    }
}

if (!function_exists('getTotalDays')) {
    function getTotalDays($years, $months, $days)
    {
        // Simplified, assumes average month length which might not be accurate for precise calculations
        // For exact day counts between dates, use dateDiffDays or DateTime::diff directly.
        $result = ($years * 360) + ($months * 30) + $days;
        return $result;
    }
}

// --- Financial Calculations ---
if (!function_exists('endOfService')) {
    function endOfService($joinDate, $endDate, $salary)
    {
        try {
            $date1 = new DateTime($joinDate);
            $date2 = new DateTime($endDate);
        } catch (Exception $e) {
            return 0; // Or throw exception
        }

        if ($date1 > $date2) {
            // Swap dates if join date is after end date (unlikely but possible input error)
            list($date1, $date2) = [$date2, $date1];
        }

        $diff = $date1->diff($date2);
        // Use precise day count for calculation
        $totalDaysService = $diff->days;

        // Convert salary to float, handle potential errors
        $salaryFloat = filter_var($salary, FILTER_VALIDATE_FLOAT);
        if ($salaryFloat === false || $salaryFloat <= 0) {
            return 0; // Return 0 or throw an exception
        }

        // --- Revised Calculation Logic based on Saudi Labor Law Interpretation ---
        // Note: This is a common interpretation. Consult legal advice for definitive calculations.
        // Assumes a year has 365 days for EOS calculation.
        $daysInYear = 365;
        $result = 0;

        // First 5 years (up to 1825 days): Half month salary per year
        $firstPeriodDays = min($totalDaysService, 5 * $daysInYear);
        if ($firstPeriodDays > 0) {
            $result += ($salaryFloat / 2) * ($firstPeriodDays / $daysInYear);
        }

        // Years after 5 (days exceeding 1825): Full month salary per year
        $secondPeriodDays = max(0, $totalDaysService - (5 * $daysInYear));
        if ($secondPeriodDays > 0) {
            $result += $salaryFloat * ($secondPeriodDays / $daysInYear);
        }

        return round($result, 2); // Return final amount rounded
    }
}

// --- Debugging Utilities ---
if (!function_exists('debug')) {
    function debug($data, $die = true)
    {
        echo '<pre style="background: #1e1e1e; color: #f0f0f0; padding: 10px; border-radius: 4px; text-align: left; font-family: monospace; font-size: 12px; z-index: 9999; position: relative;">';
        echo "<strong>DEBUG OUTPUT:</strong>\n";
        if (is_bool($data) || is_null($data)) {
            var_dump($data); // Better for booleans & NULL
        } else {
            print_r($data); // Cleaner for arrays/objects
        }
        echo "\n\n<strong>BACKTRACE (Limited):</strong>\n";
        // Limit backtrace for brevity, showing file and line
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        foreach ($trace as $item) {
            echo basename($item['file'] ?? '?') . ':' . ($item['line'] ?? '?') . ' - ' . ($item['function'] ?? '?') . "()\n";
        }
        echo '</pre>';
        if ($die) die(); // Optional: Stop execution
    }
}

if (!function_exists('dd')) {
    function dd($data) // Dump and Die
    {
        debug($data, true);
    }
}

if (!function_exists('console_log')) {
    function console_log($data)
    {
        // Basic type handling for console output
        $output = json_encode($data);
        if ($output === false) {
            // Handle json encoding failure (e.g., non-UTF8 data, recursion)
            $output = "'PHP console_log: Error encoding data. Type: " . gettype($data) . "'";
        }
        echo '<script>';
        // console.log disabled (PHP injected)
        // echo 'console.log("PHP DEBUG:", ' . $output . ');';
        echo '</script>';
    }
}

// --- Navigation & Response Utilities ---
/**
 * Redirect or refresh the page with optional delay and status messages.
 * @param string $path URL to redirect to (empty = refresh current page)
 * @param int $delay Delay in seconds (0 = immediate)
 * @param bool $exit Terminate script after redirect? (Default: true)
 * @param string $message Custom message to display during delay
 * @return void
 */
if (!function_exists('redirect')) {
    function redirect($path = "", $delay = 0, $exit = true, $message = "")
    {
        // Prevent header modification errors if output already started
        if (headers_sent($file, $line)) {
            // Force meta refresh if headers are sent
            $delay = max($delay, 0); // Ensure delay is not negative
        }

        $url = ($path !== "") ? $path : $_SERVER['REQUEST_URI'];
        // Basic validation/sanitization for URL
        $url = filter_var($url, FILTER_SANITIZE_URL);

        // Immediate redirect (if headers not sent)
        if (!headers_sent() && $delay === 0) {
            header("Location: " . $url);
            if ($exit) exit();
            return;
        }

        // Delayed redirect or fallback (HTML + meta refresh)
        $delay = (int)$delay; // Ensure delay is an integer
        $defaultMessage = ($delay > 0)
            ? "Redirecting in <span id='countdown'>$delay</span> seconds..."
            : "Redirecting...";
        $finalMessage = ($message !== "") ? htmlspecialchars($message) : $defaultMessage; // Sanitize message

        echo <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="refresh" content="$delay;url=$url">
        <title>Redirecting...</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background-color: #f4f4f4; color: #333; }
            p { font-size: 1.1em; }
            #countdown { font-weight: bold; color: #007bff; }
        </style>
    </head>
    <body>
        <p>$finalMessage</p>
        <p><a href="$url">Click here if you are not redirected automatically.</a></p>
        <script>
            if ($delay > 0) {
                var timeLeft = $delay;
                var countdownElem = document.getElementById('countdown');
                if (countdownElem) {
                    var countdown = setInterval(function() {
                        timeLeft--;
                        countdownElem.textContent = timeLeft;
                        if (timeLeft <= 0) clearInterval(countdown);
                    }, 1000);
                }
            }
        </script>
    </body>
    </html>
HTML;

        if ($exit) exit();
    }
}


if (!function_exists('salert')) {
    function salert($title, $message, $type = 'success', $redirectUrl = "", $btn = 'OK')
    {
        // Sanitize output for security
        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $type = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        $redirectUrl = filter_var($redirectUrl, FILTER_SANITIZE_URL);
        $btn = htmlspecialchars($btn, ENT_QUOTES, 'UTF-8');

        // Basic validation for type
        $validTypes = ['success', 'error', 'warning', 'info', 'question'];
        if (!in_array($type, $validTypes)) {
            $type = 'info'; // Default to info if invalid type provided
        }

        echo <<<HTML
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <body class="enlarged" data-keep-enlarged="true" style="display: none;"> <!-- Hide body content -->
        <script>
            document.addEventListener('DOMContentLoaded', function() { // Wait for DOM
                Swal.fire({
                    title: "$title",
                    text: "$message",
                    icon: "$type",
                    allowOutsideClick: false, // Prevent dismissal by clicking outside
                    confirmButtonText: "$btn",
                    customClass: {
                        confirmButton: 'btn btn-lg btn-primary' // Bootstrap class
                    },
                    buttonsStyling: false,
                }).then((result) => {
                    if (result.isConfirmed && "$redirectUrl" !== "") {
                        window.location.href = "$redirectUrl";
                    } else if (result.isConfirmed) {
                        // Optional: Go back if no redirect URL is provided
                        // if (window.history.length > 1) { window.history.back(); }
                    }
                });
            });
        </script>
        </body>
    HTML;
        exit(); // Stop further script execution
    }
}

// --- Input Sanitization ---
if (!function_exists('sanitize_input')) {
    function sanitize_input($data)
    {
        if (is_array($data)) {
            return array_map('sanitize_input', $data);
        }
        $data = trim($data);
        $data = stripslashes($data); // Use with caution if magic quotes are off
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

// --- JSON Response Helper ---
if (!function_exists('send_json_response')) {
    function send_json_response($title, $message, $type, $http_status_code = 200, $additional_data = [])
    {
        // Prevent potential errors if headers already sent
        $response = ['title' => $title, 'message' => $message, 'type' => $type];

        // Merge additional data
        if (!empty($additional_data) && is_array($additional_data)) {
            $response = array_merge($response, $additional_data);
        }

        if (headers_sent($file, $line)) {
            // Still try to output JSON, but status code might be wrong
            echo json_encode($response);
        } else {
            http_response_code($http_status_code);
            header('Content-Type: application/json; charset=utf-8'); // Ensure charset
            echo json_encode($response);
        }
        exit(); // Terminate script after sending JSON response
    }
}

// --- PDO Debug Helper ---
if (!function_exists('debugPDO')) {
    function debugPDO($stmt, $params = [])
    {
        // Ensure PDOStatement object is passed
        if (!$stmt instanceof PDOStatement) {
            return "Error: Invalid PDOStatement object provided.";
        }

        $query = $stmt->queryString;
        $interpolatedQuery = $query;

        // Use built-in debugDumpParams for more accurate representation
        ob_start();
        $stmt->debugDumpParams();
        $debugInfo = ob_get_clean();

        // Crude interpolation for display (less accurate than debugDumpParams)
        foreach ($params as $param => $value) {
            if (is_string($value)) {
                // Basic quoting, might need refinement for complex strings
                $value = "'" . addslashes($value) . "'";
            } elseif (is_null($value)) {
                $value = 'NULL';
            } elseif (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            // Replace named or positional placeholders
            if (is_int($param)) { // Positional (?)
                $interpolatedQuery = preg_replace('/\?/', $value, $interpolatedQuery, 1);
            } else { // Named (:)
                $interpolatedQuery = str_replace($param, $value, $interpolatedQuery);
            }
        }

        return "<pre style='background: #1e1e1e; color: #f0f0f0; padding: 10px; border-radius: 4px; font-family: monospace;'>"
            . "<strong>Original Query:</strong>\n" . htmlspecialchars($query) . "\n\n"
            . "<strong>Crude Interpolated Query (for display only):</strong>\n" . htmlspecialchars($interpolatedQuery) . "\n\n"
            . "<strong>PDO debugDumpParams():</strong>\n" . htmlspecialchars($debugInfo)
            . "</pre>";
    }
}

// --- Name Parsing Utility ---
if (!function_exists('parseName')) {
    function parseName($fullName, $format = 'FIRST_LAST')
    {
        if (empty(trim($fullName))) return ''; // Handle empty input

        $parts = array_values(array_filter(explode(' ', trim($fullName))));
        $count = count($parts);

        $firstName = $parts[0] ?? '';
        $lastName = $count > 1 ? end($parts) : '';
        $middleName = '';
        if ($count > 2) {
            $middleName = implode(' ', array_slice($parts, 1, -1));
        }

        // Determine components based on the available parts
        $components = [
            'FIRST' => $firstName,
            'LAST' => $lastName,
            'MIDDLE' => $middleName,
            'SECOND' => $parts[1] ?? '', // Usually the second name part
            // Grandfather name interpretation depends on structure, assuming it's the one before last if > 3 parts
            'GRANDFATHER' => ($count > 3) ? $parts[$count - 2] : '',
        ];


        $requested = explode('_', strtoupper($format)); // Ensure format is uppercase
        $result = [];

        foreach ($requested as $component) {
            if (isset($components[$component]) && !empty($components[$component])) {
                $result[] = $components[$component];
            }
        }

        // Avoid duplicate names if format requests overlapping parts (e.g., FIRST_SECOND_MIDDLE_LAST with 3 names)
        return implode(' ', array_unique(array_filter($result)));
    }
}

// --- Search Highlighting ---
if (!function_exists('highlightKeywords')) {
    function highlightKeywords($text, $search)
    {
        if (empty(trim($search)) || empty($text)) return $text; // No search term or text

        // Escape special regex characters in search terms
        $wordsAry = preg_split('/\s+/', trim($search)); // Split by any whitespace
        $wordsCount = count($wordsAry);

        for ($i = 0; $i < $wordsCount; $i++) {
            $keyword = trim($wordsAry[$i]);
            if (empty($keyword)) continue; // Skip empty strings after split

            $pattern = '/' . preg_quote($keyword, '/') . '/i'; // Case-insensitive search, properly escaped
            $highlighted_text = "<span class='search-highlight'>$0</span>"; // Use $0 to replace with the exact match (preserves case)

            // Use preg_replace for regex-based replacement
            $text = preg_replace($pattern, $highlighted_text, $text);
        }
        return $text;
    }
}

// --- Formatting Utilities ---
if (!function_exists('formatPeriod')) {
    function formatPeriod($periodString)
    {
        // Example input: "1 year - 2023-10-29"
        $parts = explode(' - ', $periodString, 2); // Limit split to 2 parts
        if (count($parts) < 1) return $periodString; // Return original if format is unexpected

        $durationPart = $parts[0]; // "1 year"
        $datePart = $parts[1] ?? ''; // "2023-10-29", might be empty

        // Split the duration part
        $durationParts = explode(' ', $durationPart, 2);
        $number = $durationParts[0] ?? '';
        $unit = strtolower(trim($durationParts[1] ?? ''));

        // Translate the unit using the __() function (ensure it handles plurals or singular)
        // Assume __() can handle 'year', 'month', 'day' etc.
        $translatedUnit = __($unit); // Translate the unit

        // Reconstruct the string
        $formattedString = $number . " " . $translatedUnit;
        if (!empty($datePart)) {
            $formattedString .= " - " . $datePart; // Add the date back if it exists
        }

        return $formattedString;
    }
}

// --- Age Calculation ---
if (!function_exists('ageDOB')) {
    function ageDOB($dob)
    {
        try {
            $birthDate = new DateTime($dob);
            $today = new DateTime('today');

            // Check if birth date is in the future
            if ($birthDate > $today) {
                return __('invalid_date_of_birth');
            }

            $age = $birthDate->diff($today);

            // Format the output using translated terms
            return sprintf(
                "%s <b>%d</b> %s <b>%d</b> %s <b>%d</b>",
                __('years'),
                $age->y,
                __('months'),
                $age->m,
                __('days'),
                $age->d
            );
        } catch (Exception $e) {
            return __('invalid_date_format');
        }
    }
}

// --- Pagination ---
/**
 * Generates a full set of pagination controls with detailed item counts.
 * [Existing Docblock]...
 */
if (!function_exists('generate_pagination_controls')) {
    function generate_pagination_controls($current_page, $total_pages, $total_items, $items_per_page, $limit_options, $show_all, $base_params = [], $unfiltered_total_items = null)
    {
        // Basic validation
        if (!is_numeric($current_page) || !is_numeric($total_pages) || !is_numeric($total_items) || !is_numeric($items_per_page) || !is_array($limit_options) || !is_bool($show_all)) {
            return '<!-- Pagination Error: Invalid arguments. -->';
        }

        $current_page = max(1, (int)$current_page); // Ensure current page is at least 1
        $total_pages = max(0, (int)$total_pages);   // Ensure non-negative
        $total_items = max(0, (int)$total_items);   // Ensure non-negative
        $items_per_page = max(1, (int)$items_per_page); // Ensure at least 1 item per page if not showing all
        $unfiltered_total_items = ($unfiltered_total_items !== null) ? max(0, (int)$unfiltered_total_items) : null;

        // No controls needed if only one page and not enough items to warrant dropdown
        if ($total_pages <= 1 && $total_items <= min($limit_options)) {
            // Optionally show item count even on single page
            if ($total_items > 0) {
                $single_page_text = __('showing') . " 1 " . __('to') . " {$total_items} " . __('of') . " {$total_items} " . __('entries');
                if ($unfiltered_total_items !== null && $unfiltered_total_items > $total_items) {
                    $single_page_text .= " (" . __('filtered_from') . " {$unfiltered_total_items} " . __('entries') . ")";
                }
                return "<div class='row mt-4' style=\"margin-top: 5rem !important;\"><div class='col-12 text-muted'>{$single_page_text}</div></div>";
            }
            return ''; // Return empty if no items
        }

        $html = '<div class="row mt-4"><div class="col-12 d-md-flex justify-content-between align-items-center">';

        // --- Items per page dropdown ---
        $html .= '<div class="mb-3 mb-md-0">';
        $html .= '<div class="form-inline">';
        $html .= '<label for="limitFilter" class="mr-2 font-weight-bold">' . __('show') . ':</label>';
        $html .= '<select class="form-control form-control-sm" id="limitFilter" onchange="applyFilters()">';
        foreach ($limit_options as $limit) {
            $limit = (int)$limit;
            if ($limit <= 0) continue; // Skip invalid limit options
            $selected = (!$show_all && $items_per_page == $limit) ? 'selected' : '';
            $html .= "<option value='{$limit}' {$selected}>{$limit}</option>";
        }
        $all_selected = $show_all ? 'selected' : '';
        $html .= "<option value='all' {$all_selected}>" . __('all_option') . "</option>";
        $html .= "</select><span class='ml-2 text-muted'>" . __('items_per_page') . "</span>";
        $html .= "</div></div>";

        // --- Page info and navigation ---
        $html .= "<div class='d-flex align-items-center justify-content-center flex-wrap'>";

        // Displaying start and end item numbers and total items
        if ($total_items > 0) {
            $showing_text = '';
            if (!$show_all && $items_per_page > 0 && $total_pages > 0) {
                $start_item = (($current_page - 1) * $items_per_page) + 1;
                $end_item = min($start_item + $items_per_page - 1, $total_items);
                $showing_text = "" . __('showing') . " {$start_item} " . __('to') . " {$end_item} " . __('of') . " {$total_items} " . __('entries') . "";
            } elseif ($show_all || $total_pages == 1) { // Also show for single page result
                $showing_text = "" . __('showing') . " 1 " . __('to') . " {$total_items} " . __('of') . " {$total_items} " . __('entries') . "";
                if ($show_all) $showing_text = "" . __('showing_all') . " {$total_items} " . __('entries') . "";
            }

            if (!empty($showing_text) && $unfiltered_total_items !== null && $unfiltered_total_items > $total_items) {
                $showing_text .= " (" . __('filtered_from') . " {$unfiltered_total_items} " . __('entries') . ")";
            }

            if (!empty($showing_text)) {
                $html .= "<span class='text-muted mr-3'>{$showing_text}</span>";
            }
        } elseif ($unfiltered_total_items !== null && $unfiltered_total_items > 0) {
            // Show only the filtered message if total_items is 0 but unfiltered_total_items > 0
            $html .= "<span class='text-muted mr-3'>" . __('showing') . " 0 " . __('entries') . " (" . __('filtered_from') . " {$unfiltered_total_items} " . __('entries') . ")</span>";
        }


        // Build page links only if there are multiple pages and not showing all
        if ($total_pages > 1 && !$show_all) {
            $html .= '<nav aria-label="Page navigation"><ul class="pagination mb-0">';

            // Helper function to build query string safely
            $build_query = function ($page) use ($base_params) {
                return "?" . http_build_query(array_merge($base_params, ['page' => $page]));
            };

            $first_disabled = ($current_page <= 1) ? 'disabled' : '';
            $html .= "<li class='page-item {$first_disabled}'><a class='page-link' href='{$build_query(1)}'>" . __('first') . "</a></li>";

            $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
            $html .= "<li class='page-item {$prev_disabled}'><a class='page-link' href='{$build_query($current_page - 1)}'>" . __('previous') . "</a></li>";

            $range = 2; // Number of links around the current page
            $start_range = max(1, $current_page - $range);
            $end_range = min($total_pages, $current_page + $range);

            if ($start_range > 1) {
                $html .= "<li class='page-item'><a class='page-link' href='{$build_query(1)}'>1</a></li>";
                if ($start_range > 2) {
                    $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                }
            }

            for ($i = $start_range; $i <= $end_range; $i++) {
                $active_class = ($current_page == $i) ? 'active' : '';
                $html .= "<li class='page-item {$active_class}'><a class='page-link' href='{$build_query($i)}'>{$i}</a></li>";
            }

            if ($end_range < $total_pages) {
                if ($end_range < $total_pages - 1) {
                    $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                }
                $html .= "<li class='page-item'><a class='page-link' href='{$build_query($total_pages)}'>{$total_pages}</a></li>";
            }

            $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
            $html .= "<li class='page-item {$next_disabled}'><a class='page-link' href='{$build_query($current_page + 1)}'>" . __('next') . "</a></li>";

            $last_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
            $html .= "<li class='page-item {$last_disabled}'><a class='page-link' href='{$build_query($total_pages)}'>" . __('last') . "</a></li>";

            $html .= '</ul></nav>';
        }

        $html .= '</div>'; // End d-flex
        $html .= '</div></div>'; // End row and outer div
        return $html;
    }
}


/*=============================================
=            Approval System Functions        =
=============================================*/

/**
 * Fetches all active employees suitable for being approvers.
 * Excludes 'employee' user_type AND 'Supporter' emptype. Includes user_type and dept.
 * @param mysqli $conDB Database connection
 * @return array List of potential approvers
 */
if (!function_exists('get_potential_approvers')) {
    function get_potential_approvers($conDB)
    {
        $employees = [];
        // Ensure admin_login alias `al` is used correctly
        $sql = "SELECT e.`emp_id`, e.`name`, al.`user_type`, e.`dept`
                FROM `employees` e
                JOIN `admin_login` al ON e.`emp_id` = al.`emp_id`
                WHERE al.`user_type` != 'employee' 
                  AND e.`emptype` != 'Supporter'
                  AND e.`status` = 1
                ORDER BY e.`name`";
        $query = mysqli_query($conDB, $sql);
        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                $employees[] = $row;
            }
            mysqli_free_result($query); // Free result
        } else {
        }
        return $employees;
    }
}

/**
 * Get Department ID by name (case-insensitive, partial match allowed).
 * Tries exact match first, then LIKE-based fuzzy match.
 * @param mysqli $conDB
 * @param string $dept_name
 * @return int|null Department ID or null if not found
 */
if (!function_exists('get_department_id_by_name')) {
    function get_department_id_by_name($conDB, $dept_name)
    {
        if (!$conDB || empty($dept_name)) return null;
        $name = trim($dept_name);
        // 1) Try exact (case-insensitive)
        $sql1 = "SELECT `id` FROM `department` WHERE LOWER(`dep_nme`) = LOWER(?) LIMIT 1";
        $stmt1 = mysqli_prepare($conDB, $sql1);
        if ($stmt1) {
            mysqli_stmt_bind_param($stmt1, "s", $name);
            if (mysqli_stmt_execute($stmt1)) {
                $res1 = mysqli_stmt_get_result($stmt1);
                if ($res1 && ($row = mysqli_fetch_assoc($res1))) {
                    $id = (int)$row['id'];
                    mysqli_free_result($res1);
                    mysqli_stmt_close($stmt1);
                    return $id;
                }
                if ($res1) mysqli_free_result($res1);
            }
            mysqli_stmt_close($stmt1);
        }

        // 2) Try fuzzy contains match
        $like = '%' . strtolower($name) . '%';
        $sql2 = "SELECT `id` FROM `department` WHERE LOWER(`dep_nme`) LIKE ? LIMIT 1";
        $stmt2 = mysqli_prepare($conDB, $sql2);
        if ($stmt2) {
            mysqli_stmt_bind_param($stmt2, "s", $like);
            if (mysqli_stmt_execute($stmt2)) {
                $res2 = mysqli_stmt_get_result($stmt2);
                if ($res2 && ($row2 = mysqli_fetch_assoc($res2))) {
                    $id = (int)$row2['id'];
                    mysqli_free_result($res2);
                    mysqli_stmt_close($stmt2);
                    return $id;
                }
                if ($res2) mysqli_free_result($res2);
            }
            mysqli_stmt_close($stmt2);
        }

        return null;
    }
}

/**
 * =================================================================
 * == NEW FUNCTION
 * =================================================================
 * Fetches all active employees from a specific department suitable for being approvers.
 * Excludes 'employee' user_type AND 'Supporter' emptype.
 * @param mysqli $conDB Database connection
 * @param int $dept_id The department ID to filter by
 * @return array List of potential approvers in that department
 */
if (!function_exists('get_department_approvers')) {
    function get_department_approvers($conDB, $dept_id)
    {
        $employees = [];
        if (!is_numeric($dept_id) || $dept_id <= 0) {
            return $employees;
        }
        $dept_id_safe = (int)$dept_id;

        $sql = "SELECT e.`emp_id`, e.`name`, al.`user_type`, e.`dept`
                FROM `employees` e
                JOIN `admin_login` al ON e.`emp_id` = al.`emp_id`
                WHERE al.`user_type` != 'employee' 
                  AND e.`emptype` != 'Supporter'
                  AND e.`dept` = ?
                  AND e.`status` = 1
                ORDER BY e.`name`";

        $stmt = mysqli_prepare($conDB, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $dept_id_safe);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($result)) {
                    $employees[] = $row;
                }
                mysqli_free_result($result); // Free result
                mysqli_stmt_close($stmt);
            } else {
                mysqli_stmt_close($stmt); // Close on fail
            }
        } else {
        }
        return $employees;
    }
}

/**
 * Fetch all active employees in a department (including basic employees) for selection lists.
 * Designed for asset departments where we need every active staff member, not just approvers.
 * @param mysqli $conDB
 * @param int $dept_id
 * @return array
 */
if (!function_exists('get_department_employees_all')) {
    function get_department_employees_all($conDB, $dept_id)
    {
        $employees = [];
        if (!is_numeric($dept_id) || $dept_id <= 0) {
            return $employees;
        }

        $dept_id_safe = (int) $dept_id;
        $sql = "SELECT e.`emp_id`, e.`name`, COALESCE(al.`user_type`, 'employee') AS `user_type`, e.`dept`, e.`emptype`
                FROM `employees` e
                LEFT JOIN `admin_login` al ON e.`emp_id` = al.`emp_id`
                WHERE e.`status` = 1 AND e.`dept` = ?
                ORDER BY e.`name`";

        $stmt = mysqli_prepare($conDB, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $dept_id_safe);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($result)) {
                    $employees[] = $row;
                }
                if ($result) mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
        }

        return $employees;
    }
}

/**
 * Fetch active employees for a list of departments and return with department names.
 * @param mysqli $conDB
 * @param array $dept_ids
 * @return array Array of ['id' => int, 'name' => string, 'employees' => array]
 */
if (!function_exists('get_departments_employees')) {
    function get_departments_employees($conDB, $dept_ids)
    {
        $clean_ids = [];
        foreach ((array)$dept_ids as $id) {
            $id = (int)$id;
            if ($id > 0 && !in_array($id, $clean_ids, true)) {
                $clean_ids[] = $id;
            }
        }

        if (empty($clean_ids)) {
            return [];
        }

        $departments = [];

        // Build safe IN clause from sanitized IDs
        $id_list = implode(',', $clean_ids);
        $sql = "SELECT `id`, `dep_nme` FROM `department` WHERE `id` IN ($id_list)";
        $result = mysqli_query($conDB, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $dept_id = (int)$row['id'];
                $departments[$dept_id] = [
                    'id' => $dept_id,
                    'name' => $row['dep_nme'] ?? '',
                    'employees' => []
                ];
            }
            mysqli_free_result($result);
        }

        // Populate employees for each requested department
        foreach ($clean_ids as $dept_id) {
            $employees = get_department_employees_all($conDB, $dept_id);
            if (!isset($departments[$dept_id])) {
                // In case the department name lookup failed, still include employees
                $departments[$dept_id] = [
                    'id' => $dept_id,
                    'name' => '',
                    'employees' => []
                ];
            }
            $departments[$dept_id]['employees'] = $employees;
        }

        // Return as indexed array to keep JSON output tidy
        return array_values($departments);
    }
}

/**
 * =================================================================
 * == NEW FUNCTION
 * =================================================================
 * Fetches all active HR Assistants (user_type = 'assistant') from Dept 5.
 * @param mysqli $conDB Database connection
 * @return array List of HR Assistants
 */
if (!function_exists('get_hr_assistants')) {
    function get_hr_assistants($conDB)
    {
        $employees = [];
        $hr_dept_id = 5; // Hard-coded HR Dept ID

        $sql = "SELECT e.`emp_id`, e.`name`, al.`user_type`, e.`dept`
                FROM `employees` e
                JOIN `admin_login` al ON e.`emp_id` = al.`emp_id`
                WHERE al.`user_type` = 'assistant'
                  AND e.`dept` = ?
                  AND e.`status` = 1
                ORDER BY e.`name`";

        $stmt = mysqli_prepare($conDB, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $hr_dept_id);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($result)) {
                    $employees[] = $row;
                }
                mysqli_free_result($result); // Free result
                mysqli_stmt_close($stmt);
            } else {
                mysqli_stmt_close($stmt); // Close on fail
            }
        } else {
        }
        return $employees;
    }
}


/**
 * Saves the chosen approval chain for a new request.
 * @param mysqli $conDB Database connection
 * @param string $inv_no The request's invoice number
 * @param string $request_type The type of request (e.g., 'smart_request')
 * @param array $approver_ids An array of emp_id strings/integers, in order of approval
 * @return bool True on success, false on failure
 */
if (!function_exists('save_approval_chain')) {
    function save_approval_chain($conDB, $inv_no, $request_type, $approver_ids)
    {
        if (!$conDB) {
            error_log("save_approval_chain ERROR: No database connection");
            return false;
        }
        if (empty($inv_no) || empty($request_type) || !is_array($approver_ids) || empty($approver_ids)) {
            error_log("save_approval_chain ERROR: Invalid parameters - inv_no: $inv_no, request_type: $request_type, approver_ids: " . json_encode($approver_ids));
            return false;
        }

        // 1. Get the request_type_id
        // First try to look up by type_name (new schema)
        $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
        if (!$type_query) {
            error_log("save_approval_chain ERROR: Query failed for type_name lookup: " . mysqli_error($conDB));
            return false;
        }
        
        // If not found by type_name, try looking up by id column for backward compatibility
        if (mysqli_num_rows($type_query) == 0) {
            mysqli_free_result($type_query);
            $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `id` = '" . escape_string($request_type) . "' LIMIT 1");
            if (!$type_query || mysqli_num_rows($type_query) == 0) {
                if ($type_query) mysqli_free_result($type_query);
                error_log("save_approval_chain ERROR: Request type '$request_type' not found in approval_request_types table");
                return false;
            }
        }
        
        $type_row = mysqli_fetch_assoc($type_query);
        mysqli_free_result($type_query);
        $request_type_id = (int)$type_row['id'];
        
        error_log("save_approval_chain INFO: Found request_type_id=$request_type_id for type='$request_type', inv_no=$inv_no, approvers=" . json_encode($approver_ids));

        // Start transaction
        mysqli_begin_transaction($conDB);

        try {
            // Optional: Delete existing chain for this inv_no and type before inserting new one
            // $delete_sql = "DELETE FROM `request_approvers` WHERE `request_inv_no` = '" . escape_string($inv_no) . "' AND `request_type_id` = $request_type_id";
            // if (!mysqli_query($conDB, $delete_sql)) {
            //     throw new Exception("Failed to delete existing approval chain: " . mysqli_error($conDB));
            // }

            // 2. Insert each approver into the `request_approvers` table
            $level = 1;
            foreach ($approver_ids as $approver_id) {
                $approver_id_safe = (int)$approver_id;
                if ($approver_id_safe > 0) { // Ensure valid ID
                    // Set the first approver to 'pending', others to 'awaiting'
                    $status = ($level == 1) ? 'pending' : 'awaiting';

                    $sql = "INSERT INTO `request_approvers` (`request_inv_no`, `request_type_id`, `approver_id`, `approval_level`, `status`)
                            VALUES ('" . escape_string($inv_no) . "', $request_type_id, $approver_id_safe, $level, '$status')";

                    if (!mysqli_query($conDB, $sql)) {
                        throw new Exception("Failed to insert approver level $level for InvNo $inv_no: " . mysqli_error($conDB));
                    }
                    $level++;
                } else {
                    error_log("save_approval_chain WARNING: Invalid approver_id=$approver_id for inv_no=$inv_no, skipping");
                }
            }

            // Commit transaction
            mysqli_commit($conDB);
            error_log("save_approval_chain SUCCESS: Saved " . ($level - 1) . " approvers for inv_no=$inv_no, type_id=$request_type_id");
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conDB);
            error_log("save_approval_chain ERROR: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Appends a new set of approvers to an existing approval chain.
 *
 * @param mysqli $conDB Database connection
 * @param string $inv_no The request's invoice number
 * @param int $request_type_id The ID from approval_request_types
 * @param int $current_level The *current* approval level that was just completed
 * @param array $new_approver_ids An array of new emp_ids to add
 * @return bool True on success
 * @throws Exception On database failure
 */
if (!function_exists('append_approval_chain')) {
    function append_approval_chain($conDB, $inv_no, $request_type_id, $current_level, $new_approver_ids)
    {
        if (!$conDB || empty($inv_no) || empty($new_approver_ids) || !is_array($new_approver_ids)) {
            return false;
        }

        $level = $current_level + 1;
        $first_new_approver_set = false;
        $inv_no_safe = escape_string($inv_no);

        foreach ($new_approver_ids as $approver_id) {
            $approver_id_safe = (int)$approver_id;
            if ($approver_id_safe > 0) {
                // Skip if an identical row already exists to avoid duplicates
                $exists_sql = "SELECT id FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id = ? AND approval_level = ? LIMIT 1";
                $stmt_exists = mysqli_prepare($conDB, $exists_sql);
                if ($stmt_exists) {
                    mysqli_stmt_bind_param($stmt_exists, "siii", $inv_no_safe, $request_type_id, $approver_id_safe, $level);
                    mysqli_stmt_execute($stmt_exists);
                    $res_exists = mysqli_stmt_get_result($stmt_exists);
                    $already_exists = ($res_exists && mysqli_num_rows($res_exists) > 0);
                    if ($res_exists) mysqli_free_result($res_exists);
                    mysqli_stmt_close($stmt_exists);
                    if ($already_exists) {
                        // If the first new approver row already exists as 'awaiting', move it to 'pending'
                        if (!$first_new_approver_set) {
                            $upd_sql = "UPDATE request_approvers SET status = 'pending' WHERE request_inv_no = ? AND request_type_id = ? AND approver_id = ? AND approval_level = ? AND status = 'awaiting'";
                            $stmt_upd = mysqli_prepare($conDB, $upd_sql);
                            if ($stmt_upd) {
                                mysqli_stmt_bind_param($stmt_upd, "siii", $inv_no_safe, $request_type_id, $approver_id_safe, $level);
                                mysqli_stmt_execute($stmt_upd);
                                mysqli_stmt_close($stmt_upd);
                            }
                        }
                        $first_new_approver_set = true;
                        $level++;
                        continue; // Skip inserting duplicate row
                    }
                }
                // Set the first new approver to 'pending', others to 'awaiting'
                $status = (!$first_new_approver_set) ? 'pending' : 'awaiting';

                $sql = "INSERT INTO `request_approvers` (`request_inv_no`, `request_type_id`, `approver_id`, `approval_level`, `status`)
                        VALUES (?, ?, ?, ?, ?)";

                $stmt_append = mysqli_prepare($conDB, $sql);
                if (!$stmt_append) {
                    throw new Exception("Prepare failed (append chain): " . mysqli_error($conDB));
                }
                mysqli_stmt_bind_param($stmt_append, "siiis", $inv_no_safe, $request_type_id, $approver_id_safe, $level, $status);

                if (!mysqli_stmt_execute($stmt_append)) {
                    $error_msg = mysqli_stmt_error($stmt_append);
                    mysqli_stmt_close($stmt_append);
                    throw new Exception("Failed to insert new approver level $level for InvNo $inv_no_safe: " . $error_msg);
                }
                mysqli_stmt_close($stmt_append);

                $first_new_approver_set = true;
                $level++;
            }
        }
        return true;
    }
}


/**
 * =================================================================
 * == NEW FUNCTION - EMAIL NOTIFICATION SENDER
 * =================================================================
 * Sends an email notification using PHPMailer with HTML templates.
 * @param mysqli $conDB Database connection
 * @param string $to_email The recipient's email address
 * @param string $to_name The recipient's name
 * @param string $subject The email subject
 * @param string $request_type Type of request: 'smart_request', 'vacation_request', 'leave_request', 'loan_request'
 * @param array $template_data Array of data to populate the template
 * @param array $cc_emails Optional array of CC email addresses ['email' => 'name'] - ONLY used for leave_request (excuse leave)
 * @return bool True on success, false on failure
 */
if (!function_exists('send_approval_email')) {
    function send_approval_email($conDB, $to_email, $to_name, $subject, $request_type = 'smart_request', $template_data = [], $cc_emails = [])
    {
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return false;
        }
        if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // --- [FIX] Fetch SMTP settings from app_settings table ---
        $smtp_host = get_setting($conDB, 'smtp_host');         // 'smtp_host'
        $smtp_port = (int)get_setting($conDB, 'smtp_port');    // 'smtp_port'
        $smtp_user = get_setting($conDB, 'smtp_user');         // 'smtp_user'
        $smtp_pass = get_setting($conDB, 'smtp_pass');         // 'smtp_pass'
        $smtp_from_email = get_setting($conDB, 'from_email');      // [FIX] Changed from 'smtp_from_email'
        $smtp_from_name = get_setting($conDB, 'from_name', 'Al Mutlak HR System'); // [FIX] Use dedicated from_name setting with fallback
        $smtp_secure = get_setting($conDB, 'smtp_encryption');   // [FIX] Changed from 'smtp_secure'

        if (empty($smtp_host) || empty($smtp_port) || empty($smtp_user) || empty($smtp_pass) || empty($smtp_from_email) || empty($smtp_from_name)) {
            return false;
        }
        // --- End Fetch SMTP settings ---

        // Load and populate email template
        $body_html = load_email_template($request_type, $template_data);
        if ($body_html === false) {
            // Fallback: build a minimal HTML body so final approvals still notify
            $reqId = htmlspecialchars($template_data['REQUEST_ID'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $reqType = htmlspecialchars($template_data['REQUEST_TYPE'] ?? ucfirst(str_replace('_',' ', $request_type)), ENT_QUOTES, 'UTF-8');
            $msg = $template_data['EMAIL_MESSAGE'] ?? 'A request update requires your attention.';
            $reqUrl = $template_data['REQUEST_URL'] ?? get_base_url() . '/dashboard.php';
            $body_html = '<div style="font-family:Segoe UI,Arial,sans-serif;color:#222;">'
                       . '<h2 style="margin:0 0 10px;">' . $reqType . '</h2>'
                       . '<p style="margin:0 0 8px;">Request ID: <strong>' . $reqId . '</strong></p>'
                       . '<p style="margin:0 0 12px;">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>'
                       . '<p style="margin:14px 0 0;"><a href="' . htmlspecialchars($reqUrl, ENT_QUOTES, 'UTF-8') . '" style="background:#007bff;color:#fff;padding:8px 12px;border-radius:4px;text-decoration:none;">Open in Portal</a></p>'
                       . '</div>';
        }

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_user;
            $mail->Password   = $smtp_pass;

            // Set encryption based on setting (match login.php logic)
            switch (strtolower($smtp_secure)) {
                case 'tls':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    break;
                case 'ssl':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    break;
                default:
                    // No encryption
                    $mail->SMTPSecure = false;
                    break;
            }

            $mail->Port       = $smtp_port;
            $mail->CharSet    = 'UTF-8';

            // Recipients
            $mail->setFrom($smtp_from_email, $smtp_from_name);
            $mail->addAddress($to_email, $to_name);
            $mail->addReplyTo($smtp_from_email, $smtp_from_name);

            // Add CC recipients ONLY for leave_request (excuse leave)
            // CC is disabled for all other request types
            if ($request_type === 'leave_request' && !empty($cc_emails) && is_array($cc_emails)) {
                foreach ($cc_emails as $cc_email => $cc_name) {
                    if (filter_var($cc_email, FILTER_VALIDATE_EMAIL)) {
                        $mail->addCC($cc_email, $cc_name);
                    }
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body_html;
            $mail->AltBody = strip_tags($body_html); // Plain text version

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * =================================================================
 * Loads and populates email template with data.
 * @param string $request_type Type of request
 * @param array $data Template data
 * @return string|false HTML content or false on failure
 */
if (!function_exists('load_email_template')) {
    function load_email_template($request_type, $data = [])
    {
        // Map request types to template files
        $template_map = [
            'smart_request' => 'smart_request_email_template.html',
            'general_request' => 'general_request_email_template.html',
            'vacation_request' => 'vacation_request_email_template.html',
            'leave_request' => 'vacation_request_email_template.html', // Uses same template as vacation
            'loan_request' => 'loan_request_email_template.html',
            'resignation_request' => 'resignation_request_email_template.html',
            'modification_request' => 'modification_request_email_template.html',
            'rejoin_request' => 'rejoin_request_email_template.html'
        ];

        $template_file = $template_map[$request_type] ?? 'smart_request_email_template.html';
        $template_path = __DIR__ . '/PHPMailerMaster/' . $template_file;

        if (!file_exists($template_path)) {
            return false;
        }

        $html = file_get_contents($template_path);
        if ($html === false) {
            return false;
        }

        // Default logo URL - adjust to your actual logo path
        $base_url = get_base_url();
        $defaults = [
            // 'LOGO_URL' => $base_url . '/assets/logo/logo_color_sm.png',
            'LOGO_URL' => 'https://hr.almutlaksystem.com/assets/logo/logo_color_sm.png',
            'APPROVER_NAME' => 'Approver',
            'REQUEST_ID' => 'N/A',
            'REQUEST_TITLE' => 'N/A',
            'REQUESTER_NAME' => 'N/A',
            'REQUEST_URL' => $base_url . '/dashboard.php',
            'EMAIL_MESSAGE' => 'A new request requires your attention.',
            'CATEGORY' => 'N/A',
            'PRIORITY' => 'N/A',
            'DESCRIPTION' => 'No description provided.',
            'REJECTION_BORDER' => 'border-bottom: 1px solid #404040;',
            'REJECTION_BORDER_INST' => '',
            'REJECTION_INFO' => '',
            'EXIT_INTERVIEW_SECTION' => '',
            'EMP_ID' => 'N/A',
            'EMP_NAME' => 'N/A',
            'DEPARTMENT' => 'N/A',
            'DESIGNATION' => 'N/A',
            'RESIGNATION_ID' => 'N/A',
            'LAST_WORKING_DAY' => 'N/A',
            'SUBMISSION_DATE' => 'N/A',
            'UPDATE_TYPE' => 'N/A',
            'CURRENT_VALUE' => 'N/A',
            'NEW_VALUE' => 'N/A'
        ];

        // Merge so passed data overrides defaults
        $data = array_merge($defaults, $data);

        // Handle rejection-specific placeholders for loan template
        if ($request_type === 'loan_request') {
            if (!empty($data['REJECTION_REASON'])) {
                // This is a rejection email
                $data['EMAIL_MESSAGE'] = $data['EMAIL_MESSAGE'] ?? 'Unfortunately, a loan request has been rejected.';
                $data['REJECTION_BORDER'] = 'border-bottom: 1px solid #404040;';
                $data['REJECTION_BORDER_INST'] = 'border-bottom: 1px solid #404040;';
                $data['REJECTION_INFO'] = '<tr><td style="padding: 8px 0; border-bottom: 1px solid #404040;"><span style="color: #a0a0a0; font-size: 14px;">Rejected By:</span><span style="color: #ff6b6b; font-size: 14px; float: right;">' . htmlspecialchars($data['REJECTED_BY'], ENT_QUOTES, 'UTF-8') . '</span></td></tr><tr><td style="padding: 12px 0;"><span style="color: #a0a0a0; font-size: 14px; display: block; margin-bottom: 8px;">Rejection Reason:</span><div style="background-color: #1e1e1e; padding: 12px; border-radius: 4px; border-left: 3px solid #ff6b6b;"><p style="margin: 0; color: #ffffff; font-size: 14px; line-height: 1.6;">' . nl2br(htmlspecialchars($data['REJECTION_REASON'], ENT_QUOTES, 'UTF-8')) . '</p></div></td></tr>';
            } else {
                // Normal approval request
                $data['EMAIL_MESSAGE'] = $data['EMAIL_MESSAGE'] ?? 'A new loan request has been submitted and requires your approval.';
            }
        }

        // Replace template placeholders
        foreach ($data as $key => $value) {
            // Skip already processed rejection info and HTML content
            if ($key === 'REJECTION_INFO' || $key === 'REJECTION_BORDER' || $key === 'REJECTION_BORDER_INST' || $key === 'EXIT_INTERVIEW_SECTION') {
                $html = str_replace('{{' . $key . '}}', $value, $html);
            } else {
                $html = str_replace('{{' . $key . '}}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $html);
            }
        }

        return $html;
    }
}

/**
 * =================================================================
 * Gets the base URL of the application.
 * @return string Base URL
 */
if (!function_exists('get_base_url')) {
    function get_base_url()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script_path = dirname($_SERVER['SCRIPT_NAME'] ?? '');

        // Clean up script path
        if ($script_path === '/' || $script_path === '\\') {
            $script_path = '';
        }

        return $protocol . '://' . $host . $script_path;
    }
}

/**
 * =================================================================
 * Fetches request details for email template based on request type.
 * @param mysqli $conDB Database connection
 * @param string $inv_no Request invoice number
 * @param string $request_type Type of request
 * @param string $approver_name Name of the approver
 * @return array|false Template data array or false on failure
 */
if (!function_exists('get_request_details_for_email')) {
    function get_request_details_for_email($conDB, $inv_no, $request_type, $approver_name)
    {
        $base_url = get_base_url();

        $template_data = [
            'APPROVER_NAME' => $approver_name,
            'REQUEST_ID' => $inv_no
        ];

        if ($request_type === 'vacation_request') {
            // Fetch vacation details
            $sql = "SELECT v.*, e.name as employee_name 
                    FROM emp_vacation v 
                    JOIN employees e ON v.emp_id = e.emp_id 
                    WHERE v.request_inv_no = ? 
                    LIMIT 1";

            $stmt = mysqli_prepare($conDB, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $inv_no);
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    if ($row = mysqli_fetch_assoc($result)) {
                        // Determine if it's vacation or leave
                        $vac_type = strtolower($row['vac_type'] ?? '');
                        $fly_type = $row['fly_type'] ?? '';

                        if (in_array($vac_type, ['sick', 'emergency', 'unpaid', 'maternity', 'paternity'])) {
                            // It's a leave request
                            $template_data['REQUEST_TYPE'] = ucfirst($vac_type) . ' Leave Request';
                            $template_data['REQUEST_TYPE_LOWER'] = strtolower($vac_type) . ' leave request';
                        } else {
                            // It's a vacation request
                            $template_data['REQUEST_TYPE'] = 'Annual Vacation Request';
                            $template_data['REQUEST_TYPE_LOWER'] = 'annual vacation request';
                        }

                        $template_data['EMPLOYEE_NAME'] = $row['employee_name'];
                        $template_data['START_DATE'] = date('d M Y', strtotime($row['start_date']));
                        $template_data['END_DATE'] = date('d M Y', strtotime($row['return_date']));
                        $template_data['DURATION'] = $row['vacdays'];
                        $template_data['REQUEST_URL'] = $base_url . '/all_applied_vac.php?status=my_pending';

                        mysqli_free_result($result);
                        mysqli_stmt_close($stmt);
                        return $template_data;
                    }
                    if ($result) mysqli_free_result($result);
                }
                mysqli_stmt_close($stmt);
            }
            return false;
        } elseif ($request_type === 'loan_request') {
            // Fetch loan details
            $sql = "SELECT l.*, e.name as employee_name 
                    FROM emp_loan l 
                    JOIN employees e ON l.emp_id = e.emp_id 
                    WHERE l.inv_no = ? 
                    LIMIT 1";

            $stmt = mysqli_prepare($conDB, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $inv_no);
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    if ($row = mysqli_fetch_assoc($result)) {
                        $template_data['EMPLOYEE_NAME'] = $row['employee_name'];
                        $template_data['LOAN_TYPE'] = str_replace('_', ' ', $row['loan_type']);
                        $template_data['LOAN_AMOUNT'] = number_format($row['loan_amount'], 2);
                        $template_data['INSTALLMENTS'] = $row['installments'];
                        $template_data['REQUEST_URL'] = $base_url . '/all_applied_loan.php?status=my_pending';

                        mysqli_free_result($result);
                        mysqli_stmt_close($stmt);
                        return $template_data;
                    }
                    if ($result) mysqli_free_result($result);
                }
                mysqli_stmt_close($stmt);
            }
            return false;
        } elseif ($request_type === 'smart_request') {
            // Fetch smart request details
            $sql = "SELECT sr.*, e.name as employee_name, d.dep_nme as department_name
                    FROM smart_request sr 
                    LEFT JOIN employees e ON sr.emp_id = e.emp_id 
                    LEFT JOIN department d ON sr.department = d.id
                    WHERE sr.inv_no = ? 
                    LIMIT 1";

            $stmt = mysqli_prepare($conDB, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $inv_no);
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    if ($row = mysqli_fetch_assoc($result)) {
                        $template_data['REQUEST_TITLE'] = $row['sub_title'] ?? 'Smart Request';
                        $template_data['SUBMITTED_BY'] = $row['employee_name'] ?? $row['prep_by'] ?? 'Employee';
                        $template_data['DEPARTMENT'] = $row['department_name'] ?? 'N/A';
                        $template_data['REQUEST_URL'] = $base_url . '/open_request.php?id=' . urlencode($inv_no);

                        mysqli_free_result($result);
                        mysqli_stmt_close($stmt);
                        return $template_data;
                    }
                    if ($result) mysqli_free_result($result);
                }
                mysqli_stmt_close($stmt);
            }
            return false;
        } elseif ($request_type === 'resignation_request') {
            // Fetch resignation details
            $sql = "SELECT r.*, e.emp_id, e.name as employee_name, e.iqama,
                           d.dep_nme as department, j.job as designation
                    FROM emp_resignations r
                    LEFT JOIN employees e ON e.emp_id = r.emp_id
                    LEFT JOIN department d ON d.id = e.dept
                    LEFT JOIN ac_jobs j ON j.id = e.actual_job
                    WHERE r.request_inv_no = ? 
                    LIMIT 1";

            $stmt = mysqli_prepare($conDB, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $inv_no);
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    if ($row = mysqli_fetch_assoc($result)) {
                        $template_data['EMP_ID'] = $row['emp_id'] ?? 'N/A';
                        $template_data['EMP_NAME'] = $row['employee_name'] ?? 'N/A';
                        $template_data['IQAMA'] = $row['iqama'] ?? 'N/A';
                        $template_data['DEPARTMENT'] = $row['department'] ?? 'N/A';
                        $template_data['DESIGNATION'] = $row['designation'] ?? 'N/A';
                        $template_data['RESIGNATION_ID'] = $inv_no;
                        $template_data['LAST_WORKING_DAY'] = isset($row['last_working_day']) ? date('d M Y', strtotime($row['last_working_day'])) : 'N/A';
                        $template_data['SUBMISSION_DATE'] = isset($row['created_at']) ? date('d M Y H:i', strtotime($row['created_at'])) : 'N/A';
                        $template_data['REQUEST_URL'] = $base_url . '/all_resignations.php';

                        mysqli_free_result($result);
                        mysqli_stmt_close($stmt);
                        return $template_data;
                    }
                    if ($result) mysqli_free_result($result);
                }
                mysqli_stmt_close($stmt);
            }
            return false;
        } elseif ($request_type === 'general_request') {
            // Fetch general request details
            $sql = "SELECT gr.*, d.dep_nme as department_name
                    FROM general_requests gr 
                    LEFT JOIN department d ON gr.user_dept = d.id
                    WHERE gr.inv_no = ? 
                    LIMIT 1";

            $stmt = mysqli_prepare($conDB, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $inv_no);
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    if ($row = mysqli_fetch_assoc($result)) {
                        $template_data['REQUEST_TITLE'] = $row['request_title'] ?? 'General Request';
                        $template_data['REQUESTER_NAME'] = $row['emp_name'] ?? 'Employee';
                        $template_data['DEPARTMENT'] = $row['department_name'] ?? 'N/A';
                        $template_data['PRIORITY'] = ucfirst($row['priority'] ?? 'normal');
                        $template_data['CATEGORY'] = $row['request_category'] ?? 'N/A';
                        $template_data['DESCRIPTION'] = $row['description'] ?? 'No description provided';
                        $template_data['EMAIL_MESSAGE'] = 'A General Request requires your approval.';
                        $template_data['REQUEST_URL'] = $base_url . '/view_general_request.php?id=' . urlencode($inv_no);

                        mysqli_free_result($result);
                        mysqli_stmt_close($stmt);
                        return $template_data;
                    }
                    if ($result) mysqli_free_result($result);
                }
                mysqli_stmt_close($stmt);
            }
            return false;
        }

        // Unknown request type
        return false;
    }
}



/**
 * =================================================================
 * Fetches all exit interview questions and answers for a resignation
 * @param mysqli $conDB Database connection
 * @param int $resignation_id The resignation ID
 * @return array Exit interview Q&A array or empty array if not found
 */
if (!function_exists('get_exit_interview_data')) {
    function get_exit_interview_data($conDB, $resignation_id)
    {
        $exit_interviews = [];

        if (!is_numeric($resignation_id) || $resignation_id <= 0) {
            return $exit_interviews;
        }

        $resignation_id_safe = (int)$resignation_id;
        $sql = "SELECT * FROM `emp_exit_interviews` WHERE `resignation_id` = ? ORDER BY `id` ASC";

        $stmt = mysqli_prepare($conDB, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $resignation_id_safe);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($result)) {
                    $exit_interviews[] = $row;
                }
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
        }

        return $exit_interviews;
    }
}

/**
 * =================================================================
 * Generates HTML for exit interview display in emails
 * @param array $exit_interviews Array of exit interview records
 * @return string HTML formatted exit interview Q&A display
 */
if (!function_exists('format_exit_interview_html')) {
    function format_exit_interview_html($exit_interviews)
    {
        if (empty($exit_interviews)) {
            return '';
        }

        $html = '<h2 style="margin: 0 0 20px; color: #ffffff; font-size: 18px; font-weight: 600; border-bottom: 2px solid #dc3545; padding-bottom: 10px;">
                    📋 Exit Interview Responses
                 </h2>';

        $html .= '<div style="background-color: #151515; padding: 15px; border-radius: 4px;">';

        // Define question labels
        $questions = [
            'What are the main reasons behind your decision to leave the company?',
            'Did you feel supported and appreciated by management and colleagues?',
            'Were you provided with sufficient tools and resources to perform your job effectively?',
            'How would you evaluate your direct manager\'s leadership style?',
            'Were the available growth and development opportunities suitable for you?',
            'How do you evaluate the compensation and benefits you received?',
            'What do you wish had been different during your time here?',
            'Would you recommend the company as a workplace to others? Why or why not?',
            'Is there anything else you would like to share before you leave?'
        ];

        $answerKeys = ['q1_reasons', 'q2_support', 'q3_resources', 'q4_manager', 'q5_growth', 'q6_compensation', 'q7_different', 'q8_recommend', 'q9_additional'];

        foreach ($exit_interviews as $record) {
            foreach ($answerKeys as $index => $key) {
                if (!empty($record[$key])) {
                    $question_num = $index + 1;
                    $html .= '<div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #2a2a2a;">';

                    // Question
                    $html .= '<p style="margin: 0 0 8px; color: #ffc107; font-size: 14px; font-weight: 600;">
                                Q' . $question_num . ': ' . htmlspecialchars($questions[$index], ENT_QUOTES, 'UTF-8') . '
                              </p>';

                    // Answer
                    $html .= '<p style="margin: 0; color: #c0c0c0; font-size: 14px; line-height: 1.6; padding: 10px; background-color: #1a1a1a; border-left: 3px solid #28a745; border-radius: 3px;">
                                ' . nl2br(htmlspecialchars($record[$key], ENT_QUOTES, 'UTF-8')) . '
                              </p>';

                    $html .= '</div>';
                }
            }
        }

        $html .= '</div>';

        return $html;
    }
}

/**
 * Handles an approver's action (approve/reject).
 * Now accepts an optional $next_approver_chain to dynamically build the chain.
 *
 * @param mysqli $conDB Database connection
 * @param string $inv_no The request's invoice number
 * @param string $request_type The type of request (e.g., 'smart_request')
 * @param int $current_user_id The emp_id of the user taking the action
 * @param string $action The action taken ('approve' or 'reject')
 * @param string $note A note for the action
 * @param array $next_approver_chain (Optional) An array of emp_ids for the *next* approval levels.
 * @return array Status of the operation ['status' => 'success'|'error', 'message' => string, 'next_approver' => array|null, 'next_approver_id' => int|null]
 */
if (!function_exists('handle_approval_action')) {
    function handle_approval_action($conDB, $inv_no, $request_type, $current_user_id, $action, $note, $next_approver_chain = [])
    {
        global $userwel; // Assumes $userwel contains the current user's name

        // Helper: Get a friendly human-readable label for the request type (once per call)
        if (!function_exists('get_friendly_request_label')) {
            function get_friendly_request_label($type)
            {
                $map = [
                    'vacation_request' => 'Vacation Request',
                    'smart_request' => 'Smart Request',
                    'general_request' => 'General Request',
                    // Extend here as new request types are added
                ];
                if (isset($map[$type])) return $map[$type];
                // Fallback: transform snake_case to Title Case
                return ucwords(str_replace('_', ' ', trim($type)));
            }
        }
        $friendly_label = get_friendly_request_label($request_type);

        // ** Input Validation **
        if (!$conDB) {
            return ['status' => 'error', 'message' => 'Database connection error.'];
        }
        if (empty($inv_no) || empty($request_type) || !is_numeric($current_user_id) || $current_user_id <= 0 || ($action !== 'approve' && $action !== 'reject')) {
            return ['status' => 'error', 'message' => 'Invalid parameters for approval action.'];
        }

        // ** Get Request Type Info **
        $type_query = mysqli_query($conDB, "SELECT `id`, `main_table_name` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
        if (!$type_query || mysqli_num_rows($type_query) == 0) {
            if ($type_query) mysqli_free_result($type_query); // Free result
            return ['status' => 'error', 'message' => 'Invalid request type specified.'];
        }
        $type_row = mysqli_fetch_assoc($type_query);
        mysqli_free_result($type_query); // Free result
        $request_type_id = (int)$type_row['id'];
        $main_table_name = $type_row['main_table_name']; // Make sure this table exists and has necessary columns
        if (empty($main_table_name)) {
            return ['status' => 'error', 'message' => 'Configuration error: Main table not defined for this request type.'];
        }

        // ** Determine the invoice column name based on the table **
        // smart_request and general_requests use 'inv_no', emp_vacation uses 'request_inv_no'
        $inv_column_name = (in_array($main_table_name, ['smart_request', 'general_requests'])) ? 'inv_no' : 'request_inv_no';

        // ** Sanitize Inputs **
        $inv_no_safe = escape_string($inv_no);
        $note_safe = escape_string($note);
        $current_user_id_safe = (int)$current_user_id;

        // ** Start Transaction **
        mysqli_begin_transaction($conDB);

        try {
            // ** Find and Lock Pending Task **
            $find_sql = "SELECT * FROM `request_approvers`
                         WHERE `request_inv_no` = ? AND `request_type_id` = ? AND `approver_id` = ? AND `status` = 'pending'
                         ORDER BY `approval_level` LIMIT 1 FOR UPDATE"; // Lock the row
            $stmt_find = mysqli_prepare($conDB, $find_sql);
            if (!$stmt_find) throw new Exception("Prepare failed (find task): " . mysqli_error($conDB));
            mysqli_stmt_bind_param($stmt_find, "sii", $inv_no_safe, $request_type_id, $current_user_id_safe);
            if (!mysqli_stmt_execute($stmt_find)) throw new Exception("Execute failed (find task): " . mysqli_stmt_error($stmt_find));
            $find_result = mysqli_stmt_get_result($stmt_find);

            if (!$find_result || mysqli_num_rows($find_result) == 0) {
                if ($find_result) mysqli_free_result($find_result); // Free result
                mysqli_stmt_close($stmt_find);
                // Check if already actioned (without locking)
                $check_actioned_sql = "SELECT `status` FROM `request_approvers`
                                       WHERE `request_inv_no` = ? AND `request_type_id` = ? AND `approver_id` = ?
                                       ORDER BY `action_date` DESC LIMIT 1";
                $stmt_check = mysqli_prepare($conDB, $check_actioned_sql);
                if (!$stmt_check) throw new Exception("Prepare failed (check actioned): " . mysqli_error($conDB));
                mysqli_stmt_bind_param($stmt_check, "sii", $inv_no_safe, $request_type_id, $current_user_id_safe);
                if (!mysqli_stmt_execute($stmt_check)) throw new Exception("Execute failed (check actioned): " . mysqli_stmt_error($stmt_check));
                $check_result = mysqli_stmt_get_result($stmt_check);
                if ($check_result && $row = mysqli_fetch_assoc($check_result)) {
                    mysqli_free_result($check_result); // Free result
                    mysqli_rollback($conDB); // Release transaction
                    mysqli_stmt_close($stmt_check);
                    return ['status' => 'error', 'message' => 'You have already actioned this request (' . $row['status'] . ').'];
                }
                if ($check_result) mysqli_free_result($check_result); // Free result
                mysqli_stmt_close($stmt_check);
                mysqli_rollback($conDB); // Release transaction
                return ['status' => 'error', 'message' => 'No pending approval found for you on this request, or it has been modified.'];
            }
            $current_task = mysqli_fetch_assoc($find_result);
            mysqli_free_result($find_result); // Free result
            mysqli_stmt_close($stmt_find); // Close the find statement

            $current_level = (int)$current_task['approval_level'];
            $current_task_id = (int)$current_task['id'];

            // ** CHECK IF CURRENT APPROVER IS HR PAYROLL **
            $current_user_role_query = mysqli_query($conDB, "SELECT user_type FROM admin_login WHERE emp_id = '{$current_user_id_safe}'");
            $is_current_user_hr_payroll = false;
            if ($current_user_role_query && mysqli_num_rows($current_user_role_query) > 0) {
                $user_role_row = mysqli_fetch_assoc($current_user_role_query);
                if (strtolower($user_role_row['user_type']) === 'hr_payroll') {
                    $is_current_user_hr_payroll = true;
                }
                mysqli_free_result($current_user_role_query);
            }

            // ** Update Current Approver's Task **
            $action_status = ($action == 'approve') ? 'approved' : 'rejected';
            $update_sql = "UPDATE `request_approvers` SET `status` = ?, `note` = ?, `action_date` = NOW() WHERE `id` = ?";
            $stmt_update = mysqli_prepare($conDB, $update_sql);
            if (!$stmt_update) throw new Exception("Prepare failed (update task): " . mysqli_error($conDB));
            mysqli_stmt_bind_param($stmt_update, "ssi", $action_status, $note_safe, $current_task_id);
            if (!mysqli_stmt_execute($stmt_update)) throw new Exception("Execute failed (update task): " . mysqli_stmt_error($stmt_update));
            mysqli_stmt_close($stmt_update);

            // ** [NEW] IF HR PAYROLL APPROVED, UPDATE VACATION BALANCE IMMEDIATELY **
            // CRITICAL: Do NOT update balance for asset clearance approvals - only for final HR_Payroll approval
            $is_asset_clearance = (stripos($note, 'Asset Clearance') !== false);
            if ($action == 'approve' && $is_current_user_hr_payroll && $request_type === 'vacation_request' && !$is_asset_clearance) {
                $vacation_id_for_balance = null;
                $sql_get_balance_id = "SELECT `id` FROM `emp_vacation` WHERE `request_inv_no` = ? LIMIT 1";
                $stmt_balance = mysqli_prepare($conDB, $sql_get_balance_id);
                if ($stmt_balance) {
                    mysqli_stmt_bind_param($stmt_balance, "s", $inv_no_safe);
                    if (mysqli_stmt_execute($stmt_balance)) {
                        $res_balance = mysqli_stmt_get_result($stmt_balance);
                        if ($row_balance = mysqli_fetch_assoc($res_balance)) {
                            $vacation_id_for_balance = (int)$row_balance['id'];
                        }
                        if ($res_balance) mysqli_free_result($res_balance);
                    }
                    mysqli_stmt_close($stmt_balance);
                }
                if ($vacation_id_for_balance > 0 && function_exists('update_vacation_balance_on_approval')) {
                    update_vacation_balance_on_approval($conDB, $vacation_id_for_balance);
                }
            }

            // ** Log Action in smt_request_status (If this table is used for vacations) **
            $log_status = ($action == 'approve') ? "approved_level_$current_level" : 'rejected';
            $log_user_name = escape_string($userwel ?? 'System');
            $log_sql = "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES (?, ?, ?, ?, ?)";
            $stmt_log = mysqli_prepare($conDB, $log_sql);
            if ($stmt_log) {
                mysqli_stmt_bind_param($stmt_log, "issss", $current_user_id_safe, $inv_no_safe, $log_user_name, $log_status, $note_safe);
                if (!mysqli_stmt_execute($stmt_log)) {
                    // Continue processing even if logging fails
                }
                mysqli_stmt_close($stmt_log);
            }


            // ** Handle Next Step **
            $result_payload = ['status' => 'success', 'next_approver' => null, 'next_approver_id' => null]; // Initialize result payload

            if ($action == 'approve') {

                // *** NEW LOGIC: Check for manually provided next approvers ***
                // This logic is now triggered *only if* the current approver adds a new chain
                $valid_next_approvers = [];
                if (!empty($next_approver_chain) && is_array($next_approver_chain)) {
                    foreach ($next_approver_chain as $approver_id) {
                        if (!empty($approver_id) && is_numeric($approver_id)) {
                            $valid_next_approvers[] = (int)$approver_id;
                        }
                    }
                }

                if (!empty($valid_next_approvers)) {
                    // --- Case 1: Manager IS adding a new chain ---

                    // 1. Append them to the chain
                    append_approval_chain($conDB, $inv_no_safe, $request_type_id, $current_level, $valid_next_approvers);

                    // 2. Update main request table status to reflect next level
                    $next_level = $current_level + 1;
                    $update_main_pending_sql = "UPDATE `$main_table_name` SET `current_status` = 'pending_approval', `current_approval_level` = ? WHERE `$inv_column_name` = ?";
                    $stmt_main_pending = mysqli_prepare($conDB, $update_main_pending_sql);
                    if (!$stmt_main_pending) throw new Exception("Prepare failed (update main pending): " . mysqli_error($conDB));
                    mysqli_stmt_bind_param($stmt_main_pending, "is", $next_level, $inv_no_safe);
                    if (!mysqli_stmt_execute($stmt_main_pending)) throw new Exception("Execute failed (update main pending): " . mysqli_stmt_error($stmt_main_pending));
                    mysqli_stmt_close($stmt_main_pending);

                    // 3. Prepare details for notification
                    $next_approver_id = $valid_next_approvers[0]; // The first one in the new chain
                    $next_approver_details = getEmployeeDetailsForApproval($conDB, $next_approver_id);
                    if ($next_approver_details) {
                        $result_payload['next_approver'] = $next_approver_details;
                        $result_payload['next_approver_id'] = $next_approver_id;

                        // --- [FIX] SEND NOTIFICATION TO NEXT APPROVER ---
                        $notification_title = "New $friendly_label";
                        $notification_message = "A new $friendly_label ($inv_no_safe) is pending your approval.";
                        // Dynamic URL based on request type
                        if ($request_type === 'smart_request') {
                            $notification_url = "all_requests.php?status=pending_approval";
                        } elseif ($request_type === 'vacation_request') {
                            $notification_url = "all_applied_vac.php?status=my_pending";
                        } elseif ($request_type === 'loan_request') {
                            $notification_url = "all_applied_loan.php?status=my_pending";
                        } else {
                            $notification_url = "all_requests.php"; // Fallback
                        }
                        create_browser_notification($conDB, $next_approver_id, $notification_title, $notification_message, $notification_url);

                        if ($next_approver_details['email']) {
                            // Fetch request details for email template
                            $template_data = get_request_details_for_email($conDB, $inv_no_safe, $request_type, $next_approver_details['name']);

                            if ($template_data) {
                                send_approval_email($conDB, $next_approver_details['email'], $next_approver_details['name'], $notification_title, $request_type, $template_data);
                            } else {
                                // Fallback to simple email if details not found
                                $email_body = "Dear " . htmlspecialchars($next_approver_details['name']) . ",<br><br>A new $friendly_label ($inv_no_safe) is pending your approval. Please log in to the portal to review it.<br><br>Thank you.";
                                send_approval_email($conDB, $next_approver_details['email'], $next_approver_details['name'], $notification_title, $request_type, ['APPROVER_NAME' => $next_approver_details['name'], 'REQUEST_ID' => $inv_no_safe]);
                            }
                        }
                        // --- [END FIX] ---

                    } else {
                    }
                } else {
                    // --- Case 2: Manager is NOT adding a chain (or it's not Level 1) ---
                    // Find the *existing* next approver in the table
                    $find_next_sql = "SELECT * FROM `request_approvers` WHERE `request_inv_no` = ? AND `request_type_id` = ? AND `approval_level` = ? AND `status` = 'awaiting' LIMIT 1";
                    $next_level = $current_level + 1;
                    $stmt_find_next = mysqli_prepare($conDB, $find_next_sql);
                    if (!$stmt_find_next) throw new Exception("Prepare failed (find next): " . mysqli_error($conDB));
                    mysqli_stmt_bind_param($stmt_find_next, "sii", $inv_no_safe, $request_type_id, $next_level);
                    if (!mysqli_stmt_execute($stmt_find_next)) throw new Exception("Execute failed (find next): " . mysqli_stmt_error($stmt_find_next));
                    $find_next_result = mysqli_stmt_get_result($stmt_find_next);

                    if ($find_next_result && mysqli_num_rows($find_next_result) > 0) {
                        // --- 2a: There IS an existing next approver ---
                        $next_task = mysqli_fetch_assoc($find_next_result);
                        $next_task_id = (int)$next_task['id'];
                        $next_approver_id = (int)$next_task['approver_id'];

                        // Update next task to 'pending'
                        $update_next_sql = "UPDATE `request_approvers` SET `status` = 'pending' WHERE `id` = ?";
                        $stmt_update_next = mysqli_prepare($conDB, $update_next_sql);
                        if (!$stmt_update_next) throw new Exception("Prepare failed (update next): " . mysqli_error($conDB));
                        mysqli_stmt_bind_param($stmt_update_next, "i", $next_task_id);
                        if (!mysqli_stmt_execute($stmt_update_next)) throw new Exception("Execute failed (update next): " . mysqli_stmt_error($stmt_update_next));
                        mysqli_stmt_close($stmt_update_next);

                        // Update main request table status
                        $update_main_pending_sql = "UPDATE `$main_table_name` SET `current_status` = 'pending_approval', `current_approval_level` = ? WHERE `$inv_column_name` = ?";
                        $stmt_main_pending = mysqli_prepare($conDB, $update_main_pending_sql);
                        if (!$stmt_main_pending) throw new Exception("Prepare failed (update main pending-next): " . mysqli_error($conDB));
                        mysqli_stmt_bind_param($stmt_main_pending, "is", $next_level, $inv_no_safe);
                        if (!mysqli_stmt_execute($stmt_main_pending)) throw new Exception("Execute failed (update main pending-next): " . mysqli_stmt_error($stmt_main_pending));
                        mysqli_stmt_close($stmt_main_pending);

                        // Prepare details for notification
                        $next_approver_details = getEmployeeDetailsForApproval($conDB, $next_approver_id);
                        if ($next_approver_details) {
                            $result_payload['next_approver'] = $next_approver_details;
                            $result_payload['next_approver_id'] = $next_approver_id;

                            // --- [FIX] SEND NOTIFICATION TO NEXT APPROVER ---
                            $notification_title = "New $friendly_label";
                            $notification_message = "A $friendly_label ($inv_no_safe) is now pending your approval.";
                            // Dynamic URL based on request type
                            if ($request_type === 'smart_request') {
                                $notification_url = "all_requests.php?status=pending_approval";
                            } elseif ($request_type === 'vacation_request') {
                                $notification_url = "all_applied_vac.php?status=my_pending";
                            } elseif ($request_type === 'loan_request') {
                                $notification_url = "all_applied_loan.php?status=my_pending";
                            } else {
                                $notification_url = "all_requests.php"; // Fallback
                            }
                            create_browser_notification($conDB, $next_approver_id, $notification_title, $notification_message, $notification_url);

                            if ($next_approver_details['email']) {
                                // Fetch request details for email template
                                $template_data = get_request_details_for_email($conDB, $inv_no_safe, $request_type, $next_approver_details['name']);

                                if ($template_data) {
                                    send_approval_email($conDB, $next_approver_details['email'], $next_approver_details['name'], $notification_title, $request_type, $template_data);
                                } else {
                                    // Fallback to simple email if details not found
                                    $email_body = "Dear " . htmlspecialchars($next_approver_details['name']) . ",<br><br>A $friendly_label ($inv_no_safe) is now pending your approval. Please log in to the portal to review it.<br><br>Thank you.";
                                    send_approval_email($conDB, $next_approver_details['email'], $next_approver_details['name'], $notification_title, $request_type, ['APPROVER_NAME' => $next_approver_details['name'], 'REQUEST_ID' => $inv_no_safe]);
                                }
                            }
                            // --- [END FIX] ---

                        } else {
                        }
                    } else {
                        // --- 2b: This is FINAL APPROVAL (no more approvers in chain) ---
                        // Set appropriate status based on request type
                        $final_status = 'approved'; // Default to 'approved' for most requests
                        $review_status = null;       // Review status for vacation requests
                        $is_leave_request = false;   // Flag for LV- requests stored in emp_vacation
                        $leave_final_email_sent = false; // Prevent duplicate creator email for LV-

                        if ($request_type === 'vacation_request') {
                            // Default to approved; only certain cases should complete here
                            $final_status = 'approved';
                            $review_status = null;

                            // Check vacation type
                            $sql_vac_check = "SELECT `vac_type`, `fly_type` FROM `$main_table_name` WHERE `$inv_column_name` = ? LIMIT 1";
                            $stmt_vac_check = mysqli_prepare($conDB, $sql_vac_check);
                            $is_annual_fly = false;
                            $is_local_annual = false;
                            $is_fly_emergency = false;
                            $is_asset_clearance = (stripos($note_safe, 'Asset Clearance') !== false);

                            if ($stmt_vac_check) {
                                mysqli_stmt_bind_param($stmt_vac_check, "s", $inv_no_safe);
                                if (mysqli_stmt_execute($stmt_vac_check)) {
                                    $res_vac = mysqli_stmt_get_result($stmt_vac_check);
                                    if ($row_vac = mysqli_fetch_assoc($res_vac)) {
                                        $vac_type_val = strtolower($row_vac['vac_type']);
                                        $fly_type_val = strtolower($row_vac['fly_type']);
                                        $is_annual_fly = ($vac_type_val === 'fly' && $fly_type_val === 'annual');
                                        $is_local_annual = ($vac_type_val !== 'fly' && $fly_type_val === 'annual');
                                        $is_fly_emergency = ($vac_type_val === 'fly' && $fly_type_val === 'emergency');
                                    }
                                    if ($res_vac) mysqli_free_result($res_vac);
                                }
                                mysqli_stmt_close($stmt_vac_check);
                            }

                            // Rules:
                            // - Fly | Annual: stay approved until travel email + payments + adjustments handled
                            // - Local | Annual: stay approved; complete only after adjustments (handled in updateVacationAdjustments)
                            // - Fly | Emergency: stay approved; complete on adjustments and deduct balance once
                            // - Asset Clearance approvals must NOT complete
                            // - Other non-annual types: can complete here
                            if (!$is_annual_fly && !$is_local_annual && !$is_fly_emergency && !$is_asset_clearance) {
                                $final_status = 'completed';
                                $review_status = 'C';
                            }
                        } elseif ($request_type === 'general_request') {
                            // General requests go to 'waiting_for_delivery' after approval
                            // They will be marked 'completed' when all items are delivered
                            $final_status = 'waiting_for_delivery';
                        }

                        // Update main table with final status
                        if ($request_type === 'vacation_request' && $review_status !== null) {
                            // Update vacation with both status and review
                            $update_main_approved_sql = "UPDATE `$main_table_name` SET `current_status` = ?, `review` = ?, `current_approval_level` = ? WHERE `$inv_column_name` = ?";
                            $stmt_main_approved = mysqli_prepare($conDB, $update_main_approved_sql);
                            if (!$stmt_main_approved) throw new Exception("Prepare failed (update main approved): " . mysqli_error($conDB));
                            mysqli_stmt_bind_param($stmt_main_approved, "ssis", $final_status, $review_status, $current_level, $inv_no_safe);
                        } else {
                            // Standard update for other request types or annual fly vacations
                            $update_main_approved_sql = "UPDATE `$main_table_name` SET `current_status` = ?, `current_approval_level` = ? WHERE `$inv_column_name` = ?";
                            $stmt_main_approved = mysqli_prepare($conDB, $update_main_approved_sql);
                            if (!$stmt_main_approved) throw new Exception("Prepare failed (update main approved): " . mysqli_error($conDB));
                            mysqli_stmt_bind_param($stmt_main_approved, "sis", $final_status, $current_level, $inv_no_safe);
                        }
                        if (!mysqli_stmt_execute($stmt_main_approved)) throw new Exception("Execute failed (update main approved): " . mysqli_stmt_error($stmt_main_approved));
                        mysqli_stmt_close($stmt_main_approved);


                        // --- [NEW] UPDATE VACATION BALANCE ON FINAL APPROVAL ---
                        if ($request_type == 'vacation_request') {
                            // We need the integer ID from the emp_vacation table
                            $vacation_id = null;
                            $vacation_emp_id = null; // Employee who created the request
                            $vacation_type = null;   // Vacation type to decide fly flag
                            $vacation_start_date = null; // Start date to check if vacation is active
                            $request_inv_no = null;  // To check if it's a Leave Request (LV-*)
                            // Use the dynamic main_table_name which we know is 'emp_vacation' for this request type
                            $sql_get_id = "SELECT `id`, `emp_id`, `vac_type`, `start_date`, `request_inv_no` FROM `$main_table_name` WHERE `$inv_column_name` = ? LIMIT 1";
                            $stmt_get_id = mysqli_prepare($conDB, $sql_get_id);

                            if ($stmt_get_id) {
                                mysqli_stmt_bind_param($stmt_get_id, "s", $inv_no_safe);
                                if (mysqli_stmt_execute($stmt_get_id)) {
                                    $res_id = mysqli_stmt_get_result($stmt_get_id);
                                    if ($row_id = mysqli_fetch_assoc($res_id)) {
                                        $vacation_id = (int)$row_id['id'];
                                        $vacation_emp_id = isset($row_id['emp_id']) ? (int)$row_id['emp_id'] : null;
                                        $vacation_type = isset($row_id['vac_type']) ? $row_id['vac_type'] : null;
                                        $vacation_start_date = isset($row_id['start_date']) ? $row_id['start_date'] : null;
                                        $request_inv_no = isset($row_id['request_inv_no']) ? $row_id['request_inv_no'] : null;
                                    }
                                    if ($res_id) mysqli_free_result($res_id); // Free result
                                } else {
                                }
                                mysqli_stmt_close($stmt_get_id);
                            } else {
                            }

                            if ($vacation_id > 0) {
                                // CRITICAL: Only update balance if this is NOT an asset clearance approval
                                // Balance should only be updated when HR_Payroll approves, not during asset clearance
                                $is_asset_clearance = (stripos($note_safe, 'Asset Clearance') !== false);
                                
                                if (!$is_asset_clearance) {
                                    // Call the new function you added (it's at the end of this file)
                                    if (!update_vacation_balance_on_approval($conDB, $vacation_id)) {
                                        // Log an error, but don't throw an exception, as the approval itself is done.
                                    } else {
                                    }
                                }

                                // --- [UPDATED] Fly Status Management ---
                                // Set fly=1 at final HR_Payroll approval, except Encashment
                                if ($final_status === 'completed' && !empty($vacation_emp_id)) {
                                    $vac_type_lower = strtolower($vacation_type ?? '');
                                    if ($vac_type_lower !== 'encashed') {
                                        $stmtFly = mysqli_prepare($conDB, "UPDATE employees SET fly = 1 WHERE emp_id = ?");
                                        if ($stmtFly) {
                                            mysqli_stmt_bind_param($stmtFly, "i", $vacation_emp_id);
                                            mysqli_stmt_execute($stmtFly);
                                            mysqli_stmt_close($stmtFly);
                                        }
                                    }
                                }

                                $is_leave_request = !empty($request_inv_no) && strpos($request_inv_no, 'LV-') === 0;

                                if ($is_leave_request && $vacation_emp_id) {
                                    $creator_details = getEmployeeDetailsForApproval($conDB, $vacation_emp_id);
                                    if ($creator_details && !empty($creator_details['email'])) {
                                        $final_subject = "Excuse Leave Approved - " . $inv_no_safe;

                                        // Build CC list with HR Payroll
                                        $cc_emails = [];
                                        $hr_cc_label = '';
                                        $hr_payroll_result = mysqli_query($conDB, "SELECT e.name, al.email FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type='hr_payroll' AND e.status=1 AND al.email IS NOT NULL AND al.email != '' ORDER BY e.emp_id ASC LIMIT 1");
                                        if ($hr_payroll_result && ($hrp = mysqli_fetch_assoc($hr_payroll_result))) {
                                            if (!empty($hrp['email'])) {
                                                $cc_emails[$hrp['email']] = $hrp['name'];
                                                $hr_cc_label = $hrp['name'] . " (" . $hrp['email'] . ")";
                                            }
                                        }
                                        if ($hr_payroll_result) mysqli_free_result($hr_payroll_result);

                                        // Build rich template data explicitly for leave_request (avoid empty template)
                                        $base_url = get_base_url();
                                        $request_url = $base_url . '/all_applied_vac.php?status=my_approved';
                                        $leave_label = !empty($vacation_type) ? ucfirst($vacation_type) . ' Leave' : 'Excuse Leave';
                                        $template_data_lr = [
                                            'APPROVER_NAME' => $creator_details['name'],
                                            'REQUEST_TYPE' => $leave_label . ' - Approved',
                                            'REQUEST_TYPE_LOWER' => strtolower($leave_label) . ' approved',
                                            'REQUEST_ID' => $inv_no_safe,
                                            'EMPLOYEE_NAME' => $creator_details['name'],
                                            'START_DATE' => !empty($vacation_start_date) ? date('d M Y', strtotime($vacation_start_date)) : '',
                                            'END_DATE' => '',
                                            'DURATION' => isset($row_id['vacdays']) ? (int)$row_id['vacdays'] : '',
                                            'REQUEST_URL' => $request_url,
                                            'EMAIL_MESSAGE' => 'Your excuse leave has been fully approved.' . ($hr_cc_label ? ' CC: ' . $hr_cc_label : '')
                                        ];

                                        // Send email using leave_request type so CC applies
                                        if (function_exists('send_approval_email')) {
                                            $leave_final_email_sent = send_approval_email($conDB, $creator_details['email'], $creator_details['name'], $final_subject, 'leave_request', $template_data_lr, $cc_emails);
                                        }
                                    }
                                }

                                // Leave requests handled above for email; no fly status change here
                            } else {
                            }
                        }
                        // --- [END NEW] ---

                        // --- [FIX] NOTIFY CREATOR OF FINAL APPROVAL ---
                        $creator_id = null;
                        $creator_id_query = mysqli_query($conDB, "SELECT emp_id FROM `$main_table_name` WHERE `$inv_column_name` = '$inv_no_safe' LIMIT 1");
                        if ($creator_id_query && $creator_row = mysqli_fetch_assoc($creator_id_query)) {
                            $creator_id = (int)$creator_row['emp_id'];
                        }
                        if ($creator_id_query) mysqli_free_result($creator_id_query);

                        if ($creator_id > 0) {
                            $creator_details = getEmployeeDetailsForApproval($conDB, $creator_id);
                            if ($creator_details) {
                                // Create a more specific subject line for approval
                                if ($request_type === 'vacation_request') {
                                    $notification_title = "Vacation Request Approved - $inv_no_safe";
                                } elseif ($request_type === 'loan_request') {
                                    $notification_title = "Loan Request Approved - $inv_no_safe";
                                } elseif ($request_type === 'smart_request') {
                                    $notification_title = "Smart Request Approved - $inv_no_safe";
                                } else {
                                    $notification_title = "$friendly_label Approved";
                                }

                                $notification_message = "Your $friendly_label ($inv_no_safe) has been fully approved.";
                                // Dynamic URL based on request type
                                if ($request_type === 'smart_request') {
                                    $notification_url = "open_request.php?id=" . urlencode($inv_no_safe);
                                } elseif ($request_type === 'vacation_request') {
                                    $notification_url = "my_vacations.php";
                                } else {
                                    $notification_url = "all_requests.php"; // Fallback
                                }
                                create_browser_notification($conDB, $creator_id, $notification_title, $notification_message, $notification_url);

                                if ($creator_details['email']) {
                                    // Avoid duplicate email for leave requests when already sent with CC
                                    if ($is_leave_request && $leave_final_email_sent) {
                                    } else {
                                        // Fetch request details for email template
                                        $template_data = get_request_details_for_email($conDB, $inv_no_safe, $request_type, $creator_details['name']);

                                        if ($template_data) {
                                            // Modify the template to indicate it's approved
                                            if (isset($template_data['REQUEST_TYPE'])) {
                                                $template_data['REQUEST_TYPE'] = $template_data['REQUEST_TYPE'] . ' - Approved';
                                            }
                                            send_approval_email($conDB, $creator_details['email'], $creator_details['name'], $notification_title, $request_type, $template_data);
                                        } else {
                                            // Fallback if details not found
                                            send_approval_email($conDB, $creator_details['email'], $creator_details['name'], $notification_title, $request_type, ['APPROVER_NAME' => $creator_details['name'], 'REQUEST_ID' => $inv_no_safe]);
                                        }
                                    }
                                }
                            }
                        }
                        // --- [END FIX] ---

                        // --- [NEW] NOTIFY FINANCE MANAGER FOR SMART REQUESTS ---
                        // Finance Manager should ALWAYS be notified when smart request reaches final approval
                        // so they can assign a payer, regardless of whether they were in the approval chain
                        if ($request_type === 'smart_request') {
                            // Get Finance Manager (Department 2)
                            $finance_manager_details = getDeptManager($conDB, 2);

                            if ($finance_manager_details && !empty($finance_manager_details['email'])) {
                                // Check if Finance Manager was already in the approval chain (to avoid duplicate notification)
                                $fm_in_chain = false;
                                $check_fm_sql = "SELECT COUNT(*) as cnt FROM `request_approvers` 
                                                WHERE `request_inv_no` = ? 
                                                AND `request_type_id` = ? 
                                                AND `approver_id` = ?";
                                $stmt_check_fm = mysqli_prepare($conDB, $check_fm_sql);
                                if ($stmt_check_fm) {
                                    mysqli_stmt_bind_param($stmt_check_fm, "sii", $inv_no_safe, $request_type_id, $finance_manager_details['emp_id']);
                                    if (mysqli_stmt_execute($stmt_check_fm)) {
                                        $res_fm = mysqli_stmt_get_result($stmt_check_fm);
                                        if ($row_fm = mysqli_fetch_assoc($res_fm)) {
                                            $fm_in_chain = ((int)$row_fm['cnt'] > 0);
                                        }
                                        if ($res_fm) mysqli_free_result($res_fm);
                                    }
                                    mysqli_stmt_close($stmt_check_fm);
                                }

                                // Only send notification if Finance Manager was NOT already notified as an approver
                                if (!$fm_in_chain) {
                                    $fm_notification_title = "Smart Request Requires Payer Assignment - $inv_no_safe";
                                    $fm_notification_message = "Smart Request $inv_no_safe has been approved and requires you to assign a payer.";
                                    $fm_notification_url = "open_request.php?id=" . urlencode($inv_no_safe);

                                    // Send browser notification
                                    create_browser_notification($conDB, $finance_manager_details['emp_id'], $fm_notification_title, $fm_notification_message, $fm_notification_url);

                                    // Send email notification
                                    $fm_template_data = get_request_details_for_email($conDB, $inv_no_safe, $request_type, $finance_manager_details['name']);
                                    if ($fm_template_data) {
                                        // Customize message for Finance Manager
                                        $fm_template_data['EMAIL_MESSAGE'] = "This request has been fully approved and requires you to assign a payer from your department.";
                                        send_approval_email($conDB, $finance_manager_details['email'], $finance_manager_details['name'], $fm_notification_title, $request_type, $fm_template_data);
                                    } else {
                                        // Fallback email
                                        $fm_email_body = "Dear " . htmlspecialchars($finance_manager_details['name']) . ",<br><br>Smart Request <b>$inv_no_safe</b> has been fully approved and requires you to assign a payer from your department.<br><br>Please review the request and assign a payer.";
                                        send_approval_email($conDB, $finance_manager_details['email'], $finance_manager_details['name'], $fm_notification_title, $request_type, ['APPROVER_NAME' => $finance_manager_details['name'], 'REQUEST_ID' => $inv_no_safe, 'EMAIL_MESSAGE' => $fm_email_body]);
                                    }
                                } else {
                                }
                            } else {
                            }
                        }
                        // --- [END FINANCE MANAGER NOTIFICATION] ---


                        // --- Finance Manager Payer Assignment (if smart_request) ---
                        // [This logic is specific to smart_request, but safe to keep]
                        if ($request_type == 'smart_request') {
                            // ... (Finance manager notification logic as before) ...
                        }
                    }
                    if ($find_next_result) mysqli_free_result($find_next_result);
                } // End if/else (new chain vs existing chain)

            } // End if ($action == 'approve')
            else {
                // --- Action was 'reject' ---
                $update_main_rejected_sql = "UPDATE `$main_table_name` SET `current_status` = 'rejected', `current_approval_level` = ? WHERE `$inv_column_name` = ?";
                $stmt_main_rejected = mysqli_prepare($conDB, $update_main_rejected_sql);
                if (!$stmt_main_rejected) throw new Exception("Prepare failed (update main rejected): " . mysqli_error($conDB));
                mysqli_stmt_bind_param($stmt_main_rejected, "is", $current_level, $inv_no_safe);
                if (!mysqli_stmt_execute($stmt_main_rejected)) throw new Exception("Execute failed (update main rejected): " . mysqli_stmt_error($stmt_main_rejected));
                mysqli_stmt_close($stmt_main_rejected);


                // --- Rejection Notification Logic ---
                // Create a more specific subject line for rejection
                if ($request_type === 'vacation_request') {
                    $notification_title = "Vacation Request Rejected - $inv_no_safe";
                } elseif ($request_type === 'loan_request') {
                    $notification_title = "Loan Request Rejected - $inv_no_safe";
                } elseif ($request_type === 'smart_request') {
                    $notification_title = "Smart Request Rejected - $inv_no_safe";
                } else {
                    $notification_title = "Request Rejected";
                }

                $notification_message = "Request " . htmlspecialchars($inv_no_safe) . " was rejected by " . htmlspecialchars($userwel ?? 'Approver') . ". Reason: " . htmlspecialchars($note_safe);
                // MODIFICATION: Make URL dynamic based on request type
                $notification_url = "my_vacations.php"; // Default for creator
                if ($request_type == 'smart_request') {
                    $notification_url = "open_request.php?id=" . urlencode($inv_no_safe);
                } elseif ($request_type == 'vacation_request') {
                    $notification_url = "my_vacations.php"; // <-- Adjust this URL
                }

                $approver_notification_url = "all_applied_vac.php"; // Default for approvers
                if ($request_type == 'smart_request') {
                    $approver_notification_url = "open_request.php?id=" . urlencode($inv_no_safe);
                } elseif ($request_type == 'vacation_request') {
                    $approver_notification_url = "all_applied_vac.php"; // <-- Adjust this URL
                }


                // 1. Notify the Creator
                $creator_id = null;
                $creator_id_query = mysqli_query($conDB, "SELECT emp_id FROM `$main_table_name` WHERE `$inv_column_name` = '$inv_no_safe' LIMIT 1");
                if ($creator_id_query && $creator_row = mysqli_fetch_assoc($creator_id_query)) {
                    $creator_id = (int)$creator_row['emp_id'];
                }
                if ($creator_id_query) mysqli_free_result($creator_id_query); // Free result

                if ($creator_id > 0 && $creator_id != $current_user_id_safe) {
                    $creator_details = getEmployeeDetailsForApproval($conDB, $creator_id); // Get details for email
                    if (function_exists('create_browser_notification')) {
                        create_browser_notification($conDB, $creator_id, $notification_title, $notification_message, $notification_url);
                    }
                    // --- [FIX] SEND REJECTION EMAIL TO CREATOR ---
                    if ($creator_details && $creator_details['email']) {
                        // Fetch request details for email template
                        $template_data = get_request_details_for_email($conDB, $inv_no_safe, $request_type, $creator_details['name']);

                        if ($template_data) {
                            // Modify the template to indicate it's rejected
                            if (isset($template_data['REQUEST_TYPE'])) {
                                $template_data['REQUEST_TYPE'] = $template_data['REQUEST_TYPE'] . ' - Rejected';
                            }
                            send_approval_email($conDB, $creator_details['email'], $creator_details['name'], $notification_title, $request_type, $template_data);
                        } else {
                            // Fallback if details not found
                            send_approval_email($conDB, $creator_details['email'], $creator_details['name'], $notification_title, $request_type, ['APPROVER_NAME' => $creator_details['name'], 'REQUEST_ID' => $inv_no_safe]);
                        }
                    }
                    // --- [END FIX] ---
                }

                // 2. Notify Previous Approvers
                $prev_approvers_sql = "SELECT `approver_id` FROM `request_approvers` WHERE `request_inv_no` = ? AND `request_type_id` = ? AND `status` = 'approved'";
                $stmt_prev = mysqli_prepare($conDB, $prev_approvers_sql);
                if ($stmt_prev) {
                    mysqli_stmt_bind_param($stmt_prev, "si", $inv_no_safe, $request_type_id);
                    if (mysqli_stmt_execute($stmt_prev)) {
                        $prev_result = mysqli_stmt_get_result($stmt_prev);
                        while ($prev_row = mysqli_fetch_assoc($prev_result)) {
                            $prev_approver_id = (int)$prev_row['approver_id'];
                            if ($prev_approver_id > 0 && $prev_approver_id != $current_user_id_safe) {
                                $prev_approver_details = getEmployeeDetailsForApproval($conDB, $prev_approver_id); // Get details for email
                                if (function_exists('create_browser_notification')) {
                                    create_browser_notification($conDB, $prev_approver_id, $notification_title, $notification_message, $approver_notification_url);
                                }
                                // --- [FIX] SEND REJECTION EMAIL TO PREVIOUS APPROVERS ---
                                if ($prev_approver_details && $prev_approver_details['email']) {
                                    // Fetch request details for email template
                                    $template_data = get_request_details_for_email($conDB, $inv_no_safe, $request_type, $prev_approver_details['name']);

                                    if ($template_data) {
                                        // Modify the template to indicate it's rejected
                                        if (isset($template_data['REQUEST_TYPE'])) {
                                            $template_data['REQUEST_TYPE'] = $template_data['REQUEST_TYPE'] . ' - Rejected';
                                        }
                                        send_approval_email($conDB, $prev_approver_details['email'], $prev_approver_details['name'], $notification_title, $request_type, $template_data);
                                    } else {
                                        // Fallback if details not found
                                        send_approval_email($conDB, $prev_approver_details['email'], $prev_approver_details['name'], $notification_title, $request_type, ['APPROVER_NAME' => $prev_approver_details['name'], 'REQUEST_ID' => $inv_no_safe]);
                                    }
                                }
                                // --- [END FIX] ---
                            }
                        }
                        mysqli_free_result($prev_result); // Free result
                    }
                    mysqli_stmt_close($stmt_prev);
                }
                // --- End Rejection Notification Logic ---

            } // End if/else (approve vs reject)

            // ** Commit Transaction **
            mysqli_commit($conDB);
            return $result_payload; // Return success payload

        } catch (Exception $e) {
            // ** Rollback Transaction on Error **
            mysqli_rollback($conDB);
            // Provide a more specific error message if possible, otherwise generic
            $errorMessage = 'An error occurred during the approval process: ' . $e->getMessage();
            return ['status' => 'error', 'message' => $errorMessage];
        }
    } // End function handle_approval_action
}



/**
 * Gets the full approval chain with names and statuses for display.
 * @param mysqli $conDB Database connection
 * @param string $inv_no The request's invoice number
 * @param string $request_type The type of request (e.g., 'smart_request')
 * @return array List of approval chain steps with status and details
 */
if (!function_exists('get_approval_chain_status')) {
    function get_approval_chain_status($conDB, $inv_no, $request_type)
    {
        $chain = [];
        if (!$conDB) return $chain;

        $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
        if (!$type_query || mysqli_num_rows($type_query) == 0) {
            if ($type_query) mysqli_free_result($type_query); // Free result
            return $chain; // Return empty array if type is invalid
        }
        $type_row = mysqli_fetch_assoc($type_query);
        mysqli_free_result($type_query); // Free result
        $request_type_id = (int)$type_row['id'];

        $sql = "SELECT ra.*, e.name as approver_name
                FROM `request_approvers` ra
                LEFT JOIN `employees` e ON ra.approver_id = e.emp_id /* LEFT JOIN ensures entry shows even if employee deleted */
                WHERE ra.`request_inv_no` = '" . escape_string($inv_no) . "'
                  AND ra.`request_type_id` = $request_type_id
                ORDER BY ra.`approval_level`";

        $query = mysqli_query($conDB, $sql);
        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                // Handle case where employee might be deleted/missing
                $row['approver_name'] = $row['approver_name'] ?? ('Unknown ID: ' . $row['approver_id']);
                $chain[] = $row;
            }
            mysqli_free_result($query); // Free result
        } else {
        }
        return $chain;
    }
}


/**
 * Finds the current pending approver's ID.
 * @param mysqli $conDB Database connection
 * @param string $inv_no The request's invoice number
 * @param string $request_type The type of request (e.g., 'smart_request')
 * @return int|null The emp_id of the current approver, or null if none pending or error
 */
if (!function_exists('get_current_approver')) {
    function get_current_approver($conDB, $inv_no, $request_type)
    {
        if (!$conDB) return null;

        $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
        if (!$type_query || mysqli_num_rows($type_query) == 0) {
            if ($type_query) mysqli_free_result($type_query); // Free result
            return null;
        }
        $type_row = mysqli_fetch_assoc($type_query);
        mysqli_free_result($type_query); // Free result
        $request_type_id = (int)$type_row['id'];

        $sql = "SELECT `approver_id`
                FROM `request_approvers`
                WHERE `request_inv_no` = '" . escape_string($inv_no) . "'
                  AND `request_type_id` = $request_type_id
                  AND `status` = 'pending'
                ORDER BY `approval_level` LIMIT 1";

        $query = mysqli_query($conDB, $sql);
        if ($query && mysqli_num_rows($query) > 0) {
            $row = mysqli_fetch_assoc($query);
            mysqli_free_result($query); // Free result
            return (int)$row['approver_id'];
        } elseif (!$query) {
        }
        if ($query) mysqli_free_result($query); // Free result if no rows
        // No pending approver found or query failed
        return null;
    }
}

/**
 * Gets the total count of pending approvals for a specific user across all request types.
 * @param mysqli $conDB Database connection
 * @param int $emp_id The employee's ID
 * @return int The number of pending approvals
 */
if (!function_exists('get_pending_approval_count')) {
    function get_pending_approval_count($conDB, $emp_id)
    {
        if (!$conDB || !is_numeric($emp_id) || $emp_id <= 0) return 0;

        $emp_id_safe = (int)$emp_id;
        $sql = "SELECT COUNT(DISTINCT ra.request_inv_no, ra.request_type_id) as pending_count /* Count distinct requests */
                FROM `request_approvers` ra
                WHERE ra.`approver_id` = $emp_id_safe AND ra.`status` = 'pending'";

        $query = mysqli_query($conDB, $sql);
        if ($query && mysqli_num_rows($query) > 0) {
            $row = mysqli_fetch_assoc($query);
            mysqli_free_result($query); // Free result
            return (int)$row['pending_count'];
        } elseif (!$query) {
        }
        if ($query) mysqli_free_result($query); // Free result if no rows
        return 0;
    }
}


/**
 * Helper to get essential employee details (name, email) for notifications/approvals.
 * Returns null email if invalid or empty.
 * @param mysqli $conDB Database connection
 * @param int $emp_id The employee's ID
 * @return array|null Associative array with 'name' and 'email', or null if not found/error
 */
if (!function_exists('getEmployeeDetailsForApproval')) {
    function getEmployeeDetailsForApproval($conDB, $emp_id)
    {
        if (!$conDB || !is_numeric($emp_id) || $emp_id <= 0) return null;
        $emp_id_safe = (int)$emp_id;
        $sql = "SELECT e.name, COALESCE(al.email, e.email) AS email
            FROM `employees` e
            LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id
            WHERE e.`emp_id` = ? AND e.`status` = 1
            LIMIT 1"; // Added status check
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $emp_id_safe);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                $details = mysqli_fetch_assoc($result);
                mysqli_free_result($result);
                mysqli_stmt_close($stmt);
                // Return null email if it's empty or invalid format, but still return name
                $details['email'] = (!empty($details['email']) && filter_var($details['email'], FILTER_VALIDATE_EMAIL)) ? $details['email'] : null;
                $details['name'] = $details['name'] ?? 'Unknown Employee'; // Default name
                return $details;
            } else {
                if ($result) mysqli_free_result($result); // Free result
            }
        } else {
        }
        mysqli_stmt_close($stmt);
        return null; // Not found or error
    }
}


// --- Department/Role Specific Employee Fetching ---

if (!function_exists('getDeptManager')) {
    function getDeptManager($conDB, $dept_id)
    {
        if (!$conDB || !is_numeric($dept_id) || $dept_id <= 0) return null;
        $dept_id_safe = (int)$dept_id;
        // Prioritize 'Manager' emptype, then 'dept_user' user_type
        $sql = "SELECT e.emp_id, e.name, al.email
                FROM `employees` e
                LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id
                WHERE e.`dept`= ? AND e.`status`= 1
                  AND (e.`emptype`='Manager' OR al.`user_type` = 'dept_user')
                ORDER BY FIELD(e.emptype, 'Manager') DESC, FIELD(al.user_type, 'dept_user') DESC
                LIMIT 1";
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $dept_id_safe);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                $details = mysqli_fetch_assoc($result);
                mysqli_free_result($result);
                mysqli_stmt_close($stmt);
                $details['email'] = (!empty($details['email']) && filter_var($details['email'], FILTER_VALIDATE_EMAIL)) ? $details['email'] : null;
                return $details;
            } else {
                if ($result) mysqli_free_result($result); // Free result
            }
        } else {
        }
        mysqli_stmt_close($stmt);
        return null;
    }
}

if (!function_exists('getFinancePersonnel')) {
    function getFinancePersonnel($conDB, $dept_id = 2)
    { // Default Finance Dept ID = 2
        if (!$conDB) return [];
        $dept_id_safe = (int)$dept_id;
        $sql = "SELECT e.emp_id, e.name, al.email
                FROM `employees` e
                LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id
                WHERE e.`dept`= ? AND e.`status`= 1
                ORDER BY FIELD(e.emptype, 'Manager', 'Assistant', 'Supporter'), e.name";
        $stmt = mysqli_prepare($conDB, $sql);
        $personnel = [];
        if (!$stmt) {
            return $personnel;
        }
        mysqli_stmt_bind_param($stmt, "i", $dept_id_safe);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $row['email'] = (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) ? $row['email'] : null;
                    $personnel[] = $row;
                }
                mysqli_free_result($result);
            }
        } else {
        }
        mysqli_stmt_close($stmt);
        return $personnel;
    }
}

if (!function_exists('getHRPersonnel')) {
    function getHRPersonnel($conDB, $dept_id = 5)
    { // Default HR Dept ID = 5
        if (!$conDB) return [];
        $dept_id_safe = (int)$dept_id;
        $sql = "SELECT e.emp_id, e.name, al.email
                FROM `employees` e
                LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id
                WHERE e.`dept`= ? AND e.`status`= 1
                ORDER BY FIELD(e.emptype, 'Manager', 'Assistant', 'Supporter'), e.name";
        $stmt = mysqli_prepare($conDB, $sql);
        $personnel = [];
        if (!$stmt) {
            return $personnel;
        }
        mysqli_stmt_bind_param($stmt, "i", $dept_id_safe);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $row['email'] = (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) ? $row['email'] : null;
                    $personnel[] = $row;
                }
                mysqli_free_result($result);
            }
        } else {
        }
        mysqli_stmt_close($stmt);
        return $personnel;
    }
}


if (!function_exists('getGeneralManager')) {
    function getGeneralManager($conDB)
    {
        if (!$conDB) return null;
        // Find user with 'gm' user_type in admin_login, ensure active in employees
        $sql = "SELECT e.emp_id, e.name, al.email
                 FROM `admin_login` al
                 JOIN `employees` e ON al.emp_id = e.emp_id
                 WHERE al.`user_type`= 'gm' AND e.`status`= 1
                 LIMIT 1";
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            return null;
        }
        // No parameters to bind
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                $details = mysqli_fetch_assoc($result);
                mysqli_free_result($result);
                mysqli_stmt_close($stmt);
                $details['email'] = (!empty($details['email']) && filter_var($details['email'], FILTER_VALIDATE_EMAIL)) ? $details['email'] : null;
                return $details;
            } else {
                if ($result) mysqli_free_result($result); // Free result
            }
        } else {
        }
        mysqli_stmt_close($stmt);
        return null;
    }
}

if (!function_exists('getEmployeeDetails')) {
    function getEmployeeDetails($conDB, $emp_id)
    {
        $default_return = ['name' => 'N/A', 'email' => null]; // Consistent return type
        if (!$conDB || !is_numeric($emp_id) || $emp_id <= 0) return $default_return;
        $emp_id_clean = (int)$emp_id;
        $sql = "SELECT e.name, COALESCE(al.email, e.email) AS email
            FROM `employees` e
            LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id
            WHERE e.`emp_id`= ?
            LIMIT 1";
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            return $default_return;
        }
        mysqli_stmt_bind_param($stmt, "i", $emp_id_clean);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                $details = mysqli_fetch_assoc($result);
                mysqli_free_result($result);
                mysqli_stmt_close($stmt);
                $details['email'] = (!empty($details['email']) && filter_var($details['email'], FILTER_VALIDATE_EMAIL)) ? $details['email'] : null;
                $details['name'] = $details['name'] ?? 'Unknown';
                return $details;
            } else {
                if ($result) mysqli_free_result($result); // Free result
            }
        } else {
        }
        mysqli_stmt_close($stmt);
        return $default_return; // Not found or error
    }
}


/*=====  End of Approval System Functions  ======*/


/*=============================================
=      Browser Notification Functions         =
=============================================*/

/**
 * Creates a browser notification entry in the database using prepared statements.
 * Logs errors for better debugging.
 *
 * @param mysqli $conDB Database connection
 * @param int $emp_id The employee ID to notify
 * @param string $title The notification title (max 255 chars)
 * @param string $message The notification body (max 1000 chars - check DB limit)
 * @param string $url The URL to open on click (relative preferred, max 255 chars)
 * @return bool True on success, false on failure
 */
if (!function_exists('create_browser_notification')) {
    function create_browser_notification($conDB, $emp_id, $title, $message, $url)
    {
        // ** Input Validation and Sanitization **
        if (!$conDB) {
            return false;
        }
        if (!is_numeric($emp_id) || $emp_id <= 0) {
            return false;
        }
        $title_trimmed = trim($title);
        if (empty($title_trimmed)) {
            return false;
        }
        $message_trimmed = trim($message);
        if (empty($message_trimmed)) {
            return false;
        }
        $url_trimmed = trim($url);
        if (empty($url_trimmed)) {
            return false;
        }

        $emp_id_safe = (int)$emp_id;
        // No need for escape_string with prepared statements
        $title_final = mb_substr($title_trimmed, 0, 255);
        $message_final = mb_substr($message_trimmed, 0, 1000); // Adjust length as needed
        $url_final = mb_substr($url_trimmed, 0, 255);

        // ** Use Prepared Statements **
        $sql = "INSERT INTO `user_notifications` (`emp_id`, `title`, `message`, `url`, `created_at`)
                VALUES (?, ?, ?, ?, NOW())"; // Add created_at

        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            return false;
        }

        // Bind parameters: i=integer, s=string
        mysqli_stmt_bind_param($stmt, "isss", $emp_id_safe, $title_final, $message_final, $url_final);

        // ** Execute and Check **
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected_rows === 1) {
                return true;
            } else {
                return false; // Or handle as needed
            }
        } else {
            // Log specific error
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}

/**
 * Displays a browser notification popup using the Web Notifications API.
 * Returns JavaScript code that should be executed on client side.
 *
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $url URL to navigate to on click
 * @param string $icon_url Optional icon URL
 * @return string JavaScript code for browser notification
 */
if (!function_exists('trigger_browser_notification_popup')) {
    function trigger_browser_notification_popup($title, $message, $url, $icon_url = '')
    {
        $title_js = addslashes(htmlspecialchars($title, ENT_QUOTES));
        $message_js = addslashes(htmlspecialchars($message, ENT_QUOTES));
        $url_js = addslashes(htmlspecialchars($url, ENT_QUOTES));
        $icon_js = addslashes(htmlspecialchars($icon_url, ENT_QUOTES));
        
        $js_code = "<script>
        (function() {
            if ('Notification' in window) {
                if (Notification.permission === 'granted') {
                    showNotification();
                } else if (Notification.permission !== 'denied') {
                    Notification.requestPermission().then(function(permission) {
                        if (permission === 'granted') {
                            showNotification();
                        }
                    });
                }
            }
            function showNotification() {
                var options = {body: '$message_js', icon: '$icon_js', badge: 'assets/images/logo-small.png', tag: 'notif-' + Date.now()};
                var notif = new Notification('$title_js', options);
                notif.onclick = function() {window.focus(); window.location.href = '$url_js'; notif.close();};
                setTimeout(function() {notif.close();}, 6000);
            }
        })();
        </script>";
        return $js_code;
    }
}

/**
 * Combined function: Creates database notification AND shows browser popup.
 * Best for immediate + persistent notifications.
 *
 * @param mysqli $conDB Database connection
 * @param int $emp_id Employee ID
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $url URL to navigate to
 * @param string $icon_url Optional icon URL
 * @return array ['db_saved' => bool, 'js_code' => string]
 */
if (!function_exists('create_and_show_notification')) {
    function create_and_show_notification($conDB, $emp_id, $title, $message, $url, $icon_url = '')
    {
        $db_saved = create_browser_notification($conDB, $emp_id, $title, $message, $url);
        $js_code = trigger_browser_notification_popup($title, $message, $url, $icon_url);
        return ['db_saved' => $db_saved, 'js_code' => $js_code];
    }
}

/**
 * Fetches all unread notifications for a specific user using prepared statements.
 * Logs errors.
 *
 * @param mysqli $conDB Database connection
 * @param int $emp_id The employee's ID
 * @return array List of unread notifications, ordered by creation date descending
 */
if (!function_exists('get_unread_notifications')) {
    function get_unread_notifications($conDB, $emp_id)
    {
        $notifications = [];
        if (!$conDB) {
            return $notifications;
        }
        if (!is_numeric($emp_id) || $emp_id <= 0) {
            return $notifications;
        }

        $emp_id_safe = (int)$emp_id;
        // Select specific columns and order by creation time
        $sql = "SELECT `id`, `title`, `message`, `url`, `created_at`
                FROM `user_notifications`
                WHERE `emp_id` = ? AND `is_read` = 0
                ORDER BY `created_at` DESC";

        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            return $notifications;
        }

        mysqli_stmt_bind_param($stmt, "i", $emp_id_safe);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    // Sanitize output just in case (though should be safe from DB)
                    $row['title'] = htmlspecialchars($row['title']);
                    $row['message'] = htmlspecialchars($row['message']);
                    $row['url'] = htmlspecialchars($row['url']);
                    $notifications[] = $row;
                }
                mysqli_free_result($result);
            } else {
                // Log error getting result
            }
        } else {
            // Log execution error
        }
        mysqli_stmt_close($stmt);

        return $notifications;
    }
}

/**
 * Marks a list of notification IDs as read for a specific user using prepared statements.
 * Ensures the user owns the notifications being marked.
 * Logs errors.
 *
 * @param mysqli $conDB Database connection
 * @param int $emp_id The employee ID whose notifications are being marked
 * @param array $notification_ids An array of notification IDs to mark as read
 * @return bool True if the query executed successfully (even if 0 rows affected), false on failure.
 */
if (!function_exists('mark_notifications_as_read')) {
    function mark_notifications_as_read($conDB, $emp_id, $notification_ids)
    {
        // Validate inputs
        if (!$conDB) {
            return false;
        }
        if (!is_numeric($emp_id) || $emp_id <= 0 || empty($notification_ids) || !is_array($notification_ids)) {
            return false;
        }

        // Sanitize all IDs to integers and filter out invalid ones
        $ids_safe = array_map('intval', $notification_ids);
        $ids_safe = array_filter($ids_safe, function ($id) {
            return $id > 0;
        });

        if (empty($ids_safe)) {
            return false; // No valid IDs to process
        }

        $emp_id_safe = (int)$emp_id;
        //$ids_list = implode(',', $ids_safe); // Used only for logging now

        // ** Use Prepared Statement with dynamic IN clause **
        // Construct placeholders for IN clause dynamically (?,?,?)
        $placeholders = implode(',', array_fill(0, count($ids_safe), '?'));
        $sql = "UPDATE `user_notifications`
                SET `is_read` = 1
                WHERE `emp_id` = ? AND `id` IN ($placeholders)"; // Add emp_id check

        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            return false;
        }

        // Bind parameters: first the emp_id (integer), then all notification IDs (integers)
        $types = 'i' . str_repeat('i', count($ids_safe)); // 'i' for emp_id, 'i' for each id
        $bind_params = array_merge([$emp_id_safe], $ids_safe); // Combine emp_id and notification ids

        // Use call_user_func_array or argument unpacking (...) for dynamic binding
        // Note: Argument unpacking (...) requires PHP 5.6+
        if (!mysqli_stmt_bind_param($stmt, $types, ...$bind_params)) {
            mysqli_stmt_close($stmt);
            return false;
        }


        // Execute and Check
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected_rows >= 0) { // Even 0 affected rows is a success if the query ran and no error occurred
                // Log success, including how many rows were actually updated
                return true; // Indicate success (query executed without error)
            } else {
                // Should not happen with MySQLi, but good practice
                return false; // Error indicated by negative affected rows
            }
        } else {
            // Log execution error
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}


/*=====  End of Browser Notification Functions ======*/


/**
 * Helper function to get system setting value with basic caching using prepared statements.
 * Logs errors.
 *
 * @param mysqli $conDB Database connection
 * @param string $setting_name The name of the setting
 * @return string|null The setting value or null if not found/error
 */
if (!function_exists('get_setting')) {
    // Simple static cache to reduce DB queries for the same setting within a single request
    $settings_cache = [];

    function get_setting($conDB, $setting_name, $default = null)
    {
        global $settings_cache; // Access the cache

        if (!$conDB) {
            return $default;
        }
        $setting_name_trimmed = trim($setting_name);
        if (empty($setting_name_trimmed)) {
            return $default;
        }

        // Check cache first
        if (isset($settings_cache[$setting_name_trimmed])) {
            return $settings_cache[$setting_name_trimmed];
        }

        // [FIX] Use app_settings table
        $sql = "SELECT `setting_value` FROM `app_settings` WHERE `setting_name` = ? LIMIT 1";
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "s", $setting_name_trimmed);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $value = $row['setting_value'];
                $settings_cache[$setting_name_trimmed] = $value; // Store in cache
                mysqli_free_result($result);
                mysqli_stmt_close($stmt);
                return $value;
            } else {
                // Setting not found, return default value
                $settings_cache[$setting_name_trimmed] = $default;
                if ($default !== null) {
                } else {
                }
                if ($result) mysqli_free_result($result); // Free result even if no rows
            }
        } else {
        }
        mysqli_stmt_close($stmt);
        return $default; // Return default if setting not found or error
    }
}


/**
 * =================================================================
 * == NEW FUNCTION - FOR FINAL VACATION APPROVAL
 * =================================================================
 * Updates the emp_vacation_balance table after a vacation is fully approved.
 * This should only be called on the *final* approval.
 *
 * @param mysqli $conDB
 * @param int $vacation_id The ID from the `emp_vacation` table.
 * @return bool True on success, false on failure.
 */
if (!function_exists('update_vacation_balance_on_approval')) {
    function update_vacation_balance_on_approval($conDB, $vacation_id)
    {
        if (!$conDB || !is_numeric($vacation_id) || $vacation_id <= 0) {
            return false;
        }

        $vac_id_safe = (int)$vacation_id;

        // 1. Get the approved vacation details
        $sql_vac = "SELECT `emp_id`, `vacdays`, `is_deductible`, `vac_type`, `fly_type`, `remarks` FROM `emp_vacation` WHERE `id` = ?";
        $stmt_vac = mysqli_prepare($conDB, $sql_vac);
        if (!$stmt_vac) {
            return false;
        }
        mysqli_stmt_bind_param($stmt_vac, "i", $vac_id_safe);
        if (!mysqli_stmt_execute($stmt_vac)) {
            mysqli_stmt_close($stmt_vac);
            return false;
        }
        $res_vac = mysqli_stmt_get_result($stmt_vac);
        if (mysqli_num_rows($res_vac) == 0) {
            mysqli_free_result($res_vac); // ADDED
            mysqli_stmt_close($stmt_vac);
            return false;
        }
        $vac_details = mysqli_fetch_assoc($res_vac);
        mysqli_free_result($res_vac); // ADDED
        mysqli_stmt_close($stmt_vac);

        $emp_id = (int)$vac_details['emp_id'];
        $days_to_deduct = (float)$vac_details['vacdays'];
        $remarks = trim(strtolower($vac_details['remarks'] ?? ''));
        $vac_type_lower = trim(strtolower($vac_details['vac_type'] ?? ''));

        // [NEW] Check if this is an ENCASHMENT request
        $is_encashment = ($remarks === 'encashment') || ($vac_type_lower === 'encashed');        // 2. CHECK IF THIS VACATION TYPE SHOULD BE DEDUCTED FROM BALANCE
        // We only deduct 'Fly' (annual/emergency) and 'Local Vacation'.
        // We do NOT deduct 'Sick Leave', 'Business Trip', etc.
        // `is_deductible` = 1 means deduct from salary (e.g. Casual), 0 = no salary deduction
        // Your logic seems to be: 'Fly' and 'Local Vacation' *should* be deducted from balance.
        // Your old `is_deductible` flag seems to be for *salary* deduction, not balance deduction.
        // Let's rely on `vac_type` and `fly_type`.

        $is_balance_deductible = false;
        if ($vac_details['vac_type'] == 'Fly' && ($vac_details['fly_type'] == 'annual' || $vac_details['fly_type'] == 'emergency')) {
            $is_balance_deductible = true;
        }
        if ($vac_details['vac_type'] == 'Local Vacation') {
            $is_balance_deductible = true;
        }

        // [NEW] Encashment is ALWAYS balance deductible
        if ($is_encashment) {
            $is_balance_deductible = true;
        }

        if (!$is_balance_deductible) {
            return true; // Not an error, just no action needed.
        }

        // 3. Get Employee's Contract Details (for total days)
        // [FIXED] Changed `e.contract_id` to `e.vac_period` and aliased it as `contract_id`
        // This matches the employees schema where `vac_period` is the FK to `contract_period.id`
        $sql_emp = "SELECT e.vac_period AS contract_id, cp.vac_period 
                    FROM `employees` e
                    JOIN `contract_period` cp ON e.vac_period = cp.id
                    WHERE e.emp_id = ?";
        $stmt_emp = mysqli_prepare($conDB, $sql_emp);
        if (!$stmt_emp) {
            return false;
        }
        mysqli_stmt_bind_param($stmt_emp, "i", $emp_id);
        if (!mysqli_stmt_execute($stmt_emp)) {
            mysqli_stmt_close($stmt_emp);
            return false;
        }
        $res_emp = mysqli_stmt_get_result($stmt_emp);
        if (mysqli_num_rows($res_emp) == 0) {
            mysqli_free_result($res_emp); // ADDED
            mysqli_stmt_close($stmt_emp);
            return false;
        }
        $emp_details = mysqli_fetch_assoc($res_emp);
        mysqli_free_result($res_emp); // ADDED
        mysqli_stmt_close($stmt_emp);

        $contract_id = (int)$emp_details['contract_id']; // This will now correctly get the ID from `e.vac_period`
        $total_contract_days = (float)$emp_details['vac_period']; // e.g., 30

        // 4. Get the *latest* balance row for this employee
        $sql_latest_balance = "SELECT * FROM `emp_vacation_balance` WHERE `emp_id` = ? ORDER BY `id` DESC LIMIT 1";
        $stmt_latest = mysqli_prepare($conDB, $sql_latest_balance);
        if (!$stmt_latest) {
            return false;
        }
        mysqli_stmt_bind_param($stmt_latest, "i", $emp_id);
        if (!mysqli_stmt_execute($stmt_latest)) {
            mysqli_stmt_close($stmt_latest);
            return false;
        }
        $res_latest = mysqli_stmt_get_result($stmt_latest);
        $latest_balance = mysqli_fetch_assoc($res_latest);
        mysqli_free_result($res_latest); // ADDED
        mysqli_stmt_close($stmt_latest);

        // 5. Calculate new values
        $old_used_days = 0.0;
        $old_remaining_balance = $total_contract_days;
        $carryover_days = 0.0;
        $period_start = date('Y-m-d'); // Default
        $period_end = date('Y-m-d', strtotime('+1 year')); // Default

        if ($latest_balance) {
            // Found a previous record, use it as the baseline
            $old_used_days = (float)$latest_balance['used_days'];
            $old_remaining_balance = (float)$latest_balance['remaining_balance'];
            $carryover_days = (float)$latest_balance['carryover_days'];
            // We assume the total days and period start/end are the same,
            // as this is just an update *within* the current period.
            $total_contract_days = (float)$latest_balance['total_days'];
            $period_start = $latest_balance['period_start'];
            $period_end = $latest_balance['period_end'];
        } else {
            // No previous record. This is the first deduction.
            // We need to create a period. Let's assume it starts from contract_id (if it's a date)
            // This part is tricky. Your schema for `addManualHistory` implies period_start/end are set manually.
            // For now, let's assume a manual record *must* exist.
            // A more robust system would fetch contract start/end dates.
            // For now, if no record, let's just use the contract total.
            // We are missing period_start and period_end if no manual record exists.
            // This is a potential flaw in the logic if manual history isn't added first.
            // Let's default to today's date for the period if missing, but this should be reviewed.
            // $period_start = $latest_balance['period_start'] ?? date('Y-m-d');
            // $period_end = $latest_balance['period_end'] ?? date('Y-m-d', strtotime('+1 year'));
        }

        // [NEW] Encashment logic: Deduct the encashed days from available balance
        if ($is_encashment) {
            // For encashment, deduct the specific number of days from available_balance
            // and add to used_days
            $new_used_days = $old_used_days + $days_to_deduct;
            $max_allowable = ($total_contract_days + $carryover_days);
            
            // Ensure we don't exceed total available days
            if ($new_used_days > $max_allowable) {
                $new_used_days = $max_allowable;
            }
            
            // Calculate remaining and available balances
            $new_remaining_balance = $max_allowable - $new_used_days;
            $new_available_balance = $new_remaining_balance;
            // SYNC: Keep total_days synchronized with available_balance to prevent balance discrepancies
            $total_contract_days = $new_available_balance;
        } else {
            // Normal vacation deduction logic
            $new_used_days = $old_used_days + $days_to_deduct;
            $max_allowable = ($total_contract_days + $carryover_days);
            if ($new_used_days > $max_allowable) {
                // Prevent negative balances: cap used_days at total available
                $new_used_days = $max_allowable;
            }
            $new_remaining_balance = $max_allowable - $new_used_days;
            // Available balance should probably be the same as remaining
            $new_available_balance = $new_remaining_balance;
            // SYNC: Keep total_days synchronized with available_balance to prevent balance discrepancies
            $total_contract_days = $new_available_balance;
        }

        // 6. Check if a balance record ALREADY EXISTS FOR THIS SPECIFIC VACATION
        // This prevents updating a balance record for a different vacation in the same period
        $sql_check_vac = "SELECT id FROM `emp_vacation_balance` WHERE `vac_id` = ? LIMIT 1";
        $stmt_check_vac = mysqli_prepare($conDB, $sql_check_vac);
        if (!$stmt_check_vac) {
            return false;
        }
        mysqli_stmt_bind_param($stmt_check_vac, "i", $vac_id_safe);
        mysqli_stmt_execute($stmt_check_vac);
        $res_check_vac = mysqli_stmt_get_result($stmt_check_vac);
        $row_check_vac = mysqli_fetch_assoc($res_check_vac);
        mysqli_free_result($res_check_vac);
        mysqli_stmt_close($stmt_check_vac);

        if ($row_check_vac) {
            // This vacation already has a balance record, UPDATE it
            $sql_update = "UPDATE `emp_vacation_balance` SET 
                `period_end` = ?,
                `total_days` = ?,
                `used_days` = ?,
                `remaining_balance` = ?,
                `available_balance` = ?,
                `carryover_days` = ?,
                `last_updated` = NOW()
                WHERE `vac_id` = ?";
            $stmt_update = mysqli_prepare($conDB, $sql_update);
            if (!$stmt_update) {
                return false;
            }
            $id_int = (int)$row_check_vac['id'];
            mysqli_stmt_bind_param(
                $stmt_update,
                "sdddddi",
                $period_end,
                $total_contract_days,
                $new_used_days,
                $new_remaining_balance,
                $new_available_balance,
                $carryover_days,
                $vac_id_safe
            );
            if (mysqli_stmt_execute($stmt_update)) {
                mysqli_stmt_close($stmt_update);
                error_log("DEBUG: Updated existing balance record for vacation ID {$vac_id_safe}");
                return true;
            } else {
                mysqli_stmt_close($stmt_update);
                return false;
            }
        } else {
            // No existing record for this vacation, INSERT a new one
            // Use INSERT ... ON DUPLICATE KEY UPDATE to handle race condition:
            // If a balance record for this (emp_id, contract_id, period_start) already exists
            // from another approval stage, UPDATE it instead of failing
            $sql_insert_balance = "INSERT INTO `emp_vacation_balance` 
                                    (`emp_id`, `vac_id`, `contract_id`, `period_start`, `period_end`, 
                                     `total_days`, `used_days`, `remaining_balance`, `available_balance`, `carryover_days`, `last_updated`) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                                   ON DUPLICATE KEY UPDATE
                                   `vac_id` = ?,
                                   `period_end` = ?,
                                   `total_days` = ?,
                                   `used_days` = ?,
                                   `remaining_balance` = ?,
                                   `available_balance` = ?,
                                   `carryover_days` = ?,
                                   `last_updated` = NOW()";
            $stmt_insert = mysqli_prepare($conDB, $sql_insert_balance);
            if (!$stmt_insert) {
                return false;
            }
            // Bind for INSERT values
            mysqli_stmt_bind_param(
                $stmt_insert,
                "iiisssddddisddddd",
                $emp_id,
                $vac_id_safe,
                $contract_id,
                $period_start,
                $period_end,
                $total_contract_days,
                $new_used_days,
                $new_remaining_balance,
                $new_available_balance,
                $carryover_days,
                // ON DUPLICATE KEY UPDATE values
                $vac_id_safe,
                $period_end,
                $total_contract_days,
                $new_used_days,
                $new_remaining_balance,
                $new_available_balance,
                $carryover_days
            );
            if (mysqli_stmt_execute($stmt_insert)) {
                mysqli_stmt_close($stmt_insert);
                error_log("DEBUG: Inserted or updated balance record for vacation ID {$vac_id_safe}");
                return true;
            } else {
                $error = mysqli_stmt_error($stmt_insert);
                error_log("DEBUG: INSERT/UPDATE failed for vacation ID {$vac_id_safe}: {$error}");
                mysqli_stmt_close($stmt_insert);
                return false;
            }
        }
    }
}

/**
 * =================================================================
 * == TRAVEL COMPANY EMAIL NOTIFICATION FUNCTION
 * =================================================================
 * Sends employee travel information to the traveling company
 * when an annual fly vacation is approved.
 * 
 * @param object $conDB Database connection
 * @param string $employee_name Employee's full name
 * @param string $passport_no Employee's passport number
 * @param string $passport_expiry Employee's passport expiry date
 * @param string $country_name Destination country name
 * @param string $departure_date Flight departure date
 * @param string $arrival_date Flight arrival date
 * @param string $request_inv_no Vacation request invoice number (for reference)
 * @param string $cc_email Optional CC email address (e.g., gr_officer)
 * @return bool True if email sent successfully, false otherwise
 */
if (!function_exists('send_travel_company_email')) {
    function send_travel_company_email($conDB, $employee_name, $passport_no, $passport_expiry, $country_name, $departure_date, $arrival_date, $request_inv_no = '', $cc_email = '', $passport_file_path = '', $passport_file_name = '')
    {
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return false;
        }

        // Fetch SMTP settings from app_settings table
        $smtp_host = get_setting($conDB, 'smtp_host');
        $smtp_port = (int)get_setting($conDB, 'smtp_port');
        $smtp_user = get_setting($conDB, 'smtp_user');
        $smtp_pass = get_setting($conDB, 'smtp_pass');
        $smtp_from_email = get_setting($conDB, 'from_email');
        $smtp_from_name = get_setting($conDB, 'from_name', 'Al Mutlak HR System');
        $smtp_secure = get_setting($conDB, 'smtp_encryption');

        // Get traveling company emails (stored as JSON array)
        $traveling_company_email_setting = get_setting($conDB, 'traveling_company_email');

        if (empty($traveling_company_email_setting)) {
            return false;
        }

        // Parse JSON array of emails
        $traveling_company_emails = [];
        $decoded = json_decode($traveling_company_email_setting, true);
        
        if (is_array($decoded)) {
            // It's a JSON array of emails
            foreach ($decoded as $email) {
                $email = trim($email);
                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $traveling_company_emails[] = $email;
                }
            }
        } else {
            // Fallback: treat as single email (backward compatibility)
            $email = trim($traveling_company_email_setting);
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $traveling_company_emails[] = $email;
            }
        }

        if (empty($traveling_company_emails)) {
            return false;
        }

        if (empty($smtp_host) || empty($smtp_port) || empty($smtp_user) || empty($smtp_pass) || empty($smtp_from_email)) {
            return false;
        }

        // Format dates for display
        $departure_formatted = !empty($departure_date) ? date('d M Y', strtotime($departure_date)) : 'N/A';
        $arrival_formatted = !empty($arrival_date) ? date('d M Y', strtotime($arrival_date)) : 'N/A';
        $passport_expiry_formatted = !empty($passport_expiry) ? date('d M Y', strtotime($passport_expiry)) : 'N/A';

        // Build email body
        $site_title = get_setting($conDB, 'site_title', 'Al-Mutlak');
        $logo_url = get_setting($conDB, 'logo', 'assets/images/logo.png');

        $body_html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Travel Information</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 30px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .email-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 30px; text-align: center; }
        .email-header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .email-body { padding: 30px; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table tr { border-bottom: 1px solid #e9ecef; }
        .info-table tr:last-child { border-bottom: none; }
        .info-table td { padding: 15px 10px; }
        .info-table td:first-child { font-weight: 600; color: #667eea; width: 40%; }
        .info-table td:last-child { color: #333; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; }
        .highlight { background-color: #fff9e6; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🛫 Employee Travel Information</h1>
            <p style="margin: 5px 0 0 0; font-size: 14px;">New Vacation Travel Details</p>
        </div>
        <div class="email-body">
            <p>Dear Travel Coordinator,</p>
            <p>We are pleased to inform you that an employee has been approved for annual vacation travel. Below are the traveler details:</p>
            
            <table class="info-table">
                <tr>
                    <td>Traveler Name:</td>
                    <td><strong>' . htmlspecialchars((string)($employee_name ?? 'N/A')) . '</strong></td>
                </tr>
                <tr>
                    <td>Passport No:</td>
                    <td>' . htmlspecialchars((string)($passport_no ?: 'N/A')) . '</td>
                </tr>
                <tr>
                    <td>Passport Expiry:</td>
                    <td>' . htmlspecialchars((string)($passport_expiry_formatted ?? 'N/A')) . '</td>
                </tr>
                <tr>
                    <td>Departure To:</td>
                    <td><strong>' . htmlspecialchars((string)($country_name ?: 'N/A')) . '</strong></td>
                </tr>
                <tr>
                    <td>Departure Date:</td>
                    <td>' . htmlspecialchars((string)($departure_formatted ?? 'N/A')) . '</td>
                </tr>
                <tr>
                    <td>Arrival Date:</td>
                    <td>' . htmlspecialchars((string)($arrival_formatted ?? 'N/A')) . '</td>
                </tr>
            </table>
            
            <div class="highlight">
                <strong>📋 Reference Number:</strong> ' . htmlspecialchars((string)($request_inv_no ?? 'N/A')) . '
            </div>
            
            <p>Please proceed with the necessary travel arrangements for the above employee.</p>
            <p>If you have any questions or require additional information, please contact our HR department.</p>
            
            <p style="margin-top: 30px;">Best regards,<br><strong>' . htmlspecialchars((string)($site_title ?? 'HR')) . ' HR Department</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated email from ' . htmlspecialchars((string)($site_title ?? 'HR')) . ' HR System.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>';

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_user;
            $mail->Password   = $smtp_pass;

            // Set encryption
            switch (strtolower($smtp_secure)) {
                case 'tls':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    break;
                case 'ssl':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    break;
                default:
                    $mail->SMTPSecure = false;
                    break;
            }

            $mail->Port       = $smtp_port;
            $mail->CharSet    = 'UTF-8';

            // Recipients
            $mail->setFrom($smtp_from_email, $smtp_from_name);
            
            // Add all traveling company emails as recipients
            foreach ($traveling_company_emails as $index => $tc_email) {
                if ($index === 0) {
                    // First email as primary recipient
                    $mail->addAddress($tc_email, 'Travel Company');
                } else {
                    // Additional emails as CC
                    $mail->addCC($tc_email, 'Travel Company');
                }
            }
            
            $mail->addReplyTo($smtp_from_email, $smtp_from_name);

            // Add CC if provided (e.g., gr_officer)
            if (!empty($cc_email) && filter_var($cc_email, FILTER_VALIDATE_EMAIL)) {
                $mail->addCC($cc_email, 'HR');
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Employee Travel Information - ' . $employee_name . ' - Ref: ' . $request_inv_no;
            $mail->Body    = $body_html;
            $mail->AltBody = strip_tags($body_html);

            // Attach passport file if provided
            if (!empty($passport_file_path) && file_exists($passport_file_path)) {
                $mail->addAttachment($passport_file_path, $passport_file_name);
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Get employee's current vacation balance from database
 * Reads from emp_vacation_balance table (updated daily by cron job)
 * 
 * @param mysqli $conDB Database connection
 * @param string $emp_id Employee ID
 * @return float Current available balance (returns 0 if not found)
 */
if (!function_exists('get_employee_vacation_balance_from_db')) {
    function get_employee_vacation_balance_from_db($conDB, $emp_id)
    {
        if (empty($emp_id)) {
            return 0.0;
        }

        $stmt = mysqli_query($conDB, "SELECT `available_balance` FROM `emp_vacation_balance` WHERE `emp_id` = '" . mysqli_real_escape_string($conDB, $emp_id) . "' ORDER BY `last_updated` DESC LIMIT 1");

        if ($stmt && mysqli_num_rows($stmt) > 0) {
            $row = mysqli_fetch_assoc($stmt);
            $balance = (float)$row['available_balance'];
            mysqli_free_result($stmt);
            return $balance;
        }

        return 0.0;
    }
}

/**
 * Calculate LIVE vacation balance for an employee using VacationCalculator
 * This performs real-time calculation based on current date, contract, and vacation history
 * Use this when you need the most up-to-date balance calculation
 * 
 * @param mysqli $conDB Database connection
 * @param string $emp_id Employee ID
 * @return float|null Current calculated available balance or null on error
 */
if (!function_exists('get_live_vacation_balance')) {
    function get_live_vacation_balance($conDB, $emp_id)
    {
        if (empty($emp_id)) {
            return null;
        }

        // Load VacationCalculator
        $calcFile = __DIR__ . '/vacation_calculator.php';
        if (!file_exists($calcFile)) {
            return null;
        }

        require_once $calcFile;

        if (!class_exists('VacationCalculator')) {
            return null;
        }

        try {
            $vc = new VacationCalculator($conDB);
            $result = $vc->getCalculatedBalance($emp_id);

            if ($result && isset($result['available_balance'])) {
                return (float)$result['available_balance'];
            }

            return null;
        } catch (Throwable $e) {
            return null;
        }
    }
}


// Helper: safely display values or show translated not_available
if (!function_exists('display_or_na')) {
    function display_or_na($val)
    {
        if (is_null($val) || $val === '' || $val === false) {
            return __('not_available');
        }
        return htmlspecialchars((string)$val);
    }
}


/**
 * Check if an employee is on an active vacation with cleared/kept assets
 * Active = current_status is 'approved' or 'complete' or 'completed' AND return_date is in the future
 * Cleared/Kept = assets have status of 'Assets Received' or 'Employee Keep Assets'
 * 
 * @param mysqli $conDB Database connection
 * @param int $emp_id Employee ID
 * @return bool True if employee is on active vacation with cleared/kept assets, false otherwise
 */
if (!function_exists('is_employee_on_active_vacation_with_cleared_assets')) {
    function is_employee_on_active_vacation_with_cleared_assets($conDB, $emp_id)
    {
        if (!$conDB || !is_numeric($emp_id) || $emp_id <= 0) {
            return false;
        }

        $emp_id_safe = (int)$emp_id;
        $today = date('Y-m-d');

        // Check for active vacation (approved, complete, or completed) with return_date in the future
        $sql_active_vac = "SELECT `id`, `emp_id` FROM `emp_vacation` 
                          WHERE `emp_id` = ? 
                          AND `current_status` IN ('approved', 'completed')
                          AND `return_date` > ?
                          ORDER BY `start_date` DESC 
                          LIMIT 1";

        $stmt = mysqli_prepare($conDB, $sql_active_vac);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "is", $emp_id_safe, $today);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);
        $has_active_vac = (mysqli_num_rows($result) > 0);
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);

        if (!$has_active_vac) {
            return false;
        }

        // Check if employee's assets are cleared or kept (status column, not return_status)
        $sql_assets = "SELECT `id` FROM `employee_assets` 
                      WHERE `emp_id` = ? 
                      AND `status` IN ('Assets Received', 'Employee Keep Assets')
                      LIMIT 1";

        $stmt = mysqli_prepare($conDB, $sql_assets);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $emp_id_safe);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);
        $has_cleared_assets = (mysqli_num_rows($result) > 0);
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);

        return $has_cleared_assets;
    }
}


/*=============================================
=      Approval Comments Helper Functions     =
=============================================*/

if (!function_exists('get_approval_comments')) {
    /**
     * Fetch all approval comments for a request
     * 
     * @param mysqli $conDB Database connection
     * @param string $request_inv_no Request invoice number
     * @param string $request_type Type of request
     * @return array Array of comments or empty array
     */
    function get_approval_comments($conDB, $request_inv_no, $request_type) {
        $comments = [];
        
        if (!$conDB) {
            return [];
        }
        
        $sql = "SELECT * FROM `approval_comments` 
                WHERE request_inv_no = ? AND request_type = ?
                ORDER BY comment_date ASC";
        
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            error_log("Error preparing get_approval_comments: " . mysqli_error($conDB));
            return [];
        }
        
        mysqli_stmt_bind_param($stmt, 'ss', $request_inv_no, $request_type);
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Error executing get_approval_comments: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return [];
        }
        
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $comments[] = $row;
        }
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
        
        return $comments;
    }
}

if (!function_exists('get_latest_approval_comment')) {
    /**
     * Get the most recent approval comment for a request
     * 
     * @param mysqli $conDB Database connection
     * @param string $request_inv_no Request invoice number
     * @param string $request_type Type of request
     * @return array|null Latest comment or null if none found
     */
    function get_latest_approval_comment($conDB, $request_inv_no, $request_type) {
        if (!$conDB) {
            return null;
        }
        
        $sql = "SELECT * FROM `approval_comments` 
                WHERE request_inv_no = ? AND request_type = ?
                ORDER BY comment_date DESC 
                LIMIT 1";
        
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            error_log("Error preparing get_latest_approval_comment: " . mysqli_error($conDB));
            return null;
        }
        
        mysqli_stmt_bind_param($stmt, 'ss', $request_inv_no, $request_type);
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Error executing get_latest_approval_comment: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null;
        }
        
        $result = mysqli_stmt_get_result($stmt);
        $comment = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
        
        return $comment;
    }
}

if (!function_exists('save_approval_comment_db')) {
    /**
     * Save approval comment directly to database
     * 
     * @param mysqli $conDB Database connection
     * @param string $request_inv_no Request invoice number
     * @param string $request_type Type of request
     * @param string $approval_action Action (approved, rejected, hold, adjusted)
     * @param int $approver_emp_id Employee ID of approver
     * @param string $approver_name Name of approver
     * @param string $comment_text The comment/review text
     * @param int $approval_level Optional approval level
     * @param int $approver_admin_id Optional admin ID of approver
     * @return int|false Inserted ID or false on failure
     */
    function save_approval_comment_db($conDB, $request_inv_no, $request_type, $approval_action, 
                                     $approver_emp_id, $approver_name, $comment_text, 
                                     $approval_level = 0, $approver_admin_id = null) {
        if (!$conDB) {
            return false;
        }
        
        $sql = "INSERT INTO `approval_comments` 
                (request_inv_no, request_type, approval_action, approver_emp_id, 
                 approver_admin_id, approver_name, approval_level, comment_text, comment_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            error_log("Error preparing save_approval_comment_db: " . mysqli_error($conDB));
            return false;
        }
        
        // Handle null approver_admin_id
        if ($approver_admin_id === null) {
            $approver_admin_id = 0;
        }
        
        mysqli_stmt_bind_param($stmt, 'sssiisis', 
            $request_inv_no, $request_type, $approval_action, $approver_emp_id,
            $approver_admin_id, $approver_name, $approval_level, $comment_text);
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Error executing save_approval_comment_db: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
        
        $inserted_id = mysqli_insert_id($conDB);
        mysqli_stmt_close($stmt);
        
        return $inserted_id;
    }
}

if (!function_exists('display_approval_comments_html')) {
    /**
     * Generate HTML to display approval comments
     * 
     * @param array $comments Array of comments from get_approval_comments()
     * @return string HTML markup
     */
    function display_approval_comments_html($comments) {
        if (empty($comments)) {
            return '<div class="alert alert-info"><i class="fa fa-info-circle"></i> No approval comments yet</div>';
        }
        
        $html = '<div class="approval-comments-timeline">';
        
        foreach ($comments as $comment) {
            $action = $comment['approval_action'] ?? 'reviewed';
            $approver = htmlspecialchars($comment['approver_name'] ?? 'Unknown');
            $text = htmlspecialchars($comment['comment_text'] ?? '');
            $date = $comment['comment_date'] ?? date('Y-m-d H:i:s');
            
            // Determine icon and color based on action
            $iconClass = 'fa-check text-success';
            $badgeClass = 'badge-success';
            
            if ($action === 'rejected') {
                $iconClass = 'fa-times text-danger';
                $badgeClass = 'badge-danger';
            } elseif ($action === 'hold') {
                $iconClass = 'fa-pause text-warning';
                $badgeClass = 'badge-warning';
            } elseif ($action === 'adjusted') {
                $iconClass = 'fa-cog text-info';
                $badgeClass = 'badge-info';
            }
            
            $html .= '
                <div class="approval-comment-item" style="margin-bottom: 20px; padding: 15px; border-left: 4px solid #007bff; background-color: #f8f9fa; border-radius: 4px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div>
                            <i class="fa ' . $iconClass . '" style="margin-right: 8px;"></i>
                            <strong>' . ucfirst($approver) . '</strong>
                            <span class="badge ' . $badgeClass . '" style="margin-left: 8px;">' . strtoupper($action) . '</span>
                        </div>
                        <small style="color: #999;">' . date('Y-m-d H:i', strtotime($date)) . '</small>
                    </div>
                    <div style="margin-left: 24px; padding: 12px; background-color: white; border-radius: 4px; border-left: 2px solid #007bff;">
                        ' . nl2br($text) . '
                    </div>
                </div>
            ';
        }
        
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('get_comments_count_by_action')) {
    /**
     * Get count of comments by approval action
     * 
     * @param mysqli $conDB Database connection
     * @param string $request_inv_no Request invoice number
     * @param string $request_type Type of request
     * @return array Associative array with counts by action
     */
    function get_comments_count_by_action($conDB, $request_inv_no, $request_type) {
        $counts = [
            'approved' => 0,
            'rejected' => 0,
            'hold' => 0,
            'adjusted' => 0
        ];
        
        if (!$conDB) {
            return $counts;
        }
        
        foreach (array_keys($counts) as $action) {
            $sql = "SELECT COUNT(*) as cnt FROM `approval_comments` 
                    WHERE request_inv_no = ? AND request_type = ? AND approval_action = ?";
            
            $stmt = mysqli_prepare($conDB, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sss', $request_inv_no, $request_type, $action);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($result);
                $counts[$action] = (int)($row['cnt'] ?? 0);
                mysqli_free_result($result);
                mysqli_stmt_close($stmt);
            }
        }
        
        return $counts;
    }
}

if (!function_exists('logApprovalComment')) {
    /**
     * Log approval comment to activity log (if ActivityLogger is available)
     * 
     * @param mysqli $conDB Database connection
     * @param string $request_inv_no Request invoice number
     * @param string $request_type Type of request
     * @param string $approval_action Action taken
     * @param string $comment_text The comment
     * @return bool Success status
     */
    function logApprovalComment($conDB, $request_inv_no, $request_type, $approval_action, $comment_text) {
        global $ActivityLogger;
        
        if (!isset($ActivityLogger) || !class_exists('ActivityLogger')) {
            return false;
        }
        
        try {
            $action_details = [
                'request_inv_no' => $request_inv_no,
                'request_type' => $request_type,
                'approval_action' => $approval_action,
                'comment_length' => strlen($comment_text)
            ];
            
            $ActivityLogger->logApprovalComment(
                $request_type,
                $request_inv_no,
                $approval_action,
                json_encode($action_details)
            );
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

/*=====  End of Approval Comments Helper Functions ======*/

/*===== Supervisor Validation Helper Functions =====*/

/**
 * Validates that an employee has a supervisor assigned and the supervisor is active
 * Checks supervisor existence in employees table, with fallback to admin_login table
 * 
 * @param mysqli $conDB Database connection
 * @param string|int $empId Employee ID to validate
 * @return array ['valid' => bool, 'message' => string]
 */
if (!function_exists('validate_employee_supervisor')) {
    function validate_employee_supervisor($conDB, $empId) {
        // Check that the employee has a supervisor assigned and that supervisor exists as an active user
        if (empty($empId)) {
            return ['valid' => false, 'message' => 'Employee ID is required.'];
        }

        $empIdEsc = mysqli_real_escape_string($conDB, $empId);
        $sql = "SELECT e.supervisor_id, e.status FROM employees e WHERE e.emp_id = '$empIdEsc' LIMIT 1";
        $res = mysqli_query($conDB, $sql);
        if (!$res || mysqli_num_rows($res) === 0) {
            return ['valid' => false, 'message' => 'Employee not found or inactive.'];
        }
        $row = mysqli_fetch_assoc($res);
        mysqli_free_result($res);

        if ((int)$row['status'] !== 1) {
            return ['valid' => false, 'message' => 'Employee is not active.'];
        }

        $supervisorId = $row['supervisor_id'] ?? '';
        if (empty($supervisorId)) {
            return ['valid' => false, 'message' => 'No supervisor assigned. Please assign a direct supervisor before submitting resignation.'];
        }

        // Verify supervisor exists in employees/admin_login and is active
        $supervisorEsc = mysqli_real_escape_string($conDB, $supervisorId);
        $supRes = mysqli_query($conDB, "SELECT e.emp_id, e.status FROM employees e WHERE e.emp_id = '$supervisorEsc' LIMIT 1");
        if ($supRes && mysqli_num_rows($supRes) > 0) {
            $supRow = mysqli_fetch_assoc($supRes);
            mysqli_free_result($supRes);
            if ((int)$supRow['status'] !== 1) {
                return ['valid' => false, 'message' => 'Assigned supervisor is not active.'];
            }
        } else {
            // Fallback: check admin_login table for active account
            $adminRes = mysqli_query($conDB, "SELECT emp_id, status FROM admin_login WHERE emp_id = '$supervisorEsc' LIMIT 1");
            if (!$adminRes || mysqli_num_rows($adminRes) === 0) {
                return ['valid' => false, 'message' => 'Assigned supervisor account not found.'];
            }
            $adminRow = mysqli_fetch_assoc($adminRes);
            mysqli_free_result($adminRes);
            if ((int)$adminRow['status'] !== 1) {
                return ['valid' => false, 'message' => 'Assigned supervisor account is inactive.'];
            }
        }

        return ['valid' => true, 'message' => 'Supervisor validation passed.'];
    }
}

/*=====  End of Supervisor Validation Helper Functions ======*/

/*====================================================
  Contract Period & Expiry Helpers
====================================================*/
if (!function_exists('getContractTermYearsFromVacPeriod')) {
    /**
     * Maps employees.vac_period code to contract term in years.
     * Known mappings from contract_period:
     *  - 3: 2 Years - 15
     *  - 4: 1 Year  - 21
     *  - 5: 2 Years - 21
     *  - 6: 1 Year  - 30
     *  - 7: 2 Years - 30
     * @param int|null $vacPeriod
     * @return int|null 1 or 2 years, or null if unknown
     */
    function getContractTermYearsFromVacPeriod(?int $vacPeriod): ?int
    {
        if ($vacPeriod === null) return null;
        if (in_array($vacPeriod, [4, 6], true)) return 1;
        if (in_array($vacPeriod, [3, 5, 7], true)) return 2;
        return null;
    }
}

if (!function_exists('computeContractExpiry')) {
    /**
     * Computes the next contract expiry date based on joining date and vac_period code.
     * The expiry is calculated by adding the contract term (1 or 2 years) from joining date
     * repeatedly until the result is on or after today (the current active year).
     *
     * @param string|null $joiningDate   Employee joining date (Y-m-d or any parsable format)
     * @param int|null    $vacPeriod     employees.vac_period code (3,4,5,6,7)
     * @param string      $format        Output date format (default: 'd M Y')
     * @return string|null Formatted date string, or null if not computable
     */
    function computeContractExpiry(?string $joiningDate, ?int $vacPeriod, string $format = 'd M Y'): ?string
    {
        $termYears = getContractTermYearsFromVacPeriod($vacPeriod);
        if (!$joiningDate || !$termYears) return null;
        try {
            $jd = new DateTime($joiningDate);
            $today = new DateTime();
            $expiry = clone $jd;
            while ($expiry < $today) {
                $expiry->modify("+{$termYears} years");
            }
            return $expiry->format($format);
        } catch (Exception $e) {
            return null;
        }
    }
}

