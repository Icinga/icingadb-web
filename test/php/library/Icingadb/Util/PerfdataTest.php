<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Tests\Icinga\Module\Icingadb\Util;

use Icinga\Module\Icingadb\Util\PerfData;
use PHPUnit\Framework\TestCase;

class PerfdataTest extends TestCase
{
    public function testWhetherFromStringThrowsExceptionWhenGivenAnEmptyString()
    {
        $this->expectException(\InvalidArgumentException::class);

        Perfdata::fromString('');
    }

    public function testWhetherFromStringThrowsExceptionWhenGivenAnInvalidString()
    {
        $this->expectException(\InvalidArgumentException::class);

        Perfdata::fromString('test');
    }

    public function testWhetherFromStringParsesAGivenStringCorrectly()
    {
        $p = Perfdata::fromString('key=1234');
        $this->assertSame(
            'key',
            $p->getLabel(),
            'Perfdata::fromString does not properly parse performance data labels'
        );
        $this->assertSame(
            1234.0,
            $p->getValue(),
            'Perfdata::fromString does not properly parse performance data values'
        );
    }

    /**
     * @depends testWhetherFromStringParsesAGivenStringCorrectly
     */
    public function testWhetherGetValueReturnsValidValues()
    {
        $this->assertSame(
            1337.0,
            Perfdata::fromString('test=1337')->getValue(),
            'Perfdata::getValue does not return correct values'
        );
        $this->assertSame(
            1337.0,
            Perfdata::fromString('test=1337;;;;')->getValue(),
            'Perfdata::getValue does not return correct values'
        );
    }

    /**
     * @depends testWhetherFromStringParsesAGivenStringCorrectly
     */
    public function testWhetherDecimalValuesAreCorrectlyParsed()
    {
        $this->assertSame(
            1337.5,
            Perfdata::fromString('test=1337.5')->getValue(),
            'Perfdata objects do not parse decimal values correctly'
        );
        $this->assertSame(
            1337.5,
            Perfdata::fromString('test=1337.5B')->getValue(),
            'Perfdata objects do not parse decimal values correctly'
        );
    }

    /**
     * @depends testWhetherFromStringParsesAGivenStringCorrectly
     */
    public function testWhetherGetValueReturnsNullForInvalidOrUnknownValues()
    {
        $this->assertNull(
            Perfdata::fromString('test=U')->getValue(),
            'Perfdata::getValue does not return null for unknown values'
        );
        $this->assertNull(
            Perfdata::fromString('test=i am not a value')->getValue(),
            'Perfdata::getValue does not return null for invalid values'
        );
        $this->assertNull(
            PerfData::fromString('test=')->getValue(),
            'Perfdata::getValue does not return null for invalid values'
        );
        $this->assertNull(
            PerfData::fromString('test=-kW')->getValue(),
            'Perfdata::getValue does not return null for invalid values'
        );
        $this->assertNull(
            PerfData::fromString('test=kW')->getValue(),
            'Perfdata::getValue does not return null for invalid values'
        );
        $this->assertNull(
            PerfData::fromString('test=-')->getValue(),
            'Perfdata::getValue does not return null for invalid values'
        );
    }

    /**
     * @depends testWhetherFromStringParsesAGivenStringCorrectly
     */
    public function testWhetherUnitOfUnkownValuesIsCorrectlyIdentified()
    {
        $this->assertNull(
            Perfdata::fromString('test=U')->getUnit(),
            'Perfdata::getUnit does not return null for unknown values'
        );
        $this->assertNull(
            Perfdata::fromString('test=i am not a value')->getUnit(),
            'Perfdata::getUnit does not return null for unknown values'
        );
        $this->assertNull(
            PerfData::fromString('test=')->getUnit(),
            'Perfdata::getUnit does not return null for unknown values'
        );
        $this->assertSame(
            'kW',
            PerfData::fromString('test=-kW')->getUnit(),
            'Perfdata::getUnit does not return correct unit for invalid values'
        );
        $this->assertSame(
            'kW',
            PerfData::fromString('test=kW')->getUnit(),
            'Perfdata::getUnit does not return correct unit for invalid values'
        );
        $this->assertNull(
            PerfData::fromString('test=-')->getUnit(),
            'Perfdata::getUnit does not return null for unknown values'
        );
    }

