<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

use Icinga\Module\Icingadb\Command\IcingaCommand;

/**
 * Delete a host or service comment
 */
class DeleteCommentCommand extends IcingaCommand
{
    use CommandAuthor;

    /**
     * Name of the comment
     *
     * @var string
     */
    protected $commentName;

    /**
     * Get the name of the comment
     *
     * @return string
     */
    public function getCommentName(): string
    {
        if ($this->commentName === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->commentName;
    }

    /**
     * Set the name of the comment
     *
     * @param   string  $commentName
     *
     * @return  $this
     */
    public function setCommentName(string $commentName): self
    {
        $this->commentName = $commentName;

        return $this;
    }
}
