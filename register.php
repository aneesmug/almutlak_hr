<?php
require_once('./includes/auth.php');
include('./includes/MainClass.php');

if (isset($_SESSION['emp_id'])) {
    $emp_data = json_decode($class->get_emp_data($_SESSION['emp_id']));
} else {
    echo "<script>location.href = './index.php';</script>";
}
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $register = json_decode($class->register());
    if($register->status == 'success'){
        $_SESSION['flashdata']['type']='success';
        $_SESSION['flashdata']['msg'] = ' Account has been registered successfully.';
        unset($_SESSION['emp_id']);
        session_destroy();
        echo "<div onload=\"showCustomAlert('success', 'Registered Successfully', 'Please contact with IT Department for Activate your account.', './index.php')\"></div>";
        // echo "<script>location.href = './index.php';</script>";
        // exit;
    }else{
        echo "<script>console.error(".json_encode($register).");</script>";
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
        <style type="text/css">
            
        </style>
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

                                <p class="text-muted m-b-0 font-14">Enter required<span class="text-danger">*</span> fields for register in system. </p>
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

                                <div class="form-group row m-b-20">
                                    <div class="col-12">
                                        <label for="emp_id">Employee ID</label>
                                        <input class="form-control" type="text" readonly name="emp_id" value="<?=$emp_data->data->emp_id?>">
                                    </div>
                                </div>

                                <div class="form-group row m-b-20">
                                    <div class="col-12">
                                        <label for="full_name">Full Name</label>
                                        <input class="form-control" type="text" readonly name="fullname" value="<?=$emp_data->data->name?>">
                                    </div>
                                </div>

                                <div class="form-group row m-b-20">
                                    <div class="col-12">
                                        <label for="username">Username<span class="text-danger">*</span></label>
                                        <input class="form-control" type="text" name="username" required="" placeholder="Enter your username">
                                    </div>
                                </div>

                                <div class="form-group row m-b-20">
                                    <div class="col-12">
                                        <label for="emailaddress">Email address</label>
                                        <input class="form-control" type="email" name="email" id="emailaddress" parsley-trigger="change" placeholder="john@deo.com">
                                    </div>
                                </div>

                                <div class="form-group row m-b-20">
                                    <div class="col-12">
                                        <label for="mobile">Mobile<span class="text-danger">*</span></label>
                                        <input type="text" name="mobile" parsley-trigger="change" required="" class="form-control" id="mobile" autocomplete="off" placeholder="Enter your mobile" value="<?=$emp_data->data->mobile?>">
                                    </div>
                                </div>

                                <div class="form-group row m-b-20">
                                    <div class="col-12">
                                            <label for="password">Password<span class="text-danger">*</span></label>
                                        <div class='form-label-group input-group'>
                                            <!-- <i class="fa fa-eye" id="togglePassword"></i> -->
                                            <input class="form-control" type="password" required="" id="password" name="password" placeholder="Enter your password">
                                            <span class="input-group-text" style="border-radius: 0px 4px 4px 0px"><i id="togglePassword" class="fa fa-eye"></i></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row m-b-20">
                                    <div class="col-12">
                                        <div class='form-label-group'>
                                            <label for="password">Confirm password<span class="text-danger">*</span></label>
                                            <input class="form-control" type="password" required="" id="cfrm_password" name="cfrm_password" placeholder="Enter your confirm password">
                                            <span id='message'></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row text-center m-t-10">
                                    <div class="col-12">
                                        <button class="btn btn-block btn-dark waves-effect waves-light btn_submit" type="submit">Create Account</button>
                                    </div>
                                </div>

                                        <input type="hidden" name="dept" value="<?=$emp_data->data->dept?>" readonly>
                                        <input type="hidden" name="avatar" value="<?=$emp_data->data->avatar?>" readonly>
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
        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.10/jquery.mask.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

        <script type="text/javascript">


        </script>

    </body>
</html>
<?php //} ?>