    /**
     * @depends testWhetherFromStringParsesAGivenStringCorrectly
     */
    public function testWhethergetWarningThresholdReturnsCorrectValues()
    {
        $zeroToTen = Perfdata::fromString('test=1;10')->getWarningThreshold();
        $this->assertSame(
            0.0,
            $zeroToTen->getMin(),
            'Perfdata::getWarningThreshold does not return correct values'
        );
        $this->assertSame(
            10.0,
            $zeroToTen->getMax(),
            'Perfdata::getWarningThreshold does not return correct values'
        );
        $tenToInfinity = Perfdata::fromString('test=1;10:')->getWarningThreshold();
        $this->assertSame(
            10.0,
            $tenToInfinity->getMin(),
            'Perfdata::getWarningThreshold does not return correct values'
        );
        $this->assertNull(
            $tenToInfinity->getMax(),
            'Perfdata::getWarningThreshold does not return correct values'
        );
        $infinityToTen = Perfdata::fromString('test=1;~:10')->getWarningThreshold();
        $this->assertNull(
            $infinityToTen->getMin(),
            'Perfdata::getWarningThreshold does not return correct values'
        );
        $this->assertSame(
            10.0,
            $infinityToTen->getMax(),
            'Perfdata::getWarningThreshold does not return correct values'
        );
        $tenToTwenty = Perfdata::fromString('test=1;10:20')->getWarningThreshold();
        $this->assertSame(
            10.0,
            $tenToTwenty->getMin(),
            'Perfdata::getWarningThreshold does not return correct values'
        );
        $this->assertSame(
            20.0,
            $tenToTwenty->getMax(),
            'Perfdata::getWarningThreshold does not return correct values'
        );
        $tenToTwentyInverted = Perfdata::fromString('test=1;@10:20')->getWarningThreshold();
        $this->assertTrue(
            $tenToTwentyInverted->isInverted(),
            'Perfdata::getWarningThreshold does not return correct values'
        );
    }

    /**
     * @depends testWhetherFromStringParsesAGivenStringCorrectly
     */
    public function testWhetherGetCriticalThresholdReturnsCorrectValues()
    {
        $zeroToTen = Perfdata::fromString('test=1;;10')->getCriticalThreshold();
        $this->assertSame(
            0.0,
            $zeroToTen->getMin(),
            'Perfdata::getCriticalThreshold does not return correct values'
        );
        $this->assertSame(
            10.0,
            $zeroToTen->getMax(),
            'Perfdata::getCriticalThreshold does not return correct values'
        );
        $tenToInfinity = Perfdata::fromString('test=1;;10:')->getCriticalThreshold();
        $this->assertSame(
            10.0,
            $tenToInfinity->getMin(),
            'Perfdata::getCriticalThreshold does not return correct values'
        );
        $this->assertNull(
            $tenToInfinity->getMax(),
            'Perfdata::getCriticalThreshold does not return correct values'
        );
        $infinityToTen = Perfdata::fromString('test=1;;~:10')->getCriticalThreshold();
        $this->assertNull(
            $infinityToTen->getMin(),
            'Perfdata::getCriticalThreshold does not return correct values'
        );
        $this->assertSame(
            10.0,
            $infinityToTen->getMax(),
            'Perfdata::getCriticalThreshold does not return correct values'
        );
        $tenToTwenty = Perfdata::fromString('test=1;;10:20')->getCriticalThreshold();
        $this->assertSame(
            10.0,
            $tenToTwenty->getMin(),
            'Perfdata::getCriticalThreshold does not return correct values'
        );
        $this->assertSame(
            20.0,
            $tenToTwenty->getMax(),
            'Perfdata::getCriticalThreshold does not return correct values'
        );
        $tenToTwentyInverted = Perfdata::fromString('test=1;;@10:20')->getCriticalThreshold();
        $this->assertTrue(
            $tenToTwentyInverted->isInverted(),
            'Perfdata::getCriticalThreshold does not return correct values'
        );
    }

    /**
     * @depends testWhetherFromStringParsesAGivenStringCorrectly
     */
    public function testWhetherGetMinimumValueReturnsCorrectValues()
    {
        $this->assertSame(
            1337.0,
            Perfdata::fromString('test=1;;;1337')->getMinimumValue(),
            'Perfdata::getMinimumValue does not return correct values'
        );
        $this->assertSame(
            1337.5,
            Perfdata::fromString('test=1;;;1337.5')->getMinimumValue(),
            'Perfdata::getMinimumValue does not return correct values'
        );
    }

    /**
     * @depends testWhetherFromStringParsesAGivenStringCorrectly
     */
    public function testWhetherGetMaximumValueReturnsCorrectValues()
    {
        $this->assertSame(
            1337.0,
            Perfdata::fromString('test=1;;;;1337')->getMaximumValue(),
            'Perfdata::getMaximumValue does not return correct values'
        );
        $this->assertSame(
            1337.5,
            Perfdata::fromString('test=1;;;;1337.5')->getMaximumValue(),
            'Perfdata::getMaximumValue does not return correct values'
        );
    }

