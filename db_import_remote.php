<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

if (!($is_system_admin ?? false)) {
    header("Location: error403.php?page=" . urlencode(basename(__FILE__)));
    exit;
}

$localDbName = '';
$res = $conDB->query("SELECT DATABASE() AS db");
if ($res && ($row = $res->fetch_assoc())) {
    $localDbName = (string) $row['db'];
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?? '' ?> - Import From Live Server</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .dbi-max { max-width: 1440px; }

        /* Wizard stepper */
        .dbi-wizard { padding: 16px 18px 14px; }
        .dbi-wizard .sr-steps { margin-top: 0; }
        .dbi-wizard .sr-step-label small { display: block; font-weight: 500; color: var(--sr-muted); font-size: 11px; margin-top: 1px; }

        /* Connection form */
        .dbi-input { position: relative; }
        .dbi-input > i { position: absolute; inset-inline-start: 12px; top: 50%; transform: translateY(-50%); color: var(--sr-muted); pointer-events: none; font-size: 13px; }
        .sr-form .dbi-input .form-control { padding-inline-start: 34px; }
        .sr-form .dbi-input.has-btn .form-control { padding-inline-end: 40px; }
        .dbi-input-btn { position: absolute; inset-inline-end: 4px; top: 50%; transform: translateY(-50%); width: 32px; height: 30px; border: 0; border-radius: 8px; background: transparent; color: var(--sr-muted); cursor: pointer; }
        .dbi-input-btn:hover { background: var(--sr-accent-soft); color: var(--sr-accent-strong); }
        .dbi-conn-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
        .sr-form .dbi-conn-foot .sr-check { display: inline-flex; margin: 0; font-size: 13px; color: var(--sr-text-2); }
        .dbi-conn-foot .sr-check small { font-weight: 400; color: var(--sr-muted); margin-inline-start: 4px; }
        .dbi-conn-foot .sr-btn-primary { height: 40px; padding: 0 18px; }
        .dbi-flow { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 12px; background: var(--sr-surface-2); border: 1px dashed var(--sr-border-strong); font-size: 12.5px; }
        .dbi-flow-node { display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1 1 0; }
        .dbi-flow-node > i { flex: 0 0 auto; width: 30px; height: 30px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; }
        .dbi-flow-node b { display: block; color: var(--sr-text); font-size: 12.5px; }
        .dbi-flow-node span { display: block; color: var(--sr-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: "SFMono-Regular", Consolas, monospace; font-size: 11.5px; }
        .dbi-flow-node.is-live > i { background: var(--tone-sky-bg); color: var(--tone-sky-fg); }
        .dbi-flow-node.is-local > i { background: var(--tone-green-bg); color: var(--tone-green-fg); }
        .dbi-flow-arrow { flex: 0 0 auto; color: var(--sr-accent); }
        [dir="rtl"] .dbi-flow-arrow { transform: scaleX(-1); }

        /* Summary stats after connect */
        .dbi-sum { margin: 0 0 20px; }
        .dbi-sum .sr-stat { background: var(--sr-surface); box-shadow: var(--sr-shadow); }

        /* Table picker */
        .dbi-filter { display: inline-flex; padding: 3px; border-radius: 10px; background: var(--sr-surface-3); gap: 2px; }
        .dbi-filter button { border: 0; background: transparent; padding: 6px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 600; color: var(--sr-muted); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .dbi-filter button .sr-count { height: 18px; min-width: 20px; font-size: 11px; }
        .dbi-filter button.active { background: var(--sr-surface); color: var(--sr-text); box-shadow: 0 1px 2px rgba(15, 23, 42, .08); }
        #tableListWrap { max-height: 460px; overflow-y: auto; }
        #tableListWrap thead th { position: sticky; top: 0; background: var(--sr-surface-2) !important; z-index: 1; }
        #tableListWrap .table-check, #checkAllVisible { width: 16px; height: 16px; cursor: pointer; accent-color: var(--sr-accent); vertical-align: middle; }
        #tableListWrap tbody tr { cursor: pointer; }
        #tableListWrap tbody tr.is-checked td { background: var(--sr-accent-soft); }
        #tableListWrap .dbi-cb { width: 44px; }
        #tableListWrap .dbi-num { text-align: end; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .dbi-tname { display: flex; align-items: center; gap: 10px; }
        .dbi-tname > i { flex: 0 0 auto; width: 26px; height: 26px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: var(--sr-surface-3); color: var(--sr-muted); font-size: 11px; }
        tr.is-diff .dbi-tname > i { background: var(--tone-amber-bg); color: var(--tone-amber-fg); }
        tr.is-missing .dbi-tname > i { background: var(--tone-red-bg); color: var(--tone-red-fg); }
        .dbi-empty-row td { cursor: default; }

        /* Import options */
        .dbi-modes { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        @media (max-width: 767px) { .dbi-modes { grid-template-columns: 1fr; } }
        .dbi-mode { position: relative; margin: 0 !important; cursor: pointer; }
        .dbi-mode input { position: absolute; opacity: 0; pointer-events: none; }
        .dbi-mode-card { display: flex; gap: 12px; align-items: flex-start; height: 100%; padding: 13px 14px; border: 1px solid var(--sr-border-strong); border-radius: 12px; background: var(--sr-surface-2); transition: border-color .15s, background .15s, box-shadow .15s; }
        .dbi-mode-card > i { width: 34px; height: 34px; flex: 0 0 auto; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: var(--sr-surface-3); color: var(--sr-muted); font-size: 15px; }
        .dbi-mode-card b { display: block; font-size: 13.5px; color: var(--sr-text); text-transform: none; letter-spacing: 0; }
        .dbi-mode-card small { display: block; margin-top: 2px; font-size: 12px; font-weight: 400; color: var(--sr-muted); line-height: 1.45; text-transform: none; letter-spacing: 0; }
        .dbi-mode input:checked + .dbi-mode-card { border-color: var(--sr-accent); background: var(--sr-accent-soft); box-shadow: 0 0 0 1px var(--sr-accent) inset; }
        .dbi-mode input:checked + .dbi-mode-card > i { background: var(--sr-accent); color: #fff; }
        .dbi-mode.is-danger input:checked + .dbi-mode-card { border-color: #dc2626; background: var(--tone-red-bg); box-shadow: 0 0 0 1px #dc2626 inset; }
        .dbi-mode.is-danger input:checked + .dbi-mode-card > i { background: #dc2626; }
        .dbi-mode input:focus-visible + .dbi-mode-card { box-shadow: 0 0 0 3px rgba(99, 102, 241, .25); }

        /* Sticky selection / action bar */
        .dbi-bar { position: sticky; bottom: 0; z-index: 5; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 12px 18px; border-top: 1px solid var(--sr-border); background: var(--sr-surface); border-radius: 0 0 var(--sr-radius) var(--sr-radius); box-shadow: 0 -6px 16px rgba(15, 23, 42, .05); }
        .dbi-bar-info { display: flex; align-items: center; gap: 10px; margin-inline-end: auto; font-size: 13px; color: var(--sr-muted); }
        .dbi-bar-info > i { width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; background: var(--sr-accent-soft); color: var(--sr-accent-strong); }
        .dbi-bar-info b { color: var(--sr-text); font-size: 14px; }
        .dbi-bar .sr-btn-primary { height: 40px; padding: 0 18px; }
        .dbi-bar .sr-btn-primary.is-danger { background: #dc2626; border-color: #dc2626; }

        .dbi-side-list { margin: 0; padding: 0; list-style: none; font-size: 12.5px; }
        .dbi-side-list li { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 7px 0; border-bottom: 1px dashed var(--sr-border); }
        .dbi-side-list li:last-child { border-bottom: 0; }
        .dbi-side-list span { color: var(--sr-muted); }

        /* Import progress modal (lives in the Swal popup, outside .sr-page - so literal colors + app-dark overrides) */
        .swal2-popup.dbi-popup { padding: 22px 22px 18px; border-radius: 16px; }
        .swal2-popup.dbi-popup .swal2-title { font-size: 19px; font-weight: 700; color: #0f172a; padding: 0; margin: 0 0 4px; text-align: left; }
        .swal2-popup.dbi-popup .swal2-html-container { margin: 0; padding: 0; text-align: left; overflow: visible; }
        .dbi-pg-sub { font-size: 13px; color: #64748b; margin-bottom: 14px; }
        .dbi-steps { display: flex; gap: 8px; margin-bottom: 14px; }
        .dbi-step { flex: 1; display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 10px; border: 1px solid #e5e7eb; background: #f8fafc; font-size: 12.5px; font-weight: 600; color: #64748b; }
        .dbi-step-num { width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; background: #e2e8f0; color: #475569; flex: 0 0 auto; }
        .dbi-step.is-active { border-color: #a5b4fc; background: #eef2ff; color: #4338ca; }
        .dbi-step.is-active .dbi-step-num { background: #6366f1; color: #fff; }
        .dbi-step.is-done { color: #15803d; border-color: #86efac; background: #f0fdf4; }
        .dbi-step.is-done .dbi-step-num { background: #22c55e; color: #fff; }
        .dbi-step.is-failed { color: #b91c1c; border-color: #fca5a5; background: #fef2f2; }
        .dbi-step.is-failed .dbi-step-num { background: #ef4444; color: #fff; }
        .dbi-prog-head { display: flex; justify-content: space-between; align-items: baseline; font-size: 12.5px; color: #64748b; margin-bottom: 6px; }
        .dbi-prog-head strong { color: #0f172a; font-size: 14px; font-variant-numeric: tabular-nums; }
        .dbi-prog { height: 8px; border-radius: 999px; background: #e2e8f0; overflow: hidden; margin-bottom: 12px; }
        .dbi-prog > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #6366f1, #22c55e); transition: width .3s; }
        .dbi-prog.has-error > span { background: linear-gradient(90deg, #6366f1, #f59e0b); }
        .dbi-time { display: flex; flex-wrap: wrap; gap: 6px 16px; margin: -4px 0 12px; font-size: 12.5px; color: #64748b; }
        .dbi-time i { color: #6366f1; margin-right: 3px; }
        .dbi-time strong { color: #0f172a; font-variant-numeric: tabular-nums; }
        html.app-dark .dbi-time strong { color: #f1f5f9; }
        .dbi-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin-bottom: 12px; }
        .dbi-stat { padding: 8px 10px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; }
        .dbi-stat-label { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
        .dbi-stat-value { display: block; font-size: 17px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; line-height: 1.3; }
        .dbi-stat.tone-ok .dbi-stat-value { color: #15803d; }
        .dbi-stat.tone-warn .dbi-stat-value { color: #b45309; }
        .dbi-stat.tone-err .dbi-stat-value { color: #b91c1c; }
        .dbi-list { position: relative; max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; }
        .dbi-row { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .dbi-row:last-child { border-bottom: 0; }
        .dbi-row.is-running { background: #eef2ff; }
        .dbi-row-icon { width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; flex: 0 0 auto; background: #f1f5f9; color: #94a3b8; }
        .dbi-row.is-running .dbi-row-icon { background: #e0e7ff; color: #4f46e5; }
        .dbi-row.is-done .dbi-row-icon { background: #dcfce7; color: #16a34a; }
        .dbi-row.is-warn .dbi-row-icon { background: #fef3c7; color: #d97706; }
        .dbi-row.is-error .dbi-row-icon { background: #fee2e2; color: #dc2626; }
        .dbi-row-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: "SFMono-Regular", Consolas, monospace; color: #334155; }
        .dbi-row.is-pending .dbi-row-name { color: #94a3b8; }
        .dbi-row-count { flex: 0 0 auto; white-space: nowrap; font-size: 12px; font-weight: 600; padding: 2px 9px; border-radius: 999px; background: #f1f5f9; color: #64748b; font-variant-numeric: tabular-nums; }
        .dbi-row.is-done .dbi-row-count { background: #f0fdf4; color: #15803d; }
        .dbi-row.is-warn .dbi-row-count { background: #fffbeb; color: #b45309; }
        .dbi-row.is-error .dbi-row-count { background: #fef2f2; color: #b91c1c; }
        .dbi-row.is-running .dbi-row-count { background: #e0e7ff; color: #4338ca; }
        .dbi-alert { margin-bottom: 12px; padding: 10px 12px; border-radius: 10px; font-size: 12.5px; background: #fef2f2; color: #b91c1c; border: 1px solid #fca5a5; max-height: 120px; overflow-y: auto; }
        .dbi-alert.is-warn { background: #fffbeb; color: #b45309; border-color: #fcd34d; }
        .dbi-done-head { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .dbi-done-icon { width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; flex: 0 0 auto; background: #dcfce7; color: #16a34a; }
        .dbi-done-icon.is-warn { background: #fef3c7; color: #d97706; }
        /* Schema check modal */
        .dbs-list { max-height: 360px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
        .dbs-card { border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; flex: 0 0 auto; }
        .dbs-card.has-diff { border-color: #fcd34d; }
        .dbs-card-head { display: flex; align-items: center; gap: 10px; padding: 10px 12px; }
        .dbs-card-icon { width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; background: #eef2ff; color: #4f46e5; font-size: 12px; flex: 0 0 auto; }
        .dbs-card.has-diff .dbs-card-icon { background: #fef3c7; color: #d97706; }
        .dbs-card-name { flex: 1; min-width: 0; font-family: "SFMono-Regular", Consolas, monospace; font-size: 13.5px; font-weight: 600; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dbs-pill { flex: 0 0 auto; font-size: 11.5px; font-weight: 700; padding: 3px 10px; border-radius: 999px; }
        .dbs-pill.is-ok { background: #f0fdf4; color: #15803d; }
        .dbs-pill.is-warn { background: #fffbeb; color: #b45309; }
        .dbs-pill.is-err { background: #fef2f2; color: #b91c1c; }
        .dbs-card-body { padding: 4px 12px 10px; border-top: 1px solid #f1f5f9; }
        .dbs-sec { display: flex; align-items: center; gap: 6px; margin: 10px 0 6px; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .dbs-sec span { font-weight: 500; text-transform: none; letter-spacing: 0; color: #64748b; }
        .dbs-sec.is-err { color: #b91c1c; }
        .dbs-sec.is-warn { color: #b45309; }
        .dbs-note { margin-top: 8px; font-size: 12.5px; padding: 7px 10px; border-radius: 8px; }
        .dbs-note.is-err { background: #fef2f2; color: #b91c1c; }
        .dbs-col { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 6px 8px; border-radius: 8px; background: #f8fafc; margin-bottom: 4px; }
        .dbs-col-info { display: flex; align-items: center; gap: 8px; min-width: 0; }
        .dbs-col-info code { font-size: 12.5px; color: #4338ca; background: #eef2ff; padding: 1px 6px; border-radius: 5px; }
        .dbs-col-type { font-size: 12px; color: #64748b; font-family: "SFMono-Regular", Consolas, monospace; }
        .dbs-seg { position: relative; display: inline-flex; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background: #fff; }
        .dbs-seg input { position: absolute; opacity: 0; pointer-events: none; }
        .dbs-seg label { margin: 0; padding: 4px 10px; font-size: 12px; font-weight: 600; color: #64748b; cursor: pointer; border-left: 1px solid #e5e7eb; transition: background .15s, color .15s; }
        .dbs-seg label:first-of-type { border-left: 0; }
        .dbs-seg input:checked + label { background: #6366f1; color: #fff; }
        .dbs-seg input:checked + label.is-ok { background: #16a34a; }
        .dbs-seg input:checked + label.is-err { background: #dc2626; }
        .dbs-seg input:focus-visible + label { box-shadow: inset 0 0 0 2px rgba(99, 102, 241, .5); }
        .dbs-types { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; }
        .dbs-types code { font-size: 12px; color: #b45309; background: #fffbeb; padding: 1px 6px; border-radius: 5px; }
        .dbs-sql { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px 12px; background: #f8fafc; }
        .dbs-sql-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 13px; font-weight: 700; color: #0f172a; }
        .dbs-sql-btns { display: flex; gap: 6px; }
        .dbs-sql-out { width: 100%; margin-top: 10px; font-family: "SFMono-Regular", Consolas, monospace; font-size: 12px; border-radius: 8px; border: 1px solid #1e293b; background: #0f172a; color: #e2e8f0; padding: 10px; resize: vertical; }
        .dbs-sql-hint { margin-top: 6px; font-size: 11.5px; color: #64748b; }
        html.app-dark .dbs-card, html.app-dark .dbs-seg { background: #1d2436; border-color: #2a3348; }
        html.app-dark .dbs-card.has-diff { border-color: #6b5417; }
        html.app-dark .dbs-card-name, html.app-dark .dbs-sql-head { color: #f1f5f9; }
        html.app-dark .dbs-card-body { border-color: #2a3348; }
        html.app-dark .dbs-col, html.app-dark .dbs-sql { background: #161c2a; border-color: #2a3348; }
        html.app-dark .dbs-col-info code { background: #272c52; color: #c7d2fe; }
        html.app-dark .dbs-seg label { border-color: #2a3348; color: #8b98ad; }
        html.app-dark .dbs-pill.is-ok { background: #12301f; color: #86efac; }
        html.app-dark .dbs-pill.is-warn, html.app-dark .dbs-types code { background: #3a2e12; color: #fcd34d; }
        html.app-dark .dbs-pill.is-err, html.app-dark .dbs-note.is-err { background: #3a1717; color: #fca5a5; }
        @media (max-width: 575px) { .dbi-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .dbi-steps { flex-direction: column; } }
        html.app-dark .swal2-popup.dbi-popup .swal2-title, html.app-dark .dbi-prog-head strong, html.app-dark .dbi-stat-value { color: #f1f5f9; }
        html.app-dark .dbi-step, html.app-dark .dbi-stat, html.app-dark .dbi-list { background: #1d2436; border-color: #2a3348; }
        html.app-dark .dbi-step-num, html.app-dark .dbi-prog, html.app-dark .dbi-row-icon, html.app-dark .dbi-row-count { background: #252e44; }
        html.app-dark .dbi-step.is-active, html.app-dark .dbi-row.is-running { background: #252b4a; border-color: #4547a0; color: #c7d2fe; }
        html.app-dark .dbi-step.is-done { background: #12301f; border-color: #1f6a3c; color: #86efac; }
        html.app-dark .dbi-step.is-failed, html.app-dark .dbi-alert { background: #3a1717; border-color: #7a2a2a; color: #fca5a5; }
        html.app-dark .dbi-alert.is-warn { background: #3a2e12; border-color: #6b5417; color: #fcd34d; }
        html.app-dark .dbi-row { border-color: #252e44; }
        html.app-dark .dbi-row-name { color: #d8dee9; }
    </style>
    <?php if ($is_rtl ?? false) : ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22"></span>
                        <i><img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>

        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>
            <div class="content sr-page">
                <div class="container-fluid dbi-max">

                    <div class="sr-head">
                        <div>
                            <h1>Import From Live Server</h1>
                            <p>Compare this database with the live server and pull the differences in, table by table.</p>
                        </div>
                        <div class="sr-head-actions">
                            <span class="sr-pill tone-amber"><i class="fa-solid fa-lock"></i> System admin only</span>
                            <a href="db_export.php" class="sr-btn"><i class="fa-solid fa-file-export"></i> Database Export</a>
                        </div>
                    </div>

                    <div class="sr-card dbi-wizard">
                        <div class="sr-steps" id="dbiWizard">
                            <div class="sr-step current" data-step="1"><div class="sr-step-dot">1</div><div class="sr-step-label">Connect<small>Live URL + export key</small></div></div>
                            <div class="sr-step" data-step="2"><div class="sr-step-dot">2</div><div class="sr-step-label">Choose tables<small>Review the differences</small></div></div>
                            <div class="sr-step" data-step="3"><div class="sr-step-dot">3</div><div class="sr-step-label">Import<small>Upsert or full replace</small></div></div>
                        </div>
                    </div>

                    <div class="sr-import">
                        <div>
                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="fa-solid fa-server"></i> Live Server</h5>
                                    <span id="connStatus"><span class="sr-pill tone-slate"><span class="sr-dot"></span>Not connected</span></span>
                                </div>
                                <div class="sr-form">
                                    <div class="sr-fgrid" style="padding: 18px;">
                                        <div class="sr-fcol c-7">
                                            <label for="baseUrl">Live Server URL</label>
                                            <div class="dbi-input">
                                                <i class="fa-solid fa-globe"></i>
                                                <input type="url" class="form-control" id="baseUrl" placeholder="https://your-live-domain.com" autocomplete="url">
                                            </div>
                                        </div>
                                        <div class="sr-fcol c-5">
                                            <label for="exportKey">Export Key</label>
                                            <div class="dbi-input has-btn">
                                                <i class="fa-solid fa-key"></i>
                                                <input type="password" class="form-control" id="exportKey" placeholder="From Database Export on live" autocomplete="off" spellcheck="false">
                                                <button type="button" class="dbi-input-btn" id="toggleKeyBtn" title="Show key" aria-label="Show key"><i class="fa-solid fa-eye"></i></button>
                                            </div>
                                        </div>
                                        <div class="sr-fcol c-12">
                                            <div class="dbi-flow">
                                                <div class="dbi-flow-node is-live"><i class="fa-solid fa-cloud"></i><div><b>Live</b><span id="flowLive">not set</span></div></div>
                                                <i class="fa-solid fa-arrow-right-long dbi-flow-arrow"></i>
                                                <div class="dbi-flow-node is-local"><i class="fa-solid fa-laptop-code"></i><div><b>This database</b><span><?= htmlspecialchars($localDbName ?: '-') ?></span></div></div>
                                            </div>
                                        </div>
                                        <div class="sr-fcol c-12">
                                            <div class="dbi-conn-foot">
                                                <label class="sr-check" for="rememberUrl">
                                                    <input type="checkbox" id="rememberUrl">
                                                    <span>Remember URL on this browser<small>(the key is never saved)</small></span>
                                                </label>
                                                <button type="button" class="sr-btn sr-btn-primary" id="testConnBtn"><i class="fa-solid fa-plug"></i> Connect &amp; Compare</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="pickerSection" style="display:none;">
                                <div class="sr-stats dbi-sum">
                                    <div class="sr-stat is-sky">
                                        <div class="sr-stat-label">Live tables <i class="fa-solid fa-table"></i></div>
                                        <div class="sr-stat-value" id="sumTables">0</div>
                                        <div class="sr-stat-sub" id="sumDb">-</div>
                                    </div>
                                    <div class="sr-stat is-amber">
                                        <div class="sr-stat-label">With differences <i class="fa-solid fa-code-compare"></i></div>
                                        <div class="sr-stat-value" id="sumDiff">0</div>
                                        <div class="sr-stat-sub">Row count differs</div>
                                    </div>
                                    <div class="sr-stat is-red">
                                        <div class="sr-stat-label">Missing locally <i class="fa-solid fa-circle-exclamation"></i></div>
                                        <div class="sr-stat-value" id="sumMissing">0</div>
                                        <div class="sr-stat-sub">Create before import</div>
                                    </div>
                                    <div class="sr-stat is-green">
                                        <div class="sr-stat-label">Live rows <i class="fa-solid fa-list-ol"></i></div>
                                        <div class="sr-stat-value" id="sumRows">0</div>
                                        <div class="sr-stat-sub" id="sumRowsSub">Across all tables</div>
                                    </div>
                                </div>

                                <div class="sr-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="fa-solid fa-database"></i> Tables <span class="sr-count"><span id="tableCount">0</span></span></h5>
                                        <div class="dbi-filter" role="tablist" aria-label="Table filter">
                                            <button type="button" class="active" data-filter="diff">Differences <span class="sr-count" id="cntDiff">0</span></button>
                                            <button type="button" data-filter="missing">Missing <span class="sr-count" id="cntMissing">0</span></button>
                                            <button type="button" data-filter="all">All <span class="sr-count" id="tableTotalCount">0</span></button>
                                        </div>
                                    </div>
                                    <div class="sr-toolbar">
                                        <div class="sr-search">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                            <input type="text" id="tableFilter" placeholder="Search tables...">
                                        </div>
                                        <div class="sr-toolbar-right">
                                            <button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="selectAllBtn"><i class="fa-solid fa-square-check"></i> All</button>
                                            <button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="selectNoneBtn"><i class="fa-regular fa-square"></i> None</button>
                                            <button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="selectDiffBtn"><i class="fa-solid fa-code-compare"></i> Differences only</button>
                                        </div>
                                    </div>
                                    <div class="sr-table-wrap" id="tableListWrap"></div>

                                    <div class="sr-form">
                                        <div class="sr-fgrid" style="padding: 18px; border-top: 1px solid var(--sr-border);">
                                            <div class="sr-fcol c-12">
                                                <label>Import mode</label>
                                                <div class="dbi-modes">
                                                    <label class="dbi-mode">
                                                        <input type="radio" name="importMode" id="modeUpsert" value="upsert" checked>
                                                        <span class="dbi-mode-card"><i class="fa-solid fa-code-merge"></i><span><b>Merge (upsert)</b><small>Update matching rows, insert new ones. Local-only rows are kept.</small></span></span>
                                                    </label>
                                                    <label class="dbi-mode is-danger">
                                                        <input type="radio" name="importMode" id="truncateFirst" value="replace">
                                                        <span class="dbi-mode-card"><i class="fa-solid fa-eraser"></i><span><b>Full replace (truncate first)</b><small>Empties the selected local tables, then loads live data. Use when counts match but data differs.</small></span></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="sr-fcol c-6">
                                                <label for="sinceInput">Only rows changed since <span style="font-weight: 400; text-transform: none;">(optional)</span></label>
                                                <input type="text" class="form-control" id="sinceInput" placeholder="Any time" autocomplete="off">
                                                <small class="sr-fhint" id="sinceHint">Tables with <code>updated_at</code>/<code>created_at</code> only. Not available with Full replace.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="dbi-bar">
                                        <div class="dbi-bar-info">
                                            <i class="fa-solid fa-layer-group"></i>
                                            <div><b id="selCount">0</b> table(s) selected<br><span id="selRows">0 live rows</span></div>
                                        </div>
                                        <button type="button" class="sr-btn" id="checkSchemaBtn"><i class="fa-solid fa-table-columns"></i> Check Schema</button>
                                        <button type="button" class="sr-btn sr-btn-primary" id="importBtn"><i class="fa-solid fa-cloud-arrow-down"></i> <span>Import Selected</span></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="fa-solid fa-route"></i> How It Works</h5>
                                </div>
                                <div class="sr-card-body">
                                    <ol class="sr-guide">
                                        <li>On the <b>live</b> server open <b>Database Export</b> and copy the export key.</li>
                                        <li>Enter the live URL and key here, then <b>Connect &amp; Compare</b>.</li>
                                        <li>Tables whose row counts differ are <b>pre-selected</b>. Run <b>Check Schema</b> if columns may have changed.</li>
                                        <li><b>Import</b>. Tables are pulled one at a time in pages of 20,000 rows; the key rotates automatically between requests.</li>
                                    </ol>
                                </div>
                            </div>

                            <div class="sr-card">
                                <div class="sr-card-head">
                                    <h5 class="sr-card-title"><i class="fa-solid fa-circle-info"></i> Good to Know</h5>
                                </div>
                                <div class="sr-card-body">
                                    <ul class="dbi-side-list">
                                        <li><span>Matching rows</span><span class="sr-pill sr-pill-xs tone-sky">updated</span></li>
                                        <li><span>Rows only on live</span><span class="sr-pill sr-pill-xs tone-green">inserted</span></li>
                                        <li><span>Rows only local</span><span class="sr-pill sr-pill-xs tone-slate">kept</span></li>
                                        <li><span>Full replace</span><span class="sr-pill sr-pill-xs tone-red">local rows deleted</span></li>
                                    </ul>
                                </div>
                            </div>

                            <div class="sr-notice tone-amber">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <div><strong>Writes to this database.</strong> Only connect to a live server and key you trust, and take a local backup before a full replace.</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <footer class="footer"><?= $site_footer ?? '' ?></footer>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script>
        let liveTables = [];
        let tableFilterMode = 'diff';

        const $baseUrl = $('#baseUrl');
        const $exportKey = $('#exportKey');

        // Only the URL is ever remembered. Older versions also stored the key here -
        // wipe it so a secret doesn't linger in localStorage.
        try {
            localStorage.removeItem('db_import_remote_export_key');
            const savedUrl = localStorage.getItem('db_import_remote_base_url');
            if (savedUrl) {
                $baseUrl.val(savedUrl);
                $('#rememberUrl').prop('checked', true);
            }
        } catch (e) { /* storage blocked */ }

        function setWizardStep(step) {
            $('#dbiWizard .sr-step').each(function() {
                const n = parseInt(this.dataset.step, 10);
                $(this).toggleClass('done', n < step).toggleClass('current', n === step);
                $(this).find('.sr-step-dot').html(n < step ? '<i class="fa-solid fa-check"></i>' : n);
            });
        }

        function updateFlowLive() {
            const url = $baseUrl.val().trim();
            $('#flowLive').text(url ? url.replace(/^https?:\/\//i, '').replace(/\/+$/, '') : 'not set');
        }
        $baseUrl.on('input', updateFlowLive);
        updateFlowLive();

        $('#toggleKeyBtn').on('click', function() {
            const show = $exportKey.attr('type') === 'password';
            $exportKey.attr('type', show ? 'text' : 'password');
            $(this).attr('title', show ? 'Hide key' : 'Show key')
                .html(show ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>');
        });

        $baseUrl.add($exportKey).on('keydown', function(e) {
            if (e.key === 'Enter') $('#testConnBtn').trigger('click');
        });

        function renderTableList(tables) {
            liveTables = tables;
            const diffCount = tables.filter(t => (t.diff || 0) !== 0 || !t.exists_locally).length;
            const missingCount = tables.filter(t => !t.exists_locally).length;
            const liveRows = tables.reduce((s, t) => s + (t.live_rows || 0), 0);

            $('#tableTotalCount').text(tables.length);
            $('#cntDiff').text(diffCount);
            $('#cntMissing').text(missingCount);
            $('#sumTables').text(tables.length.toLocaleString());
            $('#sumDiff').text(diffCount.toLocaleString());
            $('#sumMissing').text(missingCount.toLocaleString());
            $('#sumRows').text(liveRows.toLocaleString());

            const $wrap = $('#tableListWrap').empty();
            const $table = $('<table class="table sr-table" style="width:100%;"><thead><tr>' +
                '<th class="dbi-cb"><input type="checkbox" id="checkAllVisible" title="Select all visible"></th><th>Table</th><th class="dbi-num">Local</th>' +
                '<th class="dbi-num">Live</th><th class="dbi-num">Difference</th>' +
                '</tr></thead><tbody></tbody></table>');
            const $tbody = $table.find('tbody');

            tables.forEach(t => {
                const diff = t.diff || 0;
                const missing = !t.exists_locally;
                const name = escHtml(t.name);
                const localLabel = missing ? '<span class="sr-pill sr-pill-xs tone-red"><span class="sr-dot"></span>missing</span>' : (t.local_rows || 0).toLocaleString();
                let diffLabel = '<span class="sr-pill sr-pill-xs tone-slate">in sync</span>';
                if (diff > 0) diffLabel = '<span class="sr-pill sr-pill-xs tone-green"><i class="fa-solid fa-arrow-down"></i> +' + diff.toLocaleString() + '</span>';
                else if (diff < 0) diffLabel = '<span class="sr-pill sr-pill-xs tone-red"><i class="fa-solid fa-arrow-up"></i> ' + diff.toLocaleString() + '</span>';
                const changed = diff !== 0 || missing;

                $tbody.append(`
                    <tr class="table-row${changed ? ' is-diff' : ''}${missing ? ' is-missing' : ''}" data-diff="${diff}" data-missing="${missing ? 1 : 0}">
                        <td class="dbi-cb"><input type="checkbox" class="table-check" value="${name}" data-live-rows="${t.live_rows || 0}" ${changed ? 'checked' : ''}></td>
                        <td><span class="dbi-tname"><i class="fa-solid ${missing ? 'fa-circle-exclamation' : (changed ? 'fa-code-compare' : 'fa-table')}"></i><span class="sr-cell-title sr-mono">${name}</span></span></td>
                        <td class="dbi-num">${localLabel}</td>
                        <td class="dbi-num">${(t.live_rows || 0).toLocaleString()}</td>
                        <td class="dbi-num">${diffLabel}</td>
                    </tr>
                `);
            });
            $tbody.append('<tr class="dbi-empty-row" style="display:none;"><td colspan="5"><div class="sr-empty"><i class="fa-solid fa-circle-check"></i><div>No tables match this view.</div></div></td></tr>');
            $wrap.append($table);
            $('#pickerSection').show();
            // Open on "Differences" when there are any, otherwise show everything
            setFilterMode(diffCount ? 'diff' : 'all');
        }

        function setFilterMode(mode) {
            tableFilterMode = mode;
            $('.dbi-filter button').removeClass('active').filter('[data-filter="' + mode + '"]').addClass('active');
            applyRowVisibility();
        }

        // The filter tabs pick the eligible set (differences / missing / all); the search
        // box narrows within it. Selection is never changed by filtering.
        function applyRowVisibility() {
            const q = $('#tableFilter').val().toLowerCase();
            let shown = 0;
            $('.table-row').each(function() {
                const $row = $(this);
                const diff = parseInt($row.attr('data-diff'), 10) || 0;
                const missing = $row.attr('data-missing') === '1';
                const name = $row.find('.table-check').val().toLowerCase();
                let eligible = true;
                if (tableFilterMode === 'diff') eligible = diff !== 0 || missing;
                else if (tableFilterMode === 'missing') eligible = missing;
                const visible = eligible && name.includes(q);
                $row.toggle(visible);
                if (visible) shown++;
            });
            $('#tableCount').text(shown);
            $('.dbi-empty-row').toggle(shown === 0);
            updateSelectionInfo();
        }

        function updateSelectionInfo() {
            let count = 0, rows = 0;
            $('.table-check').each(function() {
                $(this).closest('tr').toggleClass('is-checked', this.checked);
                if (this.checked) {
                    count++;
                    rows += parseInt(this.dataset.liveRows, 10) || 0;
                }
            });
            $('#selCount').text(count);
            $('#selRows').text(rows.toLocaleString() + ' live rows');
            $('#sumRowsSub').text(rows.toLocaleString() + ' in selection');
            $('#importBtn, #checkSchemaBtn').prop('disabled', count === 0);

            const $visible = $('.table-row:visible .table-check');
            const checkedVisible = $visible.filter(':checked').length;
            $('#checkAllVisible')
                .prop('checked', $visible.length > 0 && checkedVisible === $visible.length)
                .prop('indeterminate', checkedVisible > 0 && checkedVisible < $visible.length);
        }

        $('#tableFilter').on('input', applyRowVisibility);
        $('.dbi-filter').on('click', 'button', function() { setFilterMode(this.dataset.filter); });

        $('#tableListWrap')
            .on('change', '.table-check', updateSelectionInfo)
            .on('change', '#checkAllVisible', function() {
                $('.table-row:visible .table-check').prop('checked', this.checked);
                updateSelectionInfo();
            })
            .on('click', '.table-row', function(e) {
                if ($(e.target).is('input')) return;
                const cb = $(this).find('.table-check')[0];
                cb.checked = !cb.checked;
                updateSelectionInfo();
            });

        const $sinceInput = $('#sinceInput');
        AppDate.single('#sinceInput', { enableTime: true, allowInput: true });
        const $truncateFirst = $('#truncateFirst');

        $('input[name="importMode"]').on('change', function() {
            const replace = $truncateFirst.is(':checked');
            if (replace) {
                const fp = AppDate.get('#sinceInput');
                if (fp) fp.clear(); else $sinceInput.val('');
                $sinceInput.prop('disabled', true);
            } else {
                $sinceInput.prop('disabled', false);
            }
            $('#importBtn').toggleClass('is-danger', replace)
                .find('span').text(replace ? 'Replace Selected' : 'Import Selected');
        });

        $('#selectAllBtn').on('click', () => { $('.table-row:visible .table-check').prop('checked', true); updateSelectionInfo(); });
        $('#selectNoneBtn').on('click', () => { $('.table-row:visible .table-check').prop('checked', false); updateSelectionInfo(); });
        $('#selectDiffBtn').on('click', function() {
            $('.table-row').each(function() {
                const $cb = $(this).find('.table-check');
                const t = liveTables.find(x => x.name === $cb.val());
                $cb.prop('checked', !!(t && (t.diff !== 0 || !t.exists_locally)));
            });
            updateSelectionInfo();
        });

        // Shared by the "Connect" button and the post-import refresh below - both
        // just need the current Local/Live/Diff counts re-pulled and re-rendered.
        function refreshConnectionAndTables(baseUrl, exportKey, onDone) {
            $('#connStatus').html('<span class="sr-pill tone-slate"><i class="fa-solid fa-spinner fa-spin"></i> Connecting...</span>');

            $.ajax({
                url: './includes/ajaxFile/ajaxDbImportRemote.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'test_connection', base_url: baseUrl, export_key: exportKey }
            }).done(function(res) {
                if (res.status === 'success') {
                    const db = escHtml(res.database);
                    $('#connStatus').html('<span class="sr-pill tone-green"><span class="sr-dot"></span>Connected &middot; ' + db + '</span>');
                    $('#sumDb').html('Live database <b class="sr-mono">' + db + '</b>');
                    renderTableList(res.tables || []);
                    setWizardStep(2);
                } else {
                    $('#connStatus').html('<span class="sr-pill tone-red"><span class="sr-dot"></span>' + escHtml(res.message || 'Connection failed') + '</span>');
                    $('#pickerSection').hide();
                    setWizardStep(1);
                }
            }).fail(function() {
                $('#connStatus').html('<span class="sr-pill tone-red"><span class="sr-dot"></span>Request failed</span>');
                $('#pickerSection').hide();
                setWizardStep(1);
            }).always(function() {
                if (onDone) onDone();
            });
        }

        $('#testConnBtn').on('click', function() {
            const baseUrl = $baseUrl.val().trim();
            const exportKey = $exportKey.val().trim();
            if (!baseUrl || !exportKey) {
                Swal.fire({ title: 'Missing info', text: 'Enter both the live server URL and the export key.', icon: 'warning', allowOutsideClick: false });
                return;
            }

            try {
                if ($('#rememberUrl').is(':checked')) {
                    localStorage.setItem('db_import_remote_base_url', baseUrl);
                } else {
                    localStorage.removeItem('db_import_remote_base_url');
                }
            } catch (e) { /* storage blocked */ }

            const $btn = $(this).prop('disabled', true);
            const original = $btn.html();
            $btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Connecting...');
            refreshConnectionAndTables(baseUrl, exportKey, () => $btn.prop('disabled', false).html(original));
        });

        // --- Two-step progress UI: Step 1 (only when Truncate is checked) truncates the
        // selected tables in one batched call and ticks each one off from that single
        // response. Step 2 imports the selected tables ONE AT A TIME (a separate run_import
        // call per table) so each table's row/spinner/checkmark updates as it completes -
        // this also means one slow/large table timing out only affects that table, instead
        // of the whole combined multi-table export being thrown away (the old single
        // combined-download behavior).

        // Row status is tracked in a plain JS object (name -> {kind, text, rows}) and
        // the WHOLE table is re-rendered into the Swal on every change via Swal.update({html}).
        // Deliberately not doing targeted DOM patches (find-by-id into the popup's content) -
        // that approach turned out unreliable against SweetAlert2's own content
        // rendering/timing, silently no-op'ing every row update while the title (a separate,
        // simpler Swal.update call) kept working fine. Re-rendering the full small table on
        // each change trades a little redundant work for actually being guaranteed correct.
        // Each state is { kind, text } - kind: pending | running | done | skipped | warn | error
        function makeInitialStates(tables) {
            const states = {};
            tables.forEach(t => { states[t] = { kind: 'pending', text: 'waiting' }; });
            return states;
        }

        const ROW_ICONS = {
            pending: '<i class="fa fa-circle-o"></i>',
            running: '<i class="fa fa-spinner fa-spin"></i>',
            done: '<i class="fa fa-check"></i>',
            skipped: '<i class="fa fa-minus"></i>',
            warn: '<i class="fa fa-exclamation"></i>',
            error: '<i class="fa fa-times"></i>'
        };

        function escHtml(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        function formatDuration(ms) {
            let s = Math.max(0, Math.round(ms / 1000));
            const h = Math.floor(s / 3600); s -= h * 3600;
            const m = Math.floor(s / 60); s -= m * 60;
            return h ? `${h}h ${m}m` : (m ? `${m}m ${s}s` : `${s}s`);
        }

        // Ticks the "Elapsed" value every second between page responses (Swal re-renders
        // replace the element, so it's looked up fresh each tick)
        let elapsedTicker = null;
        function startElapsedTicker() {
            stopElapsedTicker();
            elapsedTicker = setInterval(() => {
                const el = document.getElementById('dbiElapsed');
                if (el) el.textContent = formatDuration(Date.now() - Number(el.dataset.start));
            }, 1000);
        }
        function stopElapsedTicker() {
            if (elapsedTicker) clearInterval(elapsedTicker);
            elapsedTicker = null;
        }

        // ui = { truncate, phase: 'truncate'|'import', failed, sub, alert, alertWarn, timing: {start, expected} }
        function renderProgressHtml(ui, tables, states) {
            const list = tables.map(t => states[t] || { kind: 'pending', text: 'waiting' });
            const finished = list.filter(s => s.kind !== 'pending' && s.kind !== 'running').length;
            const ok = list.filter(s => s.kind === 'done').length;
            const warn = list.filter(s => s.kind === 'warn').length;
            const err = list.filter(s => s.kind === 'error').length;
            const rows = list.reduce((sum, s) => sum + (s.rows || 0), 0);
            // Row-based progress when the live row totals are known (one 4M-row table would
            // otherwise sit at 0% until it finishes), table-based otherwise
            const timing = ui.timing || null;
            const expected = timing && timing.expected > 0 ? timing.expected : 0;
            const pct = expected
                ? Math.min(100, Math.floor(rows / expected * 100))
                : (tables.length ? Math.round(finished / tables.length * 100) : 0);

            let html = ui.sub ? `<div class="dbi-pg-sub">${escHtml(ui.sub)}</div>` : '';
            if (ui.truncate) {
                const s1 = ui.phase === 'truncate' ? (ui.failed ? 'is-failed' : 'is-active') : 'is-done';
                const s2 = ui.phase === 'import' ? 'is-active' : '';
                html += `<div class="dbi-steps">
                    <div class="dbi-step ${s1}"><span class="dbi-step-num">${s1 === 'is-done' ? '<i class="fa fa-check"></i>' : '1'}</span> Truncate local tables</div>
                    <div class="dbi-step ${s2}"><span class="dbi-step-num">2</span> Import from live</div>
                </div>`;
            }
            if (ui.alert) {
                html += `<div class="dbi-alert${ui.alertWarn ? ' is-warn' : ''}">${ui.alert}</div>`;
            }
            html += `<div class="dbi-prog-head"><span>${finished} of ${tables.length} tables${expected ? ' · ' + rows.toLocaleString() + ' / ' + expected.toLocaleString() + ' rows' : ''}</span><strong>${pct}%</strong></div>`;
            html += `<div class="dbi-prog${err || warn ? ' has-error' : ''}"><span style="width:${pct}%"></span></div>`;
            if (timing) {
                const elapsedMs = Date.now() - timing.start;
                const rate = elapsedMs > 0 ? rows / (elapsedMs / 1000) : 0;
                let remaining = 'calculating...';
                if (expected && rows >= expected) remaining = 'finishing...';
                else if (expected && rate > 0 && rows > 0) remaining = '~' + formatDuration((expected - rows) / rate * 1000);
                else if (!expected) remaining = '-';
                html += `<div class="dbi-time">
                    <span><i class="fa fa-clock-o"></i> Elapsed <strong id="dbiElapsed" data-start="${timing.start}">${formatDuration(elapsedMs)}</strong></span>
                    <span><i class="fa fa-hourglass-half"></i> Remaining <strong>${remaining}</strong></span>
                    <span><i class="fa fa-tachometer"></i> <strong>${rate > 0 ? Math.round(rate).toLocaleString() : '-'}</strong> rows/s</span>
                </div>`;
            }
            html += `<div class="dbi-stats">
                <div class="dbi-stat tone-ok"><span class="dbi-stat-label">Done</span><span class="dbi-stat-value">${ok}</span></div>
                <div class="dbi-stat tone-warn"><span class="dbi-stat-label">Warnings</span><span class="dbi-stat-value">${warn}</span></div>
                <div class="dbi-stat tone-err"><span class="dbi-stat-label">Failed</span><span class="dbi-stat-value">${err}</span></div>
                <div class="dbi-stat"><span class="dbi-stat-label">Rows</span><span class="dbi-stat-value">${rows.toLocaleString()}</span></div>
            </div>`;
            html += '<div class="dbi-list" id="dbiList">' + tables.map((t, i) => {
                const s = list[i];
                return `<div class="dbi-row is-${s.kind}"><span class="dbi-row-icon">${ROW_ICONS[s.kind] || ''}</span><span class="dbi-row-name" title="${escHtml(t)}">${escHtml(t)}</span><span class="dbi-row-count">${escHtml(s.text)}</span></div>`;
            }).join('') + '</div>';
            return html;
        }

        // Full re-render (see note above), then keep the running row in view - otherwise the
        // list snaps back to the top on every update and table 30/40 is never visible.
        function renderStep(title, ui, tables, states) {
            Swal.update({ title: title, html: renderProgressHtml(ui, tables, states) });
            const listEl = document.getElementById('dbiList');
            const active = listEl && (listEl.querySelector('.dbi-row.is-running') || listEl.querySelector('.dbi-row.is-pending'));
            if (listEl && active) {
                listEl.scrollTop = Math.max(0, active.offsetTop - listEl.clientHeight / 2 + active.offsetHeight / 2);
            }
        }

        function ajaxPost(data) {
            return $.ajax({
                url: './includes/ajaxFile/ajaxDbImportRemote.php',
                type: 'POST',
                dataType: 'json',
                data: data
            });
        }

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        // Step 1 - the truncate itself is one batched local call (fast, no per-table
        // network round trip), so there's nothing to show "live" while it's in flight -
        // instead, once the result comes back, each table's outcome is revealed one at a
        // time with a short pause between them, so it visibly ticks down row by row
        // instead of the whole table flipping to its end state in one frame.
        const ROW_REVEAL_DELAY_MS = 220;

        const TRUNCATE_UI = { truncate: true, phase: 'truncate', sub: 'Deleting existing local rows before import.' };

        async function runTruncateStep(baseUrl, exportKey, tables, states) {
            tables.forEach(t => { states[t] = { kind: 'running', text: 'truncating' }; });
            renderStep('Truncating Tables', TRUNCATE_UI, tables, states);
            await sleep(50); // let the spinners actually paint before the request fires

            let res;
            try {
                res = await ajaxPost({ action: 'truncate_tables', base_url: baseUrl, export_key: exportKey, tables: tables });
            } catch (e) {
                res = { status: 'error', message: 'Truncate request failed.' };
            }

            if (res.status !== 'success') {
                const failUi = Object.assign({}, TRUNCATE_UI, {
                    failed: true,
                    alert: '<i class="fa fa-times-circle"></i> ' + escHtml(res.message || 'Truncate failed') +
                        (res.errors ? '<br><small>' + res.errors.map(escHtml).join('<br>') + '</small>' : '')
                });
                for (const t of tables) {
                    states[t] = { kind: 'error', text: 'failed' };
                    renderStep('Truncate Failed', failUi, tables, states);
                    await sleep(ROW_REVEAL_DELAY_MS);
                }
                return { ok: false };
            }

            const truncated = res.truncated_tables || [];
            const skipped = res.skipped_tables || [];
            for (const t of tables) {
                if (truncated.includes(t)) states[t] = { kind: 'done', text: 'truncated' };
                else if (skipped.includes(t)) states[t] = { kind: 'skipped', text: 'skipped' };
                else states[t] = { kind: 'error', text: 'error' };
                renderStep('Truncating Tables', TRUNCATE_UI, tables, states);
                await sleep(ROW_REVEAL_DELAY_MS);
            }
            return { ok: true };
        }

        // Step 2 - one run_import call PER table, sequentially, updating that table's row
        // as soon as its own call finishes before moving to the next. Each table enforces a
        // minimum visible spinner time so a fast/small table doesn't just flash past -
        // the point of this step is to actually SEE it move table by table.
        const MIN_ROW_VISIBLE_MS = 350;
        const IMPORT_PAGE_SIZE = 20000; // rows per live request

        async function runImportStepPerTable(baseUrl, exportKey, tables, since, truncate, stepLabel, states) {
            // Expected total = live row counts shown in the table list. Not used for a
            // "changed since" delta pull - that only fetches a fraction of each table.
            let expected = 0;
            if (!since) {
                tables.forEach(t => {
                    const cb = document.querySelector('.table-check[value="' + CSS.escape(t) + '"]');
                    expected += cb ? (parseInt(cb.dataset.liveRows, 10) || 0) : 0;
                });
            }
            const ui = { truncate: truncate, phase: 'import', sub: 'Importing tables one at a time from the live server.', timing: { start: Date.now(), expected: expected } };
            startElapsedTicker();
            tables.forEach(t => { states[t] = { kind: 'pending', text: 'waiting' }; });
            renderStep(stepLabel, ui, tables, states);
            await sleep(50); // let the fresh table paint before the first request fires

            const tableCounts = {};
            const errors = [];

            for (let i = 0; i < tables.length; i++) {
                const t = tables[i];
                const startedAt = Date.now();
                states[t] = { kind: 'running', text: 'importing...' };
                renderStep(stepLabel, ui, tables, states);

                // Pull the table in pages of IMPORT_PAGE_SIZE rows (one request each) until the
                // live server says there's no more - a million-row table in one response blows
                // the live host's time limit and gets cut off. A live server without paging
                // support just sends the whole table in the first response (has_more absent).
                let res;
                let cursor = '';
                let tableTotal = null;
                let importedSoFar = 0;
                while (true) {
                    const payload = { action: 'run_import', base_url: baseUrl, export_key: exportKey, tables: [t], since: since, truncate: truncate ? 1 : 0, page_size: IMPORT_PAGE_SIZE, cursor: cursor };
                    if (tableTotal !== null) payload.table_total = tableTotal;
                    try {
                        res = await ajaxPost(payload);
                    } catch (e) {
                        res = { status: 'error', message: 'Request failed' + (importedSoFar ? ' after ' + importedSoFar.toLocaleString() + ' rows' : '') + '.' };
                    }

                    // The export key is single-use - the live server rotates it on every
                    // successful pull (every page too), so the NEXT request must use the value
                    // it just handed back. Also reflect it in the visible field so the user has
                    // the current key if this wizard gets interrupted partway through.
                    if (res.next_export_key) {
                        exportKey = res.next_export_key;
                        $exportKey.val(exportKey);
                    }

                    if (res.status !== 'success' && res.status !== 'warning') break;
                    if (tableTotal === null && res.table_counts && res.table_counts[t] !== undefined) {
                        tableTotal = res.table_counts[t];
                    }
                    if (!res.paged || !res.has_more) break;

                    importedSoFar += res.page_rows || 0;
                    cursor = String(res.next_cursor);
                    states[t] = {
                        kind: 'running',
                        text: importedSoFar.toLocaleString() + (tableTotal ? ' / ' + tableTotal.toLocaleString() : '') + ' rows',
                        rows: importedSoFar
                    };
                    renderStep(stepLabel, ui, tables, states);
                }

                const elapsed = Date.now() - startedAt;
                if (elapsed < MIN_ROW_VISIBLE_MS) {
                    await sleep(MIN_ROW_VISIBLE_MS - elapsed);
                }

                if (res.status === 'success' || res.status === 'warning') {
                    const count = tableTotal !== null ? tableTotal : ((res.table_counts && res.table_counts[t]) || 0);
                    tableCounts[t] = count;
                    states[t] = { kind: res.status === 'warning' ? 'warn' : 'done', text: count.toLocaleString() + ' rows', rows: count };
                    if (res.status === 'warning') errors.push(t + ': ' + res.message);
                } else {
                    states[t] = { kind: 'error', text: 'failed' };
                    errors.push(t + ': ' + (res.message || 'Import failed'));
                }
                renderStep(stepLabel, ui, tables, states);
            }

            stopElapsedTicker();
            return { tableCounts, errors };
        }

        // All the actual async work runs from didOpen, not right after Swal.fire() - fire()
        // returns before the popup has necessarily finished mounting, so an update issued
        // too early can land before there's anything open to update and get silently
        // dropped. Everything from here on only starts once SweetAlert2 confirms the modal
        // is actually on screen, so every status change is guaranteed to render.
        async function runImportWizard(baseUrl, exportKey, tables, since, truncate) {
            const states = makeInitialStates(tables);
            const wizardStart = Date.now();

            if (truncate) {
                const truncResult = await runTruncateStep(baseUrl, exportKey, tables, states);
                if (!truncResult.ok) {
                    Swal.update({ showConfirmButton: true, confirmButtonText: 'Close' });
                    return;
                }
                await sleep(400); // let the finished truncate checklist stay on screen for a beat
            }

            const { tableCounts, errors } = await runImportStepPerTable(baseUrl, exportKey, tables, since, truncate, 'Importing Tables', states);

            const totalRecords = Object.values(tableCounts).reduce((a, b) => a + b, 0);
            const importedTables = Object.keys(tableCounts).length;
            const hasErrors = errors.length > 0;

            // Final screen reuses the same progress layout (list + stats) with a result header
            const doneUi = {
                truncate: false,
                phase: 'import',
                sub: '',
                alert: hasErrors ? errors.map(escHtml).join('<br>') : '',
                alertWarn: true
            };
            const headHtml = `<div class="dbi-done-head">
                    <span class="dbi-done-icon${hasErrors ? ' is-warn' : ''}"><i class="fa ${hasErrors ? 'fa-exclamation-triangle' : 'fa-check'}"></i></span>
                    <div class="dbi-pg-sub" style="margin:0;">Imported <strong>${totalRecords.toLocaleString()}</strong> record(s) across <strong>${importedTables}</strong> of ${tables.length} table(s) in <strong>${formatDuration(Date.now() - wizardStart)}</strong>.</div>
                </div>`;

            Swal.fire({
                title: hasErrors ? 'Imported With Errors' : 'Import Complete',
                customClass: { popup: 'dbi-popup' },
                width: '640px',
                html: headHtml + renderProgressHtml(doneUi, tables, states),
                confirmButtonText: 'Close'
            }).then(() => {
                // Refresh the Local/Live/Diff table list so it reflects what was just
                // imported, instead of leaving the pre-import counts on screen. Read the
                // key fresh from the field rather than this function's own exportKey
                // parameter - it's been rotated (and the field updated) per table above.
                refreshConnectionAndTables($baseUrl.val().trim(), $exportKey.val().trim());
            });
        }

        function doImport(baseUrl, exportKey, tables, since, truncate) {
            const initialStates = makeInitialStates(tables);
            setWizardStep(3);
            Swal.fire({
                title: truncate ? 'Truncating Tables' : 'Importing Tables',
                html: renderProgressHtml(truncate ? TRUNCATE_UI : { truncate: false, phase: 'import', sub: 'Importing tables one at a time from the live server.' }, tables, initialStates),
                customClass: { popup: 'dbi-popup' },
                width: '640px',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    runImportWizard(baseUrl, exportKey, tables, since, truncate);
                }
            });
        }

        // Builds one column definition fragment (used inside ADD COLUMN / for display) from
        // the {name, type, nullable, default, extra} shape returned by check_schema.
        function buildColumnDefSql(col) {
            let sql = '`' + col.name + '` ' + col.type;
            sql += col.nullable ? ' NULL' : ' NOT NULL';
            if (col.default !== null && col.default !== undefined) {
                sql += /^CURRENT_TIMESTAMP/i.test(col.default)
                    ? ' DEFAULT ' + col.default
                    : " DEFAULT '" + String(col.default).replace(/'/g, "''") + "'";
            }
            if (col.extra) {
                sql += ' ' + col.extra;
            }
            sql += col.after ? ' AFTER `' + col.after + '`' : ' FIRST';
            return sql;
        }

        function generateAlterSql(report) {
            const localStatements = [];
            const liveStatements = [];

            Object.keys(report).forEach(function(table) {
                const r = report[table];
                const localClauses = [];
                const liveClauses = [];

                (r.missing_in_local || []).forEach(function(col) {
                    const action = $('input[name="oiAction_' + table + '_' + col.name + '"]:checked').val();
                    if (action === 'add_local') {
                        localClauses.push('ADD COLUMN ' + buildColumnDefSql(col));
                    } else if (action === 'remove_live') {
                        liveClauses.push('DROP COLUMN `' + col.name + '`');
                    }
                });
                (r.missing_in_live || []).forEach(function(col) {
                    const action = $('input[name="oiAction_' + table + '_' + col.name + '"]:checked').val();
                    if (action === 'drop_local') {
                        localClauses.push('DROP COLUMN `' + col.name + '`');
                    } else if (action === 'add_live') {
                        liveClauses.push('ADD COLUMN ' + buildColumnDefSql(col));
                    }
                });

                if (localClauses.length) {
                    localStatements.push('ALTER TABLE `' + table + '`\n  ' + localClauses.join(',\n  ') + ';');
                }
                if (liveClauses.length) {
                    liveStatements.push('ALTER TABLE `' + table + '`\n  ' + liveClauses.join(',\n  ') + ';');
                }
            });

            let out = '';
            if (localStatements.length) {
                out += '-- Run on LOCAL database\n' + localStatements.join('\n\n');
            }
            if (liveStatements.length) {
                out += (out ? '\n\n' : '') + '-- Run on LIVE database\n' + liveStatements.join('\n\n');
            }
            return out;
        }

        // Segmented radio choice per column - radio name/values stay as generateAlterSql() reads them
        function schemaChoiceHtml(name, options) {
            return '<div class="dbs-seg">' + options.map(function(o, i) {
                const id = name + '_' + o.value;
                return '<input type="radio" name="' + escHtml(name) + '" id="' + escHtml(id) + '" value="' + o.value + '"' + (i === 0 ? ' checked' : '') + '>' +
                    '<label for="' + escHtml(id) + '" class="' + (o.tone || '') + '">' + o.label + '</label>';
            }).join('') + '</div>';
        }

        function schemaColRow(col, choiceHtml) {
            return '<div class="dbs-col">' +
                '<div class="dbs-col-info"><code>' + escHtml(col.name) + '</code><span class="dbs-col-type">' + escHtml(col.type) + (col.nullable === false ? ' NOT NULL' : '') + '</span></div>' +
                choiceHtml + '</div>';
        }

        function showSchemaDiffModal(res) {
            const report = res.report || {};
            const names = Object.keys(report);
            let diffTables = 0, colIssues = 0, missingTables = 0;

            const cards = names.map(function(table) {
                const r = report[table];
                const missLocal = r.missing_in_local || [];
                const missLive = r.missing_in_live || [];
                const types = r.type_mismatches || [];
                const issues = missLocal.length + missLive.length + types.length;
                let body = '';

                if (!r.exists_locally) {
                    missingTables++;
                    body += '<div class="dbs-note is-err"><i class="fa fa-times-circle"></i> Table does not exist locally.</div>';
                }
                if (missLocal.length) {
                    body += '<div class="dbs-sec is-err"><i class="fa fa-arrow-down"></i> Missing locally <span>live has these columns</span></div>';
                    missLocal.forEach(function(col) {
                        body += schemaColRow(col, schemaChoiceHtml('oiAction_' + table + '_' + col.name, [
                            { value: 'add_local', label: 'Add to Local', tone: 'is-ok' },
                            { value: 'remove_live', label: 'Remove from Live', tone: 'is-err' },
                            { value: 'skip', label: 'Skip' }
                        ]));
                    });
                }
                if (missLive.length) {
                    body += '<div class="dbs-sec is-warn"><i class="fa fa-arrow-up"></i> Extra locally <span>live does not have these columns</span></div>';
                    missLive.forEach(function(col) {
                        body += schemaColRow(col, schemaChoiceHtml('oiAction_' + table + '_' + col.name, [
                            { value: 'drop_local', label: 'Drop from Local', tone: 'is-err' },
                            { value: 'add_live', label: 'Add to Live', tone: 'is-ok' },
                            { value: 'skip', label: 'Skip' }
                        ]));
                    });
                }
                if (types.length) {
                    body += '<div class="dbs-sec is-warn"><i class="fa fa-exchange"></i> Type differs <span>review manually, not auto-generated</span></div>';
                    types.forEach(function(m) {
                        body += '<div class="dbs-col"><div class="dbs-col-info"><code>' + escHtml(m.column) + '</code></div>' +
                            '<div class="dbs-types"><span>local</span><code>' + escHtml(m.local_type) + '</code><i class="fa fa-long-arrow-right"></i><span>live</span><code>' + escHtml(m.live_type) + '</code></div></div>';
                    });
                }

                const bad = issues > 0 || !r.exists_locally;
                if (bad) { diffTables++; colIssues += issues; }
                const pill = !r.exists_locally
                    ? '<span class="dbs-pill is-err">Missing locally</span>'
                    : (issues ? '<span class="dbs-pill is-warn">' + issues + ' difference' + (issues > 1 ? 's' : '') + '</span>' : '<span class="dbs-pill is-ok"><i class="fa fa-check"></i> Matches</span>');

                return '<div class="dbs-card' + (bad ? ' has-diff' : '') + '">' +
                    '<div class="dbs-card-head"><span class="dbs-card-icon"><i class="fa fa-table"></i></span><span class="dbs-card-name">' + escHtml(table) + '</span>' + pill + '</div>' +
                    (body ? '<div class="dbs-card-body">' + body + '</div>' : '') +
                    '</div>';
            });

            // Tables with differences first
            const ordered = names.map(function(n, i) { return { bad: /has-diff/.test(cards[i]), html: cards[i] }; })
                .sort(function(a, b) { return b.bad - a.bad; }).map(function(c) { return c.html; }).join('');

            const ok = !res.any_mismatch;
            const head = '<div class="dbi-done-head">' +
                '<span class="dbi-done-icon' + (ok ? '' : ' is-warn') + '"><i class="fa ' + (ok ? 'fa-check' : 'fa-exclamation-triangle') + '"></i></span>' +
                '<div class="dbi-pg-sub" style="margin:0;">' + (ok
                    ? 'All <strong>' + names.length + '</strong> selected table(s) have the same columns locally and on live.'
                    : '<strong>' + diffTables + '</strong> of ' + names.length + ' table(s) differ. Pick an action per column, then generate the ALTER SQL.') +
                '</div></div>';

            const stats = '<div class="dbi-stats">' +
                '<div class="dbi-stat"><span class="dbi-stat-label">Checked</span><span class="dbi-stat-value">' + names.length + '</span></div>' +
                '<div class="dbi-stat tone-ok"><span class="dbi-stat-label">Matching</span><span class="dbi-stat-value">' + (names.length - diffTables) + '</span></div>' +
                '<div class="dbi-stat tone-warn"><span class="dbi-stat-label">Differ</span><span class="dbi-stat-value">' + diffTables + '</span></div>' +
                '<div class="dbi-stat tone-err"><span class="dbi-stat-label">Column issues</span><span class="dbi-stat-value">' + colIssues + '</span></div>' +
                '</div>';

            const sqlBlock = ok ? '' :
                '<div class="dbs-sql">' +
                '<div class="dbs-sql-head"><span><i class="fa fa-code"></i> ALTER SQL</span>' +
                '<span class="dbs-sql-btns"><button type="button" class="sr-btn sr-btn-sm sr-btn-primary" id="genAlterBtn"><i class="fa fa-magic"></i> Generate</button>' +
                '<button type="button" class="sr-btn sr-btn-sm" id="copyAlterBtn" style="display:none;"><i class="fa fa-copy"></i> Copy</button></span></div>' +
                '<textarea id="alterSqlOut" class="dbs-sql-out" rows="8" readonly style="display:none;"></textarea>' +
                '<div class="dbs-sql-hint">Output is split into LOCAL and LIVE sections based on your selections - run each on the matching server.</div>' +
                '</div>';

            Swal.fire({
                title: ok ? 'Schema matches' : 'Schema differences found',
                customClass: { popup: 'dbi-popup' },
                width: '780px',
                html: head + stats + '<div class="dbs-list">' + ordered + '</div>' + sqlBlock,
                showConfirmButton: true,
                confirmButtonText: 'Close'
            });

            $(document).off('click', '#genAlterBtn').on('click', '#genAlterBtn', function() {
                const sql = generateAlterSql(report);
                const $out = $('#alterSqlOut');
                $out.val(sql || '-- No columns selected.').show();
                $('#copyAlterBtn').show();
            });

            $(document).off('click', '#copyAlterBtn').on('click', '#copyAlterBtn', function() {
                const $out = $('#alterSqlOut');
                $out.select();
                navigator.clipboard.writeText($out.val()).then(function() {
                    $('#copyAlterBtn').html('<i class="fa fa-check"></i> Copied!');
                    setTimeout(function() { $('#copyAlterBtn').html('<i class="fa fa-copy"></i> Copy'); }, 1500);
                }).catch(function() {
                    document.execCommand('copy');
                });
            });
        }

        $('#checkSchemaBtn').on('click', function() {
            const baseUrl = $baseUrl.val().trim();
            const exportKey = $exportKey.val().trim();
            const tables = $('.table-check:checked').map(function() { return this.value; }).get();

            if (!tables.length) {
                Swal.fire('Nothing selected', 'Pick at least one table.', 'warning');
                return;
            }

            const $btn = $(this).prop('disabled', true);
            Swal.fire({
                title: 'Checking schema...',
                html: '<div class="dbi-pg-sub" style="margin:0;">Comparing columns for ' + tables.length + ' table(s) against the live server.</div>',
                customClass: { popup: 'dbi-popup' },
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: './includes/ajaxFile/ajaxDbImportRemote.php',
                type: 'POST',
                dataType: 'json',
                data: { action: 'check_schema', base_url: baseUrl, export_key: exportKey, tables: tables }
            }).done(function(res) {
                if (res.status !== 'success') {
                    Swal.fire('Schema check failed', res.message || 'Unknown error.', 'error');
                    return;
                }

                showSchemaDiffModal(res);
            }).fail(function() {
                Swal.fire('Request failed', 'Could not reach the local server.', 'error');
            }).always(function() {
                $btn.prop('disabled', false);
            });
        });

        $('#importBtn').on('click', function() {
            const baseUrl = $baseUrl.val().trim();
            const exportKey = $exportKey.val().trim();
            const tables = $('.table-check:checked').map(function() { return this.value; }).get();
            const since = $truncateFirst.is(':checked') ? '' : $sinceInput.val();
            const truncate = $truncateFirst.is(':checked');

            if (!tables.length) {
                Swal.fire('Nothing selected', 'Pick at least one table.', 'warning');
                return;
            }

            if (truncate) {
                Swal.fire({
                    title: 'Truncate before import?',
                    icon: 'warning',
                    html: 'This deletes ALL local rows in <b>' + tables.length + ' table(s)</b> first, then loads live data. ' +
                        'Any row that exists only locally will be permanently lost. This cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Truncate & Import',
                    confirmButtonColor: '#dc3545',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        doImport(baseUrl, exportKey, tables, since, true);
                    }
                });
                return;
            }

            doImport(baseUrl, exportKey, tables, since, false);
        });
    </script>
</body>
</html>
