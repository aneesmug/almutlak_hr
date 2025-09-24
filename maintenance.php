<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance | Almutlak</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            position: relative;
        }
        
        .glowing-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .glow {
            position: absolute;
            border-radius: 50%;
            background: rgba(0, 247, 255, 0.15);
            filter: blur(100px);
            animation: float 20s infinite linear;
        }
        
        .glow-1 {
            width: 500px;
            height: 500px;
            top: -250px;
            left: -250px;
            animation-delay: 0s;
            background: rgba(0, 247, 255, 0.1);
        }
        
        .glow-2 {
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -200px;
            animation-delay: -5s;
            background: rgba(0, 255, 149, 0.1);
        }
        
        .cyber-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.1;
            background-image: 
                linear-gradient(rgba(0, 247, 255, 0.3) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 247, 255, 0.3) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: gridMove 20s infinite linear;
        }
        
        .container {
            display: flex;
            max-width: 1200px;
            width: 90%;
            background: rgba(0, 15, 30, 0.7);
            border-radius: 16px;
            box-shadow: 0 0 40px rgba(0, 247, 255, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 247, 255, 0.3);
            position: relative;
            overflow: hidden;
            animation: containerGlow 3s infinite alternate;
        }
        
        .container::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            z-index: -1;
            background: linear-gradient(45deg, #00f7ff, #00ff95, #ff00e6, #00f7ff);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            filter: blur(20px);
            opacity: 0.3;
        }
        
        .left-panel {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-right: 1px solid rgba(0, 247, 255, 0.2);
        }
        
        .right-panel {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(0, 25, 50, 0.5);
        }
        
        .logo {
            margin-bottom: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .logo-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #00f7ff, #00ff95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            box-shadow: 0 0 30px rgba(0, 247, 255, 0.7);
            animation: pulse 3s infinite ease-in-out;
        }
        
        h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
            color: #00f7ff;
            font-weight: 700;
            text-shadow: 0 0 10px rgba(0, 247, 255, 0.7);
        }
        
        .subtitle {
            font-size: 1.3rem;
            color: #a0f0ff;
            margin-bottom: 30px;
            font-weight: 400;
        }
        
        p {
            font-size: 1.1rem;
            line-height: 1.7;
            margin-bottom: 30px;
            color: #a0f0ff;
        }
        
        .status-card {
            background: rgba(0, 30, 60, 0.7);
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
            border: 1px solid rgba(0, 247, 255, 0.2);
            box-shadow: 0 0 20px rgba(0, 247, 255, 0.2);
        }
        
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .status-title {
            font-size: 1.2rem;
            color: #00ff95;
            font-weight: 600;
            text-shadow: 0 0 5px rgba(0, 255, 149, 0.5);
        }
        
        .status-badge {
            background: linear-gradient(135deg, #00ff95, #00f7ff);
            color: #0f2027;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 0 10px rgba(0, 255, 149, 0.5);
        }
        
        .progress-container {
            width: 100%;
            height: 10px;
            background: rgba(0, 247, 255, 0.1);
            border-radius: 10px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            width: 0%; /* Start at 0, will be updated by JS */
            background: linear-gradient(90deg, #00ff95, #00f7ff);
            border-radius: 10px;
            position: relative;
            box-shadow: 0 0 10px rgba(0, 247, 255, 0.5);
            transition: width 0.5s ease-out;
        }
        
        .progress-bar:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(255, 255, 255, 0.3) 50%, 
                transparent 100%);
            animation: shine 2s infinite;
        }
        
        .countdown {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 40px 0;
        }
        
        .countdown-item {
            background: rgba(0, 30, 60, 0.7);
            padding: 20px 15px;
            border-radius: 12px;
            min-width: 90px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 247, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .countdown-item:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #00ff95, #00f7ff);
            animation: cyberLine 2s infinite linear;
        }
        
        .countdown-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 20px rgba(0, 247, 255, 0.4);
        }
        
        .countdown-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: #00ff95;
            margin-bottom: 5px;
            text-shadow: 0 0 5px rgba(0, 255, 149, 0.5);
        }
        
        .countdown-label {
            font-size: 0.9rem;
            color: #00f7ff;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .info-item {
            background: rgba(0, 30, 60, 0.7);
            padding: 20px;
            border-radius: 12px;
            text-align: left;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 247, 255, 0.2);
            transition: transform 0.3s ease;
        }
        
        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 20px rgba(0, 247, 255, 0.3);
        }
        
        .info-icon {
            font-size: 24px;
            color: #00f7ff;
            margin-bottom: 15px;
            text-shadow: 0 0 5px rgba(0, 247, 255, 0.5);
        }
        
        .info-title {
            font-size: 1.1rem;
            color: #00ff95;
            margin-bottom: 10px;
            font-weight: 600;
            text-shadow: 0 0 5px rgba(0, 255, 149, 0.3);
        }
        
        .info-text {
            font-size: 0.95rem;
            color: #a0f0ff;
            line-height: 1.6;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00ff95, #00f7ff);
            color: #0f2027;
            box-shadow: 0 0 15px rgba(0, 247, 255, 0.5);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 0 20px rgba(0, 247, 255, 0.7);
        }
        
        .btn-outline {
            background: transparent;
            color: #00f7ff;
            border: 1px solid #00f7ff;
            box-shadow: 0 0 10px rgba(0, 247, 255, 0.3);
        }
        
        .btn-outline:hover {
            background: rgba(0, 247, 255, 0.1);
            transform: translateY(-3px);
            box-shadow: 0 0 15px rgba(0, 247, 255, 0.5);
        }
        
        .contact-info {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid rgba(0, 247, 255, 0.2);
        }
        
        .contact-text {
            font-size: 1rem;
            color: #a0f0ff;
            margin-bottom: 10px;
        }
        
        .contact-email {
            color: #00f7ff;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
            text-shadow: 0 0 5px rgba(0, 247, 255, 0.3);
        }
        
        .contact-email:hover {
            color: #00ff95;
            text-shadow: 0 0 5px rgba(0, 255, 149, 0.3);
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(20px, 20px) rotate(180deg); }
            100% { transform: translate(0, 0) rotate(360deg); }
        }
        
        @keyframes gridMove {
            0% { background-position: 0 0; }
            100% { background-position: 50px 50px; }
        }
        
        @keyframes containerGlow {
            0% { box-shadow: 0 0 40px rgba(0, 247, 255, 0.5); }
            100% { box-shadow: 0 0 50px rgba(0, 247, 255, 0.8); }
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes cyberLine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
                width: 95%;
            }
            
            .left-panel {
                border-right: none;
                border-bottom: 1px solid rgba(0, 247, 255, 0.2);
            }
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2.2rem;
            }
            
            .subtitle {
                font-size: 1.1rem;
            }
            
            .countdown {
                flex-wrap: wrap;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="glowing-background">
        <div class="glow glow-1"></div>
        <div class="glow glow-2"></div>
    </div>
    
    <div class="cyber-grid"></div>
    
    <div class="container">
        <div class="left-panel">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-cogs"></i>
                </div>
            </div>
            
            <h1>System Maintenance</h1>
            <div class="subtitle">We're upgrading our systems for a better experience</div>
            
            <p>Our website is currently undergoing scheduled maintenance. We're working hard to improve your experience and will be back online shortly. Thank you for your patience.</p>
            
            <div class="status-card">
                <div class="status-header">
                    <div class="status-title">Maintenance Progress</div>
                    <div class="status-badge">In Progress</div>
                </div>
                <p>Our team is performing server upgrades and security enhancements to serve you better.</p>
                <div class="progress-container">
                    <div class="progress-bar"></div>
                </div>
                <div class="status-text">Estimated completion: <strong>0%</strong></div>
            </div>
        </div>
        
        <div class="right-panel">
            <h2>Estimated Time Until Completion:</h2>
            <div class="countdown">
                <div class="countdown-item">
                    <div class="countdown-value" id="days">00</div>
                    <div class="countdown-label">Days</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-value" id="hours">00</div>
                    <div class="countdown-label">Hours</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-value" id="minutes">00</div>
                    <div class="countdown-label">Minutes</div>
                </div>
                <div class="countdown-item">
                    <div class="countdown-value" id="seconds">00</div>
                    <div class="countdown-label">Seconds</div>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="info-title">Server Upgrade</div>
                    <div class="info-text">We're upgrading our server infrastructure for improved performance and reliability.</div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="info-title">Security Enhancements</div>
                    <div class="info-text">Implementing the latest security measures to protect your data and privacy.</div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="tel:+966538092933" class="btn btn-primary">
                    <i class="fas fa-envelope"></i> Contact Support
                </a>
                <a href="#" class="btn btn-outline">
                    <i class="fas fa-status"></i> Status Page
                </a>
            </div>
            
            <div class="contact-info">
                <div class="contact-text">For urgent inquiries, please contact us at</div>
                <a href="mailto:it@almutlak.com" class="contact-email">it@almutlak.com</a>
            </div>
        </div>
    </div>

    <script>
        // Define the start and end dates for the maintenance period
        const startDate = new Date("September 03, 2025 17:00:00").getTime();
        const countDownDate = new Date("September 25, 2025 17:00:00").getTime();

        // Get elements
        const progressBar = document.querySelector('.progress-bar');
        const progressText = document.querySelector('.status-text strong');

        // Update the countdown and progress bar every 1 second
        const countdownFunction = setInterval(function() {
            // Get today's date and time
            const now = new Date().getTime();
            
            // Find the distance between now and the count down date
            const distance = countDownDate - now;
            
            // --- Countdown Timer Logic ---
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById("days").innerText = days > 0 ? days.toString().padStart(2, '0') : "00";
            document.getElementById("hours").innerText = hours > 0 ? hours.toString().padStart(2, '0') : "00";
            document.getElementById("minutes").innerText = minutes > 0 ? minutes.toString().padStart(2, '0') : "00";
            document.getElementById("seconds").innerText = seconds > 0 ? seconds.toString().padStart(2, '0') : "00";

            // --- Progress Bar Logic ---
            const totalDuration = countDownDate - startDate;
            const elapsedDuration = now - startDate;
            let progressPercentage = (elapsedDuration / totalDuration) * 100;
            
            // Clamp the percentage between 0 and 100
            progressPercentage = Math.max(0, Math.min(100, progressPercentage));

            // Update progress bar width and text
            progressBar.style.width = progressPercentage + '%';
            progressText.innerText = Math.round(progressPercentage) + '%';
            
            // If the count down is over, update the page
            if (distance < 0) {
                clearInterval(countdownFunction);
                
                // Finalize countdown display
                document.getElementById("days").innerText = "00";
                document.getElementById("hours").innerText = "00";
                document.getElementById("minutes").innerText = "00";
                document.getElementById("seconds").innerText = "00";
                
                // Finalize progress bar
                progressBar.style.width = "100%";
                progressText.innerText = "100%";

                // Update the page content
                document.querySelector("h1").innerText = "Maintenance Complete!";
                document.querySelector(".subtitle").innerText = "Our systems are now back online";
                document.querySelector(".status-badge").innerText = "Completed";
                document.querySelector(".status-badge").style.background = "linear-gradient(135deg, #00ff95, #38a169)";
            }
        }, 1000);
    </script>
</body>
</html>
