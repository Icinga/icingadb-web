<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\TimeSince;
use ipl\Web\Widget\VerticalKeyValue;

class Health extends BaseHtmlElement
{
    protected $data;

    protected $tag = 'section';

    public function __construct($data)
    {
        $this->data = $data;
    }

    protected function assemble()
    {
        if ($this->data->heartbeat->getTimestamp() > time() - 60) {
            $this->add(Html::tag('div', ['class' => 'icinga-health up'], [
                Html::sprintf(
                    t('Icinga 2 is up and running %s', '...since <timespan>'),
                    new TimeSince($this->data->icinga2_start_time->getTimestamp())
                )
            ]));
        } else {
            $this->add(Html::tag('div', ['class' => 'icinga-health down'], [
                Html::sprintf(
                    t('Icinga 2 or Icinga DB is not running %s', '...since <timespan>'),
                    new TimeSince($this->data->heartbeat->getTimestamp())
                )
            ]));
        }

        $icingaInfo = Html::tag('div', ['class' => 'icinga-info'], [
            new VerticalKeyValue(
                t('Icinga 2 Version'),
                $this->data->icinga2_version
            ),
            new VerticalKeyValue(
                t('Icinga 2 Start Time'),
                new TimeAgo($this->data->icinga2_start_time->getTimestamp())
            ),
            new VerticalKeyValue(
                t('Last Heartbeat'),
                new TimeAgo($this->data->heartbeat->getTimestamp())
            ),
            new VerticalKeyValue(
                t('Active Icinga 2 Endpoint'),
                $this->data->endpoint->name ?: t('N/A')
            ),
            new VerticalKeyValue(
                t('Icinga DB Version'),
                $this->data->icingadb_version ?? t('N/A')
            ),
            new VerticalKeyValue(
                t('Active Icinga Web Endpoint'),
                gethostname() ?: t('N/A')
            )
        ]);
        $this->add($icingaInfo);
    }
}
