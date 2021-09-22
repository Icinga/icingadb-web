<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Module\Icingadb\Util\PerfData;
use Icinga\Module\Icingadb\Util\PerfDataSet;
use Icinga\Module\Icingadb\Widget\EmptyState;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Table;
use ipl\Html\Text;

class PerfDataTable extends Table
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
     */
    public function __construct(string $perfdataStr, int $limit = 0, string $color = PerfData::PERFDATA_OK)
    {
        $this->perfdataStr = $perfdataStr;
        $this->limit = $limit;
        $this->color = $color;
    }

    public function assemble()
    {
        $pieChartData = PerfDataSet::fromString($this->perfdataStr)->asArray();
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

        $headerRow = new HtmlElement('tr');
        foreach ($keys as $key => $col) {
            if ((! $this->containsSparkline) && $col == '') {
                unset($keys[$key]);
                continue;
            }
            if (isset($col)) {
                $headerRow->addHtml(new HtmlElement('th', Attributes::create([
                    'class' => ($col == 'label' ? 'title' : null)
                ]), Text::create($labels[$col])));
            }
        }

        $this->getHeader()->addHtml($headerRow);

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
                    $text = htmlspecialchars($value);
                    $cols[] = Table::td(
                        new HtmlElement(
                            'span',
                            Attributes::create([
                                'class' => ($text ? '' : 'no-value')
                            ]),
                            $text ? Text::create($text) : new EmptyState(t('None', 'value'))
                        ),
                        [ 'class' => ($column == 'label' ? 'title' : null) ]
                    );
                }

                $this->addHtml(Table::tr([$cols]));
            }
        }
    }
}
