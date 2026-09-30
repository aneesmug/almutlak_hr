<?php
// Employees > Send Memo: pick an employee (select2, by ID or name), pick a ready
// template, review/edit the filled email, send. History is kept per employee and
// shown on view_employee.php > Memos. Server side: includes/ajaxFile/employeeMemoHandler.php.
// Page access: includes/page_access_helper.php ('employee_memos.php'), enforced by main_menu.php.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';
require_once __DIR__ . '/includes/memo_helper.php';

include("./includes/avatar_select.php");
memo_ensure_table($conDB);

$preselectDraftId = (int) ($_GET['draft_id'] ?? 0);
// Add/edit/delete templates: admin or the 'manage_memo_templates' Special Access key.
$canManageTemplates = memo_user_can($conDB, 'templates', $empid ?? '', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$preselectEmpId = trim((string) ($_GET['emp_id'] ?? ''));
$preselectText = '';
if ($preselectEmpId !== '') {
    $stmt = $conDB->prepare("SELECT e.emp_id, e.name, d.dep_nme, c.comp_name FROM employees e
                             LEFT JOIN department d ON d.id = e.dept LEFT JOIN companies c ON c.comp_id = e.comp_no
                             WHERE e.emp_id = ? AND e.status = 1 LIMIT 1");
    $stmt->bind_param('s', $preselectEmpId);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $preselectText = memo_short_name($row['name']) . ' (' . $row['emp_id'] . ')'
            . (($row['dep_nme'] || $row['comp_name']) ? ' - ' . implode(' | ', array_filter([$row['dep_nme'], $row['comp_name']])) : '');
    } else {
        $preselectEmpId = '';
    }
    $stmt->close();
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('send_memo', 'Send Memo') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/summernote/0.8.20/summernote-bs4.min.css" rel="stylesheet" type="text/css" />

    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <?php if ($is_rtl ?? false): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script>window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>

    <style>
        .memo-loc-cascade { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        @media (max-width: 767px) { .memo-loc-cascade { grid-template-columns: 1fr; } }
        .memo-trail { display: inline-flex; flex-wrap: wrap; align-items: center; gap: 4px; line-height: 1.2; }
        .memo-crumb { display: inline-block; padding: 3px 10px; border-radius: 999px; background: #eef0fb; color: #4e5ad6; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .memo-crumb-current { background: #5b6ee1; color: #fff; }
        .memo-crumb-ar { font-weight: 500; opacity: .85; margin-left: 4px; }
        .memo-crumb-sep { color: #9aa3b8; font-size: 12px; margin: 0 2px; }
        .select2-results__option--highlighted .memo-crumb { background: rgba(255, 255, 255, .22); color: #fff; }
        .select2-results__option--highlighted .memo-crumb-current { background: #fff; color: #4e5ad6; }
        .select2-results__option--highlighted .memo-crumb-sep { color: #fff; }
        .select2-selection__rendered .memo-crumb { padding: 1px 8px; font-size: 11px; }
        html.app-dark .memo-crumb { background: #26304a; color: #aab4ff; }
        html.app-dark .memo-crumb-current { background: #5b6ee1; color: #fff; }
        .memo-type-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 8px; }
        .memo-type-btn {
            display: flex; align-items: center; gap: 8px; width: 100%; text-align: left;
            padding: 9px 12px; border: 1px solid #e3e6f0; border-radius: 8px; background: #fff;
            color: #334155; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .15s;
        }
        .memo-type-btn i { width: 18px; text-align: center; color: #6366f1; }
        .memo-type-btn:hover { border-color: #6366f1; background: #f5f6ff; }
        .memo-type-btn.active { border-color: #6366f1; background: #6366f1; color: #fff; }
        .memo-type-btn.active i { color: #fff; }
        .memo-step { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6366f1; margin-bottom: 8px; }
        .memo-compose-disabled { opacity: .5; pointer-events: none; }
        .note-editor.note-frame { border-color: #e3e6f0; }
        /* The theme's .btn-light gradient hides its own teal toolbar colour and leaves white icons on a near-white button. */
        .note-editor .note-btn-group .btn-light { background-image: none !important; }
        html:not(.app-dark) .note-editor .note-btn-group .btn-light { background-color: #fff !important; color: #334155 !important; border: 1px solid #e3e6f0 !important; }
        html:not(.app-dark) .note-editor .note-btn-group .btn-light:hover,
        html:not(.app-dark) .note-editor .note-btn-group .btn-light.active { background-color: #eef2ff !important; color: #4338ca !important; }
        /* Keep the editor usable even if the Summernote stylesheet fails to load. */
        .note-editor .note-editing-area .note-editable { overflow: auto; word-wrap: break-word; }
        .note-editor:not(.codeview) .note-editing-area .note-codable { display: none; }
        .memo-view-body { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #fff; color: #1f2937; text-align: left; max-height: 60vh; overflow: auto; }
        .select2-container { width: 100% !important; }
        .memo-draft-flag { display: none; }
        .memo-draft-flag.show { display: inline-block; }
        .memo-ph-chip { display: inline-block; margin: 0 6px 6px 0; padding: 3px 9px; border-radius: 12px; font-size: 12px; background: #eef2ff; color: #3730a3; cursor: pointer; border: 0; }
        .memo-ph-chip:hover { background: #6366f1; color: #fff; }
        #memoTplModal .modal-dialog { max-width: 1100px; }
        .memo-tpl-inactive { opacity: .55; }
        #memoTplModal .modal-dialog { max-width: 1300px; }
        .memo-field-card { border: 1px solid #e3e6f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px; }
        .memo-field-card > label { font-weight: 600; margin-bottom: 6px; }
        .memo-field-pair { display: flex; gap: 10px; }
        .memo-field-pair > div { flex: 1 1 0; min-width: 0; }
        .memo-field-pair .lang-tag { font-size: 11px; color: #94a3b8; }
        .memo-fields-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap: 0 12px; }
        .memo-preview { border: 1px solid #e3e6f0; border-radius: 8px; padding: 18px; background: #fff; color: #1f2937; font-size: 14px; line-height: 1.6; min-height: 200px; max-height: 70vh; overflow: auto; }
        .memo-preview .memo-missing, .memo-view-body .memo-missing { background: #fff3cd; color: #92400e; border-radius: 3px; padding: 0 3px; }
        .memo-fields-grid .memo-field-wide { grid-column: 1 / -1; }
        .memo-fields-editor input, .memo-fields-editor select { font-size: 12px; }
        @media (max-width: 767px) { .memo-field-pair { flex-direction: column; } .memo-fields-grid { grid-template-columns: 1fr; } }

        /* Compose form: titles and entry boxes must not look alike - titles are small
           slate captions (a tinted header strip on the Details cards), entry boxes are
           tinted with a stronger border and turn white with an indigo ring on focus. */
        .memo-form label { font-size: 12px; font-weight: 600; color: #5b6b86; letter-spacing: .2px; margin-bottom: 5px; }
        .memo-form .custom-control-label { font-size: 13px; color: #334155; letter-spacing: 0; }
        .memo-form .memo-field-card { padding: 0 12px 12px; overflow: hidden; }
        .memo-form .memo-field-card > label {
            display: block; margin: 0 -12px 10px; padding: 7px 12px;
            background: #eef1fb; border-bottom: 1px solid #dfe4f5; color: #3f4a8a; font-size: 13px;
        }
        .memo-form .memo-field-card > label .text-muted { color: #7b86b8 !important; font-weight: 500; }
        .memo-form .memo-field-pair .lang-tag { font-weight: 600; color: #7c8aa5; text-transform: uppercase; letter-spacing: .4px; }
        html:not(.app-dark) .memo-form .form-control,
        html:not(.app-dark) .memo-form .select2-container .select2-selection--single {
            background-color: #f5f8fd; border-color: #c3cede; color: #0f172a; font-weight: 500;
        }
        html:not(.app-dark) .memo-form .form-control::placeholder { color: #9aa7bb; font-weight: 400; }
        html:not(.app-dark) .memo-form .select2-container .select2-selection__placeholder { color: #9aa7bb; font-weight: 400; }
        html:not(.app-dark) .memo-form .form-control:hover,
        html:not(.app-dark) .memo-form .select2-container .select2-selection--single:hover { border-color: #9fb0c9; }
        html:not(.app-dark) .memo-form .form-control:focus,
        html:not(.app-dark) .memo-form .select2-container--focus .select2-selection--single,
        html:not(.app-dark) .memo-form .select2-container--open .select2-selection--single {
            background-color: #fff; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, .16); outline: 0;
        }
        html:not(.app-dark) .memo-form .form-control:disabled,
        html:not(.app-dark) .memo-form .select2-container--disabled .select2-selection--single { background-color: #e9edf3; color: #94a3b8; }
        html:not(.app-dark) .memo-form .input-group-text { background-color: #e6ebf5; border-color: #c3cede; color: #4f5bd5; }
        html.app-dark .memo-form .memo-field-card > label { background: #222b40; border-bottom-color: #2f3a55; color: #c3cbff; }
        /* Salary box (job offer): one row per added salary element. */
        .memo-sal-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .memo-sal-name { flex: 1 1 0; min-width: 0; font-size: 13px; font-weight: 600; }
        .memo-sal-amount { flex: 0 0 220px; }
        .memo-sal-del { flex: 0 0 34px; }
        .memo-sal-add { display: flex; gap: 10px; max-width: 520px; margin-top: 4px; }
        .memo-sal-add .btn { white-space: nowrap; }
        .memo-sal-total { margin-top: 10px; padding-top: 8px; border-top: 1px dashed #cfd6e4; text-align: right; font-size: 14px; }
        @media (max-width: 575px) { .memo-sal-amount { flex-basis: 120px; } }
    </style>
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

        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card-box memo-form">
                            <h4 class="m-t-0 header-title"><i class="fa-duotone fa-envelope-open-text mr-1"></i> <?= __('send_memo', 'Send Memo') ?></h4>
                            <p class="text-muted"><?= __('send_memo_desc', 'Choose an employee and a memo type. The email is prepared from a ready template with the employee\'s details - review or edit it, then send. Every memo is saved in the employee\'s master file.') ?></p>

                            <div class="row">
                                <div class="col-lg-5 mb-3">
                                    <div class="memo-step">1. <?= __('employee', 'Employee') ?></div>
                                    <select id="memoEmployee" class="form-control">
                                        <?php if ($preselectEmpId !== ''): ?>
                                            <option value="<?= htmlspecialchars($preselectEmpId) ?>" selected><?= htmlspecialchars($preselectText) ?></option>
                                        <?php endif; ?>
                                    </select>
                                    <small class="form-text text-muted"><?= __('search_by_emp_id_or_name', 'Search by Employee ID, name or Iqama.') ?></small>
                                    <div class="alert alert-info py-2 px-3 mt-2 mb-0 small" id="memoCandidateNote" style="display:none">
                                        <i class="fa fa-user-plus mr-1"></i><?= __('memo_candidate_note', 'Job offer for a new employee: no employee is selected. Enter the candidate and the offer terms in Details. The candidate gets an email with a link to open, print and sign the letter.') ?>
                                    </div>
                                </div>
                                <div class="col-lg-7 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="memo-step">2. <?= __('memo_type', 'Memo Type') ?></div>
                                        <?php if ($canManageTemplates): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary mb-2" id="memoManageTpl"><i class="fa fa-sliders mr-1"></i><?= __('manage_templates', 'Manage Templates') ?></button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="memo-type-grid" id="memoTypeGrid"></div>
                                </div>
                            </div>

                            <div id="memoCompose" class="memo-compose-disabled">
                                <div class="memo-step mt-2">3. <?= __('memo_details', 'Details') ?>
                                    <span class="badge badge-warning ml-2 memo-draft-flag" id="memoDraftFlag"><i class="fa fa-file-pen mr-1"></i><?= __('editing_draft', 'Editing draft') ?> #<span></span></span>
                                </div>
                                <div id="memoFields" class="mb-2"></div>

                                <div class="memo-step mt-3">4. <?= __('review_and_send', 'Review & Send') ?></div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="memoTo"><?= __('to', 'To') ?> <span class="text-danger">*</span></label>
                                        <input type="email" id="memoTo" class="form-control" placeholder="employee@company.com">
                                        <small class="form-text text-muted" id="memoToHint"></small>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label for="memoCc"><?= __('cc', 'CC') ?></label>
                                        <input type="text" id="memoCc" class="form-control" placeholder="<?= __('cc_hint', 'Optional - separate multiple emails with commas') ?>">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="memoRef"><?= __('reference_no', 'Reference No.') ?></label>
                                        <input type="text" id="memoRef" class="form-control">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="memoSubject"><?= __('subject', 'Subject') ?> <span class="text-danger">*</span></label>
                                    <input type="text" id="memoSubject" class="form-control">
                                </div>
                                <div class="form-group">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="mb-0"><?= __('preview', 'Preview') ?> <small class="text-muted">(English | العربية)</small></label>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="memoManual">
                                            <label class="custom-control-label" for="memoManual"><?= __('edit_manually', 'Edit final text manually') ?></label>
                                        </div>
                                    </div>
                                    <div id="memoPreview" class="memo-preview ad-keep"></div>
                                    <div id="memoManualWrap" style="display:none">
                                        <textarea id="memoBody"></textarea>
                                        <small class="form-text text-warning"><?= __('memo_manual_hint', 'Manual mode: changes in the Details fields no longer update the text. Turn it off to rebuild the memo from the fields.') ?></small>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button type="button" class="btn btn-light mr-2" id="memoNew"><i class="fa fa-file mr-1"></i><?= __('new_memo', 'New Memo') ?></button>
                                    <button type="button" class="btn btn-secondary mr-2" id="memoSaveDraft"><i class="fa fa-floppy-disk mr-1"></i><?= __('save_draft', 'Save Draft') ?></button>
                                    <button type="button" class="btn btn-primary" id="memoSend"><i class="fa fa-paper-plane mr-1"></i><?= __('send_memo', 'Send Memo') ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card-box">
                            <h4 class="m-t-0 header-title"><?= __('sent_memos', 'Sent Memos') ?></h4>
                            <table id="memoHistory" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead>
                                <tr>
                                    <th><?= __('date') ?></th>
                                    <th><?= __('employee', 'Employee') ?></th>
                                    <th><?= __('memo_type', 'Memo Type') ?></th>
                                    <th><?= __('subject', 'Subject') ?></th>
                                    <th><?= __('reference_no', 'Reference No.') ?></th>
                                    <th><?= __('status') ?></th>
                                    <th><?= __('sent_by', 'Sent By') ?></th>
                                    <th><?= __('action') ?></th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="footer"><?= $site_footer ?></footer>

        <!-- Manage Templates -->
        <div class="modal fade" id="memoTplModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa fa-sliders mr-1"></i><?= __('manage_templates', 'Manage Templates') ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div id="memoTplListView">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted"><?= __('memo_tpl_list_hint', 'Edit the wording of any template. Inactive templates are hidden from the memo type list. Built-in templates can be reset to their original text.') ?></small>
                                <button type="button" class="btn btn-sm btn-primary" id="memoTplAdd"><i class="fa fa-plus mr-1"></i><?= __('new_template', 'New Template') ?></button>
                            </div>
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr><th><?= __('name', 'Name') ?></th><th><?= __('subject', 'Subject') ?></th><th><?= __('active', 'Active') ?></th><th><?= __('last_updated', 'Last Updated') ?></th><th class="text-right"><?= __('action') ?></th></tr></thead>
                                <tbody id="memoTplRows"></tbody>
                            </table>
                        </div>
                        <div id="memoTplEditView" style="display:none">
                            <input type="hidden" id="tplKey">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="tplLabel"><?= __('name', 'Name') ?> (English) <span class="text-danger">*</span></label>
                                    <input type="text" id="tplLabel" class="form-control" maxlength="120">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="tplLabelAr"><?= __('name', 'Name') ?> (العربية)</label>
                                    <input type="text" id="tplLabelAr" class="form-control" maxlength="120" dir="rtl">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="tplIcon"><?= __('icon', 'Icon') ?> <small class="text-muted">(e.g. fa-envelope)</small></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i id="tplIconPreview" class="fa-solid fa-envelope"></i></span></div>
                                        <input type="text" id="tplIcon" class="form-control" maxlength="60">
                                    </div>
                                </div>
                            </div>

                            <label class="mb-1"><?= __('memo_fields', 'Detail fields') ?> <small class="text-muted"><?= __('memo_fields_hint', '- what HR fills in when sending; use {{f:key}} in the text') ?></small></label>
                            <table class="table table-sm memo-fields-editor mb-1">
                                <thead><tr><th style="width:15%">Key</th><th>Label (English)</th><th>Label (العربية)</th><th style="width:12%">Type</th><th style="width:9%" title="Separate English and Arabic values">EN + AR</th><th style="width:8%">Required</th><th style="width:5%"></th></tr></thead>
                                <tbody id="tplFieldRows"></tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="tplFieldAdd"><i class="fa fa-plus mr-1"></i><?= __('add_field', 'Add field') ?></button>

                            <div class="form-group mb-1">
                                <label class="mb-1"><?= __('placeholders', 'Placeholders') ?> <small class="text-muted"><?= __('memo_ph_hint2', '- click to insert where the cursor is (subject or body)') ?></small></label>
                                <div id="tplPlaceholders"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tplSubject"><?= __('subject', 'Subject') ?> (English) <span class="text-danger">*</span></label>
                                        <input type="text" id="tplSubject" class="form-control tpl-ph-target" maxlength="255">
                                    </div>
                                    <label><?= __('body', 'Body') ?> (English) <span class="text-danger">*</span></label>
                                    <textarea id="tplBody"></textarea>
                                </div>
                                <div class="col-md-6" dir="rtl">
                                    <div class="form-group text-right">
                                        <label for="tplSubjectAr"><?= __('subject', 'Subject') ?> (العربية)</label>
                                        <input type="text" id="tplSubjectAr" class="form-control tpl-ph-target" maxlength="255" dir="rtl">
                                    </div>
                                    <label class="d-block text-right"><?= __('body', 'Body') ?> (العربية)</label>
                                    <textarea id="tplBodyAr"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div id="memoTplEditButtons" style="display:none">
                            <button type="button" class="btn btn-light" id="memoTplBack"><i class="fa fa-arrow-left mr-1"></i><?= __('back', 'Back') ?></button>
                            <button type="button" class="btn btn-primary" id="memoTplSave"><i class="fa fa-floppy-disk mr-1"></i><?= __('save_template', 'Save Template') ?></button>
                        </div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="memoTplClose"><?= __('close', 'Close') ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/metisMenu.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="assets/js/jquery.slimscroll.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="./plugins/select2/js/select2.min.js"></script>
<script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
<script src="./plugins/datatables/jquery.dataTables.min.js"></script>
<script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
<script src="./plugins/datatables/dataTables.responsive.min.js"></script>
<script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>
<script src="./plugins/summernote/0.8.20/summernote-bs4.min.js"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
<script>window.MEMO_OPEN_DRAFT_ID = <?= (int) $preselectDraftId ?>;</script>
<script src="assets/js/employee_memos.js?v=<?= @filemtime(__DIR__ . '/assets/js/employee_memos.js') ?>"></script>
</body>
</html>
