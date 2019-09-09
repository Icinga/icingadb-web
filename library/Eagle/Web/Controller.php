<?php

namespace Icinga\Module\Eagle\Web;

use Icinga\Data\ResourceFactory;
use ipl\Web\Compat\CompatController;
use ipl\Sql\Connection;

class Controller extends CompatController
{
    /** @var Connection Connection to the Icinga database */
    private $db;

    /**
     * Get the connection to the Icinga database
     *
     * @return Connection
     *
     * @throws \Icinga\Exception\ConfigurationError If the related resource configuration does not exist
     */
    public function getDb()
    {
        if ($this->db === null) {
            $this->db = new Connection(
                ResourceFactory::getResourceConfig(
                    $this->Config()->get('icingadb', 'resource', 'icingadb')
                )->toArray()
            );
        }

        return $this->db;
    }
}
