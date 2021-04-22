<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;

class ContinueWith implements ValidHtml
{
    protected $filter;

    protected $url;

    public function __construct(Filter\Rule $filter, Url $url)
    {
        $this->filter = $filter;

        $this->url = $url;
    }

    public function render()
    {
        if ($this->filter instanceof Filter\Chain && $this->filter->isEmpty()) {
            return null;
        }

        $continue = new ActionLink(
            t('Continue with filter'),
            $this->url->setQueryString(QueryString::render($this->filter)),
            'share-square',
            ['class' => 'continue-with', 'data-base-target' => '_next']
        );

        return $continue->render();
    }
}
