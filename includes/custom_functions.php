<?php

/*
    MODIFICATION SUMMARY:
    - Added new SQL to create the `user_notifications` table. You must run this SQL in your database (e.g., phpMyAdmin) ONE TIME.
    - Added new function `create_browser_notification()`: Inserts a new notification record into the database for a specific user.
    - Added new function `get_unread_notifications()`: Fetches all unread notifications for a user.
    - Added new function `mark_notifications_as_read()`: Marks specific notifications as read after they are fetched.
*/

/*
    ---------------------------------------------------------------------------------
    !!! IMPORTANT: DATABASE UPDATE - RUN THIS SQL ONCE IN PHPMYADMIN !!!
    ---------------------------------------------------------------------------------
    
    CREATE TABLE IF NOT EXISTS `user_notifications` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `emp_id` INT NOT NULL,
      `title` VARCHAR(255) NOT NULL,
      `message` TEXT NOT NULL,
      `url` VARCHAR(512) NOT NULL,
      `is_read` TINYINT(1) NOT NULL DEFAULT 0,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_emp_id_is_read` (`emp_id`, `is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    ---------------------------------------------------------------------------------
*/


$formatter = new NumberFormatter('en_SA',  NumberFormatter::CURRENCY);

function escape_string($param)
{
    if (is_array($param))
        return array_map(__METHOD__, $param);

    if (!empty($param) && is_string($param)) {
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $param);
    }
    return $param;
}


function timeAgo($time_ago)
{
    $time_ago = strtotime($time_ago);
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
        if ($minutes == 1) {
            return "one minute ago";
        } else {
            return "$minutes minutes ago";
        }
    }
    //Hours
    else if ($hours <= 24) {
        if ($hours == 1) {
            return "an hour ago";
        } else {
            return "$hours hrs ago";
        }
    }
    //Days
    else if ($days <= 7) {
        if ($days == 1) {
            return "yesterday";
        } else {
            return "$days days ago";
        }
    }
    //Weeks
    else if ($weeks <= 4.3) {
        if ($weeks == 1) {
            return "a week ago";
        } else {
            return "$weeks weeks ago";
        }
    }
    //Months
    else if ($months <= 12) {
        if ($months == 1) {
            return "a month ago";
        } else {
            return "$months months ago";
        }
    }
    //Years
    else {
        if ($years == 1) {
            return "one year ago";
        } else {
            return "$years years ago";
        }
    }
}
function timeAgoAr($time_ago)
{
    $time_ago = strtotime($time_ago);
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
        if ($minutes == 1) {
            return "قبل دقيقة واحدة";
        } else {
            return "$minutes دقائق مضت";
        }
    }
    //Hours
    else if ($hours <= 24) {
        if ($hours == 1) {
            return "قبل ساعة";
        } else {
            return "$hours قبل ساعات";
        }
    }
    //Days
    else if ($days <= 7) {
        if ($days == 1) {
            return "أمس";
        } else {
            return "$days قبل أيام";
        }
    }
    //Weeks
    else if ($weeks <= 4.3) {
        if ($weeks == 1) {
            return "قبل أسبوع";
        } else {
            return "$weeks قبل أسابيع";
        }
    }
    //Months
    else if ($months <= 12) {
        if ($months == 1) {
            return "قبل شهر";
        } else {
            return "$months قبل شهور";
        }
    }
    //Years
    else {
        if ($years == 1) {
            return "قبل عام";
        } else {
            return "$years منذ سنوات";
        }
    }
}


