<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Tests\Icinga\Modules\Icingadb\Model\Behavior;

use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Sql\Connection;
use ipl\Sql\Test\SqlAssertions;
use ipl\Sql\Test\TestConnection;
use ipl\Stdlib\Filter;
use PHPUnit\Framework\TestCase;

class FlattenedObjectVarsTest extends TestCase
{
    use SqlAssertions;

    private const SINGLE_UNEQUAL_RESULT = <<<'SQL'
SELECT host.id
FROM host
WHERE (host.id NOT IN ((SELECT sub_customvar_flat_host.id AS sub_customvar_flat_host_id
                        FROM customvar_flat sub_customvar_flat
                                 INNER JOIN host_customvar sub_customvar_flat_t_host_customvar
                                            ON sub_customvar_flat_t_host_customvar.customvar_id =
                                               sub_customvar_flat.customvar_id
                                 INNER JOIN host sub_customvar_flat_host
                                            ON sub_customvar_flat_host.id = sub_customvar_flat_t_host_customvar.host_id
                        WHERE ((sub_customvar_flat.flatname = ?) AND (sub_customvar_flat.flatvalue = ?))
                          AND (sub_customvar_flat_host.id IS NOT NULL)
                        GROUP BY sub_customvar_flat_host.id
                        HAVING COUNT(DISTINCT sub_customvar_flat.id) >= ?)) OR host.id IS NULL)
ORDER BY host.id
SQL;

    private const DOUBLE_UNEQUAL_RESULT = <<<'SQL'
SELECT host.id
FROM host
WHERE (host.id NOT IN ((SELECT sub_customvar_flat_host.id AS sub_customvar_flat_host_id
                        FROM customvar_flat sub_customvar_flat
                                 INNER JOIN host_customvar sub_customvar_flat_t_host_customvar
                                            ON sub_customvar_flat_t_host_customvar.customvar_id =
                                               sub_customvar_flat.customvar_id
                                 INNER JOIN host sub_customvar_flat_host
                                            ON sub_customvar_flat_host.id = sub_customvar_flat_t_host_customvar.host_id
                        WHERE (((sub_customvar_flat.flatname = ?) AND (sub_customvar_flat.flatvalue = ?)) OR
                               ((sub_customvar_flat.flatname = ?) AND (sub_customvar_flat.flatvalue = ?)))
                          AND (sub_customvar_flat_host.id IS NOT NULL)
                        GROUP BY sub_customvar_flat_host.id
                        HAVING COUNT(DISTINCT sub_customvar_flat.id) >= ?)) OR host.id IS NULL)
ORDER BY host.id
SQL;

    private const EQUAL_UNEQUAL_RESULT = <<<'SQL'
SELECT host.id
FROM host
WHERE ((host.id NOT IN ((SELECT sub_customvar_flat_host.id AS sub_customvar_flat_host_id
                         FROM customvar_flat sub_customvar_flat
                                  INNER JOIN host_customvar sub_customvar_flat_t_host_customvar
                                             ON sub_customvar_flat_t_host_customvar.customvar_id =
                                                sub_customvar_flat.customvar_id
                                  INNER JOIN host sub_customvar_flat_host
                                             ON sub_customvar_flat_host.id = sub_customvar_flat_t_host_customvar.host_id
                         WHERE ((sub_customvar_flat.flatname = ?) AND (sub_customvar_flat.flatvalue = ?))
                           AND (sub_customvar_flat_host.id IS NOT NULL)
                         GROUP BY sub_customvar_flat_host.id
                         HAVING COUNT(DISTINCT sub_customvar_flat.id) >= ?)) OR host.id IS NULL))
  AND (host.id IN ((SELECT sub_customvar_flat_host.id AS sub_customvar_flat_host_id
                    FROM customvar_flat sub_customvar_flat
                             INNER JOIN host_customvar sub_customvar_flat_t_host_customvar
                                        ON sub_customvar_flat_t_host_customvar.customvar_id =
                                           sub_customvar_flat.customvar_id
                             INNER JOIN host sub_customvar_flat_host
                                        ON sub_customvar_flat_host.id = sub_customvar_flat_t_host_customvar.host_id
                    WHERE (sub_customvar_flat.flatname = ?)
                      AND (sub_customvar_flat.flatvalue = ?)
                    GROUP BY sub_customvar_flat_host.id
                    HAVING COUNT(DISTINCT sub_customvar_flat.id) >= ?)))
ORDER BY host.id
SQL;

    private const DOUBLE_EQUAL_RESULT = <<<'SQL'
SELECT host.id
FROM host
WHERE host.id IN ((SELECT sub_customvar_flat_host.id AS sub_customvar_flat_host_id
                   FROM customvar_flat sub_customvar_flat
                            INNER JOIN host_customvar sub_customvar_flat_t_host_customvar
                                       ON sub_customvar_flat_t_host_customvar.customvar_id =
                                          sub_customvar_flat.customvar_id
                            INNER JOIN host sub_customvar_flat_host
                                       ON sub_customvar_flat_host.id = sub_customvar_flat_t_host_customvar.host_id
                   WHERE ((sub_customvar_flat.flatname = ?) AND (sub_customvar_flat.flatvalue = ?))
                      OR ((sub_customvar_flat.flatname = ?) AND (sub_customvar_flat.flatvalue = ?))
                   GROUP BY sub_customvar_flat_host.id
                   HAVING COUNT(DISTINCT sub_customvar_flat.id) >= ?))
ORDER BY host.id
SQL;

    /** @var Connection */
    private $connection;

    public function setUp(): void
    {
        $this->connection = new TestConnection();
        Backend::setDb($this->connection);
        $this->setUpSqlAssertions();
    }

    public function testSingleUnequalCondition()
    {
        $query = Host::on($this->connection)
            ->columns('host.id')
            ->orderBy('host.id')
            ->filter(Filter::unequal('host.vars.in.valid', 'foo'));

        $this->assertSql(self::SINGLE_UNEQUAL_RESULT, $query->assembleSelect(), ['in.valid', 'foo', 1]);
    }

    public function testDoubleUnequalCondition()
    {
        $query = Host::on($this->connection)
            ->columns('host.id')
            ->orderBy('host.id')
            ->filter(Filter::unequal('host.vars.in.valid', 'foo'))
            ->filter(Filter::unequal('host.vars.missing', 'bar'));

        $this->assertSql(
            self::DOUBLE_UNEQUAL_RESULT,
            $query->assembleSelect(),
            ['in.valid', 'foo', 'missing', 'bar', 1]
        );
    }

    public function testEqualAndUnequalCondition()
    {
        $query = Host::on($this->connection)
            ->columns('host.id')
            ->orderBy('host.id')
            ->filter(Filter::unequal('host.vars.in.valid', 'bar'))
            ->filter(Filter::equal('host.vars.env', 'foo'));

        $this->assertSql(
            self::EQUAL_UNEQUAL_RESULT,
            $query->assembleSelect(),
            ['in.valid', 'bar', 1, 'env', 'foo', 1]
        );
    }

    public function testDoubleEqualCondition()
    {
        $query = Host::on($this->connection)
            ->columns('host.id')
            ->orderBy('host.id')
            ->filter(Filter::equal('host.vars.env', 'foo'))
            ->filter(Filter::equal('host.vars.os', 'bar'));

        $this->assertSql(
            self::DOUBLE_EQUAL_RESULT,
            $query->assembleSelect(),
            ['env', 'foo', 'os', 'bar', 2]
        );
    }
}
