<?php
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
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - All Items</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- Modal -->
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

  <!-- Plugins css -->
        <!-- <link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet"> -->
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
        <style type="text/css">
            tr.disableLoc{
                background-color: #f1556c !important;
                color: #fff;
            }
            tr.disableLoc:hover{
                background-color: #ef3d58 !important;
            }
        </style>
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
  <div class="card-box table-responsive">
            <!-- <a href="add_location.php" class="btn btn-primary waves-effect"><i class="mdi mdi-settings"></i> Add New Location</a> -->
   <h4 class="m-t-0 header-title">All Registerd Items</h4>
<table id="items_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
 <thead>
  <tr>
   <th>Image</th>
   <th>Item Name (English)</th>
   <th>Item Name (Arabic)</th>
   <th>Category</th>
   <th>Price Level</th>
   <th>Price (larg)</th>
   <th>Price (Small)</th>
   <th>Calories (Larg)</th>
   <th>Calories (Small)</th>
   <th>Status</th>
   <th width="60">Action</th>
  </tr>
 </thead>
 <tbody>
<?php
    $sql_loc = "
    SELECT `menu_item`.*, `menu_category`.`name_eng` as `catname`, `category_type`.`type`, `menu_item_img`.`file`
    FROM `menu_item`
    LEFT JOIN `menu_item_img` ON `menu_item`.`id` = `menu_item_img`.`itm_id`
    LEFT JOIN `menu_category` ON `menu_item`.`category_id` = `menu_category`.`id`
    LEFT JOIN `category_type` ON `menu_item`.`price_level` = `category_type`.`id` 
    ORDER BY `menu_item`.`name_eng` REGEXP '^[^A-Za-z]' ASC, `menu_item`.`name_eng`
";

$query_loc = mysqli_query($conDB, $sql_loc);

while ($rec = mysqli_fetch_array($query_loc)) {
    $id = $rec["id"];
    $name_eng = $rec["name_eng"];
    $name_ar = $rec["name_ar"];
    $catname = $rec["catname"];
    $price_level = $rec["price_level"];
    $big_price = $rec["big_price"];
    $small_price = $rec["small_price"];
    $big_cal = $rec["big_cal"];
    $small_cal = $rec["small_cal"];
    $image = $rec["file"];
    $status = $rec["status"];
    $type = $rec["type"];
    $reg_date = $rec["reg_date"];
     
    // $times_reg = strtotime("$date_emp");
    // $datevac = date('d, M Y', $times_reg);
    $image = ($image)?$image:"sample_image.png";
?>

    <tr <?= ($status != "1") ? "class='table-danger'" : false ; ?> >
  <th><img src="./QR_MENU/images/item_img/<?=$image?>" class="rounded-circle bx-shadow-lg" width="50" height="50" /></th>
        <th><?= $name_eng?></th>
        <th><?= $name_ar ?></th>
        <th><?= $catname ?></th>
        <th><?= $type ?></th>
        <th><?= $big_price."<small> SAR</small>" ?></th>
        <th><?= $small_price."<small> SAR</small>" ?></th>
        <th><?= $big_cal."<small> cal</small>" ?></th>
        <th><?= $small_cal."<small> cal</small>" ?></th>
        
  <td><?= ($status == "1") ? "<span class='badge-border badge-border-success'>Active</span>" : "<span class='badge-border badge-border-danger'>Inactive</span>" ; ?>
        </td>
        
  <td>

    <div class='btn-group dropdown'>
        <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
        <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
            <a href='javascript:void(0);' class='dropdown-item text-custom editItemAttr' data-id="<?=$rec['id']?>" data-i_name_eng="<?=$rec['name_eng']?>" data-i_name_ar="<?=$rec['name_ar']?>" data-i_big_price="<?=$rec['big_price']?>" data-i_small_price="<?=$rec['small_price']?>" data-i_big_cal="<?=$rec['big_cal']?>" data-i_small_cal="<?=$rec['small_cal']?>" data-category_id="<?=$rec['category_id']?>" data-price_level="<?=$rec['price_level']?>" data-catname="<?=$rec['catname']?>" data-istatus="<?=$rec['status']?>" data-iimage="<?=$rec['image']?>"><i class='fa fa-edit mr-2 font-18 vertical-middle'></i>Edit</a>
            <?php if($user_type == $access1){ ?>
            <a href='javascript:void(0);' class='dropdown-item  text-danger deleteAjax' data-id='<?=$id?>' data-tbl='menu_item' data-file='1' data-column='image' ><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>Delete</a>
            <?php } ?>
        </div>
    </div>

  </td>
 </tr>
<?php } ?>
          </tbody>
