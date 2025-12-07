<?php

namespace Laravilt\Auth\Pages\Profile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravilt\Auth\Clusters\Settings;
use Laravilt\Auth\Events\TwoFactorDisabled;
use Laravilt\Auth\Events\TwoFactorEnabled;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class ManageTwoFactor extends Page
{
    protected static ?string $title = 'Two-Factor Auth';

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'two-factor';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $component = 'laravilt-auth/ManageTwoFactorPage';

    public function getHeading(): string
    {
        return 'Two-Factor Auth';
    }

    public function getSubheading(): ?string
    {
        return 'Add additional security to your account using two-factor authentication.';
    }

    public function getLayout(): string
    {
        return PageLayout::Settings->value;
    }

    protected function getSchema(): array
    {
        return [];
    }

    protected function getActions(): array
    {
        return [];
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        $twoFactorEnabled = $user->two_factor_enabled ?? false;
        $twoFactorMethod = $user->two_factor_method ?? null;
        $needsConfirmation = ! $twoFactorEnabled && ! is_null($twoFactorMethod);

        // Get QR code and secret if TOTP 2FA is being set up but not confirmed
        $qrCode = null;
        $secret = null;
        $recoveryCodes = null;

        if ($needsConfirmation && $twoFactorMethod === 'totp') {
            // Get QR code from Fortify
            $qrCode = $user->twoFactorQrCodeSvg();
            $secret = decrypt($user->two_factor_secret);
        } elseif ($twoFactorEnabled && session('two_factor_recovery_codes')) {
            // Show recovery codes after successful confirmation
            $recoveryCodes = session('two_factor_recovery_codes');
        }

        $panelId = $panel->getId();

        return [
            'twoFactorEnabled' => $twoFactorEnabled,
            'twoFactorMethod' => $twoFactorMethod,
            'qrCode' => $qrCode,
            'secret' => $secret,
            'recoveryCodes' => $recoveryCodes,
            'needsConfirmation' => $needsConfirmation,
            'enableAction' => route("{$panelId}.two-factor.enable"),
            'disableAction' => route("{$panelId}.two-factor.disable"),
            'confirmAction' => route("{$panelId}.two-factor.confirm"),
            'cancelAction' => route("{$panelId}.two-factor.cancel"),
            'regenerateAction' => route("{$panelId}.two-factor.recovery-codes"),
            'enableSchema' => [
                $this->getMethodSelectionInput(),
                $this->getEnableAction(),
            ],
            'confirmSchema' => [
                $this->getConfirmCodeInput(),
                $this->getConfirmAction(),
                $this->getCancelAction(),
            ],
            'disableSchema' => [
                $this->getDisableAction(),
            ],
        ];
    }

    /**
     * Enable two-factor authentication.
     */
    public function enable(Request $request)
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        $method = $request->input('method', 'totp');

        // Get the driver from the panel's provider manager
        $manager = $panel->getTwoFactorProviderManager();
        $driver = $manager?->getDriver($method);

        if (! $driver) {
            return back()->withErrors([
                'method' => "Two-factor method '{$method}' is not available.",
            ]);
        }

        // Enable using the driver
        $data = $driver->enable($user);

        // Refresh user to get any changes made by the driver
        $user->refresh();

        // Set method but don't enable yet (will be enabled after confirmation)
        $user->update([
            'two_factor_method' => $method,
            'two_factor_enabled' => false,
        ]);

        return back()->with('status', 'two-factor-authentication-enabled');
    }

    /**
     * Confirm two-factor authentication.
     */
    public function confirm(Request $request)
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Validate the code
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = $request->input('code');
        $method = $user->two_factor_method ?? 'totp';

        // Get the driver
        $manager = $panel->getTwoFactorProviderManager();
        $driver = $manager?->getDriver($method);

        if (! $driver) {
            return back()->withErrors([
                'code' => 'Two-factor authentication method is not available.',
            ]);
        }

        // Verify the code using the driver
        if (! $driver->verify($user, $code)) {
            return back()->withErrors([
                'code' => 'The provided two factor authentication code was invalid.',
            ]);
        }

        // Mark as confirmed and enabled
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);

        // Generate recovery codes
        $service = app(\Laravilt\Auth\Services\TwoFactorService::class);
        $recoveryCodes = $service->generateRecoveryCodes($user);

        // Dispatch 2FA enabled event
        TwoFactorEnabled::dispatch(
            $user,
            $method,
            $panel->getId()
        );

        return back()->with([
            'status' => 'two-factor-authentication-confirmed',
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Disable two-factor authentication.
     */
    public function disable(Request $request)
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Get the method before disabling
        $method = $user->two_factor_method;

        // Disable 2FA (clear all 2FA related data)
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_method' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        // Dispatch 2FA disabled event
        TwoFactorDisabled::dispatch(
            $user,
            $method,
            $panel->getId()
        );

        return back()->with('status', 'two-factor-authentication-disabled');
    }

    /**
     * Cancel two-factor authentication setup.
     */
    public function cancel(Request $request)
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Simply clear 2FA setup data
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_method' => null,
        ]);

        return back();
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Regenerate recovery codes using Fortify action
        app(\Laravel\Fortify\Actions\GenerateNewRecoveryCodes::class)($user);

        $recoveryCodes = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);

        return back()->with([
            'status' => 'recovery-codes-generated',
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);
    }

    protected function getEnableAction(): \Laravilt\Actions\Action
    {
        $panelId = $this->getPanel()->getId();

        return \Laravilt\Actions\Action::make('enable')
            ->label('Enable Two-Factor Authentication')
            ->url(route("{$panelId}.two-factor.enable"))
            ->preserveScroll(false)
            ->preserveState(false)
            ->requiresConfirmation(false);
    }

    protected function getConfirmCodeInput(): \Laravilt\Forms\Components\PinInput
    {
        return \Laravilt\Forms\Components\PinInput::make('code')
            ->label('Verification Code')
            ->required()
            ->length(6)
            ->type('numeric')
            ->otp();
    }

    protected function getConfirmAction(): \Laravilt\Actions\Action
    {
        $panelId = $this->getPanel()->getId();

        return \Laravilt\Actions\Action::make('confirm')
            ->preserveScroll(false)
            ->preserveState(false)
            ->label('Confirm and Enable')
            ->url(route("{$panelId}.two-factor.confirm"))
            ->requiresConfirmation(false);
    }

    protected function getCancelAction(): \Laravilt\Actions\Action
    {
        $panelId = $this->getPanel()->getId();

        return \Laravilt\Actions\Action::make('cancel')
            ->preserveScroll(false)
            ->preserveState(false)
            ->label('Cancel')
            ->url(route("{$panelId}.two-factor.cancel"))
            ->color('secondary')
            ->requiresConfirmation(false);
    }

    protected function getDisableAction(): \Laravilt\Actions\Action
    {
        $panelId = $this->getPanel()->getId();

        return \Laravilt\Actions\Action::make('disable')
            ->label('Disable Two-Factor Authentication')
            ->url(route("{$panelId}.two-factor.disable"))
            ->preserveScroll(false)
            ->preserveState(false)
            ->method('delete')
            ->color('danger')
            ->requiresConfirmation(true)
            ->modalHeading('Disable Two-Factor Authentication')
            ->modalDescription('Are you sure you want to disable two-factor authentication? This will make your account less secure.');
    }

    protected function getMethodSelectionInput(): \Laravilt\Forms\Components\Radio
    {
        $panel = $this->getPanel();
        $providers = $panel->getTwoFactorProviders();

        // Build options and descriptions from registered providers
        $options = [];
        $descriptions = [];

        foreach ($providers as $provider) {
            $options[$provider['name']] = $provider['label'];
            $descriptions[$provider['name']] = $this->getProviderDescription($provider['name']);
        }

        // Default to first available provider
        $defaultMethod = $providers[0]['name'] ?? 'totp';

        return \Laravilt\Forms\Components\Radio::make('method')
            ->label('Select Method')
            ->default($defaultMethod)
            ->options($options)
            ->descriptions($descriptions);
    }

    /**
     * Get description for a provider.
     */
    protected function getProviderDescription(string $name): string
    {
        return match ($name) {
            'totp' => 'Use an authenticator app like Google Authenticator or Authy',
            'email' => 'Receive verification codes via email',
            default => '',
        };
    }
}
