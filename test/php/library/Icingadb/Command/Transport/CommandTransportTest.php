<?php

namespace Tests\Icinga\Module\Icingadb\Command\Transport;

use Icinga\Module\Icingadb\Command\Object\AddCommentCommand;
use Icinga\Module\Icingadb\Command\Transport\CommandTransportException;
use Icinga\Module\Icingadb\Model\Host;
use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Icingadb\Lib\FailoverCommandTransport;
use Tests\Icinga\Module\Icingadb\Lib\IntermittentlyFailingCommandTransport;
use Tests\Icinga\Module\Icingadb\Lib\StrikingCommandTransport;

class CommandTransportTest extends TestCase
{
    public function testFatalErrorHandling(): void
    {
        $this->expectException(CommandTransportException::class);
        $this->expectExceptionMessage('endpointA strikes!');

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

    public function testFallbackHandling(): void
    {
        $result = (new FailoverCommandTransport())->send(
            (new AddCommentCommand())
                ->setExpireTime(42)
                ->setAuthor('GLaDOS')
                ->setComment('The cake is a lie')
                ->setObjects(new \ArrayIterator([
                    (new Host())->setProperties(['name' => 'host1']),
                    (new Host())->setProperties(['name' => 'host2']),
                ]))
        );

        $this->assertSame(
            [
                'author' => 'GLaDOS',
                'comment' => 'The cake is a lie',
                'expiry' => 42,
                'hosts' => ['host1', 'host2']
            ],
            $result
        );
    }

    public function testGeneratorsAreNotSupported(): void
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

    public function testChunkedObjectsWithFallbackHandling(): void
    {
        // Multiple chunks

        $result = (new FailoverCommandTransport())->send(
            (new AddCommentCommand())
                ->setExpireTime(42)
                ->setAuthor('GLaDOS')
                ->setComment('The cake is a lie')
                ->setChunkSize(1)
                ->setObjects(new \ArrayIterator([
                    (new Host())->setProperties(['name' => 'host1']),
                    (new Host())->setProperties(['name' => 'host2']),
                ]))
        );

        $this->assertSame(
            [
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host1']
                ],
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host2']
                ]
            ],
            $result
        );

        // A single chunk

        $result = (new FailoverCommandTransport())->send(
            (new AddCommentCommand())
                ->setExpireTime(42)
                ->setAuthor('GLaDOS')
                ->setComment('The cake is a lie')
                ->setChunkSize(4)
                ->setObjects(new \ArrayIterator([
                    (new Host())->setProperties(['name' => 'host1']),
                    (new Host())->setProperties(['name' => 'host2']),
                ]))
        );

        $this->assertSame(
            [
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host1', 'host2']
                ]
            ],
            $result
        );
    }

    public function testIntermittentFailureHandlingDuringChunkedTransmission(): void
    {
        // Fails after the 2nd chunk

        $result = (new IntermittentlyFailingCommandTransport())->send(
            (new AddCommentCommand())
                ->setExpireTime(42)
                ->setAuthor('GLaDOS')
                ->setComment('The cake is a lie')
                ->setChunkSize(1)
                ->setObjects(new \CallbackFilterIterator(new \ArrayIterator([
                    (new Host())->setProperties(['name' => 'host1']),
                    (new Host())->setProperties(['name' => 'host2']),
                    (new Host())->setProperties(['name' => 'host3']),
                    (new Host())->setProperties(['name' => 'host4']),
                    (new Host())->setProperties(['name' => 'host5']),
                    (new Host())->setProperties(['name' => 'host6']),
                ]), fn() => true))
        );

        $this->assertSame(
            [
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host1'],
                    'endpoint' => 'endpointA'
                ],
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host2'],
                    'endpoint' => 'endpointB'
                ],
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host3'],
                    'endpoint' => 'endpointB'
                ],
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host4'],
                    'endpoint' => 'endpointB'
                ],
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host5'],
                    'endpoint' => 'endpointB'
                ],
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host6'],
                    'endpoint' => 'endpointB'
                ]
            ],
            $result
        );

        // Fails after the next-to-last chunk

        IntermittentlyFailingCommandTransport::$failAtAttemptNo = 3;
        IntermittentlyFailingCommandTransport::$attemptNo = 0;

        $result = (new IntermittentlyFailingCommandTransport())->send(
            (new AddCommentCommand())
                ->setExpireTime(42)
                ->setAuthor('GLaDOS')
                ->setComment('The cake is a lie')
                ->setChunkSize(2)
                ->setObjects(new \CallbackFilterIterator(new \ArrayIterator([
                    (new Host())->setProperties(['name' => 'host1']),
                    (new Host())->setProperties(['name' => 'host2']),
                    (new Host())->setProperties(['name' => 'host3']),
                    (new Host())->setProperties(['name' => 'host4']),
                    (new Host())->setProperties(['name' => 'host5']),
                    (new Host())->setProperties(['name' => 'host6']),
                ]), fn() => true))
        );

        $this->assertSame(
            [
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host1', 'host2'],
                    'endpoint' => 'endpointA'
                ],
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host3', 'host4'],
                    'endpoint' => 'endpointA'
                ],
                [
                    'author' => 'GLaDOS',
                    'comment' => 'The cake is a lie',
                    'expiry' => 42,
                    'hosts' => ['host5', 'host6'],
                    'endpoint' => 'endpointB'
                ]
            ],
            $result
        );
    }

    public function testFatalErrorHandlingDuringChunkedTransmission(): void
    {
        $this->expectException(CommandTransportException::class);
        $this->expectExceptionMessage('endpointA strikes!');

        (new StrikingCommandTransport())->send(
            (new AddCommentCommand())
            ->setExpireTime(42)
            ->setAuthor('GLaDOS')
            ->setComment('The cake is a lie')
            ->setChunkSize(1)
            ->setObjects(new \ArrayIterator([
                (new Host())->setProperties(['name' => 'host1']),
                (new Host())->setProperties(['name' => 'host2']),
            ]))
        );
    }
}
