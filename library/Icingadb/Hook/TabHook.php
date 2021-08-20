<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Hook\Common\HookUtils;
use ipl\Html\ValidHtml;
use ipl\Orm\Model;

abstract class TabHook
{
    use Auth;
    use Database;
    use HookUtils;

    /**
     * Get the tab's name
     *
     * The name is used to identify this hook later on. It must be unique.
     * Multiple words in the name should be separated by dashes. (-)
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Get the tab's label
     *
     * The label is shown on the tab and in the browser's title.
     *
     * @return string
     */
    abstract public function getLabel(): string;

    /**
     * Get tab content for the given object
     *
     * @param Model $object
     *
     * @return ValidHtml[]
     */
    abstract public function getContent(Model $object): array;

    /**
     * Get tab controls for the given object
     *
     * @param Model $object
     *
     * @return ValidHtml[]
     */
    public function getControls(Model $object): array
    {
        return [];
    }

    /**
     * Get tab footer for the given object
     *
     * @param Model $object
     *
     * @return ValidHtml[]
     */
    public function getFooter(Model $object): array
    {
        return [];
    }

    /**
     * Get whether this tab should be shown
     *
     * @param Model $object
     *
     * @return bool
     */
    public function shouldBeShown(Model $object): bool
    {
        return true;
    }
}
