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
    protected static ?string $title = 'Language & Region';

    protected static ?string $cluster = Settings::class;

    protected static ?string $slug = 'locale-timezone';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationIcon = 'globe';

    public function getHeading(): string
    {
        return 'Language & Region';
    }

    public function getSubheading(): ?string
    {
        return 'Set your preferred language and timezone for the application.';
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
                ->label('Language')
                ->options($localeOptions)
                ->default($user->locale ?? 'en')
                ->searchable()
                ->required(),

            Select::make('timezone')
                ->label('Timezone')
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
                ->label('Save Preferences')
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
            'timezone' => ['required', 'string', 'max:50', 'timezone:all'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Update user
        $user->update($validated);

        notify('Language and timezone preferences updated successfully.');

        return back();
    }

    protected function getInertiaProps(): array
    {
        $panel = $this->getPanel();
        $guard = $panel->getAuthGuard();
        $user = Auth::guard($guard)->user();

        return [
            'user' => [
                'locale' => $user->locale,
                'timezone' => $user->timezone,
            ],
            'availableLocales' => static::getAvailableLocales(),
            'availableTimezones' => static::getAvailableTimezones(),
            'status' => session('status'),
        ];
    }

    /**
     * RTL (Right-to-Left) locales.
     */
    protected static array $rtlLocales = [
        'ar', // Arabic
        'he', // Hebrew
        'fa', // Persian/Farsi
        'ur', // Urdu
        'ps', // Pashto
        'sd', // Sindhi
        'yi', // Yiddish
        'ku', // Kurdish (some variants)
        'ug', // Uyghur
        'dv', // Divehi
    ];

    /**
     * Get available locales.
     */
    public static function getAvailableLocales(): array
    {
        return [
            ['value' => 'en', 'label' => 'English', 'dir' => 'ltr'],
            ['value' => 'es', 'label' => 'Spanish', 'dir' => 'ltr'],
            ['value' => 'fr', 'label' => 'French', 'dir' => 'ltr'],
            ['value' => 'de', 'label' => 'German', 'dir' => 'ltr'],
            ['value' => 'it', 'label' => 'Italian', 'dir' => 'ltr'],
            ['value' => 'pt', 'label' => 'Portuguese', 'dir' => 'ltr'],
            ['value' => 'ru', 'label' => 'Russian', 'dir' => 'ltr'],
            ['value' => 'zh', 'label' => 'Chinese', 'dir' => 'ltr'],
            ['value' => 'ja', 'label' => 'Japanese', 'dir' => 'ltr'],
            ['value' => 'ko', 'label' => 'Korean', 'dir' => 'ltr'],
            ['value' => 'ar', 'label' => 'العربية (Arabic)', 'dir' => 'rtl'],
            ['value' => 'he', 'label' => 'עברית (Hebrew)', 'dir' => 'rtl'],
            ['value' => 'fa', 'label' => 'فارسی (Persian)', 'dir' => 'rtl'],
            ['value' => 'ur', 'label' => 'اردو (Urdu)', 'dir' => 'rtl'],
            ['value' => 'hi', 'label' => 'Hindi', 'dir' => 'ltr'],
            ['value' => 'nl', 'label' => 'Dutch', 'dir' => 'ltr'],
            ['value' => 'pl', 'label' => 'Polish', 'dir' => 'ltr'],
            ['value' => 'tr', 'label' => 'Turkish', 'dir' => 'ltr'],
        ];
    }

    /**
     * Check if a locale is RTL.
     */
    public static function isRtlLocale(string $locale): bool
    {
        $baseLocale = explode('_', $locale)[0];
        $baseLocale = explode('-', $baseLocale)[0];

        return in_array(strtolower($baseLocale), static::$rtlLocales);
    }

    /**
     * Get available timezones grouped by region.
     */
    public static function getAvailableTimezones(): array
    {
        $timezones = DateTimeZone::listIdentifiers();
        $grouped = [];

        foreach ($timezones as $timezone) {
            $parts = explode('/', $timezone, 2);
            $region = $parts[0];

            // Skip generic regions
            if (in_array($region, ['UTC', 'GMT'])) {
                $region = 'UTC';
            }

            $label = str_replace(['_', '/'], [' ', ' / '], $timezone);

            if (! isset($grouped[$region])) {
                $grouped[$region] = [
                    'region' => $region,
                    'timezones' => [],
                ];
            }

            $grouped[$region]['timezones'][] = [
                'value' => $timezone,
                'label' => $label,
            ];
        }

        // Sort regions and their timezones
        ksort($grouped);
        foreach ($grouped as &$group) {
            usort($group['timezones'], fn ($a, $b) => $a['label'] <=> $b['label']);
        }

        return array_values($grouped);
    }
}
