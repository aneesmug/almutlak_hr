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





$param = $_GET['id'];

QRcode::png('http://sys.almutlak.com/attach_smt_request.php?id='.$param, false, QR_ECLEVEL_L, 6, 1, false);
// QRcode::png('http://192.168.9.158/almutlak/system/attach_smt_request.php?id='.$param, false, QR_ECLEVEL_L, 6, 1, false);





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



