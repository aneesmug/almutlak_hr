<?php
if($subdomain == "ar"){
function get_timeago( $ptime ){
    $estimate_time = time() - $ptime;

    if( $estimate_time < 1 ){
        return 'قبل أقل من 1 ثانية';
    }

    $condition = array(
                12 * 30 * 24 * 60 * 60  =>  'س',
                30 * 24 * 60 * 60       =>  'ش',
                24 * 60 * 60            =>  'ايام',
                60 * 60                 =>  'ساعات',
                60                      =>  'دقاءق',
                1                       =>  'ثانية'
    );

    foreach( $condition as $secs => $str ){
        $d = $estimate_time / $secs;

        if( $d >= 1 ){
            $r = round( $d );
            //return 'about ' . $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
			//return '' . $r . ' ' . $str . ( $r > 1 ? '' : '' ) . ' قبل';
			return '' . $r . ' ' . $str . ( $r > 1 ? '' : '' ) . ' ';
        }
    }
	}
} elseif($subdomain == "en"){
function get_timeago( $ptime ){
    $estimate_time = time() - $ptime;

    if( $estimate_time < 1 ){
        return 'less than 1 second ago';
    }

    $condition = array(
                12 * 30 * 24 * 60 * 60  =>  'year',
                30 * 24 * 60 * 60       =>  'month',
                24 * 60 * 60            =>  'day',
                60 * 60                 =>  'hour',
                60                      =>  'minute',
                1                       =>  'second'
    );

    foreach( $condition as $secs => $str ){
        $d = $estimate_time / $secs;

        if( $d >= 1 ){
            $r = round( $d );
            //return 'about ' . $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
			return '' . $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
        }
    }
	}
} else {
	function get_timeago( $ptime ){
    $estimate_time = time() - $ptime;

    if( $estimate_time < 1 ){
        return 'less than 1 second ago';
    }

    $condition = array(
                12 * 30 * 24 * 60 * 60  =>  'year',
                30 * 24 * 60 * 60       =>  'month',
                24 * 60 * 60            =>  'day',
                60 * 60                 =>  'hour',
                60                      =>  'minute',
                1                       =>  'second'
    );

    foreach( $condition as $secs => $str ){
        $d = $estimate_time / $secs;

        if( $d >= 1 ){
            $r = round( $d );
            //return 'about ' . $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
			return '' . $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
        }
    }
	}
}
/***********************/
function get_timeago_arbic($ptime){
    $periods = array(
        "second"  => "ثانية", 
        "seconds" => "ثواني", 
        "minute"  => "دقيقة", 
        "minutes" => "دقائق", 
        "hour"    => "ساعة", 
        "hours"   => "ساعات", 
        "day"     => "يوم", 
        "days"    => "أيام", 
        "month"   => "شهر", 
        "months"  => "شهور", 
    );

    $difference = (int) abs(time() - $ptime);

    $plural = array(3,4,5,6,7,8,9,10);

    $readable_date = "قبل ";

    if ($difference < 60) // less than a minute
    { 
        $readable_date .= $difference . " ";
        if (in_array($difference, $plural)) {
            $readable_date .= $periods["seconds"];
        } else {
            $readable_date .= $periods["second"];
        }
    } 
    elseif ($difference < (60*60)) // less than an hour
    { 
        $diff = (int) ($difference / 60);
        $readable_date .= $diff . " ";
        if (in_array($diff, $plural)) {
            $readable_date .= $periods["minutes"];
        } else {
            $readable_date .= $periods["minute"];
        }
    } 
    elseif ($difference < (24*60*60)) // less than a day
    { 
        $diff = (int) ($difference / (60*60));
        $readable_date .= $diff . " ";
        if (in_array($diff, $plural)) {
            $readable_date .= $periods["hours"];
        } else {
            $readable_date .= $periods["hour"];
        }
    } 
    elseif ($difference < (30*24*60*60)) // less than a month
    { 
        $diff = (int) ($difference / (24*60*60));
        $readable_date .= $diff . " ";
        if (in_array($diff, $plural)) {
            $readable_date .= $periods["days"];
        } else {
            $readable_date .= $periods["day"];
        }
    } 
    elseif ($difference < (365*24*60*60)) // less than a year
    { 
        $diff = (int) ($difference / (30*24*60*60));
        $readable_date .= $diff . " ";

        if (in_array($diff, $plural)) {
            $readable_date .= $periods["months"];
        } else {
            $readable_date .= $periods["month"];
        }
    } 
    else 
    {
        $readable_date = date("d-m-Y", $ptime);
    }

    return $readable_date;
}
/**/
/*********************Diffrent*********************/
/* 
 * Returns a string stating how long ago this happened
 */

