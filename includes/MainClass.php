<?php

if(session_status() === PHP_SESSION_NONE)
session_start();
require_once 'db.php';

Class MainClass{
    protected $db;
    function __construct(){
        // include("db.php");
        $this->db = new mysqli(DB_HOST , DB_USER , DB_PASS , DB_NAME);
        if(!$this->db){
            die("Database Connection Failed. Error: ".$this->db->error);
        }
    }
    function db_connect(){
        return $this->db;
    }
    public function register(){
        foreach($_POST as $k => $v){
            $$k = $this->db->real_escape_string($v);
        }
        $passwordhash = sha1(md5($password));
        $mobile = str_replace('-','', $mobile);
        $username = strtolower($username);
        $check_emp_id = $this->db->query("SELECT * FROM `admin_login` WHERE `emp_id`= '$emp_id' ")->num_rows;
        $check_user = $this->db->query("SELECT * FROM `admin_login` WHERE `id_iqama`= '$username' ")->num_rows;
        if($check_emp_id > 0){
            $resp['status'] = 'failed';
            $_SESSION['flashdata']['type']='danger';
            $_SESSION['flashdata']['msg'] = ' Employee already exists.';
        }elseif($check_user > 0){
            $resp['status'] = 'failed';
            $_SESSION['flashdata']['type']='danger';
            $_SESSION['flashdata']['msg'] = ' Username already exists.';
        }else{
            $sql = "INSERT INTO `admin_login` (`emp_id`,`fullname`,`username`,`mobile`,`email`,`password`,`bk_password`,`user_type`,`dept`,`avatar`) VALUES ('$emp_id','$fullname','$username','$mobile','$email','$passwordhash','$password','employee','$dept','$avatar')";
            $save = $this->db->query($sql);
            if($save){
                $resp['status'] = 'success';
            }else{
                $resp['status'] = 'failed';
                $resp['err'] = $this->db->error;
                $_SESSION['flashdata']['type']='danger';
                $_SESSION['flashdata']['msg'] = ' An error occurred.';
            }
        }
        return json_encode($resp);
    }
    public function login(){
        extract($_POST);
        $sql = "SELECT * FROM `admin_login` WHERE `id_iqama` = ? AND `password`= ? AND `status` = 1 ";
        $password = sha1(md5($password));
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            $data = $result->fetch_array();            
            $_SESSION['verify_user_type'] = $data['user_type'];

            if ($data['user_type'] == 'employee') {
                $resp['status'] = 'employee_success';
                $_SESSION['otp_verify_user_id'] = $data['id'];
                $_SESSION['verify_user_type'] = 'employee';
            } else {
                $_SESSION['check_user_data'] = $data['id'];
                $pass_is_right = sha1(md5($data['password']));
                $remember = (isset($_POST['remember']) == "true"?"true":"false");
                $has_code = false;
                if($pass_is_right && (is_null($data['otp']) || (!is_null($data['otp']) && !is_null($data['otp_expiration']) && strtotime($data['otp_expiration']) < time()) ) ){
                    $otp = sprintf("%'.06d",mt_rand(0,999999));
                    $expiration = date("Y-m-d H:i" ,strtotime(date('Y-m-d H:i')." +1 mins"));
                    $update_sql ="UPDATE `admin_login` SET `otp_expiration`='{$expiration}',`otp`='{$otp}',`remember`='{$remember}' WHERE `id`='{$data['id']}'";
                    $update_otp = $this->db->query($update_sql);
                    if($update_otp){
                        $has_code = true;
                        $resp['status'] = 'success';
                        $_SESSION['otp_verify_user_id'] = $data['id'];
                        $this->send_mail($data['email'],$otp);
                    }else{
                        $resp['status'] = 'failed';
                        $_SESSION['flashdata']['type'] = 'danger';
                        $_SESSION['flashdata']['msg'] = ' An error occurred while loggin in. Please try again later.';
                    }
                }else if(!$pass_is_right){
                   $resp['status'] = 'failed';
                   $_SESSION['flashdata']['type'] = 'danger';
                   $_SESSION['flashdata']['msg'] = ' Incorrect Password';
                }
            }
        }else{
            $resp['status'] = 'failed';
            $_SESSION['flashdata']['type'] = 'danger';
            $_SESSION['flashdata']['msg'] = ' Please check your username or password!.';
        }
        return json_encode($resp);
    }
    public function get_user_data($id){
        extract($_POST);
        $sql = "SELECT * FROM `admin_login` WHERE `id` = ? AND `status` = 1 ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dat=[];
        if($result->num_rows > 0){
            $resp['status'] = 'success';
            foreach($result->fetch_array() as $k => $v){
                if(!is_numeric($k)){
                    $data[$k] = $v;
                }
            }
            $resp['data'] = $data;
        }else{
            $resp['status'] = 'false';
        }
        return json_encode($resp);
    }
    public function resend_otp($id){
        $otp = sprintf("%'.06d",mt_rand(0,999999));
        $expiration = date("Y-m-d H:i" ,strtotime(date('Y-m-d H:i')." +1 mins"));
        $update_sql = "UPDATE `admin_login` SET `otp_expiration` = '{$expiration}', `otp` = '{$otp}' WHERE `id` = '{$id}'  ";
        $update_otp = $this->db->query($update_sql);
        if($update_otp){
            $resp['status'] = 'success';
            $email = $this->db->query("SELECT `email` FROM `admin_login` WHERE `id` = '{$id}'")->fetch_array()[0];
            $this->send_mail($email,$otp);
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->db->error;
        }
        return json_encode($resp);
    }
    public function otp_verify(){

            if ($_SESSION['verify_user_type'] == 'employee') {
                $sql = "SELECT * FROM `admin_login` WHERE `id` = '".$_SESSION['otp_verify_user_id']."' AND `status` = 1 ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows > 0){
                    $resp['status'] = 'success';
                    $_SESSION['user_login'] = 1;
                    $userData = $result->fetch_array();

                    if ($userData['status'] == true) {
                        $id = $userData['username'];
                        $user_type_dept = $userData['user_type'];
                        $_SESSION['user'] = $userData['username'];
                    }
                }else{
                    $resp['status'] = 'failed';
                    $_SESSION['flashdata']['type'] = 'danger';
                    $_SESSION['flashdata']['msg'] = ' Incorrect OTP.';
                }

            } else {
                extract($_POST);
                $sql = "SELECT * FROM `admin_login` WHERE `id` = ? AND `otp` = ? AND `status` = 1 ";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('is',$id,$otp);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows > 0){
                    $resp['status'] = 'success';
                    $this->db->query("UPDATE `admin_login` SET `otp` = NULL, `otp_expiration` = NULL, `remember` = NULL WHERE `id` = '{$id}'");
                    $_SESSION['user_login'] = 1;
                    $userData = $result->fetch_array();
                    if ($userData['status'] == true) {
                        $id = $userData['username'];
                        $user_type_dept = $userData['user_type'];
                        $cookie_name = "user";
                        $cookie_value = $id;
                        //expiriry time. 86400 = 1 day (86400*30 = 1 month)
                        $expiry = time() + (86400 * 30);
                        if($userData['remember'] == 'true'){
                            //setting cookie variable
                            setcookie($cookie_name, $cookie_value, $expiry);
                        } elseif ($userData['remember'] == 'false'){
                            //if your server requires to set session path
                            $_SESSION['user'] = $userData['username'];
                        }
                    }
                }else{
                    $resp['status'] = 'failed';
                    $_SESSION['flashdata']['type'] = 'danger';
                    $_SESSION['flashdata']['msg'] = ' Incorrect OTP.';
                }
            }

        return json_encode($resp);
    }
    public function get_emp_admin_data($id){
        extract($_POST);
        $sql = "SELECT * FROM `admin_login` WHERE `emp_id` = ? AND `status` = 1 ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dat=[];
        if($result->num_rows > 0){
            $resp['status'] = 'success';
            foreach($result->fetch_array() as $k => $v){
                if(!is_numeric($k)){
                    $data[$k] = $v;
                }
            }
            $resp['data'] = $data;
        }else{
            $resp['status'] = 'false';
        }
        return json_encode($resp);
    }
    public function get_emp_data($id){
        extract($_POST);
        $sql = "SELECT * FROM `employees` WHERE `emp_id` = ? AND `status` = 1 ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dat=[];
        if($result->num_rows > 0){
            $resp['status'] = 'success';
            foreach($result->fetch_array() as $k => $v){
                if(!is_numeric($k)){
                    $data[$k] = $v;
                }
            }
            $resp['data'] = $data;
        }else{
            $resp['status'] = 'false';
        }
        return json_encode($resp);
    }
    function send_mail($to="",$pin=""){
        if(!empty($to)){
            try{
                require './includes/PHPMailerMaster/PHPMailerAutoload.php';
                $mail = new PHPMailer;
                $mail->isSMTP();                                        // Set mailer to use SMTP 
                // $mail->SMTPDebug = 0;
                $mail->Debugoutput = 'html';
                $mail->Host = 'smtp.office365.com';
                $mail->Port = 587;
                $mail->SMTPSecure = 'tls';
                $mail->SMTPAuth = true;                                 // Enable SMTP authentication 
                $mail->Username = 'a.afzal@almutlak.com';               // SMTP username 
                $mail->Password = '@DmiN56539306@';                     // SMTP password 
                $mail->setFrom("a.afzal@almutlak.com", "Al Mutlak System");
                $mail->addAddress($to, $to);
                $mail->Subject = "OTP [ ".$pin." ] from Al Mutlak System";
                $mail->Body="
                    <html>
                        <body>
                            <h2>You are Attempting to Login in Al Mutlak System</h2>
                            <p>Here is yout OTP (One-Time PIN) to verify your Identity.</p>
                            <h3><b>".$pin."</b></h3>
                        </body>
                    </html>
                ";
                $mail->AltBody = 'This is a plain-text message body';
                if (!$mail->send()) {
                    echo "Mailer Error: " . $mail->ErrorInfo;
                } else {
                    header( "refresh:0 ; url=login.php" );
                }
            }catch(Exception $e){
                $_SESSION['flashdata']['type']='danger';
                $_SESSION['flashdata']['msg'] = ' An error occurred while sending the OTP. Error: '.$e->getMessage();
            }
        }
    }

    function obfuscate_email($email){
        /*$em   = explode("@",$email);
        $name = implode('@', array_slice($em, 0, count($em)-1));
        $len  = floor(strlen($name)/2);
        return substr($name,0, $len) . str_repeat('*', $len) . "@" . end($em);*/
        /*$mail_parts = explode("@", $email);
        $length = strlen($mail_parts[0]);
        $show = floor($length/2);
        $hide = $length - $show;
        $replace = str_repeat("*", $hide);
        return substr_replace ( $mail_parts[0] , $replace , $show, $hide ) . "@" . substr_replace($mail_parts[1], $replace, 0, $show);*/
        if(filter_var($email, FILTER_VALIDATE_EMAIL)){
            list($first, $last) = explode('@', $email);
            $first = str_replace(substr($first, '2'), str_repeat('*', strlen($first)-2), $first);
            $last = explode('.', $last);
            $last_domain = str_replace(substr($last['0'], '2'), str_repeat('*', strlen($last['0'])-2), $last['0']);
            $hideEmailAddress = $first.'@'.$last_domain.'.'.$last['1'];
            return $hideEmailAddress;
        }
    }

    function ageDOB($dob){ /* $y = year, $m = month, $d = day */
        $dob_a = explode("-", $dob);
        $dob_y = $dob_a[0];$dob_m = $dob_a[1];$dob_d = $dob_a[2];
        $ageY = date("Y")-intval($dob_y);
        $ageM = date("n")-intval($dob_m);
        $ageD = date("j")-intval($dob_d);

        if ($ageD < 0){
            $ageD = $ageD += date("t");
            $ageM--;
            }
        if ($ageM < 0){
            $ageM+=12;
            $ageY--;
            }
        if ($ageY < 0){ $ageD = $ageM = $ageY = -1; }
        // return array( 'y'=>$ageY, 'm'=>$ageM, 'd'=>$ageD );
        return "Years <b>".$ageY."</b> Months <b>".$ageM."</b> Days <b>".$ageD."</b>";
    }
    

    function __destruct(){
         $this->db->close();
    }
}
$class = new MainClass();
$conDB= $class->db_connect();

