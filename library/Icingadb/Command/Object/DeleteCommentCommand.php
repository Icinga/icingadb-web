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
    public function getCommentName()
    {
        return $this->commentName;
    }

    /**
     * Set the name of the comment
     *
     * @param   string  $commentName
     *
     * @return  $this
     */
    public function setCommentName($commentName)
    {
        $this->commentName = $commentName;

        return $this;
    }
}
