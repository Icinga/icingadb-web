<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Hook\ExtensionHook;

use Icinga\Application\Hook;
use Icinga\Application\Logger;
use Icinga\Exception\IcingaException;
use Icinga\Module\Icingadb\Hook\HostsDetailExtensionHook;
use Icinga\Module\Icingadb\Hook\ServicesDetailExtensionHook;
use InvalidArgumentException;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Orm\Query;
use ipl\Stdlib\BaseFilter;
use ipl\Stdlib\Filter;
use Throwable;

abstract class ObjectsDetailExtensionHook extends BaseExtensionHook
{
    use BaseFilter;

    /**
     * Load all extensions for the given objects
     *
     * @param string $objectType
     * @param Query $query
     * @param Filter\Rule $baseFilter
     *
     * @return array<int, ValidHtml>
     *
     * @throws InvalidArgumentException If the given object type is not supported
     */
    final public static function loadExtensions(string $objectType, Query $query, Filter\Rule $baseFilter): array
    {
        switch ($objectType) {
            case 'host':
                $hookName = 'Icingadb\\HostsDetailExtension';
                break;
            case 'service':
                $hookName = 'Icingadb\\ServicesDetailExtension';
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf('%s is not a supported object type', $objectType)
                );
        }

        $extensions = [];
        $lastUsedLocations = [];
        /** @var HostsDetailExtensionHook|ServicesDetailExtensionHook $hook */
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
                $extension = (string) $hook->setBaseFilter($baseFilter)->getHtmlForObjects(clone $query);

                if (! empty($extension)) {
                    $moduleName = $hook->getModule()->getName();

                    $extensions[$location] = new HtmlElement(
                        'div',
                        Attributes::create([
                            'class' => 'icinga-module module-' . $moduleName,
                            'data-icinga-module' => $moduleName
                        ]),
                        HtmlString::create($extension)
                    );
                } else {
                    // There's no way for an extension to decide whether it should provide a result or not.
                    // So, even if empty, the result must be used as it's perfectly valid.
                    $extensions[$location] = HtmlString::create('');
                }
            } catch (Throwable $e) {
                Logger::error("Failed to load details extension: %s\n%s", $e, $e->getTraceAsString());
                $extensions[$location] = Text::create(IcingaException::describe($e));
            }
        }

        return $extensions;
    }
}
