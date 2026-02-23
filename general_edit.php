<?php
// general_edit.php
// Dynamic general edit page for any table/record

require_once __DIR__ . '/includes/session_check.php';

function getAllTables($pdo) {
    $stmt = $pdo->query("SHOW TABLES");
    return $stmt->fetchAll(PDO::FETCH_NUM);
}

function getTableColumns($pdo, $table) {
    $stmt = $pdo->prepare("DESCRIBE `$table`");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecordById($pdo, $table, $pk, $id) {
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE `$pk` = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateRecord($pdo, $table, $pk, $id, $data) {
    $fields = array_keys($data);
    $set = implode(', ', array_map(fn($f) => "`$f` = ?", $fields));
    $values = array_values($data);
    $values[] = $id;
    $sql = "UPDATE `$table` SET $set WHERE `$pk` = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($values);
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {

$pdo = getDbConnection();
$tables = getAllTables($pdo);

$table = $_POST['table'] ?? '';
$id = $_POST['id'] ?? '';
$columns = [];
$record = [];
$pk = '';
$msg = '';

if ($table) {
    $columns = getTableColumns($pdo, $table);
    // Find primary key
    foreach ($columns as $col) {
        if ($col['Key'] === 'PRI') {
            $pk = $col['Field'];
            break;
        }
    }
    if ($id && $pk) {
        $record = getRecordById($pdo, $table, $pk, $id);
        if (!$record) {
            $msg = '<div class="alert alert-danger">Record not found.</div>';
        }
    }
    // Handle update
    if (isset($_POST['update']) && $pk && $id) {
        $updateData = [];
        foreach ($columns as $col) {
            $field = $col['Field'];
            if ($field !== $pk && isset($_POST[$field])) {
                $updateData[$field] = $_POST[$field];
            }
        }
        if (updateRecord($pdo, $table, $pk, $id, $updateData)) {
            $msg = '<div class="alert alert-success">Record updated successfully.</div>';
            $record = getRecordById($pdo, $table, $pk, $id);
        } else {
            $msg = '<div class="alert alert-danger">Update failed.</div>';
        }
    }
}
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - General Edit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <!-- Select2 -->
    <link rel="stylesheet" href="./plugins/select2/css/select2.min.css">

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
    <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>
</head>

<body class="enlarged" data-keep-enlarged="true" data-page="general-edit">

    <!-- Begin page -->
    <div id="wrapper">

        <!-- ========== Left Sidebar Start ========== -->
        <div class="left side-menu">

            <div class="slimscroll-menu" id="remove-scroll">

                <!-- LOGO -->
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span>
                            <img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22">
                        </span>
                        <i>
                            <img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28">
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
                        <div class="col-md-12">
                            <div class="card-box">
                                <h4 class="m-t-0 header-title">General Edit Page</h4>
                                <?= $msg ?>
                                <form method="post" class="registration mb-3">
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="table">Table Name <span class="text-danger">*</span></label>
                                            <select name="table" id="table" class="form-control select2" onchange="this.form.submit()" required>
                                                <option value="">Select Table</option>
                                                <?php foreach ($tables as $t): $tname = $t[0]; ?>
                                                    <option value="<?= htmlspecialchars($tname) ?>" <?= $table === $tname ? 'selected' : '' ?>><?= htmlspecialchars($tname) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <?php if ($table && $pk): ?>
                                        <div class="form-group col-md-4">
                                            <label for="id"><?= htmlspecialchars($pk) ?> (ID) <span class="text-danger">*</span></label>
                                            <input type="text" name="id" id="id" value="<?= htmlspecialchars($id) ?>" class="form-control" required />
                                        </div>
                                        <div class="form-group col-md-4 align-self-end d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary"><i class="mdi mdi-magnify"></i> Search</button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </form>
                                <?php if ($record && $columns): ?>
                                <form method="post" class="registration">
                                    <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>" />
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>" />
                                    <div class="form-row">
                                        <?php foreach ($columns as $col): $field = $col['Field']; ?>
                                            <div class="form-group col-md-4">
                                                <label for="<?= htmlspecialchars($field) ?>"><?= htmlspecialchars($field) ?><?= $field === $pk ? ' <span class=\'text-danger\'>*</span>' : '' ?></label>
                                                <?php if ($field === $pk): ?>
                                                    <input type="text" class="form-control" id="<?= htmlspecialchars($field) ?>" value="<?= htmlspecialchars($record[$field] ?? '') ?>" disabled />
                                                <?php else: ?>
                                                    <input type="text" name="<?= htmlspecialchars($field) ?>" id="<?= htmlspecialchars($field) ?>" class="form-control" value="<?= htmlspecialchars($record[$field] ?? '') ?>" />
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="form-group mt-3">
                                        <button type="submit" name="update" class="btn btn-success"><i class="mdi mdi-content-save"></i> Update</button>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>


                </div> <!-- container -->

            </div> <!-- content -->

            <footer class="footer">
                <?= $site_footer ?>
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

    <!-- Select2 -->
    <script src="./plugins/select2/js/select2.min.js"></script>

    <!-- App js -->
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            // Initialize Select2 on table dropdown
            $('#table').select2({
                placeholder: 'Select Table',
                allowClear: true,
                width: '100%'
            });
        });
    </script>

</body>

</html>
<?php } ?>

