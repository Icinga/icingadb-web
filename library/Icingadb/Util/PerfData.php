<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Util;

use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Web\Widget\Chart\InlinePie;
use InvalidArgumentException;
use LogicException;

class PerfData
{
    const PERFDATA_OK = 'ok';
    const PERFDATA_WARNING = 'warning';
    const PERFDATA_CRITICAL = 'critical';

    /**
     * The performance data value being parsed
     *
     * @var string
     */
    protected $perfdataValue;

    /**
     * Unit of measurement (UOM)
     *
     * @var string
     */
    protected $unit;

    /**
     * The label
     *
     * @var string
     */
    protected $label;

    /**
     * The value
     *
     * @var float
     */
    protected $value;

    /**
     * The minimum value
     *
     * @var float
     */
    protected $minValue;

    /**
     * The maximum value
     *
     * @var float
     */
    protected $maxValue;

    /**
     * The WARNING threshold
     *
     * @var ThresholdRange
     */
    protected $warningThreshold;

    /**
     * The CRITICAL threshold
     *
     * @var ThresholdRange
     */
    protected $criticalThreshold;

    /**
     * Create a new PerfData object based on the given performance data label and value
     *
     * @param   string      $label      The perfdata label
     * @param   string      $value      The perfdata value
     */
    public function __construct(string $label, string $value)
    {
        $this->perfdataValue = $value;
        $this->label = $label;
        $this->parse();

        if ($this->unit === '%') {
            if ($this->minValue === null) {
                $this->minValue = 0.0;
            }
            if ($this->maxValue === null) {
                $this->maxValue = 100.0;
            }
        }

        $warn = $this->warningThreshold->getMax();
        if ($warn !== null) {
            $crit = $this->criticalThreshold->getMax();
            if ($crit !== null && $warn > $crit) {
                $this->warningThreshold->setInverted();
                $this->criticalThreshold->setInverted();
            }
        }
    }

    /**
     * Return a new PerfData object based on the given performance data key=value pair
     *
     * @param   string      $perfdata       The key=value pair to parse
     *
     * @return  PerfData
     *
     * @throws  InvalidArgumentException    In case the given performance data has no content or a invalid format
     */
    public static function fromString(string $perfdata): self
    {
        if (empty($perfdata)) {
            throw new InvalidArgumentException('PerfData::fromString expects a string with content');
        } elseif (strpos($perfdata, '=') === false) {
            throw new InvalidArgumentException(
                'PerfData::fromString expects a key=value formatted string. Got "' . $perfdata . '" instead'
            );
        }

        list($label, $value) = explode('=', $perfdata, 2);
        return new static(trim($label), trim($value));
    }

    /**
     * Return whether this performance data's value is a number
     *
     * @return  bool    True in case it's a number, otherwise False
     */
    public function isNumber(): bool
    {
        return $this->unit === null;
    }

    /**
     * Return whether this performance data's value are seconds
     *
     * @return  bool    True in case it's seconds, otherwise False
     */
    public function isSeconds(): bool
    {
        return $this->unit === 's';
    }

    /**
     * Return whether this performance data's value is a temperature
     *
     * @return  bool    True in case it's temperature, otherwise False
     */
    public function isTemperature(): bool
    {
        return in_array($this->unit, array('C', 'F', 'K'));
    }

    /**
     * Return whether this performance data's value is in percentage
     *
     * @return  bool    True in case it's in percentage, otherwise False
     */
    public function isPercentage(): bool
    {
        return $this->unit === '%';
    }

    /**
     * Get whether this perf data's value is in packets
     *
     * @return bool   True in case it's in packets
     */
    public function isPackets(): bool
    {
        return $this->unit === 'packets';
    }

    /**
     * Get whether this perf data's value is in lumen
     *
     * @return bool
     */
    public function isLumens(): bool
    {
        return $this->unit === 'lm';
    }

    /**
     * Get whether this perf data's value is in decibel-milliwatts
     *
     * @return bool
     */
    public function isDecibelMilliWatts(): bool
    {
        return $this->unit === 'dBm';
    }

    /**
     * Get whether this data's value is in bits
     *
     * @return bool
     */
    public function isBits(): bool
    {
        return $this->unit === 'b';
    }

    /**
     * Return whether this performance data's value is in bytes
     *
     * @return  bool    True in case it's in bytes, otherwise False
     */
    public function isBytes(): bool
    {
        return $this->unit === 'B';
    }

    /**
     * Get whether this data's value is in watt hours
     *
     * @return bool
     */
    public function isWattHours(): bool
    {
        return $this->unit === 'Wh';
    }

