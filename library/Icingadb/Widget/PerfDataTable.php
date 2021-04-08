<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Util\Perfdata;
use Icinga\Module\Icingadb\Util\PerfdataSet;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Table;

class PerfdataTable extends Table
{
    /** @var bool Whether the table contains a sparkline column */
    protected $containsSparkline = false;

    protected $defaultAttributes = [
        'class' => 'performance-data-table collapsible',
        'data-visible-rows' => 6
    ];

    /** @var string The perfdata string  */
    protected $perfdataStr;

    /** @var int Max labels to show; 0 for no limit */
    protected $limit;

    /** @var string The color indicating the perfdata state */
    protected $color;

    /**
     * Display the given perfdata string to the user
     *
     * @param   string  $perfdataStr    The perfdata string
     * @param   int       $limit              Max labels to show; 0 for no limit
     * @param   string  $color             The color indicating the perfdata state
     *
     * @return string
     */
    public function __construct($perfdataStr, $limit = 0, $color = Perfdata::PERFDATA_OK)
    {
        $this->perfdataStr = $perfdataStr;
        $this->limit = $limit;
        $this->color = $color;
    }

    public function assemble()
    {
        $pieChartData = PerfdataSet::fromString($this->perfdataStr)->asArray();
        uasort(
            $pieChartData,
            function ($a, $b) {
                return $a->worseThan($b) ? -1 : ($b->worseThan($a) ? 1 : 0);
            }
        );
        $keys = ['', 'label', 'value', 'min', 'max', 'warn', 'crit'];
        $columns = [];
        $labels = array_combine(
            $keys,
            [
                '',
                t('Label'),
                t('Value'),
                t('Min'),
                t('Max'),
                t('Warning'),
                t('Critical')
            ]
        );
        foreach ($pieChartData as $perfdata) {
            if ($perfdata->isVisualizable()) {
                $columns[''] = '';
                $this->containsSparkline = true;
            }

            foreach ($perfdata->toArray() as $column => $value) {
                if (
                    empty($value) ||
                    $column === 'min' && floatval($value) === 0.0 ||
                    $column === 'max' && $perfdata->isPercentage() && floatval($value) === 100
                ) {
                    continue;
                }

                $columns[$column] = $labels[$column];
            }
        }

        if (! $this->containsSparkline) {
            $keys = array_slice($keys, 1, -1);
        }

        $headerRow = new HtmlElement('tr');
        foreach ($keys as $col) {
            if (isset($col)) {
                $headerRow->add(new HtmlElement('th', [
                    'class' => ($col == 'label' ? 'title' : null)
                ], $labels[$col]));
            }
        }

        $this->getHeader()->add($headerRow);

        foreach ($pieChartData as $count => $perfdata) {
            if ($this->limit != 0 && $count > $this->limit) {
                break;
            } else {
                $cols = [];
                if ($this->containsSparkline) {
                    if ($perfdata->isVisualizable()) {
                        $cols[] = Table::td(
                            HtmlString::create($perfdata->asInlinePie($this->color)->render()),
                            [ 'class' => 'sparkline-col']
                        );
                    } else {
                        $cols[] = Table::td('');
                    }
                }

                foreach ($perfdata->toArray() as $column => $value) {
                    $text = htmlspecialchars(empty($value) ? '-' : $value);
                    $cols[] = Table::td(
                        new HtmlElement(
                            'span',
                            [ 'title' => $text ],
                            $text
                        ),
                        [ 'class' => ($column == 'label' ? 'title' : null) ]
                    );
                }

                $this->add(Table::tr([$cols]));
            }
        }
    }
}
