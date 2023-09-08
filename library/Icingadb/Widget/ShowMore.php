<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Orm\ResultSet;
use ipl\Web\Common\BaseTarget;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;

class ShowMore extends BaseHtmlElement
{
    use BaseTarget;

    protected $defaultAttributes = ['class' => 'show-more'];

    protected $tag = 'div';

    /** @var ResultSet */
    protected $resultSet;

    /** @var Url */
    protected $url;

    /** @var ?string */
    protected $label;

    public function __construct(ResultSet $resultSet, Url $url, string $label = null)
    {
        $this->label = $label;
        $this->resultSet = $resultSet;
        $this->url = $url;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label ?: t('Show More');
    }

    public function renderUnwrapped(): string
    {
        if ($this->resultSet->hasMore()) {
            return parent::renderUnwrapped();
        }

        return '';
    }

    protected function assemble(): void
    {
        if ($this->resultSet->hasMore()) {
            $this->addHtml(new ActionLink($this->getLabel(), $this->url));
        }
    }
}
