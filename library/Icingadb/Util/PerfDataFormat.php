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

    protected static $wattPrefix = ['W', 'kW', 'MW', 'GW', 'TW', 'PW', 'EW', 'ZW', 'YW'];

    protected static $amperePrefix = ['A', 'kA', 'MA', 'GA', 'TA', 'PA', 'EA', 'ZA', 'YA'];

    protected static $ampSecondPrefix = ['As', 'kAs', 'MAs', 'GAs', 'TAs', 'PAs', 'EAs', 'ZAs', 'YAs'];

    protected static $voltPrefix = ['V', 'kV', 'MV', 'GV', 'TV', 'PV', 'EV', 'ZV', 'YV'];

    protected static $ohmPrefix = ['O', 'kO', 'MO', 'GO', 'TO', 'PO', 'EO', 'ZO', 'YO'];

    protected static $gramPrefix = ['g', 'kg', 't'];

    protected static $literPrefix = ['l', 'hl'];
    protected static $literBase = 100;

    protected static $secondPrefix = ['s'];

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
        return self::formatForUnits($value, self::$literPrefix, self::$literBase);
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
            $units[abs($pow)]
        );
    }
}
