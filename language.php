<?php
// File: language.php (Updated)
// Main page for managing translations.

/**************************************************************************************************
 * MODIFICATION SUMMARY
 *
 * 1.  **Added RTL Support**: Included a PHP block to check for the `$is_rtl` variable (set in
 * `init.php`). If true, it adds `dir="rtl"` to the `<html>` tag and includes the new
 * `style_rtl.css` stylesheet for proper right-to-left layout adjustments.
 *
 **************************************************************************************************/

	require_once __DIR__ . '/includes/db.php';
	include("./includes/session_check.php");
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."' ");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= __('language_page_title', 'Translations') ?> - <?= $site_title ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- Modal -->
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

		<!-- Plugins css -->
        <link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
		<!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <!-- Responsive datatable examples -->
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- Multi Item Selection examples -->
        <link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>

        <script src="assets/js/modernizr.min.js"></script>


        <style type="text/css">
            tr.disableLoc{
                background-color: #f1556c !important;
                color: #fff;
            }
            tr.disableLoc:hover{
                background-color: #ef3d58 !important;
            }
            .swal2-html-container{
                padding: 10px !important;
            }
        </style>

        <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    </head>
    <body class="enlarged" data-keep-enlarged="true">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">

                <div class="slimscroll-menu" id="remove-scroll">

                    <!-- LOGO -->
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span>
                                <img src="assets/images/logo.png" alt="" height="22">
                            </span>
                            <i>
                                <img src="assets/images/logo_sm.png" alt="" height="28">
                            </i>
                        </a>
                    </div>

                    <!-- User box -->
                    
                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <!-- Sidebar -->

                    <div class="clearfix"></div>

                </div>
                <!-- Sidebar -left -->

            </div>
            <!-- Left Sidebar End -->



            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->

            <div class="content-page">

                <!-- Top Bar Start -->
                <?php include("./includes/topbar.php"); ?>
                <!-- Top Bar End -->


                <!-- Start Page content -->
                <div class="content">
                    <div class="container-fluid">

                        <div class="row">
                            <div class="col-12">
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title"><?= __('add_new_translation') ?></h4>
                                    <p class="text-muted m-b-30 font-14">
                                        <?= __('add_new_translation_desc', 'Use the form below to add a new translation key and its English and Arabic versions.') ?>
                                    </p>

                                    <form id="addTranslationForm" class="form-inline">
                                        <div class="form-group">
                                            <label for="lang_key" class="sr-only"><?= __('language_key') ?></label>
                                            <input type="text" class="form-control" id="lang_key_input" name="lang_key" placeholder="<?= __('language_key') ?>" style="margin: 2px;">
                                        </div>
                                        <div class="form-group">
                                            <label for="en_translation" class="sr-only"><?= __('english_translation') ?></label>
                                            <input type="text" class="form-control" id="en_translation_input" name="en_translation" placeholder="<?= __('english_translation') ?>" style="margin: 2px;">
                                        </div>
                                        <div class="form-group">
                                            <label for="ar_translation" class="sr-only"><?= __('arabic_translation') ?></label>
                                            <input type="text" class="form-control" id="ar_translation_input" name="ar_translation" placeholder="<?= __('arabic_translation') ?>" style="margin: 2px;">
                                        </div>
                                        <button type="submit" class="btn btn-primary waves-effect waves-light" style="margin: 2px;"><?= __('add_language', 'Add') ?></button>
                                    </form>
                                </div>
                            </div>
                        </div>
											
                        <div class="row">
                            <div class="col-12">
                                <div class="card-box table-responsive">
                                    <h4 class="m-t-0 header-title"><?= __('all_translations') ?></h4>
                        <table id="language" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                            <thead>
                                <tr>
                                    <th><?= __('table_header_key', 'Language Key') ?></th>
                                    <th><?= __('table_header_english', 'English') ?></th>
                                    <th><?= __('table_header_arabic', 'Arabic') ?></th>
                                    <th width="80"><?= __('table_header_action', 'Action') ?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
						

                    </div> <!-- container -->

                </div> <!-- content -->

                <footer class="footer">
                    <?php echo $site_footer ?>
                </footer>

            </div>

            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->
        </div>
        <!-- END wrapper -->

        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>


        <!-- Modal-Effect -->
		<script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
		<script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>
        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>


		<script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

        
		<!-- Required datatable js -->
        <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <!-- Buttons examples -->
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/pdfmake.min.js"></script>
        <script src="./plugins/datatables/vfs_fonts.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>

        <!-- Key Tables -->
        <script src="./plugins/datatables/dataTables.keyTable.min.js"></script>

        <!-- Responsive examples -->
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <!-- Selection table -->
        <script src="./plugins/datatables/dataTables.select.min.js"></script>
		
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>


