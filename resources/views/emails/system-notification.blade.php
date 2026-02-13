<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #003B5C 0%, #00AEEF 100%);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
        }
        .icon.success { color: #10B981; }
        .icon.warning { color: #F59E0B; }
        .icon.error { color: #EF4444; }
        .icon.info { color: #3B82F6; }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 15px;
        }
        .message {
            color: #6B7280;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #003B5C;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
        }
        .button:hover {
            background-color: #002B42;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            color: #9CA3AF;
            font-size: 14px;
        }
        .footer a {
            color: #6B7280;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 DOST Management Indicator System</h1>
        </div>

        <div class="content">
            @php
                $iconClass = match($type) {
                    'success' => 'success',
                    'warning' => 'warning',
                    'error' => 'error',
                    default => 'info',
                };
                $icon = match($type) {
                    'success' => '✅',
                    'warning' => '⚠️',
                    'error' => '❌',
                    default => 'ℹ️',
                };
            @endphp

            <div class="icon {{ $iconClass }}">
                {{ $icon }}
            </div>

            <div class="title">{{ $title }}</div>
            <div class="message">{{ $message }}</div>

            @if($actionUrl)
                <div style="text-align: center;">
                    <a href="{{ $actionUrl }}" class="button">View Indicator</a>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>This is an automated notification from DOST MIS.</p>
            <p>
                <a href="{{ config('app.url') }}">{{ config('app.name') }}</a>
            </p>
        </div>
    </div>
</body>
</html>
