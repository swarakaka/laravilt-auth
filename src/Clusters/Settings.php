<?php

namespace Laravilt\Auth\Clusters;

use Illuminate\Http\Request;
use Laravilt\Panel\Cluster;

class Settings extends Cluster
{
    protected static ?string $navigationIcon = 'user';

    protected static ?string $navigationLabel = null;

    protected static ?string $slug = 'settings';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 100;

    public static function getNavigationLabel(): string
    {
        return __('laravilt-auth::auth.settings.title');
    }

    public static function getClusterTitle(): string
    {
        return __('laravilt-auth::auth.settings.title');
    }

    public static function getClusterDescription(): ?string
    {
        return __('laravilt-auth::auth.settings.description');
    }

    /**
     * Handle GET request to the cluster index.
     */
    public function create(Request $request, ...$parameters)
    {
        // Get the current panel from PanelRegistry
        $panel = app(\Laravilt\Panel\PanelRegistry::class)->getCurrent();

        if (! $panel) {
            abort(404);
        }

        $pages = $panel->getPages();

        // Find the first page that belongs to this cluster
        foreach ($pages as $page) {
            if (method_exists($page, 'getCluster') && $page::getCluster() === static::class) {
                return redirect($page::getUrl());
            }
        }

        // If no pages found, return 404
        abort(404);
    }
}