    /**
     * Get whether this data's value is in watt
     *
     * @return bool
     */
    public function isWatts(): bool
    {
        return $this->unit === 'W';
    }

    /**
     * Get whether this data's value is in ampere
     *
     * @return bool
     */
    public function isAmperes(): bool
    {
        return $this->unit === 'A';
    }

    /**
     * Get whether this data's value is in ampere seconds
     *
     * @return bool
     */
    public function isAmpSeconds(): bool
    {
        return $this->unit === 'As';
    }

    /**
     * Get whether this data's value is in volts
     *
     * @return bool
     */
    public function isVolts(): bool
    {
        return $this->unit === 'V';
    }

    /**
     * Get whether this data's value is in ohm
     *
     * @return bool
     */
    public function isOhms(): bool
    {
        return $this->unit === 'O';
    }

    /**
     * Get whether this data's value is in grams
     *
     * @return bool
     */
    public function isGrams(): bool
    {
        return $this->unit === 'g';
    }

    /**
     * Get whether this data's value is in Litters
     *
     * @return bool
     */
    public function isLiters(): bool
    {
        return $this->unit === 'l';
    }

    /**
     * Return whether this performance data's value is a counter
     *
     * @return  bool    True in case it's a counter, otherwise False
     */
    public function isCounter(): bool
    {
        return $this->unit === 'c';
    }

    /**
     * Returns whether it is possible to display a visual representation
     *
     * @return  bool    True when the perfdata is visualizable
     */
    public function isVisualizable(): bool
    {
        return isset($this->minValue) && isset($this->maxValue) && isset($this->value);
    }

    /**
     * Return this perfomance data's label
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Return the value or null if it is unknown (U)
     *
     * @return  null|float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Return the unit as a string
     *
     * @return ?string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Return the value as percentage (0-100)
     *
     * @return  null|float
     */
    public function getPercentage()
    {
        if ($this->isPercentage()) {
            return $this->value;
        }

        if ($this->maxValue !== null) {
            $minValue = $this->minValue !== null ? $this->minValue : 0.0;
            if ($this->maxValue == $minValue) {
                return null;
            }

            if ($this->value > $minValue) {
                return (($this->value - $minValue) / ($this->maxValue - $minValue)) * 100;
            }
        }
    }

    /**
     * Return this performance data's warning treshold
     *
     * @return  ThresholdRange
     */
    public function getWarningThreshold(): ThresholdRange
    {
        return $this->warningThreshold;
    }

    /**
     * Return this performance data's critical treshold
     *
     * @return  ThresholdRange
     */
    public function getCriticalThreshold(): ThresholdRange
    {
        return $this->criticalThreshold;
    }

    /**
     * Return the minimum value or null if it is not available
     *
     * @return ?float
     */
    public function getMinimumValue()
    {
        return $this->minValue;
    }

    /**
     * Return the maximum value or null if it is not available
     *
     * @return  null|float
     */
    public function getMaximumValue()
    {
        return $this->maxValue;
    }

    /**
     * Return this performance data as string
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->formatLabel();
    }

    /**
     * Parse the current performance data value
     *
     * @todo    Handle optional min/max if UOM == %
     */
    protected function parse()
    {
        $parts = explode(';', $this->perfdataValue);

        $matches = array();
        if (preg_match('@^(-?\d+(\.\d+)?)([a-zA-Z%°]{1,3})$@u', $parts[0], $matches)) {
            $this->unit = $matches[3];
            $this->value = $matches[1];
        } else {
            $this->value = $parts[0];
        }

        switch (count($parts)) {
            /* @noinspection PhpMissingBreakStatementInspection */
            case 5:
                if ($parts[4] !== '') {
                    $this->maxValue = $parts[4];
                }
            /* @noinspection PhpMissingBreakStatementInspection */
            case 4:
                if ($parts[3] !== '') {
                    $this->minValue = $parts[3];
                }
            /* @noinspection PhpMissingBreakStatementInspection */
            case 3:
                $this->criticalThreshold = ThresholdRange::fromString(trim($parts[2]));
            // Fallthrough
            case 2:
                $this->warningThreshold = ThresholdRange::fromString(trim($parts[1]));
        }

        if ($this->warningThreshold === null) {
            $this->warningThreshold = new ThresholdRange();
        }
        if ($this->criticalThreshold === null) {
            $this->criticalThreshold = new ThresholdRange();
        }
    }

