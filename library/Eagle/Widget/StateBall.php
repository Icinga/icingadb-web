<?php

namespace Icinga\Module\Eagle\Widget;

use ipl\Html\BaseHtmlElement;

/**
 * @TODO(el): Move to ipl-web.
 */
class StateBall extends BaseHtmlElement
{
    const SIZE_TINY = 'xs';

    const SIZE_SMALL = 's';

    const SIZE_MEDIUM = 'm';

    const SIZE_LARGE = 'l';

    protected $tag = 'div';

    public function __construct($state = 'none', $size = self::SIZE_MEDIUM)
    {
        $state = trim($state);

        if (empty($state)) {
            $state = 'none';
        }

        $size = trim($size);

        if (empty($size)) {
            $size = self::SIZE_MEDIUM;
        }

        $this->defaultAttributes = ['class' => "state-ball state-$state ball-size-$size"];
    }
}
