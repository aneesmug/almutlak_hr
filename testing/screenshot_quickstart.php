<?php
/**
 * Screenshot System Quick Start Guide
 * Simple HTML page to access all screenshot tools
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Screenshot System - Quick Start</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1200px;
        }
        .hero {
            background: white;
            border-radius: 16px;
            padding: 60px 40px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        .hero h1 {
            color: #667eea;
            font-size: 44px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .hero p {
            color: #666;
            font-size: 18px;
            margin: 0;
        }
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .tool-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        .tool-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.25);
        }
        .tool-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .tool-card-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .tool-card-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }
        .tool-card-body {
            padding: 20px;
        }
        .tool-card-desc {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .tool-card-link {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .tool-card-link:hover {
            background: #764ba2;
            color: white;
            text-decoration: none;
        }
        .admin-badge {
            display: inline-block;
            background: #ff6b6b;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .steps-section {
            background: white;
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .steps-section h2 {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }
        .step {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            align-items: flex-start;
        }
        .step-number {
            flex-shrink: 0;
            width: 50px;
            height: 50px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
        }
        .step-content h3 {
            color: #333;
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }
        .step-content p {
            color: #666;
            margin: 0;
            line-height: 1.6;
        }
        .docs-section {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .docs-section h2 {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }
        .doc-list {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .doc-item {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        .doc-item:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        .doc-item a {
            display: block;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .doc-item a:hover {
            text-decoration: underline;
        }
        .doc-item-desc {
            font-size: 13px;
            color: #666;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin: 0;
        }
        @media (max-width: 768px) {
            .hero {
                padding: 40px 20px;
            }
            .hero h1 {
                font-size: 32px;
            }
            .hero p {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Hero Section -->
        <div class="hero">
            <h1><i class="fas fa-images"></i> Screenshot System</h1>
            <p>Complete guide for adding step-by-step screenshots to your system guide</p>
        </div>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat">
                <div class="stat-number">43</div>
                <div class="stat-label">Total Screenshots Needed</div>
            </div>
            <div class="stat">
                <div class="stat-number">5</div>
                <div class="stat-label">Main Sections</div>
            </div>
            <div class="stat">
                <div class="stat-number">3-4</div>
                <div class="stat-label">Estimated Hours</div>
            </div>
            <div class="stat">
                <div class="stat-number">5MB</div>
                <div class="stat-label">Max File Size</div>
            </div>
        </div>

        <!-- Main Tools -->
        <div class="tools-grid">
            <!-- Admin Dashboard -->
            <div class="tool-card">
                <div class="tool-card-header">
                    <div class="tool-card-icon"><i class="fas fa-tachometer-alt"></i></div>
                    <h3 class="tool-card-title">Admin Dashboard</h3>
                </div>
                <div class="tool-card-body">
                    <div class="admin-badge">ADMIN ONLY</div>
                    <p class="tool-card-desc">Central hub for managing all screenshots. View progress, statistics, and access all tools.</p>
                    <a href="screenshot_admin_dashboard.php" class="tool-card-link"><i class="fas fa-arrow-right"></i> Open</a>
                </div>
            </div>

            <!-- Upload Panel -->
            <div class="tool-card">
                <div class="tool-card-header">
                    <div class="tool-card-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <h3 class="tool-card-title">Upload Screenshots</h3>
                </div>
                <div class="tool-card-body">
                    <div class="admin-badge">ADMIN ONLY</div>
                    <p class="tool-card-desc">Upload screenshots one at a time. Select section, step, add title, and upload image.</p>
                    <a href="manage_guide_screenshots.php" class="tool-card-link"><i class="fas fa-arrow-right"></i> Open</a>
                </div>
            </div>

            <!-- Requirements -->
            <div class="tool-card">
                <div class="tool-card-header">
                    <div class="tool-card-icon"><i class="fas fa-list-ul"></i></div>
                    <h3 class="tool-card-title">What's Needed</h3>
                </div>
                <div class="tool-card-body">
                    <div class="admin-badge">ADMIN ONLY</div>
                    <p class="tool-card-desc">Visual guide showing exactly which screenshots you need for each section and step.</p>
                    <a href="screenshot_requirements.php" class="tool-card-link"><i class="fas fa-arrow-right"></i> Open</a>
                </div>
            </div>

            <!-- Progress Tracker -->
            <div class="tool-card">
                <div class="tool-card-header">
                    <div class="tool-card-icon"><i class="fas fa-check-circle"></i></div>
                    <h3 class="tool-card-title">Track Progress</h3>
                </div>
                <div class="tool-card-body">
                    <div class="admin-badge">ADMIN ONLY</div>
                    <p class="tool-card-desc">Interactive checklist to track which screenshots you've uploaded. Auto-saves progress.</p>
                    <a href="screenshot_checklist.html" class="tool-card-link"><i class="fas fa-arrow-right"></i> Open</a>
                </div>
            </div>

            <!-- System Guide -->
            <div class="tool-card">
                <div class="tool-card-header">
                    <div class="tool-card-icon"><i class="fas fa-book"></i></div>
                    <h3 class="tool-card-title">View System Guide</h3>
                </div>
                <div class="tool-card-body">
                    <p class="tool-card-desc">See how the system guide looks to employees. View all uploaded screenshots in action.</p>
                    <a href="system_guide.php" class="tool-card-link"><i class="fas fa-arrow-right"></i> Open</a>
                </div>
            </div>

            <!-- Documentation -->
            <div class="tool-card">
                <div class="tool-card-header">
                    <div class="tool-card-icon"><i class="fas fa-file-alt"></i></div>
                    <h3 class="tool-card-title">Documentation</h3>
                </div>
                <div class="tool-card-body">
                    <p class="tool-card-desc">Comprehensive guides and best practices for taking and uploading screenshots.</p>
                    <a href="SCREENSHOT_SYSTEM_COMPLETE_GUIDE.md" class="tool-card-link" download><i class="fas fa-download"></i> Download</a>
                </div>
            </div>
        </div>

        <!-- Quick Start Steps -->
        <div class="steps-section">
            <h2><i class="fas fa-rocket"></i> Quick Start (5 Steps)</h2>
            
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Review Requirements</h3>
                    <p>Go to <strong>screenshot_requirements.php</strong> to see a visual breakdown of every screenshot needed.</p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Prepare Screenshots</h3>
                    <p>Take screenshots of each step using 1280x720 resolution. Hide sensitive information like names and IDs.</p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Start Uploading</h3>
                    <p>Go to <strong>manage_guide_screenshots.php</strong> and upload screenshots one at a time. Select section, step, add title, upload file.</p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>Track Progress</h3>
                    <p>Use <strong>screenshot_checklist.html</strong> to check off each screenshot and see your completion percentage.</p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">5</div>
                <div class="step-content">
                    <h3>Verify Results</h3>
                    <p>Visit <strong>system_guide.php</strong> to see how employees will view the guide with your screenshots.</p>
                </div>
            </div>
        </div>

        <!-- What You Need to Upload -->
        <div class="steps-section">
            <h2><i class="fas fa-images"></i> Screenshots by Section</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                    <h4 style="color: #333; margin: 0 0 10px 0;">Vacations & Leaves</h4>
                    <p style="margin: 0; color: #666; font-size: 13px;">
                        <strong>21 screenshots</strong><br>
                        • Annual Leave: 7<br>
                        • Emergency: 6-7<br>
                        • Encashment: 6-7
                    </p>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                    <h4 style="color: #333; margin: 0 0 10px 0;">Loans</h4>
                    <p style="margin: 0; color: #666; font-size: 13px;">
                        <strong>12 screenshots</strong><br>
                        • EOS Loan: 4<br>
                        • House Loan: 4<br>
                        • Advance Salary: 4
                    </p>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                    <h4 style="color: #333; margin: 0 0 10px 0;">Other Sections</h4>
                    <p style="margin: 0; color: #666; font-size: 13px;">
                        <strong>10 screenshots</strong><br>
                        • Excuse Leave: 4<br>
                        • Resignation: 3<br>
                        • Rejoin: 3
                    </p>
                </div>
            </div>
        </div>

        <!-- Documentation Files -->
        <div class="docs-section">
            <h2><i class="fas fa-folder-open"></i> Documentation & Guides</h2>
            
            <ul class="doc-list">
                <li class="doc-item">
                    <a href="SCREENSHOT_SYSTEM_COMPLETE_GUIDE.md" download><i class="fas fa-book"></i> Complete Guide</a>
                    <div class="doc-item-desc">Full documentation with all details about the system</div>
                </li>
                <li class="doc-item">
                    <a href="SCREENSHOT_UPLOAD_INSTRUCTIONS.md" download><i class="fas fa-list-check"></i> Upload Instructions</a>
                    <div class="doc-item-desc">Step-by-step instructions for taking and uploading screenshots</div>
                </li>
                <li class="doc-item">
                    <a href="SCREENSHOT_SYSTEM_SETUP_COMPLETE.txt" download><i class="fas fa-info-circle"></i> Setup Overview</a>
                    <div class="doc-item-desc">Overview of what's been set up and ready to use</div>
                </li>
                <li class="doc-item">
                    <a href="SCREENSHOT_SETUP_GUIDE.md" download><i class="fas fa-cogs"></i> Technical Guide</a>
                    <div class="doc-item-desc">Technical details about database and file organization</div>
                </li>
            </ul>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 50px; color: white; padding: 30px 0;">
            <p style="margin: 0 0 15px 0; font-size: 16px;">Ready to get started?</p>
            <a href="screenshot_admin_dashboard.php" style="display: inline-block; background: white; color: #667eea; padding: 15px 40px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 16px; transition: all 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <i class="fas fa-play"></i> Go to Admin Dashboard
            </a>
            <p style="margin: 20px 0 0 0; font-size: 13px; opacity: 0.8;">System Created: December 14, 2025</p>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
