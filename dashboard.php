<?php
	// require_once __DIR__ . '/includes/db.php';
	// Initialize the application and load translations
	require_once __DIR__ . '/includes/session_check.php';	
	/*require_once('./includes/auth.php');
	include('./includes/MainClass.php');*/
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	
	include("./includes/avatar_select.php");
	include("./includes/Hijri_GregorianConvert.php");
	$DateConv = new Hijri_GregorianConvert;
	$format="DD/MM/YYYY";

	/****************Employee Allow Page*****************/
    // Only regular employees (not team members) should be redirected to profile
    // ...unless they hold an explicit 'Access Page: Dashboard' Special Access grant.
    if ($user_role == 'Employee' && !user_has_special_access($conDB, $empid ?? '', 'access_dashboard', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
        header("Location: ./profile.php");
        exit();
    }
    /****************Employee Allow Page*****************/

// DEPARTMENT & COMPANY-BASED ACCESS CONTROL FOR DASHBOARD COUNTS
// Determine if user can see all employees or only their department
$can_see_all_employees = (
	function_exists('canSeeAllEmployeesByRole')
		? canSeeAllEmployeesByRole(true)
		: (
			$is_system_admin ||
			$user_type == 'administrator' ||
			$user_dept == 5 ||
			$isHR ||
			$isDeptHr ||
			$user_dept == 1
		)
);

// Build department filter for queries
$company_filter = getCompanyFilterSQL('comp_no', true);

$dept_filter = "";
if (!$can_see_all_employees && isset($user_dept)) {
	$dept_filter = " AND `dept`='".$user_dept."' ";
}

// Add company filter if user has company restrictions
// Use 'comp_no' directly since simple queries don't alias the table
$company_filter = getCompanyFilterSQL('comp_no', true);
$company_filter_alias = getCompanyFilterSQL('e.comp_no', true);

// Add department filter if user has department restrictions
// This works alongside company filter for multi-level access control
$department_filter = getDepartmentFilterSQL('dept', true);
$department_filter_alias = getDepartmentFilterSQL('e.dept', true);

// Add employee filter if user has employee restrictions
$employee_filter = getEmployeeFilterSQL('emp_id', true);
$employee_filter_alias = getEmployeeFilterSQL('e.emp_id', true);

// Fallback department scope (legacy-safe):
// If user has no explicit allowed_departments/allowed_employees restrictions,
// non-HR/non-admin users should still be limited to their own department.
$has_explicit_scope_restrictions = function_exists('hasExplicitEmployeeScopeRestrictions')
	? hasExplicitEmployeeScopeRestrictions(true)
	: (!empty($allowed_companies_array) || !empty($allowed_departments_array) || !empty($allowed_employees_array));
if (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept)) {
	$escapedUserDept = mysqli_real_escape_string($conDB, $user_dept);
	$department_filter = " AND `dept`='" . $escapedUserDept . "'";
	$department_filter_alias = " AND `e`.`dept`='" . $escapedUserDept . "'";
}

// Count Active Employees (Status=1, Not on vacation)
$sql_query_active = "SELECT COUNT(*) AS `activeusr` FROM `employees` WHERE `status`=1 AND `fly`=0 ".$company_filter.$department_filter.$employee_filter;
$sql_count_active = mysqli_query($conDB, $sql_query_active) or error_log("DASHBOARD ERROR - Active Count Query Failed: " . mysqli_error($conDB));
while($rec = mysqli_fetch_assoc($sql_count_active)){$status_cont_active = $rec["activeusr"];}

// Count Terminated Employees
$sql_count_ter = mysqli_query($conDB, "SELECT COUNT(*) AS `ter` FROM `employees` WHERE `status`=0 ".$company_filter.$department_filter.$employee_filter);
while($rec = mysqli_fetch_assoc($sql_count_ter)){$status_cont_ter = $rec["ter"];}

// Count Terminated Employees (Current Year)
$sql_count_ter_year = mysqli_query($conDB, "SELECT COUNT(*) AS `ter_year` FROM `employees` WHERE `status`=0 AND YEAR(`ter_date`)=YEAR(CURDATE()) ".$company_filter.$department_filter.$employee_filter);
while($rec = mysqli_fetch_assoc($sql_count_ter_year)){$status_cont_ter_year = $rec["ter_year"];}

// Count Employees on Departed Vacation (approved, latest vacation vac_type='Fly', employee fly=1)
$sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) AS `flying` FROM `employees` e INNER JOIN (SELECT emp_id, MAX(id) latest_id FROM emp_vacation WHERE current_status='approved' GROUP BY emp_id) AS lv ON lv.emp_id = e.emp_id INNER JOIN emp_vacation ev ON ev.id = lv.latest_id WHERE e.fly=1 AND ev.vac_type='Fly' ".$company_filter_alias.$department_filter_alias.$employee_filter_alias);
while($rec = mysqli_fetch_assoc($sql_count_fly)){$status_cont_fly = $rec["flying"];}

// Count Employees on Local Vacation (approved, latest vacation vac_type IN ('Local Vacation','Encashed'), employee fly=1)
$sql_count_local_vac = mysqli_query($conDB, "SELECT COUNT(*) AS `local_vac` FROM `employees` e INNER JOIN (SELECT emp_id, MAX(id) latest_id FROM emp_vacation WHERE current_status='approved' GROUP BY emp_id) AS lv ON lv.emp_id = e.emp_id INNER JOIN emp_vacation ev ON ev.id = lv.latest_id WHERE e.fly=1 AND ev.vac_type IN ('Local Vacation','Encashed') ".$company_filter_alias.$department_filter_alias.$employee_filter_alias);
while($rec = mysqli_fetch_assoc($sql_count_local_vac)){$status_cont_local_vac = $rec["local_vac"];}

// Count Total Employees
$sql_query_tot = "SELECT COUNT(*) AS `tot` FROM `employees` WHERE 1=1 ".$company_filter.$department_filter.$employee_filter;
$sql_count_tot = mysqli_query($conDB, $sql_query_tot) or error_log("DASHBOARD ERROR - Total Count Query Failed: " . mysqli_error($conDB));
while($rec = mysqli_fetch_assoc($sql_count_tot)){$status_cont_tot = $rec["tot"];}

// Count Man Power Employees
$sql_count_man_power = mysqli_query($conDB, "SELECT COUNT(*) AS `manpwr` FROM `employees` WHERE `emp_sup_type`='man_power' AND `status`=1 ".$company_filter.$department_filter.$employee_filter);
while($rec = mysqli_fetch_assoc($sql_count_man_power)){$status_cont_man_power = $rec["manpwr"];}

// Percentages for dashboard progress bars
$totalEmployees = max(1, (int)($status_cont_tot ?? 0));
$pct_active      = round((($status_cont_active ?? 0) / $totalEmployees) * 100, 1);
$pct_man_power   = round((($status_cont_man_power ?? 0) / $totalEmployees) * 100, 1);
$pct_on_job      = round(((($status_cont_man_power ?? 0) + ($status_cont_active ?? 0)) / $totalEmployees) * 100, 1);
$pct_fly         = round((($status_cont_fly ?? 0) / $totalEmployees) * 100, 1);
$pct_local_vac   = round((($status_cont_local_vac ?? 0) / $totalEmployees) * 100, 1);
$pct_terminated  = round((($status_cont_ter ?? 0) / $totalEmployees) * 100, 1);
$pct_terminated_year = round((($status_cont_ter_year ?? 0) / $totalEmployees) * 100, 1);
$pct_total       = 100.0;

// ================= DASHBOARD PIE CHART DATA (active employees, scoped) =================
require_once __DIR__ . '/includes/dashboard_chart_helpers.php';

$dashboard_chart_data = get_all_dashboard_chart_data(
    $conDB,
    $company_filter_alias . $department_filter_alias . $employee_filter_alias,
    $company_filter . $department_filter . $employee_filter,
    ['department_ids' => [], 'company_ids' => [], 'sex' => null, 'country_id' => null, 'saudi' => null],
    ($is_rtl ?? false)
);

// Department / Company option lists for the chart filter dropdowns (scoped the same way as the rest of the dashboard)
$chart_filter_departments = [];
$dept_opt_query = mysqli_query($conDB, "SELECT DISTINCT `id`, `dep_nme`, `dep_nme_ar` FROM `department` ORDER BY `dep_nme`");
if ($dept_opt_query) {
    while ($row = mysqli_fetch_assoc($dept_opt_query)) {
        $chart_filter_departments[] = $row;
    }
}

$chart_filter_companies = [];
$comp_opt_query = mysqli_query($conDB, "SELECT DISTINCT `comp_id`, `comp_name`, `comp_name_ar` FROM `companies` ORDER BY `comp_name`");
if ($comp_opt_query) {
    while ($row = mysqli_fetch_assoc($comp_opt_query)) {
        $chart_filter_companies[] = $row;
    }
}

// Access scope labels for dashboard card
$allowed_company_names = 'All Companies';
if (!empty($allowed_companies_array)) {
	$company_ids = implode(',', array_map('intval', $allowed_companies_array));
	$comp_query = mysqli_query($conDB, "SELECT GROUP_CONCAT(DISTINCT `comp_name` SEPARATOR ', ') AS `names` FROM `companies` WHERE `id` IN ($company_ids) OR `comp_id` IN ($company_ids)");
	if ($comp_query && $comp_row = mysqli_fetch_assoc($comp_query)) {
		$allowed_company_names = $comp_row['names'] ?: 'All Companies';
	}
}

$allowed_department_names = 'All Departments';
if (!empty($allowed_departments_array)) {
	$department_ids = implode(',', array_map('intval', $allowed_departments_array));
	$dept_query = mysqli_query($conDB, "SELECT GROUP_CONCAT(DISTINCT `dep_nme` SEPARATOR ', ') AS `names` FROM `department` WHERE `id` IN ($department_ids)");
	if ($dept_query && $dept_row = mysqli_fetch_assoc($dept_query)) {
		$allowed_department_names = $dept_row['names'] ?: 'All Departments';
	}
}

$allowed_employee_names = null; // Only show if employees are specifically assigned
if (!empty($allowed_employees_array)) {
	$employee_ids = implode(',', array_map('intval', $allowed_employees_array));
	$emp_query = mysqli_query($conDB, "SELECT GROUP_CONCAT(DISTINCT CONCAT(`name`) SEPARATOR ', ') AS `names` FROM `employees` WHERE `emp_id` IN ($employee_ids)");
	if ($emp_query && $emp_row = mysqli_fetch_assoc($emp_query)) {
		$allowed_employee_names = $emp_row['names'];
	}
}

// Access Scope card: [allowed count, total, name list] per scope. Empty name list = full access.
$scope_split = function ($names, $all_label) {
	$names = trim((string)$names);
	return ($names === '' || $names === $all_label) ? [] : array_values(array_filter(array_map('trim', explode(',', $names)), 'strlen'));
};
$scope_count = function ($sql) use ($conDB) {
	$res = mysqli_query($conDB, $sql);
	$row = $res ? mysqli_fetch_row($res) : null;
	return $row ? (int)$row[0] : 0;
};
$scope_company_list = $scope_split($allowed_company_names, 'All Companies');
$scope_department_list = $scope_split($allowed_department_names, 'All Departments');
$scope_employee_list = $scope_split($allowed_employee_names, '');
$scope_company_total = $scope_count("SELECT COUNT(*) FROM `companies`");
$scope_department_total = $scope_count("SELECT COUNT(*) FROM `department`");
$scope_employee_total = $scope_employee_list ? $scope_count("SELECT COUNT(*) FROM `employees` WHERE `status` = 1") : 0;

