<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Application\Config as AppConfig;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Config as SqlConfig;
use ipl\Sql\Connection;
use ipl\Sql\QueryBuilder;
use PDO;

trait Database
{
    /** @var Connection Connection to the Icinga database */
    private $db;

    /**
     * Get the connection to the Icinga database
     *
     * @return Connection
     *
     * @throws ConfigurationError If the related resource configuration does not exist
     */
    public function getDb(): Connection
    {
        if ($this->db === null) {
            $config = new SqlConfig(ResourceFactory::getResourceConfig(
                AppConfig::module('icingadb')->get('icingadb', 'resource')
            ));

            $config->options = [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION SQL_MODE='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE"
                    . ",ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'"
            ];

            $this->db = new Connection($config);

            $adapter = $this->db->getAdapter();
            if ($adapter instanceof Pgsql) {
                // user is a reserved key word in PostgreSQL, so we need to quote it.
                // TODO(lippserd): This is pretty hacky, reconsider how to properly implement identifier quoting.
                $quoted = $adapter->quoteIdentifier('user');
                $this->db->getQueryBuilder()->on(QueryBuilder::ON_SELECT_ASSEMBLED, function (&$sql) use ($quoted) {
                    $sql = str_replace(' user ', sprintf(' %s ', $quoted), $sql);
                    $sql = str_replace(' user.', sprintf(' %s.', $quoted), $sql);
                    $sql = str_replace('(user.', sprintf('(%s.', $quoted), $sql);
                });
            }
        }

        return $this->db;
    }
}
