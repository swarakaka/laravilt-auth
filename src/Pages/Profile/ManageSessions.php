<?php

namespace Laravilt\Auth\Pages\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravilt\Auth\Clusters\Settings;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class ManageSessions extends Page
{
    protected static ?string $title = 'Browser Sessions';

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'sessions';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $component = 'laravilt-auth/ManageSessionsPage';

    public function getHeading(): string
    {
        return 'Browser Sessions';
    }

    public function getSubheading(): ?string
    {
        return 'Manage and log out your active sessions on other browsers and devices.';
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
        $request = request();

        // Get active sessions
        $sessions = collect(
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getAuthIdentifier())
                ->orderBy('last_activity', 'desc')
                ->get()
        )->map(function ($session) use ($request) {
            $agent = $this->createAgent($session);

            return [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => $session->last_activity,
                'last_activity_human' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                'is_current' => $session->id === $request->session()->getId(),
                'device' => [
                    'browser' => $agent->browser(),
                    'platform' => $agent->platform(),
                    'device_type' => $agent->isDesktop() ? 'desktop' : ($agent->isTablet() ? 'tablet' : 'mobile'),
                ],
            ];
        });

        return [
            'sessions' => $sessions,
            'currentSessionId' => $request->session()->getId(),
            'logoutAction' => '', // TODO: Add logout action URL
            'revokeAction' => '', // TODO: Add revoke action URL
        ];
    }

    /**
     * Create a new agent instance from the given session.
     */
    protected function createAgent($session): \Jenssegers\Agent\Agent
    {
        return tap(new \Jenssegers\Agent\Agent, function ($agent) use ($session) {
            $agent->setUserAgent($session->user_agent);
        });
    }
}
