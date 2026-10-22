<?php
// Shared lookups for the General Request pages (new_general_request.php, view_general_request.php).
// Stored values stay English (they are saved in general_requests / general_request_items);
// labels go through __() for display.

// Target departments: stored value => label
function gr_target_departments() {
    return [
        'IT'             => __('it_department', 'IT Department'),
        'HR'             => __('hr_department', 'HR Department'),
        'Transportation' => __('transportation_dept', 'Transportation'),
        'Finance'        => __('finance_department', 'Finance'),
        'Maintenance'    => __('maintenance_dept', 'Maintenance'),
        'Admin'          => __('admin_department', 'Admin'),
        'Other'          => __('other', 'Other'),
    ];
}

// Request categories: stored value => label
function gr_categories() {
    return [
        'Equipment'   => __('equipment', 'Equipment'),
        'Service'     => __('service', 'Service'),
        'Supplies'    => __('supplies', 'Supplies'),
        'Maintenance' => __('maintenance', 'Maintenance'),
        'Support'     => __('support', 'Support'),
        'Other'       => __('other', 'Other'),
    ];
}

// Priority: value => [label, sr tone]
function gr_priorities() {
    return [
        'low'    => [__('low_priority', 'Low'), 'tone-sky'],
        'medium' => [__('medium_priority', 'Medium'), 'tone-indigo'],
        'high'   => [__('high_priority', 'High'), 'tone-amber'],
        'urgent' => [__('urgent_priority', 'Urgent'), 'tone-red'],
    ];
}

// Item types per target department: [{v: stored value, l: label}]. Older requests stored
// 'Information Technology' as the department, so it maps to the IT list.
function gr_item_types() {
    $raw = [
        'IT'             => ['Laptop', 'Desktop', 'Monitor', 'Keyboard', 'Mouse', 'Printer', 'Printer Ink/Toner', 'Network Equipment', 'Software License', 'Other'],
        'Transportation' => ['Vehicle', 'Mobile Phone', 'SIM Card', 'Fuel Card', 'GPS Device', 'Other'],
        'HR'             => ['Stationery', 'Office Supplies', 'Furniture', 'ID Card', 'Badge', 'Other'],
        'Finance'        => ['Calculator', 'Receipt Book', 'Document Folder', 'Safe/Locker', 'Other'],
        'Maintenance'    => ['Tools', 'Equipment', 'Safety Gear', 'Cleaning Supplies', 'Other'],
        'Admin'          => ['Furniture', 'Office Equipment', 'Decoration', 'Other'],
        'Other'          => ['Other'],
    ];
    $out = [];
    foreach ($raw as $dept => $types) {
        $out[$dept] = array_map(function ($t) {
            $key = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $t));
            return ['v' => $t, 'l' => __(trim($key, '_'), $t)];
        }, $types);
    }
    $out['Information Technology'] = $out['IT'];
    return $out;
}

// Request status: value => [label, sr tone, mdi icon]
function gr_statuses() {
    return [
        'draft'                => [__('draft_status', 'Draft'), 'tone-slate', 'mdi-pencil'],
        'pending_approval'     => [__('pending_approval', 'Pending Approval'), 'tone-amber', 'mdi-timer-sand'],
        'approved'             => [__('approved', 'Approved'), 'tone-indigo', 'mdi-seal'],
        'waiting_for_delivery' => [__('waiting_for_delivery', 'Waiting for Delivery'), 'tone-sky', 'mdi-truck-delivery'],
        'completed'            => [__('completed', 'Completed'), 'tone-green', 'mdi-check-all'],
        'rejected'             => [__('rejected', 'Rejected'), 'tone-red', 'mdi-close-circle-outline'],
        'cancelled'            => [__('cancelled', 'Cancelled'), 'tone-slate', 'mdi-cancel'],
    ];
}

// Per-item delivery status: value => [label, sr tone, mdi icon]
function gr_delivery_statuses() {
    return [
        'delivered' => [__('delivered', 'Delivered'), 'tone-green', 'mdi-check-circle'],
        'pending'   => [__('pending', 'Pending'), 'tone-amber', 'mdi-clock'],
        'canceled'  => [__('canceled', 'Canceled'), 'tone-red', 'mdi-close-circle'],
    ];
}
