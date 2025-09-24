<?php
/**
 * MODIFICATION SUMMARY
 *
 * Replaced the direct file link for the sample template with a JavaScript-powered download.
 * A new script is added that dynamically generates the CSV content and triggers a download
 * when the user clicks the link, removing the need for a physical sample file on the server.
 * The link in the "sample-download" section has been updated to trigger this script.
 * Added a note to the page to clarify that the Hijri date (iqama_exp) is automatically
 * calculated and saved in a YYYY-MM-DD format based on the uploaded Gregorian date.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Iqama Expiration Dates</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; background-color: #fff; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="file"] { padding: 5px; border: 1px solid #ddd; width: 100%; box-sizing: border-box; }
        input[type="submit"] { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 3px; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        h2 { border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .sample-download { margin-bottom: 20px; padding: 10px; background-color: #e7f3fe; border-left: 4px solid #2196F3; }
        .sample-download a { color: #0d6efd; font-weight: bold; text-decoration: none; cursor: pointer; }
        .sample-download a:hover { text-decoration: underline; }
        .sample-download p { margin: 0; }
        .sample-download p + p { margin-top: 5px; font-size: 0.9em; color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Import and Update Iqama Expiration Dates</h2>
        <p>Please upload an Excel or CSV file (<code>.xlsx</code>, <code>.xls</code>, <code>.csv</code>) with two columns in this order: <strong>iqama</strong> and <strong>iqama_exp_g</strong>.</p>
        <p>The first row should be a header and will be skipped.</p>
        <p>Note: The Hijri date (<code>iqama_exp</code>) will be calculated automatically from the Gregorian date and saved in <code>YYYY-MM-DD</code> format.</p>

        <div class="sample-download">
            <p>Need a template? <a id="downloadSampleLink">Click here to download a sample file.</a></p>
            <p>You can open this file in Excel, add your employee data, and then upload it using the form below.</p>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'success'): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_GET['updated_count']); ?> records updated successfully.
                    <?php if (isset($_GET['not_found_count']) && $_GET['not_found_count'] > 0): ?>
                        <br><?php echo htmlspecialchars($_GET['not_found_count']); ?> records failed because the Iqama number was not found.
                    <?php endif; ?>
                </div>
            <?php elseif ($_GET['status'] == 'error'): ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form action="./includes/process_iqama_import.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="employee_file">Select File:</label>
                <input type="file" name="employee_file" id="employee_file" required accept=".xlsx, .xls, .csv">
            </div>
            <input type="submit" name="import" value="Upload and Process">
        </form>
    </div>

    <script>
    document.getElementById('downloadSampleLink').addEventListener('click', function(event) {
        event.preventDefault(); // Prevent default link behavior

        // Define the CSV content
        const csvContent = "iqama,iqama_exp_g\n" +
                           "2451234567,2026-10-25\n" +
                           "2387654321,2027-01-15\n" +
                           "2519876543,2025-12-05\n" +
                           "2498765432,2026-02-28\n" +
                           "2334567890,2027-05-30";

        // Create a Blob from the CSV content
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        
        // Create a temporary link element
        const link = document.createElement("a");

        // Use the Object URL method to create a temporary link to the blob
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "sample_iqama_import.csv");
        
        // Append to the DOM, click, and then remove
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Clean up the Object URL
        URL.revokeObjectURL(url);
    });
    </script>
</body>
</html>
