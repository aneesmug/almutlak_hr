<?php

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
 * 
 * @param string $path     URL to redirect to (empty = refresh current page)
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