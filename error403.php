<?php
header("HTTP/1.1 403 Forbidden");
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .error-code {
            font-size: 100px;
            font-weight: bold;
            color: #dc3545;
            line-height: 1;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .error-message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .error-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .btn-home {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: transform 0.3s, box-shadow 0.3s;
            font-weight: 600;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .security-info {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔒</div>
        <div class="error-code">403</div>
        <div class="error-title">Access Denied</div>
        <div class="error-message">
            <p>Sorry, you don't have permission to access this page.</p>
            <?php if (!empty($_GET['page'])): ?>
                <p><strong><?= htmlspecialchars((string)$_GET['page'], ENT_QUOTES, 'UTF-8') ?></strong> is not included in your role's page access.</p>
            <?php endif; ?>
            <p>Contact your administrator if you believe you should have access.</p>
        </div>
        <a href="dashboard.php" class="btn-home">← Back to Dashboard</a>
        <div class="security-info">
            <p>If you believe this is an error, please contact the administrator.</p>
            <p><?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>
</body>
</html>
