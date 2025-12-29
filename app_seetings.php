<?php
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/session_check.php';
    $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
    if(mysqli_num_rows($query) == 1){
        include("./includes/avatar_select.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?=$site_title ?? 'Application Settings'; ?> - App Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    
    <!-- Plugins -->
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .loader {
            border: 4px solid #f3f3f3;
            border-radius: 50%;
            border-top: 4px solid #4fa0e3;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .nav-pills .nav-link.active, .nav-pills .show>.nav-link {
            color: #fff;
            background-color: #4fa0e3;
        }
        .preview-image {
            max-height: 50px;
            max-width: 150px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
            background-color: #f8f9fa;
        }

        /* --- Approval Chain Styles --- */
        .approval-chain-container {
            min-height: 60px;
        }
        .approval-step {
            cursor: move;
            transition: all 0.2s ease;
        }
        .approval-step:hover {
            background-color: #f0f8ff !important;
            border-color: #4fa0e3 !important;
        }
        .approval-steps {
            position: relative;
        }
        .approval-step .badge {
            font-size: 0.75rem;
            padding: 0.35em 0.6em;
        }

        /* --- Select2 Bootstrap 4 Style Fixes --- */
        .select2-container {
            width: 100% !important;
        }
        .select2-container .select2-selection--single {
            height: 38px !important; /* Match Bootstrap's form-control height */
            border: 1px solid #ced4da;
            border-radius: .25rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px; /* Vertically center text */
            padding-left: .75rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 5px;
        }
        .select2-dropdown {
             border: 1px solid #ced4da;
             border-radius: .25rem;
             z-index: 1050; /* Ensure dropdown appears above other content */
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
                        <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                        <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                    </a>
                </div>
                <!--- Sidemenu -->
                <?php include("./includes/main_menu.php"); ?>
                <!-- Sidebar -->
                <div class="clearfix"></div>
            </div>
        </div>
        <!-- Left Sidebar End -->

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
                                <h4 class="m-t-0 header-title">Application Settings</h4>
                                <p class="text-muted m-b-30 font-14">Manage your application's configuration.</p>

                                <form id="settingsForm">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <ul id="settings-nav" class="nav nav-pills flex-column" role="tablist">
                                                <!-- Nav items will be injected here by JavaScript -->
                                                <div class="d-flex justify-content-center align-items-center" style="height: 100px;">
                                                    <div class="loader"></div>
                                                </div>
                                            </ul>
                                        </div>
                                        <div class="col-md-9">
                                            <div id="settings-container" class="tab-content p-3 border">
                                                <!-- Tab content will be injected here by JavaScript -->
                                                <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                                                    <div class="loader"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group text-right m-t-20">
                                        <button type="submit" id="saveBtn" class="btn btn-primary waves-effect waves-light">
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div> <!-- container -->
            </div> <!-- content -->

            <footer class="footer">
                <?=$site_footer ?>
            </footer>
        </div>
    </div>
    <!-- END wrapper -->

    <!-- jQuery  -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <!-- Plugins -->
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- App js -->
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let appSettings = [];
        let groupedSettings = {};
        const settingsContainer = document.getElementById('settings-container');
        const settingsNav = document.getElementById('settings-nav');
        const settingsForm = document.getElementById('settingsForm');

        function renderSettingsGroup(groupName) {
            let formHtml = '';
            const settings = groupedSettings[groupName];
            
            if (!settings) {
                 settingsContainer.innerHTML = '<p class="text-center text-danger">Group not found.</p>';
                 return;
            }

            // Special handling for approval chain configuration
            if (groupName === 'approval') {
                renderApprovalChainSettings();
                return;
            }

            formHtml += `<div class="tab-pane active" id="group-${groupName}" role="tabpanel">`;
            settings.forEach(setting => {
                const id = `setting-${setting.setting_name}`;
                const label = setting.description;
                const isImagePath = setting.setting_name.includes('logo') || setting.setting_name.includes('favicon');
                const isEmailList = setting.setting_name === 'traveling_company_email';

                formHtml += `<div class="form-group row">`;
                formHtml += `<label for="${id}" class="col-sm-3 col-form-label">${label}</label>`;
                formHtml += `<div class="col-sm-9">`;
                
                if (isImagePath) {
                    formHtml += `<div class="d-flex align-items-center">`;
                    formHtml += `<img id="preview-${setting.setting_name}" src="${setting.setting_value || 'assets/images/placeholder.png'}" alt="Preview" class="preview-image mr-3">`;
                    formHtml += `<div class="flex-grow-1">`;
                    formHtml += `<input type="file" id="${id}" name="${setting.setting_name}" accept="image/*" class="form-control-file">`;
                    formHtml += `<small class="form-text text-muted">Current: ${setting.setting_value || 'Not set'}</small>`;
                    formHtml += `</div></div>`;
                } else if (isEmailList) {
                    // Special handling for email list
                    let emails = [];
                    try {
                        const parsed = JSON.parse(setting.setting_value || '[]');
                        emails = Array.isArray(parsed) ? parsed : [setting.setting_value].filter(e => e);
                    } catch (e) {
                        emails = setting.setting_value ? [setting.setting_value] : [];
                    }
                    
                    formHtml += `<div id="email-list-container">`;
                    if (emails.length === 0) {
                        formHtml += `<div class="email-item mb-2">
                            <div class="input-group">
                                <input type="email" class="form-control email-input" placeholder="email@example.com" value="">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-danger remove-email-btn" disabled><i class="mdi mdi-delete"></i></button>
                                </div>
                            </div>
                        </div>`;
                    } else {
                        emails.forEach((email, idx) => {
                            formHtml += `<div class="email-item mb-2">
                                <div class="input-group">
                                    <input type="email" class="form-control email-input" placeholder="email@example.com" value="${email}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-danger remove-email-btn"><i class="mdi mdi-delete"></i></button>
                                    </div>
                                </div>
                            </div>`;
                        });
                    }
                    formHtml += `</div>`;
                    formHtml += `<button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-email-btn"><i class="mdi mdi-plus"></i> Add Email</button>`;
                    formHtml += `<input type="hidden" id="${id}" name="${setting.setting_name}" value="">`;
                } else {
                    let inputHtml = '';
                    switch (setting.input_type) {
                        case 'select':
                            let options = JSON.parse(setting.options || '{}');
                            // Note: We still use form-control for layout, but our custom CSS will target .select2-container for styling.
                            inputHtml = `<select id="${id}" name="${setting.setting_name}" class="form-control select2">`;
                            for (const [value, text] of Object.entries(options)) {
                                inputHtml += `<option value="${value}" ${setting.setting_value == value ? 'selected' : ''}>${text}</option>`;
                            }
                            inputHtml += `</select>`;
                            break;
                        default:
                            inputHtml = `<input type="text" id="${id}" name="${setting.setting_name}" class="form-control" value="${setting.setting_value || ''}">`;
                            break;
                    }
                    formHtml += inputHtml;
                }
                
                formHtml += `</div></div>`;
            });
            formHtml += `</div>`;
            settingsContainer.innerHTML = formHtml;
            
            // Initialize Select2 with a width setting for better Bootstrap integration.
            $('.select2').select2({
                width: '100%'
            });

            attachPreviewListeners();
            attachEmailListListeners();
        }

        function renderApprovalChainSettings() {
            const defaultRequestTypes = [
                { id: 'vacation_request', name: 'Vacation Request', description: 'Annual vacation and fly vacation approval chain' },
                { id: 'excuse_leave', name: 'Excuse Leave', description: 'Sick leave, exam leave, and other excuse types' },
                { id: 'loan_request', name: 'Loan Request', description: 'Employee loan application approval chain' },
                { id: 'resignation_request', name: 'Resignation Request', description: 'Employee resignation approval chain' },
                { id: 'rejoin_request', name: 'Rejoin Request', description: 'Employee rejoin after resignation approval chain' }
            ];

            // Fetch all request types including custom ones
            fetch('./includes/approval_chain_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'get_all_request_types' })
            })
            .then(response => response.json())
            .then(data => {
                const requestTypes = data.success && Array.isArray(data.types) ? data.types : defaultRequestTypes;
                
                // Filter out request types you want to skip from the UI
                const skipRequestTypes = ['smart_request', 'general_request']; // Add any request types to skip
                const filteredTypes = requestTypes.filter(type => !skipRequestTypes.includes(type.id));
                
                renderApprovalChainUI(filteredTypes);
            })
            .catch(error => {
                console.error('Error loading request types:', error);
                renderApprovalChainUI(defaultRequestTypes);
            });
        }

        function renderApprovalChainUI(requestTypes) {
            let formHtml = `<div class="tab-pane active" id="group-approval" role="tabpanel">`;
            formHtml += `<div class="d-flex justify-content-between align-items-center mb-3">`;
            formHtml += `<h5 class="mb-0">Approval Chain Configuration</h5>`;
            formHtml += `<button type="button" class="btn btn-sm btn-success" id="btn-add-request-type"><i class="mdi mdi-plus"></i> Add New Request Type</button>`;
            formHtml += `</div>`;
            formHtml += `<p class="text-muted mb-4">Configure the approval workflow steps for each request type. Drag to reorder approval levels.</p>`;

            requestTypes.forEach(requestType => {
                formHtml += `
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="mdi mdi-check-circle-outline mr-2"></i>${requestType.name}
                                <small class="text-muted ml-2">${requestType.description}</small>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Approval Steps (in order)</label>
                                <div id="approval-chain-${requestType.id}" class="approval-chain-container border rounded p-3 bg-light">
                                    <div class="text-center text-muted">
                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                        <span class="ml-2">Loading...</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-approver-btn" data-request-type="${requestType.id}">
                                    <i class="mdi mdi-plus"></i> Add Approver
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            formHtml += `</div>`;
            settingsContainer.innerHTML = formHtml;

            // Load approval chains for each request type
            requestTypes.forEach(requestType => {
                loadApprovalChain(requestType.id);
            });

            // Attach event listeners for "Add Approver" buttons
            document.querySelectorAll('.add-approver-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestType = this.dataset.requestType;
                    showAddApproverModal(requestType);
                });
            });

            // Attach event listener for "Add New Request Type" button
            const btnAddRequestType = document.getElementById('btn-add-request-type');
            if (btnAddRequestType) {
                btnAddRequestType.addEventListener('click', showAddNewRequestTypeModal);
            }
        }

        async function loadApprovalChain(requestType) {
            try {
                const response = await fetch('./includes/approval_chain_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'get_approval_chain', 
                        request_type: requestType 
                    })
                });

                if (!response.ok) throw new Error('Failed to load approval chain');
                const data = await response.json();

                const container = document.getElementById(`approval-chain-${requestType}`);
                if (!data.success || !data.chain || data.chain.length === 0) {
                    container.innerHTML = '<p class="text-muted mb-0"><i class="mdi mdi-information-outline"></i> No approval steps configured yet.</p>';
                    return;
                }

                let chainHtml = '<div class="approval-steps">';
                data.chain.forEach((step, index) => {
                    chainHtml += `
                        <div class="approval-step d-flex align-items-center justify-content-between p-2 mb-2 bg-white border rounded" data-level="${step.level}" data-role="${step.user_type}">
                            <div class="d-flex align-items-center">
                                <span class="badge badge-primary mr-2">Level ${step.level}</span>
                                <span class="font-weight-bold">${step.role_label || step.user_type}</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-approver-btn" data-request-type="${requestType}" data-level="${step.level}">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </div>
                    `;
                });
                chainHtml += '</div>';
                container.innerHTML = chainHtml;

                // Attach remove button listeners
                container.querySelectorAll('.remove-approver-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        removeApprovalStep(this.dataset.requestType, this.dataset.level);
                    });
                });

            } catch (error) {
                console.error('Error loading approval chain:', error);
                const container = document.getElementById(`approval-chain-${requestType}`);
                container.innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> Error: ${error.message}</p>`;
            }
        }

        function showAddApproverModal(requestType) {
            Swal.fire({
                icon: 'info',
                title: 'Add Approver',
                html: `
                    <div class="form-group text-left">
                        <label for="approver-role">Select Approver Role</label>
                        <select id="approver-role" class="form-control">
                            <option value="">-- Select Role --</option>
                            <option value="administrator">Administrator</option>
                            <option value="gm">General Manager (GM)</option>
                            <option value="hr_senior_bp">HR Senior BP</option>
                            <option value="hr_operations">HR Operations</option>
                            <option value="hr_supervisor">HR Supervisor</option>
                            <option value="hr_recruitment">HR Recruitment</option>
                            <option value="hr_payroll">HR Payroll</option>
                            <option value="hr">HR Manager</option>
                            <option value="finance_officer">Finance Officer</option>
                            <option value="finance">Finance Manager</option>
                            <option value="auditor">Auditor</option>
                            <option value="gr_officer">GR Officer</option>
                            <option value="it">IT Manager</option>
                            <option value="dept_user">Department User</option>
                            <option value="assistant">Assistant</option>
                            <option value="direct_supervisor">Direct Supervisor</option>
                            <option value="dept_manager">Department Manager</option>
                            <option value="admin_manager">Admin Manager</option>
                            <option value="transportation_manager">Transportation Manager</option>
                        </select>
                    </div>
                `,
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: 'Add',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const role = document.getElementById('approver-role').value;
                    if (!role) {
                        Swal.showValidationMessage('Please select a role');
                        return false;
                    }
                    return { role };
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await addApprovalStep(requestType, result.value.role);
                }
            });
        }

        async function addApprovalStep(requestType, userType) {
            try {
                const response = await fetch('./includes/approval_chain_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'add_approval_step', 
                        request_type: requestType,
                        user_type: userType
                    })
                });

                if (!response.ok) throw new Error('Failed to add approval step');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('Added!', 'Approval step added successfully', 'success');
                    loadApprovalChain(requestType); // Reload the chain
                } else {
                    throw new Error(data.message || 'Failed to add approval step');
                }
            } catch (error) {
                Swal.fire('Error!', error.message, 'error');
            }
        }

        async function removeApprovalStep(requestType, level) {
            const result = await Swal.fire({
                title: 'Remove Approval Step?',
                text: 'This will remove this approval level from the chain',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove it',
                cancelButtonText: 'Cancel'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch('./includes/approval_chain_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'remove_approval_step', 
                        request_type: requestType,
                        level: level
                    })
                });

                if (!response.ok) throw new Error('Failed to remove approval step');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('Removed!', 'Approval step removed successfully', 'success');
                    loadApprovalChain(requestType); // Reload the chain
                } else {
                    throw new Error(data.message || 'Failed to remove approval step');
                }
            } catch (error) {
                Swal.fire('Error!', error.message, 'error');
            }
        }

        async function showAddNewRequestTypeModal() {
            const result = await Swal.fire({
                icon: 'info',
                title: 'Add New Request Type',
                html: `
                    <div class="text-left">
                        <div class="form-group">
                            <label for="new-request-type-id">Request Type ID <small class="text-danger">(lowercase, underscores)</small></label>
                            <input type="text" id="new-request-type-id" class="form-control" placeholder="e.g., travel_request, business_trip" pattern="[a-z_]+" title="Use lowercase letters and underscores only">
                        </div>
                        <div class="form-group">
                            <label for="new-request-type-name">Request Type Name</label>
                            <input type="text" id="new-request-type-name" class="form-control" placeholder="e.g., Travel Request">
                        </div>
                        <div class="form-group">
                            <label for="new-main-table-name">Main Table Name <small class="text-muted">(optional)</small></label>
                            <input type="text" id="new-main-table-name" class="form-control" placeholder="e.g., travel_requests">
                        </div>
                        <div class="form-group">
                            <label for="new-request-type-description">Description</label>
                            <textarea id="new-request-type-description" class="form-control" rows="2" placeholder="Brief description of this request type"></textarea>
                        </div>
                    </div>
                `,
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: 'Create',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const id = document.getElementById('new-request-type-id').value.trim().toLowerCase();
                    const name = document.getElementById('new-request-type-name').value.trim();
                    const mainTable = document.getElementById('new-main-table-name').value.trim();
                    const description = document.getElementById('new-request-type-description').value.trim();

                    if (!id) {
                        Swal.showValidationMessage('Request Type ID is required');
                        return false;
                    }
                    if (!name) {
                        Swal.showValidationMessage('Request Type Name is required');
                        return false;
                    }
                    if (!/^[a-z_]+$/.test(id)) {
                        Swal.showValidationMessage('Request Type ID must contain only lowercase letters and underscores');
                        return false;
                    }
                    return { id, name, mainTable, description };
                }
            });

            if (result.isConfirmed) {
                await addNewRequestType(result.value.id, result.value.name, result.value.mainTable, result.value.description);
            }
        }

        async function addNewRequestType(requestTypeId, requestTypeName, mainTableName, description) {
            try {
                const response = await fetch('./includes/approval_chain_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'create_new_request_type',
                        request_type_id: requestTypeId,
                        request_type_name: requestTypeName,
                        main_table_name: mainTableName || '',
                        request_type_description: description
                    })
                });

                if (!response.ok) throw new Error('Failed to create request type');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('Created!', `New request type "${requestTypeName}" has been added successfully. You can now configure its approval chain.`, 'success')
                        .then(() => {
                            renderApprovalChainSettings(); // Reload the approval chain settings
                        });
                } else {
                    throw new Error(data.message || 'Failed to create request type');
                }
            } catch (error) {
                Swal.fire('Error!', error.message, 'error');
            }
        }

        function attachPreviewListeners() {
            appSettings.forEach(setting => {
                const isImagePath = setting.setting_name.includes('logo') || setting.setting_name.includes('favicon');
                if (isImagePath) {
                    const inputEl = document.getElementById(`setting-${setting.setting_name}`);
                    const previewEl = document.getElementById(`preview-${setting.setting_name}`);
                    if (inputEl && previewEl) {
                        inputEl.addEventListener('change', (e) => {
                            const file = e.target.files[0];
                            if (file) {
                                previewEl.src = URL.createObjectURL(file);
                            }
                        });
                        previewEl.addEventListener('error', () => {
                            previewEl.src = 'assets/images/placeholder.png';
                        });
                    }
                }
            });
        }

        function attachEmailListListeners() {
            const addEmailBtn = document.getElementById('add-email-btn');
            if (addEmailBtn) {
                addEmailBtn.addEventListener('click', function() {
                    const container = document.getElementById('email-list-container');
                    const newEmailHtml = `
                        <div class="email-item mb-2">
                            <div class="input-group">
                                <input type="email" class="form-control email-input" placeholder="email@example.com" value="">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-danger remove-email-btn"><i class="mdi mdi-delete"></i></button>
                                </div>
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', newEmailHtml);
                    attachRemoveEmailListeners();
                    updateEmailListHiddenField();
                });
            }
            
            attachRemoveEmailListeners();
            updateEmailListHiddenField();
        }

        function attachRemoveEmailListeners() {
            document.querySelectorAll('.remove-email-btn').forEach(btn => {
                btn.replaceWith(btn.cloneNode(true)); // Remove old listeners
            });
            
            document.querySelectorAll('.remove-email-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const emailItems = document.querySelectorAll('.email-item');
                    if (emailItems.length > 1) {
                        this.closest('.email-item').remove();
                        updateEmailListHiddenField();
                    } else {
                        Swal.fire('Notice', 'At least one email field must remain', 'info');
                    }
                });
            });

            // Update hidden field on email input change
            document.querySelectorAll('.email-input').forEach(input => {
                input.removeEventListener('input', updateEmailListHiddenField);
                input.addEventListener('input', updateEmailListHiddenField);
            });
        }

        function updateEmailListHiddenField() {
            const emailInputs = document.querySelectorAll('.email-input');
            const emails = Array.from(emailInputs)
                .map(input => input.value.trim())
                .filter(email => email !== '');
            
            const hiddenField = document.getElementById('setting-traveling_company_email');
            if (hiddenField) {
                hiddenField.value = JSON.stringify(emails);
            }
        }

        async function loadSettings() {
            try {
                const response = await fetch('./includes/settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_settings' })
                });
                if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
                
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Failed to retrieve settings.');

                appSettings = data.settings;
                groupedSettings = appSettings.reduce((acc, setting) => {
                    const group = setting.setting_group;
                    if (!acc[group]) acc[group] = [];
                    acc[group].push(setting);
                    return acc;
                }, {});

                // Restore last active group from localStorage if available
                const savedGroup = localStorage.getItem('app_settings_active_group');
                const groups = Object.keys(groupedSettings);

                let navHtml = '';
                groups.forEach((group) => {
                    const isActive = (savedGroup === group);
                    navHtml += `
                        <li class="nav-item">
                            <a class="nav-link ${isActive ? 'active' : ''}" data-toggle="pill" href="#group-${group}" role="tab" data-group="${group}">
                                <span class="text-capitalize">${group}</span>
                            </a>
                        </li>
                    `;
                });
                settingsNav.innerHTML = navHtml;

                // Determine initial group to render
                const initialGroup = (savedGroup && groups.includes(savedGroup)) ? savedGroup : groups[0];
                if(initialGroup) {
                    renderSettingsGroup(initialGroup);
                } else {
                    settingsContainer.innerHTML = '<p class="text-center">No settings found.</p>';
                }

                // Click handlers: render and persist active group, update nav active class
                settingsNav.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const group = link.dataset.group;
                        renderSettingsGroup(group);
                        localStorage.setItem('app_settings_active_group', group);
                        // Toggle active class on nav links
                        settingsNav.querySelectorAll('a').forEach(a => a.classList.remove('active'));
                        link.classList.add('active');
                    });
                });

            } catch (error) {
                settingsContainer.innerHTML = `<p class="text-danger text-center">${error.message}</p>`;
                Swal.fire('Error!', `Could not load settings: ${error.message}`, 'error');
            }
        }

        settingsForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            // Validate email list before submitting
            const emailInputs = document.querySelectorAll('.email-input');
            let hasInvalidEmail = false;
            emailInputs.forEach(input => {
                if (input.value.trim() !== '' && !input.checkValidity()) {
                    hasInvalidEmail = true;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (hasInvalidEmail) {
                Swal.fire('Validation Error', 'Please enter valid email addresses', 'error');
                return;
            }
            
            // Update hidden field one more time before submission
            updateEmailListHiddenField();
            
            const formData = new FormData();
            formData.append('action', 'update_settings');

            appSettings.forEach(setting => {
                const element = document.getElementById(`setting-${setting.setting_name}`);
                if (element) {
                    const isImagePath = setting.setting_name.includes('logo') || setting.setting_name.includes('favicon');
                    if (isImagePath) {
                        if (element.files.length > 0) {
                            formData.append(setting.setting_name, element.files[0]);
                        }
                    } else {
                        // Simplified logic: this works for both standard inputs and select2.
                        formData.append(setting.setting_name, element.value);
                    }
                }
            });

            Swal.fire({
                title: 'Saving...',
                text: 'Your settings are being updated.',
                allowOutsideClick: false,
                onBeforeOpen: () => { Swal.showLoading(); }
            });

            try {
                const response = await fetch('./includes/settings_handler.php', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) throw new Error(await response.text());
                
                const result = await response.json();
                Swal.close();

                if (result.success) {
                    Swal.fire('Saved!', 'Your settings have been updated successfully.', 'success')
                        .then(() => window.location.reload());
                } else {
                    Swal.fire('Error!', result.message || 'Could not save settings.', 'error');
                }

            } catch (error) {
                Swal.close();
                Swal.fire('Request Failed!', `An error occurred: ${error.message}`, 'error');
            }
        });

        loadSettings();
    });
    </script>

</body>
</html>