<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Icinga\Application\Hook\AuditHook;
use Icinga\Application\Logger;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Module\Icingadb\Command\IcingaApiCommand;
use Icinga\Module\Icingadb\Command\IcingaCommand;
use Icinga\Module\Icingadb\Command\Renderer\IcingaApiCommandRenderer;
use Icinga\Util\Json;

/**
 * Command transport over Icinga 2's REST API
 */
class ApiCommandTransport implements CommandTransportInterface
{
    /** @var int Used timeout when sending a command */
    protected const SEND_TIMEOUT = 15;

    /**
     * Transport identifier
     */
    public const TRANSPORT = 'api';

    /**
     * API host
     *
     * @var string
     */
    protected $host;

    /**
     * API password
     *
     * @var string
     */
    protected $password;

    /**
     * API port
     *
     * @var int
     */
    protected $port = 5665;

    /**
     * Command renderer
     *
     * @var IcingaApiCommandRenderer
     */
    protected $renderer;

    /**
     * API username
     *
     * @var string
     */
    protected $username;

    /**
     * Create a new API command transport
     */
    public function __construct()
    {
        $this->renderer = new IcingaApiCommandRenderer();
    }

    /**
     * Set the name of the Icinga application object
     *
     * @param   string  $app
     *
     * @return  $this
     */
    public function setApp(string $app): self
    {
        $this->renderer->setApp($app);

        return $this;
    }

    /**
     * Get the API host
     *
     * @return string
     */
    public function getHost(): string
    {
        if ($this->host === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->host;
    }

    /**
     * Set the API host
     *
     * @param   string  $host
     *
     * @return  $this
     */
    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get the API password
     *
     * @return string
     */
    public function getPassword(): string
    {
        if ($this->password === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->password;
    }

    /**
     * Set the API password
     *
     * @param   string  $password
     *
     * @return  $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get the API port
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Set the API port
     *
     * @param   int $port
     *
     * @return  $this
     */
    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get the API username
     *
     * @return string
     */
    public function getUsername(): string
    {
        if ($this->username === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->username;
    }

    /**
     * Set the API username
     *
     * @param   string  $username
     *
     * @return  $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get URI for endpoint
     *
     * @param   string  $endpoint
     *
     * @return  string
     */
    protected function getUriFor(string $endpoint): string
    {
        return sprintf('https://%s:%u/v1/%s', $this->getHost(), $this->getPort(), $endpoint);
    }

    /**
     * Send the given command to the icinga2's REST API
     *
     * @param IcingaApiCommand $command
     *
     * @return mixed
     */
    protected function sendCommand(IcingaApiCommand $command)
    {
        Logger::debug(
            'Sending Icinga command "%s" to the API "%s:%u"',
            $command->getEndpoint(),
            $this->getHost(),
            $this->getPort()
        );

        $data = $command->getData();
        $payload = Json::encode($data);
        AuditHook::logActivity(
            'monitoring/command',
            "Issued command {$command->getEndpoint()} with the following payload: $payload",
            $data
        );

        $headers = ['Accept' => 'application/json'];
        if ($command->getMethod() !== 'POST') {
            $headers['X-HTTP-Method-Override'] = $command->getMethod();
        }

        try {
            $response = (new Client(['timeout' => static::SEND_TIMEOUT]))
                ->post($this->getUriFor($command->getEndpoint()), [
                    'auth'          => [$this->getUsername(), $this->getPassword()],
                    'headers'       => $headers,
                    'json'          => $command->getData(),
                    'http_errors'   => false,
                    'verify'        => false
                ]);
        } catch (GuzzleException $e) {
            if (str_starts_with(ltrim($e->getMessage()), 'cURL error 28:')) {
                throw new ApiCommandException(t(
                    'No response from the Icinga 2 API received after %d seconds.'
                    . ' Please make sure the action has not been performed, before retrying'
                ), static::SEND_TIMEOUT, $e);
            }

            throw (new CommandTransportException(
                'Can\'t connect to the Icinga 2 API: %u %s',
                $e->getCode(),
                $e->getMessage()
            ))->setCommand($command);
        }

        try {
            $responseData = Json::decode((string) $response->getBody(), true);
        } catch (JsonDecodeException $e) {
            throw new CommandTransportException(
                'Got invalid JSON response from the Icinga 2 API: %s',
                $e->getMessage()
            );
        }

        if (! isset($responseData['results']) || empty($responseData['results'])) {
            if (isset($responseData['error'])) {
                throw new ApiCommandException(
                    'Can\'t send external Icinga command: %u %s',
                    $responseData['error'],
                    $responseData['status']
                );
            }

            return;
        }

        $errorResult = $responseData['results'][0];
        if (isset($errorResult['code']) && ($errorResult['code'] < 200 || $errorResult['code'] >= 300)) {
            throw new ApiCommandException(
                'Can\'t send external Icinga command: %u %s',
                $errorResult['code'],
                $errorResult['status']
            );
        }

        return $responseData['results'];
    }

    /**
     * Send the Icinga command over the Icinga 2 API
     *
     * @param IcingaCommand|IcingaApiCommand $command
     * @param int|null $now
     *
     * @throws CommandTransportException
     *
     * @return mixed
     */
    public function send(IcingaCommand|IcingaApiCommand $command, int $now = null)
    {
        if ($command instanceof IcingaCommand) {
            $command = $this->renderer->render($command);
        }

        return $this->sendCommand($command);
    }

    /**
     * Try to connect to the API
     *
     * @return void
     *
     * @throws  CommandTransportException In case the connection was not successful
     */
    public function probe()
    {
        try {
            $response = (new Client(['timeout' => static::SEND_TIMEOUT]))
                ->get($this->getUriFor(''), [
                    'auth'          => [$this->getUsername(), $this->getPassword()],
                    'headers'       => ['Accept' => 'application/json'],
                    'http_errors'   => false,
                    'verify'        => false
                ]);
        } catch (GuzzleException $e) {
            throw new CommandTransportException(
                'Can\'t connect to the Icinga 2 API: %u %s',
                $e->getCode(),
                $e->getMessage()
            );
        }

        try {
            $responseData = Json::decode((string) $response->getBody(), true);
        } catch (JsonDecodeException $e) {
            throw new CommandTransportException(
                'Got invalid JSON response from the Icinga 2 API: %s',
                $e->getMessage()
            );
        }

        if (! isset($responseData['results']) || empty($responseData['results'])) {
            throw new CommandTransportException(
                'Got invalid response from the Icinga 2 API: %s',
                JSON::encode($responseData)
            );
        }

        $result = array_pop($responseData['results']);
        if (! isset($result['user']) || $result['user'] !== $this->getUsername()) {
            throw new CommandTransportException(
                'Got invalid response from the Icinga 2 API: %s',
                JSON::encode($responseData)
            );
        }
    }
}
