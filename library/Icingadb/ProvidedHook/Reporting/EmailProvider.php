<?php

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use Icinga\Exception\ConfigurationError;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Reporting\Hook\EmailProviderHook;

class EmailProvider extends EmailProviderHook
{
    use Database;

    /**
     * @return array
     * @throws ConfigurationError
     */
    public function getContactEmails(): array
    {
        $emails = [];

        foreach (User::on($this->getDb()) as $user) {
            $emails[$user['email']] = $user['display_name'];
        }

        return $emails;
    }
}
