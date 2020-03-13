<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

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
        if ($this->data->heartbeat > time() - 60) {
            $this->add(Html::tag('div', ['class' => 'icinga-health up'], [
                'Icinga 2 is up and running ', new TimeSince($this->data->icinga2_start_time)
            ]));
        } else {
            $this->add(Html::tag('div', ['class' => 'icinga-health down'], [
                'Icinga 2 or Icinga DB is not running ', new TimeSince($this->data->heartbeat)
            ]));
        }

        $icingaInfo = Html::tag('div', ['class' => 'icinga-info'], [
            new VerticalKeyValue(
                'Icinga 2 Version',
                $this->data->icinga2_version
            ),
            new VerticalKeyValue(
                'Icinga 2 Start Time',
                new TimeAgo($this->data->icinga2_start_time)
            ),
            new VerticalKeyValue(
                'Last Heartbeat',
                new TimeAgo($this->data->heartbeat)
            ),
            new VerticalKeyValue(
                'Active Icinga 2 Endpoint',
                $this->data->endpoint->name ?: 'N/A'
            ),
            new VerticalKeyValue(
                'Active Icinga Web Endpoint',
                gethostname() ?: 'N/A'
            )
        ]);
        $this->add($icingaInfo);
    }
}
