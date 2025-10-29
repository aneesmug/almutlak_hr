<?php

// --- PHPMailer ---
// Ensure the path is correct relative to this file's location
// Assuming vendor folder is at the same level as the 'includes' directory. Adjust if needed.
$mailerAutoloadPath = __DIR__ . '/../vendor/autoload.php'; // Example: Go up one level, then into vendor
if (file_exists($mailerAutoloadPath)) {
    require_once $mailerAutoloadPath;
    // error_log("DEBUG: PHPMailer autoload successful from: " . $mailerAutoloadPath); // Optional: Confirm loading
} else {
    // Log an error if PHPMailer cannot be found. Email functionality will be disabled.
    error_log("CRITICAL ERROR: PHPMailer autoload file not found at: " . $mailerAutoloadPath . ". Email notifications will NOT work.");
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
function escape_string($param)
{
    global $conDB; // Ensure connection is available
    if (!$conDB) {
        error_log("WARNING: escape_string called but \$conDB is not available. Using basic escaping (less secure).");
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


// --- Time Ago Functions ---
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
    if ($seconds <= 60) { return "just now"; }
    //Minutes
    else if ($minutes <= 60) { return ($minutes == 1) ? "one minute ago" : "$minutes minutes ago"; }
    //Hours
    else if ($hours <= 24) { return ($hours == 1) ? "an hour ago" : "$hours hrs ago"; }
    //Days
    else if ($days <= 7) { return ($days == 1) ? "yesterday" : "$days days ago"; }
    //Weeks
    else if ($weeks <= 4.3) { return ($weeks == 1) ? "a week ago" : "$weeks weeks ago"; }
    //Months
    else if ($months <= 12) { return ($months == 1) ? "a month ago" : "$months months ago"; }
    //Years
    else { return ($years == 1) ? "one year ago" : "$years years ago"; }
}
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
    if ($seconds <= 60) { return "الآن"; }
    //Minutes
    else if ($minutes <= 60) { return ($minutes == 1) ? "قبل دقيقة واحدة" : "$minutes دقائق مضت"; }
    //Hours
    else if ($hours <= 24) { return ($hours == 1) ? "قبل ساعة" : "$hours قبل ساعات"; }
    //Days
    else if ($days <= 7) { return ($days == 1) ? "أمس" : "$days قبل أيام"; }
    //Weeks
    else if ($weeks <= 4.3) { return ($weeks == 1) ? "قبل أسبوع" : "$weeks قبل أسابيع"; }
    //Months
    else if ($months <= 12) { return ($months == 1) ? "قبل شهر" : "$months قبل شهور"; }
    //Years
    else { return ($years == 1) ? "قبل عام" : "$years منذ سنوات"; }
}

// --- String & Number Utilities ---
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

function number_pad($number, $n)
{
    return str_pad((int) $number, $n, "0", STR_PAD_LEFT);
}

// --- Date & Time Utilities ---
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
        error_log("dateDiffDays Error: " . $e->getMessage());
        return "Error calculating difference";
    }
}

function getTotalDays($years, $months, $days)
{
    // Simplified, assumes average month length which might not be accurate for precise calculations
    // For exact day counts between dates, use dateDiffDays or DateTime::diff directly.
    $result = ($years * 360) + ($months * 30) + $days;
    return $result;
};

