<?php
// include mysql database configuration file
include_once 'db.php';
require_once '../includes/session_check.php';
 
if (isset($_POST['submit']))
{
 
    // Allowed mime types
    $fileMimes = array(
        'text/x-comma-separated-values',
        'text/comma-separated-values',
        'application/octet-stream',
        'application/vnd.ms-excel',
        'application/x-csv',
        'text/x-csv',
        'text/csv',
        'application/csv',
        'application/excel',
        'application/vnd.msexcel',
        'text/plain'
    );
 
    // Validate whether selected file is a CSV file
    if (!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $fileMimes))
    {
 
            // Open uploaded CSV file with read-only mode
            $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
 
            // Skip the first line
            fgetcsv($csvFile);
 
            // Parse data from CSV file line by line
            $import_count = 0;
            $update_count = 0;
            while (($getData = fgetcsv($csvFile, 10000, ",")) !== FALSE)
            {
                // Get row data
                $name = mysqli_real_escape_string($conDB, $getData[0]);
                $email = mysqli_real_escape_string($conDB, $getData[1]);
                $phone = mysqli_real_escape_string($conDB, $getData[2]);
                $status = mysqli_real_escape_string($conDB, $getData[3]);
 
                // If user already exists in the database with the same email
                $query = "SELECT id FROM users WHERE email = '" . $email . "'";
 
                $check = mysqli_query($conDB, $query);
 
                if ($check->num_rows > 0)
                {
                    // Fetch old data before update
                    $old_user = mysqli_fetch_assoc(mysqli_query($conDB, "SELECT * FROM users WHERE email = '" . $email . "'"));
                    $user_id = $old_user['id'];
                    
                    mysqli_query($conDB, "UPDATE users SET name = '" . $name . "', phone = '" . $phone . "', status = '" . $status . "', created_at = NOW() WHERE email = '" . $email . "'");
                    
                    // Log the update
                    ActivityLogger::logUpdate('User', 'import_csv.php', $user_id, $old_user, [
                        'name' => $name,
                        'phone' => $phone,
                        'status' => $status
                    ], "Bulk imported user update: {$name}", 'users');
                    $update_count++;
                }
                else
                {
                     mysqli_query($conDB, "INSERT INTO users (name, email, phone, created_at, updated_at, status) VALUES ('" . $name . "', '" . $email . "', '" . $phone . "', NOW(), NOW(), '" . $status . "')");
                     $new_user_id = mysqli_insert_id($conDB);
                     
                     // Log the creation
                     ActivityLogger::logCreate('User', 'import_csv.php', $new_user_id, [
                         'name' => $name,
                         'email' => $email,
                         'phone' => $phone,
                         'status' => $status
                     ], "Bulk imported new user: {$name}", 'users');
                     $import_count++;
                }
            }
            
            // Log bulk import summary
            ActivityLogger::log('User', 'import_csv.php', 0, 'IMPORT', [
                'imported' => $import_count,
                'updated' => $update_count
            ], "Bulk imported {$import_count} new users and updated {$update_count} users from CSV", 'MEDIUM', 'users');
 
            // Close opened CSV file
            fclose($csvFile);
 
            header("Location: index.php");
         
    }
    else
    {
        echo "Please select valid file";
    }
}