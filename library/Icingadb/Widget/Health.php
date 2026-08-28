<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Widget;

use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\ProvidedHook\IcingaHealth;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\I18n\Translation;
use ipl\Web\Widget\TimeAgo;
use ipl\Web\Widget\TimeSince;
use ipl\Web\Widget\VerticalKeyValue;

class Health extends BaseHtmlElement
{
    use Auth;
    use Translation;

    protected $data;

    protected $tag = 'section';

    public function __construct($data)
    {
        $this->data = $data;
    }

    protected function assemble()
    {
        if (
            ! isset($this->data->icingadb_version)
            || version_compare(
                IcingaHealth::normalizeVersion($this->data->icingadb_version),
                IcingaHealth::REQUIRED_ICINGADB_VERSION,
                '<'
            )
        ) {
            $this->addHtml(Html::tag('div', ['class' => 'icinga-health down'], [
                sprintf(
                    $this->translate('Icinga DB is outdated, please upgrade to version %s or later.'),
                    IcingaHealth::REQUIRED_ICINGADB_VERSION
                )
            ]));
        } elseif (
            isset($this->data->notifications_healthy)
            && ! $this->data->notifications_healthy
            &&  (
                $this->getAuth()->hasPermission('icingadb/notifications/manage')
                || $this->getAuth()->hasPermission('icingadb/notifications/subscribe')
            )
        ) {
            $this->addHtml(Html::tag('div', ['class' => 'icinga-health down'], [
                $this->translate(
                    'Notification transmission has been interrupted.'
                    . ' Make sure Icinga DB is able to connect to Icinga Notifications.'
                )
            ]));
        } elseif ($this->data->heartbeat->getTimestamp() > time() - 60) {
            $this->add(Html::tag('div', ['class' => 'icinga-health up'], [
                Html::sprintf(
                    $this->translate('Icinga 2 is up and running %s', '...since <timespan>'),
                    new TimeSince($this->data->icinga2_start_time->getTimestamp())
                )
            ]));
        } else {
            $this->add(Html::tag('div', ['class' => 'icinga-health down'], [
                Html::sprintf(
                    $this->translate('Icinga 2 or Icinga DB is not running %s', '...since <timespan>'),
                    new TimeSince($this->data->heartbeat->getTimestamp())
                )
            ]));
        }

        $icingaInfo = Html::tag('div', ['class' => 'icinga-info'], [
            new VerticalKeyValue(
                $this->translate('Icinga 2 Version'),
                $this->data->icinga2_version
            ),
            new VerticalKeyValue(
                $this->translate('Icinga 2 Start Time'),
                new TimeAgo($this->data->icinga2_start_time->getTimestamp())
            ),
            new VerticalKeyValue(
                $this->translate('Last Heartbeat'),
                new TimeAgo($this->data->heartbeat->getTimestamp())
            ),
            new VerticalKeyValue(
                $this->translate('Active Icinga 2 Endpoint'),
                $this->data->endpoint->name ?: t('N/A')
            ),
            new VerticalKeyValue(
                $this->translate('Icinga DB Version'),
                $this->data->icingadb_version ?? t('N/A')
            ),
            new VerticalKeyValue(
                $this->translate('Active Icinga Web Endpoint'),
                gethostname() ?: t('N/A')
            ),
            isset($this->data->notifications_healthy) ? new VerticalKeyValue(
                $this->translate('Icinga Notifications Connection'),
                $this->data->notifications_healthy
                    ? $this->translate('Healthy')
                    : $this->translate('Unhealthy')
            ) : null
        ]);
        $this->add($icingaInfo);
    }
}
