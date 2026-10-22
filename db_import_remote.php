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

        .dbi-act-line { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 2px 12px; flex: 0 0 auto; line-height: 1.5; min-height: 19px; }
        .dbi-act-line.is-stalled { color: #b45309; font-weight: 600; }
        .dbi-act-line.is-idle, .dbi-act-line.is-queue { color: #64748b; }
        .dbi-q { display: inline-flex; gap: 3px; vertical-align: middle; margin: 0 4px; }
        .dbi-q-slot { width: 18px; height: 8px; border-radius: 3px; border: 1px solid #a5b4fc; background: transparent; }
        .dbi-q-slot.is-full { background: #6366f1; border-color: #6366f1; }
        html.app-dark .dbi-act-line.is-stalled { color: #fcd34d; }
        .dbi-activity { display: flex; flex-direction: column; flex: 0 0 auto; gap: 4px; margin: -4px 0 12px; padding: 8px 12px; border-radius: 10px; font-size: 12.5px; color: #3730a3; background: #eef2ff; border: 1px solid #c7d2fe; }
        .dbi-activity i { margin-right: 4px; }
        .dbi-activity-age { font-size: 11.5px; color: #64748b; font-variant-numeric: tabular-nums; }
        .dbi-activity.is-stalled { color: #b45309; background: #fffbeb; border-color: #fcd34d; }
        .dbi-activity.is-stalled .dbi-activity-age { color: #b45309; font-weight: 700; }
        html.app-dark .dbi-activity { color: #c7d2fe; background: #252b4a; border-color: #4547a0; }
        html.app-dark .dbi-activity.is-stalled { color: #fcd34d; background: #3a2e12; border-color: #6b5417; }
        .dbi-resume { align-items: center; }
        .dbi-resume > div:nth-child(2) { flex: 1 1 auto; }
        .dbi-resume-btns { display: flex; gap: 6px; flex: 0 0 auto; }
        .swal2-popup.dbi-popup .swal2-actions { margin-top: 14px; }
        .swal2-popup.dbi-popup .swal2-deny { background: #fff !important; color: #b45309 !important; border: 1px solid #fcd34d !important; box-shadow: none !important; }
        html.app-dark .swal2-popup.dbi-popup .swal2-deny { background: #3a2e12 !important; color: #fcd34d !important; border-color: #6b5417 !important; }

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
        .dbi-flow-node > div { min-width: 0; line-height: 1.4; }
        .dbi-conn-btns { display: flex; flex-wrap: wrap; gap: 8px; }
        .dbi-conn-btns .sr-btn { height: 40px; }
        .dbi-head-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .dbi-updated { font-size: 11.5px; color: var(--sr-muted); }
        .dbi-refresh.is-busy i { animation: fa-spin 1s linear infinite; }
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
        .dbs-list { max-height: 46vh; }
        .dbs-card-head .dbs-pick-table, .dbs-col .dbs-pick { width: 16px; height: 16px; flex: 0 0 auto; cursor: pointer; accent-color: #6366f1; margin: 0; }
        .dbs-col { border: 1px solid transparent; transition: background .15s, border-color .15s; }
        .dbs-col-info { margin: 0; cursor: pointer; flex: 1 1 auto; flex-wrap: wrap; }
        .dbs-col .dbs-seg { opacity: .45; transition: opacity .15s; }
        .dbs-col.is-picked { background: #eef2ff; border-color: #c7d2fe; }
        .dbs-col.is-picked .dbs-seg { opacity: 1; }
        .dbs-idx-note { flex-basis: 100%; font-size: 11.5px; color: #b45309; }
        .dbs-types span { font-size: 11px; text-transform: uppercase; letter-spacing: .03em; }
        /* sr-btn colours inside the Swal popup (outside .sr-page, so the tokens are not inherited) */
        .swal2-popup.dbi-popup { --sr-accent: #6366f1; --sr-accent-strong: #4f46e5; --sr-surface: #fff; --sr-border-strong: #d4d9e3; --sr-text-2: #334155; --sr-surface-2: #f8fafc; }
        html.app-dark .swal2-popup.dbi-popup { --sr-accent: #6366f1; --sr-accent-strong: #818cf8; --sr-surface: #1d2436; --sr-border-strong: #3a4560; --sr-text-2: #d8dee9; --sr-surface-2: #252e44; }
        .dbs-quick .dbs-refresh { margin-inline-start: auto; }
        .dbs-quick { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 10px; font-size: 12px; font-weight: 600; color: #64748b; }
        .dbs-foot { position: sticky; bottom: 0; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 12px; background: #f8fafc; }
        .dbs-foot-info { display: flex; gap: 8px; }
        .dbs-foot-btns { display: flex; flex-wrap: wrap; gap: 6px; }
        .dbs-side { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; }
        .dbs-side b { font-variant-numeric: tabular-nums; }
        .dbs-side.is-local { background: #f0fdf4; color: #15803d; }
        .dbs-side.is-live { background: #f0f9ff; color: #0369a1; }
        #dbsOut { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
        #dbsOut:empty { display: none; }
        .dbs-sql-head .dbs-side { font-size: 12px; }
        .dbs-wait { font-size: 13px; color: #64748b; padding: 8px 2px; }
        .dbs-note code { font-size: 12px; background: transparent; color: inherit; font-weight: 700; }
        .dbs-confirm { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; border-radius: 12px; font-size: 12.5px; border: 1px solid #86efac; background: #f0fdf4; color: #166534; }
        .dbs-confirm.is-live { border-color: #fca5a5; background: #fef2f2; color: #991b1b; }
        .dbs-confirm > div:first-child { flex: 1 1 300px; }
        .dbs-confirm-btns { display: flex; gap: 6px; justify-content: flex-end; }
        .dbs-result { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px 12px; background: #fff; display: flex; flex-direction: column; gap: 6px; }
        .dbs-result-head { font-size: 13px; font-weight: 700; }
        .dbs-result.is-ok .dbs-result-head { color: #15803d; }
        .dbs-result.is-warn .dbs-result-head { color: #b45309; }
        .dbs-result.is-err .dbs-result-head { color: #b91c1c; }
        .dbs-result-row { display: flex; gap: 8px; align-items: flex-start; font-size: 12px; }
        .dbs-result-row > i { margin-top: 3px; }
        .dbs-result-row.is-ok > i { color: #16a34a; }
        .dbs-result-row.is-err > i { color: #dc2626; }
        .dbs-result-row code { display: block; font-size: 11.5px; color: #334155; background: transparent; white-space: pre-wrap; word-break: break-all; padding: 0; }
        .dbs-result-row small { display: block; color: #b91c1c; }
        html.app-dark .dbs-col.is-picked { background: #252b4a; border-color: #4547a0; }
        html.app-dark .dbs-foot, html.app-dark .dbs-result { background: #161c2a; border-color: #2a3348; }
        html.app-dark .dbs-side.is-local { background: #12301f; color: #86efac; }
        html.app-dark .dbs-side.is-live { background: #102c3d; color: #7dd3fc; }
        html.app-dark .dbs-confirm { background: #12301f; border-color: #1f6a3c; color: #86efac; }
        html.app-dark .dbs-confirm.is-live { background: #3a1717; border-color: #7a2a2a; color: #fca5a5; }
        html.app-dark .dbs-result-row code { color: #d8dee9; }
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
        /* Truncate confirm: tables to be emptied */
        .dbt-wrap { margin-top: 14px; max-height: 260px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 10px; }
        .dbt-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: start; }
        .dbt-table th { position: sticky; top: 0; background: #f8fafc; color: #64748b; font-weight: 600; font-size: 12px; padding: 8px 12px; border-bottom: 1px solid #e5e7eb; text-align: start; }
        .dbt-table td { padding: 7px 12px; border-bottom: 1px solid #f1f5f9; text-align: start; }
        .dbt-table tr:last-child td { border-bottom: 0; }
        .dbt-table .dbt-num { text-align: end; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .dbt-del { color: #dc2626; font-weight: 600; }
        html.app-dark .dbt-wrap { border-color: #252e44; }
        html.app-dark .dbt-table th { background: #1a2133; color: #94a3b8; border-color: #252e44; }
        html.app-dark .dbt-table td { border-color: #252e44; }
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

                    <div class="sr-notice tone-amber dbi-resume" id="resumeBanner" style="display:none;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <div id="resumeText"></div>
                        <div class="dbi-resume-btns">
                            <button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="discardResumeBtn"><i class="fa-solid fa-xmark"></i> Discard</button>
                            <button type="button" class="sr-btn sr-btn-sm sr-btn-primary" id="resumeBtn"><i class="fa-solid fa-play"></i> Resume</button>
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
                                                <div class="dbi-conn-btns">
                                                    <button type="button" class="sr-btn dbi-refresh" id="refreshTablesBtn" style="display:none;" title="Re-compare row counts with live (does not use up the key)"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>
                                                    <button type="button" class="sr-btn sr-btn-primary" id="testConnBtn"><i class="fa-solid fa-plug"></i> Connect &amp; Compare</button>
                                                </div>
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
                                        <div class="dbi-head-tools">
                                            <span class="dbi-updated" id="tablesUpdated"></span>
                                            <div class="dbi-filter" role="tablist" aria-label="Table filter">
                                                <button type="button" class="active" data-filter="diff">Differences <span class="sr-count" id="cntDiff">0</span></button>
                                                <button type="button" data-filter="missing">Missing <span class="sr-count" id="cntMissing">0</span></button>
                                                <button type="button" data-filter="all">All <span class="sr-count" id="tableTotalCount">0</span></button>
                                            </div>
                                            <button type="button" class="sr-btn sr-btn-sm sr-btn-icon dbi-refresh" title="Refresh counts"><i class="fa-solid fa-arrows-rotate"></i></button>
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

        function renderTableList(tables, keepFilter) {
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
                        <td class="dbi-cb"><input type="checkbox" class="table-check" value="${name}" data-live-rows="${t.live_rows || 0}" data-local-rows="${missing ? -1 : (t.local_rows || 0)}" ${changed ? 'checked' : ''}></td>
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
            // Open on "Differences" when there are any, otherwise show everything.
            // A refresh keeps the tab the user is on.
            setFilterMode(keepFilter ? tableFilterMode : (diffCount ? 'diff' : 'all'));
            $('#tablesUpdated').text('Updated ' + new Date().toLocaleTimeString());
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
        function refreshConnectionAndTables(baseUrl, exportKey, onDone, keepFilter) {
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
                    renderTableList(res.tables || [], keepFilter);
                    setWizardStep(2);
                    $('#refreshTablesBtn').show();
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

        // Re-pull Local/Live/Diff counts after a sync (test_connection only reads - the
        // single-use key is not consumed)
        $(document).on('click', '.dbi-refresh', function() {
            const baseUrl = $baseUrl.val().trim();
            const exportKey = $exportKey.val().trim();
            if (!baseUrl || !exportKey) {
                Swal.fire({ title: 'Missing info', text: 'Enter both the live server URL and the export key.', icon: 'warning', allowOutsideClick: false });
                return;
            }
            const $btns = $('.dbi-refresh').prop('disabled', true).addClass('is-busy');
            refreshConnectionAndTables(baseUrl, exportKey, () => $btns.prop('disabled', false).removeClass('is-busy'), true);
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
                // Rate only counts rows moved in THIS run (a resumed run starts part-way)
                const movedRows = Math.max(0, rows - (timing.startRows || 0));
                const rate = elapsedMs > 0 ? movedRows / (elapsedMs / 1000) : 0;
                let remaining = 'calculating...';
                if (expected && rows >= expected) remaining = 'finishing...';
                else if (expected && rate > 0 && movedRows > 0) remaining = '~' + formatDuration((expected - rows) / rate * 1000);
                else if (!expected) remaining = '-';
                html += `<div class="dbi-time">
                    <span><i class="fa fa-clock-o"></i> Elapsed <strong id="dbiElapsed" data-start="${timing.start}">${formatDuration(elapsedMs)}</strong></span>
                    <span><i class="fa fa-hourglass-half"></i> Remaining <strong>${remaining}</strong></span>
                    <span><i class="fa fa-tachometer"></i> <strong>${rate > 0 ? Math.round(rate).toLocaleString() : '-'}</strong> rows/s</span>
                </div>`;
            }
            if (ui.activity) {
                html += `<div class="dbi-activity${ui.activityStalled ? ' is-stalled' : ''}">${ui.activity}</div>`;
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
        const IMPORT_PAGE_SIZE = 35000; // rows per live request
        const MAX_QUEUED = 3; // downloaded batches kept ready for the writer
        const PAGE_TIMEOUT_MS = 16 * 60 * 1000; // server caps the live fetch at 15 min
        const RETRY_DELAYS_S = [5, 15, 30, 60, 120, 300];

        let importRunning = false;
        let importStopRequested = false;

        window.addEventListener('beforeunload', function(e) {
            if (!importRunning) return;
            e.preventDefault();
            e.returnValue = '';
        });

        function getCheckpoint() {
            return $.ajax({ url: './includes/ajaxFile/ajaxDbImportRemote.php', type: 'POST', dataType: 'json', timeout: 20000, data: { action: 'get_checkpoint' } })
                .then(res => (res && res.checkpoint) || null, () => null);
        }

        function xhrMessage(xhr, status) {
            if (status === 'timeout') return 'Request timed out.';
            if (xhr && xhr.status === 0) return 'Local server not reachable.';
            if (xhr && xhr.status) return 'Local server returned HTTP ' + xhr.status + '.';
            return 'Request failed.';
        }

        // Network drop, timeout, Apache/MySQL restart, live hiccup -> wait and retry the
        // same page. Local SQL errors are not retried (they would fail the same way).
        function isRetryable(res) {
            if (res._transport) return true;
            if (res._keyRejected) return false;
            return /fetch failed|connection|timed out|incomplete|HTTP 5\d\d|HTTP 0|gone away|lost connection|deadlock|lock wait/i.test(res.message || '');
        }

        function isKeyError(res) {
            return !res._transport && /HTTP 403|export key/i.test(res.message || '');
        }

        // Posts one page; on a retryable failure waits (counting down in the row) and
        // re-reads the server checkpoint first - the live key may have been rotated by a
        // request whose response never arrived, and the cursor tells which page is next.
        function getLatestKey() {
            return $.ajax({ url: './includes/ajaxFile/ajaxDbImportRemote.php', type: 'POST', dataType: 'json', timeout: 20000, data: { action: 'get_checkpoint' } })
                .then(res => (res && res.export_key) || '', () => '');
        }

        function newToken() {
            return Date.now() + '-' + Math.random().toString(36).slice(2);
        }

        // One request with automatic retry (countdown shown via onWait).
        //  fetch: same page is downloaded again. The newest key the server saved is used
        //         when it differs (a lost response may have rotated it); keys live rejects
        //         are remembered so an old saved key can't shadow the one the user pasted.
        //  apply: the same spool file is sent again - upserts are idempotent and a file
        //         that is already gone was committed by the earlier attempt.
        async function postWithRetry(kind, payload, onWait, keys) {
            for (let attempt = 0; ; attempt++) {
                let res;
                try {
                    res = await $.ajax({ url: './includes/ajaxFile/ajaxDbImportRemote.php', type: 'POST', dataType: 'json', timeout: PAGE_TIMEOUT_MS, data: payload });
                } catch (xhr) {
                    res = { status: 'error', message: xhrMessage(xhr, xhr && xhr.statusText), _transport: true };
                }
                if (res.status === 'success' || res.status === 'warning') return res;

                if (kind === 'fetch' && isKeyError(res)) {
                    keys.rejected.add(payload.export_key);
                    const latest = await getLatestKey();
                    if (latest && !keys.all.includes(latest)) keys.all.unshift(latest);
                    const next = keys.all.find(k => k && !keys.rejected.has(k));
                    if (next) {
                        payload.export_key = next;
                        continue;
                    }
                    res._keyRejected = true;
                    return res;
                }
                if (!isRetryable(res) || attempt >= RETRY_DELAYS_S.length || importStopRequested) return res;

                for (let s = RETRY_DELAYS_S[attempt]; s > 0; s--) {
                    if (importStopRequested) return res;
                    onWait('retry in ' + s + 's (' + (attempt + 1) + '/' + RETRY_DELAYS_S.length + ')', res.message);
                    await sleep(1000);
                }
                onWait('retrying...', res.message);

                if (kind === 'fetch') {
                    const latest = await getLatestKey();
                    if (latest && !keys.rejected.has(latest)) {
                        if (!keys.all.includes(latest)) keys.all.unshift(latest);
                        payload.export_key = latest;
                    }
                }
            }
        }

        function fmtBytes(b) {
            if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
            if (b >= 1024) return Math.round(b / 1024) + ' KB';
            return b + ' B';
        }

        // Polls both server progress files every second: the downloader (bytes received)
        // and the writer (rows committed). Each line shows its own "last activity", so a
        // stall on either side is obvious.
        const STALL_SECONDS = 45;
        function startPipelinePoll(act, applied, totals, stepLabel, ui, tables, states, queue) {
            let active = true, busy = false;
            const ageOf = (p, token, since) => (p && p.token === token) ? (p.age || 0) : Math.round((Date.now() - since) / 1000);
            const ageHtml = (age) => '<span class="dbi-activity-age">' + (age >= STALL_SECONDS ? '<i class="fa fa-exclamation-triangle"></i> no activity for ' + formatDuration(age * 1000) : 'last activity ' + formatDuration(age * 1000) + ' ago') + '</span>';
            const timer = setInterval(function() {
                if (!active || busy) return;
                busy = true;
                $.ajax({ url: './includes/ajaxFile/ajaxDbImportRemote.php', type: 'POST', dataType: 'json', timeout: 8000, data: { action: 'get_progress' } })
                    .done(function(res) {
                        if (!active) return;
                        const p = (res && res.progress) || {};
                        let html = '', stalled = false;

                        if (act.dl) {
                            const d = act.dl, pd = p.dl && p.dl.token === d.token ? p.dl : null;
                            const age = ageOf(p.dl, d.token, d.since);
                            stalled = stalled || age >= STALL_SECONDS;
                            html += '<div class="dbi-act-line' + (age >= STALL_SECONDS ? ' is-stalled' : '') + '"><span><i class="fa fa-cloud-download"></i> <b>Download</b> ' + escHtml(d.t) + ' &middot; ' +
                                (pd && pd.bytes > 0 ? fmtBytes(pd.bytes) + ' received'
                                    : 'live is preparing the next ' + IMPORT_PAGE_SIZE.toLocaleString() + ' rows (' + formatDuration(Date.now() - d.since) + ')') + '</span>' + ageHtml(age) + '</div>';
                        } else if (act.dlWaiting) {
                            html += '<div class="dbi-act-line is-idle"><span><i class="fa fa-pause"></i> <b>Download</b> paused - queue is full, waiting for the writer</span></div>';
                        }
                        html += '<div class="dbi-act-line is-queue"><span><i class="fa fa-inbox"></i> <b>Queue</b> ' +
                            '<span class="dbi-q">' + Array.from({ length: MAX_QUEUED }, (_, k) => '<span class="dbi-q-slot' + (k < queue.length ? ' is-full' : '') + '"></span>').join('') + '</span> ' +
                            queue.length + ' of ' + MAX_QUEUED + ' downloaded batch(es) waiting to be written</span></div>';
                        if (act.ap) {
                            const a = act.ap, pa = p.ap && p.ap.token === a.token ? p.ap : null;
                            const age = ageOf(p.ap, a.token, a.since);
                            stalled = stalled || age >= STALL_SECONDS;
                            const done = pa ? (pa.applied || 0) : 0;
                            html += '<div class="dbi-act-line' + (age >= STALL_SECONDS ? ' is-stalled' : '') + '"><span><i class="fa fa-database"></i> <b>Write</b> ' + escHtml(a.t) + ' &middot; ' +
                                done.toLocaleString() + ' / ' + (a.pageRows || 0).toLocaleString() + ' rows of this batch</span>' + ageHtml(age) + '</div>';
                            const rows = (applied[a.t] || 0) + done;
                            const tot = totals[a.t];
                            states[a.t] = { kind: 'running', text: rows.toLocaleString() + (tot ? ' / ' + tot.toLocaleString() : '') + ' rows', rows: rows };
                        } else if (act.dl && !queue.length) {
                            html += '<div class="dbi-act-line is-idle"><span><i class="fa fa-hourglass-half"></i> <b>Write</b> idle - waiting for the download (live is the slower side)</span></div>';
                        }
                        ui.activity = html;
                        ui.activityStalled = stalled;
                        renderStep(stepLabel, ui, tables, states);
                    })
                    .always(() => { busy = false; });
            }, 1000);
            return { stop: () => { active = false; clearInterval(timer); } };
        }

        // Pipelined import: while batch N is written into the local database (apply_page),
        // batch N+1 is already downloading from live (fetch_page). Downloads run one after
        // another (each returns the next single-use key), writes run one after another in
        // order; the two overlap. resume = { index, cursor, imported, tableTotal }.
        async function runImportStepPerTable(baseUrl, exportKey, tables, since, truncate, stepLabel, states, resume) {
            let expected = 0;
            if (!since) {
                tables.forEach(t => {
                    const cb = document.querySelector('.table-check[value="' + CSS.escape(t) + '"]');
                    expected += cb ? (parseInt(cb.dataset.liveRows, 10) || 0) : 0;
                });
            }
            const startIndex = resume ? resume.index : 0;
            const ui = {
                truncate: truncate && !resume,
                phase: 'import',
                sub: resume ? 'Resuming from the last saved batch. Downloading and writing run in parallel.' : 'Downloading ahead (up to 2 batches queued) while the current batch is written locally.',
                timing: { start: Date.now(), expected: expected, startRows: resume ? (resume.imported || 0) : 0 }
            };
            startElapsedTicker();
            tables.forEach((t, i) => { states[t] = i < startIndex ? { kind: 'done', text: 'done earlier' } : { kind: 'pending', text: 'waiting' }; });

            const tableCounts = {}, errors = [], applied = {}, totals = {};
            const act = { dl: null, ap: null, dlWaiting: false };
            const keys = { all: [exportKey], rejected: new Set() };
            let key = exportKey;
            let stopped = false, stopReason = '';

            if (resume && startIndex < tables.length) {
                applied[tables[startIndex]] = resume.imported || 0;
                if (resume.tableTotal) totals[tables[startIndex]] = resume.tableTotal;
            }
            const rowsText = t => (applied[t] || 0).toLocaleString() + (totals[t] ? ' / ' + totals[t].toLocaleString() : '') + ' rows';
            const render = () => renderStep(stepLabel, ui, tables, states);
            const onWait = (t) => (waitText, why) => {
                states[t] = { kind: 'running', text: waitText, rows: applied[t] || 0 };
                ui.alert = '<i class="fa fa-refresh"></i> ' + escHtml(why || 'Connection problem') + ' - retrying automatically.';
                ui.alertWarn = true;
                render();
            };

            function startFetch(i, cursor) {
                const t = tables[i];
                const token = newToken();
                act.dl = { token: token, t: t, since: Date.now() };
                if (states[t].kind === 'pending') states[t] = { kind: 'running', text: applied[t] ? rowsText(t) : 'downloading...', rows: applied[t] || 0 };
                const payload = {
                    action: 'fetch_page', base_url: baseUrl, export_key: key, table: t, cursor: cursor || '',
                    page_size: IMPORT_PAGE_SIZE, since: since, truncate: truncate ? 1 : 0, page_token: token
                };
                if (totals[t] != null) payload.table_total = totals[t];
                return postWithRetry('fetch', payload, onWait(t), keys).then(function(res) {
                    if (act.dl && act.dl.token === token) act.dl = null;
                    if (res.next_export_key) {
                        key = res.next_export_key;
                        keys.all.unshift(key);
                        $exportKey.val(key);
                    }
                    return { i: i, t: t, res: res };
                });
            }

            // Producer / consumer with a bounded queue: the downloader keeps fetching until
            // MAX_QUEUED downloaded batches are waiting, the writer takes them in order; each
            // batch the writer takes frees a slot and the downloader refills it.
            const queue = [];
            const failedTables = new Set();
            let abort = false;
            let producerFinishedAll = false;
            let lastQueued = null; // { i, hasMore } of the last batch handed to the queue

            const poll = startPipelinePoll(act, applied, totals, stepLabel, ui, tables, states, queue);
            render();

            async function producer() {
                let i = startIndex;
                let cursor = resume ? (resume.cursor || '') : '';
                while (i < tables.length) {
                    if (abort || importStopRequested) return;
                    if (failedTables.has(i)) { i++; cursor = ''; continue; }
                    if (queue.length >= MAX_QUEUED) {
                        act.dlWaiting = true;
                        await sleep(150);
                        continue;
                    }
                    act.dlWaiting = false;

                    const f = await startFetch(i, cursor);
                    const t = f.t;
                    if (f.res.status !== 'success') {
                        if (f.res._keyRejected || isRetryable(f.res)) {
                            abort = true;
                            stopped = true;
                            stopReason = f.res._keyRejected
                                ? 'the live server rejected the export key. Open Database Export on live, copy the current key into the Export Key field here, then click Resume'
                                : (f.res.message || 'Connection lost');
                            return;
                        }
                        failedTables.add(i);
                        states[t] = { kind: 'error', text: 'download failed' };
                        errors.push(t + ': ' + (f.res.message || 'Download failed'));
                        render();
                        i++;
                        cursor = '';
                        continue;
                    }
                    ui.alert = '';
                    if (f.res.table_total != null) totals[t] = f.res.table_total;
                    if (failedTables.has(i)) { i++; cursor = ''; continue; } // writer failed this table meanwhile
                    queue.push(f);
                    lastQueued = { i: i, hasMore: !!f.res.has_more };
                    render();
                    if (f.res.has_more) {
                        cursor = String(f.res.next_cursor);
                    } else {
                        i++;
                        cursor = '';
                    }
                }
                producerFinishedAll = true;
            }

            let producerDone = false;
            async function writer() {
                while (true) {
                    if (!queue.length) {
                        if (producerDone || abort) return;
                        await sleep(100);
                        continue;
                    }
                    const f = queue.shift(); // frees a slot -> downloader continues
                    const t = f.t;
                    if (failedTables.has(f.i)) continue;

                    const apToken = newToken();
                    act.ap = { token: apToken, t: t, pageRows: f.res.page_rows || 0, since: Date.now() };
                    states[t] = { kind: 'running', text: rowsText(t), rows: applied[t] || 0 };
                    render();
                    const a = await postWithRetry('apply', {
                        action: 'apply_page', base_url: baseUrl, export_key: key, table: t, file_id: f.res.file_id,
                        has_more: f.res.has_more ? 1 : 0, next_cursor: f.res.next_cursor, page_rows: f.res.page_rows || 0,
                        imported: applied[t] || 0, table_total: totals[t] != null ? totals[t] : '', since: since,
                        truncate: truncate ? 1 : 0, queue: JSON.stringify(tables), page_token: apToken
                    }, onWait(t), keys);
                    act.ap = null;

                    if (a.status === 'success' || a.status === 'warning') {
                        ui.alert = '';
                        applied[t] = (applied[t] || 0) + (f.res.page_rows || 0);
                        if (!f.res.has_more) {
                            const count = totals[t] != null ? totals[t] : applied[t];
                            tableCounts[t] = count;
                            states[t] = { kind: a.status === 'warning' ? 'warn' : 'done', text: count.toLocaleString() + ' rows', rows: count };
                            if (a.status === 'warning') errors.push(t + ': ' + a.message);
                        } else {
                            states[t] = { kind: 'running', text: rowsText(t), rows: applied[t] };
                        }
                    } else if (isRetryable(a)) {
                        // Stop everything; Resume restarts from the last written batch
                        abort = true;
                        stopped = true;
                        stopReason = a.message || 'Connection lost';
                        states[t] = { kind: 'error', text: 'interrupted at ' + rowsText(t), rows: applied[t] || 0 };
                        render();
                        return;
                    } else {
                        // Local SQL error: fail this table, drop its queued batches, go on
                        failedTables.add(f.i);
                        states[t] = { kind: 'error', text: 'failed at ' + rowsText(t), rows: applied[t] || 0 };
                        errors.push(t + ': ' + (a.message || 'Write failed'));
                    }
                    render();
                }
            }

            const producerRun = producer().finally(() => { producerDone = true; act.dl = null; act.dlWaiting = false; });
            await Promise.all([producerRun, writer()]);

            poll.stop();
            act.dl = act.ap = null;
            ui.activity = '';

            if (stopped) {
                const t = lastQueued ? tables[lastQueued.i] : tables[startIndex];
                if (t && states[t] && states[t].kind === 'running') states[t] = { kind: 'warn', text: 'paused at ' + rowsText(t), rows: applied[t] || 0 };
            }
            const lastIndex = lastQueued ? lastQueued.i : startIndex;
            const lastHadMore = lastQueued ? lastQueued.hasMore : true;

            // Stop button: the queue drained without starting new downloads
            if (!stopped && importStopRequested && !producerFinishedAll) {
                stopped = true;
                const t = tables[lastIndex];
                if (lastHadMore) states[t] = { kind: 'warn', text: 'stopped at ' + rowsText(t), rows: applied[t] || 0 };
            }
            if (stopped) {
                tables.forEach(t => { if (states[t].kind === 'pending') states[t] = { kind: 'skipped', text: 'not started' }; });
            }
            stopElapsedTicker();
            return { tableCounts, errors, stopped, stopReason };
        }

        // All the actual async work runs from didOpen, not right after Swal.fire() - fire()
        // returns before the popup has necessarily finished mounting, so an update issued
        // too early can land before there's anything open to update and get silently
        // dropped. Everything from here on only starts once SweetAlert2 confirms the modal
        // is actually on screen, so every status change is guaranteed to render.
        async function runImportWizard(baseUrl, exportKey, tables, since, truncate, resume) {
            const states = makeInitialStates(tables);
            const wizardStart = Date.now();
            importRunning = true;
            importStopRequested = false;

            if (truncate && !resume) {
                const truncResult = await runTruncateStep(baseUrl, exportKey, tables, states);
                if (!truncResult.ok) {
                    importRunning = false;
                    Swal.update({ showConfirmButton: true, confirmButtonText: 'Close', showDenyButton: false });
                    return;
                }
                await sleep(400); // let the finished truncate checklist stay on screen for a beat
            }

            const { tableCounts, errors, stopped, stopReason } = await runImportStepPerTable(baseUrl, exportKey, tables, since, truncate, 'Importing Tables', states, resume);
            importRunning = false;

            const totalRecords = Object.values(tableCounts).reduce((a, b) => a + b, 0);
            const importedTables = Object.keys(tableCounts).length;
            const hasErrors = errors.length > 0;

            // Finished the whole batch -> the checkpoint is no longer needed
            if (!stopped) {
                ajaxPost({ action: 'clear_checkpoint' });
                $('#resumeBanner').hide();
            }

            // Final screen reuses the same progress layout (list + stats) with a result header
            const doneUi = {
                truncate: false,
                phase: 'import',
                sub: '',
                alert: hasErrors ? errors.map(escHtml).join('<br>') : '',
                alertWarn: true
            };
            const summary = stopped
                ? (stopReason ? 'Import interrupted: ' + escHtml(stopReason) + '. ' : 'Import stopped. ') + 'Progress is saved - use <strong>Resume</strong> on this page to continue from the last page.'
                : `Imported <strong>${totalRecords.toLocaleString()}</strong> record(s) across <strong>${importedTables}</strong> of ${tables.length} table(s) in <strong>${formatDuration(Date.now() - wizardStart)}</strong>.`;
            const headHtml = `<div class="dbi-done-head">
                    <span class="dbi-done-icon${hasErrors || stopped ? ' is-warn' : ''}"><i class="fa ${hasErrors || stopped ? 'fa-exclamation-triangle' : 'fa-check'}"></i></span>
                    <div class="dbi-pg-sub" style="margin:0;">${summary}</div>
                </div>`;

            Swal.fire({
                title: stopped ? 'Import Paused' : (hasErrors ? 'Imported With Errors' : 'Import Complete'),
                customClass: { popup: 'dbi-popup' },
                width: '640px',
                html: headHtml + renderProgressHtml(doneUi, tables, states),
                confirmButtonText: 'Close',
                allowOutsideClick: false
            }).then(() => {
                if (stopped) {
                    loadCheckpointBanner();
                    return;
                }
                // Refresh the Local/Live/Diff table list so it reflects what was just
                // imported, instead of leaving the pre-import counts on screen. Read the
                // key fresh from the field rather than this function's own exportKey
                // parameter - it's been rotated (and the field updated) per table above.
                refreshConnectionAndTables($baseUrl.val().trim(), $exportKey.val().trim());
            });
        }

        function doImport(baseUrl, exportKey, tables, since, truncate, resume) {
            const initialStates = makeInitialStates(tables);
            setWizardStep(3);
            $('#resumeBanner').hide();
            Swal.fire({
                title: resume ? 'Resuming Import' : (truncate ? 'Truncating Tables' : 'Importing Tables'),
                html: renderProgressHtml(truncate && !resume ? TRUNCATE_UI : { truncate: false, phase: 'import', sub: 'Importing tables one at a time from the live server.' }, tables, initialStates),
                customClass: { popup: 'dbi-popup' },
                width: '640px',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                showDenyButton: true,
                denyButtonText: '<i class="fa fa-pause"></i> Stop after this page',
                // Never closes the popup - the loop notices the flag after the page in flight
                preDeny: () => {
                    importStopRequested = true;
                    const btn = Swal.getDenyButton();
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Stopping after current page...';
                    }
                    return false;
                },
                didOpen: () => {
                    runImportWizard(baseUrl, exportKey, tables, since, truncate, resume);
                }
            });
        }

        // --- Resume an interrupted import (checkpoint saved server-side after every page)
        let pendingCheckpoint = null;

        function loadCheckpointBanner() {
            getCheckpoint().then(function(cp) {
                pendingCheckpoint = cp;
                if (!cp || !Array.isArray(cp.queue) || !cp.queue.length) {
                    $('#resumeBanner').hide();
                    return;
                }
                const idx = cp.queue.indexOf(cp.table);
                const doneAll = cp.table_done && idx === cp.queue.length - 1;
                if (idx < 0 || doneAll) {
                    $('#resumeBanner').hide();
                    return;
                }
                const pos = cp.table_done
                    ? '<b class="sr-mono">' + escHtml(cp.table) + '</b> finished; <b>' + (cp.queue.length - idx - 1) + '</b> table(s) left'
                    : '<b class="sr-mono">' + escHtml(cp.table) + '</b> at <b>' + Number(cp.imported || 0).toLocaleString() + '</b>' +
                        (cp.table_total ? ' / ' + Number(cp.table_total).toLocaleString() : '') + ' rows (table ' + (idx + 1) + ' of ' + cp.queue.length + ')';
                $('#resumeText').html('Unfinished import from <b>' + escHtml(cp.base_url || '') + '</b>: ' + pos + '. Last saved ' + escHtml(cp.updated_at || '') + '. If the live key was regenerated, paste the new one in <b>Export Key</b> before resuming.');
                $('#resumeBanner').css('display', 'flex');
            });
        }

        $('#resumeBtn').on('click', function() {
            const cp = pendingCheckpoint;
            if (!cp) return;
            let index = cp.queue.indexOf(cp.table);
            let resume = { index: index, cursor: cp.cursor || '', imported: cp.imported || 0, tableTotal: cp.table_total || null };
            if (cp.table_done) {
                resume = { index: index + 1, cursor: '', imported: 0, tableTotal: null };
            }
            // A key typed into the field wins over the saved one: after a restart or a key
            // regenerate on live, the saved key is dead and the user pastes the new one.
            const key = $exportKey.val().trim() || cp.export_key || '';
            if (!key) {
                Swal.fire({ title: 'Export key needed', text: 'Paste the current export key from Database Export on the live server, then click Resume.', icon: 'warning', allowOutsideClick: false });
                return;
            }
            $baseUrl.val(cp.base_url || '');
            updateFlowLive();
            $exportKey.val(key);
            doImport(cp.base_url, key, cp.queue, cp.since || '', !!cp.truncate, resume);
        });

        $('#discardResumeBtn').on('click', function() {
            Swal.fire({
                title: 'Discard the saved progress?',
                html: 'The tables already imported stay as they are. Partly imported tables keep the rows that were already copied.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Discard',
                confirmButtonColor: '#dc3545',
                allowOutsideClick: false
            }).then(r => {
                if (!r.isConfirmed) return;
                ajaxPost({ action: 'clear_checkpoint' }).always(() => {
                    pendingCheckpoint = null;
                    $('#resumeBanner').hide();
                });
            });
        });

        loadCheckpointBanner();

        // --- Schema check modal ---------------------------------------------------------
        // Every difference is one row with its own checkbox + direction choice. Ticked rows
        // are sent as {table, column, choice}; the server re-reads both schemas, builds the
        // SQL itself (includes/db_schema_sync.php) and either previews or applies it.
        const SCHEMA_CHOICES = {
            create: [{ value: 'create_local', label: 'Create on Local', side: 'local', tone: 'is-ok' }],
            missing_local: [
                { value: 'add_local', label: 'Add to Local', side: 'local', tone: 'is-ok' },
                { value: 'remove_live', label: 'Drop from Live', side: 'live', tone: 'is-err' }
            ],
            missing_live: [
                { value: 'add_live', label: 'Add to Live', side: 'live', tone: 'is-ok' },
                { value: 'drop_local', label: 'Drop from Local', side: 'local', tone: 'is-err' }
            ],
            type: [
                { value: 'modify_local', label: 'Live → Local', side: 'local' },
                { value: 'modify_live', label: 'Local → Live', side: 'live' }
            ],
            index_live: [{ value: 'add_index_live', label: 'Add to Live', side: 'live', tone: 'is-ok' }],
            index_local: [{ value: 'add_index_local', label: 'Add to Local', side: 'local', tone: 'is-ok' }]
        };
        const CHOICE_SIDE = {};
        Object.values(SCHEMA_CHOICES).forEach(list => list.forEach(c => { CHOICE_SIDE[c.value] = c.side; }));

        let schemaRowSeq = 0;
        let schemaTables = [];

        function schemaRowHtml(table, column, kind, infoHtml, checked) {
            const id = 'dbsRow' + (++schemaRowSeq);
            const seg = '<div class="dbs-seg">' + SCHEMA_CHOICES[kind].map(function(o, i) {
                return '<input type="radio" name="' + id + '" id="' + id + '_' + i + '" value="' + o.value + '"' + (i === 0 ? ' checked' : '') + '>' +
                    '<label for="' + id + '_' + i + '" class="' + (o.tone || '') + '">' + o.label + '</label>';
            }).join('') + '</div>';
            return '<div class="dbs-col' + (checked ? ' is-picked' : '') + '">' +
                '<label class="dbs-col-info"><input type="checkbox" class="dbs-pick" data-table="' + escHtml(table) + '" data-column="' + escHtml(column) + '" data-kind="' + kind + '"' + (checked ? ' checked' : '') + '>' + infoHtml + '</label>' +
                seg + '</div>';
        }

        function colInfo(col) {
            return '<code>' + escHtml(col.name) + '</code><span class="dbs-col-type">' + escHtml(col.type) + (col.nullable === false ? ' NOT NULL' : '') + '</span>';
        }

        function schemaFooterHtml() {
            return '<div class="dbs-foot">' +
                '<div class="dbs-foot-info"><span class="dbs-side is-local"><i class="fa-solid fa-laptop-code"></i> Local <b id="dbsCntLocal">0</b></span>' +
                '<span class="dbs-side is-live"><i class="fa-solid fa-cloud"></i> Live <b id="dbsCntLive">0</b></span></div>' +
                '<div class="dbs-foot-btns">' +
                '<button type="button" class="sr-btn sr-btn-sm" id="dbsPreviewBtn"><i class="fa-solid fa-code"></i> Generate SQL</button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-success" id="dbsApplyLocalBtn" data-target="local"><i class="fa-solid fa-laptop-code"></i> Apply to Local</button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-danger" id="dbsApplyLiveBtn" data-target="live"><i class="fa-solid fa-cloud-arrow-up"></i> Apply to Live</button>' +
                '</div></div>' +
                '<div id="dbsOut"></div>';
        }

        function showSchemaDiffModal(res) {
            const report = res.report || {};
            const names = Object.keys(report);
            let diffTables = 0, colIssues = 0;
            schemaRowSeq = 0;

            const cards = names.map(function(table) {
                const r = report[table];
                const missLocal = r.missing_in_local || [];
                const missLive = r.missing_in_live || [];
                const types = r.type_mismatches || [];
                const idxLive = r.index_missing_in_live || [];
                const idxLocal = r.index_missing_in_local || [];
                const issues = r.exists_locally ? missLocal.length + missLive.length + types.length + idxLive.length + idxLocal.length : 1;
                let body = '';

                if (!r.exists_locally) {
                    body += '<div class="dbs-sec is-err"><i class="fa-solid fa-circle-xmark"></i> Table missing locally <span>' + missLocal.length + ' column(s) on live</span></div>';
                    body += schemaRowHtml(table, '', 'create', '<span class="dbs-col-type">CREATE TABLE <code>' + escHtml(table) + '</code> using the live definition</span>', true);
                } else {
                    if (missLocal.length) {
                        body += '<div class="dbs-sec is-err"><i class="fa-solid fa-arrow-down"></i> Only on live <span>missing in local</span></div>';
                        missLocal.forEach(col => { body += schemaRowHtml(table, col.name, 'missing_local', colInfo(col), true); });
                    }
                    if (missLive.length) {
                        body += '<div class="dbs-sec is-warn"><i class="fa-solid fa-arrow-up"></i> Only on local <span>missing on live</span></div>';
                        missLive.forEach(col => { body += schemaRowHtml(table, col.name, 'missing_live', colInfo(col), false); });
                    }
                    if (types.length) {
                        body += '<div class="dbs-sec is-warn"><i class="fa-solid fa-right-left"></i> Type differs</div>';
                        types.forEach(function(m) {
                            const info = '<code>' + escHtml(m.column) + '</code><span class="dbs-types"><span>local</span><code>' + escHtml(m.local_type) +
                                '</code><span>live</span><code>' + escHtml(m.live_type) + '</code></span>';
                            body += schemaRowHtml(table, m.column, 'type', info, false);
                        });
                    }
                    const idxInfo = i => '<code>' + escHtml(i.name) + '</code><span class="dbs-col-type">' + escHtml(i.label) + '</span>' +
                        (i.unique ? '<span class="dbs-idx-note">duplicates are removed (earliest row kept, original table kept as backup)</span>' : '');
                    if (idxLive.length) {
                        body += '<div class="dbs-sec is-warn"><i class="fa-solid fa-key"></i> Index only on local <span>missing on live</span></div>';
                        idxLive.forEach(i => { body += schemaRowHtml(table, i.name, 'index_live', idxInfo(i), false); });
                    }
                    if (idxLocal.length) {
                        body += '<div class="dbs-sec is-warn"><i class="fa-solid fa-key"></i> Index only on live <span>missing in local</span></div>';
                        idxLocal.forEach(i => { body += schemaRowHtml(table, i.name, 'index_local', idxInfo(i), false); });
                    }
                }

                const bad = issues > 0;
                if (bad) { diffTables++; colIssues += issues; }
                const pill = !r.exists_locally
                    ? '<span class="dbs-pill is-err">Missing locally</span>'
                    : (issues ? '<span class="dbs-pill is-warn">' + issues + ' difference' + (issues > 1 ? 's' : '') + '</span>' : '<span class="dbs-pill is-ok"><i class="fa-solid fa-check"></i> Matches</span>');

                return {
                    bad: bad,
                    html: '<div class="dbs-card' + (bad ? ' has-diff' : '') + '">' +
                        '<div class="dbs-card-head">' +
                        (bad ? '<input type="checkbox" class="dbs-pick-table" title="Select all in this table">' : '') +
                        '<span class="dbs-card-icon"><i class="fa-solid fa-table"></i></span><span class="dbs-card-name">' + escHtml(table) + '</span>' + pill + '</div>' +
                        (body ? '<div class="dbs-card-body">' + body + '</div>' : '') +
                        '</div>'
                };
            });

            // Tables with differences first
            const ordered = cards.sort((a, b) => b.bad - a.bad).map(c => c.html).join('');

            const ok = !res.any_mismatch;
            const head = '<div class="dbi-done-head">' +
                '<span class="dbi-done-icon' + (ok ? '' : ' is-warn') + '"><i class="fa-solid ' + (ok ? 'fa-check' : 'fa-triangle-exclamation') + '"></i></span>' +
                '<div class="dbi-pg-sub" style="margin:0;">' + (ok
                    ? 'All <strong>' + names.length + '</strong> selected table(s) have the same columns locally and on live.'
                    : '<strong>' + diffTables + '</strong> of ' + names.length + ' table(s) differ. Tick the changes you want, pick a direction for each, then generate the SQL or apply it directly.') +
                '</div></div>';

            const stats = '<div class="dbi-stats">' +
                '<div class="dbi-stat"><span class="dbi-stat-label">Checked</span><span class="dbi-stat-value">' + names.length + '</span></div>' +
                '<div class="dbi-stat tone-ok"><span class="dbi-stat-label">Matching</span><span class="dbi-stat-value">' + (names.length - diffTables) + '</span></div>' +
                '<div class="dbi-stat tone-warn"><span class="dbi-stat-label">Differ</span><span class="dbi-stat-value">' + diffTables + '</span></div>' +
                '<div class="dbi-stat tone-err"><span class="dbi-stat-label">Differences</span><span class="dbi-stat-value">' + colIssues + '</span></div>' +
                '</div>';

            const refreshBtn = '<button type="button" class="sr-btn sr-btn-sm dbs-refresh" id="dbsRefreshBtn" title="Compare the schema again"><i class="fa-solid fa-arrows-rotate"></i> Refresh</button>';
            const idxHint = res.indexes_checked === false
                ? '<div class="dbs-note is-err" style="margin-bottom:10px;"><i class="fa-solid fa-circle-info"></i> Indexes were not compared - upload the latest <code>includes/api/db_export_schema.php</code> to live.</div>'
                : '';
            const quick = idxHint + '<div class="dbs-quick">' + (ok ? '' :
                '<span>Quick select:</span>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" data-quick="local"><i class="fa-solid fa-laptop-code"></i> Make Local match Live</button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" data-quick="live"><i class="fa-solid fa-cloud"></i> Make Live match Local</button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" data-quick="none"><i class="fa-regular fa-square"></i> Clear</button>') +
                refreshBtn + '</div>';

            Swal.fire({
                title: ok ? 'Schema matches' : 'Schema differences',
                customClass: { popup: 'dbi-popup' },
                width: '880px',
                html: head + stats + quick + '<div class="dbs-list">' + ordered + '</div>' + (ok ? '' : schemaFooterHtml()),
                showConfirmButton: true,
                confirmButtonText: 'Close',
                allowOutsideClick: false,
                didOpen: updateSchemaSelection
            });
        }

        function collectSchemaChanges(side) {
            const changes = [];
            $('.dbs-pick:checked').each(function() {
                const choice = $(this).closest('.dbs-col').find('input[type="radio"]:checked').val();
                if (!choice || (side && CHOICE_SIDE[choice] !== side)) return;
                changes.push({ table: this.dataset.table, column: this.dataset.column, choice: choice });
            });
            return changes;
        }

        function updateSchemaSelection() {
            const all = collectSchemaChanges();
            const local = all.filter(c => CHOICE_SIDE[c.choice] === 'local').length;
            const live = all.length - local;
            $('#dbsCntLocal').text(local);
            $('#dbsCntLive').text(live);
            $('#dbsPreviewBtn').prop('disabled', !all.length);
            $('#dbsApplyLocalBtn').prop('disabled', !local);
            $('#dbsApplyLiveBtn').prop('disabled', !live);
            $('.dbs-col').each(function() {
                $(this).toggleClass('is-picked', $(this).find('.dbs-pick').is(':checked'));
            });
            $('.dbs-card').each(function() {
                const $picks = $(this).find('.dbs-pick');
                const n = $picks.filter(':checked').length;
                $(this).find('.dbs-pick-table').prop('checked', n > 0 && n === $picks.length).prop('indeterminate', n > 0 && n < $picks.length);
            });
        }

        function schemaPost(action, extra) {
            return ajaxPost(Object.assign({
                action: action,
                base_url: $baseUrl.val().trim(),
                export_key: $exportKey.val().trim()
            }, extra || {}));
        }

        function sqlBlockHtml(side, items) {
            if (!items.length) return '';
            const okItems = items.filter(i => i.sql && !i.error);
            const bad = items.filter(i => i.error);
            const sql = okItems.map(i => i.sql).join('\n');
            let html = '<div class="dbs-sql">' +
                '<div class="dbs-sql-head"><span class="dbs-side is-' + side + '"><i class="fa-solid ' + (side === 'live' ? 'fa-cloud' : 'fa-laptop-code') + '"></i> Run on ' + side.toUpperCase() + ' <b>' + okItems.length + '</b></span>' +
                (okItems.length ? '<button type="button" class="sr-btn sr-btn-sm dbs-copy"><i class="fa-solid fa-copy"></i> Copy</button>' : '') + '</div>';
            if (okItems.length) {
                html += '<textarea class="dbs-sql-out" rows="' + Math.min(10, okItems.length + 1) + '" readonly>' + escHtml('-- Run on ' + side.toUpperCase() + ' database\n' + sql) + '</textarea>';
            }
            bad.forEach(i => {
                html += '<div class="dbs-note is-err"><i class="fa-solid fa-circle-xmark"></i> <code>' + escHtml(i.table + (i.column ? '.' + i.column : '')) + '</code> ' + escHtml(i.error) + '</div>';
            });
            return html + '</div>';
        }

        function setSchemaBusy(busy) {
            $('#dbsPreviewBtn, #dbsApplyLocalBtn, #dbsApplyLiveBtn, #dbsConfirmApply, .dbs-quick button').prop('disabled', busy);
            if (!busy) updateSchemaSelection();
        }

        function showSchemaOut(html) {
            const $out = $('#dbsOut').html(html);
            if ($out[0]) $out[0].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        $(document).off('.dbs')
            .on('change.dbs', '.dbs-pick, .dbs-seg input', function() {
                // Picking a direction implies "include this change"
                if (this.type === 'radio') $(this).closest('.dbs-col').find('.dbs-pick').prop('checked', true);
                $('#dbsOut').empty();
                updateSchemaSelection();
            })
            .on('change.dbs', '.dbs-pick-table', function() {
                $(this).closest('.dbs-card').find('.dbs-pick').prop('checked', this.checked);
                $('#dbsOut').empty();
                updateSchemaSelection();
            })
            .on('click.dbs', '.dbs-quick button', function() {
                const mode = this.dataset.quick;
                $('.dbs-pick').each(function() {
                    const $row = $(this).closest('.dbs-col');
                    if (mode === 'none') { this.checked = false; return; }
                    const $radio = $row.find('input[type="radio"]').filter(function() { return CHOICE_SIDE[this.value] === mode; });
                    this.checked = $radio.length > 0;
                    if ($radio.length) $radio.prop('checked', true);
                });
                $('#dbsOut').empty();
                updateSchemaSelection();
            })
            .on('click.dbs', '.dbs-copy', function() {
                const $btn = $(this);
                const text = $btn.closest('.dbs-sql').find('.dbs-sql-out').val();
                const done = () => { $btn.html('<i class="fa-solid fa-check"></i> Copied'); setTimeout(() => $btn.html('<i class="fa-solid fa-copy"></i> Copy'), 1500); };
                if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(done);
                else { $btn.closest('.dbs-sql').find('.dbs-sql-out').select(); document.execCommand('copy'); done(); }
            })
            .on('click.dbs', '#dbsPreviewBtn', function() {
                const changes = collectSchemaChanges();
                if (!changes.length) return;
                setSchemaBusy(true);
                showSchemaOut('<div class="dbs-wait"><i class="fa-solid fa-spinner fa-spin"></i> Building SQL...</div>');
                schemaPost('schema_plan', { changes: JSON.stringify(changes) }).done(function(res) {
                    if (res.status !== 'success') {
                        showSchemaOut('<div class="dbs-note is-err">' + escHtml(res.message || 'Failed to build SQL') + '</div>');
                        return;
                    }
                    showSchemaOut(sqlBlockHtml('local', res.local || []) + sqlBlockHtml('live', res.live || []) +
                        '<div class="dbs-sql-hint">Statements are built from the current schema on each server. Column comments and per-column collation are not copied.</div>');
                }).fail(function() {
                    showSchemaOut('<div class="dbs-note is-err">Request failed.</div>');
                }).always(() => setSchemaBusy(false));
            })
            .on('click.dbs', '#dbsApplyLocalBtn, #dbsApplyLiveBtn', function() {
                // Step 1: preview exactly what will run + inline confirm (a second Swal would
                // replace this modal and lose the selection)
                const target = this.dataset.target;
                const changes = collectSchemaChanges(target);
                if (!changes.length) return;
                setSchemaBusy(true);
                showSchemaOut('<div class="dbs-wait"><i class="fa-solid fa-spinner fa-spin"></i> Preparing...</div>');
                schemaPost('schema_plan', { changes: JSON.stringify(changes) }).done(function(res) {
                    if (res.status !== 'success') {
                        showSchemaOut('<div class="dbs-note is-err">' + escHtml(res.message || 'Failed to build SQL') + '</div>');
                        return;
                    }
                    const items = res[target] || [];
                    const runnable = items.filter(i => i.sql && !i.error).length;
                    const drops = items.filter(i => /^(drop_local|remove_live)$/.test(i.choice) && !i.error).length;
                    let html = sqlBlockHtml(target, items);
                    if (runnable) {
                        html += '<div class="dbs-confirm is-' + target + '">' +
                            '<div><i class="fa-solid fa-triangle-exclamation"></i> Run <b>' + runnable + '</b> statement(s) directly on the <b>' + target.toUpperCase() + '</b> database' +
                            (drops ? ' - including <b>' + drops + ' DROP COLUMN</b> (data in those columns is lost permanently)' : '') + '. Schema changes cannot be rolled back.</div>' +
                            '<div class="dbs-confirm-btns"><button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="dbsCancelApply">Cancel</button>' +
                            '<button type="button" class="sr-btn sr-btn-sm ' + (target === 'live' ? 'sr-btn-danger' : 'sr-btn-success') + '" id="dbsConfirmApply" data-target="' + target + '">' +
                            '<i class="fa-solid fa-bolt"></i> Confirm &amp; apply to ' + target + '</button></div></div>';
                    }
                    showSchemaOut(html);
                }).fail(function() {
                    showSchemaOut('<div class="dbs-note is-err">Request failed.</div>');
                }).always(() => setSchemaBusy(false));
            })
            .on('click.dbs', '#dbsCancelApply', () => $('#dbsOut').empty())
            .on('click.dbs', '#dbsConfirmApply', function() {
                const target = this.dataset.target;
                const changes = collectSchemaChanges(target);
                setSchemaBusy(true);
                $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Applying...');
                schemaPost('apply_schema', { target: target, changes: JSON.stringify(changes) }).done(function(res) {
                    // Live rotates the single-use key on every apply call
                    if (res.next_export_key) $exportKey.val(res.next_export_key);
                    const results = res.results || [];
                    const tone = res.status === 'success' ? 'is-ok' : (res.status === 'warning' ? 'is-warn' : 'is-err');
                    let html = '<div class="dbs-result ' + tone + '"><div class="dbs-result-head"><i class="fa-solid ' +
                        (res.status === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation') + '"></i> ' + escHtml(res.message || '') + '</div>';
                    results.forEach(r => {
                        html += '<div class="dbs-result-row ' + (r.ok ? 'is-ok' : 'is-err') + '"><i class="fa-solid ' + (r.ok ? 'fa-check' : 'fa-xmark') + '"></i><div>' +
                            '<code>' + escHtml(r.sql || r.table || '') + '</code>' + (r.error ? '<small>' + escHtml(r.error) + '</small>' : '') + '</div></div>';
                    });
                    html += '<div class="dbs-confirm-btns"><button type="button" class="sr-btn sr-btn-sm sr-btn-primary" id="dbsRecheck"><i class="fa-solid fa-rotate"></i> Re-check schema</button></div></div>';
                    showSchemaOut(html);
                }).fail(function() {
                    showSchemaOut('<div class="dbs-note is-err">Request failed - check the server state with Re-check before trying again.</div>' +
                        '<div class="dbs-confirm-btns"><button type="button" class="sr-btn sr-btn-sm sr-btn-primary" id="dbsRecheck"><i class="fa-solid fa-rotate"></i> Re-check schema</button></div>');
                }).always(() => setSchemaBusy(false));
            })
            .on('click.dbs', '#dbsRecheck, #dbsRefreshBtn', () => runSchemaCheck(schemaTables));

        function runSchemaCheck(tables, $btn) {
            schemaTables = tables;
            if ($btn) $btn.prop('disabled', true);
            Swal.fire({
                title: 'Checking schema...',
                html: '<div class="dbi-pg-sub" style="margin:0;">Comparing columns for ' + tables.length + ' table(s) against the live server.</div>',
                customClass: { popup: 'dbi-popup' },
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            schemaPost('check_schema', { tables: tables }).done(function(res) {
                if (res.status !== 'success') {
                    Swal.fire({ title: 'Schema check failed', text: res.message || 'Unknown error.', icon: 'error', allowOutsideClick: false });
                    return;
                }
                showSchemaDiffModal(res);
            }).fail(function() {
                Swal.fire({ title: 'Request failed', text: 'Could not reach the local server.', icon: 'error', allowOutsideClick: false });
            }).always(function() {
                if ($btn) $btn.prop('disabled', false);
            });
        }

        $('#checkSchemaBtn').on('click', function() {
            const tables = $('.table-check:checked').map(function() { return this.value; }).get();
            if (!tables.length) {
                Swal.fire({ title: 'Nothing selected', text: 'Pick at least one table.', icon: 'warning', allowOutsideClick: false });
                return;
            }
            runSchemaCheck(tables, $(this));
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
                // Every table that will be emptied, with the local rows deleted and the live rows loaded
                let totalLocal = 0;
                const rows = $('.table-check:checked').map(function() {
                    const local = parseInt($(this).data('local-rows'), 10);
                    const live = parseInt($(this).data('live-rows'), 10) || 0;
                    if (local > 0) totalLocal += local;
                    return '<tr><td class="sr-mono">' + escHtml(this.value) + '</td>' +
                        '<td class="dbt-num">' + (local < 0 ? '<span class="sr-pill sr-pill-xs tone-slate">new table</span>' : '<span class="dbt-del">' + local.toLocaleString() + '</span>') + '</td>' +
                        '<td class="dbt-num">' + live.toLocaleString() + '</td></tr>';
                }).get().join('');
                Swal.fire({
                    title: 'Truncate before import?',
                    icon: 'warning',
                    width: 620,
                    html: '<div style="text-align:start">This deletes ALL local rows in <b>' + tables.length + ' table(s)</b> first' +
                        (totalLocal ? ' (<b>' + totalLocal.toLocaleString() + '</b> rows)' : '') + ', then loads live data. ' +
                        'Any row that exists only locally will be permanently lost. This cannot be undone.</div>' +
                        '<div class="dbt-wrap"><table class="dbt-table"><thead><tr><th>Table</th><th class="dbt-num">Local rows deleted</th><th class="dbt-num">Live rows loaded</th></tr></thead>' +
                        '<tbody>' + rows + '</tbody></table></div>',
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
