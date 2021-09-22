<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Web\Control;

use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;

class ProblemToggle extends CompatForm
{
    use FormUid;

    protected $filter;

    protected $protector;

    protected $defaultAttributes = [
        'name'    => 'problem-toggle',
        'class'   => 'icinga-form icinga-controls inline'
    ];

    public function __construct($filter)
    {
        $this->filter = $filter;
    }

    /**
     * Set callback to protect ids with
     *
     * @param   callable $protector
     *
     * @return  $this
     */
    public function setIdProtector(callable $protector): self
    {
        $this->protector = $protector;

        return $this;
    }

    /**
     * Get whether the toggle is checked
     *
     * @return bool
     */
    public function isChecked(): bool
    {
        $this->ensureAssembled();

        return $this->getElement('problems')->isChecked();
    }

    protected function assemble()
    {
        $this->addElement('checkbox', 'problems', [
            'class'     => 'autosubmit',
            'id'        => $this->protectId('problems'),
            'label'     => t('Problems Only'),
            'value'     => $this->filter !== null
        ]);

        $this->add($this->createUidElement());
    }

    private function protectId($id)
    {
        if (is_callable($this->protector)) {
            return call_user_func($this->protector, $id);
        }

        return $id;
    }
}
