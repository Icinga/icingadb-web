<?php

namespace Tests\Icinga\Module\Icingadb\Lib;

use DateTime;
use Icinga\Module\Icingadb\ProvidedHook\Reporting\Common\SlaReportUtils;
use Icinga\Module\Icingadb\ProvidedHook\Reporting\Common\SlaTimeline;
use ipl\Sql\Connection;

class SlaReportWithCustomDb
{
    use SlaReportUtils;

    /** @var Connection */
    protected static $conn;

    protected $reportType;

    protected static $dbConfiguration = [
        'mysql' => [
            'db'        => 'mysql',
            'host'      => '127.0.0.1',
            'port'      => 3306,
            'dbname'    => 'icingadb_web_unittest',
            'username'  => 'icingadb_web_unittest',
            'password'  => 'icingadb_web_unittest'
        ]
    ];

    public function resetConn()
    {
        static::$conn = null;
    }

    public function getDb(): Connection
    {
        if (! static::$conn) {
            $config = static::$dbConfiguration['mysql'];
            $host = getenv('ICINGADBWEB_TEST_MYSQL_HOST');
            if ($host) {
                $config['host'] = $host;
            }

            $port = getenv('ICINGADBWEB_TEST_MYSQL_PORT');
            if ($port) {
                $config['port'] = $port;
            }

            static::$conn = new Connection($config);
            $fixtures = file_get_contents(__DIR__ . '/fixtures.sql');
            static::$conn->exec($fixtures);
        }

        return static::$conn;
    }

    public function getReportType(): string
    {
        return $this->reportType;
    }

    public function getSlaTimeline(DateTime $start, DateTime $end, string $type, string $id): SlaTimeline
    {
        $this->reportType = $type;

        return $this->fetchReportData($start, $end, ['filter' => null])->getTimelines(bin2hex($id))[0];
    }

    protected function createReportData()
    {
        return new FakeReportData();
    }

    protected function createReportRow($_)
    {
        return 'NOPE!';
    }
}