// Only HR, Department HR, and System Admin can view dashboard expiry sections
$can_view_expiry_block = ($is_system_admin || $isHR || $isDeptHr || $isGR_Officer);

// Document expiry bands for dashboard cards (30/20/10 day windows)
$doc_expiry_bands = [
	'info' => [],
	'warning' => [],
	'danger' => []
];
$doc_expiry_counts = [
	'info' => 0,
	'warning' => 0,
	'danger' => 0
];
$doc_expiry_doc_counts = [
	'info' => ['id_iqama' => 0, 'passport' => 0, 'contract' => 0],
	'warning' => ['id_iqama' => 0, 'passport' => 0, 'contract' => 0],
	'danger' => ['id_iqama' => 0, 'passport' => 0, 'contract' => 0]
];
$doc_expiry_data_json = '{"info":[],"warning":[],"danger":[]}';

if ($can_view_expiry_block) {
	$doc_expiry_query = "SELECT
		`e`.`emp_id`,
		`e`.`name`,
		`e`.`iqama_exp_g`,
		`e`.`passport_exp`,
		`e`.`joining_date`,
		`e`.`vac_period`,
		`d`.`dep_nme`,
		`d`.`dep_nme_ar`
		FROM `employees` AS `e`
		LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
		WHERE `e`.`status` = 1 " . $company_filter_alias . $department_filter_alias . $employee_filter_alias;

	$doc_expiry_result = mysqli_query($conDB, $doc_expiry_query);
	if ($doc_expiry_result) {
		$today_ts = strtotime(date('Y-m-d'));
		$seconds_per_day = 86400;

		while ($emp = mysqli_fetch_assoc($doc_expiry_result)) {
			$documents = [
				['type' => 'ID/Iqama', 'key' => 'id_iqama', 'date' => $emp['iqama_exp_g'] ?? null],
				['type' => 'Passport', 'key' => 'passport', 'date' => $emp['passport_exp'] ?? null]
			];

			$contract_expiry_date = computeContractExpiry(
				$emp['joining_date'] ?? null,
				isset($emp['vac_period']) ? (int)$emp['vac_period'] : null,
				'Y-m-d'
			);
			if (!empty($contract_expiry_date)) {
				$documents[] = ['type' => 'Contract Renewal', 'key' => 'contract', 'date' => $contract_expiry_date];
			}

			foreach ($documents as $doc) {
				$raw_date = $doc['date'] ?? '';
				if (empty($raw_date) || $raw_date === '0000-00-00') {
					continue;
				}

				$normalized_date = str_replace('/', '-', $raw_date);
				$expiry_ts = strtotime($normalized_date);
				if ($expiry_ts === false) {
					continue;
				}

				$days_remaining = (int) floor(($expiry_ts - $today_ts) / $seconds_per_day);
				if ($days_remaining > 30) {
					continue;
				}

				if ($days_remaining <= 10) {
					$band = 'danger';
				} elseif ($days_remaining <= 20) {
					$band = 'warning';
				} else {
					$band = 'info';
				}

				$doc_key = $doc['key'] ?? null;
				if ($doc_key && isset($doc_expiry_doc_counts[$band][$doc_key])) {
					$doc_expiry_doc_counts[$band][$doc_key]++;
				}

				$emp_id_key = (string)$emp['emp_id'];
				if (!isset($doc_expiry_bands[$band][$emp_id_key])) {
					$doc_expiry_bands[$band][$emp_id_key] = [
						'emp_id' => $emp['emp_id'],
						'name' => getDisplayName($emp['name']),
						'department' => ($is_rtl ?? false) ? ($emp['dep_nme_ar'] ?? '') : ($emp['dep_nme'] ?? ''),
						'docs' => []
					];
				}

				$doc_expiry_bands[$band][$emp_id_key]['docs'][] = [
					'type' => $doc['type'],
					'expiry' => date('Y-m-d', $expiry_ts),
					'days' => $days_remaining,
					'status' => $days_remaining < 0 ? 'expired' : ($days_remaining === 0 ? 'today' : 'upcoming')
				];
			}
		}

		$doc_expiry_counts = [
			'info' => count($doc_expiry_bands['info']),
			'warning' => count($doc_expiry_bands['warning']),
			'danger' => count($doc_expiry_bands['danger'])
		];

		$doc_expiry_data_for_js = [
			'info' => array_values($doc_expiry_bands['info']),
			'warning' => array_values($doc_expiry_bands['warning']),
			'danger' => array_values($doc_expiry_bands['danger'])
		];

		$doc_expiry_data_json = json_encode(
			$doc_expiry_data_for_js,
			JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
		);
	}
}

if(isset($_POST['submit'])){
	if($_POST['iqama_exp']){
		$iqama_exp_gup = $DateConv->HijriToGregorian($_POST['iqama_exp'], $format);
		$iqama_exp_g_up = date("Y/m/d", strtotime($iqama_exp_gup));
		mysqli_query($conDB, "UPDATE `employees` SET `iqama_exp`='".$_POST['iqama_exp']."', `iqama_exp_g`='".$iqama_exp_g_up."' WHERE `id`='".$_POST['iid']."' ") or die();
		$message = "<div class='alert alert-success bg-success text-white border-0'><strong>Successfully!</strong> Your car details will be successfully edit!</div>";
		header( "refresh:1 ; url= ./dashboard.php" );

	} else {
		$message = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please select expiry date!</div>";
	}
}


