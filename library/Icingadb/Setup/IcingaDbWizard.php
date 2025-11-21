<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Icingadb\Setup;

use Icinga\Application\Icinga;
use Icinga\Module\Setup\Forms\SummaryPage;
use Icinga\Module\Setup\Requirement\PhpModuleRequirement;
use Icinga\Module\Setup\Requirement\WebLibraryRequirement;
use Icinga\Module\Setup\RequirementSet;
use Icinga\Module\Setup\Setup;
use Icinga\Module\Setup\SetupWizard;
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

        $requiredVersions = Icinga::app()->getModuleManager()->getModule('icingadb')->getRequiredLibraries();

        $set->add(new WebLibraryRequirement([
            'condition'     => ['icinga-php-library', '', $requiredVersions['icinga-php-library']],
            'alias'         => 'Icinga PHP library',
            'description'   => t('The Icinga PHP library (IPL) is required for Icinga DB Web')
        ]));

        $set->add(new WebLibraryRequirement([
            'condition'     => ['icinga-php-thirdparty', '',  $requiredVersions['icinga-php-thirdparty']],
            'alias'         => 'Icinga PHP Thirdparty',
            'description'   => t('The Icinga PHP Thirdparty library is required for Icinga DB Web')
        ]));

        $set->add(new PhpModuleRequirement([
            'condition'     => 'libxml',
            'alias'         => 'libxml',
            'description'   => t('For check plugins that output HTML the libxml extension is required')
        ]));

        $set->add(new PhpModuleRequirement([
            'condition'     => 'dom',
            'alias'         => 'dom',
            'description'   => t('For check plugins that output HTML the dom extension is required')
        ]));

        $set->add(new PhpModuleRequirement([
            'condition'     => 'curl',
            'alias'         => 'cURL',
            'description'   => t(
                'To send external commands over Icinga 2\'s API, the cURL module for PHP is required.'
            )
        ]));

        return $set;
    }
}
