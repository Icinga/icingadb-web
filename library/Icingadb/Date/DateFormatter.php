<?php

namespace Icinga\Module\Icingadb\Date;

class DateFormatter
{
    /**
     * Format a duration
     *
     * @param int|float $seconds Duration in seconds
     * @param bool      $short
     *
     * @return string
     */
    public static function formatDuration($seconds, $short = false)
    {
        if ($short && $seconds > 3600) {
            $seconds = $seconds + (3600 - ($seconds % 3600));
        }

        $minutes = floor((float) $seconds / 60);
        if ($minutes < 60) {
            $formatted = sprintf('%dm %ds', $minutes, $seconds % 60);
        } else {
            $hours = floor($minutes / 60);
            if ($hours < 24) {
                $formatted = sprintf('%dh %dm', $hours, $minutes % 60);
            } else {
                $formatted = sprintf('%dd %dh', floor($hours / 24), $hours % 24);
            }
        }

        if ($short) {
            return explode(' ', $formatted, 2)[0];
        }

        return $formatted;
    }
}
