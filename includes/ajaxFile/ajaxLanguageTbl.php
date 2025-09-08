<?php
// File: includes/ajaxFile/ajaxLanguageTbl.php (Updated)

/**************************************************************************************************
 * MODIFICATION SUMMARY
 *
 * 1.  **Fixed Fatal Error**: Corrected the "Unknown column in order clause" error by removing the
 * incorrect use of `mysqli_real_escape_string` on the column name.
 * 2.  **Added Whitelisting**: Implemented a whitelist for sortable columns. This is a crucial
 * security and stability improvement that ensures only expected column names (`lang_key`,
 * `en_translation`, `ar_translation`) can be used in the `ORDER BY` clause, preventing both
 * errors and potential SQL injection.
 *
 **************************************************************************************************/

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

## Read value
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
$row = isset($_POST['start']) ? intval($_POST['start']) : 0;
$rowperpage = isset($_POST['length']) ? intval($_POST['length']) : 10;
$columnIndex = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$columnSortOrder = isset($_POST['order'][0]['dir']) ? mysqli_real_escape_string($conDB, $_POST['order'][0]['dir']) : 'asc';
$searchValue = isset($_POST['search']['value']) ? mysqli_real_escape_string($conDB, $_POST['search']['value']) : '';

## Column Sorting Logic (The Fix)
$sortableColumns = ['lang_key', 'en_translation', 'ar_translation'];
$requestedColumn = isset($_POST['columns'][$columnIndex]['data']) ? $_POST['columns'][$columnIndex]['data'] : 'lang_key';
// Use the whitelisted column name; default to 'lang_key' if not found.
$columnName = in_array($requestedColumn, $sortableColumns) ? $requestedColumn : 'lang_key';


## Base query for fetching pivoted data
$baseQuery = "
    SELECT
        lang_key,
        MAX(CASE WHEN lang_code = 'en' THEN translation END) as en_translation,
        MAX(CASE WHEN lang_code = 'ar' THEN translation END) as ar_translation
    FROM translations
    GROUP BY lang_key
";

## Build the search query dynamically
$searchConditions = [];
if (!empty($searchValue)) {
    $searchableCols = ['lang_key', 'en_translation', 'ar_translation'];
    foreach ($searchableCols as $col) {
        // Note: The search happens on the aliases from the subquery
        $searchConditions[] = "IFNULL({$col}, '') LIKE '%{$searchValue}%'";
    }
}
$searchQuery = empty($searchConditions) ? "" : "WHERE " . implode(' OR ', $searchConditions);

## Total number of records without filtering
$sel = mysqli_query($conDB,"SELECT COUNT(DISTINCT lang_key) AS `allcount` FROM `translations`");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$queryWithFilter = "SELECT COUNT(*) AS allcount FROM ({$baseQuery}) AS t {$searchQuery}";
$sel = mysqli_query($conDB, $queryWithFilter);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "
    SELECT *
    FROM ({$baseQuery}) AS t
    {$searchQuery}
    ORDER BY {$columnName} {$columnSortOrder}
    LIMIT {$row},{$rowperpage}
";

$empRecords = mysqli_query($conDB, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
    $data[] = array(
        "lang_key" => htmlspecialchars($row['lang_key']),
        "en_translation" => htmlspecialchars($row['en_translation'] ?? ''), // Use null coalescing for safety
        "ar_translation" => htmlspecialchars($row['ar_translation'] ?? ''), // Use null coalescing for safety
        "action" => "<div class='btn-group'>
                        <button class='btn btn-info btn-sm update-translation' data-key='".htmlspecialchars($row['lang_key'], ENT_QUOTES)."' data-en='".htmlspecialchars($row['en_translation'] ?? '', ENT_QUOTES)."' data-ar='".htmlspecialchars($row['ar_translation'] ?? '', ENT_QUOTES)."'><i class='fa fa-pencil'></i></button>
                        <button class='btn btn-danger btn-sm delete-translation' data-key='".htmlspecialchars($row['lang_key'], ENT_QUOTES)."'><i class='fa fa-trash'></i></button>
                    </div>"
    );
}

## Response
$response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecordwithFilter,
    "aaData" => $data
);

echo json_encode($response);
?>
