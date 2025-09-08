<?php
/*******************************************************************************************************************
 * MODIFICATION SUMMARY (005-emp_query.php):
 *
 * 1. REMOVED OLD LOAN JOIN: The previous LEFT JOIN to the `emp_loan` table has been removed to avoid ambiguity.
 * 2. ADDED SPECIFIC LOAN CHECKS: Two new subqueries have been added to the main SELECT statement.
 * - `has_active_regular_loan`: This checks if the employee has any 'regular' loan with a status of 'pending' or 'approved'.
 * - `has_active_emergency_loan`: This checks if the employee has any 'emergency' loan with a status of 'pending' or 'approved'.
 * 3. IMPROVED ACCURACY: This new method accurately determines the presence of each type of active loan, allowing for more precise control over button visibility in `view_employee.php`.
 *******************************************************************************************************************/

	$empidget = (isset($_GET['emp_id'])? $_GET['emp_id'] : $_SESSION['empid']);

    $get_emp_data = mysqli_query($conDB, "SELECT 
		`employees`.*, 
		CASE 
			WHEN `employees`.`sex` = 1 THEN 'male' 
			WHEN `employees`.`sex` = 2 THEN 'female'
			ELSE ''
		END AS `sex`, 
		`employees`.`emp_id` AS `empid`, 
		`employees`.`id` AS `eid`, 
		`emp_salary`.`basic`, 
		`emp_salary`.`housing`, 
		`emp_salary`.`transport`, 
		`emp_salary`.`food`, 
		`emp_salary`.`misc`, 
		`emp_salary`.`transport`, 
		`emp_salary`.`fuel`, 
		`emp_salary`.`tel`, 
		`emp_salary`.`cashier`, 
		`emp_salary`.`other`, 
		`emp_salary`.`guard`, 
		`emp_salary`.`id` AS `seid`,
		COALESCE(`social`.`count`, 0) as `empsocialcount`,
		`portfolio`.`title`, 
		`portfolio`.`attachment`, 
		`portfolio`.`description`,
		`countries`.`name` AS `country_name`,
		`countries`.`name_ar` AS `country_name_ar`,
		`countries`.`id` AS `country_id`,
		`bank_list`.`name` AS `b_name`,
		`bank_list`.`bank_name_ar` AS `b_name_ar`,
		`bank_list`.`bnk_id` AS `bnk_id`,
		`section`.`section_name` AS `sectin_nme`,
		`section`.`id` AS `sectin_id`,
		`department`.`dep_nme` AS `deptnme`,
		`department`.`dep_nme_ar` AS `deptnme_ar`,
		`contract_period`.`period` AS `period`,
		`emp_gosi`.`gosi_no`,
		`emp_gosi`.`amount`,
		`emp_gosi`.`date_greg`,
		`emp_gosi`.`date_hijri`,
		COALESCE(`fly_status`.`flystus`, 0) AS `flystus`,
		COALESCE(`encashed_status`.`encashstus`, 0) AS `encashstus`,
		COALESCE(`doc_count`.`docu`, 0) AS `docu`,
		COALESCE(`supemp_count`.`supemp`, 0) AS `supemp`,
		COALESCE(`noteemp_count`.`empnote`, 0) AS `empnote`,
		`approved_vacations`.`approval_status` AS `apd_status`,
		`approved_vacations`.`review` AS `apd_review`,
		`approved_vacations`.`id` AS `lastvacid`,
		COALESCE(`emp_documents`.`docs_count`, 0) AS `docs_count`,
		`cars_drv`.`car_id`,
		`cars_drv`.`car_user`,
		`cars_drv`.`rcv_date`,
		`cars_drv`.`rtn_date`,
		`ac_jobs`.`job` AS `jobname`,
		`ac_jobs`.`job_ar` AS `jobname_ar`,
		`ac_jobs`.`id` AS `jobid`,
		`sponsorship`.`sponsor` AS `sponsor`,
		`admin_login`.`dept` AS `av_dept`,
		`admin_login`.`user_type`,
        (SELECT 1 FROM emp_loan WHERE emp_id = `employees`.`emp_id` AND loan_type = 'regular' AND status IN ('dept_manager_pending', 'hr_manager_pending', 'finance_manager_pending', 'gm_pending', 'finance_assistant_pending', 'approved') LIMIT 1) AS has_active_regular_loan,
        (SELECT 1 FROM emp_loan WHERE emp_id = `employees`.`emp_id` AND loan_type = 'emergency' AND status IN ('dept_manager_pending', 'hr_manager_pending', 'finance_manager_pending', 'gm_pending', 'finance_assistant_pending', 'approved') LIMIT 1) AS has_active_emergency_loan,
		COALESCE(
			(COALESCE(`vacation_balance`.`remaining_balance`, `contract_period`.`vac_period`) + COALESCE(`vacation_balance`.`carryover_days`, 0)), 
			`contract_period`.`vac_period`
		) AS `total_remaining_leave`
	FROM `employees`
	LEFT JOIN (SELECT * FROM `emp_salary` WHERE `status`=1 ORDER BY `id` DESC) AS `emp_salary` ON `emp_salary`.`emp_id` = `employees`.`emp_id`
	LEFT JOIN `portfolio` ON `portfolio`.`emp_id` = `employees`.`emp_id`
	LEFT JOIN (SELECT `emp_id`, COUNT(*) as `count` FROM `social` GROUP BY `emp_id`) AS `social` ON `social`.`emp_id` = `employees`.`emp_id`
	LEFT JOIN `countries` ON `countries`.`id` = `employees`.`country`
	LEFT JOIN `bank_list` ON `bank_list`.`bnk_id` = `employees`.`bank_name`
	LEFT JOIN `section` ON `section`.`id` = `employees`.`sectin_nme`
	LEFT JOIN `department` ON `department`.`id` = `employees`.`dept`
	LEFT JOIN `contract_period` ON `contract_period`.`id` = `employees`.`vac_period`
	LEFT JOIN `emp_gosi` ON `emp_gosi`.`emp_id` = `employees`.`emp_id`
	LEFT JOIN `ac_jobs` ON `ac_jobs`.`id` = `employees`.`actual_job`
	LEFT JOIN `admin_login` ON `admin_login`.`emp_id` = `employees`.`emp_id`
	LEFT JOIN `sponsorship` ON `sponsorship`.`id` = `employees`.`emp_sup_type`
	LEFT JOIN (SELECT `emp_id`, COUNT(*) AS `flystus` FROM `emp_vacation` WHERE `note`='Fly' GROUP BY `emp_id`) AS `fly_status` ON `fly_status`.`emp_id` = `employees`.`emp_id`
	LEFT JOIN (SELECT `emp_id`, COUNT(*) AS `encashstus` FROM `emp_vacation` WHERE `note`='Encashed' GROUP BY `emp_id`) AS `encashed_status` ON `encashed_status`.`emp_id` = `employees`.`emp_id`
	LEFT JOIN (SELECT `emp_id`, COUNT(*) AS `docu` FROM `emp_docu` GROUP BY `emp_id`) AS `doc_count` ON `doc_count`.`emp_id` = `employees`.`emp_id`
	LEFT JOIN (SELECT `dept`, COUNT(*) AS `supemp` FROM `employees` WHERE `status`=1 AND `emp_id` <> `employees`.`emp_id` GROUP BY `dept`) AS `supemp_count` ON `supemp_count`.`dept` = `employees`.`dept`
	LEFT JOIN (SELECT * FROM `emp_vacation` WHERE `review`='A' ORDER BY `id` DESC LIMIT 1) AS `approved_vacations` ON `approved_vacations`.`emp_id` = `employees`.`emp_id`
	LEFT JOIN (SELECT * FROM `emp_vacation_balance`) AS `vacation_balance` ON `vacation_balance`.`emp_id` = `employees`.`emp_id` AND `vacation_balance`.`period_end` = (SELECT MAX(`period_end`) FROM `emp_vacation_balance` WHERE `emp_id` = `employees`.`emp_id`)
	LEFT JOIN (SELECT `emp_id`, COUNT(*) AS `docs_count` FROM `emp_docu` WHERE `status`='A' GROUP BY `emp_id`) AS `emp_documents` ON `emp_documents`.`emp_id` = `employees`.`emp_id`
	LEFT JOIN (SELECT * FROM `cars_drv` WHERE `status`=1 ORDER BY `id` DESC LIMIT 1) AS `cars_drv` ON `cars_drv`.`car_user` = `employees`.`emp_id`
	LEFT JOIN (SELECT `emp_id`, COUNT(*) AS `empnote` FROM `emp_notice` WHERE `is_deleted` = 0 GROUP BY `emp_id`) AS `noteemp_count` ON `noteemp_count`.`emp_id` = `employees`.`emp_id`
	WHERE `employees`.`emp_id` = {$empidget} 
	ORDER BY `seid` DESC 
	LIMIT 1
	");

	function car_get_info($id) {
		global $conDB;
		// Return null if ID is empty or not numeric
		if (empty($id) || !is_numeric($id)) {
			return null;
		}
		// Sanitize the ID to prevent SQL injection
		$id = (int)$id;
		$query = "SELECT 
			`cars`.*, 
			`car_maker`.`maker` AS `maker_name`, 
			`car_maker`.`logo_pos`, 
			`car_model`.`model` 
			FROM `cars`
			LEFT JOIN `car_maker` ON `car_maker`.`id` = `cars`.`maker_name`
			LEFT JOIN `car_model` ON `car_model`.`id` = `cars`.`model`
			WHERE `cars`.`id` = $id";
		$result = mysqli_query($conDB, $query);
		// Return null if query fails
		if (!$result) {
			return null;
		}
		$cardata = mysqli_fetch_assoc($result);
		// Free the result set
		mysqli_free_result($result);
		return $cardata ?: null; // Return the data or null if empty
	}

	function lastVacIdGet($empidget) {
		global $conDB;
		// Return null if ID is empty or not numeric
		if (empty($empidget) || !is_numeric($empidget)) {
			return null;
		}
		// Sanitize the ID to prevent SQL injection
		$empidget = (int)$empidget;
		$query = "SELECT 
			`employees`.*, 
			`approved_vacations`.`id` AS `vacid`,
			`approved_vacations`.`return_date` AS `returndate`
			FROM `employees`
			LEFT JOIN (SELECT * FROM `emp_vacation` WHERE `review`='C' ORDER BY `id` DESC LIMIT 1) AS `approved_vacations` ON `approved_vacations`.`emp_id` = `employees`.`emp_id`
			WHERE `employees`.`emp_id` = $empidget";
		$result = mysqli_query($conDB, $query);
		// Return null if query fails
		if (!$result) {
			return null;
		}
		$vacdata = mysqli_fetch_assoc($result);
		// Free the result set
		mysqli_free_result($result);
		return $vacdata ?: null; // Return the data or null if empty
	}
?>
