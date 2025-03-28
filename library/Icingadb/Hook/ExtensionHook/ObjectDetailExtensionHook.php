<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook\ExtensionHook;

use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Module\Icingadb\Hook\EventDetailExtensionHook;
use Icinga\Module\Icingadb\Hook\HostDetailExtensionHook;
use Icinga\Module\Icingadb\Hook\ServiceDetailExtensionHook;
use Icinga\Module\Icingadb\Hook\UserDetailExtensionHook;
use Icinga\Module\Icingadb\Hook\UsergroupDetailExtensionHook;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\RedundancyGroup;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Icingadb\Model\Usergroup;
use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Orm\Model;
use Throwable;

use function ipl\Stdlib\get_php_type;

abstract class ObjectDetailExtensionHook extends BaseExtensionHook
{
    /**
     * Load all extensions for the given object
     *
     * @param Host|Service|User|Usergroup|History $object
     *
     * @return array<int, ValidHtml>
     *
     * @throws InvalidArgumentException If the given model is not supported
     */
    final public static function loadExtensions(Model $object): array
    {
        switch (true) {
            case $object instanceof Host:
                $hookName = 'Icingadb\\HostDetailExtension';
                break;
            case $object instanceof Service:
                $hookName = 'Icingadb\\ServiceDetailExtension';
                break;
            case $object instanceof RedundancyGroup:
                $hookName = 'Icingadb\\RedundancyGroupDetailExtension';
                break;
            case $object instanceof User:
                $hookName = 'Icingadb\\UserDetailExtension';
                break;
            case $object instanceof Usergroup:
                $hookName = 'Icingadb\\UsergroupDetailExtension';
                break;
            case $object instanceof History:
                $hookName = 'Icingadb\\EventDetailExtension';
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf('%s is not a supported object type', get_php_type($object))
                );
        }

        $extensions = [];
        $lastUsedLocations = [];

        /**
         * @var $hook HostDetailExtensionHook
         * @var $hook ServiceDetailExtensionHook
         * @var $hook UserDetailExtensionHook
         * @var $hook UsergroupDetailExtensionHook
         * @var $hook EventDetailExtensionHook
         */
        foreach (Hook::all($hookName) as $hook) {
            $location = $hook->getLocation();
            if ($location < 0) {
                $location = null;
            }

            if ($location === null) {
                $section = $hook->getSection();
                if (! isset(self::BASE_LOCATIONS[$section])) {
                    Logger::error('Detail extension %s is using an invalid section: %s', get_class($hook), $section);
                    $section = self::DETAIL_SECTION;
                }

                if (isset($lastUsedLocations[$section])) {
                    $location = ++$lastUsedLocations[$section];
                } else {
                    $location = self::BASE_LOCATIONS[$section];
                    $lastUsedLocations[$section] = $location;
                }
            }

            try {
                // It may be ValidHtml, but modules shouldn't be able to break our views.
                // That's why it needs to be rendered instantly, as any error will then
                // be caught here.
                $extension = (string) $hook->getHtmlForObject(clone $object);

                $moduleName = $hook->getModule()->getName();

                $extensions[$location] = new HtmlElement(
                    'div',
                    Attributes::create([
                        'class' => 'icinga-module module-' . $moduleName,
                        'data-icinga-module' => $moduleName
                    ]),
                    HtmlString::create($extension)
                );
            } catch (Throwable $e) {
                Logger::error("Failed to load detail extension: %s\n%s", $e, $e->getTraceAsString());
                $extensions[$location] = Text::create(IcingaException::describe($e));
            }
        }

        return $extensions;
    }
}