function split_words($string, $nb_caracs, $separator)
{
    $string = strip_tags(html_entity_decode($string));
    if (strlen($string) <= $nb_caracs) {
        $final_string = $string;
    } else {
        $final_string = "";
        $words = explode(" ", $string);
        foreach ($words as $value) {
            if (strlen($final_string . " " . $value) < $nb_caracs) {
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


function dateDiffDays($startDate, $endDate)
{
    // Declare and define two dates
    $date1 = strtotime($startDate);
    $date2 = strtotime($endDate);
    // Formulate the Difference between two dates
    $diff = abs($date2 - $date1);
    // To get the year divide the resultant date into
    // total seconds in a year (365*60*60*24)
    $years = floor($diff / (365 * 60 * 60 * 24));
    // To get the month, subtract it with years and
    // divide the resultant date into
    // total seconds in a month (30*60*60*24)
    $months = floor(($diff - $years * 365 * 60 * 60 * 24)
        / (30 * 60 * 60 * 24));
    // To get the day, subtract it with years and
    // months and divide the resultant date into
    // total seconds in a days (60*60*24)
    $days = floor(($diff - $years * 365 * 60 * 60 * 24 -
        $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));
    // To get the hour, subtract it with years,
    // months & seconds and divide the resultant
    // date into total seconds in a hours (60*60)
    $hours = floor(($diff - $years * 365 * 60 * 60 * 24
        - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24)
        / (60 * 60));
    // To get the minutes, subtract it with years,
    // months, seconds and hours and divide the
    // resultant date into total seconds i.e. 60
    $minutes = floor(($diff - $years * 365 * 60 * 60 * 24
        - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24
        - $hours * 60 * 60) / 60);
    // To get the minutes, subtract it with years,
    // months, seconds, hours and minutes
    $seconds = floor(($diff - $years * 365 * 60 * 60 * 24
        - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24
        - $hours * 60 * 60 - $minutes * 60));

    // To get the day, subtract it with years and
    // months and divide the resultant date into
    // total seconds in a days (60*60*24)
    // Print the result
    return printf("%d years, %d months, %d days, %d hours, " . "%d minutes, %d seconds", $years, $months, $days, $hours, $minutes, $seconds);
    /*$interval = $date1->diff($date2);
    $totalDays = $interval->days;
    return $totalDays;*/
}

function getTotalDays($years, $months, $days)
{
    $result = 0;
    $result += $years * 360;
    $result += $months * 30;
    $result += $days;
    // console.log(result);
    return $result;
};

function endOfService($joinDate, $endDate, $salary)
{
    $date1 = new DateTime($joinDate);
    $date2 = new DateTime($endDate);
    $diff = $date1->diff($date2);
    $totalDays = (getTotalDays($diff->y, $diff->m, $diff->d));
    if ($totalDays <= 5 * 360) {
        $result = ($salary / 2) * ($totalDays/*+1*/);
    } else if ($totalDays > 5 * 360) {
        $resultFirstFiveYears =  ($salary / 2) * (5 * 360);
        $yearsGreaterThanFive  = $totalDays - (5 * 360);
        $resultGreaterFiveYears = $salary * $yearsGreaterThanFive;
        $result = $resultFirstFiveYears + $resultGreaterFiveYears;
    } else {
        if ($totalDays < 2 * 360) {
            $result = 0;
        } else if ($totalDays >= 2 * 360 && $totalDays <= 5 * 360) {
            $result = ($salary / 6) * $totalDays;
        } else if ($totalDays > 5 * 360 && $totalDays < 10 * 360) {
            $resultFirstFiveYears =  ($salary / 3) * (5 * 360);
            $yearsGreaterThanFive  = $totalDays - (5 * 360);
            $resultGreaterFiveYears = (($salary / 3) * 2) * $yearsGreaterThanFive;
            $result = $resultFirstFiveYears + $resultGreaterFiveYears;
        } else if ($totalDays >= 10 * 360) {
            $resultFirstFiveYears =  ($salary / 2) * (5 * 360);
            $yearsGreaterThanFive  = $totalDays - (5 * 360);
            $resultGreaterFiveYears = $salary * $yearsGreaterThanFive;
            $result = $resultFirstFiveYears + $resultGreaterFiveYears;
        }
    }
    return $Final_result = $result / 360;
    // return number_format((float)$Final_result, 2, '.', '');
    // return number_format((float)$Final_result,2);
}

function debug($data, $die = true)
{
    // echo '<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">';
    echo '<pre style="background: #1e1e1e; color: #f0f0f0; padding: 10px; border-radius: 4px;">';
    if (is_bool($data) || is_null($data)) {
        var_dump($data); // Better for booleans & NULL
    } else {
        print_r($data); // Cleaner for arrays/objects
    }
    echo "\n\n<b>DEBUG BACKTRACE:</b>\n";
    print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)); // Last 5 calls
    echo '</pre>';
    if ($die) die(); // Optional: Stop execution
}

function dd($data)
{
    echo '<pre style="background: #1e1e1e; color: #f0f0f0; padding: 10px; border-radius: 4px;">';
    var_dump($data);
    echo '</pre>';
    die();
}

function console_log($data)
{
    echo '<script>';
    echo 'console.log(' . json_encode($data) . ');';
    echo '</script>';
}

/**
 * Redirect or refresh the page with optional delay and status messages.
 * * @param string $path     URL to redirect to (empty = refresh current page)
 * @param int $delay       Delay in seconds (0 = immediate)
 * @param bool $exit       Terminate script after redirect? (Default: true)
 * @param string $message  Custom message to display during delay
 * @return void
 */
function redirect($path = "", $delay = 0, $exit = true, $message = "")
{
    $url = ($path !== "") ? $path : $_SERVER['REQUEST_URI'];

    // Immediate redirect (if headers not sent)
    if (!headers_sent() && $delay === 0) {
        header("Location: " . $url);
        if ($exit) exit();
        return;
    }
    // Delayed redirect or fallback (HTML + meta refresh)
    $defaultMessage = ($delay > 0)
        ? "Redirecting in <span id='countdown'>$delay</span> seconds..."
        : "Redirecting...";
    $finalMessage = ($message !== "") ? $message : $defaultMessage;
    echo <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="refresh" content="$delay;url=$url">
        <title>Redirecting...</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            #countdown { font-weight: bold; color: #007bff; }
        </style>
    </head>
    <body>
        <p>$finalMessage</p>
        <script>
            if ($delay > 0) {
                var timeLeft = $delay;
                var countdown = setInterval(function() {
                    timeLeft--;
                    document.getElementById('countdown').textContent = timeLeft;
                    if (timeLeft <= 0) clearInterval(countdown);
                }, 1000);
            }
        </script>
    </body>
    </html>
HTML;

    if ($exit) exit();
}

/**
 * SweetAlert with confirmation button.
 */
function salert($title, $message, $type = 'success', $redirectUrl = "", $btn = 'OK')
{
    echo <<<HTML
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <body class="enlarged" data-keep-enlarged="true">
        <script>
            Swal.fire({
                title: "$title",
                text: "$message",
                icon: "$type",
                allowOutsideClick:false,
                confirmButtonText: "$btn",
                customClass: {
                    confirmButton: 'btn btn-lg btn-primary' // Bootstrap class
                },
                buttonsStyling: false,
            }).then((result) => {
                (result.isConfirmed && "$redirectUrl" !== "")?window.location.href = "$redirectUrl":""
            });
        </script>
        </body>
    HTML;
}

function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// --- Helper Function ---
function send_json_response($title, $message, $type, $http_status_code = 200)
{
    http_response_code($http_status_code);
    header('Content-Type: application/json');
    exit(json_encode([
        'title' => $title,
        'message' => $message,
        'type' => $type
    ]));
}

function debugPDO($stmt, $params = [])
{
    //1 usage $stmt = $pdo->prepare("INSERT INTO `emp_docu` (`emp_id`, `docu_typ`, `path`, `docu_ext`, `pgid`) VALUES (:emp_id, :docu_typ, :filename, :ext, :pgid)");
    //2 usage $params = [':emp_id' => $emp_id_up,':docu_typ' => $docu_typ_up,':filename' => $filename_po,':ext' => $file_ext,':pgid' => $id];
    $query = $stmt->queryString;
    foreach ($params as $param => $value) {
        if (is_string($value)) {
            $value = "'" . $value . "'";
        }
        $query = str_replace($param, $value, $query);
    }
    return $query;
}


function parseName($fullName, $format = 'FIRST_LAST')
{
    $parts = array_values(array_filter(explode(' ', trim($fullName))));
    $count = count($parts);

    // Define available components
    $components = [
        'FIRST' => $parts[0] ?? '',
        'SECOND' => $parts[1] ?? '',
        'LAST' => $count > 1 ? end($parts) : '',
        'GRANDFATHER' => ($count > 3) ? $parts[2] : (($count > 2) ? $parts[1] : ''),
        'MIDDLE' => ($count > 3) ? implode(' ', array_slice($parts, 1, -1)) : (($count > 2) ? $parts[1] : '')
    ];

    // Split requested format
    $requested = explode('_', $format);
    $result = [];

    foreach ($requested as $component) {
        if (isset($components[$component])) {
            $result[] = $components[$component];
        }
    }

    return implode(' ', array_filter($result));
}

function highlightKeywords($text, $search)
{
    $wordsAry = explode(" ", $search);
    $wordsCount = count($wordsAry);
    for ($i = 0; $i < $wordsCount; $i++) {
        $highlighted_text = "<span class='search-highlight'>$wordsAry[$i]</span>";
        $text = str_ireplace($wordsAry[$i], $highlighted_text, $text);
    }
    return $text;
}

function formatPeriod($periodString)
{
    // Explode the string into parts
    $parts = explode(' ', $periodString);
    // Rebuild the string with the translated unit
    // Note: A function should 'return' a value, not 'echo' it.
    // This makes it much more flexible.
    return $parts[0] . " " . __(strtolower($parts[1])) . " - " . $parts[3];
}

function ageDOB($dob)
{ /* $y = year, $m = month, $d = day */
    $dob_a = explode("-", $dob);
    $dob_y = $dob_a[0];
    $dob_m = $dob_a[1];
    $dob_d = $dob_a[2];
    $ageY = date("Y") - intval($dob_y);
    $ageM = date("n") - intval($dob_m);
    $ageD = date("j") - intval($dob_d);

    if ($ageD < 0) {
        $ageD = $ageD += date("t");
        $ageM--;
    }
    if ($ageM < 0) {
        $ageM += 12;
        $ageY--;
    }
    if ($ageY < 0) {
        $ageD = $ageM = $ageY = -1;
    }
    // return array( 'y'=>$ageY, 'm'=>$ageM, 'd'=>$ageD );
    return __('years'). " <b>" . $ageY . "</b> ". __('months') ." <b>" . $ageM . "</b> ". __('days') ." <b>" . $ageD . "</b>";
}


/**
 * Generates a full set of pagination controls with detailed item counts.
 *
 * @param int $current_page The current active page.
 * @param int $total_pages The total number of pages.
 * @param int $total_items The total number of items after filtering.
 * @param int $items_per_page The number of items displayed per page.
 * @param array $limit_options An array of integers for the "items per page" dropdown.
 * @param bool $show_all A flag indicating if the "show all" option is active.
 * @param array $base_params An associative array of base URL parameters to preserve.
 * @param int|null $unfiltered_total_items The total number of items before any filtering.
 * @return string The generated HTML for the pagination controls.
 */
function generate_pagination_controls($current_page, $total_pages, $total_items, $items_per_page, $limit_options, $show_all, $base_params = [], $unfiltered_total_items = null)
{
    // --- Backwards Compatibility Shim ---
    if (is_array($items_per_page)) {
        $base_params    = $show_all ?? [];
        $show_all       = $limit_options;
        $limit_options  = $items_per_page;
        $items_per_page = $total_items;
        $total_items    = null; 
    }
    // --- End of Shim ---

    if (!is_array($limit_options) || !is_numeric($items_per_page)) {
        return '<!-- Pagination Error: Invalid arguments. Please check the function call. -->';
    }

    $current_page = (int)$current_page;
    $total_pages = (int)$total_pages;
    $total_items = (int)$total_items;
    $items_per_page = (int)$items_per_page;
    $unfiltered_total_items = ($unfiltered_total_items !== null) ? (int)$unfiltered_total_items : null;

    if ($total_items < 1 && !$show_all) {
        return '';
    }

    $html = '<div class="row mt-4"><div class="col-12 d-md-flex justify-content-between align-items-center">';

    // --- Items per page dropdown ---
    $html .= '<div class="mb-3 mb-md-0">';
    $html .= '<div class="form-inline">';
    $html .= '<label for="limitFilter" class="mr-2 font-weight-bold">'.__('show').':</label>';
    $html .= '<select class="form-control form-control-sm" id="limitFilter" onchange="applyFilters()">';
    foreach ($limit_options as $limit) {
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
        if (!$show_all && $items_per_page > 0) {
            $start_item = ($current_page - 1) * $items_per_page + 1;
            $end_item = min($start_item + $items_per_page - 1, $total_items);
            $showing_text = "".__('showing')." {$start_item} ".__('to')." {$end_item} ".__('of')." {$total_items} ".__('entries')."";
        } else {
             $showing_text = "".__('showing_all')." {$total_items} ".__('entries')."";
        }

        if ($unfiltered_total_items !== null && $unfiltered_total_items > $total_items) {
             $showing_text .= " (".__('filtered_from')." {$unfiltered_total_items} ".__('entries').")";
        }

        $html .= "<span class='text-muted mr-3'>{$showing_text}</span>";
    }

    if ($total_pages > 1 && !$show_all) {
        $html .= '<nav aria-label="Page navigation"><ul class="pagination mb-0">';
        
        $first_disabled = ($current_page <= 1) ? 'disabled' : '';
        $first_link = "?" . http_build_query(array_merge($base_params, ['page' => 1]));
        $html .= "<li class='page-item {$first_disabled}'><a class='page-link' href='{$first_link}'>".__('first')."</a></li>";

        $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
        $prev_link = "?" . http_build_query(array_merge($base_params, ['page' => $current_page - 1]));
        $html .= "<li class='page-item {$prev_disabled}'><a class='page-link' href='{$prev_link}'>".__('previous')."</a></li>";

        $range = 2;
        $start_range = max(1, $current_page - $range);
        $end_range = min($total_pages, $current_page + $range);

        if ($start_range > 1) {
            $page_link = "?" . http_build_query(array_merge($base_params, ['page' => 1]));
            $html .= "<li class='page-item'><a class='page-link' href='{$page_link}'>1</a></li>";
            if ($start_range > 2) {
                $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
            }
        }

        for ($i = $start_range; $i <= $end_range; $i++) {
            $active_class = ($current_page == $i) ? 'active' : '';
            $page_link = "?" . http_build_query(array_merge($base_params, ['page' => $i]));
            $html .= "<li class='page-item {$active_class}'><a class='page-link' href='{$page_link}'>{$i}</a></li>";
        }
        
        if ($end_range < $total_pages) {
            if ($end_range < $total_pages - 1) {
                $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
            }
            $page_link = "?" . http_build_query(array_merge($base_params, ['page' => $total_pages]));
            $html .= "<li class='page-item'><a class='page-link' href='{$page_link}'>{$total_pages}</a></li>";
        }

        $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
        $next_link = "?" . http_build_query(array_merge($base_params, ['page' => $current_page + 1]));
        $html .= "<li class='page-item {$next_disabled}'><a class='page-link' href='{$next_link}'>".__('next')."</a></li>";
        
        $last_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
        $last_link = "?" . http_build_query(array_merge($base_params, ['page' => $total_pages]));
        $html .= "<li class='page-item {$last_disabled}'><a class='page-link' href='{$last_link}'>".__('last')."</a></li>";

        $html .= '</ul></nav>';
    }

    $html .= '</div>';
    $html .= '</div></div>';
    return $html;
}

/*
MODIFICATION SUMMARY:
- This is a new file containing all the functions for the general approval system.
- get_all_employees(): Fetches all employees to populate approver dropdowns.
- save_approval_chain(): Saves the selected approvers into the new `request_approvers` table.
- handle_approval_action(): The main logic engine. Handles an approver's "approve" or "reject" action.
- get_approval_chain_status(): Fetches the complete status of a request's approval chain for display.
- get_current_approver(): Finds the specific employee who needs to approve the request right now.
- getMainRequestTable(): Helper to get the name of the main table associated with a request type.
- getEmployeeDetailsForApproval(): Fetches employee details for sending notification emails.
*/

/**
 * Fetches all active employees to populate approver dropdowns.
 * @param mysqli $conDB Database connection
 * @return array List of employees
 */
function get_all_employees($conDB) {
    $employees = [];
    $query = mysqli_query($conDB, "SELECT `emp_id`, `name` FROM `employees` WHERE `status` = 1 ORDER BY `name`");
    if ($query) {
        while ($row = mysqli_fetch_assoc($query)) {
            $employees[] = $row;
        }
    }
    return $employees;
}

/**
 * Saves the chosen approval chain for a new request.
 * @param mysqli $conDB Database connection
 * @param string $inv_no The request's invoice number
 * @param string $request_type The type of request (e.g., 'smart_request')
 * @param array $approver_ids An array of emp_id strings, in order of approval
 * @return bool True on success, false on failure
 */
function save_approval_chain($conDB, $inv_no, $request_type, $approver_ids) {
    // 1. Get the request_type_id
    $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
    if (mysqli_num_rows($type_query) == 0) {
        return false;
    }
    $type_row = mysqli_fetch_assoc($type_query);
    $request_type_id = $type_row['id'];

    // 2. Insert each approver into the `request_approvers` table
    $level = 1;
    foreach ($approver_ids as $approver_id) {
        if (!empty($approver_id)) {
            // Set the first approver to 'pending', others to 'awaiting'
            $status = ($level == 1) ? 'pending' : 'awaiting';
            $approver_id_safe = (int)$approver_id;
            
            $sql = "INSERT INTO `request_approvers` (`request_inv_no`, `request_type_id`, `approver_id`, `approval_level`, `status`) 
                    VALUES ('" . escape_string($inv_no) . "', $request_type_id, $approver_id_safe, $level, '$status')";
            
            if (!mysqli_query($conDB, $sql)) {
                // Handle insert error if needed
                return false;
            }
            $level++;
        }
    }
    return true;
}

/**
 * Handles an approver's action (approve/reject).
 * @param mysqli $conDB Database connection
 * @param string $inv_no The request's invoice number
 * @param string $request_type The type of request (e.g., 'smart_request')
 * @param int $current_user_id The emp_id of the user taking the action
 * @param string $action The action taken ('approve' or 'reject')
 * @param string $note A note for the action
 * @return array Status of the operation
 */
function handle_approval_action($conDB, $inv_no, $request_type, $current_user_id, $action, $note) {
    global $userwel; // For logging status

    // 1. Get Request Type ID and Main Table Name
    $type_query = mysqli_query($conDB, "SELECT `id`, `main_table_name` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
    if (mysqli_num_rows($type_query) == 0) {
        return ['status' => 'error', 'message' => 'Invalid request type.'];
    }
    $type_row = mysqli_fetch_assoc($type_query);
    $request_type_id = $type_row['id'];
    $main_table_name = $type_row['main_table_name'];
    $inv_no_safe = escape_string($inv_no);
    $note_safe = escape_string($note);

    // 2. Find the approver's pending task
    $find_sql = "SELECT * FROM `request_approvers` 
                 WHERE `request_inv_no` = '$inv_no_safe' 
                   AND `request_type_id` = $request_type_id 
                   AND `approver_id` = " . (int)$current_user_id . " 
                   AND `status` = 'pending' 
                 ORDER BY `approval_level` LIMIT 1";
    $find_query = mysqli_query($conDB, $find_sql);

    if (mysqli_num_rows($find_query) == 0) {
        return ['status' => 'error', 'message' => 'No pending approval found for you on this request.'];
    }
    $current_task = mysqli_fetch_assoc($find_query);
    $current_level = $current_task['approval_level'];
    $current_task_id = $current_task['id'];

    // 3. Update the current approver's task
    $action_status = ($action == 'approve') ? 'approved' : 'rejected';
    $update_sql = "UPDATE `request_approvers` 
                   SET `status` = '$action_status', `note` = '$note_safe', `action_date` = NOW() 
                   WHERE `id` = $current_task_id";
    mysqli_query($conDB, $update_sql);

    // 4. Handle next step based on action
    if ($action == 'approve') {
        // Find the next approver in the chain
        $next_level = $current_level + 1;
        $next_sql = "SELECT * FROM `request_approvers` 
                     WHERE `request_inv_no` = '$inv_no_safe' 
                       AND `request_type_id` = $request_type_id 
                       AND `approval_level` = $next_level 
                     LIMIT 1";
        $next_query = mysqli_query($conDB, $next_sql);

        if (mysqli_num_rows($next_query) > 0) {
            // There is a next approver
            $next_task = mysqli_fetch_assoc($next_query);
            
            // Set next approver's status to 'pending'
            mysqli_query($conDB, "UPDATE `request_approvers` SET `status` = 'pending' WHERE `id` = " . $next_task['id']);
            
            // Update main request table status
            mysqli_query($conDB, "UPDATE `$main_table_name` SET `current_status` = 'pending_approval', `current_approval_level` = $next_level WHERE `inv_no` = '$inv_no_safe'");
            
            // Log status
            mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$current_user_id', '$inv_no_safe', '$userwel', 'pending_approval', 'Approved at level $current_level. $note_safe')");

            // Return details for email notification
            $next_approver_details = getEmployeeDetailsForApproval($conDB, $next_task['approver_id']);
            // --- NEW: Return next approver's ID for browser notification ---
            return ['status' => 'success', 'next_approver' => $next_approver_details, 'next_approver_id' => $next_task['approver_id']];

        } else {
            // This was the final approval
            mysqli_query($conDB, "UPDATE `$main_table_name` SET `current_status` = 'approved', `current_approval_level` = $current_level WHERE `inv_no` = '$inv_no_safe'");
            mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$current_user_id', '$inv_no_safe', '$userwel', 'approved', 'Final approval. $note_safe')");
            return ['status' => 'success', 'next_approver' => null]; // Final approval
        }

    } else {
        // Action was 'reject'
        mysqli_query($conDB, "UPDATE `$main_table_name` SET `current_status` = 'rejected', `current_approval_level` = $current_level WHERE `inv_no` = '$inv_no_safe'");
        mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('$current_user_id', '$inv_no_safe', '$userwel', 'rejected', '$note_safe')");
        return ['status' => 'success', 'next_approver' => null]; // Rejected
    }
}

/**
 * Gets the full approval chain with names and statuses for display.
 * @param mysqli $conDB Database connection
 * @param string $inv_no The request's invoice number
 * @param string $request_type The type of request (e.g., 'smart_request')
 * @return array List of approval chain steps
 */
function get_approval_chain_status($conDB, $inv_no, $request_type) {
    $chain = [];
    $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
    if (mysqli_num_rows($type_query) == 0) {
        return $chain;
    }
    $type_row = mysqli_fetch_assoc($type_query);
    $request_type_id = $type_row['id'];

    $sql = "SELECT ra.*, e.name as approver_name 
            FROM `request_approvers` ra
            JOIN `employees` e ON ra.approver_id = e.emp_id
            WHERE ra.`request_inv_no` = '" . escape_string($inv_no) . "' 
              AND ra.`request_type_id` = $request_type_id
            ORDER BY ra.`approval_level`";
    
    $query = mysqli_query($conDB, $sql);
    if ($query) {
        while ($row = mysqli_fetch_assoc($query)) {
            $chain[] = $row;
        }
    }
    return $chain;
}

/**
 * Finds the current pending approver's ID.
 * @param mysqli $conDB Database connection
 * @param string $inv_no The request's invoice number
 * @param string $request_type The type of request (e.g., 'smart_request')
 * @return int|null The emp_id of the current approver, or null
 */
function get_current_approver($conDB, $inv_no, $request_type) {
    $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = '" . escape_string($request_type) . "' LIMIT 1");
    if (mysqli_num_rows($type_query) == 0) {
        return null;
    }
    $type_row = mysqli_fetch_assoc($type_query);
    $request_type_id = $type_row['id'];

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
    }
    return null;
}

/**
 * --- NEW FUNCTION ---
 * Gets the total count of pending approvals for a specific user.
 * @param mysqli $conDB Database connection
 * @param int $emp_id The employee's ID
 * @return int The number of pending approvals
 */
function get_pending_approval_count($conDB, $emp_id) {
    if (!$conDB || !$emp_id) return 0;

    $emp_id_safe = (int)$emp_id;
    $sql = "SELECT COUNT(*) as pending_count 
            FROM `request_approvers`
            WHERE `approver_id` = $emp_id_safe AND `status` = 'pending'";
            
    $query = mysqli_query($conDB, $sql);
    if ($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        return (int)$row['pending_count'];
    }
    return 0;
}


/**
 * Helper to get employee details for email notifications.
 * @param mysqli $conDB Database connection
 * @param int $emp_id The employee's ID
 * @return array|null Employee details or null
 */
function getEmployeeDetailsForApproval($conDB, $emp_id) {
    $query = mysqli_query($conDB, "SELECT e.name, al.email 
                                    FROM `employees` e 
                                    LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id 
                                    WHERE e.`emp_id`='" . (int)$emp_id . "' 
                                    LIMIT 1");
    return mysqli_num_rows($query) > 0 ? mysqli_fetch_assoc($query) : null;
}


/**
 * Helper to get employee details for open request.
 * @param mysqli $conDB Database connection
 * @param int $emp_id The employee's ID
 */


if (!function_exists('getDeptManager')) {
    function getDeptManager($conDB, $dept_id) {
        if (!$conDB || !$dept_id) return null;
        $query = mysqli_query($conDB, "SELECT e.emp_id, e.name, al.email FROM `employees` e LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id WHERE e.`dept`='".escape_string($dept_id)."' AND e.`emptype`='Manager' AND e.`status`=1 LIMIT 1");
        return ($query && mysqli_num_rows($query) > 0) ? mysqli_fetch_assoc($query) : null;
    }
}

if (!function_exists('getFinancePersonnel')) {
    function getFinancePersonnel($conDB, $dept_id = 2) {
        if (!$conDB) return [];
        $query = mysqli_query($conDB, "SELECT e.emp_id, e.name, al.email FROM `employees` e LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id WHERE e.`dept`='".escape_string($dept_id)."' AND e.`status`=1 ORDER BY FIELD(e.emptype, 'Manager', 'Supporter'), e.name"); // Added ordering by name
        $personnel = [];
        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) { $personnel[] = $row; }
        }
        return $personnel;
    }
}

// NEW Function to get HR Personnel
if (!function_exists('getHRPersonnel')) {
    // UPDATED: Default dept_id changed to 5
    function getHRPersonnel($conDB, $dept_id = 5) { // ** HR Dept ID is now 5 **
        if (!$conDB) return [];
        $query = mysqli_query($conDB, "SELECT e.emp_id, e.name, al.email
                                        FROM `employees` e
                                        LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id
                                        WHERE e.`dept`='" . escape_string($dept_id) . "' AND e.`status`=1
                                        ORDER BY e.name");
        $personnel = [];
        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) { $personnel[] = $row; }
        }
        return $personnel;
    }
}


