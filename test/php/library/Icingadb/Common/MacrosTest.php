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

    public function testHostMacros()
    {
        $mock = \Mockery::mock(Host::class);
        $mock->name = 'test';
        $mock->address = '1.1.1.1';
        $mock->address6 = '::1';

        $mock->hostgroup = new Query();

        $mock->vars = null;
        $mock->shouldReceive('mutateVarsProperty')->once()->andReturn([
            'os'      => "Ubuntu",
            'days[0]' => 'mo',
            'days[1]' => 'tue',
            'days[2]' => 'wed',
            'days[3]' => 'thu',
            'days[4]' => 'fr'
        ]);

        $this->assertEquals($mock->name, $this->expandMacros('$host.name$', $mock));
        $this->assertEquals($mock->name, $this->expandMacros('$name$', $mock));
        $this->assertEquals($mock->address, $this->expandMacros('$host.address$', $mock));
        $this->assertEquals($mock->address6, $this->expandMacros('$host.address6$', $mock));

        // A Host can have more than one hostgroups
        $this->assertEquals('$host.hostgroup$', $this->expandMacros('$host.hostgroup$', $mock));
        $this->assertEquals('$host.hostgroup.name$', $this->expandMacros('$host.hostgroup.name$', $mock));

        // Host custom vars
        $this->assertEquals($mock->vars['os'], $this->expandMacros('$host.vars.os$', $mock));
        $this->assertEquals($mock->vars['os'], $this->expandMacros('$vars.os$', $mock));
        $this->assertEquals($mock->vars['days[2]'], $this->expandMacros('$vars.days[2]$', $mock));
        $this->assertEquals($mock->vars['days[4]'], $this->expandMacros('$host.vars.days[4]$', $mock));

        // Host to service relation
        $this->assertEquals('$service.name$', $this->expandMacros('$service.name$', $mock));
        $this->assertEquals('$service.address$', $this->expandMacros('$service.address$', $mock));

        // Service custom vars
        $this->assertEquals('$service.vars.os$', $this->expandMacros('$service.vars.os$', $mock));
        $this->assertEquals('$service.vars.days[0]$', $this->expandMacros('$service.vars.days[0]$', $mock));
        $this->assertEquals('$service.vars.days[2]$', $this->expandMacros('$service.vars.days[2]$', $mock));
    }

    public function testServiceMacros()
    {
        $mock = \Mockery::mock(Service::class);
        $mock->name = 'test-service';
        $mock->description = 'A test service';

        $mock->servicegroup = new Query();

        $mock->vars = null;
        $mock->shouldReceive('mutateVarsProperty')->once()->andReturn([
            'os'      => "Ubuntu",
            'days[0]' => 'mo',
            'days[1]' => 'tue',
            'days[2]' => 'wed',
            'days[3]' => 'thu',
            'days[4]' => 'fr'
        ]);

        $hostMock = \Mockery::mock(Host::class);
        $hostMock->name = 'test';
        $hostMock->address = '1.1.1.1';
        $hostMock->hostgroup = new ResultSet(new \ArrayIterator());

        $hostMock->vars = null;
        $hostMock->shouldReceive('mutateVarsProperty')->once()->andReturn([
            'os'      => "Ubuntu",
            'days[0]' => 'mo',
            'days[1]' => 'tue',
            'days[2]' => 'wed',
            'days[3]' => 'thu',
            'days[4]' => 'fr'
        ]);

        $mock->host = $hostMock;

        $this->assertEquals($mock->name, $this->expandMacros('$service.name$', $mock));
        $this->assertEquals($mock->name, $this->expandMacros('$name$', $mock));
        $this->assertEquals($mock->description, $this->expandMacros('$service.description$', $mock));

        // A Service can have more than one hostgroups
        $this->assertEquals('$service.servicegroup$', $this->expandMacros('$service.servicegroup$', $mock));
        $this->assertEquals('$service.servicegroup.name$', $this->expandMacros('$service.servicegroup.name$', $mock));

        // Service custom vars
        $this->assertEquals($mock->vars['os'], $this->expandMacros('$service.vars.os$', $mock));
        $this->assertEquals($mock->vars['os'], $this->expandMacros('$vars.os$', $mock));
        $this->assertEquals($mock->vars['days[2]'], $this->expandMacros('$vars.days[2]$', $mock));
        $this->assertEquals($mock->vars['days[4]'], $this->expandMacros('$service.vars.days[4]$', $mock));

        $this->assertEquals($hostMock->name, $this->expandMacros('$host.name$', $mock));
        $this->assertEquals($hostMock->address, $this->expandMacros('$host.address$', $mock));

        // Host custom vars
        $this->assertEquals($hostMock->vars['os'], $this->expandMacros('$host.vars.os$', $mock));
        $this->assertEquals($hostMock->vars['days[0]'], $this->expandMacros('$host.vars.days[0]$', $mock));
        $this->assertEquals($hostMock->vars['days[3]'], $this->expandMacros('$host.vars.days[3]$', $mock));

        // A Host can have more than one hostgroups
        $this->assertEquals('$host.hostgroup$', $this->expandMacros('$host.hostgroup$', $mock));
        $this->assertEquals('$host.hostgroup.name$', $this->expandMacros('$host.hostgroup.name$', $mock));
    }
}
