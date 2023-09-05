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
     * @param   int     $limit          Max labels to show; 0 for no limit
     * @param   string  $color          The color indicating the perfdata state
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
        $keys = [
            ''      => '',
            'label' => t('Label'),
            'value' => t('Value'),
            'min'   => t('Min'),
            'max'   => t('Max'),
            'warn'  => t('Warning'),
            'crit'  => t('Critical')
        ];

        $containsSparkline = false;
        foreach ($pieChartData as $perfdata) {
            if ($perfdata->isVisualizable()) {
                $containsSparkline = true;
                break;
            }
        }

        $headerRow = new HtmlElement('tr');
        foreach ($keys as $key => $col) {
            if (! $containsSparkline && $key === '') {
                continue;
            }

            $headerRow->addHtml(new HtmlElement('th', Attributes::create([
                'class' => $key === 'label' ? 'title' : null
            ]), Text::create($col)));
        }

        $this->getHeader()->addHtml($headerRow);

        $count = 0;
        foreach ($pieChartData as $perfdata) {
            if ($this->limit > 0 && $count === $this->limit) {
                break;
            }

            $count++;
            $cols = [];
            if ($containsSparkline) {
                if ($perfdata->isVisualizable()) {
                    $cols[] = Table::td(
                        HtmlString::create($perfdata->asInlinePie($this->color)->render()),
                        ['class' => 'sparkline-col']
                    );
                } else {
                    $cols[] = Table::td('');
                }
            }

            foreach ($perfdata->toArray() as $column => $value) {
                $cols[] = Table::td(
                    new HtmlElement(
                        'span',
                        Attributes::create(['class' => $value ? null : 'no-value']),
                        $value ? Text::create($value) : new EmptyState(t('None', 'value'))
                    ),
                    ['class' => $column === 'label' ? 'title' : null]
                );
            }

            $this->addHtml(Table::tr($cols));
        }
    }
}
