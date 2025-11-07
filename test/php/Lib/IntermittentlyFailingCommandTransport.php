<?php

namespace Tests\Icinga\Module\Icingadb\Lib;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Module\Icingadb\Command\IcingaApiCommand;
use Icinga\Module\Icingadb\Command\Transport\ApiCommandTransport;
use Icinga\Module\Icingadb\Command\Transport\CommandTransport;
use Icinga\Module\Icingadb\Command\Transport\CommandTransportException;

class IntermittentlyFailingCommandTransport extends CommandTransport
{
    public static $failAtAttemptNo = 2;

    public static $attemptNo = 0;

    public static function getConfig(): Config
    {
        return Config::fromArray(['endpoint1' => ['host' => 'endpointA'], 'endpoint2' => ['host' => 'endpointB']]);
    }

    public static function createTransport(ConfigObject $config): ApiCommandTransport
    {
        return (new class extends ApiCommandTransport {
            protected function sendCommand(IcingaApiCommand $command)
            {
                $attemptNo = ++IntermittentlyFailingCommandTransport::$attemptNo;
                $failAtAttemptNo = IntermittentlyFailingCommandTransport::$failAtAttemptNo;

                if ($attemptNo === $failAtAttemptNo) {
                    throw (new CommandTransportException(sprintf('%s intermittently fails!', $this->getHost())))
                        ->setCommand($command);
                }

                return $command->getData() + ['endpoint' => $this->getHost()];
            }
        })->setHost($config->host);
    }
}