<script type="text/javascript">
$(document).ready(function(){

    // =================================================================
    // Auto-generate English translation from Language Key
    // =================================================================
    $('#lang_key_input').on('keyup', function() {
        let originalText = $(this).val();

        // 1. Keep original for English translation
        let englishText = originalText.replace(/_/g, ' ');
        englishText = englishText.charAt(0).toUpperCase() + englishText.slice(1);
        $('#en_translation_input').val(englishText);

        // 2. For lang_key: lowercase + replace spaces with underscores + remove special chars
        let langKey = originalText
            .toLowerCase()
            .replace(/\s+/g, '_')        // spaces → underscore
            .replace(/[.()'*\-]/g, '');   // remove ( ) ' * -

        $(this).val(langKey);
    });

    // =================================================================
    // DataTable Initialization
    // =================================================================
    var languageTable = $('#language').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "./includes/ajaxFile/ajaxLanguageTbl.php",
            type: "POST" 
        },
        columns: [
            { data: 'lang_key', name: 'lang_key' },
            { data: 'en_translation', name: 'en_translation' },
            { data: 'ar_translation', name: 'ar_translation' },
            { data: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
    });

    // =================================================================
    // Add New Translation
    // =================================================================
    $('#addTranslationForm').on('submit', function(e) {
    // Prevent the default form submission behavior
    e.preventDefault();

    $.ajax({
        url: './includes/ajaxFile/addLanguageAjax.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            // Check if the request was successful
            if (response.type === 'success' && response.lang_key) {
                // If successful, show a confirmation dialog that copies the key on confirm
                Swal.fire({
                    title: __(response.title, response.title),
                    icon: 'success',
                    // Use the html property to display the language key
                    html: `
                        <p>${__(response.message, response.message)}</p>
                        <div style="margin-top: 15px; padding: 10px; border-radius: 5px; background-color: #f0f0f0; text-align: center;">
                            <strong id="lang-key-text" style="font-family: monospace;">${response.lang_key}</strong>
                        </div>
                    `,
                    confirmButtonClass: 'btn btn-lg',
                    confirmButtonText: 'Copy & OK',
                }).then(function(result) {
                    // This block runs after the user interacts with the alert
                    if (result.isConfirmed) {
                        // Create a temporary textarea to hold the text to be copied
                        const textArea = document.createElement('textarea');
                        textArea.value = response.lang_key;

                        // Prevent scrolling to bottom of page in MS Edge
                        textArea.style.position = 'fixed';
                        textArea.style.top = 0;
                        textArea.style.left = 0;
                        textArea.style.opacity = 0;

                        document.body.appendChild(textArea);
                        textArea.focus();
                        textArea.select();

                        try {
                            // Execute the copy command
                            document.execCommand('copy');

                            // Show a temporary "toast" notification to confirm the copy
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                            Toast.fire({
                                icon: 'success',
                                title: 'Key copied to clipboard!'
                            });

                        } catch (err) {
                            console.error('Fallback: Oops, unable to copy', err);
                        }

                        document.body.removeChild(textArea);
                    }

                    // After the user closes the alert, reset the form and reload the table
                    $('#addTranslationForm')[0].reset();
                    if (typeof languageTable !== 'undefined') {
                        languageTable.ajax.reload();
                    }
                });
            } else {
                // Handle other responses (e.g., validation errors) from the server
                Swal.fire({
                    title: __(response.title, response.title),
                    text: __(response.message, response.message),
                    icon: response.type,
                    confirmButtonClass: 'btn btn-lg',
                });
            }
        },
        error: function() {
            // Handle AJAX-level errors (e.g., server not responding)
            Swal.fire(__('error', 'Error'), __('generic_error_message', 'An unexpected error occurred.'), 'error');
        }
    });
});

    // =================================================================
    // Update Translation
    // =================================================================
    $(document).on('click', '.update-translation', function (e) {
        e.preventDefault();
        var originalKey = $(this).data('key');
        var enText = $(this).data('en');
        var arText = $(this).data('ar');
        
        Swal.fire({
            title: __('update_translation_title', 'Update Translation'),
            html: `
                <form id="updateTranslationForm" class="text-left">
                    <input type="hidden" name="original_lang_key" value="${originalKey}">
                    <div class="form-group">
                        <label for="update_en_translation">${__('english_translation', 'English Translation')}</label>
                        <input type="text" id="update_en_translation" name="en_translation" class="form-control" value="${enText}">
                    </div>
                    <div class="form-group">
                        <label for="update_ar_translation">${__('arabic_translation', 'Arabic Translation')}</label>
                        <input type="text" id="update_ar_translation" name="ar_translation" class="form-control" value="${arText}">
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: __('update_button', 'Update'),
            cancelButtonText: __('cancel_button', 'Cancel'),
            preConfirm: () => {
                return $.ajax({
                    url: './includes/ajaxFile/updateLanguageAjax.php',
                    type: 'POST',
                    data: $('#updateTranslationForm').serialize(),
                    dataType: 'json'
                }).fail(function(){
                    Swal.showValidationMessage(__('generic_error_message', 'An unexpected error occurred.'));
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: __(result.value.title, result.value.title),
                    text: __(result.value.message, result.value.message),
                    icon: result.value.type,
                }).then(() => {
                    if (result.value.type === 'success') {
                        languageTable.ajax.reload();
                    }
                });
            }
        });
    });

    // =================================================================
    // Delete Translation
    // =================================================================
    $(document).on('click', '.delete-translation', function(e) {
        e.preventDefault();
        var langKey = $(this).data('key');

        Swal.fire({
            title: __('are_you_sure', 'Are you sure?'),
            text: __('delete_warning_text', "You won't be able to revert this!"),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: __('yes_delete_it', 'Yes, delete it!'),
            cancelButtonText: __('cancel_button', 'Cancel'),
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: './includes/ajaxFile/delete_translation_ajax.php',
                    type: 'POST',
                    data: { lang_key: langKey },
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire(
                            __(response.title, response.title),
                            __(response.message, response.message),
                            response.type
                        ).then(() => {
                            if (response.type === 'success') {
                                languageTable.ajax.reload();
                            }
                        });
                    },
                    error: function() {
                         Swal.fire(__('error', 'Error'), __('generic_error_message', 'An unexpected error occurred.'), 'error');
                    }
                });
            }
        })
    });
});
</script>

    </body>
</html>
<?php } ?>
