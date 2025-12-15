<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screenshot System - Getting Started</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 1000px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px 12px 0 0 !important;
        }
        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        .btn-step {
            display: block;
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: left;
        }
        .btn-step i {
            margin-right: 10px;
            font-size: 20px;
        }
        .btn-step-1 {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #2196f3;
        }
        .btn-step-1:hover {
            background: #bbdefb;
            text-decoration: none;
        }
        .btn-step-2 {
            background: #f3e5f5;
            color: #6a1b9a;
            border-left: 4px solid #9c27b0;
        }
        .btn-step-2:hover {
            background: #e1bee7;
            text-decoration: none;
        }
        .btn-step-3 {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        .btn-step-3:hover {
            background: #c8e6c9;
            text-decoration: none;
        }
        .hero {
            background: white;
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        .hero h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .hero p {
            color: #666;
            font-size: 1.1em;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .feature-box {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #eee;
        }
        .feature-box i {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
            display: block;
        }
        .feature-box h6 {
            color: #333;
            font-weight: 600;
            margin: 0;
        }
        .docs {
            margin-top: 30px;
        }
        .doc-link {
            display: flex;
            align-items: center;
            padding: 12px;
            margin: 8px 0;
            background: white;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        .doc-link:hover {
            text-decoration: none;
            color: #667eea;
            padding-left: 16px;
        }
        .doc-link i {
            font-size: 20px;
            margin-right: 10px;
            color: #667eea;
        }
        .badge-ready {
            background: #4caf50;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Hero Section -->
        <div class="hero">
            <h1>📸 Screenshot System</h1>
            <p>Upload and manage screenshots for the system guide</p>
            <div class="feature-grid" style="max-width: 600px; margin: 30px auto;">
                <div class="feature-box">
                    <i class="fas fa-image"></i>
                    <h6>Upload Images</h6>
                </div>
                <div class="feature-box">
                    <i class="fas fa-gallery"></i>
                    <h6>Beautiful Gallery</h6>
                </div>
                <div class="feature-box">
                    <i class="fas fa-lock"></i>
                    <h6>Admin Only</h6>
                </div>
            </div>
            <span class="badge-ready">✅ Production Ready</span>
        </div>

        <!-- Three Step Guide -->
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>📊 Step 1: Create Table</h5>
                    </div>
                    <div class="card-body">
                        <p>Initialize the database table for screenshots.</p>
                        <p class="text-muted small">Run once to create guide_screenshots table.</p>
                        <a href="create_screenshots_table.php" class="btn-step btn-step-1">
                            <i class="fas fa-database"></i> Create Table
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>⬆️ Step 2: Upload Screenshots</h5>
                    </div>
                    <div class="card-body">
                        <p>Upload screenshots by section and step.</p>
                        <p class="text-muted small">Admin access required.</p>
                        <a href="manage_guide_screenshots.php" class="btn-step btn-step-2">
                            <i class="fas fa-upload"></i> Upload Manager
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>👁️ Step 3: View Guide</h5>
                    </div>
                    <div class="card-body">
                        <p>View system guide with your screenshots.</p>
                        <p class="text-muted small">Screenshots appear automatically.</p>
                        <a href="system_guide.php" class="btn-step btn-step-3">
                            <i class="fas fa-book"></i> View System Guide
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information Cards -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>📚 Documentation</h5>
                    </div>
                    <div class="card-body">
                        <div class="docs">
                            <a href="screenshot_setup_info.php" class="doc-link">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <strong>Setup Information</strong>
                                    <small style="display: block; color: #999;">Browser-based setup guide</small>
                                </div>
                            </a>
                            <a href="SCREENSHOT_SETUP_GUIDE.md" class="doc-link" target="_blank">
                                <i class="fas fa-file-alt"></i>
                                <div>
                                    <strong>Detailed Instructions</strong>
                                    <small style="display: block; color: #999;">Step-by-step guide (Markdown)</small>
                                </div>
                            </a>
                            <a href="SCREENSHOT_SYSTEM_README.md" class="doc-link" target="_blank">
                                <i class="fas fa-book"></i>
                                <div>
                                    <strong>Full Documentation</strong>
                                    <small style="display: block; color: #999;">Complete technical docs</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>🎯 Quick Links</h5>
                    </div>
                    <div class="card-body">
                        <div class="docs">
                            <a href="SCREENSHOT_QUICK_REFERENCE.txt" class="doc-link" target="_blank">
                                <i class="fas fa-bookmark"></i>
                                <div>
                                    <strong>Quick Reference Card</strong>
                                    <small style="display: block; color: #999;">Cheat sheet for all operations</small>
                                </div>
                            </a>
                            <a href="README_SETUP_COMPLETE.txt" class="doc-link" target="_blank">
                                <i class="fas fa-clipboard"></i>
                                <div>
                                    <strong>Setup Complete Guide</strong>
                                    <small style="display: block; color: #999;">Overview of everything created</small>
                                </div>
                            </a>
                            <a href="system_guide.php" class="doc-link">
                                <i class="fas fa-book-open"></i>
                                <div>
                                    <strong>System Guide</strong>
                                    <small style="display: block; color: #999;">View employee guide with screenshots</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>✨ Features</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> Upload real screenshots</li>
                            <li><i class="fas fa-check text-success"></i> Automatic organization</li>
                            <li><i class="fas fa-check text-success"></i> Beautiful gallery display</li>
                            <li><i class="fas fa-check text-success"></i> Icon placeholders fallback</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> Admin-only uploads</li>
                            <li><i class="fas fa-check text-success"></i> Image preview</li>
                            <li><i class="fas fa-check text-success"></i> Easy management</li>
                            <li><i class="fas fa-check text-success"></i> Mobile responsive</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Files Created -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>📁 Files Created</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr style="background: #f9f9f9;">
                                <th>File</th>
                                <th>Purpose</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>create_screenshots_table.php</code></td>
                                <td>Create database table</td>
                            </tr>
                            <tr>
                                <td><code>manage_guide_screenshots.php</code></td>
                                <td>Upload/manage screenshots (Admin)</td>
                            </tr>
                            <tr>
                                <td><code>system_guide.php</code> (updated)</td>
                                <td>Display guide with images</td>
                            </tr>
                            <tr>
                                <td><code>includes/screenshot_helper.php</code></td>
                                <td>Helper functions</td>
                            </tr>
                            <tr>
                                <td><code>assets/screenshots/</code></td>
                                <td>Image storage folder</td>
                            </tr>
                            <tr>
                                <td><code>SCREENSHOT_SETUP_GUIDE.md</code></td>
                                <td>Detailed instructions</td>
                            </tr>
                            <tr>
                                <td><code>SCREENSHOT_SYSTEM_README.md</code></td>
                                <td>Full documentation</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 40px; color: white;">
            <p>✅ Screenshot system successfully installed</p>
            <p style="font-size: 12px; opacity: 0.8;">Version 1.0 | Production Ready | December 2025</p>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
