<?php

namespace Laravilt\Auth\Pages;

use Laravilt\Actions\Action;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class MagicLink extends Page
{
    protected static ?string $title = 'Magic Link Login';

    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return 'Magic Link Login';
    }

    public function getSubheading(): ?string
    {
        return 'Enter your email and we\'ll send you a magic link.';
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    protected function getSchema(): array
    {
        return [
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->autofocus()
                ->tabindex(1),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('send-magic-link')
                ->label('Send Magic Link')
                ->action(function (array $data) {
                    return $this->sendMagicLink($data);
                }),
        ];
    }

    public function sendMagicLink(array $data): mixed
    {
        // Validate email
        request()->validate([
            'email' => ['required', 'email'],
        ]);

        // Here you would send the magic link
        // This is a placeholder - implement your magic link logic
        // For example: MagicLink::send($data['email']);

        return back()->with('status', 'Magic link sent! Check your email.');
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();

        return [
            'canLogin' => $panel->hasLogin(),
            'loginUrl' => $panel->hasLogin() ? route($panel->getId().'.login') : null,
            'status' => session('status'),
        ];
    }
}
