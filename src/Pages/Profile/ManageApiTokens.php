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
    protected static ?string $title = null;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'api-tokens';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $component = 'laravilt-auth/ManageApiTokensPage';

    public static function getTitle(): string
    {
        return __('laravilt-auth::auth.profile.api_tokens.title');
    }

    public function getHeading(): string
    {
        return __('laravilt-auth::auth.profile.api_tokens.title');
    }

    public function getSubheading(): ?string
    {
        return __('laravilt-auth::auth.profile.api_tokens.description');
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
            '*' => 'Full Access',
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
            ->label(__('laravilt-auth::auth.profile.api_tokens.create_new'))
            ->modalHeading(__('laravilt-auth::auth.profile.api_tokens.create_new'))
            ->modalDescription(__('laravilt-auth::auth.profile.api_tokens.third_party_info'))
            ->modalSubmitActionLabel(__('laravilt-auth::auth.profile.api_tokens.create'))
            ->preserveState(false)
            ->preserveScroll(false)
            ->schema([
                \Laravilt\Forms\Components\TextInput::make('name')
                    ->label(__('laravilt-auth::auth.profile.api_tokens.token_name'))
                    ->required()
                    ->placeholder(__('laravilt-auth::auth.profile.api_tokens.token_name_placeholder'))
                    ->helperText(__('laravilt-auth::auth.profile.api_tokens.token_name_hint')),

                \Laravilt\Forms\Components\Checkbox::make('abilities')
                    ->label(__('laravilt-auth::auth.profile.api_tokens.permissions'))
                    ->options($availableAbilities)
                    ->helperText(__('laravilt-auth::auth.profile.api_tokens.permissions_hint')),
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
            ->label(__('laravilt-auth::auth.profile.api_tokens.revoke_all'))
            ->color('danger')
            ->modalHeading(__('laravilt-auth::auth.profile.api_tokens.revoke_all'))
            ->modalDescription(__('laravilt-auth::auth.profile.api_tokens.revoke_all_warning'))
            ->modalSubmitActionLabel(__('laravilt-auth::auth.profile.api_tokens.revoke_all'))
            ->requiresConfirmation()
            ->preserveScroll(false)
            ->preserveState(false)
            ->schema([
                \Laravilt\Forms\Components\TextInput::make('password')
                    ->label(__('laravilt-auth::auth.fields.password'))
                    ->type('password')
                    ->required()
                    ->placeholder(__('laravilt-auth::auth.profile.api_tokens.password_placeholder'))
                    ->helperText(__('laravilt-auth::auth.profile.api_tokens.password_hint')),
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
            ->label(__('laravilt-auth::auth.profile.api_tokens.revoke'))
            ->icon('trash-2')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('laravilt-auth::auth.profile.api_tokens.revoke_token'))
            ->modalDescription(__('laravilt-auth::auth.profile.api_tokens.confirm_revoke'))
            ->modalSubmitActionLabel(__('laravilt-auth::auth.profile.api_tokens.revoke_token'))
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
        // Ensure abilities is an array, default to ['*'] (full access) if empty
        $abilities = is_array($abilities) && ! empty($abilities) ? $abilities : ['*'];

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

    /**
     * Delete a specific API token.
     */
    public function destroy(Request $request, int $token)
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Find and delete the token belonging to this user
        $tokenModel = $user->tokens()->find($token);

        if (! $tokenModel) {
            return back()->withErrors(['token' => 'Token not found.']);
        }

        $tokenModel->delete();

        return back()->with('status', 'api-token-revoked');
    }
}