function timeElapsed($ptime){
    $diff = time() - $ptime;
    $calc_times = array();
    $timeleft   = array();

    // Prepare array, depending on the output we want to get.
    $calc_times[] = array("Year",   "Years",   31557600);
    $calc_times[] = array("Month",  "Months",  2592000);
    $calc_times[] = array("Day",    "Days",    86400);
    $calc_times[] = array("hr",   "hrs",   3600);
    $calc_times[] = array("min", "mins", 60);
    $calc_times[] = array("sec", "secs", 1);

    foreach ($calc_times AS $timedata){
        list($time_sing, $time_plur, $offset) = $timedata;

        if ($diff >= $offset){
            $left = floor($diff / $offset);
            $diff -= ($left * $offset);
            $timeleft[] = "{$left} " . ($left == 1 ? $time_sing : $time_plur);
        }
    }

    return $timeleft ? (time() > $ptime ? null : '-') . implode(' ', $timeleft) : 0;
}
/**************************************************/

function get_time_ago($time_stamp){
    $time_difference = strtotime('now') - $time_stamp;

    if ($time_difference >= 60 * 60 * 24 * 365.242199){
        /*
         * 60 seconds/minute * 60 minutes/hour * 24 hours/day * 365.242199 days/year
         * This means that the time difference is 1 year or more
         */
        return get_time_ago_string($time_stamp, 60 * 60 * 24 * 365.242199, 'year');
    } elseif ($time_difference >= 60 * 60 * 24 * 30.4368499){
        /*
         * 60 seconds/minute * 60 minutes/hour * 24 hours/day * 30.4368499 days/month
         * This means that the time difference is 1 month or more
         */
        return get_time_ago_string($time_stamp, 60 * 60 * 24 * 30.4368499, 'month');
    } elseif ($time_difference >= 60 * 60 * 24 * 7){
        /*
         * 60 seconds/minute * 60 minutes/hour * 24 hours/day * 7 days/week
         * This means that the time difference is 1 week or more
         */
        return get_time_ago_string($time_stamp, 60 * 60 * 24 * 7, 'week');
    } elseif ($time_difference >= 60 * 60 * 24){
        /*
         * 60 seconds/minute * 60 minutes/hour * 24 hours/day
         * This means that the time difference is 1 day or more
         */
        return get_time_ago_string($time_stamp, 60 * 60 * 24, 'day');
    } elseif ($time_difference >= 60 * 60){
        /*
         * 60 seconds/minute * 60 minutes/hour
         * This means that the time difference is 1 hour or more
         */
        return get_time_ago_string($time_stamp, 60 * 60, 'hr');
    } else {
        /*
         * 60 seconds/minute
         * This means that the time difference is a matter of minutes
         */
        return get_time_ago_string($time_stamp, 60, 'min');
    }
}

