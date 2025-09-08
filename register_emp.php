<?php
require_once('./includes/auth.php');
include('./includes/MainClass.php');

if (isset($_POST['check_id'])) {
    $emp_data = json_decode($class->get_emp_data($_POST['emp_id']));
    if ($emp_data->status == 'success') {        
        $user_data = json_decode($class->get_emp_admin_data($emp_data->data->emp_id));
        if ($user_data->status == 'false') {
            // $_GET['emp_id'] = $_POST['emp_id'];
            $_SESSION['emp_id'] = $_POST['emp_id'];
            echo "<script>location.replace('./register.php')</script>";
        } else {
            $_SESSION['flashdata']['type']='danger';
            $_SESSION['flashdata']['msg']=" You're already registerd in Mochachino system.";
            echo "<script>window.setTimeout(function () {location.href = \"./index.php\";}, 5000);</script>";
        }
    } else{
        $_SESSION['flashdata']['type']='danger';
        $_SESSION['flashdata']['msg']=" Your enterd Employee ID is not registerd in system.";
    }
}

?>

<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?=$site_title?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />

        <script src="assets/js/modernizr.min.js"></script>

    </head>


    <body class="account-pages">

        <!-- Begin page -->
        <div class="accountbg" style="background: url('assets/images/login-background.jpg');background-size: cover;background-position: center;"></div>

        <div class="wrapper-page account-page-full">

            <div class="card">
                <div class="card-block">

                    <div class="account-box">

                        <div class="card-box p-5">
                            <div class="text-center">
                                <div class="mb-3">
                                    <img src="assets/images/logo_sm.png" class="rounded-circle img-thumbnail thumb-lg" alt="thumbnail">
                                </div>

                                <p class="text-muted m-b-0 font-14">Enter your employee id to access the register. </p>
                            </div>

                            <form action="<?=$_SERVER['PHP_SELF']?>" class="form-horizontal" method="post">
                                
                                <?php if(isset($_SESSION['flashdata'])): ?>
                                <div class="dynamic_alert alert alert-<?php echo $_SESSION['flashdata']['type'] ?> my-2 rounded-0">
                                    <div class="d-flex align-items-center">
                                        <div class="col-11"><?=$_SESSION['flashdata']['msg'] ?></div>
                                        <div class="col-1 text-end">
                                            <div class="float-end"><a href="javascript:void(0)" class="text-dark text-decoration-none" onclick="$(this).closest('.dynamic_alert').hide('slow').remove()">x</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php unset($_SESSION['flashdata']) ?>
                                <?php endif; ?>

                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="emp_id">Employee ID</label>
                                        <input class="form-control" type="text" id="emp_id" name="emp_id" placeholder="Enter your employee id" onkeypress="return isNumber(event)" required>
                                    </div>
                                </div>
                                <div class="form-group row text-center">
                                    <div class="col-12">
                                        <button class="btn btn-block btn-dark waves-effect waves-light btn_submit" name="check_id" type="submit">Check Employee ID</button>

                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
            </div>

            <div class="m-t-40 text-center">
                <p class="account-copyright"><?=$site_footer?></p>
            </div>

        </div>

        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>


    </body>
</html>
<?php //} ?>