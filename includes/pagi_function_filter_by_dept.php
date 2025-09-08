<?php
/**
 * Generates dynamic pagination HTML for a given query.
 *
 * This function is designed to be reusable. Instead of hardcoding table names or
 * filter conditions, you provide it with a query to count the total records
 * and an array of parameters to build the pagination links.
 *
 * @param mysqli $conDB The database connection object.
 * @param string $count_query The SQL query to count the total number of records (e.g., "SELECT COUNT(*) as totalCount FROM users WHERE status='active'").
 * @param int $per_page The number of items to display per page.
 * @param int $page The current page number, fetched from the URL.
 * @param array $url_params An associative array of parameters to include in the pagination links (e.g., ['status' => 'active', 'dept' => 'IT']).
 * @return string The generated HTML for the pagination controls.
 */
function generatePagination($conDB, $count_query, $per_page, $page, $url_params = []){

    // Build the base URL with existing filters/search parameters
    // http_build_query creates a URL-encoded string from an array (e.g., status=active&dept=IT)
    $page_url = "?" . http_build_query($url_params) . "&";

    // Run the count query to get the total number of records
    $rec = mysqli_fetch_array(mysqli_query($conDB, $count_query));
    if (!$rec) {
        // Handle query error, maybe return an empty string or an error message
        return 'Error executing count query: ' . mysqli_error($conDB);
    }
    $total = $rec['totalCount'];
    $adjacents = "2";

    $page = ($page == 0 ? 1 : $page);
    $start = ($page - 1) * $per_page;

    $prev = $page - 1;
    $next = $page + 1;
    $setLastpage = ceil($total / $per_page);
    $lpm1 = $setLastpage - 1;

    $setPaginate = "";
    if ($setLastpage > 1) {
        $setPaginate .= "<ul class='pagination pagination-split mt-0'>";
        $setPaginate .= "<li class='page-item'><span class='page-link'>Page $page of $setLastpage</span></li>";

        if ($setLastpage < 7 + ($adjacents * 2)) {
            for ($counter = 1; $counter <= $setLastpage; $counter++) {
                if ($counter == $page)
                    $setPaginate .= "<li class='page-item active'><a class='page-link'>$counter</a></li>";
                else
                    $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=$counter'>$counter</a></li>";
            }
        } elseif ($setLastpage > 5 + ($adjacents * 2)) {
            if ($page < 1 + ($adjacents * 2)) {
                for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                    if ($counter == $page)
                        $setPaginate .= "<li class='page-item active'><a class='page-link'>$counter</a></li>";
                    else
                        $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=$counter'>$counter</a></li>";
                }
                $setPaginate .= "<li class='page-item'><a class='page-link'>...</a></li>";
                $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=$lpm1'>$lpm1</a></li>";
                $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=$setLastpage'>$setLastpage</a></li>";
            } elseif ($setLastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=1'>1</a></li>";
                $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=2'>2</a></li>";
                $setPaginate .= "<li class='page-item'><a class='page-link'>...</a></li>";
                for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                    if ($counter == $page)
                        $setPaginate .= "<li class='page-item active'><a class='page-link'>$counter</a></li>";
                    else
                        $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=$counter'>$counter</a></li>";
                }
                $setPaginate .= "<li class='page-item'><a class='page-link'>..</a></li>";
                $setPaginate .= "<li class='page-item'><a class'page-link' href='{$page_url}page=$lpm1'>$lpm1</a></li>";
                $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=$setLastpage'>$setLastpage</a></li>";
            } else {
                $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=1'>1</a></li>";
                $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=2'>2</a></li>";
                $setPaginate .= "<li class='page-item'><a class='page-link'>..</a></li>";
                for ($counter = $setLastpage - (2 + ($adjacents * 2)); $counter <= $setLastpage; $counter++) {
                    if ($counter == $page)
                        $setPaginate .= "<li class='page-item active'><a class='page-link'>$counter</a></li>";
                    else
                        $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=$counter'>$counter</a></li>";
                }
            }
        }

        if ($page < $counter - 1) {
            $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=$next'>Next</a></li>";
            $setPaginate .= "<li class='page-item'><a class='page-link' href='{$page_url}page=$setLastpage'>Last</a></li>";
        } else {
            $setPaginate .= "<li class='page-item active'><a class='page-link'>Next</a></li>";
            $setPaginate .= "<li class='page-item active'><a class='page-link'>Last</a></li>";
        }

        $setPaginate .= "</ul>\n";
    }

    return $setPaginate;
}
?>