function get_time_ago_string($time_stamp, $divisor, $time_unit){
    $time_difference = strtotime("now") - $time_stamp;
    $time_units      = floor($time_difference / $divisor);

    settype($time_units, 'string');

    if ($time_units === '0'){
		//return 'less than 1 ' . $time_unit . ' ago';
        return 'Just now';
    } elseif ($time_units === '1'){
        return '1 ' . $time_unit . ' ago';
    } else {
        /*
         * More than "1" $time_unit. This is the "plural" message.
         */
        // TODO: This pluralizes the time unit, which is done by adding "s" at the end; this will not work for i18n!
        return $time_units . ' ' . $time_unit . 's ago';
    }
}
/************************************************************/
$months_ar = array(
    "Jan" => "يناير",
    "Feb" => "فبراير",
    "Mar" => "مارس",
    "Apr" => "أبريل",
    "May" => "مايو",
    "Jun" => "يونيو",
    "Jul" => "يوليو",
    "Aug" => "أغسطس",
    "Sep" => "سبتمبر",
    "Oct" => "أكتوبر",
    "Nov" => "نوفمبر",
    "Dec" => "ديسمبر"
);
$days_ar = array (
	"Sat" => "السبت", 
	"Sun" => "الأحد", 
	"Mon" => "الإثنين", 
	"Tue" => "الثلاثاء", 
	"Wed" => "الأربعاء", 
	"Thu" => "الخميس",
	"Fri" => "الجمعة"
);
$standard = array(
	"0" => "٠",
	"1" => "١",
	"2" => "٢",
	"3" => "٣",
	"4" => "٤",
	"5" => "٥",
	"6" => "٦",
	"7" => "٧",
	"8" => "٨",
	"9" => "٩"
);

$your_date = "2012-12-25"; // for example

$en_month = date("M", strtotime($your_date));
$en_day = date("D", strtotime($your_date));
$enday = date("d", strtotime($your_date));

$ar_month = $months_ar[$en_month];
$ar_day = $days_ar[$en_day];
/************************************************************/
function single_post_arabic_date($postdate_d,$postdate_d2,$postdate_m,$postdate_y) {
    $months = array("Jan" => "يناير", "Feb" => "فبراير", "Mar" => "مارس", "Apr" => "أبريل", "May" => "مايو", "Jun" => "يونيو", "Jul" => "يوليو", "Aug" => "أغسطس", "Sep" => "سبتمبر", "Oct" => "أكتوبر", "Nov" => "نوفمبر", "Dec" => "ديسمبر");
    $en_month = $postdate_m;
    foreach ($months as $en => $ar) {
        if ($en == $en_month) { $ar_month = $ar; }
    }

    $find = array ("Sat", "Sun", "Mon", "Tue", "Wed" , "Thu", "Fri");
    $replace = array ("السبت", "الأحد", "الإثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة");
    $ar_day_format = $postdate_d2;
    $ar_day = str_replace($find, $replace, $ar_day_format);

    header('Content-Type: text/html; charset=utf-8');
    $standard = array("0","1","2","3","4","5","6","7","8","9");
    $eastern_arabic_symbols = array("٠","١","٢","٣","٤","٥","٦","٧","٨","٩");
    $post_date = $ar_day.' '.$postdate_d.' '.$ar_month.' '.$postdate_y;
    $arabic_date = str_replace($standard , $eastern_arabic_symbols , $post_date);

    return $arabic_date;
}
/************************************************************/
function ArabicDate_ar() {
    $months = array("Jan" => "يناير", "Feb" => "فبراير", "Mar" => "مارس", "Apr" => "أبريل", "May" => "مايو", "Jun" => "يونيو", "Jul" => "يوليو", "Aug" => "أغسطس", "Sep" => "سبتمبر", "Oct" => "أكتوبر", "Nov" => "نوفمبر", "Dec" => "ديسمبر");
    //$your_date = date('y-m-d'); // The Current Date
    $your_date = date('y-m-d'); // The Current Date
    $en_month = date("M", strtotime($your_date));
    foreach ($months as $en => $ar) {
        if ($en == $en_month) { $ar_month = $ar; }
    }
    $find = array ("Sat", "Sun", "Mon", "Tue", "Wed" , "Thu", "Fri");
    $replace = array ("السبت", "الأحد", "الإثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة");
    $ar_day_format = date('D'); // The Current Day
    $ar_day = str_replace($find, $replace, $ar_day_format);

    header('Content-Type: text/html; charset=utf-8');
    $standard = array("0","1","2","3","4","5","6","7","8","9");
    $eastern_arabic_symbols = array("٠","١","٢","٣","٤","٥","٦","٧","٨","٩");
    $current_date = $ar_day.' '.date('d').' / '.$ar_month.' / '.date('Y');
    $arabic_date = str_replace($standard , $eastern_arabic_symbols , $current_date);

    return $arabic_date;
}


?>