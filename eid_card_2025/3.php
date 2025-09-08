<?php
    include("./includes/db_config.php");
?>
<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <title>Hello, world!</title>
</head>

<body>


    <!-- <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="input-group input-group-lg">
                    <span class="input-group-text" id="inputGroup-sizing-lg">Large</span>
                    <input type="text" class="form-control" aria-label="Sizing example input"
                        aria-describedby="inputGroup-sizing-lg">
                </div>
            </div>
        </div>
    </div> -->


    <!-- Optional JavaScript; choose one of the two! -->

    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script type='text/javascript' src="../system/app-assets/vendors/js/extensions/sweet-alert/v11/sweetalert2.js">
    </script>
    <script type='text/javascript' src="../system/app-assets/vendors/js/extensions/sweet-alert/v11/sweetalert2.min.js">
    </script>
    <script type='text/javascript' src="../system/app-assets/vendors/js/extensions/sweet-alert/v11/sweetalert2.all.js">
    </script>
    <script type='text/javascript'
        src="../system/app-assets/vendors/js/extensions/sweet-alert/v11/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Option 2: Separate Popper and Bootstrap JS -->
    <!--
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    -->
    <?php if (!$_GET['name']): ?>
    <script>
    window.addEventListener("load", function() {
        loadDateForEOS();
    })
    </script>
    <?php endif ?>
    <script>
        
    function loadDateForEOS() {
        Swal.fire({
            title: 'Enter Name for Card!',
            html: `
            <form id="submitEditCategoryForm">
                <div class="form-row customSweetAlertMLR">
                    <div class="form-group col-md-12">
                        <input type="text" name="name" class="form-control" id="name">
                    </div>
                    <div class="form-group col-md-12">
                    <label>Select Design</label><br>
                    <div class="d-inline-block custom-control custom-radio mr-1">
                        <input type="radio" class="custom-control-input" name="design" id="radio5" value="1">
                        <label class="custom-control-label" for="radio5"><img src="./design1.jpg" width="200" /></label>
                    </div>
                    <div class="d-inline-block custom-control custom-radio mr-1">
                        <input type="radio" class="custom-control-input " name="design" id="radio6" value="0">
                        <label class="custom-control-label" for="radio6"><img src="./design2.jpg" height="200" /></label>
                    </div>
                </div>
                </div>
            </form>
        `,
            showCancelButton: false,
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Enter!',
            allowEscapeKey: false,
            showLoaderOnConfirm: true,
            allowOutsideClick: false,
            // willOpen: function() {
            //     jQuery('#name').datepicker({
            //         format: "yyyy-mm-dd",
            //         autoclose: true,
            //         todayHighlight: true,
            //         todayBtn: "linked",
            //     });
            // },
            preConfirm: function() {
                var name = $('input[name="name"]').val();
                if (name == "") {
                    Swal.showValidationMessage(`Please enter name for card.`)
                }
                return new Promise(function(reject, resolve) {
                    if (name == '') {
                        reject("Please fill all mendatory(*) fields first!");
                        return false;
                    }
                    $.ajax({
                            url: './includes/ajaxFile/ajaxEmployee.php',
                            type: 'POST',
                            dataType: "JSON",
                            data: {
                                name: name,
                                ajaxType: 'name_get'
                            },
                        })
                        .done(function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    title: response.title,
                                    text: response.message,
                                    icon: response.type,
                                    allowOutsideClick: false
                                }).then(function(isConfirm) {
                                    (isConfirm) ? window.location.href =
                                        './employee_audit_gen.php?date=' + name: ""
                                });
                            }
                        })
                        .fail(function() {
                            Swal.fire("response.title", "response.message", "response.type");
                        });
                });
            },
        })
    }
    </script>

</body>

</html>