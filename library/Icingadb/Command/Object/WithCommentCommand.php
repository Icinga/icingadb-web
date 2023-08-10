<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Command\Object;

/**
 * Base class for commands adding comments
 */
abstract class WithCommentCommand extends ObjectsCommand
{
    use CommandAuthor;

    /**
     * Comment
     *
     * @var string
     */
    protected $comment;

    /**
     * Set the comment
     *
     * @param   string $comment
     *
     * @return  $this
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get the comment
     *
     * @return string
     */
    public function getComment(): string
    {
        if ($this->comment === null) {
            throw new \LogicException(
                'You are accessing an unset property. Please make sure to set it beforehand.'
            );
        }

        return $this->comment;
    }
}
