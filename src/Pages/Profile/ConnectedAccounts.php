<?php

namespace Laravilt\Auth\Pages\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravilt\Actions\Action;
use Laravilt\Auth\Clusters\Settings;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class ConnectedAccounts extends Page
{
    protected static ?string $title = 'Connected Accounts';

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'connected-accounts';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $component = 'laravilt-auth/ConnectedAccountsPage';

    public function getHeading(): string
    {
        return 'Connected Accounts';
    }

    public function getSubheading(): ?string
    {
        return 'Manage your connected social accounts.';
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

    /**
     * Disconnect a social account - method for action system.
     */
    protected function disconnectAccount(string $provider): mixed
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Find the connected account
        $socialAccount = $user->connectedAccounts()
            ->where('provider', $provider)
            ->first();

        if (! $socialAccount) {
            throw ValidationException::withMessages([
                'provider' => ['Social account not found.'],
            ]);
        }

        // Delete the social account
        $socialAccount->delete();

        // Return success - Inertia will reload the page
        return null;
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Get user's connected accounts if implemented
        $connectedAccountsCollection = method_exists($user, 'connectedAccounts') ? $user->connectedAccounts()->get() : collect();

        // Get available providers from panel configuration
        $availableProviders = $panel->getSocialProviders() ?? [];

        // Build providers array with connection status
        $providers = collect($availableProviders)->map(function ($providerConfig) use ($connectedAccountsCollection, $panel) {
            $providerName = is_array($providerConfig) ? $providerConfig['name'] : $providerConfig;
            $connectedAccount = $connectedAccountsCollection->firstWhere('provider', $providerName);

            $connectAction = null;
            $disconnectAction = null;

            if (is_null($connectedAccount)) {
                // Not connected - provide connect action (redirect to OAuth)
                $connectAction = [
                    'label' => 'Connect',
                    'url' => route($panel->getId().'.auth.social.redirect', ['provider' => $providerName]),
                    'method' => 'get',
                    'type' => 'link',
                ];
            } else {
                // Connected - provide disconnect action
                $disconnectAction = Action::make('disconnect-'.$providerName)
                    ->label('Disconnect')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Disconnect '.$providerName)
                    ->modalDescription('Are you sure you want to disconnect your '.$providerName.' account?')
                    ->modalSubmitActionLabel('Disconnect')
                    ->action(function () use ($providerName) {
                        return $this->disconnectAccount($providerName);
                    })
                    ->toArray();
            }

            return [
                'name' => $providerName,
                'label' => ucfirst($providerName),
                'connected' => ! is_null($connectedAccount),
                'account' => $connectedAccount ? [
                    'id' => $connectedAccount->id,
                    'provider' => $connectedAccount->provider,
                    'provider_id' => $connectedAccount->provider_id,
                    'name' => $connectedAccount->name ?? null,
                    'email' => $connectedAccount->email ?? null,
                    'avatar' => $connectedAccount->avatar ?? null,
                    'created_at' => $connectedAccount->created_at->toDateTimeString(),
                ] : null,
                'connectAction' => $connectAction,
                'disconnectAction' => $disconnectAction,
            ];
        })->values()->all();

        return [
            'providers' => $providers,
        ];
    }
}
