<?php

declare(strict_types=1);

namespace Valolink\GpWoo;

/**
 * Contract every module implements. The loader only instantiates enabled modules,
 * calls should_load() (a cheap, side-effect-free context check), then register().
 */
interface Module
{
    /** Cheap check: is this module relevant to the current request? No side effects. */
    public function should_load(Context $context): bool;

    /** Wire hooks. Only called when the module is enabled and should_load() returned true. */
    public function register(): void;

    /** Remove this module's persistent footprint (options, meta, tables, cron). */
    public function uninstall(): void;
}
