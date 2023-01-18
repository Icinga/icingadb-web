<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Tests\Icinga\Modules\Icingadb\Common;

use Icinga\Module\Icingadb\Common\Macros;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use ipl\Orm\Query;
use ipl\Orm\ResultSet;
use PHPUnit\Framework\TestCase;

class MacrosTest extends TestCase
{
    use Macros;

    const VARS = [
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

        $this->assertEquals($host->name, $this->expandMacros('$host.name$', $host));
        $this->assertEquals($host->name, $this->expandMacros('$name$', $host));
        $this->assertEquals($host->address, $this->expandMacros('$host.address$', $host));
        $this->assertEquals($host->address6, $this->expandMacros('$host.address6$', $host));

        // A Host can have more than one hostgroups
        $this->assertEquals('$host.hostgroup$', $this->expandMacros('$host.hostgroup$', $host));
        $this->assertEquals('$host.hostgroup.name$', $this->expandMacros('$host.hostgroup.name$', $host));

        // Host custom vars
        $this->assertEquals($host->vars['os'], $this->expandMacros('$host.vars.os$', $host));
        $this->assertEquals($host->vars['os'], $this->expandMacros('$vars.os$', $host));
        $this->assertEquals($host->vars['days[2]'], $this->expandMacros('$vars.days[2]$', $host));
        $this->assertEquals($host->vars['days[4]'], $this->expandMacros('$host.vars.days[4]$', $host));

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

        $this->assertEquals($service->name, $this->expandMacros('$service.name$', $service));
        $this->assertEquals($service->name, $this->expandMacros('$name$', $service));
        $this->assertEquals($service->description, $this->expandMacros('$service.description$', $service));

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
        $this->assertEquals($service->vars['os'], $this->expandMacros('$service.vars.os$', $service));
        $this->assertEquals($service->vars['os'], $this->expandMacros('$vars.os$', $service));
        $this->assertEquals($service->vars['days[2]'], $this->expandMacros('$vars.days[2]$', $service));
        $this->assertEquals($service->vars['days[4]'], $this->expandMacros('$service.vars.days[4]$', $service));

        $this->assertEquals($host->name, $this->expandMacros('$host.name$', $service));
        $this->assertEquals($host->address, $this->expandMacros('$host.address$', $service));

        // Host custom vars
        $this->assertEquals($host->vars['os'], $this->expandMacros('$host.vars.os$', $service));
        $this->assertEquals($host->vars['days[0]'], $this->expandMacros('$host.vars.days[0]$', $service));
        $this->assertEquals($host->vars['days[3]'], $this->expandMacros('$host.vars.days[3]$', $service));

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
