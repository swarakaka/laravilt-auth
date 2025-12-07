<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Recovery Codes</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">{{ config('app.name') }}</h1>
    </div>

    <div style="background: #f8f9fa; padding: 40px 30px; border-radius: 0 0 8px 8px;">
        <h2 style="color: #333; margin-top: 0;">Your Recovery Codes</h2>

        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <p style="font-size: 14px; color: #856404; margin: 0; font-weight: 500;">
                ⚠️ Important: Save these codes in a secure location
            </p>
        </div>

        <p style="font-size: 16px; color: #666;">
            You have successfully enabled two-factor authentication. Below are your recovery codes that can be used to access your account if you lose your device:
        </p>

        <div style="background: white; border: 2px solid #667eea; border-radius: 8px; padding: 20px; margin: 30px 0;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-family: 'Courier New', monospace; font-size: 14px; color: #333;">
                @foreach($recoveryCodes as $code)
                    <div style="padding: 8px; background: #f8f9fa; border-radius: 4px; text-align: center;">
                        {{ $code }}
                    </div>
                @endforeach
            </div>
        </div>

        <div style="background: #e3f2fd; border: 1px solid #2196F3; border-radius: 8px; padding: 15px; margin-top: 20px;">
            <p style="font-size: 14px; color: #1565C0; margin: 0;">
                <strong>Important Notes:</strong>
            </p>
            <ul style="font-size: 14px; color: #1565C0; margin: 10px 0 0 0; padding-left: 20px;">
                <li>Each code can only be used once</li>
                <li>Store these codes in a safe place (password manager recommended)</li>
                <li>If you lose all your codes, you'll need to contact support to regain access</li>
            </ul>
        </div>

        <p style="font-size: 14px; color: #999; margin-top: 30px;">
            If you didn't enable two-factor authentication, please contact support immediately.
        </p>
    </div>

    <div style="text-align: center; padding: 20px; color: #999; font-size: 12px;">
        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
