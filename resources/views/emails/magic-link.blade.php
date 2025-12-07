<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic Link for Two-Factor Authentication</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">{{ config('app.name') }}</h1>
    </div>

    <div style="background: #f8f9fa; padding: 40px 30px; border-radius: 0 0 8px 8px;">
        <h2 style="color: #333; margin-top: 0;">Your Magic Link</h2>

        <p style="font-size: 16px; color: #666;">
            You requested a magic link for two-factor authentication. Click the button below to complete your sign-in:
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                Complete Sign-In
            </a>
        </div>

        <p style="font-size: 14px; color: #666; margin-top: 30px;">
            Or copy and paste this URL into your browser:
        </p>

        <div style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px; word-break: break-all; font-size: 12px; color: #667eea;">
            {{ $url }}
        </div>

        <p style="font-size: 14px; color: #999; margin-top: 30px;">
            This link will expire in 15 minutes.
        </p>

        <p style="font-size: 14px; color: #999;">
            If you didn't request this link, please ignore this email or contact support if you have concerns.
        </p>
    </div>

    <div style="text-align: center; padding: 20px; color: #999; font-size: 12px;">
        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
