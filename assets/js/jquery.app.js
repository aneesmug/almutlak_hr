/**
 * Theme: Highdmin - Responsive Bootstrap 4 Admin Dashboard
 * Author: Coderthemes
 * Module/App: Main Js
 */


//Sweet Alert v2.0
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/sweet-alert/v11/sweetalert2.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/sweet-alert/v11/sweetalert2.min.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/sweet-alert/v11/sweetalert2.all.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/sweet-alert/v11/sweetalert2.all.min.js"));
// Avatar Cropie
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/croppie/croppie.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/croppie/croppie.min.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/croppie/exif.js"));
// File Dropzone
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/dropzone/dropzone.js"));
// Time Picker
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/bootstrap-timepicker/bootstrap-timepicker.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/clockpicker/js/bootstrap-clockpicker.min.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/bootstrap-daterangepicker/daterangepicker.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"));
// Input Mask
/*$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/imask.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/bootstrap-inputmask/jquery.inputmask.min.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js"));
/*$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/inputmask-pages/inputmask.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/inputmask-pages/inputmask.min.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/inputmask-pages/jquery.inputmask.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/inputmask-pages/jquery.inputmask.min.js"));*/
// Validate
// $("head").append($("<script type='text/javascript'></script>").attr("src", "./assets/js/jquery.validate.js"));
// Select 2
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/select2/js/select2.min.js"));
$("head").append($("<script type='text/javascript'></script>").attr("src", "./plugins/bootstrap-select/js/bootstrap-select.js"));




/*$.getScript("./plugins/sweet-alert/v11/sweetalert2.js");
$.getScript("./plugins/sweet-alert/v11/sweetalert2.min.js");
$.getScript("./plugins/sweet-alert/v11/sweetalert2.all.js");
$.getScript("./plugins/sweet-alert/v11/sweetalert2.all.min.js");
$.getScript("./plugins/croppie/croppie.js");
$.getScript("./plugins/croppie/croppie.min.js");
$.getScript("./plugins/croppie/exif.js");*/


function __(key, defaultText = '') {
    // Check if the global language object has been defined by PHP.
    if (typeof window.lang === 'undefined' || window.lang === null) {
        // Log an error for easier debugging if the object is missing.
        console.error("Translation Error: The global 'lang' object is not defined. Make sure it's included correctly in your PHP template.");
        return defaultText || key;
    }
    // New check: Warn if the lang object seems empty.
    if (Object.keys(window.lang).length < 5) {
        console.warn("Translation Warning: The global 'lang' object is defined but appears to be empty or incomplete. Check the output of json_encode in your PHP template.", window.lang);
    }
    // Check if the specific key exists in the language object.
    if (typeof window.lang[key] !== 'undefined') {
        return window.lang[key];
    }
    // If the key is not found, return the default text or the key itself.
    return defaultText || key;
}


// --- Main Script Logic (Your existing functions) ---
$(document).ready(function() {
    $('.addnote').on('click', function() {add_noties.call(this)});
        // Get the current page's name from the data-page attribute
    const currentPage = $('body').data('page');
    // Check if we are on the 'edit-employee' page
    if (currentPage === 'edit-employee' || currentPage === 'new-employee' || currentPage === 'add_emp_slry' ) {
        initializeEditFormValidation();
        console.log('load employees');
    }
    // Check if we are on the 'view-employee' page
    if (currentPage === 'view-employee') {
        console.log("Running script for View Employee page.");
        // All your view-employee specific code goes here
    }
});


