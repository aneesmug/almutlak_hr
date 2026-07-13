/**
 * Theme: Highdmin - Responsive Bootstrap 4 Admin Dashboard
 * Author: Coderthemes
 * Module/App: Main Js
 */

// Load colors from external API endpoint
// This ensures APP_COLORS is always fetched from a single source
window.APP_COLORS = {}; // Initialize empty, will be populated by fetch

(function() {
    'use strict';
    
    // Fetch colors from the server
    var colorsUrl = './includes/ajaxFile/getColors.php';
    
    // Use synchronous XMLHttpRequest to ensure colors are loaded before any code uses them
    var xhr = new XMLHttpRequest();
    xhr.open('GET', colorsUrl, false); // false = synchronous
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                window.APP_COLORS = JSON.parse(xhr.responseText);
                console.info('Colors loaded from external file successfully');
            } catch(e) {
                console.error('Failed to parse colors JSON:', e);
            }
        }
    };
    xhr.onerror = function() {
        console.error('Failed to load colors from: ' + colorsUrl);
    };
    xhr.send(null);
})();

// Option 2: Reusable function with optional attributes
function loadResource(src, type = 'js', attributes = {}, position = 'head') {
  let element;
  if (type === 'js') {
    element = document.createElement('script');
    element.src = src;
    element.type = 'text/javascript';
  } else if (type === 'css') {
    // CSS always goes in head, position is ignored for CSS
    element = document.createElement('link');
    element.rel = 'stylesheet';
    element.href = src;
    position = 'head'; // Force CSS to head
  }
  Object.entries(attributes).forEach(([key, value]) => element[key] = value);
  // Choose where to append
  if (position === 'body' && type === 'js') {
    document.body.appendChild(element);
  } else {
    document.head.appendChild(element);
  }
  return element;
}

// loadResource('assets/css/app.css', 'css', { media: 'screen' }, 'head');

// Include shared AJAX error handling utilities
loadResource('./assets/js/ajaxErrorHandling.js', 'js', {}, 'head');

//Sweet Alert v2.0
// $("head").append($("<script type='text/javascript'></script>").attr("src", "https://cdn.jsdelivr.net/npm/sweetalert2@11"));
loadResource('https://cdn.jsdelivr.net/npm/sweetalert2@11', 'js', { async: true, defer: true }, 'head');
// Avatar Cropie
loadResource('./plugins/croppie/croppie.js', 'js', { async: true, defer: true }, 'head');
loadResource('./plugins/croppie/croppie.min.js', 'js', { async: true, defer: true }, 'head');
loadResource('https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.js', 'js', { async: true, defer: true }, 'head');
loadResource('./plugins/croppie/exif.js', 'js', { async: true, defer: true }, 'head');
// File Dropzone
loadResource('./plugins/dropzone/dropzone.js', 'js', { async: true, defer: true }, 'head');
// Time Picker
loadResource('./plugins/bootstrap-timepicker/bootstrap-timepicker.js', 'js', { async: true, defer: true }, 'head');
loadResource('./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js', 'js', { async: true, defer: true }, 'head');
loadResource('./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js', 'js', { async: true, defer: true }, 'head');
loadResource('./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js', 'js', { async: true, defer: true }, 'head');
loadResource('./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js', 'js', { async: true, defer: true }, 'head');
loadResource('./plugins/clockpicker/js/bootstrap-clockpicker.min.js', 'js', { async: true, defer: true }, 'head');
loadResource('./plugins/bootstrap-daterangepicker/daterangepicker.js', 'js', { async: true, defer: true }, 'head');
loadResource('./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js', 'js', { async: true, defer: true }, 'head');
// Validate
loadResource('./assets/js/jquery.validate.js', 'js', { async: true, defer: true }, 'head');
// Select 2
loadResource('./plugins/select2/js/select2.min.js', 'js', { async: true, defer: true }, 'head');
loadResource('./plugins/bootstrap-select/js/bootstrap-select.js', 'js', { async: true, defer: true }, 'head');

loadResource('./assets/js/notifications.js', 'js', { async: true, defer: true }, 'head');
loadResource('./assets/js/translation.js', 'js', { async: true, defer: true }, 'head');
loadResource('./assets/js/geolocation-capture.js', 'js', { async: true, defer: true }, 'head');

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
    // Initialize global RTL datepicker support
    setupGlobalRTLDatepicker();
    
    // Use event delegation for dynamically created modal elements
    $(document).on('click', '.addnote', function(e) {
        e.preventDefault();
        add_noties.call(this);
    });
        // Get the current page's name from the data-page attribute
    const currentPage = $('body').data('page');
    // Check if we are on the 'edit-employee' page
    if (currentPage === 'edit-employee' || currentPage === 'new-employee' || currentPage === 'add_emp_slry' ) {
        initializeEditFormValidation();
        // console.log('load employees');
    }
    // Check if we are on the 'view-employee' page
    if (currentPage === 'view-employee') {
        // console.log("Running script for View Employee page.");
        // All your view-employee specific code goes here
    }
});

/**
 * Global RTL Datepicker Setup
 * Call this function once to apply RTL positioning to ALL datepickers automatically
 * Works for existing and dynamically created datepicker instances
 */
