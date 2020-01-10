<?php

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Data\Filter\Filter;
use ipl\Html\ValidHtml;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;

class ContinueWith implements ValidHtml
{
    protected $filter;

    protected $url;

    public function __construct(Filter $filter, Url $url)
    {
        $this->filter = $filter;

        $this->url = $url;
    }

    public function render()
    {
        if ($this->filter->isEmpty()) {
            return null;
        }

        $continue = new ActionLink(
            'Continue with filter',
            $this->url->setQueryString($this->filter->toQueryString()),
            'forward',
            ['class' => 'continue-with', 'data-base-target' => '_next']
        );

        return $continue->render();
    }
}
