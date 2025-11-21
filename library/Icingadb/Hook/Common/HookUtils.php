<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Hook\Common;

use Icinga\Application\ClassLoader;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;

trait HookUtils
{
    final public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize this hook
     *
     * Override this in your concrete implementation for any initialization at construction time.
     */
    protected function init()
    {
    }

    /**
     * Get the module this hook belongs to
     *
     * @return Module
     */
    final public function getModule(): Module
    {
        $moduleName = ClassLoader::extractModuleName(static::class);

        return Icinga::app()->getModuleManager()
            ->getModule($moduleName);
    }
}