$(document).on('click', '.deleteAjax', function (e) {
    e.preventDefault();
    var itemId = $(this).data('id');
    var tbl = $(this).data('tbl');
    var column = $(this).data('column');
    var fileCheck = ($(this).data('file')==1)?{id:itemId,tbl:tbl,column:column}:{id:itemId,tbl:tbl};
    Swal.fire({
        title: __("are_you_sure"),
        text: __("revert_warning"),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_delete_it"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    url: './includes/ajaxFile/deleteAjax.php',
                    type: 'POST',
                    data: fileCheck,
                    cache: false,
                    dataType: "json",
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.isDeleteAjax', function (e) {
    e.preventDefault();
    var itemId = $(this).data('id');
    var tbl = $(this).data('tbl');
    Swal.fire({
        title: __("are_you_sure"),
        text: __("revert_warning"),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_delete_it"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    url: './includes/ajaxFile/deleteAjax.php',
                    type: 'POST',
                    data: {id: itemId, tbl: tbl, ajaxType:'isDelete'},
                    cache: false,
                    dataType: "json",
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.deleteTblAjax', function (e) {
    e.preventDefault();
    var itemId = $(this).data('id');
    var tbl = $(this).data('tbl');
    var column = $(this).data('column');
    var fileCheck = ($(this).data('file')==1)?{id:itemId,tbl:tbl,column:column}:{id:itemId,tbl:tbl};
    Swal.fire({
        title: __("are_you_sure"),
        text: __("revert_warning"),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_delete_it"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    url: './includes/ajaxFile/deleteAjax.php',
                    type: 'POST',
                    data: fileCheck,
                    cache: false,
                    dataType: "json",
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?$('#'+tbl+'').DataTable().ajax.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.deleteInvAjax', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    var tbl = $(this).data('tbl');
    Swal.fire({
        title: __("are_you_sure"),
        text: __("revert_warning"),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_delete_it"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    url: './includes/ajaxFile/deleteAjax.php',
                    type: 'POST',
                    data: {id:id, tbl:tbl, ajaxType:'deleteInv'},
                    cache: false,
                    dataType: "json",
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

//:::::::::::Custom Sweet alert Handling::::::::::://
function showCustomAlert(iconD, titleD, textD, locationD){
    Swal.fire({
        title: titleD,
        text: textD,
        icon: iconD,
        allowOutsideClick:false,
        confirmButtonClass: "btn btn-lg btn-danger",
        buttonsStyling: false,
    }).then(function(isConfirm){(isConfirm)?window.location = locationD:""});
}$(function(){
    $('div[onload]').trigger('onload');
});
function showSweetAlert(title, message, type = 'success', redirectUrl = '') {
    Swal.fire({
        title: title,
        text: message,
        icon: type,
        allowOutsideClick: false,
        customClass: {
            confirmButton: 'btn btn-lg btn-primary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed && redirectUrl) {
            window.location.href = redirectUrl;
        }
    });
}
//:::::::::::Custom Sweet alert Handling::::::::::://

$(document).on('click', '.signout', function (e) {
    e.preventDefault();
    var action = $(this).data('action');
    Swal.fire({
        title: __("are_you_sure"),
        text: __("signout_warning"),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_signout"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    url: './includes/ajaxFile/signoutAjax.php',
                    type: 'POST',
                    data: {action:action},
                    cache: false,
                    dataType: "json",
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

////////////////////////////////////////////////////////////////////
//////////////////       Start Item Handling       /////////////////
////////////////////////////////////////////////////////////////////

function addItemFunc(){
    var validExtensions = ["image/jpg", "image/jpeg", "image/png"];
    Swal.fire({
        title: __("add_new_item_info"),
        html: item_HTML(),
        text: __("revert_warning"),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_register"),
        showLoaderOnConfirm: true,
        width: '30%',
        allowOutsideClick: false,
        willOpen: function() {
            $(document).on('change', '#price_level', function (e) {
                // var style = (this.value == 1 || this.value == 2) ? 'block' : 'none';
                if (this.value == 1 || this.value == 2) {
                    $(".attachmentDIV").removeClass("noneDIV");
                    $("#fileupload").attr('name', 'fileupload');
                    $("#fileupload").attr('accept', validExtensions);
                } else {
                    $(".attachmentDIV").addClass("noneDIV");
                    $("#checkatt").attr('name', '');
                }
            })
            $.ajax({
                url: './includes/ajaxFile/ajaxItem.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "category_type_view"},
                success: function(response) {
                    if (response.status == 200) {
                        var len = response.data.length;
                        let options = '';
                        for (var i = 0; i<len; i++){
                            $("#price_level").append("<option value='"+response.data[i].id+"'>"+response.data[i].type+"</option>");
                        }
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
            $("#price_level").bind("change", function() {
                var price_level = $("#price_level").val();
                $.ajax({
                    url: "./includes/ajaxFile/ajaxItem.php",
                    type: 'POST',
                    data: {request: 1, ajaxType: "price_level_view", price_level: price_level},
                    success: function(response) {
                        $("#category_id").html(response);
                    }
                });
            });
        },
        preConfirm: function() {
            var form_Data = new FormData();
            var file  = $('#fileupload')[0].files;
            var name_eng = $('#i_name_eng').val();
            var name_ar = $('#i_name_ar').val();
            var big_price = $('#i_big_price').val();
            var small_price = $('#i_small_price').val();
            var big_cal = $('#i_big_cal').val();
            var small_cal = $('#i_small_cal').val();
            var category_id = $('#category_id').val();
            var price_level = $('#price_level').val();
            form_Data.append("file", file[0]);
            form_Data.append("name_eng", name_eng);
            form_Data.append("name_ar", name_ar);
            form_Data.append("big_price", big_price);
            form_Data.append("small_price", small_price);
            form_Data.append("big_cal", big_cal);
            form_Data.append("small_cal", small_cal);
            form_Data.append("category_id", category_id);
            form_Data.append("price_level", price_level);
            form_Data.append("ajaxType", "add_item");

            if(name_eng == ''){
                Swal.showValidationMessage(__("enter_name_en_validation"))
            } else if(name_ar == ''){
                Swal.showValidationMessage(__("enter_name_ar_validation"))
            } else if(price_level == ''){
                Swal.showValidationMessage(__("select_price_level_validation"))
            } else if(category_id == ''){
                Swal.showValidationMessage(__("select_item_category_validation"))
            }

            if (price_level == 1 || price_level == 2 ) {
                if(big_price == ''){
                    Swal.showValidationMessage(__("enter_big_item_price_validation"))
                } else if(small_price == ''){
                    Swal.showValidationMessage(__("enter_small_item_price_validation"))
                } else if(big_cal == ''){
                    Swal.showValidationMessage(__("enter_big_calories_validation"))
                } else if(small_cal == ''){
                    Swal.showValidationMessage(__("enter_small_calories_validation"))
                }
                if(file.length == 1){
                    var filesiz = 1048576 * 5;
                    var isValidExt = validExtensions.indexOf(file[0].type) > -1;
                    var extCheck = ( isValidExt == false );
                    var sizCheck = ( file[0].size >= filesiz );
                }
                var fileCheck = ( file.length == 0 )?"0":"1";
                if(file.length == 0){
                    Swal.showValidationMessage(__("select_item_image_validation"))
                } else if(isValidExt == false){
                    Swal.showValidationMessage(__("upload_jpg_png_only_validation"))
                } else if(file[0].size >= filesiz){
                    Swal.showValidationMessage(__("upload_size_limit_5mb_validation"))
                }
            }

            return new Promise(function(reject, resolve) {
                var chechitm = (big_price == "" || small_price == "" || big_cal == "" || small_cal == "" || file.length == 0 )?false:true;
                if( name_eng == "" || name_ar == "" || price_level == '' || category_id == '' && chechitm == true || fileCheck == 0 || extCheck == true || sizCheck == true ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxItem.php',
                    type: 'POST',
                    dataType: "JSON",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_Data,
                })
                .done(function(response){
                    // console.log(response.title);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
};

$(document).on('click', '.editItemAttr', function (e) {
    e.preventDefault();
    var id              = $(this).data('id');
    var istatus         = $(this).data('istatus');
    var price_level     = $(this).data('price_level');
    var category_id     = $(this).data('category_id');
    var catname         = $(this).data('catname');
    var i_name_eng      = $(this).data('i_name_eng'         );
    var i_name_ar       = $(this).data('i_name_ar');
    var i_big_price     = $(this).data('i_big_price');
    var i_small_price   = $(this).data('i_small_price');
    var i_big_cal       = $(this).data('i_big_cal');
    var i_small_cal     = $(this).data('i_small_cal');
    var iimage          = $(this).data('iimage');
    var validExtensions = ["image/jpg", "image/jpeg", "image/png"];

    Swal.fire({
        title: __("update_item_info"),
        html: item_HTML('edit'),
        text: __("revert_warning"),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_update"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '50%',
        didOpen: function() {
            $.ajax({
                url: "./includes/ajaxFile/ajaxItem.php",
                type: 'POST',
                data: {request: 1, ajaxType: "price_level_view", price_level: price_level},
                success: function(response) {
                    $("#category_id").html(response);
                    $('#category_id option[value="'+category_id+'"]').prop("selected", "selected");
                }
            });
            if (price_level == 1 || price_level == 2) {
                $(".attachmentDIV").removeClass("noneDIV");
                $("#fileupload").attr('name', 'fileupload');
                $("#fileupload").attr('accept', validExtensions);
            }
        },
        willOpen: function() {
            $('#i_name_eng').val(i_name_eng);
            $('#i_name_ar').val(i_name_ar);
            $('#i_big_price').val(i_big_price);
            $('#i_small_price').val(i_small_price);
            $('#i_big_cal').val(i_big_cal);
            $('#i_small_cal').val(i_small_cal);
            $('#iimage').val(iimage);
            $('#itemid').val(id);
            $('input[name="itmstatus"][value="'+istatus+'"]').prop('checked', true);
            $.ajax({
                url: './includes/ajaxFile/ajaxItem.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "category_type_view"},
                success: function(response) {
                    if (response.status == 200) {
                        let options = '';
                        for (let i in response.data)
                            options += `<option value="${response.data[i].id}">${response.data[i].type}</option>`;
                            $('#price_level').append(options);
                            $('#price_level option[value="'+price_level+'"]').prop("selected", "selected");
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
            $("#price_level").bind("change", function() {
                var price_level = $("#price_level").val();
                $.ajax({
                    url: "./includes/ajaxFile/ajaxItem.php",
                    type: 'POST',
                    data: {request: 1, ajaxType: "price_level_view", price_level: price_level},
                    success: function(response) {
                        $("#category_id").html(response);
                    }
                });
            });

        },
        preConfirm: function() {
            var form_Data = new FormData();
            var file = $('#fileupload')[0].files;
            var checkFile = (file.length == 1)? (!file[0].type.match('image/jpeg') && !file[0].type.match('image/png')) :'';
            if($('#i_name_eng').val() == ""){
                Swal.showValidationMessage(__("enter_name_en_validation"))
            } else if($('#i_name_ar').val() == ''){
                Swal.showValidationMessage(__("enter_name_ar_validation"))
            } else if( checkFile == true ){
                if(!file[0].type.match('image/jpeg') && !file[0].type.match('image/png')){
                    Swal.showValidationMessage(__("not_an_image_validation"))
                }
            }
            return new Promise(function(reject, resolve) {
                if( $('#i_name_eng').val() == '' || $('#i_name_ar').val() == '' || checkFile == true ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                if ( file.length == 1 ) {
                    form_Data.append("file", file[0]);
                } else {                
                    form_Data.append("iimage", $('#iimage').val());
                }
                form_Data.append("itemid", id);
                form_Data.append("name_eng", $('#i_name_eng').val());
                form_Data.append("name_ar", $('#i_name_ar').val());
                form_Data.append("big_price", $('#i_big_price').val());
                form_Data.append("small_price", $('#i_small_price').val());
                form_Data.append("big_cal", $('#i_big_cal').val());
                form_Data.append("small_cal", $('#i_small_cal').val());
                form_Data.append("category_id", $('#category_id').val());
                form_Data.append("price_level", $('#price_level').val());
                form_Data.append("status", $('input[name="itmstatus"]:checked').val());
                form_Data.append("ajaxType", "edit_item");

                $.ajax({
                    url: './includes/ajaxFile/ajaxItem.php',
                    type: 'POST',
                    dataType: "JSON",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_Data,
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

function addCategoryFunc(){
    Swal.fire({
        title: __("add_category_info"),
        html: category_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_register"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        // customClass: 'swal-wide',
        willOpen: function() {
            $.ajax({
                url: './includes/ajaxFile/ajaxItem.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType : 'category_type_view'},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].id}">${res.data[i].type}</option>`;
                        $('#category_type').append(options);
                        //$('#dept option[value="'+dept+'"]').prop("selected", "selected");
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
        },
        preConfirm: function() {
            var name_eng        = $('.name_eng').val();
            var name_ar         = $('.name_ar').val();
            var desc_eng        = $('.desc_eng').val();
            var desc_ar         = $('.desc_ar').val();
            var category_type   = $('#category_type').val();
            if(name_eng == ""){
                Swal.showValidationMessage(__("enter_category_name_en_validation"))
            } else if(name_ar == ''){
                Swal.showValidationMessage(__("enter_category_name_ar_validation"))
            } else if(category_type == ''){
                Swal.showValidationMessage(__("select_category_type_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( name_eng == '' || name_ar == '' || category_type == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxItem.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEditCategoryForm").serialize()+'&'+$.param({ajaxType: "category_type_add"}),
                })
                .done(function(response){
                    // console.log(response.title);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
};

$(document).on('click', '.editCategoryAttr', function (e) {
    e.preventDefault();
    var status              = $(this).data('status');
    var smid                = $(this).data('id'); 
    var name_eng            = $(this).data('name_eng');
    var name_ar             = $(this).data('name_ar');
    var desc_eng            = $(this).data('desc_eng');
    var desc_ar             = $(this).data('desc_ar');
    var category_type       = $(this).data('category_type');
    Swal.fire({
        title: __("update_category_info"),
        html: category_HTML('edit'),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_update"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        // customClass: 'swal-wide',
        willOpen: function() {
            $('.smid').val(smid); 
            $('.name_eng').val(name_eng);
            $('.name_ar').val(name_ar);
            $('.desc_eng').val(desc_eng);
            $('.desc_ar').val(desc_ar);
            $('input[name="status"][value="'+status+'"]').prop('checked', true);
            $.ajax({
                url: './includes/ajaxFile/ajaxItem.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType : 'category_type_view'},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].id}">${res.data[i].type}</option>`;
                        $('#category_type').append(options);
                        $('#category_type option[value="'+category_type+'"]').prop("selected", "selected");
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
        },
        preConfirm: function() {
            if(name_eng == ""){
                Swal.showValidationMessage(__("enter_category_name_en_validation"))
            } else if(name_ar == ''){
                Swal.showValidationMessage(__("enter_category_name_ar_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( name_eng == '' || name_ar == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxItem.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEditCategoryForm").serialize()+'&'+$.param({ajaxType: "category_type_edit"}),
                })
                .done(function(response){
                    // console.log(response.title);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

////////////////////////////////////////////////////////////////////
//////////////////       End Item Handling      ////////////////////
////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////
//////////////////       Start Car Handling     ////////////////////
////////////////////////////////////////////////////////////////////

function addCarFunc(){
    Swal.fire({
        title: __("add_new_car_info"),
        html: car_HTML(),
        text: __("revert_warning"),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_register"),
        showLoaderOnConfirm: true,
        width: '50%',
        allowOutsideClick: false,
        willOpen: function() {
            $("#maker_name").select2();
            $("#maker_model").select2();
            $("#type").select2();
            $.ajax({
                url: './includes/ajaxFile/ajaxCar.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "maker_search"},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data) //(explode(" ",$name)[0])." ".(explode(" ",$name)[1])
                            options += `<option value="${res.data[i].id}">${res.data[i].maker}</option>`;
                        $('#maker_name').append(options);
                        // $('#car_user option[value="'+caruser+'"]').prop("selected", "selected");
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
            
            $("#maker_name").bind("change", function() {
                var maker_name = $("#maker_name").val();
                $.ajax({
                    url: "./includes/ajaxFile/ajaxCar.php",
                    type: 'POST',
                    data: {request: 1, maker_name: maker_name, ajaxType: "model_search"},
                    success: function(response) {
                        $("#maker_model").html(response);
                        $('#maker_model').append(`<option value="0">Others</option>`);
                    }
                });
            });

            $("#maker_model").bind("change", function() {
                var maker_name = $("#maker_name").val();
                console.log(maker_name);
                if (this.value == 0) {
                    addCarModelFunc(maker_name);
                }
            });

            Inputmask("9999-aaa", {
                placeholder: "1234-ABC",
                greedy: false,
                casing: "upper",
                jitMasking: true
            }).mask('#plate_no');

        },
        preConfirm: function() {
            var id          = $('#carid').val();
            var maker_name  = $('#maker_name').val();
            var maker_model = $('#maker_model').val();
            var made_year   = $('#made_year').val();
            var plate_no    = $('#plate_no').val();
            var type        = $('#type option:selected').val();
            var remarks     = $('#remarks').val();
            if($('#maker_name').val() == ""){
                Swal.showValidationMessage(__("enter_car_maker_validation"))
            } else if($('#maker_model').val() == ''){
                Swal.showValidationMessage(__("enter_car_model_validation"))
            } else if($('#made_year').val() == ''){
                Swal.showValidationMessage(__("enter_car_made_year_validation"))
            } else if( type.length == 0 ){
                Swal.showValidationMessage(__("select_car_type_validation"))
            } else if($('#plate_no').val() == ''){
                Swal.showValidationMessage(__("enter_car_plate_no_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( $('#maker_name').val() == '' || $('#maker_model').val() == '' || $('#made_year').val() == '' || $('#plate_no').val() == '' || type.length == 0 ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxCar.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEditUserForm").serialize()+'&'+$.param({ajaxType: "add_car"}),
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
};

$(document).on('click', '.editCarAttr', function (e) {
    e.preventDefault();
    var id          = $(this).data('id');
    var maker_name  = $(this).data('maker_name');
    var model       = $(this).data('model');
    var made_year   = $(this).data('made_year');
    var plate_no    = $(this).data('plate_no');
    var type        = $(this).data('type');
    var remarks     = $(this).data('remarks');
    var status      = $(this).data('status');
    Swal.fire({
        title: __("update_car_info"),
        html: car_HTML('edit'),
        text: __("revert_warning"),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_update"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '50%',
        // customClass: 'swal-wide',
        didOpen: function(){
            $.ajax({
                url: "./includes/ajaxFile/ajaxCar.php",
                type: 'POST',
                data: {request: 1, maker_name: maker_name, ajaxType: "model_search"},
                success: function(response) {
                    $("#maker_model").html(response);
                    $('#maker_model option[value="'+model+'"]').prop("selected", "selected");
                }
            });
        },
        willOpen: function() {
            $("#maker_name").select2();
            $("#maker_model").select2();
            $.ajax({
                url: './includes/ajaxFile/ajaxCar.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "maker_search"},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data) //(explode(" ",$name)[0])." ".(explode(" ",$name)[1])
                            options += `<option value="${res.data[i].id}">${res.data[i].maker}</option>`;
                        $('#maker_name').append(options);
                        $('#maker_name option[value="'+maker_name+'"]').prop("selected", "selected");
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });

            $("#maker_name").bind("change", function() {
                var maker_name = $("#maker_name").val();
                $.ajax({
                    url: "./includes/ajaxFile/ajaxCar.php",
                    type: 'POST',
                    data: {request: 1, maker_name: maker_name, ajaxType: "model_search"},
                    success: function(response) {
                        $("#maker_model").html(response);
                    }
                });
            });

            $('#maker_name').val(maker_name);
            $('#maker_model').val(model);
            $('#made_year').val(made_year);
            $('#plate_no').val(plate_no);
            $('#remarks').val(remarks);
            $('#status').val(status);
            $('#carid').val(id);
            $('input[name="status"][value="'+status+'"]').prop('checked', true);
            $('#type option[value="'+type+'"]').prop("selected", "selected");
            Inputmask("9999-aaa", {
                placeholder: "1234-ABC",
                greedy: false,
                casing: "upper",
                jitMasking: true
            }).mask('#plate_no');
        },
        preConfirm: function() {
            var id          = $('#carid').val();
            var maker_name  = $('#made_year').val();
            var model       = $('#maker_model').val();
            var made_year   = $('#made_year').val();
            var plate_no    = $('#plate_no').val();
            var type        = $('#type option:selected').val();
            var remarks     = $('#remarks').val();
            if($('#maker_name').val() == ""){
                Swal.showValidationMessage(__("enter_car_maker_validation"))
            } else if($('#maker_model').val() == ''){
                Swal.showValidationMessage(__("enter_car_model_validation"))
            } else if($('#made_year').val() == ''){
                Swal.showValidationMessage(__("enter_car_made_year_validation"))
            } else if($('#plate_no').val() == ''){
                Swal.showValidationMessage(__("enter_car_plate_no_validation"))
            } else if($('#type').val() == ''){
                Swal.showValidationMessage(__("select_car_type_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( $('#maker_name').val() == '' || $('#maker_model').val() == '' || $('#made_year').val() == '' || $('#plate_no').val() == '' || $('#type').val() == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxCar.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEditUserForm").serialize()+'&'+$.param({ajaxType: "edit_car"}),
                })
                .done(function(response){
                    // console.log(response.title);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.addMaintAttr', function (e) {
    e.preventDefault();
    var cid = $(this).data('id');
    var caruser = $(this).data('caruser');
    Swal.fire({
        title: __("add_maintenance_info"),
        html: maintenance_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_update"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '40%',
        willOpen: function() {
            $('input[name="cid"]').val(cid);
            $("#car_user").select2();
            // $('#addTypeAtter').attr('data-id', cid);
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                dataType: 'JSON',
                type: 'POST',
                data:{ajaxType:"emp_search"},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data) //(explode(" ",$name)[0])." ".(explode(" ",$name)[1])
                            options += `<option value="${res.data[i].emp_id}">${res.data[i].name.split(' ')[0]+' '+res.data[i].name.split(' ')[1] }</option>`;
                        $('#car_user').append(options);
                        $('#car_user option[value="'+caruser+'"]').prop("selected", "selected");
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
            $.ajax({
                url: './includes/ajaxFile/ajaxCar.php',
                dataType: 'JSON',
                type: 'POST',
                data:{ajaxType:"maint_type"},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].type}">${res.data[i].type}</option>`;
                        $('#type').append(options);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
            $.ajax({
                url: './includes/ajaxFile/ajaxCar.php',
                dataType: 'JSON',
                type: 'POST',
                data: {id:cid, ajaxType: "cars_maint"},
                success: function(res) {
                    var reading = (res.data !== "")? res.data : 0;
                    $('input[name="oldmeter"]').val(res.data);
                },
            });
            jQuery('#date').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                todayHighlight: true,
                endDate: '+0d',
            });
            Inputmask("999999999", {
                jitMasking: true,
                placeholder: "_",
                greedy: false
            }).mask('.meter');
            $("#submitMaintenanceForm").on('input', 'input.meter', function() {
                getTotalCost($(this).attr("for"));
            });
            // Using a new index rather than your global variable i
            function getTotalCost(ind) {
                var ometer = $('input[name="oldmeter"]').val();
                var meter = $('input[name="meter"]').val();
                var oldmeter = (ometer !== "")?ometer:meter;
                $('#diffmeter').val( meter - oldmeter + 'KM' );
                // calculateSubTotal();
            }
        },
        preConfirm: function() {
            var car_user        = $('#car_user').val();
            var date            = $('input[name="date"]').val();
            var meter           = $('input[name="meter"]').val();
            var type            = $('#type').val();
            var details         = $('input[name="details"]').val();
            var remarks         = $('input[name="remarks"]').val();
            if(car_user == ""){
                Swal.showValidationMessage(__("select_car_driver_validation"))
            } else if(date == ''){
                Swal.showValidationMessage(__("select_maintenance_date_validation"))
            } else if(meter == ''){
                Swal.showValidationMessage(__("enter_meter_reading_validation"))
            } else if(type == ''){
                Swal.showValidationMessage(__("select_maintenance_type_validation"))
            } else if(details == ''){
                Swal.showValidationMessage(__("enter_maintenance_details_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( car_user == '' || date == '' || meter == '' || type == '' || details == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxCar.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitMaintenanceForm").serialize()+'&'+$.param({ajaxType: "cars_maint_add"}),
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.addDrvrAtter', function (e) {
    e.preventDefault();
    var cid = $(this).data('id');
    Swal.fire({
        title: __("add_driver_info"),
        html: driver_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_register"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: "30%",
        willOpen: function(){
            $("#car_user").select2();
            $('input[name="cid"]').val(cid);
            jQuery('#date').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                todayHighlight: true,
                endDate: '+0d',
            });
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                dataType: 'JSON',
                type: 'POST',
                data:{ajaxType:"emp_search"},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data) //(explode(" ",$name)[0])." ".(explode(" ",$name)[1])
                            options += `<option value="${res.data[i].emp_id}">${res.data[i].name.split(' ')[0]+' '+res.data[i].name.split(' ')[1] }</option>`;
                        $('#car_user').append(options);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
        },
        preConfirm: function() {
            var caruser = $('#car_user option:selected').val();
            var date = $('input[name="date"]').val();
            if(caruser == ""){
                Swal.showValidationMessage(__("select_car_driver_validation"))
            } else if(date == ""){
                Swal.showValidationMessage(__("select_issue_date_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( caruser == '' || date == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxCar.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitDriverForm").serialize()+'&'+$.param({ajaxType: "driver_add"}),
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.addRtrnDrvrAtter', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    var cid = $(this).data('cid');
    Swal.fire({
        title: __("are_you_sure"),
        text: __("want_to_return_car"),
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __("cancel"),
        confirmButtonText: __("yes_do_it"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    url: './includes/ajaxFile/ajaxCar.php',
                    type: 'POST',
                    data: {pid:id,pcid:cid, ajaxType: "drvr_rtrn_update"},
                    dataType: "JSON",
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.addTypeAtter', function (e) {
    e.preventDefault();
    Swal.fire({
        title: __("add_type"),
        html: addType_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_register"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: function() {
            var type    = $('input[name="type"]').val();
            if(type == ""){
                Swal.showValidationMessage(__("enter_type_name_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( type == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxCar.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEditCategoryForm").serialize()+'&'+$.param({ajaxType: "maint_type_add"}),
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.addDocuAtter', function (e) {
    e.preventDefault();
    var cid = $(this).data('id');
    var validExtensions = ["image/jpg", "image/jpeg", "image/png", "application/pdf"];
    Swal.fire({
        title: __("add_documents_info"),
        html: documents_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_register"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: "30%",
        willOpen: function(){
            $('input[name="cid"]').val(cid);
            jQuery('#date_select').datepicker({
                format: "yyyy-mm-dd",
                toggleActive: true,
                todayHighlight: true,
            });
            $(document).on('click', '.showAttachment', function (e) {
                $(".attachmentDIV").removeClass("noneDIV");
                $("#checkatt").attr('name', 'file');
                $("#checkatt").attr('accept', validExtensions);
            })
            $(document).on('click', '.hideAttachment', function (e) {
                $(".attachmentDIV").addClass("noneDIV");
                $("#checkatt").attr('name', '');
            })
        },
        preConfirm: function() {
            var form_Data = new FormData();
            var doc_type = $('#doc_type option:selected').val();
            var issue_date = $('input[name="issue_date"]').val();
            var exp_date = $('input[name="exp_date"]').val();
            var attach = $('input[name=attach]:checked').is(':checked');
            var file = $('#checkatt')[0].files;
            form_Data.append("file", file[0]);
            form_Data.append("cid", cid);
            form_Data.append("doc_type", doc_type);
            form_Data.append("issue_date", issue_date);
            form_Data.append("exp_date", exp_date);
            form_Data.append("ajaxType", "document_add");
            if(doc_type == ""){
                Swal.showValidationMessage(__("select_documents_type_validation"))
            } else if(issue_date == ""){
                Swal.showValidationMessage(__("select_issue_date_validation"))
            } else if(attach == false){
                Swal.showValidationMessage(__("select_attachment_selection_validation"))
            }

            if ($('input[name=attach]:checked').val() == 'yes') {
                if(file.length == 1){
                    var filesiz = 1048576 * 5;
                    var isValidExt = validExtensions.indexOf(file[0].type) > -1;
                    var extCheck = ( isValidExt == false );
                    var sizCheck = ( file[0].size >= filesiz );
                } //ajaxCarModelAdd
                var fileCheck = ( file.length == 0 )?"0":"1";
                if(file.length == 0){
                    Swal.showValidationMessage(__("select_attachment_file_validation"))
                } else if(isValidExt == false){
                    Swal.showValidationMessage(__("upload_pdf_jpg_only_validation"))
                } else if(file[0].size >= filesiz){
                    Swal.showValidationMessage(__("upload_size_limit_5mb_validation"))
                }
            }

            return new Promise(function(reject, resolve) {
                if( doc_type == '' || issue_date == '' || attach == '' || fileCheck == "0" || extCheck == true || sizCheck == true ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxCar.php',
                    type: 'POST',
                    dataType: "JSON",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_Data,
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

function addCarModelFunc(id){
    Swal.fire({
        title: __('add_car_model'),
        html: addCarModel_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __("yes_register"),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        willOpen: function(){
            $('input[name="maker_name"]').val(id);
        },
        preConfirm: function() {
            var maker_model    = $('input[name="maker_model"]').val();
            if(maker_model == ""){
                Swal.showValidationMessage(__("enter_car_model_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( maker_model == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxCar.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEditCategoryForm").serialize()+'&'+$.param({ajaxType: "model_add"}),
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
};

////////////////////////////////////////////////////////////////////
//////////////////       End Car Handling       ////////////////////
////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////
////////////       Start Smart Request Handling       //////////////
////////////////////////////////////////////////////////////////////

$(document).on('click', '.editItemLineAttr', function (e) {
    e.preventDefault();
    var id          = $(this).data('id');
    var i_item_name  = $(this).data('i_item_name');
    var i_quantity       = $(this).data('i_quantity');
    var i_product_price   = $(this).data('i_product_price');
    var i_vat_rate    = $(this).data('i_vat_rate');
    var i_idiscount        = $(this).data('i_idiscount');
    var i_itmvalue     = $(this).data('i_itmvalue');
    var i_vat_val     = $(this).data('i_vat_val');
    var i_amount      = $(this).data('i_amount');
    var i_total_cost      = $(this).data('i_total_cost');
    var i_location      = $(this).data('i_location');
    Swal.fire({
        title: __('update_line_info'),
        html: request_line_HTML(),
        text: __("revert_warning"),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '50%',
        // customClass: 'swal-wide',
        willOpen: function() {
            $('#itemid').val(id);
            $('.item_name').val(i_item_name);
            $('.quantity').val(i_quantity);
            $('.product_price').val(i_product_price);
            $('.vat_rate').val(i_vat_rate);
            $('.idiscount').val(i_idiscount);
            $('.itmvalue').val(i_itmvalue);
            $('.vat_val').val(i_vat_val);
            $('.amount').val(i_amount);
            $('.total_cost').val(i_total_cost);
            $.ajax({
                url: './includes/ajaxFile/ajaxLocation.php',
                dataType: 'JSON',
                type: 'POST',
                data:{ajaxType: "section_view"},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].section_name}">${res.data[i].section_name}</option>`;
                            $('#location').append(options);
                            $('#location option[value="'+i_location+'"]').prop("selected", "selected");
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
            // Handle input changes for calculation
            $("#submitEditLineForm").on('input', 'input.quantity, input.product_price, input.vat_rate, input.idiscount', function() {
                calculateTotals();
            });
            // Handle VAT option change
            $("#submitEditLineForm").on('change', 'select.vat_option', function() {
                calculateTotals();
            });
            function calculateTotals() {
                // Get all input values
                var qty = parseFloat($('#quantity').val()) || 0;
                var price = parseFloat($('#product_price').val()) || 0;
                var vatRate = parseFloat($('#vat_rate').val()) || 0;
                var discount = parseFloat($('#idiscount').val()) || 0;
                var vatOption = $('.vat_option').val(); 
                // Calculate item value (quantity * price)
                var itemValue = qty * price;
                $('#itmvalue').val(itemValue.toFixed(2));
                var vatValue, amount;
                if (vatOption === 'exclude') {
                    // VAT is excluded - add VAT on top of net price
                    vatValue = itemValue * (vatRate / 100);
                    amount = itemValue + vatValue;
                } else {
                    // VAT is included - calculate VAT amount included in the price
                    vatValue = itemValue - (itemValue / (1 + (vatRate / 100)));
                    amount = itemValue; // Total already includes VAT
                }
                // Calculate final total after discount
                var total = amount - discount;
                // Update the form fields
                $('#vat_val').val(vatValue.toFixed(2));
                $('#amount').val(amount.toFixed(2));
                $('#total_cost').val(total.toFixed(2));
            }
            // Initialize calculation on page load
            calculateTotals();
        },
        preConfirm: function() {
            if($('.item_name').val() == ""){
                Swal.showValidationMessage(__("enter_item_name_validation"))
            } else if($('#location').val() == ''){
                Swal.showValidationMessage(__("select_location_validation"))
            } else if($('.quantity').val() == ''){
                Swal.showValidationMessage(__("enter_item_quantity_validation"))
            } else if($('.product_price').val() == ''){
                Swal.showValidationMessage(__("enter_product_price_validation"))
            } else if($('.vat_rate').val() == ''){
                Swal.showValidationMessage(__("enter_vat_rate_validation"))
            }
            return new Promise(function(reject, resolve) {
                if($('.item_name').val()==''||$('#location').val()==''||$('.quantity').val()==''||$('.product_price').val()==''||$('.vat_rate').val()==''){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxSmartRequest.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEditLineForm").serialize()+'&'+$.param({ajaxType: "request_line_update"}),
                })
                .done(function(response){
                    // console.log(response.title);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.editReqAttr', function (e) {
    e.preventDefault();
    var id          = $(this).data('id');
    var sub_type  = $(this).data('sub_type');
    var sub_title       = $(this).data('sub_title');
    var tally_id   = $(this).data('tally_id');
    var injazat_id    = $(this).data('injazat_id');
    var remarks        = $(this).data('remarks');
    Swal.fire({
        title: __('update_request_info'),
        html: request_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        // customClass: 'swal-wide',
        willOpen: function() {
            $('#reqid').val(id);
            $('.sub_title').val(sub_title);
            $('.tally_id').val(tally_id);
            $('.injazat_id').val(injazat_id);
            $('.remarks').val(remarks);
            $.ajax({
                url: './includes/ajaxFile/ajaxSmartRequest.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "sub_type"},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].sub_type}">${res.data[i].sub_type}</option>`;
                        $('#sub_type').append(options);
                        $('#sub_type option[value="'+sub_type+'"]').prop("selected", "selected");
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
        },
        preConfirm: function() {
            if($('.sub_title').val() == ""){
                Swal.showValidationMessage(__("enter_request_subtitle_validation"))
            } else if($('#sub_type').val() == ''){
                Swal.showValidationMessage(__("select_request_type_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( $('.sub_title').val() == '' || $('#sub_type').val() == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxSmartRequest.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEditRequestForm").serialize()+'&'+$.param({ajaxType: "request_update"}),
                })
                .done(function(response){
                    // console.log(response.title);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.smt_attachment', function (e) {
    e.preventDefault();
    var inv_no = $(this).data('inv_no');
    Swal.fire({
        title: __('dropzone_file_upload'),
        html: '<form action="#" class="attform">'+
                '<div class="fallback">'+
                    '<input name="file" type="file" multiple />'+
                '</div>'+
            '</form>',
        icon: 'info',
        showCancelButton: true,
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonColor: '#3085d6',
        confirmButtonText: __('yes_upload_it'),
        showLoaderOnConfirm: true,
        customClass: 'swal-wide',
        willOpen : () => {
            $('form.attform').attr('id','dropzone').addClass('dropzone');
            const myDropzone = new Dropzone('#dropzone', {
                url: "./includes/ajaxFile/ajaxSmartRequest.php",
                paramName: "file",
                maxFilesize: 8,
                maxFiles: 10,
                acceptedFiles: "image/png,image/jpeg,image/jpg,image/bmp,application/pdf",
                parallelUploads: 10,
                autoProcessQueue: false,
                // autoProcessQueue: true,
                init: function() {
                    this.on('success', function(){
                        if (myDropzone.getQueuedFiles().length == 0 && myDropzone.getQueuedFiles().length == 0) {
                            Swal.fire({
                                title: __("uploaded"),
                                text: __("files_uploaded_successfully"),
                                icon:'success',allowOutsideClick:false
                            }).then(function(isConfirm){(isConfirm)?location.reload():""});
                        }
                    });
                }
            })
            myDropzone.on('sending', function(file, xhr, formData){
                formData.append('id', inv_no);
                formData.append('ajaxType', 'smt_attachments');
            })
        },
        preConfirm: function() {
            return new Promise(function(resolve) {
                var myDropzone = Dropzone.forElement("#dropzone");
                myDropzone.processQueue();
            });
        },
        allowOutsideClick: false
    })
});

$(document).on('click', '.deleteSmt', function (e) {
    e.preventDefault();
    var itemId = $(this).data('id');
    Swal.fire({
        title: __('are_you_sure'),
        text: __("revert_warning"),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_delete_it'),
        showLoaderOnConfirm: true,
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    url: './includes/ajaxFile/smt_delete.php',
                    type: 'POST',
                    data: {id:itemId},
                    cache: false,
                    dataType: "json",
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },

        allowOutsideClick: false
    })
});

////////////////////////////////////////////////////////////////////
////////////        End Smart Request Handling        //////////////
////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////
////////////          Start Location Handling         //////////////
////////////////////////////////////////////////////////////////////

$(document).on('click', '.upload_img', function (e) {
    e.preventDefault();
    var id              = $(this).data('id');
    var img             = $(this).data('img');
    var section         = $(this).data('section');
    var postion         = $(this).data('postion');
    Swal.fire({
        title: __('update_shop_image'),
        html: `
        <div class="row customSweetAlertMLR" >
            <div class="col-md-6 text-center">
                <div id="upload_loc_img" style="width:350px"></div>
            </div>
            <div class="col-md-6" style="text-align: right !important">
              <div >
                  <img src="${img}" style="width:300px;padding:30px;height:300px;margin-top:30px" />
              </div>
            </div>
            <div class="col-md-6" style="padding-top:30px;">
                <strong>${__('select_image')}</strong>
                <div class="input_container">
                    <input type="file" id="upload_inside_img" accept="image/*">   
                </div>
            </div>
        </div>`,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '50%',
        willOpen: function() {
            $uploadCrop_in_img = $('#upload_loc_img').croppie({
                url: img,
                enableExif: true,
                viewport: {
                    width: 400,
                    height: 400,
                    type: 'square', /*type: 'circle',*/
                },
                boundary: {
                    width: 500,
                    height: 500,
                }
            });
            var fileTypes = ['jpg', 'jpeg', 'png', 'webp'];
            $('#upload_inside_img').on('change', function () {
                var file = this.files[0]; // Get your file here
                var fileExt = file.type.split('/')[1]; // Get the file extension
                if (fileTypes.indexOf(fileExt) !== -1) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $uploadCrop_in_img.croppie('bind', {
                            url: e.target.result
                        }).then(function(){
                            console.log('jQuery bind complete');
                        });
                    }
                    reader.readAsDataURL(this.files[0]);
                } else {
                    Swal.fire({
                        title:__('oops'),text:__('file_not_supported'),icon:'error',allowOutsideClick:false
                    });
                    return false;
                }
            });
        },
        preConfirm: function() {
            return new Promise(function(resolve) {
                $uploadCrop_in_img.croppie('result', {
                    type: 'canvas',
                    size: 'viewport'
                }).then(function (resp) {
                    // console.log(resp);
                    $.ajax({
                        url: "./includes/ajaxFile/ajaxLocation.php",
                        type: "POST",
                        dataType: "JSON",
                        data: { "image": resp, "id": id, "section_name": section, "postion": postion, ajaxType: "upload_image" },
                        success: function (response) {
                            Swal.fire({
                                title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
                            }).then(function(isConfirm){(isConfirm)?location.reload():""});
                        }
                    });
                });
            });
        },
    })
});

function addlocarionFunc(){
    Swal.fire({
        title: __('add_new_location'),
        html: location_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '50%',
        willOpen: function() {
            $.ajax({
                url: './includes/ajaxFile/ajaxLocation.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: 'loc_department'},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].dep_nme}">${res.data[i].dep_nme}</option>`;
                        $('#dept').append(options);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
            $("#b_license_exp_hijri").hijriDatePicker({
                locale: "ar-sa",
                hijri:true,
                showSwitcher:false,
                hijriFormat:"iYYYY-iMM-iDD",
                hijriDayViewHeaderFormat: "iMMMM iYYYY",
                showTodayButton: true,
            });
        },
        preConfirm: function() {
            var section         = $('input[name="section_name"]').val();
            var latitude        = $('input[name="latitude"]').val();
            var longitude       = $('input[name="longitude"]').val();
            var dept            = $('#dept option:selected').val();
            var bulding_size    = $('input[name="t_bulding_size"]').val();
            var location_dist   = $('input[name="location_dist"]').val();
            var b_license_no    = $('input[name="b_license_no"]').val();
            var b_license_exp   = $('input[name="b_license_exp"]').val();
            if(section == ""){
                Swal.showValidationMessage(__("enter_section_name_validation"))
            } else if(latitude == ''){
                Swal.showValidationMessage(__("enter_latitude_validation"))
            } else if(longitude == ''){
                Swal.showValidationMessage(__("enter_longitude_validation"))
            } else if(dept == ''){
                Swal.showValidationMessage(__("select_department_validation"))
            } else if(bulding_size == ''){
                Swal.showValidationMessage(__("enter_building_size_validation"))
            } else if(location_dist == ''){
                Swal.showValidationMessage(__("enter_location_district_validation"))
            } else if(b_license_no == ''){
                Swal.showValidationMessage(__("enter_baladya_license_no_validation"))
            } else if(b_license_exp == ''){
                Swal.showValidationMessage(__("select_balady_license_expiry_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( section == '' || latitude == '' || longitude == '' || dept == '' || bulding_size == '' || location_dist == '' || b_license_no == '' || b_license_exp == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxLocation.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitlocationForm").serialize()+'&'+$.param({ajaxType: "add_location"}),
                })
                .done(function(response){
                    // console.log(response.title);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
};

$(document).on('click', '.editLocationAttr', function (e) {
    e.preventDefault();
    var status          = $(this).data('status');
    var smid            = $(this).data('id');
    var section         = $(this).data("section_name");
    var dept            = $(this).data("dept");
    var camera_in        = $(this).data("camera_in");
    var camera_out        = $(this).data("camera_out");
    var b_license_exp   = $(this).data("b_license_exp");
    var b_license_no    = $(this).data("b_license_no");
    var location_dist   = $(this).data("location_dist");
    var bulding_base    = $(this).data("bulding_base");
    var bulding_size    = $(this).data("bulding_size");
    var t_bulding_size    = $(this).data("t_bulding_size");
    var latitude        = $(this).data("latitude");
    var longitude       = $(this).data("longitude");
    var location_name       = $(this).data("location_name");
    var municipality       = $(this).data("municipality");
    var sub_municipality       = $(this).data("sub_municipality");
    Swal.fire({
        title: __('update_location_info'),
        html: location_HTML('edit'),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '80%',
        willOpen: function() {
            $('input[name="smid"]').val(smid);
            $('input[name="section_name"]').val(section);
            $('input[name="dept"]').val(dept);
            $('input[name="camera_in"]').val(camera_in);
            $('input[name="camera_out"]').val(camera_out);
            $('input[name="b_license_exp"]').val(b_license_exp);
            $('input[name="b_license_no"]').val(b_license_no);
            $('input[name="location_dist"]').val(location_dist);
            $('input[name="bulding_base"]').val(bulding_base);
            $('input[name="bulding_size"]').val(bulding_size);
            $('input[name="t_bulding_size"]').val(t_bulding_size);
            $('input[name="latitude"]').val(latitude);
            $('input[name="longitude"]').val(longitude);
            $('input[name="loc_address"]').val(location_name);
            $('input[name="municipality"]').val(municipality);
            $('input[name="sub_municipality"]').val(sub_municipality);
            $('input[name="status"][value="'+status+'"]').prop('checked', true);
            $.ajax({
                url: './includes/ajaxFile/ajaxLocation.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: 'loc_department'},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].dep_nme}">${res.data[i].dep_nme}</option>`;
                        $('#dept').append(options);
                        $('#dept option[value="'+dept+'"]').prop("selected", "selected");
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
            $("#b_license_exp_hijri").hijriDatePicker({
                locale: "ar-sa",
                hijri:true,
                showSwitcher:false,
                hijriFormat:"iYYYY-iMM-iDD",
                hijriDayViewHeaderFormat: "iMMMM iYYYY",
                showTodayButton: true,
            });
        },
        preConfirm: function() {
            var section         = $('input[name="section_name"]').val();
            var latitude        = $('input[name="latitude"]').val();
            var longitude       = $('input[name="longitude"]').val();
            var dept            = $('#dept option:selected').val();
            var location_dist   = $('input[name="location_dist"]').val();
            var b_license_no    = $('input[name="b_license_no"]').val();
            var b_license_exp   = $('input[name="b_license_exp"]').val();
            if(section == ""){
                Swal.showValidationMessage(__("enter_section_name_validation"))
            } else if(latitude == ''){
                Swal.showValidationMessage(__("enter_latitude_validation"))
            } else if(longitude == ''){
                Swal.showValidationMessage(__("enter_longitude_validation"))
            } else if(dept == ''){
                Swal.showValidationMessage(__("select_department_validation"))
            } else if(location_dist == ''){
                Swal.showValidationMessage(__("enter_location_district_validation"))
            } else if(b_license_no == ''){
                Swal.showValidationMessage(__("enter_baladya_license_no_validation"))
            } else if(b_license_exp == ''){
                Swal.showValidationMessage(__("select_balady_license_expiry_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( section == '' || latitude == '' || longitude == '' || dept == '' || location_dist == '' || b_license_no == '' || b_license_exp == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxLocation.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitlocationForm").serialize()+'&'+$.param({ajaxType: "edit_location"}),
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.addLocContractAttr', function (e) {
    e.preventDefault();
    var locid = $(this).data('id');
    Swal.fire({
        title: __('add_location_contract_info'),
        html: loc_contract_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '60%',
        willOpen: function() {
            $('input[name="locid"]').val(locid);
            $('.autonumber').autoNumeric('init');
            jQuery('#start_cont_date').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                todayHighlight: true,
                // endDate: '+0d',
            });
            jQuery('#end_cont_date').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                todayHighlight: true,
                // endDate: '+0d',
            });
            $("#b_license_exp_hijri").hijriDatePicker({
                locale: "ar-sa",
                hijri:true,
                showSwitcher:false,
                hijriFormat:"iYYYY-iMM-iDD",
                hijriDayViewHeaderFormat: "iMMMM iYYYY",
                showTodayButton: true,
            });
        },
        preConfirm: function() {
            var owner_name      = $('input[name="owner_name"]').val();
            var owner_number    = $('input[name="owner_number"]').val();
            var owner_email     = $('input[name="owner_email"]').val();
            var contract_no     = $('input[name="contract_no"]').val();
            var start_cont_date = $('input[name="start_cont_date"]').val();
            var end_cont_date   = $('input[name="end_cont_date"]').val();
            var rent            = $('input[name="rent"]').val();
            var incuranse_prc   = $('input[name="incuranse_prc"]').val();
            if(owner_name == ""){
                Swal.showValidationMessage(__("enter_owner_name_validation"))
            } else if(owner_number == ''){
                Swal.showValidationMessage(__("enter_owner_contact_validation"))
            } else if(owner_email == ''){
                Swal.showValidationMessage(__("enter_owner_email_validation"))
            } else if(contract_no == ''){
                Swal.showValidationMessage(__("enter_contract_number_validation"))
            } else if(start_cont_date == ''){
                Swal.showValidationMessage(__("select_start_contract_date_validation"))
            } else if(end_cont_date == ''){
                Swal.showValidationMessage(__("select_end_contract_date_validation"))
            } else if(rent == ''){
                Swal.showValidationMessage(__("enter_rent_amount_validation"))
            } else if(incuranse_prc == ''){
                Swal.showValidationMessage(__("enter_insurance_amount_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( owner_name == '' || owner_number == '' || owner_email == '' || contract_no == '' || start_cont_date == '' || end_cont_date == '' || rent == '' || incuranse_prc == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxLocation.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitlocationContractForm").serialize()+'&'+$.param({ajaxType: "add_contract"}),
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.upldLocDocuAttr', function (e) {
    e.preventDefault();
    var lid = $(this).data('id');
    Swal.fire({
        title: __('dropzone_file_upload'),
        html: ` <form action="#" class="attform">
                    <div class="fallback">
                        <input name="file" type="file" multiple />
                    </div>
                </form>`,
        icon: 'info',
        showCancelButton: true,
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonColor: '#3085d6',
        confirmButtonText: __('yes_upload_it'),
        showLoaderOnConfirm: true,
        width:"40%",
        willOpen : () => {
            Dropzone.autoDiscover = false;
            $('form.attform').attr('id','dropzone').addClass('dropzone');
            const myDropzone = new Dropzone('#dropzone', {
                url: "./includes/ajaxFile/ajaxLocation.php",
                paramName: "file",
                maxFilesize: 8,
                maxFiles: 10,
                acceptedFiles: "image/png,image/jpeg,image/jpg,image/bmp,application/pdf",
                parallelUploads: 10,
                autoProcessQueue: false,
                // autoProcessQueue: true,
                init: function() {
                    this.on('success', function(){
                        if (myDropzone.getQueuedFiles().length == 0 && myDropzone.getQueuedFiles().length == 0) {
                            Swal.fire({
                                title: __("uploaded"),
                                text: __("files_uploaded_successfully"),
                                icon:'success',allowOutsideClick:false
                            }).then(function(isConfirm){(isConfirm)?location.reload():""});
                        }
                    });
                }
            })
            myDropzone.on('sending', function(file, xhr, formData){
                formData.append('location_id', lid);
                formData.append('ajaxType', "upload_document");
            })
        },
        preConfirm: function() {
            return new Promise(function(resolve) {
                var myDropzone = Dropzone.forElement("#dropzone");
                myDropzone.processQueue();
            });
        },
        allowOutsideClick: false
    })
});

////////////////////////////////////////////////////////////////////
////////////           End Location Handling          //////////////
////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////
////////////          Start Customer Handling         //////////////
////////////////////////////////////////////////////////////////////

function addCustomerAtter(){
// $(document).on('click', '.addCustomerAtter', function (e) {
    // e.preventDefault();
    Swal.fire({
        title: __('add_new_customer'),
        html: customer_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '50%',
        willOpen: function(){
            $('#injazat_no').autoNumeric('init');
            $("#location").select2();
            jQuery('#card_exp').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                todayHighlight: true,
                startDate: '+2y',
            });
            $.ajax({
                url: './includes/ajaxFile/ajaxLocation.php',
                dataType: 'JSON',
                type: 'POST',
                data:{ajaxType: "section"},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].section_name}">${res.data[i].section_name}</option>`;
                        $('#location').append(options);
                        // $('#location option[value="'+i_location+'"]').prop("selected", "selected");
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
        },
        preConfirm: function() {
            var full_name = $('input[name="full_name"]').val();
            var injazat_no = $('input[name="injazat_no"]').val();
            var mobile = $('input[name="mobile"]').val();
            var acc_no = $('input[name="acc_no"]').val();
            var card_exp = $('input[name="card_exp"]').val();
            var location = $('#location option:selected').val();
            if($('input[name="full_name"]').val() == ""){
                Swal.showValidationMessage(__("enter_customer_full_name_validation"))
            } else if(injazat_no == ""){
                Swal.showValidationMessage(__("enter_customer_injazat_no_validation"))
            } else if(mobile == ""){
                Swal.showValidationMessage(__("enter_mobile_number_validation"))
            } else if(acc_no == ""){
                Swal.showValidationMessage(__("enter_account_number_validation"))
            } else if(card_exp == ""){
                Swal.showValidationMessage(__("select_expiry_date_validation"))
            } else if(location == ""){
                Swal.showValidationMessage(__("select_location_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( injazat_no == '' || injazat_no == '' || mobile == '' || acc_no == '' || card_exp == '' || location == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxCustomer.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitCustomerForm").serialize()+'&'+$.param({ajaxType: "add_customer"}),
                })
                .done(function(response){
                    // console.log(response.title);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)? $('#customers_tbl').DataTable().ajax.reload() :""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
};
//});

$(document).on('click', '.editCustomerAtter', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    var full_name = $(this).data('full_name');
    var mobile = $(this).data('mobile');
    var injazat_no = $(this).data('injazat_no');
    var acc_no = $(this).data('acc_no');
    var card_exp = $(this).data('card_exp');
    var location = $(this).data('location');
    Swal.fire({
        title: __('update_customer_info'),
        html: customer_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '50%',
        willOpen: function(){
            $('input[name="id"]').val(id);
            $("#full_name").val(full_name);
            $("#mobile").val(mobile);
            $("#injazat_no").val(injazat_no);
            $("#acc_no").val(acc_no);
            $("#card_exp").val(card_exp);
            $("#location").val(location);
            $('#injazat_no').autoNumeric('init');
            $("#location").select2();
            jQuery('#card_exp').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                todayHighlight: true,
                startDate: '+2y',
            });
            $.ajax({
                url: './includes/ajaxFile/ajaxLocation.php',
                dataType: 'JSON',
                type: 'POST',
                data:{ajaxType: "section"},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].section_name}">${res.data[i].section_name}</option>`;
                        $('#location').append(options);
                        $('#location option[value="'+location+'"]').prop("selected", "selected");
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
        },
        preConfirm: function() {
            var full_name = $('input[name="full_name"]').val();
            var injazat_no = $('input[name="injazat_no"]').val();
            var mobile = $('input[name="mobile"]').val();
            var acc_no = $('input[name="acc_no"]').val();
            var card_exp = $('input[name="card_exp"]').val();
            var location = $('#location option:selected').val();
            if(full_name == ""){
                Swal.showValidationMessage(__("enter_customer_full_name_validation"))
            } else if(injazat_no == ""){
                Swal.showValidationMessage(__("enter_customer_injazat_no_validation"))
            } else if(mobile == ""){
                Swal.showValidationMessage(__("enter_mobile_number_validation"))
            } else if(acc_no == ""){
                Swal.showValidationMessage(__("enter_account_number_validation"))
            } else if(card_exp == ""){
                Swal.showValidationMessage(__("select_expiry_date_validation"))
            } else if(location == ""){
                Swal.showValidationMessage(__("select_location_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( injazat_no == '' || injazat_no == '' || mobile == '' || acc_no == '' || card_exp == '' || location == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxCustomer.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitCustomerForm").serialize()+'&'+$.param({ajaxType: "edit_customer"}),
                })
                .done(function(response){
                    // console.log(response.title);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)? $('#customers_tbl').DataTable().ajax.reload() :""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.cardUpdateAttr', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    var injazat_no = $(this).data('injazat_no');
    Swal.fire({
        title: __('update_vip_customer_card'),
        html: cust_upd_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        willOpen: function(){
            $('input[name="id"]').val(id);
            $('input[name="injazat_no"]').val(injazat_no);
            $("#location").select2();
            jQuery('#card_exp').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                todayHighlight: true,
                startDate: '+2y',
            });
            $.ajax({
                url: './includes/ajaxFile/ajaxLocation.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "section"},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].section_name}">${res.data[i].section_name}</option>`;
                        $('#location').append(options);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
        },
        preConfirm: function() {
            var card_exp = $('input[name="card_exp"]').val();
            var location = $('#location option:selected').val();
            if(card_exp == ""){
                Swal.showValidationMessage(__("select_expiry_date_validation"))
            } else if(location == ""){
                Swal.showValidationMessage(__("select_location_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( card_exp == '' || location == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxCustomer.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitCustomerCardUpdForm").serialize()+'&'+$.param({ajaxType: "card_update"}),
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)? window.location.href = './view_customer.php?id='+id :""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.cardAddAttr', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    var acc_no = $(this).data('acc_no');
    Swal.fire({
        title: __('add_vip_customer_card'),
        html: cust_add_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        willOpen: function(){
            $('input[name="id"]').val(id);
            $('input[name="acc_no"]').val(acc_no);
            $("#location").select2();
            $('input[name="injazat_no"]').autoNumeric('init');
            jQuery('#card_exp').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                todayHighlight: true,
                startDate: '+2y',
            });
            $.ajax({
                url: './includes/ajaxFile/ajaxLocation.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "section"},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].section_name}">${res.data[i].section_name}</option>`;
                        $('#location').append(options);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
        },
        preConfirm: function() {
            var injazat_no = $('input[name="injazat_no"]').val();
            var card_exp = $('input[name="card_exp"]').val();
            var location = $('#location option:selected').val();
            if(injazat_no == ""){
                Swal.showValidationMessage(__("enter_new_injazat_no_validation"))
            } else if(card_exp == ""){
                Swal.showValidationMessage(__("select_expiry_date_validation"))
            } else if(location == ""){
                Swal.showValidationMessage(__("select_location_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( card_exp == '' || location == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxCustomer.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitCustomerCardAddForm").serialize()+'&'+$.param({ajaxType: "add_card"}),
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)? window.location.href = './view_customer.php?id='+id :""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

////////////////////////////////////////////////////////////////////
////////////           End Customer Handling          //////////////
////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////
////////////             End Users Handling           //////////////
////////////////////////////////////////////////////////////////////

$(document).on('click', '.updateUserAjax', function (e) {
    e.preventDefault();
    var e_iduser        = $(this).data('id'); 
    var e_idpasusr      = $(this).data('id'); 
    var e_fullname      = $(this).data('fullname');
    var e_username      = $(this).data('username');
    var e_dept          = $(this).data('dept');
    var e_email         = $(this).data('email');
    var e_email_pass    = $(this).data('email_pass');
    var e_mobile        = $(this).data('mobile');
    var status          = $(this).data('status');
    var user_type       = $(this).data('user_type');
    var oldpass         = $(this).data('oldpass');
    Swal.fire({
        title: __('update_user_info'),
        html: edit_user_HTML(),
        text: __("revert_warning"),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        customClass: 'swal-wide',
        willOpen: function() {
            $('#iduser')    .val(e_iduser); 
            $('#idpass')    .attr('data-id', e_iduser);
            $('#idpasusr')  .val(e_idpasusr); 
            $('#fullname')  .val(e_fullname);
            $('#username')  .val(e_username);
            $('#dept')      .val(e_dept);
            $('#email')     .val(e_email);
            $('#email_pass').val(e_email_pass);
            $('#mobile')    .val(e_mobile);
            $('#oldpass')   .val(oldpass);
            $('input[name="status"][value="'+status+'"]').prop('checked', true);
            $('#user_type option[value="'+user_type+'"]').prop("selected", "selected");
            $('.updatePasswordAjax').attr('data-oldpass', oldpass);
        },
        preConfirm: function() {
            return new Promise(function(reject) {
                if($('#fullname').val() == "" || $('#username').val() == "" )
                {reject(__("fill_mandatory_fields"));}
                $.ajax({
                        url: './includes/ajaxFile/ajaxUser.php',
                        type: 'POST',
                        data: $('#submitEditUserForm').serialize()+'&'+$.param({ajaxType: "user_upate"}),
                        cache: false,
                        dataType: "json",
                })
                .done(function(response){
                    /*Swal.fire({
                        title : response.title,
                        text : response.message,
                        toast : true,
                        position : 'top-right',
                        timer : 3000,
                        showCancelButton : false,
                        showConfirmButton : false,
                        icon : response.type,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});*/
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
        allowOutsideClick: false
    })
});

$(document).on('click', '.updatePasswordAjax', function (e) {
    e.preventDefault();
    var iduser        = $(this).data('id');
    var oldpass       = $(this).data('oldpass');
    Swal.fire({
        title: __('update_password_for_user'),
        html: edit_password_HTML(),
        text: __("revert_warning"),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        footer: `<a href="javascript:void(0);" class="showPasswordAjax" data-oldpass="${oldpass}" >${__('show_password')}</a>`,
        preConfirm: function() {
            var id = $('input[name=id]').val();
            var password = $('input[name=password]').val();
            var password_confirm = $('input[name=password_confirm]').val();
            if(password == ""){
                Swal.showValidationMessage(__("enter_new_password_validation"))
            } else if (password_confirm == "") {
                Swal.showValidationMessage(__("enter_confirm_password_validation"))
            } else if (password.length < 5) {
                Swal.showValidationMessage(__("password_minlength_5_validation"))
            } else if (password !== password_confirm) {
                Swal.showValidationMessage(__("password_no_match_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( password == "" || password_confirm == "" || password.length < 5 || password !== password_confirm ){
                    reject(__("fill_mandatory_fields"));
                    setTimeout(function () { Swal.resetValidationMessage(); },2500);
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxUser.php',
                    type: 'POST',
                    data: {ajax: 1, id: iduser, password: password, ajaxType: 'password_update'},
                    cache: false,
                    dataType: "json",
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
        allowOutsideClick: false
    })
});

$(document).on('click', '.showPasswordAjax', function (e) {
    e.preventDefault();
    var oldpass = $(this).data('oldpass');
    Swal.fire({
        title: __('your_current_password'),
        html: oldpass ,
        showCancelButton: true,
        showConfirmButton: false,
        cancelButtonColor: '#d33',
        cancelButtonText: __('close'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false
    })
});

// Main function to handle user creation
$(document).on('click', '.createUserDeptAjax', function(e) {
    e.preventDefault();
    var emp_id = $(this).data('emp_id');
    let hasUserInteracted = false;

    Swal.fire({
        title: __('create_new_user'),
        html: create_user_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('create_user'),
        showLoaderOnConfirm: true,
        allowOutsideClick: () => {
            if (hasUserInteracted) {return false;}
            return !Swal.isLoading();
        },
        didOpen: () => {
            setupInputValidations();
            const fields = [
                { id: 'email', event: 'input', validation: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value), requiredMessage: __('enter_valid_email') },
                { id: 'user_type',  event: 'change', validation: (value) => value !== "", requiredMessage: __('select_employee_type') }
            ];
            const onFirstInteraction = () => { hasUserInteracted = true; };
            setupDynamicValidation(fields, onFirstInteraction);
        },
        preConfirm: () => {
            return $.ajax({
                url: './includes/ajaxFile/ajaxUser.php',
                type: 'POST',
                data: {
                    emp_id: emp_id,
                    email: $('#email').val(),
                    user_type: $('#user_type').val(),
                    ajaxType: 'create_user'
                },
                cache: false,
                dataType: "json",
            })
            .done(function(response){
                Swal.fire({
                    title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
                }).then(function(isConfirm){(isConfirm)?location.reload():""});
            })
            .fail(function(jqXHR, textStatus) {
                const error = handleAjaxFailure(jqXHR, textStatus);
                Swal.showValidationMessage(`${__('request_failed')} ${error.message}`);
            });
        }
    })
});


////////////////////////////////////////////////////////////////////
////////////            End Users Handling            //////////////
////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////
////////////         Start Employee Handling          //////////////
////////////////////////////////////////////////////////////////////

$(document).on('click', '.endOfService', function (e) {
    e.preventDefault();
    /*var name = $(this).data('name');
    var email = $(this).data('email');
    var idiqama = $(this).data('idiqama');
    var idiqamaexpiry = $(this).data('idiqamaexpiry');
    var passport = $(this).data('passport');
    var passportexpiry = $(this).data('passportexpiry');
    var dob = $(this).data('dob');
    var age = $(this).data('age');
    var gender = $(this).data('gender');
    var mstatus = $(this).data('mstatus');
    var mobile = $(this).data('mobile');
    var country = $(this).data('country');
    var dept = $(this).data('dept');
    var sectin_nme = $(this).data('sectin_nme');
    var address = $(this).data('address');
    var status = $(this).data('status');*/
    
    var joining_date = $(this).data('joining_date');
    var empid = $(this).data('emp_id');
    var salary = $(this).data('salary');
    Swal.fire({
        title: __('eos_calculator_title'),
        html: endOfService_HTML(),
        allowOutsideClick: false,
        showCancelButton : false,
        showConfirmButton : false,
        footer: `<a href="javascript:void(0);" class="SwalBtn2 btn btn-success btn-lg" >${__('yes_calculate')}</a>
                 <!--<a href="javascript:void(0);" class="printSwalBtn btn btn-primary btn-lg" ><i class="fa fa-print"></i> Print</a>-->
                 <a href="javascript:void(0);" class="SwalBtn3 btn btn-danger btn-lg" >${__('close')}</a>`,
        width: "40%",
        willOpen: function() {
            $('input[name="empid"]').val(empid);
            $('input[name="salary"]').val(salary);
            $('input[name="joining_date"]').val(joining_date);
            $('#event_period').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                inputs: [$('#joining_date'),$('#end_date')],
                todayBtn: true,
            });
            $("#inputPeriod").on("change", function() {
                $.ajax({
                    type: "GET", 
                    url: "./includes/RuleSelect.php",
                    data: "pid="+$("#inputPeriod").val(),
                    success: function(html) {
                        $("#inputState").html(html);
                    }
                });
            });
            $("#calculatorForm").on('blur', '#end_date', function() {
                getTotalCost($(this).attr("for"));
            });
            function getTotalCost(ind) {
                var datePeriod = $('#joining_date').val();
                var endDatePeriod = $('#end_date').val();
                $('#yearsPeriod').val( dateDiffYear(datePeriod, endDatePeriod) );
                $('#monthsPeriod').val( dateDiffMonth(datePeriod, endDatePeriod) );
                $('#daysPeriod').val( dateDiffDay(datePeriod, endDatePeriod) );
            }
            $(document).on('click', '.SwalBtn2', function() {
                calculatorForm();
            });
            $(document).on('click', '.SwalBtn3', function() {
                swal.clickCancel();
            });
            function calculatorForm() {
                var form = document.getElementById('calculatorForm');
                e.preventDefault();
                if (form[0].checkValidity() === true) {
                    var inputPeriod = $('#inputPeriod option:selected').val();
                    var inputState = $('#inputState option:selected').val();
                    var salary = $('#salary').val();
                    var yearsPeriod = $('#yearsPeriod').val();
                    var monthsPeriod = $('#monthsPeriod').val();
                    var daysPeriod = $('#daysPeriod').val();
                    sumbitCalc(inputPeriod,inputState,salary,Number(yearsPeriod),Number(monthsPeriod),Number(daysPeriod));
                }
            };
            function sumbitCalc(inputPeriod,inputState,salary,yearsPeriod,monthsPeriod,daysPeriod) {
                var totalDays = getTotalDays(yearsPeriod,monthsPeriod,daysPeriod);
                let result = 0;
                if(inputState === '39' || inputState === '43' || inputState === '45'){
                    result = 0;
                } else if (inputPeriod == '47' && inputState === '46') {
                    result = 0;
                } else if (inputPeriod == '48' && inputState === '46') {
                    if (totalDays < 2 * 360 ){
                        result = 0;
                    } else if (totalDays >= 2 * 360 && totalDays <= 5 * 360){
                        result = (salary / 6) * totalDays;
                    } else if (totalDays > 5 * 360 && totalDays < 10 * 360){
                        var resultFirstFiveYears =  (salary / 3) * ( 5 * 360 );
                        var yearsGreaterThanFive  = totalDays - ( 5 * 360 );
                        var resultGreaterFiveYears = ((salary / 3) * 2 ) * yearsGreaterThanFive;
                        result = resultFirstFiveYears + resultGreaterFiveYears;
                    } else if (totalDays >= 10 * 360){
                        var resultFirstFiveYears =  (salary / 2) * ( 5 * 360 );
                        var yearsGreaterThanFive  = totalDays - ( 5 * 360 );
                        var resultGreaterFiveYears = salary * yearsGreaterThanFive;
                        result = resultFirstFiveYears + resultGreaterFiveYears;
                    }
                } else if((inputPeriod == '47' || inputPeriod == '48') && totalDays <= 5 * 360){
                    result = (salary / 2) * totalDays;
                } else if((inputPeriod == '47' || inputPeriod == '48') && totalDays > 5 * 360){
                    var resultFirstFiveYears =  (salary / 2) * ( 5 * 360 );
                    var yearsGreaterThanFive  = totalDays - ( 5 * 360 );
                    var resultGreaterFiveYears = salary * yearsGreaterThanFive;
                    result = resultFirstFiveYears + resultGreaterFiveYears;
                }
                var Final_result = result / 360;
                $('#resultCalc').html(Final_result.toFixed(2) + ' SR');
                /*var finalAmount = $('#finalAmount').val(Final_result.toFixed(2) + ' SR');*/ // Not Used
            }
            
            function getTotalDays(years,months,days) {
                let result = 0;
                result += years * 360;
                result += months * 30;
                result += days;
                // console.log(result);
                return result;
            };
            /*$(document).on('click', '.printSwalBtn', function() {
                printHTML(eosReportPrint(name,email,idiqama,idiqamaexpiry,passport,passportexpiry,dob,age,gender,mstatus,mobile,country,joining_date,dept,sectin_nme,salary,address,status, yearsPeriod, monthsPeriod, daysPeriod, finalAmount));
            });
            function printHTML(input){
                var iframe = document.createElement("iframe"); // create the element
                document.body.appendChild(iframe);  // insert the element to the DOM 
                iframe.contentWindow.document.open(); // write the HTML to be printed
                iframe.contentWindow.document.write('<html><head><title>End of Service</title>'); // write the HTML to be printed
                iframe.contentWindow.document.write(`
                    <style>
                        table, thead, th, td {
                            border:solid 1px #000;
                            font-size:14px;
                            text-align: left;
                            font-family:"Rubik", sans-serif;
                            border-collapse: collapse;
                        }
                        tr:nth-of-type(odd) { 
                            background: #eee; 
                        }
                        th { 
                            font-weight: bold; 
                        }
                        td, th { 
                            padding: 5px; 
                            border: 1px solid #ccc; 
                            text-align: left; 
                        }
                    </style>`);
                iframe.contentWindow.document.write('</head><body>'); // write the HTML to be printed
                iframe.contentWindow.document.write(input); // write the HTML to be printed
                iframe.contentWindow.document.close(); // write the HTML to be printed
                iframe.contentWindow.print();  // print it
                document.body.removeChild(iframe); // remove the iframe from the DOM
            };*/ // Not Used
        },
    })
});

$(document).on('click', '.empAvatarShow', function (e) {
    e.preventDefault();
    var id          = $(this).data('id');
    var emp_id      = $(this).data('emp_id');
    var emp_name    = $(this).data('emp_name');
    var img         = $(this).data('img');
    var emptype     = $(this).data('emptype');
    Swal.fire({
        title: __('change_employee_image'),
        html: `
        <div class="row customSweetAlertMLR" >
            <div class="col-md-6 text-center">
                <div id="emp-img" style="width:350px"></div>
            </div>
            <div class="col-md-6" style="align-items: center; display: grid; justify-content: center;">
                <img src="${img}" style="width:200px;height:200px" />
            </div>
        </div>`,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('close'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '40%',
        willOpen: function(e) {
            $('.image').trigger('click');
            var reader,file;
            $uploadCrop = $('#emp-img').croppie({
                enableExif: true,
                viewport: {
                    width: 300,
                    height: 300,
                    type: 'circle', //type: 'circle',square
                },
                boundary: {
                    width: 350,
                    height: 350,
                }
            });
            $('#img-crop').on('change', function () {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $uploadCrop.croppie('bind', {
                        url: e.target.result
                    }).then(function(){
                        console.log('jQuery bind complete');
                    }).catch(function(error){
                        Swal.fire({
                            title:__('file_error_title'),text:__('select_jpg_format_only'),icon:'error',allowOutsideClick:false
                        })
                    });
                };
                reader.readAsDataURL(this.files[0]);
            });
        },
        preConfirm: function() {
            return new Promise(function(resolve) {
                $uploadCrop.croppie('result', {
                    type: 'canvas',
                    format: 'jpeg'|'png'|'webp',
                    size: 'viewport'
                }).then(function (resp) {
                    $.ajax({
                        url: "./includes/ajaxFile/ajaxEmployee.php",
                        type: "POST",
                        dataType: "JSON",
                        data: {"image": resp, "id": id, "emp_id": emp_id, "emp_name": emp_name, "emptype": emptype, ajaxType: 'avatar'},
                        success: function (response) {
                            Swal.fire({
                                title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
                            }).then(function(isConfirm){(isConfirm)?location.reload():""});
                        }
                    });
                });
            });
        },
    })
});

$(document).on('click', '.addSocial', function (e) {
    e.preventDefault();
    var emp_id = $(this).data('emp_id');
    Swal.fire({
        title: __('add_social_media_links'),
        html: social_add_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        willOpen: function(){
            $('input[name="emp_id"]').val(emp_id);
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                dataType: 'JSON',
                type: 'POST',
                data: {'emp_id': emp_id, ajaxType: 'social_links'},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].id}">${res.data[i].sname}</option>`;
                        $('#social_link').append(options);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
        },
        preConfirm: function() {
            var link = $('input[name="link"]').val();
            var social_link = $('#social_link option:selected').val();
            if(link == ""){
                Swal.showValidationMessage(__("enter_social_address_validation"))
            } else if(social_link == ""){
                Swal.showValidationMessage(__("select_social_link_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( link == '' || social_link == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitCustomerCardAddForm").serialize()+'&'+$.param({ajaxType: "add_social_links"}),
                })
                .done(function(response){
                    // console.log(response.title)
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.addPortfolio', function (e) {
    e.preventDefault();
    var emp_id = $(this).data('emp_id');
    Swal.fire({
        title: __('add_portfolio_details'),
        html: portfolio_add_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '50%',
        willOpen: function(){
            $('#inlineeditor').summernote({
                placeholder: __('add_details_for_portfolio_placeholder'),
                tabsize: 2,
                height: 230,
                toolbar: [
                  ['style', ['style']],
                  ['font', ['bold', 'underline', 'clear']],
                  ['para', ['ul', 'ol', 'paragraph']],
                  ['view', ['codeview', 'help']]
                  /*['view', ['fullscreen', 'codeview', 'help']]
                  ['color', ['color']],
                  ['table', ['table']],
                  ['insert', ['link', 'picture', 'video']],*/
                ]
            });
        },
        preConfirm: function() {
            var form_Data       = new FormData();
            var file            = $('#fileupload')[0].files;
            var title           = $('#title').val();
            var inlineeditor    = $("#inlineeditor").summernote('code');
            form_Data.append("file", file[0]);
            form_Data.append("emp_id", emp_id);
            form_Data.append("title", title);
            form_Data.append("description", inlineeditor);
            form_Data.append("ajaxType", 'add_portfolio');

            if(title == ""){
                Swal.showValidationMessage(__("enter_portfolio_title_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( title == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: "JSON",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_Data,
                })
                .done(function(response){
                    // console.log(response.title)
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.iqama_exp_hijri', function (e) {
    e.preventDefault();
    var emid = $(this).data('id');
    Swal.fire({
        title: __('update_id_expiry_info'),
        html: id_exp_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        willOpen: function() {
            $('input[name="emid"]').val(emid);
            $("#iq_id_exp_hijri").hijriDatePicker({
                locale: "ar-sa",
                hijri:true,
                showSwitcher:false,
                hijriFormat:"iYYYY-iMM-iDD",
                hijriDayViewHeaderFormat: "iMMMM iYYYY",
                showTodayButton: true,
                inline: true,
                ignoreReadonly: true,
            });
            jQuery('#iq_id_exp_greg').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                todayHighlight: true,
                todayBtn: "linked",
            });
            $("input[name$='note']").click(function(){
                var value = $(this).val();
                if(value=='hijri') {
                    $("#hijriDiv").show();
                    $("#gregorianDiv").hide();
                    $("#iq_id_exp_hijri").attr('name', 'iqama_exp');
                    $("#iq_id_exp_greg").removeAttr('name');
                } else if(value=='gregorian') {
                    $("#hijriDiv").hide();
                    $("#gregorianDiv").show();
                    $("#iq_id_exp_hijri").removeAttr('name');
                    $("#iq_id_exp_greg").attr('name', 'iqama_exp_g');
                }
            });
            var dateInput = document.getElementById('iq_id_exp_greg');
            dateInput.value = new Date().toISOString().split('T')[0];
        },
        preConfirm: function() {
            var iqama_exp = $('input[name="iqama_exp"]').val();
            var iqama_exp_g = $('input[name="iqama_exp_g"]').val();
            var inputCheck = $("input[name$='note']:checked").is(':checked');
            if(inputCheck == false){
                Swal.showValidationMessage(__("select_id_iqama_expiry_validation"))
            }
            return new Promise(function(reject, resolve) {
                if( inputCheck == false ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: {'id': emid, 'iqama_exp':iqama_exp, 'iqama_exp_g':iqama_exp_g, ajaxType:'id_iqama_update' },
                })
                .done(function(response){
                    // console.log(response.title)
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});

$(document).on('click', '.addEmpDocuAtter', function (e) {
    e.preventDefault();
    var id                  = $(this).data('id');
    var emp_id              = $(this).data('emp_id');
    var emptype             = $(this).data('emptype');
    var validExtensions     = ["image/jpg", "image/jpeg", "image/png", "application/pdf"];
    let hasUserInteracted = true;
    Swal.fire({
        title: __('add_employee_documents'),
        html: empDocuments_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        rtl: true,
        allowOutsideClick: () => {
            if (hasUserInteracted) {return false;}
            return !Swal.isLoading();
        },
        width: "30%",
        willOpen: function(){
            $('input[name="emp_id"]').val(emp_id);
            $('input[name="id"]').val(id);
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: 'emp_doc_type'},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].duc_type}">${res.data[i].duc_type}</option>`;
                            options += `<option value="Others">${__('others')}</option>`;
                        $('#docu_typ').append(options);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
            setupInputValidations();
            const fields = [
                { id: 'docu_typ',  event: 'change', validation: (value) => value !== "", requiredMessage: __('select_documents_type_validation') },
                { id: 'checkatt',  event: 'change', validation: (value) => value !== "", requiredMessage: __('select_attachment_file_validation') },
            ];
            const onFirstInteraction = () => { hasUserInteracted = true; };
            setupDynamicValidation(fields, onFirstInteraction);
        },
        preConfirm: function() {
            var form_Data = new FormData();
            var docu_typ = $('#docu_typ option:selected').val();
            var file = $('#checkatt')[0].files;
            form_Data.append("file", file[0]);
            form_Data.append("id", id);
            form_Data.append("emp_id", emp_id);
            form_Data.append("docu_typ", docu_typ);
            form_Data.append("emptype", emptype);
            form_Data.append("ajaxType", 'add_emp_document');
            if(docu_typ == ""){
                Swal.showValidationMessage(__("select_documents_type_validation"))
            }
            if(file.length == 1){
                var filesiz = 1048576 * 5;
                var isValidExt = validExtensions.indexOf(file[0].type) > -1;
                var extCheck = ( isValidExt == false );
                var sizCheck = ( file[0].size >= filesiz );
            }
            var fileCheck = ( file.length == 0 )?"0":"1";
            if(file.length == 0){
                Swal.showValidationMessage(__("select_attachment_file_validation"))
            } else if(isValidExt == false){
                Swal.showValidationMessage(__("upload_pdf_jpg_only_validation"))
            } else if(file[0].size >= filesiz){
                Swal.showValidationMessage(__("upload_size_limit_5mb_validation"))
            }
            
            return new Promise(function(reject, resolve) {
                if( docu_typ == '' || fileCheck == "0" || extCheck == true || sizCheck == true ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: "JSON",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_Data,
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});


$(document).on('click', '.contantChk', function (e) {
    e.preventDefault();

    // --- 1. Get data from the clicked button ---
    var emid = $(this).data('emp_id');
    var id = $(this).data('id');
    var type = $(this).data('type');
    var path = $(this).data('path');
    var newValue = $(this).data('new_value'); // The new requested value

    Swal.fire({
        title: __('employee_info_update_request'),
        html: contant_chk_HTML(),
        width: '600px',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: __('submit_action'),
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        footer: (path) ? `<a href="${path}" target="_blank" class="btn btn-dark btn-sm">${__('show_attachment')}</a>` : '',

        willOpen: function() {
            // --- 2. Populate the form inside the modal ---
            const form = $('#submitEmployeeTempContantForm');
            form.find('input[name="empid"]').val(emid);
            form.find('input[name="id"]').val(id);
            form.find('input[name="type"]').val(type);
            form.find('input[name="path"]').val(path);
            form.find('input[name="new_value"]').val(newValue);

            // --- 3. Display the requested change clearly to the HR user ---
            let conViewHtml = '';
            if (path) { // It's a document or image update
                 conViewHtml = `<h5 class="text-primary d-flex justify-content-center text-center">${__('request_update_field')} ${type.toUpperCase()}</h5>`;
                 if(newValue) {
                    conViewHtml += `<p class="text-muted text-center">${__('description')} ${newValue}</p>`;
                 }
            } else { // It's a data field update
                conViewHtml = `
                    <h5 class="text-primary text-center">${__('request_update_field')} ${type.toUpperCase()}</h5>
                    <hr>
                    <p class="text-center">${__('new_value')} <strong class="text-success fs-5">${newValue}</strong></p>
                `;
            }
            $('#conView').html(conViewHtml);

            // --- 4. Logic to show/hide notes based on approval/rejection ---
            $(".contant_check").change(function(){
                var value = $(this).val();
                if(value === 'approve') {
                    $('#approved').show();
                    $('#notapprove').hide();
                    $('#notesa').attr('name', 'notes'); // Add name to be serialized
                    $('#notesna').removeAttr('name');
                    $('#reqchk').val(''); // Not required
                } else if(value === 'not_approve') {
                    $('#notapprove').show();
                    $('#approved').hide();
                    $('#notesna').attr('name', 'notes'); // Add name to be serialized
                    $('#notesa').removeAttr('name');
                    $('#reqchk').val('required'); // Rejection reason is required
                } else {
                    $('#approved').hide();
                    $('#notapprove').hide();
                    $('#notesa').removeAttr('name');
                    $('#notesna').removeAttr('name');
                    $('#reqchk').val('');
                }
            });
        },

        preConfirm: function() {
            // --- 5. Validate the form before submitting ---
            var action = $('#contant_check').val();
            var isRejection = $('#reqchk').val() === "required";
            var rejectionReason = $("#notesna").val();

            if (action === "") {
                Swal.showValidationMessage(__("select_action_validation"));
                return false;
            }
            if (isRejection && rejectionReason.trim().length === 0) {
                Swal.showValidationMessage(__("enter_rejection_reason_validation"));
                return false;
            }

            // --- 6. Perform the AJAX request ---
            return new Promise(function(resolve, reject) {
                $.ajax({
                    // IMPORTANT: Update this URL to your actual PHP file location
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: "JSON",
                    // Serialize the form and add the ajaxType parameter
                    data: $("#submitEmployeeTempContantForm").serialize() + '&' + $.param({ ajaxType: "emp_temp_contant" }),
                })
                .done(function(response){
                    // On success, show the final status message from the server
                    Swal.fire({
                        title: response.title,
                        text: response.message,
                        icon: response.type,
                        allowOutsideClick: false
                    }).then(function(isConfirm) {
                        if (isConfirm) {
                            location.reload(); // Reload the page to see updated request list
                        }
                    });
                    resolve();
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    // On failure, show a generic error
                    Swal.showValidationMessage(`${__('request_failed_status')} ${textStatus}`);
                    reject();
                });
            });
        },
    });
});

$('#startUpdateRequest').on('click', function() {
    const empid = $(this).data('empid');
    const avatarLoad = $(this).data('avatar');
    const mobile = $(this).data('mobile');
    const email = $(this).data('email');
    const address = $(this).data('address');
    const passport_number = $(this).data('passport_number');
    const passport_exp = $(this).data('passport_exp');
    // --- First Modal: Ask WHAT to update ---
    Swal.fire({
        title: __('what_to_update_title'),
        input: 'select',
        inputOptions: {
            'Mobile': __('mobile'),
            'Email': __('email'),
            'Address': __('address'),
            'Passport No': __('passport_number'),
            'Passport Exp': __('passport_expiry_date'),
            'Profile Picture': __('profile_picture')
        },
        inputPlaceholder: __('select_an_item_placeholder'),
        showCancelButton: true,
        confirmButtonText: __('next'),
        customClass: {
            confirmButton: 'btn btn-primary waves-effect waves-light',
            cancelButton: 'btn btn-secondary waves-effect waves-light ml-2'
        },
        buttonsStyling: false,
        inputValidator: (value) => {
            if (!value) {
                return __('you_need_to_select_something_validation')
            }
        }
    }).then((result) => {
        // If the user clicked "Next" and selected a field
        if (result.isConfirmed && result.value) {
            const field = result.value;
            // --- Handle Profile Picture with Croppie ---
            if (field === 'Profile Picture') {
                const empData = {
                    emp_id: empid,
                    img: avatarLoad
                };
                Swal.fire({
                    title: __('change_profile_picture_title'),
                    html: `
                        <div class="row" >
                            <div class="col-md-12 text-center">
                                <p>${__('current_picture')}</p>
                                <img src="${empData.img}" class="img-fluid rounded-circle mb-3" style="width:150px;height:150px" />
                                <p>${__('new_picture')}</p>
                                <div id="emp-img-cropper" style="width:300px; height:300px; margin:auto;"></div>
                            </div>
                        </div>`,
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    cancelButtonText: __('cancel'),
                    confirmButtonText: __('yes_update'),
                    showLoaderOnConfirm: true,
                    allowOutsideClick: false,
                    width: '500px',
                    didOpen: () => {
                        // This variable will hold the Croppie instance, as per your snippet.
                        let $uploadCrop;
                        // Initialize Croppie on the correct element from the modal's HTML.
                        const el = document.getElementById('emp-img-cropper');
                        $uploadCrop = $(el).croppie({
                            enableExif: true,
                            viewport: {
                                width: 300,
                                height: 300,
                                type: 'circle',
                            },
                            boundary: {
                                width: 350,
                                height: 350,
                            }
                        });
                        // Handle file selection
                        $('#img-crop-input').on('change', function () {
                            var reader = new FileReader();
                            reader.onload = function (e) {
                                // Use the correct method to bind the image to the Croppie instance
                                $uploadCrop.croppie('bind', { 
                                    url: e.target.result 
                                });
                            };
                            reader.readAsDataURL(this.files[0]);
                        });
                        // Trigger the hidden file input
                        $('#img-crop-input').trigger('click');
                        // Store instance for preConfirm
                        Swal.getContainer().croppieInstance = $uploadCrop;
                    },
                    preConfirm: () => {
                        // CORRECTED: Call the 'result' method on the Croppie instance stored in the container
                        return Swal.getContainer().croppieInstance.croppie('result', {
                            type: 'canvas',
                            size: 'viewport',
                            format: 'png'
                        }).then(function (resp) {
                            return $.ajax({
                                url: "./includes/ajaxFile/ajaxEmployee.php",
                                type: "POST",
                                dataType: "JSON",
                                data: {
                                    "image_base64": resp,
                                    "emp_id": empData.emp_id,
                                    "type": "Profile Picture",
                                    ajaxType: 'create_update_request'
                                }
                            }).fail(function() {
                                Swal.showValidationMessage(__("request_failed_try_again"));
                            });
                        });
                    },
                }).then((croppieResult) => {
                    if (croppieResult.isConfirmed && croppieResult.value) {
                        Swal.fire({
                            title: croppieResult.value.title,
                            text: croppieResult.value.message,
                            icon: croppieResult.value.type
                        }).then(() => location.reload());
                    }
                });

            } 
            // --- Handle all other fields ---
            else {
                let inputType = 'text';
                let currentValue = '';
                switch(field) {
                    case 'Mobile': currentValue = mobile; break;
                    case 'Email': inputType = 'email'; currentValue = email; break;
                    case 'Address': currentValue = address; break;
                    case 'Passport No': currentValue = passport_number; break;
                    case 'Passport Exp': inputType = 'date'; currentValue = passport_exp; break;
                }
                Swal.fire({
                    title: `${__('update_field_title')} ${field}`,
                    html: `
                        <p class="text-muted">${__('your_current_value_is')} <strong>${currentValue}</strong></p>
                        <form id="updateRequestForm" class="mt-3">
                                <input type="hidden" name="type" value="${field}">
                                <input type="hidden" name="emp_id" value="${empid}">
                                <input type="${inputType}" id="swal-input" name="new_value" class="form-control" placeholder="${__('enter_new_field_placeholder')} ${field.toLowerCase()}" required>
                        </form>`,
                    confirmButtonText: __('submit_request'),
                    customClass: {
                        confirmButton: 'btn btn-success waves-effect waves-light',
                        cancelButton: 'btn btn-danger waves-effect waves-light ml-2'
                    },
                    buttonsStyling: false,
                    showCancelButton: true,
                    focusConfirm: false,
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading(),
                    preConfirm: () => {
                        const form = document.getElementById('updateRequestForm');
                        const formData = new FormData(form);
                        formData.append('ajaxType', 'create_update_request');
                        return $.ajax({
                            url: './includes/ajaxFile/ajaxEmployee.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json'
                        }).fail(function() {
                            Swal.showValidationMessage(__("request_failed"));
                        });
                    }
                }).then((finalResult) => {
                    if (finalResult.isConfirmed) {
                        Swal.fire({
                            title: finalResult.value.title,
                            text: finalResult.value.message,
                            icon: finalResult.value.type
                        });
                    }
                });
            }
        }
    });
});

/*$(document).on('click', '.editEmpInfo', function (e) {
    e.preventDefault();
    var emid = $(this).data('emp_id');
    var id = $(this).data('id');
    Swal.fire({
        title: 'Employee Contant information',
        html: edit_emp_chk_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Update!',
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        willOpen: function() {
            $('input[name="empid"]').val(emid);
            $('input[name="id"]').val(id);
            $("#edit_contant_check").on("change", function() {
                var value = $(this).val();
                if($(this).val().length > 0) {
                    $('#notapprove').show();
                    if ($(this).val() == 'iqama_exp') {
                        var label_text = 'Iqama Expiry';
                        $('#field_text').html(`<input type="text" name="iqama_exp" class="form-control" />`);
                        $("input[name=iqama_exp]").hijriDatePicker({
                            locale: "ar-sa",
                            hijri:true,
                            showSwitcher:false,
                            hijriFormat:"iYYYY-iMM-iDD",
                            hijriDayViewHeaderFormat: "iMMMM iYYYY",
                            showTodayButton: true,
                            inline: true,
                            ignoreReadonly: true,
                        });
                    }else if($(this).val() == 'mobile'){
                        var label_text = 'Mobile Number';
                        $('#field_text').html(`<input type="text" name="mobile" data-mask="0599999999" class="form-control" />`);
                    }else if($(this).val() == 'emg_mobile'){
                        var label_text = 'Emergency Contact';
                        $('#field_text').html(`<input type="text" name="emg_mobile" data-mask="0599999999" class="form-control" />`);
                    }else if($(this).val() == 't_shirt_size'){
                        var label_text = 'T-Size';
                        $('#field_text').html(`<input type="text" name="t_shirt_size" class="form-control" />`);
                    }else if($(this).val() == 'iban'){
                        var label_text = 'Bank Account IBAN';
                        $('#field_text').html(`<input type="text" name="iban" class="form-control" data-mask="SA99 9999 9999 9999 9999 9999" />`);
                    }else if($(this).val() == 'email'){
                        var label_text = 'Email';
                        $('#field_text').html(`<input type="text" name="email" class="form-control" />`);
                    }else if($(this).val() == 'address'){
                        var label_text = 'Address';
                        $('#field_text').html(`<input type="text" name="address" class="form-control" />`);
                    }else{
                        var label_text = '';
                        $('#field_text').val('');
                        $('#field_text').attr('name','');
                    }
                }else{
                    $('#notapprove').hide();
                    $('#field_text').val('');
                    $('#field_text').attr('name','');
                }

                $('.label_text').html(`${label_text}`);

            });
        },
        preConfirm: function() {
            var contant_check = $('#edit_contant_check option:selected');
            var field_text = $("#submitEmployeeTempContantForm input[type=text]").val();
            if(contant_check.val() == ""){
                Swal.showValidationMessage(`Please select option for edit.`);
            } else if (field_text == "") {
                Swal.showValidationMessage(`Please enter value ${contant_check.text()}`);
            }
            return new Promise(function(reject, resolve) {
                if( contant_check.val() == "" || field_text == "" ){
                    reject("Please fill all mendatory(*) fields first!");
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEmployeeTempContantForm").serialize()+'&'+$.param({ajaxType: "emp_edit_contannt"}),
                })
                .done(function(response){
                    // console.log(response.title);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
});
*/

function loadDateForEOS(){
    Swal.fire({
        title: __('select_date_for_calculation'),
        html: eos_select_date_HTML(),
        showCancelButton: false,
        confirmButtonColor: '#3085d6',
        confirmButtonText: __('yes_select'),
        allowEscapeKey : false,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        willOpen: function() {
            jQuery('#eos_date').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                todayHighlight: true,
                todayBtn: "linked",
            });
        },
        preConfirm: function() {
            var eos_date = $('input[name="eos_date"]').val();
            if(eos_date == ""){
                Swal.showValidationMessage(__("select_date_for_eos_validation"))
            } 
            return new Promise(function(reject, resolve) {
                if( eos_date == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: {eos_date: eos_date, ajaxType : 'eos_date_get'},
                })
                .done(function(response){
                    if (response.status == 200) {
                        Swal.fire({
                            title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
                        }).then(function(isConfirm){(isConfirm)? window.location.href = './employee_audit_gen.php?date='+eos_date :""});
                    }  
                })
                .fail(function(){
                    Swal.fire("response.title", "response.message", "response.type");
                });
            });
        },
    })
}


// Make sure you have included the SweetAlert2 library in your page
// <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

function assignAsset(empId) {
    // First, fetch the list of available assets from the server
    $.ajax({
        type: 'POST',
        url: './includes/ajaxFile/ajaxEmployee.php',
        data: { ajaxType: 'get_asset_types' },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.assets.length > 0) {
                // Build the options for the select dropdown
                let assetOptions = response.assets.map(asset => {
                    return `<option value="${asset.id}">${asset.name}</option>`;
                }).join('');

                // Now, show the SweetAlert2 modal with the dynamic dropdown
                Swal.fire({
                    title: __('assign_new_asset'),
                    html: `
                        <form id="assignAssetForm" class="text-left">
                            <div class="form-group">
                                <label for="swal-asset-id">${__('asset_type')}</label>
                                <select id="swal-asset-id" class="form-control">
                                    <option value="">${__('select_an_asset')}</option>
                                    ${assetOptions}
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="swal-serial-number">${__('serial_number_identifier')}</label>
                                <input id="swal-serial-number" class="form-control" placeholder="${__('serial_placeholder')}">
                            </div>
                            <div class="form-group">
                                <label for="swal-description">${__('description')}</label>
                                <textarea id="swal-description" class="form-control" placeholder="${__('description_placeholder')}"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="swal-assigned-date">${__('assigned_date')}</label>
                                <input type="text" id="swal-assigned-date" class="form-control" value="${new Date().toISOString().slice(0, 10)}">
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    cancelButtonText: __('cancel'),
                    confirmButtonText: __('assign'),
                    showLoaderOnConfirm: true,
                    didOpen: () => {
                        $('#swal-assigned-date').datepicker({
                            format: "yyyy-mm-dd",
                            todayHighlight: true,
                            autoclose: true,
                            // startDate: '+0d'
                        });
                        const fields = [
                            { id: 'swal-asset-id',  event: 'change', validation: (value) => value !== "", requiredMessage: __('select_asset_type_validation') },
                            { id: 'swal-serial-number',  event: 'change', validation: (value) => value !== "", requiredMessage: __('enter_asset_identity_serial_validation') },
                            { id: 'swal-assigned-date',  event: 'changeDate', validation: (value) => value !== "", requiredMessage: __('select_assigned_date_validation') },
                        ];
                        const onFirstInteraction = () => { hasUserInteracted = true; };
                        setupDynamicValidation(fields, onFirstInteraction);
                    },
                    preConfirm: () => {
                        // Collect data from the form
                        const assetId = document.getElementById('swal-asset-id').value;
                        const serialNumber = document.getElementById('swal-serial-number').value;
                        const description = document.getElementById('swal-description').value;
                        const assignedDate = document.getElementById('swal-assigned-date').value;
                        // Return the data to be sent via AJAX
                        return {
                            ajaxType: 'assign_asset',
                            emp_id: empId,
                            asset_id: assetId,
                            serial_number: serialNumber,
                            description: description,
                            assigned_date: assignedDate
                        };
                    },
                    allowOutsideClick: false,
                }).then((result) => {
                    if (result.isConfirmed) {
                        // The `preConfirm` function returned data, now send it
                        $.ajax({
                            type: 'POST',
                            url: './includes/ajaxFile/ajaxEmployee.php',
                            data: result.value, // The data from preConfirm
                            dataType: 'json',
                            success: function(ajaxResponse) {
                                Swal.fire({
                                    title: ajaxResponse.title,
                                    text: ajaxResponse.message,
                                    icon: ajaxResponse.type
                                }).then(() => {
                                    if(ajaxResponse.type === 'success') {
                                        // Optionally, reload the page or update a table to show the new asset
                                        location.reload();
                                    }
                                });
                            },
                            error: function() {
                                Swal.fire(__('error_title'), __('unexpected_error'), 'error');
                            }
                        });
                    }
                });

            } else {
                Swal.fire(__('error_title'), __('could_not_load_asset_types'), 'error');
            }
        },
        error: function() {
            Swal.fire(__('error_title'), __('failed_to_connect_for_asset_types'), 'error');
        }
    });
}

function unassignAsset(assetRecordId) {
    Swal.fire({
        title: __('return_asset'),
        html: `
            <form id="returnAssetForm" class="text-left" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="swal-return-date">${__('return_date')}</label>
                    <input type="text" id="swal-return-date" class="form-control" value="${new Date().toISOString().slice(0, 10)}">
                </div>
                <div class="form-group">
                    <label for="swal-return-status">${__('return_status')}</label>
                    <select id="swal-return-status" class="form-control">
                        <option value="">${__('select_status')}</option>
                        <option value="Returned">${__('returned')}</option>
                        <option value="Damaged">${__('damaged')}</option>
                        <option value="Lost">${__('lost')}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="swal-return-attachment">${__('proof_of_return')}</label>
                    <input type="file" id="swal-return-attachment" class="form-control-file">
                </div>
            </form>`,
        showCancelButton: true,
        confirmButtonText: __('submit_return'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        didOpen: () => {
            $('#swal-return-date').datepicker({
                    format: "yyyy-mm-dd",
                    todayHighlight: true,
                    autoclose: true,
                    // startDate: '+0d'
                });
                const fields = [
                    { id: 'swal-return-status',  event: 'change', validation: (value) => value !== "", requiredMessage: __('select_return_status_validation') },
                    { id: 'swal-return-date',  event: 'changeDate', validation: (value) => value !== "", requiredMessage: __('select_return_date_validation') },
                    { id: 'swal-return-attachment',  event: 'change', validation: (value) => value !== "", requiredMessage: __('select_proof_of_return_file_validation') },
                ];
                const onFirstInteraction = () => { hasUserInteracted = true; };
                setupDynamicValidation(fields, onFirstInteraction);
        },
        preConfirm: () => {
            const returnDate = document.getElementById('swal-return-date').value;
            const returnStatus = document.getElementById('swal-return-status').value;
            const attachmentFile = document.getElementById('swal-return-attachment').files[0];

            // if (!returnDate || !returnStatus) {
            //     Swal.showValidationMessage('Please select a return date and status.');
            //     return false;
            // }
            
            const formData = new FormData();
            formData.append('ajaxType', 'unassign_asset');
            formData.append('asset_record_id', assetRecordId);
            formData.append('return_date', returnDate);
            formData.append('return_status', returnStatus);
            if (attachmentFile) {
                formData.append('return_attachment', attachmentFile);
            }
            return formData;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST',
                url: './includes/ajaxFile/ajaxEmployee.php',
                data: result.value,
                dataType: 'json',
                contentType: false, // Important for file uploads
                processData: false, // Important for file uploads
                success: function(ajaxResponse) {
                    Swal.fire({ title: ajaxResponse.title, text: ajaxResponse.message, icon: ajaxResponse.type })
                    .then(() => { if(ajaxResponse.type === 'success') { location.reload(); } });
                },
                error: function() { Swal.fire(__('error_title'), __('unexpected_error'), 'error'); }
            });
        }
    });
}


////////////////////////////////////////////////////////////////////
////////////          End Employee Handling           //////////////
////////////////////////////////////////////////////////////////////

////////////          Start Voucher Handling          //////////////
////////////////////////////////////////////////////////////////////

function addVoucherFunc(empid){
    var validExtensions = ["image/jpg", "image/jpeg", "image/png", "application/pdf"];
    Swal.fire({
        title: __('add_new_voucher_title'),
        html: Voucher_HTML(),
        // text: "You won't be able to revert this!",
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        width: '50%',
        allowOutsideClick: false,
        // customClass: 'swal-wide',
        willOpen: function() {
            $('input[name="empid"]').val(empid);
            $("#emp_v_user").select2();
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: 'emp_search'},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data)
                            options += `<option value="${res.data[i].emp_id}">${res.data[i].name.split(' ')[0]+' '+res.data[i].name.split(' ')[1] }</option>`;
                        $('#emp_v_user').append(options);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
            $(document).on('click', '.showAttachment', function (e) {
                $(".attachmentDIV").removeClass("noneDIV");
                $("#checkatt").attr('name', 'file');
                $("#checkatt").attr('accept', validExtensions);
            });
            $(document).on('click', '.hideAttachment', function (e) {
                $(".attachmentDIV").addClass("noneDIV");
                $("#checkatt").attr('name', '');
            });
        },
        preConfirm: function() {
            var form_Data = new FormData();
            var emp_v_user = $('#emp_v_user').val();
            var voucher_type = $('#voucher_type').val();
            var amount = $('#amount').val();
            var details = $('#details').val();
            var empid = $('#empid').val();
            var acc_no = $('#acc_no').val();
            var chq_no = $('#chq_no').val();
            var attach = $('input[name=attach]:checked').is(':checked');
            var file = $('#checkatt')[0].files;
            form_Data.append("file", file[0]);
            form_Data.append("emp_v_user", emp_v_user);
            form_Data.append("voucher_type", voucher_type);
            form_Data.append("amount", amount);
            form_Data.append("details", details);
            form_Data.append("empid", empid);
            form_Data.append("acc_no", acc_no);
            form_Data.append("chq_no", chq_no);
            form_Data.append("ajaxType", 'add_voucher');
            if(emp_v_user == ""){
                Swal.showValidationMessage(__("select_employee_validation"))
            } else if(voucher_type == ""){
                Swal.showValidationMessage(__("select_voucher_type_validation"))
            } else if(amount == ""){
                Swal.showValidationMessage(__("enter_voucher_amount_validation"))
            }  else if(details == ""){
                Swal.showValidationMessage(__("enter_voucher_details_validation"))
            } else if(attach == false){
                Swal.showValidationMessage(__("select_attachment_selection_validation"))
            } 
            if ($('input[name=attach]:checked').val() == 'yes') {
                if(file.length == 1){
                    var filesiz = 1048576 * 5;
                    var isValidExt = validExtensions.indexOf(file[0].type) > -1;
                    var extCheck = ( isValidExt == false );
                    var sizCheck = ( file[0].size >= filesiz );
                }
                var fileCheck = ( file.length == 0 )?"0":"1";
                if(file.length == 0){
                    Swal.showValidationMessage(__("select_attachment_file_validation"))
                } else if(isValidExt == false){
                    Swal.showValidationMessage(__("upload_pdf_jpg_only_validation"))
                } else if(file[0].size >= filesiz){
                    Swal.showValidationMessage(__("upload_size_limit_5mb_validation"))
                }
            }
            return new Promise(function(reject, resolve) {
                if( emp_v_user == '' || voucher_type == '' || amount == '' || details == '' || attach == '' || fileCheck == "0" || extCheck == true || sizCheck == true ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxVoucher.php',
                    type: 'POST',
                    dataType: "JSON",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_Data,
                })
                .done(function(response){
                    // console.log(response.title);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __('ok')
                    }).then(function(isConfirm){(isConfirm)? $('#vouchers_vac').DataTable().ajax.reload() :""});
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        },
    })
};

////////////////////////////////////////////////////////////////////
////////////          End Voucher Handling            //////////////
////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////
////////////      Start Employee Invoice Handling     //////////////
////////////////////////////////////////////////////////////////////
function addRejNote(srno){
    Swal.fire({
        title: __('add_rejected_note_title'),
        html: addRejNote_HTML(),
        showCancelButton: false,
        confirmButtonColor: '#3085d6',
        confirmButtonText: __('yes_add'),
        allowEscapeKey : false,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        willOpen: function() {
            $('input[name="srno"]').val(srno);
        },
        preConfirm: function() {
            var rejectnote = $('input[name="note"]').val();
            if(rejectnote == ""){
                Swal.showValidationMessage(__("enter_rejected_note_validation"))
            } 
            return new Promise(function(reject, resolve) {
                if( rejectnote == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxInvoStatus.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEditCategoryForm").serialize()+'&'+$.param({ajaxType: "rejectnotepost"}),
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(){
                    Swal.fire("response.title", "response.message", "response.type");
                });
            });
        },
    })
};

var status_chk = document.querySelector('#status_chk');
if (status_chk != null) {
    $('#status_chk').editable({
        showbuttons: false,
        prepend: __('status_not_selected'),
        mode: 'inline',
        inputclass: 'form-control-sm',
        source: [
            {value: 'approve', text: __('approve')},
            {value: 'reject', text: __('reject')}
        ],
        url: function(prm) {
            var status = prm.value;
            var elem = $(this);
            var srno = elem.data('srno');
            $.ajax({
                url: './includes/ajaxFile/ajaxInvoStatus.php',
                type: 'POST',
                dataType: 'JSON',
                data: {
                    srno: srno,
                    status: status,
                },
                success: function (response) {
                    if(response.status == 'reject'){
                        addRejNote(srno);
                    } else {
                        Swal.fire({
                            title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
                        }).then(function(isConfirm){(isConfirm)?location.reload():""});
                    }
                }
            });
        },
        display: function(value, sourceData) {
            var colors = {"": "gray", 'approve': "green", 'reject': "red"},
                elem = $.grep(sourceData, function(o){return o.value == value;});
            if(elem.length) {
                $(this).text(elem[0].text).css("color", colors[value]);
            } else {
                $(this).empty();
            }
        }
    });
}

function addInvoiceAmount(){
    Swal.fire({
        title: __('add_total_amount_title'),
        html: add_inv_mont_HTML(),
        showCancelButton: false,
        confirmButtonColor: '#3085d6',
        confirmButtonText: __('yes_add'),
        allowEscapeKey : false,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        didOpen: function() {
            jQuery(function($) {
                $('.autonumber').autoNumeric('init');
            });
            jQuery.browser = {};
            (function () {
                jQuery.browser.msie = false;
                jQuery.browser.version = 0;
                if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
                    jQuery.browser.msie = true;
                    jQuery.browser.version = RegExp.$1;
                }
            })();
            $.ajax({
                url: './includes/ajaxFile/ajaxInvoStatus.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: 'last_inv_search'},
                success: function(res) {
                    $('input[name="srno"]').val(res);
                },
                error: function(j, e) {
                    errorHandling(j, e)
                },
            });
        },
        preConfirm: function() {
            var totalAmount = $('input[name="amount"]').val();
            if(totalAmount == ""){
                Swal.showValidationMessage(__("enter_total_invoice_amount_validation"))
            } 
            return new Promise(function(reject, resolve) {
                if( totalAmount == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxInvoStatus.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEditCategoryForm").serialize()+'&'+$.param({ajaxType: "total_amount"}),
                })
                .done(function(response){
                    // console.log(response);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(){
                    Swal.fire("response.title", "response.message", "response.type");
                });
            });
        },
    })
}

function updateInvoiceAmount(id){
    Swal.fire({
        title: __('add_total_amount_title'),
        html: add_inv_mont_HTML(),
        showCancelButton: false,
        confirmButtonColor: '#3085d6',
        confirmButtonText: __('yes_add'),
        allowEscapeKey : false,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        didOpen: function() {
            jQuery(function($) {
                $('.autonumber').autoNumeric('init');
            });
            jQuery.browser = {};
            (function () {
                jQuery.browser.msie = false;
                jQuery.browser.version = 0;
                if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
                    jQuery.browser.msie = true;
                    jQuery.browser.version = RegExp.$1;
                }
            })();
            $('input[name="srno"]').val(id);
        },
        preConfirm: function() {
            var totalAmount = $('input[name="amount"]').val();
            if(totalAmount == ""){
                Swal.showValidationMessage(__("enter_total_invoice_amount_validation"))
            } 
            return new Promise(function(reject, resolve) {
                if( totalAmount == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxInvoStatus.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitEditCategoryForm").serialize()+'&'+$.param({ajaxType: "total_amount"}),
                })
                .done(function(response){
                    // console.log(response);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(){
                    Swal.fire("response.title", "response.message", "response.type");
                });
            });
        },
    })
}

function approveInvoiceAmount(id,amount){
    Swal.fire({
        title: __('approve_total_amount_title'),
        html: approv_inv_mont_HTML() /*`<input type="text" value="${amount}" >`*/,
        showCancelButton: false,
        confirmButtonColor: '#3085d6',
        confirmButtonText: __('yes_add'),
        allowEscapeKey : false,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        didOpen: function() {
            jQuery(function($) {
                $('.autonumber').autoNumeric('init');
            });
            jQuery.browser = {};
            (function () {
                jQuery.browser.msie = false;
                jQuery.browser.version = 0;
                if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
                    jQuery.browser.msie = true;
                    jQuery.browser.version = RegExp.$1;
                }
            })();
            $('input[name="srno"]').val(id);
        },
        preConfirm: function() {
            var totalAmount = $('input[name="amount"]').val();
            if(totalAmount == ""){
                Swal.showValidationMessage(__("enter_total_invoice_amount_validation"))
            } 
            return new Promise(function(reject, resolve) {
                if( totalAmount == '' ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/ajaxInvoStatus.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitApprovAmountForm").serialize()+'&'+$.param({ajaxType: "aprrov_amount"})/*+'&'+$.param({oldamount: amount})*/,
                })
                .done(function(response){
                    // console.log(response);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(){
                    Swal.fire("response.title", "response.message", "response.type");
                });
            });
        },
    })
}
$(function(){
    $('div[onload]').trigger('onload');
});
$(function(){
    $('div[class="approv"]').trigger('onload');
});
////////////////////////////////////////////////////////////////////
////////////       End Employee Invoice Handling      //////////////
////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////
///////////       Start Employee vacation Handling      ////////////
////////////////////////////////////////////////////////////////////



/**
 * Toggles the visibility of form fields based on the selected leave type.
 */
function toggleLeaveFields() {
    const selectedType = $('#leave_type_select').val();
    
    // Hide all conditional sections first
    $('#dateSection, #reasonSection, #attachmentSection, #tripSection').addClass('d-none');
    $('#end_date').closest('.form-group').show(); // Show end date by default
    calculateTotalDays(); // Recalculate days when type changes

    if (!selectedType) return;

    // Show sections based on the selected type
    switch (selectedType) {
        case 'Sick Leave':
            $('#dateSection, #reasonSection, #attachmentSection').removeClass('d-none');
            break;
        case 'Maternity Leave':
            $('#dateSection, #attachmentSection').removeClass('d-none');
            break;
        case 'Business Trip':
            $('#dateSection, #tripSection, #reasonSection').removeClass('d-none');
            break;
        case 'Compensatory Leave':
            $('#dateSection, #reasonSection').removeClass('d-none');
            $('#end_date').closest('.form-group').hide(); // Compensatory leave is usually for a single day
            $('#end_date').val($('#start_date').val()); // Set end date same as start date
            calculateTotalDays();
            break;
        default: // Casual Leave, Other Leave, Compassionate Leave
            $('#dateSection, #reasonSection').removeClass('d-none');
            break;
    }
}

function calculateTotalDays() {
    const startDateStr = $('#start_date').val();
    const endDateStr = $('#end_date').val();
    
    if (startDateStr && endDateStr) {
        const startDate = new Date(startDateStr);
        const endDate = new Date(endDateStr);

        if (endDate >= startDate) {
            // Calculate the difference in time (milliseconds) and convert to days
            const timeDiff = endDate.getTime() - startDate.getTime();
            const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
            $('#total_days').val(dayDiff + (dayDiff > 1 ? __('days_suffix') : __('day_suffix')));
        } else {
            $('#total_days').val(''); // Clear if end date is before start date
        }
    } else if (startDateStr && $('#leave_type_select').val() === 'Compensatory Leave') {
            $('#total_days').val('1' + __('day_suffix'));
    } else {
        $('#total_days').val(''); // Clear if one or both dates are missing
    }
}

// Main click event handler for the "Apply for Leave" button
$(document).on('click', '.applyLeaveRequest', function(e) {
    e.preventDefault();
    const empid = $(this).data('empid');

    Swal.fire({
        title: __('loading_employee_info'),
        html: generateLeaveFormHTML(),
        width: '50rem', // Adjusted for Bootstrap form layout
        showCancelButton: true,
        confirmButtonText: __('submit_request'),
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        willOpen: () => {
            // Show a loading state while fetching employee data
            Swal.showLoading();

            // Fetch employee data to get the name
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    ajaxType: "emp_data",
                    empid: empid
                },
                success: function(res) {
                    if (res.status == 200 && res.data.length > 0) {
                        const employeeName = res.data[0].name;
                        // Update the modal title with the employee's name
                        $('.swal2-title').html(`${__('leave_application_for')} <br><span style="color:#3085d6;">${employeeName}</span>`);
                        Swal.hideLoading();
                    } else {
                        // Handle case where employee is not found
                        $('.swal2-title').text(__('employee_not_found'));
                        Swal.hideLoading();
                    }
                },
                error: function() {
                    $('.swal2-title').text(__('error_fetching_data'));
                    Swal.hideLoading();
                }
            });
        },
        didOpen: () => {
            // Initialize Select2
            $('#leave_type_select').select2({
                placeholder: __("select_leave_type_placeholder"),
                dropdownParent: $('.swal2-container') // Important for positioning
            });

            // Initialize datepickers and add event listeners
            $('#start_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true,
                startDate: '+0d'
            }).on('changeDate', function(e) {
                $('#end_date').datepicker('setStartDate', e.date);
                if ($('#leave_type_select').val() === 'Compensatory Leave') {
                    $('#end_date').val($(this).val()).datepicker('update');
                }
                calculateTotalDays();
            });

            $('#end_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true,
                startDate: '+0d'
            }).on('changeDate', function(e) {
                calculateTotalDays();
            });

            // Add event listener for the Select2 dropdown
            $('#leave_type_select').on('change', toggleLeaveFields);
        },
        preConfirm: () => {
            const form = document.getElementById('applyLeaveForm');
            const formData = new FormData(form);
            formData.append("ajaxType", "applyLeave");
            formData.append("empid", empid);

            // --- UPDATED Validation Logic ---
            const leaveType = formData.get('leave_type');
            if (!leaveType) {
                Swal.showValidationMessage(__('select_leave_type_validation'));
                return false;
            }

            const startDate = formData.get('start_date');
            if (!$('#dateSection').hasClass('d-none') && !startDate) {
                Swal.showValidationMessage(__('start_date_required'));
                return false;
            }
            
            const endDate = formData.get('end_date');
            if (!$('#dateSection').hasClass('d-none') && leaveType !== 'Compensatory Leave' && !endDate) {
                    Swal.showValidationMessage(__('end_date_required'));
                    return false;
            }

            if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                Swal.showValidationMessage(__('end_date_before_start_date_validation'));
                return false;
            }

            const destination = formData.get('trip_destination');
            if (leaveType === 'Business Trip' && !destination.trim()) {
                Swal.showValidationMessage(__('destination_required_validation'));
                return false;
            }

            const reason = formData.get('reason');
            const reasonRequiredTypes = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Business Trip', 'Compensatory Leave', 'Other Leave'];
            if (reasonRequiredTypes.includes(leaveType) && !reason.trim()) {
                Swal.showValidationMessage(__('reason_required_validation'));
                return false;
            }


            // --- AJAX Submission ---
            return $.ajax({
                url: './includes/ajaxFile/ajaxVacation.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).catch(error => {
                if (error.responseJSON && error.responseJSON.message) {
                        Swal.showValidationMessage(error.responseJSON.message);
                } else {
                        Swal.showValidationMessage(`${__('request_failed_status')} ${error.statusText || 'Unknown error'}`);
                }
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: result.value.title,
                text: result.value.message,
                icon: result.value.type,
            }).then(() => {
                if (result.value.type === 'success') {
                    location.reload();
                }
            });
        }
    });
});



$(document).on('click', '.applyvacationAtter', function (e) {
    e.preventDefault();
    var empid = $(this).data('empid');
    var deptId = $(this).data('dept');
    var country = $(this).data('country');
    Swal.fire({
        title: __('apply_vacation_info_title'),
        html: vacationApply_HTML(country),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: __('yes_register'),
        cancelButtonText: __('cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        rtl: true,
        // width: "50%",
        willOpen: () => {
            // Date picker initialization
            /*$('#start_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true,
                startDate: '+0d'
            }).on('changeDate', function(e) {
                var startDate = e.date;
                var maxEndDate = new Date(startDate);
                maxEndDate.setDate(maxEndDate.getDate() + 20.00); // Add 20 days
                // Set end date to same as start date initially
                $('#end_date').datepicker('setStartDate', startDate);
                $('#end_date').datepicker('setEndDate', maxEndDate);
                $('#end_date').datepicker('update', startDate); // Auto-set end date to start date
            });
            $('#end_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true
            }).on('changeDate', function(e) {
                // Prevent start date from being after end date
                $('#start_date').datepicker('setEndDate', e.date); 
                // Calculate if end date is more than 20 days from start
                var startDate = $('#start_date').datepicker('getDate');
                if (startDate) {
                    var maxAllowedDate = new Date(startDate);
                    maxAllowedDate.setDate(maxAllowedDate.getDate() + 20.00);
                    if (e.date > maxAllowedDate) {
                        $('#end_date').datepicker('update', maxAllowedDate);
                        alert('Maximum 20 days range allowed');
                    }
                }
            });*/
            $('#start_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true,
                startDate: '+0d'
            }).on('changeDate', function (e) {
                var startDate = e.date;
                $('#end_date').datepicker('setStartDate', startDate); // Prevent end date before start
            });

            $('#end_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true
            }).on('changeDate', function (e) {
                var endDate = e.date;
                $('#start_date').datepicker('setEndDate', endDate); // Prevent start date after end
            });
            // Initialize Select2 for replacement person dropdown
            $("#replacement_per").select2();
            // Load replacement persons
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "emp_department", dept: deptId},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data) {
                            options += `<option value="${res.data[i].emp_id}">${res.data[i].name.split(' ')[0]+' '+res.data[i].name.split(' ')[1]}</option>`;
                        }
                        $('#replacement_per').append(options);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e);
                },
            });
            // Load employee data
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "emp_data", empid: empid},
                success: function(res) {
                    if (res.status == 200) {
                        $('input[name="name"]').val(res.data[0].name);
                        $('input[name="empid"]').val(res.data[0].emp_id);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e);
                },
            });
            // Toggle fields based on vacation type selection
            function toggleVacationFields() {
                const selectedVac = document.querySelector('input[name="vac_type"]:checked');
                // Hide all by default
                $('#flyTypeSection, #replacementSection, #date_select, #notesSection').addClass('d-none');
                if (!selectedVac) return;
                const vacValue = selectedVac.value;
                if (vacValue === 'Local Vacation' || vacValue === 'Fly') {
                    $('#flyTypeSection').removeClass('d-none');
                    // Check if any fly_type is already selected
                    const selectedFlyType = document.querySelector('input[name="fly_type"]:checked');
                    if (selectedFlyType) {
                        const flyVal = selectedFlyType.value;
                        if (flyVal === 'annual' || flyVal === 'emergency') {
                            $('#replacementSection, #date_select').removeClass('d-none');
                        }
                    }
                    // Attach fly_type listener to trigger section toggle
                    document.querySelectorAll('input[name="fly_type"]').forEach(flyRadio => {
                        flyRadio.addEventListener('change', function () {
                            const flyVal = this.value;
                            if (flyVal === 'annual' || flyVal === 'emergency') {
                                $('#replacementSection, #date_select').removeClass('d-none');
                            } else {
                                $('#replacementSection, #date_select').addClass('d-none');
                            }
                        });
                    });
                }
            }
            // Initialize date picker and fields when form is created
            function initVacationForm() {
                document.querySelectorAll('input[name="vac_type"]').forEach(radio => {
                    radio.addEventListener('change', toggleVacationFields);
                });
                toggleVacationFields(); // trigger once on load
            }
            initVacationForm();
        },
        preConfirm: function() {
            const formElement = document.getElementById('submitVacationApplyForm');
            const formData = new FormData(formElement);
            formData.append("ajaxType", "applyVacation");
            const selectedRadio = $('input[name="vac_type"]:checked').val();
            if (!selectedRadio) {
                Swal.showValidationMessage(__('select_vacation_type_validation'));
                return false;
            }
            // Validation for "Local Vacation" or "Fly"
            if (selectedRadio === 'Local Vacation' || selectedRadio === 'Fly') {
                const flyType = $('input[name="fly_type"]:checked').val();
                if (!flyType) {
                    Swal.showValidationMessage(__('select_vacation_type_validation'));
                    return false;
                }
                if (flyType === 'annual' || flyType === 'emergency') {
                    const startDate = $('#start_date').val();
                    const endDate = $('#end_date').val();
                    const replacement = $('#replacement_per').val();
                    if (!startDate || !endDate) {
                        Swal.showValidationMessage(__('start_return_date_required_validation'));
                        return false;
                    }
                    if (!replacement) {
                        Swal.showValidationMessage(__('replacement_person_required_validation'));
                        return false;
                    }
                }
            }
            // No extra validation needed for "Encashed"
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: './includes/ajaxFile/ajaxVacation.php',
                    type: 'POST',
                    dataType: "JSON",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: formData,
                })
                .done(function (response) {
                    Swal.fire({
                        title: response.title,
                        text: response.message,
                        icon: response.type,
                        allowOutsideClick: false
                    }).then(function (isConfirm) {
                        if (isConfirm) location.reload();
                    });
                })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        }

    })
});



// --- Main Script Logic
function add_noties() {
    const empid = $(this).data('emp_id');
    Swal.fire({
        title: __('add_note_to_employee_title'),
        html: add_note_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const note = $('input[name=note]').val();
            // Validation
            if (!note) {
                Swal.showValidationMessage(__('enter_notes_validation'));
                return false;
            }
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    data: { 
                        empid: empid, 
                        note: note, 
                        ajaxType: 'add_note'
                    },
                    dataType: "json"
                })
                .done(response => {
                    Swal.fire({
                        title: response.title,
                        text: response.message,
                        icon: response.type,
                        allowOutsideClick: false
                    }).then(isConfirm => {
                        if (isConfirm) location.reload();
                    });
                })
                .fail(error => {
                    reject(__('failed_to_update_password'));
                    console.error('Error:', error);
                });
            });
        },
        allowOutsideClick: false
    });
}



////////////////////////////////////////////////////////////////////
////////////       End Employee vacation Handling      /////////////
////////////////////////////////////////////////////////////////////

/*:::::::::::::::::::::::::::::::HTML HANDLER::::::::::::::::::::::::::::::*/


function generateLeaveFormHTML() {
    const leaveTypes = [
        'Sick Leave', 'Casual Leave', 'Maternity Leave', 
        'Business Trip', 'Compensatory Leave', 'Other Leave'
    ];

    let leaveOptions = leaveTypes.map(type => `<option value="${type}">${type}</option>`).join('');

    return `
        <form id="applyLeaveForm" class="text-left" enctype="multipart/form-data">
            <div class="form-group">
                <label for="leave_type_select">${__('leave_type')}</label>
                <select id="leave_type_select" name="leave_type" class="form-control" style="width: 100%;">
                    <option value="" selected disabled>${__('select_leave_type_placeholder')}</option>
                    ${leaveOptions}
                </select>
            </div>

            <!-- Dynamic sections that will be shown/hidden -->
            <div id="dateSection" class="d-none">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="start_date">${__('start_date')}</label>
                        <input type="text" name="start_date" id="start_date" class="form-control datepicker" placeholder="YYYY-MM-DD" readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="end_date">${__('end_date')}</label>
                        <input type="text" name="end_date" id="end_date" class="form-control datepicker" placeholder="YYYY-MM-DD" readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="total_days">${__('total_days')}</label>
                        <input type="text" name="total_days" id="total_days" class="form-control" placeholder="${__('auto_calculated_placeholder')}" readonly style="cursor: not-allowed; background-color: #e9ecef;">
                    </div>
                </div>
            </div>

            <div id="tripSection" class="form-group d-none">
                <label for="trip_destination">${__('destination')}</label>
                <input type="text" name="trip_destination" id="trip_destination" class="form-control" placeholder="${__('destination_placeholder')}">
            </div>
            
            <div id="reasonSection" class="form-group d-none">
                <label for="reason">${__('reason_notes')}</label>
                <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="${__('reason_placeholder')}"></textarea>
            </div>

            <div id="attachmentSection" class="form-group d-none">
                <label for="attachment">${__('attach_document_optional')}</label>
                <input type="file" name="attachment" id="attachment" class="form-control-file">
                <small class="form-text text-muted">${__('attachment_example')}</small>
            </div>
        </form>
    `;
}


function item_HTML(sts){
    var statusView = 
    `<div class="form-group col-md-3">
        <label>${__('status')}</label><br>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input" name="itmstatus" id="radio5" value="1">
            <label class="custom-control-label" for="radio5">${__('active')}</label>
        </div>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input " name="itmstatus" id="radio6" value="0">
            <label class="custom-control-label" for="radio6">${__('inactive')}</label>
        </div>
            <!-- <input type="checkbox" name="status" /> -->
    </div>
    `;
    var strView =
    `<form id="submitEditUserForm" enctype="multipart/form-data">
        <div class="form-row customSweetAlertMLR" >
            <div class="form-group col-md-6">
                <label for="name_eng">${__('name_in_english')}</label>
                <input type="text" name="name_eng" id="i_name_eng" class="form-control name_eng">
            </div>
            <div class="form-group col-md-6">
                <label for="name_ar">${__('name_in_arabic')}</label>
                <input type="text" name="name_ar" id="i_name_ar" class="form-control name_ar">
            </div>
            <div class="form-group col-md-6">
                <label for="price_level">${__('select_price_type')}</label>
                <select class="form-control price_level" name="price_level" id="price_level" required="">
                    <option value="">${__('select')}</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="category_id">${__('select_category')}</label>
                <select class="form-control category_id" name="category_id" id="category_id" required="">
                    <option value="">${__('select')}</option>
                </select>
            </div>
        </div>
        <div class="form-row customSweetAlertMLR attachmentDIV noneDIV">
            <div class="form-group col-md-3">
                <label for="big_price">${__('large_price')}</label>
                <input type="text" name="big_price" id="i_big_price" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <label for="small_price">${__('small_price')}</label>
                <input type="text" name="small_price" id="i_small_price" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <label for="big_cal">${__('large_calorie')}</label>
                <input type="text" name="big_cal" id="i_big_cal" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <label for="small_cal">${__('small_calorie')}</label>
                <input type="text" name="small_cal" id="i_small_cal" class="form-control">
            </div>
            <div class="form-group col-md-12">
                <label>${__('select_item_image')}</label>
                <div class="input_container" style="margin-top:0 !important;">
                    <input type="file" id="fileupload" />
                </div>
                <input type="hidden" name="iimage" id="iimage" />
            </div>
            ${(sts == 'edit')? statusView :''}
        </div>

            <input type="hidden" id="itemid" name="itemid">
        </div>
    </form>`;
    return strView;
}

function car_HTML(sts){
    var statusView = 
    `<div class="form-group col-md-3">
        <label>${__('status')}</label><br>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input" name="status" id="radio5" value="1">
            <label class="custom-control-label" for="radio5">${__('active')}</label>
        </div>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input " name="status" id="radio6" value="0">
            <label class="custom-control-label" for="radio6">${__('inactive')}</label>
        </div>
            <!-- <input type="checkbox" name="status" /> -->
    </div>
    `;
    var strView =
    `<form id="submitEditUserForm" enctype="multipart/form-data">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="maker_name">${__('maker_name')}<span class="text-danger">*</span></label>
                        <select class="form-control" name="maker_name" id="maker_name">
                            <option value="">${__('select')}</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="maker_model">${__('maker_name')}<span class="text-danger">*</span></label>
                        <select class="form-control" name="maker_model" id="maker_model">
                            <option value="">${__('select')}</option>
                        </select>
                    </div>
                    
                    <!--<div class="form-group col-md-3">
                        <label for="model">${__('model')}<span class="text-danger">*</span></label>
                        <input type="text" name="model" placeholder="${__('enter_model_placeholder')}" class="form-control" id="model">
                    </div>-->

                    <div class="form-group col-md-3">
                        <label for="made_year" >${__('made_year')}<span class="text-danger">*</span></label>
                        <input type="text" name="made_year" placeholder="${__('enter_made_year_placeholder')}" class="form-control" id="made_year">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="type" >${__('type_of_car')}<span class="text-danger">*</span></label>
                        <select class="form-control" name="type" id="type">
                            <option value="">${__('select')}</option>
                            <option value="Bus">${__('bus')}</option>
                            <option value="Car">${__('car')}</option>
                            <option value="Dyna">${__('dyna')}</option>
                            <option value="Fork Lift">${__('fork_lift')}</option>
                            <option value="Jeep">${__('jeep')}</option>
                            <option value="Pick Up">${__('pick_up')}</option>
                            <option value="Truck">${__('truck')}</option>
                            <option value="Van">${__('van')}</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="plate_no" >${__('plate_no')}<span class="text-danger">*</span></label>
                        <input type="text" name="plate_no" placeholder="1234-ABC" class="form-control" id="plate_no" autocomplete="off" style="text-transform: uppercase !important;" >
                    </div>
                    <div class="form-group col-md-4">
                        <label for="remarks" >${__('remarks')}</label>
                        <input type="text" name="remarks" placeholder="${__('enter_remarks_placeholder')}" class="form-control" id="remarks">
                    </div>
                    ${(sts == 'edit')? statusView :''}
                    <input type="hidden" id="carid" name="carid">
                </div>
            </div>
        </div>
    </form>`;
    return strView;
}

function request_line_HTML(){
    var strView = 
    `<form id="submitEditLineForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-4">
                <label for="item_name">${__('item_name')}</label>
                <input type="text" name="item_name" class="form-control item_name"  >
            </div>
            <div class="form-group col-md-3">
                <label for="location">${__('location')}</label>
                <select id="location" class="form-control" name="location"><option value="">${__('select')}</option></select>
            </div>
            <div class="form-group col-md-1">
                <label for="quantity">${__('quantity')}</label>
                <input type="text" name="quantity" class="form-control quantity" id='quantity'>
            </div>
            <div class="form-group col-md-2">
                <label for="product_price">${__('unit_cost')}</label>
                <input type="text" name="product_price" class="form-control product_price" id='product_price'>
            </div>
            <div class="form-group col-md-2">
                <label for="itmvalue">${__('item_value')}</label>
                <input type='text' id='itmvalue' class="form-control itmvalue" name='itmvalue' readonly />
            </div>
            <div class="form-group col-md-2">
                <label for="vat_option">${__('vat_opt')}</label>
                <select class="form-control vat_option" name="vat_option[]">
                    <option value="include">${__('include_15_percent')}</option>
                    <option value="exclude" selected=selected>${__('exclude_15_percent')}</option>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label for="vat_rate">${__('vat_rate_percent')}</label>
                <input type="text" name="vat_rate" class="form-control vat_rate" id="vat_rate" readonly />
            </div>
            <div class="form-group col-md-2">
                <label for="vat_val">${__('vat_val_percent')}</label>
                <input type='text' class="form-control vat_val" id='vat_val' name='vat_val' readonly />
            </div>
            <div class="form-group col-md-2">
                <label for="amount">${__('amount')}</label>
                <input type='text' class="form-control amount" id='amount' name='amount' readonly />
            </div>
            <div class="form-group col-md-2">
                <label for="idiscount">${__('discount')}</label>
                <input type="text" name="idiscount" class="form-control idiscount" id='idiscount' >
            </div>
            <div class="form-group col-md-2">
                <label for="total_cost">${__('total')}</label>
                <input type='text' class="form-control total_cost" id='total_cost' name='total_cost' readonly />
            </div>
            <input type="hidden" id="itemid" name="itemid">
        </div>
    </form>`;
    return strView;
}

function request_HTML(){
    var strView = 
    `<form id="submitEditRequestForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="item_name">${__('sub_title')}</label>
                <input class="form-control sub_title" type='text' name="sub_title" />
            </div>
            <div class="form-group col-md-6">
                <label for="sub_type">${__('sub_type')}<span class="text-danger">*</span></label>
                <select id="sub_type" class="form-control" name="sub_type" required>
                    <option value="">${__('select')}</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="quantity">${__('tally_id')}</label>
                <input class="form-control tally_id" type='text' name='tally_id'/>
            </div>
            <div class="form-group col-md-6">
                <label for="quantity">${__('injazat_id')}</label>
                 <input class="form-control injazat_id" type='text' name='injazat_id'/>
            </div>
            <div class="form-group col-md-12">
                <label for="quantity">${__('remarks')}</label>
                <input class="form-control remarks" type='text' name="remarks"/>
            </div>
            <input type="hidden" id="reqid" name="reqid">
        </div>
    </form>`;
    return strView;
}

function category_HTML(sts){
    var statusView = 
    `<div class="form-group col-md-6">
        <label>${__('status')}</label><br>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input" name="status" id="radio5" value="1">
            <label class="custom-control-label" for="radio5">${__('active')}</label>
        </div>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input " name="status" id="radio6" value="0">
            <label class="custom-control-label" for="radio6">${__('inactive')}</label>
        </div>
    </div>
    `;
    var strView = 
    `<form id="submitEditCategoryForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="name_eng">${__('name_in_english')}</label>
                <input type="text" name="name_eng" class="form-control name_eng">
            </div>
            <div class="form-group col-md-6">
                <label for="name_ar">${__('name_in_arabic')}</label>
                <input type="text" name="name_ar" class="form-control name_ar">
            </div>
            <div class="form-group col-md-6">
                <label for="desc_eng">${__('description_in_english')}</label>
                <input type="text" name="desc_eng" class="form-control desc_eng">
            </div>
            <div class="form-group col-md-6">
                <label for="desc_ar">${__('description_in_arabic')}</label>
                <input type="text" name="desc_ar" class="form-control desc_ar">
            </div>
            <div class="form-group col-md-12">
                <label for="category_type">${__('category_type')}</label>
                <select class="form-control" name="category_type" id="category_type" class="category_type">
                    <option value="">${__('select')}</option>
                </select>
            </div>
            ${(sts == 'edit')? statusView :''}
            <input type="hidden" class="smid" name="smid">
        </div>
    </form>`;
    return strView;
}

function location_HTML(sts){
    var statusView = 
    `<div class="form-group col-md-3">
        <label>${__('status')}</label><br>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input" name="status" id="radio5" value="1">
            <label class="custom-control-label" for="radio5">${__('active')}</label>
        </div>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input " name="status" id="radio6" value="0">
            <label class="custom-control-label" for="radio6">${__('inactive')}</label>
        </div>
    </div>
    `;
    var strView = 
    `<form id="submitlocationForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-4">
                <label for="section_name">${__('location_name')}<span class="text-danger">*</span></label>
                <input type="text" name="section_name" placeholder="${__('enter_section_name_placeholder')}" class="form-control section_name" >
            </div>
            <div class="form-group col-md-4">
                <label for="latitude">${__('latitude')}<span class="text-danger">*</span></label>
                <input type="text" name="latitude" placeholder="${__('enter_latitude_placeholder')}" class="form-control latitude" >
            </div>
            <div class="form-group col-md-4">
                <label for="longitude">${__('longitude')}<span class="text-danger">*</span></label>
                <input type="text" name="longitude" placeholder="${__('enter_longitude_placeholder')}" class="form-control longitude" >
            </div>
            <div class="form-group col-md-4">
                <label for="b_license_exp_hijri">${__('balady_license_exp')}<span class="text-danger">*</span></label>
                <input type="text" name="b_license_exp" placeholder="${__('enter_balady_license_exp_placeholder')}" class="form-control b_license_exp_hijri" id="b_license_exp_hijri">
            </div>
            <div class="form-group col-md-4">
                <label for="b_license_no">${__('balady_license_no')}<span class="text-danger">*</span></label>
                <input type="text" name="b_license_no" placeholder="${__('enter_balady_license_no_placeholder')}" class="form-control b_license_no" >
            </div>                      
            <div class="form-group col-md-4">
                <label for="dept">${__('select_department')}<span class="text-danger">*</span></label>
                <select class="form-control" name="dept" id="dept">
                    <option value="">${__('select')}</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="camera_in">${__('camera_in')}</label>
                <input type="text" name="camera_in" placeholder="${__('enter_camera_in_placeholder')}" class="form-control camera_in" >
            </div>
            <div class="form-group col-md-4">
                <label for="camera_out">${__('camera_out')}</label>
                <input type="text" name="camera_out" placeholder="${__('enter_camera_out_placeholder')}" class="form-control camera_out" >
            </div>
            <div class="form-group col-md-4">
                <label for="t_bulding_size">${__('total_building_size_m')}</label>
                <input type="text" name="t_bulding_size" placeholder="${__('enter_total_building_size_placeholder')}" class="form-control t_bulding_size">
            </div>
            <div class="form-group col-md-4">
                <label for="bulding_base">${__('building_base')}</label>
                <input type="text" name="bulding_base" placeholder="${__('enter_building_base_placeholder')}" class="form-control bulding_base" >
            </div>
            <div class="form-group col-md-4">
                <label for="bulding_size">${__('building_size_l_w')}</label>
                <input type="text" name="bulding_size" placeholder="${__('enter_building_size_l_w_placeholder')}" class="form-control bulding_size" >
            </div>                    
            <div class="form-group col-md-4">
                <label for="location_dist">${__('district')}<span class="text-danger">*</span></label>
                <input type="text" name="location_dist" placeholder="${__('enter_district_placeholder')}" class="form-control location_dist" >
            </div>                      
            <div class="form-group col-md-4">
                <label for="municipality">${__('municipality')}</label>
                <input type="text" name="municipality" placeholder="${__('enter_municipality_placeholder')}" class="form-control municipality" >
            </div>                      
            <div class="form-group col-md-4">
                <label for="sub_municipality">${__('sub_municipality')}</label>
                <input type="text" name="sub_municipality" placeholder="${__('enter_sub_municipality_placeholder')}" class="form-control "sub_municipality>
            </div>
            <div class="form-group col-md-12">
                <label for="loc_address">${__('location_address')}</label>
                <input type="text" name="loc_address" placeholder="${__('enter_location_address_placeholder')}" class="form-control loc_address">
            </div>
            ${(sts == 'edit')? statusView :''}
            <input type="hidden" class="smid" name="smid">
        </div>
    </form>`;
    return strView;
}

function maintenance_HTML(){
    var strView = 
    `<form id="submitMaintenanceForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="car_user">${__('select_driver')}<span class="text-danger">*</span></label>
                <select class="form-control" name="car_user" id="car_user">
                    <option value="">${__('select')}</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="name_eng">${__('select_date')}</label>
                <input type="text" name="date" class="form-control" id="date">
            </div>
            <div class="form-group col-md-3">
                <label for="meter">${__('new_meter_reading')}</label>
                <input type="text" name="meter" class="form-control meter" placeholder="12345678">
            </div>
            <div class="form-group col-md-3">
                <label for="oldmeter">${__('old_meter_reading')}</label>
                <input type="text" name="oldmeter" readonly class="form-control oldmeter" id="oldmeter" value="">
            </div>
            <div class="form-group col-md-3">
                <label for="diffmeter">${__('diff_meter_reading')}</label>
                <input type="text" name="diffmeter" readonly class="form-control diffmeter" id="diffmeter">
            </div>
            <div class="form-group col-md-2">
                <label for="type">${__('select_type')}<span class="text-danger">*</span></label>
                <select class="form-control" name="type" id="type">
                    <option value="">${__('select')}</option>
                </select>
            </div>
            <div class="form-group col-md-1">
                <label for="type">${__('add')}</label>
                <a href="javascript:void(0);" class="form-control btn btn-success btn-small addTypeAtter" id="addTypeAtter">
                    <i class="fa fa-plus"></i>
                </a>
            </div>
            <div class="form-group col-md-6">
                <label for="details">${__('description_for_maintenance')}</label>
                <input type="text" name="details" class="form-control details">
            </div>
            <div class="form-group col-md-6">
                <label for="remarks">${__('remarks')}</label>
                <input type="text" name="remarks" class="form-control remarks">
            </div>
            <input type="hidden" class="cid" name="cid">
        </div>
    </form>`;
    return strView;
}

function addType_HTML(){
    var strView = 
    `<form id="submitEditCategoryForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <label for="name_eng">${__('type_name')}<span class="text-danger">*</span></label>
                <input type="text" name="type" class="form-control">
            </div>
        </div>
    </form>`;
    return strView;
}

function documents_HTML(){
    var strView = 
    `<form id="submitDocumentsForm" enctype="multipart/form-data">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-4">
                <label for="doc_type">${__('type_of_document')}<span class="text-danger">*</span></label>
                <select class="form-control" name="doc_type" id="doc_type">
                    <option value="">${__('select')}</option>
                    <option value="Licence">${__('licence')}</option>
                    <option value="Insurance">${__('insurance')}</option>
                    <option value="MVPI">${__('mvpi')}</option>
                </select>
            </div>
            <div class="form-group col-md-8 input-daterange" id="date_select">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="issue_date">${__('issue_date')}<span class="text-danger">*</span></label>
                        <input type="text" name="issue_date" placeholder="${__('select_issue_date_placeholder')}" class="form-control" id="issue_date">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="exp_date">${__('expiry_date')}<span class="text-danger">*</span></label>
                        <input type="text" name="exp_date" required placeholder="${__('select_expiry_date_placeholder')}" class="form-control" id="exp_date">
                    </div>
                </div>
            </div>
            <div class="form-group col-md-12">
                <label class="radioalign">${__('attachment')}<span class="text-danger">*</span></label>
                
                <div class="radio radio-info form-check-inline">
                    <input type="radio" id="inlineRadio3" value="yes" name="attach" class="showAttachment">
                    <label for="inlineRadio3" class="atch"><i class="mdi mdi-paperclip"></i> ${__('have_attachments')}</label>
                </div>

                <div class="radio radio-info form-check-inline">
                    <input type="radio" id="inlineRadio2" value="no" name="attach" class="hideAttachment">
                    <label for="inlineRadio2" class="atch"><i class="mdi mdi-clippy"></i> ${__('no_attachment')}</label>
                </div>

                <!--<label class="noneDIV attachmentDIV" for="checkatt">${__('browse_files')}</label>-->
                <div class="input_container noneDIV attachmentDIV">
                    <input type="file" id="checkatt" class="checkatt">
                </div>
            </div>
            <input type="hidden" class="cid" name="cid">
        </div>
    </form>`;
    return strView;
}

function driver_HTML(){
    var strView = 
    `<form id="submitDriverForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="doc_type">${__('select_driver_name')}<span class="text-danger">*</span></label>
                <select class="form-control" name="car_user" id="car_user">
                    <option>${__('select')}</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="name_eng">${__('select_date')}</label>
                <input type="text" name="rcv_date" class="form-control" id="date">
            </div>
            <input type="hidden" class="cid" name="cid">
        </div>
    </form>`;
    return strView;
}

function customer_HTML(){
    var strView = 
    `<form id="submitCustomerForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-4">
                <label for="full_name">${__('customer_name')}<span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" id="full_name" autocomplete="off" style="text-transform: uppercase !important;" >
            </div>
            <div class="form-group col-md-4">
                <label for="injazat_no">${__('injazat_no')}<span class="text-danger">*</span></label>
                <input type="text" name="injazat_no" data-v-max="999999" data-v-min="0" parsley-trigger="change" class="form-control autonumber" id="injazat_no" autocomplete="off">
            </div>
            <div class="form-group col-md-4">
                <label for="mobile">${__('mobile_no')}<span class="text-danger">*</span></label>
                <input type="text" name="mobile" data-mask="0599999999" parsley-trigger="change" class="form-control" id="mobile" autocomplete="off">
            </div>
            <div class="form-group col-md-4">
                <label for="acc_no">${__('account_no')}<span class="text-danger">*</span></label>
                <input type="text" name="acc_no" parsley-trigger="change" class="form-control" id="acc_no" autocomplete="off" style="text-transform: uppercase !important;" >
            </div>
            <div class="form-group col-md-4">
                <label for="card_exp">${__('card_expire')}<span class="text-danger">*</span></label>
                <input type="text" name="card_exp" parsley-trigger="change" class="form-control" id="card_exp" autocomplete="off">
            </div>
            <div class="form-group col-md-4">
                <label for="location">${__('for_shop')}<span class="text-danger">*</span></label>
                <select class="form-control" name="location" id="location">
                    <option value="">${__('select')}</option>
                </select>
            </div>
            <input type="hidden" name="id">
        </div>
    </form>`;
    return strView;
}

function cust_upd_HTML(){
    var strView = 
    `<form id="submitCustomerCardUpdForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="card_exp">${__('card_expire')}</label>
                <input type="text" name="card_exp" parsley-trigger="change" class="form-control" autocomplete="off" id="card_exp">
            </div>
            <div class="form-group col-md-6">
                <label for="location">${__('for_shop')}</label>
                <select class="form-control" name="location" id="location">
                    <option value="">${__('select')}</option>
                </select>
            </div>
        </div>
        <input type="hidden" name="id">
        <input type="hidden" name="injazat_no">
    </form>`;
    return strView;
}

function cust_add_HTML(){
    var strView = 
    `<form id="submitCustomerCardAddForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="injazat_no">${__('new_injazat_no')}</label>
                <input type="text" name="injazat_no" data-v-max="999999" data-v-min="0" parsley-trigger="change" class="form-control" autocomplete="off">
            </div>
            <div class="form-group col-md-6">
                <label for="acc_no">${__('account_no')}</label>
                <input type="text" name="acc_no" parsley-trigger="change" class="form-control" >
            </div>
            <div class="form-group col-md-6">
                <label for="card_exp">${__('card_expire')}</label>
                <input type="text" name="card_exp" parsley-trigger="change" class="form-control"autocomplete="off" id="card_exp">
            </div>
            <div class="form-group col-md-6">
                <label for="location">${__('for_shop')}</label>
                <select class="form-control" name="location" id="location">
                    <option value="">${__('select')}</option>
                </select>
            </div>
        </div>
        <input type="hidden" name="id">
    </form>`;
    return strView;
}

function loc_contract_HTML(){
    var strView = 
    `<form id="submitlocationContractForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-3">
                <label for="owner_name">${__('location_owner_name')}<span class="text-danger">*</span></label>
                <input type="text" name="owner_name" placeholder="${__('enter_owner_name_placeholder')}" class="form-control" id="owner_name" >
            </div>
            <div class="form-group col-md-3">
                <label for="owner_number">${__('owner_number')}<span class="text-danger">*</span></label>
                <input type="text" name="owner_number" placeholder="${__('enter_owner_number_placeholder')}" class="form-control" id="owner_number" parsley-trigger="change" data-mask="0599999999" >
            </div>
            <div class="form-group col-md-3">
                <label for="owner_email">${__('owner_email')}<span class="text-danger">*</span></label>
                <input type="text" name="owner_email" placeholder="${__('enter_owner_email_placeholder')}" class="form-control" id="owner_email" autocomplete="off" >
            </div>
            <div class="form-group col-md-3">
                <label for="contract_no">${__('contract_no')}<span class="text-danger">*</span></label>
                <input type="text" name="contract_no" placeholder="${__('enter_contract_no_placeholder')}" class="form-control" id="contract_no" autocomplete="off">
            </div>
            <div class="form-group col-md-3">
                <label for="start_cont_date">${__('contract_starting_date')}<span class="text-danger">*</span></label>
                <input type="text" name="start_cont_date" placeholder="${__('enter_contract_start_date_placeholder')}" class="form-control" id="start_cont_date"  autocomplete="off" required>
            </div>
            <div class="form-group col-md-3">
                <label for="end_cont_date">${__('contract_ending_date')}<span class="text-danger">*</span></label>
                <input type="text" name="end_cont_date" placeholder="${__('enter_contract_ending_date_placeholder')}" class="form-control" id="end_cont_date"  autocomplete="off" required>
            </div>
            <div class="form-group col-md-3">
                <label for="rent">${__('amount_of_rent')}<span class="text-danger">*</span></label>
                <input type="text" name="rent" placeholder="${__('enter_amount_of_rent_placeholder')}" class="form-control autonumber" id="rent" autocomplete="off" required>
            </div>
            <div class="form-group col-md-3">
                <label for="service">${__('amount_of_services')}</label>
                <input type="text" name="service" placeholder="${__('enter_amount_of_services_placeholder')}" class="form-control autonumber" id="service" autocomplete="off">
            </div>                    
            <div class="form-group col-md-3">
                <label for="elect_prc">${__('amount_of_electricity')}</label>
                <input type="text" name="elect_prc" placeholder="${__('enter_amount_of_electricity_placeholder')}" class="form-control autonumber" id="elect_prc" autocomplete="off" >
            </div>                      
            <div class="form-group col-md-3">
                <label for="water_prc">${__('amount_of_water')}</label>
                <input type="text" name="water_prc" placeholder="${__('enter_amount_of_water_placeholder')}" class="form-control autonumber" id="water_prc" autocomplete="off">
            </div>                      
            <div class="form-group col-md-3">
                <label for="incuranse_prc">${__('amount_of_insurance')}<span class="text-danger">*</span></label>
                <input type="text" name="incuranse_prc" placeholder="${__('enter_amount_of_insurance_placeholder')}" class="form-control autonumber" id="incuranse_prc" autocomplete="off">
            </div>                      
            <div class="form-group col-md-3">
                <label for="others">${__('others')}</label>
                <input type="text" name="others" placeholder="${__('enter_others_placeholder')}" class="form-control autonumber" id="others" autocomplete="off" >
            </div> 
        </div>
        <input type="hidden" name="locid">
    </form>`;
    return strView;
}

function edit_password_HTML(){
    var strView =
    `<form class="contact-input" id="validatedForm" class="submitEditUserPassForm">
        <div class="modal-body">
            <div class="form-row">
            <div class="form-group col-md-6">
                <label for="name">${__('enter_new_password')}</label>
                <input type="password" id="password" name="password" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="name">${__('confirm_password')}</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control">
            </div>
        </div>
    </form>
    `;
    return strView;
}


function create_user_HTML() {
    // This function now returns only the form elements, without any custom error containers.
    return `
    <form class="contact-input" id="createUserForm" style="text-align: left;">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="email">${__('email')}<span class="text-danger">*</span></label>
                    <input type="text" id="email" name="email" class="form-control email-validation">
                </div>
                <div class="form-group col-md-12">
                    <label for="user_type">${__('employee_type')}<span class="text-danger">*</span></label>
                    <select id="user_type" name="user_type" class="form-control">
                        <option value="">${__('select_type')}</option>
                        <option value="Manager">${__('manager')}</option>
                        <option value="Assistant">${__('assistant')}</option>
                    </select>
                </div>
            </div>
        </div>
    </form>`;
}


function edit_user_HTML(){
    var strView =
    `<form id="submitEditUserForm">
    <div class="form-row customSweetAlertMLR">
        <div class="form-row customSweetAlertMLR">
        <div class="form-group col-md-4">
            <label for="name">${__('full_name')}</label>
            <input type="text" id="fullname" name="fullname" class="form-control">
        </div>
        <div class="form-group col-md-4">
            <label for="name">${__('username')}</label>
            <input type="text" id="username" name="username" class="form-control">
        </div>
        <div class="form-group col-md-4">
            <label for="name">${__('department')}</label>
            <input type="text" id="dept" name="dept" class="form-control" readonly="">
        </div>
        <div class="form-group col-md-4">
            <label for="name">${__('type_of_permission')}</label>
            <select class="custom-select" name="user_type" id="user_type" required="">
                <option value="administrator">${__('administrator')}</option>
                <option value="dept_user">${__('department_manager')}</option>
                <option value="assistant">${__('assistant')}</option>
                <option value="employee">${__('employee')}</option>
                <option value="gm">${__('general_manager')}</option>
                <option value="hr">${__('human_resource')}</option>
            </select>
        </div>
        <div class="form-group col-md-4">
            <label for="name">${__('email')}</label>
            <input type="text" id="email" name="email" class="form-control">
        </div>
        <div class="form-group col-md-4">
            <label for="name">${__('email_password')}</label>
            <input type="text" id="email_pass" name="email_pass" class="form-control">
        </div>
        <div class="form-group col-md-4">
            <label for="name">${__('mobile')}</label>
            <input type="text" id="mobile" name="mobile" class="form-control">
        </div>
        <div class="form-group col-md-4">
            <label for="name">${__('changing_password')}</label>
            <a href="javascript:void:(0);" class="btn bt-sm btn-warning updatePasswordAjax" id="idpass" >${__('update_password')}</a>
        </div>
        <div class="form-group col-md-4">
            <br><br>
            <div class="d-inline-block custom-control custom-radio mr-1">
                <input type="radio" class="custom-control-input" name="status" id="radio1" value="1">
                <label class="custom-control-label" for="radio1">${__('active')}</label>
            </div>
            <div class="d-inline-block custom-control custom-radio mr-1">
                <input type="radio" class="custom-control-input" name="status" id="radio2" value="0">
                <label class="custom-control-label" for="radio2">${__('inactive')}</label>
            </div>
        </div>
    <input type="hidden" id="iduser" name="id"></div>
    <input type="hidden" id="oldpass" name="oldpass"></div></form>
    `;
    return strView;
}

function endOfService_HTML(){
    var strView =
    `<form id="calculatorForm">
            <h1><p value="0" id="resultCalc">0</p></h1>
            <!--<input type="text" class="form-control" id="resultCalc">-->
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-lg-6 col-sm-12">
                <label>${__('type_of_contract')}<span class="text-danger">*</span></label>
                <select id="inputPeriod" required class="form-control" >
                    <option selected value="">${__('select_type')}</option>
                    <option value="47">${__('fixed_time')}</option>
                    <option value="48">${__('unlimited_period')}</option>
                </select>
            </div>
            <div class="form-group col-lg-6 col-sm-12">
                <label>${__('end_of_service_reason')}<span class="text-danger">*</span></label>
                <select id="inputState" required class="form-control">
                    <option selected value="">${__('select_reason')}</option>
                </select>
            </div>
            <div class="form-group col-md-8" id="event_period">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="joining_date">${__('joining_date')}<span class="text-danger">*</span></label>
                        <input type="text" name="joining_date" placeholder="${__('select_join_date_placeholder')}" class="form-control" id="joining_date">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="end_date">${__('end_of_service_date')}<span class="text-danger">*</span></label>
                        <input type="text" name="end_date" required placeholder="${__('select_end_date_placeholder')}" class="form-control" id="end_date">
                    </div>
                </div>
            </div>
            <div class="form-group col-lg-4">
                <label>${__('salary')}</label>
                <input type="text" required class="form-control" id="salary" name="salary" readonly>
            </div>
            <div class="form-group col-lg-4">
                <label>${__('duration_of_service_years')}</label>
                <input type="text" class="form-control" id="yearsPeriod" readonly>
            </div>
        
            <div class="form-group col-lg-4">
                <label>${__('number_of_months')}</label>
                <input type="text" class="form-control" id="monthsPeriod" readonly>
            </div>
            <div class="form-group col-lg-4">
                <label>${__('number_of_days')}</label>
                <input type="text" class="form-control" id="daysPeriod" readonly>
                <input type="hidden" id="finalAmount" readonly>
            </div>

        </div>
    <input type="hidden" id="empid" name="empid"></div>
    </form>
    `;
    return strView;
}

/*function eosReportPrint(name,email,idiqama,idiqamaexpiry,passport,passportexpiry,dob,age,gender,mstatus,mobile,country,joining_date,dept,sectin_nme,salary,address,status, yearsPeriod, monthsPeriod, daysPeriod, finalAmount){
    var htmlRpt = `
        <div class="row">
            <div class="col-12">
                <div class="card-box">
                    <table class="table table-hover mb-0" style="width: 100%">
                        <thead class="thead-dark">
                            <tr>
                                <th colspan="4">
                                    <center>
                                        <h1>${finalAmount.value}</h1>
                                        <h2>ﺔﻴﺋﺎﻬﻧ ﺔﺼﻟﺎﺨﻣ</h2>
                                        <h2>FINAL SETTLEMENT</h2>
                                    </center>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>${__('name_of_employee')}</th>
                                <td>${name}</td>
                                <th>${__('email_address')}</th>
                                <td>${email}</td>
                            </tr>
                            <tr>
                                <th>${__('iqama_id')}</th>
                                <td>${idiqama}</td>
                                <th>${__('iqama_id_expiry')}</th>
                                <td>${idiqamaexpiry}</td>
                            </tr>
                            <tr>
                                <th>${__('passport_no')}</th>
                                <td>${passport}</td>
                                <th>${__('passport_expiry')}</th>
                                <td>${passportexpiry}</td>
                            </tr>
                                <tr>
                                <th>${__('date_of_birth')}</th>
                                <td>${dob}</td>
                                <th>${__('age')}</th>
                                <td>${age}</td>
                            </tr>
                            <tr>
                                <th>${__('gender')}</th>
                                <td>${gender}</td>
                                <th>${__('marital_status')}</th>
                                <td>${mstatus}</td>
                            </tr>
                            <tr>
                                <th>${__('mobile_no')}</th>
                                <td>${mobile}</td>
                                <th>${__('country')}</th>
                                <td>${country}</td>
                            </tr>
                            <tr>
                                <th>${__('date_hired')}</th>
                                <td>${joining_date}</td>
                                <th>${__('department')}</th>
                                <td>${dept}</td>
                            </tr>
                            <tr>
                                <th>${__('section_area')}</th>
                                <td>${sectin_nme}</td>
                                <th>${__('current_salary')}</th>
                                <td>${salary}</td>
                            </tr>
                            <tr>
                                <th>${__('address')}</th>
                                <td colspan="3">${address}</td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table table-hover mb-0" style="width: 100%">
                        <tbody>
                            <tr>
                                <th>${__('years')}</th>
                                <th>${__('months')}</th>
                                <th>${__('days')}</th>
                            </tr>
                            <tr>
                                <td>${yearsPeriod.value}</td>
                                <td>${monthsPeriod.value}</td>
                                <td>${daysPeriod.value}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    `
    return htmlRpt;
}*/ // Not Used

function social_add_HTML(){
    var strView = 
    `<form id="submitCustomerCardAddForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="link">${__('add_link_address')}</label>
                <input type="text" name="link" class="form-control" >
            </div>
            <div class="form-group col-md-6">
                <label for="social_link">${__('for_shop')}</label>
                <select class="form-control" name="social_id" id="social_link">
                    <option value="">${__('select')}</option>
                </select>
            </div>
        </div>
        <input type="hidden" name="emp_id">
    </form>`;
    return strView;
}

function portfolio_add_HTML(){
    var strView = 
    `<form id="submitCustomerCardAddForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="link">${__('add_portfolio_title')}<span class="text-danger">*</span></label>
                <input type="text" id="title" class="form-control" >
            </div>
            <div class="form-group col-md-6">
                <label for="link">${__('select_attachment_file')}<span class="text-danger">*</span></label>
                <div class="input_container" style="margin-top:0 !important">
                    <input type="file" id="fileupload" accept="image/*, application/pdf">   
                </div>
            </div>
            <div class="form-group col-md-12">
                <label for="link">${__('description_of_portfolio')}<span class="text-danger">*</span></label>
                <div id="inlineeditor"></div>
            </div>
        </div>
        <input type="hidden" name="emp_id">
    </form>`;
    return strView;
}

function id_exp_HTML(){
    var strView =
    `<form class="contact-input" id="submitEditEmployeeIDForm">
        <div class="modal-body">
            <div class="form-row">
                <div class="col-md-12">
                    <label for="inlineRadio" class="col-form-label radioalign">${__('select_date_type')}<span class="text-danger">*</span></label>
                    <div class="d-inline-block custom-control custom-radio mr-1">
                        <input type="radio" class="custom-control-input" id="hijri" value="hijri" name="note">
                        <label class="custom-control-label" for="hijri" style="cursor:pointer">${__('hijri_date')}</label>
                    </div>
                    <div class="d-inline-block custom-control custom-radio mr-1">
                        <input type="radio" class="custom-control-input" id="gregorian" value="gregorian" name="note">
                        <label class="custom-control-label" for="gregorian" style="cursor:pointer">${__('gregorian_date')}</label>
                    </div>
                    <div class="form-group col-md-12" id="hijriDiv" style="display:none;">
                        <input type="text" class="form-control mt-2" id="iq_id_exp_hijri" readonly="readonly">
                        <input type="hidden" id="emid" name="id" class="form-control">
                    </div>
                    <div class="form-group col-md-12" id="gregorianDiv" style="display:none;">
                        <input type="text" class="form-control mt-2" id="iq_id_exp_greg" readonly="readonly">
                        <input type="hidden" id="emid" name="id" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </form>
    `;
    return strView;
}

function empDocuments_HTML(){
    var strView = 
    `<form id="submitDocumentsForm" enctype="multipart/form-data">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <label for="docu_typ">${__('type_of_document')}<span class="text-danger">*</span></label>
                <select class="form-control" name="docu_typ" id="docu_typ">
                    <option value="">${__('select')}</option>
                </select>
            </div>
            <div class="form-group col-md-12">
                <label for="checkatt">${__('attachment')}<span class="text-danger">*</span></label>
                <div class="input_container" style="margin-top: 0 !important">
                    <input type="file" id="checkatt" class="checkatt">
                </div>
            </div>
            <input type="hidden" class="id" name="id">
            <input type="hidden" class="emp_id" name="emp_id">
        </div>
    </form>`;
    return strView;
}

function Voucher_HTML(){
    var strView = 
    `<form id="submitVoucherForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="emp_v_user">${__('select_employee')}<span class="text-danger">*</span></label>
                <select class="form-control" name="emp_v_user" id="emp_v_user">
                    <option value="">${__('select')}</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="voucher_type">${__('select_voucher')}<span class="text-danger">*</span></label>
                <select class="form-control" name="voucher_type" id="voucher_type">
                    <option value="">${__('select')}</option>
                    <option value="receipt">${__('payment_receipt')}</option>
                    <option value="payment">${__('payment_voucher')}</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount">${__('amount')}<span class="text-danger">*</span></label>
                <input type="text" name="amount" id="amount" class="form-control amount" placeholder="12345678" onkeypress="return isNumber(event)">
            </div>
            <div class="form-group col-md-6">
                <label for="details">${__('details')}<span class="text-danger">*</span></label>
                <input type="text" name="details" class="form-control details" id="details" value="">
            </div>
            <div class="form-group col-md-6">
                <label for="acc_no">${__('account_no')}</label>
                <input type="text" name="acc_no" class="form-control acc_no" id="acc_no">
            </div>
            <div class="form-group col-md-6">
                <label for="chq_no">${__('cheque_no')}</label>
                <input type="text" name="chq_no" class="form-control chq_no" id="chq_no">
            </div>
            <div class="form-group col-md-12">
                <label class="radioalign">${__('attachment')}<span class="text-danger">*</span></label>
                
                <div class="radio radio-info form-check-inline">
                    <input type="radio" id="inlineRadio3" value="yes" name="attach" class="showAttachment">
                    <label for="inlineRadio3" class="atch"><i class="mdi mdi-paperclip"></i> ${__('have_attachments')}</label>
                </div>

                <div class="radio radio-info form-check-inline">
                    <input type="radio" id="inlineRadio2" value="no" name="attach" class="hideAttachment">
                    <label for="inlineRadio2" class="atch"><i class="mdi mdi-clippy"></i> ${__('no_attachment')}</label>
                </div>

                <!--<label class="noneDIV attachmentDIV" for="checkatt">${__('browse_files')}</label>-->
                <div class="input_container noneDIV attachmentDIV">
                    <input type="file" id="checkatt" class="checkatt">
                </div>
            </div>
            <input type="hidden" class="empid" name="empid" id="empid">
        </div>
    </form>`;
    return strView;
}

function addCarModel_HTML(){
    var strView = 
    `<form id="submitEditCategoryForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <label for="name_eng">${__('car_model_name')}<span class="text-danger">*</span></label>
                <input type="text" name="maker_model" class="form-control">
                <input type="hidden" name="maker_name" class="form-control">
            </div>
        </div>
    </form>`;
    return strView;
}

function contant_chk_HTML() {
    return `
        <form id="submitEmployeeTempContantForm" method="post" class="text-start">
            <input type="hidden" name="id" value="">
            <input type="hidden" name="empid" value="">
            <input type="hidden" name="type" value="">
            <input type="hidden" name="path" value="">
            <input type="hidden" name="new_value" value="">
            <input type="hidden" id="reqchk" value="">

            <!-- This div will display the details of the change request -->
            <div id="conView" class="mb-3 p-3 border rounded bg-light"></div>

            <div class="form-group mb-3">
                <label for="contant_check" class="form-label"><strong>${__('action')}<span class="text-danger">*</span></strong></label>
                <select name="contant_check" class="form-select contant_check" id="contant_check">
                    <option value="">${__('select_action')}</option>
                    <option value="approve">${__('approve_request')}</option>
                    <option value="not_approve">${__('reject_request')}</option>
                </select>
            </div>

            <!-- Notes for Approval (Optional) -->
            <div class="form-group" id="approved" style="display:none;">
                <label for="notesa" class="form-label">${__('approval_notes')}</label>
                <textarea id="notesa" class="form-control" placeholder="${__('optional_notes_placeholder')}"></textarea>
            </div>

            <!-- Reason for Rejection (Required) -->
            <div class="form-group" id="notapprove" style="display:none;">
                <label for="notesna" class="form-label"><strong>${__('rejection_reason')}<span class="text-danger">*</span></strong></label>
                <textarea id="notesna" class="form-control" placeholder="${__('provide_rejection_reason_placeholder')}"></textarea>
            </div>
        </form>
    `;
}

function edit_emp_chk_HTML(){
    var strView = 
    `<form id="submitEmployeeTempContantForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <label for="edit_contant_check">${__('please_select')}<span class="text-danger">*</span></label>
                <select class="form-control edit_contant_check" name="edit_contant_check" id="edit_contant_check">
                    <option value="">${__('select_from_list')}</option>
                    <option value="iqama_exp">${__('iqama_expiry')}</option>
                    <option value="mobile">${__('mobile')}</option>
                    <option value="emg_mobile">${__('emergency_contact')}</option>
                    <option value="t_shirt_size">${__('t_shirt_size')}</option>
                    <option value="iban">${__('bank_account')}</option>
                    <option value="email">${__('email')}</option>
                    <option value="address">${__('address')}</option>
                </select>
            </div>
            <div class="form-group col-md-12">
                <div style="display: none;" id="notapprove">
                    <label for="field_text" class="label_text"></label>
                    <!-- <input type="text" class="form-control" id="field_text" />-->
                    <div id="field_text"></div>
                </div>
                <input type="hidden" readonly name="empid" />
                <input type="hidden" readonly name="id" />
            </div>
        </div>
    </form>`;
    return strView;
}

function addRejNote_HTML(){
    var strView = 
    `<form id="submitEditCategoryForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <label for="name_eng">${__('note')}<span class="text-danger">*</span></label>
                <input type="text" name="note" id="rejectnote" class="form-control">
                <input type="hidden" name="srno" class="form-control">
                <input type="hidden" name="status" value="reject" class="form-control">
            </div>
        </div>
    </form>`;
    return strView;
}

function add_inv_mont_HTML(){
    var strView =
    `<form id="submitEditCategoryForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <label for="name_eng">${__('total_invoices_amount')}<span class="text-danger">*</span></label>
                <input type="text" name="amount" class="form-control autonumber">
                <input type="hidden" name="srno">
            </div>
        </div>
    </form>
    `;
    return strView;
}

function approv_inv_mont_HTML(){
    var strView =
    `<form id="submitApprovAmountForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <label for="name_eng">${__('approve_total_invoices_amount')}<span class="text-danger">*</span></label>
                <input type="text" name="amount" class="form-control autonumber">
                <input type="hidden" name="srno">
            </div>
        </div>
    </form>
    `;
    return strView;
}

function vacationApply_HTML(country) {
    var strView = 
    `<form id="submitVacationApplyForm" enctype="multipart/form-data">
    <div class="row customSweetAlertMLR">

        <div class="form-group col-md-9">
            <label for="name">${__('employee_name')}</label>
            <input type="text" class="form-control" name="name" id="name" readonly>
        </div>
        <div class="form-group col-md-3">
            <label for="emp_id">${__('employee_id')}</label>
            <input type="text" class="form-control" name="empid" id="empid" readonly>
        </div>

        <div class="form-group col-md-12">
            <label for="inlineRadio3" class="radioalign">${__('remarks')}<span class="text-danger">*</span></label>
            <div class="radio radio-info form-check-inline mt-3">
                <input type="radio" id="inlineRadio3" value="Local Vacation" name="vac_type">
                <label for="inlineRadio3" class="atch"><i class="fa fa-odnoklassniki"></i> ${__('local_vacation')}</label>
             </div>`;
             
                if (country != 191 && country != 150) {
                    strView += `<div class="radio radio-info form-check-inline">
                        <input type="radio" id="inlineRadio1" value="Fly" name="vac_type">
                        <label for="inlineRadio1" class="atch"><i class="mdi mdi-airplane-takeoff"></i> ${__('fly')}</label>
                    </div>`;
                }

                strView += `
            <div class="radio radio-info form-check-inline">
                <input type="radio" id="inlineRadio2" value="Encashed" name="vac_type">
                <label for="inlineRadio2" class="atch"><i class="mdi mdi-square-inc-cash"></i> ${__('encashed')}</label>
            </div>
        </div>

        <div class="col-md-12 d-none" id="flyTypeSection">
            <div class="form-group">
                <label for="type" class="radioalign">${__('select_vacation_type')}<span class="text-danger">*</span></label>
                <div class="radio radio-info form-check-inline">
                    <input type="radio" id="vac_type1" value="annual" name="fly_type">
                    <label for="vac_type1" class="atch"><i class="mdi mdi-all-inclusive"></i> ${__('annual_vacation')}</label>
                </div>
                <div class="radio radio-info form-check-inline">
                    <input type="radio" id="vac_type2" value="emergency" name="fly_type">
                    <label for="vac_type2" class="atch"><i class="mdi mdi-chemical-weapon"></i> ${__('emergency_vacation')}</label>
                </div>
            </div>
        </div>

        <div class="form-group col-md-12 input-daterange d-none" id="date_select">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="start_date">${__('start_date')}<span class="text-danger">*</span></label>
                    <input type="text" name="start_date" placeholder="${__('select_start_date_placeholder')}" class="form-control" id="start_date">
                </div>
                <div class="form-group col-md-6">
                    <label for="end_date">${__('return_date')}<span class="text-danger">*</span></label>
                    <input type="text" name="end_date" required placeholder="${__('select_return_date_placeholder')}" class="form-control" id="end_date">
                </div>
            </div>
        </div>

        <div class="col-md-12 d-none" id="replacementSection">
            <div class="form-group">
                <label for="replacement_per">${__('replacement_person')}<span class="text-danger">*</span></label>
                <select class="form-control" name="replacement_per" id="replacement_per">
                    <option value="">${__('select')}</option>
                </select>
            </div>
        </div>

        <div class="col-md-12 d-none" id="notesSection">
             <div class="form-group">
                <label for="remarks">${__('notes')}<span class="text-danger">*</span></label>
                <input type="text" name="remarks" parsley-trigger="change" class="form-control" id="remarks" autocomplete="off">
            </div>
        </div>

        <input type="hidden" class="cid" name="cid">
    </div>
</form>`;
    return strView;
}


function eos_select_date_HTML(){
    var strView =
    `<form id="submitEditCategoryForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <label for="eos_date">${__('select_date_for_eos')}<span class="text-danger">*</span></label>
                <input type="text" name="eos_date" class="form-control" id="eos_date">
            </div>
        </div>
    </form>
    `;
    return strView;
}


function add_note_HTML(){
    var strView =
    `<form class="contact-input" id="validatedForm" class="submitEditUserPassForm">
        <div class="modal-body">
            <div class="form-row">
            <div class="form-group col-md-12">
                <label for="name">${__('enter_note')}</label>
                <input type="text" id="note" name="note" class="form-control">
            </div>
        </div>
    </form>
    `;
    return strView;
}

/*:::::::::::::::::::::::::::::::HTML HANDLER::::::::::::::::::::::::::::::*/

String.prototype.toArabicNumber = function() {
    var id = ['۰', '١', '٢', '۳', '٤', '٥', '٦', '۷', '۸', '۹'];
    return this.replace(/[0-9]/g, function(w) {
        return id[+w]
    });
}
String.prototype.toArabicDigits =  function () {
    /*var en = ['a', 'b', 'j', 'd', 'r', 's', 'x', 't', 'e', 'g', 'k', 'l', 'z', 'n', 'h', 'u', 'v'];
    var ar = ['ا', 'ﺏ', 'ﺡ', 'ﺩ', 'ﺭ', 'ﺱ', 'ﺹ', 'ﻁ', 'ﻉ', 'ﻕ', 'ﻙ', 'ﻝ', 'ﻡ', 'ﻥ', 'ﻩ', 'ﻭ', 'ﻯ'];*/
    var string = this;
    var obj = {'a' :'ا','b' :'ﺏ','j' :'ﺡ','d' :'ﺩ','r' :'ﺭ','s' :'ﺱ','x' :'ﺹ','t' :'ﻁ','e' :'ﻉ','g' :'ﻕ','k' :'ﻙ','l' :'ﻝ','z' :'ﻡ','n' :'ﻥ','h' :'ﻩ','u' :'ﻭ','v' :'ﻯ', };
    Object.keys(obj).forEach(function(key) {
        string = string.replaceAll(key, obj[key]+' ');
    });
    return string
};
$('.plateNumberValAr').each(function(){
    var currentval = $(this).text();
    var finalVal = currentval.toArabicNumber();
    $(this).text(finalVal);
});                
$('.plateNumberDigAr').each(function(){
    var currentval = $(this).text();
    var finalVal = currentval.toArabicDigits();
    $(this).text(finalVal);
});

function displayPopup(url) {
    var popupWindow;
    var width = 960;
    var height = 700;
    var htmlBody = `<div id="articleContent"></div>`;
    var left = parseInt((screen.availWidth / 2) - (width / 2));
    var top = parseInt((screen.availHeight / 2) - (height / 2));
    var articleContent = htmlBody.innerHTML;
    var windowProperties = "width=" + width + ",height=" + height + ",status,resizable,left=" + left + ",top=" + top + ",screenX=" + left + ",screenY=" + top + ",scrollbars=yes";
    popupWindow = window.open(url, 'article', windowProperties);
    /*var articleDiv = popupWindow.document.getElementById("article");
    articleDiv.innerHTML += articleContent;*/
    popupWindow.document.close();
    if (window.focus) 
    { popupWindow.focus() }
}


function round(value, decimals) {
    return Number(Math.round(value +'e'+ decimals) +'e-'+ decimals).toFixed(decimals);
}

(function ($) {

    'use strict';


    function initSlimscrollMenu() {

        $('.slimscroll-menu').slimscroll({
            height: 'auto',
            position: 'right',
            size: "8px",
            color: '#9ea5ab',
            wheelStep: 5
        });
    }

    function initSlimscroll() {
        $('.slimscroll').slimscroll({
            height: 'auto',
            position: 'right',
            size: "8px",
            color: '#9ea5ab'
        });
    }

    function initMetisMenu() {
        //metis menu
        $("#side-menu").metisMenu();
    }

    function initLeftMenuCollapse() {
        // Left menu collapse
        $('.button-menu-mobile').on('click', function (event) {
            event.preventDefault();
            $("body").toggleClass("enlarged");
            initSlimscrollMenu();
        });
    }

    function initEnlarge() {
        if ($(window).width() < 1025) {
            $('body').addClass('enlarged');
        } else {
            if ($('body').data('keep-enlarged') != true)
                $('body').removeClass('enlarged');
        }
    }

    function initActiveMenu() {
        // === following js will activate the menu in left side bar based on url ====
        $("#sidebar-menu a").each(function () {
            var pageUrl = window.location.href.split(/[?#]/)[0];
            if (this.href == pageUrl) { 
                $(this).addClass("active");
                $(this).parent().addClass("active"); // add active to li of the current link
                $(this).parent().parent().addClass("in");
                $(this).parent().parent().prev().addClass("active"); // add active class to an anchor
                $(this).parent().parent().parent().addClass("active");
                $(this).parent().parent().parent().parent().addClass("in"); // add active to li of the current link
                $(this).parent().parent().parent().parent().parent().addClass("active");
            }
        });
    }

    function init() {
        initSlimscrollMenu();
        initSlimscroll();
        initMetisMenu();
        initLeftMenuCollapse();
        initEnlarge();
        initActiveMenu();
    }

    init();

})(jQuery)

function errorHandling(jqXHR, exception) {
    var error_msg = '';
    if (jqXHR.status === 0) {
        error_msg = __('error_no_connection');
    } else if (jqXHR.status == 404) {
    // 404 page error
        error_msg = __('error_404');
    } else if (jqXHR.status == 500) {
    // 500 Internal Server error
        error_msg = __('error_500');
    } else if (exception === 'parsererror') {
    // Requested JSON parse
        error_msg = __('error_parser');
    } else if (exception === 'timeout') {
    // Time out error
        error_msg = __('error_timeout');
    } else if (exception === 'abort') {
    // request aborte
        error_msg = __('error_abort');
    } else {
        error_msg = __('error_uncaught') + '\n' + jqXHR.responseText;
    }
    // error alert message
    Swal.fire({
        title: __('oops'),
        text: error_msg,
        icon:'error',
        allowOutsideClick:false,
        confirmButtonClass: 'btn btn-lg',
        buttonsStyling: false,
    })
    return true;
}

function dateDiffDay(startingDate, endingDate) {
    let startDate = new Date(new Date(startingDate).toISOString().substr(0, 10));
    if (!endingDate) {
        endingDate = new Date().toISOString().substr(0, 10); // need date in YYYY-MM-DD format
    }
    let endDate = new Date(endingDate);
    if (startDate > endDate) {
        const swap = startDate;
        startDate = endDate;
        endDate = swap;
    }
    const startYear = startDate.getFullYear();
    const february = (startYear % 4 === 0 && startYear % 100 !== 0) || startYear % 400 === 0 ? 29 : 28;
    const daysInMonth = [31, february, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];                    
    let dayDiff = endDate.getDate() - startDate.getDate();
    if (dayDiff < 0) {
        dayDiff += daysInMonth[startDate.getMonth()];
    }
    return dayDiff/* + ' Days'*/;
    }

    function dateDiffMonth(startingDate, endingDate) {
    let startDate = new Date(new Date(startingDate).toISOString().substr(0, 10));
    if (!endingDate) {
        endingDate = new Date().toISOString().substr(0, 10); // need date in YYYY-MM-DD format
    }
    let endDate = new Date(endingDate);
    if (startDate > endDate) {
        const swap = startDate;
        startDate = endDate;
        endDate = swap;
    }
    const startYear = startDate.getFullYear();
    const february = (startYear % 4 === 0 && startYear % 100 !== 0) || startYear % 400 === 0 ? 29 : 28;
    const daysInMonth = [31, february, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    let monthDiff = endDate.getMonth() - startDate.getMonth();
    if (monthDiff < 0) {
        monthDiff += 12;
    }
    let dayDiff = endDate.getDate() - startDate.getDate();
    if (dayDiff < 0) {
        if (monthDiff > 0) {
            monthDiff--;
        } else {
            monthDiff = 11;
        }
    }
    return monthDiff/* + ' Months'*/;
    }

    function dateDiffYear(startingDate, endingDate) {
    let startDate = new Date(new Date(startingDate).toISOString().substr(0, 10));
    if (!endingDate) {
        endingDate = new Date().toISOString().substr(0, 10); // need date in YYYY-MM-DD format
    }
    let endDate = new Date(endingDate);
    if (startDate > endDate) {
        const swap = startDate;
        startDate = endDate;
        endDate = swap;
    }
    const startYear = startDate.getFullYear();
    const february = (startYear % 4 === 0 && startYear % 100 !== 0) || startYear % 400 === 0 ? 29 : 28;
    const daysInMonth = [31, february, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    let yearDiff = endDate.getFullYear() - startYear;
    let monthDiff = endDate.getMonth() - startDate.getMonth();
    if (monthDiff < 0) {
        yearDiff--;
        monthDiff += 12;
    }
    let dayDiff = endDate.getDate() - startDate.getDate();
    if (dayDiff < 0) {
        if (monthDiff > 0) {
        } else {
            yearDiff--;
        }
    }
    return yearDiff/* + ' Years'*/;
    }


spans = document.querySelectorAll(".copyToClipboard");
    for (const span of spans) {
        span.onclick = function() {
            document.execCommand("copy");
        }
    span.addEventListener("copy", function(event) {
        event.preventDefault();
        if (event.clipboardData) {
            event.clipboardData.setData("text/plain", span.textContent);
            // console.log(event.clipboardData.getData("text"))
            Swal.fire({
                title : __('success_title'),
                text : __('copy_success_message'),
                toast : true,
                position : 'top-right',
                timer : 2000,
                showCancelButton : false,
                showConfirmButton : false,
                icon : 'success',
                timerProgressBar: true,
                willOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        }
    });
}


function calculate_overtime(){
    var basic_salary = document.getElementById('basic_salary').value;
    var overtime_hours = document.getElementById('overtime_hours').value;
    if(basic_salary != "" && overtime_hours != "" && !isNaN(basic_salary) && !isNaN(overtime_hours)){
        var wages_per_hour = (basic_salary / 30 / 8).toFixed(2);
        var overtime_per_hour = (wages_per_hour * 1.5).toFixed(2);
        var total_overtime = (overtime_per_hour * overtime_hours).toFixed(0);
        document.getElementById('wages_per_hour').innerHTML = wages_per_hour;
        document.getElementById('overtime_per_hour').innerHTML = overtime_per_hour;
        document.getElementById('total_overtime').innerHTML = total_overtime;
        jQuery('.final-result').css('display','block');
    } else {
        jQuery('.final-result').css('display','none');
        Swal.fire({title: __('oops'), text: __('enter_valid_value_alert'), icon: 'warning'});
    }
}

/*************************VAT Calculate*****************************/
function xParseFloat(x) {
    var amount = x.replace(',', '.');
    amount = amount.replace(/[^0-9.]/, '');
    if (amount === '') {
        return false;
    } else {
        return parseFloat(amount);
    }
}

function getAmount() {
    var amount = document.getElementById('sum').value;
    return xParseFloat(amount);
}

function getVat() {
    var amount = document.getElementById('vat').value;
    return xParseFloat(amount);
}

function getOperation() {
    return document.getElementById('formactv').checked ? 'exclude' : 'add';
}

function calculatorSubmit() {
    var amount = getAmount();
    if (amount === false || isNaN(amount) || !isFinite(amount)) {
        return false;
    }
    var vat = getVat();
    if (vat === false || isNaN(vat) || !isFinite(vat)) {
        return false;
    }
    var operation = getOperation();
    var result;
    if (operation === 'exclude') {
        result = amount - amount / (1 + vat / 100);
    } else if (operation === 'add') {
        result = amount * (1 + vat / 100);
    }
    addResults(amount, vat, operation, result);
}
function addResults(amount, vat, operation, result) {
    amount = toCurrencyString(amount);
    vat = toCurrencyString(vat);
    result = toCurrencyString(result);
    var html = '<div class="result clearfix">' +
        resultBlock(__('amount_label'), amount) +
        resultBlock(__('vat_percent_label'), vat) +
        resultBlock(__('operation_label'), operation) +
        ( operation === 'add' ?
            resultBlock(__('vat_added_label'), toCurrencyString(parseFloat(result) - parseFloat(amount))) + resultBlock(__('gross_amount_label'), result) :
            resultBlock(__('vat_excluded_label'), result) + resultBlock(__('net_amount_label'), toCurrencyString(parseFloat(amount) - parseFloat(result))) ) +
        '</div>';
    var innerHTML = document.getElementById('results').innerHTML;
    innerHTML = html + innerHTML;
    document.getElementById('results').innerHTML = innerHTML;
    return true;
}
function toCurrencyString(x) {
    return (Math.round(x*100)/100).toFixed(2)
}
function resultBlock(caption, value) {
    return '<div class="result-block">' +
            caption + '<br/>' + value +
        '</div>'
}
/*************************VAT Calculate*****************************/


function isNumber(evt) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    return true;
}

var field = document.querySelector('[name="username"]');
if (field != null) {
    field.onkeypress = function(e) {
        var key = e.which || e.keyCode || 0;
        return (
                (key >= 47 && key <= 58) ||     // numeric (0-9)
                (key >= 65 && key <= 92) ||     // upper alpha (A-Z)
                (key >= 97 && key <= 124)       // lower alpha (a-z)
            );
        
    };
}

$(function () {
    $(document).on('click', '#togglePassword', function (e) {
        e.preventDefault();
        const type = ($('#password').attr("type") == "password")?"text":"password";
        $('#password').attr("type", type)
        $('#cfrm_password').attr("type", type)
        // this.classList.toggle("fa-eye-slash");
        if($('#password').attr("type") == "password") {
            $(this).closest('#togglePassword').addClass('fa-eye');
            $(this).closest('#togglePassword').removeClass('fa-eye-slash');
        } else {
            $(this).closest('#togglePassword').addClass('fa-eye-slash');
            $(this).closest('#togglePassword').removeClass('fa-eye');
        }    
    });
});


var mobile = document.querySelector('#mobile');
if (mobile != null) {
    // $('#mobile').mask("c50-000-0000",{translation: {'c':{pattern:/[0]/}},placeholder: "050-000-0000"});
    $(mobile).inputmask({"mask": "0599999999"});
}

var emailaddress = document.querySelector('#emailaddress');
if (emailaddress != null) {
    $('#emailaddress').mask("A", {translation: {"A": { pattern: /[\w@\-.+]/, recursive: true }}});
}

$('#password, #cfrm_password').on('keyup', function () {
    if ($('#password').val() == $('#cfrm_password').val()) {
        $('#message').html(``).css('color', 'green');
        $('#btn_submit').removeAttr('disabled');
    } else {
        $('#message').html(`
        <div class="alert alert-danger my-2 rounded-0">
            <div class="d-flex align-items-center">
                <div class="col-11">${__('password_no_match_alert')}</div>
            </div>
        </div>
        `).css('color', 'red');
        $('#btn_submit').attr('disabled','');
    }
});

var autonumber = document.querySelector('.autonumber');
if (autonumber != null) {
    jQuery(function($) {
        $('.autonumber').autoNumeric('init');
    });
    jQuery.browser = {};
    (function () {
        jQuery.browser.msie = false;
        jQuery.browser.version = 0;
        if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
            jQuery.browser.msie = true;
            jQuery.browser.version = RegExp.$1;
        }
    })();
}


$('.form-horizontal').find('.btn_submit').attr('disabled', 'disabled');
$('.form-horizontal').find(':input[type="text"],input[type="password"]').keyup(function() {
    var disable = false;
    $('.form-horizontal').find(':input[type="text"],input[type="password"]').each(function(i, el) {
        if ($.trim(el.value) === '') {
            disable = true; // disable submit if any of them are still blank
        }
    });
    if (disable == true)
        $('.form-horizontal').find('.btn_submit').attr('disabled', 'disabled');
    else
        $('.form-horizontal').find('.btn_submit').removeAttr("disabled");
});

splitFloat = function(n){
    const regex = /(\d*)[.,]{1}(\d*)/;
    var m;
    if ((m = regex.exec(n.toString())) !== null) {
        return {
        integer:parseInt(m[1]),
            decimal:parseFloat(`0.${m[2]}`)
        }
    }
}

/*$(document).ready(function(){
    // $("input[type='email']").validate({
    $("#registration").validate({
        rules: {
            c_email: {required: true, email: true},
        },
        messages: {
            c_email: {required: "required", email: "Enter a valid email address."}
        }
    });
});*/

/*function checkLength(){
    var textbox = document.getElementById("textbox");
    if(textbox.value.length >= 10 && textbox.value.length <= 100){
        alert("success");
    }
    else{
        alert("make sure the input is minimum 10 characters long")
    }
}*/

/*function isNumber(e) {
var keyCode = (e.which) ? e.which : e.keyCode;
    if ((keyCode >= 48 && keyCode <= 57) || (keyCode == 8))
        return true;
    else if (keyCode == 46) {
        var curVal = document.activeElement.value;
        if (curVal != null && curVal.trim().indexOf('.') == -1)
            return true;
        else
            return false;
    }
    else
        return false;
}*/

// Snowstorm JS
/*var firstScript = document.getElementsByTagName('script')[0],
js = document.createElement('script');
js.src = 'https://cdnjs.cloudflare.com/ajax/libs/Snowstorm/20131208/snowstorm-min.js';
js.onload = function () {
    // do stuff with your dynamically loaded script
    snowStorm.snowColor = '#99ccff';
};
firstScript.parentNode.insertBefore(js, firstScript);*/


// Function to detect system scaling and apply compensation
function adjustForSystemScaling() {
    // Standard ratios (may vary slightly by OS/browser)
    const scalingRatios = {
        '100%': 1.0,
        '125%': 1.25,
        '150%': 1.5,
        '175%': 1.75,
        '200%': 2.0
    };
    // Get current device pixel ratio
    const currentRatio = window.devicePixelRatio;
    // Check if ratio matches 125% scaling (with some tolerance)
    const targetRatio = 1.25;
    const tolerance = 0.05; // ±5% tolerance
    if (Math.abs(currentRatio - targetRatio) < tolerance) {
        // Apply 80% zoom to compensate for 125% system scaling
        document.body.style.zoom = "80%";
        // Cross-browser alternative using transform
        document.body.style.transform = "scale(0.8)";
        document.body.style.transformOrigin = "top left";
        // Adjust layout dimensions to compensate
        document.body.style.width = "125%"; // Inverse of 0.8
    }
}
// Run when page loads and when orientation changes
window.addEventListener('load', adjustForSystemScaling);
window.addEventListener('resize', adjustForSystemScaling);


/**
 * Handles AJAX failure responses and displays appropriate error messages
 * @param {jqXHR} jqXHR - The jQuery XHR object
 * @param {string} textStatus - Status text of the error
 * @param {string} defaultTitle - Default title if none found in response
 * @param {string} defaultMsg - Default message if none found in response
 */
function handleAjaxFailure(jqXHR, textStatus, defaultTitle = __('request_failed_title'), defaultMsg = __('server_or_network_error')) {
    let errorTitle = defaultTitle;
    let errorMsg = defaultMsg + ': ' + textStatus;
    let errorIcon = 'error';
    // Check if responseText exists
    if (jqXHR.responseText) {
        // Find JSON in response (may be wrapped in HTML)
        const jsonStart = jqXHR.responseText.indexOf('{');
        if (jsonStart !== -1) {
            const jsonString = jqXHR.responseText.substring(jsonStart);
            const parsedResponse = JSON.parse(jsonString);
            errorTitle = parsedResponse.title || errorTitle;
            errorMsg = parsedResponse.message || errorMsg;
            errorIcon = parsedResponse.type || errorIcon;
        } else {
            errorMsg = jqXHR.responseText;
        }
    }
}

/**
 * Restricts input to only allow numbers with configurable options
 * @param {HTMLInputElement} inputElement - The input field to control
 * @param {Object} [options] - Configuration options
 * @param {boolean} [options.allowDecimal=false] - Whether to allow decimal points
 * @param {boolean} [options.allowNegative=false] - Whether to allow negative numbers
 * @param {number} [options.maxDigits=null] - Maximum number of digits allowed
 * @param {number} [options.maxValue=null] - Maximum numeric value allowed
 * @param {number} [options.minValue=null] - Minimum numeric value allowed
 */
function restrictToNumbers(inputElement, options = {}) {
    if (!inputElement) {
            console.warn(__("restrict_to_numbers_warning"));
        return;
    }
    const config = {
        allowDecimal: false,
        allowNegative: false,
        maxDigits: null,
        maxValue: null,
        minValue: null,
        ...options
    };
    // Handle input events
    inputElement.addEventListener('input', function(e) {
        let value = this.value;  
        // Build regex pattern based on options
        let pattern = '[^0-9]'; // Default: only digits
        if (config.allowDecimal) pattern = '[^0-9.]';
        if (config.allowNegative) pattern = '[^0-9-]';
        if (config.allowDecimal && config.allowNegative) pattern = '[^0-9.-]';
        // Remove invalid characters
        value = value.replace(new RegExp(pattern, 'g'), '');
        // Validate decimal points
        if (config.allowDecimal) {
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
        }
        // Validate negative sign
        if (config.allowNegative) {
            const minusCount = (value.match(/-/g) || []).length;
            if (minusCount > 1 || (minusCount === 1 && !value.startsWith('-'))) {
                value = value.replace(/-/g, '');
                if (minusCount > 0 && value.length > 0) {
                    value = '-' + value;
                }
            }
        }
        // Apply max digits restriction
        if (config.maxDigits) {
            const digits = value.replace(/[^0-9]/g, '');
            if (digits.length > config.maxDigits) {
                value = value.slice(0, -(digits.length - config.maxDigits));
            }
        }
        this.value = value;
    });
    // Handle paste events
    inputElement.addEventListener('paste', function(e) {
        const pasteData = e.clipboardData.getData('text/plain');
        const numericRegex = config.allowDecimal ? 
            (config.allowNegative ? /^-?\d*\.?\d+$/ : /^\d*\.?\d+$/) :
            (config.allowNegative ? /^-?\d+$/ : /^\d+$/); 
        if (!numericRegex.test(pasteData)) {
            e.preventDefault();
        }
    });

    // Handle blur event to validate final value
    inputElement.addEventListener('blur', function() {
        if (this.value === '') return;
        const numValue = parseFloat(this.value);
        // Check if valid number
        if (isNaN(numValue)) {
            this.value = '';
            return;
        }
        // Apply min/max value constraints
        if (config.minValue !== null && numValue < config.minValue) {
            this.value = config.minValue;
        }
        if (config.maxValue !== null && numValue > config.maxValue) {
            this.value = config.maxValue;
        }
    });
}



// // You can define your specific functions outside the ready block for better organization
function initializeEditFormValidation() {
    restrictToNumbers(document.getElementById('iqama'), {
	    allowDecimal: true,
        maxDigits: 10
        // allowNegative: false,
        // minValue: 0,
        // maxValue: 1000000
    });
    restrictToNumbers(document.getElementById('mobile'), {
	    allowDecimal: true,
        maxDigits: 10
    });
    $(".registration select").select2();

    // restrictToNumbers(document.getElementById('basic'), {allowDecimal: true });
    restrictToNumbers(document.getElementById('basic'));
}

// function initializeDataTables() {
//     // Your data table logic...
//     console.log('DataTables has been initialized.');
// }

function printReport() {
    // 1. Get the HTML of the report container
    const printContent = document.getElementById('report-content').innerHTML;
    
    // 2. Get all the stylesheets from the current page
    const styles = Array.from(document.styleSheets)
        .map(styleSheet => {
            try {
                // Convert the CSS rules to text
                return Array.from(styleSheet.cssRules)
                    .map(rule => rule.cssText)
                    .join('');
            } catch (e) {
                // This can happen with external stylesheets (e.g., Google Fonts) due to security policies
                // We can still link to them
                if (styleSheet.href) {
                    return `<link rel="stylesheet" href="${styleSheet.href}">`;
                }
                return '';
            }
        })
        .join('\n');
    // 3. Create a hidden iframe
    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = '0';
    document.body.appendChild(iframe);
    // 4. Write the content and styles to the iframe
    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write('<html><head><title>' + __('print_vacation_report_title') + '</title>');
    // Add all the styles from the parent page
    doc.write('<style>' + styles + '</style>');
    doc.write('</head><body>');
    doc.write(printContent);
    doc.write('</body></html>');
    doc.close();
    // 5. Wait for the content to load, then print and remove the iframe
    iframe.onload = function() {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        // Remove the iframe after printing
        setTimeout(() => {
            document.body.removeChild(iframe);
        }, 500);
    };
}

function printReportPopup() {
    // Get the HTML of the report container
    const printContent = document.getElementById('report-content').innerHTML;
    
    // Get all the stylesheets from the current page
    const styles = Array.from(document.styleSheets)
        .map(styleSheet => {
            try {
                // Convert the CSS rules to text
                return Array.from(styleSheet.cssRules)
                    .map(rule => rule.cssText)
                    .join('');
            } catch (e) {
                console.log(__('cannot_read_stylesheet_log'), e);
                return '';
            }
        })
        .join('\\n');

    // Create a new window to print from
    const printWindow = window.open('', '', 'height=800,width=1000');

    // Write the content to the new window
    printWindow.document.write('<html><head><title>' + __('print_vacation_report_title') + '</title>');
    // Add all the styles from the parent page
    printWindow.document.write('<style>' + styles + '</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    
    // Wait for the content to load, then print and close
    printWindow.onload = function() {
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };
}

function setupInputValidations() {
    // Sets numeric input mode for specific fields for better mobile UX.
    document.querySelectorAll('.amount-validation, .numeric-only, .saudi-mobile-number').forEach(input => {
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('pattern', '[0-9]*');
    });

    // Main event listener for real-time input formatting.
    document.body.addEventListener('input', function(event) {
        const input = event.target;
        // Validates and formats amounts to allow decimals.
        if (input.classList.contains('amount-validation')) {
            let value = input.value.replace(/[^0-9.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
            if (parts[1] && parts[1].length > 2) {
                parts[1] = parts[1].substring(0, 2);
                value = parts.join('.');
            }
            input.value = value;
        // Ensures only numbers are entered.
        } else if (input.classList.contains('numeric-only')) {
            input.value = input.value.replace(/\D/g, '');
        // Formats and validates Saudi mobile numbers.
        } else if (input.classList.contains('saudi-mobile-number')) {
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 1 && value[0] !== '0') value = '0' + value;
            if (value.length >= 2 && value.substring(0, 2) !== '05') value = '05' + value.substring(2);
            input.value = value.substring(0, 10);
        }
    });

    // Event listener for validation after a user leaves an input field.
    document.body.addEventListener('focusout', function(event) {
        const input = event.target;
        if (input.classList.contains('amount-validation')) {
            let value = parseFloat(input.value);
            if (!isNaN(value)) input.value = value.toFixed(2);
        } else if (input.classList.contains('saudi-mobile-number')) {
            const value = input.value;
            const isValid = /^05\d{8}$/.test(value);
            input.classList.toggle('is-invalid', value && !isValid);
        } else if (input.classList.contains('email-validation')) {
            const value = input.value;
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            input.classList.toggle('is-invalid', value && !isValid);
        }
    });
}

function setupDynamicValidation(fieldsToValidate, onFirstInteraction = null) {
    const confirmButton = Swal.getConfirmButton();
    let interactionFired = false;

    const elements = new Map(
        fieldsToValidate.map(field => [field.id, document.getElementById(field.id)])
    );

    const validateAll = () => {
        const allValid = fieldsToValidate.every(field => {
            const element = elements.get(field.id);
            return field.validation(element.value);
        });
        confirmButton.disabled = !allValid;
    };

    // This function now uses Swal.showValidationMessage to display errors.
    const updateValidationMessages = () => {
        let invalidFieldsMessages = [];

        // Check each field and collect messages for invalid ones.
        fieldsToValidate.forEach(field => {
            const element = elements.get(field.id);
            const isValid = field.validation(element.value);
            // Highlight the individual invalid field
            element.classList.toggle('is-invalid', !isValid);
            if (!isValid) {
                invalidFieldsMessages.push(field.requiredMessage);
            }
        });

        // If there are errors, join them and display using SweetAlert's function.
        if (invalidFieldsMessages.length > 0) {
            // **UPDATED**: Join messages with a <br> tag to force a line break.
            const htmlMessages = invalidFieldsMessages.join('<br>');
            Swal.showValidationMessage(htmlMessages);
        } else {
            // If all fields are valid, clear the validation message.
            Swal.resetValidationMessage();
        }
    };


    // Attach event listeners to each specified field.
    fieldsToValidate.forEach(field => {
        const element = elements.get(field.id);
        if (element) {
            const handleInteraction = () => {
                if (onFirstInteraction && !interactionFired) {
                    onFirstInteraction();
                    interactionFired = true;
                }
                updateValidationMessages(); // Update the combined error message list
                validateAll(); // Update the button state
            };

            // Validate as the user types or changes a selection.
            element.addEventListener(field.event, handleInteraction);
            
            // Use 'blur' to trigger validation when the user leaves a field.
            element.addEventListener('blur', handleInteraction);
        }
    });

    // Run initial check to disable the button, but don't show messages yet.
    validateAll();
}

function dateofbirth(selector){
    $(selector).datepicker({
        format: "yyyy-mm-dd",
        todayHighlight: true,
        autoclose: true,
        endDate: '+0d' // disable future dates
    });
}