    protected function calculatePieChartData(): array
    {
        $rawValue = $this->getValue();
        $minValue = $this->getMinimumValue() !== null ? $this->getMinimumValue() : 0;
        $usedValue = ($rawValue - $minValue);

        $green = $orange = $red = 0;

        if ($this->criticalThreshold->contains($rawValue)) {
            if ($this->warningThreshold->contains($rawValue)) {
                $green = $usedValue;
            } else {
                $orange = $usedValue;
            }
        } else {
            $red = $usedValue;
        }

        return array($green, $orange, $red, ($this->getMaximumValue() - $minValue) - $usedValue);
    }


    public function asInlinePie(): InlinePie
    {
        if (! $this->isVisualizable()) {
            throw new LogicException('Cannot calculate piechart data for unvisualizable perfdata entry.');
        }

        $data = $this->calculatePieChartData();
        $pieChart = new InlinePie($data, $this);
        $pieChart->setColors(array('#44bb77', '#ffaa44', '#ff5566', '#ddccdd'));

        return $pieChart;
    }

    /**
     * Format the given value depending on the currently used unit
     */
    protected function format($value)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ThresholdRange) {
            if ($value->getMin()) {
                return (string) $value;
            }

            $max = $value->getMax();
            return $max === null ? '' : $this->format($max);
        }

        switch (true) {
            case $this->isPercentage():
                return (string) $value . '%';
            case $this->isPackets():
                return (string) $value . 'packets';
            case $this->isLumens():
                return (string) $value . 'lm';
            case $this->isDecibelMilliWatts():
                return (string) $value . 'dBm';
            case $this->isCounter():
                return (string) $value . 'c';
            case $this->isTemperature():
                return (string) $value . $this->unit;
            case $this->isBits():
                return PerfDataFormat::bits($value);
            case $this->isBytes():
                return PerfDataFormat::bytes($value);
            case $this->isSeconds():
                return PerfDataFormat::seconds($value);
            case $this->isWatts():
                return PerfDataFormat::watts($value);
            case $this->isWattHours():
                return PerfDataFormat::wattHours($value);
            case $this->isAmperes():
                return PerfDataFormat::amperes($value);
            case $this->isAmpSeconds():
                return PerfDataFormat::ampereSeconds($value);
            case $this->isVolts():
                return PerfDataFormat::volts($value);
            case $this->isOhms():
                return PerfDataFormat::ohms($value);
            case $this->isGrams():
                return PerfDataFormat::grams($value);
            case $this->isLiters():
                return PerfDataFormat::liters($value);
            default:
                return number_format($value, 2);
        }
    }

    /**
     * Format the title string that represents this perfdata set
     *
     * @param bool $html
     *
     * @return string
     */
    public function formatLabel(bool $html = false): string
    {
        return sprintf(
            $html ? '<b>%s %s</b> (%s%%)' : '%s %s (%s%%)',
            htmlspecialchars($this->getLabel()),
            $this->format($this->value),
            number_format($this->getPercentage(), 2)
        );
    }

    public function toArray(): array
    {
        return array(
            'label' => $this->getLabel(),
            'value' => $this->format($this->getvalue()),
            'min' => isset($this->minValue) && !$this->isPercentage()
                ? $this->format($this->minValue)
                : '',
            'max' => isset($this->maxValue) && !$this->isPercentage()
                ? $this->format($this->maxValue)
                : '',
            'warn' => $this->format($this->warningThreshold),
            'crit' => $this->format($this->criticalThreshold)
        );
    }

    /**
     * Return the state indicated by this perfdata
     *
     * @return int
     */
    public function getState(): int
    {
        if ($this->value === null) {
            return ServiceStates::UNKNOWN;
        }

        if (! $this->criticalThreshold->contains($this->value)) {
            return ServiceStates::CRITICAL;
        }

        if (! $this->warningThreshold->contains($this->value)) {
            return ServiceStates::WARNING;
        }

        return ServiceStates::OK;
    }

    /**
     * Return whether the state indicated by this perfdata is worse than
     * the state indicated by the other perfdata
     * CRITICAL > UNKNOWN > WARNING > OK
     *
     * @param PerfData $rhs     the other perfdata
     *
     * @return bool
     */
    public function worseThan(PerfData $rhs): bool
    {
        if (($state = $this->getState()) === ($rhsState = $rhs->getState())) {
            return $this->getPercentage() > $rhs->getPercentage();
        }

        if ($state === ServiceStates::CRITICAL) {
            return true;
        }

        if ($state === ServiceStates::UNKNOWN) {
            return $rhsState !== ServiceStates::CRITICAL;
        }

        if ($state === ServiceStates::WARNING) {
            return $rhsState === ServiceStates::OK;
        }

        return false;
    }
}
