<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Document Invitation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f8fa;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e1e4e8;
        }

        .header {
            background-color: #1d72b8;
            color: white;
            padding: 20px;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
        }

        .content {
            padding: 25px;
            font-size: 15px;
            color: #333333;
        }

        .btn {
            display: inline-block;
            background-color: #1d72b8;
            color: white;
            padding: 12px 24px;
            margin-top: 15px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .footer {
            background-color: #f6f8fa;
            padding: 15px;
            font-size: 13px;
            color: #6c757d;
            text-align: center;
            border-top: 1px solid #e1e4e8;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            Document Uploaded
        </div>

        <!-- Content -->
        <div class="content">
            <p>Hello,</p>
            <p>Your document has been uploaded successfully:</p>
            <h3 style="color: #1d72b8; margin-top: 10px;">{{ $documentName }}</h3>

            <p style="margin: 8px 0;">
                <strong>Project:</strong> {{ $projectLabel }}<br>
                <strong>Uploaded at:</strong> {{ $uploadedAt ?? '' }}
            </p>

            <p>Please click the button below to view the document:</p>
            <p style="text-align: center;">
                <a href="{{ $viewUrl }}" class="btn">View Document</a>
            </p>
            <p>If the button above doesn't work, copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #1d72b8;">
                {{ $viewUrl }}
            </p>
            <p>Thank you,<br>Noahlex App Team</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            &copy; {{ date('Y') }} Noahlex. All rights reserved.
        </div>
    </div>
</body>

</html>