function setupGlobalRTLDatepicker() {
    var isRTL = $('html').attr('dir') === 'rtl' || $('body').attr('dir') === 'rtl';
    
    if (!isRTL) return; // Exit if not RTL mode
    
    // Function to position picker correctly in RTL
    function positionPickerRTL($input) {
        var datepickerData = $input.data('datepicker');
        if (!datepickerData || !datepickerData.picker) return;
        var $picker = datepickerData.picker;
        var $modal = $input.closest('.modal:visible');
        var inputWidth = $input.outerWidth();
        var pickerWidth = $picker.outerWidth();
        var inputHeight = $input.outerHeight();
        var leftPos, topPos;
        if ($modal.length > 0) {
            // Input is inside a modal
            var inputPos = $input.position(); // relative to modal
            // Append picker to modal if not already
            if (!$picker.parent().is($modal)) {
                $picker.appendTo($modal);
            }
            leftPos = inputPos.left + inputWidth - pickerWidth;
            topPos = inputPos.top + inputHeight + 5;
            // Ensure picker doesn't go off left edge
            if (leftPos < 0) leftPos = inputPos.left;
        } else {
            // Not in modal, use offset and append to body
            var inputOffset = $input.offset();
            if (!$picker.parent().is('body')) {
                $picker.appendTo('body');
            }
            leftPos = inputOffset.left + inputWidth - pickerWidth;
            topPos = inputOffset.top + inputHeight + 5;
            if (leftPos < 0) leftPos = inputOffset.left;
        }
        $picker.css({
            'position': 'absolute',
            'left': leftPos + 'px',
            'top': topPos + 'px',
            'z-index': '99999',
            'margin': '0',
            'transform': 'none'
        });
    }
    
    // Override datepicker show event for all inputs
    $(document).on('show.datepicker', '.datepicker', function() {
        var $input = $(this);
        
        // Position on first show
        setTimeout(function() {
            positionPickerRTL($input);
        }, 0);
        
        // Keep repositioning while picker is visible
        var intervalId = setInterval(function() {
            var datepickerData = $input.data('datepicker');
            if (!datepickerData || !datepickerData.picker || !datepickerData.picker.is(':visible')) {
                clearInterval(intervalId);
                return;
            }
            positionPickerRTL($input);
        }, 100);
    });
    
    // Watch for dynamically created inputs
    var observer = new MutationObserver(function(mutations) {
        $(mutations).each(function() {
            $(this.addedNodes).find('input.datepicker, input[data-datepicker="true"]').each(function() {
                var $input = $(this);
                // Attach event listener if datepicker is already initialized
                if ($input.data('datepicker')) {
                    $input.off('show.datepicker').on('show.datepicker', function() {
                        setTimeout(function() {
                            positionPickerRTL($input);
                        }, 0);
                        
                        var intervalId = setInterval(function() {
                            var datepickerData = $input.data('datepicker');
                            if (!datepickerData || !datepickerData.picker || !datepickerData.picker.is(':visible')) {
                                clearInterval(intervalId);
                                return;
                            }
                            positionPickerRTL($input);
                        }, 100);
                    });
                }
            });
        });
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Alias for backward compatibility - initializes datepicker with RTL support
function initializeDatepickerRTL() {
    // Initialize all elements with class 'datepicker'
    $('.datepicker').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        todayHighlight: true,
        orientation: 'auto'
    });
    
    // Then apply RTL positioning
    setupGlobalRTLDatepicker();
}

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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
                    var filesiz = 1048576 * 8;
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
                    Swal.showValidationMessage(__("upload_size_limit_5mb_validation").replace('%s', (filesiz / 1048576).toFixed(0)))
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
                // console.log(maker_name);
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
                url: './includes/ajaxFile/hrHandler.php',
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

// * working fine but disabled as per request */
/* $(document).on('click', '.addDrvrAtter', function (e) {
    e.preventDefault();
    var cid = $(this).data('id');
    Swal.fire({
        title: __("add_driver_info"),
        html: driver_HTML(),
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
                url: './includes/ajaxFile/hrHandler.php',
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
}); */

$(document).on('click', '.addRtrnDrvrAtter', function (e) {
    e.preventDefault();
    var id = $(this).data('id');
    var cid = $(this).data('cid');
    Swal.fire({
        title: __("are_you_sure"),
        text: __("want_to_return_car"),
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
                    var filesiz = 1048576 * 8;
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
                    Swal.showValidationMessage(__("upload_size_limit_5mb_validation").replace('%s', (filesiz / 1048576).toFixed(0)))
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        // icon: 'info',
        showCancelButton: true,
        cancelButtonColor: APP_COLORS.danger_dark,
        cancelButtonText: __('cancel'),
        confirmButtonColor: APP_COLORS.primary,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
                            // console.log('jQuery bind complete');
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        cancelButtonColor: APP_COLORS.danger_dark,
        cancelButtonText: __('cancel'),
        confirmButtonColor: APP_COLORS.primary,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
////////////          Company Access Control Functions            //////////////
////////////////////////////////////////////////////////////////////

/**
 * Load companies and set up company access UI for user editing
 * @param {int} userId - The admin_login.id of the user
 * @param {string} userType - The user's current type
 */
function loadCompanyAccess(userId, userType) {
    // Check if user is system admin
    var isSystemAdmin = (userType === 'administrator' || userType === 'gm');
    
    $.ajax({
        url: './includes/ajaxFile/getCompanyAccess.php',
        type: 'POST',
        data: { user_id: userId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Destroy any existing Select2 instance
                if ($('#allowed_companies').data('select2')) {
                    $('#allowed_companies').select2('destroy');
                }
                
                // Clear the select element first
                $('#allowed_companies').empty();
                
                // Populate company options
                var companyHTML = '';
                $.each(response.companies, function(index, company) {
                    companyHTML += '<option value="' + company.id + '">' + company.name + '</option>';
                });
                $('#allowed_companies').html(companyHTML);
                
                // Initialize Select2 with search functionality
                $('#allowed_companies').select2({
                    placeholder: __('select_companies') || 'Search and select companies...',
                    allowClear: false,
                    width: '100%',
                    dropdownParent: $('.swal2-container'),
                    language: {
                        searching: function () { return __('searching') || 'Searching...'; },
                        noResults: function () { return __('no_results_found') || 'No results found'; }
                    }
                });
                
                // Set current selections
                if (response.allowed_companies && response.allowed_companies.length > 0) {
                    $('#fullAccessCheckbox').prop('checked', false);
                    $('#allowed_companies').prop('disabled', false);
                    
                    // Delay value setting to ensure DOM is ready and Select2 is initialized
                    setTimeout(function() {
                        $('#allowed_companies').val(response.allowed_companies).trigger('change');
                    }, 150);
                } else {
                    // No restrictions = full access
                    $('#fullAccessCheckbox').prop('checked', true);
                    $('#allowed_companies').val([]).trigger('change');
                    $('#allowed_companies').prop('disabled', true);
                }
                
                // Handle full access checkbox
                $('#fullAccessCheckbox').off('change').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#allowed_companies').prop('disabled', true);
                        $('#allowed_companies').val([]).trigger('change');
                        $('#allowed_companies').closest('.form-group').addClass('opacity-50');
                    } else {
                        $('#allowed_companies').prop('disabled', false);
                        $('#allowed_companies').closest('.form-group').removeClass('opacity-50');
                        $('#allowed_companies').focus();
                    }
                });
                
                // Show/hide company access section based on user type
                toggleCompanyAccessSection(userType);
                
                // Toggle when user type changes
                $('#user_type').off('change').on('change', function() {
                    toggleCompanyAccessSection($(this).val());
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading company access:', error);
        }
    });
}

/**
 * Toggle visibility of company access section based on user type
 * @param {string} userType - The selected user type
 */
function toggleCompanyAccessSection(userType) {
    // Hide for system admins and employees
    var isSystemAdmin = (userType === 'administrator' || userType === 'gm');
    var isEmployee = (userType === 'employee');
    
    if (isSystemAdmin || isEmployee) {
        $('#company-access-group').hide();
        $('#fullAccessCheckbox').removeAttr('required');
    } else {
        $('#company-access-group').show();
        $('#fullAccessCheckbox').attr('required', 'required');
    }
}

/**
 * Load departments and set up department access UI for user editing
 * @param {int} userId - The admin_login.id of the user
 * @param {string} userType - The user's current type
 */
function loadDepartmentAccess(userId, userType) {
    // Check if user is system admin
    var isSystemAdmin = (userType === 'administrator' || userType === 'gm');
    
    $.ajax({
        url: './includes/ajaxFile/getDepartmentAccess.php',
        type: 'POST',
        data: { user_id: userId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Destroy any existing Select2 instance
                if ($('#allowed_departments').data('select2')) {
                    $('#allowed_departments').select2('destroy');
                }
                
                // Populate department options
                var departmentHTML = '';
                $.each(response.departments, function(index, dept) {
                    departmentHTML += '<option value="' + dept.id + '">' + dept.name + '</option>';
                });
                $('#allowed_departments').html(departmentHTML);
                
                // Initialize Select2 with search functionality
                $('#allowed_departments').select2({
                    placeholder: __('select_departments') || 'Search and select departments...',
                    allowClear: false,
                    width: '100%',
                    dropdownParent: $('.swal2-container'),
                    language: {
                        searching: function () { return __('searching') || 'Searching...'; },
                        noResults: function () { return __('no_results_found') || 'No results found'; }
                    }
                });
                
                // Set current selections
                if (response.allowed_departments && response.allowed_departments.length > 0) {
                    // Delay value setting to ensure DOM is ready
                    setTimeout(function() {
                        $('#allowed_departments').val(response.allowed_departments).trigger('change');
                    }, 50);
                    $('#fullDeptAccessCheckbox').prop('checked', false);
                    $('#allowed_departments').prop('disabled', false);
                } else {
                    // No restrictions = full access
                    $('#fullDeptAccessCheckbox').prop('checked', true);
                    $('#allowed_departments').val([]).trigger('change');
                    $('#allowed_departments').prop('disabled', true);
                }
                
                // Handle full access checkbox
                $('#fullDeptAccessCheckbox').off('change').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#allowed_departments').prop('disabled', true);
                        $('#allowed_departments').val([]).trigger('change');
                        $('#allowed_departments').closest('.form-group').addClass('opacity-50');
                    } else {
                        $('#allowed_departments').prop('disabled', false);
                        $('#allowed_departments').closest('.form-group').removeClass('opacity-50');
                        $('#allowed_departments').focus();
                    }
                });
                
                // Show/hide department access section based on user type
                toggleDepartmentAccessSection(userType);
                
                // Toggle when user type changes
                $('#user_type').off('change.dept').on('change.dept', function() {
                    toggleDepartmentAccessSection($(this).val());
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading department access:', error);
        }
    });
}

/**
 * Toggle visibility of department access section based on user type
 * @param {string} userType - The selected user type
 */
function toggleDepartmentAccessSection(userType) {
    // Hide for system admins and employees
    var isSystemAdmin = (userType === 'administrator' || userType === 'gm');
    var isEmployee = (userType === 'employee');
    
    if (isSystemAdmin || isEmployee) {
        $('#department-access-group').hide();
        $('#fullDeptAccessCheckbox').removeAttr('required');
    } else {
        $('#department-access-group').show();
        $('#fullDeptAccessCheckbox').attr('required', 'required');
    }
}

/**
 * Load Employee Access - Load all employees and set current user's allowed employees
 * @param {number} userId - The admin_login.id
 * @param {string} userType - The selected user type
 */
function loadEmployeeAccess(userId, userType) {
    $.ajax({
        url: './includes/ajaxFile/getEmployeeAccess.php',
        type: 'POST',
        data: { user_id: userId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Destroy any existing Select2 instance
                if ($('#allowed_employees').data('select2')) {
                    $('#allowed_employees').select2('destroy');
                }
                
                // Populate employee options
                var employeeHTML = '';
                $.each(response.employees, function(index, emp) {
                    employeeHTML += '<option value="' + emp.id + '">' + emp.name + '</option>';
                });
                $('#allowed_employees').html(employeeHTML);
                
                // Initialize Select2 with search functionality
                $('#allowed_employees').select2({
                    placeholder: __('select_employees') || 'Search and select employees...',
                    allowClear: false,
                    width: '100%',
                    dropdownParent: $('.swal2-container'),
                    language: {
                        searching: function () { return __('searching') || 'Searching...'; },
                        noResults: function () { return __('no_results_found') || 'No results found'; }
                    }
                });
                
                // Set current selections
                if (response.allowed_employees && response.allowed_employees.length > 0) {
                    // Delay value setting to ensure DOM is ready
                    setTimeout(function() {
                        $('#allowed_employees').val(response.allowed_employees).trigger('change');
                    }, 50);
                    $('#fullEmpAccessCheckbox').prop('checked', false);
                    $('#allowed_employees').prop('disabled', false);
                } else {
                    // No restrictions = full access
                    $('#fullEmpAccessCheckbox').prop('checked', true);
                    $('#allowed_employees').val([]).trigger('change');
                    $('#allowed_employees').prop('disabled', true);
                }
                
                // Handle full access checkbox
                $('#fullEmpAccessCheckbox').off('change').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#allowed_employees').prop('disabled', true);
                        $('#allowed_employees').val([]).trigger('change');
                        $('#allowed_employees').closest('.form-group').addClass('opacity-50');
                    } else {
                        $('#allowed_employees').prop('disabled', false);
                        $('#allowed_employees').closest('.form-group').removeClass('opacity-50');
                        $('#allowed_employees').focus();
                    }
                });
                
                // Show/hide employee access section based on user type
                toggleEmployeeAccessSection(userType);
                
                // Toggle when user type changes
                $('#user_type').off('change.emp').on('change.emp', function() {
                    toggleEmployeeAccessSection($(this).val());
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading employee access:', error);
        }
    });
}

/**
 * Toggle visibility of employee access section based on user type
 * @param {string} userType - The selected user type
 */
function toggleEmployeeAccessSection(userType) {
    // Hide for system admins and employees
    var isSystemAdmin = (userType === 'administrator' || userType === 'gm');
    var isEmployee = (userType === 'employee');
    
    if (isSystemAdmin || isEmployee) {
        $('#employee-access-group').hide();
        $('#fullEmpAccessCheckbox').removeAttr('required');
    } else {
        $('#employee-access-group').show();
        $('#fullEmpAccessCheckbox').attr('required', 'required');
    }
}

////////////////////////////////////////////////////////////////////
////////////             End Users Handling           //////////////
////////////////////////////////////////////////////////////////////

// Function to toggle email field visibility based on user type
function toggleEmailFieldVisibility() {
    var selectedType = $('#user_type').val();
    // console.log('Toggling email field. Selected type:', selectedType);
    if (selectedType === 'employee') {
        $('#email-group').hide();
        $('#email').removeAttr('required');
        // console.log('Email field hidden (employee)');
    } else {
        $('#email-group').show();
        $('#email').attr('required', 'required');
        // console.log('Email field shown for:', selectedType);
    }
}

// Global change handler to keep email field in sync for any user_type select
$(document).on('change', '#user_type', function() {
    toggleEmailFieldVisibility();
});

$(document).on('click', '.updateUserAjax', function (e) {
    e.preventDefault();
    var e_iduser        = $(this).data('id'); 
    var e_fullname      = $(this).data('fullname');
    var e_dept          = $(this).data('dept');
    var e_email         = $(this).data('email');
    var user_type       = $(this).data('user_type');
    var user_status     = $(this).data('status');
    Swal.fire({
        title: e_fullname, // Show user's name in title
        html: edit_user_HTML(),
        customClass: 'swal-landscape',
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        willOpen: function() {
            // Give DOM time to render
            setTimeout(function() {
                $('#iduser').val(e_iduser); 
                $('#dept').val(e_dept);
                $('#email').val(e_email);
                $('#user_status').prop('checked', user_status == 1);

                // Set the select value properly using .val() and then trigger the toggle
                $('#user_type').val(user_type);
                toggleEmailFieldVisibility();  // Call immediately after setting value

                // Load companies and set current user's allowed companies
                loadCompanyAccess(e_iduser, user_type);
                
                // Load departments and set current user's allowed departments
                loadDepartmentAccess(e_iduser, user_type);
                
                // Load employees and set current user's allowed employees
                loadEmployeeAccess(e_iduser, user_type);
            }, 100);
        },
        didClose: function() {
            // Destroy Select2 instances when modal closes
            if ($('#allowed_companies').data('select2')) {
                $('#allowed_companies').select2('destroy');
            }
            if ($('#allowed_departments').data('select2')) {
                $('#allowed_departments').select2('destroy');
            }
            if ($('#allowed_employees').data('select2')) {
                $('#allowed_employees').select2('destroy');
            }
        },
        preConfirm: function() {
            var selectedType = $('#user_type').val();
            var fullAccess = $('#fullAccessCheckbox').is(':checked');
            var selectedCompanies = $('#allowed_companies').val();
            var fullDeptAccess = $('#fullDeptAccessCheckbox').is(':checked');
            var selectedDepartments = $('#allowed_departments').val();
            var fullEmpAccess = $('#fullEmpAccessCheckbox').is(':checked');
            var selectedEmployees = $('#allowed_employees').val();
            
            // Validate user type
            if($('#user_type').val() == "") {
                Swal.showValidationMessage(__("select_employee_type"));
                return false;
            }
            
            // Only validate email if user type is not 'employee'
            if(selectedType !== 'employee' && $('#email').val() == "") {
                Swal.showValidationMessage(__("enter_valid_email"));
                return false;
            }
            
            // Validate company access (only for non-admin, non-employee users)
            if($('#company-access-group').is(':visible')) {
                if(!fullAccess && (!selectedCompanies || selectedCompanies.length === 0)) {
                    Swal.showValidationMessage(__("select_at_least_one_company") || "Please select at least one company or grant full access");
                    return false;
                }
            }
            
            // Validate department access (only for non-admin, non-employee users)
            if($('#department-access-group').is(':visible')) {
                if(!fullDeptAccess && (!selectedDepartments || selectedDepartments.length === 0)) {
                    Swal.showValidationMessage(__("select_at_least_one_department") || "Please select at least one department or grant full access");
                    return false;
                }
            }
            
            // Validate employee access (only for non-admin, non-employee users)
            if($('#employee-access-group').is(':visible')) {
                if(!fullEmpAccess && (!selectedEmployees || selectedEmployees.length === 0)) {
                    Swal.showValidationMessage(__("select_at_least_one_employee") || "Please select at least one employee or grant full access");
                    return false;
                }
            }
            
            return new Promise(function(reject) {
                // Build form data with proper company and department handling
                var formData = new FormData($('#submitEditUserForm')[0]);
                
                // Handle company access
                // Remove allowed_companies field if full access is checked
                if(fullAccess) {
                    formData.delete('allowed_companies');
                    formData.append('full_access', '1');
                } else if(selectedCompanies && selectedCompanies.length > 0) {
                    // Remove default serialized companies and rebuild as array
                    formData.delete('allowed_companies');
                    selectedCompanies.forEach(function(companyId) {
                        formData.append('allowed_companies[]', companyId);
                    });
                    formData.append('full_access', '0');
                }
                
                // Handle department access
                // Remove allowed_departments field if full access is checked
                if(fullDeptAccess) {
                    formData.delete('allowed_departments');
                    formData.append('full_dept_access', '1');
                } else if(selectedDepartments && selectedDepartments.length > 0) {
                    // Remove default serialized departments and rebuild as array
                    formData.delete('allowed_departments');
                    selectedDepartments.forEach(function(deptId) {
                        formData.append('allowed_departments[]', deptId);
                    });
                    formData.append('full_dept_access', '0');
                }
                
                // Handle employee access
                // Remove allowed_employees field if full access is checked
                if(fullEmpAccess) {
                    formData.delete('allowed_employees');
                    formData.append('full_emp_access', '1');
                } else if(selectedEmployees && selectedEmployees.length > 0) {
                    // Remove default serialized employees and rebuild as array
                    formData.delete('allowed_employees');
                    selectedEmployees.forEach(function(empId) {
                        formData.append('allowed_employees[]', empId);
                    });
                    formData.append('full_emp_access', '0');
                }
                
                // Add AJAX action type
                formData.append('ajaxType', 'user_upate');
                
                $.ajax({
                        url: './includes/ajaxFile/ajaxUser.php',
                        type: 'POST',
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        dataType: "json",
                })
                .done(function(response){
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
                    }).then(function(result){
                        // Refresh AJAX table instead of reloading page
                        if(result.isConfirmed && typeof userTable !== 'undefined') {
                            userTable.draw(false);
                        }
                    });
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
                    }).then(function(result){
                        // Refresh AJAX table instead of reloading page
                        if(result.isConfirmed && typeof userTable !== 'undefined') {
                            userTable.draw(false);
                        }
                    });
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
        cancelButtonColor: APP_COLORS.danger_dark,
        cancelButtonText: __('close'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false
    })
});

$(document).on('click', '.createUserDeptAjax', function(e) {
    e.preventDefault();
    var emp_id = $(this).data('emp_id');
    let hasUserInteracted = false;

    Swal.fire({
        title: __('create_new_user'),
        html: create_user_HTML(),
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        cancelButtonText: __('cancel'),
        confirmButtonText: __('create_user'),
        showLoaderOnConfirm: true,
        allowOutsideClick: () => {
            if (hasUserInteracted) {return false;}
            return !Swal.isLoading();
        },
        didOpen: () => {
            setupInputValidations();
            
            // Function to update button state
            function updateButtonState() {
                var empIdVal = $('#emp_id').val();
                var userTypeVal = $('#user_type').val();
                var confirmBtn = Swal.getConfirmButton();
                
                // Enable button only if both fields have values
                if (empIdVal && userTypeVal) {
                    confirmBtn.disabled = false;
                    confirmBtn.style.opacity = '1';
                } else {
                    confirmBtn.disabled = true;
                    confirmBtn.style.opacity = '0.5';
                }
            }
            
            // Load available employees (those not in admin_login)
            $.ajax({
                url: './includes/ajaxFile/getAvailableEmployees.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.data.length > 0) {
                        let empOptions = '<option value="">' + (__('select_employee') || 'Select Employee') + '</option>';
                        
                        response.data.forEach(function(emp) {
                            empOptions += `<option value="${emp.emp_id}">${emp.display_text}</option>`;
                        });
                        
                        $('#emp_id').html(empOptions);
                        
                        // If emp_id is passed, select it
                        if (emp_id) {
                            $('#emp_id').val(emp_id);
                        }
                        
                        // Initialize Select2 for employee dropdown
                        $('#emp_id').select2({
                            placeholder: __('select_employee') || 'Select Employee',
                            allowClear: true,
                            width: '100%'
                        });
                        
                        // Add change listener for employee selection
                        $('#emp_id').on('change', function() {
                            updateButtonState();
                        });
                        
                        // Initial button state check
                        updateButtonState();
                    } else {
                        $('#emp_id').html('<option value="">' + (__('no_available_employees') || 'No available employees') + '</option>');
                        $('#emp_id').prop('disabled', true);
                        Swal.getConfirmButton().disabled = true;
                        Swal.showValidationMessage(__('no_available_employees') || 'No employees available for user creation');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading employees:', error);
                    $('#emp_id').html('<option value="">' + (__('error_loading_employees') || 'Error loading employees') + '</option>');
                    Swal.getConfirmButton().disabled = true;
                }
            });
            
            // Function to toggle email field requirement based on user type
            function toggleEmailField() {
                var selectedType = $('#user_type').val();
                if (selectedType === 'employee') {
                    $('#email').parent().hide();
                } else {
                    $('#email').parent().show();
                }
            }
            
            // Initial toggle on load
            toggleEmailField();
            
            // Load access control lists for create flow
            loadCompanyAccess(0, $('#user_type').val());
            loadDepartmentAccess(0, $('#user_type').val());
            loadEmployeeAccess(0, $('#user_type').val());
            
            // Toggle on change and update button state
            $('#user_type').on('change', function() {
                toggleEmailField();
                updateButtonState();
                loadCompanyAccess(0, $(this).val());
                loadDepartmentAccess(0, $(this).val());
                loadEmployeeAccess(0, $(this).val());
            });
            
            const onFirstInteraction = () => { hasUserInteracted = true; };
            setupDynamicValidation([
                { id: 'emp_id', event: 'change', validation: (value) => value !== "", requiredMessage: __('select_employee') },
                { id: 'user_type', event: 'change', validation: (value) => value !== "", requiredMessage: __('select_employee_type') }
            ], onFirstInteraction);
            
            // Disable button initially
            Swal.getConfirmButton().disabled = true;
            Swal.getConfirmButton().style.opacity = '0.5';
        },
        preConfirm: () => {
            var selectedEmpId = $('#emp_id').val();
            var selectedType = $('#user_type').val();
            var fullAccess = $('#fullAccessCheckbox').is(':checked');
            var selectedCompanies = $('#allowed_companies').val();
            var fullDeptAccess = $('#fullDeptAccessCheckbox').is(':checked');
            var selectedDepartments = $('#allowed_departments').val();
            var fullEmpAccess = $('#fullEmpAccessCheckbox').is(':checked');
            var selectedEmployees = $('#allowed_employees').val();
            
            // Validate employee selection (always required)
            if(!selectedEmpId || selectedEmpId == "") {
                Swal.showValidationMessage(__('select_employee') || 'Please select an employee');
                return false;
            }
            
            // Validate user type (always required)
            if(!selectedType || selectedType == "") {
                Swal.showValidationMessage(__('select_employee_type'));
                return false;
            }
            
            // Only validate email if user type is not 'employee'
            if(selectedType !== 'employee' && !$('#email').val()) {
                Swal.showValidationMessage(__('enter_valid_email'));
                return false;
            }
            
            // Validate email format if provided
            if($('#email').val() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($('#email').val())) {
                Swal.showValidationMessage(__('enter_valid_email'));
                return false;
            }
            
            // Validate company access (only for non-admin, non-employee users)
            if($('#company-access-group').is(':visible')) {
                if(!fullAccess && (!selectedCompanies || selectedCompanies.length === 0)) {
                    Swal.showValidationMessage(__("select_at_least_one_company") || "Please select at least one company or grant full access");
                    return false;
                }
            }
            
            // Validate department access (only for non-admin, non-employee users)
            if($('#department-access-group').is(':visible')) {
                if(!fullDeptAccess && (!selectedDepartments || selectedDepartments.length === 0)) {
                    Swal.showValidationMessage(__("select_at_least_one_department") || "Please select at least one department or grant full access");
                    return false;
                }
            }
            
            // Validate employee access (only for non-admin, non-employee users)
            if($('#employee-access-group').is(':visible')) {
                if(!fullEmpAccess && (!selectedEmployees || selectedEmployees.length === 0)) {
                    Swal.showValidationMessage(__("select_at_least_one_employee") || "Please select at least one employee or grant full access");
                    return false;
                }
            }
            
            var formData = new FormData($('#createUserForm')[0]);
            formData.append('emp_id', selectedEmpId);
            formData.append('email', $('#email').val());
            formData.append('user_type', selectedType);
            
            // Handle company access
            if(fullAccess) {
                formData.delete('allowed_companies');
                formData.append('full_access', '1');
            } else if(selectedCompanies && selectedCompanies.length > 0) {
                formData.delete('allowed_companies');
                selectedCompanies.forEach(function(companyId) {
                    formData.append('allowed_companies[]', companyId);
                });
                formData.append('full_access', '0');
            }
            
            // Handle department access
            if(fullDeptAccess) {
                formData.delete('allowed_departments');
                formData.append('full_dept_access', '1');
            } else if(selectedDepartments && selectedDepartments.length > 0) {
                formData.delete('allowed_departments');
                selectedDepartments.forEach(function(deptId) {
                    formData.append('allowed_departments[]', deptId);
                });
                formData.append('full_dept_access', '0');
            }
            
            // Handle employee access
            if(fullEmpAccess) {
                formData.delete('allowed_employees');
                formData.append('full_emp_access', '1');
            } else if(selectedEmployees && selectedEmployees.length > 0) {
                formData.delete('allowed_employees');
                selectedEmployees.forEach(function(empId) {
                    formData.append('allowed_employees[]', empId);
                });
                formData.append('full_emp_access', '0');
            }
            
            formData.append('ajaxType', 'create_user');
            
            return $.ajax({
                url: './includes/ajaxFile/ajaxUser.php',
                type: 'POST',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
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
    var $uploadCrop;
    
    Swal.fire({
        title: __('change_employee_image'),
        html: `
        <div class="row customSweetAlertMLR" >
            <div class="col-md-12 text-center mb-3">
                <input type="file" id="emp-img-crop-input" accept="image/*" style="display:none;" />
            </div>
            <div class="col-md-6 text-center">
                <p>${__('new_picture') || 'New Picture'}</p>
                <div id="emp-img" style="width:350px"></div>
            </div>
            <div class="col-md-6" style="align-items: center; display: grid; justify-content: center;">
                <p>${__('current_picture') || 'Current Picture'}</p>
                <img src="${img}" style="width:200px;height:200px" />
            </div>
        </div>`,
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        cancelButtonText: __('close'),
        confirmButtonText: __('yes_update'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '40%',
        didOpen: function() {
            $uploadCrop = $('#emp-img').croppie({
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
            
            // Handle file input change
            $('#emp-img-crop-input').on('change', function () {
                if (this.files && this.files[0]) {
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
                }
            });
            
            // Auto-trigger file input after modal is fully rendered
            setTimeout(function() {
                $('#emp-img-crop-input')[0].click();
            }, 100);
        },
        preConfirm: function() {
            // Check if an image was loaded
            if (!$uploadCrop || !$uploadCrop.croppie) {
                Swal.showValidationMessage(__('please_select_image') || 'Please select an image first');
                return false;
            }
            
            return $uploadCrop.croppie('result', {
                type: 'canvas',
                format: 'png',
                size: 'viewport'
            }).then(function (resp) {
                return $.ajax({
                    url: "./includes/ajaxFile/hrHandler.php",
                    type: "POST",
                    dataType: "JSON",
                    data: {"image": resp, "id": id, "emp_id": emp_id, "emp_name": emp_name, "emptype": emptype, ajaxType: 'avatar'}
                }).then(function(response) {
                    if (response && response.type === 'success') {
                        return response;
                    } else {
                        throw new Error(response.message || 'Upload failed');
                    }
                }).catch(function(error) {
                    Swal.showValidationMessage(error.message || __("request_failed_try_again"));
                    return false;
                });
            }).catch(function(error) {
                Swal.showValidationMessage(__('error_processing_image') || 'Error processing image');
                return false;
            });
        },
    }).then(function(result) {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                title: result.value.title,
                text: result.value.message,
                icon: result.value.type,
                allowOutsideClick: false
            }).then(function() {
                location.reload();
            });
        }
    });
});

// Update Salary Button Click Handler
$(document).on('click', '.updateSalaryBtn', function(e) {
    e.preventDefault();
    const empId = $(this).data('emp_id');
    const isAutoTriggered = $(this).data('auto_triggered') || false;
    const currentSalaryData = {
        basic: $(this).data('basic') || 0,
        housing: $(this).data('housing') || 0,
        transport: $(this).data('transport') || 0,
        food: $(this).data('food') || 0,
        misc: $(this).data('misc') || 0,
        cashier: $(this).data('cashier') || 0,
        fuel: $(this).data('fuel') || 0,
        tel: $(this).data('tel') || 0,
        other: $(this).data('other') || 0,
        guard: $(this).data('guard') || 0
    };
    
    updateEmployeeSalary(empId, currentSalaryData, isAutoTriggered);
});

$(document).on('click', '.addSocial', function (e) {
    e.preventDefault();
    var emp_id = $(this).data('emp_id');
    Swal.fire({
        title: __('add_social_media_links'),
        html: social_add_HTML(),
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        willOpen: function(){
            $('input[name="emp_id"]').val(emp_id);
            $.ajax({
                url: './includes/ajaxFile/hrHandler.php',
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
                    url: './includes/ajaxFile/hrHandler.php',
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
                    url: './includes/ajaxFile/hrHandler.php',
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
                url: './includes/ajaxFile/hrHandler.php',
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
                var filesiz = 1048576 * 8;
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
                Swal.showValidationMessage(__("upload_size_limit_5mb_validation").replace('%s', (filesiz / 1048576).toFixed(0)))
            }
            
            return new Promise(function(reject, resolve) {
                if( docu_typ == '' || fileCheck == "0" || extCheck == true || sizCheck == true ){
                    reject(__("fill_mandatory_fields"));
                    return false;
                }
                $.ajax({
                    url: './includes/ajaxFile/hrHandler.php',
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
        confirmButtonColor: APP_COLORS.primary,
        confirmButtonText: __('submit_action'),
        cancelButtonColor: APP_COLORS.danger_dark,
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
                    url: './includes/ajaxFile/hrHandler.php',
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

$(document).on('click', '#startUpdateRequest', function() {
    const empid = $(this).data('empid');
    const avatarLoad = $(this).data('avatar');
    const mobile = $(this).data('mobile');
    const email = $(this).data('email');
    const address = $(this).data('address');
    const passport_number = $(this).data('passport_number');
    const passport_exp = $(this).data('passport_exp');
    
    // Show field selection modal - pending check happens after field selection
    showUpdateRequestModal(empid, avatarLoad, mobile, email, address, passport_number, passport_exp);
});

// Extracted function to show the update request modal
function showUpdateRequestModal(empid, avatarLoad, mobile, email, address, passport_number, passport_exp) {
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
        allowOutsideClick: false,
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
    ,cancelButtonColor:APP_COLORS.danger_dark,cancelButtonText:__('cancel')}).then((result) => {
        // If the user clicked "Next" and selected a field
        if (result.isConfirmed && result.value) {
            const field = result.value;
            
            // Check if there's a pending request for THIS specific field type
            $.ajax({
                url: './includes/ajaxFile/hrHandler.php',
                type: 'POST',
                dataType: 'JSON',
                data: { ajaxType: 'check_pending_update', empid: empid, type: field },
                success: function(response) {
                    if (response.has_pending) {
                        Swal.fire({
                            title: __('pending_request_title', 'Request Pending'),
                            html: __('pending_request_message', 'You already have a modification request for this field sent and waiting for approval.') + 
                                  '<br><br><strong>' + __('pending_type', 'Field') + ':</strong> ' + response.pending_type + 
                                  '<br><strong>' + __('submitted_on', 'Submitted On') + ':</strong> ' + response.created_at,
                            icon: 'info',
                            confirmButtonText: __('ok'),
                            allowOutsideClick: false
                        }).then(() => {
                            // Return to field selection modal
                            showUpdateRequestModal(empid, avatarLoad, mobile, email, address, passport_number, passport_exp);
                        });
                        return;
                    }
                    
                    // No pending request for this field, proceed with the update modal
                    proceedWithFieldUpdate(field, empid, avatarLoad, mobile, email, address, passport_number, passport_exp);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    handleAjaxFailure(jqXHR, textStatus);
                }
            });
        }
    });
}

// Separate function to handle the actual field update after validation
function proceedWithFieldUpdate(field, empid, avatarLoad, mobile, email, address, passport_number, passport_exp) {
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
                                <input type="file" id="img-crop-input" accept="image/*" style="display:none;" />
                                <div id="emp-img-cropper" style="width:300px; height:300px; margin:auto;"></div>
                            </div>
                        </div>`,
                    showCancelButton: true,
                    confirmButtonColor: APP_COLORS.primary,
                    cancelButtonColor: APP_COLORS.danger_dark,
                    cancelButtonText: __('cancel'),
                    confirmButtonText: __('yes_update'),
                    showLoaderOnConfirm: true,
                    allowOutsideClick: false,
                    width: '500px',
                    didOpen: () => {
                        // Initialize Croppie on the correct element from the modal's HTML.
                        const el = document.getElementById('emp-img-cropper');
                        const $uploadCrop = $(el);
                        
                        $uploadCrop.croppie({
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
                        
                        // Track if image was loaded
                        let imageLoaded = false;
                        
                        // Handle file selection
                        $('#img-crop-input').on('change', function () {
                            if (this.files && this.files[0]) {
                                const reader = new FileReader();
                                reader.onload = function (event) {
                                    // Use the correct method to bind the image to the Croppie instance
                                    $uploadCrop.croppie('bind', { 
                                        url: event.target.result 
                                    }).then(function() {
                                        imageLoaded = true;
                                    });
                                };
                                reader.readAsDataURL(this.files[0]);
                            }
                        });
                        
                        // Trigger the hidden file input
                        $('#img-crop-input').trigger('click');
                        
                        // Store jQuery element and loaded status for preConfirm
                        Swal.getContainer().croppieElement = $uploadCrop;
                        Swal.getContainer().imageLoaded = () => imageLoaded;
                    },
                    preConfirm: () => {
                        // Check if image was loaded
                        if (!Swal.getContainer().imageLoaded()) {
                            Swal.showValidationMessage(__('please_select_image'));
                            return false;
                        }
                        
                        // Get the result from the Croppie instance
                        const $croppie = Swal.getContainer().croppieElement;
                        return $croppie.croppie('result', {
                            type: 'canvas',
                            size: 'viewport',
                            format: 'png'
                        }).then(function (resp) {
                            return $.ajax({
                                url: "./includes/ajaxFile/hrHandler.php",
                                type: "POST",
                                dataType: "JSON",
                                data: {
                                    "profile_img": resp,
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
                        ,allowOutsideClick:false}).then(() => location.reload());
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
                            url: './includes/ajaxFile/hrHandler.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json'
                        }).fail(function() {
                            Swal.showValidationMessage(__("request_failed"));});
                    }
                ,cancelButtonColor:APP_COLORS.danger_dark,cancelButtonText:__('cancel')}).then((finalResult) => {
                    if (finalResult.isConfirmed) {
                        Swal.fire({
                            title: finalResult.value.title,
                            text: finalResult.value.message,
                            icon: finalResult.value.type
                        ,allowOutsideClick:false});
                    }
                });
            }
}

/*$(document).on('click', '.editEmpInfo', function (e) {
    e.preventDefault();
    var emid = $(this).data('emp_id');
    var id = $(this).data('id');
    Swal.fire({
        title: 'Employee Contant information',
        html: edit_emp_chk_HTML(),
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
                    url: './includes/ajaxFile/hrHandler.php',
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
        confirmButtonColor: APP_COLORS.primary,
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
                    url: './includes/ajaxFile/hrHandler.php',
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
        url: './includes/ajaxFile/hrHandler.php',
        data: { ajaxType: 'get_asset_types' },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.assets.length > 0) {
                // Build the options for the select dropdown
                let assetOptions = response.assets.map(asset => {
                    let type = asset.asset_type ? ` (${asset.asset_type})` : '';
                    return `<option value="${asset.id}" data-type="${asset.asset_type || ''}" data-dept="${asset.clearance_dept_id || ''}">${asset.name}${type}</option>`;
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
                    cancelButtonColor: APP_COLORS.danger_dark,
                    confirmButtonText: __('assign'),
                    showLoaderOnConfirm: true,
                    didOpen: () => {
                        $('#swal-assigned-date').datepicker({
                            format: "yyyy-mm-dd",
                            todayHighlight: true,
                            autoclose: true,
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
                        const assetSelect = document.getElementById('swal-asset-id');
                        const assetId = assetSelect.value;
                        const assetType = assetSelect.options[assetSelect.selectedIndex].getAttribute('data-type');
                        const clearanceDeptId = assetSelect.options[assetSelect.selectedIndex].getAttribute('data-dept');
                        const serialNumber = document.getElementById('swal-serial-number').value;
                        const description = document.getElementById('swal-description').value;
                        const assignedDate = document.getElementById('swal-assigned-date').value;
                        // Return the data to be sent via AJAX
                        return {
                            ajaxType: 'assign_asset',
                            emp_id: empId,
                            asset_id: assetId,
                            asset_type: assetType,
                            clearance_dept_id: clearanceDeptId,
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
                            url: './includes/ajaxFile/hrHandler.php',
                            data: result.value, // The data from preConfirm
                            dataType: 'json',
                            success: function(ajaxResponse) {
                                Swal.fire({
                                    title: ajaxResponse.title,
                                    text: ajaxResponse.message,
                                    icon: ajaxResponse.type
                                ,allowOutsideClick:false}).then(() => {
                                    if(ajaxResponse.type === 'success') {
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
// New function to register asset with type and clearance department
function registerAssetModal() {
    Swal.fire({
        title: __('register_new_asset'),
        html: `
            <form id="registerAssetForm" class="text-left">
                <div class="form-group">
                    <label for="swal-asset-name">${__('asset_name')}</label>
                    <input id="swal-asset-name" class="form-control" placeholder="${__('asset_name_placeholder')}">
                </div>
                <div class="form-group">
                    <label for="swal-asset-type">${__('asset_type')}</label>
                    <input id="swal-asset-type" class="form-control" placeholder="${__('asset_type_placeholder')}">
                </div>
                <div class="form-group">
                    <label for="swal-clearance-dept-id">${__('clearance_department_id')}</label>
                    <input id="swal-clearance-dept-id" class="form-control" placeholder="${__('clearance_department_id_placeholder')}">
                </div>
            </form>
        `,
        showCancelButton: true,
        cancelButtonText: __('cancel'),
        confirmButtonText: __('register'),
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const assetName = document.getElementById('swal-asset-name').value;
            const assetType = document.getElementById('swal-asset-type').value;
            const clearanceDeptId = document.getElementById('swal-clearance-dept-id').value;
            if (!assetName || !assetType || !clearanceDeptId) {
                Swal.showValidationMessage(__('fill_all_fields_validation'));
                return false;
            }
            return {
                ajaxType: 'register_asset',
                name: assetName,
                asset_type: assetType,
                clearance_dept_id: clearanceDeptId
            };
        },
        allowOutsideClick: false,
        }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST',
                url: './includes/ajaxFile/hrHandler.php',
                data: result.value,
                dataType: 'json',
                success: function(ajaxResponse) {
                    Swal.fire({
                        title: ajaxResponse.title,
                        text: ajaxResponse.message,
                        icon: ajaxResponse.type
                    ,allowOutsideClick:false}).then(() => {
                        if(ajaxResponse.type === 'success') {
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
}
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
    ,cancelButtonColor:APP_COLORS.danger_dark,cancelButtonText:__('cancel')}).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST',
                url: './includes/ajaxFile/hrHandler.php',
                data: result.value,
                dataType: 'json',
                contentType: false, // Important for file uploads
                processData: false, // Important for file uploads
                success: function(ajaxResponse) {
                    Swal.fire({ title: ajaxResponse.title, text: ajaxResponse.message, icon: ajaxResponse.type })
                    .then(() => { if(ajaxResponse.type === 'success') { location.reload(); } allowOutsideClick:false});
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
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
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
                url: './includes/ajaxFile/hrHandler.php',
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
                    var filesiz = 1048576 * 8;
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
                    Swal.showValidationMessage(__("upload_size_limit_5mb_validation").replace('%s', (filesiz / 1048576).toFixed(0)))
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
        confirmButtonColor: APP_COLORS.primary,
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
        confirmButtonColor: APP_COLORS.primary,
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
        confirmButtonColor: APP_COLORS.primary,
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
        confirmButtonColor: APP_COLORS.primary,
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
 * ALL leave types now require: dates, reason/notes, and attachment
 */
function toggleLeaveFields() {
    const selectedType = $('#leave_type_select').val();
    
    // Hide all sections first
    $('#dateSection, #reasonSection, #attachmentSection').addClass('d-none');
    calculateTotalDays();

    if (!selectedType) return;

    // ALL leave types show: dates, reason, and attachment
    $('#dateSection, #reasonSection, #attachmentSection').removeClass('d-none');
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
    let employeeGender = null;

    function resetLeaveDropzone() {
        if (window.leaveDropzoneInstance) {
            try {
                window.leaveDropzoneInstance.destroy();
            } catch (error) {
                console.error('Error destroying leave Dropzone:', error);
            }
            window.leaveDropzoneInstance = null;
        }
    }

    // First, fetch employee data to get gender
    $.ajax({
        url: './includes/ajaxFile/hrHandler.php',
        type: 'POST',
        dataType: 'json',
        async: false,
        data: {
            ajaxType: "emp_data",
            empid: empid
        },
        success: function(res) {
            if (res.status == 200 && res.data.length > 0) {
                employeeGender = parseInt(res.data[0].sex) || null;
            }
        }
    });

    Swal.fire({
        title: __('loading_employee_info'),
        html: generateLeaveFormHTML(employeeGender),
        width: '50rem',
        showCancelButton: true,
        confirmButtonText: __('submit_request'),
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        cancelButtonText: __('cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        didClose: () => {
            resetLeaveDropzone();
        },
        willOpen: () => {
            // Show a loading state while fetching employee data
            Swal.showLoading();

            // Fetch employee data to get the name
            $.ajax({
                url: './includes/ajaxFile/hrHandler.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    ajaxType: "emp_data",
                    empid: empid
                },
                success: function(res) {
                    if (res.status == 200 && res.data.length > 0) {
                        var employeeName = res.data[0].name;
                        var currentLang = getCurrentLanguage();
                        translateName(employeeName, 'en', currentLang, function(translatedName) {
                            // Update the modal title with the translated employee's name
                            $('.swal2-title').html(`${__('leave_application_for')} <br><span style="color:${APP_COLORS.primary};">${translatedName}</span>`);
                        });
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
            setupGlobalRTLDatepicker();
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
                startDate: '-10d'
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
            $('#leave_type_select').on('change', function() {
                toggleLeaveFields();
                
                // Initialize Dropzone after attachment section becomes visible
                setTimeout(function() {
                    if (!$('#attachmentSection').hasClass('d-none') && $('#leaveDropzone').length > 0 && !window.leaveDropzoneInstance) {
                        initializeLeaveDropzone();
                    }
                }, 200);
            });

            // Function to initialize Dropzone
            function initializeLeaveDropzone() {
                // Double-check element exists
                const dropzoneElement = document.getElementById('leaveDropzone');
                if (!dropzoneElement) {
                    console.error('Dropzone element #leaveDropzone not found in DOM');
                    return;
                }

                // Check if already initialized
                if (window.leaveDropzoneInstance) {
                    if (window.leaveDropzoneInstance.element === dropzoneElement) {
                        return;
                    }
                    resetLeaveDropzone();
                }

                try {
                    Dropzone.autoDiscover = false;
                    const leaveDropzone = new Dropzone(dropzoneElement, {
                        url: '#', // Dummy URL since we'll handle submission via AJAX
                        autoProcessQueue: false,
                        uploadMultiple: true,
                        parallelUploads: 10,
                        maxFiles: 10,
                        maxFilesize: 5, // MB
                        acceptedFiles: '.pdf,.jpg,.jpeg,.png',
                        addRemoveLinks: true,
                        dictDefaultMessage: __('drag_drop_files') || 'Drag & Drop files here or click to browse',
                        dictRemoveFile: __('remove_file') || 'Remove',
                        dictMaxFilesExceeded: __('max_10_files_allowed') || 'Maximum 10 files allowed',
                        dictFileTooBig: __('file_too_large_dropzone') || 'File is too large ({{filesize}}MB). Max: {{maxFilesize}}MB',
                        dictInvalidFileType: __('invalid_file_type') || 'Invalid file type. Only PDF, JPG, PNG allowed',
                        init: function() {
                            this.on('addedfile', function(file) {
                                // console.log('File added:', file.name);
                            });
                            this.on('removedfile', function(file) {
                                // console.log('File removed:', file.name);
                            });
                            this.on('maxfilesexceeded', function(file) {
                                this.removeFile(file);
                                Swal.showValidationMessage(__('max_10_files_allowed') || 'Maximum 10 files allowed');
                                // Clear validation message after 3 seconds
                                setTimeout(() => {
                                    const validationMsg = document.querySelector('.swal2-validation-message');
                                    if (validationMsg) {
                                        validationMsg.style.display = 'none';
                                    }
                                }, 3000);
                            });
                            this.on('error', function(file, errorMessage) {
                                // console.log('Error:', errorMessage);
                                this.removeFile(file);
                                if (typeof errorMessage === 'string') {
                                    Swal.showValidationMessage(errorMessage);
                                    // Clear validation message after 3 seconds
                                    setTimeout(() => {
                                        const validationMsg = document.querySelector('.swal2-validation-message');
                                        if (validationMsg) {
                                            validationMsg.style.display = 'none';
                                        }
                                    }, 3000);
                                }
                            });
                        }
                    });

                    // Store dropzone instance for later access
                    window.leaveDropzoneInstance = leaveDropzone;
                    // console.log('Dropzone initialized successfully');
                } catch (error) {
                    console.error('Error initializing Dropzone:', error);
                }
            }
        },
        preConfirm: () => {
            const form = document.getElementById('applyLeaveForm');
            const formData = new FormData(form);
            formData.append("ajaxType", "applyLeave");
            formData.append("empid", empid);

            // --- UPDATED Validation Logic - ALL fields required for ALL leave types ---
            const leaveType = formData.get('leave_type');
            if (!leaveType) {
                Swal.showValidationMessage(__('select_leave_type_validation'));
                return false;
            }

            // Start date is REQUIRED for all leave types
            const startDate = formData.get('start_date');
            if (!startDate) {
                Swal.showValidationMessage(__('start_date_required'));
                return false;
            }
            
            // End date is REQUIRED for all leave types
            const endDate = formData.get('end_date');
            if (!endDate) {
                Swal.showValidationMessage(__('end_date_required'));
                return false;
            }

            // Validate date range
            if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                Swal.showValidationMessage(__('end_date_before_start_date_validation'));
                return false;
            }

            // Reason/Notes is REQUIRED for ALL leave types
            const reason = formData.get('reason');
            if (!reason || !reason.trim()) {
                Swal.showValidationMessage(__('reason_required_validation'));
                return false;
            }

            // Validate Dropzone attachments
            const dropzone = window.leaveDropzoneInstance;
            const selectedFiles = dropzone && typeof dropzone.getAcceptedFiles === 'function'
                ? dropzone.getAcceptedFiles()
                : (dropzone ? dropzone.files.filter(file => file.status !== Dropzone.CANCELED && file.status !== Dropzone.ERROR) : []);

            if (!dropzone || selectedFiles.length === 0) {
                Swal.showValidationMessage(__('at_least_one_file_required') || 'At least one file is required');
                return false;
            }

            if (selectedFiles.length > 10) {
                Swal.showValidationMessage(__('max_10_files_allowed') || 'Maximum 10 files allowed');
                return false;
            }

            // Add Dropzone files to FormData
            selectedFiles.forEach((file) => {
                formData.append('attachments[]', file);
            });


            // --- AJAX Submission ---
            return $.ajax({
                url: './includes/ajaxFile/leaveHandler.php',
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
                allowOutsideClick:false}).then(() => {
                if (result.value.type === 'success') {
                    location.reload();
                }
            });
        }
    });
});



// $(document).on('click', '.applyvacationAtterXXOrignal', function (e) {
//     e.preventDefault();
//     var empid = $(this).data('empid');
//     var deptId = $(this).data('dept');
//     var country = $(this).data('country');
//     Swal.fire({
//         title: __('apply_vacation_info_title'),
//         html: vacationApply_HTML(country),
//         showCancelButton: true,
//         confirmButtonColor: APP_COLORS.primary,
//         cancelButtonColor: APP_COLORS.danger_dark,
//         confirmButtonText: __('yes_register'),
//         cancelButtonText: __('cancel'),
//         showLoaderOnConfirm: true,
//         allowOutsideClick: false,
//         rtl: true,
//         // width: "50%",
//         willOpen: () => {
//             $('#start_date').datepicker({
//                 format: "yyyy-mm-dd",
//                 todayHighlight: true,
//                 autoclose: true
//             }).on('changeDate', function (e) {
//                 var startDate = e.date;
//                 $('#end_date').datepicker('setStartDate', startDate); // Prevent end date before start
//             });

//             $('#end_date').datepicker({
//                 format: "yyyy-mm-dd",
//                 todayHighlight: true,
//                 autoclose: true
//             }).on('changeDate', function (e) {
//                 var endDate = e.date;
//                 $('#start_date').datepicker('setEndDate', endDate); // Prevent start date after end
//             });
//             // Date picker initialization
//             /*$('#start_date').datepicker({
//                 format: "yyyy-mm-dd",
//                 todayHighlight: true,
//                 autoclose: true,
//                 startDate: '+0d'
//             }).on('changeDate', function(e) {
//                 var startDate = e.date;
//                 var maxEndDate = new Date(startDate);
//                 maxEndDate.setDate(maxEndDate.getDate() + 20.00); // Add 20 days
//                 // Set end date to same as start date initially
//                 $('#end_date').datepicker('setStartDate', startDate);
//                 $('#end_date').datepicker('setEndDate', maxEndDate);
//                 $('#end_date').datepicker('update', startDate); // Auto-set end date to start date
//             });
//             $('#end_date').datepicker({
//                 format: "yyyy-mm-dd",
//                 todayHighlight: true,
//                 autoclose: true
//             }).on('changeDate', function(e) {
//                 // Prevent start date from being after end date
//                 $('#start_date').datepicker('setEndDate', e.date); 
//                 // Calculate if end date is more than 20 days from start
//                 var startDate = $('#start_date').datepicker('getDate');
//                 if (startDate) {
//                     var maxAllowedDate = new Date(startDate);
//                     maxAllowedDate.setDate(maxAllowedDate.getDate() + 20.00);
//                     if (e.date > maxAllowedDate) {
//                         $('#end_date').datepicker('update', maxAllowedDate);
//                         alert('Maximum 20 days range allowed');
//                     }
//                 }
//             });*/
//             /*$('#start_date').datepicker({
//                 format: "yyyy-mm-dd",
//                 todayHighlight: true,
//                 autoclose: true,
//                 startDate: '+0d'
//             }).on('changeDate', function (e) {
//                 var startDate = e.date;
//                 $('#end_date').datepicker('setStartDate', startDate); // Prevent end date before start
//             });

//             $('#end_date').datepicker({
//                 format: "yyyy-mm-dd",
//                 todayHighlight: true,
//                 autoclose: true
//             }).on('changeDate', function (e) {
//                 var endDate = e.date;
//                 $('#start_date').datepicker('setEndDate', endDate); // Prevent start date after end
//             });*/

//             // Initialize Select2 for replacement person dropdown
//             $("#replacement_per").select2();
//             // Load replacement persons
//             $.ajax({
//                 url: './includes/ajaxFile/hrHandler.php',
//                 dataType: 'JSON',
//                 type: 'POST',
//                 data: {ajaxType: "emp_department", dept: deptId},
//                 success: function(res) {
//                     if (res.status == 200) {
//                         let options = '';
//                         for (let i in res.data) {
//                             options += `<option value="${res.data[i].emp_id}">${res.data[i].name.split(' ')[0]+' '+res.data[i].name.split(' ')[1]}</option>`;
//                         }
//                         $('#replacement_per').append(options);
//                     }
//                 },
//                 error: function(j, e) {
//                     errorHandling(j, e);
//                 },
//             });
//             // Load employee data
//             $.ajax({
//                 url: './includes/ajaxFile/hrHandler.php',
//                 dataType: 'JSON',
//                 type: 'POST',
//                 data: {ajaxType: "emp_data", empid: empid},
//                 success: function(res) {
//                     if (res.status == 200) {
//                         $('input[name="name"]').val(res.data[0].name);
//                         $('input[name="empid"]').val(res.data[0].emp_id);
//                     }
//                 },
//                 error: function(j, e) {
//                     errorHandling(j, e);
//                 },
//             });
//             // Toggle fields based on vacation type selection
//             function toggleVacationFields() {
//                 const selectedVac = document.querySelector('input[name="vac_type"]:checked');
//                 // Hide all by default
//                 $('#flyTypeSection, #replacementSection, #date_select, #notesSection').addClass('d-none');
//                 if (!selectedVac) return;
//                 const vacValue = selectedVac.value;
//                 if (vacValue === 'Local Vacation' || vacValue === 'Fly') {
//                     $('#flyTypeSection').removeClass('d-none');
//                     // Check if any fly_type is already selected
//                     const selectedFlyType = document.querySelector('input[name="fly_type"]:checked');
//                     if (selectedFlyType) {
//                         const flyVal = selectedFlyType.value;
//                         if (flyVal === 'annual' || flyVal === 'emergency') {
//                             $('#replacementSection, #date_select').removeClass('d-none');
//                         }
//                     }
//                     // Attach fly_type listener to trigger section toggle
//                     document.querySelectorAll('input[name="fly_type"]').forEach(flyRadio => {
//                         flyRadio.addEventListener('change', function () {
//                             const flyVal = this.value;
//                             if (flyVal === 'annual' || flyVal === 'emergency') {
//                                 $('#replacementSection, #date_select').removeClass('d-none');
//                             } else {
//                                 $('#replacementSection, #date_select').addClass('d-none');
//                             }
//                         });
//                     });
//                 }
//             }
//             // Initialize date picker and fields when form is created
//             function initVacationForm() {
//                 document.querySelectorAll('input[name="vac_type"]').forEach(radio => {
//                     radio.addEventListener('change', toggleVacationFields);
//                 });
//                 toggleVacationFields(); // trigger once on load
//             }
//             initVacationForm();
//         },
//         preConfirm: function() {
//             const formElement = document.getElementById('submitVacationApplyForm');
//             const formData = new FormData(formElement);
//             formData.append("ajaxType", "applyVacation");
//             const selectedRadio = $('input[name="vac_type"]:checked').val();
//             if (!selectedRadio) {
//                 Swal.showValidationMessage(__('select_vacation_type_validation'));
//                 return false;
//             }
//             // Validation for "Local Vacation" or "Fly"
//             if (selectedRadio === 'Local Vacation' || selectedRadio === 'Fly') {
//                 const flyType = $('input[name="fly_type"]:checked').val();
//                 if (!flyType) {
//                     Swal.showValidationMessage(__('select_vacation_type_validation'));
//                     return false;
//                 }
//                 if (flyType === 'annual' || flyType === 'emergency') {
//                     const startDate = $('#start_date').val();
//                     const endDate = $('#end_date').val();
//                     const replacement = $('#replacement_per').val();
//                     if (!startDate || !endDate) {
//                         Swal.showValidationMessage(__('start_return_date_required_validation'));
//                         return false;
//                     }
//                     if (!replacement) {
//                         Swal.showValidationMessage(__('replacement_person_required_validation'));
//                         return false;
//                     }
//                 }
//             }
//             // No extra validation needed for "Encashed"
//             return new Promise(function (resolve, reject) {
//                 $.ajax({
//                     url: './includes/ajaxFile/leaveHandler.php',
//                     type: 'POST',
//                     dataType: "JSON",
//                     cache: false,
//                     contentType: false,
//                     processData: false,
//                     data: formData,
//                 })
//                 .done(function (response) {
//                     Swal.fire({
//                         title: response.title,
//                         text: response.message,
//                         icon: response.type,
//                         allowOutsideClick: false
//                     }).then(function (isConfirm) {
//                         if (isConfirm) location.reload();
//                     });
//                 })
//                 .fail(function (jqXHR, textStatus, errorThrown) {
//                     reject(handleAjaxFailure(jqXHR, textStatus).message);
//                 });
//             });
//         }

//     })
// });

$(document).on('click', '.applyvacationAtter', function (e) {
    e.preventDefault();
    var empid = $(this).data('empid');
    var deptId = $(this).data('dept');
    var country = $(this).data('country');
    var currentBalance = $(this).data('balance') || 0;

    // Quick pre-check: block opening the modal if there's already a pending request
    // Note: We'll allow emergency vacation even with pending requests
    try {
        $.ajax({
            url: './includes/ajaxFile/leaveHandler.php',
            type: 'POST',
            dataType: 'json',
            data: { ajaxType: 'canApplyVacation', emp_id: empid, is_emergency: 0 },
        }).done(function(res) {
            if (!res || res.ok === false) {
                Swal.fire({ title: 'Error', text: (res && res.message) ? res.message : 'Unable to verify eligibility.', icon: 'error' ,allowOutsideClick:false});
                return;
            }
            if (res.can_apply === false) {
                const shouldForceEmergency = (parseFloat(currentBalance) || 0) < 1;
                // Build a richer status message if details are available, plus the full approval chain
                const esc = (s) => String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]));
                let lines = [];
                if (res.pending_inv) lines.push(`${__('request') || 'Request'}: ${esc(res.pending_inv)}`);
                if (res.current_status || res.current_level) {
                    const statusText = (function(){
                        if (res.current_status === 'approved') return __('approved') || 'Approved';
                        if (res.current_status === 'rejected') return __('rejected') || 'Rejected';
                        return __('pending_approval') || 'Pending approval';
                    })();
                    const levelText = res.current_level ? ` (${__('level') || 'Level'} ${esc(res.current_level)})` : '';
                    lines.push(`${__('current_status_label') || 'Current status'}: ${statusText}${levelText}`);
                }
                if (res.current_approver_name) {
                    lines.push(`${__('pending_with') || 'Pending with'}: ${esc(res.current_approver_name)}`);
                }

                // Build approval chain HTML
                let chainHtml = '';
                if (Array.isArray(res.chain) && res.chain.length) {
                    const labelRaw = __('approval_chain');
                    const label = (labelRaw && labelRaw !== 'approval_chain') ? labelRaw : 'Approval chain';
                    const statusLabel = (s) => {
                        if (s === 'approved') return __('approved') || 'Approved';
                        if (s === 'pending') return __('pending') || 'Pending';
                        if (s === 'awaiting') return __('awaiting') || 'Awaiting';
                        if (s === 'rejected') return __('rejected') || 'Rejected';
                        return esc(s);
                    };
                    const icon = (s) => s === 'approved' ? '✅' : (s === 'pending' ? '🟡' : (s === 'awaiting' ? '⏸️' : (s === 'rejected' ? '❌' : 'ℹ️')));
                    const rows = res.chain
                        .sort((a, b) => (a.level||0) - (b.level||0))
                        .map(step => `<div style="text-align:left;">${icon(step.status)} ${__('level') || 'Level'} ${esc(step.level)}: ${esc(step.name)} — ${statusLabel(step.status)}</div>`) 
                        .join('');
                    chainHtml = `<hr/><div style="text-align:left;"><strong>${label}:</strong></div>${rows}`;
                }

                const textMsg = res.message || lines.join('\n');
                
                // Add option text based on balance: emergency only when balance is below 1
                const htmlTop = esc(textMsg).replace(/\n/g, '<br/>');
                const nextApplyNote = shouldForceEmergency
                    ? (__('you_can_apply_for_emergency_vacation_with_different_dates') || 'You can apply for emergency vacation with different dates.')
                    : (__('you_can_apply_for_another_vacation_with_different_date') || 'You can apply for another vacation with different dates.');
                const fullHtml = chainHtml 
                    ? `${htmlTop}${chainHtml}<hr/><p style="margin-top:15px;"><strong>${__('note') || 'Note'}:</strong> ${nextApplyNote}</p>`
                    : `${htmlTop}<br/><br/><strong>${__('note') || 'Note'}:</strong> ${nextApplyNote}`;
                
                Swal.fire({ 
                    title: __('cannot_apply_now') || 'Cannot Apply', 
                    html: fullHtml, 
                    icon: 'info', 
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonText: __('apply_another_vacation') || 'Apply Another Vacation',
                    cancelButtonText: __('cancel') || 'Cancel',
                    confirmButtonColor: APP_COLORS.danger_dark,
                    cancelButtonColor: APP_COLORS.secondary,
                }).then((result) => {
                    if (result.isConfirmed) {
                        openVacationApplyModal(empid, deptId, country, currentBalance, shouldForceEmergency, res.active_return_date, !shouldForceEmergency);
                    }
                });
                return;
            }

            // Proceed to open the modal as usual, passing active_return_date if available
            openVacationApplyModal(empid, deptId, country, currentBalance, false, res.active_return_date || null);
        }).fail(function(jqXHR){
            let msg = 'Unable to verify eligibility.';
            try { let j = JSON.parse(jqXHR.responseText); if (j.message) msg = j.message; } catch(e) {}
            Swal.fire({ title: 'Error', text: msg, icon: 'error' ,allowOutsideClick:false});
        });
    } catch(err) {
        Swal.fire({ title: 'Error', text: 'Unexpected error. Please try again.', icon: 'error' ,allowOutsideClick:false});
    }
});

// Extracted function to open the Apply Vacation modal after eligibility check
function openVacationApplyModal(empid, deptId, country, currentBalance, forceEmergency, activeReturnDate, preferAnotherTitle) {
    currentBalance = currentBalance || 0;
    forceEmergency = forceEmergency || false;
    activeReturnDate = activeReturnDate || null;
    preferAnotherTitle = preferAnotherTitle || false;

    const modalTitleText = forceEmergency
        ? __('apply_emergency_vacation')
        : (preferAnotherTitle ? (__('apply_another_vacation') || 'Apply Another Vacation') : __('apply_vacation_info_title'));

    Swal.fire({
        title: '<i class="fa fa-umbrella-beach"></i> ' + modalTitleText,
        html: vacationApply_HTML(country),
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        confirmButtonText: '<i class="fa fa-check"></i> ' + __('yes_register'),
        cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        customClass: {
            popup: 'vacation-modal-popup',
            title: 'vacation-modal-title',
            confirmButton: 'btn-modern-confirm',
            cancelButton: 'btn-modern-cancel'
        },
        width: '95%',
        padding: '20px',
        willOpen: () => {
            const swalModal = Swal.getHtmlContainer();
            
            // [NEW] Hide/Show vacation options based on balance
            // If balance >= 1: Hide Emergency Vacation, Show Annual Vacation and Encashed
            // If balance < 1: Hide Annual Vacation and Encashed, Show Emergency Vacation
            if (swalModal) {
                if (currentBalance >= 1) {
                    // Employee has sufficient balance - hide Emergency Vacation, show Annual and Encashed
                    console.log('Current Balance:', currentBalance, '- Hiding Emergency Vacation, showing Annual and Encashed');
                    $(swalModal).find('*').each(function() {
                        const text = $(this).text().trim();
                        if (text === 'Emergency vacation') {
                            $(this).hide();
                            console.log('Hidden Emergency vacation element');
                            return false; // break
                        }
                    });
                } else {
                    // Employee has insufficient balance (< 1 day) - hide Annual and Encashed, show Emergency
                    console.log('Current Balance:', currentBalance, '- Hiding Annual Vacation and Encashed, showing Emergency');
                    $(swalModal).find('*').each(function() {
                        const text = $(this).text().trim();
                        if (text === 'Annual vacation' || text === 'Encashed') {
                            $(this).hide();
                            console.log('Hidden:', text);
                        }
                    });
                }
            }
            
            // Helper function to check if Emergency vacation is currently selected
            const isEmergencySelected = () => {
                const flyType = $('input[name="fly_type"]:checked').val();
                return flyType === 'emergency';
            };

            setupGlobalRTLDatepicker();
            
            // Helper function to initialize date pickers with proper restrictions
            const initializeDatePickers = () => {
                const isEmergency = isEmergencySelected();
                
                // Helper to format date as YYYY-MM-DD
                const formatDateToString = (date) => {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                };
                
                // Determine the minimum start date
                let minStartDate = null;
                
                // If there's an active return date, start from day after return
                if (activeReturnDate) {
                    const parts = activeReturnDate.split('-');
                    minStartDate = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                    minStartDate.setDate(minStartDate.getDate() + 1); // Day AFTER return date
                    const formattedDate = formatDateToString(minStartDate);
                    console.log('✅ Active vacation found - Return date:', activeReturnDate, '- Min start date for 2nd vacation:', formattedDate);
                } else {
                    // No active vacation - allow from 3 days ago onwards
                    minStartDate = new Date();
                    minStartDate.setDate(minStartDate.getDate() - 3); // 3 days ago
                    const formattedDate = formatDateToString(minStartDate);
                    console.log('✅ No active vacation - Min start date:', formattedDate);
                }
                
                const minStartDateString = formatDateToString(minStartDate);
                console.log('🔄 initializeDatePickers called - activeReturnDate:', activeReturnDate, 'minStartDate:', minStartDateString);
                
                const startDateConfig = {
                    format: "yyyy-mm-dd",
                    todayHighlight: false,
                    autoclose: true,
                    startDate: minStartDateString
                };
                
                const endDateConfig = {
                    format: "yyyy-mm-dd",
                    todayHighlight: false,
                    autoclose: true,
                    startDate: minStartDateString
                };
                
                console.log('📅 Date picker configuration - startDate (string):', minStartDateString);
                console.log('📅 Date picker configuration - startDate (object):', minStartDate);
                
                // Try both string and Date object formats for better compatibility
                startDateConfig.startDate = minStartDate;  // Use Date object
                endDateConfig.startDate = minStartDate;    // Use Date object
                
                // Remove existing datepicker instances before creating new ones
                try {
                    $('#start_date').datepicker('destroy');
                    $('#end_date').datepicker('destroy');
                } catch(e) {
                    console.log('Date pickers not yet initialized');
                }
                
                $('#start_date').datepicker(startDateConfig);
                // Use setStartDate method to ensure proper restriction even for past dates
                $('#start_date').datepicker('setStartDate', minStartDate);
                $('#start_date').on('changeDate', function (e) {
                    var startDate = e.date;
                    $('#end_date').datepicker('setStartDate', startDate);
                    $('#departure_date').datepicker('setStartDate', startDate);
                    $('#arrival_date').datepicker('setStartDate', startDate);
                    calculateVacationDays();
                });

                $('#end_date').datepicker(endDateConfig);
                // Use setStartDate method to ensure proper restriction even for past dates
                $('#end_date').datepicker('setStartDate', minStartDate);
                $('#end_date').on('changeDate', function (e) {
                    var endDate = e.date;
                    $('#start_date').datepicker('setEndDate', endDate);
                    $('#departure_date').datepicker('setEndDate', endDate);
                    $('#arrival_date').datepicker('setEndDate', endDate);
                    calculateVacationDays();
                });
            };
            // If forceEmergency is true, pre-select Fly and Emergency vacation
            // if (forceEmergency) {
            //     setTimeout(() => {
            //         // Select "Fly" vacation type
            //         $('#inlineRadio1').prop('checked', true).trigger('change');
                    
            //         // Show flyTypeSection and select "emergency"
            //         setTimeout(() => {
            //             $('#vac_type2').prop('checked', true).trigger('change');
            //             // Initialize date pickers AFTER emergency is selected
            //             setTimeout(() => {
            //                 initializeDatePickers();
            //             }, 50);
            //         }, 100);
            //     }, 100);
            // } else {
            //     // Initialize date pickers normally if not emergency
            //     initializeDatePickers();
            // }
            initializeDatePickers();

            // Function to calculate and display vacation days
            function calculateVacationDays() {
                var startDate = $('#start_date').datepicker('getDate');
                var endDate = $('#end_date').datepicker('getDate');
                
                if (startDate && endDate) {
                    // Calculate difference in days (inclusive)
                    var timeDiff = endDate.getTime() - startDate.getTime();
                    var daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                    
                    // Display the vacation days
                    $('#vacation_days_count').text(daysDiff);
                    $('#vacation_days_display').removeClass('d-none');
                } else {
                    $('#vacation_days_display').addClass('d-none');
                }

                updateLocalVacationSalaryVisibility();
            }

            function updateLocalVacationSalaryVisibility() {
                const selectedVac = $('input[name="vac_type"]:checked').val();
                const selectedFly = $('input[name="fly_type"]:checked').val();

                if (!(selectedVac === 'Local Vacation' && selectedFly === 'annual')) {
                    return;
                }

                // Local annual must always show explicit vacation salary payment choice.
                $('#salaryTypeSection').removeClass('d-none');

                const startDate = $('#start_date').datepicker('getDate') || ($('#start_date').val() ? new Date($('#start_date').val()) : null);
                const endDate = $('#end_date').datepicker('getDate') || ($('#end_date').val() ? new Date($('#end_date').val()) : null);

                let localVacationDays = 0;
                if (startDate instanceof Date && !isNaN(startDate) && endDate instanceof Date && !isNaN(endDate)) {
                    localVacationDays = Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 3600 * 24)) + 1;
                }

                if (localVacationDays > 0 && localVacationDays <= 5) {
                    $('#salary_with_payroll').prop('checked', true);
                    $('#salary_with_eos').prop('checked', false).prop('disabled', true);
                } else {
                    $('#salary_with_eos').prop('disabled', false);
                }
            }

            // Initialize departure and arrival date pickers
            $('#departure_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: false,
                autoclose: true,
                startDate: '+1d'
            }).on('changeDate', function (e) {
                var departureDate = e.date;
                $('#arrival_date').datepicker('setStartDate', departureDate);
            });

            $('#arrival_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: false,
                autoclose: true,
                startDate: '+1d'
            }).on('changeDate', function (e) {
                var arrivalDate = e.date;
                $('#departure_date').datepicker('setEndDate', arrivalDate);
            });

            // Original replacement person loader
            $("#replacement_per").select2({
                dropdownParent: $(swalModal) // Attach to modal
            });
            $.ajax({
                url: './includes/ajaxFile/hrHandler.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "emp_department", dept: deptId, exclude_emp_id: empid, for_replacement: 1},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        const currentLang = getCurrentLanguage();
                        let pendingTranslations = res.data.length;
                        
                        for (let i in res.data) {
                            const emp = res.data[i];
                            // Defensive: ensure name has at least two parts
                            const nameParts = emp.name ? emp.name.split(' ') : [];
                            const displayName = nameParts.length >= 2 ? (nameParts[0] + ' ' + nameParts[1]) : emp.name;
                            
                            // Translate the display name
                            translateName(displayName, 'en', currentLang, function(translatedName) {
                                // Update the option with translated name
                                const optionHtml = `<option value="${emp.emp_id}">${translatedName}</option>`;
                                // Store in data attribute for later use
                                $(`#replacement_per`).append(optionHtml);
                                
                                pendingTranslations--;
                                if (pendingTranslations === 0) {
                                    // All translations done, add NONE option
                                    $('#replacement_per').append(`<option value="N/A">${__('no_replacement_available')}</option>`);
                                    $("#replacement_per").select2('destroy').select2({
                                        dropdownParent: $(swalModal)
                                    });
                                }
                            });
                        }
                        
                        if (res.data.length === 0) {
                            // No available replacement persons
                            $('#replacement_per').append(`<option value="N/A" selected>${__('no_replacement_available')}</option>`);
                        }
                    } else {
                        // Non-200 status, still provide a NONE fallback
                        $('#replacement_per').append(`<option value="N/A" selected>${__('no_replacement_available')}</option>`);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e);
                    // On error also ensure user can proceed without replacement
                    if (!$('#replacement_per option').length) {
                        $('#replacement_per').append(`<option value="N/A" selected>${__('no_replacement_available')}</option>`);
                    }
                },
            });

            // Original emp_data loader
            $.ajax({
                url: './includes/ajaxFile/hrHandler.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "emp_data", empid: empid},
                success: function(res) {
                    if (res.status == 200) {
                        
                        var employeeName = res.data[0].name;
                        var currentLang = getCurrentLanguage();
                        translateName(employeeName, 'en', currentLang, function(translatedName) {
                            $('input[name="name"]').val(translatedName);
                        });

                        $('input[name="empid"]').val(res.data[0].emp_id);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e);
                },
            });

            // ...existing code...
            
            // MODIFIED: Toggle Fields Logic - now includes salary type section, flight dates, remarks, and encashment
            function toggleVacationFields() {
                const selectedVac = document.querySelector('input[name="vac_type"]:checked');
                $('#flyTypeSection, #replacementSection, #date_select, #notesSection, #salaryTypeSection, #flightDatesSection, #encashSection').addClass('d-none');
                if (!selectedVac) return;
                const vacValue = selectedVac.value;
                if (vacValue === 'Encashed') {
                    // Show encashment section
                    $('#encashSection').removeClass('d-none');

                    const parseEncashWholeDays = (value) => {
                        const match = String(value || '').trim().match(/^(\d+)/);
                        return match ? (parseInt(match[1], 10) || 0) : 0;
                    };
                    
                    // Show loading state
                    $('#vacation_balance_display').text('Loading...');
                    
                    // Fetch current balance from server
                    $.ajax({
                        url: './includes/ajaxFile/leaveHandler.php',
                        type: 'POST',
                        dataType: 'JSON',
                        data: {ajaxType: "getCurrentVacationBalance", empid: empid},
                        success: function(res) {
                            // console.log('Balance Response:', res);
                            if (res && res.status == 200) {
                                var balance = parseFloat(res.balance) || 0;
                                var maxWholeDays = Math.max(0, Math.floor(balance));
                                $('#vacation_balance_display').text(balance.toFixed(2));
                                $('#encash_days').attr('max', maxWholeDays);
                            } else {
                                console.error('Failed to fetch balance:', res);
                                $('#vacation_balance_display').text('0.00');
                                $('#encash_days').attr('max', 0);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Balance AJAX Error:', error);
                            console.error('Response Text:', xhr.responseText);
                            $('#vacation_balance_display').text('0.00');
                            $('#encash_days').attr('max', 0);
                        }
                    });
                    
                    // Enforce whole-number days only and keep display as X.00
                    $('#encash_days')
                    .off('focus input blur')
                    .on('focus', function() {
                        const days = parseEncashWholeDays($(this).val());
                        if (days > 0) {
                            $(this).val(days);
                        }
                    })
                    .on('input', function() {
                        const digitsOnly = String($(this).val() || '').replace(/\D/g, '');
                        $(this).val(digitsOnly);

                        var days = parseInt(digitsOnly, 10) || 0;
                        var maxDays = parseInt($(this).attr('max'), 10) || 0;
                        
                        if (maxDays > 0 && days > maxDays) {
                            days = maxDays;
                            $(this).val(days);
                        }
                        
                        if (days > 0) {
                            // Fetch salary from backend
                            $.ajax({
                                url: './includes/ajaxFile/hrHandler.php',
                                type: 'POST',
                                dataType: 'JSON',
                                data: {ajaxType: "calculate_encash_salary", empid: empid, days: days},
                                success: function(res) {
                                    // console.log('Salary Calculation Response:', res);
                                    if (res && res.status == 200) {
                                        $('#encashment_salary_display').text(res.salary);
                                    } else {
                                        $('#encashment_salary_display').text('0');
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Salary Calculation Error:', error, xhr.responseText);
                                    $('#encashment_salary_display').text('0');
                                }
                            });
                        } else {
                            $('#encashment_salary_display').text('0');
                        }
                    })
                    .on('blur', function() {
                        var days = parseEncashWholeDays($(this).val());
                        var maxDays = parseInt($(this).attr('max'), 10) || 0;

                        if (maxDays > 0 && days > maxDays) {
                            days = maxDays;
                        }

                        if (days > 0) {
                            $(this).val(days + '.00');
                        } else {
                            $(this).val('');
                        }
                    });
                } else if (vacValue === 'Local Vacation' || vacValue === 'Fly') {
                    $('#flyTypeSection').removeClass('d-none');
                    const selectedFlyType = document.querySelector('input[name="fly_type"]:checked');
                    if (selectedFlyType) {
                        const flyVal = selectedFlyType.value;
                        if (flyVal === 'annual' || flyVal === 'emergency') {
                            $('#replacementSection, #date_select').removeClass('d-none');
                            // NEW: Show salary type selection for BOTH Fly + Annual AND Local Vacation + Annual
                            if (flyVal === 'annual') {
                                if (vacValue === 'Local Vacation') {
                                    updateLocalVacationSalaryVisibility();
                                } else {
                                    $('#salaryTypeSection').removeClass('d-none');
                                }
                                // Show flight dates AND remarks ONLY for Fly + Annual (NOT Local Vacation) and NOT for country 191 (Saudi Arabia)
                                if (vacValue === 'Fly' && country !== '191') {
                                    $('#flightDatesSection, #notesSection').removeClass('d-none');
                                } else {
                                    // Explicitly hide flight dates for Local Vacation or country 191
                                    $('#flightDatesSection, #notesSection').addClass('d-none');
                                }
                            }
                        }
                    }
                    document.querySelectorAll('input[name="fly_type"]').forEach(flyRadio => {
                        flyRadio.addEventListener('change', function () {
                            const flyVal = this.value;
                            // Re-check the current vacation type (don't use stale vacValue)
                            const currentVacType = document.querySelector('input[name="vac_type"]:checked');
                            const currentVacValue = currentVacType ? currentVacType.value : '';
                            if (flyVal === 'annual' || flyVal === 'emergency') {
                                $('#replacementSection, #date_select').removeClass('d-none');
                                // NEW: Show salary type selection for BOTH Fly + Annual AND Local Vacation + Annual
                                if (flyVal === 'annual') {
                                    if (currentVacValue === 'Local Vacation') {
                                        updateLocalVacationSalaryVisibility();
                                    } else {
                                        $('#salaryTypeSection').removeClass('d-none');
                                    }
                                    // Show flight dates AND remarks ONLY for Fly + Annual and NOT for country 191
                                    if (currentVacValue === 'Fly' && country !== '191') {
                                        $('#flightDatesSection, #notesSection').removeClass('d-none');
                                    } else {
                                        // Explicitly hide for Local Vacation or country 191
                                        $('#flightDatesSection, #notesSection').addClass('d-none');
                                    }
                                } else {
                                    $('#salaryTypeSection, #flightDatesSection, #notesSection').addClass('d-none');
                                }
                            } else {
                                $('#replacementSection, #date_select, #salaryTypeSection, #flightDatesSection, #notesSection').addClass('d-none');
                            }
                            // Re-initialize date pickers when fly type changes to apply proper restrictions
                            setTimeout(() => {
                                console.log('🔄 Fly type changed to:', flyVal);
                                initializeDatePickers();
                            }, 100);
                        });
                    });
                }
            }
            function initVacationForm() {
                document.querySelectorAll('input[name="vac_type"]').forEach(radio => {
                    radio.addEventListener('change', toggleVacationFields);
                });
                toggleVacationFields();
            }
            initVacationForm();
        },
        preConfirm: function() {
            const formElement = document.getElementById('submitVacationApplyForm');
            const formData = new FormData(formElement);
            formData.append("ajaxType", "applyVacation");
            formData.append("emp_id", empid);
            formData.append("dept_id", deptId);


            const selectedRadio = $('input[name="vac_type"]:checked').val();
            if (!selectedRadio) {
                Swal.showValidationMessage(__('select_vacation_type_validation'));
                return false;
            }

            const balance = parseFloat(currentBalance) || 0;
            let isEmergencySelection = false;
            if (selectedRadio === 'emergency') {
                isEmergencySelection = true;
                if (balance >= 1) {
                    Swal.showValidationMessage(__('emergency_vacation_requires_zero_balance') || 'Emergency Vacation is only available when you have insufficient balance. Please use regular vacation.');
                    return false;
                }
            }

            if (selectedRadio === 'Encashed') {
                if (balance < 1) {
                    Swal.showValidationMessage(__('only_emergency_allowed_when_balance_below_one') || 'Your available balance is below 1 day. Please apply for Emergency Vacation only.');
                    return false;
                }
                const encashDays = parseInt(String($('#encash_days').val() || '').match(/^(\d+)/)?.[1] || '0', 10) || 0;
                const currentDisplayedBalance = parseFloat($('#vacation_balance_display').text()) || 0;
                const maxEncashDays = Math.max(0, Math.floor(currentDisplayedBalance));
                if (!encashDays || encashDays < 1) {
                    Swal.showValidationMessage(__('enter_days_to_encash_validation') || 'Please enter number of days to encash');
                    return false;
                }
                if (encashDays > maxEncashDays) {
                    Swal.showValidationMessage(__('encash_days_exceeds_balance') || 'You cannot encash more than your balance');
                    return false;
                }
                // Attach encashment info to formData
                formData.append('encash_days', encashDays.toFixed(2));
                formData.append('encashment_salary', $('#encashment_salary_display').text());
            } else if (selectedRadio === 'Local Vacation' || selectedRadio === 'Fly') {
                const flyType = $('input[name="fly_type"]:checked').val();
                if (!flyType) {
                    Swal.showValidationMessage(__('select_vacation_type_validation'));
                    return false;
                }
                if (flyType === 'emergency') {
                    isEmergencySelection = true;
                }
                if (balance < 1 && !isEmergencySelection) {
                    Swal.showValidationMessage(__('only_emergency_allowed_when_balance_below_one') || 'Your available balance is below 1 day. Please apply for Emergency Vacation only.');
                    return false;
                }
                if (balance >= 1 && isEmergencySelection) {
                    Swal.showValidationMessage(__('emergency_vacation_requires_zero_balance') || 'Emergency Vacation is only available when you have insufficient balance. Please use regular vacation.');
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
                    // Validate flight dates for Fly + Annual vacation
                    if (selectedRadio === 'Fly' && flyType === 'annual') {
                        const departureDate = $('#departure_date').val();
                        const arrivalDate = $('#arrival_date').val();
                        if (!departureDate || !arrivalDate) {
                            Swal.showValidationMessage(__('flight_dates_required_validation') || 'Please select departure and arrival dates');
                            return false;
                        }
                        // Validate that flight dates are within vacation period
                        const start = new Date(startDate);
                        const end = new Date(endDate);
                        const departure = new Date(departureDate);
                        const arrival = new Date(arrivalDate);
                        if (departure < start || departure > end) {
                            Swal.showValidationMessage(__('departure_date_must_be_between_vacation_dates') || 'Departure date must be between start date and return date');
                            return false;
                        }
                        if (arrival < start || arrival > end) {
                            Swal.showValidationMessage(__('arrival_date_must_be_between_vacation_dates') || 'Arrival date must be between start date and return date');
                            return false;
                        }
                    }
                    // NEW: Validate vacation salary type selection for annual vacations ONLY (Emergency vacation is unpaid)
                    if (flyType === 'annual') {
                        const localVacationDays = (function () {
                            if (selectedRadio !== 'Local Vacation') {
                                return null;
                            }
                            const start = new Date(startDate);
                            const end = new Date(endDate);
                            if (isNaN(start) || isNaN(end)) {
                                return null;
                            }
                            return Math.ceil((end.getTime() - start.getTime()) / (1000 * 3600 * 24)) + 1;
                        })();

                        if (selectedRadio === 'Local Vacation' && localVacationDays !== null && localVacationDays <= 5) {
                            formData.set('vacation_salary_type', 'payroll');
                        } else {
                            const salaryType = $('input[name="vacation_salary_type"]:checked').val();
                            if (!salaryType) {
                                Swal.showValidationMessage(__('vacation_salary_type_required') || 'Please select vacation salary payment option');
                                return false;
                            }
                        }
                    }
                    
                    // Note: Emergency vacation does NOT require balance check as it is unpaid
                    // Balance validation is only for Annual vacation or Encashed vacation
                }
            }
            
            // Add flag to indicate if this is emergency vacation (for backend processing)
            const flyType = $('input[name="fly_type"]:checked').val();
            if (flyType === 'emergency') {
                formData.append('is_emergency', '1');
            }

            // NEW: Automatically set direct supervisor as first approver
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: './includes/ajaxFile/hrHandler.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: { ajaxType: 'get_direct_supervisor', emp_id: empid },
                }).done(function(res) {
                    if (res && res.supervisor_id) {
                        formData.append('first_approver_id', res.supervisor_id);
                    }
                    
                    // DEBUG: Log FormData contents
                    // console.log('=== FormData Contents ===');
                    for (let pair of formData.entries()) {
                        // console.log(pair[0] + ': ' + pair[1]);
                    }
                    // console.log('departure_date value:', $('#departure_date').val());
                    // console.log('arrival_date value:', $('#arrival_date').val());
                    // console.log('vacation_salary_type checked:', $('input[name="vacation_salary_type"]:checked').val());
                    // console.log('========================');
                    
                    $.ajax({
                        url: './includes/ajaxFile/leaveHandler.php',
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
                            if (isConfirm.value && response.type === 'success') {
                                location.reload();
                            }
                        });
                        resolve();
                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        let errorMsg = 'An error occurred. Please try again.';
                        try {
                            let jsonResponse = JSON.parse(jqXHR.responseText);
                            if (jsonResponse && jsonResponse.message) {
                                errorMsg = jsonResponse.message;
                            } else if (jqXHR.responseText) {
                                let responseText = jqXHR.responseText.split('<br />');
                                errorMsg = responseText[responseText.length - 1].replace(/<b>Warning<\/b>:|<b>Fatal error<\/b>:|Uncaught \(in promise\) Error!:/gi, '').trim();
                            }
                        } catch (e) {}
                        Swal.fire({
                            title: 'Error!',
                            text: errorMsg,
                            icon: 'error'
                        ,allowOutsideClick:false});
                        reject(errorMsg);
                    });
                }).fail(function() {
                    Swal.fire({
                        title: __('error'),
                        text: __('could_not_find_supervisor'),
                        icon: 'error',
                        allowOutsideClick: false
                    });
                    reject(__('could_not_find_supervisor'));
                });
            });
        }
    })
}



// --- Main Script Logic
function add_noties() {
    const empid = $(this).data('emp_id');
    Swal.fire({
        title: __('add_note_to_employee_title'),
        html: add_note_HTML(),
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        cancelButtonText: __('cancel'),
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        width: '600px',
        preConfirm: () => {
            const note = $('#note').val();
            const noteType = $('#note_type').val();
            const attachmentFile = document.getElementById('attachment').files[0];
            
            // Validation
            if (!noteType) {
                Swal.showValidationMessage(__('select_note_type_validation') || 'Please select note type');
                return false;
            }
            if (!note || note.trim() === '') {
                Swal.showValidationMessage(__('enter_notes_validation'));
                return false;
            }

            // Validate file size if attachment is provided (max 5MB)
            if (attachmentFile && attachmentFile.size > 5 * 1024 * 1024) {
                Swal.showValidationMessage(__('file_too_large') || 'File size must be less than 5MB');
                return false;
            }

            // Validate file type if attachment is provided
            if (attachmentFile) {
                const allowedTypes = ['application/pdf', 'application/msword', 
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(attachmentFile.type)) {
                    Swal.showValidationMessage(__('invalid_file_type') || 'Invalid file type. Only PDF, DOC, DOCX, JPG, PNG allowed');
                    return false;
                }
            }

            // Create FormData for file upload
            const formData = new FormData();
            formData.append('empid', empid);
            formData.append('note', note.trim());
            formData.append('note_type', noteType);
            formData.append('ajaxType', 'add_note');
            
            if (attachmentFile) {
                formData.append('attachment', attachmentFile);
            }

            return new Promise((resolve, reject) => {
                $.ajax({
                    url: './includes/ajaxFile/hrHandler.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
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
                    reject(__('failed_to_add_note') || 'Failed to add note');
                    console.error('Error:', error);
                });
            });
        },
        allowOutsideClick: false
    });
}



////////////////////////////////////////////////////////////////////
////////////       End Employee vacation /*Handling      /////////////
// ================================================================
// New: Global modal to add/edit overtime/deduction adjustments
// Can be triggered from any page after a vacation is approved
// Usage (direct): addVacationAdjustments(vacId, empName, otHrs, dedHrs, dedDays, otherEarnings, note)
// Usage (via DOM): add a button with class 'addVacationAdjustments' and data-*
//    data-vacation-id, data-employee-name, data-overtime-hours, data-deduction-hours, data-deduction-days, data-other-earnings, data-payroll-note
// ================================================================
if (typeof window.addVacationAdjustments === 'undefined') {
    window.addVacationAdjustments = function(vacationId, employeeName, currentOvertimeHours, currentDeductionHours, currentDeductionDays, otherEarningsOrNote, currentPayrollNote, currentOtherDeductions) {
        try {
            // Handle backward compatibility: if otherEarningsOrNote is a string, treat it as payroll_note
            let currentOtherEarnings = 0;
            let payrollNote = currentPayrollNote || '';
            let currentOtherDeductionsVal = parseFloat(currentOtherDeductions) || 0;
            
            // Check if parameter 6 is a numeric string (other_earnings) or text string (payroll_note from old code)
            // isNaN() returns false for numeric strings, true for non-numeric strings
            if (typeof otherEarningsOrNote === 'string' && isNaN(otherEarningsOrNote)) {
                // It's a non-numeric string, treat it as payroll_note (old format)
                payrollNote = otherEarningsOrNote;
                currentOtherEarnings = 0;
            } else {
                // It's a numeric string or number, treat it as other_earnings (new format)
                currentOtherEarnings = parseFloat(otherEarningsOrNote) || 0;
            }

            Swal.fire({
                title: __('add_edit_adjustments_for', 'Add/Edit adjustments for {0}').replace('{0}', employeeName || ''),
                html: `
                    <div class="text-left" style="padding: 10px 20px;">
                        <div class="form-group p-3 bg-warning rounded" style="margin-bottom: 20px;">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="adj_no_modifications" onchange="
                                    const noteField = document.getElementById('adj_payroll_note');
                                    const overtimeField = document.getElementById('adj_overtime_hours');
                                    document.querySelectorAll('.adj-field').forEach(el => {
                                        el.disabled = this.checked;
                                        if (this.checked) {
                                            el.value = '0';
                                        }
                                    });
                                    if (this.checked) {
                                        noteField.value = 'No modifications needed';
                                        // Trigger recalculation for payroll summary
                                        setTimeout(() => {
                                            overtimeField.dispatchEvent(new Event('change', { bubbles: true }));
                                        }, 100);
                                    } else {
                                        noteField.value = '';
                                    }
                                ">
                                <label class="custom-control-label" for="adj_no_modifications">
                                    <strong><i class="fa fa-check-circle"></i> ${__('no_modifications_needed') || 'No Modifications Needed'}</strong>
                                    <small class="d-block mt-1">${__('check_this_if_no_adjustments_required') || 'Check this if no overtime/deductions are required'}</small>
                                </label>
                            </div>
                        </div>
                        
                        <!-- EARNINGS SECTION -->
                        <div style="padding: 12px; background-color: #d4edda; border: 2px solid #28a745; border-radius: 6px; margin-bottom: 20px;">
                            <h5 style="color: #155724; margin-bottom: 15px; font-weight: bold;">
                                <i class="fa fa-plus-circle"></i> ${__('earnings') || 'Earnings'}
                            </h5>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label for="adj_overtime_hours" class="text-success font-weight-bold">
                                        <i class="fa fa-clock"></i> ${__('overtime_hours') || 'Overtime (Hours)'}
                                    </label>
                                    <input type="number" id="adj_overtime_hours" class="form-control payroll-calc-trigger adj-field" placeholder="0" step="0.5" min="0" value="${currentOvertimeHours || 0}">
                                </div>
                                <div class="form-group">
                                    <label for="adj_other_earnings" class="font-weight-bold text-success">
                                        <i class="fa fa-plus-circle"></i> ${__('other_earnings') || 'Other Earnings'}
                                    </label>
                                    <input type="number" id="adj_other_earnings" class="form-control payroll-calc-trigger adj-field" placeholder="0.00" step="0.01" min="0" value="${currentOtherEarnings || 0}">
                                </div>
                            </div>
                        </div>
                        
                        <!-- DEDUCTIONS SECTION -->
                        <div style="padding: 12px; background-color: #f8d7da; border: 2px solid #dc3545; border-radius: 6px; margin-bottom: 20px;">
                            <h5 style="color: #721c24; margin-bottom: 15px; font-weight: bold;">
                                <i class="fa fa-minus-circle"></i> ${__('deductions') || 'Deductions'}
                            </h5>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label for="adj_deduction_hours" class="text-danger font-weight-bold">
                                        <i class="fa fa-minus-circle"></i> ${__('deduction_hours') || 'Deduction (Hours)'}
                                    </label>
                                    <input type="number" id="adj_deduction_hours" class="form-control payroll-calc-trigger adj-field" placeholder="0" step="0.5" min="0" value="${currentDeductionHours || 0}">
                                </div>
                                <div class="form-group">
                                    <label for="adj_deduction_days" class="text-danger font-weight-bold">
                                        <i class="fa fa-calendar-minus"></i> ${__('deduction_days') || 'Deduction (Days)'}
                                    </label>
                                    <input type="number" id="adj_deduction_days" class="form-control payroll-calc-trigger adj-field" placeholder="0" step="0.5" min="0" value="${currentDeductionDays || 0}">
                                </div>
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label for="adj_other_deductions" class="font-weight-bold text-danger">
                                        <i class="fa fa-minus-circle"></i> ${__('other_deductions') || 'Other Deductions'}
                                    </label>
                                    <input type="number" id="adj_other_deductions" class="form-control payroll-calc-trigger adj-field" placeholder="0.00" step="0.01" min="0" value="${currentOtherDeductionsVal || 0}">
                                </div>
                            </div>
                        </div>

                        <!-- AUTO GOSI DEDUCTION SECTION -->
                        <div style="padding: 12px; background-color: #d1ecf1; border: 2px solid #17a2b8; border-radius: 6px; margin-bottom: 20px;">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="adj_auto_gosi_deduction" checked>
                                <label class="custom-control-label" for="adj_auto_gosi_deduction">
                                    <strong><i class="fa fa-dollar-sign"></i> ${__('auto_gosi_deduction') || 'Auto GOSI Deduction'}</strong>
                                    <small class="d-block mt-1">${__('auto_gosi_deduction_help') || 'If checked, GOSI will be automatically deducted from the vacation payment. Uncheck to exclude GOSI deduction.'}</small>
                                </label>
                            </div>
                        </div>
                        
                        <hr style="display: none;" id="payroll_summary_hr_top">
                        
                        <div class="form-group p-3 bg-light rounded" id="payroll_calculation_summary" style="display: none;">
                            <h6 class="text-primary mb-3"><i class="fa fa-calculator"></i> ${__('payroll_summary') || 'Payroll Summary'}</h6>
                            <!-- Two-column layout for payroll summary -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                    <span class="text-success font-weight-bold">${__('overtime_hours') || 'Overtime Amount'}:</span>
                                    <span class="text-success font-weight-bold">+<span id="calc_overtime_amount">0.00</span> SAR</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                    <span class="text-danger font-weight-bold">${__('deduction_amount') || 'Deduction Amount'}:</span>
                                    <span class="text-danger font-weight-bold">-<span id="calc_deduction_amount">0.00</span> SAR</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                    <span class="text-danger font-weight-bold">${__('other_deductions') || 'Other Deductions'}:</span>
                                    <span class="text-danger font-weight-bold">-<span id="calc_other_deductions">0.00</span> SAR</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding: 4px 0;">
                                    <span class="text-success font-weight-bold">${__('other_earnings') || 'Other Earnings'}:</span>
                                    <span class="text-success font-weight-bold">+<span id="calc_other_earnings">0.00</span> SAR</span>
                                </div>
                            </div>
                            <hr style="margin: 10px 0;">
                            <div style="display: flex; justify-content: center; padding: 8px 0;">
                                <span class="font-weight-bold text-info">${__('net_adjustment') || 'Net Adjustment'}:&nbsp;</span>
                                <span class="font-weight-bold text-info" id="calc_net_adjustment">0.00 SAR</span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="form-group">
                            <label for="adj_payroll_note" class="font-weight-bold">
                                <i class="fa fa-sticky-note"></i> ${__('payroll_note') || 'Note'}
                            </label>
                            <textarea id="adj_payroll_note" class="form-control adj-field" rows="3" placeholder="${__('payroll_note_placeholder') || 'Add any notes about overtime/deductions...'}">${payrollNote || ''}</textarea>
                        </div>
                    </div>
                `,
                confirmButtonText: __('save_adjustments') || 'Save Adjustments',
                showCancelButton: true,
                allowOutsideClick: false,
                width: '60%',
                // maxWidth: '1200px',
                willOpen: () => {
                    const swalModal = Swal.getHtmlContainer();
                    
                    // Fetch employee salary data
                    let basicSalary = 0;
                    let totalSalary = 0;
                    let salaryLoaded = false;
                    
                    // Extract employee ID from vacation ID by fetching vacation details
                    $.ajax({
                        url: './includes/ajaxFile/leaveHandler.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            ajaxType: 'getVacationDetails',
                            vacation_id: vacationId
                        },
                        success: function(res) {
                            if (res.status === 200 && res.emp_id) {
                                // Now fetch salary for this employee
                                $.ajax({
                                    url: './includes/ajaxFile/hrHandler.php',
                                    type: 'POST',
                                    dataType: 'json',
                                    data: {
                                        ajaxType: 'get_employee_salary',
                                        emp_id: res.emp_id
                                    },
                                    success: function(salaryRes) {
                                        if (salaryRes.status === 200 && salaryRes.salary) {
                                            totalSalary = parseFloat(salaryRes.salary) || 0;
                                            basicSalary = parseFloat(salaryRes.basic_salary) || 0;
                                            salaryLoaded = true;
                                            
                                            // Trigger initial calculation
                                            calculatePayroll();
                                        }
                                    }
                                });
                            }
                        }
                    });
                    
                    // Function to calculate payroll amounts
                    const calculatePayroll = () => {
                        const overtimeHours = parseFloat(document.getElementById('adj_overtime_hours').value) || 0;
                        const deductionHours = parseFloat(document.getElementById('adj_deduction_hours').value) || 0;
                        const deductionDays = parseFloat(document.getElementById('adj_deduction_days').value) || 0;
                        const otherEarnings = parseFloat(document.getElementById('adj_other_earnings').value) || 0;
                        const otherDeductions = parseFloat(document.getElementById('adj_other_deductions').value) || 0;
                        
                        // Show payroll summary block when user starts modifying
                        const summaryBlock = document.getElementById('payroll_calculation_summary');
                        const hrTop = document.getElementById('payroll_summary_hr_top');
                        const hasModifications = overtimeHours > 0 || deductionHours > 0 || deductionDays > 0 || otherEarnings > 0 || otherDeductions > 0;
                        
                        if (hasModifications) {
                            summaryBlock.style.display = 'block';
                            hrTop.style.display = 'block';
                        } else {
                            summaryBlock.style.display = 'none';
                            hrTop.style.display = 'none';
                        }
                        
                        // Wait for salary to load before calculating
                        if (!salaryLoaded || totalSalary === 0) {
                            return;
                        }
                        
                        // Calculate using EOS formula matching backend
                        const dailyRateDeduction = totalSalary / 30;
                        const hourlyRateDeduction = dailyRateDeduction / 8;
                        const overtimeHourlyRate = ((basicSalary / 240) / 2) + (totalSalary / 240);
                        
                        const overtimeAmount = (overtimeHours * overtimeHourlyRate);
                        const deductionAmount = (deductionHours * hourlyRateDeduction) + (deductionDays * dailyRateDeduction);
                        
                        const netAdjustment = overtimeAmount - (deductionAmount + otherDeductions) + otherEarnings;
                        
                        document.getElementById('calc_overtime_amount').textContent = overtimeAmount.toFixed(2);
                        document.getElementById('calc_deduction_amount').textContent = deductionAmount.toFixed(2);
                        document.getElementById('calc_other_deductions').textContent = otherDeductions.toFixed(2);
                        document.getElementById('calc_other_earnings').textContent = otherEarnings.toFixed(2);
                        document.getElementById('calc_net_adjustment').textContent = netAdjustment.toFixed(2) + ' SAR';
                    };
                    
                    // Attach event listeners - DO NOT call calculatePayroll on willOpen
                    // Only call it when user modifies the fields
                    $(swalModal).on('change keyup', '.payroll-calc-trigger', calculatePayroll);
                },
                didRender: () => {
                    // Add "Edit Date" button as a main action button alongside Save and Cancel
                    const swalActions = Swal.getActions();
                    if (swalActions) {
                        const editDateBtn = document.createElement('button');
                        editDateBtn.type = 'button';
                        editDateBtn.className = 'btn btn-primary';
                        editDateBtn.id = 'edit_date_btn_adj';
                        editDateBtn.innerHTML = '<i class="fa fa-calendar-edit"></i> ' + (__('edit_dates') || 'Edit Dates');
                        editDateBtn.style.marginRight = '10px';
                        editDateBtn.style.padding = '.625em 1.1em';
                        editDateBtn.style.fontSize = '1em';
                        editDateBtn.style.borderRadius = '0.25em';
                        
                        // Close current modal and open payment modal to modify dates
                        editDateBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            
                            // Fetch current vacation details to get payment values and employee name
                            $.ajax({
                                url: './includes/ajaxFile/leaveHandler.php',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    ajaxType: 'getVacationDetails',
                                    vacation_id: vacationId
                                },
                                success: function(data) {
                                    if (data.status === 200) {
                                        // Close current adjustments modal
                                        Swal.hideLoading();
                                        Swal.close();
                                        
                                        // Get employee name from vacation details
                                        const empName = data.emp_name || 'Employee';
                                        const ticketPay = data.ticket_pay || '0.00';
                                        const permitFee = data.permit_fee || '0.00';
                                        
                                        // Open payment/date modification modal
                                        setTimeout(() => {
                                            addVacationPayments(vacationId, empName, ticketPay, permitFee);
                                        }, 300);
                                    }
                                },
                                error: function() {
                                    Swal.fire('Error', __('error_loading_vacation_details') || 'Error loading vacation details', 'error');
                                }
                            });
                        });
                        
                        // Insert the button as a main action button before the confirm button
                        const confirmBtn = Swal.getConfirmButton();
                        if (confirmBtn) {
                            confirmBtn.parentElement.insertBefore(editDateBtn, confirmBtn);
                        }
                    }
                },
                preConfirm: () => {
                    const no_modifications = document.getElementById('adj_no_modifications').checked;
                    const overtime_hours = parseFloat(document.getElementById('adj_overtime_hours').value) || 0;
                    const deduction_hours = parseFloat(document.getElementById('adj_deduction_hours').value) || 0;
                    const deduction_days = parseFloat(document.getElementById('adj_deduction_days').value) || 0;
                    const other_earnings = parseFloat(document.getElementById('adj_other_earnings').value) || 0;
                    const other_deductions = parseFloat(document.getElementById('adj_other_deductions').value) || 0;
                    const payroll_note = document.getElementById('adj_payroll_note').value || '';
                    const auto_gosi_deduction = document.getElementById('adj_auto_gosi_deduction').checked;

                    // Allow saving if "No modifications" is checked OR if there are actual values
                    if (!no_modifications && overtime_hours === 0 && deduction_hours === 0 && deduction_days === 0 && other_earnings === 0 && other_deductions === 0 && !payroll_note) {
                        Swal.showValidationMessage(__('please_enter_adjustments_or_check_no_modifications') || 'Please enter adjustments or check "No Modifications Needed"');
                        return false;
                    }

                    if (overtime_hours < 0 || deduction_hours < 0 || deduction_days < 0 || other_earnings < 0 || other_deductions < 0) {
                        Swal.showValidationMessage(__('invalid_negative_values_not_allowed') || 'Negative values not allowed');
                        return false;
                    }
                    return { 
                        no_modifications,
                        overtime_hours, 
                        deduction_hours, 
                        deduction_days, 
                        other_earnings, 
                        other_deductions, 
                        payroll_note,
                        auto_gosi_deduction
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: './includes/ajaxFile/leaveHandler.php',
                        type: 'POST',
                        dataType: 'JSON',
                        data: {
                            ajaxType: 'updateVacationAdjustments',
                            vacation_id: vacationId,
                            no_modifications: result.value.no_modifications ? 1 : 0,
                            overtime_hours: result.value.overtime_hours,
                            deduction_hours: result.value.deduction_hours,
                            deduction_days: result.value.deduction_days,
                            other_earnings: result.value.other_earnings,
                            other_deductions: result.value.other_deductions,
                            payroll_note: result.value.payroll_note,
                            auto_gosi_deduction: result.value.auto_gosi_deduction ? 1 : 0
                        },
                    })
                    .done(function(response){
                        Swal.fire({
                            title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
                        }).then(function(isConfirm){(isConfirm)?location.reload():""});
                    })
                    .fail(function(){
                        Swal.fire('Error', __('error_saving_adjustments') || 'Error saving adjustments', 'error');
                    });
                }
            });
        } catch (e) {
            Swal.fire('Error', e && e.message ? e.message : 'Unexpected error', 'error');
        }
    }
}

