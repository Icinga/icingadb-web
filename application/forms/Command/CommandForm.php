<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command;

use ArrayIterator;
use Exception;
use Generator;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Application\Web;
use Icinga\Module\Icingadb\Command\IcingaCommand;
use Icinga\Module\Icingadb\Command\Transport\CommandTransport;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use ipl\Html\Form;
use ipl\Orm\Model;
use ipl\Web\Common\CsrfCounterMeasure;
use Traversable;

abstract class CommandForm extends Form
{
    use Auth;
    use CsrfCounterMeasure;

    protected $defaultAttributes = ['class' => 'icinga-form icinga-controls'];

    /** @var mixed */
    protected $objects;

    /**
     * Whether an error occurred while sending the command
     *
     * Prevents the success message from being rendered simultaneously
     *
     * @var bool
     */
    protected $errorOccurred = false;

    /**
     * Set the objects to issue the command for
     *
     * @param mixed $objects A traversable that is also countable
     *
     * @return $this
     */
    public function setObjects($objects): self
    {
        $this->objects = $objects;

        return $this;
    }

    /**
     * Get the objects to issue the command for
     *
     * @return mixed
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * Create and add form elements representing the command's options
     *
     * @return void
     */
    abstract protected function assembleElements();

    /**
     * Create and add a submit button to the form
     *
     * @return void
     */
    abstract protected function assembleSubmitButton();

    /**
     * Get the commands to issue for the given objects
     *
     * @param Traversable<Model> $objects
     *
     * @return Traversable<IcingaCommand>
     */
    abstract protected function getCommands(Traversable $objects): Traversable;

    protected function assemble()
    {
        $this->assembleElements();

        /** @var Web $app */
        $app = Icinga::app();
        if ($app->getRequest()->isXmlHttpRequest() || ! $app->getRequest()->isApiRequest()) {
            // TODO: Remove/adjust this condition once a real API exists and is stable (#162)
            $this->assembleSubmitButton();
            $this->addElement($this->createCsrfCounterMeasure(Session::getSession()->getId()));
        }
    }

    protected function onSuccess()
    {
        $errors = [];
        $objects = $this->getObjects();

        foreach ($this->getCommands(is_array($objects) ? new ArrayIterator($objects) : $objects) as $command) {
            try {
                $this->sendCommand($command);
            } catch (Exception $e) {
                Logger::error($e->getMessage());
                $errors[] = $e->getMessage();
            }
        }

        if (! empty($errors)) {
            if (count($errors) > 1) {
                Notification::warning(
                    t('Some commands were not transmitted. Please check the log. The first error follows.')
                );
            }

            $this->errorOccurred = true;

            Notification::error($errors[0]);
        }
    }

    /**
     * Transmit the given command
     *
     * @param IcingaCommand $command
     *
     * @return void
     */
    protected function sendCommand(IcingaCommand $command)
    {
        (new CommandTransport())->send($command);
    }

    /**
     * Yield the $objects the currently logged in user has the permission $permission for
     *
     * @param string      $permission
     * @param Traversable $objects
     *
     * @return Generator
     */
    protected function filterGrantedOn(string $permission, Traversable $objects): Generator
    {
        foreach ($objects as $object) {
            if ($this->isGrantedOn($permission, $object)) {
                yield $object;
            }
        }
    }
}
