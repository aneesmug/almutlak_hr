<?php
/**
 * "Sync ZKT now" - endpoint for the agent on the local BioTime server
 * (zk_local_agent/zk_sync_poll.ps1, run every minute by Task Scheduler).
 * Same auth as zk_sync_import.php: shared secret + optional allowed-IP list.
 *
 *   POST action=poll    -> records that the agent is alive; when a request made with the
 *                          "Sync ZKT" button on Device Monitor is pending, marks it running
 *                          and returns {request: {id, mode}} (mode: new | resync).
 *                          busy=1 -> a scheduled export is running; the request keeps waiting.
 *   POST action=progress -> id, progress (JSON: stage, batch, fetched, sent, inserted, ...):
 *                          live step-by-step status shown in the Sync ZKT modal.
 *   POST action=report  -> id, ok (1/0), sent, inserted, duplicates, last_id, message:
 *                          stores the outcome so the button can show it.
 *
 * The agent only ever calls OUT to this app - nothing on the office network is exposed.
 */
require_once __DIR__ . '/includes/zk_helpers.php';

header('Content-Type: application/json; charset=UTF-8');

$conn = zk_get_db_connection();
if (!$conn) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Database unavailable.']);
    exit;
}

$authError = zk_sync_agent_auth_error($conn);
if ($authError) {
    error_log('[ZK] zk_sync_request.php rejected request from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ': ' . $authError[1]);
    http_response_code($authError[0]);
    echo json_encode(['status' => 'error', 'message' => $authError[1]]);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'poll') {
    $now = date('Y-m-d H:i:s');
    zk_sync_setting_set($conn, 'zk_sync_agent_seen', $now);

    $request = zk_sync_request_get($conn);
    if (!$request || ($request['status'] ?? '') !== 'pending' || zk_sync_request_is_stale($request)) {
        echo json_encode(['status' => 'success', 'request' => null]);
        exit;
    }

    // busy=1: a scheduled export is still running on the ZKT server - the request
    // waits for it; the Sync modal shows "ZKT server busy, starts right after".
    if (!empty($_POST['busy']) && $_POST['busy'] !== '0') {
        $request['agent_busy_at'] = $now;
        zk_sync_request_save($conn, $request);
        echo json_encode(['status' => 'success', 'request' => null]);
        exit;
    }

    $request['status'] = 'running';
    $request['picked_at'] = $now;
    $request['progress'] = ['stage' => 'starting', 'at' => $now];
    $request['stage_times'] = ['starting' => $now];
    zk_sync_request_save($conn, $request);

    echo json_encode(['status' => 'success', 'request' => ['id' => $request['id'], 'mode' => $request['mode']]]);
    exit;
}

if ($action === 'progress') {
    $request = zk_sync_request_get($conn);
    $id = (string) ($_POST['id'] ?? '');
    if (!$request || $id === '' || !hash_equals((string) $request['id'], $id) || ($request['status'] ?? '') !== 'running') {
        echo json_encode(['status' => 'error', 'message' => 'Unknown or finished request id.']);
        exit;
    }

    $in = json_decode((string) ($_POST['progress'] ?? ''), true);
    $in = is_array($in) ? $in : [];
    $now = date('Y-m-d H:i:s');
    $stage = (string) ($in['stage'] ?? ($request['progress']['stage'] ?? 'starting'));
    if (!in_array($stage, zk_sync_progress_stages(), true)) {
        $stage = $request['progress']['stage'] ?? 'starting';
    }

    $progress = ['stage' => $stage, 'at' => $now] + (is_array($request['progress'] ?? null) ? $request['progress'] : []);
    foreach (['batch', 'fetched', 'sent', 'inserted', 'duplicates', 'devices', 'batch_size', 'sync_days'] as $k) {
        if (isset($in[$k]) && is_numeric($in[$k])) {
            $progress[$k] = (int) $in[$k];
        }
    }
    foreach (['last_time', 'window_from', 'message'] as $k) {
        if (isset($in[$k]) && is_scalar($in[$k])) {
            $progress[$k] = mb_substr(trim((string) $in[$k]), 0, 200);
        }
    }
    $request['progress'] = $progress;
    if (empty($request['stage_times'][$stage])) {
        $request['stage_times'][$stage] = $now;
    }
    zk_sync_request_save($conn, $request);

    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'report') {
    $request = zk_sync_request_get($conn);
    $id = (string) ($_POST['id'] ?? '');
    if (!$request || $id === '' || !hash_equals((string) $request['id'], $id)) {
        echo json_encode(['status' => 'error', 'message' => 'Unknown request id.']);
        exit;
    }

    $ok = !empty($_POST['ok']) && $_POST['ok'] !== '0';
    $request['status'] = $ok ? 'done' : 'failed';
    $request['finished_at'] = date('Y-m-d H:i:s');
    $request['result'] = [
        'sent' => (int) ($_POST['sent'] ?? 0),
        'inserted' => (int) ($_POST['inserted'] ?? 0),
        'duplicates' => (int) ($_POST['duplicates'] ?? 0),
        'last_id' => (string) ($_POST['last_id'] ?? ''),
        'message' => mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 500),
    ];
    zk_sync_request_save($conn, $request);

    echo json_encode(['status' => 'success']);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
