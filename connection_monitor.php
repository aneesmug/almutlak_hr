<?php
// Deliberately bypasses the normal login/session chain (session_check.php) -
// see includes/connmon_gate.php for why. If there's no valid access token yet,
// stop here and show only the OTP prompt - no DB connection is attempted at
// all, so this still works even when MySQL's connection pool is exhausted.
require_once __DIR__ . '/includes/connmon_gate.php';

if (!connmon_has_valid_token()) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <title>Connection Monitor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{margin:0; min-height:100vh; background:#0b0f1a; color:#e5e9f2; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; display:flex; align-items:center; justify-content:center}
    </style>
    </head>
    <body>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    async function promptOtp() {
        const { value: otp } = await Swal.fire({
            title: 'Access Code',
            text: 'Enter the current 4-digit access code.',
            input: 'text',
            inputAttributes: { maxlength: 4, inputmode: 'numeric', autocapitalize: 'off', autocorrect: 'off' },
            confirmButtonText: 'Unlock',
            allowOutsideClick: false,
            allowEscapeKey: false,
            background: '#131826',
            color: '#e5e9f2',
            inputValidator: (value) => !value ? 'Enter a code' : undefined
        });
        if (!otp) {
            return promptOtp();
        }
        try {
            const res = await fetch('connection_monitor_otp_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'otp=' + encodeURIComponent(otp)
            });
            const d = await res.json();
            if (d.success) {
                window.location.reload();
            } else {
                await Swal.fire({ icon: 'error', title: 'Incorrect code', background: '#131826', color: '#e5e9f2' });
                promptOtp();
            }
        } catch (e) {
            await Swal.fire({ icon: 'error', title: 'Network error', background: '#131826', color: '#e5e9f2' });
            promptOtp();
        }
    }
    promptOtp();
    </script>
    </body>
    </html>
    <?php
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/user_activity_logger.php';
require_once __DIR__ . '/includes/connection_monitor_helper.php';

