<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Vacation Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
        }
    </style>
</head>
<body class="p-6 md:p-12">
    <!-- Main Container -->
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Employee Vacation Tracker</h1>
        <p class="text-center text-gray-600 mb-8">Data is now stored in a database for persistence.</p>

        <!-- User ID Display -->
        <div class="mb-6 p-4 bg-gray-100 rounded-lg">
            <p class="text-sm font-medium text-gray-700">Your User ID (for data sharing):</p>
            <p id="userIdDisplay" class="break-words font-mono text-xs text-gray-500 mt-1"></p>
        </div>

        <!-- Employee Selector -->
        <div class="mb-8">
            <label for="employeeSelect" class="block text-sm font-medium text-gray-700">Select Employee</label>
            <select id="employeeSelect" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                <option value="">-- Loading employees... --</option>
            </select>
        </div>

        <!-- Employee Info and Balances -->
        <div id="employeeDetails" class="hidden">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-blue-800">Employee Details</h3>
                    <p class="text-gray-600">Name: <span id="empName" class="font-medium text-gray-800"></span></p>
                    <p class="text-gray-600">ID: <span id="empId" class="font-medium text-gray-800"></span></p>
                    <p class="text-gray-600">Total Vacation Days: <span id="totalDays" class="font-medium text-gray-800"></span></p>
                </div>
                <div class="p-4 bg-green-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-green-800">Vacation Balance</h3>
                    <p class="text-gray-600">Used Days: <span id="usedDays" class="font-medium text-gray-800"></span></p>
                    <p class="text-gray-600">Remaining Balance: <span id="remainingBalance" class="font-bold text-lg text-green-700"></span></p>
                </div>
            </div>

            <!-- Vacation History Table -->
            <div class="bg-gray-50 p-6 rounded-lg mb-8">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Vacation History</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Used</th>
                            </tr>
                        </thead>
                        <tbody id="vacationHistoryTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- History rows will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Add New Vacation Form -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Add New Vacation</h3>
                <form id="addVacationForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="startDate" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" id="startDate" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="endDate" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" id="endDate" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Add Vacation
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Employee Form / Import Placeholder -->
        <div class="mt-8 bg-gray-100 p-6 rounded-lg">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Employee Management</h3>
            <form id="addEmployeeForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="newEmpId" class="block text-sm font-medium text-gray-700">Employee ID</label>
                    <input type="text" id="newEmpId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="newEmpName" class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" id="newEmpName" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="newEmpVacationDays" class="block text-sm font-medium text-gray-700">Vacation Days/Year</label>
                    <input type="number" id="newEmpVacationDays" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="md:col-span-2 flex justify-end gap-2 mt-4">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Add Employee
                    </button>
                    <button type="button" id="simulateImportBtn" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-500 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400">
                        Simulate Data Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Alert Box -->
    <div id="alertBox" class="fixed inset-x-0 bottom-4 flex justify-center z-50 transition-transform duration-300 transform translate-y-20 opacity-0">
        <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg text-sm font-medium">
            <span id="alertMessage"></span>
        </div>
    </div>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
        import { getAuth, signInAnonymously, signInWithCustomToken } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";
        import { getFirestore, collection, doc, getDoc, setDoc, updateDoc, onSnapshot, query, where, addDoc } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";
        import { setLogLevel } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-firestore.js";

        // Firebase configuration and initialization
        const appId = typeof __app_id !== 'undefined' ? __app_id : 'default-app-id';
        const firebaseConfig = JSON.parse(typeof __firebase_config !== 'undefined' ? __firebase_config : '{}');
        const __initial_auth_token = typeof __initial_auth_token !== 'undefined' ? __initial_auth_token : null;

        let db;
        let auth;
        let userId;

        const employeesCollectionPath = `artifacts/${appId}/users/my-data-collection/employees`;
        const vacationsCollectionPath = `artifacts/${appId}/users/my-data-collection/vacations`;
        
        // This is a placeholder for your AS400 data
        const AS400_DATA = [
            { emp_id: '1122', name: 'JOHN DOE', vacation_days: 25 },
            { emp_id: '3344', name: 'JANE SMITH', vacation_days: 21 },
            { emp_id: '5566', name: 'PETER JONES', vacation_days: 30 }
        ];

        // DOM Elements
        const employeeSelect = document.getElementById('employeeSelect');
        const employeeDetails = document.getElementById('employeeDetails');
        const empNameSpan = document.getElementById('empName');
        const empIdSpan = document.getElementById('empId');
        const totalDaysSpan = document.getElementById('totalDays');
        const usedDaysSpan = document.getElementById('usedDays');
        const remainingBalanceSpan = document.getElementById('remainingBalance');
        const vacationHistoryTableBody = document.getElementById('vacationHistoryTableBody');
        const addVacationForm = document.getElementById('addVacationForm');
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const addEmployeeForm = document.getElementById('addEmployeeForm');
        const newEmpIdInput = document.getElementById('newEmpId');
        const newEmpNameInput = document.getElementById('newEmpName');
        const newEmpVacationDaysInput = document.getElementById('newEmpVacationDays');
        const simulateImportBtn = document.getElementById('simulateImportBtn');
        const alertBox = document.getElementById('alertBox');
        const alertMessage = document.getElementById('alertMessage');
        const userIdDisplay = document.getElementById('userIdDisplay');

        setLogLevel('Debug');

        // Function to show a temporary alert message
        function showAlert(message, type = 'success') {
            alertMessage.textContent = message;
            if (type === 'success') {
                alertBox.classList.remove('bg-red-500');
                alertBox.classList.add('bg-green-500');
            } else {
                alertBox.classList.remove('bg-green-500');
                alertBox.classList.add('bg-red-500');
            }
            alertBox.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                alertBox.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        }

        // Function to calculate working days between two dates
        function getWorkingDays(startDate, endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            let days = 0;
            const currentDate = new Date(start);

            while (currentDate <= end) {
                // Check if the current day is not a Saturday (6) or Sunday (0)
                if (currentDate.getDay() !== 6 && currentDate.getDay() !== 0) {
                    days++;
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
            return days;
        }

        // Function to update the UI with vacation details for a specific employee
        async function updateVacationDetails(selectedEmpId) {
            if (!selectedEmpId) {
                employeeDetails.classList.add('hidden');
                return;
            }

            try {
                // Fetch employee data
                const employeeDocRef = doc(db, employeesCollectionPath, selectedEmpId);
                const employeeDocSnap = await getDoc(employeeDocRef);
                const employee = employeeDocSnap.data();

                if (!employee) {
                    employeeDetails.classList.add('hidden');
                    return;
                }

                // Fetch vacation history for the selected employee
                const vacationsQuery = query(collection(db, vacationsCollectionPath), where('emp_id', '==', selectedEmpId));
                onSnapshot(vacationsQuery, (querySnapshot) => {
                    let totalUsedDays = 0;
                    const vacationHistory = [];

                    querySnapshot.forEach(doc => {
                        const vac = doc.data();
                        totalUsedDays += vac.days_used;
                        vacationHistory.push(vac);
                    });

                    vacationHistory.sort((a, b) => new Date(a.start_date) - new Date(b.start_date));

                    // Update UI
                    empNameSpan.textContent = employee.name;
                    empIdSpan.textContent = employee.emp_id;
                    totalDaysSpan.textContent = employee.vacation_days;
                    usedDaysSpan.textContent = totalUsedDays.toFixed(2);
                    remainingBalanceSpan.textContent = (employee.vacation_days - totalUsedDays).toFixed(2);
                    employeeDetails.classList.remove('hidden');

                    // Populate vacation history table
                    vacationHistoryTableBody.innerHTML = '';
                    vacationHistory.forEach(vacation => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${vacation.start_date}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${vacation.end_date}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${vacation.days_used.toFixed(2)}</td>
                        `;
                        vacationHistoryTableBody.appendChild(row);
                    });
                });

            } catch (error) {
                console.error("Error updating vacation details:", error);
                showAlert(`Failed to load employee data: ${error.message}`, 'error');
            }
        }

        // Event listener for employee selection
        employeeSelect.addEventListener('change', (e) => {
            const selectedEmpId = e.target.value;
            updateVacationDetails(selectedEmpId);
        });

        // Event listener for adding a new vacation record
        addVacationForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const selectedEmpId = employeeSelect.value;
            if (!selectedEmpId) {
                showAlert('Please select an employee first.', 'error');
                return;
            }

            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            if (new Date(startDate) > new Date(endDate)) {
                showAlert('Start date cannot be after end date.', 'error');
                return;
            }

            const daysUsed = getWorkingDays(startDate, endDate);

            try {
                // Add the new vacation record to Firestore
                await addDoc(collection(db, vacationsCollectionPath), {
                    emp_id: selectedEmpId,
                    start_date: startDate,
                    end_date: endDate,
                    days_used: daysUsed,
                    last_updated: new Date().toISOString()
                });

                showAlert('Vacation added successfully!');
                addVacationForm.reset();
            } catch (error) {
                console.error("Error adding vacation record:", error);
                showAlert(`Failed to add vacation record: ${error.message}`, 'error');
            }
        });

        // Event listener for adding a new employee
        addEmployeeForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const newEmpId = newEmpIdInput.value;
            const newEmpName = newEmpNameInput.value;
            const newEmpVacationDays = newEmpVacationDaysInput.value;

            try {
                // Add new employee to Firestore using their ID as the document ID
                await setDoc(doc(db, employeesCollectionPath, newEmpId), {
                    emp_id: newEmpId,
                    name: newEmpName,
                    vacation_days: parseInt(newEmpVacationDays)
                });

                showAlert('Employee added successfully!');
                addEmployeeForm.reset();
            } catch (error) {
                console.error("Error adding employee:", error);
                showAlert(`Failed to add employee: ${error.message}`, 'error');
            }
        });

        // Event listener for simulating data import
        simulateImportBtn.addEventListener('click', async () => {
            try {
                for (const emp of AS400_DATA) {
                    await setDoc(doc(db, employeesCollectionPath, emp.emp_id), emp);
                }
                showAlert('Simulated data import successful!');
            } catch (error) {
                console.error("Error importing data:", error);
                showAlert(`Failed to import data: ${error.message}`, 'error');
            }
        });

        // Initial setup
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const app = initializeApp(firebaseConfig);
                auth = getAuth(app);
                db = getFirestore(app);
                
                if (__initial_auth_token) {
                    await signInWithCustomToken(auth, __initial_auth_token);
                } else {
                    await signInAnonymously(auth);
                }
                
                userId = auth.currentUser.uid;
                userIdDisplay.textContent = userId;

                // Listen for real-time updates to the employees collection
                onSnapshot(collection(db, employeesCollectionPath), (querySnapshot) => {
                    const employees = [];
                    querySnapshot.forEach(doc => {
                        employees.push(doc.data());
                    });
                    
                    // Populate the employee dropdown
                    employeeSelect.innerHTML = '<option value="">-- Select an Employee --</option>';
                    employees.sort((a, b) => a.name.localeCompare(b.name));
                    employees.forEach(employee => {
                        const option = document.createElement('option');
                        option.value = employee.emp_id;
                        option.textContent = `${employee.emp_id} - ${employee.name}`;
                        employeeSelect.appendChild(option);
                    });

                    // If an employee was already selected, re-update their details
                    if (employeeSelect.value) {
                        updateVacationDetails(employeeSelect.value);
                    }
                });

            } catch (error) {
                console.error("Firebase initialization failed:", error);
                showAlert(`Application failed to initialize: ${error.message}`, 'error');
            }
        });
    </script>
</body>
</html>
