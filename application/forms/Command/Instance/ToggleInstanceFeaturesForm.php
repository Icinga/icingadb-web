<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Instance;

use Icinga\Module\Icingadb\Command\Instance\ToggleInstanceFeatureCommand;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use ipl\Orm\Model;
use ipl\Web\FormDecorator\IcingaFormDecorator;

class ToggleInstanceFeaturesForm extends CommandForm
{
    use Auth;

    protected $features;

    protected $featureStatus;

    public function __construct($featureStatus)
    {
        $this->featureStatus = $featureStatus;
        $this->features = [
            ToggleInstanceFeatureCommand::FEATURE_ACTIVE_HOST_CHECKS =>
                t('Active Host Checks'),
            ToggleInstanceFeatureCommand::FEATURE_ACTIVE_SERVICE_CHECKS =>
                t('Active Service Checks'),
            ToggleInstanceFeatureCommand::FEATURE_EVENT_HANDLERS =>
                t('Event Handlers'),
            ToggleInstanceFeatureCommand::FEATURE_FLAP_DETECTION =>
                t('Flap Detection'),
            ToggleInstanceFeatureCommand::FEATURE_NOTIFICATIONS =>
                t('Notifications'),
            ToggleInstanceFeatureCommand::FEATURE_PERFORMANCE_DATA =>
                t('Performance Data')
        ];

        $this->getAttributes()->add('class', 'instance-features');
    }

    protected function assembleElements()
    {
        $disabled = ! $this->getAuth()->hasPermission('monitoring/command/feature/instance');
        $decorator = new IcingaFormDecorator();

        foreach ($this->features as $feature => $label) {
            $this->addElement(
                'checkbox',
                $feature,
                [
                    'class'     => 'autosubmit',
                    'label'     => $label,
                    'disabled'  => $disabled,
                    'value'     => (bool) $this->featureStatus[$feature]
                ]
            );
            $decorator->decorate($this->getElement($feature));
        }
    }

    protected function assembleSubmitButton()
    {
    }

    protected function getCommand(Model $object)
    {
        foreach ($this->features as $feature => $spec) {
            $featureState = $this->getElement($feature)->isChecked();

            if ((int) $featureState === (int) $this->featureStatus[$feature]) {
                continue;
            }

            $command = new ToggleInstanceFeatureCommand();
            $command->setFeature($feature);
            $command->setEnabled((int) $featureState);

            yield $command;
        }
    }
}
