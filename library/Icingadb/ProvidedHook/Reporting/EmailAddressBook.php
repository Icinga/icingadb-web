<?php

namespace Icinga\Module\Icingadb\ProvidedHook\Reporting;

use Icinga\Exception\ConfigurationError;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\User;
use Icinga\Module\Reporting\Hook\EmailAddressBookHook;

class EmailAddressBook extends EmailAddressBookHook
{
    use Database;

    /**
     * @return array
     * @throws ConfigurationError
     */
    public function listEmailAddresses(): array
    {
        $emails = [];

        foreach (User::on($this->getDb()) as $user) {
            $emails[$user['email']] = $user['display_name'];
        }

        return $emails;
    }
}
