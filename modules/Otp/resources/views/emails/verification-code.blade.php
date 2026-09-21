@php
    $colors = mailThemeColors();
    $emailPrimary = $colors['primary'];
    $emailLight = $colors['light'];
    $appName = mailAppName();
    $logo = mailLogoUrl();
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            max-width: 150px;
            height: auto;
        }

        .content {
            background-color: #fff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            margin-top: 0;
            font-size: 24px;
        }

        .code {
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            color: {{ $emailPrimary }};
            padding: 15px 0;
            margin: 20px 0;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            letter-spacing: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            color: #777;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $appName }}">
            @else
                <span style="font-size: 21px; font-weight: 700; color: {{ $emailPrimary }}; letter-spacing: .2px;">{{ $appName }}</span>
            @endif
        </div>
        <div class="content">
            <h1>Verification Code</h1>
            <p>Thank you for using {{ $appName }}. Please use the following verification code
                to
                complete your
                verification:</p>
            <div class="code">{{ $code }}</div>
            <p>This code is valid for 30 minutes.</p>
        </div>
        <div class="footer">
            <p>If you did not request this code, please ignore this email.</p>
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>