// Optional delegated handler: use buttons/links with class .addVacationAdjustments
$(document).on('click', '.addVacationAdjustments', function(e){
    try {
        e.preventDefault();
        const $el = $(this);
        const vacationId = $el.data('vacation-id') || $el.data('id');
        const employeeName = $el.data('employee-name') || '';
        const overtime = parseFloat($el.data('overtime-hours')) || 0;
        const dedHrs = parseFloat($el.data('deduction-hours')) || 0;
        const dedDays = parseFloat($el.data('deduction-days')) || 0;
        const otherEarnings = parseFloat($el.data('other-earnings')) || 0;
        const otherDeductions = parseFloat($el.data('other-deductions')) || 0;
        const note = $el.data('payroll-note') || '';
        if (!vacationId) {
            Swal.fire('Error', 'Missing vacation id', 'error');
            return;
        }
        window.addVacationAdjustments(vacationId, employeeName, overtime, dedHrs, dedDays, otherEarnings, note, otherDeductions);
    } catch(err) {
        Swal.fire('Error', err && err.message ? err.message : 'Unexpected error', 'error');
    }
});
////////////////////////////////////////////////////////////////////

/*:::::::::::::::::::::::::::::::HTML HANDLER::::::::::::::::::::::::::::::*/


function generateLeaveFormHTML(employeeGender) {
    // Define all leave types with gender requirements
    // employeeGender: 1 = Male, 2 = Female
    const allLeaveTypes = [
        { value: 'Sick Leave', label: __('sick_leave'), gender: null },
        { value: 'Exam Leave', label: __('exam_leave'), gender: null },
        { value: 'Hajj Leave', label: __('hajj_leave'), gender: null },
        { value: 'Maternity Leave', label: __('maternity_leave'), gender: 2 },
        { value: 'Marriage Leave', label: __('marriage_leave'), gender: null },
        { value: 'Newborn Leave', label: __('newborn_leave'), gender: 1 },
        { value: 'Death Leave', label: __('death_leave'), gender: null }
    ];

    // Filter leave types based on employee gender
    const leaveTypes = allLeaveTypes.filter(type => 
        type.gender === null || type.gender === employeeGender
    );
    
    let leaveOptions = leaveTypes.map(type => 
        `<option value="${type.value}">${type.label}</option>`
    ).join('');

    return `
        <form id="applyLeaveForm" class="text-left" enctype="multipart/form-data">
            <div class="form-group">
                <label for="leave_type_select">${__('leave_type')} <span class="text-danger">*</span></label>
                <select id="leave_type_select" name="leave_type" class="form-control" style="width: 100%;" required>
                    <option value="" selected disabled>${__('select_leave_type_placeholder')}</option>
                    ${leaveOptions}
                </select>
            </div>

            <!-- Date Section - Always shown for all leave types -->
            <div id="dateSection" class="d-none">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="start_date">${__('start_date')} <span class="text-danger">*</span></label>
                        <input type="text" name="start_date" id="start_date" class="form-control datepicker" placeholder="YYYY-MM-DD" readonly required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="end_date">${__('end_date')} <span class="text-danger">*</span></label>
                        <input type="text" name="end_date" id="end_date" class="form-control datepicker" placeholder="YYYY-MM-DD" readonly required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="total_days">${__('total_days')}</label>
                        <input type="text" name="total_days" id="total_days" class="form-control" placeholder="${__('auto_calculated_placeholder')}" readonly style="cursor: not-allowed; background-color: #e9ecef;">
                    </div>
                </div>
            </div>

            <!-- Reason/Notes - Required for ALL leave types -->
            <div id="reasonSection" class="form-group d-none">
                <label for="reason">${__('reason_notes')} <span class="text-danger">*</span></label>
                <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="${__('reason_placeholder')}" required></textarea>
            </div>

            <!-- Attachment - Required for ALL leave types -->
            <div id="attachmentSection" class="form-group d-none">
                <label for="attachment">${__('attach_document_required')} <span class="text-danger">*</span></label>
                <div id="leaveDropzone" class="dropzone" style="border: 2px dashed #4e73df; border-radius: 8px; padding: 20px; min-height: 150px; background: #f8f9fc; cursor: pointer; transition: all 0.3s ease;">
                    <div class="dz-message" style="margin: 20px 0; text-align: center;">
                        <i class="fa fa-cloud-upload-alt" style="font-size: 48px; color: #4e73df; margin-bottom: 15px; display: block;"></i>
                        <h4 style="margin: 15px 0 10px 0; color: #495057; font-weight: 600;">${__('drag_drop_files') || 'Drag & Drop files here'}</h4>
                        <p style="color: #6c757d; margin: 10px 0; font-size: 14px;">${__('or_click_to_browse') || 'or click to browse'}</p>
                        <small style="color: #858796; display: block; margin-top: 10px; font-size: 12px;">
                            <i class="fa fa-info-circle"></i> ${__('attachment_dropzone_help') || '1-10 files • Max 5MB each • PDF, JPG, PNG'}
                        </small>
                    </div>
                </div>
                <small class="form-text text-muted mt-2" style="display: block; margin-top: 8px;">
                    <i class="fa fa-info-circle"></i> ${__('attachment_multiple_help') || 'You can upload 1-10 files. Each file must be less than 5MB. Accepted formats: PDF, JPG, PNG'}
                </small>
                <style>
                    #leaveDropzone:hover {
                        border-color: #2e59d9;
                        background: #eef2ff;
                    }
                    #leaveDropzone .dz-preview {
                        margin: 10px;
                    }
                    #leaveDropzone .dz-preview .dz-image {
                        border-radius: 8px;
                    }
                    #leaveDropzone .dz-preview .dz-details {
                        background: #fff;
                        padding: 8px;
                        border-radius: 4px;
                    }
                    #leaveDropzone .dz-preview .dz-remove {
                        color: #e74a3b;
                        font-size: 12px;
                        text-decoration: none;
                        cursor: pointer;
                    }
                    #leaveDropzone .dz-preview .dz-remove:hover {
                        color: #c9302c;
                        text-decoration: underline;
                    }
                    #leaveDropzone.dz-drag-hover {
                        border-color: #2e59d9;
                        background: #e3f2fd;
                    }
                </style>
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
    // Define all available user roles/permissions based on admin_login.user_type ENUM
    const roleTypes = [
        // Primary Roles
        { value: 'administrator', label: __('administrator') || 'System Administrator', group: 'primary' },
        { value: 'gm', label: __('general_manager') || 'General Manager', group: 'primary' },
        { value: 'employee', label: __('employee') || 'Regular Employee', group: 'primary' },
        
        // HR Roles
        { value: 'hr_senior_bp', label: __('hr_senior_bp') || 'HR Senior Business Partner', group: 'hr' },
        { value: 'hr_operations', label: __('hr_operations') || 'HR Operations', group: 'hr' },
        { value: 'hr_supervisor', label: __('hr_supervisor') || 'HR Supervisor', group: 'hr' },
        { value: 'hr_recruitment', label: __('hr_recruitment') || 'HR Recruitment', group: 'hr' },
        { value: 'hr_payroll', label: __('hr_payroll') || 'HR Payroll', group: 'hr' },
        
        // Department Roles
        { value: 'dept_user', label: __('dept_user') || 'Department User', group: 'dept' },
        { value: 'finance_officer', label: __('finance_officer') || 'Finance Officer', group: 'dept' },
        { value: 'auditor', label: __('auditor') || 'Auditor', group: 'dept' },
        { value: 'gr_officer', label: __('gr_officer') || 'Government Relations Officer', group: 'dept' },
        
        // Legacy Roles (for backward compatibility)
        { value: 'hr', label: __('hr') || 'HR (Legacy)', group: 'legacy' },
        { value: 'it', label: __('it') || 'IT (Legacy)', group: 'legacy' },
        { value: 'finance', label: __('finance') || 'Finance (Legacy)', group: 'legacy' },
        { value: 'assistant', label: __('assistant') || 'Assistant (Legacy)', group: 'legacy' }
    ];
    
    // Group roles for better UI organization
    let roleOptions = '';
    
    // Primary roles
    roleOptions += `<optgroup label="${__('primary_roles') || 'Primary Roles'}">`;
    roleOptions += roleTypes.filter(r => r.group === 'primary').map(role => 
        `<option value="${role.value}">${role.label}</option>`
    ).join('');
    roleOptions += '</optgroup>';
    
    // HR roles
    roleOptions += `<optgroup label="${__('hr_roles') || 'HR Department Roles'}">`;
    roleOptions += roleTypes.filter(r => r.group === 'hr').map(role => 
        `<option value="${role.value}">${role.label}</option>`
    ).join('');
    roleOptions += '</optgroup>';
    
    // Department roles
    roleOptions += `<optgroup label="${__('department_roles') || 'Department Roles'}">`;
    roleOptions += roleTypes.filter(r => r.group === 'dept').map(role => 
        `<option value="${role.value}">${role.label}</option>`
    ).join('');
    roleOptions += '</optgroup>';
    
    // Legacy roles (commented out or shown separately)
    roleOptions += `<optgroup label="${__('legacy_roles') || 'Legacy Roles (Not Recommended)'}">`;
    roleOptions += roleTypes.filter(r => r.group === 'legacy').map(role => 
        `<option value="${role.value}">${role.label}</option>`
    ).join('');
    roleOptions += '</optgroup>';
    
    return `
    <form class="contact-input" id="createUserForm" style="text-align: left;">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="emp_id">${__('employee') || 'Select Employee'}<span class="text-danger">*</span></label>
                    <select id="emp_id" name="emp_id" class="form-control select2-single">
                        <option value="">${__('loading_employees') || 'Loading employees...'}</option>
                    </select>
                    <small class="form-text text-muted">${__('select_employee_note') || 'Note: Only employees without existing system access are shown.'}</small>
                </div>
                <div class="form-group col-md-12">
                    <label for="user_type">${__('type_of_permission') || 'User Role / Permission'}<span class="text-danger">*</span></label>
                    <select id="user_type" name="user_type" class="form-control">
                        <option value="">${__('select_type')}</option>
                        ${roleOptions}
                    </select>
                    <small class="form-text text-muted">${__('user_role_note') || 'Note: User role determines system access permissions. Employee type is automatically inherited from employee record.'}</small>
                </div>
                <div class="form-group col-md-12">
                    <label for="email">${__('email')}</label>
                    <input type="email" id="email" name="email" class="form-control email-validation">
                    <small class="form-text text-muted">${__('admin_email_note') || 'Note: Email is optional for regular employees. Required for administrative roles.'}</small>
                </div>

                <!-- Company Access Control Section -->
                <div class="form-group col-md-6" id="company-access-group">
                    <label for="allowed_companies">${__('allowed_companies') || 'Allowed Companies'}<span class="text-danger">*</span></label>
                    <div id="company-select-container">
                        <div class="d-flex align-items-center mb-2">
                            <input type="checkbox" id="fullAccessCheckbox" name="full_access" value="1">
                            <label class="ml-2 mb-0" for="fullAccessCheckbox">${__('full_access_to_all_companies') || 'Full Access to All Companies'}</label>
                        </div>
                    </div>
                    <select class="form-control select2-multi" name="allowed_companies" id="allowed_companies" multiple="multiple" style="width: 100%">
                        <!-- Companies will be loaded dynamically -->
                    </select>
                    <small class="form-text text-muted d-block mt-1">${__('company_access_note') || 'Type to search and select companies. Hold Ctrl/Cmd to select multiple. Leave empty for full access.'}</small>
                </div>

                <!-- Department Access Control Section -->
                <div class="form-group col-md-6" id="department-access-group">
                    <label for="allowed_departments">${__('allowed_departments') || 'Allowed Departments'}<span class="text-danger">*</span></label>
                    <div id="department-select-container">
                        <div class="d-flex align-items-center mb-2">
                            <input type="checkbox" id="fullDeptAccessCheckbox" name="full_dept_access" value="1">
                            <label class="ml-2 mb-0" for="fullDeptAccessCheckbox">${__('full_access_to_all_departments') || 'Full Access to All Departments'}</label>
                        </div>
                    </div>
                    <select class="form-control select2-multi" name="allowed_departments" id="allowed_departments" multiple="multiple" style="width: 100%">
                        <!-- Departments will be loaded dynamically -->
                    </select>
                    <small class="form-text text-muted d-block mt-1">${__('department_access_note') || 'Type to search and select departments. Hold Ctrl/Cmd to select multiple. Leave empty for full access.'}</small>
                </div>

                <!-- Employee Access Control Section -->
                <div class="form-group col-md-6" id="employee-access-group">
                    <label for="allowed_employees">${__('allowed_employees') || 'Allowed Employees'}<span class="text-danger">*</span></label>
                    <div id="employee-select-container">
                        <div class="d-flex align-items-center mb-2">
                            <input type="checkbox" id="fullEmpAccessCheckbox" name="full_emp_access" value="1">
                            <label class="ml-2 mb-0" for="fullEmpAccessCheckbox">${__('full_access_to_all_employees') || 'Full Access to All Employees'}</label>
                        </div>
                    </div>
                    <select class="form-control select2-multi" name="allowed_employees" id="allowed_employees" multiple="multiple" style="width: 100%">
                        <!-- Employees will be loaded dynamically -->
                    </select>
                    <small class="form-text text-muted d-block mt-1">${__('employee_access_note') || 'Type to search and select employees. Hold Ctrl/Cmd to select multiple. Leave empty for full access.'}</small>
                </div>
            </div>
        </div>
    </form>`;
}


function edit_user_HTML(){
    // Define all available user roles/permissions based on admin_login.user_type ENUM
    const roleTypes = [
        // Primary Roles
        { value: 'administrator', label: __('administrator') || 'System Administrator', group: 'primary' },
        { value: 'gm', label: __('general_manager') || 'General Manager', group: 'primary' },
        { value: 'employee', label: __('employee') || 'Regular Employee', group: 'primary' },
        
        // HR Roles
        { value: 'hr_senior_bp', label: __('hr_senior_bp') || 'HR Senior Business Partner', group: 'hr' },
        { value: 'hr_operations', label: __('hr_operations') || 'HR Operations', group: 'hr' },
        { value: 'hr_supervisor', label: __('hr_supervisor') || 'HR Supervisor', group: 'hr' },
        { value: 'hr_recruitment', label: __('hr_recruitment') || 'HR Recruitment', group: 'hr' },
        { value: 'hr_payroll', label: __('hr_payroll') || 'HR Payroll', group: 'hr' },
        
        // Department Roles
        { value: 'dept_user', label: __('dept_user') || 'Department User', group: 'dept' },
        { value: 'finance_officer', label: __('finance_officer') || 'Finance Officer', group: 'dept' },
        { value: 'auditor', label: __('auditor') || 'Auditor', group: 'dept' },
        { value: 'gr_officer', label: __('gr_officer') || 'Government Relations Officer', group: 'dept' },
        
        // Legacy Roles (for backward compatibility)
        { value: 'hr', label: __('hr') || 'HR (Legacy)', group: 'legacy' },
        { value: 'it', label: __('it') || 'IT (Legacy)', group: 'legacy' },
        { value: 'finance', label: __('finance') || 'Finance (Legacy)', group: 'legacy' },
        { value: 'assistant', label: __('assistant') || 'Assistant (Legacy)', group: 'legacy' }
    ];
    
    // Group roles for better UI organization
    let roleOptions = '';
    
    // Primary roles
    roleOptions += `<optgroup label="${__('primary_roles') || 'Primary Roles'}">`;
    roleOptions += roleTypes.filter(r => r.group === 'primary').map(role => 
        `<option value="${role.value}">${role.label}</option>`
    ).join('');
    roleOptions += '</optgroup>';
    
    // HR roles
    roleOptions += `<optgroup label="${__('hr_roles') || 'HR Department Roles'}">`;
    roleOptions += roleTypes.filter(r => r.group === 'hr').map(role => 
        `<option value="${role.value}">${role.label}</option>`
    ).join('');
    roleOptions += '</optgroup>';
    
    // Department roles
    roleOptions += `<optgroup label="${__('department_roles') || 'Department Roles'}">`;
    roleOptions += roleTypes.filter(r => r.group === 'dept').map(role => 
        `<option value="${role.value}">${role.label}</option>`
    ).join('');
    roleOptions += '</optgroup>';
    
    // Legacy roles
    roleOptions += `<optgroup label="${__('legacy_roles') || 'Legacy Roles (Not Recommended)'}">`;
    roleOptions += roleTypes.filter(r => r.group === 'legacy').map(role => 
        `<option value="${role.value}">${role.label}</option>`
    ).join('');
    roleOptions += '</optgroup>';

    var strView =
    `<form id="submitEditUserForm">
    <div class="form-row customSweetAlertMLR">
        <div class="form-group col-md-6">
            <label for="dept">${__('department')}</label>
            <input type="text" id="dept" name="dept" class="form-control" readonly>
        </div>
        <div class="form-group col-md-6">
            <label for="user_type">${__('type_of_permission')}<span class="text-danger">*</span></label>
            <select class="custom-select" name="user_type" id="user_type" required>
                <option value="">${__('select_type')}</option>
                ${roleOptions}
            </select>
            <small class="form-text text-muted">${__('user_role_note') || 'User role determines system access permissions.'}</small>
        </div>
        <div class="form-group col-md-6" id="email-group">
            <label for="email">${__('email')}</label>
            <input type="email" id="email" name="email" class="form-control" required>
            <small class="form-text text-muted">${__('admin_email_note') || 'Email is optional for regular employees. Required for administrative roles.'}</small>
        </div>
        
        <!-- Company Access Control Section -->
        <div class="form-group col-md-6" id="company-access-group">
            <label for="allowed_companies">${__('allowed_companies') || 'Allowed Companies'}<span class="text-danger">*</span></label>
            <div id="company-select-container">
                <div class="d-flex align-items-center mb-2">
                    <input type="checkbox" id="fullAccessCheckbox" name="full_access" value="1">
                    <label class="ml-2 mb-0" for="fullAccessCheckbox">${__('full_access_to_all_companies') || 'Full Access to All Companies'}</label>
                </div>
            </div>
            <select class="form-control select2-multi" name="allowed_companies" id="allowed_companies" multiple="multiple" style="width: 100%">
                <!-- Companies will be loaded dynamically -->
            </select>
            <small class="form-text text-muted d-block mt-1">${__('company_access_note') || 'Type to search and select companies. Hold Ctrl/Cmd to select multiple. Leave empty for full access.'}</small>
        </div>

        <!-- Department Access Control Section -->
        <div class="form-group col-md-6" id="department-access-group">
            <label for="allowed_departments">${__('allowed_departments') || 'Allowed Departments'}<span class="text-danger">*</span></label>
            <div id="department-select-container">
                <div class="d-flex align-items-center mb-2">
                    <input type="checkbox" id="fullDeptAccessCheckbox" name="full_dept_access" value="1">
                    <label class="ml-2 mb-0" for="fullDeptAccessCheckbox">${__('full_access_to_all_departments') || 'Full Access to All Departments'}</label>
                </div>
            </div>
            <select class="form-control select2-multi" name="allowed_departments" id="allowed_departments" multiple="multiple" style="width: 100%">
                <!-- Departments will be loaded dynamically -->
            </select>
            <small class="form-text text-muted d-block mt-1">${__('department_access_note') || 'Type to search and select departments. Hold Ctrl/Cmd to select multiple. Leave empty for full access.'}</small>
        </div>

        <!-- Employee Access Control Section -->
        <div class="form-group col-md-6" id="employee-access-group">
            <label for="allowed_employees">${__('allowed_employees') || 'Allowed Employees'}<span class="text-danger">*</span></label>
            <div id="employee-select-container">
                <div class="d-flex align-items-center mb-2">
                    <input type="checkbox" id="fullEmpAccessCheckbox" name="full_emp_access" value="1">
                    <label class="ml-2 mb-0" for="fullEmpAccessCheckbox">${__('full_access_to_all_employees') || 'Full Access to All Employees'}</label>
                </div>
            </div>
            <select class="form-control select2-multi" name="allowed_employees" id="allowed_employees" multiple="multiple" style="width: 100%">
                <!-- Employees will be loaded dynamically -->
            </select>
            <small class="form-text text-muted d-block mt-1">${__('employee_access_note') || 'Type to search and select employees. Hold Ctrl/Cmd to select multiple. Leave empty for full access.'}</small>
        </div>

        <div class="form-group col-md-6">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="user_status" name="user_status" value="1">
                <label class="custom-control-label" for="user_status">${__('active_user') || 'Active User'}</label>
            </div>
            <small class="form-text text-muted d-block mt-2">${__('user_status_note') || 'Check to activate this user account, uncheck to deactivate.'}</small>
        </div>
        
        <input type="hidden" id="iduser" name="id">
    </div>
    </form>
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
    `<style>
        
    </style>
    
    <form id="submitVacationApplyForm" enctype="multipart/form-data">
        <div class="vacation-form-container">
            
            <!-- Employee Information -->
            <div class="info-row">
                <div class="info-field" style="flex: 2;">
                    <label>${__('employee_name')}</label>
                    <input type="text" name="name" id="name" readonly>
                </div>
                <div class="info-field" style="flex: 1;">
                    <label>${__('employee_id')}</label>
                    <input type="text" name="empid" id="empid" readonly>
                </div>
            </div>


            <!-- Vacation Type Selection -->
            <div class="vacation-card">
                <div class="vacation-card-header">
                    <i class="fa fa-clipboard-list"></i>
                    ${__('remarks')}<span class="text-danger">*</span>
                </div>
                <div class="vac-radio-group">
                    <div class="vac-radio-option">
                        <input type="radio" id="inlineRadio3" value="Local Vacation" name="vac_type">
                        <label for="inlineRadio3" class="vac-radio-label">
                            <i class="fa fa-map-marker-alt"></i>
                            <span>${__('local_vacation')}</span>
                        </label>
                    </div>
                    ${(country != 191 && country != 150) ? `
                    <div class="vac-radio-option">
                        <input type="radio" id="inlineRadio1" value="Fly" name="vac_type">
                        <label for="inlineRadio1" class="vac-radio-label">
                            <i class="fa fa-plane-departure"></i>
                            <span>${__('fly')}</span>
                        </label>
                    </div>` : ''}
                    <div class="vac-radio-option">
                        <input type="radio" id="inlineRadio2" value="Encashed" name="vac_type">
                        <label for="inlineRadio2" class="vac-radio-label">
                            <i class="fa fa-money-bill-wave"></i>
                            <span>${__('encashed')}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Encashment Section -->
            <div class="vacation-card d-none" id="encashSection">
                <div class="vacation-card-header">
                    <i class="fa fa-coins"></i> ${__('vacation_balance') || 'Vacation Balance'}
                </div>
                <div style="margin-bottom:8px; color:#4e73df; font-weight:600;">
                    <span id="vacation_balance_display">0</span> ${__('days') || 'days'}
                </div>
                <div class="form-group">
                    <label for="encash_days">${__('enter_days_to_encash') || 'Enter number of days to encash'}<span class="text-danger">*</span></label>
                    <input type="text" inputmode="numeric" min="1" max="999" class="form-control" id="encash_days" name="encash_days" placeholder="${__('enter_days_to_encash_placeholder') || 'Days'}">
                </div>
                <div class="form-group">
                    <label>${__('encashment_salary_label') || 'Encashment Salary'}:</label>
                    <div style="font-weight:600; color:#28a745;" id="encashment_salary_display">0</div>
                </div>
            </div>

            <!-- Fly Type Selection -->
            <div class="vacation-card d-none" id="flyTypeSection">
                <div class="vacation-card-header">
                    <i class="fa fa-tags"></i>
                    ${__('select_vacation_type')}<span class="text-danger">*</span>
                </div>
                <div class="vac-radio-group">
                    <div class="vac-radio-option">
                        <input type="radio" id="vac_type1" value="annual" name="fly_type">
                        <label for="vac_type1" class="vac-radio-label">
                            <i class="fa fa-calendar-check"></i>
                            <span>${__('annual_vacation')}</span>
                        </label>
                    </div>
                    <div class="vac-radio-option">
                        <input type="radio" id="vac_type2" value="emergency" name="fly_type">
                        <label for="vac_type2" class="vac-radio-label">
                            <i class="fa fa-exclamation-triangle"></i>
                            <span>${__('emergency_vacation')}</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Date Selection -->
            <div class="vacation-card d-none" id="date_select">
                <div class="vacation-card-header">
                    <i class="fa fa-calendar-alt"></i>
                    ${__('start_date')} & ${__('return_date')}
                </div>
                <div class="date-range-container">
                    <div class="date-field">
                        <label class="form-label-modern">${__('start_date')}<span class="text-danger">*</span></label>
                        <input type="text" name="start_date" placeholder="${__('select_start_date_placeholder')}" class="form-control form-control-modern" id="start_date">
                    </div>
                    <div class="date-field">
                        <label class="form-label-modern">${__('return_date')}<span class="text-danger">*</span></label>
                        <input type="text" name="end_date" placeholder="${__('select_return_date_placeholder')}" class="form-control form-control-modern" id="end_date">
                    </div>
                </div>
                <div id="vacation_days_display" class="d-none" style="margin-top: 15px; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; text-align: center;">
                    <div style="color: white; font-size: 14px; font-weight: 500; margin-bottom: 5px;">
                        <i class="fa fa-calendar-check"></i> ${__('vacation_days') || 'Vacation Days'}
                    </div>
                    <div style="color: white; font-size: 24px; font-weight: 700;" id="vacation_days_count">0</div>
                </div>
            </div>

            <!-- Flight Dates (Departure & Arrival) -->
            <div class="vacation-card d-none" id="flightDatesSection">
                <div class="vacation-card-header">
                    <i class="fa fa-plane"></i>
                    ${__('flight_dates') || 'Flight Dates'}
                </div>
                <div class="date-range-container">
                    <div class="date-field">
                        <label class="form-label-modern">${__('departure_date') || 'Departure Date'}<span class="text-danger">*</span></label>
                        <input type="text" name="departure_date" placeholder="${__('select_departure_date') || 'Select departure date'}" class="form-control form-control-modern" id="departure_date">
                    </div>
                    <div class="date-field">
                        <label class="form-label-modern">${__('arrival_date') || 'Arrival Date'}<span class="text-danger">*</span></label>
                        <input type="text" name="arrival_date" placeholder="${__('select_arrival_date') || 'Select arrival date'}" class="form-control form-control-modern" id="arrival_date">
                    </div>
                </div>
            </div>

            <!-- Replacement Person -->
            <div class="vacation-card d-none" id="replacementSection">
                <div class="vacation-card-header">
                    <i class="fa fa-user-friends"></i>
                    ${__('replacement_person')}<span class="text-danger">*</span>
                </div>
                <select class="form-control form-control-modern" name="replacement_per" id="replacement_per">
                    <option value="">${__('select')}</option>
                </select>
            </div>

            <!-- Notes Section -->
            <div class="vacation-card d-none" id="notesSection">
                <div class="vacation-card-header">
                    <i class="fa fa-sticky-note"></i>
                    ${__('notes')}
                </div>
                <input type="text" name="remarks" class="form-control form-control-modern" id="remarks" autocomplete="off" placeholder="${__('enter_notes_placeholder') || 'Enter additional notes...'}">
            </div>

            <!-- Vacation Salary Type Selection -->
            <div class="vacation-card d-none" id="salaryTypeSection" style="margin-top: 20px;">
                <div class="vacation-card-header">
                    <i class="fa fa-wallet"></i>
                    ${__('vacation_salary_payment')} <span class="text-danger">*</span>
                </div>
                <div class="vac-radio-group">
                    <div class="vac-radio-option">
                        <input type="radio" id="salary_with_payroll" value="payroll" name="vacation_salary_type">
                        <label for="salary_with_payroll" class="vac-radio-label">
                            <i class="fa fa-money-check-alt"></i>
                            <span>${__('yes')}</span>
                        </label>
                    </div>
                    <div class="vac-radio-option">
                        <input type="radio" id="salary_with_eos" value="end_of_service" name="vacation_salary_type">
                        <label for="salary_with_eos" class="vac-radio-label">
                            <i class="fa fa-piggy-bank"></i>
                            <span>${__('no')}</span>
                        </label>
                    </div>
                </div>
                <small class="form-text text-muted" style="margin-top: 10px; display: block; font-size: 12px; color: #858796;">
                    ${__('vacation_salary_type_help')}
                </small>
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
    `<form class="contact-input" id="addNoteForm" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="note_type">${__('note_type')} <span class="text-danger">*</span></label>
                    <select id="note_type" name="note_type" class="form-control" required>
                        <option value="">${__('select')}</option>
                        <option value="warning">${__('warning')}</option>
                        <option value="sick_leave">${__('sick_leave')}</option>
                        <option value="appreciation">${__('appreciation')}</option>
                        <option value="violation">${__('violation')}</option>
                        <option value="absence">${__('absence')}</option>
                        <option value="late_arrival">${__('late_arrival')}</option>
                        <option value="performance_review">${__('performance_review')}</option>
                        <option value="training">${__('training')}</option>
                        <option value="promotion">${__('promotion')}</option>
                        <option value="salary_adjustment">${__('salary_adjustment')}</option>
                        <option value="disciplinary_action">${__('disciplinary_action')}</option>
                        <option value="medical_report">${__('medical_report')}</option>
                        <option value="general">${__('general_note')}</option>
                        <option value="other">${__('other')}</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="note">${__('enter_note')} <span class="text-danger">*</span></label>
                    <textarea id="note" name="note" class="form-control" rows="3" required placeholder="${__('enter_note_details')}"></textarea>
                </div>
                <div class="form-group col-md-12">
                    <label for="attachment">${__('attachment')} <span class="text-muted">(${__('optional')})</span></label>
                    <input type="file" id="attachment" name="attachment" class="form-control-file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <small class="form-text text-muted">
                        <i class="fa fa-info-circle"></i> ${__('allowed_formats')}: PDF, DOC, DOCX, JPG, PNG (Max 5MB)
                    </small>
                </div>
            </div>
        </div>
    </form>
    `;
    return strView;
}

/*:::::::::::::::::::::::::::::::HTML HANDLER::::::::::::::::::::::::::::::*/



/*:::::::::::::::::::::::::::::::HTML HANDLER::::::::::::::::::::::::::::::*/

    /*$(document).ready(function() {
        // Initialize DataTable
        const table = $('#settingsTable').DataTable({
            "ajax": {
                "url": "/includes/settings_handler.php",
                "type": "POST",
                "data": { "action": "get_settings_for_datatable" },
                "dataSrc": "data"
            },
            "columns": [
                { "data": "id", "title": "ID" },
                { "data": "setting_group", "title": "Group" },
                { "data": "setting_name", "title": "Name" },
                { "data": "setting_value", "title": "Value" },
                { "data": "description", "title": "Description" }
            ]
        });
        // --- Event Listeners ---
        $('#editAllBtn').on('click', openSettingsModal);

    });

    async function openSettingsModal() {
        try {
            const response = await fetch('/includes/settings_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'get_settings' })
            });

            if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Failed to retrieve settings.');
            
            const settings = data.settings;
            
            let formHtml = '<div id="settings-form" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;" class="p-4">';
            let currentGroup = '';

            settings.forEach(setting => {
                if (setting.setting_group !== currentGroup) {
                    currentGroup = setting.setting_group;
                    formHtml += `<h2 style="grid-column: span 2; font-size: 1.125rem; font-weight: 600; border-bottom: 2px solid #e5e7eb; margin-bottom: 0.5rem; margin-top: 1rem; text-transform: capitalize;">${currentGroup} Settings</h2>`;
                }

                const id = `swal-${setting.setting_name}`;
                const label = setting.description;

                formHtml += `<div style="display: flex; flex-direction: column;">`;
                formHtml += `<label for="${id}" style="font-weight: 600; margin-bottom: 0.25rem; display: block; text-transform: capitalize;">${label}</label>`;

                switch (setting.input_type) {
                    case 'select':
                        let options = JSON.parse(setting.options || '{}');
                        formHtml += `<select id="${id}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">`;
                        for (const [value, text] of Object.entries(options)) {
                            formHtml += `<option value="${value}" ${setting.setting_value == value ? 'selected' : ''}>${text}</option>`;
                        }
                        formHtml += `</select>`;
                        break;
                    default:
                        formHtml += `<input id="${id}" style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem;" value="${setting.setting_value || ''}">`;
                        break;
                }
                formHtml += `</div>`;
            });
            formHtml += '</div>';

            Swal.fire({
                title: 'Application Settings',
                html: formHtml,
                width: '600px',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Save Changes',
                preConfirm: () => {
                    const newSettings = {};
                    settings.forEach(setting => {
                        const element = document.getElementById(`swal-${setting.setting_name}`);
                        if (element) newSettings[setting.setting_name] = element.value;
                    ,allowOutsideClick:false,cancelButtonColor:APP_COLORS.danger_dark,cancelButtonText:__('cancel')});

                    return fetch('/includes/settings_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'update_settings', settings: JSON.stringify(newSettings) })
                    ,allowOutsideClick:false,cancelButtonColor:APP_COLORS.danger_dark,cancelButtonText:__('cancel')}).then(response => {
                        if (!response.ok) throw new Error(response.statusText);
                        return response.json();
                    }).catch(error => Swal.showValidationMessage(`Request failed: ${error}`));
                }
            }).then(result => {
                if (result.isConfirmed) {
                    if (result.value.success) {
                        Swal.fire('Saved!', 'Your settings have been updated.', 'success');
                        $('#settingsTable').DataTable().ajax.reload(); // Reload table data
                    } else {
                        Swal.fire('Error!', result.value.message || 'Could not save settings.', 'error');
                    }
                }
            });
        } catch (error) {
            Swal.fire('Error!', `Could not load settings: ${error.message}`, 'error');
        }
    }*/

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
            color: APP_COLORS.primary,
            wheelStep: 5
        });
    }

    function initSlimscroll() {
        $('.slimscroll').slimscroll({
            height: 'auto',
            position: 'right',
            size: "8px",
            color: APP_COLORS.primary
        });
    }

    function initMetisMenu() {
        //metis menu
        if ($("#side-menu").length && typeof jQuery.fn.metisMenu === 'function') {
            $("#side-menu").metisMenu();
        }
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

// The following error handling functions have been moved to assets/js/ajaxErrorHandling.js
// to allow code reuse across multiple JavaScript files:
// - handleAjaxFailure(jqXHR, textStatus, defaultTitle, defaultMsg)
// - errorHandling(jqXHR, exception)
// This file can now use the shared functions without duplication.

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
        Swal.fire({title: __('oops'), text: __('enter_valid_value_alert'), icon: 'warning',allowOutsideClick:false});
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

/*
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

*/



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
    
    // restrictToNumbers(document.getElementsByClassName('basic'));
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
                // console.log(__('cannot_read_stylesheet_log'), e);
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


/**
* Theme: Highdmin - Responsive Bootstrap 4 Admin Dashboard
* Author: Coderthemes
* Module/App: Core js
*/


/**
 * Components
 */
!function ($) {
    "use strict";

    var Components = function () { };

    //initializing tooltip
    Components.prototype.initTooltipPlugin = function () {
        $.fn.tooltip && $('[data-toggle="tooltip"]').tooltip()
    },

        //initializing popover
        Components.prototype.initPopoverPlugin = function () {
            $.fn.popover && $('[data-toggle="popover"]').popover()
        },

        //initializing Slimscroll
        Components.prototype.initSlimScrollPlugin = function () {
            //You can change the color of scroll bar here
            $.fn.slimScroll && $(".slimscroll").slimScroll({
                height: 'auto',
                position: 'right',
                size: "8px",
                touchScrollStep: 20,
                color: APP_COLORS.primary
            });
        },

        //initializing form validation
        Components.prototype.initFormValidation = function () {
            $(".needs-validation").on('submit', function (event) {
                $(this).addClass('was-validated');
                if ($(this)[0].checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                    return false;
                }
                return true;
            });
        },

        //initializing custom modal
        Components.prototype.initCustomModalPlugin = function () {
            $('[data-plugin="custommodal"]').on('click', function (e) {
                e.preventDefault();
                var modal = new Custombox.modal({
                    content: {
                        target: $(this).attr("href"),
                        effect: $(this).attr("data-animation")
                    },
                    overlay: {
                        color: $(this).attr("data-overlayColor")
                    }
                });
                // Open
                modal.open();
            });
        },

        //initializing Slimscroll
        Components.prototype.initCounterUp = function () {
            var delay = $(this).attr('data-delay') ? $(this).attr('data-delay') : 100; //default is 100
            var time = $(this).attr('data-time') ? $(this).attr('data-time') : 1200; //default is 1200
            $('[data-plugin="counterup"]').each(function (idx, obj) {
                $(this).counterUp({
                    delay: delay,
                    time: time
                });
            });
        },


        Components.prototype.initPeityCharts = function () {
            $('[data-plugin="peity-pie"]').each(function (idx, obj) {
                var colors = $(this).attr('data-colors') ? $(this).attr('data-colors').split(",") : [];
                var width = $(this).attr('data-width') ? $(this).attr('data-width') : 20; //default is 20
                var height = $(this).attr('data-height') ? $(this).attr('data-height') : 20; //default is 20
                $(this).peity("pie", {
                    fill: colors,
                    width: width,
                    height: height
                });
            });
            //donut
            $('[data-plugin="peity-donut"]').each(function (idx, obj) {
                var colors = $(this).attr('data-colors') ? $(this).attr('data-colors').split(",") : [];
                var width = $(this).attr('data-width') ? $(this).attr('data-width') : 20; //default is 20
                var height = $(this).attr('data-height') ? $(this).attr('data-height') : 20; //default is 20
                $(this).peity("donut", {
                    fill: colors,
                    width: width,
                    height: height
                });
            });

            $('[data-plugin="peity-donut-alt"]').each(function (idx, obj) {
                $(this).peity("donut");
            });

            // line
            $('[data-plugin="peity-line"]').each(function (idx, obj) {
                $(this).peity("line", $(this).data());
            });

            // bar
            $('[data-plugin="peity-bar"]').each(function (idx, obj) {
                var colors = $(this).attr('data-colors') ? $(this).attr('data-colors').split(",") : [];
                var width = $(this).attr('data-width') ? $(this).attr('data-width') : 20; //default is 20
                var height = $(this).attr('data-height') ? $(this).attr('data-height') : 20; //default is 20
                $(this).peity("bar", {
                    fill: colors,
                    width: width,
                    height: height
                });
            });
        },


        Components.prototype.initKnob = function () {
            $('[data-plugin="knob"]').each(function (idx, obj) {
                $(this).knob();
            });
        },

        Components.prototype.init = function () {
            this.initTooltipPlugin();
            this.initPopoverPlugin();
            this.initSlimScrollPlugin();
            this.initFormValidation();
            this.initCustomModalPlugin();
            this.initCounterUp();
            this.initPeityCharts();
            this.initKnob();
        },

        $.Components = new Components, $.Components.Constructor = Components

}(window.jQuery);


/**
 * Portlets
 */
!function ($) {
    "use strict";

    var Portlet = function () {
        this.$body = $("body"),
            this.$portletIdentifier = ".card",
            this.$portletCloser = '.card a[data-toggle="remove"]',
            this.$portletRefresher = '.card a[data-toggle="reload"]'
    };

    //on init
    Portlet.prototype.init = function () {
        // Panel closest
        var $this = this;
        $(document).on("click", this.$portletCloser, function (ev) {
            ev.preventDefault();
            var $portlet = $(this).closest($this.$portletIdentifier);
            var $portlet_parent = $portlet.parent();
            $portlet.remove();
            if ($portlet_parent.children().length == 0) {
                $portlet_parent.remove();
            }
        });

        // Panel Reload
        $(document).on("click", this.$portletRefresher, function (ev) {
            ev.preventDefault();
            var $portlet = $(this).closest($this.$portletIdentifier);
            // This is just a simulation, nothing is going to be reloaded
            $portlet.append('<div class="card-disabled"><div class="card-portlets-loader"></div></div>');
            var $pd = $portlet.find('.card-disabled');
            setTimeout(function () {
                $pd.fadeOut('fast', function () {
                    $pd.remove();
                });
            }, 500 + 300 * (Math.random() * 5));
        });
    },
        //
        $.Portlet = new Portlet, $.Portlet.Constructor = Portlet

}(window.jQuery);


/**
 * App
 */
!function ($) {
    "use_strict";

    var App = function () {
        this.VERSION = "1.0.0",
            this.AUTHOR = "Coderthemes",
            this.SUPPORT = "coderthemes@gmail.com",
            this.pageScrollElement = "html, body",
            this.$body = $("body")
    };

    //initializing tooltip
    App.prototype.initTooltipPlugin = function () {
        $.fn.tooltip && $('[data-toggle="tooltip"]').tooltip()
    },

        //initializing popover
        App.prototype.initPopoverPlugin = function () {
            $.fn.popover && $('[data-toggle="popover"]').popover()
        },

        //initializing Slimscroll
        App.prototype.initSlimScrollPlugin = function () {
            //You can change the color of scroll bar here
            $.fn.slimScroll && $(".slimscroll-alt").slimScroll({
                position: 'right',
                size: "5px",
                color: APP_COLORS.secondary,
                wheelStep: 10
            });
        },

        //initilizing
        App.prototype.init = function () {
            this.initTooltipPlugin();
            this.initPopoverPlugin();
            this.initSlimScrollPlugin();
            $.Portlet.init();

            // initlayout
            if ($.Layout) {
                $.Layout.init();
            }
        },

        $.App = new App, $.App.Constructor = App

}(window.jQuery);

// ========================================
// SALARY UPDATE FUNCTION
// ========================================
function updateEmployeeSalary(empId, currentSalaryData, isAutoTriggered = false) {
    Swal.fire({
        title: __('update_employee_salary') || 'Update Employee Salary',
        html: `
            <div class="row" style="text-align: left;">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="swal-basic">${__('basic') || 'Basic'} <span class="text-danger">*</span></label>
                        <input type="text" id="swal-basic" class="form-control salary-input numeric-only" value="${currentSalaryData.basic || 0}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="swal-housing">${__('housing') || 'Housing'}</label>
                        <input type="text" id="swal-housing" class="form-control salary-input numeric-only" value="${currentSalaryData.housing || 0}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="swal-transport">${__('transport') || 'Transport'}</label>
                        <input type="text" id="swal-transport" class="form-control salary-input numeric-only" value="${currentSalaryData.transport || 0}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="swal-food">${__('food') || 'Food'}</label>
                        <input type="text" id="swal-food" class="form-control salary-input numeric-only" value="${currentSalaryData.food || 0}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="swal-misc">${__('misc') || 'Misc'}</label>
                        <input type="text" id="swal-misc" class="form-control salary-input numeric-only" value="${currentSalaryData.misc || 0}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="swal-cashier">${__('cashier') || 'Cashier'}</label>
                        <input type="text" id="swal-cashier" class="form-control salary-input numeric-only" value="${currentSalaryData.cashier || 0}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="swal-fuel">${__('fuel') || 'Fuel'}</label>
                        <input type="text" id="swal-fuel" class="form-control salary-input numeric-only" value="${currentSalaryData.fuel || 0}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="swal-tel">${__('tel') || 'Tel'}</label>
                        <input type="text" id="swal-tel" class="form-control salary-input numeric-only" value="${currentSalaryData.tel || 0}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="swal-other">${__('others') || 'Others'}</label>
                        <input type="text" id="swal-other" class="form-control salary-input numeric-only" value="${currentSalaryData.other || 0}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="swal-guard">${__('guard') || 'Guard'}</label>
                        <input type="text" id="swal-guard" class="form-control salary-input numeric-only" value="${currentSalaryData.guard || 0}">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="swal-total" class="font-weight-bold">${__('total_salary') || 'Total Salary'}</label>
                        <input type="text" id="swal-total" class="form-control font-weight-bold" readonly style="background-color: #e9ecef; font-size: 18px;">
                    </div>
                </div>
            </div>
        `,
        width: '700px',
        showCancelButton: !isAutoTriggered,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        confirmButtonText: __('yes_update') || 'Yes, Update',
        cancelButtonText: __('cancel') || 'Cancel',
        allowOutsideClick: false,
        allowEscapeKey: !isAutoTriggered,
        didOpen: function() {
            // Calculate total function
            function calculateTotal() {
                const total = 
                    (parseFloat($('#swal-basic').val()) || 0) +
                    (parseFloat($('#swal-housing').val()) || 0) +
                    (parseFloat($('#swal-transport').val()) || 0) +
                    (parseFloat($('#swal-food').val()) || 0) +
                    (parseFloat($('#swal-misc').val()) || 0) +
                    (parseFloat($('#swal-cashier').val()) || 0) +
                    (parseFloat($('#swal-fuel').val()) || 0) +
                    (parseFloat($('#swal-tel').val()) || 0) +
                    (parseFloat($('#swal-other').val()) || 0) +
                    (parseFloat($('#swal-guard').val()) || 0);
                $('#swal-total').val(total.toFixed(2));
            }
            
            // Initial calculation
            calculateTotal();
            // Restrict salary inputs to numeric only
            setupInputValidations();
            
            // Auto-update on input change
            $('.salary-input').on('input change', calculateTotal);
            
            // Handle Enter key to move to next field
            $('.salary-input').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    const inputs = $('.salary-input');
                    const index = inputs.index(this);
                    if (index < inputs.length - 1) {
                        inputs.eq(index + 1).focus().select();
                    } else {
                        // Last field - trigger confirm
                        Swal.clickConfirm();
                    }
                }
            });
            
            // Focus first input
            $('#swal-basic').focus().select();
        },
        preConfirm: function() {
            const salaryData = {
                basic: parseFloat($('#swal-basic').val()) || 0,
                housing: parseFloat($('#swal-housing').val()) || 0,
                transport: parseFloat($('#swal-transport').val()) || 0,
                food: parseFloat($('#swal-food').val()) || 0,
                misc: parseFloat($('#swal-misc').val()) || 0,
                cashier: parseFloat($('#swal-cashier').val()) || 0,
                fuel: parseFloat($('#swal-fuel').val()) || 0,
                tel: parseFloat($('#swal-tel').val()) || 0,
                other: parseFloat($('#swal-other').val()) || 0,
                guard: parseFloat($('#swal-guard').val()) || 0,
                totalsal: parseFloat($('#swal-total').val()) || 0,
                emp_id: empId,
                submit: 1
            };
            
            // Validate basic salary
            if (salaryData.basic <= 0) {
                Swal.showValidationMessage(__('basic_salary_required') || 'Basic salary is required');
                return false;
            }
            
            return $.ajax({
                url: "./includes/ajaxFile/hrHandler.php",
                type: "POST",
                dataType: "JSON",
                data: {
                    ...salaryData,
                    ajaxType: 'update_salary'
                }
            }).then(function(response) {
                if (response && response.type === 'success') {
                    return response;
                } else {
                    throw new Error(response.message || 'Update failed');
                }
            }).catch(function(error) {
                Swal.showValidationMessage(error.message || __("request_failed_try_again"));
                return false;
            });
        }
    }).then(function(result) {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                title: result.value.title,
                text: result.value.message,
                icon: result.value.type,
                allowOutsideClick: false
            }).then(function() {
                location.reload();
            });
        }
    });
}

