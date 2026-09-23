<?php

namespace App\Services\Plugin;

// Single place that reads a plugin's module.json — used by PluginLicense,
// PluginManagerController, LicenseInstaller, and PluginDiscoveryServiceProvider.
class PluginManifest
{
    public static function read(string $slug): ?array
    {
        $path = app_path("Modules/{$slug}/module.json");

        if (!file_exists($path)) {
            return null;
        }

        $manifest = json_decode(file_get_contents($path), true);

        return is_array($manifest) ? $manifest : null;
    }
}
