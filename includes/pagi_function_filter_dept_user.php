<?php
/********************************************

	For More Detail please Visit: 
	
	http://www.discussdesk.com/download-pagination-in-php-and-mysql-with-example.htm

	************************************************/

	
   function displayPaginationBelow($per_page,$page){
        global $conDB;
        
	   $page_url="?";

		$query = "SELECT COUNT(*) as totalCount FROM `employees` WHERE `status`='".$_GET['status']."' AND `fly`='".$_GET['fly']."' AND `dept`='user_dept' ";
	   
    	$rec = mysqli_fetch_array(mysqli_query($conDB, $query));
    	$total = $rec['totalCount'];
        $adjacents = "2"; 

    	$page = ($page == 0 ? 1 : $page);  
    	$start = ($page - 1) * $per_page;								
		
    	$prev = $page - 1;							
    	$next = $page + 1;
        $setLastpage = ceil($total/$per_page);
    	$lpm1 = $setLastpage - 1;
    	
    	$setPaginate = "";
    	if($setLastpage > 1)
    	{	
    		$setPaginate .= "<ul class='pagination pagination-split mt-0'>";
                    $setPaginate .= "<li class='page-item'><span class='page-link'>Page $page of $setLastpage</span></li>";
    		if ($setLastpage < 7 + ($adjacents * 2))
    		{	
    			for ($counter = 1; $counter <= $setLastpage; $counter++)
    			{
    				if ($counter == $page)
    					$setPaginate.= "<li class='page-item active'><a class='page-link'>$counter</a></li>";
    				else
    					$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=$counter&status=$_GET[status]&fly=$_GET[fly]'>$counter</a></li>";					
    			}
    		}
    		elseif($setLastpage > 5 + ($adjacents * 2))
    		{
    			if($page < 1 + ($adjacents * 2))		
    			{
    				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
    				{
    					if ($counter == $page)
    						$setPaginate.= "<li class='page-item active'><a class='page-link'>$counter</a></li>";
    					else
    						$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=$counter&status=$_GET[status]&fly=$_GET[fly]'>$counter</a></li>";					
    				}
    				$setPaginate.= "<li class='page-item'><a class='page-link'>...</a></li>";
    				$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=$lpm1&status=$_GET[status]&fly=$_GET[fly]'>$lpm1</a></li>";
    				$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=$setLastpage&status=$_GET[status]&fly=$_GET[fly]'>$setLastpage</a></li>";		
    			}
    			elseif($setLastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
    			{
    				$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=1&status=$_GET[status]&fly=$_GET[fly]'>1</a></li>";
    				$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=2&status=$_GET[status]&fly=$_GET[fly]'>2</a></li>";
    				$setPaginate.= "<li class='page-item'><a class='page-link'>...</a></li>";
    				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
    				{
    					if ($counter == $page)
    						$setPaginate.= "<li class='page-item active'><a class='page-link'>$counter</a></li>";
    					else
    						$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=$counter&status=$_GET[status]&fly=$_GET[fly]'>$counter</a></li>";					
    				}
    				$setPaginate.= "<li class='page-item'><a class='page-link'>..</a></li>";
    				$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=$lpm1&status=$_GET[status]&fly=$_GET[fly]'>$lpm1</a></li>";
    				$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=$setLastpage&status=$_GET[status]&fly=$_GET[fly]'>$setLastpage</a></li>";		
    			}
    			else
    			{
    				$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=1&status=$_GET[status]&fly=$_GET[fly]'>1</a></li>";
    				$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=2&status=$_GET[status]&fly=$_GET[fly]'>2</a></li>";
    				$setPaginate.= "<li class='page-item'><a class='page-link'>..</a></li>";
    				for ($counter = $setLastpage - (2 + ($adjacents * 2)); $counter <= $setLastpage; $counter++)
    				{
    					if ($counter == $page)
    						$setPaginate.= "<li class='page-item active'><a class='page-link'>$counter</a></li>";
    					else
    						$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=$counter&status=$_GET[status]&fly=$_GET[fly]'>$counter</a></li>";					
    				}
    			}
    		}
    		
    		if ($page < $counter - 1){ 
    			$setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=$next&status=$_GET[status]&fly=$_GET[fly]'>Next</a></li>";
                $setPaginate.= "<li class='page-item'><a class='page-link' href='{$page_url}page=$setLastpage&status=$_GET[status]&fly=$_GET[fly]'>Last</a></li>";
    		}else{
    			$setPaginate.= "<li class='page-item active'><a class='page-link'>Next</a></li>";
                $setPaginate.= "<li class='page-item active'><a class='page-link'>Last</a></li>";
            }

    		$setPaginate.= "</ul>\n";		
    	}
    
    
        return $setPaginate;
    } 
?>