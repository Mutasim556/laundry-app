<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * Cache duration for settings in seconds (24 hours).
     */
    protected const CACHE_TTL = 86400;

    /**
     * Retrieve a setting value by key, with optional default fallback.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", self::CACHE_TTL, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set or update a setting value by key.
     */
    public static function set(string $key, mixed $value): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("setting.{$key}");
        Cache::forget('settings.all');

        return $setting;
    }

    /**
     * Remove / delete a setting by key.
     */
    public static function forget(string $key): bool
    {
        Cache::forget("setting.{$key}");
        Cache::forget('settings.all');

        return (bool) static::where('key', $key)->delete();
    }

    /**
     * Determine if a custom asset is set and exists in storage.
     */
    public static function hasCustom(string $key): bool
    {
        $path = static::get($key);

        return ! empty($path) && Storage::disk('public')->exists($path);
    }

    /**
     * Get the URL for the main site logo, falling back to default asset.
     */
    public static function getLogoUrl(): string
    {
        $path = static::get('site_logo');

        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        return asset('admin/assets/images/logo-text.png');
    }

    /**
     * Get the URL for the dark/inverted site logo, falling back to light logo or default asset.
     */
    public static function getDarkLogoUrl(): string
    {
        $path = static::get('site_logo_dark');

        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        $lightLogo = static::get('site_logo');
        if ($lightLogo && Storage::disk('public')->exists($lightLogo)) {
            return asset('storage/' . $lightLogo);
        }

        return asset('admin/assets/images/logo-light-text.png');
    }

    /**
     * Determine if a custom login page logo is configured and exists.
     */
    public static function hasLoginLogo(): bool
    {
        return static::hasCustom('site_logo_login');
    }

    /**
     * Get the URL for the login page logo, falling back to dark logo or default asset.
     */
    public static function getLoginLogoUrl(): string
    {
        $path = static::get('site_logo_login');

        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        return static::getDarkLogoUrl();
    }

    /**
     * Get the URL for the site icon / mini logo, falling back to default icon.
     */
    public static function getIconUrl(): string
    {
        $path = static::get('site_icon');

        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        return asset('admin/assets/images/logo-icon.png');
    }

    /**
     * Get the URL for the dark/inverted site icon, falling back to default icon.
     */
    public static function getDarkIconUrl(): string
    {
        $path = static::get('site_icon_dark');

        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        $mainIcon = static::get('site_icon');
        if ($mainIcon && Storage::disk('public')->exists($mainIcon)) {
            return asset('storage/' . $mainIcon);
        }

        return asset('admin/assets/images/logo-light-icon.png');
    }

    /**
     * Get the URL for the site favicon, falling back to default favicon.
     */
    public static function getFaviconUrl(): string
    {
        $path = static::get('site_favicon');

        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        return asset('admin/assets/images/favicon.png');
    }

    /**
     * Determine if a custom preloader/reloader animation or image is set.
     */
    public static function hasPreloader(): bool
    {
        return static::hasCustom('site_preloader');
    }

    /**
     * Get the URL for the custom preloader, or null if using standard SVG spinner.
     */
    public static function getPreloaderUrl(): ?string
    {
        $path = static::get('site_preloader');

        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        return null;
    }

    /**
     * Get application name, falling back to config('app.name').
     */
    public static function getSiteName(): string
    {
        return (string) static::get('site_name', config('app.name', 'Laundry App'));
    }

    /**
     * Get footer copyright text, falling back to default.
     */
    public static function getFooterText(): string
    {
        return (string) static::get('footer_text', 'All Rights Reserved by ' . static::getSiteName() . '.');
    }
}
