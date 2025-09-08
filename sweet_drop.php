<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Title Page</title>
        <!-- Bootstrap CSS -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/sweet-alert/sweetalert2.min.css" rel="stylesheet" type="text/css" />
        <!-- Dropzone css -->
        <link href="./plugins/dropzone/dropzone.css" rel="stylesheet" type="text/css" />
    </head>
    <body><br><br><br><br><br><br><br>
        <h1 class="text-center">
    <a href="javascript:void(0);" class="btn btn-info smt_attachment" id="" data-attach="ok"><i class="mdi mdi-cloud-upload "></i> Upload Documents</a>
    </h1>
    
<!-- jQuery -->
<script src="//code.jquery.com/jquery.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
<!-- Dropzone js -->
<script src="./plugins/dropzone/dropzone.js"></script>
<script src="./plugins/sweet-alert/sweetalert2.min.js"></script>
<script src="assets/pages/jquery.sweet-alert.init.js"></script>
<script type="text/javascript">
/****************************/
        //Disabling autoDiscover
        /*Dropzone.autoDiscover = false;
        if ($('#dropzone').length) {
        $(function() {
            var myDropzone = new Dropzone(".dropzone", {
                url: "./includes/ajaxFile/upload_smt_attachments.php",
                paramName: "file",
                maxFilesize: 5,
                maxFiles: 10,
                acceptedFiles: "image/png,image/jpeg,image/jpg,image/bmp,application/pdf",
                parallelUploads: 10,
                autoProcessQueue: false,
                init: function() {
                    this.on('success', function(){
                        if (this.getQueuedFiles().length == 0 && this.getUploadingFiles().length == 0) {
                            swal({
                                title:"Uploaded!",
                                text:"Your files bas been uploaded successfully.",
                                type:'success',allowOutsideClick:false
                            }).then(function(isConfirm){(isConfirm)?location.reload():""});
                        }
                    });
                }
            });
            $('#startUpload').click(function(){           
                myDropzone.processQueue();
            });

        });
    }*/
/********************************************/
$(document).on('click', '.smt_attachment', function (e) {
    e.preventDefault();
    // var itemId = $(this).data('id');
    swal({
        title: 'Dropzone File Upload',
        // html: '<form action="#" class="attform">'+
        html: '<form action="#" class="attform">'+
                '<div class="fallback">'+
                    '<input name="file" type="file" multiple />'+
                '</div>'+
            '</form>',
        type: 'warning',
        showCancelButton: true,
        cancelButtonColor: '#d33',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Upload it!',
        showLoaderOnConfirm: true,
        // showConfirmButton: false,
        // willOpen : () => {
        onOpen : () => {
            $('form.attform').attr('id','dropzone').addClass('dropzone');
            // const files = Swal.getPopup().querySelector('#dropzone')
            const myDropzone = new Dropzone('#dropzone', {
                url: "./includes/ajaxFile/upload_smt_attachments.php",
                paramName: "file",
                // paramName: 'someParameter[file]',
                maxFilesize: 5,
                maxFiles: 10,
                acceptedFiles: "image/png,image/jpeg,image/jpg,image/bmp,application/pdf",
                parallelUploads: 10,
                // autoProcessQueue: false,
                autoProcessQueue: true,
                init: function() {
                    this.on('success', function(){
                        if (myDropzone.getQueuedFiles().length == 0 && myDropzone.getQueuedFiles().length == 0) {
                            swal({
                                title:"Uploaded!",
                                text:"Your files bas been uploaded successfully.",
                                type:'success',allowOutsideClick:false
                            }).then(function(isConfirm){(isConfirm)?location.reload():""});
                        }
                    });
                }
            })
            myDropzone.on('sending', function(file, xhr, formData){
                formData.append('id', 'MYINVOICEID');
            })
        },

        preConfirm: function() {
            return new Promise(function(resolve) {
                myDropzone.processQueue()
                .done(function(response){
                    swal({
                        title:response.title,text:response.message,type:response.type,allowOutsideClick:false
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(){
                    swal(response.title, response.message, response.type);
                });
            });
        },

        /*preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    url: "./includes/ajaxFile/upload_smt_attachments.php",
                    type: 'POST',
                    data: {id:itemId,tbl:tbl},
                    cache: false,
                    dataType: "json",
                })
                .done(function(response){
                    swal({
                        title:response.title,text:response.message,type:response.type,allowOutsideClick:false
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(){
                    swal(response.title, response.message, response.type);
                });
            });
        },*/
        allowOutsideClick: false
    })

});

$('#startUpload').click(function(){           
    myDropzone.processQueue();
});
/*    function att_view(){
    var strView = '<form action="#" class="attform">'+
                    '<div class="fallback">'+
                        '<input name="file" type="file" multiple />'+
                    '</div>'+
                '</form>';
        return strView;
    }*/
/********************************************/
</script>
    </body>
</html>