//initializing main application module
////////////////////////////////////////////////////////////////////
////////////      Start Rejoin Request Handling      //////////////
////////////////////////////////////////////////////////////////////

$(document).on('click', '.submitRejoinRequest', function(e) {
    e.preventDefault();
    var vacation_id = $(this).data('vacation-id');
    var emp_id = $(this).data('emp-id');
    
    Swal.fire({
        title: __('submit_rejoin_request_title'),
        html: `
            <form id="rejoinRequestForm" class="text-left">
                <div class="form-group">
                    <label for="rejoin_date">${__('rejoin_date')} <span style="color: ${APP_COLORS.danger};">*</span></label>
                    <input type="text" id="rejoin_date" name="rejoin_date" class="form-control" placeholder="YYYY-MM-DD" required>
                </div>
                <div class="form-group">
                    <label for="rejoin_reason">${__('rejoin_reason')}</label>
                    <textarea id="rejoin_reason" name="rejoin_reason" class="form-control" rows="3" placeholder="${__('enter_rejoin_reason')}"></textarea>
                </div>
            </form>`,
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        cancelButtonText: __('cancel'),
        confirmButtonText: __('submit_request'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '500px',
        didOpen: function() {
            // Initialize date picker for rejoin_date
            $('#rejoin_date').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                todayHighlight: true,
                startDate: '+0d'
            });
        },
        preConfirm: function() {
            var rejoin_date = $('#rejoin_date').val();
            
            // Validate rejoin_date is required
            if(!rejoin_date || rejoin_date.trim() === '') {
                Swal.showValidationMessage(__('rejoin_date_required'));
                return false;
            }
            
            return $.ajax({
                url: './includes/ajaxFile/leaveHandler.php',
                type: 'POST',
                dataType: 'JSON',
                data: {
                    ajaxType: 'submitRejoinRequest',
                    vacation_id: vacation_id,
                    emp_id: emp_id,
                    rejoin_date: rejoin_date,
                    rejoin_reason: $('#rejoin_reason').val()
                }
            }).then(function(response) {
                // Only return success, throw everything else to prevent default error modal
                if(response.type === 'success') {
                    return response;
                } else {
                    // Throw with special marker to identify in catch
                    return Promise.reject(response);
                }
            }).fail(function(jqXHR) {
                // AJAX error - parse the response
                if(jqXHR.responseJSON) {
                    return Promise.reject(jqXHR.responseJSON);
                } else {
                    return Promise.reject({
                        type: 'error',
                        title: __('error'),
                        message: __('request_failed_status')
                    });
                }
            });
        }
    }).then(function(result) {
        // Only success responses reach here
        if(result.value && result.value.type === 'success') {
            Swal.fire({
                icon: 'success',
                title: result.value.title || __('rejoin_request_submitted'),
                text: result.value.message,
                allowOutsideClick: false,
                confirmButtonColor: APP_COLORS.primary,
                confirmButtonText: __('ok')
            }).then(function() {
                location.reload();
            });
        }
    }).catch(function(dismissReason) {
        // SweetAlert2 was dismissed/rejected
        // Check if it's our custom rejection (warning/error) or just cancelled
        if(dismissReason && typeof dismissReason === 'object' && dismissReason.type) {
            // This is our custom error/warning response
            if(dismissReason.type === 'warning' && dismissReason.active_request) {
                // Show warning with active request details
                Swal.fire({
                    icon: 'warning',
                    title: dismissReason.title || __('active_rejoin_request_exists'),
                    html: `
                        <div style="background-color: #e3f2fd; border-left: 4px solid ${APP_COLORS.primary}; padding: 15px; text-align: left; margin-top: 15px; border-radius: 4px;">
                            <div style="margin-bottom: 10px;">
                                <strong>${__('request_number')}:</strong> 
                                <span style="color: ${APP_COLORS.danger_dark};">${dismissReason.active_request.request_inv_no}</span>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>${__('status')}:</strong> 
                                <span style="color: ${APP_COLORS.danger_dark}; font-weight: bold;">${dismissReason.active_request.status.toUpperCase()}</span>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>${__('requested_rejoin_date')}:</strong> 
                                <span>${dismissReason.active_request.requested_rejoin_date}</span>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>${__('submitted_at')}:</strong> 
                                <span>${dismissReason.active_request.requested_at}</span>
                            </div>
                            <hr style="margin: 10px 0;">
                            <div style="margin-bottom: 10px;">
                                <strong>${__('associated_vacation')}:</strong> 
                                <span>${dismissReason.active_request.vacation_inv_no}</span>
                            </div>
                            <div>
                                <strong>${__('vacation_type')}:</strong> 
                                <span>${dismissReason.active_request.vac_type}</span>
                            </div>
                        </div>
                    `,
                    allowOutsideClick: false,
                    confirmButtonColor: APP_COLORS.primary,
                    confirmButtonText: __('ok')
                });
            } else if(dismissReason.type === 'error') {
                // Show error modal
                Swal.fire({
                    icon: 'error',
                    title: dismissReason.title || __('error'),
                    text: dismissReason.message || __('request_failed_status'),
                    allowOutsideClick: false,
                    confirmButtonColor: APP_COLORS.danger_dark,
                    confirmButtonText: __('ok')
                });
            }
        }
        // Otherwise, it was just cancelled - do nothing
    });
});

