<?php
// Create General Request. Saved by includes/ajaxFile/ajaxGeneralRequest.php (action=create_general_request),
// then view_general_request.php opens so the creator can pick the approval chain.
// Styling: assets/css/smart_request.css (sr-* design system).
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
require_once __DIR__ . '/includes/general_request_helpers.php';

// Restrict access to non-employee users only
if ($user_type == 'employee') {
    header('Location: dashboard.php');
    exit;
}

// General requests are globally blocked - hide this page entirely (matches sidebar link hiding).
if (is_employee_request_blocked($conDB, $empid ?? '', 'general_request')['blocked']) {
    header('Location: dashboard.php');
    exit;
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
if(mysqli_num_rows($query) == 1){
    include("./includes/avatar_select.php");
}

// Generate invoice number if not present
if (!isset($_GET['id']) || $_GET['id'] === '') {
    $inv_no = 'GR' . $empid . date("ymdHis");
    header("Location: new_general_request.php?id=$inv_no");
    exit;
}
$inv_no = (string)$_GET['id'];

// Company the request is raised for (defaults to the user's own company when allowed)
gr_ensure_company_column($conDB);
$company_options = gr_company_options($conDB);
$default_company = isset($company_options[(int)($user_company ?? 0)]) ? (int)$user_company : (count($company_options) === 1 ? (int)array_key_first($company_options) : 0);
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?=__('create_general_request', 'Create General Request')?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>

    <style type="text/css">
        /* Item row: Name | Type | Qty | [+][-] , Specifications on the next line */
        .gr-new .gr-item .sr-addline-grid { grid-template-columns: minmax(0, 6fr) minmax(0, 4fr) minmax(0, 2fr) auto; }
        .gr-new .sr-addline-grid .g-name, .gr-new .sr-addline-grid .g-type, .gr-new .sr-addline-grid .g-qty { grid-column: auto; }
        .gr-new .sr-addline-grid .g-full { grid-column: 1 / -1; }
        .gr-new .sr-addline-grid .g-act { display: flex; gap: 6px; align-self: end; }
        .gr-new .g-act .sr-btn { width: 38px; height: 38px; padding: 0; justify-content: center; }
        @media (max-width: 767px) {
            .gr-new .gr-item .sr-addline-grid { grid-template-columns: minmax(0, 1fr) auto; }
            .gr-new .sr-addline-grid .g-name, .gr-new .sr-addline-grid .g-type { grid-column: 1 / -1; }
        }
        .gr-new .sr-addline textarea { min-height: 60px; resize: vertical; }
        .gr-new .sr-fsec .sr-addlines { max-height: none; overflow: visible; margin: 0; padding: 14px 14px 0; }
        .gr-new .sr-fsec .sr-addlines .gr-item:last-child { margin-bottom: 14px; }
        .gr-new .gr-file-list { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
        .gr-new .gr-file-list:empty { display: none; }
        /* Priority choices take their tone colour (low=sky, medium=indigo, high=amber, urgent=red) */
        .gr-new .gr-pri.tone-sky    { --pri-fg: var(--tone-sky-fg);    --pri-bg: var(--tone-sky-bg);    --pri-bd: var(--tone-sky-bd); }
        .gr-new .gr-pri.tone-indigo { --pri-fg: var(--tone-indigo-fg); --pri-bg: var(--tone-indigo-bg); --pri-bd: var(--tone-indigo-bd); }
        .gr-new .gr-pri.tone-amber  { --pri-fg: var(--tone-amber-fg);  --pri-bg: var(--tone-amber-bg);  --pri-bd: var(--tone-amber-bd); }
        .gr-new .gr-pri.tone-red    { --pri-fg: var(--tone-red-fg);    --pri-bg: var(--tone-red-bg);    --pri-bd: var(--tone-red-bd); }
        .gr-new .gr-pri span i { color: var(--pri-fg); }
        .gr-new .gr-pri span:hover { border-color: var(--pri-bd); }
        .gr-new .gr-pri input:checked + span {
            border-color: var(--pri-fg); background: var(--pri-bg); color: var(--pri-fg);
            box-shadow: 0 0 0 1px var(--pri-fg) inset;
        }
        .gr-new .gr-pri input:checked + span i { color: var(--pri-fg); }
        .gr-new .select2-container .select2-selection.is-invalid { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220, 38, 38, .12); }
        .gr-new .gr-submit-bar { display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }
    </style>

    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <!-- Left Sidebar -->
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

        <!-- Content Page -->
        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>

            <div class="content sr-page gr-new">
                <div class="container-fluid">

                    <div class="sr-head">
                        <div>
                            <h1><?=__('create_general_request', 'Create General Request')?></h1>
                            <p><?= __('create_general_request_subtitle', 'Ask another department for equipment, supplies or a service.') ?></p>
                        </div>
                        <div class="sr-head-actions">
                            <a href="all_general_requests.php" class="sr-btn"><i class="mdi mdi-arrow-left"></i> <?=__('all_general_requests', 'All General Requests')?></a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-8">
                            <form id="generalRequestForm" class="sr-form" autocomplete="off" novalidate>
                                <input type="hidden" name="inv_no" value="<?= htmlspecialchars($inv_no, ENT_QUOTES) ?>">
                                <input type="hidden" name="emp_id" value="<?= htmlspecialchars((string)$empid, ENT_QUOTES) ?>">
                                <input type="hidden" name="emp_name" value="<?= htmlspecialchars((string)$userwel, ENT_QUOTES) ?>">

                                <!-- Request details -->
                                <div class="sr-fsec">
                                    <div class="sr-fsec-head">
                                        <span><i class="mdi mdi-file-document"></i> <?= __('request_details', 'Request details') ?></span>
                                        <span class="sr-chip sr-mono"><i class="mdi mdi-pound"></i><?= htmlspecialchars($inv_no) ?></span>
                                    </div>
                                    <div class="sr-fgrid">
                                        <div class="sr-fcol c-12">
                                            <div class="sr-newreq-info mb-0">
                                                <span><i class="mdi mdi-calendar"></i> <?= __('request_date', 'Request Date') ?>: <b><?= date('d M Y') ?></b></span>
                                                <span><i class="mdi mdi-account-outline"></i> <?= __('requester_name', 'Requester Name') ?>: <b><?= htmlspecialchars(getDisplayName((string)$userwel)) ?></b></span>
                                            </div>
                                        </div>
                                        <div class="sr-fcol c-12">
                                            <label for="f_request_title"><?= __('request_title', 'Request Title') ?> <span class="text-danger">*</span></label>
                                            <input type="text" name="request_title" id="f_request_title" class="form-control" maxlength="250" data-required="1" placeholder="<?= htmlspecialchars(__('enter_request_title', 'Enter request title'), ENT_QUOTES) ?>">
                                        </div>
                                        <div class="sr-fcol c-4">
                                            <label for="f_comp_no"><?= __('company', 'Company') ?> <span class="text-danger">*</span></label>
                                            <select name="comp_no" id="f_comp_no" class="form-control" data-required="1" data-placeholder="<?= htmlspecialchars(__('select_company', 'Select company'), ENT_QUOTES) ?>">
                                                <option value=""></option>
                                                <?php foreach ($company_options as $cid => $cname): ?>
                                                    <option value="<?= $cid ?>"<?= $cid === $default_company ? ' selected' : '' ?>><?= htmlspecialchars($cname) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="sr-fcol c-4">
                                            <label for="f_department_to"><?= __('target_department', 'Target Department') ?> <span class="text-danger">*</span></label>
                                            <select name="department_to" id="f_department_to" class="form-control" data-required="1">
                                                <option value=""><?= __('select') ?></option>
                                                <?php foreach (gr_target_departments() as $v => $l): ?>
                                                    <option value="<?= htmlspecialchars($v, ENT_QUOTES) ?>"><?= htmlspecialchars($l) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="sr-fcol c-4">
                                            <label for="f_request_category"><?= __('request_category', 'Request Category') ?> <span class="text-danger">*</span></label>
                                            <select name="request_category" id="f_request_category" class="form-control" data-required="1">
                                                <option value=""><?= __('select') ?></option>
                                                <?php foreach (gr_categories() as $v => $l): ?>
                                                    <option value="<?= htmlspecialchars($v, ENT_QUOTES) ?>"><?= htmlspecialchars($l) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="sr-fcol c-12">
                                            <label><?= __('priority', 'Priority') ?> <span class="text-danger">*</span></label>
                                            <div class="sr-attach-choice" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
                                                <?php $pri_icons = ['low' => 'mdi-flag-outline', 'medium' => 'mdi-flag', 'high' => 'mdi-flag', 'urgent' => 'mdi-flag'];
                                                foreach (gr_priorities() as $v => $p): ?>
                                                    <label class="sr-choice gr-pri <?= $p[1] ?>">
                                                        <input type="radio" name="priority" value="<?= $v ?>"<?= $v === 'medium' ? ' checked' : '' ?>>
                                                        <span><i class="mdi <?= $pri_icons[$v] ?>"></i> <?= htmlspecialchars($p[0]) ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="sr-fcol c-12">
                                            <label for="f_description"><?= __('description', 'Description') ?></label>
                                            <textarea name="description" id="f_description" class="form-control" rows="3" placeholder="<?= htmlspecialchars(__('additional_details', 'Additional details or notes'), ENT_QUOTES) ?>"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items -->
                                <div class="sr-fsec">
                                    <div class="sr-fsec-head">
                                        <span><i class="mdi mdi-format-list-bulleted"></i> <?= __('request_items', 'Request Items') ?></span>
                                        <span class="sr-count" id="grItemCount">0</span>
                                    </div>
                                    <div id="grItems" class="sr-addlines"></div>
                                </div>

                                <!-- Attachments -->
                                <div class="sr-fsec">
                                    <div class="sr-fsec-head">
                                        <span><i class="mdi mdi-paperclip"></i> <?= __('attachments', 'Attachments') ?> <?= __('optional', '(Optional)') ?></span>
                                    </div>
                                    <div class="sr-fgrid">
                                        <div class="sr-fcol c-12">
                                            <label class="sr-filepick" for="attachments">
                                                <input type="file" name="attachments[]" id="attachments" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                                                <i class="mdi mdi-cloud-upload"></i>
                                                <span>
                                                    <b class="js-file-name"><?= __('choose_files', 'Choose files or drop them here') ?></b>
                                                    <small><?= __('upload_multiple_files', 'You can upload multiple files (PDF, Images, Word, Excel)') ?></small>
                                                </span>
                                            </label>
                                            <div class="gr-file-list" id="grFileList"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="gr-submit-bar mb-4">
                                    <a href="all_general_requests.php" class="sr-btn"><i class="mdi mdi-close"></i> <?= __('cancel') ?></a>
                                    <button type="submit" class="sr-btn sr-btn-primary" id="submitBtn"><i class="mdi mdi-content-save"></i> <?= __('save_as_draft', 'Save as Draft') ?></button>
                                </div>
                            </form>
                        </div>

                        <!-- Side: how it works -->
                        <div class="col-xl-4">
                            <div class="sr-sticky">
                                <div class="sr-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-information-outline"></i> <?= __('how_it_works', 'How it works') ?></h5>
                                    </div>
                                    <div class="sr-card-body">
                                        <ol class="sr-guide">
                                            <li><?= __('gr_step_details', 'Fill in the title, company, target department, category and priority.') ?></li>
                                            <li><?= __('gr_step_items', 'Add each item you need with its quantity and specifications.') ?></li>
                                            <li><?= __('gr_step_draft', 'Save the request. It stays a draft until you submit it.') ?></li>
                                            <li><?= __('gr_step_approvers', 'On the next page, choose the approvers in order and submit for approval.') ?></li>
                                            <li><?= __('gr_step_delivery', 'Once approved, the target department delivers the items.') ?></li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <footer class="footer"><?= $site_footer ?></footer>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script src="assets/js/gr_item_suggest.js?v=<?= @filemtime(__DIR__ . '/assets/js/gr_item_suggest.js') ?>"></script>

    <script type="text/javascript">
    $(function () {
        const T = {
            select: <?= json_encode(__('select')) ?>,
            itemName: <?= json_encode(__('item_name', 'Item Name')) ?>,
            itemNamePh: <?= json_encode(__('enter_item_name', 'Enter item name')) ?>,
            itemType: <?= json_encode(__('item_type', 'Type/Category')) ?>,
            quantity: <?= json_encode(__('quantity')) ?>,
            specs: <?= json_encode(__('specifications', 'Specifications')) ?>,
            specsPh: <?= json_encode(__('item_specifications', 'Item specifications or requirements')) ?>,
            item: <?= json_encode(__('item', 'Item')) ?>,
            remove: <?= json_encode(__('remove', 'Remove')) ?>,
            addItem: <?= json_encode(__('add_new_item', 'Add New Item')) ?>,
            pickDeptFirst: <?= json_encode(__('select_target_department_first', 'Select the target department to see item types')) ?>,
            error: <?= json_encode(__('error')) ?>,
            success: <?= json_encode(__('success')) ?>,
            required: <?= json_encode(__('fill_required_fields_validation', 'Please fill all required fields')) ?>,
            oneItem: <?= json_encode(__('add_at_least_one_item', 'Please add at least one item to the request')) ?>,
            submitting: <?= json_encode(__('submitting', 'Submitting')) ?>,
            created: <?= json_encode(__('request_created_successfully', 'Request created successfully')) ?>,
            nextStep: <?= json_encode(__('gr_next_step', 'Saved as a draft. Next: choose approvers and submit.')) ?>,
            failed: <?= json_encode(__('request_creation_failed', 'Failed to create request')) ?>,
            submitError: <?= json_encode(__('request_submission_error', 'An error occurred while submitting the request')) ?>,
            filesChosen: <?= json_encode(__('files_selected', 'files selected')) ?>
        };
        const ITEM_TYPES = <?= json_encode(gr_item_types()) ?>;
        const $form = $('#generalRequestForm');
        const $items = $('#grItems');
        const saveBtnHtml = $('#submitBtn').html();

        function esc(str) {
            return $('<div>').text(str == null ? '' : String(str)).html();
        }

        function typeOptions(dept) {
            const types = ITEM_TYPES[dept] || [];
            let html = '<option value="">' + esc(dept ? T.select : T.pickDeptFirst) + '</option>';
            types.forEach(function (t) { html += '<option value="' + esc(t.v) + '">' + esc(t.l) + '</option>'; });
            return html;
        }

        // Item rows use the same line-card look as the Smart Request "New Request" popup
        function addItem(focus, $after) {
            const dept = $('#f_department_to').val();
            const $row = $(
                '<div class="sr-addline gr-item">' +
                    '<div class="sr-addline-head">' +
                        '<span class="sr-addline-no"></span>' +
                    '</div>' +
                    '<div class="sr-addline-grid">' +
                        '<div class="g-name"><label>' + esc(T.itemName) + ' <span class="text-danger">*</span></label>' +
                            '<input type="text" class="form-control" data-f="item_name" data-required="1" maxlength="250" autocomplete="off" placeholder="' + esc(T.itemNamePh) + '"></div>' +
                        '<div class="g-type"><label>' + esc(T.itemType) + '</label>' +
                            '<select class="form-control" data-f="item_type">' + typeOptions(dept) + '</select></div>' +
                        '<div class="g-qty"><label>' + esc(T.quantity) + ' <span class="text-danger">*</span></label>' +
                            '<input type="number" class="form-control" data-f="quantity" data-required="1" value="1" min="1" step="1"></div>' +
                        '<div class="g-act">' +
                            '<button type="button" class="sr-btn sr-btn-success gr-add" title="' + esc(T.addItem) + '"><i class="mdi mdi-plus"></i></button>' +
                            '<button type="button" class="sr-btn sr-btn-danger gr-remove" title="' + esc(T.remove) + '"><i class="mdi mdi-minus"></i></button>' +
                        '</div>' +
                        '<div class="g-full"><label>' + esc(T.specs) + '</label>' +
                            '<textarea class="form-control" data-f="specifications" rows="2" placeholder="' + esc(T.specsPh) + '"></textarea></div>' +
                    '</div>' +
                '</div>'
            );
            if ($after && $after.length) $after.after($row); else $items.append($row);
            renumber();
            if (focus) $row.find('[data-f="item_name"]').trigger('focus');
        }

        // Keeps names sequential (items[1..n][field]) and the first row non-removable
        function renumber() {
            const $rows = $items.children('.gr-item');
            $rows.each(function (i) {
                $(this).find('.sr-addline-no').text(i + 1);
                $(this).find('[data-f]').each(function () {
                    $(this).attr('name', 'items[' + (i + 1) + '][' + $(this).data('f') + ']');
                });
                // First row is never removable: hide its "-" (space kept so all rows stay aligned)
                $(this).find('.gr-remove').css('visibility', i === 0 ? 'hidden' : 'visible');
            });
            $('#grItemCount').text($rows.length);
        }

        $('#f_comp_no').each(function () {
            $(this).select2({ placeholder: $(this).data('placeholder'), width: '100%' });
        }).on('change', function () {
            $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
        });

        // Item Name auto-fill from earlier requests; picking one also fills Type and Specifications
        GRItemSuggest.bind($items, {
            selector: '[data-f="item_name"]',
            getDept: function () { return $('#f_department_to').val(); },
            onPick: function (it, $in) {
                const $row = $in.closest('.gr-item');
                const $type = $row.find('[data-f="item_type"]');
                if (it.type && $type.find('option').filter(function () { return this.value === it.type; }).length) $type.val(it.type);
                const $specs = $row.find('[data-f="specifications"]');
                if (it.specs && !$.trim($specs.val())) $specs.val(it.specs);
                $row.find('[data-f="quantity"]').trigger('focus').trigger('select');
            }
        });

        // + adds a row right below this one, - removes this row (the last remaining row stays)
        $items.on('click', '.gr-add', function () {
            addItem(true, $(this).closest('.gr-item'));
        });
        $items.on('click', '.gr-remove', function () {
            if ($(this).closest('.gr-item').index() === 0) return;
            $(this).closest('.gr-item').remove();
            renumber();
        });

        // Target department drives the item type list (keeps the current choice when still valid)
        $('#f_department_to').on('change', function () {
            const html = typeOptions($(this).val());
            $items.find('[data-f="item_type"]').each(function () {
                const cur = $(this).val();
                $(this).html(html);
                if (cur && $(this).find('option').filter(function () { return this.value === cur; }).length) $(this).val(cur);
            });
        });

        // Enter in an item field adds the next row; never submits the form
        $form.on('keydown', function (e) {
            if (e.key !== 'Enter' || $(e.target).is('textarea, button')) return;
            e.preventDefault();
            const $cur = $(e.target).closest('.gr-item');
            if ($cur.length) addItem(true, $cur);
        });

        // Multi-file picker: list the chosen files as chips
        const $pick = $form.find('.sr-filepick'), $file = $('#attachments'), $fname = $pick.find('.js-file-name');
        const pickText = $fname.text();
        function showFiles() {
            const files = $file[0].files || [];
            $pick.toggleClass('has-file', files.length > 0);
            $fname.text(files.length ? files.length + ' ' + T.filesChosen : pickText);
            $('#grFileList').html(Array.prototype.map.call(files, function (f) {
                return '<span class="sr-chip"><i class="mdi mdi-paperclip"></i>' + esc(f.name) + ' &middot; ' + (f.size / 1048576).toFixed(2) + ' MB</span>';
            }).join(''));
        }
        $file.on('change', showFiles);
        $pick.on('dragover dragenter', function (e) { e.preventDefault(); $pick.addClass('is-drag'); })
            .on('dragleave dragend drop', function () { $pick.removeClass('is-drag'); })
            .on('drop', function (e) {
                e.preventDefault();
                const dt = e.originalEvent.dataTransfer;
                if (dt && dt.files && dt.files.length) { $file[0].files = dt.files; showFiles(); }
            });

        $form.on('input change', '.is-invalid', function () { $(this).removeClass('is-invalid'); });

        function fail(text) {
            Swal.fire({ icon: 'error', title: T.error, text: text, allowOutsideClick: false });
        }

        $form.on('submit', function (e) {
            e.preventDefault();

            let $bad = $();
            $form.find('[data-required]').each(function () {
                if (!String($(this).val() || '').trim() || ($(this).attr('type') === 'number' && parseInt($(this).val(), 10) < 1)) {
                    $bad = $bad.add(this);
                }
            });
            $bad.addClass('is-invalid');
            // select2 hides the real <select>: mark its visible box instead
            $('#f_comp_no').each(function () {
                $(this).next('.select2-container').find('.select2-selection').toggleClass('is-invalid', $bad.is(this));
            });
            if ($items.children('.gr-item').length === 0) { fail(T.oneItem); return; }
            if ($bad.length) {
                if (!$bad.first().is('#f_comp_no')) $bad.first().trigger('focus');
                fail(T.required);
                return;
            }

            $('#submitBtn').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> ' + esc(T.submitting) + '...');

            const formData = new FormData(this);
            formData.append('action', 'create_general_request');

            $.ajax({
                url: './includes/ajaxFile/ajaxGeneralRequest.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (response) {
                if (response.success) {
                    Swal.fire({ icon: 'success', title: response.message || T.created, text: T.nextStep, timer: 1800, showConfirmButton: false, allowOutsideClick: false })
                        .then(function () { window.location.href = 'view_general_request.php?id=' + encodeURIComponent(response.inv_no); });
                } else {
                    fail(response.message || T.failed);
                    $('#submitBtn').prop('disabled', false).html(saveBtnHtml);
                }
            }).fail(function () {
                fail(T.submitError);
                $('#submitBtn').prop('disabled', false).html(saveBtnHtml);
            });
        });

        addItem(false);
    });
    </script>
</body>
</html>
