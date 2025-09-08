<?php

    include_once 'db.php';
    if(isset($_POST["Import"])){
        $filename=$_FILES["file"]["tmp_name"];    
        if($_FILES["file"]["size"] > 0){
            $file = fopen($filename, "r");
            while (($getData = fgetcsv($file, 10000, ",")) !== FALSE){
                $result = mysqli_query($conDB, "INSERT INTO `attendance` (`emp_id`, `emp_name`, `date`, `time`, `punch_state`) values ('".$getData[0]."','".$getData[1]."','".date('Y-m-d', strtotime($getData[3]))."','".$getData[4]."','".$getData[5]."')");
                if(!isset($result)){
                  echo "<script type=\"text/javascript\">
                      alert(\"Invalid File:Please Upload CSV File.\");
                      window.location = \"index.php\"
                      </script>";
                } else {
                    echo "<script type=\"text/javascript\">
                    alert(\"CSV File has been successfully Imported.\");
                    window.location = \"index.php\"
                  </script>";
                }
            }
            fclose($file);  
        }
    }   
?>