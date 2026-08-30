<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\ProvidedHook\Notifications;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Notifications\Common\Database;
use Icinga\Module\Notifications\Hook\SourceIntegrationSetupHook;
use Icinga\Web\Notification;
use ipl\Html\Form;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class SourceIntegrationSetup extends SourceIntegrationSetupHook
{
    use Translation;

    public function getIntegration(): ValidHtml
    {
        return (new CompatForm())
            ->addAttributes(['class' => 'inline'])
            ->setAction((string) Url::fromRequest())
            ->addElement('submit', 'submit',  ['label' => $this->translate('Icinga2 Integration')])
            ->on(Form::ON_SUBMIT, function (): void {
               // Database::get()->update('source', ['listener_username' => 'test', 'deleted' => 'n'], ['id = ?' => 1]);
                Notification::success($this->translate('Icinga2 source integration setup completed'));

                $this->setFinished();
            })
            ->handleRequest(ServerRequest::fromGlobals());
    }
}