    /**
     * @depends testWhetherFromStringParsesAGivenStringCorrectly
     */
    public function testWhetherMissingValuesAreProperlyHandled()
    {
        $perfdata = Perfdata::fromString('test=1;;3;5');
        $this->assertEmpty(
            (string) $perfdata->getWarningThreshold(),
            'Perfdata objects do not correctly identify omitted warning tresholds'
        );
        $this->assertNull(
            $perfdata->getMaximumValue(),
            'Perfdata objects do not return null for missing maximum values'
        );
    }

    /**
     * @depends testWhetherGetValueReturnsValidValues
     */
    public function testWhetherValuesAreIdentifiedAsNumber()
    {
        $this->assertTrue(
            Perfdata::fromString('test=666')->isNumber(),
            'Perfdata objects do not identify ordinary digits as number'
        );
    }

    /**
     * @depends testWhetherGetValueReturnsValidValues
     */
    public function testWhetherValuesAreIdentifiedAsSeconds()
    {
        $this->assertTrue(
            Perfdata::fromString('test=666s')->isSeconds(),
            'Perfdata objects do not identify seconds as seconds'
        );
    }

    /**
     * @depends testWhetherGetValueReturnsValidValues
     */
    public function testWhetherValuesAreIdentifiedAsPercentage()
    {
        $this->assertTrue(
            Perfdata::fromString('test=66%')->isPercentage(),
            'Perfdata objects do not identify percentages as percentages'
        );
    }

    /**
     * @depends testWhetherValuesAreIdentifiedAsPercentage
     */
    public function testWhetherMinAndMaxAreNotRequiredIfUnitIsInPercent()
    {
        $perfdata = Perfdata::fromString('test=1%');
        $this->assertSame(
            0.0,
            $perfdata->getMinimumValue(),
            'Perfdata objects do not set minimum value to 0 if UOM is %'
        );
        $this->assertSame(
            100.0,
            $perfdata->getMaximumValue(),
            'Perfdata objects do not set maximum value to 100 if UOM is %'
        );
    }

    /**
     * @depends testWhetherGetValueReturnsValidValues
     */
    public function testWhetherValuesAreIdentifiedAsBytes()
    {
        $this->assertTrue(
            Perfdata::fromString('test=66666B')->isBytes(),
            'Perfdata objects do not identify bytes as bytes'
        );
    }

    /**
     * @depends testWhetherGetValueReturnsValidValues
     */
    public function testWhetherValuesAreIdentifiedAsCounter()
    {
        $this->assertTrue(
            Perfdata::fromString('test=123c')->isCounter(),
            'Perfdata objects do not identify counters as counters'
        );
    }

    /**
     * @depends testWhetherValuesAreIdentifiedAsPercentage
     */
    public function testWhetherPercentagesAreHandledCorrectly()
    {
        $this->assertSame(
            66.0,
            Perfdata::fromString('test=66%')->getPercentage(),
            'Perfdata objects do not correctly handle native percentages'
        );
        $this->assertSame(
            50.0,
            Perfdata::fromString('test=0;;;-250;250')->getPercentage(),
            'Perfdata objects do not correctly convert suitable values to percentages'
        );
        $this->assertNull(
            Perfdata::fromString('test=50')->getPercentage(),
            'Perfdata objects do return a percentage though their unit is not % and no maximum is given'
        );
        $this->assertNull(
            Perfdata::fromString('test=25;;;50;100')->getPercentage(),
            'Perfdata objects do return a percentage though their value is lower than it\'s allowed minimum'
        );
        $this->assertNull(
            Perfdata::fromString('test=25;;;0;')->getPercentage(),
            'Perfdata objects do not ignore empty max values when returning percentages'
        );
        $this->assertNull(
            Perfdata::fromString('test=25;;;0;0')->getPercentage(),
            'Perfdata objects do not ignore impossible min/max combinations when returning percentages'
        );
    }

    public function testWhetherInvalidValueInPerfDataHandledCorrectly()
    {
        $p1 = Perfdata::fromString('test=2,0');
        $this->assertFalse($p1->isValid());
        $this->assertNull(
            $p1->getValue(),
            'Perfdata::getValue does not return null for invalid values'
        );
        $this->assertSame(
            '2,0',
            $p1->toArray()['value']
        );

        $p2 = Perfdata::fromString('test=i am not a value');
        $this->assertFalse($p2->isValid());
        $this->assertNull(
            $p2->getValue(),
            'Perfdata::getValue does not return null for invalid values'
        );
        $this->assertSame(
            'i am not a value',
            $p2->toArray()['value']
        );

        $p3 = Perfdata::fromString('test=');
        $this->assertFalse($p3->isValid());
        $this->assertNull(
            $p3->getValue(),
            'Perfdata::getValue does not return null for invalid values'
        );
        $this->assertSame(
            '',
            $p3->toArray()['value']
        );

        $p4 = Perfdata::fromString('test=-kW');
        $this->assertFalse($p4->isValid());
        $this->assertNull(
            $p4->getValue(),
            'Perfdata::getValue does not return null for invalid values'
        );
        $this->assertSame(
            '-kW',
            $p4->toArray()['value']
        );

        $p5 = Perfdata::fromString('test=kW');
        $this->assertFalse($p5->isValid());
        $this->assertNull(
            $p5->getValue(),
            'Perfdata::getValue does not return null for invalid values'
        );
        $this->assertSame(
            'kW',
            $p5->toArray()['value']
        );

        $p6 = Perfdata::fromString('test=-');
        $this->assertFalse($p6->isValid());
        $this->assertNull(
            $p6->getValue(),
            'Perfdata::getValue does not return null for invalid values'
        );
        $this->assertSame(
            '-',
            $p6->toArray()['value']
        );
    }

