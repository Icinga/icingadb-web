<?php

namespace Icinga\Module\Icingadb\Model;

use Icinga\Module\Icingadb\Common\HostStates;
use ipl\Orm\Relations;

/**
 * Host state model.
 */
class HostState extends State
{
    public function getTableName()
    {
        return 'host_state';
    }

    public function getKeyName()
    {
        return 'host_id';
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('host', Host::class);
    }

    /**
     * Get the host state as the textual representation
     *
     * @return string
     */
    public function getStateText()
    {
        return HostStates::text($this->properties['soft_state']);
    }

    /**
     * Get the host state as the translated textual representation
     *
     * @return string
     */
    public function getStateTextTranslated()
    {
        return HostStates::text($this->properties['soft_state']);
    }
}
