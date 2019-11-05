<?php

namespace Icinga\Module\Eagle\Model;

use Icinga\Module\Eagle\Common\ServiceStates;
use ipl\Orm\Relations;

class ServiceState extends State
{
    public function getTableName()
    {
        return 'service_state';
    }

    public function getKeyName()
    {
        return 'service_id';
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('environment', Environment::class);
        $relations->belongsTo('service', Service::class);

        $relations->hasOne('comment', ServiceComment::class)
            ->setCandidateKey('acknowledgement_comment_id');
    }

    /**
     * Get the host state as the textual representation
     *
     * @return string
     */
    public function getStateText()
    {
        return ServiceStates::text($this->properties['soft_state']);
    }

    /**
     * Get the host state as the translated textual representation
     *
     * @return string
     */
    public function getStateTextTranslated()
    {
        return ServiceStates::text($this->properties['soft_state']);
    }
}
