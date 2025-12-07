<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravilt\Actions\Action;
use Laravilt\Auth\Events\OtpFailed;
use Laravilt\Auth\Events\OtpVerified;
use Laravilt\Forms\Components\PinInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class OTP extends Page
{
    protected static ?string $title = 'Verify Code';

    protected static bool $shouldRegisterNavigation = false;

    public function create(Request $request, ...$parameters)
    {
        return $this->render();
    }

    public function store(Request $request)
    {
        return $this->verifyCode($request->all());
    }

    public function getHeading(): string
    {
        return 'Enter Verification Code';
    }

    public function getSubheading(): ?string
    {
        $email = session('otp.email');
        if ($email) {
            return 'Please enter the 6-digit code we sent to '.$email;
        }

        return 'Please enter the code we sent to your email.';
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    protected function getSchema(): array
    {
        return [
            PinInput::make('code')
                ->label('Verification Code')
                ->required()
                ->autofocus()
                ->tabindex(1)
                ->length(6)
                ->otp(),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('verify-code')
                ->label('Verify')
                ->action(function (array $data) {
                    return $this->verifyCode($data);
                })
                ->preserveScroll(false)
                ->preserveState(false),
        ];
    }

    public function verifyCode(array $data): mixed
    {
        // Validate the code
        request()->validate([
            'code' => ['required', 'string'],
        ]);

        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();

        // Get user from session (for registration OTP flow)
        $userId = session('otp.user_id');
        $email = session('otp.email');

        if (! $userId || ! $email) {
            // Dispatch OTP failed event (session expired)
            OtpFailed::dispatch(
                null,
                $data['code'] ?? '',
                'registration',
                'session_expired',
                $panel->getId()
            );

            throw ValidationException::withMessages([
                'code' => ['Session expired. Please try registering again.'],
            ]);
        }

        // Clean the code (remove spaces)
        $code = str_replace(' ', '', $data['code']);

        // Find the OTP in database
        $otpRecord = DB::table('otp_codes')
            ->where('identifier', $email)
            ->where('code', $code)
            ->where('purpose', 'registration')
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->first();

        if (! $otpRecord) {
            // Get the user model to dispatch event with user
            $provider = config("auth.guards.{$guard}.provider");
            $userModel = config("auth.providers.{$provider}.model", \App\Models\User::class);
            $user = $userModel::find($userId);

            // Dispatch OTP failed event (invalid or expired code)
            OtpFailed::dispatch(
                $user,
                $code,
                'registration',
                'invalid_or_expired',
                $panel->getId()
            );

            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        // Mark OTP as verified
        DB::table('otp_codes')
            ->where('id', $otpRecord->id)
            ->update(['verified' => true]);

        // Get the user model
        $provider = config("auth.guards.{$guard}.provider");
        $userModel = config("auth.providers.{$provider}.model", \App\Models\User::class);
        $user = $userModel::find($userId);

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => ['User not found.'],
            ]);
        }

        // Mark email as verified
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // Dispatch OTP verified event
        OtpVerified::dispatch(
            $user,
            'registration',
            $panel->getId()
        );

        // Clear OTP session data
        session()->forget(['otp.user_id', 'otp.email']);

        // Log the user in
        auth($guard)->login($user);

        return redirect()->intended($panel->getPath())
            ->with('status', 'Email verified successfully! Welcome aboard.');
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();

        return [
            'canLogin' => $panel->hasLogin(),
            'loginUrl' => $panel->hasLogin() ? route($panel->getId().'.login') : null,
        ];
    }
}
