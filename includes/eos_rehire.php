<?php
/**
 * Re-hire after End of Service.
 *
 * An employee who left through End of Service keeps that record untouched (inactive, with its
 * emp_eos settlement). Re-hiring (Edit Employee > status Active/Draft) registers them again as a
 * NEW employee under the next free emp_id - a copy of the old record with a new joining date - and
 * links the two on the emp_eos row (rehired_emp_id), so the old record can point to the new one.
 */

if (!function_exists('eos_ensure_rehire_columns')) {
	function eos_ensure_rehire_columns($conDB): void
	{
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		$have = [];
		$res = mysqli_query($conDB, "SHOW COLUMNS FROM `emp_eos`");
		if (!$res) {
			return;
		}
		while ($row = mysqli_fetch_assoc($res)) {
			$have[$row['Field']] = true;
		}
		$add = [
			'rehired_at' => "ADD COLUMN `rehired_at` DATETIME NULL DEFAULT NULL",
			'rehired_by' => "ADD COLUMN `rehired_by` VARCHAR(50) NULL DEFAULT NULL",
			'rehired_emp_id' => "ADD COLUMN `rehired_emp_id` VARCHAR(255) NULL DEFAULT NULL",
			'rehire_joining_date' => "ADD COLUMN `rehire_joining_date` DATE NULL DEFAULT NULL",
		];
		$parts = [];
		foreach ($add as $col => $sql) {
			if (!isset($have[$col])) {
				$parts[] = $sql;
			}
		}
		if ($parts) {
			mysqli_query($conDB, "ALTER TABLE `emp_eos` " . implode(', ', $parts));
		}
	}
}

/** The employee's latest End of Service row (with the re-hire link, if any), or null. */
if (!function_exists('eos_latest_record')) {
	function eos_latest_record($conDB, string $empId): ?array
	{
		eos_ensure_rehire_columns($conDB);
		$stmt = $conDB->prepare("SELECT `id`, `end_date`, `net_payment`, `leaving_reason`, `rehired_at`, `rehired_emp_id`, `rehire_joining_date`
			FROM `emp_eos` WHERE `emp_id` = ? ORDER BY `id` DESC LIMIT 1");
		$stmt->bind_param('s', $empId);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_assoc();
		$stmt->close();
		return $row ?: null;
	}
}

/**
 * Next free employee number: one above the highest number used by an employee or held by an
 * Add New Employee draft (employee_drafts reserves its emp_id). Numeric max - emp_id is a VARCHAR.
 */
if (!function_exists('eos_next_emp_id')) {
	function eos_next_emp_id(PDO $pdo): string
	{
		$max = (int)$pdo->query("SELECT COALESCE(MAX(CAST(`emp_id` AS UNSIGNED)), 0) FROM `employees`")->fetchColumn();
		$hasDrafts = $pdo->query("SHOW TABLES LIKE 'employee_drafts'")->fetchColumn();
		if ($hasDrafts) {
			$max = max($max, (int)$pdo->query("SELECT COALESCE(MAX(CAST(`emp_id` AS UNSIGNED)), 0) FROM `employee_drafts`")->fetchColumn());
		}
		return (string)($max + 1);
	}
}

/**
 * Copies $oldEmpId's employees row to a new row under the next free emp_id, as a fresh employment:
 * new joining date and status, inactive / vacation details cleared. Returns the new emp_id.
 * Runs inside the caller's transaction; retries when another registration takes the number first.
 */
if (!function_exists('eos_create_rehire_record')) {
	function eos_create_rehire_record(PDO $pdo, string $oldEmpId, string $joiningDate, string $status): string
	{
		$override = [
			'emp_id' => null, // set per attempt
			'status' => $status,
			'joining_date' => $joiningDate,
			'note' => '',
			'ter_note' => '',
			'ter_date' => '',
			'fly' => '0',
			'contract_end_date' => null,
			'salary_update_pending' => 0,
			'created_at' => date('Y-m-d H:i:s'),
		];
		$cols = [];
		foreach ($pdo->query("SHOW COLUMNS FROM `employees`") as $c) {
			if ($c['Field'] === 'id' || $c['Field'] === 'updated_at') {
				continue; // auto values
			}
			$cols[] = $c['Field'];
		}
		$select = [];
		foreach ($cols as $col) {
			$select[] = array_key_exists($col, $override) ? ":ov_$col" : "`$col`";
		}
		$sql = "INSERT INTO `employees` (`" . implode('`, `', $cols) . "`) SELECT " . implode(', ', $select)
			. " FROM `employees` WHERE `emp_id` = :old_emp_id LIMIT 1";

		for ($attempt = 0; $attempt < 3; $attempt++) {
			$newEmpId = eos_next_emp_id($pdo);
			$params = [':old_emp_id' => $oldEmpId];
			foreach ($override as $col => $value) {
				if (in_array($col, $cols, true)) {
					$params[":ov_$col"] = $col === 'emp_id' ? $newEmpId : $value;
				}
			}
			try {
				$pdo->exec("SAVEPOINT rehire_insert");
				$pdo->prepare($sql)->execute($params);
				return $newEmpId;
			} catch (PDOException $e) {
				$pdo->exec("ROLLBACK TO SAVEPOINT rehire_insert");
				if ($e->getCode() !== '23000' || $attempt === 2) {
					throw $e;
				}
				// number taken meanwhile (unique emp_id) - try the next one
			}
		}
		throw new RuntimeException('Could not allocate a new employee number.');
	}
}
