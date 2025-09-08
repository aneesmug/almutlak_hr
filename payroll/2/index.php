<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Payroll Management</title>
    <!-- Tailwind CSS CDN -->
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <!-- <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        inter: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script> -->
    <!-- DataTables CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        /* .container {
            max-width: 1200px;
        } */
        .shadow-custom {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        .btn-primary {
            background-image: linear-gradient(to right, #6366f1, #8b5cf6);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-image: linear-gradient(to right, #4f46e5, #7c3aed);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
        }
        .btn-secondary {
            background-color: #e2e8f0;
            color: #475569;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(160, 174, 192, 0.3);
        }
        .btn btn-info {
            background-color: #34d399;
            transition: all 0.3s ease;
        }
        .btn btn-info:hover {
            background-color: #10b981;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(52, 211, 153, 0.3);
        }
        .btn-delete {
            background-color: #ef4444;
            transition: all 0.3s ease;
        }
        .btn-delete:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }
        /* DataTables customization */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d1d5db;
            padding: 0.5rem;
            border-radius: 0.375rem;
        }
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d1d5db;
            padding: 0.5rem;
            border-radius: 0.375rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            margin-left: 0.25rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #6366f1;
            color: white !important;
            border: 1px solid #6366f1;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e0e7ff;
            color: #4f46e5 !important;
        }
        /* Payroll details modal styles */
        .item-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .name-input {
            flex: 2;
            min-width: 150px;
        }
        .amount-input {
            width: 100px;
        }
        /* Styles for the print report modal */
        #payrollReportModal table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        #payrollReportModal th, #payrollReportModal td {
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
            text-align: left;
            font-size: 0.875rem;
        }
        #payrollReportModal th {
            background-color: #f8fafc;
        }
        #payrollReportModal .total-row {
            font-weight: bold;
            background-color: #e0f2fe; /* Light blue */
        }

        /* --- PRINT STYLES --- */
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: none; /* Remove background in print */
            }
            /* Hide everything normally on the page */
            body > *:not(.swal2-container) {
                display: none !important;
                visibility: hidden !important;
            }

            /* Make the SweetAlert2 container and its direct children visible */
            .swal2-container {
                position: static !important; /* Remove fixed positioning for print flow */
                width: auto !important;
                height: auto !important;
                overflow: visible !important;
                display: block !important;
                visibility: visible !important;
                background-color: white !important; /* Ensure white background */
                box-shadow: none !important;
            }

            .swal2-popup {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                position: static !important;
                display: block !important;
                visibility: visible !important;
                top: auto !important; /* Override SweetAlert's inline styles */
                left: auto !important;
                transform: none !important;
            }

            /* Make header, content, and actions visible within the popup */
            .swal2-header,
            .swal2-title,
            .swal2-html-container,
            .swal2-actions,
            #payrollReportModal { /* Ensure the specific report modal content is visible */
                visibility: visible !important;
                display: block !important;
                width: auto !important;
                height: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 1rem !important; /* Add some padding back for print */
                box-sizing: border-box;
            }
            
            /* Hide the "Close" button and the "Print Report" button */
            .swal2-cancel,
            #payrollReportModal .print-button-container {
                display: none !important;
                visibility: hidden !important;
            }

            /* Ensure table is fully visible */
            #payrollReportModal table,
            #payrollReportModal th,
            #payrollReportModal td {
                visibility: visible !important;
                display: table-cell !important; /* Ensure table elements are displayed correctly */
                page-break-inside: avoid; /* Prevent breaking inside table rows */
            }
            #payrollReportModal thead {
                display: table-header-group; /* Repeat table header on each page */
            }
            #payrollReportModal tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            /* Optional: Adjust font sizes for print if needed */
            #payrollReportModal {
                font-size: 10pt;
            }
            #payrollReportModal h2 {
                font-size: 14pt;
            }
        }
    </style> -->
</head>
<body>
    <div class="container my-5">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 px-md-5">
                 <h1 class="display-5 fw-bold text-center text-body-secondary mb-0">Employee Payroll Management</h1>
            </div>
            <div class="card-body p-4 p-md-5">
                <!-- Controls Section -->
                <div class="card bg-light border-light-subtle mb-4 p-3 rounded-3">
                    <div class="row g-3 align-items-end">
                        <!-- Month Selector -->
                        <div class="col-lg-3 col-md-6">
                            <label for="payrollMonth" class="form-label fw-medium">Select Month</label>
                            <input type="month" id="payrollMonth" class="form-control">
                        </div>

                        <!-- Department Filter -->
                        <div class="col-lg-3 col-md-6">
                            <label for="departmentFilter" class="form-label fw-medium">Filter by Department</label>
                            <select id="departmentFilter" class="form-select">
                                <option value="" selected>All Departments</option>
                                <!-- Department options will be populated here by JavaScript -->
                            </select>
                        </div>

                        <!-- Spacer Column -->
                        <div class="col-lg"></div>

                        <!-- Action Buttons -->
                        <div class="col-lg-auto">
                            <div class="d-grid d-md-flex gap-2">
                                <button id="generatePayrollBtn" class="btn btn-primary">
                                    Generate Payroll for Selected
                                </button>
                                <button id="generateReportBtn" class="btn btn-outline-secondary">
                                    Generate Payroll Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="table-responsive">
                    <table id="employeeTable" class="table table-striped table-hover align-middle w-100">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-center" style="width: 10px;">
                                    <input class="form-check-input" type="checkbox" id="selectAllEmployees">
                                </th>
                                <th scope="col" style="width: 100px;">Employee ID</th>
                                <th scope="col">Name</th>
                                <th scope="col" style="width: 230px;">Department</th>
                                <th scope="col" style="width: 200px;">Salary</th>
                                <th scope="col" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded by DataTables or JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this to your <head> or before your script -->