?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
    <head>
        <meta charset="utf-8" />
        <title><?=$site_title ?> - <?=__('dashboard') ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
		
		<!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <!-- Responsive datatable examples -->
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- Multi Item Selection examples -->
        <link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
        <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">

		<link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.css" rel="stylesheet">
        <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">

        <!-- App css -->
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <!-- <link href="assets/css/icons.css" rel="stylesheet" type="text/css" /> -->
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

        <script src="assets/js/modernizr.min.js"></script>

		<style>
			/* Modern Stats Card Design - Matching dashbydepart.php */
			.stats-card {
				border-radius: 16px;
				box-shadow: 0 2px 12px rgba(0,0,0,0.08);
				padding: 32px 24px 24px 24px;
				margin-bottom: 28px;
				transition: box-shadow 0.2s, transform 0.2s;
				border: none;
				position: relative;
				min-height: 180px;
				display: flex;
				flex-direction: row;
				align-items: center;
				background: var(--card-gradient, linear-gradient(90deg,#2196f3 0%,#21cbf3 100%));
				color: #fff;
				overflow: hidden;
			}
			.stats-card:hover {
				box-shadow: 0 8px 32px rgba(0,0,0,0.18);
				transform: translateY(-4px) scale(1.02);
			}
			.stats-card-icon {
				background: rgba(255,255,255,0.18);
				border-radius: 50%;
				width: 72px;
				height: 72px;
				display: flex;
				align-items: center;
				justify-content: center;
				font-size: 40px;
				margin-right: 28px;
				box-shadow: 0 2px 16px rgba(0,0,0,0.12);
				position: relative;
				transition: transform 0.2s;
				flex-direction: column;
			}
			.stats-card-count-circle {
				background: #fff;
				color: #2196f3;
				border-radius: 50%;
				width: 38px;
				height: 38px;
				display: flex;
				align-items: center;
				justify-content: center;
				font-size: 20px;
				font-weight: 700;
				margin-bottom: 6px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.10);
			}
			.stats-card-icon i {
				font-size: 28px;
				color: #2196f3;
			}
			.stats-card-icon:hover {
				transform: scale(1.10) rotate(-6deg);
			}
			.stats-card-icon .stats-card-tooltip {
				display: none;
				position: absolute;
				top: -32px;
				left: 50%;
				transform: translateX(-50%);
				background: #222;
				color: #fff;
				padding: 4px 10px;
				border-radius: 6px;
				font-size: 12px;
				white-space: nowrap;
				z-index: 10;
			}
			.stats-card-icon:hover .stats-card-tooltip {
				display: block;
			}
			.stats-card-content {
				flex: 1;
				display: flex;
				flex-direction: column;
				align-items: flex-start;
				position: relative;
			}
			.stats-card-label {
				font-size: 18px;
				font-weight: 700;
				color: #fff;
				margin-bottom: 12px;
				letter-spacing: 0.5px;
			}
			.stats-card-footer {
				display: flex;
				align-items: center;
				gap: 18px;
			}
			.stats-card-percentage {
				font-size: 15px;
				font-weight: 600;
				display: flex;
				align-items: center;
			}
			.stats-card-percentage.positive {
				color: #34c38f;
			}
			.stats-card-percentage.negative {
				color: #f46a6a;
			}
			.stats-card-percentage i {
				font-size: 18px;
				margin-right: 4px;
			}
			@media (max-width: 767px) {
				.stats-card {
					flex-direction: column;
					align-items: flex-start;
					padding: 16px 8px;
					min-height: 160px;
				}
				.stats-card-icon {
					margin-bottom: 10px;
					margin-right: 0;
				}
			}

			/* Badge-based card styling for Allowed Employees */
			.allowed-employees-card {
				background: #f8f9fa;
				border: 1px solid #e0e0e0;
				border-radius: 8px;
				padding: 16px;
			}

			.allowed-employees-card-title {
				text-transform: uppercase;
				letter-spacing: 0.5px;
				font-size: 13px;
				color: #333;
				margin-bottom: 12px;
				font-weight: 600;
			}

			.allowed-employees-card-content {
				display: flex;
				flex-wrap: wrap;
				gap: 6px;
				max-height: 160px;
				overflow-y: auto;
				padding-right: 4px;
			}
			.allowed-employees-card-content::-webkit-scrollbar {
				width: 6px;
			}
			.allowed-employees-card-content::-webkit-scrollbar-thumb {
				background: rgba(0, 0, 0, 0.18);
				border-radius: 6px;
			}
			.allowed-employees-card-content::-webkit-scrollbar-track {
				background: transparent;
			}

			.allowed-employees-card .employee-badge {
				display: inline-flex;
				align-items: center;
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: #fff;
				padding: 6px 12px;
				border-radius: 20px;
				font-size: 12px;
				box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
				white-space: nowrap;
			}

			.allowed-employees-card .employee-badge.all-employees {
				background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
				color: #333;
				box-shadow: 0 2px 8px rgba(168, 237, 234, 0.3);
			}

			.allowed-employees-card .no-data-message {
				color: #999;
				font-size: 13px;
				font-style: italic;
			}

			/* Document Expiry cards + SweetAlert listing */
			.doc-expiry-card {
				cursor: pointer;
				min-height: 145px;
			}

			.doc-expiry-card .stats-card-label small {
				display: block;
				font-size: 12px;
				opacity: 0.9;
				margin-top: 4px;
			}

			/* Document expiry modal (Swal popup lives outside .sr-page - literal colors + app-dark overrides) */
			.swal2-popup.dex-popup { padding: 22px 22px 18px; border-radius: 16px; }
			.swal2-popup.dex-popup .swal2-title { padding: 0; margin: 0 0 14px; text-align: start; font-size: 19px; font-weight: 700; color: #0f172a; }
			.swal2-popup.dex-popup .swal2-html-container { margin: 0; padding: 0; text-align: start; overflow: visible; }
			.dex-title { display: flex; align-items: center; gap: 12px; }
			.dex-title-icon { width: 40px; height: 40px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; flex: 0 0 auto; }
			.dex-title-icon.tone-red { background: #fef2f2; color: #dc2626; }
			.dex-title-icon.tone-amber { background: #fffbeb; color: #d97706; }
			.dex-title-icon.tone-sky { background: #f0f9ff; color: #0284c7; }
			.dex-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px; }
			@media (max-width: 640px) { .dex-stats { grid-template-columns: 1fr; } }
			.dex-stat {
				--ds: #6366f1; --ds-fg: #4f46e5; --ds-bg: #eef2ff;
				position: relative; overflow: hidden; display: flex; flex-direction: column; gap: 10px; text-align: start;
				padding: 14px 16px 12px; border-radius: 14px; border: 1px solid #e5e7eb; background: #fff;
			}
			.dex-stat::before { content: ""; position: absolute; inset: 0 0 auto; height: 3px; background: linear-gradient(90deg, var(--ds), color-mix(in srgb, var(--ds) 30%, transparent)); }
			.dex-stat.is-sky { --ds: #0ea5e9; --ds-fg: #0369a1; --ds-bg: #f0f9ff; }
			.dex-stat.is-amber { --ds: #f59e0b; --ds-fg: #b45309; --ds-bg: #fffbeb; }
			.dex-stat.is-red { --ds: #ef4444; --ds-fg: #b91c1c; --ds-bg: #fef2f2; }
			.dex-stat.is-green { --ds: #10b981; --ds-fg: #047857; --ds-bg: #ecfdf5; }
			.dex-stat-top { display: flex; align-items: center; gap: 12px; }
			.dex-stat-icon { width: 40px; height: 40px; border-radius: 12px; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; background: var(--ds-bg); color: var(--ds-fg); }
			.dex-stat-text { min-width: 0; display: flex; flex-direction: column; }
			.dex-stat-label { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
			.dex-stat-value { display: block; font-size: 24px; font-weight: 800; color: var(--ds-fg); line-height: 1.15; letter-spacing: -.3px; font-variant-numeric: tabular-nums; }
			.dex-stat-sub { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 11.5px; color: #64748b; }
			.dex-stat-bar { height: 5px; border-radius: 999px; background: #eef1f5; overflow: hidden; }
			.dex-stat-bar > span { display: block; height: 100%; border-radius: 999px; background: var(--ds); }
			.dex-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin-bottom: 10px; }
			.dex-search { position: relative; flex: 0 1 280px; min-width: 200px; }
			.dex-search i { position: absolute; inset-inline-start: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; }
			.dex-search input { width: 100%; height: 36px; padding-inline: 32px 10px; border: 1px solid #e5e7eb; border-radius: 9px; font-size: 13px; background: #f8fafc; color: #0f172a; outline: 0; }
			.dex-search input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, .15); background: #fff; }
			.dex-chips { display: flex; flex-wrap: wrap; gap: 6px; }
			.dex-chip { border: 1px solid #e5e7eb; background: #fff; color: #475569; border-radius: 999px; padding: 4px 11px; font-size: 12px; font-weight: 600; cursor: pointer; }
			.dex-chip span { margin-inline-start: 4px; padding: 0 6px; border-radius: 999px; background: #f1f5f9; font-size: 11px; }
			.dex-chip:hover { border-color: #a5b4fc; }
			.dex-chip.active { background: #6366f1; border-color: #6366f1; color: #fff; }
			.dex-chip.active span { background: rgba(255, 255, 255, .25); }
			.dex-list { max-height: 420px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; }
			.dex-row { display: grid; grid-template-columns: minmax(200px, 300px) 1fr auto; align-items: center; gap: 12px; padding: 10px 14px; border-bottom: 1px solid #f1f5f9; }
			.dex-row:last-of-type { border-bottom: 0; }
			.dex-row:hover { background: #f8fafc; }
			.dex-emp { display: flex; align-items: center; gap: 10px; min-width: 0; }
			.dex-avatar { width: 34px; height: 34px; border-radius: 50%; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; background: #eef2ff; color: #4f46e5; }
			.dex-emp-text { display: flex; flex-direction: column; min-width: 0; }
			.dex-emp-name { font-size: 13.5px; font-weight: 600; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
			.dex-emp-sub { font-size: 12px; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
			.dex-mono { font-family: "SFMono-Regular", Consolas, monospace; }
			.dex-docs { display: flex; flex-wrap: wrap; gap: 6px; }
			.dex-doc { display: inline-flex; align-items: center; gap: 8px; padding: 4px 4px 4px 10px; border-radius: 8px; font-size: 12px; border: 1px solid; }
			.dex-doc-type { font-weight: 700; }
			.dex-doc-date { opacity: .85; white-space: nowrap; }
			.dex-doc-days { padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 11px; white-space: nowrap; }
			.dex-doc.tone-red { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
			.dex-doc.tone-red .dex-doc-days { background: #dc2626; color: #fff; }
			.dex-doc.tone-amber { background: #fffbeb; border-color: #fde68a; color: #b45309; }
			.dex-doc.tone-amber .dex-doc-days { background: #f59e0b; color: #fff; }
			.dex-doc.tone-sky { background: #f0f9ff; border-color: #bae6fd; color: #0369a1; }
			.dex-doc.tone-sky .dex-doc-days { background: #0ea5e9; color: #fff; }
			.dex-view { display: inline-flex; align-items: center; gap: 5px; height: 32px; padding: 0 12px; border-radius: 8px; font-size: 12.5px; font-weight: 600; color: #4f46e5; border: 1px solid #c7d2fe; background: #eef2ff; text-decoration: none !important; white-space: nowrap; }
			.dex-view:hover { background: #6366f1; border-color: #6366f1; color: #fff; }
			.dex-view i { font-size: 16px; }
			.dex-empty { padding: 28px 16px; text-align: center; color: #64748b; font-size: 13px; }
			.dex-empty i { display: block; font-size: 30px; color: #cbd5e1; margin-bottom: 4px; }
			@media (max-width: 767px) {
				.dex-row { grid-template-columns: 1fr auto; }
				.dex-docs { grid-column: 1 / -1; grid-row: 2; }
				.dex-view span { display: none; }
			}
			html.app-dark .swal2-popup.dex-popup .swal2-title, html.app-dark .dex-stat-value, html.app-dark .dex-emp-name { color: #f1f5f9; }
			html.app-dark .dex-stat, html.app-dark .dex-list, html.app-dark .dex-chip { background: #1d2436; border-color: #2a3348; color: #d8dee9; }
			html.app-dark .dex-stat { --ds-fg: #a5b4fc; --ds-bg: #262d52; }
			html.app-dark .dex-stat.is-sky { --ds-fg: #7dd3fc; --ds-bg: #102c3d; }
			html.app-dark .dex-stat.is-amber { --ds-fg: #fcd34d; --ds-bg: #3a2e12; }
			html.app-dark .dex-stat.is-red { --ds-fg: #fca5a5; --ds-bg: #3a1717; }
			html.app-dark .dex-stat.is-green { --ds-fg: #6ee7b7; --ds-bg: #10302a; }
			html.app-dark .dex-stat-label, html.app-dark .dex-stat-sub { color: #94a3b8; }
			html.app-dark .dex-stat-bar { background: #2a3348; }
			html.app-dark .dex-search input { background: #161c2a; border-color: #2a3348; color: #f1f5f9; }
			html.app-dark .dex-row { border-color: #252e44; }
			html.app-dark .dex-row:hover { background: #252e44; }
			html.app-dark .dex-chip span { background: #252e44; }
			html.app-dark .dex-avatar, html.app-dark .dex-view { background: #252b4a; border-color: #4547a0; color: #c7d2fe; }
			html.app-dark .dex-doc.tone-red { background: #3a1717; border-color: #7a2a2a; color: #fca5a5; }
			html.app-dark .dex-doc.tone-amber { background: #3a2e12; border-color: #6b5417; color: #fcd34d; }
			html.app-dark .dex-doc.tone-sky { background: #102c3d; border-color: #1d5673; color: #7dd3fc; }

			/* Dashboard demographic pie charts - always light background regardless of OS theme */
			.dash-pie-card {
				background: #ffffff;
				border: 1px solid #e5e7eb;
				border-radius: 12px;
				padding: 16px;
				height: 100%;
			}
			.dash-pie-title {
				font-size: 14px;
				font-weight: 700;
				color: #212529;
				margin-bottom: 10px;
				text-align: center;
			}
			.dash-pie-canvas-wrap {
				position: relative;
				min-height: 280px;
				margin: 0 auto;
			}
			/* Slice hover "pop" - bounce-style scale via overshoot easing, plus lift shadow */
			.dash-pie-canvas-wrap .apexcharts-pie-area {
				transform-box: fill-box;
				transform-origin: center;
				transition: transform 0.35s cubic-bezier(.34, 1.61, .7, 1), filter 0.2s ease-out;
				cursor: pointer;
			}
			.dash-pie-canvas-wrap .apexcharts-pie-area:hover {
				transform: scale(1.09);
				filter: drop-shadow(0 6px 10px rgba(0, 0, 0, .35));
			}
			.dash-pie-canvas-wrap .apexcharts-pie-series:hover .apexcharts-pie-area:not(:hover) {
				opacity: .85;
			}
			.dash-pie-filter-row label {
				font-size: 13px;
				font-weight: 600;
				color: #495057;
			}
			#dashChartApplyFilterBtn, #dashChartResetFilterBtn {
				margin-top: 4px;
			}

			.dash-slice-filter-badge {
				cursor: pointer;
				font-size: 12px;
				padding: 6px 10px;
				margin-top: 10px;
				display: inline-block;
			}
		</style>
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
		<style>
			/* ---------- New GUI (sr-* design system) ---------- */
			.dash-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 20px; }
			@media (max-width: 1199px) { .dash-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
			@media (max-width: 575px) { .dash-kpis { grid-template-columns: 1fr; } }
			.dash-kpi {
				--kpi: #6366f1; --kpi-soft: rgba(99, 102, 241, .12);
				position: relative; display: flex; flex-direction: column; gap: 10px; padding: 16px 18px; min-width: 0;
				border-radius: var(--sr-radius); background: var(--sr-surface); border: 1px solid var(--sr-border); box-shadow: var(--sr-shadow);
				color: var(--sr-text-2) !important; text-decoration: none !important; overflow: hidden;
				transition: transform .15s, border-color .15s, box-shadow .15s;
			}
			.dash-kpi::before { content: ''; position: absolute; top: 0; bottom: 0; inset-inline-start: 0; width: 4px; background: var(--kpi); }
			a.dash-kpi:hover { transform: translateY(-2px); border-color: var(--kpi); box-shadow: 0 8px 24px rgba(15, 23, 42, .08); }
			.dash-kpi.is-blue   { --kpi: #3b82f6; --kpi-soft: rgba(59, 130, 246, .12); }
			.dash-kpi.is-indigo { --kpi: #6366f1; --kpi-soft: rgba(99, 102, 241, .12); }
			.dash-kpi.is-violet { --kpi: #8b5cf6; --kpi-soft: rgba(139, 92, 246, .12); }
			.dash-kpi.is-sky    { --kpi: #0ea5e9; --kpi-soft: rgba(14, 165, 233, .12); }
			.dash-kpi.is-amber  { --kpi: #f59e0b; --kpi-soft: rgba(245, 158, 11, .14); }
			.dash-kpi.is-teal   { --kpi: #14b8a6; --kpi-soft: rgba(20, 184, 166, .12); }
			.dash-kpi.is-red    { --kpi: #ef4444; --kpi-soft: rgba(239, 68, 68, .12); }
			.dash-kpi.is-rose   { --kpi: #f43f5e; --kpi-soft: rgba(244, 63, 94, .12); }
			.dash-kpi.is-green  { --kpi: #22c55e; --kpi-soft: rgba(34, 197, 94, .12); }
			.dash-kpi-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
			.dash-kpi-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); line-height: 1.35; }
			.dash-kpi-value { margin-top: 4px; font-size: 28px; font-weight: 800; line-height: 1; color: var(--sr-text); font-variant-numeric: tabular-nums; }
			.dash-kpi-icon {
				flex: 0 0 auto; width: 42px; height: 42px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
				font-size: 18px; color: var(--kpi); background: var(--kpi-soft);
			}
			.dash-kpi-bar { height: 6px; border-radius: 6px; background: var(--sr-surface-3); overflow: hidden; }
			.dash-kpi-bar span { display: block; height: 100%; border-radius: 6px; background: var(--kpi); transition: width .6s; }
			.dash-kpi-foot { display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--sr-muted); }
			.dash-kpi-foot b { color: var(--sr-text-2); }
			.dash-kpi-foot i { color: var(--kpi); }

			/* Demographics */
			.dash-filters { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; align-items: end; padding: 14px 18px; border-bottom: 1px solid var(--sr-border); background: var(--sr-surface-2); }
			@media (max-width: 991px) { .dash-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
			@media (max-width: 575px) { .dash-filters { grid-template-columns: 1fr; } }
			.dash-filters label { display: block; margin: 0 0 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); }
			.dash-filters .select2-container--default .select2-selection--multiple { min-height: 38px; border-radius: 10px; border: 1px solid var(--sr-border-strong); background: var(--sr-surface); }
			.dash-check { display: flex; align-items: center; gap: 8px; margin: 0 0 9px; font-size: 13px; font-weight: 600; color: var(--sr-text-2); cursor: pointer; text-transform: none !important; letter-spacing: 0 !important; }
			.dash-check input { width: 16px; height: 16px; accent-color: var(--sr-accent); }
			.dash-tip { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 10px 18px 0; font-size: 12px; color: var(--sr-muted); }
			.dash-charts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; padding: 14px 18px 18px; }
			@media (max-width: 1399px) { .dash-charts { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
			@media (max-width: 767px) { .dash-charts { grid-template-columns: 1fr; } }
			.sr-page .dash-pie-card { border-radius: 12px; border-color: var(--sr-border); }
			.sr-page .dash-pie-title { font-size: 12px; text-transform: uppercase; letter-spacing: .4px; color: #475569; }
			.sr-page .dash-slice-filter-badge { margin-top: 0; }

			/* Document expiry alert tiles */
			.dash-expiry { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; padding: 18px; }
			@media (max-width: 991px) { .dash-expiry { grid-template-columns: 1fr; } }
			.sr-page .doc-expiry-card {
				--dx: #0ea5e9; --dx-fg: var(--tone-sky-fg); --dx-bg: var(--tone-sky-bg);
				position: relative; overflow: hidden; display: flex; flex-direction: column; gap: 14px; min-height: 0;
				padding: 18px 18px 14px; border-radius: 14px; border: 1px solid var(--sr-border); background: var(--sr-surface, #fff);
				color: var(--sr-text); cursor: pointer; transition: transform .15s, box-shadow .15s, border-color .15s;
			}
			.sr-page .doc-expiry-card::before { content: ""; position: absolute; inset: 0 0 auto; height: 4px; background: linear-gradient(90deg, var(--dx), color-mix(in srgb, var(--dx) 35%, transparent)); }
			.sr-page .doc-expiry-card.tone-amber { --dx: #f59e0b; --dx-fg: var(--tone-amber-fg); --dx-bg: var(--tone-amber-bg); }
			.sr-page .doc-expiry-card.tone-red { --dx: #ef4444; --dx-fg: var(--tone-red-fg); --dx-bg: var(--tone-red-bg); }
			.sr-page .doc-expiry-card:hover { transform: translateY(-3px); border-color: color-mix(in srgb, var(--dx) 45%, var(--sr-border)); box-shadow: 0 12px 28px -10px color-mix(in srgb, var(--dx) 45%, transparent); }
			.doc-expiry-card .dx-head { display: flex; align-items: center; gap: 10px; }
			.doc-expiry-card .dx-icon { width: 38px; height: 38px; border-radius: 11px; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; background: var(--dx-bg); color: var(--dx-fg); }
			.doc-expiry-card .dx-title { flex: 1; min-width: 0; font-size: 14px; font-weight: 700; color: var(--sr-text); }
			.doc-expiry-card .dx-range { flex: 0 0 auto; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; background: var(--dx-bg); color: var(--dx-fg); white-space: nowrap; }
			.doc-expiry-card .dx-main { display: flex; align-items: baseline; gap: 8px; }
			.doc-expiry-card .dx-count { font-size: 34px; font-weight: 800; line-height: 1.1; letter-spacing: -.5px; color: var(--dx-fg); font-variant-numeric: tabular-nums; }
			.doc-expiry-card .dx-count-label { font-size: 12px; font-weight: 600; color: var(--sr-muted); }
			.doc-expiry-card .dx-docs { display: flex; flex-direction: column; gap: 9px; }
			.doc-expiry-card .dx-doc { display: grid; grid-template-columns: 96px 1fr 34px; align-items: center; gap: 10px; font-size: 12px; color: var(--sr-text-2, var(--sr-muted)); }
			.doc-expiry-card .dx-doc i { width: 14px; color: var(--sr-muted); }
			.doc-expiry-card .dx-doc-bar { height: 6px; border-radius: 999px; background: var(--sr-surface-3, #eef1f5); overflow: hidden; }
			.doc-expiry-card .dx-doc-bar > span { display: block; height: 100%; border-radius: 999px; background: var(--dx); }
			.doc-expiry-card .dx-doc-num { text-align: end; font-weight: 800; color: var(--sr-text); font-variant-numeric: tabular-nums; }
			.doc-expiry-card .dx-foot { display: flex; align-items: center; justify-content: space-between; padding-top: 10px; border-top: 1px dashed var(--sr-border); font-size: 12px; font-weight: 700; color: var(--dx-fg); }
			.doc-expiry-card .dx-foot i { transition: transform .15s; }
			.doc-expiry-card:hover .dx-foot i { transform: translateX(3px); }
			html[dir="rtl"] .doc-expiry-card .dx-foot i { transform: scaleX(-1); }
			html[dir="rtl"] .doc-expiry-card:hover .dx-foot i { transform: translateX(-3px) scaleX(-1); }

			/* Access scope */
			.dash-scope { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; padding: 18px; }
			@media (max-width: 991px) { .dash-scope { grid-template-columns: 1fr; } }
			.dash-scope-item {
				--sc: #6366f1; --sc-fg: #5b21b6; --sc-bg: #f5f3ff;
				position: relative; overflow: hidden; display: flex; flex-direction: column; gap: 14px; min-width: 0;
				padding: 18px; border-radius: 14px; border: 1px solid var(--sr-border); background: var(--sr-surface, #fff);
				transition: transform .15s, box-shadow .15s, border-color .15s;
			}
			.dash-scope-item::before { content: ""; position: absolute; inset: 0 0 auto; height: 4px; background: linear-gradient(90deg, var(--sc), color-mix(in srgb, var(--sc) 35%, transparent)); }
			.dash-scope-item.sc-sky { --sc: #0ea5e9; --sc-fg: var(--tone-sky-fg); --sc-bg: var(--tone-sky-bg); }
			.dash-scope-item.sc-green { --sc: #10b981; --sc-fg: var(--tone-green-fg, #047857); --sc-bg: var(--tone-green-bg, #ecfdf5); }
			.dash-scope-item:hover { transform: translateY(-3px); border-color: color-mix(in srgb, var(--sc) 45%, var(--sr-border)); box-shadow: 0 12px 28px -10px color-mix(in srgb, var(--sc) 45%, transparent); }
			html.app-dark .dash-scope-item.sc-violet { --sc-fg: #c4b5fd; --sc-bg: #2a2350; }
			.dash-scope-head { display: flex; align-items: center; gap: 10px; }
			.dash-scope-icon { width: 38px; height: 38px; border-radius: 11px; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; background: var(--sc-bg); color: var(--sc-fg); }
			.dash-scope-title { flex: 1; min-width: 0; font-size: 14px; font-weight: 700; color: var(--sr-text); }
			.dash-scope-pill { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; background: var(--sc-bg); color: var(--sc-fg); white-space: nowrap; }
			.dash-scope-main { display: flex; align-items: baseline; gap: 8px; }
			.dash-scope-count { font-size: 30px; font-weight: 800; line-height: 1.1; letter-spacing: -.5px; color: var(--sc-fg); font-variant-numeric: tabular-nums; }
			.dash-scope-of { font-size: 12px; font-weight: 600; color: var(--sr-muted); }
			.dash-scope-bar { height: 6px; border-radius: 999px; background: var(--sr-surface-3, #eef1f5); overflow: hidden; }
			.dash-scope-bar > span { display: block; height: 100%; border-radius: 999px; background: var(--sc); }
			.dash-scope-chips { display: flex; flex-wrap: wrap; gap: 6px; max-height: 112px; overflow-y: auto; padding-top: 12px; border-top: 1px dashed var(--sr-border); }
			.dash-scope-chip { display: inline-flex; align-items: center; gap: 5px; max-width: 100%; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; background: var(--sr-surface-2, #f8fafc); border: 1px solid var(--sr-border); color: var(--sr-text-2, var(--sr-text)); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
			.dash-scope-chip.is-all { background: var(--sc-bg); border-color: transparent; color: var(--sc-fg); }
			.dash-scope-chip.is-more { background: transparent; border-style: dashed; color: var(--sr-muted); cursor: help; }

			.dash-dt-search { max-width: 300px; }
			.sr-page .sr-table .iqama_exp_hijri { white-space: nowrap; }
		</style>

		<?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>

    </head>


    <body class="enlarged" data-keep-enlarged="true">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">
				
                <div class="slimscroll-menu" id="remove-scroll">

                    <!-- LOGO -->
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span>
                                <img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22">
                            </span>
                            <i>
                                <img src="<?=get_setting($conDB, 'white_logo')?>	" alt="" height="28">
                            </i>
                        </a>
                    </div>

                    <!-- User box -->
                    

                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <!-- Sidebar -->

                    <div class="clearfix"></div>

                </div>
                <!-- Sidebar -left -->
				
            </div>
            <!-- Left Sidebar End -->

            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->

            <div class="content-page">

                <!-- Top Bar Start -->
               <?php include("./includes/topbar.php"); ?>
                <!-- Top Bar End -->
                <!-- Start Page content -->
                <div class="content sr-page">

                    <div class="container-fluid">

						<?php
							$message;
							if (isset($_SESSION['error_msg'])) {
								echo $_SESSION['error_msg'];
								unset($_SESSION['error_msg']); // Clear after displaying
								header("refresh:3 ; ./dashboard.php");
							}
						?>

						<div class="sr-head">
							<div>
								<h1><?= __('dashboard') ?></h1>
								<?php
								// Saudi official Hijri date (Umm al-Qura calendar, Riyadh time)
								$hijri_today = '';
								if (class_exists('IntlDateFormatter')) {
									$hijri_fmt = new IntlDateFormatter(
										'ar_SA@calendar=islamic-umalqura;numbers=latn',
										IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Asia/Riyadh',
										IntlDateFormatter::TRADITIONAL, 'd MMMM y'
									);
									$hijri_today = $hijri_fmt->format(time()) . ' هـ';
								}
								?>
								<div class="sr-date-chips">
									<span class="sr-date-chip tone-indigo">
										<i class="fa fa-calendar-alt"></i>
										<span><small><?= __('gregorian', 'Gregorian') ?></small><b><?= date('l, d F Y') ?></b></span>
									</span>
									<?php if ($hijri_today): ?>
									<span class="sr-date-chip tone-green">
										<i class="fa fa-moon"></i>
										<span><small><?= __('hijri', 'Hijri') ?></small><b><bdi dir="rtl"><?= htmlspecialchars($hijri_today) ?></bdi></b></span>
									</span>
									<?php endif; ?>
								</div>
							</div>
							<div class="sr-head-actions">
								<a href="reg_employee.php" class="sr-btn"><i class="fa fa-users"></i> <?= __('all_employees') ?></a>
							</div>
						</div>

						<?php
						// KPI tiles: [label, value, percent, fa icon, tone, link (null = not clickable)]
						$dash_kpis = [
							[__('almutlak_employees'), (int)$status_cont_active, $pct_active, 'fa-house-chimney-user', 'blue', 'dashbydepart.php'],
							[__('man_power_employees'), (int)$status_cont_man_power, $pct_man_power, 'fa-users-rays', 'violet', 'filter_employee.php?page=1&status=1&fly=no&emp_sup_type=man_power'],
							[__('total_on_job_employees'), (int)$status_cont_man_power + (int)$status_cont_active, $pct_on_job, 'fa-briefcase', 'indigo', $status_cont_fly > 0 ? 'dashbydepart.php' : null],
							[__('departed_employees'), (int)$status_cont_fly, $pct_fly, 'fa-plane-departure', 'amber', $status_cont_fly > 0 ? 'filter_employee.php?page=1&departed=1' : null],
							[__('local_vacation_employees'), (int)($status_cont_local_vac ?? 0), $pct_local_vac, 'fa-umbrella-beach', 'sky', ($status_cont_local_vac ?? 0) > 0 ? 'filter_employee.php?page=1&local_vac=1' : null],
							[__('terminated_employees'), (int)$status_cont_ter, $pct_terminated, 'fa-users-slash', 'red', 'filter_employee.php?page=1&status=0&fly=0'],
							[__('terminated_employees_current_year', 'Terminated Employees (Current Year)'), (int)$status_cont_ter_year, $pct_terminated_year, 'fa-user-slash', 'rose', 'filter_employee.php?page=1&status=0&fly=0&ter_year=' . date('Y')],
							[__('total_employees'), (int)$status_cont_tot, $pct_total, 'fa-users-viewfinder', 'green', 'reg_employee.php'],
						];
						?>
						<div class="dash-kpis">
							<?php foreach ($dash_kpis as [$k_label, $k_value, $k_pct, $k_icon, $k_tone, $k_href]):
								$k_tag = $k_href ? 'a href="' . htmlspecialchars($k_href) . '"' : 'div';
								$k_end = $k_href ? 'a' : 'div';
							?>
								<<?= $k_tag ?> class="dash-kpi is-<?= $k_tone ?>">
									<div class="dash-kpi-top">
										<div style="min-width: 0;">
											<div class="dash-kpi-label"><?= htmlspecialchars($k_label) ?></div>
											<div class="dash-kpi-value"><?= number_format($k_value) ?></div>
										</div>
										<span class="dash-kpi-icon"><i class="fa <?= $k_icon ?>"></i></span>
									</div>
									<div class="dash-kpi-bar"><span style="width: <?= (float)$k_pct ?>%;"></span></div>
									<div class="dash-kpi-foot">
										<span><b><?= $k_pct ?>%</b> <?= __('of_total_employees') ?></span>
										<?php if ($k_href): ?><i class="fa fa-arrow-right"></i><?php endif; ?>
									</div>
								</<?= $k_end ?>>
							<?php endforeach; ?>
						</div>

						<div class="sr-card">
							<div class="sr-card-head">
								<h5 class="sr-card-title"><i class="fa fa-chart-pie"></i> <?= __('employee_demographics') ?: 'Employee Demographics' ?></h5>
							</div>
							<div class="dash-filters dash-pie-filter-row">
								<div>
									<label for="dashChartDeptFilter"><?= __('department') ?></label>
									<select class="form-control" id="dashChartDeptFilter" multiple>
										<?php foreach ($chart_filter_departments as $d): ?>
											<option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars(($is_rtl ?? false) && !empty($d['dep_nme_ar']) ? $d['dep_nme_ar'] : $d['dep_nme'], ENT_QUOTES, 'UTF-8') ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div>
									<label for="dashChartCompFilter"><?= __('company') ?: 'Company' ?></label>
									<select class="form-control" id="dashChartCompFilter" multiple>
										<?php foreach ($chart_filter_companies as $c): ?>
											<option value="<?= (int)$c['comp_id'] ?>"><?= htmlspecialchars(($is_rtl ?? false) && !empty($c['comp_name_ar']) ? $c['comp_name_ar'] : $c['comp_name'], ENT_QUOTES, 'UTF-8') ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div style="display: flex; gap: 6px;">
									<button type="button" class="sr-btn sr-btn-primary" id="dashChartApplyFilterBtn"><i class="fa fa-filter"></i> <?= __('apply') ?: 'Apply' ?></button>
									<button type="button" class="sr-btn" id="dashChartResetFilterBtn"><?= __('reset') ?></button>
								</div>
								<div>
									<label class="dash-check" for="dashChartShowLabels">
										<input type="checkbox" id="dashChartShowLabels">
										<span><?= __('show_slice_labels', 'Show data labels on slices') ?></span>
									</label>
								</div>
							</div>
							<div class="dash-tip">
								<span><i class="fa fa-lightbulb"></i> <?= __('click_a_chart_slice_to_filter') ?: 'Tip: click a slice on the Country, Gender, or Saudi/Non-Saudi chart to filter all charts by it.' ?></span>
								<span class="sr-pill sr-pill-xs tone-indigo dash-slice-filter-badge" id="dashSliceFilterBadge" style="display:none;" title="<?= __('click_to_clear') ?: 'Click to clear' ?>"></span>
							</div>
							<div class="dash-charts">
								<div class="dash-pie-card">
									<div class="dash-pie-title"><?= __('employees_by_country') ?: 'Employees by Country' ?></div>
									<div class="dash-pie-canvas-wrap" id="chartByCountry"></div>
								</div>
								<div class="dash-pie-card">
									<div class="dash-pie-title"><?= __('employees_by_gender') ?: 'Employees by Gender' ?></div>
									<div class="dash-pie-canvas-wrap" id="chartByGender"></div>
								</div>
								<div class="dash-pie-card">
									<div class="dash-pie-title"><?= __('employees_by_company') ?: 'Employees by Company' ?></div>
									<div class="dash-pie-canvas-wrap" id="chartByCompany"></div>
								</div>
								<div class="dash-pie-card">
									<div class="dash-pie-title"><?= __('saudi_vs_non_saudi') ?: 'Saudi vs Non-Saudi' ?></div>
									<div class="dash-pie-canvas-wrap" id="chartBySaudi"></div>
								</div>
							</div>
						</div>

						<?php if ($can_view_expiry_block): ?>
						<?php
						$dash_expiry_tiles = [
							['info', 'sky', __('within_30_days', getDisplayName('Within 30 Days')), getDisplayName('Employees in 21-30 days range'), 'fa-circle-info', __('days_21_30', '21–30 days')],
							['warning', 'amber', __('within_20_days', getDisplayName('Within 20 Days')), getDisplayName('Employees in 11-20 days range'), 'fa-triangle-exclamation', __('days_11_20', '11–20 days')],
							['danger', 'red', __('within_10_days', getDisplayName('Within 10 Days')), getDisplayName('Employees in 0-10 days or expired range'), 'fa-skull-crossbones', __('days_0_10_expired', '≤10 days / expired')],
						];
						?>
						<div class="sr-card">
							<div class="sr-card-head">
								<h5 class="sr-card-title"><i class="fa fa-id-card"></i> <?= getDisplayName('Document Expiry Alerts') ?></h5>
								<span class="sr-card-sub"><?= __('click_card_to_view_list', 'Click a card to see the employees') ?></span>
							</div>
							<div class="dash-expiry">
								<?php foreach ($dash_expiry_tiles as [$x_level, $x_tone, $x_title, $x_tip, $x_icon, $x_range]):
									// Bars: each document type's share of the documents expiring in this range
									$x_docs = [
										['id_iqama', getDisplayName('ID/Iqama'), 'fa-id-card'],
										['passport', getDisplayName('Passport'), 'fa-passport'],
										['contract', getDisplayName('Contract'), 'fa-file-signature'],
									];
									$x_doc_total = 0;
									foreach ($x_docs as $x_doc) { $x_doc_total += (int)$doc_expiry_doc_counts[$x_level][$x_doc[0]]; }
								?>
									<div class="doc-expiry-card tone-<?= $x_tone ?>" data-level="<?= $x_level ?>" title="<?= htmlspecialchars($x_tip) ?>">
										<div class="dx-head">
											<span class="dx-icon"><i class="fa <?= $x_icon ?>"></i></span>
											<span class="dx-title"><?= htmlspecialchars($x_title) ?></span>
											<span class="dx-range"><?= htmlspecialchars($x_range) ?></span>
										</div>
										<div class="dx-main">
											<span class="dx-count"><?= (int)$doc_expiry_counts[$x_level] ?></span>
											<span class="dx-count-label"><?= getDisplayName('Employees') ?></span>
										</div>
										<div class="dx-docs">
											<?php foreach ($x_docs as [$x_key, $x_label, $x_doc_icon]):
												$x_n = (int)$doc_expiry_doc_counts[$x_level][$x_key];
											?>
												<div class="dx-doc">
													<span><i class="fa <?= $x_doc_icon ?>"></i> <?= htmlspecialchars($x_label) ?></span>
													<span class="dx-doc-bar"><span style="width: <?= $x_doc_total > 0 ? round($x_n / $x_doc_total * 100, 1) : 0 ?>%;"></span></span>
													<span class="dx-doc-num"><?= $x_n ?></span>
												</div>
											<?php endforeach; ?>
										</div>
										<div class="dx-foot">
											<span><?= __('view_employees', 'View employees') ?></span>
											<i class="fa fa-arrow-right"></i>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<?php endif; ?>

						<div class="sr-card">
							<div class="sr-card-head">
								<h5 class="sr-card-title"><i class="fa fa-shield-halved"></i> <?= getDisplayName('Access Scope') ?></h5>
							</div>
							<?php
							// [tone, icon, title, allowed names (empty = all), total, "all" label]
							$dash_scopes = [
								['violet', 'fa-building', __('allowed_companies') ?: 'Allowed Companies', $scope_company_list, $scope_company_total, __('all_companies', 'All Companies')],
								['sky', 'fa-sitemap', __('allowed_departments') ?: 'Allowed Departments', $scope_department_list, $scope_department_total, __('all_departments', 'All Departments')],
							];
							if ($scope_employee_list) {
								$dash_scopes[] = ['green', 'fa-users', __('allowed_employees') ?: 'Allowed Employees', $scope_employee_list, $scope_employee_total, ''];
							}
							$scope_chip_max = 12;
							?>
							<div class="dash-scope">
								<?php foreach ($dash_scopes as [$sc_tone, $sc_icon, $sc_title, $sc_list, $sc_total, $sc_all_label]):
									$sc_full = !$sc_list;
									$sc_count = $sc_full ? $sc_total : count($sc_list);
									$sc_pct = $sc_full ? 100 : ($sc_total > 0 ? min(100, round($sc_count / $sc_total * 100, 1)) : 100);
								?>
									<div class="dash-scope-item sc-<?= $sc_tone ?>">
										<div class="dash-scope-head">
											<span class="dash-scope-icon"><i class="fa <?= $sc_icon ?>"></i></span>
											<span class="dash-scope-title"><?= htmlspecialchars($sc_title) ?></span>
											<?php if ($sc_full): ?>
												<span class="dash-scope-pill"><i class="fa fa-circle-check"></i> <?= __('full_access', 'Full access') ?></span>
											<?php else: ?>
												<span class="dash-scope-pill"><i class="fa fa-filter"></i> <?= __('restricted', 'Restricted') ?></span>
											<?php endif; ?>
										</div>
										<div>
											<div class="dash-scope-main">
												<span class="dash-scope-count"><?= number_format($sc_count) ?></span>
												<?php if ($sc_total > 0): ?><span class="dash-scope-of"><?= __('of', 'of') ?> <?= number_format($sc_total) ?></span><?php endif; ?>
											</div>
											<div class="dash-scope-bar" style="margin-top: 8px;"><span style="width: <?= $sc_pct ?>%;"></span></div>
										</div>
										<div class="dash-scope-chips">
											<?php if ($sc_full): ?>
												<span class="dash-scope-chip is-all"><i class="fa fa-infinity"></i> <?= htmlspecialchars($sc_all_label) ?></span>
											<?php else: ?>
												<?php foreach (array_slice($sc_list, 0, $scope_chip_max) as $sc_name): ?>
													<span class="dash-scope-chip" title="<?= htmlspecialchars($sc_name) ?>"><?= htmlspecialchars($sc_name) ?></span>
												<?php endforeach; ?>
												<?php if (count($sc_list) > $scope_chip_max): ?>
													<span class="dash-scope-chip is-more" title="<?= htmlspecialchars(implode(', ', array_slice($sc_list, $scope_chip_max))) ?>">+<?= count($sc_list) - $scope_chip_max ?> <?= __('more', 'more') ?></span>
												<?php endif; ?>
											<?php endif; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>

							<?php

							// Show expiry notifications for: Administrators, HR team, Finance team, IT team, and department managers
							if ($can_view_expiry_block):
								// Use the same access control pattern for expiry notifications
								if(!$can_see_all_employees && isset($user_dept)){
									$result=mysqli_query($conDB, "SELECT
									`e`.*,
									`d`.`dep_nme`,
									`d`.`dep_nme_ar`,
									`c`.`name` AS `countryname_en`,
									`c`.`name_ar` AS `countryname_ar`
									FROM `employees` as `e`
									LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
									LEFT JOIN `countries` `c` ON `c`.`id` = `e`.`country`
									WHERE `e`.`status`=1 AND `e`.`iqama_exp_g` BETWEEN CURDATE() and DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND `e`.`dept`='".$user_dept."' ");
								}else{
									$result=mysqli_query($conDB, "SELECT
									`e`.*,
									`d`.`dep_nme`,
									`d`.`dep_nme_ar`,
									`c`.`name` AS `countryname_en`,
									`c`.`name_ar` AS `countryname_ar`
									FROM `employees` as `e`
									LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
									LEFT JOIN `countries` `c` ON `c`.`id` = `e`.`country`
									WHERE `e`.`status`=1 AND `e`.`iqama_exp_g` BETWEEN CURDATE() and DATE_ADD(CURDATE(), INTERVAL 30 DAY) ");
								}

								if( mysqli_num_rows($result) != 0 ){
							?>
						<div class="sr-card">
							<div class="sr-toolbar">
								<h5 class="sr-card-title"><i class="fa fa-hourglass-half"></i> <?=__('coming_soon_expiry_with_30_days') ?></h5>
								<div class="sr-search dash-dt-search">
									<i class="fa fa-search"></i>
									<input type="search" class="dash-dt-filter" data-table="#datatable" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
								</div>
							</div>
							<div class="sr-table-wrap">
							<table id="datatable" class="table sr-table dt-responsive nowrap" style="width: 100%;">
								<thead>
								<tr>
									<th><?=__('name') ?></th>
									<th><?=__('iqama_id') ?></th>
									<th><?=__('department') ?></th>
									<th><?=__('country') ?></th>
									<th><?=__('iqama_id_expiry') ?></th>
									<th><?=__('days_of_expiry') ?></th>
									<th class="text-right"><?=__('action') ?></th>
								</tr>
								</thead>

								<tbody>
								<?php
									while($row = mysqli_fetch_assoc($result)){
										if(strtotime(date("Y/m/d")) < strtotime($row['iqama_exp_g'])){

											$from = strtotime(date("d-m-Y", strtotime($row['iqama_exp_g'])));
											$today = time();
											$difference = $from - $today;
											$daystoexp = floor($difference / 86400);  // (60 * 60 * 24)

											if($daystoexp <= "10"){
												$days_tone = "red";
											}elseif($daystoexp <= "20"){
												$days_tone = "amber";
											}else{
												$days_tone = "sky";
											}
								?>
								<tr>
									<td data-order="<?= htmlspecialchars(getDisplayName($row['name'])) ?>">
										<div class="sr-person">
											<span class="sr-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr(trim((string)$row['name']), 0, 1))) ?></span>
											<div style="min-width: 0;">
												<span class="sr-cell-title"><?= htmlspecialchars(getDisplayName($row['name'])) ?></span>
												<span class="sr-cell-sub sr-mono"><?= htmlspecialchars($row['emp_id']) ?></span>
											</div>
										</div>
									</td>
									<td><span class="sr-mono copyToClipboard" style="cursor: copy;"><?= htmlspecialchars($row['iqama']) ?></span></td>
									<td><?= htmlspecialchars(($is_rtl ?? false) ? $row['dep_nme_ar'] : $row['dep_nme']) ?></td>
									<td><?= htmlspecialchars(($is_rtl ?? false) ? $row['countryname_ar'] : $row['countryname_en']) ?></td>
									<td data-order="<?= htmlspecialchars($row['iqama_exp_g']) ?>"><?= htmlspecialchars($row['iqama_exp']) ?></td>
									<td data-order="<?= (int)$daystoexp ?>"><span class="sr-pill sr-pill-xs tone-<?= $days_tone ?>"><span class="sr-dot"></span><?= $daystoexp ?> <?= __('days') ?></span></td>
									<td class="text-right">
										<a href="javascript:void(0);" class="sr-open-btn iqama_exp_hijri" data-id="<?=$row['id']?>"><i class="fa fa-id-card"></i> <?=__('update_expiry')?></a>
									</td>
								</tr>
								<?php } } ?>
								</tbody>
							</table>
							</div>
						</div>
						<?php } ?>
						<?php
							// Always use the full query, filtered by department if needed
							$expired_filter = $can_see_all_employees ? "" : " AND `e`.`dept`='".$user_dept."'";
							$result=mysqli_query($conDB, "SELECT
								`e`.*,
								`d`.`dep_nme`,
								`d`.`dep_nme_ar`,
								`c`.`name` AS `countryname_en`,
								`c`.`name_ar` AS `countryname_ar`
								FROM `employees` as `e`
								LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
								LEFT JOIN `countries` `c` ON `c`.`id` = `e`.`country`
								WHERE `e`.`status`=1 AND DATEDIFF(`e`.`iqama_exp_g`, NOW()) <= 1".$expired_filter."
							");

							if( mysqli_num_rows($result) != 0 ){

						?>
						<div class="sr-card">
							<div class="sr-toolbar">
								<h5 class="sr-card-title"><i class="fa fa-circle-exclamation" style="color: #ef4444;"></i> <?=__('expired') ?></h5>
								<div class="sr-search dash-dt-search">
									<i class="fa fa-search"></i>
									<input type="search" class="dash-dt-filter" data-table="#datatable_exp" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
								</div>
							</div>
							<div class="sr-table-wrap">
							<table id="datatable_exp" class="table sr-table dt-responsive nowrap" style="width: 100%;">
								<thead>
								<tr>
									<th><?=__('name') ?></th>
									<th><?=__('iqama_id') ?></th>
									<th><?=__('department') ?></th>
									<th><?=__('country') ?></th>
									<th><?=__('iqama_id_expiry') ?></th>
									<th><?=__('days_of_expiry') ?></th>
									<th class="text-right"><?=__('action') ?></th>
								</tr>
								</thead>

								<tbody>
								<?php
									while($row = mysqli_fetch_assoc($result)){
										if(strtotime(date("Y/m/d")) > strtotime($row['iqama_exp_g'])){
											$from = strtotime(date("d-m-Y", strtotime($row['iqama_exp_g'])));
											$today = time();
											$difference = $from - $today;
											$daystoexp = floor($difference / 86400);  // (60 * 60 * 24)
									?>
								<tr>
									<td data-order="<?= htmlspecialchars(getDisplayName($row['name'])) ?>">
										<div class="sr-person">
											<span class="sr-avatar" style="background: var(--tone-red-bg); color: var(--tone-red-fg);"><?= htmlspecialchars(mb_strtoupper(mb_substr(trim((string)$row['name']), 0, 1))) ?></span>
											<div style="min-width: 0;">
												<span class="sr-cell-title"><?= htmlspecialchars(getDisplayName($row['name'])) ?></span>
												<span class="sr-cell-sub sr-mono"><?= htmlspecialchars($row['emp_id']) ?></span>
											</div>
										</div>
									</td>
									<td><span class="sr-mono copyToClipboard" style="cursor: copy;"><?= htmlspecialchars($row['iqama']) ?></span></td>
									<td><?= htmlspecialchars(($is_rtl ?? false) ? $row['dep_nme_ar'] : $row['dep_nme']) ?></td>
									<td><?= htmlspecialchars(($is_rtl ?? false) ? $row['countryname_ar'] : $row['countryname_en']) ?></td>
									<td data-order="<?= htmlspecialchars($row['iqama_exp_g']) ?>"><?= date("d/m/Y", strtotime($row['iqama_exp_g'])) ?></td>
									<td data-order="<?= (int)$daystoexp ?>"><span class="sr-pill sr-pill-xs tone-red"><span class="sr-dot"></span><?= $daystoexp ?> <?= __('days') ?></span></td>
									<td class="text-right">
										<a href="javascript:void(0);" class="sr-open-btn iqama_exp_hijri" data-id="<?=$row['id']?>" style="color: var(--tone-red-fg);"><i class="fa fa-id-card"></i> <?=__('update_expiry') ?></a>
									</td>
								</tr>
								<?php } } ?>
								</tbody>
							</table>
							</div>
						</div>
						<?php } ?>

					<?php endif; ?>

                    </div> <!-- container -->

                </div> <!-- content -->

                <footer class="footer">
                    <?=$site_footer ?>
                </footer>

            </div>


            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->


        </div>
        <!-- END wrapper -->


        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>

        <!-- Required datatable js -->
        <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <!-- Buttons examples -->
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/pdfmake.min.js"></script>
        <script src="./plugins/datatables/vfs_fonts.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>

        <!-- Key Tables -->
        <script src="./plugins/datatables/dataTables.keyTable.min.js"></script>

        <!-- Responsive examples -->
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <!-- Selection table -->
        <script src="./plugins/datatables/dataTables.select.min.js"></script>

		<script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>

        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>

        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
		
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		
        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

		<!-- Make sure this is on EVERY page -->
    	<script src="assets/js/notifications.js"></script>

        <!-- Dashboard Init -->
        <!-- <script src="assets/pages/jquery.dashboard.init.js"></script> -->

		<!-- Demographic Pie Charts (ApexCharts, always-light theme, filterable) -->
		<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
		<script src="./plugins/select2/js/select2.min.js"></script>
		<script type="text/javascript">
			(function() {
				if (typeof ApexCharts === 'undefined') { return; }

				// Fixed-order categorical palette (never reassigned per-series)
				var DASH_PALETTE = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];
				var DASH_OTHER_COLOR = '#898781';
				var NO_DATA_LABEL = <?= json_encode(__('no_records_found') ?: 'No data', JSON_UNESCAPED_UNICODE) ?>;
				var OTHER_LABEL = <?= json_encode(__('other'), JSON_UNESCAPED_UNICODE) ?>;
				var FILTERED_BY_LABEL = <?= json_encode(__('filtered_by') ?: 'Filtered by', JSON_UNESCAPED_UNICODE) ?>;

				function colorsFor(labels) {
					return labels.map(function(label, idx) {
						if (label === OTHER_LABEL) { return DASH_OTHER_COLOR; }
						return DASH_PALETTE[idx % DASH_PALETTE.length];
					});
				}

				var dashCharts = {};
				// Each pie's currently rendered slice ids, in series order - lets a click map back to a real id
				var dashChartIds = {};
				// The active click-driven slice filter (only one dimension active at a time)
				var sliceFilter = { dim: null, value: null, label: null };
				// Slice data labels are redundant with the hover tooltip - hidden by default, toggled via checkbox
				var showSliceLabels = false;
				// Last data rendered, so the labels checkbox can re-render without a round trip
				var lastDashChartData = null;

				// containerId -> which filter dimension a click on that chart sets
				var DIM_BY_CONTAINER = {
					chartByCountry: 'country_id',
					chartByGender: 'sex',
					chartBySaudi: 'saudi'
					// chartByCompany intentionally omitted - use the Company dropdown for that dimension
				};

				function updateSliceFilterBadge() {
					var $badge = $('#dashSliceFilterBadge');
					if (sliceFilter.dim && sliceFilter.value !== null) {
						$badge.text(FILTERED_BY_LABEL + ': ' + sliceFilter.label + ' ✕').show();
					} else {
						$badge.hide();
					}
				}

				function buildPie(containerId, labels, data, ids) {
					var el = document.getElementById(containerId);
					if (!el) { return; }

					dashChartIds[containerId] = ids || [];

					var hasData = labels.length > 0 && data.reduce(function(a, b) { return a + b; }, 0) > 0;
					var dim = DIM_BY_CONTAINER[containerId];
					// App-wide dark theme (App Settings > Theme Config) - let the dark card show
					// through instead of painting a white box inside it.
					var isDarkUi = document.documentElement.classList.contains('app-dark');
					var options = {
						chart: {
							type: 'donut',
							height: 280,
							background: isDarkUi ? 'transparent' : '#ffffff',
							foreColor: isDarkUi ? '#d8dee9' : '#212529',
							events: dim ? {
								dataPointSelection: function(event, chartCtx, config) {
									onSliceClick(containerId, dim, config.dataPointIndex);
								}
							} : {}
						},
						theme: { mode: isDarkUi ? 'dark' : 'light' },
						series: hasData ? data : [1],
						labels: hasData ? labels : [NO_DATA_LABEL],
						colors: hasData ? colorsFor(labels) : [isDarkUi ? '#2a3348' : '#e5e7eb'],
						plotOptions: {
							pie: {
								donut: {
									size: '65%',
									labels: {
										show: true,
										total: { show: true, label: '' }
									}
								}
							}
						},
						fill: {
							type: hasData ? 'gradient' : 'solid',
							gradient: {
								shade: 'light',
								type: 'radial',
								shadeIntensity: 0.65,
								gradientToColors: undefined,
								inverseColors: true,
								opacityFrom: 1,
								opacityTo: 1,
								stops: [0, 100]
							}
						},
						stroke: { show: true, width: 2, colors: [isDarkUi ? '#151b28' : '#ffffff'] },
						states: {
							hover: { filter: { type: 'none' } },
							active: { filter: { type: 'none' } }
						},
						legend: { show: showSliceLabels, position: 'bottom', fontSize: '12px' },
						dataLabels: { enabled: hasData, style: { fontSize: '11px' } },
						tooltip: { enabled: hasData, theme: isDarkUi ? 'dark' : 'light' },
						noData: { text: NO_DATA_LABEL }
					};

					if (dashCharts[containerId]) {
						dashCharts[containerId].updateOptions(options, true, true);
					} else {
						dashCharts[containerId] = new ApexCharts(el, options);
						dashCharts[containerId].render();
						if (dim) {
							el.style.cursor = 'pointer';
						}
					}
				}

				function renderAll(data) {
					lastDashChartData = data;
					buildPie('chartByCountry', data.country.labels, data.country.data, data.country.ids);
					buildPie('chartByGender', data.gender.labels, data.gender.data, data.gender.ids);
					buildPie('chartByCompany', data.company.labels, data.company.data, data.company.ids);
					buildPie('chartBySaudi', data.saudi.labels, data.saudi.data, data.saudi.ids);
					updateSliceFilterBadge();
				}

				// Initial render with server-side data (no filters applied yet)
				renderAll(<?= json_encode($dashboard_chart_data, JSON_UNESCAPED_UNICODE) ?>);

				if ($.fn.select2) {
					$('#dashChartDeptFilter').select2({ placeholder: '<?= addslashes(__('all_departments')) ?>', allowClear: true, closeOnSelect: false, width: '100%' });
					$('#dashChartCompFilter').select2({ placeholder: '<?= addslashes(__('all_companies') ?: 'All Companies') ?>', allowClear: true, closeOnSelect: false, width: '100%' });

					lockSelectedSelect2Options('#dashChartDeptFilter, #dashChartCompFilter');
				}

				function applyDashboardChartFilter() {
					var deptIds = $('#dashChartDeptFilter').val() || [];
					var compIds = $('#dashChartCompFilter').val() || [];
					var payload = { department_ids: deptIds, company_ids: compIds };
					if (sliceFilter.dim && sliceFilter.value !== null) {
						payload[sliceFilter.dim] = sliceFilter.value;
					}

					$.ajax({
						url: './includes/ajaxFile/dashboardChartsHandler.php',
						type: 'POST',
						dataType: 'json',
						data: payload,
						success: function(resp) {
							if (resp && resp.success) {
								renderAll(resp.data);
							}
						}
					});
				}

				function onSliceClick(containerId, dim, dataPointIndex) {
					var ids = dashChartIds[containerId] || [];
					var value = ids[dataPointIndex];
					if (value === undefined || value === null) { return; } // "Other"/unspecified slices aren't filterable

					var chart = dashCharts[containerId];
					var label = (chart && chart.w && chart.w.config && chart.w.config.labels) ? chart.w.config.labels[dataPointIndex] : String(value);

					if (sliceFilter.dim === dim && String(sliceFilter.value) === String(value)) {
						// Clicking the active slice again clears the filter
						sliceFilter = { dim: null, value: null, label: null };
					} else {
						sliceFilter = { dim: dim, value: value, label: label };
					}
					applyDashboardChartFilter();
				}

				$('#dashChartApplyFilterBtn').on('click', applyDashboardChartFilter);
				$('#dashChartResetFilterBtn').on('click', function() {
					$('#dashChartDeptFilter').val(null).trigger('change');
					$('#dashChartCompFilter').val(null).trigger('change');
					sliceFilter = { dim: null, value: null, label: null };
					applyDashboardChartFilter();
				});
				$('#dashSliceFilterBadge').on('click', function() {
					sliceFilter = { dim: null, value: null, label: null };
					applyDashboardChartFilter();
				});
				$('#dashChartShowLabels').on('change', function() {
					showSliceLabels = this.checked;
					if (lastDashChartData) {
						renderAll(lastDashChartData);
					}
				});
			})();
		</script>

		<script type="text/javascript">
			var docExpiryBandData = <?= $doc_expiry_data_json ?: '{"info":[],"warning":[],"danger":[]}' ?>;

			function escapeDocExpiryHtml(value) {
				if (value === null || value === undefined) {
					return '';
				}
				return String(value)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;')
					.replace(/'/g, '&#039;');
			}

			function formatDocDays(doc) {
				var days = parseInt(doc.days, 10);
				if (isNaN(days)) {
					return '';
				}

				if (days < 0) {
					return '<?= getDisplayName('Expired') ?> ' + Math.abs(days) + ' <?= getDisplayName('day(s) ago') ?>';
				}
				if (days === 0) {
					return '<?= getDisplayName('Expires today') ?>';
				}
				return days + ' <?= getDisplayName('day(s) left') ?>';
			}

			function getDocExpiryTitle(level) {
				if (level === 'danger') {
					return '<?= getDisplayName('Employees with documents within 10 days or expired') ?>';
				}
				if (level === 'warning') {
					return '<?= getDisplayName('Employees with documents within 20 days') ?>';
				}
				return '<?= getDisplayName('Employees with documents within 30 days') ?>';
			}

			// Urgency tone per document: expired / <=10 days red, <=20 amber, else sky
			function docExpiryTone(doc) {
				var days = parseInt(doc.days, 10);
				if (isNaN(days)) return 'sky';
				if (days <= 10) return 'red';
				if (days <= 20) return 'amber';
				return 'sky';
			}

			function buildDocExpiryListHtml(level) {
				var rows = (docExpiryBandData && docExpiryBandData[level]) ? docExpiryBandData[level] : [];
				if (!rows.length) {
					return '<div class="dex-empty"><i class="fa fa-file-circle-check"></i><?= getDisplayName('No employees found in this range.') ?></div>';
				}

				var docCount = 0, expiredCount = 0, types = {};
				var cards = rows.map(function(emp) {
					var docs = Array.isArray(emp.docs) ? emp.docs : [];
					var docsHtml = docs.map(function(doc) {
						var days = parseInt(doc.days, 10);
						docCount++;
						if (!isNaN(days) && days < 0) expiredCount++;
						types[doc.type] = (types[doc.type] || 0) + 1;
						return '<span class="dex-doc tone-' + docExpiryTone(doc) + '">'
							+ '<span class="dex-doc-type">' + escapeDocExpiryHtml(doc.type) + '</span>'
							+ '<span class="dex-doc-date"><i class="fa fa-calendar"></i> ' + escapeDocExpiryHtml(doc.expiry) + '</span>'
							+ '<span class="dex-doc-days">' + escapeDocExpiryHtml(formatDocDays(doc)) + '</span>'
							+ '</span>';
					}).join('');

					var name = String(emp.name || '').trim();
					var initials = name.split(/\s+/).slice(0, 2).map(function(w) { return w.charAt(0); }).join('').toUpperCase() || '?';
					var search = (emp.emp_id + ' ' + name + ' ' + (emp.department || '') + ' ' + docs.map(function(d) { return d.type; }).join(' ')).toLowerCase();
					var viewUrl = 'view_employee.php?emp_id=' + encodeURIComponent(emp.emp_id);

					return '<div class="dex-row" data-search="' + escapeDocExpiryHtml(search) + '" data-types="' + escapeDocExpiryHtml(docs.map(function(d) { return d.type; }).join('|')) + '">'
						+ '<div class="dex-emp">'
						+ '<span class="dex-avatar">' + escapeDocExpiryHtml(initials) + '</span>'
						+ '<div class="dex-emp-text">'
						+ '<span class="dex-emp-name" title="' + escapeDocExpiryHtml(name) + '">' + escapeDocExpiryHtml(name) + '</span>'
						+ '<span class="dex-emp-sub"><span class="dex-mono">#' + escapeDocExpiryHtml(emp.emp_id) + '</span> · ' + escapeDocExpiryHtml(emp.department || '-') + '</span>'
						+ '</div></div>'
						+ '<div class="dex-docs">' + docsHtml + '</div>'
						+ '<a href="' + viewUrl + '" class="dex-view" target="_blank" rel="noopener" title="<?= getDisplayName('View Employee') ?>">'
						+ '<i class="fa fa-eye"></i><span><?= getDisplayName('View') ?></span></a>'
						+ '</div>';
				}).join('');

				var typeChips = '<button type="button" class="dex-chip active" data-type=""><?= getDisplayName('All') ?> <span>' + docCount + '</span></button>'
					+ Object.keys(types).sort().map(function(t) {
						return '<button type="button" class="dex-chip" data-type="' + escapeDocExpiryHtml(t) + '">' + escapeDocExpiryHtml(t) + ' <span>' + types[t] + '</span></button>';
					}).join('');

				// Stat tiles: [tone, icon, label, value, sub-line left, sub-line right, bar % (null = no bar)]
				var levelTone = level === 'danger' ? 'red' : (level === 'warning' ? 'amber' : 'sky');
				var activeCount = docCount - expiredCount;
				var pctOf = function(n) { return docCount > 0 ? Math.round(n / docCount * 1000) / 10 : 0; };
				var perEmp = rows.length ? (docCount / rows.length).toFixed(1) : '0';
				var statTiles = [
					['indigo', 'fa-users', '<?= getDisplayName('Employees') ?>', rows.length, '<?= getDisplayName('With expiring documents') ?>', '', null],
					[levelTone, 'fa-file-lines', '<?= getDisplayName('Documents') ?>', docCount, '<?= getDisplayName('Per employee') ?>', perEmp, null],
					[expiredCount > 0 ? 'red' : 'green', expiredCount > 0 ? 'fa-circle-exclamation' : 'fa-circle-check', '<?= getDisplayName('Expired') ?>', expiredCount,
						expiredCount > 0 ? '<?= getDisplayName('Of documents') ?>' : '<?= getDisplayName('All still valid') ?>', (expiredCount > 0 ? pctOf(expiredCount) : pctOf(activeCount)) + '%', expiredCount > 0 ? pctOf(expiredCount) : pctOf(activeCount)]
				];
				var statsHtml = statTiles.map(function(t) {
					return '<div class="dex-stat' + (t[0] === 'indigo' ? '' : ' is-' + t[0]) + '">'
						+ '<div class="dex-stat-top"><span class="dex-stat-icon"><i class="fa ' + t[1] + '"></i></span>'
						+ '<div class="dex-stat-text"><span class="dex-stat-label">' + t[2] + '</span><span class="dex-stat-value">' + t[3] + '</span></div></div>'
						+ (t[6] !== null ? '<div class="dex-stat-bar"><span style="width:' + t[6] + '%"></span></div>' : '')
						+ '<div class="dex-stat-sub"><span>' + t[4] + '</span><b>' + t[5] + '</b></div>'
						+ '</div>';
				}).join('');

				return ''
					+ '<div class="dex-wrap">'
					+ '<div class="dex-stats">' + statsHtml + '</div>'
					+ '<div class="dex-tools">'
					+ '<div class="dex-search"><i class="fa fa-magnifying-glass"></i><input type="search" id="dexSearch" placeholder="<?= getDisplayName('Search name, ID, department...') ?>" autocomplete="off"></div>'
					+ '<div class="dex-chips">' + typeChips + '</div>'
					+ '</div>'
					+ '<div class="dex-list" id="dexList">' + cards
					+ '<div class="dex-empty" id="dexNoMatch" style="display:none;"><i class="fa fa-magnifying-glass-minus"></i><?= getDisplayName('No matching employees.') ?></div>'
					+ '</div>'
					+ '</div>';
			}

			function wireDocExpiryFilters(popup) {
				var searchEl = popup.querySelector('#dexSearch');
				var activeType = '';
				function apply() {
					var q = searchEl ? searchEl.value.trim().toLowerCase() : '';
					var shown = 0;
					popup.querySelectorAll('.dex-row').forEach(function(row) {
						var okText = !q || row.getAttribute('data-search').indexOf(q) !== -1;
						var okType = !activeType || row.getAttribute('data-types').split('|').indexOf(activeType) !== -1;
						var show = okText && okType;
						row.style.display = show ? '' : 'none';
						if (show) shown++;
					});
					var none = popup.querySelector('#dexNoMatch');
					if (none) none.style.display = shown ? 'none' : '';
				}
				if (searchEl) searchEl.addEventListener('input', apply);
				popup.querySelectorAll('.dex-chip').forEach(function(chip) {
					chip.addEventListener('click', function() {
						popup.querySelectorAll('.dex-chip').forEach(function(c) { c.classList.remove('active'); });
						chip.classList.add('active');
						activeType = chip.getAttribute('data-type') || '';
						apply();
					});
				});
			}

			$('.updateExpID').click(function() {
	            $('#iid')      .val($(this).data('iid'));
	        });

            $(document).ready(function() {

                // Default Datatable
                $('#datatable').DataTable({
						dom: 'rtip',
						lengthChange: false,
						order: [[ 4, "asc" ]],
						language: {
							search: `<span>${__('search')}:</span> _INPUT_`,
							searchPlaceholder: `${__('search')}...`,
							lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
							info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
							infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
							infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
							paginate: {
								first: __('first'),
								last: __('last'),
								next: __('next'),
								previous: __('previous')
							},
							emptyTable: __('no_data_available_in_table'),
							zeroRecords: __('no_matching_records_found'),
							processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
						}
					}
				);
                $('#datatable_exp').DataTable({
					dom: 'rtip',
					order: [[ 4, "asc" ]],
					language: {
						search: `<span>${__('search')}:</span> _INPUT_`,
						searchPlaceholder: `${__('search')}...`,
						lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
						info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
						infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
						infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
						paginate: {
							first: __('first'),
							last: __('last'),
							next: __('next'),
							previous: __('previous')
						},
						emptyTable: __('no_data_available_in_table'),
						zeroRecords: __('no_matching_records_found'),
						processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
					}
				});

                // Card search boxes drive the DataTables search
                $('.dash-dt-filter').on('input', function() {
                    var sel = $(this).data('table');
                    if ($.fn.dataTable.isDataTable(sel)) { $(sel).DataTable().search(this.value).draw(); }
                });

                //Buttons examples
                var table = $('#datatable-buttons').DataTable({
                    lengthChange: false,
                    buttons: ['copy', 'excel', 'pdf']
                });

                // Key Tables
                $('#key-table').DataTable({
                    keys: true
                });

                // Responsive Datatable
                $('#responsive-datatable').DataTable();

                // Multi Selection Datatable
                $('#selection-datatable').DataTable({
                    select: {
                        style: 'multi'
                    }
                });
                table.buttons().container()
                        .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');
            } );

			$(document).on('click', '.iqama_exp_hijri', function (e) {
				e.preventDefault();
				var emid = $(this).data('id');
				Swal.fire({
					title: __('update_id_expiry_info'),
					html: id_exp_HTML(),
					showCancelButton: true,
					confirmButtonColor: APP_COLORS.primary,
					cancelButtonColor: APP_COLORS.danger_dark,
					cancelButtonText: __('cancel'),
					confirmButtonText: __('yes_update'),
					showLoaderOnConfirm: true,
					allowOutsideClick: false,
					willOpen: function() {
						$('input[name="emid"]').val(emid);
						AppDate.hijri('#iq_id_exp_hijri', { inline: true });
						AppDate.inline('#iq_id_exp_greg');
						$("input[name$='note']").click(function(){
							var value = $(this).val();
							if(value=='hijri') {
								$("#hijriDiv").show();
								$("#gregorianDiv").hide();
								$("#iq_id_exp_hijri").attr('name', 'iqama_exp');
								$("#iq_id_exp_greg").removeAttr('name');
							} else if(value=='gregorian') {
								$("#hijriDiv").hide();
								$("#gregorianDiv").show();
								$("#iq_id_exp_hijri").removeAttr('name');
								$("#iq_id_exp_greg").attr('name', 'iqama_exp_g');
							}
						});
					},
					preConfirm: function() {
						var iqama_exp = $('input[name="iqama_exp"]').val();
						var iqama_exp_g = $('input[name="iqama_exp_g"]').val();
						var inputCheck = $("input[name$='note']:checked").is(':checked');
						if(inputCheck == false){
							Swal.showValidationMessage(__("select_id_iqama_expiry_validation"))
						}
						return new Promise(function(reject, resolve) {
							if( inputCheck == false ){
								reject(__("fill_mandatory_fields"));
								return false;
							}
							$.ajax({
								url: './includes/ajaxFile/hrHandler.php',
								type: 'POST',
								dataType: "JSON",
								data: {'id': emid, 'iqama_exp':iqama_exp, 'iqama_exp_g':iqama_exp_g, ajaxType:'id_iqama_update' },
							})
							.done(function(response){
								// console.log(response.title)
								Swal.fire({
									title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
								}).then(function(isConfirm){(isConfirm)?location.reload():""});
							})
							.fail(function(jqXHR, textStatus, errorThrown) {
							
								reject(handleAjaxFailure(jqXHR, textStatus).message);
							});
						});
					},
				})
			});

			$(document).on('click', '.doc-expiry-card', function () {
				var level = $(this).data('level');
				var tone = level === 'danger' ? 'red' : (level === 'warning' ? 'amber' : 'sky');
				Swal.fire({
					title: '<span class="dex-title"><span class="dex-title-icon tone-' + tone + '"><i class="fa fa-file-shield"></i></span>' + getDocExpiryTitle(level) + '</span>',
					html: buildDocExpiryListHtml(level),
					width: '1000px',
					customClass: { popup: 'dex-popup' },
					allowOutsideClick: false,
					confirmButtonText: __('close') || 'Close',
					confirmButtonColor: APP_COLORS.primary,
					didOpen: function(popup) { wireDocExpiryFilters(popup); }
				});
			});


        </script>

    </body>
</html>
<?php } ?>