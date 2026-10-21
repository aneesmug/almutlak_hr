<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../special_access_helper.php';
require_once __DIR__ . '/../zk_helpers.php';
// Attendance switched off in App Settings > Integrations
if (!attendance_enabled($conDB)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Attendance is turned off']);
    exit;
}

$canManage = !empty($is_system_admin) || user_has_special_access($conDB, $empid ?? '', 'manage_device_monitor', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$canManage) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied.']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_device':
        add_device($conDB);
        break;
    case 'update_device':
        update_device($conDB);
        break;
    case 'poll_state':
        poll_state($conDB);
        break;
    case 'sync_request':
        sync_request($conDB);
        break;
    case 'sync_status':
        sync_status($conDB);
        break;
    case 'sync_cancel':
        sync_cancel($conDB);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}

function add_device($conDB) {
    $serial = trim((string) ($_POST['serial_number'] ?? ''));
    $name = trim((string) ($_POST['device_name'] ?? ''));
    $area = trim((string) ($_POST['area'] ?? ''));
    $ip = trim((string) ($_POST['device_ip'] ?? ''));
    $pullHost = trim((string) ($_POST['pull_host'] ?? '')) ?: '185.137.245.28';
    $pullPortRaw = trim((string) ($_POST['pull_port'] ?? ''));
    $pullPort = $pullPortRaw !== '' ? (int) $pullPortRaw : null;

    if ($serial === '' || $name === '') {
        echo json_encode(['status' => 'error', 'message' => 'Serial number and device name are required.']);
        return;
    }

    $stmt = mysqli_prepare($conDB, "INSERT INTO zk_devices (serial_number, device_name, area, device_ip, pull_host, pull_port) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sssssi', $serial, $name, $area, $ip, $pullHost, $pullPort);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Device registered. It will show Online once it contacts this server, or once the socket sync check succeeds.']);
    } else {
        $isDuplicate = mysqli_errno($conDB) === 1062;
        echo json_encode(['status' => 'error', 'message' => $isDuplicate ? 'A device with this serial number is already registered.' : 'Failed to register device.']);
    }
    mysqli_stmt_close($stmt);
}

function update_device($conDB) {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['device_name'] ?? ''));
    $area = trim((string) ($_POST['area'] ?? ''));
    $ip = trim((string) ($_POST['device_ip'] ?? ''));
    $pullHost = trim((string) ($_POST['pull_host'] ?? '')) ?: '185.137.245.28';
    $pullPortRaw = trim((string) ($_POST['pull_port'] ?? ''));
    $pullPort = $pullPortRaw !== '' ? (int) $pullPortRaw : null;

    if ($id <= 0 || $name === '') {
        echo json_encode(['status' => 'error', 'message' => 'Device name is required.']);
        return;
    }

    // Serial number is intentionally not editable here - it's the identity
    // key the physical device uses to phone home (via ADMS push, still
    // pointed at BioTime), changing it here would orphan the record.
    $stmt = mysqli_prepare($conDB, "UPDATE zk_devices SET device_name = ?, area = ?, device_ip = ?, pull_host = ?, pull_port = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'ssssii', $name, $area, $ip, $pullHost, $pullPort, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success', 'message' => 'Device updated.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update device.']);
    }
    mysqli_stmt_close($stmt);
}

function poll_state($conDB) {
    $result = mysqli_query($conDB, "SELECT serial_number, state, last_activity, transaction_qty FROM zk_devices");
    $devices = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $devices[$row['serial_number']] = [
            'state' => $row['state'],
            'last_activity' => $row['last_activity'],
            'transaction_qty' => (int) $row['transaction_qty'],
        ];
    }
    echo json_encode(['status' => 'success', 'devices' => $devices]);
}

// --- "Sync ZKT now": queue a request for the agent on the local BioTime server
// (zk_local_agent/zk_sync_poll.ps1 picks it up within a minute via zk_sync_request.php)
function sync_request($conDB) {
    global $empid;
    $mode = ($_POST['mode'] ?? '') === 'resync' ? 'resync' : 'new';

    $current = zk_sync_request_get($conDB);
    if (zk_sync_request_is_active($current)) {
        echo json_encode(['status' => 'success', 'already' => true] + sync_status_payload($conDB));
        return;
    }

    $name = '';
    $stmt = mysqli_prepare($conDB, "SELECT name FROM employees WHERE emp_id = ? LIMIT 1");
    if ($stmt) {
        $eid = (string) ($empid ?? '');
        mysqli_stmt_bind_param($stmt, 's', $eid);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));
        $name = $row ? trim((string) $row[0]) : '';
        mysqli_stmt_close($stmt);
    }

    zk_sync_request_save($conDB, [
        'id' => bin2hex(random_bytes(8)),
        'mode' => $mode,
        'status' => 'pending',
        'requested_by' => (string) ($empid ?? ''),
        'requested_name' => $name,
        'requested_at' => date('Y-m-d H:i:s'),
    ]);
    echo json_encode(['status' => 'success', 'already' => false] + sync_status_payload($conDB));
}

// Stop a pending/running request. A running export on the ZKT server is stopped by
// zk_sync_poll.ps1 on its next progress call (already-sent batches stay saved).
function sync_cancel($conDB) {
    $request = zk_sync_request_get($conDB);
    if ($request && in_array($request['status'] ?? '', ['pending', 'running'], true)) {
        $request['status'] = 'cancelled';
        $request['finished_at'] = date('Y-m-d H:i:s');
        zk_sync_request_save($conDB, $request);
    }
    echo json_encode(['status' => 'success'] + sync_status_payload($conDB));
}

function sync_status($conDB) {
    echo json_encode(['status' => 'success'] + sync_status_payload($conDB));
}

function sync_status_payload($conDB) {
    $request = zk_sync_request_get($conDB);
    if ($request && zk_sync_request_is_active($request) === false && in_array($request['status'] ?? '', ['pending', 'running'], true)) {
        $request['status'] = 'stale'; // agent never answered / never reported back
    }
    $seen = zk_sync_setting_get($conDB, 'zk_sync_agent_seen');
    $seenTs = $seen !== '' ? strtotime($seen) : false;
    return [
        'request' => $request,
        'agent_seen' => $seen,
        'agent_seen_ago' => $seenTs ? max(0, time() - $seenTs) : null,
        'now' => date('Y-m-d H:i:s'),
    ];
}
