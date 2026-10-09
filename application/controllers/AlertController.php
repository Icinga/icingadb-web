<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Controllers;

use Icinga\Module\Icingadb\Model\AlertHistory;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Web\Controller;
use Icinga\Module\Icingadb\Widget\Detail\AlertDetail;
use Icinga\Module\Icingadb\Widget\Detail\ObjectHeader;
use ipl\Stdlib\Filter;

class AlertController extends Controller
{
    protected AlertHistory $alert;

    public function init()
    {
        $this->addTitleTab($this->translate('Alert'));

        $id = hex2bin($this->params->getRequired('id'));

        // Alerts have no host or service relation, so they are restricted through the event they belong to
        $query = History::on($this->getDb())
            ->with(['host', 'service'])
            ->filter(Filter::equal('alert.id', $id));

        $this->applyRestrictions($query);

        /** @var ?AlertHistory $alert */
        $alert = $query->first()?->alert->filter(Filter::equal('id', $id))->first();
        if ($alert === null) {
            $this->httpNotFound($this->translate('Alert not found'));
        }

        $this->alert = $alert;
    }

    public function indexAction()
    {
        $this->addControl(new ObjectHeader($this->alert));
        $this->addContent(new AlertDetail($this->alert));
    }
}
