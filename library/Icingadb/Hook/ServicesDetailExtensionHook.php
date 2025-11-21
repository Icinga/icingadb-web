<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Hook;

use Icinga\Module\Icingadb\Hook\ExtensionHook\ObjectsDetailExtensionHook;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Html\ValidHtml;
use ipl\Orm\Query;

abstract class ServicesDetailExtensionHook extends ObjectsDetailExtensionHook
{
    /**
     * Assemble and return an HTML representation of the given services
     *
     * The given query is already pre-filtered with the user's custom filter and restrictions. The base filter does
     * only contain the user's custom filter, use this for e.g. subsidiary links.
     *
     * The query is also limited by default, use `$hosts->limit(null)` to clear that. But beware that this may yield
     * a huge result set in case of a bulk selection.
     *
     * @param Query<Service> $services
     *
     * @return ValidHtml
     */
    abstract public function getHtmlForObjects(Query $services): ValidHtml;
}