////////////////////////////////////////////////////////////////////
////////////      End Rejoin Request Handling        //////////////
////////////////////////////////////////////////////////////////////

// Safe HTML escaper (since Swal.escapeHtml is not available)
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, function (s) {
        switch (s) {
            case '&': return '&amp;';
            case '<': return '&lt;';
            case '>': return '&gt;';
            case '"': return '&quot;';
            case "'": return '&#39;';
            default: return s;
        }
    });
}

(function ($) {
    "use_strict";
    $.App.init();

    // === ALL NOTIFICATION LOGIC HAS BEEN MOVED TO assets/js/notifications.js ===

}(window.jQuery));

// ================================================================
// === SESSION TIMEOUT HANDLING (Moved from session_check.php) ===
// ================================================================
// Global state for pre-timeout alert
let alertShown = false;
let countdownInterval;

function extendSessionAndDismissAlert() {
    // Reset client-side timer
    alertShown = false;
    if (countdownInterval) clearInterval(countdownInterval);
    
    console.log('Extend Session clicked - sending request...');
    
    // Close current alert first
    Swal.close();
    
    // Keep session alive on server
    fetch('/includes/session_check.php?extend_session=1', { 
        method: 'GET', 
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Session extended:', data);
        // Reset to current server time
        window.SERVER_LAST_ACTIVITY = Math.floor(Date.now() / 1000);
        
        // Show success toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
        Toast.fire({
            icon: 'success',
            title: 'Session extended for another ' + Math.floor(window.SESSION_TIMEOUT_MS / 60000) + ' minutes'
        });
    })
    .catch(error => {
        console.error('Extend session error:', error);
        // Reset anyway even on error
        window.SERVER_LAST_ACTIVITY = Math.floor(Date.now() / 1000);
        
        // Show error toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
        Toast.fire({
            icon: 'error',
            title: 'Failed to extend session'
        });
    });
}

