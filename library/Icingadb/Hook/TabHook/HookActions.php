<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook\TabHook;

use Generator;
use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Module\Icingadb\Hook\TabHook;
use ipl\Html\ValidHtml;
use ipl\Orm\Model;
use ipl\Stdlib\Str;
use Throwable;

/**
 * Trait HookActions
 */
trait HookActions
{
    /** @var Model The object to load tabs for */
    protected $objectToLoadTabsFor;

    /** @var TabHook[] Loaded tab hooks */
    protected $tabHooks;

    /**
     * Get default control elements
     *
     * @return ValidHtml[]
     */
    abstract protected function getDefaultTabControls(): array;

    public function __call($methodName, $args)
    {
        if (substr($methodName, -6) === 'Action') {
            $hookName = substr($methodName, 0, -6);

            $hooks = $this->loadTabHooks();
            if (isset($hooks[$hookName])) {
                $this->showTabHook($hooks[$hookName]);
                return;
            }
        }

        parent::__call($methodName, $args);
    }

    /**
     * Register the object for which to load additional tabs
     *
     * @param Model $object
     *
     * @return void
     */
    protected function loadTabsForObject(Model $object)
    {
        $this->objectToLoadTabsFor = $object;
    }

    /**
     * Load tab hooks
     *
     * @return array<string, TabHook>
     */
    protected function loadTabHooks(): array
    {
        if ($this->objectToLoadTabsFor === null) {
            return [];
        } elseif ($this->tabHooks !== null) {
            return $this->tabHooks;
        }

        $this->tabHooks = [];
        foreach (Hook::all('Icingadb\\Tab') as $hook) {
            /** @var TabHook $hook */
            try {
                if ($hook->shouldBeShown($this->objectToLoadTabsFor)) {
                    $this->tabHooks[Str::camel($hook->getName())] = $hook;
                }
            } catch (Throwable $e) {
                Logger::error("Failed to load tab hook: %s\n%s", $e, $e->getTraceAsString());
            }
        }

        return $this->tabHooks;
    }

    /**
     * Load additional tabs
     *
     * @return Generator<string, array{label: string, url: string}>
     */
    protected function loadAdditionalTabs(): Generator
    {
        foreach ($this->loadTabHooks() as $hook) {
            yield $hook->getName() => [
                'label' => $hook->getLabel(),
                'url'   => 'icingadb/' . $this->getRequest()->getControllerName() . '/' . $hook->getName()
            ];
        }
    }

    /**
     * Render the given tab hook
     *
     * @param TabHook $hook
     *
     * @return void
     */
    protected function showTabHook(TabHook $hook)
    {
        $moduleName = $hook->getModule()->getName();

        foreach ($hook->getControls($this->objectToLoadTabsFor) as $control) {
            $this->addControl($control);
        }

        if (! empty($this->controls->getContent())) {
            $this->controls->addAttributes([
                'class'                 => ['icinga-module', 'module-' . $moduleName],
                'data-icinga-module'    => $moduleName
            ]);
        } else {
            foreach ($this->getDefaultTabControls() as $control) {
                $this->addControl($control);
            }
        }

        foreach ($hook->getContent($this->objectToLoadTabsFor) as $content) {
            $this->addContent($content);
        }

        $this->content->addAttributes([
            'class'                 => ['icinga-module', 'module-' . $moduleName],
            'data-icinga-module'    => $moduleName
        ]);

        foreach ($hook->getFooter($this->objectToLoadTabsFor) as $footer) {
            $this->addFooter($footer);
        }

        $this->footer->addAttributes([
            'class'                 => ['icinga-module', 'module-' . $moduleName],
            'data-icinga-module'    => $moduleName
        ]);
    }
}
