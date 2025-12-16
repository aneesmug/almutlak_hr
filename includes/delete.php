<?php 
	require_once __DIR__ . '/db.php';
	
	// Fetch record before deletion for audit trail
	$table = mysqli_real_escape_string($conDB, $_GET['tbl']);
	$id = mysqli_real_escape_string($conDB, $_GET['id']);
	$fetch_query = "SELECT * FROM `".$table."` WHERE `id` = '".$id."'";
	$fetch_result = mysqli_query($conDB, $fetch_query);
	$deleted_record = mysqli_fetch_assoc($fetch_result);
	
	// Determine module name from table name
	$module_map = [
		'customer' => 'Customer',
		'cars' => 'Vehicle',
		'machines' => 'Machine',
		'section' => 'Location',
		'employees' => 'Employee',
		'users' => 'User'
	];
	$module = isset($module_map[$table]) ? $module_map[$table] : ucfirst($table);
	
	$mysql="DELETE FROM `".$table."` WHERE `id` = '".$id."' ";
	mysqli_query($conDB, $mysql);
	
	// Log deletion with full record data
	if ($deleted_record) {
		ActivityLogger::logDelete($module, 'delete.php', $id, $deleted_record, "Deleted {$module} record from {$table}", $table);
	}
	
//	header("Location: ../view_employee.php?id=".$_GET['id']."");
	echo "<body onload='history.go(-1);'>";
?>