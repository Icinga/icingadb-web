<?php

/* Icinga DB Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Model\Comment;
use ipl\Html\BaseHtmlElement;

/**
 * @property Comment $object
 */
class CommentHeader extends BaseObjectHeader
{
    use CommentHeaderUtils;

    protected $defaultAttributes = ['class' => 'comment-header'];

    /** @var bool Whether to create an object link */
    protected $wantObjectLink;

    /** @var bool Whether to create caption */
    protected $wantCaption;

    public function __construct(Comment $object, bool $wantObjectLink = true, $wantCaption = false)
    {
        $this->wantObjectLink = $wantObjectLink;
        $this->wantCaption = $wantCaption;

        parent::__construct($object);
    }

    protected function getObject(): Comment
    {
        return $this->object;
    }

    protected function wantSubjectLink(): bool
    {
        return false;
    }

    protected function wantObjectLink(): bool
    {
        return $this->wantObjectLink;
    }

    protected function assembleHeader(BaseHtmlElement $header): void
    {
        $header->addHtml($this->createTitle());
        $header->addHtml($this->createTimestamp());
    }

    protected function assembleMain(BaseHtmlElement $main): void
    {
        $main->addHtml($this->createHeader());

        if ($this->wantCaption) {
            $main->addHtml($this->createCaption());
        }
    }

    protected function assemble(): void
    {
        $this->addHtml($this->createVisual());
        $this->addHtml($this->createMain());
    }
}
