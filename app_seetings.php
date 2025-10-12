<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .loader {
            border: 4px solid #f3f3f3;
            border-radius: 50%;
            border-top: 4px solid #3B82F6;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .select2-container--default .select2-selection--single {
            height: 2.625rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 2.5rem;
            padding-left: 1rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 2.5rem;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <div class="container mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Application Settings</h1>
            <p class="text-gray-500 mt-1">Manage your application's configuration.</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Sidebar Navigation -->
            <aside class="w-full lg:w-1/4">
                <div class="bg-white p-4 rounded-xl shadow-sm">
                    <h2 class="text-lg font-semibold mb-4 text-gray-700">Navigation</h2>
                    <ul id="settings-nav" class="space-y-2">
                         <!-- Navigation items will be injected here -->
                    </ul>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="w-full lg:w-3/4">
                <form id="settingsForm" class="bg-white rounded-xl shadow-sm">
                    <div id="settings-container" class="p-6 md:p-8">
                        <!-- Settings fields will be injected here -->
                        <div class="flex justify-center items-center h-64">
                            <div class="loader"></div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 md:px-8 py-4 border-t border-gray-200 rounded-b-xl flex justify-end">
                        <button type="submit" id="saveBtn" class="bg-blue-600 text-white font-semibold px-6 py-2.5 rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                            Save Changes
                        </button>
                    </div>
                </form>
            </main>

        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let appSettings = [];
        let groupedSettings = {};
        const settingsContainer = document.getElementById('settings-container');
        const settingsNav = document.getElementById('settings-nav');
        const settingsForm = document.getElementById('settingsForm');

        const groupIcons = {
            'general': 'fa-solid fa-sliders',
            'localization': 'fa-solid fa-language',
            'developer': 'fa-solid fa-code',
            'email': 'fa-solid fa-envelope',
            'social': 'fa-solid fa-share-nodes',
            'api': 'fa-solid fa-key'
        };

        function renderSettingsGroup(groupName) {
            let formHtml = '';
            const settings = groupedSettings[groupName];
            
            if (!settings) {
                 settingsContainer.innerHTML = '<p class="text-center text-red-500">Group not found.</p>';
                 return;
            }

            formHtml += `<div class="space-y-6">`;
            settings.forEach(setting => {
                const id = `setting-${setting.setting_name}`;
                const label = setting.description;
                const isImagePath = setting.setting_name.includes('logo') || setting.setting_name.includes('favicon');

                formHtml += `<div>`;
                formHtml += `<label for="${id}" class="block text-sm font-medium text-gray-700 mb-1">${label}</label>`;
                
                if (isImagePath) {
                    formHtml += `<div class="flex items-center gap-4 mt-2">`;
                    formHtml += `<img id="preview-${setting.setting_name}" src="${setting.setting_value || 'https://placehold.co/100x40/f0f0f0/ccc?text=Preview'}" alt="Preview" class="h-12 w-auto bg-gray-100 border border-gray-200 rounded-md p-1 object-contain">`;
                    formHtml += `<div class="flex-grow">`;
                    formHtml += `<input type="file" id="${id}" name="${setting.setting_name}" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"/>`;
                    formHtml += `<p class="text-xs text-gray-500 mt-1">Current: <span class="font-mono">${setting.setting_value || 'Not set'}</span></p>`;
                    formHtml += `</div></div>`;
                } else {
                    let inputHtml = '';
                    switch (setting.input_type) {
                        case 'select':
                            let options = JSON.parse(setting.options || '{}');
                            inputHtml = `<select id="${id}" name="${setting.setting_name}" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">`;
                            for (const [value, text] of Object.entries(options)) {
                                inputHtml += `<option value="${value}" ${setting.setting_value == value ? 'selected' : ''}>${text}</option>`;
                            }
                            inputHtml += `</select>`;
                            break;
                        default:
                            inputHtml = `<input id="${id}" name="${setting.setting_name}" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="${setting.setting_value || ''}">`;
                            break;
                    }
                    formHtml += inputHtml;
                }
                
                formHtml += `</div>`;
            });
            formHtml += `</div>`;
            settingsContainer.innerHTML = formHtml;
            
            // Re-initialize Select2 if the timezone field is in the current group
            if (groupedSettings[groupName].some(s => s.setting_name === 'timezone')) {
                 $('#setting-timezone').select2({ width: '100%' });
            }

            // Re-attach event listeners for image previews
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
                            previewEl.src = 'https://placehold.co/100x40/f0f0f0/ccc?text=Invalid';
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
                    const iconClass = groupIcons[group] || 'fa-solid fa-cog';
                    navHtml += `
                        <li>
                            <a href="#" data-group="${group}" class="${index === 0 ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'} group flex items-center px-3 py-2 text-sm font-medium rounded-md">
                                <i class="${iconClass} text-gray-400 group-hover:text-gray-500 mr-3 flex-shrink-0 h-6 w-6 text-lg"></i>
                                <span class="truncate capitalize">${group}</span>
                            </a>
                        </li>
                    `;
                });
                settingsNav.innerHTML = navHtml;

                // Render the first group by default
                const firstGroup = Object.keys(groupedSettings)[0];
                if(firstGroup) {
                    renderSettingsGroup(firstGroup);
                } else {
                    settingsContainer.innerHTML = '<p class="text-center">No settings found.</p>';
                }

                // Add click handlers for navigation
                settingsNav.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        
                        settingsNav.querySelectorAll('a').forEach(l => l.classList.remove('bg-blue-50', 'text-blue-600'));
                        link.classList.add('bg-blue-50', 'text-blue-600');
                        
                        renderSettingsGroup(link.dataset.group);
                    });
                });

            } catch (error) {
                settingsContainer.innerHTML = `<p class="text-red-500 text-center">${error.message}</p>`;
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
                    } else if (setting.setting_name === 'timezone') {
                        formData.append(setting.setting_name, $('#setting-timezone').val());
                    } else {
                        formData.append(setting.setting_name, element.value);
                    }
                }
            });

            const savingToast = Swal.fire({
                title: 'Saving...',
                text: 'Your settings are being updated.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch('./includes/settings_handler.php', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) throw new Error(await response.text());
                
                const result = await response.json();
                savingToast.close();

                if (result.success) {
                    Swal.fire('Saved!', 'Your settings have been updated successfully.', 'success')
                        .then(() => window.location.reload());
                } else {
                    Swal.fire('Error!', result.message || 'Could not save settings.', 'error');
                }

            } catch (error) {
                savingToast.close();
                Swal.fire('Request Failed!', `An error occurred: ${error.message}`, 'error');
            }
        });

        loadSettings();
    });
    </script>

</body>
</html>