<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Instance;

use Icinga\Module\Icingadb\Command\IcingaCommand;

/**
 * Enable or disable a feature of an Icinga instance
 */
class ToggleInstanceFeatureCommand extends IcingaCommand
{
    /**
     * Feature for enabling or disabling active host checks on an Icinga instance
     */
    const FEATURE_ACTIVE_HOST_CHECKS = 'active_host_checks_enabled';

    /**
     * Feature for enabling or disabling active service checks on an Icinga instance
     */
    const FEATURE_ACTIVE_SERVICE_CHECKS = 'active_service_checks_enabled';

    /**
     * Feature for enabling or disabling host and service event handlers on an Icinga instance
     */
    const FEATURE_EVENT_HANDLERS = 'event_handlers_enabled';

    /**
     * Feature for enabling or disabling host and service flap detection on an Icinga instance
     */
    const FEATURE_FLAP_DETECTION = 'flap_detection_enabled';

    /**
     * Feature for enabling or disabling host and service notifications on an Icinga instance
     */
    const FEATURE_NOTIFICATIONS = 'notifications_enabled';

    /**
     * Feature for enabling or disabling the processing of host and service performance data on an Icinga instance
     */
    const FEATURE_PERFORMANCE_DATA = 'process_performance_data';

    /**
     * Feature that is to be enabled or disabled
     *
     * @var string
     */
    protected $feature;

    /**
     * Whether the feature should be enabled or disabled
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Set the feature that is to be enabled or disabled
     *
     * @param   string $feature
     *
     * @return  $this
     */
    public function setFeature(string $feature): self
    {
        $this->feature = $feature;

        return $this;
    }

    /**
     * Get the feature that is to be enabled or disabled
     *
     * @return string
     */
    public function getFeature(): string
    {
        if ($this->feature === null) {
            throw new \LogicException('You have to set the feature first before getting it.');
        }

        return $this->feature;
    }

    /**
     * Set whether the feature should be enabled or disabled
     *
     * @param   bool $enabled
     *
     * @return  $this
     */
    public function setEnabled(bool $enabled = true): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get whether the feature should be enabled or disabled
     *
     * @return ?bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
}
