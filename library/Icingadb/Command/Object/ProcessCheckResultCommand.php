<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Submit a passive check result for a host or service
 */
class ProcessCheckResultCommand extends ObjectsCommand
{
    /**
     * Host up
     */
    const HOST_UP = 0;

    /**
     * Host down
     */
    const HOST_DOWN = 1;

    /**
     * Service ok
     */
    const SERVICE_OK = 0;

    /**
     * Service warning
     */
    const SERVICE_WARNING = 1;

    /**
     * Service critical
     */
    const SERVICE_CRITICAL = 2;

    /**
     * Service unknown
     */
    const SERVICE_UNKNOWN = 3;

    /**
     * Status code of the host or service check result
     *
     * @var int
     */
    protected $status;

    /**
     * Text output of the host or service check result
     *
     * @var string
     */
    protected $output;

    /**
     * Optional performance data of the host or service check result
     *
     * @var string
     */
    protected $performanceData;

    /**
     * Set the status code of the host or service check result
     *
     * @param   int $status
     *
     * @return  $this
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the status code of the host or service check result
     *
     * @return int
     */
    public function getStatus(): int
    {
        if ($this->status === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->status;
    }

    /**
     * Set the text output of the host or service check result
     *
     * @param   string $output
     *
     * @return  $this
     */
    public function setOutput(string $output): self
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Get the text output of the host or service check result
     *
     * @return ?string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Set the performance data of the host or service check result
     *
     * @param   string|null $performanceData
     *
     * @return  $this
     */
    public function setPerformanceData(string $performanceData = null): self
    {
        $this->performanceData = $performanceData;

        return $this;
    }

    /**
     * Get the performance data of the host or service check result
     *
     * @return ?string
     */
    public function getPerformanceData()
    {
        return $this->performanceData;
    }
}
