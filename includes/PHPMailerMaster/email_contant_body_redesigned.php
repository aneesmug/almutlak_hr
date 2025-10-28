<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
<head>
<meta name="viewport" content="width=device-width" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Almutlak System - Smart Request Notification</title>
<style type="text/css">
    body {
        background-color: #f6f6f6;
        width: 100% !important;
        height: 100%;
        line-height: 1.6em;
        margin: 0;
        padding: 0;
        -webkit-font-smoothing: antialiased;
        -webkit-text-size-adjust: none;
    }
    img {
        max-width: 100%;
    }
    .body-wrap {
        background-color: #f6f6f6;
        width: 100%;
    }
    .container {
        display: block !important;
        max-width: 600px !important;
        margin: 0 auto !important;
        clear: both !important;
    }
    .content {
        max-width: 600px;
        margin: 0 auto;
        display: block;
        padding: 20px;
    }
    .main {
        background-color: #fff;
        border: 1px solid #e9e9e9;
        border-radius: 3px;
    }
    .content-wrap {
        padding: 30px; /* Increased padding */
    }
    .header {
        text-align: center;
        padding: 20px 0;
        border-bottom: 1px solid #e9e9e9;
        margin-bottom: 20px;
    }
    .content-block {
        padding: 0 0 20px;
    }
    .footer {
        padding: 20px;
        text-align: center;
        color: #888;
        font-size: 12px;
        clear: both;
    }
    .btn-primary {
        text-decoration: none;
        color: #FFF !important; /* Ensure text is white */
        background-color: #02c0ce;
        border: solid #02c0ce;
        border-width: 8px 16px;
        line-height: 2em;
        font-weight: bold;
        text-align: center;
        cursor: pointer;
        display: inline-block;
        border-radius: 5px;
        text-transform: capitalize;
    }
    h1, h2, h3 {
        font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif;
        color: #000;
        margin: 20px 0 10px;
        line-height: 1.2em;
        font-weight: 400;
    }
    h1 { font-size: 28px; }
    h2 { font-size: 24px; }
    h3 { font-size: 18px; }
    p, ul, ol {
        margin-bottom: 15px;
        font-weight: normal;
        font-size: 15px; /* Slightly larger base font */
    }
    strong {
        font-weight: bold;
    }
    .details {
        background-color: #f9f9f9;
        padding: 15px;
        border-left: 3px solid #02c0ce;
        margin-bottom: 20px;
    }
    .details p {
        margin-bottom: 5px;
        font-size: 14px;
    }

    @media only screen and (max-width: 640px) {
        body { padding: 0 !important; }
        .container { width: 100% !important; padding: 0 !important; }
        .content { padding: 0 !important; }
        .content-wrap { padding: 15px !important; } /* Adjusted padding for mobile */
        .header img { height: 60px !important; } /* Adjusted logo size for mobile */
        h1 { font-size: 22px !important; }
        h2 { font-size: 20px !important; }
        h3 { font-size: 16px !important; }
        p, ul, ol { font-size: 14px !important; }
        .btn-primary { display: block !important; width: auto !important; } /* Make button block level */
    }
</style>
</head>

<body itemscope itemtype="http://schema.org/EmailMessage" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: none; width: 100% !important; height: 100%; line-height: 1.6em; background-color: #f6f6f6; margin: 0;" bgcolor="#f6f6f6">

<table class="body-wrap" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; width: 100%; background-color: #f6f6f6; margin: 0;" bgcolor="#f6f6f6">
    <tr style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
        <td style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0;" valign="top"></td>
        <td class="container" width="600" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; display: block !important; max-width: 600px !important; clear: both !important; margin: 0 auto;" valign="top">
            <div class="content" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; max-width: 600px; display: block; margin: 0 auto; padding: 20px;">
                <table class="main" width="100%" cellpadding="0" cellspacing="0" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; border-radius: 3px; background-color: #fff; margin: 0; border: 1px solid #e9e9e9;" bgcolor="#fff">
                    <tr style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                        <td class="header" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 16px; vertical-align: top; color: #fff; font-weight: 500; text-align: center; border-radius: 3px 3px 0 0; margin: 0; padding: 20px 0; border-bottom: 1px solid #e9e9e9; margin-bottom: 20px;" align="center" valign="top">
                            <!-- Embedded Logo - Replace with your actual base64 encoded logo -->
                            <img height="80" alt="Almutlak Logo" src="https://hr.almutlaksystem.com/assets/logo/logo_color_sm.png" />
                        </td>
                    </tr>
                    <tr style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                        <td class="content-wrap" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 30px;" valign="top">
                            <table width="100%" cellpadding="0" cellspacing="0" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                <tr style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                    <td class="content-block" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 0 0 20px;" valign="top">
                                        <h3>Smart Request Notification</h3>
                                    </td>
                                </tr>
                                <tr style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                    <td class="content-block" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 0 0 20px;" valign="top">
                                        <p>Dear Approver,</p>
                                        <p>A new request requires your attention.</p>
                                    </td>
                                </tr>
                                <tr style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                    <td class="content-block details" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 15px; background-color: #f9f9f9; border-left: 3px solid #02c0ce; margin-bottom: 20px;" valign="top" bgcolor="#F9F9F9">
                                        <p style="margin-bottom: 5px; font-size: 14px;"><strong>Request No.:</strong> $invnoget</p>
                                        <p style="margin-bottom: 5px; font-size: 14px;"><strong>Subject:</strong> $sub_title_get</p>
                                        <p style="margin-bottom: 5px; font-size: 14px;"><strong>Prepared by:</strong> $userwelext</p>
                                        <p style="margin-bottom: 5px; font-size: 14px;"><strong>Department:</strong> $dept</p>
                                    </td>
                                </tr>
                                <tr style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                    <td class="content-block" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 0 0 20px;" valign="top">
                                        Please review the details in the Smart System or click the button below to open the request directly.
                                    </td>
                                </tr>
                                <tr style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                    <td class="content-block" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 0 0 20px; text-align: center;" valign="top" align="center">
                                        <a href="https://hr.almutlak.com/open_request.php?id=$invnoget" class="btn-primary" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; color: #FFF !important; text-decoration: none; line-height: 2em; font-weight: bold; text-align: center; cursor: pointer; display: inline-block; border-radius: 5px; text-transform: capitalize; background-color: #02c0ce; margin: 0; border-color: #02c0ce; border-style: solid; border-width: 8px 16px;">Open Request</a>
                                    </td>
                                </tr>
                                <tr style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                                    <td class="content-block" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0; padding: 0 0 20px;" valign="top">
                                        <p>Best Regards,<br /><strong>Almutlak System</strong></p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <div class="footer" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 12px; width: 100%; clear: both; color: #888; margin: 0; padding: 20px; text-align: center;">
                    <table width="100%" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                        <tr style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; margin: 0;">
                            <td class="aligncenter content-block" style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 12px; vertical-align: top; color: #888; text-align: center; margin: 0; padding: 0 0 20px;" align="center" valign="top">
                                Almutlak Trade & Services Co. | This is an automated notification.
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </td>
        <td style="font-family: 'Helvetica Neue',Helvetica,Arial,sans-serif; box-sizing: border-box; font-size: 14px; vertical-align: top; margin: 0;" valign="top"></td>
    </tr>
</table>

</body>
</html>
