<?php

declare(strict_types=1);

namespace Valolink\GpWoo;

/**
 * Single non-autoloaded option holding all module enable flags + per-module settings,
 * keyed by module id. No module reads another module's settings directly.
 */
final class Settings
{
    public const OPTION_KEY = 'valolink_gp_woo_settings';

    private ?array $cache = null;

    public function all(): array
    {
        if ($this->cache === null) {
            $stored = get_option(self::OPTION_KEY, []);
            $this->cache = is_array($stored) ? $stored : [];
        }
        return $this->cache;
    }

    /**
     * Whether a module is enabled. When the module has no explicit setting yet,
     * falls back to $default (the manifest's default_enabled), so default-on modules
     * work before the settings page has ever been saved.
     */
    public function is_module_enabled(string $module_id, bool $default = false): bool
    {
        $all = $this->all();
        if (isset($all['modules'][$module_id]['enabled'])) {
            return (bool) $all['modules'][$module_id]['enabled'];
        }
        return $default;
    }

    public function set_module_enabled(string $module_id, bool $enabled): void
    {
        $all = $this->all();
        $all['modules'][$module_id]['enabled'] = $enabled;
        $this->persist($all);
    }

    public function get_module_setting(string $module_id, string $key, mixed $default = null): mixed
    {
        return $this->all()['modules'][$module_id]['settings'][$key] ?? $default;
    }

    public function set_module_setting(string $module_id, string $key, mixed $value): void
    {
        $all = $this->all();
        $all['modules'][$module_id]['settings'][$key] = $value;
        $this->persist($all);
    }

    public function forget_module(string $module_id): void
    {
        $all = $this->all();
        unset($all['modules'][$module_id]);
        $this->persist($all);
    }

    private function persist(array $all): void
    {
        $this->cache = $all;
        update_option(self::OPTION_KEY, $all, false);
    }
}
