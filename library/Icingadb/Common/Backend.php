<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Common;

use Icinga\Application\Config as AppConfig;
use Icinga\Data\ResourceFactory;
use Icinga\Module\Icingadb\Model\Schema;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Config as SqlConfig;
use ipl\Sql\Connection;
use ipl\Sql\Expression;
use ipl\Sql\QueryBuilder;
use ipl\Sql\Select;
use PDO;

/**
 * Singleton providing access to the Icinga DB and Redis
 */
final class Backend
{
    /** @var ?Connection */
    private static $db;

    /** @var ?int */
    private static $schemaVersion;

    /** @var ?IcingaRedis */
    private static $redis;

    /** @var ?bool Whether the current Icinga DB version supports dependencies */
    private static $supportsDependencies;

    /**
     * Set the connection to the Icinga DB
     *
     * Usually not required, as the connection is created on demand. Useful for testing.
     *
     * @param Connection $db
     *
     * @return void
     */
    public static function setDb(Connection $db): void
    {
        self::$db = $db;
    }

    /**
     * Get the connection to the Icinga DB
     *
     * @return Connection
     */
    public static function getDb(): Connection
    {
        if (self::$db === null) {
            $config = new SqlConfig(ResourceFactory::getResourceConfig(
                AppConfig::module('icingadb')->get('icingadb', 'resource')
            ));

            $config->options = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ];
            if ($config->db === 'mysql') {
                $config->options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION SQL_MODE='STRICT_TRANS_TABLES"
                    . ",NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
            }

            self::$db = new Connection($config);

            $adapter = self::$db->getAdapter();
            if ($adapter instanceof Pgsql) {
                $quoted = $adapter->quoteIdentifier('user');
                self::$db->getQueryBuilder()
                    ->on(QueryBuilder::ON_SELECT_ASSEMBLED, function (&$sql) use ($quoted) {
                        // user is a reserved key word in PostgreSQL, so we need to quote it.
                        // TODO(lippserd): This is pretty hacky,
                        // reconsider how to properly implement identifier quoting.
                        $sql = str_replace(' user ', sprintf(' %s ', $quoted), $sql);
                        $sql = str_replace(' user.', sprintf(' %s.', $quoted), $sql);
                        $sql = str_replace('(user.', sprintf('(%s.', $quoted), $sql);
                    })
                    ->on(QueryBuilder::ON_ASSEMBLE_SELECT, function (Select $select) {
                        // For SELECT DISTINCT, all ORDER BY columns must appear in SELECT list.
                        if (! $select->getDistinct() || ! $select->hasOrderBy()) {
                            return;
                        }

                        $candidates = [];
                        foreach ($select->getOrderBy() as list($columnOrAlias, $_)) {
                            if ($columnOrAlias instanceof Expression) {
                                // Expressions can be and include anything,
                                // also columns that aren't already part of the SELECT list,
                                // so we're not trying to guess anything here.
                                // Such expressions must be in the SELECT list if necessary and
                                // referenced manually with an alias in ORDER BY.
                                continue;
                            }

                            $candidates[$columnOrAlias] = true;
                        }

                        foreach ($select->getColumns() as $alias => $column) {
                            if (is_int($alias)) {
                                if ($column instanceof Expression) {
                                    // This is the complement to the above consideration.
                                    // If it is an unaliased expression, ignore it.
                                    continue;
                                }
                            } else {
                                unset($candidates[$alias]);
                            }

                            if (! $column instanceof Expression) {
                                unset($candidates[$column]);
                            }
                        }

                        if (! empty($candidates)) {
                            $select->columns(array_keys($candidates));
                        }
                    });
            }
        }

        return self::$db;
    }

    /**
     * Get the schema version of the Icinga DB
     *
     * @return int
     */
    public static function getDbSchemaVersion(): int
    {
        if (self::$schemaVersion === null) {
            self::$schemaVersion = Schema::on(self::getDb())
                ->columns('version')
                ->first()
                ->version ?? 0;
        }

        return self::$schemaVersion;
    }

    /**
     * Set the connection to the Icinga Redis
     *
     * Usually not required, as the connection is created on demand. Useful for testing.
     *
     * @param IcingaRedis $redis
     *
     * @return void
     */
    public static function setRedis(IcingaRedis $redis): void
    {
        self::$redis = $redis;
    }

    /**
     * Get the connection to the Icinga Redis
     *
     * @return IcingaRedis
     */
    public static function getRedis(): IcingaRedis
    {
        if (self::$redis === null) {
            self::$redis = new IcingaRedis();
        }

        return self::$redis;
    }

    /**
     * Whether the current Icinga DB version supports dependencies
     *
     * @return bool
     */
    public static function supportsDependencies(): bool
    {
        if (self::$supportsDependencies === null) {
            if (self::getDb()->getAdapter() instanceof Pgsql) {
                self::$supportsDependencies = self::getDbSchemaVersion() >= 5;
            } else {
                self::$supportsDependencies = self::getDbSchemaVersion() >= 7;
            }
        }

        return self::$supportsDependencies;
    }
}