    public function testWhetherInvalidMinInPerfDataHandledCorrectly()
    {
        $p1 = Perfdata::fromString('test=1;;;2,0');
        $this->assertFalse($p1->isValid());
        $this->assertNull(
            $p1->getMinimumValue(),
            'Perfdata::getMinimumValue does not return null for invalid min values'
        );
        $this->assertSame(
            '2,0',
            $p1->toArray()['min']
        );

        $p2 = Perfdata::fromString('test=1;;;foo');
        $this->assertFalse($p2->isValid());
        $this->assertNull(
            $p2->getMinimumValue(),
            'Perfdata::getMinimumValue does not return null for invalid min values'
        );
        $this->assertSame(
            'foo',
            $p2->toArray()['min']
        );
    }

    public function testWhetherInvalidMaxInPerfDataHandledCorrectly()
    {
        $p1 = Perfdata::fromString('test=1;;;;2,0');
        $this->assertFalse($p1->isValid());
        $this->assertNull(
            $p1->getMaximumValue(),
            'Perfdata::getMaximumValue does not return null for invalid max values'
        );
        $this->assertSame(
            '2,0',
            $p1->toArray()['max']
        );

        $p2 = Perfdata::fromString('test=1;;;;foo');
        $this->assertFalse($p2->isValid());
        $this->assertNull(
            $p2->getMaximumValue(),
            'Perfdata::getMaximumValue does not return null for invalid max values'
        );
        $this->assertSame(
            'foo',
            $p2->toArray()['max']
        );
    }

    public function testWhetherInvalidWarningThresholdInPerfDataHandledCorrectly()
    {
        $p1 = Perfdata::fromString('test=1;2,0:');
        $this->assertFalse($p1->getWarningThreshold()->isValid());
        $this->assertFalse($p1->isValid());
        $this->assertSame(
            '2,0:',
            (string) $p1->getWarningThreshold()
        );

        $p2 = Perfdata::fromString('test=1;0:4,0');
        $this->assertFalse($p2->getWarningThreshold()->isValid());
        $this->assertFalse($p2->isValid());
        $this->assertSame(
            '0:4,0',
            (string) $p2->getWarningThreshold()
        );

        $p3 = Perfdata::fromString('test=1;foo');
        $this->assertFalse($p2->getWarningThreshold()->isValid());
        $this->assertFalse($p3->isValid());
        $this->assertSame(
            'foo',
            (string) $p3->getWarningThreshold()
        );

        $p4 = Perfdata::fromString('test=1;10@');
        $this->assertFalse($p2->getWarningThreshold()->isValid());
        $this->assertFalse($p4->isValid());
        $this->assertSame(
            '10@',
            (string) $p4->getWarningThreshold()
        );
    }

    public function testWhetherInvalidCriticalThresholdInPerfDataHandledCorrectly()
    {
        $p1 = Perfdata::fromString('test=1;;2,0:');
        $this->assertFalse($p1->getCriticalThreshold()->isValid());
        $this->assertFalse($p1->isValid());
        $this->assertSame(
            '2,0:',
            (string) $p1->getCriticalThreshold()
        );

        $p2 = Perfdata::fromString('test=1;;0:4,0');
        $this->assertFalse($p2->getCriticalThreshold()->isValid());
        $this->assertFalse($p2->isValid());
        $this->assertSame(
            '0:4,0',
            (string) $p2->getCriticalThreshold()
        );

        $p3 = Perfdata::fromString('test=1;;foo');
        $this->assertFalse($p2->getCriticalThreshold()->isValid());
        $this->assertFalse($p3->isValid());
        $this->assertSame(
            'foo',
            (string) $p3->getCriticalThreshold()
        );

        $p4 = Perfdata::fromString('test=1;;10@');
        $this->assertFalse($p2->getCriticalThreshold()->isValid());
        $this->assertFalse($p4->isValid());
        $this->assertSame(
            '10@',
            (string) $p4->getCriticalThreshold()
        );
    }
}
