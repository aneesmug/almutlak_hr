<?php
// General Request print sheet (opened from view_general_request.php > Print and the list's row menu).
// Standalone A4 document like smt_print.php: no sidebar/topbar, always ONE page - fitSheet()
// below tightens and then shrinks the sheet when the content is taller than the printable area.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php'; // includes helper_functions.php
require_once __DIR__ . '/includes/general_request_helpers.php';

// Same page rules as view_general_request.php
if ($user_type == 'employee') {
    header('Location: dashboard.php');
    exit;
}

$inv_no = (string)($_GET['id'] ?? '');
$request_query = mysqli_query($conDB, "SELECT gr.*, d.dep_nme FROM general_requests gr
    LEFT JOIN department d ON d.id = gr.user_dept
    WHERE gr.inv_no = '" . escape_string($inv_no) . "' LIMIT 1");
$request = ($request_query && mysqli_num_rows($request_query)) ? mysqli_fetch_assoc($request_query) : null;
if (!$request || !gr_can_view_request($conDB, $request)) {
    header('Location: all_general_requests.php?error=request_not_found');
    exit;
}
$inv_no = $request['inv_no'];
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES); };

// Status / priority (same labels as the view page; .st-* colours below)
$st_class = ['tone-slate' => 'st-slate', 'tone-amber' => 'st-amber', 'tone-indigo' => 'st-indigo', 'tone-sky' => 'st-sky', 'tone-green' => 'st-green', 'tone-red' => 'st-red'];
[$status_label, $status_tone] = gr_statuses()[$request['current_status']] ?? [ucwords(str_replace('_', ' ', (string)$request['current_status'])), 'tone-slate'];
if ($request['current_status'] === 'pending_approval' && !empty($request['current_approval_level'])) {
    $status_label .= ' · ' . __('level', 'Level') . ' ' . (int)$request['current_approval_level'];
}
[$priority_label, $priority_tone] = gr_priorities()[$request['priority']] ?? [ucfirst((string)$request['priority']), 'tone-slate'];

gr_ensure_company_column($conDB);
$company_name = gr_company_name($conDB, $request['comp_no'] ?? 0);

// Items (same de-duplication as the view page)
$items = [];
$seen = [];
$items_query = mysqli_query($conDB, "SELECT `id`, `item_name`, `item_type`, `quantity`, `specifications`, `delivery_status` FROM general_request_items WHERE request_inv_no = '" . escape_string($inv_no) . "' ORDER BY id");
while ($items_query && ($it = mysqli_fetch_assoc($items_query))) {
    $key = md5($it['item_name'] . '|' . $it['quantity'] . '|' . $it['specifications']);
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $items[] = $it;
}
$total_qty = array_sum(array_map(function ($it) { return (int)$it['quantity']; }, $items));
$show_delivery = in_array($request['current_status'], ['completed', 'waiting_for_delivery'], true);
$delivery_map = gr_delivery_statuses();