/********************************************************************************************************************************/
// $conDB = mysqli_connect( $localhost , $db_user , $db_pass , $db_name ) or die('Error: Could not connect to database.');
$conDB->set_charset("UTF8");
// mysqli_set_charset("utf8");
// mysqli_query("SET NAMES 'utf8'");
// mysqli_query('SET CHARACTER SET utf8');
// mysqli_set_charset('utf8', $conDB);

// $result_glr=mysql_select_db($db_name);



$site_name = "Al Mutlak Co.";
$site_title = "Al Mutlak Co. | cPanel";
$site_footer = "2009 - ".date("Y")." © SnapS Production House";

/****time_zone****/
date_default_timezone_set("Asia/Riyadh");

mysqli_query($conDB,"SET NAMES utf8;");
// mysqli_query($conDB,"SET CHARACTER_SET utf8;");
header('Content-Type: text/html; charset=utf-8');


$url = "http://".$_SERVER['HTTP_HOST']."";
$parsed = parse_url($url);
$domain = explode('.', $parsed['host']);
$maindomain = '';
$subdomain = '';
if ($domain[0] == 'www'){
    $subdomain  = $domain[1];
    $maindomain = (isset($domain[2]))?$domain[2]:"";
} else {
    $subdomain  = $domain[0];
    $maindomain = (isset($domain[1]))?$domain[1]:"";
}

/*error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 0);*/

$pgname = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);