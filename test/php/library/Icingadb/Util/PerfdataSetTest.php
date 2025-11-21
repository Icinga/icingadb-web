<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Tests\Icinga\Module\Icingadb\Util;

use Icinga\Module\Icingadb\Util\PerfDataSet;
use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Icingadb\Lib\PerfdataSetWithPublicData;

class PerfdataSetTest extends TestCase
{
    public function testWhetherValidSimplePerfdataLabelsAreProperlyParsed()
    {
        $pset = PerfdataSetWithPublicData::fromString('key1=val1   key2=val2 key3  =val3');
        $this->assertSame(
            'key1',
            $pset->perfdata[0]->getLabel(),
            'PerfdataSet does not correctly parse valid simple labels'
        );
        $this->assertSame(
            'key2',
            $pset->perfdata[1]->getLabel(),
            'PerfdataSet does not correctly parse valid simple labels'
        );
        $this->assertSame(
            'key3',
            $pset->perfdata[2]->getLabel(),
            'PerfdataSet does not correctly parse valid simple labels'
        );
    }

    public function testWhetherNonQuotedPerfdataLablesWithSpacesAreProperlyParsed()
    {
        $pset = PerfdataSetWithPublicData::fromString('key 1=val1 key 1 + 1=val2');
        $this->assertSame(
            'key 1',
            $pset->perfdata[0]->getLabel(),
            'PerfdataSet does not correctly parse non quoted labels with spaces'
        );
        $this->assertSame(
            'key 1 + 1',
            $pset->perfdata[1]->getLabel(),
            'PerfdataSet does not correctly parse non quoted labels with spaces'
        );
    }

    public function testWhetherValidQuotedPerfdataLabelsAreProperlyParsed()
    {
        $pset = PerfdataSetWithPublicData::fromString('\'key 1\'=val1 "key 2"=val2 \'a=b\'=0%;;2');
        $this->assertSame(
            'key 1',
            $pset->perfdata[0]->getLabel(),
            'PerfdataSet does not correctly parse valid quoted labels'
        );
        $this->assertSame(
            'key 2',
            $pset->perfdata[1]->getLabel(),
            'PerfdataSet does not correctly parse valid quoted labels'
        );
        $this->assertSame(
            'a=b',
            $pset->perfdata[2]->getLabel(),
            'PerfdataSet does not correctly parse labels with equal signs'
        );
    }

    public function testWhetherInvalidQuotedPerfdataLabelsAreProperlyParsed()
    {
        $pset = PerfdataSetWithPublicData::fromString('\'key 1=1 key 2"=2');
        $this->assertSame(
            'key 1',
            $pset->perfdata[0]->getLabel(),
            'PerfdataSet does not correctly parse invalid quoted labels'
        );
        $this->assertSame(
            'key 2"',
            $pset->perfdata[1]->getLabel(),
            'PerfdataSet does not correctly parse invalid quoted labels'
        );
        $pset = PerfdataSetWithPublicData::fromString('"key 1=1 "key 2"=2');
        $this->assertSame(
            'key 1=1',
            $pset->perfdata[0]->getLabel(),
            'PerfdataSet does not correctly parse invalid quoted labels'
        );
        $this->assertNull(
            $pset->perfdata[0]->getValue()
        );
        $this->assertSame(
            '2"',
            $pset->perfdata[1]->getLabel(),
            'PerfdataSet does not correctly parse invalid quoted labels'
        );
        $this->assertSame(
            2.0,
            $pset->perfdata[1]->getValue()
        );
    }

    /**
     * @depends testWhetherValidSimplePerfdataLabelsAreProperlyParsed
     */
    public function testWhetherAPerfdataSetIsIterable()
    {
        $pset = PerfdataSet::fromString('key=value');
        foreach ($pset as $p) {
            $this->assertSame('key', $p->getLabel());
            return;
        }

        $this->fail('PerfdataSet objects cannot be iterated');
    }

    public function testWhetherPerfdataSetsCanBeInitializedWithEmptyStrings()
    {
        $pset = PerfdataSetWithPublicData::fromString('');
        $this->assertEmpty($pset->perfdata, 'PerfdataSet::fromString does not accept emtpy strings');
    }
}
