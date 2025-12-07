<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Code</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">{{ config('app.name') }}</h1>
    </div>

    <div style="background: #f8f9fa; padding: 40px 30px; border-radius: 0 0 8px 8px;">
        <h2 style="color: #333; margin-top: 0;">Your Authentication Code</h2>

        <p style="font-size: 16px; color: #666;">
            You requested a two-factor authentication code. Use the code below to complete your sign-in:
        </p>

        <div style="background: white; border: 2px solid #667eea; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0;">
            <div style="font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #667eea; font-family: 'Courier New', monospace;">
                {{ $code }}
            </div>
        </div>

        <p style="font-size: 14px; color: #999; margin-top: 30px;">
            This code will expire in 10 minutes.
        </p>

        <p style="font-size: 14px; color: #999;">
            If you didn't request this code, please ignore this email or contact support if you have concerns.
        </p>
    </div>

    <div style="text-align: center; padding: 20px; color: #999; font-size: 12px;">
        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
