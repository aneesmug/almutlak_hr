<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';

$canUseDynamicImport = $is_system_admin
    || user_has_special_access($conDB, $empid ?? '', 'access_import_excel_dynamic', $user_role ?? '', $actual_user_type ?? ($user_type ?? ''), $is_system_admin ?? false);
if (!$canUseDynamicImport) {
    header('Location: dashboard.php');
    exit;
}
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?=$site_title ?> - Dynamic Excel Import</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .di { --di-line: rgba(128,138,150,.28); --di-soft: rgba(128,138,150,.10); --di-accent: #2f6fed; --di-ok: #1fa971; --di-warn: #e6a100; --di-bad: #e04b4b; }
        .di .di-card { border: 1px solid var(--di-line); border-radius: 10px; padding: 0; margin-bottom: 18px; overflow: hidden; }
        .di .di-card-head { display: flex; align-items: center; gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--di-line); background: var(--di-soft); }
        .di .di-card-head h5 { margin: 0; font-size: 15px; font-weight: 600; }
        .di .di-card-head .di-num { width: 26px; height: 26px; border-radius: 50%; background: var(--di-accent); color: #fff; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; }
        .di .di-card-body { padding: 20px; }
        .di .di-hint { font-size: 12px; opacity: .7; }
        .di .di-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; opacity: .75; margin-bottom: 6px; display: block; }

        .di-stepper { display: flex; gap: 0; margin-bottom: 18px; }
        .di-stepper .st { flex: 1; display: flex; align-items: center; gap: 10px; padding: 12px 16px; border: 1px solid var(--di-line); border-right-width: 0; font-size: 13.5px; opacity: .6; }
        .di-stepper .st:first-child { border-radius: 10px 0 0 10px; }
        .di-stepper .st:last-child { border-radius: 0 10px 10px 0; border-right-width: 1px; }
        .di-stepper .st .n { width: 24px; height: 24px; border-radius: 50%; border: 2px solid currentColor; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex: 0 0 auto; }
        .di-stepper .st.active { opacity: 1; color: var(--di-accent); background: rgba(47,111,237,.08); font-weight: 600; }
        .di-stepper .st.done { opacity: 1; color: var(--di-ok); }
        .di-stepper .st.done .n { background: var(--di-ok); border-color: var(--di-ok); color: #fff; }
        @media (max-width: 767px) { .di-stepper .st span.t { display: none; } .di-stepper .st { justify-content: center; padding: 10px 6px; } }

        .di-modes { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        @media (max-width: 575px) { .di-modes { grid-template-columns: 1fr; } }
        .di-mode { position: relative; border: 1.5px solid var(--di-line); border-radius: 8px; padding: 10px 12px 10px 38px; cursor: pointer; margin: 0; transition: border-color .15s, background .15s; }
        .di-mode:hover { border-color: var(--di-accent); }
        .di-mode input { position: absolute; left: 12px; top: 13px; }
        .di-mode .t { font-weight: 600; font-size: 13.5px; display: block; }
        .di-mode .d { font-size: 12px; opacity: .7; display: block; margin-top: 2px; line-height: 1.35; }
        .di-mode.on { border-color: var(--di-accent); background: rgba(47,111,237,.08); }

        .di-cols-tools { display: flex; gap: 8px; align-items: center; margin-bottom: 10px; flex-wrap: wrap; }
        .di-cols-tools .form-control { max-width: 260px; }
        .di-cols-wrap { border: 1px solid var(--di-line); border-radius: 8px; max-height: 360px; overflow: auto; }
        .di-cols-wrap table { margin: 0; font-size: 13px; }
        .di-cols-wrap thead th { position: sticky; top: 0; background: linear-gradient(var(--di-soft), var(--di-soft)), var(--di-bg, #fff); box-shadow: 0 1px 0 var(--di-line); z-index: 1; border-top: 0; font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em; }
        .di-cols-wrap td, .di-cols-wrap th { padding: 6px 10px; vertical-align: middle; border-color: var(--di-line); }
        .di-cols-wrap tr.row-key td { background: rgba(47,111,237,.10); }
        .di-cols-wrap tr.row-off td { opacity: .5; }
        .di-tag { display: inline-block; font-size: 10.5px; padding: 1px 7px; border-radius: 10px; background: var(--di-soft); border: 1px solid var(--di-line); margin-right: 3px; }
        .di-tag.pk { background: rgba(47,111,237,.14); border-color: rgba(47,111,237,.4); }
        .di-req { color: var(--di-bad); font-weight: 700; display: none; }
        .di.is-insert .di-req { display: inline; }
        .di-count { font-size: 12.5px; opacity: .8; }

        .di-drop { border: 2px dashed var(--di-line); border-radius: 10px; padding: 34px 20px; text-align: center; cursor: pointer; transition: border-color .15s, background .15s; }
        .di-drop:hover, .di-drop.over { border-color: var(--di-accent); background: rgba(47,111,237,.06); }
        .di-drop i { width: 100%; text-align: center; font-size: 34px; color: var(--di-accent); display: block; margin-bottom: 8px; }
        .di-drop .big { font-weight: 600; }
        .di-file { display: none; align-items: center; gap: 12px; border: 1px solid var(--di-line); border-radius: 8px; padding: 10px 14px; }
        .di-file i { font-size: 22px; color: var(--di-ok); }

        .di-map-wrap { border: 1px solid var(--di-line); border-radius: 8px; max-height: 420px; overflow: auto; }
        .di-map-wrap table { margin: 0; font-size: 13px; }
        .di-map-wrap thead th { position: sticky; top: 0; background: linear-gradient(var(--di-soft), var(--di-soft)), var(--di-bg, #fff); box-shadow: 0 1px 0 var(--di-line); z-index: 1; font-size: 11.5px; text-transform: uppercase; letter-spacing: .04em; border-top: 0; }
        .di-map-wrap td, .di-map-wrap th { padding: 7px 10px; vertical-align: middle; border-color: var(--di-line); }
        .di-map-wrap td.sample { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; opacity: .75; }
        .di-map-wrap .arrow { text-align: center; opacity: .5; width: 30px; }
        .di-map-wrap tr.mapped td.stat { color: var(--di-ok); }
        .di-warn { display: none; border-left: 4px solid var(--di-warn); background: rgba(230,161,0,.10); padding: 9px 14px; border-radius: 4px; font-size: 13px; margin-top: 12px; }
        .di-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; padding: 14px 20px; border-top: 1px solid var(--di-line); background: var(--di-soft); }
        .di-preview { max-height: 200px; overflow: auto; font-size: 12px; border: 1px solid var(--di-line); border-radius: 8px; }
        .di-preview table { margin: 0; }

        .di-tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        @media (max-width: 767px) { .di-tiles { grid-template-columns: repeat(2, 1fr); } }
        .di-tile { border: 1px solid var(--di-line); border-radius: 10px; padding: 14px 16px; }
        .di-tile .v { font-size: 26px; font-weight: 700; line-height: 1.1; }
        .di-tile .l { font-size: 12px; opacity: .75; text-transform: uppercase; letter-spacing: .04em; }
        .di-tile.ok .v { color: var(--di-ok); } .di-tile.bad .v { color: var(--di-bad); }
        .di-banner { border-radius: 8px; padding: 12px 16px; margin-bottom: 14px; font-size: 14px; }
        .di-banner.dry { background: rgba(47,111,237,.10); border: 1px solid rgba(47,111,237,.35); }
        .di-banner.done { background: rgba(31,169,113,.10); border: 1px solid rgba(31,169,113,.4); }
        .di-banner.err { background: rgba(224,75,75,.10); border: 1px solid rgba(224,75,75,.4); }
        .di-errs { max-height: 280px; overflow: auto; margin-top: 14px; border: 1px solid var(--di-line); border-radius: 8px; }
        .di-errs table { margin: 0; font-size: 12.5px; }
        /* select2 */
        .di .select2-container--default .select2-selection--single { height: 44px; border: 1px solid var(--di-line); border-radius: 8px; background: transparent; display: flex; align-items: center; transition: border-color .15s, box-shadow .15s; }
        .di .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 42px; padding-left: 14px; padding-right: 36px; font-size: 14px; font-weight: 500; color: inherit; }
        .di .select2-container--default .select2-selection--single .select2-selection__placeholder { opacity: .6; font-weight: 400; }
        .di .select2-container--default .select2-selection--single .select2-selection__arrow { height: 42px; right: 8px; }
        .di .select2-container--default.select2-container--focus .select2-selection--single,
        .di .select2-container--default.select2-container--open .select2-selection--single { border-color: var(--di-accent); box-shadow: 0 0 0 3px rgba(47,111,237,.15); }
        .select2-container--open { z-index: 2000; }
        .select2-dropdown { border: 1px solid rgba(128,138,150,.35); border-radius: 10px; box-shadow: 0 12px 32px rgba(20,30,50,.18); overflow: hidden; margin-top: 4px; }
        .select2-dropdown .select2-search--dropdown { padding: 10px; border-bottom: 1px solid rgba(128,138,150,.25); }
        .select2-dropdown .select2-search--dropdown .select2-search__field { height: 38px; border: 1px solid rgba(128,138,150,.35); border-radius: 8px; padding: 0 12px; font-size: 14px; outline: none; }
        .select2-dropdown .select2-search--dropdown .select2-search__field:focus { border-color: #2f6fed; box-shadow: 0 0 0 3px rgba(47,111,237,.15); }
        .select2-dropdown .select2-results__options { max-height: 320px; padding: 6px; }
        .select2-dropdown .select2-results__option { padding: 9px 12px; border-radius: 6px; font-size: 14px; margin-bottom: 1px; }
        .select2-dropdown .select2-results__option--highlighted[aria-selected],
        .select2-dropdown .select2-results__option--highlighted.select2-results__option--selectable { background: rgba(47,111,237,.12); color: #2f6fed; }
        .select2-dropdown .select2-results__option[aria-selected=true],
        .select2-dropdown .select2-results__option--selected { background: #2f6fed; color: #fff; font-weight: 600; }
        .di .select2-container { width: 100% !important; }
        .di-hide { display: none; }
    </style>
</head>
<body class="enlarged" data-keep-enlarged="true">
<div id="wrapper">
    <div class="left side-menu">
        <div class="slimscroll-menu" id="remove-scroll">
            <div class="topbar-left">
                <a href="dashboard.php" class="logo">
                    <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                    <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                </a>
            </div>
            <?php include("./includes/main_menu.php"); ?>
            <div class="clearfix"></div>
        </div>
    </div>

    <div class="content-page">
        <?php include("./includes/topbar.php"); ?>
        <div class="content">
            <div class="container-fluid di" id="di">

                <div class="mb-3">
                    <h4 class="mb-1 header-title">Dynamic Excel Import</h4>
                    <span class="di-hint">Import or update data in any table from an Excel / CSV file. First row = column headers &middot; max 10 MB / 50,000 rows.</span>
                </div>

                <div class="di-stepper" id="stepper">
                    <div class="st active" data-s="1"><span class="n">1</span><span class="t">Table &amp; options</span></div>
                    <div class="st" data-s="2"><span class="n">2</span><span class="t">Upload file</span></div>
                    <div class="st" data-s="3"><span class="n">3</span><span class="t">Map &amp; review</span></div>
                    <div class="st" data-s="4"><span class="n">4</span><span class="t">Result</span></div>
                </div>

                <div class="di-card card-box">
                    <div class="di-card-head"><span class="di-num">1</span><h5>Table &amp; options</h5></div>
                    <div class="di-card-body">
                        <div class="row">
                            <div class="col-lg-5">
                                <label class="di-label" for="tableSelect">Target table</label>
                                <select id="tableSelect" class="form-control"></select>

                                <div id="optWrap" class="di-hide mt-4">
                                    <label class="di-label">What should the import do?</label>
                                    <div class="di-modes" id="modes">
                                        <label class="di-mode on"><input type="radio" name="mode" value="update" checked><span class="t">Update existing</span><span class="d">Match each row to a record by a key (e.g. emp_id) and update it. Nothing new is created.</span></label>
                                        <label class="di-mode"><input type="radio" name="mode" value="insert"><span class="t">Insert new</span><span class="d">Add every row as a new record. Fails on a duplicate key.</span></label>
                                        <label class="di-mode"><input type="radio" name="mode" value="insert_ignore"><span class="t">Insert, skip duplicates</span><span class="d">Add new records, silently skip rows that already exist.</span></label>
                                        <label class="di-mode"><input type="radio" name="mode" value="upsert"><span class="t">Insert or update</span><span class="d">Update the record if the key already exists, otherwise insert it.</span></label>
                                    </div>

                                    <div id="keyWrap" class="mt-3">
                                        <label class="di-label" for="keyCol">Match records by (key column)</label>
                                        <select id="keyCol" class="form-control"></select>
                                        <div class="di-hint mt-1">Your file needs a column holding this value for every row.</div>
                                    </div>
                                    <div id="keepWrap" class="mt-3">
                                        <label class="di-label" for="keepEmpty">Empty cells in the file</label>
                                        <select id="keepEmpty" class="form-control">
                                            <option value="1">Keep the existing value (recommended)</option>
                                            <option value="0">Overwrite with empty</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7 di-hide" id="colsWrap">
                                <div class="d-flex justify-content-between align-items-end flex-wrap mb-2 mt-3 mt-lg-0">
                                    <div>
                                        <label class="di-label mb-0">Columns to import</label>
                                        <span class="di-count" id="colCount"></span>
                                    </div>
                                    <a href="#" id="dlTemplate" class="btn btn-sm btn-outline-primary"><i class="fa fa-download"></i> Download template</a>
                                </div>
                                <div class="di-cols-tools">
                                    <input type="text" id="colSearch" class="form-control form-control-sm" placeholder="Search columns...">
                                    <button type="button" class="btn btn-sm btn-light" id="colAll">Select all</button>
                                    <button type="button" class="btn btn-sm btn-light" id="colNone">Clear</button>
                                </div>
                                <div class="di-cols-wrap">
                                    <table class="table table-sm mb-0" id="colTable">
                                        <thead><tr><th style="width:34px"></th><th>Column</th><th>Type</th><th>Info</th></tr></thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="di-hint mt-2"><span class="di-req">* = required for new rows (NOT NULL, no default). </span>The template and the mapping step only include the ticked columns.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="di-card card-box di-hide" id="step2">
                    <div class="di-card-head"><span class="di-num">2</span><h5>Upload file</h5></div>
                    <div class="di-card-body">
                        <div class="di-drop" id="drop">
                            <i class="fa fa-cloud-arrow-up"></i>
                            <div class="big">Drag &amp; drop your Excel / CSV file here</div>
                            <div class="di-hint">or click to browse &middot; .xlsx, .xls, .csv</div>
                        </div>
                        <input type="file" id="fileInput" class="di-hide" accept=".xlsx,.xls,.csv">
                        <div class="di-file" id="fileBox"><i class="fa fa-file-excel"></i><div class="flex-grow-1"><div id="fileName" class="font-weight-bold"></div><div id="fileMeta" class="di-hint"></div></div><button type="button" class="btn btn-sm btn-light" id="fileClear">Remove</button></div>
                    </div>
                </div>

                <div class="di-card card-box di-hide" id="step3">
                    <div class="di-card-head"><span class="di-num">3</span><h5>Map columns &amp; review</h5></div>
                    <div class="di-card-body">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="di-map-wrap">
                                    <table class="table table-sm" id="mapTable">
                                        <thead><tr><th>Excel column</th><th>Sample</th><th></th><th style="min-width:200px">Table column</th><th style="width:40px"></th></tr></thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div class="di-warn" id="unmappedWarn"></div>
                            </div>
                            <div class="col-lg-4 mt-3 mt-lg-0">
                                <label class="di-label" for="onError">When a row has an error</label>
                                <select id="onError" class="form-control">
                                    <option value="skip">Skip that row, import the rest</option>
                                    <option value="abort">Stop and save nothing</option>
                                </select>
                                <div class="di-hint mt-2">Tip: run a <strong>dry run</strong> first. It checks every row and reports problems without saving anything.</div>
                                <label class="di-label mt-4">File preview</label>
                                <div class="di-preview" id="samplePreview"></div>
                            </div>
                        </div>
                    </div>
                    <div class="di-actions">
                        <span class="di-hint" id="actionSummary"></span>
                        <span>
                            <button class="btn btn-outline-primary" id="dryBtn"><i class="fa fa-circle-check"></i> Dry run</button>
                            <button class="btn btn-success ml-1" id="importBtn"><i class="fa fa-database"></i> <span id="importBtnLbl">Update records</span></button>
                        </span>
                    </div>
                </div>

                <div class="di-card card-box di-hide" id="resultBox">
                    <div class="di-card-head"><span class="di-num">4</span><h5>Result</h5></div>
                    <div class="di-card-body">
                        <div id="resultBanner"></div>
                        <div class="di-tiles" id="tiles"></div>
                        <div class="di-errs di-hide" id="errWrap"><table class="table table-sm mb-0"><thead><tr><th style="width:80px">Row</th><th>Problem</th></tr></thead><tbody></tbody></table></div>
                    </div>
                </div>

            </div>
        </div>
        <footer class="footer"><?=$site_footer ?></footer>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/metisMenu.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="assets/js/jquery.slimscroll.js"></script>
<script src="./plugins/select2/js/select2.min.js"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function ($) {
    var API = 'includes/ajaxFile/dynamicImportHandler.php';
    (function () { var c = getComputedStyle(document.querySelector('.di-card')).backgroundColor; if (c && c !== 'rgba(0, 0, 0, 0)' && c !== 'transparent') document.getElementById('di').style.setProperty('--di-bg', c); })();
    var columns = [], token = null, headers = [], lastUpload = null;

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function post(data) { return $.post(API, data, null, 'json'); }
    function errMsg(x) { return (x.responseJSON && x.responseJSON.message) || 'Request failed.'; }
    function mode() { return $('input[name=mode]:checked').val(); }
    function isUpdate() { return mode() === 'update'; }
    function norm(s) { return String(s).toLowerCase().replace(/[^a-z0-9]/g, ''); }
    function fmtSize(b) { return b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
    var MODE_LABEL = { update: 'Update records', insert: 'Insert rows', insert_ignore: 'Insert rows', upsert: 'Import rows' };

    function setStep(n) {
        $('#stepper .st').each(function () {
            var s = +$(this).data('s');
            $(this).toggleClass('active', s === n).toggleClass('done', s < n);
            $(this).find('.n').html(s < n ? '&#10003;' : s);
        });
    }
    function scrollTo(sel) { $('html, body').animate({ scrollTop: $(sel).offset().top - 80 }, 250); }

    function showResultError(msg, errs) {
        $('#resultBox').removeClass('di-hide');
        $('#resultBanner').html('<div class="di-banner err"><strong>Import failed.</strong> ' + esc(msg) + '</div>');
        $('#tiles').empty();
        renderErrors(errs || []);
        setStep(4);
        scrollTo('#resultBox');
    }
    function renderErrors(errs) {
        var $w = $('#errWrap');
        if (!errs.length) { $w.addClass('di-hide'); return; }
        $w.removeClass('di-hide').find('tbody').html(errs.map(function (e) { return '<tr><td>' + e.row + '</td><td>' + esc(e.error) + '</td></tr>'; }).join(''));
    }

    post({ ajaxType: 'listTables' }).done(function (r) {
        var $s = $('#tableSelect').append('<option value="">Select a table...</option>');
        r.tables.forEach(function (t) { $s.append($('<option>').val(t).text(t)); });
        $s.select2({ placeholder: 'Search a table...', width: '100%' });
    });

    function selectedCols() { return $('#colTable .col-chk:checked').map(function () { return this.value; }).get(); }

    function updateColState() {
        var key = $('#keyCol').val(), upd = isUpdate();
        $('#colTable .col-chk').each(function () {
            var isKey = upd && this.value === key;
            if (isKey) this.checked = true;
            $(this).prop('disabled', isKey);
            $(this).closest('tr').toggleClass('row-key', isKey).toggleClass('row-off', !this.checked);
        });
        $('#colCount').text(selectedCols().length + ' of ' + columns.length + ' columns selected');
        $('#dlTemplate').attr('href', API + '?ajaxType=downloadTemplate&table=' + encodeURIComponent($('#tableSelect').val()) + '&columns=' + encodeURIComponent(selectedCols().join(',')));
    }

    function syncMode() {
        var upd = isUpdate();
        $('.di-mode').each(function () { $(this).toggleClass('on', $(this).find('input').prop('checked')); });
        $('#keyWrap, #keepWrap').toggle(upd);
        $('#di').toggleClass('is-insert', !upd);
        $('#importBtnLbl').text(MODE_LABEL[mode()]);
        updateColState();
        if (token) buildMapping();
    }

    $('#tableSelect').on('change', function () {
        var t = this.value;
        token = null; headers = []; lastUpload = null;
        $('#step3, #resultBox').addClass('di-hide');
        clearFile(true);
        if (!t) { $('#optWrap, #colsWrap, #step2').addClass('di-hide'); setStep(1); return; }
        post({ ajaxType: 'getColumns', table: t }).done(function (r) {
            columns = r.columns.filter(function (c) { return !c.generated; });
            $('#colTable tbody').html(columns.map(function (c) {
                var tags = '';
                if (c.key === 'PRI') tags += '<span class="di-tag pk">PK</span>'; else if (c.key === 'UNI') tags += '<span class="di-tag">unique</span>';
                if (c.auto) tags += '<span class="di-tag">auto</span>';
                if (!c.nullable) tags += '<span class="di-tag">not null</span>';
                return '<tr data-name="' + esc(c.name.toLowerCase()) + '"><td><input type="checkbox" class="col-chk" value="' + esc(c.name) + '"' + (c.auto ? '' : ' checked') + '></td>' +
                    '<td><strong>' + esc(c.name) + '</strong> ' + (c.required ? '<span class="di-req">*</span>' : '') + '</td><td class="text-muted">' + esc(c.type) + '</td><td>' + tags + '</td></tr>';
            }).join(''));
            $('#keyCol').html(columns.map(function (c) { return '<option value="' + esc(c.name) + '">' + esc(c.name) + (c.key === 'PRI' ? '  (primary key)' : c.key === 'UNI' ? '  (unique)' : '') + '</option>'; }).join(''));
            var pref = columns.find(function (c) { return /^emp_?id$/i.test(c.name); }) || columns.find(function (c) { return c.key === 'PRI'; }) || columns.find(function (c) { return c.key === 'UNI'; });
            if (pref) $('#keyCol').val(pref.name);
            $('#colSearch').val('');
            $('#optWrap, #colsWrap, #step2').removeClass('di-hide');
            syncMode();
            setStep(2);
        }).fail(function (x) { showResultError(errMsg(x)); });
    });

    $('input[name=mode]').on('change', syncMode);
    $('#keyCol').on('change', syncMode);
    $(document).on('change', '.col-chk', function () { updateColState(); if (token) buildMapping(); });
    $('#colAll').on('click', function () { $('#colTable tr:visible .col-chk:not(:disabled)').prop('checked', true); updateColState(); if (token) buildMapping(); });
    $('#colNone').on('click', function () { $('#colTable tr:visible .col-chk:not(:disabled)').prop('checked', false); updateColState(); if (token) buildMapping(); });
    $('#colSearch').on('input', function () {
        var q = this.value.toLowerCase().trim();
        $('#colTable tbody tr').each(function () { $(this).toggle(!q || String($(this).data('name')).indexOf(q) >= 0); });
    });

    function clearFile(silent) {
        token = null; lastUpload = null; headers = [];
        $('#fileInput').val(''); $('#fileBox').css('display', 'none'); $('#drop').show();
        if (!silent) { $('#step3, #resultBox').addClass('di-hide'); setStep(2); }
    }
    $('#fileClear').on('click', function () { clearFile(false); });
    $('#drop').on('click', function () { $('#fileInput').trigger('click'); })
        .on('dragover dragenter', function (e) { e.preventDefault(); $(this).addClass('over'); })
        .on('dragleave drop', function (e) { e.preventDefault(); $(this).removeClass('over'); })
        .on('drop', function (e) { var f = e.originalEvent.dataTransfer.files[0]; if (f) upload(f); });
    $('#fileInput').on('change', function () { if (this.files[0]) upload(this.files[0]); });

    function upload(f) {
        var fd = new FormData();
        fd.append('ajaxType', 'uploadFile'); fd.append('file', f);
        $('#drop .big').text('Reading ' + f.name + '...');
        $.ajax({ url: API, method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
            .done(function (r) {
                token = r.token; headers = r.headers; lastUpload = r;
                $('#fileName').text(f.name);
                $('#fileMeta').text(fmtSize(f.size) + ' · ' + r.total_rows + ' data rows · ' + headers.length + ' columns');
                $('#drop').hide(); $('#fileBox').css('display', 'flex');
                $('#mapTable tbody').empty();
                buildMapping();
                $('#step3').removeClass('di-hide'); $('#resultBox').addClass('di-hide');
                setStep(3);
                scrollTo('#step3');
            })
            .fail(function (x) { showResultError(errMsg(x)); })
            .always(function () { $('#drop .big').text('Drag & drop your Excel / CSV file here'); });
    }

    function buildMapping() {
        var chosen = selectedCols(), key = $('#keyCol').val();
        var opts = '<option value="">- do not import -</option>' + chosen.map(function (n) {
            return '<option value="' + esc(n) + '">' + esc(n) + (isUpdate() && n === key ? '  (key)' : '') + '</option>';
        }).join('');
        var prev = {};
        $('#mapTable .map-sel').each(function () { prev[$(this).data('idx')] = this.value; });
        var s0 = (lastUpload.sample && lastUpload.sample[0]) || [];
        $('#mapTable tbody').html(headers.map(function (h, i) {
            var sv = s0[i] == null ? '' : String(s0[i]);
            return '<tr><td><strong>' + esc(h || '(column ' + (i + 1) + ')') + '</strong></td><td class="sample" title="' + esc(sv) + '">' + esc(sv) + '</td><td class="arrow">&rarr;</td>' +
                '<td><select class="form-control form-control-sm map-sel" data-idx="' + i + '">' + opts + '</select></td><td class="stat"></td></tr>';
        }).join(''));
        $('#mapTable .map-sel').each(function () {
            var i = $(this).data('idx'), want = prev[i];
            if (!want || chosen.indexOf(want) < 0) want = chosen.find(function (n) { return norm(n) === norm(headers[i]); }) || '';
            $(this).val(want);
        });
        $('#samplePreview').html('<table class="table table-sm mb-0"><thead><tr>' + headers.map(function (h) { return '<th>' + esc(h) + '</th>'; }).join('') + '</tr></thead><tbody>' +
            lastUpload.sample.map(function (row) { return '<tr>' + headers.map(function (h, i) { return '<td>' + esc(row[i]) + '</td>'; }).join('') + '</tr>'; }).join('') + '</tbody></table>');
        refreshMapState();
    }

    function currentMapping() {
        var m = {};
        $('#mapTable .map-sel').each(function () { if (this.value) m[$(this).data('idx')] = this.value; });
        return m;
    }

    function refreshMapState() {
        var m = currentMapping(), used = {};
        Object.keys(m).forEach(function (k) { used[m[k]] = (used[m[k]] || 0) + 1; });
        $('#mapTable .map-sel').each(function () {
            var on = !!this.value, $tr = $(this).closest('tr');
            $tr.toggleClass('mapped', on);
            $tr.find('td.stat').html(on ? '<i class="fa fa-check"></i>' : '');
        });
        var msgs = [];
        if (isUpdate()) {
            if (!used[$('#keyCol').val()]) msgs.push('Map an Excel column to the key column <strong>' + esc($('#keyCol').val()) + '</strong>, otherwise rows cannot be matched.');
            else if (Object.keys(used).length < 2) msgs.push('Map at least one more column besides the key, otherwise there is nothing to update.');
        } else {
            var missing = columns.filter(function (c) { return c.required && !used[c.name]; }).map(function (c) { return c.name; });
            if (missing.length) msgs.push('Required columns not mapped: <strong>' + esc(missing.join(', ')) + '</strong>.');
        }
        var dup = Object.keys(used).filter(function (n) { return used[n] > 1; });
        if (dup.length) msgs.push('Mapped more than once: <strong>' + esc(dup.join(', ')) + '</strong>.');
        $('#unmappedWarn').html(msgs.join('<br>')).toggle(msgs.length > 0);
        $('#actionSummary').text(Object.keys(m).length + ' of ' + headers.length + ' file columns mapped · ' + (lastUpload ? lastUpload.total_rows : 0) + ' rows');
    }
    $(document).on('change', '.map-sel', refreshMapState);

    function run(dry) {
        var m = currentMapping();
        if (!Object.keys(m).length) { showResultError('Map at least one column.'); return; }
        var go = function () {
            var $bs = $('#dryBtn, #importBtn').prop('disabled', true);
            post({
                ajaxType: 'runImport', table: $('#tableSelect').val(), token: token, mapping: JSON.stringify(m),
                mode: mode(), key_column: $('#keyCol').val() || '', keep_empty: $('#keepEmpty').val(),
                on_error: $('#onError').val(), dry_run: dry ? '1' : '0'
            }).done(function (r) {
                $('#resultBox').removeClass('di-hide');
                $('#resultBanner').html(r.dry_run
                    ? '<div class="di-banner dry"><strong>Dry run complete - nothing was saved.</strong> This is what would happen if you import now.</div>'
                    : '<div class="di-banner done"><strong>Import finished.</strong> The changes have been saved to <code>' + esc($('#tableSelect').val()) + '</code>.</div>');
                $('#tiles').html(
                    '<div class="di-tile ok"><div class="v">' + r.saved + '</div><div class="l">' + (r.dry_run ? 'Would be saved' : 'Saved') + '</div></div>' +
                    '<div class="di-tile"><div class="v">' + r.unchanged + '</div><div class="l">Unchanged</div></div>' +
                    '<div class="di-tile"><div class="v">' + r.empty_rows_skipped + '</div><div class="l">Empty rows skipped</div></div>' +
                    '<div class="di-tile ' + (r.failed ? 'bad' : '') + '"><div class="v">' + r.failed + '</div><div class="l">Failed rows</div></div>');
                renderErrors(r.errors);
                setStep(4);
                if (!r.dry_run) { $('#step3').addClass('di-hide'); clearFile(true); }
                scrollTo('#resultBox');
            }).fail(function (x) {
                showResultError(errMsg(x), (x.responseJSON && x.responseJSON.errors) || []);
            }).always(function () { $bs.prop('disabled', false); });
        };
        if (dry) { go(); return; }
        Swal.fire({
            title: MODE_LABEL[mode()] + ' in ' + $('#tableSelect').val() + '?',
            text: 'This writes to the database and cannot be undone here. Run a dry run first if you have not.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, continue'
        }).then(function (res) { if (res.isConfirmed || res.value) go(); });
    }
    $('#dryBtn').on('click', function () { run(true); });
    $('#importBtn').on('click', function () { run(false); });
})(jQuery);
</script>
</body>
</html>
