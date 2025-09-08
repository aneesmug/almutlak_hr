<?php
	include("./../includes/db.php");
		
	$hashcode = $_GET['hashcode'];
	$verification = $_GET['verification'];

	// $getquery = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `emp_id`='".$hashcode."' AND `id`='".$verification."' ");
  $getquery = mysqli_query($conDB, "SELECT 
  `employees`.*,
  `department`.`dep_nme` AS `dept`,
  `portfolio`.`description`
  FROM `employees` 
  LEFT JOIN `portfolio` ON `portfolio`.`emp_id` = `employees`.`emp_id` 
  LEFT JOIN `department` ON `department`.`id` = `employees`.`dept` 
  WHERE `employees`.`id`='".$verification."' AND `employees`.`emp_id`='".$hashcode."' ");
	if(mysqli_num_rows($getquery) !== 0){
		while($rec = mysqli_fetch_assoc($getquery)){
			$id_get = $rec["id"];
			$name_get = $rec["name"];
			$emp_id_get = $rec["emp_id"];
			$iqama_get = $rec["iqama"];
			$mobile_get = $rec["mobile"];
			$dept_get = $rec["dept"];
      $emptype_get = $rec["emptype"];
			$email_get = $rec["c_email"];
      $avatar_get = $rec["avatar"];
      $status_get = $rec["status"];
      $sex_get = $rec["sex"];
      $description_get = $rec['description'];
		}

    if(!empty($_GET["action"])) {
        foreach ($getquery as $valueArray) {
            require_once "VcardExport.php";
            $vcardExport = new VcardExport();
            $vcardExport->contactVcardExportService(array($valueArray));
            exit;
        }
    }


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge"><![endif]-->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Page title -->
  <title><?= $name_get ?> Portfolio</title>
  <!-- /Page title -->

  <!-- CSS Files
  ========================================================= -->
  <!-- Google web fonts - Open Sans -->
  <link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,700" rel='stylesheet' type='text/css'>
  <!-- /Google web fonts -->
  <!-- Bootstrap CSS -->
  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <!-- /Bootstrap CSS -->
  <!-- Font Awesome -->
  <link rel="stylesheet" href="vendor/font-awesome/css/font-awesome.min.css" >
  <!-- /Font Awesome -->
  <!-- Nivo Lightbox -->
  <link href="vendor/nivo-lightbox/nivo-lightbox.css"  rel="stylesheet">
  <link rel="stylesheet" href="vendor/nivo-lightbox/themes/default/default.css" type="text/css" />
  <!-- /Nivo Lightbox -->
  <!-- Animate CSS -->
  <link href="vendor/animate.css" rel="stylesheet">
  <!-- /Animate CSS -->
  <!-- Theme Styles -->
  <link href="css/styles.css" rel="stylesheet">
   <link href="css/themes/employee.css" rel="stylesheet"> 
  <!-- Theme Styles -->
  <!-- / CSS Files
  ========================================================= -->


  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!--[if lt IE 9]>
    <script src="../../../oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js" tppabs="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="../../../oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js" tppabs="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->

  <!-- Favicon and Touch Icons
  ========================================================= -->
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <!-- /Favicon
  ========================================================= -->
</head>

<body data-spy="scroll" data-target="#side-menu">

<!-- Page Loader
========================================================= -->
<div class="loader" id="page-loader"> 
  <div class="loading-wrapper">
    <div class="tp-loader spinner"></div>
    <!-- Edit With Your Name -->
    <div class="side-menu-name">
      <strong><?php /*echo implode(' ', array_slice(str_word_count($name_get, 2), 0, 2)); */?></strong>
      <strong><?=(explode(" ",$name_get)[0])." ".(explode(" ",$name_get)[1]);?></strong>
    </div>
    <!-- /Edit With Your Name -->
    <!-- Edit With Your Job -->
    <p class="side-menu-job"><?= $dept_get ?> | Department </p>
    <!-- /Edit With Your Job -->
  </div>   
</div>
<!-- /End of Page loader
========================================================= -->
<!-- CONTENT
========================================================= -->
<section id="content-body" class="container animated">
  <div class="row" id="intro">

    <!-- Header Colors -->
    <div class="col-md-10 col-sm-10  col-md-offset-2 col-sm-offset-1 clearfix top-colors">
      <div class="top-color top-color1"></div>
      <div class="top-color top-color2"></div>
      <div class="top-color top-color3"></div>
      <div class="top-color top-color1"></div>
      <div class="top-color top-color2"></div>
    </div>
    <!-- /Header Colors -->    
    
    <!-- Beginning of Content -->
    <?php if ($status_get == 1): ?>
    <div class="col-md-10 col-sm-10 col-md-offset-2 col-sm-offset-1 resume-container">
      
      <!-- Header Buttons -->
      <div class="row">
        <div class="header-buttons col-md-10 col-md-offset-1">
          <!-- Download Resume Button -->
          <a href="index.php?action=export&hashcode=<?=$hashcode?>&verification=<?=$verification?>" class="btn btn-default btn-top-resume"><i class="fa fa-download"></i><span class="btn-hide-text">Download my card</span></a>
        </div>
      </div>
      <!-- /Header Buttons -->

      <!-- =============== PROFILE INTRO ====================-->
      <div class="profile-intro row">
        <!-- Left Collum with Avatar pic -->
        <div class="col-md-4 profile-col">
          <!-- Avatar pic -->
          <div class="profile-pic">
            <div class="profile-border">
              <!-- Put your picture here ( 308px by 308px for retina display)-->
              <!-- <img src="img/LogoMocha.png" alt=""> -->
              <img src="./../<?=$avatar_get ?>" alt="">
              <!-- /Put your picture here -->
            </div>          
          </div>
           <!-- /Avatar pic -->
        </div>
        <!-- /Left columm with avatar pic -->
  
        <!-- Right Columm -->
        <div class="col-md-7">
          <!-- Welcome Title-->
          <h1 class="intro-title1">Hi, i'm <span class="color1 bold"><?=(explode(" ",$name_get)[0])." ".(explode(" ",$name_get)[1])?>!</span></h1>
          <!-- /Welcome Title -->
          <!-- Job - -->
          <h2 class="intro-title2"><?=$dept_get." ".$emptype_get?></h2>
          <!-- /job -->
          <!-- Description -->
          <!--<p><strong>Turpis, sit amet iaculis dui consectetur at.</strong> Cras sagittis molestie orci. <strong>Suspendisse ut laoreet mi</strong>. Phasellus eu tortor vehicula, blandit enim eu, auctor massa. Nulla ultricies tortor dolor, sit amet suscipit enim <strong>condimentum id</strong>. Etiam eget iaculis tellus.  Varius sit amet.</p>-->
          <!-- /Description -->
        </div>
        <!-- /Right Collum -->
      </div>
      <!-- ============  /PROFILE INTRO ================= -->
      
      <!-- ============  TIMELINE ================= -->
      <div class="timeline-wrap">
        <div class="timeline-bg">

          <!-- ====>> SECTION: PROFILE INFOS <<====-->
          <section class="timeline profile-infos">

            <!-- VERTICAL MARGIN (necessary for the timeline effect) -->
            <div class="line row timeline-margin">
              <div class="content-wrap">
                <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
                <div class="col-md-2 timeline-progress hidden-sm hidden-xs full-height"></div>
                <div class="col-md-9 bg1 full-height"></div>
              </div>
            </div>
            <!-- /VERTICAL MARGIN -->

            <!-- SECTION TITLE -->
            <div class="line row">
              <!-- Margin Collums (necessary for the timeline effect) -->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <div class="col-md-2 timeline-progress hidden-sm hidden-xs timeline-title full-height">
              </div>
              <!-- /Margin Collums -->
              <!-- Item Content -->
              <div class="col-md-8 content-wrap bg1">
                <!-- Section title -->
                <h2 class="section-title">Profile</h2>
                <!-- /Section title -->
              </div>
              <!-- /Item Content -->
              <!-- Margin Collum-->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <!-- / Margin Collum-->
            </div>
            <!-- /SECTION TITLE -->

            <!-- SECTION ITEM -->
            <div class="line row">
              <!-- Margin Collums (necessary for the timeline effect) -->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <div class="col-md-2 timeline-progress hidden-sm hidden-xs full-height timeline-point "></div>
              <!-- /Margin Collums -->
              <!-- Item Content -->
              <div class="col-md-8 content-wrap bg1">
                <div class="line-content">
                  <!-- Subtitle -->
                  <h3 class="section-item-title-1">Full Name</h3>
                  <!-- /Subtitle -->
                  <!-- content -->
                  <p><?= $name_get ?></p>
                  <!-- /Content -->
                </div>
              </div>
              <!-- /Item Content -->
              <!-- Margin Collum-->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <!-- /Margin Collum-->
            </div>
            <!-- /SECTION ITEM -->
            
            <!-- SECTION ITEM -->
            <div class="line row">
              <!-- Margin Collums (necessary for the timeline effect) -->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <div class="col-md-2 timeline-progress hidden-sm hidden-xs full-height timeline-point "></div>
              <!-- /Margin Collums -->
              <!-- Item Content -->
              <div class="col-md-8 content-wrap bg1">
                <div class="line-content">
                  <!-- Subtitle -->
                  <h3 class="section-item-title-1">Mobile</h3>
                  <!-- /Subtitle -->
                  <!-- content -->
                  <p><?= $mobile_get ?></p>
                  <!-- /Content -->
                </div>
              </div>
              <!-- /Item Content -->
              <!-- Margin Collum-->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <!-- /Margin Collum-->
            </div>
            <!-- /SECTION ITEM -->   

            <!-- SECTION ITEM -->
            <?php if ($email_get): ?>
            <div class="line row">
              <!-- Margin Collums (necessary for the timeline effect) -->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <div class="col-md-2 timeline-progress hidden-sm hidden-xs full-height timeline-point "></div>
              <!-- /Margin Collums -->
              <!-- Item Content -->
              <div class="col-md-8 content-wrap bg1">
                <div class="line-content">
                  <!-- Subtitle -->
                  <h3 class="section-item-title-1">Email</h3>
                  <!-- /Subtitle -->
                  <!-- content -->
                  <p><a href="mailto:<?= $email_get ?>"><?= $email_get ?></a></p>
                  <!-- /Content -->
                </div>
              </div>
              <!-- /Item Content -->
              <!-- Margin Collum -->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <!-- /Margin Collums -->
            </div>
            <?php endif ?> 
            <?php if ($description_get): ?>
            <div class="line row">
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <div class="col-md-2 timeline-progress hidden-sm hidden-xs full-height timeline-point "></div>
              <div class="col-md-8 content-wrap bg1">
                <div class="line-content">
                  <h3 class="section-item-title-1">Introduction</h3>
                  <?=$description_get?>
                </div>
              </div>
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>

            </div>
            <?php endif ?>
            <!-- /SECTION ITEM --> 
            <?php 
              $socquery = mysqli_query($conDB, "SELECT `social_list`.*, `social`.* FROM `social_list` LEFT JOIN `social` ON `social`.`social_id` = `social_list`.`id` LEFT JOIN `employees` ON `social`.`emp_id` = `employees`.`emp_id` WHERE `employees`.`id`='".$id_get."' ");
              if (mysqli_num_rows($socquery) > 1): 
            ?>
            <!-- SECTION ITEM -->
            <div class="line row">
              <!-- Margin Collums (necessary for the timeline effect) -->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <div class="col-md-2 timeline-progress hidden-sm hidden-xs full-height timeline-point "></div>
              <!-- Margin Collums (necessary for the timeline effect) -->
              <!-- Item Content -->
              <div class="col-md-8 content-wrap bg1">
                <div class="line-content">
                  <!-- Subtitle -->
                  <h3 class="section-item-title-1">Find Me On</h3>
                  <!-- /Subtitle -->
                  <!-- content -->
                  <?php
                    while($rec = mysqli_fetch_assoc($socquery)){
                      $mainlink = parse_url($rec['link']);
                      $social = explode('//',$mainlink['host'])[0];
                      $link = ucfirst(explode('.',$social)[0]);
                  ?>
                      <a href="<?=$rec['link']."".$rec['s_link']?>" class="btn" style="background-color: <?=$rec['color']?>; color: #fff;" target="_blank">
                          <i class="<?=$rec['icon']?>" aria-hidden="true"></i> <span class="btn-hide-text"><?=$rec['s_link']?></span>
                      </a>
                  <?php } ?>
                  <!-- /Content -->
                </div>
              </div>
              <!-- /Item Content -->
              <!-- Margin Collum -->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>

              <!-- /Margin Collum -->
            </div>
            <?php endif ?>
            <!-- /SECTION ITEM --> 
          </section>
          <!-- ==>> /SECTION: PROFILE INFOS -->     

          <!-- ====>> SECTION: CONTACT <<====-->
          
          <!-- ==>> /SECTION: CONTACT -->

          <!-- ====>> SECTION: THANK YOU <<====-->
          <section class="timeline profile-infos">

            <!-- VERTICAL MARGIN (necessary for the timeline effect) -->
            <div class="line row timeline-margin timeline-margin-big">
              <div class="content-wrap">
                <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
                <div class="col-md-2 timeline-progress hidden-sm hidden-xs full-height"></div>
                <div class="col-md-9 bg1 full-height"></div>
              </div>
            </div>
            <!-- /VERTICAL MARGIN -->

            <!-- SECTION ITEM -->
            <div class="line row line-thank-you">
              <!-- Margin Collums (necessary for the timeline effect) -->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <div class="col-md-2 timeline-progress hidden-sm hidden-xs full-height timeline-point "></div>
              <!-- /Margin Collums -->
              <!-- Item Content -->
              <div class="col-md-8 content-wrap bg1">
                <div class="line-content">
                  <!-- Subtitle -->
                  <h3 class="thank-you">Thank You!</h3>
                  <!-- /Subtitle -->
                </div>
              </div>
              <!-- /Item Content -->
              <!-- Margin Collum-->
              <div class="col-md-1 bg1 timeline-space full-height hidden-sm hidden-xs"></div>
              <!-- / Margin Collum-->
            </div>
            <!-- /SECTION ITEM -->            
          </section>
          <!-- ==>> /SECTION: PROFILE INFOS -->
        </div>
      </div>
      <!-- ============  /TIMELINE ================= -->

    </div>
<?php else: ?>
  <div class="col-md-10 col-sm-10 col-md-offset-2 col-sm-offset-1 resume-container">
      <div class="profile-intro row">
        <div class="col-md-4 profile-col">
          <div class="profile-pic">
            <div class="profile-border">
              <img src="./../<?=$avatar_get ?>" alt="">
            </div>          
          </div>
        </div>
        <div class="col-md-7">
          <h1 class="intro-title1"><?=($sex_get == "male")? "Mr.":"Mrs." ?>, <span class="color1 bold"><?= implode(' ', array_slice(str_word_count($name_get, 2), 0, 2)); ?></span> not working in Al Mutalak Co.</h1>
        </div>
      </div>
      <div class="timeline-wrap">
          <section class="profile-infos">
              <div class="col-md-8">
                  <h3 class="thank-you">Thank You!</h3>
              </div>
          </section>
      </div>
    </div>
<?php endif ?>

  </div> 
</section>
<!-- /CONTENT
========================================================= -->

<!-- Contact Form - Ajax Messages
========================================================= -->
<!-- Form Sucess -->
<div class="form-result modal-wrap" id="contactSuccess">
  <div class="modal-bg"></div>
  <div class="modal-content">
    <h4 class="modal-title"><i class="fa fa-check-circle"></i> Success!</h4>
    <p>Your message has been sent to us.</p>
  </div>
</div>
<!-- /Form Sucess -->
<!-- form-error -->
<div class="form-result modal-wrap" id="contactError">
  <div class="modal-bg"></div>
  <div class="modal-content">
    <h4 class="modal-title"><i class="fa fa-times"></i> Error</h4>
    <p>There was an error sending your message.</p>
  </div>
</div>
<!-- /form-error -->

<!-- Contact Form - Ajax Messages
========================================================= -->

<!-- Javascript Files
========================================================= -->
<!-- jQuery (necessary for plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js" tppabs=""></script>
<!-- /jquery -->
<!-- bootstrap -->
<script src="vendor/bootstrap/js/bootstrap.min.js" tppabs="http://www.dotrex.co/vertica/themes/vendor/bootstrap/js/bootstrap.min.js"></script>
<!-- /bootstrap -->
<!-- easing -->
<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js" tppabs=""></script>
<!-- /easing -->
<!-- jQuery Mousewheel -->
<script src="vendor/jquery.mousewheel.min.js" tppabs="http://www.dotrex.co/vertica/themes/vendor/jquery.mousewheel.min.js"></script>
<!-- /jQuery Mousewheel -->
<!-- jQuery Mousewheel Smoothscroll -->
<script src="vendor/jquery.nicescroll.min.js" tppabs="http://www.dotrex.co/vertica/themes/vendor/jquery.nicescroll.min.js"></script>
<script src="vendor/jquery.nicescroll.plus.js" tppabs="http://www.dotrex.co/vertica/themes/vendor/jquery.nicescroll.plus.js"></script>
<!-- /jQuery Mousewheel Smoothscroll -->
<!-- Waypoints (for CSS3 animations) -->
<script src="vendor/waypoints.min.js" tppabs="http://www.dotrex.co/vertica/themes/vendor/waypoints.min.js"></script>
<!-- /waypoints -->
<!-- Modal box-->
<script src="vendor/nivo-lightbox/nivo-lightbox.min.js" tppabs="http://www.dotrex.co/vertica/themes/vendor/nivo-lightbox/nivo-lightbox.min.js"></script>
<!-- /Modal Box -->
<!-- Carousel-->
<script src="vendor/jquery.bxslider.min.js" tppabs="http://www.dotrex.co/vertica/themes/vendor/jquery.bxslider.min.js"></script>
<!-- /Carousel -->
<!-- Front-end Validator (for forms) -->
<script src="vendor/jquery.validate.min.js" tppabs="http://www.dotrex.co/vertica/themes/vendor/jquery.validate.min.js"></script>
<!-- /Front-end Validator -->
<!-- placeholder Support for IE -->
<script src="vendor/placeholders.jquery.min.js" tppabs="http://www.dotrex.co/vertica/themes/vendor/placeholders.jquery.min.js"></script>
<!-- /placeholder support -->
<!-- Cross-browser -->
<script src="js/cross-browser.js" tppabs="http://www.dotrex.co/vertica/themes/js/cross-browser.js"></script>
<!-- /Cross-browser -->
<!-- Configurations -->
<script src="js/main.js" tppabs="http://www.dotrex.co/vertica/themes/js/main.js"></script>
<!-- /Configurations -->
</body>
</html>
<?php } ?>