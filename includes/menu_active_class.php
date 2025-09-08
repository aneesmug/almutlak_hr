<?php 

	$current_page = basename($_SERVER['PHP_SELF']);
	
	function dashboard($current_page){
			if (
				
				$current_page == "dashboard.php"
				
				){echo "active ";}
		}
	function new_request($current_page){
			if (
				
				$current_page == "new_request.php"
				
				){echo "active ";}
		}
	function reg_employee($current_page){
			if (
				
				$current_page == "reg_employee.php" OR
				$current_page == "edit_employee.php" OR
				$current_page == "add_vac_emp.php" OR
				$current_page == "view_employee.php" OR			
				$current_page == "filter_employee.php" OR
				$current_page == "add_emp_docs.php" OR
				$current_page == "add_gosi.php" OR
				$current_page == "search.php" OR
				$current_page == "add_emp_slry.php"
				
				){echo "active ";}
		}
	function new_employee($current_page){
			if (
				
				$current_page == "new_employee.php" OR
				$current_page == "new_mocha_employee.php" OR
				$current_page == "new_mnpow_employee.php" OR
				$current_page == "new_user.php"
				
				){echo "active ";}
		}
	function all_customers($current_page){
			if (
				
				$current_page == "all_customers.php" OR
				$current_page == "view_customer.php" OR
				$current_page == "add_cust_card.php" OR
				$current_page == "update_cust_card.php"
				
				){echo "active ";}
		}
	function file_manager($current_page){
			if (
				
				$current_page == "file_manager.php"
				){echo "active ";}
		}
	function log_activity($current_page){
			if (
				
				$current_page == "log_activity.php"
				
				){echo "active ";}
		}
	function all_applied_vac($current_page){
			if (
				
				$current_page == "all_applied_vac.php" OR
				$current_page == "open_applied_vac.php" OR
				$current_page == "apply_vac_emp_dept.php" OR
				$current_page == "open_vac_aply.php"
				
				){echo "active ";}
		}
	function all_users($current_page){
			if (
				
				$current_page == "all_users.php"
				
				){echo "active ";}
		}
	function all_machines($current_page){
			if (
				$current_page == "all_machines.php" OR
				$current_page == "add_machine.php"
				
				){echo "active ";}
		}
	function vacation_schedule_report($current_page){
			if (
				
				$current_page == "vacation_schedule_report.php"
				
				){echo "active ";}
		}
	function all_cars($current_page){
			if (
				
				$current_page == "all_cars.php" OR
				$current_page == "add_car_doc.php" OR
				$current_page == "add_car_driv.php" OR
				$current_page == "edit_car.php" OR
				$current_page == "view_car.php" OR
				$current_page == "add_car.php"
				
				){echo "active ";}
		}
	function contactus($current_page){
			if (
				
				$current_page == "contactus.php" OR
				$current_page == "message_open.php"
				
				){echo "active ";}
		}
	function all_locations($current_page){
			if (
				
				$current_page == "all_locations.php"
				
				){echo "active ";}
		}
	function all_menu_item($current_page){
			if (
				
				$current_page == "all_menu_item.php"
				
				){echo "active ";}
		}
	function customers_survey($current_page){
			if (
				
				$current_page == "customers_survey.php" OR
				$current_page == "view_customer_survey.php"
				
				){echo "active ";}
		}
	function orders($current_page){
			if (
				
				$current_page == "all_orders.php"
				
				){echo "active ";}
		}
	function odrcustomers($current_page){
			if (
				
				$current_page == "odr_customers.php"
				
				){echo "active ";}
		}
?>