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
    <link href="./plugins/sweet-alert/sweetalert2.min.css" rel="stylesheet" type="text/css" />

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
    <script src="./plugins/sweet-alert/sweetalert2.min.js"></script>

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

            formHtml += `<div class="tab-pane active" id="group-${groupName}" role="tabpanel">`;
            settings.forEach(setting => {
                const id = `setting-${setting.setting_name}`;
                const label = setting.description;
                const isImagePath = setting.setting_name.includes('logo') || setting.setting_name.includes('favicon');

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

                let navHtml = '';
                Object.keys(groupedSettings).forEach((group, index) => {
                    navHtml += `
                        <li class="nav-item">
                            <a class="nav-link ${index === 0 ? 'active' : ''}" data-toggle="pill" href="#group-${group}" role="tab" data-group="${group}">
                                <span class="text-capitalize">${group}</span>
                            </a>
                        </li>
                    `;
                });
                settingsNav.innerHTML = navHtml;

                const firstGroup = Object.keys(groupedSettings)[0];
                if(firstGroup) {
                    renderSettingsGroup(firstGroup);
                } else {
                    settingsContainer.innerHTML = '<p class="text-center">No settings found.</p>';
                }

                settingsNav.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        renderSettingsGroup(link.dataset.group);
                        // Bootstrap's tab functionality is handled by data-toggle="pill"
                        // but we ensure the correct content is loaded before the tab shows.
                    });
                });

            } catch (error) {
                settingsContainer.innerHTML = `<p class="text-danger text-center">${error.message}</p>`;
                Swal.fire('Error!', `Could not load settings: ${error.message}`, 'error');
            }
        }

        settingsForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
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