// --- Financial Calculations ---
function endOfService($joinDate, $endDate, $salary)
{
    try {
        $date1 = new DateTime($joinDate);
        $date2 = new DateTime($endDate);
    } catch (Exception $e) {
         error_log("endOfService Date Error: Invalid date format. Join: $joinDate, End: $endDate. Error: " . $e->getMessage());
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
        error_log("endOfService Salary Error: Invalid salary value '$salary'");
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

// --- Debugging Utilities ---
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
    foreach($trace as $item) {
        echo basename($item['file'] ?? '?') . ':' . ($item['line'] ?? '?') . ' - ' . ($item['function'] ?? '?') . "()\n";
    }
    echo '</pre>';
    if ($die) die(); // Optional: Stop execution
}

function dd($data) // Dump and Die
{
    debug($data, true);
}

function console_log($data)
{
    // Basic type handling for console output
    $output = json_encode($data);
    if ($output === false) {
        // Handle json encoding failure (e.g., non-UTF8 data, recursion)
        $output = "'PHP console_log: Error encoding data. Type: " . gettype($data) . "'";
    }
    echo '<script>';
    echo 'console.log("PHP DEBUG:", ' . $output . ');';
    echo '</script>';
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
function redirect($path = "", $delay = 0, $exit = true, $message = "")
{
    // Prevent header modification errors if output already started
    if (headers_sent($file, $line)) {
        error_log("Redirect cannot use header() - Output already started in $file on line $line");
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

// --- Input Sanitization ---
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

// --- JSON Response Helper ---
function send_json_response($title, $message, $type, $http_status_code = 200)
{
    // Prevent potential errors if headers already sent
    if (headers_sent($file, $line)) {
        error_log("send_json_response cannot set headers - Output already started in $file on line $line");
        // Still try to output JSON, but status code might be wrong
        echo json_encode(['title' => $title, 'message' => $message, 'type' => $type]);
    } else {
        http_response_code($http_status_code);
        header('Content-Type: application/json; charset=utf-8'); // Ensure charset
        echo json_encode(['title' => $title, 'message' => $message, 'type' => $type]);
    }
    exit(); // Terminate script after sending JSON response
}

// --- PDO Debug Helper ---
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

// --- Name Parsing Utility ---
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

// --- Search Highlighting ---
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

// --- Formatting Utilities ---
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

// --- Age Calculation ---
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
            __('years'), $age->y,
            __('months'), $age->m,
            __('days'), $age->d
        );
    } catch (Exception $e) {
        error_log("ageDOB Error: Invalid date format '$dob'. Error: " . $e->getMessage());
        return __('invalid_date_format');
    }
}


// --- Pagination ---
/**
 * Generates a full set of pagination controls with detailed item counts.
 * [Existing Docblock]...
 */
function generate_pagination_controls($current_page, $total_pages, $total_items, $items_per_page, $limit_options, $show_all, $base_params = [], $unfiltered_total_items = null)
{
    // Basic validation
    if (!is_numeric($current_page) || !is_numeric($total_pages) || !is_numeric($total_items) || !is_numeric($items_per_page) || !is_array($limit_options) || !is_bool($show_all)) {
        error_log("generate_pagination_controls: Invalid argument types provided.");
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
             return "<div class='row mt-4'><div class='col-12 text-muted'>{$single_page_text}</div></div>";
         }
        return ''; // Return empty if no items
    }

    $html = '<div class="row mt-4"><div class="col-12 d-md-flex justify-content-between align-items-center">';

    // --- Items per page dropdown ---
    $html .= '<div class="mb-3 mb-md-0">';
    $html .= '<div class="form-inline">';
    $html .= '<label for="limitFilter" class="mr-2 font-weight-bold">'.__('show').':</label>';
    $html .= '<select class="form-control form-control-sm" id="limitFilter" onchange="applyFilters()">';
    foreach ($limit_options as $limit) {
        $limit = (int)$limit;
        if ($limit <= 0) continue; // Skip invalid limit options
        $selected = (!$show_all && $items_per_page == $limit) ? 'selected' : '';
        $html .= "<option value='{$limit}' {$selected}>{$limit}</option>";
    }
    $all_selected = $show_all ? 'selected' : '';
    $html .= "<option value='all' {$all_selected}>".__('all_option')."</option>";
    $html .= "</select><span class='ml-2 text-muted'>".__('items_per_page')."</span>";
    $html .= "</div></div>";

    // --- Page info and navigation ---
    $html .= "<div class='d-flex align-items-center justify-content-center flex-wrap'>";

    // Displaying start and end item numbers and total items
    if ($total_items > 0) {
        $showing_text = '';
        if (!$show_all && $items_per_page > 0 && $total_pages > 0) {
            $start_item = (($current_page - 1) * $items_per_page) + 1;
            $end_item = min($start_item + $items_per_page - 1, $total_items);
            $showing_text = "".__('showing')." {$start_item} ".__('to')." {$end_item} ".__('of')." {$total_items} ".__('entries')."";
        } elseif ($show_all || $total_pages == 1) { // Also show for single page result
             $showing_text = "".__('showing')." 1 ".__('to')." {$total_items} ".__('of')." {$total_items} ".__('entries')."";
             if($show_all) $showing_text = "".__('showing_all')." {$total_items} ".__('entries')."";
        }

        if (!empty($showing_text) && $unfiltered_total_items !== null && $unfiltered_total_items > $total_items) {
             $showing_text .= " (".__('filtered_from')." {$unfiltered_total_items} ".__('entries').")";
        }

        if (!empty($showing_text)) {
            $html .= "<span class='text-muted mr-3'>{$showing_text}</span>";
        }
    } elseif ($unfiltered_total_items !== null && $unfiltered_total_items > 0) {
        // Show only the filtered message if total_items is 0 but unfiltered_total_items > 0
        $html .= "<span class='text-muted mr-3'>".__('showing')." 0 ".__('entries')." (".__('filtered_from')." {$unfiltered_total_items} ".__('entries').")</span>";
    }


    // Build page links only if there are multiple pages and not showing all
    if ($total_pages > 1 && !$show_all) {
        $html .= '<nav aria-label="Page navigation"><ul class="pagination mb-0">';

        // Helper function to build query string safely
        $build_query = function($page) use ($base_params) {
            return "?" . http_build_query(array_merge($base_params, ['page' => $page]));
        };

        $first_disabled = ($current_page <= 1) ? 'disabled' : '';
        $html .= "<li class='page-item {$first_disabled}'><a class='page-link' href='{$build_query(1)}'>".__('first')."</a></li>";

        $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
        $html .= "<li class='page-item {$prev_disabled}'><a class='page-link' href='{$build_query($current_page - 1)}'>".__('previous')."</a></li>";

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
        $html .= "<li class='page-item {$next_disabled}'><a class='page-link' href='{$build_query($current_page + 1)}'>".__('next')."</a></li>";

        $last_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
        $html .= "<li class='page-item {$last_disabled}'><a class='page-link' href='{$build_query($total_pages)}'>".__('last')."</a></li>";

        $html .= '</ul></nav>';
    }

    $html .= '</div>'; // End d-flex
    $html .= '</div></div>'; // End row and outer div
    return $html;
}


/*=============================================
=            Approval System Functions        =
=============================================*/

/**
 * Fetches all active employees suitable for being approvers.
 * Excludes 'employee' user_type. Includes user_type and dept.
 * @param mysqli $conDB Database connection
 * @return array List of potential approvers
 */
