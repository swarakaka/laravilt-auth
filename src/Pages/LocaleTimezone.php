<?php

namespace Laravilt\Auth\Pages;

use DateTimeZone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravilt\Actions\Action;
use Laravilt\Auth\Clusters\Settings;
use Laravilt\Forms\Components\Select;
use Laravilt\Panel\Enums\PageLayout;
use Laravilt\Panel\Pages\Page;

class LocaleTimezone extends Page
{
    protected static ?string $title = null;

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'locale-timezone';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationIcon = 'globe';

    protected static bool $shouldRegisterNavigation = false;

    public static function getTitle(): string
    {
        return __('laravilt-auth::auth.profile.locale_timezone.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('laravilt-auth::auth.profile.locale_timezone.nav_label');
    }

    public function getHeading(): string
    {
        return __('laravilt-auth::auth.profile.locale_timezone.title');
    }

    public function getSubheading(): ?string
    {
        return __('laravilt-auth::auth.profile.locale_timezone.description');
    }

    public function getLayout(): string
    {
        return PageLayout::Settings->value;
    }

    protected function getSchema(): array
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        $localeOptions = collect(static::getAvailableLocales())
            ->pluck('label', 'value')
            ->toArray();

        // Flatten timezones for the Select component
        $timezoneOptions = [];
        foreach (static::getAvailableTimezones() as $region) {
            foreach ($region['timezones'] as $tz) {
                $timezoneOptions[$tz['value']] = $tz['label'];
            }
        }

        return [
            Select::make('locale')
                ->label(__('laravilt-auth::auth.profile.locale_timezone.language'))
                ->options($localeOptions)
                ->default($user->locale ?? 'en')
                ->searchable()
                ->required(),

            Select::make('timezone')
                ->label(__('laravilt-auth::auth.profile.locale_timezone.timezone'))
                ->options($timezoneOptions)
                ->default($user->timezone ?? 'UTC')
                ->searchable()
                ->required(),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('update-locale-timezone')
                ->label(__('laravilt-auth::auth.profile.locale_timezone.save'))
                ->action(function (array $data) {
                    return $this->updateLocaleTimezone($data);
                }),
        ];
    }

    public function updateLocaleTimezone(array $data): mixed
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        // Validate the data
        $validator = Validator::make($data, [
            'locale' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Update the user's locale and timezone
        $user->update([
            'locale' => $data['locale'],
            'timezone' => $data['timezone'],
        ]);

        return back()->with('success', __('laravilt-auth::auth.profile.locale_timezone.updated'));
    }

    /**
     * Get available locales.
     */
    public static function getAvailableLocales(): array
    {
        return config('app.available_locales', [
            ['value' => 'en', 'label' => 'English', 'dir' => 'ltr'],
            ['value' => 'ar', 'label' => 'العربية', 'dir' => 'rtl'],
        ]);
    }

    /**
     * Get available timezones grouped by region.
     */
    public static function getAvailableTimezones(): array
    {
        $timezones = [];
        $regions = [
            'Africa' => DateTimeZone::AFRICA,
            'America' => DateTimeZone::AMERICA,
            'Antarctica' => DateTimeZone::ANTARCTICA,
            'Asia' => DateTimeZone::ASIA,
            'Atlantic' => DateTimeZone::ATLANTIC,
            'Australia' => DateTimeZone::AUSTRALIA,
            'Europe' => DateTimeZone::EUROPE,
            'Indian' => DateTimeZone::INDIAN,
            'Pacific' => DateTimeZone::PACIFIC,
        ];

        foreach ($regions as $name => $region) {
            $tzList = DateTimeZone::listIdentifiers($region);
            $regionTimezones = [];

            foreach ($tzList as $tz) {
                $regionTimezones[] = [
                    'value' => $tz,
                    'label' => str_replace('_', ' ', $tz),
                ];
            }

            $timezones[] = [
                'region' => $name,
                'timezones' => $regionTimezones,
            ];
        }

        return $timezones;
    }
}
