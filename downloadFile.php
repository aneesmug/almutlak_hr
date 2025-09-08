<?php
if(isset($_GET['file'])){
//    $path = "./assets/emp_documents/" . $_GET['file'];
    $path = "./" . $_GET['file'];
    $filename = $_GET['file'];
	
	//exploding the file based on . operator
		$file_ext = explode('.',$filename);
		//count taken (if more than one . exist; files like abc.fff.2013.pdf
		$file_ext_count=count($file_ext);
		//minus 1 to make the offset correct
		$cnt=$file_ext_count-1;
		// the variable will have a value pdf as per the sample file name mentioned above.
		$file_extension= $file_ext[$cnt];

    if(file_exists($path)) {
		
		if($file_extension == "jpg" OR $file_extension == "jpeg"){
			header('Content-Description: File Transfer');
			header('Content-Type: image/jpeg');
			header('Content-Disposition: attachment; filename='.basename($filename));
			header('Accept-Ranges: bytes');  // For download resume
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: public');
			header('Pragma: public');
			readfile($path);  //this is necessary in order to get it to actually download the file, otherwise it will be 0Kb
		}elseif($file_extension == "pdf"){
//			header('Content-Transfer-Encoding: binary');  // For Gecko browsers mainly
//			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filename)) . ' GMT');
//			header('Accept-Ranges: bytes');  // For download resume
//			header('Content-Length: ' . filesize($filename));  // File size
//			header('Content-Encoding: none');
//			header('Content-Type: application/pdf');  // Change this mime type if the file is not PDF
//			header('Content-Disposition: attachment; filename=' .basename($filename));  // Make the browser display the Save As dialog
//			readfile($path);  //this is necessary in order to get it to actually download the file, otherwise it will be 0Kb
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename="'.basename($filename).'"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filename));
			flush(); // Flush system output buffer
			readfile($path);
		}
    } else {
        echo "File not found on server";
    }
}else{
    echo "No file to download";
}
?>