if (!function_exists('get_potential_approvers')) {
    function get_potential_approvers($conDB) {
        $employees = [];
        // Ensure admin_login alias `al` is used correctly
        $sql = "SELECT e.`emp_id`, e.`name`, al.`user_type`, e.`dept`
                FROM `employees` e
                JOIN `admin_login` al ON e.`emp_id` = al.`emp_id`
                WHERE al.`user_type` != 'employee' AND e.`status` = 1
                ORDER BY e.`name`";
        $query = mysqli_query($conDB, $sql);
        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                $employees[] = $row;
            }
        } else {
             error_log("get_potential_approvers: Database error - " . mysqli_error($conDB));
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
    function save_approval_chain($conDB, $inv_no, $request_type, $approver_ids) {
        if (!$conDB) {
             error_log("save_approval_chain: Database connection is not valid.");
             return false;
        }
        if (empty($inv_no) || empty($request_type) || !is_array($approver_ids) || empty($approver_ids)) {
             error_log("save_approval_chain: Invalid arguments provided.");
             return false;
        }

        // 1. Get the request_type_id
        $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
        if (!$type_query) {
             error_log("save_approval_chain: Error querying approval_request_types - " . mysqli_error($conDB));
             return false;
        }
        if (mysqli_num_rows($type_query) == 0) {
            error_log("save_approval_chain: Invalid request type '$request_type'. Not found in approval_request_types.");
            return false;
        }
        $type_row = mysqli_fetch_assoc($type_query);
        $request_type_id = (int)$type_row['id'];

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
                     error_log("save_approval_chain: Skipped invalid approver ID '$approver_id' for InvNo $inv_no.");
                }
            }

            // Commit transaction
            mysqli_commit($conDB);
            return true;

        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conDB);
            error_log("save_approval_chain: Transaction failed - " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Handles an approver's action (approve/reject).
 * Includes Finance Manager payer assignment email/notification logic on final approval.
 * Includes Creator and Previous Approver notifications on rejection.
 *
 * @param mysqli $conDB Database connection
 * @param string $inv_no The request's invoice number
 * @param string $request_type The type of request (e.g., 'smart_request')
 * @param int $current_user_id The emp_id of the user taking the action
 * @param string $action The action taken ('approve' or 'reject')
 * @param string $note A note for the action
 * @return array Status of the operation ['status' => 'success'|'error', 'message' => string, 'next_approver' => array|null, 'next_approver_id' => int|null]
 */
if (!function_exists('handle_approval_action')) {
    function handle_approval_action($conDB, $inv_no, $request_type, $current_user_id, $action, $note) {
        global $userwel; // Assumes $userwel contains the current user's name

        // ** Input Validation **
        if (!$conDB) {
            error_log("handle_approval_action: Database connection error.");
            return ['status' => 'error', 'message' => 'Database connection error.'];
        }
        if (empty($inv_no) || empty($request_type) || !is_numeric($current_user_id) || $current_user_id <= 0 || ($action !== 'approve' && $action !== 'reject')) {
             error_log("handle_approval_action: Invalid parameters. InvNo=$inv_no, Type=$request_type, UserID=$current_user_id, Action=$action");
            return ['status' => 'error', 'message' => 'Invalid parameters for approval action.'];
        }

        // ** Get Request Type Info **
        $type_query = mysqli_query($conDB, "SELECT `id`, `main_table_name` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
        if (!$type_query || mysqli_num_rows($type_query) == 0) {
            error_log("handle_approval_action: Invalid request type '$request_type' for InvNo $inv_no.");
            return ['status' => 'error', 'message' => 'Invalid request type specified.'];
        }
        $type_row = mysqli_fetch_assoc($type_query);
        $request_type_id = (int)$type_row['id'];
        $main_table_name = $type_row['main_table_name']; // Make sure this table exists and has necessary columns
        if (empty($main_table_name)) {
            error_log("handle_approval_action: main_table_name not set for request type '$request_type'.");
            return ['status' => 'error', 'message' => 'Configuration error: Main table not defined for this request type.'];
        }

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
                mysqli_stmt_close($stmt_find);
                // Check if already actioned (without locking)
                $check_actioned_sql = "SELECT `status` FROM `request_approvers`
                                       WHERE `request_inv_no` = ? AND `request_type_id` = ? AND `approver_id` = ?
                                       ORDER BY `action_date` DESC LIMIT 1";
                $stmt_check = mysqli_prepare($conDB, $check_actioned_sql);
                mysqli_stmt_bind_param($stmt_check, "sii", $inv_no_safe, $request_type_id, $current_user_id_safe);
                mysqli_stmt_execute($stmt_check);
                $check_result = mysqli_stmt_get_result($stmt_check);
                 if($check_result && $row = mysqli_fetch_assoc($check_result)) {
                     mysqli_rollback($conDB); // Release transaction
                     mysqli_stmt_close($stmt_check);
                     return ['status' => 'error', 'message' => 'You have already actioned this request (' . $row['status'] . ').'];
                 }
                 mysqli_stmt_close($stmt_check);
                mysqli_rollback($conDB); // Release transaction
                return ['status' => 'error', 'message' => 'No pending approval found for you on this request, or it has been modified.'];
            }
            $current_task = mysqli_fetch_assoc($find_result);
            mysqli_stmt_close($stmt_find); // Close the find statement

            $current_level = (int)$current_task['approval_level'];
            $current_task_id = (int)$current_task['id'];

            // ** Update Current Approver's Task **
            $action_status = ($action == 'approve') ? 'approved' : 'rejected';
            $update_sql = "UPDATE `request_approvers` SET `status` = ?, `note` = ?, `action_date` = NOW() WHERE `id` = ?";
            $stmt_update = mysqli_prepare($conDB, $update_sql);
            if (!$stmt_update) throw new Exception("Prepare failed (update task): " . mysqli_error($conDB));
            mysqli_stmt_bind_param($stmt_update, "ssi", $action_status, $note_safe, $current_task_id);
            if (!mysqli_stmt_execute($stmt_update)) throw new Exception("Execute failed (update task): " . mysqli_stmt_error($stmt_update));
            mysqli_stmt_close($stmt_update);

            // ** Log Action in smt_request_status **
             $log_status = ($action == 'approve') ? "approved_level_$current_level" : 'rejected';
             $log_user_name = escape_string($userwel ?? 'System');
             $log_sql = "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES (?, ?, ?, ?, ?)";
             $stmt_log = mysqli_prepare($conDB, $log_sql);
             if ($stmt_log) {
                mysqli_stmt_bind_param($stmt_log, "issss", $current_user_id_safe, $inv_no_safe, $log_user_name, $log_status, $note_safe);
                if (!mysqli_stmt_execute($stmt_log)) {
                     error_log("handle_approval_action: Failed to log status for InvNo $inv_no_safe. Error: " . mysqli_stmt_error($stmt_log));
                     // Continue processing even if logging fails
                }
                mysqli_stmt_close($stmt_log);
             } else { error_log("handle_approval_action: Prepare failed (log status): " . mysqli_error($conDB)); }


            // ** Handle Next Step **
            $result_payload = ['status' => 'success', 'next_approver' => null, 'next_approver_id' => null]; // Initialize result payload

            if ($action == 'approve') {
                // ** Find Next Approver **
                $next_level = $current_level + 1;
                $next_sql = "SELECT * FROM `request_approvers`
                             WHERE `request_inv_no` = ? AND `request_type_id` = ? AND `approval_level` = ? LIMIT 1";
                $stmt_next = mysqli_prepare($conDB, $next_sql);
                 if (!$stmt_next) throw new Exception("Prepare failed (find next): " . mysqli_error($conDB));
                mysqli_stmt_bind_param($stmt_next, "sii", $inv_no_safe, $request_type_id, $next_level);
                if (!mysqli_stmt_execute($stmt_next)) throw new Exception("Execute failed (find next): " . mysqli_stmt_error($stmt_next));
                $next_result = mysqli_stmt_get_result($stmt_next);

                if ($next_result && mysqli_num_rows($next_result) > 0) {
                    // --- There is a next approver ---
                    $next_task = mysqli_fetch_assoc($next_result);
                    $next_task_id = (int)$next_task['id'];
                    $next_approver_id = (int)$next_task['approver_id'];
                    mysqli_stmt_close($stmt_next); // Close statement here

                    // Set next approver's status to 'pending'
                    $update_next_sql = "UPDATE `request_approvers` SET `status` = 'pending' WHERE `id` = ?";
                    $stmt_update_next = mysqli_prepare($conDB, $update_next_sql);
                    if (!$stmt_update_next) throw new Exception("Prepare failed (update next status): " . mysqli_error($conDB));
                    mysqli_stmt_bind_param($stmt_update_next, "i", $next_task_id);
                    if (!mysqli_stmt_execute($stmt_update_next)) throw new Exception("Execute failed (update next status): " . mysqli_stmt_error($stmt_update_next));
                    mysqli_stmt_close($stmt_update_next);

                    // Update main request table status
                    $update_main_pending_sql = "UPDATE `$main_table_name` SET `current_status` = 'pending_approval', `current_approval_level` = ? WHERE `inv_no` = ?";
                    $stmt_main_pending = mysqli_prepare($conDB, $update_main_pending_sql);
                     if (!$stmt_main_pending) throw new Exception("Prepare failed (update main pending): " . mysqli_error($conDB));
                    mysqli_stmt_bind_param($stmt_main_pending, "is", $next_level, $inv_no_safe);
                    if (!mysqli_stmt_execute($stmt_main_pending)) throw new Exception("Execute failed (update main pending): " . mysqli_stmt_error($stmt_main_pending));
                    mysqli_stmt_close($stmt_main_pending);

                    // Prepare details for notification
                    $next_approver_details = getEmployeeDetailsForApproval($conDB, $next_approver_id);
                    if ($next_approver_details) {
                         $result_payload['next_approver'] = $next_approver_details;
                         $result_payload['next_approver_id'] = $next_approver_id;
                    } else {
                         error_log("handle_approval_action: Could not get details for next approver ID $next_approver_id for InvNo $inv_no_safe.");
                    }

                } else {
                    // --- FINAL APPROVAL ---
                    mysqli_stmt_close($stmt_next); // Close statement here

                     $update_main_approved_sql = "UPDATE `$main_table_name` SET `current_status` = 'approved', `current_approval_level` = ? WHERE `inv_no` = ?";
                     $stmt_main_approved = mysqli_prepare($conDB, $update_main_approved_sql);
                     if (!$stmt_main_approved) throw new Exception("Prepare failed (update main approved): " . mysqli_error($conDB));
                     mysqli_stmt_bind_param($stmt_main_approved, "is", $current_level, $inv_no_safe);
                     if (!mysqli_stmt_execute($stmt_main_approved)) throw new Exception("Execute failed (update main approved): " . mysqli_stmt_error($stmt_main_approved));
                     mysqli_stmt_close($stmt_main_approved);


                    // --- Finance Manager Payer Assignment Notification Logic ---
                    $finance_manager_in_chain = false;
                    $finance_dept_id = 2; // Assuming Dept ID 2 is Finance
                    $chain_check_sql = "SELECT e.dept FROM `request_approvers` ra JOIN `employees` e ON ra.approver_id = e.emp_id WHERE ra.`request_inv_no` = ? AND ra.`request_type_id` = ?";
                    $stmt_chain = mysqli_prepare($conDB, $chain_check_sql);
                    if ($stmt_chain) {
                         mysqli_stmt_bind_param($stmt_chain, "si", $inv_no_safe, $request_type_id);
                         if (mysqli_stmt_execute($stmt_chain)) {
                             $chain_result = mysqli_stmt_get_result($stmt_chain);
                             while ($chain_row = mysqli_fetch_assoc($chain_result)) {
                                 if ($chain_row['dept'] == $finance_dept_id) {
                                     $finance_manager_in_chain = true;
                                     break;
                                 }
                             }
                         } else { error_log("handle_approval_action: Failed to execute chain dept check. InvNo: $inv_no_safe. Error: " . mysqli_stmt_error($stmt_chain)); }
                         mysqli_stmt_close($stmt_chain);
                    } else { error_log("handle_approval_action: Failed to prepare chain dept check. InvNo: $inv_no_safe. Error: " . mysqli_error($conDB)); }


                     if (!$finance_manager_in_chain) {
                        $finance_manager_details = getDeptManager($conDB, $finance_dept_id);
                        if ($finance_manager_details && !empty($finance_manager_details['email']) && isset($finance_manager_details['emp_id'])) {
                            $fm_emp_id = $finance_manager_details['emp_id'];
                            $fm_name = $finance_manager_details['name'];
                            $fm_email = $finance_manager_details['email'];

                            // Attempt Email (Best effort)
                             if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                                try {
                                    $mail_fm = new PHPMailer(true);
                                    // SMTP Config (ensure get_setting works)
                                    $mail_fm->isSMTP();
                                    $smtp_host = get_setting($conDB, 'smtp_host');
                                    $smtp_user = get_setting($conDB, 'smtp_user');
                                    $smtp_pass = get_setting($conDB, 'smtp_pass');
                                    $smtp_secure = get_setting($conDB, 'smtp_secure');
                                    $smtp_port = get_setting($conDB, 'smtp_port');
                                    $app_name = get_setting($conDB, 'application_name');

                                    // Add checks for retrieved settings
                                    if(empty($smtp_host) || empty($smtp_user) || empty($smtp_pass) || empty($smtp_port)) {
                                        throw new Exception("SMTP configuration settings are missing or incomplete.");
                                    }

                                    $mail_fm->Host = $smtp_host;
                                    $mail_fm->SMTPAuth = true;
                                    $mail_fm->Username = $smtp_user;
                                    $mail_fm->Password = $smtp_pass;
                                    if (!empty($smtp_secure)) $mail_fm->SMTPSecure = $smtp_secure; // Only set if not empty
                                    $mail_fm->Port = $smtp_port;
                                    $mail_fm->CharSet = 'UTF-8';
                                    $mail_fm->setFrom($smtp_user, $app_name ?: 'System Notification'); // Use app name or default
                                    $mail_fm->addAddress($fm_email, $fm_name);
                                    $mail_fm->isHTML(true);
                                    $mail_fm->Subject = 'Smart Request Requires Payer Assignment - ' . $inv_no_safe;

                                    // Fetch request details for email
                                    $req_title = '';
                                    $req_details_query = mysqli_query($conDB, "SELECT sub_title FROM `$main_table_name` WHERE inv_no = '$inv_no_safe' LIMIT 1");
                                    if ($req_details_query && $req_row = mysqli_fetch_assoc($req_details_query)) {
                                         $req_title = $req_row['sub_title'];
                                    }

                                     // Construct URL
                                     $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                                     $host = $_SERVER['HTTP_HOST'];
                                     $script_dir = dirname($_SERVER['PHP_SELF']);
                                     $base_path = rtrim($script_dir, '/\\'); // Trim both slashes
                                     $base_url = $protocol . $host . ($base_path === '' ? '' : $base_path) . '/';
                                     $request_view_url = $base_url . "open_request.php?id=" . urlencode($inv_no_safe);

                                    $mail_fm->Body = "Dear " . htmlspecialchars($fm_name) . ",<br><br>" .
                                                "Smart Request <b>" . htmlspecialchars($inv_no_safe) . "</b> (" . htmlspecialchars($req_title) . ") has been fully approved and requires a payer to be assigned.<br><br>" .
                                                "Please review the request via the system.<br><br>" .
                                                "<a href='" . $request_view_url . "'>Click here to view the request</a>";
                                    $mail_fm->send();
                                    error_log("Payer assignment notification EMAIL sent to Finance Manager ($fm_email) for InvNo: " . $inv_no_safe);
                                } catch (Exception $e) {
                                    error_log("Mailer Error (Finance Manager Payer Assignment Email) for InvNo: " . $inv_no_safe . " - " . $e->getMessage()); // Use $e->getMessage()
                                }
                             } else { error_log("PHPMailer class not found, cannot send Finance Manager email for InvNo: $inv_no_safe"); }

                            // Create Browser Notification (Best effort)
                            if (function_exists('create_browser_notification')) {
                                $notification_title = "Payer Assignment Needed";
                                $notification_message = "Request " . htmlspecialchars($inv_no_safe) . " is approved and needs a payer.";
                                $notification_url = "open_request.php?id=" . urlencode($inv_no_safe);
                                if (create_browser_notification($conDB, $fm_emp_id, $notification_title, $notification_message, $notification_url)) {
                                    error_log("Payer assignment BROWSER notification created for Finance Manager (ID: $fm_emp_id) for InvNo: " . $inv_no_safe);
                                } else {
                                     error_log("Failed to create payer assignment BROWSER notification for Finance Manager (ID: $fm_emp_id) for InvNo: " . $inv_no_safe);
                                }
                            } else { error_log("create_browser_notification function not found, cannot create Finance Manager browser notification for InvNo: $inv_no_safe"); }
                        } else { error_log("Could not find Finance Manager details (Dept $finance_dept_id) or email is missing/invalid to send payer assignment notification for InvNo: " . $inv_no_safe); }
                     } // End if !$finance_manager_in_chain
                    // --- End Finance Manager Notification ---
                } // End if/else (next approver vs final approval)

            } else {
                // --- Action was 'reject' ---
                 $update_main_rejected_sql = "UPDATE `$main_table_name` SET `current_status` = 'rejected', `current_approval_level` = ? WHERE `inv_no` = ?";
                 $stmt_main_rejected = mysqli_prepare($conDB, $update_main_rejected_sql);
                 if (!$stmt_main_rejected) throw new Exception("Prepare failed (update main rejected): " . mysqli_error($conDB));
                 mysqli_stmt_bind_param($stmt_main_rejected, "is", $current_level, $inv_no_safe);
                 if (!mysqli_stmt_execute($stmt_main_rejected)) throw new Exception("Execute failed (update main rejected): " . mysqli_stmt_error($stmt_main_rejected));
                 mysqli_stmt_close($stmt_main_rejected);


                 // --- Rejection Notification Logic ---
                 $notification_title = "Request Rejected";
                 $notification_message = "Request " . htmlspecialchars($inv_no_safe) . " was rejected by " . htmlspecialchars($userwel ?? 'Approver') . ". Reason: " . htmlspecialchars($note_safe);
                 $notification_url = "open_request.php?id=" . urlencode($inv_no_safe);

                 // 1. Notify the Creator
                 $creator_id = null;
                 $creator_id_query = mysqli_query($conDB, "SELECT emp_id FROM `$main_table_name` WHERE inv_no = '$inv_no_safe' LIMIT 1"); // Use direct query as it's simple read
                 if($creator_id_query && $creator_row = mysqli_fetch_assoc($creator_id_query)){
                     $creator_id = (int)$creator_row['emp_id'];
                 } else { error_log("handle_approval_action (Rejection): Could not get creator ID for InvNo $inv_no_safe."); }

                  if ($creator_id > 0 && $creator_id != $current_user_id_safe && function_exists('create_browser_notification')) {
                     create_browser_notification($conDB, $creator_id, $notification_title, $notification_message, $notification_url);
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
                             if ($prev_approver_id > 0 && $prev_approver_id != $current_user_id_safe && function_exists('create_browser_notification')) {
                                 create_browser_notification($conDB, $prev_approver_id, $notification_title, $notification_message, $notification_url);
                             }
                         }
                     } else { error_log("handle_approval_action (Rejection): Failed to execute previous approvers query. InvNo $inv_no_safe. Error: " . mysqli_stmt_error($stmt_prev)); }
                     mysqli_stmt_close($stmt_prev);
                 } else { error_log("handle_approval_action (Rejection): Failed to prepare previous approvers query. InvNo $inv_no_safe. Error: " . mysqli_error($conDB)); }
                 // --- End Rejection Notification Logic ---

            } // End if/else (approve vs reject)

            // ** Commit Transaction **
            mysqli_commit($conDB);
            return $result_payload; // Return success payload

        } catch (Exception $e) {
            // ** Rollback Transaction on Error **
            mysqli_rollback($conDB);
            error_log("handle_approval_action: Transaction failed for InvNo $inv_no_safe - " . $e->getMessage());
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
    function get_approval_chain_status($conDB, $inv_no, $request_type) {
        $chain = [];
        if (!$conDB) return $chain;

        $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
        if (!$type_query || mysqli_num_rows($type_query) == 0) {
             error_log("get_approval_chain_status: Invalid request type '$request_type' for InvNo $inv_no.");
            return $chain; // Return empty array if type is invalid
        }
        $type_row = mysqli_fetch_assoc($type_query);
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
        } else {
            error_log("get_approval_chain_status: Failed to fetch chain for InvNo $inv_no. Error: " . mysqli_error($conDB));
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
    function get_current_approver($conDB, $inv_no, $request_type) {
        if (!$conDB) return null;

        $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
        if (!$type_query || mysqli_num_rows($type_query) == 0) {
            error_log("get_current_approver: Invalid request type '$request_type' for InvNo $inv_no.");
            return null;
        }
        $type_row = mysqli_fetch_assoc($type_query);
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
            return (int)$row['approver_id'];
        } elseif (!$query) {
             error_log("get_current_approver: Failed to query pending approver for InvNo $inv_no. Error: " . mysqli_error($conDB));
        }
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
    function get_pending_approval_count($conDB, $emp_id) {
        if (!$conDB || !is_numeric($emp_id) || $emp_id <= 0) return 0;

        $emp_id_safe = (int)$emp_id;
        $sql = "SELECT COUNT(DISTINCT ra.request_inv_no, ra.request_type_id) as pending_count /* Count distinct requests */
                FROM `request_approvers` ra
                WHERE ra.`approver_id` = $emp_id_safe AND ra.`status` = 'pending'";

        $query = mysqli_query($conDB, $sql);
        if ($query && mysqli_num_rows($query) > 0) {
            $row = mysqli_fetch_assoc($query);
            return (int)$row['pending_count'];
        } elseif (!$query) {
            error_log("get_pending_approval_count: Failed to query count for emp_id $emp_id_safe. Error: " . mysqli_error($conDB));
        }
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
    function getEmployeeDetailsForApproval($conDB, $emp_id) {
        if (!$conDB || !is_numeric($emp_id) || $emp_id <= 0) return null;
        $emp_id_safe = (int)$emp_id;
        $sql = "SELECT e.name, al.email
                FROM `employees` e
                LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id
                WHERE e.`emp_id` = ? AND e.`status` = 1
                LIMIT 1"; // Added status check
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
             error_log("getEmployeeDetailsForApproval: Prepare failed for emp_id $emp_id_safe. Error: " . mysqli_error($conDB));
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
                 error_log("getEmployeeDetailsForApproval: Employee not found or inactive for emp_id $emp_id_safe.");
            }
        } else {
             error_log("getEmployeeDetailsForApproval: Execute failed for emp_id $emp_id_safe. Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null; // Not found or error
    }
}


// --- Department/Role Specific Employee Fetching ---

if (!function_exists('getDeptManager')) {
    function getDeptManager($conDB, $dept_id) {
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
             error_log("getDeptManager: Prepare failed for dept $dept_id_safe. Error: " . mysqli_error($conDB));
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
                  error_log("getDeptManager: Manager/dept_user not found or inactive for dept $dept_id_safe.");
             }
         } else {
              error_log("getDeptManager: Execute failed for dept $dept_id_safe. Error: " . mysqli_stmt_error($stmt));
         }
         mysqli_stmt_close($stmt);
         return null;
    }
}

