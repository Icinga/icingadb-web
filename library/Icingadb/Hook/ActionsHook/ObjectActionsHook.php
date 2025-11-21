<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Hook\ActionsHook;

use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Module\Icingadb\Hook\Common\HookUtils;
use Icinga\Module\Icingadb\Hook\HostActionsHook;
use Icinga\Module\Icingadb\Hook\ServiceActionsHook;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Orm\Model;
use ipl\Web\Widget\Link;
use Throwable;

use function ipl\Stdlib\get_php_type;

abstract class ObjectActionsHook
{
    use HookUtils;

    /**
     * Load all actions for the given object
     *
     * @param Host|Service $object
     *
     * @return HtmlElement
     *
     * @throws InvalidArgumentException If the given model is not supported
     */
    final public static function loadActions(Model $object): HtmlElement
    {
        switch (true) {
            case $object instanceof Host:
                $hookName = 'Icingadb\\HostActions';
                break;
            case $object instanceof Service:
                $hookName = 'Icingadb\\ServiceActions';
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf('%s is not a supported object type', get_php_type($object))
                );
        }

        $list = new HtmlElement('ul', Attributes::create(['class' => 'object-detail-actions']));

        /** @var HostActionsHook|ServiceActionsHook $hook */
        foreach (Hook::all($hookName) as $hook) {
            try {
                foreach ($hook->getActionsForObject($object) as $link) {
                    if (! $link instanceof Link) {
                        continue;
                    }

                    if ($link->getBaseTarget() === null && ! $link->hasAttribute('target')) {
                        $link->setBaseTarget('_next');
                    }

                    // It may be ValidHtml, but modules shouldn't be able to break our views.
                    // That's why it needs to be rendered instantly, as any error will then
                    // be caught here.
                    $renderedLink = (string) $link;
                    $moduleName = $hook->getModule()->getName();

                    $list->addHtml(new HtmlElement('li', Attributes::create([
                        'class' => 'icinga-module module-' . $moduleName,
                        'data-icinga-module' => $moduleName
                    ]), HtmlString::create($renderedLink)));
                }
            } catch (Throwable $e) {
                Logger::error("Failed to load object actions: %s\n%s", $e, $e->getTraceAsString());
                $list->addHtml(new HtmlElement('li', null, Text::create(IcingaException::describe($e))));
            }
        }

        return $list;
    }
}