if (!function_exists('getGeneralManager')) {
    function getGeneralManager($conDB) {
         if (!$conDB) return null;
        $query = mysqli_query($conDB, "SELECT e.emp_id, e.name, al.email FROM `employees` e LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id WHERE e.`emp_id`='3928' AND e.`status`=1 LIMIT 1"); // Hardcoded GM ID might need review
        return ($query && mysqli_num_rows($query) > 0) ? mysqli_fetch_assoc($query) : null;
    }
}

// Modified helper function to get details for any employee - REQUIRES $conDB
if (!function_exists('getEmployeeDetails')) {
    function getEmployeeDetails($conDB, $emp_id) {
        if (!$conDB || !$emp_id) return ['name' => 'N/A', 'email' => '']; // Basic validation
        $emp_id_clean = (int)$emp_id; // Sanitize input
        $query = mysqli_query($conDB, "SELECT e.name, al.email FROM `employees` e LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id WHERE e.`emp_id`='$emp_id_clean' LIMIT 1");
        return ($query && mysqli_num_rows($query) > 0) ? mysqli_fetch_assoc($query) : ['name' => 'N/A', 'email' => ''];
    }
}


// --- NEW FUNCTIONS FOR BROWSER NOTIFICATIONS ---

/**
 * Creates a browser notification entry in the database.
 * @param mysqli $conDB Database connection
 * @param int $emp_id The employee ID to notify
 * @param string $title The notification title
 * @param string $message The notification body
 * @param string $url The URL to open on click
 * @return bool True on success, false on failure
 */
