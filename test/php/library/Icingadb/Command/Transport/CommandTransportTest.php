<?php

namespace Tests\Icinga\Module\Icingadb\Command\Transport;

use Icinga\Module\Icingadb\Command\Object\AddCommentCommand;
use Icinga\Module\Icingadb\Command\Transport\CommandTransportException;
use Icinga\Module\Icingadb\Model\Host;
use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Icingadb\Lib\StrikingCommandTransport;

class CommandTransportTest extends TestCase
{
    public function testFallbackHandling()
    {
        $this->expectException(CommandTransportException::class);
        $this->expectExceptionMessage('endpointB strikes!');

        (new StrikingCommandTransport())->send(
            (new AddCommentCommand())
                ->setExpireTime(42)
                ->setAuthor('GLaDOS')
                ->setComment('The cake is a lie')
                ->setObjects(new \CallbackFilterIterator(new \ArrayIterator([
                    (new Host())->setProperties(['name' => 'host1']),
                    (new Host())->setProperties(['name' => 'host2']),
                ]), function ($host) {
                    return $host->name === 'host2';
                }))
        );
    }

    public function testGeneratorsAreNotSupported()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Generators are not supported');

        (new StrikingCommandTransport())->send(
            (new AddCommentCommand())
                ->setExpireTime(42)
                ->setAuthor('GLaDOS')
                ->setComment('The cake is a lie')
                ->setObjects((function () {
                    yield (new Host())->setProperties(['name' => 'host1']);
                    yield (new Host())->setProperties(['name' => 'host2']);
                })())
        );
    }
}
