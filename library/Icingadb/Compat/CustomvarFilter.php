<?php

namespace Icinga\Module\Icingadb\Compat;

use FilterIterator;
use Icinga\Util\GlobFilter;
use Iterator;
use stdClass;
use function ipl\Stdlib\arrayval;

class CustomvarFilter extends FilterIterator
{
    /** @var string */
    protected $objectType;

    /** @var GlobFilter */
    protected $filter;

    /** @var string */
    protected $toObfuscate;

    protected $currentResult;

    public function __construct(Iterator $iterator, $objectType, array $restrictions, $toObfuscate)
    {
        $this->objectType = $objectType;

        if (! empty($restrictions)) {
            $this->filter = new GlobFilter($restrictions);
        }

        if ($toObfuscate) {
            $patterns = [];
            foreach (explode(',', $toObfuscate) as $pattern) {
                $nonWildcards = [];
                foreach (explode('*', $pattern) as $nonWildcard) {
                    $nonWildcards[] = preg_quote($nonWildcard, '/');
                }

                $patterns[] = implode('.*', $nonWildcards);
            }

            $this->toObfuscate = '/^(' . join('|', $patterns) . ')$/i';
        }

        parent::__construct($iterator);
    }

    public function accept()
    {
        if ($this->filter === null) {
            return true;
        }

        $model = $this->getInnerIterator()->current();

        $this->currentResult = $this->filter->removeMatching([
            $this->objectType => [
                'vars' => [
                    $model->name => $model->value
                ]
            ]
        ]);

        return isset($this->currentResult[$this->objectType]['vars'][$model->name]);
    }

    public function current()
    {
        $model = parent::current();

        if ($this->filter !== null) {
            $model->value = $this->currentResult[$this->objectType]['vars'][$model->name];
        }

        if ($this->toObfuscate !== null) {
            $model->value = $this->obfuscate($model->name, $model->value);
        }

        return $model;
    }

    protected function obfuscate($name, $value)
    {
        if (preg_match($this->toObfuscate, $name)) {
            return '***';
        } elseif (is_scalar($value)) {
            return $value;
        }

        $obfuscatedVars = [];
        foreach (arrayval($value) as $nestedName => $nestedValue) {
            $obfuscatedVars[$nestedName] = $this->obfuscate($nestedName, $nestedValue);
        }

        return $value instanceof stdClass ? (object) $obfuscatedVars : $obfuscatedVars;
    }
}
