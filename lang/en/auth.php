<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auth Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the Auth plugin.
    | You are free to modify these language lines according to your
    | application's requirements.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Common Labels
    |--------------------------------------------------------------------------
    */
    'common' => [
        'save' => 'Save',
        'save_changes' => 'Save Changes',
        'saving' => 'Saving...',
        'cancel' => 'Cancel',
        'cancel_setup' => 'Cancel Setup',
        'canceling' => 'Canceling...',
        'delete' => 'Delete',
        'deleting' => 'Deleting...',
        'close' => 'Close',
        'logout' => 'Logout',
        'submit' => 'Submit',
        'processing' => 'Processing...',
        'continue' => 'Continue',
        'back' => 'Back',
        'or' => 'or',
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'new_password_confirmation' => 'Confirm New Password',
        'code' => 'Code',
        'recovery_code' => 'Recovery Code',
    ],

    /*
    |--------------------------------------------------------------------------
    | Login Page
    |--------------------------------------------------------------------------
    */
    'login' => [
        'title' => 'Sign In',
        'heading' => 'Welcome back',
        'subheading' => 'Enter your credentials to access your account',
        'button' => 'Sign In',
        'button_loading' => 'Signing In...',
        'no_account' => "Don't have an account?",
        'sign_up' => 'Sign up',
        'forgot_password' => 'Forgot password?',
        'remember_me' => 'Remember me',
        'or_continue_with' => 'Or continue with',
        'use_passkey' => 'Use a passkey',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register Page
    |--------------------------------------------------------------------------
    */
    'register' => [
        'title' => 'Create Account',
        'heading' => 'Create your account',
        'subheading' => 'Enter your details to get started',
        'button' => 'Create Account',
        'button_loading' => 'Creating Account...',
        'have_account' => 'Already have an account?',
        'sign_in' => 'Sign in',
        'agree_terms' => 'By creating an account, you agree to our',
        'terms' => 'Terms of Service',
        'and' => 'and',
        'privacy' => 'Privacy Policy',
    ],

    /*
    |--------------------------------------------------------------------------
    | Forgot Password Page
    |--------------------------------------------------------------------------
    */
    'forgot_password' => [
        'title' => 'Forgot Password',
        'heading' => 'Forgot your password?',
        'subheading' => "No worries, we'll send you reset instructions.",
        'button' => 'Send Reset Link',
        'button_loading' => 'Sending...',
        'remember_password' => 'Remember your password?',
        'back_to_login' => 'Back to login',
        'email_sent' => 'We have emailed your password reset link!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reset Password Page
    |--------------------------------------------------------------------------
    */
    'reset_password' => [
        'title' => 'Reset Password',
        'heading' => 'Reset your password',
        'subheading' => 'Enter your new password below',
        'button' => 'Reset Password',
        'button_loading' => 'Resetting...',
    ],

    /*
    |--------------------------------------------------------------------------
    | Set Password Page
    |--------------------------------------------------------------------------
    */
    'set_password' => [
        'title' => 'Set Password',
        'heading' => 'Set Your Password',
        'subheading' => 'Please set a password for your account to continue.',
        'button' => 'Set Password',
        'button_loading' => 'Setting...',
        'password_hint' => 'Password must be at least 8 characters.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Verification
    |--------------------------------------------------------------------------
    */
    'verify_email' => [
        'title' => 'Verify Email',
        'heading' => 'Verify your email',
        'subheading' => 'Please verify your email address by clicking the link we sent you.',
        'resend' => 'Resend verification email',
        'resend_loading' => 'Sending...',
        'sent' => 'A new verification link has been sent to your email address.',
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Verification
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'title' => 'Verify Code',
        'heading' => 'Enter verification code',
        'subheading' => 'Please enter the code we sent to your email.',
        'subheading_email' => 'Please enter the 6-digit code we sent to :email',
        'button' => 'Verify Code',
        'button_loading' => 'Verifying...',
        'didnt_receive' => "Didn't receive the code?",
        'resend' => 'Resend Code',
        'resending' => 'Resending...',
        'resent' => 'A new verification code has been sent to your email.',
        'expires_in' => 'Code expires in',
        'code_expired' => 'The code has expired. Please request a new one.',
        'session_expired' => 'Session expired. Please try again.',
        'user_not_found' => 'User not found.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Challenge Page
    |--------------------------------------------------------------------------
    */
    'two_factor_challenge' => [
        'title' => 'Two-Factor Authentication',
        'heading' => 'Two-Factor Authentication',
        'subheading' => 'Please confirm access to your account',
        'choose_method' => 'Choose your verification method',
        'authenticator_code' => 'Authenticator Code',
        'authenticator_desc' => 'Use your authenticator app',
        'passkey' => 'Passkey',
        'passkey_desc' => 'Use biometric or security key',
        'magic_link' => 'Magic Link',
        'magic_link_desc' => 'Receive link via email',
        'recovery_code' => 'Recovery Code',
        'recovery_code_desc' => 'Use backup recovery code',
        'verify_button' => 'Verify Code',
        'verify_loading' => 'Verifying...',
        'back_to_methods' => 'Back to authentication methods',
        'lost_device' => 'Lost your device?',
        'use_recovery' => 'Use a recovery code',
        'have_device' => 'Have your device?',
        'use_auth_code' => 'Use authentication code',
        'use_emergency_code' => 'Use one of your emergency recovery codes',
        'enter_recovery' => 'Enter Recovery Code',
        'magic_link_sent' => 'Magic link sent! Check your email.',
        'magic_link_error' => 'Failed to send magic link',
        'didnt_receive' => "Didn't receive the code?",
        'resend' => 'Resend Code',
        'resending' => 'Resending...',
        'code_resent' => 'A new verification code has been sent to your email.',
        'resend_error' => 'Failed to resend code',
        'session_expired' => 'Session expired. Please login again.',
        'user_not_found' => 'User not found.',
        'resend_not_available' => 'Resend is not available for this authentication method.',
        'method_not_available' => 'Authentication method not available.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Recovery Page
    |--------------------------------------------------------------------------
    */
    'two_factor_recovery' => [
        'title' => 'Two-Factor Recovery',
        'heading' => 'Two-Factor Recovery',
        'subheading' => 'Enter one of your emergency recovery codes.',
        'button' => 'Verify',
        'placeholder' => 'Enter your recovery code',
        'invalid_code' => 'The recovery code is invalid.',
        'back_to_challenge' => 'Back to verification',
    ],

    /*
    |--------------------------------------------------------------------------
    | Magic Link
    |--------------------------------------------------------------------------
    */
    'magic_link' => [
        'send' => 'Send magic link',
        'sending' => 'Sending...',
        'sent' => 'Magic link sent! Check your email.',
        'error' => 'Failed to send magic link',
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Login
    |--------------------------------------------------------------------------
    */
    'social' => [
        'or_continue_with' => 'Or continue with',
        'google' => 'Google',
        'github' => 'GitHub',
        'facebook' => 'Facebook',
        'twitter' => 'Twitter',
        'linkedin' => 'LinkedIn',
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile - Information
    |--------------------------------------------------------------------------
    */
    'profile' => [
        'page' => [
            'subheading' => 'Manage your account settings and preferences',
            'profile_updated' => 'Profile updated successfully.',
            'password_updated' => 'Password updated successfully.',
        ],

        'information' => [
            'title' => 'Profile Information',
            'description' => 'Update your account\'s profile information and email address.',
            'email_unverified' => 'Your email address is unverified.',
        ],

        'update_profile' => 'Update Profile',

        'password' => [
            'title' => 'Update Password',
            'description' => 'Ensure your account is using a long, random password to stay secure.',
            'update' => 'Update Password',
            'updating' => 'Updating...',
            'current_placeholder' => 'Enter your current password',
            'new_placeholder' => 'Enter your new password',
            'confirm_placeholder' => 'Confirm your new password',
            'current_incorrect' => 'The current password is incorrect.',
        ],

        'delete' => [
            'title' => 'Delete Account',
            'description' => 'Permanently delete your account and all of your data. This action cannot be undone.',
            'confirm' => 'Are you sure you want to delete your account? This action cannot be undone.',
            'delete' => 'Delete Account',
            'deleting' => 'Deleting...',
        ],

        'two_factor' => [
            'title' => 'Two-Factor Auth',
            'description' => 'Add an additional layer of security to your account using two-factor authentication.',
            'enabled' => 'Two-factor authentication is enabled',
            'method' => 'Method: :method',
            'using_method' => 'Using :method method',
            'method_totp' => 'Authenticator App',
            'method_email' => 'Email',
            'not_enabled' => 'Two-factor authentication is not enabled yet. Enable it for additional security.',
            'enable' => 'Enable Two-Factor Authentication',
            'manage' => 'Manage',
            'disable_title' => 'Disable Two-Factor Authentication',
            'disable_desc' => 'This will remove the extra layer of security from your account',
            'disable_warning' => 'Are you sure you want to disable two-factor authentication? This will make your account less secure.',
            'enable_title' => 'Enable Two-Factor Authentication',
            'scan_qr' => 'Scan this QR code with your authenticator app',
            'enter_manually' => 'Or enter this code manually',
            'check_email' => 'Check your email',
            'code_sent' => 'We\'ve sent a 6-digit verification code to your email address',
            'save_recovery' => 'Save these recovery codes in a safe place',
            'recovery_warning' => 'You can use these codes to access your account if you lose your device',
            'saved_codes' => 'I\'ve Saved My Codes',
            'enter_password' => 'Enter your password to disable two-factor authentication',
            'continue' => 'Continue',
            'enabling' => 'Enabling...',
            'verify_code' => 'Verification Code',
            'verifying' => 'Verifying...',
            'disable' => 'Disable Two-Factor Authentication',
            'confirm_enable' => 'Confirm and Enable',
            'select_method' => 'Select Method',
            'totp_description' => 'Use an authenticator app like Google Authenticator or Authy',
            'email_description' => 'Receive verification codes via email',
            'scan_qr_title' => 'Scan QR Code',
            'verify_setup' => 'Verify Setup',
            'enter_code_app' => 'Enter the 6-digit code from your authenticator app',
            'enter_code_email' => 'Enter the 6-digit code we sent to your email',
            'check_email_code' => 'Check your email for the verification code',
            'recovery_codes_title' => 'Recovery Codes',
            'recovery_codes_desc' => 'Save these codes in a secure location. Each code can only be used once.',
            'click_to_view' => 'Click below to view your recovery codes',
            'show_codes' => 'Show Codes',
            'hide_codes' => 'Hide Codes',
            'download' => 'Download',
            'regenerate_codes' => 'Generate New Recovery Codes',
            'regenerating' => 'Regenerating...',
            'confirm' => 'Confirm',
            'cancel' => 'Cancel',
        ],

        'sessions' => [
            'title' => 'Active Sessions',
            'description' => 'Manage and log out your active sessions on other browsers and devices.',
            'info' => 'You can see all devices where you\'re currently logged in and log out of any session.',
            'manage' => 'Manage Sessions',
            'modal_description' => 'Manage your browser sessions on other devices',
            'logout_info' => 'If necessary, you may log out of all of your other browser sessions across all of your devices.',
            'loading' => 'Loading sessions...',
            'this_device' => 'This Device',
            'active_now' => 'Active now',
            'active' => 'Active',
            'confirm_password' => 'Confirm Password',
            'password_placeholder' => 'Enter your password',
            'password_hint' => 'Enter your password to log out from other sessions',
            'logout_others' => 'Log Out Other Sessions',
            'logout_others_desc' => 'Log out all other browser sessions across all your devices',
            'logging_out' => 'Logging out...',
            'confirm_logout' => 'Confirm Logout',
            'confirm_logout_desc' => 'Enter your password to log out all other browser sessions',
            'confirm_and_logout' => 'Confirm and Log Out',
            'no_sessions' => 'No active sessions found.',
            'no_sessions_hint' => 'Session tracking requires database session driver.',
            'revoke_session' => 'Revoke Session',
            'revoke_session_confirm' => 'Are you sure you want to revoke this session? The device will be logged out immediately.',
            'revoke' => 'Revoke Session',
        ],

        'api_tokens' => [
            'title' => 'API Tokens',
            'description' => 'Create and manage API tokens for accessing your account programmatically.',
            'info' => 'API tokens allow you to interact with the application through API requests.',
            'manage' => 'Manage Tokens',
            'modal_description' => 'Create and manage API tokens',
            'third_party_info' => 'API tokens allow third-party services to authenticate with our application on your behalf.',
            'loading' => 'Loading tokens...',
            'your_tokens' => 'Your Tokens',
            'abilities' => 'Abilities',
            'last_used' => 'Last used',
            'never_used' => 'Never used',
            'no_tokens' => 'No API tokens created yet.',
            'no_tokens_yet' => 'No API tokens yet',
            'create_first' => 'Create your first token to get started',
            'create_new' => 'Create New Token',
            'token_name' => 'Token Name',
            'token_name_placeholder' => 'My Application',
            'token_name_hint' => 'A descriptive name for this token.',
            'permissions' => 'Permissions',
            'permissions_hint' => 'Select the permissions this token should have.',
            'permission_read' => 'Read',
            'permission_create' => 'Create',
            'permission_update' => 'Update',
            'permission_delete' => 'Delete',
            'save_token' => 'Save your token now!',
            'save_token_warning' => 'For security reasons, this token will only be shown once. Make sure to copy it now.',
            'new_token' => 'Your new API token',
            'create' => 'Create Token',
            'creating' => 'Creating...',
            'copied_token' => 'I\'ve Copied My Token',
            'confirm_delete' => 'Are you sure you want to delete this token? This action cannot be undone.',
            'revoke' => 'Revoke',
            'revoke_token' => 'Revoke API Token',
            'revoke_all' => 'Revoke All Tokens',
            'revoke_all_warning' => 'This will revoke all active tokens. Applications using these tokens will lose access.',
            'confirm_revoke' => 'Are you sure you want to revoke this token? Applications using this token will lose access.',
            'password_placeholder' => 'Enter your password',
            'password_hint' => 'Confirm your password to revoke all tokens.',
            'token_created' => 'Token Created Successfully',
            'copy_token_warning' => 'Make sure to copy your token now. You won\'t be able to see it again!',
            'limit_reached' => 'Token Limit Reached',
            'limit_reached_desc' => 'You have reached the maximum of :max tokens. Revoke a token to create a new one.',
            'expired' => 'Expired',
            'expires' => 'Expires',
            'full_access' => 'Full Access',
            'created' => 'Created',
        ],

        'passkeys' => [
            'title' => 'Passkeys',
            'description' => 'Use passkeys for secure, passwordless authentication on supported devices.',
            'info' => 'Passkeys provide a more secure and convenient way to sign in without a password.',
            'manage' => 'Manage Passkeys',
            'modal_description' => 'Manage your passkeys for passwordless authentication',
            'biometric_info' => 'Passkeys are a secure and convenient way to sign in without a password. They use your device\'s biometric authentication or PIN.',
            'loading' => 'Loading passkeys...',
            'your_passkeys' => 'Your Passkeys',
            'created' => 'Created',
            'last_used' => 'Last used',
            'never_used' => 'Never used',
            'no_passkeys' => 'No passkeys registered yet.',
            'register_new' => 'Register New Passkey',
            'register_title' => 'Register New Passkey',
            'register_description' => 'Enter a name for this passkey to help you identify it later.',
            'name_hint' => 'This name will help you identify this passkey later.',
            'passkey_name' => 'Passkey Name',
            'name_placeholder' => 'My Device',
            'register' => 'Register Passkey',
            'registering' => 'Registering...',
            'delete_title' => 'Delete Passkey',
            'confirm_delete' => 'Are you sure you want to delete this passkey? You will no longer be able to use it to sign in.',
            'what_are_passkeys' => 'What are Passkeys?',
            'what_are_passkeys_desc' => 'Passkeys are a secure, passwordless way to sign in using your device\'s biometric authentication (Face ID, Touch ID, Windows Hello) or a security key.',
            'limit_reached' => 'Passkey Limit Reached',
            'limit_reached_desc' => 'You have reached the maximum of :max passkeys. Remove a passkey to add a new one.',
            'no_passkeys_yet' => 'No passkeys yet',
            'add_passkey_desc' => 'Add a passkey for secure, passwordless authentication',
            'added' => 'Added',
        ],

        'connected_accounts' => [
            'title' => 'Connected Accounts',
            'description' => 'Manage third-party accounts connected to your profile for social authentication.',
            'info' => 'You can connect or disconnect social accounts like Google, GitHub, and more.',
            'manage' => 'Manage Connections',
            'modal_description' => 'Manage your social account connections',
            'social_info' => 'Connect your social accounts to sign in quickly and securely.',
            'loading' => 'Loading connected accounts...',
            'connected_as' => 'Connected as',
            'not_connected' => 'Not connected',
            'no_providers' => 'No social providers configured.',
            'disconnect' => 'Disconnect',
            'connect' => 'Connect',
            'confirm_disconnect' => 'Are you sure you want to disconnect your :provider account?',
        ],

        'magic_links' => [
            'title' => 'Magic Links',
            'description' => 'Configure magic link authentication for passwordless sign-in via email.',
            'info' => 'Magic links let you sign in by clicking a link sent to your email. This feature is automatically available when enabled in your authentication settings.',
            'ready_to_use' => 'Magic link authentication is ready to use. When you sign in, you can choose to receive a magic link via email instead of entering your password.',
        ],

        'locale_timezone' => [
            'title' => 'Language & Timezone',
            'nav_label' => 'Language & Timezone',
            'description' => 'Set your preferred language and timezone for the application.',
            'language' => 'Language',
            'timezone' => 'Timezone',
            'save' => 'Save Preferences',
            'updated' => 'Language and timezone preferences updated successfully.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Cluster
    |--------------------------------------------------------------------------
    */
    'settings' => [
        'title' => 'Settings',
        'description' => 'Manage your account settings and preferences',
    ],
];