<tfoot>
  <tr>
   <th> </th>
   <th>Item Name (English)</th>
   <th>Item Name (Arabic)</th>
   <th>Category</th>
   <th>Price Level</th>
   <th>Price (larg)</th>
   <th>Price (Small)</th>
   <th>Calories (Larg)</th>
   <th>Calories (Small)</th>
   <th>Status</th>
   <th width="60">Action</th>
  </tr>
 </tfoot>
                                    </table>
                                </div>
<div class="card-box table-responsive">
            <!-- <a href="add_location.php" class="btn btn-primary waves-effect"><i class="mdi mdi-settings"></i> Add New Location</a> -->
            <h4 class="m-t-0 header-title">All Categories</h4>
<table id="category_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
    <thead>
        <tr>
            <th> </th>
            <th>Category Name (English)</th>
            <th>Category Name (Arabic)</th>
            <th>Description (larg | Small)</th>
            <th>Category Group</th>
            <th>Status</th>
            <th width="60">Action</th>
        </tr>
    </thead>
    <tbody>
<?php
    $sql_loc = "
        SELECT `menu_category`.*, `category_type`.`type`
        FROM `menu_category` 
        LEFT JOIN `category_type` 
        ON `category_type`.`id` = `menu_category`.`cate_id`
        ORDER BY `name_eng` 
        REGEXP '^[^A-Za-z]' ASC, `name_eng`
";
    $query_loc = mysqli_query($conDB, $sql_loc);

