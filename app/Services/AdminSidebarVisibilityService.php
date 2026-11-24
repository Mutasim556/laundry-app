<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AdminSidebarVisibilityService
{
    /**
     * Cached menu visibility map for a single service instance (one file read per render).
     *
     * @var array<string, mixed>|null
     */
    protected ?array $menuConfig = null;

    public function __construct(
        protected ?string $configPath = null
    ) {}

    public function getConfigPath(): string
    {
        return $this->configPath ?? config_path('visible_admin.json');
    }

    /**
     * Load and decode config/visible_admin.json once per instance.
     * Missing or invalid JSON safely falls back to an empty map (all menus visible).
     *
     * @return array<string, mixed>
     */
    public function loadConfig(): array
    {
        if ($this->menuConfig !== null) {
            return $this->menuConfig;
        }

        $path = $this->getConfigPath();
        if (! is_file($path)) {
            Log::warning('Admin sidebar visibility config file not found; defaulting all menus to visible.', [
                'path' => $path,
            ]);

            return $this->menuConfig = [];
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            Log::warning('Unable to read admin sidebar visibility config; defaulting all menus to visible.', [
                'path' => $path,
            ]);

            return $this->menuConfig = [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            Log::warning('Invalid JSON in admin sidebar visibility config; defaulting all menus to visible.', [
                'path' => $path,
                'error' => json_last_error_msg(),
            ]);

            return $this->menuConfig = [];
        }

        return $this->menuConfig = $decoded;
    }

    /**
     * Determine whether a top-level menu (or a child submenu under a parent) is visible.
     * Missing keys default to true so newly added menus are not accidentally hidden.
     */
    public function isVisible(string $menu, ?string $child = null): bool
    {
        $config = $this->loadConfig();

        if (! array_key_exists($menu, $config) || ! is_array($config[$menu])) {
            return true;
        }

        $parent = $config[$menu];
        $parentVisible = array_key_exists('visibility', $parent) ? (bool) $parent['visibility'] : true;

        if (! $parentVisible) {
            return false;
        }

        if ($child === null) {
            return true;
        }

        $children = $parent['children'] ?? null;
        if (! is_array($children) || ! array_key_exists($child, $children) || ! is_array($children[$child])) {
            return true;
        }

        $childEntry = $children[$child];

        return array_key_exists('visibility', $childEntry) ? (bool) $childEntry['visibility'] : true;
    }

    /**
     * Build the visibility callback passed to the Admin sidebar Blade view.
     */
    public function makeVisibilityChecker(): \Closure
    {
        // Eagerly load once per sidebar render
        $this->loadConfig();

        return fn (string $menu, ?string $child = null): bool => $this->isVisible($menu, $child);
    }
}
