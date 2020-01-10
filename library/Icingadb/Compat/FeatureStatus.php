<?php

namespace Icinga\Module\Icingadb\Compat;

use ArrayObject;

class FeatureStatus extends ArrayObject
{
    public function __construct($type, $summary)
    {
        $prefix = "{$type}s";

        $featureStatus = [
            'active_checks_enabled'  => $this->getFeatureStatus('active_checks_enabled', $prefix, $summary),
            'passive_checks_enabled' => $this->getFeatureStatus('passive_checks_enabled', $prefix, $summary),
            'notifications_enabled'  => $this->getFeatureStatus('notifications_enabled', $prefix, $summary),
            'event_handler_enabled'  => $this->getFeatureStatus('event_handler_enabled', $prefix, $summary),
            'flap_detection_enabled' => $this->getFeatureStatus('flapping_enabled', $prefix, $summary)
        ];

        parent::__construct($featureStatus, ArrayObject::ARRAY_AS_PROPS);
    }

    protected function getFeatureStatus($feature, $prefix, $summary)
    {
        $key = "{$prefix}_{$feature}";
        $value = (int) $summary->$key;

        if ($value === 0) {
            return 0;
        }

        $totalKey = "{$prefix}_total";
        $total = (int) $summary->$totalKey;

        if ($value === $total) {
            return 1;
        }

        return 2;
    }
}
