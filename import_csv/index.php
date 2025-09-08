<?php 

if (!function_exists('base_url')) {
    function base_url($atRoot=FALSE, $atCore=FALSE, $parse=FALSE){
        if (isset($_SERVER['HTTP_HOST'])) {
            $http = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
            $hostname = $_SERVER['HTTP_HOST'];
            $dir =  str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

            $core = preg_split('@/@', str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(dirname(__FILE__))), NULL, PREG_SPLIT_NO_EMPTY);
            $core = $core[0];

            $tmplt = $atRoot ? ($atCore ? "%s://%s/%s/" : "%s://%s/") : ($atCore ? "%s://%s/%s/" : "%s://%s%s");
            $end = $atRoot ? ($atCore ? $core : $hostname) : ($atCore ? $core : $dir);
            $base_url = sprintf( $tmplt, $http, $hostname, $end );
        }
        else $base_url = 'http://localhost/mochasys2022/';
        if ($parse) {
            $base_url = parse_url($base_url);
            if (isset($base_url['path'])) if ($base_url['path'] == '/') $base_url['path'] = '';
        }
        return $base_url;
    }
}

function home_base_url(){   
// first get http protocol if http or https
$base_url = (isset($_SERVER['HTTPS']) &&
$_SERVER['HTTPS']!='off') ? 'https://' : 'http://';
// get default website root directory
$tmpURL = dirname(__FILE__);
// when use dirname(__FILE__) will return value like this "C:\xampp\htdocs\my_website",
//convert value to http url use string replace, 
// replace any backslashes to slash in this case use chr value "92"
$tmpURL = str_replace(chr(92),'/',$tmpURL);
// now replace any same string in $tmpURL value to null or ''
// and will return value like /localhost/my_website/ or just /my_website/
$tmpURL = str_replace($_SERVER['DOCUMENT_ROOT'],'',$tmpURL);
// delete any slash character in first and last of value
$tmpURL = ltrim($tmpURL,'/');
$tmpURL = rtrim($tmpURL, '/');
// check again if we find any slash string in value then we can assume its local machine
    if (strpos($tmpURL,'/')){
// explode that value and take only first value
       $tmpURL = explode('/',$tmpURL);
       $tmpURL = $tmpURL[0];
      }
// now last steps
// assign protocol in first value
   if ($tmpURL !== $_SERVER['HTTP_HOST'])
// if protocol its http then like this
      $base_url .= $_SERVER['HTTP_HOST'].'/'.$tmpURL.'/';
    else
// else if protocol is https
      $base_url .= $tmpURL.'/';
// give return value
return $base_url; 
}
// and test it
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Upload CSV file for Attendance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="./../../plugins/sweet-alert/v11/sweetalert2.css">
    <link rel="stylesheet" href="./../../plugins/sweet-alert/v11/sweetalert2.min.css">
</head>

<body>
    <div id="wrap">
        <div class="container">
            <div class="row">
                <div class="col-12 mt-5">
                <form class="form-horizontal" action="uploadCsv.php" method="post" name="upload_excel" enctype="multipart/form-data">
                    <fieldset class="border rounded-3 p-3">

                        <!-- Form Name -->
                        <legend class="float-none w-auto px-3">Upload CSV file from BioTime.</legend>

                        <!-- File Button -->
                        <!-- <div class="form-group">
                            <label class="col-md-4 control-label" for="filebutton">Select File</label>
                            <div class="col-md-4">
                                <input type="file" name="file" id="file" class="input-large">
                            </div>
                        </div> -->

                        <!-- Button -->
                        <!-- <div class="form-group">
                            <label class="col-md-4 control-label" for="singlebutton">Import data</label>
                            <div class="col-md-4">
                                <button type="submit" id="submit" name="Import" class="btn btn-primary button-loading" data-loading-text="Loading...">Import</button>
                            </div>
                        </div> -->
                        <div class="form-group">
                            <!-- <label class="col-md-12" for="singlebutton">Import data</label> -->
                            <div class="col-md-5">
                                <a href="javascript:void(0);" class="btn btn-block btn-primary button-loading addDocuAtter">Import</a>
                            </div>
                        </div>

                    </fieldset>
                </form>
                </div>
            </div>
            <?php
               // get_all_records();
            ?>
        </div>
    </div>
</body>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script type="text/javascript" src="./../../plugins/sweet-alert/v11/sweetalert2.js"></script>
<script type="text/javascript" src="./../../plugins/sweet-alert/v11/sweetalert2.min.js"></script>
<script type="text/javascript" src="./../../plugins/sweet-alert/v11/sweetalert2.all.js"></script>
<script type="text/javascript" src="./../../plugins/sweet-alert/v11/sweetalert2.all.min.js"></script>

<script type="text/javascript">
$(document).on('click', '.addDocuAtter', function (e) {
    e.preventDefault();
    // var validExtensions = ["image/jpg", "image/jpeg", "image/png", "application/pdf"];
    var validExtensions = ["application/vnd.ms-excel","text/plain","text/csv","text/tsv"];
    Swal.fire({
        title: 'Add BioTime CSV file.',
        html: `<input type="file" name="attach" id="checkatt" class="input-large">`,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Upload!',
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        /*animation: "slide-from-top",*/
        width: "30%",
        preConfirm: function() {
            // Swal.getCancelButton().setAttribute('disabled');
            Swal.getCancelButton().setAttribute('hidden', true);
            $('#checkatt').prop('disabled', true);

            var form_Data = new FormData();
            var file = $('#checkatt')[0].files;
            form_Data.append("file", file[0]);
            
            if(file.length == 1){
                var filesiz = 1048576 * 10;
                var isValidExt = validExtensions.indexOf(file[0].type) > -1;
                var extCheck = ( isValidExt == false );
                var sizCheck = ( file[0].size >= filesiz );
            }
            var fileCheck = ( file.length == 0 )?"0":"1";
            if(file.length == 0){
                Swal.showValidationMessage(`Please select attachment file.`)
            } else if(isValidExt == false){
                Swal.showValidationMessage(`You can upload only CSV file`)
            } else if(file[0].size >= filesiz){
                Swal.showValidationMessage(`Upload file not morethen 10MB.`)
            }
            
            return new Promise(function(reject, resolve) {
                if( fileCheck == "0" || extCheck == true || sizCheck == true ){
                    reject("Please fill all mendatory(*) fields first!");
                    return false;
                }
                $.ajax({
                    url: './uploadCsv.php',
                    type: 'POST',
                    dataType: "JSON",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_Data,
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(){
                    Swal.fire(response.title, response.message, response.type);
                });
            });
        },
    })
});
</script>
</html>