<?php
/**
 * Employee avatar - one place that decides which picture an employee shows, and keeps
 * employees.avatar in sync with it:
 *
 *   1. the stored avatar, when it is an uploaded picture whose file really exists;
 *   2. otherwise the employee's newest uploaded picture still on disk
 *      (assets/emp_pics/emp_<emp_id>/..., or a legacy upload in assets/emp_pics/ named
 *      <emp_id><id>[name without spaces]<unix time>.png by ajaxpro.php / hrHandler.php) -
 *      except pictures an employee submitted that are still Pending or were Rejected
 *      (employee_temp_contants, type 'Profile Picture');
 *   3. otherwise the default picture for the employee's gender (sex 2 = female).
 *
 * Stored paths are always written as "./assets/emp_pics/..." (relative to the system root);
 * admin_login.avatar is kept equal to employees.avatar. Pictures that still fail in the browser
 * are replaced by includes/avatar_image.php (see jquery.app.js).
 * employee_avatar_sync() runs per employee on profile view / save / registration and for
 * everyone once a day from zk_sync_import.php.
 */

const EMP_AVATAR_MALE = './assets/emp_pics/defult.png';
const EMP_AVATAR_FEMALE = './assets/emp_pics/defultFemale.jpg';

if (!function_exists('employee_avatar_is_female')) {
	function employee_avatar_is_female($sex): bool
	{
		$sex = strtolower(trim((string)$sex));
		return $sex === '2' || $sex === 'female';
	}
}

if (!function_exists('employee_avatar_default')) {
	function employee_avatar_default($sex): string
	{
		return employee_avatar_is_female($sex) ? EMP_AVATAR_FEMALE : EMP_AVATAR_MALE;
	}
}

/** "./../../assets/emp_pics/x.png", "/assets/..", "assets/.." -> "./assets/emp_pics/x.png" ('' when not an emp_pics path) */
if (!function_exists('employee_avatar_normalize')) {
	function employee_avatar_normalize($path): string
	{
		$path = str_replace('\\', '/', trim((string)$path));
		$pos = strpos($path, 'assets/emp_pics/');
		if ($pos === false) {
			return '';
		}
		$rel = substr($path, $pos);
		if (strpos($rel, '..') !== false) {
			return '';
		}
		return './' . $rel;
	}
}

if (!function_exists('employee_avatar_is_default')) {
	function employee_avatar_is_default($path): bool
	{
		$file = strtolower(basename((string)$path));
		return $file === '' || $file === 'defult.png' || $file === 'defultfemale.jpg';
	}
}

if (!function_exists('employee_avatar_abs')) {
	function employee_avatar_abs(string $webPath): string
	{
		return dirname(__DIR__) . '/' . ltrim(substr($webPath, 2), '/');
	}
}

/** File names of profile pictures employees submitted that are Pending or Rejected - never auto-assigned. */
if (!function_exists('employee_avatar_unapproved_files')) {
	function employee_avatar_unapproved_files($conDB): array
	{
		$out = [];
		$res = @mysqli_query($conDB, "SELECT `path` FROM `employee_temp_contants` WHERE `type` = 'Profile Picture' AND `status` IN ('Pending', 'Rejected')");
		if ($res) {
			while ($row = mysqli_fetch_row($res)) {
				$out[strtolower(basename((string)$row[0]))] = true;
			}
		}
		return $out;
	}
}

