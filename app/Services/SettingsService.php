<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Persists the admin "Settings" page and the editable content of the public
 * download page. Values are stored as JSON in storage/app so the feature works
 * without adding tables to the existing database.
 */
class SettingsService
{
    private const PATH = 'settings.json';

    /**
     * Default content shown on /download until an administrator edits it.
     *
     * @return array<string, string>
     */
    public function defaults(): array
    {
        return [
            'site_name' => config('app.name', 'MANGNO'),
            'site_tagline' => 'Preparedness Simulation',

            'download_eyebrow' => 'Get the Game',
            'download_heading' => 'Get Ready with MANGNO',
            'download_intro' => 'Experience interactive disaster preparedness learning with MANGNO, an educational game '
                .'designed to help you understand proper preparedness measures through simulated disaster scenarios '
                .'and engaging learning challenges.',
            'download_section_eyebrow' => 'Choose Your Platform',
            'download_section_heading' => 'Available Downloads',
            'download_windows_title' => 'Windows Version',
            'download_windows_description' => 'Experience MANGNO on your Windows computer and explore interactive disaster preparedness scenarios.',
            'download_android_title' => 'Android Version',
            'download_android_description' => 'Take MANGNO with you and learn disaster preparedness through interactive gameplay on your Android device.',
            'download_before_heading' => 'Before You Download',
            'download_before_notes' => implode("\n", [
                'Choose the version compatible with your device.',
                'Make sure your device meets the application\'s requirements.',
                'Download installation files only from the official MANGNO website.',
                'For Android, installing an APK downloaded outside the Google Play Store may require you to allow your browser or file manager to install apps from that source. Grant this only temporarily for the official MANGNO file, then turn it back off, and keep your device\'s other security protections enabled.',
                'For Windows, download and run the installer only if you trust this source. If Windows shows a security warning, review it carefully before continuing.',
            ]),
            'download_about' => 'MANGNO is an educational disaster preparedness game that helps players learn the right '
                .'actions before, during, and after hazards such as earthquakes, fires, typhoons, and volcanic '
                .'eruptions. Through simulated scenarios and interactive challenges, it turns preparedness into an '
                .'engaging, hands-on experience.',
            'download_extra_heading' => '',
            'download_extra' => '',
        ];
    }

    /** @return array<string, string> */
    public function all(): array
    {
        $defaults = $this->defaults();

        if (! Storage::disk('local')->exists(self::PATH)) {
            return $defaults;
        }

        $stored = json_decode((string) Storage::disk('local')->get(self::PATH), true);

        return array_merge($defaults, is_array($stored) ? $stored : []);
    }

    /** @param array<string, mixed> $values */
    public function put(array $values): void
    {
        Storage::disk('local')->put(
            self::PATH,
            json_encode(array_merge($this->all(), $values), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
