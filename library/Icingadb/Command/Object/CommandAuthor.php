<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

trait CommandAuthor
{
    /**
     * Author of the command
     *
     * @var string
     */
    protected $author;

    /**
     * Set the author
     *
     * @param   string $author
     *
     * @return  $this
     */
    public function setAuthor(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get the author
     *
     * @return string
     */
    public function getAuthor(): string
    {
        if ($this->author === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->author;
    }
}
