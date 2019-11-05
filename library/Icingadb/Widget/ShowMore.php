<?php

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\ValidHtml;
use ipl\Orm\ResultSet;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;

class ShowMore implements ValidHtml
{
    protected $resultSet;

    protected $url;

    public function __construct(ResultSet $resultSet, Url $url)
    {
        $this->resultSet = $resultSet;
        $this->url = $url;
    }

    public function render()
    {
        if (! $this->resultSet->hasMore()) {
            return null;
        }

        return (new ActionLink('Show More', $this->url))->render();
    }
}