if (!function_exists('getFinancePersonnel')) {
    function getFinancePersonnel($conDB, $dept_id = 2) { // Default Finance Dept ID = 2
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
             error_log("getFinancePersonnel: Prepare failed for dept $dept_id_safe. Error: " . mysqli_error($conDB));
             return $personnel;
         }
         mysqli_stmt_bind_param($stmt, "i", $dept_id_safe);
         if (mysqli_stmt_execute($stmt)) {
             $result = mysqli_stmt_get_result($stmt);
             if($result) {
                 while ($row = mysqli_fetch_assoc($result)) {
                     $row['email'] = (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) ? $row['email'] : null;
                     $personnel[] = $row;
                 }
                 mysqli_free_result($result);
             }
         } else {
             error_log("getFinancePersonnel: Execute failed for dept $dept_id_safe. Error: " . mysqli_stmt_error($stmt));
         }
         mysqli_stmt_close($stmt);
        return $personnel;
    }
}

if (!function_exists('getHRPersonnel')) {
    function getHRPersonnel($conDB, $dept_id = 5) { // Default HR Dept ID = 5
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
             error_log("getHRPersonnel: Prepare failed for dept $dept_id_safe. Error: " . mysqli_error($conDB));
             return $personnel;
         }
         mysqli_stmt_bind_param($stmt, "i", $dept_id_safe);
         if (mysqli_stmt_execute($stmt)) {
             $result = mysqli_stmt_get_result($stmt);
             if($result) {
                 while ($row = mysqli_fetch_assoc($result)) {
                      $row['email'] = (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) ? $row['email'] : null;
                     $personnel[] = $row;
                 }
                  mysqli_free_result($result);
             }
         } else {
             error_log("getHRPersonnel: Execute failed for dept $dept_id_safe. Error: " . mysqli_stmt_error($stmt));
         }
         mysqli_stmt_close($stmt);
        return $personnel;
    }
}


