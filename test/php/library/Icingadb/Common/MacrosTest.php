<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Tests\Icinga\Modules\Icingadb\Common;

use Icinga\Module\Icingadb\Common\Macros;
use Icinga\Module\Icingadb\Compat\CompatHost;
use Icinga\Module\Icingadb\Compat\CompatService;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Orm\Query;
use ipl\Orm\ResultSet;
use PHPUnit\Framework\TestCase;

class MacrosTest extends TestCase
{
    use Macros;

    public const VARS = [
        'os'      => "Ubuntu",
        'days[0]' => 'mo',
        'days[1]' => 'tue',
        'days[2]' => 'wed',
        'days[3]' => 'thu',
        'days[4]' => 'fr'
    ];

    public function testHostMacros()
    {
        $host = new Host();
        $host->name = 'test';
        $host->address = '1.1.1.1';
        $host->address6 = '::1';
        $host->vars = self::VARS;

        $host->hostgroup = new Query();

        $this->performHostMacroTests($host, $host);
    }

    public function testHostMacrosOnCompatObject()
    {
        if (! class_exists('Icinga\Module\Monitoring\Object\Host')) {
            $this->markTestSkipped('This test requires the monitoring module');
        }

        $host = new Host();
        $host->name = 'test';
        $host->address = '1.1.1.1';
        $host->address6 = '::1';
        $host->vars = self::VARS;

        $host->hostgroup = new Query();

        $compatHost = new CompatHost($host);

        $this->performHostMacroTests($compatHost, $host);
    }

    protected function performHostMacroTests($host, $source)
    {
        $this->assertEquals($source->name, $this->expandMacros('$host.name$', $host));
        $this->assertEquals($source->name, $this->expandMacros('$name$', $host));
        $this->assertEquals($source->address, $this->expandMacros('$host.address$', $host));
        $this->assertEquals($source->address6, $this->expandMacros('$host.address6$', $host));

        // A Host can have more than one hostgroups
        $this->assertEquals('$host.hostgroup$', $this->expandMacros('$host.hostgroup$', $host));
        $this->assertEquals('$host.hostgroup.name$', $this->expandMacros('$host.hostgroup.name$', $host));

        // Host custom vars
        $this->assertEquals($source->vars['os'], $this->expandMacros('$host.vars.os$', $host));
        $this->assertEquals($source->vars['os'], $this->expandMacros('$vars.os$', $host));
        $this->assertEquals($source->vars['days[2]'], $this->expandMacros('$vars.days[2]$', $host));
        $this->assertEquals($source->vars['days[4]'], $this->expandMacros('$host.vars.days[4]$', $host));

        // Host to service relation
        $this->assertEquals('$service.name$', $this->expandMacros('$service.name$', $host));
        $this->assertEquals('$service.address$', $this->expandMacros('$service.address$', $host));

        // Service custom vars
        $this->assertEquals('$service.vars.os$', $this->expandMacros('$service.vars.os$', $host));
        $this->assertEquals('$service.vars.days[0]$', $this->expandMacros('$service.vars.days[0]$', $host));
        $this->assertEquals('$service.vars.days[2]$', $this->expandMacros('$service.vars.days[2]$', $host));
    }

    public function testServiceMacros()
    {
        $service = new Service();
        $service->name = 'test-service';
        $service->description = 'A test service';
        $service->vars = self::VARS;

        $service->servicegroup = new Query();

        $host = new Host();
        $host->name = 'test';
        $host->address = '1.1.1.1';
        $host->hostgroup = new ResultSet(new \ArrayIterator());
        $host->vars = self::VARS;

        $service->host = $host;

        $this->performServiceMacroTests($service, $service);
    }

    public function testServiceMacrosOnCompatObject()
    {
        if (! class_exists('Icinga\Module\Monitoring\Object\Service')) {
            $this->markTestSkipped('This test requires the monitoring module');
        }

        $service = new Service();
        $service->name = 'test-service';
        $service->description = 'A test service';
        $service->vars = self::VARS;

        $service->servicegroup = new Query();

        $host = new Host();
        $host->name = 'test';
        $host->address = '1.1.1.1';
        $host->hostgroup = new ResultSet(new \ArrayIterator());
        $host->vars = self::VARS;

        $service->host = $host;

        $compatService = new CompatService($service);

        $this->performServiceMacroTests($compatService, $service);
    }

    protected function performServiceMacroTests($service, $source)
    {
        $this->assertEquals($source->name, $this->expandMacros('$service.name$', $service));
        $this->assertEquals($source->name, $this->expandMacros('$name$', $service));
        $this->assertEquals($source->description, $this->expandMacros('$service.description$', $service));

        // A Service can have more than one hostgroups
        $this->assertEquals(
            '$service.servicegroup$',
            $this->expandMacros('$service.servicegroup$', $service)
        );
        $this->assertEquals(
            '$service.servicegroup.name$',
            $this->expandMacros('$service.servicegroup.name$', $service)
        );

        // Service custom vars
        $this->assertEquals($source->vars['os'], $this->expandMacros('$service.vars.os$', $service));
        $this->assertEquals($source->vars['os'], $this->expandMacros('$vars.os$', $service));
        $this->assertEquals($source->vars['days[2]'], $this->expandMacros('$vars.days[2]$', $service));
        $this->assertEquals($source->vars['days[4]'], $this->expandMacros('$service.vars.days[4]$', $service));

        $this->assertEquals($source->host->name, $this->expandMacros('$host.name$', $service));
        $this->assertEquals($source->host->address, $this->expandMacros('$host.address$', $service));

        // Host custom vars
        $this->assertEquals($source->host->vars['os'], $this->expandMacros('$host.vars.os$', $service));
        $this->assertEquals($source->host->vars['days[0]'], $this->expandMacros('$host.vars.days[0]$', $service));
        $this->assertEquals($source->host->vars['days[3]'], $this->expandMacros('$host.vars.days[3]$', $service));

        // A Host can have more than one hostgroups
        $this->assertEquals(
            '$host.hostgroup$',
            $this->expandMacros('$host.hostgroup$', $service)
        );
        $this->assertEquals(
            '$host.hostgroup.name$',
            $this->expandMacros('$host.hostgroup.name$', $service)
        );
    }
}
