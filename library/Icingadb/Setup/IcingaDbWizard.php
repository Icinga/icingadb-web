<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Setup;

use Icinga\Module\Setup\Forms\SummaryPage;
use Icinga\Module\Setup\Requirement\PhpModuleRequirement;
use Icinga\Module\Setup\Requirement\PhpVersionRequirement;
use Icinga\Module\Setup\Requirement\WebModuleRequirement;
use Icinga\Module\Setup\RequirementSet;
use Icinga\Module\Setup\Setup;
use Icinga\Module\Setup\SetupWizard;
use Icinga\Module\Setup\Utils\EnableModuleStep;
use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Web\Wizard;

class IcingaDbWizard extends Wizard implements SetupWizard
{
    protected function init()
    {
        $this->addPage(new WelcomePage());
        $this->addPage(new DbResourcePage());
        $this->addPage(new RedisPage());
        $this->addPage(new ApiTransportPage());
        $this->addPage(new SummaryPage(['name' => 'setup_icingadb_summary']));
    }

    public function setupPage(Form $page, Request $request)
    {
        if ($page->getName() === 'setup_icingadb_summary') {
            $page->setSummary($this->getSetup()->getSummary());
            $page->setSubjectTitle('Icinga DB Web');
        }
    }

    public function getSetup()
    {
        $pageData = $this->getPageData();
        $setup = new Setup();

        $setup->addStep(new DbResourceStep($pageData['setup_icingadb_resource']));
        $setup->addStep(new RedisStep($pageData['setup_icingadb_redis']));
        $setup->addStep(new ApiTransportStep($pageData['setup_icingadb_api_transport']));

        return $setup;
    }

    public function getRequirements()
    {
        $set = new RequirementSet();

        $set->add(new PhpModuleRequirement(array(
            'optional'      => true,
            'condition'     => 'pdo_mysql',
            'alias'         => 'PDO-MySQL',
            'description'   => mt(
                'icingadb',
                'To access Icinga DB\'s MySQL database the PDO-MySQL module for PHP is required.'
            )
        )));

        $set->add(new PhpModuleRequirement([
            'condition'     => 'curl',
            'alias'         => 'cURL',
            'description'   => mt(
                'icingadb',
                'To send external commands over Icinga 2\'s API, the cURL module for PHP is required.'
            )
        ]));

        $redisSet = new RequirementSet();
        $redisSet->add(new PhpModuleRequirement([
            'condition'     => 'redis',
            'alias'         => 'Redis',
            'description'   => mt(
                'icingadb',
                'To view the most recent state information in Icinga DB Web, the Redis module for PHP is required.'
            )
        ]));
        $redisSet->add(new PhpVersionRequirement([
            'condition'     => ['>=', '7.0'],
            'description'   => mt(
                'icingadb',
                'The Redis module for PHP in version 4.3 and above requires PHP 7+'
            )
        ]));
        $set->merge($redisSet);

        return $set;
    }
}