if (!function_exists('getGeneralManager')) {
    function getGeneralManager($conDB) {
         if (!$conDB) return null;
         // Find user with 'gm' user_type in admin_login, ensure active in employees
         $sql = "SELECT e.emp_id, e.name, al.email
                 FROM `admin_login` al
                 JOIN `employees` e ON al.emp_id = e.emp_id
                 WHERE al.`user_type`= 'gm' AND e.`status`= 1
                 LIMIT 1";
         $stmt = mysqli_prepare($conDB, $sql);
          if (!$stmt) {
             error_log("getGeneralManager: Prepare failed. Error: " . mysqli_error($conDB));
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
                 error_log("getGeneralManager: GM user_type not found or inactive.");
             }
         } else {
             error_log("getGeneralManager: Execute failed. Error: " . mysqli_stmt_error($stmt));
         }
         mysqli_stmt_close($stmt);
         return null;
    }
}

if (!function_exists('getEmployeeDetails')) {
    function getEmployeeDetails($conDB, $emp_id) {
        $default_return = ['name' => 'N/A', 'email' => null]; // Consistent return type
        if (!$conDB || !is_numeric($emp_id) || $emp_id <= 0) return $default_return;
        $emp_id_clean = (int)$emp_id;
        $sql = "SELECT e.name, al.email
                FROM `employees` e
                LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id
                WHERE e.`emp_id`= ?
                LIMIT 1";
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
             error_log("getEmployeeDetails: Prepare failed for emp_id $emp_id_clean. Error: " . mysqli_error($conDB));
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
                 error_log("getEmployeeDetails: Employee not found for emp_id $emp_id_clean.");
            }
        } else {
             error_log("getEmployeeDetails: Execute failed for emp_id $emp_id_clean. Error: " . mysqli_stmt_error($stmt));
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
    function create_browser_notification($conDB, $emp_id, $title, $message, $url) {
         // ** Input Validation and Sanitization **
        if (!$conDB) {
             error_log("create_browser_notification: Database connection is not available.");
             return false;
        }
        if (!is_numeric($emp_id) || $emp_id <= 0) {
             error_log("create_browser_notification: Invalid emp_id '$emp_id'.");
             return false;
        }
        $title_trimmed = trim($title);
        if (empty($title_trimmed)) {
             error_log("create_browser_notification: Title cannot be empty for emp_id $emp_id.");
             return false;
        }
        $message_trimmed = trim($message);
         if (empty($message_trimmed)) {
             error_log("create_browser_notification: Message cannot be empty for emp_id $emp_id.");
             return false;
        }
        $url_trimmed = trim($url);
         if (empty($url_trimmed)) {
             error_log("create_browser_notification: URL cannot be empty for emp_id $emp_id.");
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
             error_log("create_browser_notification: Prepare failed for emp_id $emp_id_safe. Error: " . mysqli_error($conDB));
             return false;
        }

        // Bind parameters: i=integer, s=string
        mysqli_stmt_bind_param($stmt, "isss", $emp_id_safe, $title_final, $message_final, $url_final);

        // ** Execute and Check **
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
             mysqli_stmt_close($stmt);
             if ($affected_rows === 1) {
                  // error_log("DEBUG: create_browser_notification: Successfully created notification for emp_id $emp_id_safe."); // Optional success log
                 return true;
             } else {
                  error_log("create_browser_notification: Execute succeeded but no rows were inserted for emp_id $emp_id_safe.");
                  return false; // Or handle as needed
             }
        } else {
            // Log specific error
            error_log("create_browser_notification: Execute failed for emp_id $emp_id_safe. Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
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
    function get_unread_notifications($conDB, $emp_id) {
        $notifications = [];
        if (!$conDB) {
             error_log("get_unread_notifications: Database connection not available.");
             return $notifications;
        }
        if (!is_numeric($emp_id) || $emp_id <= 0) {
             error_log("get_unread_notifications: Invalid emp_id '$emp_id'.");
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
             error_log("get_unread_notifications: Prepare failed for emp_id $emp_id_safe. Error: " . mysqli_error($conDB));
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
                 error_log("get_unread_notifications: Get result failed for emp_id $emp_id_safe. Error: " . mysqli_stmt_error($stmt));
             }
         } else {
             // Log execution error
             error_log("get_unread_notifications: Execute failed for emp_id $emp_id_safe. Error: " . mysqli_stmt_error($stmt));
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
    function mark_notifications_as_read($conDB, $emp_id, $notification_ids) {
         // Validate inputs
        if (!$conDB) {
             error_log("mark_notifications_as_read: Database connection not available.");
             return false;
        }
        if (!is_numeric($emp_id) || $emp_id <= 0 || empty($notification_ids) || !is_array($notification_ids)) {
             error_log("mark_notifications_as_read: Invalid input. emp_id=$emp_id, ids=" . print_r($notification_ids, true));
            return false;
        }

        // Sanitize all IDs to integers and filter out invalid ones
        $ids_safe = array_map('intval', $notification_ids);
        $ids_safe = array_filter($ids_safe, function($id) { return $id > 0; });

        if (empty($ids_safe)) {
             error_log("mark_notifications_as_read: No valid notification IDs provided after filtering.");
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
             error_log("mark_notifications_as_read: Prepare failed for emp_id $emp_id_safe. Error: " . mysqli_error($conDB));
             return false;
        }

        // Bind parameters: first the emp_id (integer), then all notification IDs (integers)
        $types = 'i' . str_repeat('i', count($ids_safe)); // 'i' for emp_id, 'i' for each id
        $bind_params = array_merge([$emp_id_safe], $ids_safe); // Combine emp_id and notification ids

        // Use call_user_func_array or argument unpacking (...) for dynamic binding
        // Note: Argument unpacking (...) requires PHP 5.6+
        if (!mysqli_stmt_bind_param($stmt, $types, ...$bind_params)) {
             error_log("mark_notifications_as_read: Bind param failed for emp_id $emp_id_safe. Error: " . mysqli_stmt_error($stmt));
             mysqli_stmt_close($stmt);
             return false;
        }


        // Execute and Check
        if (mysqli_stmt_execute($stmt)) {
             $affected_rows = mysqli_stmt_affected_rows($stmt);
             mysqli_stmt_close($stmt);
             if ($affected_rows >= 0) { // Even 0 affected rows is a success if the query ran and no error occurred
                 // Log success, including how many rows were actually updated
                 // error_log("DEBUG: mark_notifications_as_read: Success. Marked $affected_rows notifications as read for emp_id $emp_id_safe. IDs: [" . implode(',', $ids_safe) . "].");
                 return true; // Indicate success (query executed without error)
             } else {
                  // Should not happen with MySQLi, but good practice
                  error_log("mark_notifications_as_read: Execute reported negative affected rows for emp_id $emp_id_safe. IDs: [" . implode(',', $ids_safe) . "]. Error: " . mysqli_stmt_error($stmt)); // Log stmt error
                  return false; // Error indicated by negative affected rows
             }
        } else {
            // Log execution error
            error_log("mark_notifications_as_read: Execute failed for emp_id $emp_id_safe. IDs: [" . implode(',', $ids_safe) . "]. Error: " . mysqli_stmt_error($stmt));
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

    function get_setting($conDB, $setting_name) {
         global $settings_cache; // Access the cache

        if (!$conDB) {
            error_log("get_setting: Database connection not available for setting '$setting_name'.");
            return null;
        }
        $setting_name_trimmed = trim($setting_name);
        if (empty($setting_name_trimmed)) {
             error_log("get_setting: Setting name cannot be empty.");
             return null;
        }

        // Check cache first
        if (isset($settings_cache[$setting_name_trimmed])) {
            // error_log("DEBUG: get_setting: Found '$setting_name_trimmed' in cache."); // Optional cache hit log
            return $settings_cache[$setting_name_trimmed];
        }
        // error_log("DEBUG: get_setting: Fetching '$setting_name_trimmed' from DB."); // Optional cache miss log

        // Use Prepared Statement
        $sql = "SELECT `setting_value` FROM `settings` WHERE `setting_name` = ? LIMIT 1";
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
             error_log("get_setting: Prepare failed for setting '$setting_name_trimmed'. Error: " . mysqli_error($conDB));
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
                 // Setting not found, cache null to avoid re-querying
                 $settings_cache[$setting_name_trimmed] = null;
                 error_log("WARNING: get_setting: Setting '$setting_name_trimmed' not found in database.");
                 if($result) mysqli_free_result($result); // Free result even if no rows
            }
        } else {
             error_log("get_setting: Execute failed for setting '$setting_name_trimmed'. Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null; // Return null if setting not found or error
    }
}

?>