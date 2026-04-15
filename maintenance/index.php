<?php
/**
 * Maintenance Mode Page
 * 
 * Auto-redirect to index.php if maintenance mode is disabled
 */

// Check if maintenance mode is enabled (by checking if MAINTENANCE_ON file exists)
$maintenanceEnabled = file_exists(__DIR__ . '/MAINTENANCE_ON');

// If maintenance is disabled, redirect to index.php
if (!$maintenanceEnabled) {
    header('HTTP/1.1 302 Found');
    header('Location: ../index.php');
    exit;
}   
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Maintenance | Almutlak</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 45%, #dbeafe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #0f172a;
        }
        .wrapper {
            width: 100%;
            max-width: 1100px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.15);
        }
        .hero {
            padding: 48px;
            background: linear-gradient(160deg, #0f172a 0%, #1d4ed8 60%, #2563eb 100%);
            color: #fff;
            position: relative;
        }
        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 35%);
            pointer-events: none;
        }
        .pill {
            display: inline-block;
            padding: 8px 14px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 18px;
        }
        .hero h1 {
            font-size: 40px;
            line-height: 1.15;
            margin-bottom: 14px;
        }
        .hero p {
            font-size: 16px;
            line-height: 1.8;
            color: rgba(255,255,255,.88);
            margin-bottom: 22px;
        }
        .checklist {
            list-style: none;
            display: grid;
            gap: 12px;
            margin-top: 24px;
        }
        .checklist li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            color: rgba(255,255,255,.92);
        }
        .check {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(255,255,255,.16);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }
        .panel {
            padding: 42px 34px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }
        .panel h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #0f172a;
        }
        .panel .desc {
            color: #475569;
            line-height: 1.7;
            margin-bottom: 22px;
            font-size: 15px;
        }
        .countdown {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }
        .time-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px 12px;
            text-align: center;
        }
        .time-value {
            font-size: 30px;
            font-weight: 700;
            color: #1d4ed8;
            margin-bottom: 4px;
        }
        .time-label {
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #64748b;
        }
        .progress-wrap {
            margin: 18px 0 24px;
        }
        .progress-top {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #334155;
            margin-bottom: 8px;
        }
        .progress-bar {
            width: 100%;
            height: 12px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            border-radius: 999px;
            transition: width .5s ease;
            box-shadow: 0 0 12px rgba(37, 99, 235, 0.35);
        }
        .support-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 16px;
            padding: 16px;
            color: #1e3a8a;
            line-height: 1.7;
            font-size: 14px;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .btn {
            text-decoration: none;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-primary {
            background: #1d4ed8;
            color: #fff;
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
        }
        @media (max-width: 900px) {
            .wrapper {
                grid-template-columns: 1fr;
            }
            .hero, .panel {
                padding: 28px 22px;
            }
            .hero h1 {
                font-size: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <section class="hero">
            <span class="pill">Scheduled Maintenance</span>
            <h1>We’re improving the HR portal experience</h1>
            <p>
                The system is temporarily unavailable while we complete database maintenance and performance improvements.
                Access will be restored as soon as the work is safely completed.
            </p>
            <ul class="checklist">
                <li><span class="check">✓</span> Database performance tuning in progress</li>
                <li><span class="check">✓</span> Stability and timeout issues are being reviewed</li>
                <li><span class="check">✓</span> Access will return automatically after completion</li>
            </ul>
        </section>

        <section class="panel">
            <h2>Expected availability window</h2>
            <p class="desc">Please check back shortly. The estimated countdown below is set for the current maintenance window.</p>

            <div class="countdown">
                <div class="time-box"><div class="time-value" id="days">00</div><div class="time-label">Days</div></div>
                <div class="time-box"><div class="time-value" id="hours">00</div><div class="time-label">Hours</div></div>
                <div class="time-box"><div class="time-value" id="minutes">00</div><div class="time-label">Minutes</div></div>
                <div class="time-box"><div class="time-value" id="seconds">00</div><div class="time-label">Seconds</div></div>
            </div>

            <div class="progress-wrap">
                <div class="progress-top">
                    <span>Maintenance progress</span>
                    <strong id="progressText">0%</strong>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
            </div>

            <div class="support-box">
                <strong>Need urgent assistance?</strong><br>
                Email: <a href="mailto:it@almutlak.com">it@almutlak.com</a><br>
                <!-- Phone: <a href="tel:+966538092933">+966 53 809 2933</a> -->
            </div>

            <div class="actions">
                <a href="javascript:location.reload();" class="btn btn-primary">Refresh page</a>
                <a href="mailto:it@almutlak.com" class="btn btn-secondary">Contact support</a>
            </div>
        </section>
    </div>

    <script>
        // Set your maintenance start date and time here
        const maintenanceStart = new Date('2026-04-07T17:00:00');

        const startDate = maintenanceStart.getTime();
        const countDownDate = startDate + (2 * 24 * 60 * 60 * 1000);
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');

        const countdownFunction = setInterval(function () {
            const now = new Date().getTime();
            const distance = countDownDate - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('days').innerText = days > 0 ? String(days).padStart(2, '0') : '00';
            document.getElementById('hours').innerText = hours > 0 ? String(hours).padStart(2, '0') : '00';
            document.getElementById('minutes').innerText = minutes > 0 ? String(minutes).padStart(2, '0') : '00';
            document.getElementById('seconds').innerText = seconds > 0 ? String(seconds).padStart(2, '0') : '00';

            const totalDuration = countDownDate - startDate;
            const elapsedDuration = now - startDate;
            let progressPercentage = (elapsedDuration / totalDuration) * 100;
            progressPercentage = Math.max(0, Math.min(100, progressPercentage));

            progressFill.style.width = progressPercentage.toFixed(2) + '%';
            progressText.innerText = Math.round(progressPercentage) + '%';

            if (distance < 0) {
                clearInterval(countdownFunction);
                document.getElementById('days').innerText = '00';
                document.getElementById('hours').innerText = '00';
                document.getElementById('minutes').innerText = '00';
                document.getElementById('seconds').innerText = '00';
                progressFill.style.width = '100%';
                progressText.innerText = '100%';
            }
        }, 1000);
    </script>
</body>
</html>
