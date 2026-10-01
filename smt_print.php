<?php
// Smart Request print sheet (opened from open_request.php > Print).
// Standalone A4 document: no sidebar/topbar. Everything always prints on ONE page -
// fitSheet() below shrinks the sheet (CSS zoom) when the content is taller than the
// printable A4 area, e.g. requests with many lines or a long approval chain.
 require_once __DIR__ . '/includes/db.php';
 require_once __DIR__ . '/includes/session_check.php'; // includes helper_functions.php
 include("./includes/convertNumbersToWords.php");
 $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
 if(mysqli_num_rows($query) == 1){
 include("./includes/avatar_select.php");

 $getquery = mysqli_query($conDB, "SELECT
            `smt`.*,
            SUM(`smt`.`total_cost`) as subtotal,
            SUM(`smt`.`vat_val`) as vat_val,
            MAX(`smt`.`created_at`) as latest_created_at,
            `dpt`.`dep_nme`
            FROM `smart_request` `smt`
            LEFT JOIN `department` `dpt` ON `dpt`.`id` = `smt`.`department`
            WHERE `smt`.`inv_no`='" . escape_string($_GET['id'] ?? '') . "'
            GROUP BY `smt`.`inv_no`");

 if ($getquery && mysqli_num_rows($getquery) !== 0) {
    while($row = mysqli_fetch_assoc($getquery)){
        $invnoget = $row["inv_no"];
        $createdatget = $row["latest_created_at"];
        $subtypeget = $row["sub_type"];
        $sub_title_get = $row["sub_title"];
        $prep_parts = explode(" ", (string)$row["prep_by"]);
        $prep_by_get = trim(($prep_parts[0] ?? '') . " " . ($prep_parts[1] ?? ''));
        $department_get = $row["dep_nme"];
        $remarks_get = $row["remarks"];

        $total_costget = $row['subtotal'];
        $vat_get = $row['vat_val'];
        $discount_get = $row["discount"];

        $current_status_get = $row['current_status'];
        $current_approval_level_get = $row['current_approval_level'];
        $payable_by_emp_id_get = $row['payable_by_emp_id'];

        $total_cost_get = $total_costget - $vat_get;
        $total = $total_cost_get + $vat_get;
        $gtotal = $total - $discount_get;

        $created_at_get = $createdatget ? date('d M Y', strtotime($createdatget)) : '-';

        $assigned_payer_name = null;
        if ($payable_by_emp_id_get) {
            $payerDetails = getEmployeeDetails($conDB, $payable_by_emp_id_get);
            if ($payerDetails && $payerDetails['name'] !== 'N/A') {
                $assigned_payer_name = $payerDetails['name'];
            }
        }
    }
 } else {
    header("Location: ./all_requests.php?error=request_not_found");
    exit;
 }

    // Status label + colour class (.st-*)
    switch ($current_status_get) {
        case "draft":
            $status_label = __('draft_status'); $status_class = 'st-slate'; break;
        case "pending_approval":
            $status_label = __('pending_approval_level') . " " . $current_approval_level_get; $status_class = 'st-amber'; break;
        case "approved":
            $status_label = $assigned_payer_name ? __('approved_pending_payment') : __('approved_pending_assignment'); $status_class = 'st-indigo'; break;
        case "pending_payment":
            $status_label = __('ready_for_payment', 'Ready for Payment'); $status_class = 'st-sky'; break;
        case "rejected":
            $status_label = __('rejected'); $status_class = 'st-red'; break;
        case "paid":
            $status_label = __('payment_paid'); $status_class = 'st-green'; break;
        case "cancelled":
            $status_label = __('cancelled', 'Cancelled'); $status_class = 'st-slate'; break;
        default:
            $status_label = __('unknown_status'); $status_class = 'st-red';
    }

    $approval_chain = get_approval_chain_status($conDB, $invnoget, 'smart_request') ?: [];

    $line_items = [];
    $getdataloop = mysqli_query($conDB, "SELECT * FROM `smart_request` WHERE `inv_no`='" . escape_string($invnoget) . "' ORDER BY `id` ASC");
    while ($getdataloop && ($rec = mysqli_fetch_assoc($getdataloop))) {
        $line_items[] = $rec;
    }

    $attachments = [];
    $queryempdocu = mysqli_query($conDB, "SELECT `attachment` FROM `smt_attachment` WHERE `inv_no`='" . escape_string($invnoget) . "' ");
    while ($queryempdocu && ($recempdoc = mysqli_fetch_assoc($queryempdocu))) {
        $attachments[] = $recempdoc['attachment'];
    }

    $payment_details = null;
    if ($current_status_get == 'paid') {
        $payment_query = mysqli_query($conDB, "SELECT * FROM `smt_payment` WHERE `inv_no` = '" . escape_string($invnoget) . "' ORDER BY `id` DESC LIMIT 1");
        if ($payment_query && mysqli_num_rows($payment_query) > 0) {
            $payment_details = mysqli_fetch_assoc($payment_query);
        }
    }

    $fmt = function ($n) { return number_format((float)$n, 2); };

    // Opening cash comes only from the URL (typed on open_request.php, never stored).
    $opening_cash = null;
    $cash_balance = null;
    if (isset($_GET['opening']) && is_numeric($_GET['opening'])) {
        $opening_cash = round((float)$_GET['opening'], 2);
        $cash_balance = round($opening_cash - (float)$gtotal, 2);
    }
    // The 'logo' setting is the white sidebar logo; paper needs the colour one.
    $print_logo = get_setting($conDB, 'print_logo');
    if (!$print_logo) {
        $print_logo = file_exists(__DIR__ . '/assets/logo/logo.png') ? 'assets/logo/logo.png' : get_setting($conDB, 'white_logo');
    }
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($invnoget) ?> - <?= __('print_smart_request') ?></title>
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
        .st-slate  { color: #475569; background: #f1f5f9; border-color: #cbd5e1; }
        .st-amber  { color: #b45309; background: #fffbeb; border-color: #fcd34d; }
        .st-indigo { color: #4338ca; background: #eef2ff; border-color: #a5b4fc; }
        .st-sky    { color: #0369a1; background: #f0f9ff; border-color: #7dd3fc; }
        .st-green  { color: #15803d; background: #f0fdf4; border-color: #86efac; }
        .st-red    { color: #b91c1c; background: #fef2f2; border-color: #fca5a5; }

        /* Subject + meta */
        .subject { margin: 10px 0 8px; font-size: 15px; font-weight: 700; color: #0f172a; }
        .meta { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
        .meta > div { padding: 6px 9px; border-inline-end: 1px solid #e2e8f0; background: #f8fafc; min-width: 0; }
        .meta > div:last-child { border-inline-end: 0; }
        .meta .k { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; }
        .meta .v { font-size: 11px; font-weight: 600; color: #0f172a; overflow-wrap: anywhere; }
        .remarks { margin-top: 6px; padding: 5px 9px; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 10.5px; }
        .remarks b { color: #0f172a; }

        /* Section titles */
        .sec { margin: 12px 0 5px; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .7px; color: #4f46e5; }

        /* Items */
        table.items { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.items th {
            padding: 5px 6px; background: #1e293b; color: #fff; font-size: 8.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .3px; text-align: start; white-space: nowrap;
        }
        table.items td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.items tbody tr:nth-child(even) td { background: #f8fafc; }
        table.items .n { text-align: end; white-space: nowrap; font-variant-numeric: tabular-nums; }
        table.items .c { text-align: center; }
        table.items .item-name { font-weight: 600; color: #0f172a; }
        table.items .item-sub { font-size: 9px; color: #64748b; }
        table.items tr { page-break-inside: avoid; }

        /* Totals */
        .bottom { display: grid; grid-template-columns: 1fr 72mm; gap: 12px; margin-top: 8px; align-items: start; }
        .words { padding: 7px 10px; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 10.5px; }
        .words .k { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; display: block; margin-bottom: 2px; }
        .words b { color: #0f172a; text-transform: capitalize; }
        .attach { margin-top: 6px; font-size: 10px; color: #475569; }
        .attach b { color: #0f172a; }
        .totals { border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
        .totals .r { display: flex; justify-content: space-between; gap: 10px; padding: 4px 10px; font-size: 10.5px; border-bottom: 1px solid #eef2f7; }
        .totals .r span:last-child { font-weight: 600; font-variant-numeric: tabular-nums; }
        .totals .r.grand { background: #4f46e5; color: #fff; border-bottom: 0; padding: 7px 10px; font-size: 13px; font-weight: 800; }
        .totals .r.grand span:last-child { font-weight: 800; }
        .totals .r.cash { border-top: 1px dashed #cbd5e1; }
        .totals .r.balance { background: #f0fdf4; color: #15803d; font-weight: 800; font-size: 12px; border-bottom: 0; }
        .totals .r.balance.neg { background: #fef2f2; color: #b91c1c; }

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

        /* Payment */
        .payment { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid #86efac; border-radius: 6px; overflow: hidden; background: #f0fdf4; }
        .payment > div { padding: 6px 9px; border-inline-end: 1px solid #bbf7d0; min-width: 0; }
        .payment > div:last-child { border-inline-end: 0; }
        .payment .k { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #15803d; }
        .payment .v { font-size: 11px; font-weight: 600; color: #0f172a; overflow-wrap: anywhere; }

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
        .sheet.d1 .bottom { margin-top: 5px; }
        .sheet.d1 .totals .r { padding: 2px 10px; }
        .sheet.d2 table.items { font-size: 9px; }
        .sheet.d2 table.items td { padding: 1px 4px; }
        .sheet.d2 table.items th { font-size: 8px; padding: 2px 4px; }
        .sheet.d2 table.items .item-sub { font-size: 8px; }
        .sheet.d2 .meta > div, .sheet.d2 .payment > div { padding: 3px 7px; }

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
        <a href="open_request.php?id=<?= urlencode($invnoget) ?>" class="tb-btn"><i class="mdi mdi-arrow-left"></i> <?= __('back_button') ?></a>
        <span class="spacer"></span>
        <span class="fit-note" id="fitNote"></span>
        <button type="button" class="tb-btn primary" onclick="window.print()"><i class="mdi mdi-printer"></i> <?= __('print') ?></button>
    </div>

    <div class="page-wrap">
        <div class="paper ad-keep">
            <div class="sheet" id="sheet">

                <div class="doc-head">
                    <div>
                        <img src="<?= htmlspecialchars($print_logo) ?>" alt="">
                    </div>
                    <div class="doc-title">
                        <h1><?= __('smart_table_request') ?></h1>
                        <div class="inv"><?= htmlspecialchars($invnoget) ?></div>
                        <span class="status <?= $status_class ?>"><?= htmlspecialchars($status_label) ?></span>
                    </div>
                </div>

                <div class="subject"><?= htmlspecialchars($sub_title_get) ?></div>
                <div class="meta">
                    <div><div class="k"><?= __('request_date') ?></div><div class="v"><?= $created_at_get ?></div></div>
                    <div><div class="k"><?= __('subject_type') ?></div><div class="v"><?= htmlspecialchars($subtypeget) ?></div></div>
                    <div><div class="k"><?= __('department') ?></div><div class="v"><?= htmlspecialchars($department_get) ?></div></div>
                    <div><div class="k"><?= __('prepared_by') ?></div><div class="v"><?= htmlspecialchars($prep_by_get) ?></div></div>
                </div>
                <?php if ($remarks_get): ?>
                    <div class="remarks"><b><?= __('remarks') ?>:</b> <?= nl2br(htmlspecialchars($remarks_get)) ?></div>
                <?php endif; ?>

                <div class="sec"><?= __('items', 'Items') ?> (<?= count($line_items) ?>)</div>
                <table class="items">
                    <thead>
                        <tr>
                            <th style="width: 6mm;">#</th>
                            <th><?= __('description_item_name_invoice_num') ?></th>
                            <th class="c"><?= __('quantity') ?></th>
                            <th class="n"><?= __('unit_cost') ?></th>
                            <th class="n"><?= __('item_value') ?></th>
                            <th class="n"><?= __('vat_val') ?></th>
                            <th class="n"><?= __('amount') ?></th>
                            <th class="n"><?= __('discount') ?></th>
                            <th class="n"><?= __('total') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $x = 1; foreach ($line_items as $rec):
                            $sub_bits = array_filter([trim((string)($rec['reference'] ?? '')), trim((string)$rec['location'])]);
                        ?>
                            <tr>
                                <td><?= $x++ ?></td>
                                <td>
                                    <div class="item-name"><?= htmlspecialchars($rec["item_name"]) ?></div>
                                    <?php if ($sub_bits): ?><div class="item-sub"><?= htmlspecialchars(implode(' · ', $sub_bits)) ?></div><?php endif; ?>
                                </td>
                                <td class="c"><?= htmlspecialchars($rec["quantity"]) ?></td>
                                <td class="n"><?= $fmt($rec["product_price"]) ?></td>
                                <td class="n"><?= $fmt($rec["itmvalue"]) ?></td>
                                <td class="n"><?= $fmt($rec["vat_val"]) ?> <span class="item-sub">(<?= (float)$rec["vat_rate"] ?>%)</span></td>
                                <td class="n"><?= $fmt($rec["amount"]) ?></td>
                                <td class="n"><?= (float)$rec["idiscount"] > 0 ? $fmt($rec["idiscount"]) : '&ndash;' ?></td>
                                <td class="n"><b><?= $fmt($rec["total_cost"]) ?></b></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="bottom">
                    <div>
                        <div class="words">
                            <span class="k"><?= __('amount_in_words', 'Amount in words') ?></span>
                            <b><?= htmlspecialchars(trim(getSaudiCurrency($gtotal)) ?: '-') ?></b>
                        </div>
                        <?php if ($attachments): ?>
                            <div class="attach"><b><?= __('attachments') ?> (<?= count($attachments) ?>):</b> <?= htmlspecialchars(implode(', ', $attachments)) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="totals">
                        <div class="r"><span><?= __('net_total_without_vat') ?></span><span><?= $fmt($total_cost_get) ?></span></div>
                        <div class="r"><span><?= __('vat_15_percent') ?></span><span><?= $fmt($vat_get) ?></span></div>
                        <div class="r"><span><?= __('total_before_disc') ?></span><span><?= $fmt($total) ?></span></div>
                        <div class="r"><span><?= __('discount') ?></span><span><?= $fmt($discount_get) ?></span></div>
                        <div class="r grand"><span><?= __('grand_total') ?></span><span><?= $fmt($gtotal) ?> <?= __('sar') ?></span></div>
                        <?php if ($opening_cash !== null): ?>
                            <div class="r cash"><span><?= __('opening_cash', 'Opening Cash') ?></span><span><?= $fmt($opening_cash) ?></span></div>
                            <div class="r balance<?= $cash_balance < 0 ? ' neg' : '' ?>"><span><?= __('balance', 'Balance') ?></span><span><?= $fmt($cash_balance) ?> <?= __('sar') ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sec"><?= __('approval_status') ?></div>
                <?php if (empty($approval_chain)): ?>
                    <div class="empty-note"><?= __('approval_chain_not_defined_yet') ?></div>
                <?php else: ?>
                    <div class="approvals">
                        <?php foreach ($approval_chain as $step):
                            $action_date = $step['action_date'] ? date('d M Y, H:i', strtotime($step['action_date'])) : '';
                        ?>
                            <div class="appr <?= htmlspecialchars($step['status']) ?>">
                                <div class="lvl"><?= __('level') ?> <?= (int)$step['approval_level'] ?></div>
                                <div class="nm"><?= parseName($step['approver_name']) ?></div>
                                <div class="st"><?= __($step['status']) ?></div>
                                <?php if ($action_date): ?><div class="dt"><?= $action_date ?></div><?php endif; ?>
                                <?php if ($step['note']): ?><div class="nt">"<?= htmlspecialchars($step['note']) ?>"</div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($assigned_payer_name): ?>
                            <div class="appr <?= $current_status_get == 'paid' ? 'approved' : 'pending' ?>">
                                <div class="lvl"><?= __('payable_assigned_to') ?></div>
                                <div class="nm"><?= htmlspecialchars(parseName($assigned_payer_name)) ?></div>
                                <div class="st"><?= $current_status_get == 'paid' ? __('paid_status') : __('ready_for_payment', 'Ready for Payment') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($current_status_get == 'paid'): ?>
                    <div class="sec"><?= __('payment_information') ?></div>
                    <?php if ($payment_details): ?>
                        <div class="payment">
                            <div><div class="k"><?= __('paid_amount') ?></div><div class="v"><?= $fmt($payment_details['paid_amount']) ?> <?= __('sar') ?></div></div>
                            <div><div class="k"><?= __('paid_by') ?></div><div class="v"><?= htmlspecialchars($payment_details['paid_by_name']) ?></div></div>
                            <div><div class="k"><?= __('on') ?></div><div class="v"><?= date('d M Y, H:i', strtotime($payment_details['created_at'])) ?></div></div>
                            <div><div class="k"><?= __('payment_invoice_receipt') ?></div><div class="v"><?= htmlspecialchars($payment_details['payment_invoice']) ?></div></div>
                        </div>
                        <?php if ($payment_details['note']): ?>
                            <div class="remarks"><b><?= __('note') ?>:</b> <?= htmlspecialchars($payment_details['note']) ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-note"><?= __('payment_details_not_found') ?></div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="doc-foot">
                    <span><?= __('printed_by', 'Printed by') ?>: <?= htmlspecialchars($userwel ?? '') ?></span>
                    <span><?= __('printed_on', 'Printed on') ?>: <?= date('d M Y, H:i') ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Keep everything on one A4 page: if the sheet is taller than the printable area
        // (297mm - 2 x 8mm margin), shrink it with CSS zoom. The sheet is widened by the same
        // factor first so it still fills the page width after shrinking; repeated a few times
        // because a wider sheet wraps less and gets shorter.
        (function () {
            var MM = 96 / 25.4;
            var PAGE_W = 194 * MM;            // printable width
            var PAGE_H = 279 * MM;            // printable height minus a small safety gap
            var sheet = document.getElementById('sheet');
            var note = document.getElementById('fitNote');
            var useZoom = ('zoom' in sheet.style);

            // Scale needed to fit at the current density (widening the sheet as it shrinks).
            function measureScale() {
                var scale = 1;
                for (var i = 0; i < 6; i++) {
                    sheet.style.width = (PAGE_W / scale) + 'px';
                    var h = sheet.scrollHeight; // unscaled height at this width
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
                // 1) normal, 2) compact rows, 3) compact + smaller text - then zoom what is left.
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
<?php } ?>
