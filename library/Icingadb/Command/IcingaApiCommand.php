<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command;

class IcingaApiCommand
{
    /**
     * Command data
     *
     * @var array
     */
    protected $data;

    /**
     * Name of the endpoint
     *
     * @var string
     */
    protected $endpoint;

    /**
     * HTTP method to use
     *
     * @var string
     */
    protected $method = 'POST';

    /**
     * Create a new Icinga 2 API command
     *
     * @param   string  $endpoint
     * @param   array   $data
     *
     * @return  static
     */
    public static function create(string $endpoint, array $data): self
    {
        return (new static())
            ->setEndpoint($endpoint)
            ->setData($data);
    }

    /**
     * Get the command data
     *
     * @return array
     */
    public function getData(): array
    {
        if ($this->data === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->data;
    }

    /**
     * Set the command data
     *
     * @param   array   $data
     *
     * @return  $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the name of the endpoint
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        if ($this->endpoint === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->endpoint;
    }

    /**
     * Set the name of the endpoint
     *
     * @param   string  $endpoint
     *
     * @return  $this
     */
    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Get the HTTP method to use
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Set the HTTP method to use
     *
     * @param string $method All uppercase HTTP method name. Case-sensitive.
     *
     * @return $this
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }
}