<script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
<!-- jsPDF and jspdf-autotable for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script>
        let employeeTable; // DataTables instance
        let allEmployeesData = []; // Store raw employee data fetched from API
        let currentEventListeners = []; // Array to store cleanup functions for event listeners

        $(document).ready(function() {
            // Set default month to current month
            window.today = new Date();  // Makes it globally available
            $('#payrollMonth').val(`${getDateParts(today, 'year')}-${getDateParts(today, 'month')}`);
            // Initialize DataTable
            initializeDataTable();
            // Fetch employees on initial load and when month changes
            $('#payrollMonth').on('change', fetchEmployees);
            fetchEmployees();
            // Add event listener for the new report button
            $('#generateReportBtn').off('click').on('click', generatePayrollReport);
        });

        function initializeDataTable() {
            employeeTable = $('#employeeTable').DataTable({
                columns: [
                    { 
                        data: null,
                        orderable: false,
                        className: 'select-checkbox',
                        render: function(data, type, row) {
                            // Check if payroll is generated for the current month
                            // The `payroll_status` comes from the get_employees.php API response
                            const isPayrollGenerated = row.payroll_status && (row.payroll_status === 'generated' || row.payroll_status === 'paid');
                            if (isPayrollGenerated) {
                                return '<span class="text-green-600 font-semibold">Generated</span>';
                            }
                            return `<input type="checkbox" class="employee-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" data-emp-id="${row.emp_id}">`;
                        }
                    },
                    { data: 'emp_id' },
                    { data: 'name' },
                    { data: 'department_name' }, // Use 'dept' field for department name
                    { 
                        data: 'salary',
                        render: function(data, type, row) {
                            return `SAR ${parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                                <button class="btn btn-info  view-edit-btn" 
                                        data-emp-id="${row.emp_id}" data-emp-name="${row.name}">
                                    Edit
                                </button>
                            `;
                        }
                    }
                ],
                order: [[2, 'asc']], // Sort by Name by default
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100, 500],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search employees...",
                    lengthMenu: "Show _MENU_ employees per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ employees",
                    infoEmpty: "No employees found",
                    infoFiltered: "(filtered from _MAX_ total employees)"
                },
                dom: '<"flex justify-between items-center mb-4"lf>rt<"flex justify-between items-center mt-4"ip>',
                // The `initComplete` function is crucial for attaching event listeners after DataTables has drawn the table.
                initComplete: function() {
                    addEventListeners(); // Initial attachment of listeners
                }
            });
        }

        async function fetchEmployees() {
            const loadingIndicator = $('#loadingIndicator');
            const noDataMessage = $('#noDataMessage');
            const selectedMonth = $('#payrollMonth').val();
            loadingIndicator.removeClass('hidden');
            noDataMessage.addClass('hidden');
            try {
                // Ensure this path is correct for your server setup
                const response = await fetch(`api/get_employees.php?month=${selectedMonth}`);
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Server responded with status ${response.status}: ${errorText}`);
                }
                const data = await response.json();
                if (data.status === 'success') {
                    allEmployeesData = data.employees;
                    // Clear existing DataTables rows and add new ones
                    employeeTable.clear().rows.add(allEmployeesData).draw();
                    populateDepartmentFilter(allEmployeesData);
                    addEventListeners(); // Re-attach listeners after data update
                } else {
                    showError('Error', data.message || 'Failed to load employee data.');
                    employeeTable.clear().draw(); // Clear table on error
                }
            } catch (error) {
                console.error('Error fetching employees:', error);
                showError('Network Error', `Error connecting to the server or parsing data: ${error.message}.`);
                employeeTable.clear().draw(); // Clear table on network error
            } finally {
                loadingIndicator.addClass('hidden');
                if (allEmployeesData.length === 0 && noDataMessage.hasClass('hidden')) {
                     // Only show no data message if there truly is no data after fetch
                    noDataMessage.removeClass('hidden').text('No employee data available for the selected month/filters.');
                }
            }
        }

        function populateDepartmentFilter(employees) {
            const departmentFilter = $('#departmentFilter');
            const currentSelectedDept = departmentFilter.val(); // Remember current selection
            departmentFilter.empty().append('<option value="">All Departments</option>');
            const departments = new Set();
            employees.forEach(emp => {
                if (emp.dept) {
                    departments.add(emp.department_name);
                }
            });
            const sortedDepartments = Array.from(departments).sort();
            sortedDepartments.forEach(department_name => {
                departmentFilter.append(`<option value="${department_name}">${department_name}</option>`);
            });
            // Restore previous selection if it still exists
            if (sortedDepartments.includes(currentSelectedDept)) {
                departmentFilter.val(currentSelectedDept);
            } else {
                departmentFilter.val(''); // Reset to All if previous selection is gone
            }
            // Unbind and rebind change event for department filter
            departmentFilter.off('change').on('change', function() {
                const selectedDept = $(this).val();
                // DataTables column search works on the raw data of the column
                // In our setup, 'dept' is the 4th column (index 3, 0-indexed)
                employeeTable.column(3).search(selectedDept ? `^${selectedDept}$` : '', true, false).draw();
                // Update main select all checkbox for currently visible rows
                updateMainSelectAllCheckbox();
            });
        }

        function addEventListeners() {
            // It's crucial to remove previous event listeners before re-adding them
            // to prevent multiple bindings and unexpected behavior, especially with DataTables.
            // Clear previously stored cleanup functions
            currentEventListeners.forEach(cleanup => cleanup());
            currentEventListeners = [];
            // Select all checkbox
            const selectAllHandler = function() {
                const isChecked = $(this).prop('checked');
                // Select only visible and non-generated/selectable checkboxes
                employeeTable.rows({ page: 'current' }).nodes().to$().find('.employee-checkbox:not(:disabled)').prop('checked', isChecked);
                updateMainSelectAllCheckbox();
            };
            $('#selectAllEmployees').off('change', selectAllHandler).on('change', selectAllHandler);
            currentEventListeners.push(() => $('#selectAllEmployees').off('change', selectAllHandler));
            // Individual employee checkbox (delegated using jQuery on)
            const employeeCheckboxHandler = function() {
                updateMainSelectAllCheckbox();
            };
            $('#employeeTable').off('change', '.employee-checkbox', employeeCheckboxHandler).on('change', '.employee-checkbox', employeeCheckboxHandler);
            currentEventListeners.push(() => $('#employeeTable').off('change', '.employee-checkbox', employeeCheckboxHandler));
            // View/Edit Payroll button (delegated using jQuery on)
            const viewEditBtnHandler = function() {
                const empId = $(this).data('emp-id');
                const empName = $(this).data('emp-name');
                const month = $('#payrollMonth').val();
                showPayrollDetails(empId, empName, month);
            };
            $('#employeeTable').off('click', '.view-edit-btn', viewEditBtnHandler).on('click', '.view-edit-btn', viewEditBtnHandler);
            currentEventListeners.push(() => $('#employeeTable').off('click', '.view-edit-btn', viewEditBtnHandler));
            // Generate Payroll button
            $('#generatePayrollBtn').off('click', generatePayroll).on('click', generatePayroll);
            currentEventListeners.push(() => $('#generatePayrollBtn').off('click', generatePayroll));

            // New: Generate Payroll Report button
            $('#generateReportBtn').off('click', generatePayrollReport).on('click', generatePayrollReport);
            currentEventListeners.push(() => $('#generateReportBtn').off('click', generatePayrollReport));

            // Handle delete buttons within the SweetAlert2 modal for benefits
            // These event listeners need to be attached dynamically *after* the SweetAlert2 modal is opened.
            // This is handled within the `showPayrollDetails` function's `didOpen` callback.
        }

        // Updates the main "Select All" checkbox based on individual employee checkboxes
        function updateMainSelectAllCheckbox() {
            const selectAllMain = $('#selectAllEmployees');
            // Get only the checkboxes that are currently visible (on the current page of DataTables)
            // and are not disabled (i.e., not already generated payrolls)
            const visibleSelectableCheckboxes = employeeTable.rows({ page: 'current' }).nodes().to$().find('.employee-checkbox:not(:disabled)');
            const checkedVisibleCheckboxes = visibleSelectableCheckboxes.filter(':checked');

            if (visibleSelectableCheckboxes.length === 0) {
                selectAllMain.prop('checked', false).prop('indeterminate', false);
            } else if (checkedVisibleCheckboxes.length === visibleSelectableCheckboxes.length) {
                selectAllMain.prop('checked', true).prop('indeterminate', false);
            } else if (checkedVisibleCheckboxes.length > 0) {
                selectAllMain.prop('checked', false).prop('indeterminate', true);
            } else {
                selectAllMain.prop('checked', false).prop('indeterminate', false);
            }
        }
        
        async function generatePayroll() {
            const selectedEmployees = employeeTable.rows().nodes().to$().find('.employee-checkbox:checked').map(function() {
                return $(this).data('emp-id');
            }).get();
            
            const payrollMonth = $('#payrollMonth').val();

            if (selectedEmployees.length === 0) {
                showWarning('No Employees Selected', 'Please select at least one employee to generate payroll.');
                return;
            }

            if (!payrollMonth) {
                showWarning('Month Not Selected', 'Please select a payroll month.');
                return;
            }

            Swal.fire({
                title: 'Generating Payroll...',
                html: 'Please wait, this might take a moment.',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            try {
                const response = await fetch('api/process_payroll.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        employee_ids: selectedEmployees,
                        month: payrollMonth
                    }),
                });
                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Payroll Generated!',
                        text: result.message,
                        confirmButtonColor: '#6366f1'
                    });
                    fetchEmployees(); // Refresh employee list to update checkbox status
                } else {
                    throw new Error(result.message || 'An unexpected error occurred.');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error Generating Payroll', error.message);
            }
        }

        async function showPayrollDetails(empId, empName, month) {
            // Clean up previous listeners before opening new modal
            currentEventListeners.forEach(cleanup => cleanup());
            currentEventListeners = [];

            Swal.fire({
                title: `Loading Payroll for ${empName} (${empId})...`,
                html: 'Please wait.',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            try {
                const response = await fetch(`api/get_payroll_details.php?emp_id=${empId}&month=${month}`);
                const data = await response.json();

                if (data.status === 'success') {
                    const payroll = data.payroll;
                    const employee = data.employee;
                    const benefits = data.benefits;
                    let deductions = data.deductions; // Use 'let' so we can modify it
                    // --- New Logic for GOSI and House Deduction ---
                    if (employee.country === '191') { // Assuming 'country' is a property of the employee object and 191 is its value
                        // CORRECTED LINE: Parse each value to a float individually, then add them.
                        const basicPlusHousing = parseFloat(payroll.basic_salary) + parseFloat(payroll.housing_allowance);
                        const gosiAmount = (basicPlusHousing * 0.0975).toFixed(2); // Calculate 9.75% of basic + housing
                        // Check if GOSI deduction already exists to avoid duplicates
                        const gosiExists = deductions.some(d => d.deduction === 'GOSI');
                        if (!gosiExists) {
                            deductions.push({ id: null, deduction: 'GOSI', note: gosiAmount });
                        }
                    }
                    // --- End New Logic ---
                    let benefitsHtml = benefits.length > 0 ? benefits.map(b => {
                        const benefitName = b.benefit || '';
                        const benefitAmount = parseFloat(b.note || 0).toFixed(2);
                        return `
                            <div class="item-row">
                                <input type="text" class="name-input p-1 border rounded-md text-sm benefit-name" 
                                    data-benefit-id="${b.id}" value="${benefitName}" placeholder="Benefit Name">
                                <input type="number" step="0.01" class="amount-input p-1 border rounded-md text-sm benefit-amount" 
                                    data-benefit-id="${b.id}" value="${benefitAmount}" placeholder="Amount">
                                <button class="btn-delete text-white py-1 px-2 rounded-md text-xs delete-benefit-btn" 
                                    data-benefit-id="${b.id}">
                                    Delete
                                </button>
                            </div>
                        `;
                    }).join('') : '<p class="text-sm text-gray-500">No benefits recorded for this month.</p>';

                    let deductionsHtml = deductions.length > 0 ? deductions.map(d => {
                        const deductionName = d.deduction || '';
                        const deductionAmount = parseFloat(d.note || 0).toFixed(2);
                        return `
                            <div class="item-row">
                                <input type="text" class="name-input p-1 border rounded-md text-sm deduction-name" 
                                    data-deduction-id="${d.id}" value="${deductionName}" placeholder="Deduction Name">
                                <input type="number" step="0.01" class="amount-input p-1 border rounded-md text-sm deduction-amount" 
                                    data-deduction-id="${d.id}" value="${deductionAmount}" placeholder="Amount">
                                <button class="btn-delete text-white py-1 px-2 rounded-md text-xs delete-deduction-btn" 
                                    data-deduction-id="${d.id}">
                                    Delete
                                </button>
                            </div>
                        `;
                    }).join('') : '<p class="text-sm text-gray-500">No deductions recorded for this month.</p>';

                    Swal.fire({
                        title: `Payroll Details for ${employee.name}`,
                        html: `
                            <div class="text-left p-4 bg-gray-50 rounded-lg shadow-inner">
                                <h3 class="text-lg font-bold text-gray-800 mb-2">Basic Salary Components:</h3>
                                <div class="grid grid-cols-2 gap-2 text-sm text-gray-700 mb-4">
                                    <p><strong>Basic:</strong> SAR ${parseFloat(payroll.basic_salary).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                                    <p><strong>Housing:</strong> SAR ${parseFloat(payroll.housing_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                                    <p><strong>Transport:</strong> SAR ${parseFloat(payroll.transport_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                                    <p><strong>Food:</strong> SAR ${parseFloat(payroll.food_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                                    <p><strong>Miscellaneous:</strong> SAR ${parseFloat(payroll.miscellaneous_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                                    <p><strong>Cashier:</strong> SAR ${parseFloat(payroll.cashier_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                                    <p><strong>Fuel:</strong> SAR ${parseFloat(payroll.fuel_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                                    <p><strong>Telephone:</strong> SAR ${parseFloat(payroll.telephone_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                                    <p><strong>Other:</strong> SAR ${parseFloat(payroll.other_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                                    <p><strong>Guard:</strong> SAR ${parseFloat(payroll.guard_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                                </div>

                                <h3 class="text-lg font-bold text-gray-800 mb-2">Calculated Totals:</h3>
                                <p class="text-md text-gray-800 mb-2"><strong>Gross Salary:</strong> SAR ${parseFloat(payroll.total_gross_salary).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>

                                <h3 class="text-lg font-bold text-gray-800 mb-2 mt-4">Benefits:</h3>
                                <div id="benefits-list" class="space-y-2 mb-4">${benefitsHtml}</div>
                                <button id="addBenefitBtn" class="btn-secondary text-gray-800 py-1 px-3 rounded-md text-xs">Add New Benefit</button>

                                <h3 class="text-lg font-bold text-gray-800 mb-2 mt-4">Deductions:</h3>
                                <div id="deductions-list" class="space-y-2 mb-4">${deductionsHtml}</div>
                                <button id="addDeductionBtn" class="btn-secondary text-gray-800 py-1 px-3 rounded-md text-xs">Add New Deduction</button>

                                <p class="text-md font-bold text-gray-900 mt-4">Total Benefits: <span id="totalBenefitsDisplay">SAR ${parseFloat(payroll.total_benefits).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span></p>
                                <p class="text-md font-bold text-gray-900">Total Deductions: <span id="totalDeductionsDisplay">SAR ${parseFloat(payroll.total_deductions).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span></p>
                                <p class="text-xl font-extrabold text-blue-700 mt-4">Net Salary: <span id="netSalaryDisplay">SAR ${parseFloat(payroll.net_salary).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span></p>
                            </div>
                        `,
                        width: 700,
                        showCancelButton: true,
                        confirmButtonText: 'Save Changes',
                        confirmButtonColor: '#6366f1',
                        cancelButtonText: 'Close',
                        didOpen: () => {
                            const originalGrossSalary = parseFloat(payroll.total_gross_salary);
                            const updateDynamicNetSalary = () => updateNetSalaryDisplay(originalGrossSalary);

                            // Helper to attach event listeners and add to cleanup array
                            const addDynamicEventListener = (element, event, handler) => {
                                element.addEventListener(event, handler);
                                currentEventListeners.push(() => element.removeEventListener(event, handler));
                            };

                            // Add new benefit button
                            const addBenefitBtn = document.getElementById('addBenefitBtn');
                            addDynamicEventListener(addBenefitBtn, 'click', () => {
                                const benefitsList = document.getElementById('benefits-list');
                                const newBenefitDiv = document.createElement('div');
                                newBenefitDiv.classList.add('item-row');
                                newBenefitDiv.innerHTML = `
                                    <input type="text" class="name-input p-1 border rounded-md text-sm new-benefit-name" placeholder="Benefit Name">
                                    <input type="number" step="0.01" class="amount-input p-1 border rounded-md text-sm new-benefit-amount" value="0.00" placeholder="Amount">
                                    <button class="btn-delete text-white py-1 px-2 rounded-md text-xs delete-benefit-btn">Delete</button>
                                `;
                                benefitsList.appendChild(newBenefitDiv);
                                
                                const deleteBtn = newBenefitDiv.querySelector('.delete-benefit-btn');
                                addDynamicEventListener(deleteBtn, 'click', function() { $(this).closest('.item-row').remove(); updateDynamicNetSalary(); });

                                const amountInput = newBenefitDiv.querySelector('.new-benefit-amount');
                                addDynamicEventListener(amountInput, 'input', updateDynamicNetSalary);
                                
                                updateDynamicNetSalary();
                            });

                            // Add new deduction button
                            const addDeductionBtn = document.getElementById('addDeductionBtn');
                            addDynamicEventListener(addDeductionBtn, 'click', () => {
                                const deductionsList = document.getElementById('deductions-list');
                                const newDeductionDiv = document.createElement('div');
                                newDeductionDiv.classList.add('item-row');
                                newDeductionDiv.innerHTML = `
                                    <input type="text" class="name-input p-1 border rounded-md text-sm new-deduction-name" placeholder="Deduction Name">
                                    <input type="number" step="0.01" class="amount-input p-1 border rounded-md text-sm new-deduction-amount" value="0.00" placeholder="Amount">
                                    <button class="btn-delete text-white py-1 px-2 rounded-md text-xs delete-deduction-btn">Delete</button>
                                `;
                                deductionsList.appendChild(newDeductionDiv);

                                const deleteBtn = newDeductionDiv.querySelector('.delete-deduction-btn');
                                addDynamicEventListener(deleteBtn, 'click', function() { $(this).closest('.item-row').remove(); updateDynamicNetSalary(); });
                                
                                const amountInput = newDeductionDiv.querySelector('.new-deduction-amount');
                                addDynamicEventListener(amountInput, 'input', updateDynamicNetSalary);
                                updateDynamicNetSalary();
                            });

                            // Add input event listeners to recalculate totals dynamically for existing fields
                            document.querySelectorAll('.benefit-amount, .deduction-amount').forEach(input => {
                                addDynamicEventListener(input, 'input', updateDynamicNetSalary);
                            });

                            // Add event listeners for delete buttons for existing benefits/deductions
                            document.querySelectorAll('.delete-benefit-btn').forEach(btn => {
                                addDynamicEventListener(btn, 'click', async function() {
                                    const benefitId = this.dataset.benefitId;
                                    const row = this.closest('.item-row');
                                    
                                    if (!benefitId) { // For newly added, unsaved items
                                        row.remove();
                                        updateDynamicNetSalary();
                                        return;
                                    }

                                    const swalResult = await Swal.fire({
                                        title: 'Delete Benefit?',
                                        text: 'Are you sure you want to delete this benefit?',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#d33',
                                        cancelButtonColor: '#3085d6',
                                        confirmButtonText: 'Yes, delete it!'
                                    });

                                    if (swalResult.isConfirmed) {
                                        try {
                                            const response = await fetch('api/delete_benefit.php', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json' },
                                                body: JSON.stringify({ benefit_id: benefitId, emp_id: empId, month: month })
                                            });
                                            const data = await response.json();
                                            
                                            if (data.status === 'success') {
                                                row.remove();
                                                updateDynamicNetSalary();
                                                Swal.fire('Deleted!', 'The benefit has been deleted.', 'success');
                                            } else {
                                                throw new Error(data.message || 'Failed to delete benefit');
                                            }
                                        } catch (error) {
                                            Swal.fire('Error!', error.message, 'error');
                                        }
                                    }
                                });
                            });

                            document.querySelectorAll('.delete-deduction-btn').forEach(btn => {
                                addDynamicEventListener(btn, 'click', async function() {
                                    const deductionId = this.dataset.deductionId;
                                    const row = this.closest('.item-row');

                                    if (!deductionId) { // For newly added, unsaved items
                                        row.remove();
                                        updateDynamicNetSalary();
                                        return;
                                    }

                                    const swalResult = await Swal.fire({
                                        title: 'Delete Deduction?',
                                        text: 'Are you sure you want to delete this deduction?',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#d33',
                                        cancelButtonColor: '#3085d6',
                                        confirmButtonText: 'Yes, delete it!'
                                    });

                                    if (swalResult.isConfirmed) {
                                        try {
                                            const response = await fetch('api/delete_deduction.php', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json' },
                                                body: JSON.stringify({ deduction_id: deductionId, emp_id: empId, month: month })
                                            });
                                            const data = await response.json();
                                            
                                            if (data.status === 'success') {
                                                row.remove();
                                                updateDynamicNetSalary();
                                                Swal.fire('Deleted!', 'The deduction has been deleted.', 'success');
                                            } else {
                                                throw new Error(data.message || 'Failed to delete deduction');
                                            }
                                        } catch (error) {
                                            Swal.fire('Error!', error.message, 'error');
                                        }
                                    }
                                });
                            });

                            updateDynamicNetSalary(); // Initial calculation for display
                        },
                        preConfirm: () => {
                            const updatedBenefits = Array.from(document.querySelectorAll('#benefits-list > .item-row')).map(div => {
                                const benefitAmountInput = div.querySelector('.benefit-amount') || div.querySelector('.new-benefit-amount');
                                const benefitNameInput = div.querySelector('.benefit-name') || div.querySelector('.new-benefit-name');
                                const benefitId = benefitAmountInput ? benefitAmountInput.dataset.benefitId : null;

                                return {
                                    id: benefitId,
                                    name: benefitNameInput ? benefitNameInput.value.trim() : '',
                                    amount: parseFloat(benefitAmountInput.value || 0)
                                };
                            }).filter(b => b.name !== '' || b.amount > 0);

                            const updatedDeductions = Array.from(document.querySelectorAll('#deductions-list > .item-row')).map(div => {
                                const deductionAmountInput = div.querySelector('.deduction-amount') || div.querySelector('.new-deduction-amount');
                                const deductionNameInput = div.querySelector('.deduction-name') || div.querySelector('.new-deduction-name');
                                const deductionId = deductionAmountInput ? deductionAmountInput.dataset.deductionId : null;

                                return {
                                    id: deductionId,
                                    name: deductionNameInput ? deductionNameInput.value.trim() : '',
                                    amount: parseFloat(deductionAmountInput.value || 0)
                                };
                            }).filter(d => d.name !== '' || d.amount > 0);

                            return { updatedBenefits, updatedDeductions };
                        }
                    }).then((result) => {
                        // Re-attach all global listeners after SweetAlert2 closes
                        addEventListeners();
                        if (result.isConfirmed) {
                            savePayrollChanges(empId, month, result.value.updatedBenefits, result.value.updatedDeductions);
                        }
                    });

                } else {
                    showError('Error Loading Payroll', data.message || 'Failed to load payroll details');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Network Error', error.message);
            }
        }

        function updateNetSalaryDisplay(grossSalary) {
            const benefitsDisplay = document.getElementById('totalBenefitsDisplay');
            const deductionsDisplay = document.getElementById('totalDeductionsDisplay');
            const netSalaryDisplay = document.getElementById('netSalaryDisplay');
            
            if (!benefitsDisplay || !deductionsDisplay || !netSalaryDisplay) {
                return; // Exit if elements don't exist (e.g., modal not open)
            }

            const parsedGross = typeof grossSalary === 'string' ? 
                parseFloat(grossSalary.replace(/[^0-9.-]/g, '')) : 
                parseFloat(grossSalary);

            let totalBenefits = 0;
            document.querySelectorAll('.benefit-amount, .new-benefit-amount').forEach(input => {
                const value = input.value.trim();
                totalBenefits += value ? parseFloat(value) : 0;
            });

            let totalDeductions = 0;
            document.querySelectorAll('.deduction-amount, .new-deduction-amount').forEach(input => {
                const value = input.value.trim();
                totalDeductions += value ? parseFloat(value) : 0;
            });

            const netSalary = Math.round((parsedGross + totalBenefits - totalDeductions) * 100) / 100;

            const formatCurrency = (amount) => {
                return 'SAR ' + amount.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            benefitsDisplay.textContent = formatCurrency(totalBenefits);
            deductionsDisplay.textContent = formatCurrency(totalDeductions);
            netSalaryDisplay.textContent = formatCurrency(netSalary);
        }

        async function savePayrollChanges(empId, month, updatedBenefits, updatedDeductions) {
            Swal.fire({
                title: 'Saving Changes...',
                html: 'Please wait.',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            try {
                const response = await fetch('api/update_payroll.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        emp_id: empId,
                        month: month,
                        benefits: updatedBenefits,
                        deductions: updatedDeductions
                    }),
                });
                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Changes Saved!',
                        text: result.message,
                        confirmButtonColor: '#6366f1'
                    });
                    fetchEmployees(); // Refresh employee list to ensure payroll status is updated
                } else {
                    throw new Error(result.message || 'Failed to save changes');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error Saving Changes', error.message);
            }
        }

        function showError(title, message) {
            Swal.fire({
                icon: 'error',
                title: title,
                text: message,
                confirmButtonColor: '#6366f1'
            });
        }

        function showWarning(title, message) {
            Swal.fire({
                icon: 'warning',
                title: title,
                text: message,
                confirmButtonColor: '#6366f1'
            });
        }

        // --- NEW: Payroll Report Functionality ---
        async function generatePayrollReport() {
            Swal.fire({
                title: 'Select Report Month',
                html: `
                    <div class="text-left mb-4">
                        <label for="reportMonthSelect" class="block text-gray-700 text-sm font-bold mb-2">
                            Choose a month to generate the payroll report:
                        </label>
                        <select id="reportMonthSelect" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Generate Report',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#6366f1',
                // Pre-confirmation logic: validate if a month is selected
                preConfirm: () => {
                    const selectedMonth = $('#reportMonthSelect').val();
                    if (!selectedMonth) {
                        Swal.showValidationMessage('Please select a month to generate the report.');
                    }
                    return selectedMonth; // Return the selected month if valid
                },
                // didOpen callback: executed after the modal is opened
                didOpen: async () => {
                    const reportMonthSelect = document.getElementById('reportMonthSelect');
                    Swal.showLoading(); // Show loading indicator inside the modal
                    try {
                        // Fetch available payroll months from your specified API
                        const response = await fetch('api/get_available_months.php'); 
                        if (!response.ok) {
                            throw new Error('Failed to fetch available payroll months for report.');
                        }
                        const data = await response.json();

                        if (data.status === 'success' && data.months.length > 0) {
                            // Populate the select dropdown with fetched months
                            data.months.forEach(month => {
                                const option = document.createElement('option');
                                option.value = month.value;
                                option.textContent = month.label;
                                reportMonthSelect.appendChild(option);
                            });
                            // Select the first month by default (usually the most recent)
                            if (data.months.length > 0) {
                                reportMonthSelect.value = data.months[0].value;
                            }
                            Swal.hideLoading(); // Hide loading indicator
                        } else {
                            Swal.hideLoading();
                            // If no months are found, show a validation message and disable the confirm button
                            Swal.showValidationMessage('No generated payroll months found.');
                            Swal.getConfirmButton().disabled = true;
                        }
                    } catch (error) {
                        console.error('Error loading report months:', error);
                        Swal.hideLoading();
                        Swal.showValidationMessage(`Error loading months: ${error.message}`);
                        Swal.getConfirmButton().disabled = true; // Disable button on error
                    }
                }
            }).then(async (result) => {
                // After the user confirms the month selection
                if (result.isConfirmed) {
                    const selectedMonth = result.value;
                    // Proceed to fetch and display the payroll report for the selected month
                    await fetchAndDisplayPayrollReport(selectedMonth);
                }
            });
        }

        // --- New helper function to encapsulate report fetching and display ---
        async function fetchAndDisplayPayrollReport(selectedMonth) {
            Swal.fire({
                title: 'Generating Report...',
                html: `Fetching payroll data for ${new Date(selectedMonth + '-01').toLocaleString('default', { month: 'long', year: 'numeric' })}. Please wait.`,
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            try {
                // Fetch the payroll report data for the chosen month
                const response = await fetch(`api/get_payroll_report.php?month=${selectedMonth}`);
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Server responded with status ${response.status}: ${errorText}`);
                }
                const data = await response.json();

                if (data.status === 'success') {
                    const reportData = data.report;
                    if (reportData.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Payroll Data',
                            text: `No generated payrolls found for ${new Date(selectedMonth + '-01').toLocaleString('default', { month: 'long', year: 'numeric' })}.`,
                            confirmButtonColor: '#6366f1'
                        });
                        return;
                    }
                    // Prepare data for the report DataTable
                    const tableData = reportData.map(p => {
                        // Calculate total allowances for display in the report table
                        const totalAllowances = parseFloat(p.housing_allowance || 0) + parseFloat(p.transport_allowance || 0) + 
                                                parseFloat(p.food_allowance || 0) + parseFloat(p.miscellaneous_allowance || 0) + 
                                                parseFloat(p.cashier_allowance || 0) + parseFloat(p.fuel_allowance || 0) +
                                                parseFloat(p.telephone_allowance || 0) + parseFloat(p.other_allowance || 0) + 
                                                parseFloat(p.guard_allowance || 0);

                        
                        return [
                            p.emp_id,
                            p.employee_name,
                            p.department_name || 'N/A',
                            p.month_year,
                            parseFloat(p.basic_salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }),
                            totalAllowances.toLocaleString('en-US', { minimumFractionDigits: 2 }),
                            parseFloat(p.total_benefits || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }),
                            parseFloat(p.total_deductions || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }),
                            parseFloat(p.total_gross_salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }),
                            parseFloat(p.net_salary || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })
                        ];
                    });

                    // Calculate grand totals for the report footer
                    let grandTotalGross = 0;
                    let grandTotalBenefits = 0;
                    let grandTotalDeductions = 0;
                    let grandTotalNet = 0;

                    reportData.forEach(p => {
                        grandTotalGross += parseFloat(p.total_gross_salary || 0);
                        grandTotalBenefits += parseFloat(p.total_benefits || 0);
                        grandTotalDeductions += parseFloat(p.total_deductions || 0);
                        grandTotalNet += parseFloat(p.net_salary || 0);
                    });

                    // Construct the HTML for the report modal
                    const reportHtml = `
                        <div id="payrollReportModal" class="text-left">
                            <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">Payroll Report for ${new Date(selectedMonth + '-01').toLocaleString('default', { month: 'long', year: 'numeric' })}</h2>
                            <p class="text-gray-600 mb-4 text-center">Summary of all generated payrolls.</p>
                            <div class="print-button-container text-center mb-6 flex justify-center space-x-4">
                                <button id="exportPdfBtn" class="btn-primary text-white py-2 px-5 rounded-lg shadow-md hover:shadow-lg">
                                    Export to PDF
                                </button>
                                <button id="exportExcelBtn" class="btn-primary text-white py-2 px-5 rounded-lg shadow-md hover:shadow-lg">
                                    Export to Excel
                                </button>
                            </div>
                            <table class="min-w-full stripe hover" id="payrollgentbl">
                                <thead>
                                    <tr>
                                        <th>Emp ID</th>
                                        <th>Name</th>
                                        <th>Dept.</th>
                                        <th>Month</th>
                                        <th class="text-right">Basic</th>
                                        <th class="text-right">Allowances</th>
                                        <th class="text-right">Benefits</th>
                                        <th class="text-right">Deductions</th>
                                        <th class="text-right">Gross</th>
                                        <th class="text-right">Net</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded by DataTables -->
                                </tbody>
                                <tfoot>
                                    <tr class="total-row">
                                        <td colspan="4" class="text-right font-bold">Grand Totals (SAR):</td>
                                        <td></td> <!-- Basic -->
                                        <td></td> <!-- Allowances -->
                                        <td class="text-right font-bold">${grandTotalBenefits.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                        <td class="text-right font-bold">${grandTotalDeductions.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                        <td class="text-right font-bold">${grandTotalGross.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                        <td class="text-right font-bold">${grandTotalNet.toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;

                    // Display the report in a new SweetAlert2 modal
                    Swal.fire({
                        html: reportHtml,
                        width: '90%',
                        showConfirmButton: false, // No "OK" button
                        showCancelButton: true,
                        cancelButtonText: 'Close',
                        didOpen: () => {
                            // Initialize the DataTable for the report table
                            const table = $('#payrollgentbl').DataTable({
                                data: tableData,
                                pageLength: 10,
                                paging: true,      // Disable pagination for reports
                                searching: true,   // Disable searching
                                info: true,        // Disable info text (showing x of y entries)
                                lengthMenu: [5, 10, 25, 50, 100, 500],
                                order: []           // Disable initial sorting to keep data as per backend order
                            });

                            // Attach event listeners for PDF and Excel export buttons
                            document.getElementById('exportPdfBtn').addEventListener('click', () => {
                                exportPdfReport(reportData, selectedMonth);
                            });
                            document.getElementById('exportExcelBtn').addEventListener('click', () => {
                                exportExcelReport(reportData, selectedMonth);
                            });
                        }
                    });

                } else {
                    showError('Error Generating Report', data.message || 'An unexpected error occurred while fetching report data.');
                }
            } catch (error) {
                console.error('Error fetching and displaying payroll report:', error);
                showError('Network Error', `Could not connect to the server or process report: ${error.message}. Please try again.`);
            }
        }

        // --- PDF Export Function (Updated to use selectedMonth for filename and report title) ---
        async function exportPdfReport(reportData, selectedMonth) {
            const { jsPDF } = window.jspdf;
            let sr = 1;
            const doc = new jsPDF('landscape'); // 'landscape' for wider reports
            const reportTitle = `Employee Payroll Report for ${new Date(selectedMonth + '-01').toLocaleString('default', { month: 'long', year: 'numeric' })}`;

            // Define table headers for PDF
            const headers = [
                ['SER', 'ID / IQAMA', 'EMPLOYEE NAME', 'IBAN', 'BANK CODE',
                'NET SALARY', 'BASIC', 'HOUSE', 'OTHER', 'DEDUCTION', 'REF']
            ];

            // Prepare table data for PDF
            const body = reportData.map(p => {
                const totalAllowances = (
                    parseFloat(p.housing_allowance || 0) +
                    parseFloat(p.transport_allowance || 0) +
                    parseFloat(p.food_allowance || 0) +
                    parseFloat(p.miscellaneous_allowance || 0) +
                    parseFloat(p.cashier_allowance || 0) +
                    parseFloat(p.fuel_allowance || 0) +
                    parseFloat(p.telephone_allowance || 0) +
                    parseFloat(p.other_allowance || 0) +
                    parseFloat(p.guard_allowance || 0)
                ).toFixed(2);
                return [
                    sr++,
                    p.iqama,
                    p.employee_name,
                    p.iban || 'N/A',
                    p.bank_name_s || 'N/A',
                    parseFloat(p.net_salary || 0).toFixed(2),
                    parseFloat(p.basic_salary || 0).toFixed(2),
                    parseFloat(p.housing_allowance || 0).toFixed(2),
                    totalAllowances,
                    parseFloat(p.total_deductions || 0).toFixed(2),
                    p.sponsor
                ];
            });

            // Add report title to PDF
            doc.setFontSize(16);
            doc.text(reportTitle, doc.internal.pageSize.width / 2, 15, { align: 'center' });
            doc.setFontSize(10);
            doc.text('Summary of all generated payrolls.', doc.internal.pageSize.width / 2, 25, { align: 'center' });
            
            // Generate table using autoTable plugin
            doc.autoTable({
                startY: 35, // Start table below the title
                head: headers,
                body: body,
                theme: 'striped',
                margin: { top: 35, right: 2, bottom: 20, left: 2 }, // Adjust margins
                tableWidth: '100%', // Use full page width
                headStyles: { 
                    fillColor: [224, 242, 254], 
                    textColor: [71, 85, 105], 
                    fontStyle: 'bold' 
                },
                styles: { 
                    fontSize: 6, 
                    cellPadding: 2, 
                    overflow: 'linebreak',
                    minCellWidth: 15 // Ensure minimum width for readability
                },
                columnStyles: { // Align numerical columns to the right
                    4: { halign: 'right' },
                    5: { halign: 'right' },
                    6: { halign: 'right' },
                    7: { halign: 'right' },
                    8: { halign: 'right' },
                    9: { halign: 'right' } 
                },
            });
            // Save the PDF with a dynamic filename based on the selected month
            doc.save(`payroll_report_${selectedMonth.replace('-', '_')}.pdf`);
        }

        // --- Excel (XLSX) Export Function (Updated to use selectedMonth for filename) ---
        function exportExcelReport(reportData, selectedMonth) {
            // Ensure the XLSX library is available
            if (typeof XLSX === 'undefined') {
                console.error("The XLSX library (SheetJS) is not loaded. Please include it in your project.");
                // You could also add a user-facing message here.
                return;
            }

            // Create a new workbook
            const wb = XLSX.utils.book_new();

            // 1. Add headers row
            const headers = [
                'SER', 'ID / IQAMA', 'EMPLOYEE NAME', 'IBAN', 'BANK CODE',
                'NET SALARY', 'BASIC', 'HOUSE', 'OTHER', 'DEDUCTION',
                'ADDRESS', 'CUR', 'STATUS', 'DESCRIPTION', 'REF'
            ];

            // 2. Map reportData to the desired row format, converting strings to numbers
            // By processing the data first, we can separate logic from the sheet creation step.
            const dataRows = reportData.map((p, index) => {
                // Calculate the total for the 'OTHER' allowances column
                // We ensure all values are parsed as numbers.
                const totalAllowances =
                    parseFloat(p.transport_allowance || 0) +
                    parseFloat(p.food_allowance || 0) +
                    parseFloat(p.miscellaneous_allowance || 0) +
                    parseFloat(p.cashier_allowance || 0) +
                    parseFloat(p.fuel_allowance || 0) +
                    parseFloat(p.telephone_allowance || 0) +
                    parseFloat(p.other_allowance || 0) +
                    parseFloat(p.guard_allowance || 0);
                // Return an array representing the row.
                // We use parseFloat to ensure all monetary values are treated as numbers.
                // We DO NOT use .toFixed() here, because it converts numbers to strings,
                // which prevents Excel from recognizing them as numbers.
                return [
                    index + 1, // Serial number
                    p.iqama,
                    p.employee_name,
                    p.iban || 'N/A',
                    p.bank_name_s || 'N/A',
                    parseFloat(p.net_salary || 0),
                    parseFloat(p.basic_salary || 0),
                    parseFloat(p.housing_allowance || 0),
                    totalAllowances,
                    parseFloat(p.total_deductions || 0),
                    'INDUSTRIAL CITY',
                    'SAR',
                    'ACTIVE',
                    'PAYROLL',
                    p.sponsor
                ];
            });

            // Combine headers and data rows
            const allRows = [headers, ...dataRows];

            // Convert the array of rows into an Excel worksheet
            // The library will automatically detect data types (number, string, etc.)
            const ws = XLSX.utils.aoa_to_sheet(allRows);

            // Optional: You can explicitly set column formats if needed, for example, to show 2 decimal places.
            // This gives you more control over the appearance in Excel.
            const numberFormat = '#,##0.00';
            const columnsToFormat = ['F', 'G', 'H', 'I', 'J']; // Corresponds to NET SALARY, BASIC, etc.

            // Loop through all data rows (starting from row 2 in Excel, which is index 1 here)
            for (let i = 1; i <= dataRows.length; i++) {
                columnsToFormat.forEach(colLetter => {
                    const cellAddress = colLetter + (i + 1); // e.g., F2, G2...
                    if (ws[cellAddress]) { // Check if the cell exists
                        ws[cellAddress].z = numberFormat; // 'z' is the number format property
                    }
                });
            }
            // Add the worksheet to the workbook
            XLSX.utils.book_append_sheet(wb, ws, "Payroll Report");

            // Generate the XLSX file and trigger download with a dynamic filename
            const fileName = `payroll_report_${selectedMonth.replace('-', '_')}.xlsx`;
            XLSX.writeFile(wb, fileName);
        }

        function formatNumber(value) {
        // Parse input (default to 0 if invalid), round to 2 decimal places, and format for SA locale
            const num = parseFloat(value || 0).toFixed(2);
            return num;
        }

        function formaNumberWFractionDigits(value) {
        // Parse input (default to 0 if invalid), round to 2 decimal places, and format for SA locale
            const num = Number(parseFloat(value || 0).toFixed(2));
            return num.toLocaleString('en-SA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function getDateParts(date = new Date(), part = null) {
            const year = date.getFullYear();
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const day = date.getDate().toString().padStart(2, '0');
            const parts = {
                year,
                month,
                day,
                fullDate: `${year}-${month}-${day}`
            };
            return part ? parts[part] : parts;
        }

        async function loadAvailableMonthsForMainPage() {
            try {
                const response = await fetch('api/get_available_months.php'); 
                if (!response.ok) {
                    throw new Error('Failed to fetch available months for main page');
                }
                const data = await response.json();
                
                const monthSelect = $('#payrollMonth');
                monthSelect.empty(); // Clear existing options

                if (data.status === 'success' && data.months.length > 0) {
                    data.months.forEach(month => {
                        monthSelect.append($('<option>', {
                            value: month.value,
                            text: month.label
                        }));
                    });

                    // Automatically select the most recent month (first in the sorted list)
                    monthSelect.val(data.months[0].value);
                    // Crucially, call fetchEmployees here to load data for the initially selected month
                    fetchEmployees(); 
                    $('#generateReportBtn').prop('disabled', false); // Enable report button
                } else {
                    // If no months are available, show a message and disable the report button
                    monthSelect.append($('<option>', {
                        value: '',
                        text: 'No months available for payroll',
                        disabled: true,
                        selected: true
                    }));
                    $('#generateReportBtn').prop('disabled', true);
                    showInfo('No Payroll Months', 'No generated payroll months found. Please generate payrolls first.');
                }
            } catch (error) {
                console.error('Error loading available months for main page:', error);
                showError('Error', 'Could not load available months for the main filter: ' + error.message);
                $('#generateReportBtn').prop('disabled', true); // Disable button on error
            }
        }

    </script>
</body>
</html>
