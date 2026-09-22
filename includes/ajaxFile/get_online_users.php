<?php
// Live "Online Users" feed for dashbydepart.php's sys_admin-only tab.
// Polled client-side (no refresh needed) - mirrors the same
// user_activity_log.status='active' query used for the initial page render
// and for the avatar online dot (employee_card.php / emp_top_info.php).
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');

if (empty($is_system_admin)) {
    http_response_code(403);
    echo json_encode(['status' => 403, 'message' => 'Forbidden']);
    exit;
}

$res = mysqli_query($conDB, "SELECT
        `ua`.`emp_id`, `ua`.`username`, `ua`.`ip_address`, `ua`.`city`, `ua`.`country`,
        `ua`.`browser`, `ua`.`os`, `ua`.`device_type`, `ua`.`current_page`,
        `ua`.`login_time`, `ua`.`last_activity`,
        `e`.`name`, `e`.`avatar`, `e`.`sex`,
        `d`.`dep_nme`, `d`.`dep_nme_ar`
    FROM `user_activity_log` `ua`
    LEFT JOIN `employees` `e` ON `e`.`emp_id` = `ua`.`emp_id`
    LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
    WHERE `ua`.`status` = 'active'
    ORDER BY `ua`.`last_activity` DESC");

$rows = [];
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $sex = $r['sex'] ?? 1;
        $avatarDefault = ($sex == 2) ? './assets/emp_pics/defultFemale.jpg' : './assets/emp_pics/defult.png';
        $avatar = !empty($r['avatar']) ? $r['avatar'] : $avatarDefault;
        $deptName = ($is_rtl ?? false) ? ($r['dep_nme_ar'] ?: $r['dep_nme']) : ($r['dep_nme'] ?: $r['dep_nme_ar']);

        $nameCell = '<img src="' . htmlspecialchars($avatar) . '" class="rounded-circle mr-2" width="32" height="32" style="object-fit:cover;">'
            . htmlspecialchars($r['name'] ?? $r['username'] ?? '-');
        $deviceCell = htmlspecialchars(trim(($r['browser'] ?? '') . ' / ' . ($r['os'] ?? ''), ' / ') ?: '-')
            . ' <span class="text-muted small">(' . htmlspecialchars($r['device_type'] ?? '-') . ')</span>';

        $rows[] = [
            $nameCell,
            htmlspecialchars($r['emp_id'] ?? '-'),
            htmlspecialchars($deptName ?: '-'),
            htmlspecialchars($r['ip_address'] ?? '-'),
            htmlspecialchars(trim(($r['city'] ?? '') . ', ' . ($r['country'] ?? ''), ', ') ?: '-'),
            $deviceCell,
            '<span class="small text-muted">' . htmlspecialchars($r['current_page'] ?? '-') . '</span>',
            htmlspecialchars($r['login_time'] ?? '-'),
            htmlspecialchars($r['last_activity'] ?? '-'),
        ];
    }
}

echo json_encode(['status' => 200, 'count' => count($rows), 'rows' => $rows]);
