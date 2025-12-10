<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravilt\Actions\Action;
use Laravilt\Auth\Events\OtpSent;
use Laravilt\Auth\Events\RegistrationAttempt;
use Laravilt\Auth\Events\RegistrationCompleted;
use Laravilt\Auth\Notifications\OTPNotification;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class Register extends Page
{
    protected static ?string $title = null;

    protected static bool $shouldRegisterNavigation = false;

    public static function getTitle(): string
    {
        return __('laravilt-auth::auth.register.title');
    }

    public function getHeading(): string
    {
        return __('laravilt-auth::auth.register.heading');
    }

    public function getSubheading(): ?string
    {
        return __('laravilt-auth::auth.register.subheading');
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    /**
     * Handle POST request to register a new user.
     */
    public function store(Request $request)
    {
        return $this->createUser([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'password_confirmation' => $request->password_confirmation,
        ]);
    }

    protected function getSchema(): array
    {
        return [
            TextInput::make('name')
                ->label(__('laravilt-auth::auth.fields.name'))
                ->required()
                ->rules(['required', 'string', 'max:255'])
                ->autofocus()
                ->tabindex(1),

            TextInput::make('email')
                ->label(__('laravilt-auth::auth.fields.email'))
                ->email()
                ->required()
                ->rules(['required', 'email', 'max:255', 'unique:users,email'])
                ->tabindex(2),

            TextInput::make('password')
                ->label(__('laravilt-auth::auth.fields.password'))
                ->password()
                ->required()
                ->rules(['required', 'min:8', 'confirmed'])
                ->tabindex(3),

            TextInput::make('password_confirmation')
                ->label(__('laravilt-auth::auth.fields.password_confirmation'))
                ->password()
                ->required()
                ->tabindex(4),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('register')
                ->label(__('laravilt-auth::auth.register.button'))
                ->action(function (array $data) {
                    return $this->createUser($data);
                })
                ->preserveState(false)
                ->preserveScroll(false),
        ];
    }

    public function createUser(array $data): mixed
    {
        $panel = $this->getPanel();

        // Dispatch registration attempt event
        RegistrationAttempt::dispatch(
            $data,
            $panel->getId()
        );

        // Validate the data
        $validated = validator($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ])->validate();

        // Get the user model from the auth guard configuration
        $guard = $panel->getAuthGuard();
        $provider = config("auth.guards.{$guard}.provider");
        $userModel = config("auth.providers.{$provider}.model", \App\Models\User::class);

        // Create the user
        $user = $userModel::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        // Check if both email verification and OTP are enabled
        if ($panel->hasEmailVerification() && $panel->hasOtp()) {
            // Generate 6-digit OTP code
            $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addMinutes(5);

            // Store OTP in database
            DB::table('otp_codes')->insert([
                'identifier' => $user->email,
                'code' => $otpCode,
                'purpose' => 'registration',
                'expires_at' => $expiresAt,
                'verified' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Send OTP via email
            $user->notify(new OTPNotification($otpCode, 'registration', 5));

            // Dispatch OTP sent event
            OtpSent::dispatch(
                $user,
                $otpCode,
                'registration',
                $expiresAt,
                $panel->getId()
            );

            // Dispatch registration completed event (with OTP requirement)
            RegistrationCompleted::dispatch(
                $user,
                $panel->getId(),
                true // requires OTP verification
            );

            // Store user ID in session for OTP verification
            session(['otp.user_id' => $user->id, 'otp.email' => $user->email]);

            // Redirect to OTP verification page
            return redirect()->route($panel->getId().'.otp.login')
                ->with('status', 'We sent a verification code to your email. Please check your inbox.');
        }

        // Dispatch registration completed event (no OTP required)
        RegistrationCompleted::dispatch(
            $user,
            $panel->getId(),
            false // does not require OTP
        );

        // Log the user in if OTP is not required
        auth($guard)->login($user);

        return redirect($panel->getPath());
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();

        return [
            'canLogin' => $panel->hasLogin(),
            'loginUrl' => $panel->hasLogin() ? route($panel->getId().'.login') : null,
            'socialProviders' => $panel->hasSocialLogin() ? $this->getSocialProviders() : [],
            'socialRedirectUrl' => $panel->hasSocialLogin() ? route($panel->getId().'.auth.social.redirect', ['provider' => ':provider']) : null,
        ];
    }

    public function getSocialProviders(): array
    {
        $panel = $this->getPanel();

        if ($panel->hasSocialLogin()) {
            // Get social providers from panel configuration
            // These are already formatted as arrays by getProvidersForFrontend()
            return $panel->getSocialProviders();
        }

        return [];
    }
}