// Approval chain
$approval_chain = [];
$type_id = gr_request_type_id($conDB);
$chain_query = mysqli_query($conDB, "SELECT ra.*, e.name AS approver_name FROM request_approvers ra
    LEFT JOIN employees e ON e.emp_id = ra.approver_id
    WHERE ra.request_inv_no = '" . escape_string($inv_no) . "' AND ra.request_type_id = " . (int)$type_id . "
    ORDER BY ra.approval_level ASC");
while ($chain_query && ($step = mysqli_fetch_assoc($chain_query))) {
    $approval_chain[] = $step;
}

// Delivery record
$delivery_info = null;
$delivery_query = mysqli_query($conDB, "SELECT d.*, e.name AS received_employee_name FROM general_request_deliveries d
    LEFT JOIN employees e ON e.emp_id = d.received_by
    WHERE d.request_inv_no = '" . escape_string($inv_no) . "' LIMIT 1");
if ($delivery_query && mysqli_num_rows($delivery_query) > 0) {
    $delivery_info = mysqli_fetch_assoc($delivery_query);
}

// Attachment names
$attachments = [];
$att_query = mysqli_query($conDB, "SELECT `attachment`, `attachment_type` FROM general_request_attachments WHERE request_inv_no = '" . escape_string($inv_no) . "' ORDER BY id");
while ($att_query && ($a = mysqli_fetch_assoc($att_query))) {
    $attachments[] = $a['attachment'];
}

// The 'logo' setting is the white sidebar logo; paper needs the colour one (as smt_print.php).
$print_logo = get_setting($conDB, 'print_logo');
if (!$print_logo) {
    $print_logo = file_exists(__DIR__ . '/assets/logo/logo.png') ? 'assets/logo/logo.png' : get_setting($conDB, 'white_logo');
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $h($inv_no) ?> - <?= __('print_general_request', 'Print General Request') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta content="Anees Afzal" name="author" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        html, body { margin: 0; padding: 0; }
        body {
            background: #e9edf3; color: #1e293b;
            font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans Arabic", sans-serif;
            font-size: 11px; line-height: 1.4;
        }

        /* ---------- Screen toolbar ---------- */
        .toolbar {
            position: sticky; top: 0; z-index: 5; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
            padding: 10px 16px; background: #fff; border-bottom: 1px solid #dde3ec;
        }
        .toolbar .spacer { flex: 1 1 auto; }
        .toolbar .fit-note { font-size: 12px; color: #64748b; }
        .tb-btn {
            display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 14px; border-radius: 8px;
            border: 1px solid #cbd5e1; background: #fff; color: #334155; font-size: 13px; font-weight: 600;
            text-decoration: none; cursor: pointer; font-family: inherit;
        }
        .tb-btn:hover { border-color: #6366f1; color: #4338ca; }
        .tb-btn.primary { background: #6366f1; border-color: #6366f1; color: #fff; }
        .tb-btn.primary:hover { background: #4f46e5; color: #fff; }

        /* ---------- A4 sheet ---------- */
        .page-wrap { padding: 24px 12px 40px; display: flex; justify-content: center; }
        .paper { background: #fff; box-shadow: 0 6px 30px rgba(15, 23, 42, .12); padding: 8mm; width: 210mm; min-height: 297mm; }
        .sheet { width: 194mm; transform-origin: top left; }

        /* Header */
        .doc-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding-bottom: 10px; border-bottom: 3px solid #4f46e5; }
        .doc-head img { max-height: 66px; max-width: 75mm; object-fit: contain; }
        .doc-title { text-align: end; }
        .doc-title h1 { margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; letter-spacing: .2px; }
        .doc-title .inv { margin-top: 3px; font-family: Consolas, "Courier New", monospace; font-size: 12px; font-weight: 700; color: #4338ca; }

        .status { display: inline-block; margin-top: 5px; padding: 2px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; border: 1px solid; }
        .status + .status { margin-inline-start: 4px; }
        .st-slate  { color: #475569; background: #f1f5f9; border-color: #cbd5e1; }
        .st-amber  { color: #b45309; background: #fffbeb; border-color: #fcd34d; }
        .st-indigo { color: #4338ca; background: #eef2ff; border-color: #a5b4fc; }
        .st-sky    { color: #0369a1; background: #f0f9ff; border-color: #7dd3fc; }
        .st-green  { color: #15803d; background: #f0fdf4; border-color: #86efac; }
        .st-red    { color: #b91c1c; background: #fef2f2; border-color: #fca5a5; }

        /* Subject + meta */
        .subject { margin: 10px 0 8px; font-size: 15px; font-weight: 700; color: #0f172a; }
        .meta { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
        .meta + .meta { margin-top: 4px; }
        .meta > div { padding: 6px 9px; border-inline-end: 1px solid #e2e8f0; background: #f8fafc; min-width: 0; }
        .meta > div:last-child { border-inline-end: 0; }
        .meta .k { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; }
        .meta .v { font-size: 11px; font-weight: 600; color: #0f172a; overflow-wrap: anywhere; }
        .remarks { margin-top: 6px; padding: 5px 9px; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 10.5px; }
        .remarks b { color: #0f172a; }

        .sec { margin: 12px 0 5px; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .7px; color: #4f46e5; }

        /* Items */
        table.items { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.items th {
            padding: 5px 6px; background: #1e293b; color: #fff; font-size: 8.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .3px; text-align: start; white-space: nowrap;
        }
        table.items td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.items tbody tr:nth-child(even) td { background: #f8fafc; }
        table.items .c { text-align: center; }
        table.items .item-name { font-weight: 600; color: #0f172a; }
        table.items .item-sub { font-size: 9px; color: #64748b; }
        table.items tfoot td { font-weight: 800; color: #0f172a; border-top: 2px solid #1e293b; border-bottom: 0; }
        table.items tr { page-break-inside: avoid; }
        .ds { display: inline-block; padding: 1px 7px; border-radius: 999px; font-size: 9px; font-weight: 700; border: 1px solid; }

        .attach { margin-top: 6px; font-size: 10px; color: #475569; }
        .attach b { color: #0f172a; }

        /* Approvals - signature style */
        .approvals { display: grid; grid-template-columns: repeat(auto-fill, minmax(44mm, 1fr)); gap: 6px; }
        .appr { border: 1px solid #e2e8f0; border-top: 3px solid #cbd5e1; border-radius: 6px; padding: 6px 8px; min-height: 18mm; position: relative; }
        .appr.approved { border-top-color: #16a34a; }
        .appr.rejected { border-top-color: #dc2626; }
        .appr.pending  { border-top-color: #f59e0b; }
        .appr .lvl { font-size: 8.5px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
        .appr .nm { font-size: 11px; font-weight: 700; color: #0f172a; }
        .appr .st { font-size: 9.5px; font-weight: 700; }
        .appr.approved .st { color: #15803d; }
        .appr.rejected .st { color: #b91c1c; }
        .appr.pending .st { color: #b45309; }
        .appr .dt { font-size: 9px; color: #64748b; }
        .appr .nt { margin-top: 3px; font-size: 9px; color: #334155; font-style: italic; overflow-wrap: anywhere; }
        .empty-note { padding: 6px 9px; border: 1px dashed #cbd5e1; border-radius: 6px; color: #64748b; font-size: 10px; }

        /* Delivery + receiver signature */
        .delivery { display: grid; grid-template-columns: repeat(3, 1fr) 1.3fr; border: 1px solid #86efac; border-radius: 6px; overflow: hidden; background: #f0fdf4; }
        .delivery > div { padding: 6px 9px; border-inline-end: 1px solid #bbf7d0; min-width: 0; }
        .delivery > div:last-child { border-inline-end: 0; }
        .delivery .k { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #15803d; }
        .delivery .v { font-size: 11px; font-weight: 600; color: #0f172a; overflow-wrap: anywhere; }
        .delivery .sign { min-height: 12mm; }

        .doc-foot { display: flex; justify-content: space-between; gap: 10px; margin-top: 12px; padding-top: 6px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #94a3b8; }

        /* Density steps used by fitSheet() before it falls back to zooming */
        .sheet.d1 { line-height: 1.25; }
        .sheet.d1 .doc-head img { max-height: 42px; }
        .sheet.d1 .subject { margin: 6px 0 5px; font-size: 13px; }
        .sheet.d1 .sec { margin: 7px 0 3px; }
        .sheet.d1 table.items td { padding: 2px 5px; }
        .sheet.d1 table.items th { padding: 3px 5px; }
        .sheet.d1 table.items .item-name { display: inline; }
        .sheet.d1 table.items .item-sub { display: inline; margin-inline-start: 6px; }
        .sheet.d1 .appr { min-height: 0; padding: 4px 7px; }
        .sheet.d2 table.items { font-size: 9px; }
        .sheet.d2 table.items td { padding: 1px 4px; }
        .sheet.d2 table.items th { font-size: 8px; padding: 2px 4px; }
        .sheet.d2 table.items .item-sub { font-size: 8px; }
        .sheet.d2 .meta > div, .sheet.d2 .delivery > div { padding: 3px 7px; }

        [dir="rtl"] .sheet { transform-origin: top right; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page-wrap { padding: 0; display: block; }
            .paper { box-shadow: none; padding: 0; width: auto; min-height: 0; }
            /* Belt and braces: whatever happens, never spill onto a second page. */
            .paper { height: 280mm; overflow: hidden; page-break-after: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="view_general_request.php?id=<?= urlencode($inv_no) ?>" class="tb-btn"><i class="mdi mdi-arrow-left"></i> <?= __('back_button') ?></a>
        <span class="spacer"></span>
        <span class="fit-note" id="fitNote"></span>
        <button type="button" class="tb-btn primary" onclick="window.print()"><i class="mdi mdi-printer"></i> <?= __('print') ?></button>
    </div>

    <div class="page-wrap">
        <div class="paper ad-keep">
            <div class="sheet" id="sheet">

                <div class="doc-head">
                    <div>
                        <img src="<?= $h($print_logo) ?>" alt="">
                    </div>
                    <div class="doc-title">
                        <h1><?= __('general_request', 'General Request') ?></h1>
                        <div class="inv"><?= $h($inv_no) ?></div>
                        <span class="status <?= $st_class[$status_tone] ?? 'st-slate' ?>"><?= $h($status_label) ?></span>
                        <span class="status <?= $st_class[$priority_tone] ?? 'st-slate' ?>"><?= $h($priority_label) ?></span>
                    </div>
                </div>

                <div class="subject"><?= $h(getDisplayName($request['request_title'])) ?></div>
                <div class="meta">
                    <div><div class="k"><?= __('company', 'Company') ?></div><div class="v"><?= $h($company_name ?: '-') ?></div></div>
                    <div><div class="k"><?= __('requester_department', 'Requester Department') ?></div><div class="v"><?= $h(getDisplayName($request['dep_nme']) ?: '-') ?></div></div>
                    <div><div class="k"><?= __('target_department', 'Target Department') ?></div><div class="v"><?= $h(gr_department_label($request['department_to']) ?: '-') ?></div></div>
                    <div><div class="k"><?= __('request_date', 'Request Date') ?></div><div class="v"><?= !empty($request['created_at']) ? date('d M Y', strtotime($request['created_at'])) : '-' ?></div></div>
                </div>
                <div class="meta">
                    <div><div class="k"><?= __('requester', 'Requester') ?></div><div class="v"><?= $h(getDisplayName($request['emp_name']) ?: '-') ?></div></div>
                    <div><div class="k"><?= __('category', 'Category') ?></div><div class="v"><?= $h(gr_category_label($request['request_category']) ?: '-') ?></div></div>
                    <div><div class="k"><?= __('priority', 'Priority') ?></div><div class="v"><?= $h($priority_label) ?></div></div>
                    <div><div class="k"><?= __('total_quantity', 'Total quantity') ?></div><div class="v"><?= $total_qty ?></div></div>
                </div>
                <?php if (!empty($request['description'])): ?>
                    <div class="remarks"><b><?= __('description', 'Description') ?>:</b> <?= nl2br($h(getDisplayName($request['description']))) ?></div>
                <?php endif; ?>

                <div class="sec"><?= __('requested_items', 'Requested Items') ?> (<?= count($items) ?>)</div>
                <table class="items">
                    <thead>
                        <tr>
                            <th style="width: 6mm;">#</th>
                            <th><?= __('item_name', 'Item Name') ?></th>
                            <th><?= __('item_type', 'Type/Category') ?></th>
                            <th><?= __('specifications', 'Specifications') ?></th>
                            <th class="c"><?= __('quantity') ?></th>
                            <?php if ($show_delivery): ?><th class="c"><?= __('delivery', 'Delivery') ?></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="<?= $show_delivery ? 6 : 5 ?>" class="c"><?= __('no_data_available_in_table') ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($items as $i => $it): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><div class="item-name"><?= $h(getDisplayName($it['item_name'])) ?></div></td>
                                <td><?= $h(getDisplayName($it['item_type']) ?: '-') ?></td>
                                <td><?= $it['specifications'] !== '' && $it['specifications'] !== null ? nl2br($h(getDisplayName($it['specifications']))) : '<span class="item-sub">&ndash;</span>' ?></td>
                                <td class="c"><b><?= (int)$it['quantity'] ?></b></td>
                                <?php if ($show_delivery):
                                    $ds = $it['delivery_status'] ?: 'pending';
                                    [$ds_label, $ds_tone] = $delivery_map[$ds] ?? [$ds, 'tone-slate']; ?>
                                    <td class="c"><span class="ds <?= $st_class[$ds_tone] ?? 'st-slate' ?>"><?= $h($ds_label) ?></span></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php if ($items): ?>
                        <tfoot>
                            <tr>
                                <td colspan="4"><?= __('total_quantity', 'Total quantity') ?></td>
                                <td class="c"><?= $total_qty ?></td>
                                <?php if ($show_delivery): ?><td></td><?php endif; ?>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
                <?php if ($attachments): ?>
                    <div class="attach"><b><?= __('attachments') ?> (<?= count($attachments) ?>):</b> <?= $h(implode(', ', $attachments)) ?></div>
                <?php endif; ?>

                <div class="sec"><?= __('approval_status') ?></div>
                <?php if (empty($approval_chain)): ?>
                    <div class="empty-note"><?= __('approval_chain_not_defined_yet') ?></div>
                <?php else: ?>
                    <div class="approvals">
                        <?php foreach ($approval_chain as $step):
                            $action_date = !empty($step['action_date']) ? date('d M Y, H:i', strtotime($step['action_date'])) : '';
                        ?>
                            <div class="appr <?= $h($step['status']) ?>">
                                <div class="lvl"><?= __('level') ?> <?= (int)$step['approval_level'] ?></div>
                                <div class="nm"><?= $h(getDisplayName($step['approver_name'])) ?></div>
                                <div class="st"><?= $h(__($step['status'])) ?></div>
                                <?php if ($action_date): ?><div class="dt"><?= $action_date ?></div><?php endif; ?>
                                <?php if (!empty($step['note'])): ?><div class="nt">"<?= $h($step['note']) ?>"</div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($delivery_info || $show_delivery || $request['current_status'] === 'approved'): ?>
                    <div class="sec"><?= __('delivery_details', 'Delivery Details') ?></div>
                    <div class="delivery">
                        <div><div class="k"><?= __('received_by', 'Received By') ?></div><div class="v"><?= $h(getDisplayName($delivery_info['received_employee_name'] ?? '') ?: '-') ?></div></div>
                        <div><div class="k"><?= __('date', 'Date') ?></div><div class="v"><?= !empty($delivery_info['delivery_date']) ? date('d M Y, H:i', strtotime($delivery_info['delivery_date'])) : '-' ?></div></div>
                        <div><div class="k"><?= __('delivered', 'Delivered') ?></div><div class="v"><?= count(array_filter($items, function ($it) { return $it['delivery_status'] === 'delivered'; })) ?> / <?= count($items) ?></div></div>
                        <div class="sign"><div class="k"><?= __('receiver_signature', 'Receiver signature') ?></div></div>
                    </div>
                <?php endif; ?>

                <div class="doc-foot">
                    <span><?= __('printed_by', 'Printed by') ?>: <?= $h(getDisplayName($userwel ?? '')) ?></span>
                    <span><?= __('printed_on', 'Printed on') ?>: <?= date('d M Y, H:i') ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Keep everything on one A4 page (same approach as smt_print.php): try normal, then compact
        // rows, then compact + smaller text, and only then shrink with CSS zoom.
        (function () {
            var MM = 96 / 25.4;
            var PAGE_W = 194 * MM;            // printable width
            var PAGE_H = 279 * MM;            // printable height minus a small safety gap
            var sheet = document.getElementById('sheet');
            var note = document.getElementById('fitNote');
            var useZoom = ('zoom' in sheet.style);

            function measureScale() {
                var scale = 1;
                for (var i = 0; i < 6; i++) {
                    sheet.style.width = (PAGE_W / scale) + 'px';
                    var h = sheet.scrollHeight;
                    if (h * scale <= PAGE_H) break;
                    scale = (PAGE_H / h) * 0.995;
                }
                sheet.style.width = (PAGE_W / scale) + 'px';
                return scale;
            }

            function fitSheet() {
                sheet.style.zoom = '';
                sheet.style.transform = '';
                if (sheet.parentNode) sheet.parentNode.style.height = '';
                var levels = ['', 'd1', 'd2'];
                var scale = 1;
                for (var l = 0; l < levels.length; l++) {
                    sheet.classList.remove('d1', 'd2');
                    if (levels[l] === 'd1') sheet.classList.add('d1');
                    if (levels[l] === 'd2') sheet.classList.add('d1', 'd2');
                    scale = measureScale();
                    if (scale >= 1) break;
                }
                if (scale < 1) {
                    if (useZoom) {
                        sheet.style.zoom = scale;
                    } else {
                        sheet.style.transform = 'scale(' + scale + ')';
                        sheet.parentNode.style.height = (sheet.scrollHeight * scale) + 'px';
                    }
                }
                if (note) {
                    note.textContent = scale < 1
                        ? <?= json_encode(__('print_scaled_to_fit', 'Scaled to fit one page:')) ?> + ' ' + Math.round(scale * 100) + '%'
                        : <?= json_encode(__('print_fits_one_page', 'Fits on one page')) ?>;
                }
            }

            window.addEventListener('beforeprint', fitSheet);
            window.addEventListener('load', function () {
                fitSheet();
                setTimeout(function () { window.print(); }, 250);
            });
        })();
    </script>
</body>
</html>
