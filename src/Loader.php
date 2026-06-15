<?php

declare(strict_types=1);

namespace Valolink\GpWoo;

/**
 * Instantiates only enabled modules, gates each on should_load(context), then register()s.
 * The loader has no per-module knowledge — modules self-describe via their manifests.
 */
final class Loader
{
    public function __construct(
        private readonly Settings $settings,
        private readonly Registry $registry,
        private readonly Context $context,
    ) {}

    public function load(): void
    {
        foreach ($this->registry->all() as $manifest) {
            if (!$this->settings->is_module_enabled($manifest->id, $manifest->default_enabled)) {
                continue;
            }
            if (!class_exists($manifest->class)) {
                continue;
            }

            /** @var Module $module */
            $module = new ($manifest->class)(...$manifest->constructor_args);

            if (!$module->should_load($this->context)) {
                continue;
            }

            $module->register();
        }
    }
}
