<?php

require_once __DIR__ . '/../../includes/db.php';

$ajaxType = $_POST['ajaxType'];
if($ajaxType == 'add_salary'){
    $data = []; // Initialize response array
    // Check if any checkboxes are selected
    if (!isset($_POST["idk"]) || empty($_POST["idk"])) {
        $data = [
            'title'   => "Error!",
            'message' => "Please select at least one employee.",
            'type'    => 'error',
        ];
    } else {
        $counter = $_POST["idk"];
        $successCount = 0;
        $errorMessages = [];
        foreach ($counter as $chk_id) {
            // Get employee details
            $query = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `id`='$chk_id'") or die(mysqli_error($conDB));
            if (mysqli_num_rows($query) > 0) {
                $row = mysqli_fetch_array($query);
                $name = $row['name'];
                $salary = $row['salary'];
                $emp_id = $row['emp_id'];
                // Get salary details
                $querysal = mysqli_query($conDB, "SELECT * FROM `salary_emp` WHERE `emp_id`='$emp_id' ORDER BY `emp_id` DESC LIMIT 1") or die(mysqli_error($conDB));
                if (mysqli_num_rows($querysal) > 0) {
                    $row = mysqli_fetch_array($querysal);
                    $basic = $row['basic'];
                    $housing = $row['housing'];
                    $transport = $row['transport'];
                    $other_pay = $row['other_pay'];
                    // Insert payroll record
                    $sql = "INSERT INTO payroll (`chk_id`, `name`, `basic`,`housing`,`transport`,`other_pay`, `emp_id`, `month`, `created_at`) 
                            VALUES ('$chk_id','$name','$basic','$housing','$transport','$other_pay','$emp_id','" . date('m') . "','" . date('c') . "')";
                    if (mysqli_query($conDB, $sql)) {
                        $successCount++;
                    } else {
                        $errorMessages[] = "Error processing payroll for $name: " . mysqli_error($conDB);
                    }
                } else {
                    $errorMessages[] = "No salary information found for $name (ID: $emp_id)";
                }
            } else {
                $errorMessages[] = "Employee not found (ID: $chk_id)";
            }
        }   
        // Prepare response
        if ($successCount > 0) {
            $data = [
                'title'   => "Success!",
                'message' => "Payroll generated for $successCount employee(s)." . 
                            (count($errorMessages) > 0 ? " Issues: " . implode(" ", $errorMessages) : ""),
                'type'    => 'success',
            ];
        } else {
            $data = [
                'title'   => "Error!",
                'message' => "Failed to generate payroll. " . implode(" ", $errorMessages),
                'type'    => 'error',
            ];
        }
    }
    echo json_encode($data);
}