<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\Detail;

use Icinga\Date\DateFormatter;
use Icinga\Date\DateFormatter as WebDateFormatter;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Widget\ItemList\ObjectList;
use Icinga\Module\Icingadb\Widget\MarkdownText;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Forms\Command\Object\DeleteDowntimeForm;
use Icinga\Module\Icingadb\Model\Downtime;
use Icinga\Module\Icingadb\Widget\ShowMore;
use ipl\Web\Url;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\TemplateString;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use ipl\Web\Widget\StateBall;

class DowntimeDetail extends BaseHtmlElement
{
    use Auth;
    use Database;
    use HostLink;
    use ServiceLink;

    /** @var BaseHtmlElement */
    protected $control;

    /** @var Downtime */
    protected $downtime;

    protected $defaultAttributes = ['class' => ['object-detail', 'downtime-detail']];

    protected $tag = 'div';

    public function __construct(Downtime $downtime)
    {
        $this->downtime = $downtime;
    }

    protected function createCancelDowntimeForm()
    {
        $action = Links::downtimesDelete();
        $action->setFilter(Filter::equal('name', $this->downtime->name));

        return (new DeleteDowntimeForm())
            ->setObjects([$this->downtime])
            ->populate(['redirect' => '__BACK__'])
            ->setAction($action->getAbsoluteUrl());
    }

    protected function createTimeline(): DowntimeCard
    {
        return new DowntimeCard($this->downtime);
    }

    protected function assemble()
    {
        $this->add(Html::tag('h2', t('Comment')));
        $this->add(Html::tag('div', [
            new Icon('user'),
            Html::sprintf(
                t('%s commented: %s', '<username> ..: <comment>'),
                $this->downtime->author,
                new MarkdownText($this->downtime->comment)
            )
        ]));

        $this->add(Html::tag('h2', t('Details')));

        if (getenv('ICINGAWEB_EXPORT_FORMAT') === 'pdf') {
            $this->addHtml(new HorizontalKeyValue(
                t('Type'),
                $this->downtime->is_flexible ? t('Flexible') : t('Fixed')
            ));
            if ($this->downtime->object_type === 'host') {
                $this->addHtml(new HorizontalKeyValue(t('Host'), [
                    $this->downtime->host->name,
                    ' ',
                    new StateBall($this->downtime->host->state->getStateText())
                ]));
            } else {
                $this->addHtml(new HorizontalKeyValue(t('Service'), Html::sprintf(
                    t('%s on %s', '<service> on <host>'),
                    [
                        $this->downtime->service->name,
                        ' ',
                        new StateBall($this->downtime->service->state->getStateText())
                    ],
                    $this->downtime->host->name
                )));
            }
        }

        if ($this->downtime->triggered_by_id !== null || $this->downtime->parent_id !== null) {
            if ($this->downtime->triggered_by_id !== null) {
                $label = t('Triggered By');
                $relatedDowntime = $this->downtime->triggered_by;
            } else {
                $label = t('Parent');
                $relatedDowntime = $this->downtime->parent;
            }

            $this->addHtml(new HorizontalKeyValue(
                $label,
                HtmlElement::create('span', ['class' => 'accompanying-text'], TemplateString::create(
                    $relatedDowntime->is_flexible
                        ? t('{{#link}}Flexible Downtime{{/link}} for %s')
                        : t('{{#link}}Fixed Downtime{{/link}} for %s'),
                    ['link' => new Link(null, Links::downtime($relatedDowntime), ['class' => 'subject'])],
                    ($relatedDowntime->object_type === 'host'
                        ? $this->createHostLink($relatedDowntime->host, true)
                        : $this->createServiceLink($relatedDowntime->service, $relatedDowntime->host, true))
                ))
            ));
        }

        $this->add(new HorizontalKeyValue(
            t('Created'),
            WebDateFormatter::formatDateTime($this->downtime->entry_time->getTimestamp())
        ));
        $this->add(new HorizontalKeyValue(
            t('Start time'),
            $this->downtime->start_time
                ? WebDateFormatter::formatDateTime($this->downtime->start_time->getTimestamp())
                : new EmptyState(t('Not started yet'))
        ));
        $this->add(new HorizontalKeyValue(
            t('End time'),
            $this->downtime->end_time
                ? WebDateFormatter::formatDateTime($this->downtime->end_time->getTimestamp())
                : new EmptyState(t('Not started yet'))
        ));
        $this->add(new HorizontalKeyValue(
            t('Scheduled Start'),
            WebDateFormatter::formatDateTime($this->downtime->scheduled_start_time->getTimestamp())
        ));
        $this->add(new HorizontalKeyValue(
            t('Scheduled End'),
            WebDateFormatter::formatDateTime($this->downtime->scheduled_end_time->getTimestamp())
        ));
        $this->add(new HorizontalKeyValue(
            t('Scheduled Duration'),
            DateFormatter::formatDuration($this->downtime->scheduled_duration / 1000)
        ));
        if ($this->downtime->is_flexible) {
            $this->add(new HorizontalKeyValue(
                t('Flexible Duration'),
                DateFormatter::formatDuration($this->downtime->flexible_duration / 1000)
            ));
        }

        $query = Downtime::on($this->getDb())->with([
            'host',
            'host.state',
            'service',
            'service.host',
            'service.host.state',
            'service.state'
        ])
            ->limit(3)
            ->filter(Filter::equal('parent_id', $this->downtime->id))
            ->orFilter(Filter::equal('triggered_by_id', $this->downtime->id));
        $this->applyRestrictions($query);

        $children = $query->peekAhead()->execute();
        if ($children->hasResult()) {
            $this->addHtml(
                new HtmlElement('h2', null, Text::create(t('Children'))),
                new ObjectList($children),
                (new ShowMore($children, Links::downtimes()->setQueryString(
                    QueryString::render(Filter::any(
                        Filter::equal('downtime.parent.name', $this->downtime->name),
                        Filter::equal('downtime.triggered_by.name', $this->downtime->name)
                    ))
                )))->setBaseTarget('_next')
            );
        }

        $this->add(Html::tag('h2', t('Progress')));
        $this->add($this->createTimeline());

        if (
            getenv('ICINGAWEB_EXPORT_FORMAT') !== 'pdf'
            && $this->isGrantedOn(
                'icingadb/command/downtime/delete',
                $this->downtime->{$this->downtime->object_type}
            )
        ) {
            $this->add($this->createCancelDowntimeForm());
        }
    }
}
