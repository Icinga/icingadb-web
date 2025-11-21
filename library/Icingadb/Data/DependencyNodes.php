<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Data;

use ArrayIterator;
use Countable;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\DependencyNode;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Stdlib\Filter;
use IteratorAggregate;

/**
 * This class is used for {@see MultiselectQuickActions} so it can support objects of type Host
 * and Service at the same time.
 *
 * It fulfils the return type of {@see CommandActions::fetchCommandTargets()} by providing the
 * method getFilter() and implementing countable and iterable.
 *
 * @internal It must only be used in icingadb-web
 */
class DependencyNodes implements IteratorAggregate, Countable
{
    use Database;
    use Auth;

    protected $nodes;

    /** @var Filter\Rule */
    protected $filter;

    public function __construct(Filter\Rule $filter)
    {
        $this->filter = $filter;
    }

    public function getIterator(): ArrayIterator
    {
        if ($this->nodes === null) {
            $membersQuery = DependencyNode::on($this->getDb())
                ->with([
                    'host',
                    'host.state',
                    'service',
                    'service.state',
                    'service.host'
                ])
                ->filter($this->filter);

            $this->applyRestrictions($membersQuery);

            $nodes = [];
            foreach ($membersQuery as $node) {
                $nodes[] = $node->service_id !== null ? $node->service : $node->host;
            }

            $this->nodes = new ArrayIterator($nodes);
        }

        return $this->nodes;
    }

    public function getFilter(): Filter\Rule
    {
        return $this->filter;
    }

    public function count(): int
    {
        return $this->getIterator()->count();
    }

    public function getModel()
    {
        return new Host();
    }
}
