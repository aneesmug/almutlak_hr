<?php
    include("./includes/qrcode/qrlib.php");
/*
Parameters
    String	$text	text string to encode
    String	$outfile	(optional) output file name, when false file is not saved
    Integer	$level	(optional) error correction level QR_ECLEVEL_L, QR_ECLEVEL_M, QR_ECLEVEL_Q or QR_ECLEVEL_H
    Integer	$size	(optional) pixel size, multiplier for each 'virtual' pixel
    Integer	$margin	(optional) code margin (silent zone) in 'virtual' pixels 
*/

    // outputs image directly into browser, as PNG stream
//	$tempDir = EXAMPLE_TMP_SERVERPATH;
//
//	$param = $_GET['id'];
//    ob_start("callback");
//    // here DB request or some processing
//    $codeText = 'DEMO - '.$param;
//    // end of processing here
//    $debugLog = ob_get_contents();
//    ob_end_clean();
////    QRcode::png('456465465');
//    QRcode::png($param, $img, 'H', 8, 2);




// how to save PNG codes to server
            // $uuid = md5(uniqid());
            // $uuidsha1 = sha1($uuid); 

            // $codeContents = $uuidsha1;
            // we need to generate filename somehow, 
            // with md5 or with database ID used to obtains $codeContents...
            // $fileName = ''.md5($codeContents).'.png';
$param = $_GET['hashcode'];
$verification = $_GET['verification'];

$tempDir = "./assets/qrcodes/";
$pngAbsoluteFilePath = $tempDir.$verification.$param.".png";
$urlPath = 'http://sys.almutlak.com/emp_card/index.php?hashcode='.$param.'&verification='.$verification;

QRcode::png($urlPath, $pngAbsoluteFilePath, QR_ECLEVEL_L, 4, 1, false);
// header("refresh:0; url= ./view_employee.php?id=".$verification);

/*
static QRcode::svg 	( 	  	$text,
		  	$elemId = false,
		  	$outFile = false,
		  	$level = QR_ECLEVEL_L,
		  	$width = false,
		  	$size = false,
		  	$margin = 4,
		  	$compress = false 
	) 
*/
// die;
?>
<!-- <body onload="window.history.go(-1); return false;"></body> -->
<body onload="window.location.href = './view_employee.php?emp_id=<?=$param?>';"></body>