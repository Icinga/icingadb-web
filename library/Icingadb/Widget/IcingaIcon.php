<?php

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;

class IcingaIcon extends BaseHtmlElement
{
    protected $tag = 'i';

    /**
     * Create a icon element
     *
     * Creates a icon element from the given name and HTML attributes. The icon element's tag will be <i>. The given
     * name will be used as automatically added CSS class for the icon element in the format 'iicon-$name'. In addition,
     * the CSS class 'icon' will be automatically added too.
     *
     * @param string           $name       The name of the icon
     * @param Attributes|array $attributes The HTML attributes for the element
     */
    public function __construct($name, $attributes = null)
    {
        $this
            ->getAttributes()
            ->add('class', ['icon', "iicon-$name"])
            ->add($attributes);
    }
}