// Pre-timeout warning system - Initialize when SESSION_TIMEOUT_SECONDS is available
$(document).ready(function() {
    // Wait for session config to be available from session_check.php
    if (typeof window.SESSION_TIMEOUT_SECONDS !== 'undefined') {
        initSessionTimeoutAlert();
    } else {
        // Fallback: check again after a short delay
        setTimeout(function() {
            if (typeof window.SESSION_TIMEOUT_SECONDS !== 'undefined') {
                initSessionTimeoutAlert();
            }
        }, 500);
    }
});

function initSessionTimeoutAlert() {
    (function initPreTimeoutAlert() {
        function checkTimeout() {
            // Calculate elapsed time based on server's last_activity, not client time
            const currentServerTime = Math.floor(Date.now() / 1000);
            const elapsedSeconds = currentServerTime - window.SERVER_LAST_ACTIVITY;
            const remainingSeconds = window.SESSION_TIMEOUT_SECONDS - elapsedSeconds;
            
            // Show alert when 30 seconds remain (and hasn't been shown yet)
            if (remainingSeconds <= 30 && remainingSeconds > 0 && !alertShown) {
                alertShown = true;
                if (countdownInterval) clearInterval(countdownInterval);
                showPreTimeoutAlert(remainingSeconds);
            }
        }
        
        function showPreTimeoutAlert(initialCountdown) {
            let countdown = initialCountdown;
            
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: true,
                confirmButtonText: 'Extend Session',
                showCloseButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                timer: countdown * 1000,
                timerProgressBar: true,
            });
            
            Toast.fire({
                icon: 'warning',
                title: 'Session Expiring Soon',
                html: 'Your session will expire in <strong id="pre-alert-countdown">' + countdown + '</strong> seconds.'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Extend button clicked
                    console.log('Extend button clicked');
                    extendSessionAndDismissAlert();
                }
            });
            
            // Update countdown every second
            countdownInterval = setInterval(() => {
                countdown--;
                const countdownEl = document.getElementById('pre-alert-countdown');
                if (countdownEl) countdownEl.textContent = countdown;
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                }
            }, 1000);
            
            // Pause/resume on hover
            const alertContainer = Toast.getContainer();
            if (alertContainer) {
                alertContainer.addEventListener('mouseenter', () => {
                    Swal.stopTimer();
                    if (countdownInterval) clearInterval(countdownInterval);
                });
                
                alertContainer.addEventListener('mouseleave', () => {
                    Swal.resumeTimer();
                    countdownInterval = setInterval(() => {
                        countdown--;
                        const countdownEl = document.getElementById('pre-alert-countdown');
                        if (countdownEl) countdownEl.textContent = countdown;
                    }, 1000);
                });
            }
        }
        
        // Check for pre-timeout every 500ms
        setInterval(checkTimeout, 500);
    })();
}

/**
 * =====================================================================
 * SETTLEMENT MANAGEMENT FUNCTIONS (Global - Reusable across all pages)
 * =====================================================================
 * These functions handle viewing, approving, rejecting, and processing
 * settlement records across all pages (loans, vacations, advances, etc.)
 */

