<?php
// Prints the config + script for the shared Smart Request line editor
// (assets/js/smart_request_lines.js). Include after jQuery, from all_requests.php
// (New Request modal) and open_request.php (Add Line modal).
$srLinesConfig = [
    'vatRate' => (float)get_setting($conDB, 'vat'),
    't' => [
        'item'          => __('item_name'),
        'reference'     => __('reference', 'Reference'),
        'location'      => __('location'),
        'qty'           => __('quantity'),
        'price'         => __('unit_cost'),
        'vatOpt'        => __('vat_opt'),
        'include'       => __('include', 'Include'),
        'exclude'       => __('exclude', 'Exclude'),
        'noVat'         => __('no_vat', 'No VAT'),
        'discount'      => __('discount'),
        'lineTotal'     => __('total'),
        'addAnother'    => __('add_another_line', 'Add another line'),
        'net'           => __('net_total_without_vat'),
        'vat'           => __('vat_val'),
        'select'        => __('select'),
        'remove'        => __('remove', 'Remove'),
        'hint'          => __('add_line_shortcut_hint', 'Tip: press Ctrl + Enter to add another line.'),
    ],
];
?>
<script>window.SR_LINES_CONFIG = <?= json_encode($srLinesConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
<script src="assets/js/smart_request_lines.js?v=<?= @filemtime(__DIR__ . '/../assets/js/smart_request_lines.js') ?>"></script>
