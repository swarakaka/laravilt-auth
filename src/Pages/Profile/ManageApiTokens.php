<?php

namespace Laravilt\Auth\Pages\Profile;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravilt\Auth\Clusters\Settings;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class ManageApiTokens extends Page
{
    protected static ?string $title = 'API Tokens';

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'api-tokens';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $component = 'laravilt-auth/ManageApiTokensPage';

    public function getHeading(): string
    {
        return 'API Tokens';
    }

    public function getSubheading(): ?string
    {
        return 'Manage API tokens that allow third-party services to access this application on your behalf.';
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

        // Get user's tokens if using Laravel Sanctum
        $tokensCollection = method_exists($user, 'tokens') ? $user->tokens()->get() : collect();

        $tokens = $tokensCollection->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities ?? [],
                'last_used_at' => $token->last_used_at?->toDateTimeString(),
                'expires_at' => $token->expires_at?->toDateTimeString(),
                'expires_at_human' => $token->expires_at?->diffForHumans(),
                'created_at' => $token->created_at->toDateTimeString(),
                'is_expired' => $token->expires_at && $token->expires_at->isPast(),
                'deleteAction' => $this->getDeleteTokenAction($token->id),
            ];
        })->values()->all();

        $availableAbilities = $this->getAvailableAbilities();

        return [
            'createAction' => $this->getCreateTokenAction(),
            'revokeAllAction' => $this->getRevokeAllAction(),
            'tokens' => $tokens,
            'availableAbilities' => $availableAbilities,
            'maxTokens' => 10, // Configure based on your app's limits
            'newToken' => session('token'), // Get newly created token from session
        ];
    }

    /**
     * Get available abilities/permissions for API tokens.
     */
    protected function getAvailableAbilities(): array
    {
        // Configure based on your application's permissions
        return [
            'read' => 'Read',
            'write' => 'Write',
            'delete' => 'Delete',
        ];
    }

    /**
     * Get the create token action.
     */
    protected function getCreateTokenAction(): array
    {
        $availableAbilities = $this->getAvailableAbilities();

        return \Laravilt\Actions\Action::make('create')
            ->label('Create New API Token')
            ->modalHeading('Create New API Token')
            ->modalDescription('API tokens allow third-party services to authenticate with our application on your behalf.')
            ->modalSubmitActionLabel('Create Token')
            ->preserveState(false)
            ->preserveScroll(false)
            ->schema([
                \Laravilt\Forms\Components\TextInput::make('name')
                    ->label('Token Name')
                    ->required()
                    ->placeholder('My API Token')
                    ->helperText('A descriptive name for this token.'),

                \Laravilt\Forms\Components\Checkbox::make('abilities')
                    ->label('Permissions')
                    ->options($availableAbilities)
                    ->helperText('Select the permissions this token should have.'),
            ])
            ->action(function (array $data) {
                return $this->createToken(request());
            })
            ->toArray();
    }

    /**
     * Get the revoke all tokens action.
     */
    protected function getRevokeAllAction(): array
    {
        return \Laravilt\Actions\Action::make('revoke-all')
            ->label('Revoke All Tokens')
            ->color('danger')
            ->modalHeading('Revoke All Tokens')
            ->modalDescription('This will revoke all active tokens. Applications using these tokens will lose access.')
            ->modalSubmitActionLabel('Revoke All Tokens')
            ->requiresConfirmation()
            ->preserveScroll(false)
            ->preserveState(false)
            ->schema([
                \Laravilt\Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->type('password')
                    ->required()
                    ->placeholder('Enter your password')
                    ->helperText('Confirm your password to revoke all tokens.'),
            ])
            ->action(function (array $data) {
                return $this->revokeAll(request());
            })
            ->toArray();
    }

    /**
     * Get the delete token action.
     */
    protected function getDeleteTokenAction(int $tokenId): array
    {
        return \Laravilt\Actions\Action::make('delete-'.$tokenId)
            ->label('Revoke')
            ->icon('trash-2')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Revoke API Token')
            ->modalDescription('Are you sure you want to revoke this token? Applications using this token will lose access.')
            ->modalSubmitActionLabel('Revoke Token')
            ->modalIcon('trash-2')
            ->modalIconColor('danger')
            ->url(route($this->getPanel()->getId().'.api-tokens.destroy', ['token' => $tokenId]))
            ->method('delete')
            ->toArray();
    }

    /**
     * Create a new API token.
     */
    public function createToken(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['nullable', 'array'],
        ]);

        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Create token using Laravel Sanctum
        $abilities = $request->input('abilities');
        // Ensure abilities is an array (convert null to empty array)
        $abilities = is_array($abilities) ? $abilities : [];

        $token = $user->createToken(
            $request->input('name'),
            $abilities
        );

        // Store the plain text token in session to show to user
        session()->flash('token', $token->plainTextToken);

        return back()->with('status', 'api-token-created');
    }

    /**
     * Revoke all API tokens.
     */
    public function revokeAll(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Verify password
        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        // Delete all tokens
        $user->tokens()->delete();

        return back()->with('status', 'api-tokens-revoked');
    }
}
