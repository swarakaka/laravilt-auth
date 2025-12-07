<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravilt\Actions\Action;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class EmailVerification extends Page
{
    protected static ?string $title = 'Verify Email';

    protected static bool $shouldRegisterNavigation = false;

    public function getHeading(): string
    {
        return 'Verify Your Email';
    }

    public function getSubheading(): ?string
    {
        return 'Click the button below to resend the verification email.';
    }

    public function getLayout(): string
    {
        return PageLayout::Card->value;
    }

    /**
     * Display the email verification notice page.
     * Redirects verified users to the dashboard.
     */
    public function create(Request $request, ...$parameters)
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Redirect verified users to dashboard
        if ($user && $user->hasVerifiedEmail()) {
            return redirect($panel->getPath());
        }

        return parent::create($request, ...$parameters);
    }

    /**
     * Handle POST request to send verification email.
     */
    public function store(Request $request)
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        if ($user->hasVerifiedEmail()) {
            return redirect($panel->getPath());
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'Verification link sent!');
    }

    /**
     * Handle email verification from signed URL.
     */
    public function verify(Request $request, $id, $hash)
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Check if user ID matches
        if (! hash_equals((string) $id, (string) $user->getKey())) {
            abort(403);
        }

        // Check if email hash matches
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        // If already verified, just redirect
        if ($user->hasVerifiedEmail()) {
            return redirect($panel->getPath().'?verified=1');
        }

        // Mark as verified and dispatch event
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect($panel->getPath().'?verified=1');
    }

    protected function getSchema(): array
    {
        // No form fields needed for email verification
        return [];
    }

    protected function getActions(): array
    {
        return [
            Action::make('resend-verification')
                ->label('Resend Verification Email')
                ->action(function (array $data) {
                    return $this->resendVerification($data);
                }),
        ];
    }

    public function resendVerification(array $data): mixed
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($panel->getPath());
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'Verification link sent!');
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();

        return [
            'status' => session('status'),
            'canLogin' => $panel->hasLogin(),
            'loginUrl' => $panel->hasLogin() ? route($panel->getId().'.login') : null,
        ];
    }
}
