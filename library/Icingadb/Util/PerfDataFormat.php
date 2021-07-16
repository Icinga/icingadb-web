<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Util;

class PerfDataFormat
{
    protected static $instance;

    protected static $generalBase = 1000;

    protected static $bitPrefix = ['b', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'];

    protected static $bytePrefix = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

    protected static $wattHourPrefix = ['Wh', 'kWh', 'MWh', 'GWh', 'TWh', 'PWh', 'EWh', 'ZWh', 'YWh'];

    protected static $wattPrefix = [-1 => 'mW', 'W', 'kW', 'MW', 'GW'];

    protected static $amperePrefix = [-3 => 'nA', -2 => 'µA', -1 => 'mA', 'A', 'kA', 'MA', 'GA'];

    protected static $ampSecondPrefix = [-2 => 'µAs', -1 => 'mAs', 'As', 'kAs', 'MAs', 'GAs'];

    protected static $voltPrefix = [-2 => 'µV', -1 => 'mV', 'V', 'kV', 'MV', 'GV'];

    protected static $ohmPrefix = ['Ω'];

    protected static $gramPrefix = [
        -5 => 'fg',
        -4 => 'pg',
        -3 => 'ng',
        -2 => 'µg',
        -1 => 'mg',
        'g',
        'kg',
        't',
        'ktǂ',
        'Mt',
        'Gt'
    ];

    protected static $literPrefix = [
        -5 => 'fl',
        -4 => 'pl',
        -3 => 'nl',
        -2 => 'µl',
        -1 => 'ml',
        'l',
        'kl',
        'Ml',
        'Gl',
        'Tl',
        'Pl'
    ];

    protected static $secondPrefix = [-3 => 'ns', -2 => 'µs', -1 => 'ms', 's'];

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new PerfDataFormat();
        }

        return self::$instance;
    }

    public static function bits($value)
    {
        return self::formatForUnits($value, self::$bitPrefix, self::$generalBase);
    }

    public static function bytes($value)
    {
        return self::formatForUnits($value, self::$bytePrefix, self::$generalBase);
    }

    public static function wattHours($value)
    {
        return self::formatForUnits($value, self::$wattHourPrefix, self::$generalBase);
    }

    public static function watts($value)
    {
        return self::formatForUnits($value, self::$wattPrefix, self::$generalBase);
    }

    public static function amperes($value)
    {
        return self::formatForUnits($value, self::$amperePrefix, self::$generalBase);
    }

    public static function ampereSeconds($value)
    {
        return self::formatForUnits($value, self::$ampSecondPrefix, self::$generalBase);
    }

    public static function volts($value)
    {
        return self::formatForUnits($value, self::$voltPrefix, self::$generalBase);
    }

    public static function ohms($value)
    {
        return self::formatForUnits($value, self::$ohmPrefix, self::$generalBase);
    }

    public static function grams($value)
    {
        return self::formatForUnits($value, self::$gramPrefix, self::$generalBase);
    }

    public static function liters($value)
    {
        return self::formatForUnits($value, self::$literPrefix, self::$generalBase);
    }

    public static function seconds($value)
    {
        $absValue = abs($value);

        if ($absValue < 60) {
            return self::formatForUnits($value, self::$secondPrefix, self::$generalBase);
        } elseif ($absValue < 3600) {
            return sprintf('%0.2f m', $value / 60);
        } elseif ($absValue < 86400) {
            return sprintf('%0.2f h', $value / 3600);
        }

        return sprintf('%0.2f d', $value / 86400);
    }

    protected static function formatForUnits($value, &$units, $base)
    {
        $sign = '';
        if ($value < 0) {
            $value = abs($value);
            $sign = '-';
        }

        if ($value == 0) {
            $pow = $result = 0;
        } else {
            $pow = floor(log($value, $base));

            // Identify nearest unit if unknown
            while (! isset($units[$pow])) {
                if ($pow < 0) {
                    $pow++;
                } else {
                    $pow--;
                }
            }

            $result = $value / pow($base, $pow);
        }

        // 1034.23 looks better than 1.03, but 2.03 is fine:
        if ($pow > 0 && $result < 2) {
            $result = $value / pow($base, --$pow);
        }

        return sprintf(
            '%s%0.2f %s',
            $sign,
            $result,
            $units[$pow]
        );
    }
}