function viewSettlementDetails(settlementId, settlementInvNo) {
    // Show loading indicator
    Swal.fire({
        title: __('loading_settlement_details'),
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: './includes/ajaxFile/settlement_handler.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'get_settlement_details',
            settlement_id: settlementId
        },
        success: function(response) {
            if (response.success) {
                const s = response.data.settlement;
                const isRtl = document.documentElement.getAttribute('dir') === 'rtl';
                const formatFileSize = (bytes) => {
                    const size = parseInt(bytes || 0, 10);
                    if (!size) {
                        return '0 B';
                    }
                    const units = ['B', 'KB', 'MB', 'GB'];
                    const idx = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);
                    const value = size / Math.pow(1024, idx);
                    return `${value.toFixed(idx === 0 ? 0 : 1)} ${units[idx]}`;
                };
                
                // Build approval chain HTML
                let approvalChainHtml = '<div style="margin-top: 15px;"><h6>' + __('approval_chain') + ':</h6><table style="width: 100%; font-size: 13px;" dir="' + (isRtl ? 'rtl' : 'ltr') + '"><tr style="background: #f5f5f5;"><th style="padding: 8px; border: 1px solid #ddd;">' + __('level') + '</th><th style="padding: 8px; border: 1px solid #ddd;">' + __('status') + '</th><th style="padding: 8px; border: 1px solid #ddd;">' + __('approver') + '</th></tr>';
                if (response.data.approval_chain && response.data.approval_chain.length > 0) {
                    response.data.approval_chain.forEach((level, idx) => {
                        const statusBadge = level.status === 'approved' ? '<span style="background: '+ APP_COLORS.success +'; color: white; padding: 3px 8px; border-radius: 3px;">✓ ' + __('approved') + '</span>' :
                                           level.status === 'pending' ? '<span style="background: '+ APP_COLORS.warning +'; color: black; padding: 3px 8px; border-radius: 3px;">⏳ ' + __('pending') + '</span>' :
                                           level.status === 'rejected' ? '<span style="background: '+ APP_COLORS.danger_dark +'; color: white; padding: 3px 8px; border-radius: 3px;">✗ ' + __('rejected') + '</span>' :
                                           '<span style="background: '+ APP_COLORS.secondary +'; color: white; padding: 3px 8px; border-radius: 3px;">' + __('awaiting') + '</span>';
                        approvalChainHtml += `<tr><td style="padding: 8px; border: 1px solid ${APP_COLORS.border};">` + __('level') + ` ${level.approval_level}</td><td style="padding: 8px; border: 1px solid ${APP_COLORS.border};">${statusBadge}</td><td style="padding: 8px; border: 1px solid ${APP_COLORS.border};">${level.approver_name || __('not_assigned')}</td></tr>`;
                    });
                }
                approvalChainHtml += '</table></div>';
                
                // Build report link and payment proof button based on request type
                let reportLink = '';
                let downloadProofButton = '';
                const requestType = s.request_type ? s.request_type.toLowerCase() : '';
                const requestId = response.data.settlement.related_request_id || s.id;
                const empId = s.emp_id;
                
                if (requestType.includes('vacation')) {
                    reportLink = '<a href="./vacation_report_details.php?id=' + requestId + '&emp_id=' + empId + '" target="_blank" class="btn btn-sm btn-info" style="margin-top: 10px;"><i class="fa fa-file-chart-line"></i> ' + __('view_vacation_report') + '</a>';
                } else if (requestType.includes('loan')) {
                    reportLink = '<a href="./loan_report_details.php?id=' + requestId + '&emp_id=' + empId + '" target="_blank" class="btn btn-sm btn-info" style="margin-top: 10px;"><i class="fa fa-file-chart-line"></i> ' + __('view_loan_report') + '</a>';
                } else if (requestType.includes('resignation')) {
                    reportLink = '<a href="./eos_pdf.php?emp_id=' + empId + '" target="_blank" class="btn btn-sm btn-info" style="margin-top: 10px;"><i class="fa fa-file-chart-line"></i> ' + __('view_end_of_service_report') + '</a>';
                } else {
                    reportLink = '<a href="./all_general_requests.php?id=' + requestId + '&emp_id=' + empId + '" target="_blank" class="btn btn-sm btn-info" style="margin-top: 10px;"><i class="fa fa-file-chart-line"></i> ' + __('view_requests_report') + '</a>';
                }
                
                // Build payment proof download button if available
                if (s.payment_reference && s.payment_reference.trim() !== '') {
                    const proofPath = s.payment_reference;
                    const isImage = /\.(jpg|jpeg|png|gif|bmp)$/i.test(proofPath);
                    const buttonText = __('download_proof');
                    const marginDir = isRtl ? 'right' : 'left';
                    downloadProofButton = `<a href="./${proofPath}" target="_blank" class="btn btn-sm btn-primary" style="margin-top: 10px; margin-${marginDir}: 5px;"><i class="fa fa-download"></i> ${buttonText}</a>`;
                }
                
                // Build settlement attachments - use showAttachmentsModal for unified view
                let attachmentsHtml = '';
                const attachments = (response.data && response.data.attachments) ? response.data.attachments : [];
                if (attachments.length > 0) {
                    // Convert attachment objects to properly formatted file URLs
                    // Settlement attachments have structure: {id, file_path, file_name, uploaded_at, ...}
                    const attachmentPaths = attachments.map(att => {
                        if (!att || !att.file_path) return '';
                        
                        let filePath = att.file_path;
                        
                        // If file_path doesn't contain slashes, it needs date-based path construction
                        if (filePath.indexOf('/') === -1 && att.uploaded_at) {
                            const uploadDate = new Date(att.uploaded_at);
                            const year = uploadDate.getFullYear();
                            const month = String(uploadDate.getMonth() + 1).padStart(2, '0');
                            filePath = 'uploads/settlement_attachments/' + year + '/' + month + '/' + filePath;
                        } else if (filePath.indexOf('/') === -1) {
                            // Fallback if no date available
                            filePath = 'uploads/settlement_attachments/' + filePath;
                        }
                        
                        return filePath;
                    }).filter(path => path.length > 0);
                    
                    if (attachmentPaths.length > 0) {
                        const attachmentPathsJson = JSON.stringify(attachmentPaths).replace(/"/g, '&quot;');
                        attachmentsHtml = '<br><h6 style="margin-top: 15px; color: ' + APP_COLORS.text_dark + '; font-weight: 600;">📎 ' + __('attachments') + ' (' + attachmentPaths.length + '):</h6>';
                        attachmentsHtml += '<button type="button" class="btn btn-sm btn-primary" onclick="showAttachmentsModal(' + attachmentPathsJson + ', &quot;' + __('attachments') + '&quot;)" style="margin-top: 8px;"><i class="fa fa-eye"></i> ' + __('view_attachments') + '</button>';
                    }
                }
                
                let historyHtml = '<div style="margin-top: 15px;">' + reportLink + downloadProofButton + attachmentsHtml + '</div>';
                
                const contentHtml = `
                    <div style="text-align: ` + (isRtl ? 'right' : 'left') + `; font-size: 14px; direction: ` + (isRtl ? 'rtl' : 'ltr') + `;">
                        <h5 style="margin-bottom: 15px; color: ${APP_COLORS.text_dark};">` + __('settlement_information') + `</h5>
                        <table style="width: 100%; margin-bottom: 10px;" dir="` + (isRtl ? 'rtl' : 'ltr') + `">
                            <tr><td style="padding: 8px; font-weight: bold; width: 35%;">` + __('settlement_id') + `:</td><td style="padding: 8px; color: ${APP_COLORS.primary}; font-weight: 600;">${htmlspecialcharsJs(s.request_inv_no)}</td></tr>
                            <tr style="background: ${APP_COLORS.background_light};"><td style="padding: 8px; font-weight: bold;">` + __('employee_name') + `:</td><td style="padding: 8px;">${htmlspecialcharsJs(s.emp_name)}</td></tr>
                            <tr><td style="padding: 8px; font-weight: bold;">` + __('employee_id') + `:</td><td style="padding: 8px;">${htmlspecialcharsJs(s.emp_id)}</td></tr>
                            <tr style="background: ${APP_COLORS.background_light};"><td style="padding: 8px; font-weight: bold;">` + __('settlement_amount') + `:</td><td style="padding: 8px; color: ${APP_COLORS.success}; font-weight: 600;">SAR ${Math.round(parseFloat(s.calculated_payable_amount || s.settlement_amount || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td></tr>
                            <tr><td style="padding: 8px; font-weight: bold;">` + __('settlement_method') + `:</td><td style="padding: 8px;">${s.settlement_method || __('not_available')}</td></tr>
                            <tr style="background: ${APP_COLORS.background_light};"><td style="padding: 8px; font-weight: bold;">` + __('status') + `:</td><td style="padding: 8px;"><span style="background: ${APP_COLORS.primary}; color: white; padding: 3px 8px; border-radius: 3px;">${(s.settlement_status || '').toUpperCase()}</span></td></tr>
                            <tr><td style="padding: 8px; font-weight: bold;">` + __('created') + `:</td><td style="padding: 8px;">${new Date(s.created_at).toLocaleDateString('en-US', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'})}</td></tr>
                        </table>
                        ${approvalChainHtml}
                        ${historyHtml}
                    </div>
                `;
                
                Swal.fire({
                    title: __('settlement_details'),
                    html: contentHtml,
                    width: 700,
                    allowOutsideClick: false,
                    confirmButtonText: __('close')
                });
            } else {
                Swal.fire(__('error'), response.message || __('failed_to_load_settlement_details'), 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            console.error('Response:', xhr.responseText);
            Swal.fire(__('error'), __('failed_to_load_settlement_details') + ': ' + error, 'error');
        }
    });
}

/**
 * REUSABLE FINANCE MANAGER APPROVAL MODAL
 * Used for both Loan and Settlement approvals
 * Allows Finance Manager to select self or another employee to process payment
 * 
 * @param {number} requestId - Loan or Settlement ID
 * @param {string} requestType - 'loan' or 'settlement'
 * @param {number} requestedAmount - Amount to be approved
 * @param {object} options - Configuration options {ajaxEndpoint, onApprove, additionalParams}
 */
function openFinanceManagerApprovalModal(requestId, requestType, requestedAmount, options = {}) {
    const {
        ajaxEndpoint = './includes/ajaxFile/ajaxLoan.php',
        onApprove = null,
        additionalParams = {}
    } = options;
    
    // Fetch finance staff
    $.ajax({
        url: ajaxEndpoint,
        type: 'POST',
        dataType: 'JSON',
        data: { ajaxType: 'get_finance_staff' },
        success: function(staffResponse) {
            const isRtl = document.documentElement.getAttribute('dir') === 'rtl';
            const modalId = `financeApprovalModal_${requestType}_${requestId}`;
            
            let formHtml = `<div style="text-align: ` + (isRtl ? 'right' : 'left') + `;">
                <div class="form-group">
                    <label>` + __('approval_comment') + `:</label>
                    <textarea id="approvalComment_${modalId}" class="form-control" placeholder="` + __('add_approval_comment') + `" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label style="color: ${APP_COLORS.danger};">` + __('select_payer') + `: <span style="color: red;">*</span></label>
                    <div style="margin: 10px 0;">
                        <label style="display: block; margin: 8px 0;">
                            <input type="radio" name="payerType_${modalId}" value="self" checked> ` + __('myself') + `
                        </label>
                        <label style="display: block; margin: 8px 0;">
                            <input type="radio" name="payerType_${modalId}" value="other"> ` + __('other_finance_employee') + `
                        </label>
                    </div>
                </div>
                
                <div class="form-group" id="otherPayerGroup_${modalId}" style="display: none;">
                    <label style="color: ${APP_COLORS.danger};">` + __('finance_department_employee') + `: <span style="color: red;">*</span></label>
                    <select id="payerSelect_${modalId}" class="form-control form-control-lg select2-hidden-accessible" style="width: 100%;" required>
                        <option value="">` + __('select_finance_employee') + `</option>
                    </select>
                </div>
                
                <div class="form-group" id="approvedAmountGroup_${modalId}" style="text-align: ` + (isRtl ? 'right' : 'left') + `;">
                    <label style="color: ${APP_COLORS.danger};">` + __('approved_amount_sar') + `: <span style="color: red;">*</span></label>
                    <input type="number" id="approvedAmount_${modalId}" class="form-control" step="0.01" value="${parseFloat(requestedAmount).toFixed(2)}" required>
                    <small class="text-muted">` + __('settlement_amount_sar') + ` ${parseFloat(requestedAmount).toFixed(2)}</small>
                    <div id="amountError_${modalId}" style="color: ${APP_COLORS.danger}; font-size: 12px; margin-top: 5px; display: none;">Amount must match exactly</div>
                </div>
                
                <div class="form-group" id="paymentProofGroup_${modalId}">
                    <label style="color: ${APP_COLORS.danger};">` + __('payment_proof') + `: <span style="color: red;">*</span></label>
                    <input type="file" id="paymentProof_${modalId}" class="form-control-file" accept="image/*,application/pdf">
                    <small class="text-muted">` + __('attach_payment_receipt_proof') + `</small>
                </div>
            </div>`;
            
            Swal.fire({
                title: __('approve_' + requestType),
                html: formHtml,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: __('approve'),
                confirmButtonColor: APP_COLORS.success,
                cancelButtonText: __('cancel'),
                allowOutsideClick: false,
                preConfirm: () => {
                    const swalContainer = Swal.getHtmlContainer();
                    const comment = document.getElementById(`approvalComment_${modalId}`).value;
                    let payerType = null;
                    let payerSelect = null;
                    let paymentProofFile = null;
                    let approvedAmount = null;
                    
                    // Collect payer type
                    const payerTypeElem = document.querySelector(`input[name="payerType_${modalId}"]:checked`);
                    payerType = payerTypeElem ? payerTypeElem.value : 'self';
                    
                    if (payerType === 'other') {
                        const payerSelectElem = document.getElementById(`payerSelect_${modalId}`);
                        payerSelect = payerSelectElem ? payerSelectElem.value : null;
                        if (!payerSelect) {
                            Swal.showValidationMessage(__('select_finance_employee') || 'Please select a finance employee');
                            return false;
                        }
                    }
                    
                    // Collect approved amount and payment proof
                    const approvedAmountElem = document.getElementById(`approvedAmount_${modalId}`);
                    if (approvedAmountElem) {
                        approvedAmount = parseFloat(approvedAmountElem.value || 0);
                    }
                    
                    const paymentProofInput = swalContainer ? swalContainer.querySelector(`#paymentProof_${modalId}`) : null;
                    if (paymentProofInput && paymentProofInput.files && paymentProofInput.files[0]) {
                        paymentProofFile = paymentProofInput.files[0];
                    }
                    
                    // Only validate amount and payment proof if paying myself
                    if (payerType === 'self') {
                        if (!approvedAmount || approvedAmount <= 0) {
                            Swal.showValidationMessage(__('approved_amount_required') || 'Approved amount is required');
                            return false;
                        }
                        
                        if (Math.abs(approvedAmount - parseFloat(requestedAmount)) > 0.01) {
                            Swal.showValidationMessage(__('amount_must_match_approved') || 'Amount must match exactly');
                            return false;
                        }
                        
                        if (!paymentProofFile) {
                            Swal.showValidationMessage(__('payment_proof_document_is_required') || 'Payment proof is required');
                            return false;
                        }
                    }
                    
                    return { 
                        comment, 
                        payerType, 
                        payerSelect, 
                        paymentProofFile,
                        approvedAmount
                    };
                },
                didOpen: () => {
                    // Populate Select2 with finance employees
                    setTimeout(() => {
                        if (staffResponse.status === 'success' && staffResponse.staff) {
                            $('#payerSelect_' + modalId).find('option:not(:first)').remove();
                            staffResponse.staff.forEach(emp => {
                                $(`#payerSelect_${modalId}`).append(`<option value="${emp.emp_id}">${emp.name} (${emp.emp_id})</option>`);
                            });
                            
                            if ($('#payerSelect_' + modalId).data('select2')) {
                                $('#payerSelect_' + modalId).select2('destroy');
                            }
                            
                            $('#payerSelect_' + modalId).select2({
                                placeholder: __('select_finance_employee'),
                                allowClear: true,
                                width: '100%'
                            });
                            
                            $('.select2-container').css({
                                'position': 'relative',
                                'z-index': '9999'
                            });
                        }
                    }, 150);
                    
                    // Handle payer type change
                    $(`input[name="payerType_${modalId}"]`).on('change', function() {
                        if ($(this).val() === 'other') {
                            $(`#otherPayerGroup_${modalId}`).show();
                            $(`#approvedAmountGroup_${modalId}`).hide();
                            $(`#paymentProofGroup_${modalId}`).hide();
                            setTimeout(() => {
                                $(`#payerSelect_${modalId}`).select2('open');
                                $(`#payerSelect_${modalId}`).select2('close');
                            }, 100);
                        } else {
                            $(`#otherPayerGroup_${modalId}`).hide();
                            $(`#approvedAmountGroup_${modalId}`).show();
                            $(`#paymentProofGroup_${modalId}`).show();
                        }
                    });
                    
                    // Validate amount on input
                    $(`#approvedAmount_${modalId}`).on('input', function() {
                        const enteredAmt = parseFloat($(this).val() || 0);
                        if (Math.abs(enteredAmt - parseFloat(requestedAmount)) > 0.01) {
                            $(`#amountError_${modalId}`).show();
                            $('.swal2-confirm').prop('disabled', true);
                        } else {
                            $(`#amountError_${modalId}`).hide();
                            $('.swal2-confirm').prop('disabled', false);
                        }
                    });
                }
            }).then((result) => {
                if (result.isConfirmed && typeof onApprove === 'function') {
                    onApprove(result.value, additionalParams);
                }
            });
        },
        error: function() {
            Swal.fire({
                title: __('error_title') || 'Error',
                text: __('failed_to_load_payer_list') || 'Failed to load finance staff list.',
                icon: 'error',
                allowOutsideClick: false,
                confirmButtonColor: APP_COLORS.danger
            });
        }
    });
}

function approveSettlement(settlementId, settlementInvNo, empId) {
    // First, check if this is the final approval level
    $.ajax({
        url: './includes/ajaxFile/settlement_handler.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'check_final_approval',
            settlement_id: settlementId
        },
        success: function(checkResponse) {
            const isFinalApproval = checkResponse.is_final_approval || false;
            const settlementAmount = checkResponse.settlement_amount || 0;
            const isFinanceManager = checkResponse.is_finance_manager || false;
            const isFinanceEmployee = checkResponse.is_finance_employee || false;
            const isHRPayroll = (typeof window.currentUserType !== 'undefined') && window.currentUserType.toLowerCase().includes('hr_payroll');
            
            const isRtl = document.documentElement.getAttribute('dir') === 'rtl';
            
            let formHtml = `<div style="text-align: ` + (isRtl ? 'right' : 'left') + `;">
                <div class="form-group">
                    <label>` + __('approval_comment') + `:</label>
                    <textarea id="approvalComment" class="form-control" placeholder="` + __('add_approval_comment') + `" rows="3"></textarea>
                </div>`;
            
            // Add WPS file input for HR Payroll users
            if (isHRPayroll) {
                formHtml += `
                <hr>
                <h6 class="text-primary font-weight-bold">` + __('wps_file_upload_hr_payroll') + `</h6>
                <div class="form-group">
                    <label for="wpsFileUpload"><strong>` + __('select_wps_file_optional') + `</strong></label>
                    <input type="file" id="wpsFileUpload" class="form-control" accept="image/*,.pdf" />
                    <small class="form-text text-muted">` + __('accepted_formats') + ` ` + __('max_mb').replace('{{filesize}}', MAX_FILE_SIZE_MB) + `</small>
                </div>`;
            }
            
            // Show payer selection if Finance Manager at final approval
            if (isFinalApproval && isFinanceManager) {
                formHtml += `
                <div class="form-group">
                    <label class="text-danger">` + __('select_payer') + `: <span style="color: red;">*</span></label>
                    <div style="margin: 10px 0;">
                        <label style="display: block; margin: 8px 0;">
                            <input type="radio" name="payerType" value="self" checked> ` + __('myself') + `
                        </label>
                        <label style="display: block; margin: 8px 0;">
                            <input type="radio" name="payerType" value="other"> ` + __('other_finance_employee') + `
                        </label>
                    </div>
                </div>
                <div class="form-group" id="otherPayerGroup" style="display: none;">
                    <label class="text-danger">` + __('finance_department_employee') + `: <span style="color: red;">*</span></label>
                    <select id="payerSelect" class="form-control form-control-lg select2-hidden-accessible" style="width: 100%;" required>
                        <option value="">` + __('select_finance_employee') + `</option>
                    </select>
                </div>
                <div class="form-group" id="approvedAmountGroup" style="text-align: ` + (isRtl ? 'right' : 'left') + `;">
                    <label class="text-danger">` + __('approved_amount_sar') + `: <span style="color: red;">*</span></label>
                    <input type="number" id="approvedAmount" class="form-control" step="0.01" value="${parseFloat(settlementAmount).toFixed(2)}" required>
                    <small class="text-muted">` + __('settlement_amount_sar') + ` ${parseFloat(settlementAmount).toFixed(2)}</small>
                    <div id="amountError" style="color: red; font-size: 12px; margin-top: 5px; display: none;">Amount must match settlement amount exactly</div>
                </div>
                <div class="form-group" id="paymentProofGroup">
                    <label class="text-danger">` + __('payment_proof') + `: <span style="color: red;">*</span></label>
                    <input type="file" id="paymentProof" class="form-control-file" accept="image/*,application/pdf" required>
                    <small class="text-muted">` + __('attach_payment_receipt_proof') + `</small>
                </div>`;
            } else if (isFinanceEmployee) {
                // Finance Officer - show payment form (at final approval or when they need to process payment)
                formHtml += `
                <div class="form-group" id="approvedAmountGroup">
                    <label class="text-danger">` + __('approved_amount_sar') + `: <span style="color: red;">*</span></label>
                    <input type="number" id="approvedAmount" class="form-control" step="0.01" value="${parseFloat(settlementAmount).toFixed(2)}" required>
                    <small class="text-muted">` + __('settlement_amount_sar') + ` ${parseFloat(settlementAmount).toFixed(2)}</small>
                    <div id="amountError" style="color: red; font-size: 12px; margin-top: 5px; display: none;">Amount must match settlement amount exactly</div>
                </div>
                <div class="form-group" id="paymentProofGroup">
                    <label class="text-danger">` + __('payment_proof') + `: <span style="color: red;">*</span></label>
                    <input type="file" id="paymentProof" class="form-control-file" accept="image/*,application/pdf" required>
                    <small class="text-muted">` + __('attach_payment_receipt_proof') + `</small>
                </div>`;
            }
            
            formHtml += `</div>`;
            
            // Store payer type to persist across modal lifecycle
            let selectedPayerType = 'self';
            let formValues = {};
            
            Swal.fire({
                title: (isFinalApproval && (isFinanceManager || isFinanceEmployee)) ? __('approve_settlement') : __('approve_settlement'),
                html: formHtml,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: __('approve'),
                confirmButtonColor: APP_COLORS.primary,
                cancelButtonText: __('cancel'),
                allowOutsideClick: false,
                preConfirm: () => {
                    const swalContainer = Swal.getHtmlContainer();
                    const comment = document.getElementById('approvalComment').value;
                    let wpsFile = null;
                    let payerType = null;
                    let payerSelect = null;
                    let paymentProofFile = null;
                    let approvedAmount = null;
                    
                    // Collect WPS file if HR Payroll
                    if (isHRPayroll) {
                        const fileInput = swalContainer ? swalContainer.querySelector('#wpsFileUpload') : null;
                        if (fileInput && fileInput.files && fileInput.files[0]) {
                            const file = fileInput.files[0];
                            const allowedFormats = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'pdf'];
                            const fileExtension = file.name.split('.').pop().toLowerCase();
                            if (!allowedFormats.includes(fileExtension)) {
                                Swal.showValidationMessage(`${__('invalid_file_format')}. ${__('upload_pdf_jpg_only_validation')}`);
                                return false;
                            }
                            if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
                                Swal.showValidationMessage(`${__('file_size_exceeded').replace('{{filesize}}', MAX_FILE_SIZE_MB)}`);
                                return false;
                            }
                            wpsFile = file;
                        }
                    }
                    
                    // Collect Finance Manager fields
                    if (isFinanceManager) {
                        const payerTypeElem = document.querySelector('input[name="payerType"]:checked');
                        payerType = payerTypeElem ? payerTypeElem.value : 'self';
                        
                        if (payerType === 'other') {
                            const payerSelectElem = document.getElementById('payerSelect');
                            payerSelect = payerSelectElem ? payerSelectElem.value : null;
                        }
                    }
                    
                    // Collect payment proof and approved amount for Finance Manager or Finance Officer
                    if ((isFinanceManager && isFinalApproval) || isFinanceEmployee) {
                        const approvedAmountElem = document.getElementById('approvedAmount');
                        if (approvedAmountElem) {
                            approvedAmount = parseFloat(approvedAmountElem.value || 0);
                        }
                        
                        const paymentProofInput = swalContainer ? swalContainer.querySelector('#paymentProof') : null;
                        if (paymentProofInput && paymentProofInput.files && paymentProofInput.files[0]) {
                            paymentProofFile = paymentProofInput.files[0];
                        }
                    }
                    
                    // Return all collected values
                    return { 
                        comment, 
                        payerType, 
                        wpsFile, 
                        payerSelect, 
                        paymentProofFile,
                        approvedAmount
                    };
                },
                didOpen: () => {
                    if (isFinalApproval && isFinanceManager) {
                        // Fetch finance employees and populate Select2
                        setTimeout(() => {
                            $.ajax({
                                url: './includes/ajaxFile/settlement_handler.php',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    action: 'get_finance_employees'
                                },
                                success: function(response) {
                                    if (response.success && response.employees) {
                                        // Clear existing options except the first one
                                        $('#payerSelect').find('option:not(:first)').remove();
                                        
                                        response.employees.forEach(emp => {
                                            $('#payerSelect').append(`<option value="${emp.emp_id}">${emp.name} (${emp.emp_id})</option>`);
                                        });
                                        
                                        // Destroy existing Select2 if any
                                        if ($('#payerSelect').data('select2')) {
                                            $('#payerSelect').select2('destroy');
                                        }
                                        
                                        // Initialize Select2 with better options
                                        $('#payerSelect').select2({
                                            placeholder: __('select_finance_employee'),
                                            allowClear: true,
                                            width: '100%'
                                        });
                                        
                                        // Adjust dropdown positioning
                                        $('.select2-container').css({
                                            'position': 'relative',
                                            'z-index': '9999'
                                        });
                                    }
                                },
                                error: function() {
                                    console.error('Failed to load finance employees');
                                }
                            });
                        }, 150);
                        
                        // Handle payer type change
                        $('input[name="payerType"]').on('change', function() {
                            if ($(this).val() === 'other') {
                                $('#otherPayerGroup').show();
                                $('#approvedAmountGroup').hide();
                                $('#paymentProofGroup').hide();
                                // Trigger Select2 to show dropdown properly
                                setTimeout(() => {
                                    $('#payerSelect').select2('open');
                                    $('#payerSelect').select2('close');
                                }, 100);
                            } else {
                                $('#otherPayerGroup').hide();
                                $('#approvedAmountGroup').show();
                                $('#paymentProofGroup').show();
                            }
                        });
                        
                        // Validate amount on input
                        $('#approvedAmount').on('input', function() {
                            const enteredAmt = parseFloat($(this).val() || 0);
                            if (Math.abs(enteredAmt - settlementAmount) > 0.01) {
                                $('#amountError').show();
                                $('.swal2-confirm').prop('disabled', true);
                            } else {
                                $('#amountError').hide();
                                $('.swal2-confirm').prop('disabled', false);
                            }
                        });
                    } else if (isFinalApproval && isFinanceEmployee) {
                        // Finance Officer - apply amount validation
                        $('#approvedAmount').on('input', function() {
                            const enteredAmt = parseFloat($(this).val() || 0);
                            if (Math.abs(enteredAmt - settlementAmount) > 0.01) {
                                $('#amountError').show();
                                $('.swal2-confirm').prop('disabled', true);
                            } else {
                                $('#amountError').hide();
                                $('.swal2-confirm').prop('disabled', false);
                            }
                        });
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const comment = result.value.comment;
                    const payerType = result.value.payerType;
                    const wpsFile = result.value.wpsFile;
                    // Show loading indicator
                    Swal.fire({
                        title: __('processing'),
                        text: __('approving_settlement_please_wait'),
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    let formData = new FormData();
                    // Determine the correct action based on whether WPS file is being uploaded
                    let action = 'approve_settlement';
                    if (isHRPayroll && wpsFile) {
                        formData.append('wps_file', wpsFile);
                        action = 'approve_settlement_with_wps';
                    }
                    formData.append('action', action);
                    formData.append('settlement_id', settlementId);
                    formData.append('settlement_inv_no', settlementInvNo);
                    formData.append('emp_id', empId);
                    formData.append('approval_comment', comment);
                    formData.append('is_hr_payroll', isHRPayroll ? '1' : '0');

                    if (isFinalApproval) {
                        formData.append('is_final_approval', '1');
                        // Payer logic for Finance Manager
                        if (isFinanceManager) {
                            formData.append('payer_type', payerType);
                            const currentEmpId = document.body.getAttribute('data-empid') || (typeof window.currentEmpId !== 'undefined' ? window.currentEmpId : '');
                            const payerId = payerType === 'self' ? currentEmpId : result.value.payerSelect;
                            formData.append('payer_id', payerId);
                            if (payerType === 'self') {
                                if (result.value.approvedAmount && result.value.approvedAmount > 0) {
                                    formData.append('approved_amount', result.value.approvedAmount);
                                }
                                    if (result.value.paymentProofFile) {
                                        formData.append('payment_proof', result.value.paymentProofFile);
                                    }
                            }
                        } else if (isFinanceEmployee) {
                            // Finance Officer at final approval
                            formData.append('payer_type', 'self');
                            const currentEmpId = document.body.getAttribute('data-empid') || (typeof window.currentEmpId !== 'undefined' ? window.currentEmpId : '');
                            formData.append('payer_id', currentEmpId);
                            if (result.value.approvedAmount && result.value.approvedAmount > 0) {
                                formData.append('approved_amount', result.value.approvedAmount);
                            }
                            // Send payment proof file if present
                            if (result.value.paymentProofFile) {
                                formData.append('payment_proof', result.value.paymentProofFile);
                            }
                        }
                    } else if (isFinanceEmployee && result.value.paymentProofFile) {
                        // Finance Officer with payment proof (not marked as final approval initially)
                        // Mark as final approval and send payment proof
                        formData.append('is_final_approval', '1');
                        formData.append('payer_type', 'self');
                        const currentEmpId = document.body.getAttribute('data-empid') || (typeof window.currentEmpId !== 'undefined' ? window.currentEmpId : '');
                        formData.append('payer_id', currentEmpId);
                        if (result.value.approvedAmount && result.value.approvedAmount > 0) {
                            formData.append('approved_amount', result.value.approvedAmount);
                        }
                        formData.append('payment_proof', result.value.paymentProofFile);
                    }
                    
                    $.ajax({
                        url: './includes/ajaxFile/settlement_handler.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: __('success'), 
                                    text: response.message || __('settlement_approved'), 
                                    icon: 'success',
                                    confirmButtonText: __('ok'),
                                    confirmButtonColor: APP_COLORS.primary,
                                    allowOutsideClick: false
                                })
                                    .then(() => location.reload());
                            } else {
                                Swal.fire({
                                    title: __('error'),
                                    text: response.message || __('failed_approve_settlement'),
                                    icon: 'error',
                                    allowOutsideClick: false,
                                    confirmButtonText: __('ok'),
                                    confirmButtonColor: APP_COLORS.danger_dark
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Approval error:', xhr.responseText);
                            Swal.fire({
                                title: __('error'),
                                text: __('failed_approve_settlement') + ': ' + error,
                                icon: 'error',
                                allowOutsideClick: false,
                                confirmButtonText: __('ok'),
                                confirmButtonColor: APP_COLORS.danger_dark
                            });
                        }
                    });
                }
            });
        },
        error: function() {
            Swal.fire({ 
                title: __('error'), 
                text: __('failed_check_approval_status'), 
                icon: 'error'});
        }
    });
}

function rejectSettlement(settlementId, settlementInvNo) {
    Swal.fire({
        title: __('reject_settlement'),
        html: `<textarea id="rejectionReason" class="form-control" placeholder="` + __('provide_rejection_reason') + `" rows="4"></textarea>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: __('reject'),
        confirmButtonColor: APP_COLORS.danger_dark,
        cancelButtonText: __('cancel'),
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            const reason = document.getElementById('rejectionReason').value;
            if (!reason.trim()) {
                Swal.fire(__('error'), __('provide_rejection_reason'), 'error');
                return;
            }
            
            // Show loading indicator
            Swal.fire({
                title: __('processing'),
                text: __('rejecting_settlement'),
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: './includes/ajaxFile/settlement_handler.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'reject_settlement',
                    settlement_id: settlementId,
                    settlement_inv_no: settlementInvNo,
                    rejection_reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire(__('success'), response.message || __('settlement_rejected'), 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire(__('error'), response.message || __('failed_reject_settlement'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Rejection error:', xhr.responseText);
                    Swal.fire(__('error'), __('failed_reject_settlement') + ': ' + error, 'error');
                }
            });
        }
    });
}

function processSettlementPayment(settlementId, settlementInvNo) {
    Swal.fire({
        title: __('clear_settlement'),
        html: `
            <div style="text-align: left;">
                <p>` + __('select_payment_method') + `</p>
                <div style="margin: 15px 0;">
                    <label style="display: block; margin: 8px 0;">
                        <input type="radio" name="paymentMethod" value="bank_transfer" checked> ` + __('bank_transfer') + `
                    </label>
                    <label style="display: block; margin: 8px 0;">
                        <input type="radio" name="paymentMethod" value="check"> ` + __('check') + `
                    </label>
                    <label style="display: block; margin: 8px 0;">
                        <input type="radio" name="paymentMethod" value="cash"> ` + __('cash') + `
                    </label>
                </div>
                <div style="margin-top: 15px;">
                    <label>` + __('payment_reference') + ` (` + __('optional') + `):</label>
                    <input type="text" id="paymentReference" class="form-control" placeholder="` + __('eg_transaction_id') + `">
                </div>
            </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: __('clear_settlement'),
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonText: __('cancel'),
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            const method = document.querySelector('input[name="paymentMethod"]:checked').value;
            const reference = document.getElementById('paymentReference').value;
            
            // Show loading
            Swal.fire({
                title: __('processing'),
                text: __('clearing_settlement'),
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: './includes/ajaxFile/settlement_handler.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'process_payment',
                    settlement_inv_no: settlementInvNo,
                    payment_method: method,
                    payment_reference: reference
                },
                success: function(response) {
                    if (response.success || response.status === 'success') {
                        Swal.fire(__('success'), response.message || __('settlement_cleared'), 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire(__('error'), response.message || __('failed_clear_settlement'), 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Payment error:', xhr.responseText);
                    Swal.fire(__('error'), __('failed_clear_settlement') + ': ' + error, 'error');
                }
            });
        }
    });
}

/**
 * HTML entity encoder/decoder for JavaScript
 * Used in settlement functions to safely display data
 */
function htmlspecialcharsJs(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function (match) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[match];
    });
}


// =================================================================
// Copy to Clipboard Function
// =================================================================
window.copyToClipboard = function(text, iconElement) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.top = 0;
    textArea.style.left = 0;
    textArea.style.opacity = 0;
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        
        // Visual feedback - change icon temporarily
        const $icon = $(iconElement);
        const originalClass = $icon.attr('class');
        $icon.removeClass('mdi-content-copy').addClass('mdi-check').css('color', APP_COLORS.success);
        
        // Show toast notification
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
        });
        Toast.fire({
            icon: 'success',
            title: __('copied_to_clipboard', 'Copied to clipboard!')
        });
        
        // Restore original icon after 2 seconds
        setTimeout(function() {
            $icon.attr('class', originalClass).css('color', APP_COLORS.primary);
        }, 2000);
        
    } catch (err) {
        console.error('Failed to copy:', err);
        Swal.fire({
            icon: 'error',
            title: __('copy_failed', 'Copy failed'),
            text: __('please_copy_manually', 'Please copy manually'),
            timer: 2000
        });
    }
    
    document.body.removeChild(textArea);
};


// Global state to track previous modal (for restoring attachments modal after file viewer closes)
if (!window.modalState) {
    window.modalState = {
        previousAttachments: null,
        previousTitle: null,
        isRestoring: false  // Prevent recursion during modal restoration
    };
}

/**
 * Show a SweetAlert2 modal with attachment thumbnails (including PDF previews).
 * @param {Array} attachments - Array of file URLs.
 * @param {string} [title] - Optional modal title.
 */
function showAttachmentsModal(attachments, title) {
    if (!Array.isArray(attachments) || attachments.length === 0) {
        Swal.fire({
            icon: 'info',
            title: __('no_attachments') || 'No Attachments',
            text: __('no_attachments_found') || 'No attachments found for this record.',
            allowOutsideClick: false,
            confirmButtonText: __('ok'),
        });
        return;
    }
    
    // Save current modal state so we can restore it when file viewer closes
    // Only save if not currently restoring (to prevent infinite loops)
    if (!window.modalState.isRestoring) {
        window.modalState.previousAttachments = attachments;
        window.modalState.previousTitle = title;
    }
    
    // Load PDF.js library if not already loaded
    if (typeof pdfjsLib === 'undefined') {
        const pdfScript = document.createElement('script');
        pdfScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
        pdfScript.onload = function() {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            renderAttachmentsModal(attachments, title);
        };
        document.head.appendChild(pdfScript);
    } else {
        renderAttachmentsModal(attachments, title);
    }
}

/**
 * Helper function to render the attachments modal with grid and detail view
 */
function renderAttachmentsModal(attachments, title) {
    let currentIndex = -1; // -1 means showing grid, >= 0 means viewing specific attachment
    
    function renderGridView() {
        let html = '<div style="display:flex;flex-wrap:wrap;gap:16px;justify-content:flex-start;">';
        attachments.forEach(function(att, idx) {
            let ext = att.split('.').pop().toLowerCase();
            let isPdf = ext === 'pdf';
            let isImg = ['jpg','jpeg','png','gif','bmp','webp'].includes(ext);
            let safeUrl = encodeURI(att).replace(/"/g, '&quot;');
            
            let thumb;
            if (isPdf) {
                thumb = `<canvas id="pdf-canvas-${idx}" class="attachment-thumb" data-idx="${idx}" data-url="${safeUrl}" style="width:110px;height:110px;border-radius:8px;border:1px solid #ccc;cursor:pointer;box-shadow:0 2px 8px #0001;transition:transform 0.2s;background:#f9f9f9;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'"></canvas>`;
            } else if (isImg) {
                thumb = `<img src="${att}" alt="Attachment ${idx+1}" class="attachment-thumb" data-idx="${idx}" data-url="${safeUrl}" style="width:110px;height:110px;object-fit:cover;border-radius:8px;border:1px solid #ccc;cursor:pointer;box-shadow:0 2px 8px #0001;transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">`;
            } else {
                thumb = `<div class="attachment-thumb" data-idx="${idx}" data-url="${safeUrl}" style="width:110px;height:110px;display:flex;align-items:center;justify-content:center;background:#f5f5f5;border-radius:8px;border:1px solid #ccc;cursor:pointer;box-shadow:0 2px 8px #0001;font-size:2.5em;color:#007bff;transition:transform 0.2s;user-select:none;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'"><i class="fa fa-file-alt"></i></div>`;
            }
            html += `<div style="text-align:center;">${thumb}<div style="font-size:0.9em;margin-top:4px;">${__('document')} ${idx+1}</div></div>`;
        });
        html += '</div>';
        
        Swal.update({ html: html });
        
        // Render PDF thumbnails
        attachments.forEach(function(att, idx) {
            let ext = att.split('.').pop().toLowerCase();
            if (ext === 'pdf') {
                renderPdfThumbnail(att, `pdf-canvas-${idx}`);
            }
        });
        
        // Attach click handler to all attachment thumbnails
        document.querySelectorAll('.attachment-thumb').forEach(function(el) {
            el.addEventListener('click', function() {
                let idx = parseInt(this.getAttribute('data-idx'));
                if (!isNaN(idx)) {
                    renderDetailView(idx);
                }
            });
        });
    }
    
    function renderDetailView(index) {
        currentIndex = index;
        let att = attachments[index];
        let ext = att.split('.').pop().toLowerCase();
        let isPdf = ext === 'pdf';
        let isImg = ['jpg','jpeg','png','gif','bmp','webp'].includes(ext);
        
        let preview;
        if (isPdf) {
            preview = `<div style="width:100%;max-height:500px;display:flex;justify-content:center;border:1px solid #ddd;border-radius:8px;background:#f9f9f9;padding:10px;">
                <canvas id="att-pdf-canvas" style="max-width:100%;max-height:100%;border-radius:4px;"></canvas>
            </div>`;
        } else if (isImg) {
            preview = `<div style="width:100%;display:flex;justify-content:center;border:1px solid #ddd;border-radius:8px;background:#f9f9f9;padding:10px;">
                <img src="${att}" alt="Attachment ${index+1}" style="max-width:100%;max-height:500px;border-radius:4px;">
            </div>`;
        } else {
            preview = `<div style="width:100%;height:300px;display:flex;align-items:center;justify-content:center;background:#f5f5f5;border-radius:8px;border:1px solid #ccc;font-size:3em;color:#007bff;user-select:none;">
                <i class="fa fa-file-alt"></i>
            </div>`;
        }
        
        let html = `
            <div style="display:flex;flex-direction:column;gap:15px;align-items:center;">
                <div style="width:100%;">
                    ${preview}
                </div>
                <div style="width:100%;text-align:center;font-weight:bold;color:#666;">
                    ${__('document')} ${index+1} ${__('of')} ${attachments.length}
                </div>
                <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                    <button type="button" id="att-back-btn" class="btn btn-sm btn-secondary" style="padding:8px 16px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#6c757d;color:white;border:none;">
                        <i class="fa fa-arrow-left"></i> Back to Grid
                    </button>
                    <button type="button" id="att-prev-btn" class="btn btn-sm btn-secondary" style="padding:8px 16px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#6c757d;color:white;border:none;">
                        <i class="fa fa-chevron-left"></i> Previous
                    </button>
                    <button type="button" id="att-next-btn" class="btn btn-sm btn-secondary" style="padding:8px 16px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#6c757d;color:white;border:none;">
                        Next <i class="fa fa-chevron-right"></i>
                    </button>
                    <button type="button" id="att-open-fullscreen-btn" class="btn btn-sm btn-info" style="padding:8px 16px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#17a2b8;color:white;border:none;">
                        <i class="fa fa-expand"></i> Fullscreen
                    </button>
                </div>
            </div>
        `;
        
        Swal.update({ html: html });
        
        // Re-attach event listeners
        let backBtn = document.getElementById('att-back-btn');
        let prevBtn = document.getElementById('att-prev-btn');
        let nextBtn = document.getElementById('att-next-btn');
        let fullscreenBtn = document.getElementById('att-open-fullscreen-btn');
        
        // Update button states
        prevBtn.disabled = index === 0;
        nextBtn.disabled = index === attachments.length - 1;
        
        // Back button - return to grid
        backBtn.addEventListener('click', function(e) {
            e.preventDefault();
            currentIndex = -1;
            renderGridView();
        });
        
        // Previous button
        prevBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (index > 0) {
                renderDetailView(index - 1);
            }
        });
        
        // Next button
        nextBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (index < attachments.length - 1) {
                renderDetailView(index + 1);
            }
        });
        
        // Fullscreen button (zoom popup)
        fullscreenBtn.addEventListener('click', function(e) {
            e.preventDefault();
            viewFileInPopup(att, 'Attachment ' + (index + 1));
        });
        
        // Render PDF if applicable
        if (isPdf) {
            renderPdfThumbnail(att, 'att-pdf-canvas');
        }
    }
    
    Swal.fire({
        title: title || __('attachments') || 'Attachments',
        html: '<div style="text-align:center;">Loading...</div>',
        width: 'auto',
        maxWidth: '90vw',
        showCloseButton: true,
        showConfirmButton: false,
        allowOutsideClick: false,
        didOpen: function() {
            // Show grid view first
            renderGridView();
        }
    });
}

