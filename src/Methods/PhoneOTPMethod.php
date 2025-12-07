<?php

namespace Laravilt\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravilt\Auth\Services\OTPService;

class PhoneOTPMethod extends BaseAuthMethod
{
    public function __construct(
        protected OTPService $otpService,
        array $config = []
    ) {
        parent::__construct($config);
    }

    /**
     * Get the method name.
     */
    public function getName(): string
    {
        return 'phone';
    }

    /**
     * Authenticate the user.
     */
    public function authenticate(Request $request): ?Authenticatable
    {
        $phone = $request->input('phone');
        $otp = $request->input('otp');

        if (! $this->otpService->verify($phone, $otp)) {
            return null;
        }

        $guard = $this->config('guard', 'web');
        $model = $this->config('model');

        $user = $model::where('phone', $phone)->first();

        if ($user) {
            Auth::guard($guard)->login($user, $request->boolean('remember'));

            return $user;
        }

        return null;
    }

    /**
     * Check if this method can handle the request.
     */
    public function canHandle(Request $request): bool
    {
        return $request->has(['phone', 'otp']);
    }

    /**
     * Validate the credentials.
     */
    public function validate(Request $request): bool
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        return true;
    }

    /**
     * Send OTP to phone.
     */
    public function sendOTP(string $phone): bool
    {
        return $this->otpService->send($phone);
    }
}
