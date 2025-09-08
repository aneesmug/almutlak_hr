<?php
/*
MODIFICATION SUMMARY:
- Modified: The JavaScript for setting the default filter view.
- Added: A new condition for Finance Assistants/Supporters (`emptypeget != 'Manager' && user_dept == 2`). Their view now defaults to 'approved' requests, which are ready for payment processing. This provides a more role-specific user experience.
*/

 require_once __DIR__ . '/includes/db.php';
 require_once __DIR__ . '/includes/session_check.php';
 $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
 if(mysqli_num_rows($query) == 1){
 include("./includes/avatar_select.php");

    $q_post = mysqli_query($conDB, "SELECT * FROM `menu_category` ORDER BY `id` DESC LIMIT 1");
    while ($row = mysqli_fetch_assoc($q_post)) {
        $lastid =  $row['id'];
    }

?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?=__('all_requests_title')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- Modal -->
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

        <!-- Plugins css -->
        <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
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
        <script src="assets/js/modernizr.min.js"></script>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
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
  <div class="card-box table-responsive">
   <h4 class="m-t-0 header-title"><?=__('all_smart_requests_header')?></h4>

<div class="col-2 float-right">
    <div class="form-group">
        <input type="search" name="search" class="form-control" placeholder="<?=__('search_placeholder')?>" id="search" autocomplete="off">
    </div>
</div>
<div class="col-2 float-right">
    <div class="form-group" style="margin-bottom: 0 !important">
        <select class="form-control" name="smt_status" id="smtStatus">
            <option value=""><?=__('all_statuses_option')?></option>
            <option value="draft"><?=__('draft_status')?></option>
            <option value="pending_dept_manager_approval"><?=__('pending_dept_manager_status')?></option>
            <option value="pending_finance_approval"><?=__('pending_finance_status')?></option>
            <option value="pending_gm_approval"><?=__('pending_gm_status')?></option>
            <option value="approved"><?=__('approved')?></option>
            <option value="rejected"><?=__('rejected')?></option>
            <option value="Paid"><?=__('paid_status')?></option>
        </select>
    </div>
</div>


<table id="smartRequestTbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
    <thead>
        <tr>
            <th><?=__('id')?></th>
            <th><?=__('invoice_no_header')?></th>
            <th><?=__('subject_title_header')?></th>
            <th><?=__('type')?></th>
            <th><?=__('department')?></th>
            <th><?=__('prepared_by_header')?></th>
            <th><?=__('created_at')?></th>
            <th><?=__('status')?></th>
            <th width="60"><?=__('action')?></th>
            
        </tr>
    </thead>
</table>
                                </div>
                            </div>
                        </div>
      

                    </div> <!-- container -->

                </div> <!-- content -->

                <footer class="footer">
                    <?=$site_footer?>
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
    <!-- Responsive examples -->
    <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

    <!-- App js -->
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>

    <script type="text/javascript">

    $(document).ready(function(){
        
        var buttonConfig = [];
        var exportTitle = "<?=__('all_requests')?>";
        var columnNum = [ 1, 2, 3, 4, 5, 6, 7 ];
        var statusObj = {
            'draft':        { title: '<?=__('draft_status')?>', class: 'badge-secondary' },
            'pending_dept_manager_approval':      { title: '<?=__('pending_dept_manager_status')?>', class: 'badge-custom' },
            'pending_finance_approval':            { title: '<?=__('pending_finance_status')?>', class: 'badge-warning' },
            'pending_gm_approval':   { title: '<?=__('pending_gm_status')?>', class: 'badge-primary' },
            'approved':      { title: '<?=__('approved')?>', class: 'badge-success' },
            'rejected':       { title: '<?=__('rejected')?>', class: 'badge-danger' },
            'Paid':         { title: '<?=__('paid_status')?>', class: 'badge-purple' },
        };
        buttonConfig.push({extend:'excel',exportOptions: {columns: columnNum} ,title: exportTitle,className: 'btn-success'});
        buttonConfig.push({extend:'pdf',exportOptions: {columns: columnNum} ,title: exportTitle,className: 'btn-danger'});
        buttonConfig.push({extend:'print' ,exportOptions: {columns: columnNum} ,title: exportTitle,className: 'btn-dark'});
        
        <?php if ($user_type <> "gm"):?>
        buttonConfig.push({text: '<i class="fa fa-plus"></i> <?=__('new_request_button')?>', action: function ( e, dt, button, config ) {window.location = 'new_request.php?id=<?=$newinvnr ?>' } ,className: 'btn-info'});
        <?php endif; ?>
        <?php if ($user_type == "administrator"):?>
        buttonConfig.push({text: '<i class="mdi mdi-atom"></i> <?=__('all_status_logs_button')?>', action: function ( e, dt, button, config ) {window.location = 'all_smt_req_status.php' } ,className: 'btn-danger'});
        <?php endif; ?>


            var table = $('#smartRequestTbl').DataTable({
                dom: "Bfrtip",
                serverSide: true,
                buttons: buttonConfig,
                order: [[ 0, "desc" ]],
                columnDefs:
                    [
                        {
                            targets: [ 0 ],
                            visible: false,
                            searchable: false
                        },
                        {
                            targets: 7,
                            render: function ( data, type, row, meta ) {
                                return (data in statusObj) ? (`<span class="badge ${statusObj[data].class}" text-capitalized>${statusObj[data].title}</span>`) : data;
                            }
                        },
                    ],
                processing: true,
                responsive: true,
                ajax: {
                    type: "POST",
                    url:'./includes/ajaxFile/smartRequestAjaxTbl.php',
                    data: function (d) {
                        d.user_type = '<?=$user_type?>';
                        d.user_dept = '<?=$user_dept?>';
                        d.emptype   = '<?=$emptypeget?>';
                        d.emp_id    = '<?=$empid?>';
                        d.smtStatus = $('#smtStatus').val();
                        d.search    = $('#search').val();
                    },
                },
                'columns': [
                    { data: 'id' },
                    { data: 'inv_no' },
                    { data: 'sub_title' },
                    { data: 'sub_type' },
                    { data: 'department' },
                    { data: 'prep_by' },
                    { data: 'created_at' },
                    { data: 'status' },
                    { data: 'action' },
                ],
                language: {
                    search: `<span>${__('search')}:</span> _INPUT_`,
                    searchPlaceholder: `${__('search')}...`,
                    lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
                    info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                    infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                    infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
                    paginate: {
                        first: __('first'),
                        last: __('last'),
                        next: __('next'),
                        previous: __('previous')
                    },
                    emptyTable: __('no_data_available_in_table'),
                    zeroRecords: __('no_matching_records_found'),
                    processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
                }
            });
            
            $('#smtStatus').change(function () {
                table.draw();
            });
            $('#search').keyup(function(){
                table.search($(this).val()).draw() ;
            });

            $('#smartRequestTbl_filter').remove();

            // Set default filter based on user role
            if('<?=$emptypeget?>' == "Manager" && '<?=$user_dept?>' == 2){
                $('#smtStatus').val('pending_finance_approval');
                table.draw();
            } else if('<?=$emptypeget?>' != "Manager" && '<?=$user_dept?>' == 2){ // Finance Assistant/Supporter
                $('#smtStatus').val('approved'); // Default to show items ready for payment
                table.draw();
            } else if('<?=$user_type?>' == "gm"){
                $('#smtStatus').val('pending_gm_approval');
                table.draw();
            } else if ('<?=$emptypeget?>' == "Manager"){
                $('#smtStatus').val('pending_dept_manager_approval');
                table.draw();
            };

        });
</script>

    </body>
</html>
<?php } ?>
