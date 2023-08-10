<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Enable or disable a feature of an Icinga object, i.e. host or service
 */
class ToggleObjectFeatureCommand extends ObjectsCommand
{
    /**
     * Feature for enabling or disabling active checks of a host or service
     */
    const FEATURE_ACTIVE_CHECKS = 'active_checks_enabled';

    /**
     * Feature for enabling or disabling passive checks of a host or service
     */
    const FEATURE_PASSIVE_CHECKS = 'passive_checks_enabled';

    /**
     * Feature for enabling or disabling notifications for a host or service
     *
     * Notifications will be sent out only if notifications are enabled on a program-wide basis as well.
     */
    const FEATURE_NOTIFICATIONS = 'notifications_enabled';

    /**
     * Feature for enabling or disabling event handler for a host or service
     */
    const FEATURE_EVENT_HANDLER = 'event_handler_enabled';

    /**
     * Feature for enabling or disabling flap detection for a host or service.
     *
     * In order to enable flap detection flap detection must be enabled on a program-wide basis as well.
     */
    const FEATURE_FLAP_DETECTION = 'flapping_enabled';

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
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
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
