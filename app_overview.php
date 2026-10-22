<?php
require_once(__DIR__ . "/includes/init.php");
require_once(__DIR__ . "/includes/session_check.php");

// Short functional overview of the whole system (one page, anchor menu on top).
// Static content: edit $journey / $sections to add or change modules.
// Item = [name, description, Font Awesome 4.7 icon].

// The employee's path through the system, in order. Each step opens its module below.
$journey = [
    ['employees',  'Join',       'Profile, contract and documents are registered.'],
    ['attendance', 'Clock in',   'Punches arrive from the fingerprint devices.'],
    ['leave',      'Take leave', 'Vacation is requested, approved and deducted from balance.'],
    ['requests',   'Ask',        'Trips, loans, transfers and purchases go through approvals.'],
    ['payroll',    'Get paid',   'Payroll collects all of the above into the monthly salary.'],
    ['exit',       'Leave',      'Resignation, final settlement and end of service.'],
];

$sections = [
    'employees' => [
        'icon' => 'fa-users', 'c' => '#6366f1', 'c2' => '#4338ca', 'title' => 'Employees',
        'sub'  => 'Everything HR keeps about a person, from the first day.',
        'items' => [
            ['Employee register', 'Add company or manpower employees with job, contract, bank and photo.', 'fa-user-plus'],
            ['Employee profile', 'One page for documents, salary, vacations, loans, memos, warnings and assets.', 'fa-id-card'],
            ['Documents & expiry', 'Iqama, passport and other papers, with alerts before they expire.', 'fa-file-text'],
            ['Organisation', 'Companies, departments, job titles, cost centers and supervisors.', 'fa-sitemap'],
            ['Evaluations & memos', 'Yearly evaluations the employee acknowledges, plus memos and warnings.', 'fa-star'],
            ['Holidays', 'Public holiday calendar used by vacation and attendance.', 'fa-calendar'],
        ],
    ],
    'attendance' => [
        'icon' => 'fa-clock-o', 'c' => '#0ea5e9', 'c2' => '#0369a1', 'title' => 'Attendance',
        'sub'  => 'Fingerprint punches, collected without manual entry.',
        'items' => [
            ['Devices', 'ZKTeco devices with online / offline status and automatic sync.', 'fa-hdd-o'],
            ['Attendance sheet', 'Daily in and out times, late and absent, by employee or department.', 'fa-table'],
            ['Transactions', 'The raw punch log, searchable.', 'fa-list'],
            ['History', 'Attendance history per employee for HR and supervisors.', 'fa-history'],
        ],
    ],
    'leave' => [
        'icon' => 'fa-plane', 'c' => '#10b981', 'c2' => '#047857', 'title' => 'Vacations',
        'sub'  => 'Leave requests and the balance behind them.',
        'items' => [
            ['Vacation requests', 'The employee applies; the request follows the approval chain.', 'fa-paper-plane'],
            ['Balances', 'Balance grows daily, with a full history and an opening-balance import.', 'fa-balance-scale'],
            ['Rejoin', 'Return-to-work confirmation after the vacation ends.', 'fa-undo'],
            ['Schedule', 'Planned, running and completed vacations.', 'fa-calendar-check-o'],
            ['Old records', 'Enter past vacations for employees moved from another system.', 'fa-archive'],
        ],
    ],
    'requests' => [
        'icon' => 'fa-check-square-o', 'c' => '#f43f5e', 'c2' => '#be123c', 'title' => 'Requests & approvals',
        'sub'  => 'Each request type has its own approval chain.',
        'flow' => true,
        'items' => [
            ['Smart request', 'Purchase and service requests with lines, attachments and QR print.', 'fa-shopping-cart'],
            ['General request', 'Any written request to HR or management.', 'fa-envelope'],
            ['Business trip', 'Trip request with daily allowance and settlement after return.', 'fa-suitcase'],
            ['Loans', 'Loan request, monthly installments and payment proofs.', 'fa-credit-card'],
            ['Salary increment', 'Increment request with approval and history.', 'fa-line-chart'],
            ['Transfers', 'Move an employee to another department or location, permanent or temporary.', 'fa-exchange'],
            ['Approval inbox', 'One list per request type with status tabs, comments and history.', 'fa-inbox'],
        ],
    ],
    'payroll' => [
        'icon' => 'fa-money', 'c' => '#f59e0b', 'c2' => '#b45309', 'title' => 'Payroll',
        'sub'  => 'The monthly salary run and the money around it.',
        'items' => [
            ['Monthly payroll', 'Salary with allowances, deductions, loan installments and vacation rules, approved before release.', 'fa-money'],
            ['Payslips', 'One or all payslips as PDF or Excel.', 'fa-file-pdf-o'],
            ['Vouchers', 'Payment vouchers with attachments, ready to print.', 'fa-ticket'],
            ['Employees bank', 'Yearly end-of-service provision and salary report.', 'fa-university'],
            ['Loan checks', 'Tools that match loan installments against payroll.', 'fa-check-circle'],
        ],
    ],
    'exit' => [
        'icon' => 'fa-sign-out', 'c' => '#8b5cf6', 'c2' => '#6d28d9', 'title' => 'Exit',
        'sub'  => 'Closing an employee file correctly.',
        'items' => [
            ['Resignations', 'Resignation request with approvals and a report.', 'fa-sign-out'],
            ['Final settlement', 'Settlement of remaining salary, vacation, loans and assets.', 'fa-handshake-o'],
            ['End of service', 'End-of-service calculation with a printable statement.', 'fa-calculator'],
            ['Asset return', 'Check that every assigned asset came back.', 'fa-cubes'],
        ],
    ],
    'operations' => [
        'icon' => 'fa-truck', 'c' => '#f97316', 'c2' => '#c2410c', 'title' => 'Assets & operations',
        'sub'  => 'Company property and field work.',
        'items' => [
            ['Cars', 'Fleet, car documents, drivers and handover.', 'fa-car'],
            ['Locations', 'Sites and their contracts.', 'fa-map-marker'],
            ['Machines', 'Machines per location, transfers and replacements.', 'fa-cogs'],
            ['Asset inventory', 'Assets handed to employees.', 'fa-barcode'],
            ['Customers', 'Customers, cards, orders, quotations and invoices.', 'fa-briefcase'],
        ],
    ],
    'reports' => [
        'icon' => 'fa-bar-chart', 'c' => '#14b8a6', 'c2' => '#0f766e', 'title' => 'Reports',
        'sub'  => 'Numbers for management, exported in one click.',
        'items' => [
            ['Dashboard', 'Head-count, pending approvals, expiring documents and birthdays.', 'fa-tachometer'],
            ['Reports', 'Vacation, loan, payroll, trip, resignation and salary reports in Excel or PDF.', 'fa-file-excel-o'],
            ['Charts', 'The same figures by company, department and status.', 'fa-pie-chart'],
            ['Announcements', 'Send a message by email and in-app notification.', 'fa-bullhorn'],
        ],
    ],
    'security' => [
        'icon' => 'fa-shield', 'c' => '#3b82f6', 'c2' => '#1d4ed8', 'title' => 'Security & settings',
        'sub'  => 'Who can open what, and a record of what they did.',
        'items' => [
            ['Users & roles', 'Access per page by role, plus special and time-limited access.', 'fa-users'],
            ['Sign-in', 'Microsoft account sign-in and Google Authenticator codes.', 'fa-key'],
            ['Activity log', 'Every change is logged; live sessions can be watched and signed out.', 'fa-eye'],
            ['Settings', 'Company details, approval chains, payroll rules and request limits.', 'fa-sliders'],
            ['Language', 'English and Arabic, right-to-left included.', 'fa-language'],
            ['Data import', 'Excel import for loans, medical insurance and iqama dates.', 'fa-upload'],
        ],
    ],
    'integrations' => [
        'icon' => 'fa-plug', 'c' => '#84cc16', 'c2' => '#4d7c0f', 'title' => 'Connected systems',
        'sub'  => 'Outside systems this app talks to.',
        'items' => [
            ['ZKTeco BioTime', 'Attendance punches imported automatically.', 'fa-hand-pointer-o'],
            ['Microsoft Dynamics 365', 'Employee data compare, financial dimensions and payroll export.', 'fa-windows'],
            ['Recruitment portal', 'Job board, candidates and hiring.', 'fa-user-circle'],
            ['Email & QR codes', 'Email notifications and QR codes on printed documents.', 'fa-qrcode'],
        ],
    ],
];
$h = fn($v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$cv = fn($s) => '--c:' . $s['c'] . ';--c2:' . $s['c2'];
$total = array_sum(array_map(fn($s) => count($s['items']), $sections));

// Data for the JS widgets (journey panel, donut chart).
$jsData = [];
foreach ($sections as $id => $s) {
    $jsData[$id] = ['title' => $s['title'], 'sub' => $s['sub'], 'icon' => $s['icon'], 'c' => $s['c'], 'c2' => $s['c2'],
                    'items' => array_map(fn($i) => [$i[0], $i[2]], $s['items'])];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>National Advanced Solutions (NAS) - Application Overview</title>
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        html { scroll-behavior: smooth; scroll-padding-top: 72px; }
        body.sr-standalone { font-family: "Rubik", system-ui, sans-serif; }
        .ao a:focus-visible, .ao button:focus-visible, .ao input:focus-visible { outline: 2px solid var(--c, var(--sr-accent)); outline-offset: 2px; }
        .ao .soft { background: color-mix(in srgb, var(--c) 12%, var(--sr-surface)); }

        /* top menu */
        .ao-nav { position: sticky; top: 0; z-index: 50; background: color-mix(in srgb, var(--sr-surface) 88%, transparent);
                  backdrop-filter: blur(10px); border-bottom: 1px solid var(--sr-border); }
        .ao-nav-in { max-width: 1240px; margin: 0 auto; padding: 0 20px; display: flex; align-items: center; gap: 16px; height: 60px; }
        .ao-nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; flex: none; }
        .ao-nav-brand img { height: 40px; display: block; }
        .ao-nav-brand span { font-size: 13px; font-weight: 700; line-height: 1.2; color: #0f5a40; }
        .ao-nav-brand span small { display: block; font-size: 11px; font-weight: 500; color: #7a5aa3; }
        html.app-dark .ao-nav-brand span { color: #6fbf92; }
        @media (max-width: 1279px) { .ao-nav-brand span { display: none; } }
        @media (max-width: 575px) { .ao-brand-plate img { height: 64px; } }
        .ao-nav-links { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; flex: 1; padding: 4px 0; }
        .ao-nav-links::-webkit-scrollbar { display: none; }
        .ao-nav-links a { display: inline-flex; align-items: center; gap: 7px; padding: 6px 12px; border-radius: 999px; font-size: 13px; font-weight: 500;
                          color: var(--sr-text-2); text-decoration: none; white-space: nowrap; border: 1px solid transparent; transition: background .15s, color .15s; }
        .ao-nav-links a i { width: 8px; height: 8px; border-radius: 50%; background: var(--c); }
        .ao-nav-links a:hover { background: color-mix(in srgb, var(--c) 12%, transparent); }
        .ao-nav-links a.active { background: var(--c); color: #fff; }
        .ao-nav-links a.active i { background: #fff; }

        .ao-wrap { max-width: 1240px; margin: 0 auto; padding: 32px 20px 64px; }

        /* hero: text + animated module hub */
        .ao-hero { position: relative; display: grid; grid-template-columns: 1fr 480px; gap: 32px; align-items: center; padding: 40px;
                   border-radius: 24px; overflow: hidden; color: #fff; margin-bottom: 28px;
                   background: radial-gradient(circle at 10% 15%, #2f8a5c 0, transparent 40%), radial-gradient(circle at 88% 12%, #9d80c0 0, transparent 42%),
                               radial-gradient(circle at 75% 100%, #10b981 0, transparent 38%), radial-gradient(circle at 18% 100%, #7a5aa3 0, transparent 40%),
                               linear-gradient(135deg, #0b5136, #5b3f86); }
        .ao-brand-plate { display: inline-block; background: #fff; border-radius: 18px; padding: 12px 18px; margin-bottom: 22px;
                          box-shadow: 0 10px 30px rgba(15,23,42,.25); }
        .ao-brand-plate img { height: 92px; display: block; }
        .ao-hub-core b { font-size: 26px; letter-spacing: .04em; color: #0f5a40; }
        .ao-hub-core small { font-size: 11px; font-weight: 600; color: #7a5aa3; }
        .ao-foot { display: flex; align-items: center; justify-content: center; gap: 14px; margin-top: 48px; padding-top: 24px;
                   border-top: 1px solid var(--sr-border); font-size: 13px; color: var(--sr-muted); text-align: center; flex-wrap: wrap; }
        .ao-foot img { height: 44px; }
        .ao-hero h1 { font-size: 46px; line-height: 1.06; letter-spacing: -.03em; font-weight: 800; margin: 0 0 16px; color: #fff; }
        .ao-hero p { font-size: 17px; line-height: 1.55; margin: 0 0 24px; color: rgba(255,255,255,.9); max-width: 52ch; }
        .ao-hero p a { color: #fff; text-decoration: underline; }
        .ao-search { position: relative; max-width: 440px; }
        .ao-search i { position: absolute; inset-inline-start: 16px; top: 50%; transform: translateY(-50%); color: #64748b; }
        .ao-search input { width: 100%; height: 50px; border: 0; border-radius: 14px; padding: 0 16px 0 44px; font: inherit; font-size: 15px;
                           color: #0f172a; background: #fff; box-shadow: 0 10px 30px rgba(15,23,42,.25); }
        .ao-search-note { margin-top: 10px; font-size: 13px; color: rgba(255,255,255,.85); min-height: 18px; }
        .ao-kpis { display: flex; gap: 22px; margin-top: 18px; }
        .ao-kpis b { display: block; font-size: 28px; font-weight: 800; line-height: 1; }
        .ao-kpis span { font-size: 12px; color: rgba(255,255,255,.8); }

        .ao-hub { position: relative; width: 100%; aspect-ratio: 1; }
        .ao-hub svg { position: absolute; inset: 0; width: 100%; height: 100%; }
        .ao-hub line { stroke: rgba(255,255,255,.45); stroke-width: 2; stroke-dasharray: 4 6; animation: ao-flow 1.2s linear infinite; transition: stroke .2s, stroke-width .2s; }
        .ao-hub line.on { stroke: #fff; stroke-width: 3; }
        .ao-hub-core { position: absolute; left: 50%; top: 50%; width: 120px; height: 120px; margin: -60px 0 0 -60px; border-radius: 50%;
                       background: #fff; color: #4338ca; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
                       font-weight: 800; font-size: 15px; line-height: 1.15; box-shadow: 0 0 0 10px rgba(255,255,255,.18), 0 0 0 22px rgba(255,255,255,.08); animation: ao-pulse 3s ease-in-out infinite; }
        .ao-hub-core i { font-size: 28px; margin-bottom: 4px; }
        .ao-hub-node { position: absolute; width: 64px; height: 64px; margin: -32px 0 0 -32px; border-radius: 18px; border: 0; padding: 0;
                       background: linear-gradient(135deg, var(--c), var(--c2)); color: #fff; font-size: 24px; cursor: pointer;
                       box-shadow: 0 8px 20px rgba(15,23,42,.3), inset 0 0 0 2px rgba(255,255,255,.35); animation: ao-float 4s ease-in-out infinite;
                       transition: transform .2s; }
        .ao-hub-node:hover, .ao-hub-node:focus-visible { transform: scale(1.15); z-index: 2; }
        .ao-hub-node span { position: absolute; top: 100%; left: 50%; transform: translateX(-50%); margin-top: 6px; white-space: nowrap; font-size: 12px;
                            font-weight: 500; background: rgba(15,23,42,.75); padding: 3px 8px; border-radius: 6px; pointer-events: none; opacity: 0; transition: opacity .15s; }
        .ao-hub-node:hover span, .ao-hub-node:focus-visible span { opacity: 1; }
        @keyframes ao-flow { to { stroke-dashoffset: -20; } }
        @keyframes ao-pulse { 50% { box-shadow: 0 0 0 16px rgba(255,255,255,.14), 0 0 0 32px rgba(255,255,255,.05); } }
        @keyframes ao-float { 50% { translate: 0 -6px; } }

        /* blocks */
        .ao-block { background: var(--sr-surface); border: 1px solid var(--sr-border); border-radius: 20px; padding: 28px; margin-bottom: 28px; box-shadow: var(--sr-shadow); }
        .ao-block > h2 { font-size: 24px; font-weight: 800; letter-spacing: -.02em; color: var(--sr-text); margin: 0 0 4px; }
        .ao-block > p { font-size: 14px; color: var(--sr-muted); margin: 0 0 22px; }

        /* employee journey stepper */
        .ao-steps { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; }
        .ao-step { position: relative; display: flex; flex-direction: column; align-items: center; gap: 8px; width: 100%; padding: 14px 8px 16px; border: 0;
                   border-radius: 16px; background: var(--sr-surface-2); color: var(--sr-text-2); font: inherit; cursor: pointer; overflow: hidden;
                   transition: background .2s, color .2s, transform .2s; }
        .ao-step:hover { transform: translateY(-2px); background: color-mix(in srgb, var(--c) 10%, var(--sr-surface)); }
        .ao-step-n { width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;
                     background: color-mix(in srgb, var(--c) 16%, var(--sr-surface)); color: var(--c); transition: inherit; }
        .ao-step b { font-size: 15px; font-weight: 700; color: var(--sr-text); }
        .ao-step small { font-size: 11px; font-weight: 500; color: var(--sr-muted); }
        .ao-step.on { background: linear-gradient(135deg, var(--c), var(--c2)); color: #fff; transform: translateY(-3px); box-shadow: 0 12px 24px color-mix(in srgb, var(--c) 35%, transparent); }
        .ao-step.on b, .ao-step.on small { color: #fff; }
        .ao-step.on .ao-step-n { background: rgba(255,255,255,.22); color: #fff; }
        .ao-step-bar { position: absolute; left: 0; bottom: 0; height: 4px; width: 0; background: rgba(255,255,255,.75); }
        .ao-step.on.play .ao-step-bar { animation: ao-bar 5s linear forwards; }
        @keyframes ao-bar { to { width: 100%; } }

        .ao-panel { margin-top: 16px; display: grid; grid-template-columns: 220px 1fr; gap: 24px; padding: 22px; border-radius: 18px;
                    background: color-mix(in srgb, var(--c) 9%, var(--sr-surface)); border: 1px solid color-mix(in srgb, var(--c) 30%, transparent); }
        .ao-panel-ico { width: 100%; aspect-ratio: 1.4; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 64px; color: #fff;
                        background: linear-gradient(135deg, var(--c), var(--c2)); }
        .ao-panel h3 { font-size: 22px; font-weight: 800; color: var(--sr-text); margin: 0 0 4px; }
        .ao-panel p { font-size: 14px; color: var(--sr-text-2); margin: 0 0 14px; }
        .ao-panel-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .ao-panel-chips span { display: inline-flex; align-items: center; gap: 7px; padding: 7px 12px; border-radius: 999px; font-size: 13px; font-weight: 500;
                               background: var(--sr-surface); color: var(--sr-text); border: 1px solid color-mix(in srgb, var(--c) 30%, transparent);
                               animation: ao-pop .35s ease-out both; }
        .ao-panel-chips i { color: var(--c); }
        .ao-panel a.ao-go { font-size: 14px; font-weight: 700; color: var(--c2); text-decoration: none; }
        .ao-panel.swap { animation: ao-pop .3s ease-out; }
        @keyframes ao-pop { from { opacity: 0; transform: translateY(6px) scale(.98); } to { opacity: 1; transform: none; } }

        /* functions per module: donut chart */
        .ao-chart { display: grid; grid-template-columns: 280px 1fr; gap: 32px; align-items: center; }
        .ao-donut { position: relative; }
        .ao-donut svg { width: 100%; height: auto; transform: rotate(-90deg); }
        .ao-donut circle.seg { fill: none; stroke-width: 34; cursor: pointer; transition: stroke-width .2s, opacity .2s; }
        .ao-donut.hl circle.seg { opacity: .3; }
        .ao-donut.hl circle.seg.on { opacity: 1; stroke-width: 42; }
        .ao-donut-mid { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; text-align: center; }
        .ao-donut-mid b { font-size: 40px; font-weight: 800; color: var(--sr-text); line-height: 1; }
        .ao-donut-mid span { font-size: 13px; color: var(--sr-muted); max-width: 130px; }
        .ao-legend { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .ao-legend a { display: grid; grid-template-columns: 34px 1fr auto; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 12px;
                       text-decoration: none; color: var(--sr-text); font-size: 14px; font-weight: 500; transition: background .15s; }
        .ao-legend a:hover, .ao-legend a.on { background: color-mix(in srgb, var(--c) 12%, transparent); }
        .ao-legend a i { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff;
                         background: linear-gradient(135deg, var(--c), var(--c2)); }
        .ao-legend em { font-style: normal; font-weight: 700; color: var(--c2); }
        .ao-legend .bar { grid-column: 2 / 4; height: 6px; border-radius: 3px; background: var(--sr-surface-3); overflow: hidden; margin-top: -4px; }
        .ao-legend .bar s { display: block; height: 100%; width: 0; background: linear-gradient(90deg, var(--c), var(--c2)); transition: width 1s ease-out; }

        /* approval flow */
        .ao-flow { display: flex; flex-wrap: wrap; align-items: center; gap: 0; }
        .ao-flow-s { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border-radius: 14px; font-size: 14px; font-weight: 600; color: #fff;
                     background: linear-gradient(135deg, var(--c), var(--c2)); }
        .ao-flow-s i { font-size: 16px; }
        .ao-flow-a { width: 34px; height: 2px; margin: 0 4px; background: repeating-linear-gradient(90deg, var(--sr-border-strong) 0 5px, transparent 5px 9px);
                     background-size: 18px 2px; animation: ao-dash 1s linear infinite; }
        @keyframes ao-dash { to { background-position: 18px 0; } }

        /* module cards */
        .ao-group { font-size: 24px; font-weight: 800; letter-spacing: -.02em; color: var(--sr-text); margin: 40px 0 16px; }
        .ao-mods { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .ao-mod { border-radius: 20px; overflow: hidden; background: var(--sr-surface); border: 1px solid var(--sr-border); box-shadow: var(--sr-shadow);
                  display: flex; flex-direction: column; }
        .ao-mod-head { position: relative; padding: 22px 24px; color: #fff; background: linear-gradient(135deg, var(--c), var(--c2)); overflow: hidden; }
        .ao-mod-head .ghost { position: absolute; inset-inline-end: -10px; bottom: -24px; font-size: 120px; opacity: .18; transform: rotate(-12deg); }
        .ao-mod-head h2 { position: relative; font-size: 22px; font-weight: 800; margin: 0 0 4px; color: #fff; display: flex; align-items: center; gap: 10px; }
        .ao-mod-head h2 small { font-size: 12px; font-weight: 600; padding: 2px 9px; border-radius: 999px; background: rgba(255,255,255,.25); }
        .ao-mod-head p { position: relative; font-size: 14px; margin: 0; color: rgba(255,255,255,.88); }
        .ao-tiles { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 16px; flex: 1; align-content: start; }
        .ao-tile { display: grid; grid-template-columns: 38px 1fr; gap: 12px; padding: 12px; border-radius: 14px; background: var(--sr-surface-2);
                   transition: background .15s, transform .15s; }
        .ao-tile:hover { background: color-mix(in srgb, var(--c) 10%, var(--sr-surface)); transform: translateY(-2px); }
        .ao-tile > i { width: 38px; height: 38px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 16px;
                       color: var(--c2); background: color-mix(in srgb, var(--c) 16%, var(--sr-surface)); }
        .ao-tile b { display: block; font-size: 14px; font-weight: 700; color: var(--sr-text); margin-bottom: 2px; }
        .ao-tile span { font-size: 13px; line-height: 1.45; color: var(--sr-muted); }
        .ao-tile mark { background: #fde68a; color: inherit; padding: 0 1px; border-radius: 3px; }
        .ao-hide { display: none !important; }
        .ao-empty { display: none; text-align: center; padding: 40px; color: var(--sr-muted); }

        html.app-dark .ao-tile mark { background: #a16207; }
        @media (max-width: 1099px) {
            .ao-hero { grid-template-columns: 1fr; }
            .ao-hub { max-width: 440px; margin: 0 auto; }
            .ao-mods { grid-template-columns: 1fr; }
        }
        @media (max-width: 991px) {
            .ao-steps { grid-template-columns: repeat(3, 1fr); }
            .ao-panel, .ao-chart { grid-template-columns: 1fr; }
            .ao-panel-ico { aspect-ratio: auto; height: 90px; font-size: 44px; }
            .ao-donut { max-width: 260px; margin: 0 auto; }
        }
        @media (max-width: 575px) {
            .ao-hero { padding: 24px; border-radius: 18px; }
            .ao-hero h1 { font-size: 32px; }
            .ao-hub-node { width: 48px; height: 48px; margin: -24px 0 0 -24px; font-size: 18px; border-radius: 14px; }
            .ao-hub-core { width: 90px; height: 90px; margin: -45px 0 0 -45px; font-size: 12px; }
            .ao-steps { grid-template-columns: repeat(2, 1fr); }
            .ao-tiles, .ao-legend { grid-template-columns: 1fr; }
            .ao-block { padding: 18px; }
            .ao-flow-a { width: 14px; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .ao *, .ao *::before { animation: none !important; transition: none !important; }
        }
        @media print {
            .ao-nav, .ao-search, .ao-search-note, .ao-hub, .ao-journey-block { display: none; }
            .ao-hero { grid-template-columns: 1fr; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .ao-mod { break-inside: avoid; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="sr-standalone">
<div class="sr-page ao">
    <nav class="ao-nav" aria-label="Sections">
        <div class="ao-nav-in">
            <a class="ao-nav-brand" href="dashboard.php" title="Dashboard">
                <img src="assets/logo/nas_logo.svg?v=<?= @filemtime(__DIR__ . '/assets/logo/nas_logo.svg') ?>" alt="National Advanced Solutions">
                <span>National Advanced Solutions<small>الحلول الوطنية المتقدمة</small></span>
            </a>
            <div class="ao-nav-links" id="aoNav">
                <a href="#top" class="active" style="--c:#0f5a40"><i></i>Overview</a>
                <?php foreach ($sections as $id => $s): ?>
                    <a href="#<?= $id ?>" style="<?= $cv($s) ?>"><i></i><?= $h($s['title']) ?></a>
                <?php endforeach; ?>
            </div>
            <button type="button" class="sr-btn sr-btn-sm" onclick="window.print()"><i class="fa fa-print"></i> <span>Print</span></button>
        </div>
    </nav>

    <main class="ao-wrap">
        <header class="ao-hero" id="top">
            <div>
                <div class="ao-brand-plate"><img src="assets/logo/nas_logo.svg?v=<?= @filemtime(__DIR__ . '/assets/logo/nas_logo.svg') ?>" alt="National Advanced Solutions (NAS)"></div>
                <h1>One system for every employee, from hiring to the final settlement.</h1>
                <p>The National Advanced Solutions (NAS) system runs HR, attendance, payroll and approvals for the whole company in one place.
                   Click a module on the wheel, follow the employee's journey, or search for a function.
                   For step-by-step help, open the <a href="system_guide.php">full system guide</a>.</p>
                <div class="ao-search">
                    <i class="fa fa-search" aria-hidden="true"></i>
                    <input type="search" id="aoSearch" placeholder="Search functions, e.g. payslip, iqama, loan" aria-label="Search functions" autocomplete="off">
                </div>
                <div class="ao-search-note" id="aoSearchNote" aria-live="polite"></div>
                <div class="ao-kpis">
                    <div><b data-count="<?= count($sections) ?>">0</b><span>modules</span></div>
                    <div><b data-count="<?= $total ?>">0</b><span>functions</span></div>
                    <div><b>EN · AR</b><span>languages</span></div>
                </div>
            </div>

            <div class="ao-hub" id="aoHub" aria-label="Modules">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                    <?php $n = count($sections); $k = 0; foreach ($sections as $id => $s):
                        $a = -M_PI / 2 + 2 * M_PI * $k++ / $n;
                        $x = round(50 + 40 * cos($a), 2); $y = round(50 + 40 * sin($a), 2); ?>
                        <line x1="50" y1="50" x2="<?= $x ?>" y2="<?= $y ?>" data-id="<?= $id ?>" vector-effect="non-scaling-stroke"></line>
                    <?php endforeach; ?>
                </svg>
                <div class="ao-hub-core"><b>NAS</b><small>HR &amp; Operations</small></div>
                <?php $k = 0; foreach ($sections as $id => $s):
                    $a = -M_PI / 2 + 2 * M_PI * $k / $n;
                    $x = round(50 + 40 * cos($a), 2); $y = round(50 + 40 * sin($a), 2); ?>
                    <button type="button" class="ao-hub-node" data-id="<?= $id ?>" aria-label="<?= $h($s['title']) ?>"
                            style="left:<?= $x ?>%;top:<?= $y ?>%;animation-delay:<?= -0.4 * $k++ ?>s;<?= $cv($s) ?>">
                        <i class="fa <?= $s['icon'] ?>" aria-hidden="true"></i><span><?= $h($s['title']) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </header>

        <section class="ao-block ao-journey-block" aria-labelledby="aoJourneyH">
            <h2 id="aoJourneyH">The employee journey</h2>
            <p>Six stages every employee passes through. It plays on its own; click a stage to stop and look closer.</p>
            <ol class="ao-steps" id="aoSteps">
                <?php foreach ($journey as $i => [$id, $title, $desc]): ?>
                <li>
                    <button type="button" class="ao-step" data-id="<?= $id ?>" data-desc="<?= $h($desc) ?>" style="<?= $cv($sections[$id]) ?>">
                        <span class="ao-step-n"><i class="fa <?= $sections[$id]['icon'] ?>" aria-hidden="true"></i></span>
                        <small>Stage <?= $i + 1 ?></small>
                        <b><?= $h($title) ?></b>
                        <span class="ao-step-bar"></span>
                    </button>
                </li>
                <?php endforeach; ?>
            </ol>
            <div class="ao-panel" id="aoPanel" aria-live="polite"></div>
        </section>

        <section class="ao-block" aria-labelledby="aoChartH">
            <h2 id="aoChartH">What each module covers</h2>
            <p>Number of functions per module. Point at a slice or a name to compare; click to open the module.</p>
            <div class="ao-chart">
                <div class="ao-donut" id="aoDonut">
                    <svg viewBox="0 0 200 200" aria-hidden="true">
                        <?php $r = 70; $circ = 2 * M_PI * $r; $off = 0; foreach ($sections as $id => $s):
                            $len = $circ * count($s['items']) / $total; ?>
                            <circle class="seg" data-id="<?= $id ?>" cx="100" cy="100" r="<?= $r ?>" stroke="<?= $s['c'] ?>"
                                    stroke-dasharray="0 <?= round($circ, 2) ?>" data-len="<?= round($len - 1.5, 2) ?>" data-circ="<?= round($circ, 2) ?>"
                                    stroke-dashoffset="<?= round(-$off, 2) ?>"></circle>
                        <?php $off += $len; endforeach; ?>
                    </svg>
                    <div class="ao-donut-mid"><b id="aoDonutN"><?= $total ?></b><span id="aoDonutL">functions in total</span></div>
                </div>
                <div class="ao-legend" id="aoLegend">
                    <?php $max = max(array_map(fn($s) => count($s['items']), $sections));
                    foreach ($sections as $id => $s): ?>
                        <a href="#<?= $id ?>" data-id="<?= $id ?>" style="<?= $cv($s) ?>">
                            <i class="fa <?= $s['icon'] ?>" aria-hidden="true"></i><?= $h($s['title']) ?><em><?= count($s['items']) ?></em>
                            <span class="bar"><s data-w="<?= round(100 * count($s['items']) / $max) ?>"></s></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="ao-block" aria-labelledby="aoFlowH">
            <h2 id="aoFlowH">How a request is approved</h2>
            <p>Vacations, loans, trips, transfers, increments, resignations and smart requests all follow this order. Steps can be changed per request type in settings.</p>
            <div class="ao-flow">
                <?php $flow = [['Employee applies', 'fa-user', '#64748b', '#334155'], ['Supervisor', 'fa-user-circle-o', '#f59e0b', '#b45309'],
                               ['HR / Finance', 'fa-briefcase', '#0ea5e9', '#0369a1'], ['Management', 'fa-university', '#6366f1', '#4338ca'],
                               ['Applied to balance or payroll', 'fa-check-circle', '#10b981', '#047857']];
                foreach ($flow as $i => [$t, $ic, $c, $c2]): ?>
                    <?php if ($i): ?><span class="ao-flow-a" aria-hidden="true"></span><?php endif; ?>
                    <span class="ao-flow-s" style="--c:<?= $c ?>;--c2:<?= $c2 ?>"><i class="fa <?= $ic ?>" aria-hidden="true"></i><?= $h($t) ?></span>
                <?php endforeach; ?>
            </div>
        </section>

        <?php
        $groups = ['Along the employee\'s path' => array_column($journey, 0),
                   'Supporting the business'    => ['operations', 'reports', 'security', 'integrations']];
        foreach ($groups as $gTitle => $ids): ?>
            <h2 class="ao-group" data-group><?= $h($gTitle) ?></h2>
            <div class="ao-mods">
            <?php foreach ($ids as $id): $s = $sections[$id]; ?>
                <section class="ao-mod" id="<?= $id ?>" style="<?= $cv($s) ?>">
                    <div class="ao-mod-head">
                        <i class="fa <?= $s['icon'] ?> ghost" aria-hidden="true"></i>
                        <h2><?= $h($s['title']) ?> <small><?= count($s['items']) ?></small></h2>
                        <p><?= $h($s['sub']) ?></p>
                    </div>
                    <div class="ao-tiles">
                        <?php foreach ($s['items'] as [$name, $desc, $ic]): ?>
                        <div class="ao-tile">
                            <i class="fa <?= $ic ?>" aria-hidden="true"></i>
                            <div><b><?= $h($name) ?></b><span><?= $h($desc) ?></span></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <div class="ao-empty" id="aoEmpty"><i class="fa fa-search" style="font-size:32px"></i><p>No function matches that search. Try a shorter word, such as "loan" or "vacation".</p></div>
        <footer class="ao-foot">
            <img src="assets/logo/nas_logo.svg" alt="">
            <span>&copy; <?= date('Y') ?> National Advanced Solutions (NAS) &ndash; الحلول الوطنية المتقدمة</span>
        </footer>
    </main>
</div>
<script>
(function () {
    var DATA = <?= json_encode($jsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
    var esc = function (t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };
    var go = function (id) { var el = document.getElementById(id); if (el) el.scrollIntoView(); };

    // Menu: highlight the section on screen.
    var links = $$('#aoNav a');
    var spy = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            links.forEach(function (a) {
                var on = a.getAttribute('href') === '#' + e.target.id;
                a.classList.toggle('active', on);
                if (on) a.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            });
        });
    }, { rootMargin: '-72px 0px -60% 0px' });
    $$('#top, .ao-mod[id]').forEach(function (el) { spy.observe(el); });

    // Hero counters.
    $$('[data-count]').forEach(function (el) {
        var end = +el.dataset.count, t0 = null;
        if (reduce) { el.textContent = end; return; }
        requestAnimationFrame(function step(t) {
            t0 = t0 || t;
            var p = Math.min((t - t0) / 1200, 1);
            el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(step);
        });
    });

    // Hub: highlight the spoke, click opens the module.
    $$('.ao-hub-node').forEach(function (b) {
        var line = $('#aoHub line[data-id="' + b.dataset.id + '"]');
        b.addEventListener('mouseenter', function () { line.classList.add('on'); });
        b.addEventListener('mouseleave', function () { line.classList.remove('on'); });
        b.addEventListener('focus', function () { line.classList.add('on'); });
        b.addEventListener('blur', function () { line.classList.remove('on'); });
        b.addEventListener('click', function () { go(b.dataset.id); });
    });

    // Journey stepper with autoplay until the user picks a stage.
    var steps = $$('.ao-step'), panel = $('#aoPanel'), cur = 0, timer = null, playing = !reduce;
    function show(i) {
        cur = i;
        var b = steps[i], d = DATA[b.dataset.id];
        steps.forEach(function (s, k) { s.classList.toggle('on', k === i); s.classList.remove('play'); s.setAttribute('aria-pressed', k === i); });
        panel.style.cssText = '--c:' + d.c + ';--c2:' + d.c2;
        panel.innerHTML = '<div class="ao-panel-ico"><i class="fa ' + d.icon + '"></i></div><div>'
            + '<h3>' + esc(b.querySelector('b').textContent) + ' · ' + esc(d.title) + '</h3>'
            + '<p>' + esc(b.dataset.desc) + ' ' + esc(d.sub) + '</p>'
            + '<div class="ao-panel-chips">' + d.items.map(function (it, k) {
                return '<span style="animation-delay:' + (k * 50) + 'ms"><i class="fa ' + it[1] + '"></i>' + esc(it[0]) + '</span>';
            }).join('') + '</div>'
            + '<a class="ao-go" href="#' + b.dataset.id + '">See all ' + esc(d.title.toLowerCase()) + ' functions</a></div>';
        panel.classList.remove('swap'); void panel.offsetWidth; panel.classList.add('swap');
        if (playing) {
            void b.offsetWidth; b.classList.add('play');
            clearTimeout(timer);
            timer = setTimeout(function () { show((cur + 1) % steps.length); }, 5000);
        }
    }
    steps.forEach(function (b, i) {
        b.addEventListener('click', function () { playing = false; clearTimeout(timer); show(i); });
    });
    show(0);

    // Donut + legend.
    var donut = $('#aoDonut'), segs = $$('#aoDonut circle.seg'), legend = $$('#aoLegend a');
    var nEl = $('#aoDonutN'), lEl = $('#aoDonutL'), total = nEl.textContent;
    function drawDonut() {
        segs.forEach(function (c) { c.setAttribute('stroke-dasharray', c.dataset.len + ' ' + c.dataset.circ); });
        $$('#aoLegend .bar s').forEach(function (s) { s.style.width = s.dataset.w + '%'; });
    }
    segs.forEach(function (c) { c.style.transition = reduce ? 'none' : 'stroke-dasharray 1.1s ease-out, stroke-width .2s, opacity .2s'; });
    new IntersectionObserver(function (e, o) { if (e[0].isIntersecting) { drawDonut(); o.disconnect(); } }, { threshold: .3 }).observe(donut);
    function hl(id) {
        donut.classList.toggle('hl', !!id);
        segs.forEach(function (c) { c.classList.toggle('on', c.dataset.id === id); });
        legend.forEach(function (a) { a.classList.toggle('on', a.dataset.id === id); });
        nEl.textContent = id ? DATA[id].items.length : total;
        lEl.textContent = id ? DATA[id].title : 'functions in total';
    }
    segs.concat(legend).forEach(function (el) {
        el.addEventListener('mouseenter', function () { hl(el.dataset.id); });
        el.addEventListener('mouseleave', function () { hl(null); });
        el.addEventListener('focus', function () { hl(el.dataset.id); });
        el.addEventListener('blur', function () { hl(null); });
    });
    segs.forEach(function (c) { c.addEventListener('click', function () { go(c.dataset.id); }); });

    // Live search over all function tiles.
    var input = $('#aoSearch'), note = $('#aoSearchNote'), tiles = $$('.ao-tile');
    tiles.forEach(function (t) { t._b = t.querySelector('b').textContent; t._s = t.querySelector('span').textContent; });
    function mark(text, q) {
        if (!q) return esc(text);
        var i = text.toLowerCase().indexOf(q);
        return i < 0 ? esc(text) : esc(text.slice(0, i)) + '<mark>' + esc(text.slice(i, i + q.length)) + '</mark>' + esc(text.slice(i + q.length));
    }
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase(), hits = 0;
        tiles.forEach(function (t) {
            var ok = !q || (t._b + ' ' + t._s).toLowerCase().indexOf(q) > -1;
            t.classList.toggle('ao-hide', !ok);
            t.querySelector('b').innerHTML = mark(t._b, q);
            t.querySelector('span').innerHTML = mark(t._s, q);
            if (ok) hits++;
        });
        $$('.ao-mod').forEach(function (m) { m.classList.toggle('ao-hide', !m.querySelector('.ao-tile:not(.ao-hide)')); });
        $$('.ao-mods').forEach(function (g) {
            var empty = !g.querySelector('.ao-mod:not(.ao-hide)');
            g.classList.toggle('ao-hide', empty);
            g.previousElementSibling.classList.toggle('ao-hide', empty);
        });
        $('#aoEmpty').style.display = q && !hits ? 'block' : 'none';
        note.textContent = q ? (hits ? hits + ' function' + (hits > 1 ? 's' : '') + ' found below' : 'No match') : '';
    });
    input.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        var hit = $('.ao-tile:not(.ao-hide)');
        if (hit) hit.closest('.ao-mod').scrollIntoView();
    });
})();
</script>
</body>
</html>