if (!function_exists('create_browser_notification')) {
    function create_browser_notification($conDB, $emp_id, $title, $message, $url) {
        if (!$conDB || !$emp_id || empty($title) || empty($message) || empty($url)) {
            return false;
        }

        $emp_id_safe = (int)$emp_id;
        $title_safe = escape_string($title);
        $message_safe = escape_string($message);
        $url_safe = escape_string($url);

        $sql = "INSERT INTO `user_notifications` (`emp_id`, `title`, `message`, `url`) 
                VALUES ($emp_id_safe, '$title_safe', '$message_safe', '$url_safe')";
        
        if (mysqli_query($conDB, $sql)) {
            return true;
        } else {
            // Optional: Log error
            error_log("Failed to create browser notification: " . mysqli_error($conDB));
            return false;
        }
    }
}

/**
 * Fetches all unread notifications for a specific user.
 * @param mysqli $conDB Database connection
 * @param int $emp_id The employee's ID
 * @return array List of unread notifications
 */
if (!function_exists('get_unread_notifications')) {
    function get_unread_notifications($conDB, $emp_id) {
        $notifications = [];
        if (!$conDB || !$emp_id) return $notifications;

        $emp_id_safe = (int)$emp_id;
        $sql = "SELECT * FROM `user_notifications` 
                WHERE `emp_id` = $emp_id_safe AND `is_read` = 0 
                ORDER BY `created_at` DESC";
        
        $query = mysqli_query($conDB, $sql);
        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                $notifications[] = $row;
            }
        }
        return $notifications;
    }
}

/**
 * Marks a list of notification IDs as read.
 * @param mysqli $conDB Database connection
 * @param array $notification_ids An array of notification IDs to mark as read
 * @return bool True on success, false on failure
 */
if (!function_exists('mark_notifications_as_read')) {
    function mark_notifications_as_read($conDB, $notification_ids) {
        if (!$conDB || empty($notification_ids)) {
            return false;
        }

        // Sanitize all IDs to integers
        $ids_safe = array_map('intval', $notification_ids);
        $ids_list = implode(',', $ids_safe);

        if (empty($ids_list)) {
            return false;
        }

        $sql = "UPDATE `user_notifications` 
                SET `is_read` = 1 
                WHERE `id` IN ($ids_list)";
        
        return mysqli_query($conDB, $sql);
    }
}