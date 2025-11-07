<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Transport;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Icingadb\Command\IcingaCommand;
use Icinga\Module\Icingadb\Command\Object\ObjectsCommand;

/**
 * Command transport
 */
class CommandTransport implements CommandTransportInterface
{
    /**
     * Transport configuration
     *
     * @var Config
     */
    protected static $config;

    /**
     * Get transport configuration
     *
     * @return  Config
     *
     * @throws  ConfigurationError
     */
    public static function getConfig(): Config
    {
        if (static::$config === null) {
            $config = Config::module('icingadb', 'commandtransports');
            if ($config->isEmpty()) {
                throw new ConfigurationError(
                    t('No command transports have been configured in "%s".'),
                    $config->getConfigFile()
                );
            }

            static::$config = $config;
        }

        return static::$config;
    }

    /**
     * Create a transport from config
     *
     * @param   ConfigObject<string>  $config
     *
     * @return  ApiCommandTransport
     *
     * @throws  ConfigurationError
     */
    public static function createTransport(ConfigObject $config): ApiCommandTransport
    {
        $config = clone $config;
        switch (strtolower($config->transport ?? '')) {
            case ApiCommandTransport::TRANSPORT:
                $transport = new ApiCommandTransport();
                break;
            default:
                throw new ConfigurationError(
                    t('Cannot create command transport "%s". Invalid transport defined in "%s". Use one of: %s.'),
                    $config->transport,
                    static::getConfig()->getConfigFile(),
                    join(', ', [ApiCommandTransport::TRANSPORT])
                );
        }

        unset($config->transport);
        foreach ($config as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (! method_exists($transport, $method)) {
                // Ignore settings from config that don't have a setter on the transport instead of throwing an
                // exception here because the transport should throw an exception if it's not fully set up
                // when being about to send a command
                continue;
            }

            $transport->$method($value);
        }

        return $transport;
    }

    /**
     * Send the given command over an appropriate Icinga command transport
     *
     * This will try one configured transport after another until the command has been successfully sent.
     *
     * @param   IcingaCommand   $command    The command to send
     * @param   int|null        $now        Timestamp of the command or null for now
     *
     * @throws  CommandTransportException   If sending the Icinga command failed
     *
     * @return  mixed
     */
    public function send(IcingaCommand $command, int $now = null)
    {
        $errors = [];
        $results = [];
        $retryCommand = null;

        foreach (static::getConfig() as $name => $transportConfig) {
            $transport = static::createTransport($transportConfig);

            if ($command instanceof ObjectsCommand && $command->getChunkSize() > 0) {
                $objects = $command->getObjects();

                if ($retryCommand !== null) {
                    try {
                        $results[] = $transport->send($retryCommand, $now);
                    } catch (CommandTransportException) {
                        // It failed prior, so no need to log it again
                        continue;
                    }

                    $retryCommand = null;
                } else {
                    if ($objects->key() === null) {
                        // We traverse the iterator manually here, so we have to rewind it before the first iteration.
                        // That should be the case if the current key is null. May fail if an iterator explicitly yields
                        // null as the key, but I want to see a justified use case for that…
                        $objects->rewind();
                    }
                }

                while ($objects->valid()) {
                    $batchCommand = clone $command;
                    $batchCommand->setObjects(
                        new \LimitIterator(new \NoRewindIterator($objects), 0, $command->getChunkSize())
                    );

                    try {
                        $results[] = $transport->send($batchCommand, $now);
                    } catch (CommandTransportException $e) {
                        Logger::error($e);
                        $errors[] = sprintf('%s: %s.', $name, rtrim($e->getMessage(), '.'));

                        $retryCommand = $e->getCommand();
                        if ($retryCommand !== null) {
                            continue 2;
                        } else {
                            // Non-recoverable error, so stop trying to send further commands
                            break 2;
                        }
                    }
                }

                return $results;
            } elseif ($retryCommand !== null) {
                try {
                    $result = $transport->send($retryCommand, $now);
                } catch (CommandTransportException) {
                    // It failed prior, so no need to log it again
                    continue;
                }

                return $result;
            } else {
                try {
                    $result = $transport->send($command, $now);
                } catch (CommandTransportException $e) {
                    Logger::error($e);
                    $errors[] = sprintf('%s: %s.', $name, rtrim($e->getMessage(), '.'));

                    $retryCommand = $e->getCommand();
                    if ($retryCommand !== null) {
                        continue; // Try the next transport
                    } else {
                        break;
                    }
                }

                return $result; // The command was successfully sent
            }
        }

        if (! empty($errors)) {
            throw new CommandTransportException(implode("\n", $errors));
        }

        throw new CommandTransportException(t(
            'Failed to send external Icinga command. No transport has been configured'
            . ' for this instance. Please contact your Icinga Web administrator.'
        ));
    }
}
