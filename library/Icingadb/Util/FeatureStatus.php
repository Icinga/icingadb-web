<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Util;

use ArrayObject;
use Icinga\Module\Icingadb\Command\Object\ToggleObjectFeatureCommand;

class FeatureStatus extends ArrayObject
{
    public function __construct(string $type, $summary)
    {
        $prefix = "{$type}s";

        $featureStatus = [
            ToggleObjectFeatureCommand::FEATURE_ACTIVE_CHECKS =>
                $this->getFeatureStatus('active_checks_enabled', $prefix, $summary),
            ToggleObjectFeatureCommand::FEATURE_PASSIVE_CHECKS =>
                $this->getFeatureStatus('passive_checks_enabled', $prefix, $summary),
            ToggleObjectFeatureCommand::FEATURE_NOTIFICATIONS =>
                $this->getFeatureStatus('notifications_enabled', $prefix, $summary),
            ToggleObjectFeatureCommand::FEATURE_EVENT_HANDLER =>
                $this->getFeatureStatus('event_handler_enabled', $prefix, $summary),
            ToggleObjectFeatureCommand::FEATURE_FLAP_DETECTION =>
                $this->getFeatureStatus('flapping_enabled', $prefix, $summary)
        ];

        parent::__construct($featureStatus, ArrayObject::ARRAY_AS_PROPS);
    }

    protected function getFeatureStatus(string $feature, string $prefix, $summary): int
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
