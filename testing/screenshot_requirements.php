<?php
/**
 * Screenshot Upload Quick Reference
 * Display a visual guide in the admin panel for what screenshots to upload
 */
require_once(__DIR__ . "/includes/init.php");
require_once(__DIR__ . "/includes/session_check.php");

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'administrator') {
    header("Location: profile.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Screenshot Upload Quick Reference</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .section-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #007bff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .step-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .step-item {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        .step-item:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.2);
        }
        .step-number {
            display: inline-block;
            background: #007bff;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            text-align: center;
            line-height: 32px;
            font-weight: 700;
            margin-right: 10px;
        }
        .step-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .step-description {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }
        .upload-link {
            margin-top: 25px;
            text-align: center;
        }
        .upload-link a {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .upload-link a:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .info-box strong {
            color: #004085;
        }
        .legend {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .legend-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 13px;
        }
        .color-tag {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 3px;
            margin-right: 8px;
        }
        .text-success { color: #28a745; }
        .text-info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fa fa-camera"></i> Screenshot Upload Quick Reference</h1>
            <p style="margin: 10px 0 0 0; color: #666;">Use this guide to know exactly which screenshots you need to upload for each section</p>
        </div>

        <div class="info-box">
            <strong>📸 Total Screenshots Needed: ~30 images</strong><br>
            Each section needs multiple step-by-step screenshots showing the complete process for users.
        </div>

        <!-- VACATIONS Section -->
        <div class="section-card">
            <div class="section-title">
                <i class="fa fa-plane"></i> VACATIONS & LEAVES (7 Screenshots per type)
            </div>
            
            <!-- Annual Leave -->
            <h5 style="margin-top: 20px; color: #333;"><strong>Annual Leave - Section: "vacations" - Step: 1</strong></h5>
            <div class="step-row">
                <div class="step-item">
                    <div class="step-title"><span class="step-number">1</span>Profile Page</div>
                    <div class="step-description">Show the profile menu/link in header. Show where to access profile from main menu.</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">2</span>More Button</div>
                    <div class="step-description">Show the "More" button or menu in the header section of the profile page.</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">3</span>Apply Option</div>
                    <div class="step-description">Show dropdown/menu displaying "Apply Annual Vacation" option after clicking More.</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">4</span>Vacation Form</div>
                    <div class="step-description">Show the complete vacation application form that appears after selecting the option.</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">5</span>Date Selection</div>
                    <div class="step-description">Show the date picker/calendar with start and end date fields visible.</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">6</span>Type Selection</div>
                    <div class="step-description">Show the dropdown with vacation type options (Annual, Emergency, Fly) visible.</div>
                </div>
            </div>

            <h5 style="margin-top: 25px; color: #333;"><strong>Emergency Leave - Section: "vacations" - Step: 2</strong></h5>
            <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Follow the same 6-7 steps but for Emergency Leave option instead</p>
            
            <h5 style="margin-top: 25px; color: #333;"><strong>Encashment - Section: "vacations" - Step: 3</strong></h5>
            <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Follow the same steps but for Encashment (converting vacation days to cash) option</p>

            <div class="upload-link">
                <a href="manage_guide_screenshots.php">→ Go to Upload Panel</a>
            </div>
        </div>

        <!-- LOANS Section -->
        <div class="section-card">
            <div class="section-title">
                <i class="fa fa-money-bill"></i> LOANS (4-5 Screenshots each)
            </div>
            
            <h5 style="margin-top: 0; color: #333;"><strong>EOS Loan - Section: "loans" - Step: 1</strong></h5>
            <div class="step-row">
                <div class="step-item">
                    <div class="step-title"><span class="step-number">1</span>Select EOS Loan</div>
                    <div class="step-description">Show "EOS Loan" option in the loan selection menu</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">2</span>Enter Amount</div>
                    <div class="step-description">Show the loan amount input field in the form</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">3</span>Installments</div>
                    <div class="step-description">Show the monthly installment selection/calculation area</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">4</span>Review & Submit</div>
                    <div class="step-description">Show the form summary and submit button</div>
                </div>
            </div>

            <h5 style="margin-top: 25px; color: #333;"><strong>House Loan - Section: "loans" - Step: 2</strong></h5>
            <div class="step-row">
                <div class="step-item">
                    <div class="step-title"><span class="step-number">1</span>Select House Loan</div>
                    <div class="step-description">Show "House Loan" option in menu</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">2</span>Property Details</div>
                    <div class="step-description">Show the property information form fields</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">3</span>Upload Contract</div>
                    <div class="step-description">Show the file upload area for real estate contract</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">4</span>Loan Details</div>
                    <div class="step-description">Show amount and tenure selection for loan</div>
                </div>
            </div>

            <h5 style="margin-top: 25px; color: #333;"><strong>Advance Salary - Section: "loans" - Step: 3</strong></h5>
            <div class="step-row">
                <div class="step-item">
                    <div class="step-title"><span class="step-number">1</span>Select Advance</div>
                    <div class="step-description">Show "Advance Salary" option</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">2</span>Enter Amount</div>
                    <div class="step-description">Show the advance amount input field</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">3</span>Repayment Period</div>
                    <div class="step-description">Show the repayment months selection</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">4</span>Submit</div>
                    <div class="step-description">Show the submit button and confirmation</div>
                </div>
            </div>

            <div class="upload-link">
                <a href="manage_guide_screenshots.php">→ Go to Upload Panel</a>
            </div>
        </div>

        <!-- EXCUSE LEAVE Section -->
        <div class="section-card">
            <div class="section-title">
                <i class="fa fa-calendar-times"></i> EXCUSE LEAVE (4 Screenshots)
            </div>
            
            <h5 style="margin-top: 0; color: #333;"><strong>Section: "excuse" - Step: 3</strong></h5>
            <div class="step-row">
                <div class="step-item">
                    <div class="step-title"><span class="step-number">1</span>Select Excuse</div>
                    <div class="step-description">Show "Excuse Leave" option in More menu</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">2</span>Choose Date</div>
                    <div class="step-description">Show the absence date picker/calendar</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">3</span>Enter Reason</div>
                    <div class="step-description">Show the reason text field for the absence</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">4</span>Submit</div>
                    <div class="step-description">Show the submit button for the excuse request</div>
                </div>
            </div>

            <div class="upload-link">
                <a href="manage_guide_screenshots.php">→ Go to Upload Panel</a>
            </div>
        </div>

        <!-- RESIGNATION Section -->
        <div class="section-card">
            <div class="section-title">
                <i class="fa fa-sign-out"></i> RESIGNATION (3 Screenshots)
            </div>
            
            <h5 style="margin-top: 0; color: #333;"><strong>Section: "resignation" - Step: 1</strong></h5>
            <div class="step-row">
                <div class="step-item">
                    <div class="step-title"><span class="step-number">1</span>Select Resignation</div>
                    <div class="step-description">Show "Apply Resignation" option in More menu</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">2</span>Resignation Form</div>
                    <div class="step-description">Show the resignation request form with all fields</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">3</span>Confirm & Submit</div>
                    <div class="step-description">Show the confirmation dialog and final submit button</div>
                </div>
            </div>

            <div class="upload-link">
                <a href="manage_guide_screenshots.php">→ Go to Upload Panel</a>
            </div>
        </div>

        <!-- REJOIN Section -->
        <div class="section-card">
            <div class="section-title">
                <i class="fa fa-plane-arrival"></i> REJOIN REQUEST (3 Screenshots)
            </div>
            
            <h5 style="margin-top: 0; color: #333;"><strong>Section: "rejoin" - Step: 3</strong></h5>
            <div class="step-row">
                <div class="step-item">
                    <div class="step-title"><span class="step-number">1</span>Select Rejoin</div>
                    <div class="step-description">Show "Rejoin Request" option in More menu</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">2</span>Confirm Date</div>
                    <div class="step-description">Show the return date confirmation form</div>
                </div>
                <div class="step-item">
                    <div class="step-title"><span class="step-number">3</span>Submit</div>
                    <div class="step-description">Show the submit button and confirmation message</div>
                </div>
            </div>

            <div class="upload-link">
                <a href="manage_guide_screenshots.php">→ Go to Upload Panel</a>
            </div>
        </div>

        <!-- Tips -->
        <div class="section-card">
            <h3 style="color: #28a745; margin-bottom: 15px;"><i class="fa fa-lightbulb"></i> Best Practices</h3>
            
            <div class="legend">
                <div class="legend-item">
                    <span class="color-tag" style="background: #28a745;"></span>
                    <strong>Clear Images</strong> - Use 1280x720 resolution
                </div>
                <div class="legend-item">
                    <span class="color-tag" style="background: #17a2b8;"></span>
                    <strong>Good Lighting</strong> - Ensure text is readable
                </div>
                <div class="legend-item">
                    <span class="color-tag" style="background: #ffc107;"></span>
                    <strong>Consistency</strong> - Same browser, same screen size
                </div>
                <div class="legend-item">
                    <span class="color-tag" style="background: #dc3545;"></span>
                    <strong>Hide Sensitive Data</strong> - Remove names, IDs
                </div>
                <div class="legend-item">
                    <span class="color-tag" style="background: #6c757d;"></span>
                    <strong>Small File Sizes</strong> - Keep under 2MB each
                </div>
                <div class="legend-item">
                    <span class="color-tag" style="background: #20c997;"></span>
                    <strong>Descriptive Titles</strong> - Make titles clear and specific
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; padding: 20px; background: white; border-radius: 8px;">
            <p style="margin: 0 0 15px 0; color: #666;">Ready to upload screenshots?</p>
            <a href="manage_guide_screenshots.php" style="display: inline-block; background: #007bff; color: white; padding: 15px 40px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 16px; transition: all 0.3s ease;" onmouseover="this.style.background='#0056b3'" onmouseout="this.style.background='#007bff'">
                <i class="fa fa-upload"></i> Open Upload Panel
            </a>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
