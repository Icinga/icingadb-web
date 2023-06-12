<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting\Common;

use InvalidArgumentException;

trait SlaTimelines
{
    /** @var array Set of sla timelines */
    protected $timelines = [];

    /**
     * Get all timelines of all available hosts/services
     *
     * @return array
     */
    public function getAllTimelines(): array
    {
        return $this->timelines;
    }

    /**
     * Get sla timelines for the given hosts/service name, if any
     *
     * @param string $key
     *
     * @return array
     */
    public function getTimelines(string $key): array
    {
        if (! $this->hasTimelines($key)) {
            throw new InvalidArgumentException(sprintf('No timeline found for "%s"', $key));
        }

        return $this->timelines[$key];
    }

    /**
     * Add a timeline for the given host/service name
     *
     * @param string $key
     * @param SlaTimeline $timeline
     *
     * @return $this
     */
    public function addTimeline(string $key, SlaTimeline $timeline): self
    {
        $this->timelines[$key][] = $timeline;

        return $this;
    }

    /**
     * Override all timelines of the given host/service by the specified timeline
     *
     * @param string $key
     * @param SlaTimeline $timeline
     *
     * @return $this
     */
    public function setTimeline(string $key, SlaTimeline $timeline): self
    {
        $this->timelines[$key] = [];
        $this->addTimeline($key, $timeline);

        return $this;
    }

    /**
     * Get whether the given host/service has any timelines
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasTimelines(string $key): bool
    {
        return ! empty($this->getAllTimelines()) && isset($this->timelines[$key]);
    }

    public function getTimelineString(): string
    {
        $string = '';
        foreach ($this->getAllTimelines() as $name => $timelines) {
            $index = 1;
            $string .= 'Name: ' . $name;
            foreach ($timelines as $timeline) {
                $string .= ' Timeline: ' . $index++ . PHP_EOL . $timeline;
            }

            $string .= PHP_EOL;
        }

        return $string;
    }
}
