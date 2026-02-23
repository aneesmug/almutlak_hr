<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Restrict access to non-employee users only
if ($user_type == 'employee') {
    header('Location: dashboard.php');
    exit;
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
if(mysqli_num_rows($query) == 1){
    include("./includes/avatar_select.php");
}

// Get department name
$dept_query = mysqli_query($conDB, "SELECT `dep_nme` FROM `department` WHERE `id` = '$user_dept'");
$dept_name = 'N/A';
if ($dept_query && mysqli_num_rows($dept_query) > 0) {
    $dept_row = mysqli_fetch_assoc($dept_query);
    $dept_name = $dept_row['dep_nme'];
}

// Generate invoice number if not present
if (!isset($_GET['id'])) {
    $inv_no = 'GR' . $empid . date("ymdHis");
    header("Location: new_general_request.php?id=$inv_no");
    exit;
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?=__('create_general_request', 'Create General Request')?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    
    <!-- Plugins -->
    <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="./plugins/switchery/switchery.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    
    <style type="text/css">
        .priority-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem; font-weight: 600; }
        .priority-low { background-color: #36c6d3; color: white; }
        .priority-medium { background-color: #ffbd4a; color: white; }
        .priority-high { background-color: #ff8acc; color: white; }
        .priority-urgent { background-color: #f1556c; color: white; }
        .item-specs { resize: vertical; min-height: 60px; }
    </style>
    
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <!-- Left Sidebar -->
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                        <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>

        <!-- Content Page -->
        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>
            
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <h4 class="header-title mb-4">
                                    <i class="mdi mdi-file-document-box-plus-outline mr-2"></i>
                                    <?=__('create_general_request', 'Create General Request')?>
                                </h4>
                                
                                <form id="generalRequestForm">
                                    <!-- Hidden Fields -->
                                    <input type="hidden" name="inv_no" value="<?= htmlspecialchars($_GET['id']) ?>">
                                    <input type="hidden" name="emp_id" value="<?= $empid ?>">
                                    <input type="hidden" name="emp_name" value="<?= htmlspecialchars($userwel) ?>">
                                    <input type="hidden" name="user_dept" value="<?= $user_dept ?>">
                                    
                                    <!-- Request Information -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><?=__('request_number', 'Request Number')?></label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($_GET['id']) ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><?=__('request_date', 'Request Date')?></label>
                                                <input type="text" class="form-control" value="<?= date("d F Y")?>" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><?=__('requester_name', 'Requester Name')?></label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($userwel) ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><?=__('requester_department', 'Requester Department')?></label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($dept_name) ?>" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><?=__('request_title', 'Request Title')?> <span class="text-danger">*</span></label>
                                                <input type="text" name="request_title" class="form-control" required placeholder="<?=__('enter_request_title', 'Enter request title')?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label><?=__('target_department', 'Target Department')?> <span class="text-danger">*</span></label>
                                                <select name="department_to" class="form-control" required>
                                                    <option value=""><?=__('select')?></option>
                                                    <option value="IT"><?=__('it_department', 'IT Department')?></option>
                                                    <option value="HR"><?=__('hr_department', 'HR Department')?></option>
                                                    <option value="Transportation"><?=__('transportation_dept', 'Transportation')?></option>
                                                    <option value="Finance"><?=__('finance_department', 'Finance')?></option>
                                                    <option value="Maintenance"><?=__('maintenance_dept', 'Maintenance')?></option>
                                                    <option value="Admin"><?=__('admin_department', 'Admin')?></option>
                                                    <option value="Other"><?=__('other')?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label><?=__('priority', 'Priority')?> <span class="text-danger">*</span></label>
                                                <select name="priority" class="form-control" required>
                                                    <option value="low"><?=__('low', 'Low')?></option>
                                                    <option value="medium" selected><?=__('medium', 'Medium')?></option>
                                                    <option value="high"><?=__('high', 'High')?></option>
                                                    <option value="urgent"><?=__('urgent', 'Urgent')?></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><?=__('request_category', 'Request Category')?> <span class="text-danger">*</span></label>
                                                <select name="request_category" class="form-control" required>
                                                    <option value=""><?=__('select')?></option>
                                                    <option value="Equipment"><?=__('equipment', 'Equipment')?></option>
                                                    <option value="Service"><?=__('service', 'Service')?></option>
                                                    <option value="Supplies"><?=__('supplies', 'Supplies')?></option>
                                                    <option value="Maintenance"><?=__('maintenance', 'Maintenance')?></option>
                                                    <option value="Support"><?=__('support', 'Support')?></option>
                                                    <option value="Other"><?=__('other')?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label><?=__('description', 'Description')?></label>
                                                <textarea name="description" class="form-control" rows="3" placeholder="<?=__('additional_details', 'Additional details or notes')?>"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    
                                    <h5 class="mb-3">
                                        <i class="mdi mdi-format-list-bulleted mr-2"></i>
                                        <?=__('request_items', 'Request Items')?>
                                    </h5>

                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="itemsTable">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th width="5%">#</th>
                                                    <th width="25%"><?=__('item_name', 'Item Name')?> <span class="text-danger">*</span></th>
                                                    <th width="15%"><?=__('item_type', 'Type/Category')?></th>
                                                    <th width="10%"><?=__('quantity')?> <span class="text-danger">*</span></th>
                                                    <th width="35%"><?=__('specifications', 'Specifications')?></th>
                                                    <th width="10%" class="text-center"><?=__('action')?></th>
                                                </tr>
                                            </thead>
                                            <tbody id="itemsTableBody">
                                                <!-- Items will be added here dynamically -->
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr class="my-4">

                                    <!-- Attachments Section -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h5 class="mb-3">
                                                <i class="mdi mdi-paperclip mr-2"></i>
                                                <?=__('attachments', 'Attachments')?> <?=__('optional', '(Optional)')?>
                                            </h5>
                                            <div class="form-group">
                                                <input type="file" name="attachments[]" id="attachments" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                                <small class="form-text text-muted"><?=__('upload_multiple_files', 'You can upload multiple files (PDF, Images, Word, Excel)')?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <!-- Action Buttons -->
                                    <div class="row">
                                        <div class="col-12 text-right">
                                            <button type="button" class="btn btn-secondary mr-2" onclick="window.location.href='all_general_requests.php'">
                                                <i class="mdi mdi-close"></i> <?=__('cancel')?>
                                            </button>
                                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                                <i class="mdi mdi-content-save"></i> <?=__('submit_request', 'Submit Request')?>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer"><?= $site_footer ?></footer>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="./plugins/bootstrap-select/js/bootstrap-select.js"></script>
    <script src="./plugins/switchery/switchery.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script type="text/javascript">
        let itemCounter = 0;

        // Item types based on department
        const itemTypesByDepartment = {
            'IT': ['Laptop', 'Desktop', 'Monitor', 'Keyboard', 'Mouse', 'Printer', 'Printer Ink/Toner', 'Network Equipment', 'Software License', 'Other'],
            'Transportation': ['Vehicle', 'Mobile Phone', 'SIM Card', 'Fuel Card', 'GPS Device', 'Other'],
            'HR': ['Stationery', 'Office Supplies', 'Furniture', 'ID Card', 'Badge', 'Other'],
            'Finance': ['Calculator', 'Receipt Book', 'Document Folder', 'Safe/Locker', 'Other'],
            'Maintenance': ['Tools', 'Equipment', 'Safety Gear', 'Cleaning Supplies', 'Other'],
            'Admin': ['Furniture', 'Office Equipment', 'Decoration', 'Other'],
            'Other': ['Other']
        };

        function getItemTypeOptions(department) {
            const types = itemTypesByDepartment[department] || itemTypesByDepartment['Other'];
            let options = '<option value=""><?=__('select')?></option>';
            types.forEach(type => {
                options += `<option value="${type}">${type}</option>`;
            });
            return options;
        }

        function addItemRow() {
            // Validate required fields before adding new row
            const department = $('select[name="department_to"]').val();
            const category = $('select[name="request_category"]').val();
            
            if (!department || !category) {
                Swal.fire({
                    icon: 'warning',
                    title: '<?=__('required_fields', 'Required Fields')?>',
                    text: '<?=__('select_department_category_first', 'Please select Target Department and Request Category first')?>',
                    confirmButtonColor: APP_COLORS.primary
                });
                return false;
            }
            
            itemCounter++;
            const itemTypeOptions = getItemTypeOptions(department);
            
            const row = `
                <tr data-row-id="${itemCounter}">
                    <td class="text-center">${itemCounter}</td>
                    <td><input type="text" name="items[${itemCounter}][item_name]" class="form-control" required placeholder="<?=__('enter_item_name', 'Enter item name')?>"></td>
                    <td>
                        <select name="items[${itemCounter}][item_type]" class="form-control">
                            ${itemTypeOptions}
                        </select>
                    </td>
                    <td><input type="number" name="items[${itemCounter}][quantity]" class="form-control" value="1" min="1" required></td>
                    <td><textarea name="items[${itemCounter}][specifications]" class="form-control item-specs" placeholder="<?=__('item_specifications', 'Item specifications or requirements')?>"></textarea></td>
                    <td class="text-center action-cell">
                        <button type="button" class="btn btn-success btn-sm bbtn addRowBtn" aria-label="Add Row">
                            <i class="mdi mdi-plus"></i>
                        </button>
                    </td>
                </tr>
            `;
            $('#itemsTableBody').append(row);
            renumberRows();
        }

        function renumberRows() {
            const rows = $('#itemsTableBody tr');
            rows.each(function(index) {
                const newIndex = index + 1;
                $(this).find('td:first').text(newIndex);
                
                // Update form field names to be sequential
                $(this).find('input, select, textarea').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        // Replace the old index with new sequential index
                        name = name.replace(/items\[\d+\]/, `items[${newIndex}]`);
                        $(this).attr('name', name);
                    }
                });
            });
            refreshActionButtons(rows);
        }

        function refreshActionButtons(rows) {
            rows.each(function(index) {
                const isFirst = index === 0;
                const $cell = $(this).find('td.action-cell');
                if (!$cell.length) return;
                if (isFirst) {
                    // First row: show add button only
                    $cell.html('<button type="button" class="btn btn-success bbtn btn-sm addRowBtn" aria-label="Add Row"><i class="mdi mdi-plus"></i></button>');
                } else {
                    // All other rows: show delete button only
                    $cell.html(`<button type="button" class="btn btn-danger bbtn btn-sm removeItemBtn" data-row-id="${index + 1}" aria-label="Remove Row"><i class="mdi mdi-minus"></i></button>`);
                }
            });
        }

        // Update item types when department changes
        $('select[name="department_to"]').on('change', function() {
            const department = $(this).val() || 'Other';
            const itemTypeOptions = getItemTypeOptions(department);
            
            $('#itemsTableBody select[name*="[item_type]"]').each(function() {
                const currentValue = $(this).val();
                $(this).html(itemTypeOptions);
                // Try to maintain selection if it exists in new options
                if ($(this).find(`option[value="${currentValue}"]`).length > 0) {
                    $(this).val(currentValue);
                }
            });
            
            // Auto-add first row when both required fields are selected
            checkAndAddFirstRow();
        });

        // Auto-add first row when category is selected
        $('select[name="request_category"]').on('change', function() {
            checkAndAddFirstRow();
        });

        // Function to check if both fields are selected and add first row if needed
        function checkAndAddFirstRow() {
            const department = $('select[name="department_to"]').val();
            const category = $('select[name="request_category"]').val();
            const hasRows = $('#itemsTableBody tr').length > 0;
            
            if (department && category && !hasRows) {
                addItemRow();
            }
        }

        // Add item button
        $('#addItemBtn').on('click', function() {
            addItemRow();
        });

        // Add row inline button
        $(document).on('click', '.addRowBtn', function() {
            addItemRow();
        });

        // Remove item button
        $(document).on('click', '.removeItemBtn', function() {
            const $row = $(this).closest('tr');
            const rowIndex = $row.index();
            
            // Prevent removing first row
            if (rowIndex === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: '<?=__('warning', 'Warning')?>',
                    text: '<?=__('cannot_remove_first_item', 'Cannot remove the first item')?>',
                    confirmButtonColor: APP_COLORS.primary
                });
                return;
            }
            
            if ($('#itemsTableBody tr').length > 1) {
                $row.remove();
                // Reset counter and renumber all rows sequentially
                itemCounter = $('#itemsTableBody tr').length;
                renumberRows();
            }
        });

        // Prevent Enter from submitting; use it to add a row instead (except in textarea)
        $('#generalRequestForm').on('keydown', function(e) {
            if (e.key === 'Enter' && !$(e.target).is('textarea')) {
                e.preventDefault();
                addItemRow();
            }
        });

        // Form submission
        $('#generalRequestForm').on('submit', function(e) {
            e.preventDefault();
            
            // Validate at least one item
            if ($('#itemsTableBody tr').length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: '<?=__('error')?>',
                    text: '<?=__('add_at_least_one_item', 'Please add at least one item to the request')?>',
                    confirmButtonColor: APP_COLORS.primary
                });
                return false;
            }

            // Validate all items have required fields
            let hasEmptyItem = false;
            $('#itemsTableBody tr').each(function() {
                const itemName = $(this).find('input[name*="[item_name]"]').val().trim();
                const quantity = $(this).find('input[name*="[quantity]"]').val().trim();
                
                if (!itemName || !quantity) {
                    hasEmptyItem = true;
                    return false;
                }
            });
            
            if (hasEmptyItem) {
                Swal.fire({
                    icon: 'error',
                    title: '<?=__('error')?>',
                    text: '<?=__('all_items_required_fields', 'All items must have name and quantity')?>',
                    confirmButtonColor: APP_COLORS.primary
                });
                return false;
            }

            // Disable submit button
            $('#submitBtn').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> <?=__('submitting', 'Submitting')?>...');

            const formData = new FormData(this);
            formData.append('action', 'create_general_request');
            // Note: FormData(this) already includes all form inputs including attachments[]
            // No need to manually append files again

            $.ajax({
                url: './includes/ajaxFile/ajaxGeneralRequest.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '<?=__('success')?>',
                            text: response.message || '<?=__('request_created_successfully', 'Request created successfully')?>',
                            confirmButtonColor: APP_COLORS.primary,
                            timer: 2000
                        }).then(function() {
                            window.location.href = 'view_general_request.php?id=' + response.inv_no;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '<?=__('error')?>',
                            text: response.message || '<?=__('request_creation_failed', 'Failed to create request')?>',
                            confirmButtonColor: APP_COLORS.primary
                        });
                        $('#submitBtn').prop('disabled', false).html('<i class="mdi mdi-content-save"></i> <?=__('submit_request', 'Submit Request')?>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: '<?=__('error')?>',
                        text: '<?=__('request_submission_error', 'An error occurred while submitting the request')?>',
                        confirmButtonColor: APP_COLORS.primary
                    });
                    $('#submitBtn').prop('disabled', false).html('<i class="mdi mdi-content-save"></i> <?=__('submit_request', 'Submit Request')?>');
                }
            });
        });

        // Initialize with one item row
        $(document).ready(function() {
            // Don't auto-add row on page load
            // User must select department and category first
        });
    </script>
</body>
</html>