$snap = connmon_snapshot($conDB);
$tokenExpiryMs = connmon_token_expiry() * 1000;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Connection Monitor</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    :root{
        --bg:#0b0f1a; --card:#131826; --text:#e5e9f2; --muted:#8b96ac;
        --border:#232a3d; --accent:#818cf8;
    }
    *{box-sizing:border-box}
    body{
        margin:0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;
        background:var(--bg); color:var(--text); padding:32px 20px;
    }
    .wrap{width:100%; margin:0 auto}
    .topbar{display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px}
    .topbar h1{font-size:22px; margin:0; display:flex; align-items:center; gap:10px}
    .live-dot{width:9px; height:9px; border-radius:50%; background:#16a34a; box-shadow:0 0 0 rgba(22,163,74,.5); animation:pulse 1.6s infinite}
    @keyframes pulse{
        0%{box-shadow:0 0 0 0 rgba(22,163,74,.5)}
        70%{box-shadow:0 0 0 8px rgba(22,163,74,0)}
        100%{box-shadow:0 0 0 0 rgba(22,163,74,0)}
    }
    .meta{color:var(--muted); font-size:13px}

    .cards{display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin-bottom:24px}
    .card{background:var(--card); border:1px solid var(--border); border-radius:14px; padding:20px; box-shadow:0 4px 14px rgba(0,0,0,.25)}
    .card .label{font-size:12px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:600}
    .card .value{font-size:34px; font-weight:700; margin-top:6px; line-height:1; transition:color .3s ease}
    .badge{display:inline-block; margin-top:10px; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600; transition:background-color .3s ease, color .3s ease}

    .gauge-wrap{background:var(--card); border:1px solid var(--border); border-radius:14px; padding:22px 24px; margin-bottom:24px}
    .gauge-head{display:flex; justify-content:space-between; align-items:baseline; margin-bottom:10px}
    .gauge-head span{font-size:13px; color:var(--muted); font-weight:600}
    .gauge-track{height:14px; background:#1c2334; border-radius:999px; overflow:hidden}
    .gauge-fill{height:100%; border-radius:999px; width:0%; transition:width .5s ease, background-color .5s ease}

    .panel{background:var(--card); border:1px solid var(--border); border-radius:14px; overflow:hidden}
    .panel h2{font-size:14px; margin:0; padding:16px 20px; border-bottom:1px solid var(--border); color:var(--text)}
    table{width:100%; border-collapse:collapse; font-size:13px}
    th,td{text-align:left; padding:10px 20px}
    thead th{font-size:11px; text-transform:uppercase; letter-spacing:.03em; color:var(--muted); background:#0f1420; border-bottom:1px solid var(--border)}
    tbody tr:not(:last-child) td{border-bottom:1px solid var(--border)}
    tbody tr:hover{background:#181f31}
    .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:12.5px; color:#c3cad9}
    .age-fresh{color:#22c55e; font-weight:600}
    .age-stale{color:#8b96ac}
    .empty{padding:28px 20px; text-align:center; color:var(--muted); font-size:13px}
    .foot-note{color:var(--muted); font-size:12px; margin-top:16px; line-height:1.6}
    .panel + .panel{margin-top:20px}
    .table-scroll{overflow-x:auto}
    .cmd-sleep{color:var(--muted)}
    .cmd-active{color:#818cf8; font-weight:600}
    .time-ok{color:var(--text)}
    .time-warn{color:#f59e0b; font-weight:600}
    .time-crit{color:#f87171; font-weight:700}
    .info-cell{max-width:320px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis}
    .count-pill{float:right; background:#1c2334; color:var(--muted); font-size:11px; font-weight:700; padding:2px 9px; border-radius:999px}

    /* DataTables (default/non-Bootstrap build) - restyled for dark theme */
    #activeUsersTable_wrapper{padding:16px 20px}
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate{color:var(--muted); font-size:12.5px}
    .dataTables_wrapper .dataTables_filter{float:right; margin-bottom:12px}
    .dataTables_wrapper .dataTables_length{float:left; margin-bottom:12px}
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select{
        background:#0f1420; color:var(--text); border:1px solid var(--border);
        border-radius:6px; padding:5px 8px; margin-left:6px; outline:none;
    }
    .dataTables_wrapper .dataTables_filter input:focus,
    .dataTables_wrapper .dataTables_length select:focus{border-color:var(--accent)}
    .dataTables_wrapper table.dataTable{margin-top:0 !important}
    .dataTables_wrapper .dataTables_info{clear:both; padding-top:14px}
    .dataTables_wrapper .dataTables_paginate{padding-top:14px; text-align:right}
    .dataTables_wrapper .dataTables_paginate .paginate_button{
        color:var(--muted) !important; padding:4px 10px; margin-left:4px; border-radius:6px;
        border:1px solid transparent; cursor:pointer;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current{
        background:var(--accent) !important; color:#fff !important; border-color:var(--accent) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover{
        background:#1c2334 !important; color:var(--text) !important; border-color:var(--border) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled{opacity:.4; cursor:default}
    table.dataTable thead th{position:relative}
    table.dataTable thead .sorting:after,
    table.dataTable thead .sorting_asc:after,
    table.dataTable thead .sorting_desc:after{opacity:.5; margin-left:4px}

    .btn-signout{
        background:rgba(248,113,113,.12); color:#f87171; border:1px solid rgba(248,113,113,.35);
        border-radius:6px; padding:4px 10px; font-size:12px; font-weight:600; cursor:pointer;
    }
    .btn-signout:hover{background:rgba(248,113,113,.22)}
    .btn-signout:disabled{opacity:.5; cursor:default}
    .page-cell{max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:inline-block; vertical-align:bottom}

    .btn-extend{
        background:rgba(34,197,94,.12); color:#22c55e; border:1px solid rgba(34,197,94,.35);
        border-radius:6px; padding:4px 10px; font-size:12px; font-weight:600; cursor:pointer;
        animation:extendPulse 1.4s infinite;
    }
    .btn-extend:hover{background:rgba(34,197,94,.22)}
    .btn-extend:disabled{opacity:.5; cursor:default; animation:none}
    @keyframes extendPulse{
        0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.35)}
        50%{box-shadow:0 0 0 5px rgba(34,197,94,0)}
    }
    #sessionCountdown.countdown-warn{color:#f59e0b; font-weight:700}
</style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <h1><span class="live-dot"></span> Connection Monitor</h1>
        <div style="display:flex; align-items:center; gap:16px">
            <div class="meta">Updated <span id="updatedAt"><?= htmlspecialchars($snap['server_time']) ?></span> &middot; refreshes every 4s</div>
            <div class="meta">Session expires in <span id="sessionCountdown" class="mono">--:--</span></div>
            <button type="button" class="btn-extend" id="extendSessionBtn" style="display:none">Extend session</button>
            <button type="button" class="btn-signout" id="selfSignoutBtn">Sign out</button>
        </div>
    </div>

    <div class="cards">
        <div class="card">
            <div class="label">MySQL Threads Connected</div>
            <div class="value" id="threadsValue" style="color:<?= htmlspecialchars($snap['status']['color']) ?>"><?= (int) $snap['threads_connected'] ?></div>
            <span class="badge" id="statusBadge" style="background:<?= htmlspecialchars($snap['status']['bg']) ?>; color:<?= htmlspecialchars($snap['status']['color']) ?>"><?= htmlspecialchars($snap['status']['label']) ?></span>
        </div>
        <div class="card">
            <div class="label">MySQL Max Connections</div>
            <div class="value" id="maxValue"><?= (int) $snap['max_connections'] ?></div>
        </div>
        <div class="card">
            <div class="label">App-Tracked (this app)</div>
            <div class="value" id="trackedValue"><?= (int) $snap['app_tracked'] ?></div>
        </div>
        <div class="card">
            <div class="label">Active Users (logged in)</div>
            <div class="value" id="activeUsersValue"><?= (int) $snap['active_users_count'] ?></div>
        </div>
    </div>

    <div class="gauge-wrap">
        <div class="gauge-head">
            <span>Pool usage</span>
            <span id="gaugePct"><?= (int) $snap['status']['pct'] ?>%</span>
        </div>
        <div class="gauge-track">
            <div class="gauge-fill" id="gaugeFill" style="width:<?= (int) $snap['status']['pct'] ?>%; background:<?= htmlspecialchars($snap['status']['color']) ?>"></div>
        </div>
    </div>

    <div class="panel">
        <h2>Currently logged-in users <span class="count-pill" id="activeUsersCount"><?= count($snap['active_users']) ?></span></h2>
        <div class="table-scroll">
            <table id="activeUsersTable" style="width:100%">
                <thead><tr><th>Employee</th><th>Emp ID</th><th>IP</th><th>Current Page</th><th>Device</th><th>Logged in since</th><th>Duration</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($snap['active_users'] as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td class="mono"><?= htmlspecialchars((string) $u['emp_id']) ?></td>
                        <td class="mono"><?= htmlspecialchars($u['ip']) ?></td>
                        <td class="mono"><span class="page-cell" title="<?= htmlspecialchars($u['page']) ?>"><?= htmlspecialchars($u['page']) ?></span></td>
                        <td class="mono"><?= htmlspecialchars($u['device']) ?></td>
                        <td class="mono"><?= htmlspecialchars($u['since']) ?></td>
                        <td class="mono"><?= htmlspecialchars(connmon_fmt_duration($u['duration'])) ?></td>
                        <td><button type="button" class="btn-signout" data-activity-id="<?= (int) $u['activity_id'] ?>">Sign out</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h2>Live MySQL connections (SHOW PROCESSLIST) <span class="count-pill" id="plCount"><?= count($snap['processlist']) ?></span></h2>
        <div class="table-scroll" id="processlistHolder">
            <?php if ($snap['processlist']): ?>
            <table>
                <thead><tr><th>Id</th><th>User</th><th>Host</th><th>DB</th><th>Command</th><th>Time</th><th>State</th><th>Info</th></tr></thead>
                <tbody>
                    <?php foreach ($snap['processlist'] as $p):
                        $cmdClass = $p['command'] === 'Sleep' ? 'cmd-sleep' : 'cmd-active';
                        $timeClass = $p['time'] >= 30 ? 'time-crit' : ($p['time'] >= 10 ? 'time-warn' : 'time-ok');
                    ?>
                    <tr>
                        <td class="mono"><?= htmlspecialchars((string) $p['id']) ?></td>
                        <td class="mono"><?= htmlspecialchars($p['user']) ?></td>
                        <td class="mono"><?= htmlspecialchars($p['host']) ?></td>
                        <td class="mono"><?= htmlspecialchars($p['db'] ?? '') ?></td>
                        <td class="<?= $cmdClass ?>"><?= htmlspecialchars($p['command']) ?></td>
                        <td class="<?= $timeClass ?>"><?= (int) $p['time'] ?>s</td>
                        <td class="mono"><?= htmlspecialchars($p['state']) ?></td>
                        <td class="mono info-cell" title="<?= htmlspecialchars($p['info']) ?>"><?= htmlspecialchars($p['info']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty">No processlist rows returned (or DB user lacks PROCESS privilege).</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <h2>App-tracked connections (this app's own slot file)</h2>
        <div id="tableHolder">
            <?php if ($snap['entries']): ?>
            <table>
                <thead><tr><th>User</th><th>Emp ID</th><th>IP</th><th>Page</th><th>Age</th><th>PID</th></tr></thead>
                <tbody id="tableBody">
                    <?php foreach ($snap['entries'] as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['user_name'] ?? '') ?: '<span class="age-stale">Guest / no session</span>' ?></td>
                        <td class="mono"><?= htmlspecialchars((string) ($e['emp_id'] ?? '')) ?></td>
                        <td class="mono"><?= htmlspecialchars($e['ip'] ?? '') ?></td>
                        <td class="mono"><?= htmlspecialchars($e['uri'] ?? '') ?></td>
                        <td><span class="<?= ($e['age'] ?? 0) < 5 ? 'age-fresh' : 'age-stale' ?>"><?= (int) ($e['age'] ?? 0) ?>s</span></td>
                        <td class="mono"><?= htmlspecialchars((string) ($e['pid'] ?? '')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty" id="emptyState">No active app-tracked connections right now.</div>
            <?php endif; ?>
        </div>
    </div>

    <p class="foot-note">
        The top table is MySQL's own live connection list (real "Threads Connected" source) - "Sleep" rows are
        idle connections just sitting there; a "Sleep" row lingering many seconds is exactly what a leak looks like.
        MySQL has no concept of app users, so that table can't show who's behind a row.
        The bottom table only reflects requests that went through this app's own db.php concurrency slot - it shows
        the logged-in User and Emp ID for each active request, resolved from their session, so you can see who is
        actually hitting the database and from which page right now. It stays small even when MySQL's real count is
        high; that gap is connections held by something other than a normal short-lived page request (idle sleepers,
        a stuck query, or another script/cron on the account).
    </p>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let tokenExpiryMs = <?= (int) $tokenExpiryMs ?>;
let extendBtnShown = false;

function tickCountdown() {
    const remainingMs = tokenExpiryMs - Date.now();
    const countdownEl = document.getElementById('sessionCountdown');
    const extendBtn = document.getElementById('extendSessionBtn');

    if (remainingMs <= 0) {
        countdownEl.textContent = '0:00';
        window.location.reload(); // cookie's expired server-side too - reload shows the OTP prompt
        return;
    }

    const totalSeconds = Math.floor(remainingMs / 1000);
    const m = Math.floor(totalSeconds / 60);
    const s = totalSeconds % 60;
    countdownEl.textContent = m + ':' + String(s).padStart(2, '0');

    const fiveMinutes = 5 * 60 * 1000;
    if (remainingMs <= fiveMinutes) {
        countdownEl.classList.add('countdown-warn');
        if (!extendBtnShown) {
            extendBtn.style.display = '';
            extendBtnShown = true;
        }
    } else {
        countdownEl.classList.remove('countdown-warn');
        extendBtn.style.display = 'none';
        extendBtnShown = false;
    }
}
setInterval(tickCountdown, 1000);
tickCountdown();

document.getElementById('extendSessionBtn').addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    btn.textContent = 'Extending...';
    try {
        const res = await fetch('connection_monitor_extend.php', { method: 'POST' });
        const d = await res.json();
        if (d.success) {
            tokenExpiryMs = d.expiry * 1000;
            btn.style.display = 'none';
            extendBtnShown = false;
            tickCountdown();
        } else {
            await Swal.fire({ icon: 'error', title: 'Could not extend', text: d.message || 'Please sign in again.', background: '#131826', color: '#e5e9f2' });
            window.location.reload();
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Network error', background: '#131826', color: '#e5e9f2' });
    } finally {
        btn.disabled = false;
        btn.textContent = 'Extend session';
    }
});

const activeUsersDT = $('#activeUsersTable').DataTable({
    order: [],
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    columnDefs: [{ targets: -1, orderable: false }],
    language: {
        search: '',
        searchPlaceholder: 'Search users...',
        emptyTable: 'No users currently marked active.',
        info: 'Showing _START_ to _END_ of _TOTAL_ users',
        infoEmpty: 'No users to show',
        infoFiltered: '(filtered from _MAX_ total)'
    }
});

document.getElementById('selfSignoutBtn').addEventListener('click', async function() {
    const result = await Swal.fire({
        icon: 'question',
        title: 'Sign out of Connection Monitor?',
        text: 'You will need the current access code to get back in.',
        showCancelButton: true,
        confirmButtonText: 'Sign out',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f87171',
        cancelButtonColor: '#374151',
        background: '#131826',
        color: '#e5e9f2'
    });
    if (result.isConfirmed) {
        window.location.href = 'connection_monitor_signout_self.php';
    }
});

// Delegated on the table's parent (survives DataTables redrawing tbody on every refresh/page/sort).
$('#activeUsersTable').closest('.dataTables_wrapper').on('click', '.btn-signout', async function() {
    const btn = this;
    const activityId = btn.getAttribute('data-activity-id');
    if (!activityId) {
        return;
    }

    const confirmResult = await Swal.fire({
        icon: 'warning',
        title: 'Sign this user out?',
        text: 'They will be logged out on their next click or page load.',
        showCancelButton: true,
        confirmButtonText: 'Sign out',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f87171',
        cancelButtonColor: '#374151',
        background: '#131826',
        color: '#e5e9f2'
    });
    if (!confirmResult.isConfirmed) {
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Signing out...';
    try {
        const res = await fetch('connection_monitor_signout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'activity_id=' + encodeURIComponent(activityId)
        });
        const d = await res.json();
        if (d.success) {
            Swal.fire({
                icon: 'success', title: 'Signed out', timer: 1500, showConfirmButton: false,
                background: '#131826', color: '#e5e9f2'
            });
            refresh();
        } else {
            Swal.fire({
                icon: 'error', title: 'Could not sign out', text: d.message || 'Please try again.',
                background: '#131826', color: '#e5e9f2'
            });
            btn.disabled = false;
            btn.textContent = 'Sign out';
        }
    } catch (e) {
        Swal.fire({
            icon: 'error', title: 'Network error', text: 'Could not sign out this user.',
            background: '#131826', color: '#e5e9f2'
        });
        btn.disabled = false;
        btn.textContent = 'Sign out';
    }
});

async function refresh() {
    try {
        const res = await fetch('connection_monitor_data.php', { cache: 'no-store' });
        if (!res.ok) return;
        const d = await res.json();
        if (d.error) return;

        document.getElementById('threadsValue').textContent = d.threads_connected;
        document.getElementById('threadsValue').style.color = d.status.color;
        document.getElementById('maxValue').textContent = d.max_connections;
        document.getElementById('trackedValue').textContent = d.app_tracked;
        document.getElementById('updatedAt').textContent = d.server_time;

        const badge = document.getElementById('statusBadge');
        badge.textContent = d.status.label;
        badge.style.background = d.status.bg;
        badge.style.color = d.status.color;

        document.getElementById('gaugePct').textContent = d.status.pct + '%';
        const fill = document.getElementById('gaugeFill');
        fill.style.width = d.status.pct + '%';
        fill.style.background = d.status.color;

        document.getElementById('activeUsersValue').textContent = d.active_users_count;
        document.getElementById('activeUsersCount').textContent = d.active_users.length;
        const auRowsData = d.active_users.map(u => {
            const pageEsc = escapeHtml(u.page || '-');
            return [
                escapeHtml(u.name || ''),
                `<span class="mono">${escapeHtml(String(u.emp_id || ''))}</span>`,
                `<span class="mono">${escapeHtml(u.ip || '')}</span>`,
                `<span class="mono"><span class="page-cell" title="${pageEsc}">${pageEsc}</span></span>`,
                `<span class="mono">${escapeHtml(u.device || '')}</span>`,
                `<span class="mono">${escapeHtml(u.since || '')}</span>`,
                `<span class="mono">${escapeHtml(formatDuration(u.duration))}</span>`,
                `<button type="button" class="btn-signout" data-activity-id="${escapeHtml(String(u.activity_id || ''))}">Sign out</button>`
            ];
        });
        activeUsersDT.clear();
        activeUsersDT.rows.add(auRowsData);
        activeUsersDT.draw(false);

        document.getElementById('plCount').textContent = d.processlist.length;
        const plHolder = document.getElementById('processlistHolder');
        if (d.processlist.length === 0) {
            plHolder.innerHTML = '<div class="empty">No processlist rows returned (or DB user lacks PROCESS privilege).</div>';
        } else {
            let plRows = d.processlist.map(p => {
                const cmdClass = p.command === 'Sleep' ? 'cmd-sleep' : 'cmd-active';
                const timeClass = p.time >= 30 ? 'time-crit' : (p.time >= 10 ? 'time-warn' : 'time-ok');
                const info = escapeHtml(p.info || '');
                return `<tr>
                    <td class="mono">${escapeHtml(String(p.id))}</td>
                    <td class="mono">${escapeHtml(p.user || '')}</td>
                    <td class="mono">${escapeHtml(p.host || '')}</td>
                    <td class="mono">${escapeHtml(p.db || '')}</td>
                    <td class="${cmdClass}">${escapeHtml(p.command || '')}</td>
                    <td class="${timeClass}">${p.time}s</td>
                    <td class="mono">${escapeHtml(p.state || '')}</td>
                    <td class="mono info-cell" title="${info}">${info}</td>
                </tr>`;
            }).join('');
            plHolder.innerHTML = `<table>
                <thead><tr><th>Id</th><th>User</th><th>Host</th><th>DB</th><th>Command</th><th>Time</th><th>State</th><th>Info</th></tr></thead>
                <tbody>${plRows}</tbody>
            </table>`;
        }

        const holder = document.getElementById('tableHolder');
        if (d.entries.length === 0) {
            holder.innerHTML = '<div class="empty">No active app-tracked connections right now.</div>';
        } else {
            let rows = d.entries.map(e => {
                const ageClass = e.age < 5 ? 'age-fresh' : 'age-stale';
                const userCell = e.user_name ? escapeHtml(e.user_name) : '<span class="age-stale">Guest / no session</span>';
                return `<tr>
                    <td>${userCell}</td>
                    <td class="mono">${escapeHtml(String(e.emp_id || ''))}</td>
                    <td class="mono">${escapeHtml(e.ip || '')}</td>
                    <td class="mono">${escapeHtml(e.uri || '')}</td>
                    <td><span class="${ageClass}">${e.age}s</span></td>
                    <td class="mono">${escapeHtml(String(e.pid || ''))}</td>
                </tr>`;
            }).join('');
            holder.innerHTML = `<table>
                <thead><tr><th>User</th><th>Emp ID</th><th>IP</th><th>Page</th><th>Age</th><th>PID</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>`;
        }
    } catch (e) {
        // silent - keep last known values on transient network errors
    }
}

function formatDuration(seconds) {
    seconds = parseInt(seconds, 10) || 0;
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

setInterval(refresh, 4000);
</script>
</body>
</html>