/** Newest uploaded picture of this employee still on disk, as "./assets/emp_pics/...", or ''. */
if (!function_exists('employee_avatar_find_upload')) {
	function employee_avatar_find_upload(string $empId, $rowId = '', $name = '', array $unapproved = []): string
	{
		$base = dirname(__DIR__) . '/assets/emp_pics/';
		$best = '';
		$bestTime = -1;
		$consider = function (string $abs, string $web) use (&$best, &$bestTime, $unapproved) {
			if (isset($unapproved[strtolower(basename($abs))])) {
				return;
			}
			if (!preg_match('/\.(png|jpe?g|gif|webp)$/i', $abs) || !is_file($abs) || filesize($abs) === 0) {
				return;
			}
			$t = (int)@filemtime($abs);
			if ($t > $bestTime) {
				$bestTime = $t;
				$best = $web;
			}
		};
		if ($empId !== '' && ctype_digit($empId) && is_dir($base . 'emp_' . $empId)) {
			foreach ((array)@scandir($base . 'emp_' . $empId) as $f) {
				if ($f !== '.' && $f !== '..') {
					$consider($base . 'emp_' . $empId . '/' . $f, './assets/emp_pics/emp_' . $empId . '/' . $f);
				}
			}
		}
		// Legacy uploads in the folder root: <emp_id><id>[name without spaces]<unix time>.png
		$prefix = $empId . (string)$rowId;
		$nameNoSpace = str_replace(' ', '', (string)$name);
		if ($best === '' && ctype_digit($empId) && ctype_digit((string)$rowId)) {
			$pattern = '/^' . preg_quote($prefix, '/') . '(' . preg_quote($nameNoSpace, '/') . ')?\d{10}\.png$/';
			foreach ((array)@glob($base . $prefix . '*.png') as $abs) {
				if (preg_match($pattern, basename($abs))) {
					$consider($abs, './assets/emp_pics/' . basename($abs));
				}
			}
		}
		return $best;
	}
}

/** The avatar this employee should show. $row: avatar, sex, emp_id, [id, name]. */
if (!function_exists('employee_avatar_resolve')) {
	function employee_avatar_resolve(array $row, array $unapproved = []): string
	{
		$stored = employee_avatar_normalize($row['avatar'] ?? '');
		if ($stored !== '' && !employee_avatar_is_default($stored) && is_file(employee_avatar_abs($stored))) {
			return $stored;
		}
		$upload = employee_avatar_find_upload((string)($row['emp_id'] ?? ''), $row['id'] ?? '', $row['name'] ?? '', $unapproved);
		return $upload !== '' ? $upload : employee_avatar_default($row['sex'] ?? '');
	}
}

/**
 * Writes the resolved avatar to employees.avatar where it differs. $empId null = every employee.
 * Returns ['checked' => n, 'updated' => n].
 */
if (!function_exists('employee_avatar_sync')) {
	function employee_avatar_sync($conDB, ?string $empId = null): array
	{
		$sql = "SELECT `id`, `emp_id`, `name`, `sex`, `avatar` FROM `employees`";
		if ($empId !== null) {
			if (!ctype_digit($empId)) {
				return ['checked' => 0, 'updated' => 0];
			}
			$sql .= " WHERE `emp_id` = '" . mysqli_real_escape_string($conDB, $empId) . "'";
		}
		$res = mysqli_query($conDB, $sql);
		$checked = 0;
		$updated = 0;
		if ($res) {
			$unapproved = employee_avatar_unapproved_files($conDB);
			$upd = $conDB->prepare("UPDATE `employees` SET `avatar` = ? WHERE `id` = ?");
			while ($row = mysqli_fetch_assoc($res)) {
				$checked++;
				$want = employee_avatar_resolve($row, $unapproved);
				if ($want !== (string)$row['avatar']) {
					$id = (int)$row['id'];
					$upd->bind_param('si', $want, $id);
					$upd->execute();
					$updated++;
				}
			}
			$upd->close();
		}
		// admin_login keeps its own copy (user lists) - mirror the employee's picture there
		$where = $empId !== null ? " AND e.`emp_id` = '" . mysqli_real_escape_string($conDB, $empId) . "'" : '';
		@mysqli_query($conDB, "UPDATE `admin_login` a JOIN `employees` e ON e.`emp_id` = a.`emp_id`
			SET a.`avatar` = e.`avatar` WHERE a.`avatar` <> e.`avatar`" . $where);
		return ['checked' => $checked, 'updated' => $updated];
	}
}
