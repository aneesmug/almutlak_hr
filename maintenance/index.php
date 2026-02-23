<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Construction | Almutlak</title>
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
            background: 
                radial-gradient(circle at 20% 50%, rgba(0, 168, 255, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0, 212, 255, 0.03) 0%, transparent 50%),
                linear-gradient(135deg, #1a1a1a, #2d2d2d, #1a1a1a);
            color: #fff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0, 168, 255, 0.03) 2px, rgba(0, 168, 255, 0.03) 4px),
                repeating-linear-gradient(90deg, transparent, transparent 2px, rgba(0, 168, 255, 0.03) 2px, rgba(0, 168, 255, 0.03) 4px);
            background-size: 100px 100px;
            opacity: 0.4;
            z-index: -2;
            pointer-events: none;
        }
        body::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2300a8ff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
            z-index: -2;
            pointer-events: none;
        }
        .glowing-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 168, 255, 0.05) 0%, transparent 25%),
                radial-gradient(circle at 90% 80%, rgba(0, 212, 255, 0.05) 0%, transparent 25%),
                radial-gradient(circle at 50% 50%, rgba(0, 136, 255, 0.03) 0%, transparent 50%);
        }
        .glow {
            position: absolute;
            border-radius: 50%;
            background: rgba(0, 168, 255, 0.15);
            filter: blur(100px);
            animation: float 20s infinite linear;
        }
        .glow-1 {
            width: 500px;
            height: 500px;
            top: -250px;
            left: -250px;
            animation-delay: 0s;
            background: rgba(0, 168, 255, 0.1);
        }
        .glow-2 {
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -200px;
            animation-delay: -5s;
            background: rgba(0, 212, 255, 0.1);
        }
        .construction-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.15;
            background-image:
                linear-gradient(rgba(0, 168, 255, 0.4) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 168, 255, 0.4) 1px, transparent 1px),
                repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0, 168, 255, 0.02) 10px, rgba(0, 168, 255, 0.02) 20px);
            background-size: 50px 50px, 50px 50px, 100px 100px;
            animation: gridMove 20s infinite linear;
        }
        .container {
            display: flex;
            max-width: 1200px;
            width: 90%;
            background: rgba(0, 20, 40, 0.75);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 60px rgba(0, 168, 255, 0.3);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 168, 255, 0.3);
            position: relative;
            overflow: hidden;
            animation: containerGlow 4s infinite alternate, fadeIn 0.8s ease-out;
        }
        .container::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            z-index: -1;
            background: linear-gradient(45deg, #0088ff, #00a8ff, #00d4ff, #0088ff);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            filter: blur(20px);
            opacity: 0.3;
        }
        .left-panel {
            flex: 1;
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-right: 1px solid rgba(0, 168, 255, 0.25);
            background: linear-gradient(135deg, rgba(0, 20, 40, 0.3) 0%, transparent 100%);
        }
        .right-panel {
            flex: 1;
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(135deg, transparent 0%, rgba(0, 25, 50, 0.4) 100%);
        }
        .logo {
            margin-bottom: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.8s ease-out 0.2s both;
        }
        .logo-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #0088ff, #00d4ff);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content:center;
            color: white;
            font-size: 50px;
            box-shadow: 0 0 40px rgba(0, 168, 255, 0.6), 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: pulse 4s infinite ease-in-out;
            position: relative;
        }
        .logo-icon::before {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            border: 2px solid transparent;
            background: linear-gradient(135deg, #00d4ff, #0088ff) border-box;
            mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
            -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
            -webkit-mask-composite: xor;
            opacity: 0.6;
        }
        h1 {
            font-size: 3.2rem;
            margin-bottom: 20px;
            color: #00d4ff;
            font-weight: 800;
            text-shadow: 0 0 20px rgba(0, 212, 255, 0.5);
            letter-spacing: -1px;
            line-height: 1.1;
        }
        h2 {
            font-size: 1.4rem;
            color: #00d4ff;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            text-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 1.4rem;
            color: #b3e5ff;
            margin-bottom: 35px;
            font-weight: 400;
            line-height: 1.4;
            opacity: 0.95;
        }
        p {
            font-size: 1.05rem;
            line-height: 1.8;
            margin-bottom: 30px;
            color: #b3e5ff;
            opacity: 0.9;
            font-weight: 300;
        }
        .status-card {
            background: rgba(0, 30, 60, 0.8);
            border-radius: 16px;
            padding: 30px;
            margin: 35px 0;
            text-align: left;
            border: 1px solid rgba(0, 168, 255, 0.25);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 20px rgba(0, 168, 255, 0.15);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .status-card:hover {
            border-color: rgba(0, 168, 255, 0.4);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.4), 0 0 30px rgba(0, 168, 255, 0.25);
        }
        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .status-title {
            font-size: 1.3rem;
            color: #00d4ff;
            font-weight: 700;
            text-shadow: 0 0 10px rgba(0, 212, 255, 0.5);
            letter-spacing: 0.3px;
        }
        .status-text {
            color: #b3e5ff;
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 300;
        }
        .status-text strong {
            color: #00d4ff;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .status-badge {
            background: linear-gradient(135deg, #00d4ff, #00a8ff);
            color: #0a0a0a;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(0, 212, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: badgePulse 2s infinite ease-in-out;
        }
        @keyframes badgePulse {
            0%, 100% {
                box-shadow: 0 4px 15px rgba(0, 212, 255, 0.4);
            }
            50% {
                box-shadow: 0 4px 20px rgba(0, 212, 255, 0.6);
            }
        }
        .progress-container {
            width: 100%;
            height: 12px;
            background: rgba(0, 20, 40, 0.6);
            border-radius: 10px;
            margin: 25px 0;
            overflow: hidden;
            border: 1px solid rgba(0, 168, 255, 0.2);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        .progress-bar {
            height: 100%;
            width: 0%;
            /* Start at 0, will be updated by JS */
            background: linear-gradient(90deg, #00d4ff, #0088ff);
            border-radius: 10px;
            position: relative;
            box-shadow: 0 0 10px rgba(0, 168, 255, 0.5);
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
            background: rgba(0, 30, 60, 0.85);
            padding: 25px 18px;
            border-radius: 16px;
            min-width: 95px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4), 0 0 20px rgba(0, 168, 255, 0.15);
            border: 1px solid rgba(0, 168, 255, 0.25);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        .countdown-item:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #00d4ff, #0088ff);
            animation: cyberLine 2s infinite linear;
        }
        .countdown-item:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.5), 0 0 30px rgba(0, 168, 255, 0.4);
            border-color: rgba(0, 168, 255, 0.5);
        }
        .countdown-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: #00d4ff;
            margin-bottom: 8px;
            text-shadow: 0 0 10px rgba(0, 212, 255, 0.6), 0 2px 4px rgba(0, 0, 0, 0.3);
            letter-spacing: 1px;
        }
        .countdown-label {
            font-size: 0.9rem;
            color: #00a8ff;
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
            background: rgba(0, 30, 60, 0.8);
            padding: 25px;
            border-radius: 16px;
            text-align: left;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4), 0 0 15px rgba(0, 168, 255, 0.1);
            border: 1px solid rgba(0, 168, 255, 0.25);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }
        .info-item:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.5), 0 0 25px rgba(0, 168, 255, 0.25);
            border-color: rgba(0, 168, 255, 0.4);
        }
        .info-icon {
            font-size: 32px;
            color: #00d4ff;
            margin-bottom: 18px;
            text-shadow: 0 0 15px rgba(0, 212, 255, 0.6);
            display: inline-block;
            transition: transform 0.3s ease;
        }
        .info-item:hover .info-icon {
            transform: scale(1.1) rotate(5deg);
        }
        .info-title {
            font-size: 1.1rem;
            color: #00d4ff;
            margin-bottom: 10px;
            font-weight: 600;
            text-shadow: 0 0 5px rgba(0, 212, 255, 0.3);
        }
        .info-text {
            font-size: 0.95rem;
            color: #b3e5ff;
            line-height: 1.6;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 16px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
        }
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #00d4ff, #0088ff);
            color: #0a0a0a;
            box-shadow: 0 6px 20px rgba(0, 168, 255, 0.4);
            font-weight: 700;
        }
        .btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 168, 255, 0.6);
        }
        .btn-primary:active {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 168, 255, 0.5);
        }
        .btn-outline {
            background: transparent;
            color: #00d4ff;
            border: 2px solid #00a8ff;
            box-shadow: 0 6px 20px rgba(0, 168, 255, 0.2);
        }
        .btn-outline:hover {
            background: rgba(0, 168, 255, 0.15);
            border-color: #00d4ff;
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 168, 255, 0.4);
        }
        .btn-outline:active {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 168, 255, 0.3);
        }
        .contact-info {
            margin-top: 45px;
            padding-top: 30px;
            border-top: 1px solid rgba(0, 168, 255, 0.25);
        }
        .contact-text {
            font-size: 1rem;
            color: #b3e5ff;
            margin-bottom: 12px;
            opacity: 0.85;
            font-weight: 300;
        }
        .contact-email {
            color: #00d4ff;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            text-shadow: 0 0 10px rgba(0, 212, 255, 0.4);
            position: relative;
            display: inline-block;
        }
        .contact-email::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background: linear-gradient(90deg, #00d4ff, #0088ff);
            transition: width 0.3s ease;
        }
        .contact-email:hover::after {
            width: 100%;
        }
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 40px rgba(0, 168, 255, 0.6), 0 10px 30px rgba(0, 0, 0, 0.3);
            }
            50% {
               transform: scale(1.05);
               box-shadow: 0 0 60px rgba(0, 212, 255, 0.8), 0 10px 40px rgba(0, 0, 0, 0.4);
            }
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            50% {
                transform: translate(20px, 20px) rotate(180deg);
            }
            100% {
                transform: translate(0, 0) rotate(360deg);
            }
        }
        @keyframes gridMove {
            0% {
                background-position: 0 0;
            }
            100% {
                background-position: 50px 50px;
            }
        }
        @keyframes containerGlow {
            0% {
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 0 0 60px rgba(0, 168, 255, 0.3);
            }
            100% {
                box-shadow: 0 20px 70px rgba(0, 0, 0, 0.6), 0 0 80px rgba(0, 168, 255, 0.5);
            }
        }
        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        @keyframes cyberLine {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        @keyframes shine {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
                width: 95%;
            }
            .left-panel {
                border-right: none;
                border-bottom: 1px solid rgba(0, 168, 255, 0.25);
                padding: 40px 35px;
            }
            .right-panel {
                padding: 40px 35px;
            }
        }
        @media (max-width: 768px) {
            h1 {
                font-size: 2.5rem;
            }
            h2 {
                font-size: 1.2rem;
            }
            .subtitle {
                font-size: 1.15rem;
            }
            .logo-icon {
                width: 100px;
                height: 100px;
                font-size: 40px;
            }
            .left-panel, .right-panel {
                padding: 35px 25px;
            }
            .countdown {
                flex-wrap: wrap;
            }
            .countdown-item {
                min-width: 85px;
                padding: 20px 15px;
            }
            .countdown-value {
                font-size: 2rem;
            }
            .info-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .action-buttons {
                flex-direction: column;
                gap: 12px;
            }
            .btn {
                justify-content: center;
                padding: 14px 24px;
            }
        }
    </style>

