<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command;

use ArrayIterator;
use Countable;
use Exception;
use Icinga\Application\Logger;
use Icinga\Module\Icingadb\Command\IcingaCommand;
use Icinga\Module\Icingadb\Command\Transport\CommandTransport;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use ipl\Html\Form;
use ipl\Orm\Model;
use ipl\Web\Common\CsrfCounterMeasure;
use Iterator;
use IteratorIterator;
use Traversable;

abstract class CommandForm extends Form
{
    use Auth;
    use CsrfCounterMeasure;

    protected $defaultAttributes = ['class' => 'icinga-form icinga-controls'];

    /** @var (Traversable<Model>&Countable)|array<Model> */
    protected $objects;

    /** @var bool */
    protected $isApiTarget = false;

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
     * @param (Traversable<Model>&Countable)|array<Model> $objects A traversable that is also countable
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
     * @return (Traversable<Model>&Countable)|array<Model>
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * Set whether this form is an API target
     *
     * @param bool $state
     *
     * @return $this
     */
    public function setIsApiTarget(bool $state = true): self
    {
        $this->isApiTarget = $state;

        return $this;
    }

    /**
     * Get whether this form is an API target
     *
     * @return bool
     */
    public function isApiTarget(): bool
    {
        return $this->isApiTarget;
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
     * @param Iterator<Model> $objects
     *
     * @return Traversable<IcingaCommand>
     */
    abstract protected function getCommands(Iterator $objects): Traversable;

    protected function assemble()
    {
        $this->assembleElements();

        if (! $this->isApiTarget()) {
            $this->assembleSubmitButton();
            $this->addElement($this->createCsrfCounterMeasure(Session::getSession()->getId()));
        }
    }

    protected function onSuccess()
    {
        $objects = $this->getObjects();
        if (is_array($objects)) {
            $objects = new ArrayIterator($objects);
        } else {
            $objects = new IteratorIterator($objects);
        }

        $errors = [];
        foreach ($this->getCommands($objects) as $command) {
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
}