/**
 * View file (image or PDF) in a popup modal on the same page
 * Supports images (JPG, PNG, GIF, etc.) and PDF files
 * @param {string} fileUrl - URL of the file to display
 * @param {string} [fileName] - Optional filename for display
 */
function viewFileInPopup(fileUrl, fileName) {
    if (!fileUrl) {
        Swal.fire({
            icon: 'error',
            title: __('error'),
            text: __('file_not_exist_in_local_storage') || 'File not exist in local storage.',
            confirmButtonText: __('ok'),
        });
        return;
    }

    let ext = fileUrl.split('.').pop().toLowerCase();
    let isPdf = ext === 'pdf';
    let isImg = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext);

    if (isImg) {
        // Display image in popup with zoom controls and rotation
        let imageName = fileName || 'Image';
        let zoomKey = 'img_' + Date.now();
        let zoomState = {
            scale: 1,
            rotation: 0  // Track rotation in degrees (0, 90, 180, 270)
        };
        window.imageZoomState = window.imageZoomState || {};
        window.imageZoomState[zoomKey] = zoomState;

        let html = `
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%;gap:10px;">
                <div style="display:flex;gap:10px;justify-content:center;align-items:center;flex-wrap:wrap;margin-bottom:10px;">
                    <button type="button" class="btn btn-sm btn-info" id="zoom-out-${zoomKey}" style="padding:6px 12px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#17a2b8;color:white;border:none;">
                        <i class="fa fa-search-minus"></i> Zoom Out
                    </button>
                    <span style="font-weight:bold;min-width:80px;text-align:center;font-size:14px;">
                        <span id="zoom-level-${zoomKey}">100</span>%
                    </span>
                    <button type="button" class="btn btn-sm btn-info" id="zoom-in-${zoomKey}" style="padding:6px 12px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#17a2b8;color:white;border:none;">
                        <i class="fa fa-search-plus"></i> Zoom In
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" id="zoom-reset-${zoomKey}" style="padding:6px 12px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#ffc107;color:black;border:none;">
                        <i class="fa fa-undo"></i> Reset
                    </button>
                    <button type="button" class="btn btn-sm btn-success" id="rotate-ltr-${zoomKey}" style="padding:6px 12px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#28a745;color:white;border:none;">
                        <i class="fa fa-rotate-left"></i> Rotate LTR
                    </button>
                    <button type="button" class="btn btn-sm btn-success" id="rotate-rtl-${zoomKey}" style="padding:6px 12px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#28a745;color:white;border:none;">
                        <i class="fa fa-rotate-right"></i> Rotate RTL
                    </button>
                </div>
                <div id="img-container-${zoomKey}" style="width:100%;max-height:60vh;overflow:auto;display:flex;justify-content:center;align-items:flex-start;border:1px solid #ddd;border-radius:8px;background:#f9f9f9;padding:10px;cursor:grab;">
                    <img id="zoom-img-${zoomKey}" src="${fileUrl}" alt="${imageName}" style="border-radius:8px;transition:transform 0.2s ease;transform:scale(1);user-select:none;max-width:100%;max-height:100%;object-fit:contain;">
                </div>
            </div>
        `;

        Swal.fire({
            title: imageName,
            html: html,
            width: 'auto',
            maxWidth: '90vw',
            showCloseButton: true,
            showConfirmButton: false,
            allowOutsideClick: true,
            didOpen: function() {
                let img = document.getElementById('zoom-img-' + zoomKey);
                let initialWidth = 400; // Default minimum width
                let maxViewportWidth = window.innerWidth * 0.9;
                
                // Calculate dynamic width based on image dimensions
                img.onload = function() {
                    let naturalWidth = img.naturalWidth || img.width;
                    let naturalHeight = img.naturalHeight || img.height;
                    let maxViewportHeight = window.innerHeight * 0.8;
                    
                    // Calculate aspect ratio
                    let aspectRatio = naturalWidth / naturalHeight;
                    let calculatedWidth = naturalWidth;
                    
                    // If image exceeds viewport, scale it down
                    if (naturalWidth > maxViewportWidth) {
                        calculatedWidth = maxViewportWidth;
                    }
                    if (naturalHeight > maxViewportHeight) {
                        calculatedWidth = maxViewportHeight * aspectRatio;
                    }
                    
                    // Set initial width (but not less than 400px)
                    initialWidth = Math.max(calculatedWidth, 400);
                    Swal.update({
                        width: initialWidth
                    });
                    // Update zoom after width is set
                    setTimeout(function() {
                        updateZoom();
                    }, 50);
                };
                // Trigger load event if already cached
                if (img.complete) {
                    img.onload();
                }
                let container = document.getElementById('img-container-' + zoomKey);
                let zoomOutBtn = document.getElementById('zoom-out-' + zoomKey);
                let zoomInBtn = document.getElementById('zoom-in-' + zoomKey);
                let zoomResetBtn = document.getElementById('zoom-reset-' + zoomKey);
                let zoomLevelSpan = document.getElementById('zoom-level-' + zoomKey);

                let isDragging = false;
                let startX, startY, scrollLeft, scrollTop;

                function updateZoom() {
                    // Always refetch the image element to ensure we have the current reference
                    let currentImg = document.getElementById('zoom-img-' + zoomKey);
                    
                    if (currentImg) {
                        currentImg.style.transform = 'scale(' + zoomState.scale + ') rotate(' + zoomState.rotation + 'deg)';
                    }
                    
                    // Refetch and update zoom level span
                    let currentZoomLevelSpan = document.getElementById('zoom-level-' + zoomKey);
                    if (currentZoomLevelSpan) {
                        currentZoomLevelSpan.textContent = Math.round(zoomState.scale * 100);
                    }
                    
                    // Update modal width based on zoom using CSS on the Swal container
                    setTimeout(function() {
                        let newWidth = Math.round(initialWidth * zoomState.scale);
                        let maxWidth = window.innerWidth * 0.9;
                        newWidth = Math.min(newWidth, maxWidth);
                        newWidth = Math.max(newWidth, 300);
                        
                        let swalPopup = document.querySelector('.swal2-container .swal2-popup');
                        if (swalPopup) {
                            swalPopup.style.width = newWidth + 'px';
                        }
                    }, 0);
                    
                    // Refetch and update buttons
                    let currentZoomOutBtn = document.getElementById('zoom-out-' + zoomKey);
                    let currentZoomInBtn = document.getElementById('zoom-in-' + zoomKey);
                    
                    if (currentZoomOutBtn) {
                        currentZoomOutBtn.disabled = zoomState.scale <= 0.5;
                    }
                    if (currentZoomInBtn) {
                        currentZoomInBtn.disabled = zoomState.scale >= 3;
                    }
                    
                    // Change cursor based on zoom level
                    if (container) {
                        if (zoomState.scale > 1) {
                            container.style.cursor = 'grab';
                        } else {
                            container.style.cursor = 'default';
                        }
                    }
                }

                // Drag functionality for zoomed images
                container.addEventListener('mousedown', function(e) {
                    if (zoomState.scale <= 1) return;  // Only allow drag when zoomed in
                    isDragging = true;
                    startX = e.pageX - container.offsetLeft;
                    startY = e.pageY - container.offsetTop;
                    scrollLeft = container.scrollLeft;
                    scrollTop = container.scrollTop;
                    container.style.cursor = 'grabbing';
                });

                document.addEventListener('mousemove', function(e) {
                    if (!isDragging) return;
                    e.preventDefault();
                    let x = e.pageX - container.offsetLeft;
                    let y = e.pageY - container.offsetTop;
                    let walkX = (x - startX);
                    let walkY = (y - startY);
                    container.scrollLeft = scrollLeft - walkX;
                    container.scrollTop = scrollTop - walkY;
                });

                document.addEventListener('mouseup', function() {
                    isDragging = false;
                    if (zoomState.scale > 1) {
                        container.style.cursor = 'grab';
                    } else {
                        container.style.cursor = 'default';
                    }
                });

                // Use event delegation - attach to document for click events
                document.addEventListener('click', function(e) {
                    // Check if click target is one of the zoom buttons
                    if (e.target && e.target.id === 'zoom-in-' + zoomKey) {
                        if (zoomState.scale < 3) {
                            zoomState.scale = Math.min(zoomState.scale + 0.2, 3);
                            updateZoom();
                        }
                        return false;
                    }
                    if (e.target && e.target.id === 'zoom-out-' + zoomKey) {
                        if (zoomState.scale > 0.5) {
                            zoomState.scale = Math.max(zoomState.scale - 0.2, 0.5);
                            updateZoom();
                        }
                        return false;
                    }
                    if (e.target && e.target.id === 'zoom-reset-' + zoomKey) {
                        zoomState.scale = 1;
                        zoomState.rotation = 0;
                        updateZoom();
                        return false;
                    }
                    if (e.target && e.target.id === 'rotate-ltr-' + zoomKey) {
                        zoomState.rotation = (zoomState.rotation - 90) % 360;
                        updateZoom();
                        return false;
                    }
                    if (e.target && e.target.id === 'rotate-rtl-' + zoomKey) {
                        zoomState.rotation = (zoomState.rotation + 90) % 360;
                        updateZoom();
                        return false;
                    }
                });

                updateZoom();
            },
            didDestroy: function() {
                // Restore attachments modal if it was open before (and not already restoring)
                if (window.modalState && 
                    window.modalState.previousAttachments && 
                    window.modalState.previousAttachments.length > 0 &&
                    !window.modalState.isRestoring) {
                    window.modalState.isRestoring = true;
                    let attachmentsToRestore = window.modalState.previousAttachments;
                    let titleToRestore = window.modalState.previousTitle;
                    // Clear state before restoring to prevent recursion
                    window.modalState.previousAttachments = null;
                    window.modalState.previousTitle = null;
                    setTimeout(function() {
                        window.modalState.isRestoring = false;
                        showAttachmentsModal(attachmentsToRestore, titleToRestore);
                    }, 100);
                }
            }
        });
    } else if (isPdf) {
        // Load PDF.js if not already loaded
        if (typeof pdfjsLib === 'undefined') {
            const pdfScript = document.createElement('script');
            pdfScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
            pdfScript.onload = function() {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                displayPdfPopup(fileUrl, fileName);
            };
            document.head.appendChild(pdfScript);
        } else {
            displayPdfPopup(fileUrl, fileName);
        }
    } else {
        // For other file types, show a message
        Swal.fire({
            icon: 'info',
            title: __('file_type_not_supported'),
            text: 'This file type cannot be previewed in the popup. Please download to view.',
            showCancelButton: true,
            confirmButtonText: 'Download File',
            cancelButtonText: 'Cancel',
            confirmButtonColor: APP_COLORS.primary,
            cancelButtonColor: APP_COLORS.danger_dark,
            didDestroy: function() {
                // Restore attachments modal if it was open before (and not already restoring)
                if (window.modalState && 
                    window.modalState.previousAttachments && 
                    window.modalState.previousAttachments.length > 0 &&
                    !window.modalState.isRestoring) {
                    window.modalState.isRestoring = true;
                    let attachmentsToRestore = window.modalState.previousAttachments;
                    let titleToRestore = window.modalState.previousTitle;
                    // Clear state before restoring to prevent recursion
                    window.modalState.previousAttachments = null;
                    window.modalState.previousTitle = null;
                    setTimeout(function() {
                        window.modalState.isRestoring = false;
                        showAttachmentsModal(attachmentsToRestore, titleToRestore);
                    }, 100);
                }
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                window.open(fileUrl, '_blank');
            }
        });
    }
}

/**
 * Display PDF in a popup modal with page navigation and zoom controls
 * @param {string} pdfUrl - URL of the PDF file
 * @param {string} [fileName] - Optional filename for display
 */
function displayPdfPopup(pdfUrl, fileName) {
    // Store PDF state in window object to manage across multiple pages
    if (!window.pdfViewerState) {
        window.pdfViewerState = {};
    }

    let pdfName = fileName || 'PDF Document';
    let viewerKey = 'pdf_' + Date.now();
    let state = {
        url: pdfUrl,
        totalPages: 0,
        currentPage: 1,
        pdf: null,
        scale: 1.5  // Default zoom scale
    };
    window.pdfViewerState[viewerKey] = state;

    // Create HTML for PDF viewer with navigation and zoom controls
    let html = `
        <div style="display:flex;flex-direction:column;align-items:center;width:100%;gap:10px;">
            <div style="width:100%;text-align:center;margin-bottom:5px;display:flex;justify-content:center;align-items:center;gap:15px;flex-wrap:wrap;">
                <div>
                    <span style="font-weight:bold;font-size:14px;">Page <span id="current-page-${viewerKey}">1</span> of <span id="total-pages-${viewerKey}">...</span></span>
                </div>
                <div>
                    <span style="font-weight:bold;font-size:14px;">Zoom: <span id="zoom-level-${viewerKey}">150</span>%</span>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:10px;">
                <button type="button" class="btn btn-sm btn-secondary" id="prev-btn-${viewerKey}" style="padding:6px 12px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#6c757d;color:white;border:none;">
                    <i class="fa fa-chevron-left"></i> Previous
                </button>
                <input type="number" id="page-input-${viewerKey}" value="1" min="1" style="width:50px;padding:6px;text-align:center;border:1px solid #ddd;border-radius:4px;">
                <button type="button" class="btn btn-sm btn-secondary" id="next-btn-${viewerKey}" style="padding:6px 12px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#6c757d;color:white;border:none;">
                    Next <i class="fa fa-chevron-right"></i>
                </button>
                <button type="button" class="btn btn-sm btn-info" id="zoom-out-${viewerKey}" style="padding:6px 12px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#17a2b8;color:white;border:none;">
                    <i class="fa fa-search-minus"></i> Zoom Out
                </button>
                <button type="button" class="btn btn-sm btn-info" id="zoom-in-${viewerKey}" style="padding:6px 12px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#17a2b8;color:white;border:none;">
                    <i class="fa fa-search-plus"></i> Zoom In
                </button>
                <button type="button" class="btn btn-sm btn-warning" id="zoom-reset-${viewerKey}" style="padding:6px 12px;font-size:13px;cursor:pointer;border-radius:4px;background-color:#ffc107;color:black;border:none;">
                    <i class="fa fa-undo"></i> Reset
                </button>
            </div>
            <div style="width:100%;display:flex;justify-content:center;max-height:60vh;overflow-y:auto;overflow-x:auto;border:1px solid #ddd;border-radius:4px;background:#f9f9f9;padding:10px;">
                <canvas id="pdf-viewer-canvas-${viewerKey}" style="display:block;margin:auto;"></canvas>
            </div>
        </div>
    `;

    Swal.fire({
        title: pdfName,
        html: html,
        width: 'auto',
        maxWidth: '90vw',
        showCloseButton: true,
        showConfirmButton: false,
        allowOutsideClick: true,
        didOpen: function() {
            // Calculate dynamic width based on viewport
            let maxViewportWidth = window.innerWidth * 0.9;
            let initialWidth = Math.min(Math.max(window.innerWidth * 0.85, 500), maxViewportWidth);
            Swal.update({ width: initialWidth });
            
            // Initialize PDF rendering
            pdfjsLib.getDocument({
                url: pdfUrl,
                withCredentials: true
            }).promise.then(function(pdf) {
                state.pdf = pdf;
                state.totalPages = pdf.numPages;
                document.getElementById('total-pages-' + viewerKey).textContent = state.totalPages;
                renderPdfPage(state, viewerKey);

                let prevBtn = document.getElementById('prev-btn-' + viewerKey);
                let nextBtn = document.getElementById('next-btn-' + viewerKey);
                let pageInput = document.getElementById('page-input-' + viewerKey);
                let zoomInBtn = document.getElementById('zoom-in-' + viewerKey);
                let zoomOutBtn = document.getElementById('zoom-out-' + viewerKey);
                let zoomResetBtn = document.getElementById('zoom-reset-' + viewerKey);
                let zoomLevelSpan = document.getElementById('zoom-level-' + viewerKey);

                // Previous button
                prevBtn.addEventListener('click', function() {
                    if (state.currentPage > 1) {
                        state.currentPage--;
                        pageInput.value = state.currentPage;
                        renderPdfPage(state, viewerKey);
                    }
                });

                // Next button
                nextBtn.addEventListener('click', function() {
                    if (state.currentPage < state.totalPages) {
                        state.currentPage++;
                        pageInput.value = state.currentPage;
                        renderPdfPage(state, viewerKey);
                    }
                });

                // Page input
                pageInput.addEventListener('change', function() {
                    let pageNum = parseInt(this.value);
                    if (pageNum >= 1 && pageNum <= state.totalPages) {
                        state.currentPage = pageNum;
                        renderPdfPage(state, viewerKey);
                    } else {
                        this.value = state.currentPage;
                    }
                });

                // Zoom controls
                function updateZoomButton() {
                    zoomOutBtn.disabled = state.scale <= 0.8;
                    zoomInBtn.disabled = state.scale >= 3;
                }

                zoomInBtn.addEventListener('click', function() {
                    if (state.scale < 3) {
                        state.scale = Math.min(state.scale + 0.2, 3);
                        state.scale = parseFloat(state.scale.toFixed(2));  // Prevent floating point errors
                        zoomLevelSpan.textContent = Math.round(state.scale * 100);
                        updateZoomButton();
                        renderPdfPage(state, viewerKey);
                    }
                });

                zoomOutBtn.addEventListener('click', function() {
                    if (state.scale > 0.8) {
                        state.scale = Math.max(state.scale - 0.2, 0.8);
                        state.scale = parseFloat(state.scale.toFixed(2));  // Prevent floating point errors
                        zoomLevelSpan.textContent = Math.round(state.scale * 100);
                        updateZoomButton();
                        renderPdfPage(state, viewerKey);
                    }
                });

                zoomResetBtn.addEventListener('click', function() {
                    state.scale = 1.5;
                    zoomLevelSpan.textContent = '150';
                    updateZoomButton();
                    renderPdfPage(state, viewerKey);
                });

                updateZoomButton();
            }).catch(function(error) {
                document.getElementById('pdf-viewer-canvas-' + viewerKey).parentElement.innerHTML = 
                    '<div style="padding:20px;color:#d32f2f;text-align:center;"><i class="fa fa-exclamation-circle"></i> Failed to load PDF file.</div>';
            });
        },
        didDestroy: function() {
            // Restore attachments modal if it was open before (and not already restoring)
            if (window.modalState && 
                window.modalState.previousAttachments && 
                window.modalState.previousAttachments.length > 0 &&
                !window.modalState.isRestoring) {
                window.modalState.isRestoring = true;
                let attachmentsToRestore = window.modalState.previousAttachments;
                let titleToRestore = window.modalState.previousTitle;
                // Clear state before restoring to prevent recursion
                window.modalState.previousAttachments = null;
                window.modalState.previousTitle = null;
                setTimeout(function() {
                    window.modalState.isRestoring = false;
                    showAttachmentsModal(attachmentsToRestore, titleToRestore);
                }, 100);
            }
        }
    });
}

/**
 * Render a specific page of the PDF
 * @param {object} state - PDF viewer state object
 * @param {string} viewerKey - Unique key for this PDF viewer instance
 */
function renderPdfPage(state, viewerKey) {
    if (!state.pdf) {
        return;
    }

    state.pdf.getPage(state.currentPage).then(function(page) {
        let canvas = document.getElementById('pdf-viewer-canvas-' + viewerKey);
        if (!canvas) {
            return;
        }

        // Ensure scale is a valid number
        let scale = parseFloat(state.scale) || 1.5;
        if (scale < 0.8) scale = 0.8;
        if (scale > 3) scale = 3;
        state.scale = scale;

        try {
            let viewport = page.getViewport({ scale: scale });

            // Properly clear the canvas
            canvas.width = viewport.width;
            canvas.height = viewport.height;

            let ctx = canvas.getContext('2d');
            if (!ctx) {
                return;
            }

            // White background
            ctx.fillStyle = APP_COLORS.white;
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            let renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };

            // Render the page
            page.render(renderContext).promise.then(function() {
                // Page rendered successfully
                let pageSpan = document.getElementById('current-page-' + viewerKey);
                if (pageSpan) {
                    pageSpan.textContent = state.currentPage;
                }
            }).catch(function(error) {
            });
        } catch (error) {
        }
    }).catch(function(error) {
    });
}

/**
 * Render PDF thumbnail preview using PDF.js
 */
function renderPdfThumbnail(pdfUrl, canvasId) {
    let canvas = document.getElementById(canvasId);
    if (!canvas || !pdfjsLib) return;
    
    // Configure PDF.js to handle CORS for local files
    pdfjsLib.getDocument({
        url: pdfUrl,
        withCredentials: true
    }).promise.then(function(pdf) {
        return pdf.getPage(1).then(function(page) {
            let viewport = page.getViewport({ scale: 1 });
            
            // Scale to fit within 90x90
            let scale = 90 / Math.max(viewport.width, viewport.height);
            viewport = page.getViewport({ scale: scale });
            
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.style.width = viewport.width + 'px';
            canvas.style.height = viewport.height + 'px';
            
            let ctx = canvas.getContext('2d');
            
            // Fill white background
            ctx.fillStyle = APP_COLORS.white;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            let renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            
            return page.render(renderContext).promise;
        });
    }).catch(function(error) {
        console.warn('PDF rendering failed for ' + pdfUrl, error);
        // If PDF preview fails, show fallback
        if (canvas) {
            canvas.width = 90;
            canvas.height = 90;
            let ctx = canvas.getContext('2d');
            ctx.fillStyle = '#e8e8e8';
            ctx.fillRect(0, 0, 90, 90);
            ctx.fillStyle = '#666';
            ctx.font = 'bold 16px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('PDF', 45, 40);
            ctx.font = '12px Arial';
            ctx.fillText('Preview', 45, 55);
        }
    });
}

/**
 * Render Allowed Employees as a styled card with badges
 * Shows only actual assigned employees - no default "All Employees" message
 */
function renderAllowedEmployeesCard(employeeText, title = 'Allowed Employees') {
    // Return empty if no employees are assigned
    if (!employeeText || employeeText.trim() === '' || employeeText.trim() === null) {
        return '';
    }
    
    // Split by comma and create badges for each employee
    const employees = employeeText.split(',').map(e => e.trim()).filter(e => e !== '');
    
    // Only render if there are actual employees
    if (employees.length === 0) {
        return '';
    }
    
    // Create badges for each employee
    const badges = employees.map(emp => {
        return '<span class="employee-badge">' + emp + '</span>';
    }).join('');
    
    return '<div class="allowed-employees-card-content">' + badges + '</div>';
}

function resetFilters(perpage) {
    // Reset all filter controls to default values
    document.getElementById('statusFilter').value = 'my_pending';
    document.getElementById('searchFilter').value = '';
    // Redirect to base URL without any filters
    const baseUrl = window.location.href.split('?')[0];
    window.location.href = `${baseUrl}?status=my_pending&limit=${perpage}&page=1`;
}

/**
 * Generate HTML for Manual Vacation History Form
 * Used in addManualVacationHistory() modal
 * @param {number} country - Employee country code (191 = excluded from Fly option)
 */
function manualVacationHistory_HTML(country) {
    const vacationTypes = [
        { value: 'Local Vacation', label: __('local_vacation') },
        { value: 'Encashed', label: __('encashed') }
    ];
    
    // Add Fly option only if country is not 191
    if (country !== 191) {
        vacationTypes.unshift({ value: 'Fly', label: __('fly') });
    }

    const flyTypes = [
        { value: 'annual', label: __('annual') },
        { value: 'emergency', label: __('emergency') }
    ];

    let vacationTypeOptions = '<option value="">' + __('select') + '</option>';
    vacationTypes.forEach(vt => {
        vacationTypeOptions += '<option value="' + vt.value + '">' + vt.label + '</option>';
    });

    let flyTypeOptions = '<option value="">' + __('select') + '</option>';
    flyTypes.forEach(ft => {
        flyTypeOptions += '<option value="' + ft.value + '">' + ft.label + '</option>';
    });

    const html = `
    <form id="manualVacationHistoryForm">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="mvh_vac_type">${__('vacation_type')}</label>
                <select class="form-control" id="mvh_vac_type" name="vac_type" required>
                    ${vacationTypeOptions}
                </select>
            </div>
            <div class="form-group col-md-6" id="mvh_fly_type_group" style="display:none;">
                <label for="mvh_fly_type">${__('fly_type')}</label>
                <select class="form-control" id="mvh_fly_type" name="fly_type">
                    ${flyTypeOptions}
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="mvh_start_date">${__('start_date')}</label>
                <input type="text" class="form-control" id="mvh_start_date" name="start_date" placeholder="YYYY-MM-DD" required>
            </div>
            <div class="form-group col-md-6">
                <label for="mvh_return_date">${__('return_date')}</label>
                <input type="text" class="form-control" id="mvh_return_date" name="return_date" placeholder="YYYY-MM-DD" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="mvh_days">${__('days')}</label>
                <input type="number" class="form-control" id="mvh_days" name="days" step="0.5" min="0" readonly>
            </div>
            <div class="form-group col-md-6" id="mvh_permit_no_group" style="display:none;">
                <label for="mvh_permit_no">${__('permit_no')}</label>
                <input type="text" class="form-control" id="mvh_permit_no" name="permit_no">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="mvh_remarks">${__('remarks')}</label>
                <textarea class="form-control" id="mvh_remarks" name="remarks" rows="3"></textarea>
            </div>
        </div>
    </form>
    `;
    return html;
}

/**
 * Add Manual Vacation History - Allow admin to manually record vacation entries
 * Used for executives/management who submit vacation via email
 * @param {number} empid - Employee ID
 * @param {string} empname - Employee name
 * @param {number} country - Employee country code (191 = excluded from Fly option)
 */
function addManualVacationHistory(empid, empname, country) {
    if (!empid) {
        Swal.fire({
            title: __('error'),
            text: __('invalid_employee'),
            icon: 'error'
        });
        return;
    }

    Swal.fire({
        title: '<i class="fa fa-plus-circle"></i> ' + __('add_manual_vacation_history'),
        html: manualVacationHistory_HTML(country),
        showCancelButton: true,
        confirmButtonColor: APP_COLORS.primary,
        cancelButtonColor: APP_COLORS.danger_dark,
        confirmButtonText: '<i class="fa fa-save"></i> ' + __('save'),
        cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '600px',
        willOpen: function() {
            const swalModal = Swal.getHtmlContainer();

            // Setup event listeners for form fields
            $('#mvh_vac_type').on('change', function() {
                const vacType = $(this).val();
                if (vacType === 'Fly') {
                    $('#mvh_fly_type_group').show();
                    $('#mvh_fly_type').prop('required', true);
                    $('#mvh_permit_no_group').show();
                    $('#mvh_permit_no').prop('required', true);
                } else {
                    $('#mvh_fly_type_group').hide();
                    $('#mvh_fly_type').prop('required', false);
                    $('#mvh_permit_no_group').hide();
                    $('#mvh_permit_no').prop('required', false);
                }
            });

            // Calculate days when dates change
            $('#mvh_start_date, #mvh_return_date').on('change', function() {
                const startDate = $('#mvh_start_date').val();
                const returnDate = $('#mvh_return_date').val();
                
                if (startDate && returnDate) {
                    const start = new Date(startDate);
                    const end = new Date(returnDate);
                    
                    if (end >= start) {
                        // Calculate days (inclusive of both start and end dates)
                        const days = (end - start) / (1000 * 60 * 60 * 24) + 1;
                        $('#mvh_days').val(days);
                    } else {
                        Swal.showValidationMessage(__('return_date_must_be_after_start_date'));
                        $('#mvh_days').val('');
                    }
                }
            });

            // Setup date pickers
            setupGlobalRTLDatepicker();
            $('#mvh_start_date').datepicker({
                format: 'yyyy-mm-dd',
                todayHighlight: false,
                autoclose: true
            });
            $('#mvh_return_date').datepicker({
                format: 'yyyy-mm-dd',
                todayHighlight: false,
                autoclose: true
            });

            $('#mvh_start_date').on('changeDate', function(e) {
                $('#mvh_return_date').datepicker('setStartDate', e.date);
            });
        },
        preConfirm: function() {
            const vacType = $('#mvh_vac_type').val();
            const startDate = $('#mvh_start_date').val();
            const returnDate = $('#mvh_return_date').val();
            const days = $('#mvh_days').val();
            const flyType = $('#mvh_fly_type').val();
            const permitNo = $('#mvh_permit_no').val();
            const remarks = $('#mvh_remarks').val();

            // Validation
            if (!vacType) {
                Swal.showValidationMessage(__('select_vacation_type'));
                return false;
            }
            if (!startDate) {
                Swal.showValidationMessage(__('enter_start_date'));
                return false;
            }
            if (!returnDate) {
                Swal.showValidationMessage(__('enter_return_date'));
                return false;
            }
            if (!days || days <= 0) {
                Swal.showValidationMessage(__('invalid_number_of_days'));
                return false;
            }
            if (vacType === 'Fly' && !flyType) {
                Swal.showValidationMessage(__('select_fly_type'));
                return false;
            }

            // Submit to backend
            return $.ajax({
                url: './includes/ajaxFile/leaveHandler.php',
                type: 'POST',
                dataType: 'JSON',
                data: {
                    ajaxType: 'addManualVacationHistory',
                    emp_id: empid,
                    vac_type: vacType,
                    start_date: startDate,
                    return_date: returnDate,
                    vacdays: days,
                    fly_type: flyType || 'N/A',
                    permit_no: permitNo,
                    remarks: remarks
                },
                error: function(j, e) {
                    Swal.showValidationMessage(__('error_occurred') + ': ' + e);
                    console.error('Error:', j, e);
                }
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const response = result.value;
            if (response.type === 'success') {
                Swal.fire({
                    title: __('success'),
                    text: response.message,
                    icon: 'success',
                    confirmButtonColor: APP_COLORS.primary
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: __('error'),
                    text: response.message,
                    icon: 'error',
                    confirmButtonColor: APP_COLORS.danger_dark
                });
            }
        }
    });
}