</head>
<body>
    <div class="glowing-background">
        <div class="glow glow-1"></div>
        <div class="glow glow-2"></div>
    </div>
    <div class="construction-grid"></div>
    <div class="container">
        <div class="left-panel">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-hard-hat"></i>
                </div>
            </div>
            <h1 style="animation: fadeIn 0.8s ease-out 0.3s both;">Under Construction</h1>
            <div class="subtitle" style="animation: fadeIn 0.8s ease-out 0.4s both;">We're building something amazing for you</div>
            <p style="animation: fadeIn 0.8s ease-out 0.5s both;">Our website is currently under construction as we work on creating an enhanced digital experience. We're building new features and improvements that will be worth the wait. Thank you for your patience!</p>
            <div class="status-card" style="animation: fadeIn 0.8s ease-out 0.6s both;">
                <div class="status-header">
                    <div class="status-title">Construction Progress</div>
                    <div class="status-badge">Building</div>
                </div>
                <p>Our development team is actively building and testing new features to serve you better.</p>
                <div class="progress-container">
                    <div class="progress-bar"></div>
                </div>
                <div class="status-text">Estimated completion: <strong>0%</strong></div>
            </div>
        </div>
        <div class="right-panel">
            <h2 style="animation: fadeIn 0.8s ease-out 0.3s both;">Estimated Launch Time:</h2>
            <div class="countdown" style="animation: fadeIn 0.8s ease-out 0.4s both;">
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
            <div class="info-grid" style="animation: fadeIn 0.8s ease-out 0.5s both;">
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="info-title">New Features</div>
                    <div class="info-text">We're developing exciting new features and improvements to enhance your experience.</div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <div class="info-title">Modern Design</div>
                    <div class="info-text">Building a fresh, modern interface that's both beautiful and user-friendly.</div>
                </div>
            </div>
            <div class="action-buttons" style="animation: fadeIn 0.8s ease-out 0.6s both;">
                <a href="tel:+966538092933" class="btn btn-primary">
                    <i class="fas fa-phone"></i> Contact Us
                </a>
                <a href="mailto:it@almutlak.com" class="btn btn-outline">
                    <i class="fas fa-envelope"></i> Email Support
                </a>
            </div>
            <div class="contact-info" style="animation: fadeIn 0.8s ease-out 0.7s both;">
                <div class="contact-text">For urgent inquiries, please contact us at</div>
                <a href="mailto:it@almutlak.com" class="contact-email">it@almutlak.com</a>
            </div>
        </div>
    </div>
    <script>
        // Define the start and end dates for the construction period
        // Start: February 23, 2026 (today)
        // End: March 31, 2026 (36 days from now)
        const startDate = new Date("2026-02-24 00:00:00").getTime();
        const countDownDate = new Date("2026-03-31 23:59:59").getTime();

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
                document.querySelector("h1").innerText = "We're Live!";
                document.querySelector(".subtitle").innerText = "Our new website is now available";
                document.querySelector(".status-badge").innerText = "Complete";
                document.querySelector(".status-badge").style.background = "linear-gradient(135deg, #4caf50, #8bc34a)";
            }
        }, 1000);
    </script>
</body>

</html>