while ($rec = mysqli_fetch_array($query_loc)) {
    $id = $rec["id"];
    $name_eng = $rec["name_eng"];
    $name_ar = $rec["name_ar"];
    $desc_eng = $rec["desc_eng"];
    $desc_ar = $rec["desc_ar"];
    $type = $rec["type"];
    $status = $rec["status"];
?>

    <tr <?= ($status != "1") ? "class='table-danger'" : false ; ?> >
        <th><?= $id?></th>
        <th><?= $name_eng?></th>
        <th><?= $name_ar ?></th>
        <th><?= $desc_eng." | ".$desc_ar ?></th>
        <th><?= $type ?></th>
        
        <td><?= ($status == "1")?"<span class='badge-border badge-border-success'>Active</span>":"<span class='badge-border badge-border-danger'>Closed</span>";?>
        </td>
        
        <td>
    <div class='btn-group dropdown'>
        <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
        <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
            <a href='javascript:void(0);' class='dropdown-item text-custom editCategoryAttr' data-id="<?=$rec['id']?>" data-name_eng="<?=$rec['name_eng']?>" data-name_ar="<?=$rec['name_ar']?>" data-desc_eng="<?=$rec['desc_eng']?>" data-desc_ar="<?=$rec['desc_ar']?>" data-status="<?=$rec['status']?>" data-category_type="<?=$rec['cate_id']?>" ><i class='fa fa-edit mr-2 font-18 vertical-middle'></i>Edit</a>
            <?php if($user_type == $access1){ ?>
            <a href='javascript:void(0);' class='dropdown-item  text-danger deleteAjax' data-id='<?=$id?>' data-tbl='menu_category' data-file='0'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>Delete</a>
            <?php } ?>
        </div>
    </div>

        </td>
    </tr>
<?php } ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th> </th>
                                                <th>Category Name (English)</th>
                                                <th>Category Name (Arabic)</th>
                                                <th>Description (larg | Small)</th>
                                                <th>Category Group</th>
                                                <th>Status</th>
                                                <th width="60">Action</th>
                                            </tr>
                                        </tfoot>
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

    <!-- Modal-Effect -->
    <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
    <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>
    <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>
    <script src="./plugins/moment/moment.js"></script>
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

    <!-- App js -->
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {

            var buttonConfig = [];
            var exportTitle = "All Menu Items"
            buttonConfig.push({extend:'excel',exportOptions: {columns: [ 1, 2, 3, 4, 5, 6, 7, 8 ]} ,title: exportTitle,className: 'btn-success'});
            buttonConfig.push({extend:'pdf',exportOptions: {columns: [ 1, 2, 3, 4, 5, 6, 7, 8 ]} ,title: exportTitle,className: 'btn-danger'});
            buttonConfig.push({extend:'print' ,exportOptions: {columns: [ 1, 2, 3, 4, 5, 6, 7, 8 ]} ,title: exportTitle,className: 'btn-dark'});
            buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Category', action: function ( e, dt, button, config ) { addCategoryFunc() } ,className: 'btn-success'});
            buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Item', action: function ( e, dt, button, config ) { addItemFunc() } ,className: 'btn-info'});

            $('form').parsley();

            var itemTable = $('#items_tbl').DataTable({
                initComplete: function () {
                this.api()
                    .columns([3,4])
                    .every(function () {
                        var column = this;
                        var select = $('<select><option value=""></option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                column.search(val ? '^' + val + '$' : '', true, false).draw();
                            });
                        column
                            .data()
                            .unique() 
                            .sort()
                            .each(function (d, j) {
                                select.append(`<option value="${d}">${d.substr(0,30)}</option>`)
                            });
                        });
                },

                pageLength: 8,
                lengthChange: false,
                buttons: buttonConfig,
            });
    
        itemTable.buttons().container().appendTo('#items_tbl_wrapper .col-md-6:eq(0)');

        var cateTable = $('#category_tbl').DataTable({
                initComplete: function () {
                this.api()
                    .columns([1,4])
                    .every(function () {
                        var column = this;
                        var select = $('<select><option value=""></option></select>')
                            .appendTo($(column.footer()).empty())
                            .on('change', function () {
                                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                column.search(val ? '^' + val + '$' : '', true, false).draw();
                            });
                        column
                            .data()
                            .unique() 
                            .sort()
                            .each(function (d, j) {
                                select.append(`<option value="${d}">${d.substr(0,30)}</option>`)
                            });
                        });
                },

                pageLength: 5,
                lengthChange: false,
                order: [[ 0, "desc" ]],
                columnDefs: 
                    [
                        {
                        targets: [ 0 ],
                        visible: false,
                        searchable: false
                        },
                    ],
            });
    
    });
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

        /*$('.editCategoryAttrX').click(function() {
            var status      = $(this).data('status');
            $('#smid')      .val($(this).data('id')); 
            $('#name_eng')     .val($(this).data('name_eng'));
            $('#name_ar')     .val($(this).data('name_ar'));
            $('#desc_eng')     .val($(this).data('desc_eng'));
            $('#desc_ar')     .val($(this).data('desc_ar'));
            
            $('input[name="status"][value="'+status+'"]').prop('checked', true);
            // $('#position option[value="'+position+'"]').prop("selected", "selected");
        });*/

        /*$('.editItemAttr').click(function() {
            var istatus      = $(this).data('istatus');
            var price_level     = $(this).data('price_level');
            var category_id     = $(this).data('category_id');
            $('#itemid')      .val($(this).data('id')); 
            $('#i_name_eng')     .val($(this).data('i_name_eng'));
            $('#i_name_ar')     .val($(this).data('i_name_ar'));
            $('#i_big_price')     .val($(this).data('i_big_price'));
            $('#i_small_price')     .val($(this).data('i_small_price'));
            $('#i_big_cal')     .val($(this).data('i_big_cal'));
            $('#i_small_cal')     .val($(this).data('i_small_cal'));
            $('#iimage')     .val($(this).data('iimage'));

            $('input[name="status"][value="'+istatus+'"]').prop('checked', true);
            $('#price_level option[value="'+price_level+'"]').prop("selected", "selected");
            $('#category_id option[value="'+category_id+'"]').prop("selected", "selected");
            
        });*/
 
</script>

    </body>
</html>
<?php } ?>