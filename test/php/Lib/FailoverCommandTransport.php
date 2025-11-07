<?php

namespace Tests\Icinga\Module\Icingadb\Lib;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Module\Icingadb\Command\IcingaApiCommand;
use Icinga\Module\Icingadb\Command\Transport\ApiCommandTransport;
use Icinga\Module\Icingadb\Command\Transport\CommandTransport;
use Icinga\Module\Icingadb\Command\Transport\CommandTransportException;

class FailoverCommandTransport extends CommandTransport
{
    public static function getConfig(): Config
    {
        return Config::fromArray(['endpoint1' => ['host' => 'endpointA'], 'endpoint2' => ['host' => 'endpointB']]);
    }

    public static function createTransport(ConfigObject $config): ApiCommandTransport
    {
        return (new class extends ApiCommandTransport {
            protected function sendCommand(IcingaApiCommand $command)
            {
                if ($this->getHost() === 'endpointA') {
                    throw (new CommandTransportException(sprintf('%s fails!', $this->getHost())))
                        ->setCommand($command);
                }

                return $command->getData();
            }
        })->setHost($config->host);
    }
}
