<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Object;

use Icinga\Module\Icingadb\Command\Object\ToggleObjectFeatureCommand;
use Icinga\Module\Icingadb\Common\Auth;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use ipl\Orm\Model;
use ipl\Web\FormDecorator\IcingaFormDecorator;

class ToggleObjectFeaturesForm extends CommandForm
{
    use Auth;

    const LEAVE_UNCHANGED = 'noop';

    protected $features;

    protected $featureStatus;

    public function __construct($featureStatus)
    {
        $this->featureStatus = $featureStatus;
        $this->features = [
            ToggleObjectFeatureCommand::FEATURE_ACTIVE_CHECKS => [
                'label'         => t('Active Checks'),
                'permission'    => 'monitoring/command/feature/object/active-checks'
            ],
            ToggleObjectFeatureCommand::FEATURE_PASSIVE_CHECKS => [
                'label'         => t('Passive Checks'),
                'permission'    => 'monitoring/command/feature/object/passive-checks'
            ],
            ToggleObjectFeatureCommand::FEATURE_NOTIFICATIONS => [
                'label'         => t('Notifications'),
                'permission'    => 'monitoring/command/feature/object/notifications'
            ],
            ToggleObjectFeatureCommand::FEATURE_EVENT_HANDLER => [
                'label'         => t('Event Handler'),
                'permission'    => 'monitoring/command/feature/object/event-handler'
            ],
            ToggleObjectFeatureCommand::FEATURE_FLAP_DETECTION => [
                'label'         => t('Flap Detection'),
                'permission'    => 'monitoring/command/feature/object/flap-detection'
            ]
        ];

        $this->getAttributes()->add('class', 'object-features');
    }

    protected function assembleElements()
    {
        $decorator = new IcingaFormDecorator();
        foreach ($this->features as $feature => $spec) {
            $options = [
                'class'         => 'autosubmit',
                'disabled'      => ! $this->getAuth()->hasPermission($spec['permission']),
                'label'         => $spec['label']
            ];
            if ($this->featureStatus[$feature] === 2) {
                $this->addElement(
                    'select',
                    $feature,
                    $options + [
                        'description'   => t('Multiple Values'),
                        'options'       => [
                            self::LEAVE_UNCHANGED => t('Leave Unchanged'),
                            t('Disable All'),
                            t('Enable All')
                        ]
                    ]
                );
                $decorator->decorate($this->getElement($feature));

                $this->getElement($feature)
                    ->getWrapper()
                    ->getAttributes()
                    ->add('class', 'indeterminate');
            } else {
                $options['value'] = (bool) $this->featureStatus[$feature];
                $this->addElement('checkbox', $feature, $options);
                $decorator->decorate($this->getElement($feature));
            }
        }
    }

    protected function assembleSubmitButton()
    {
    }

    protected function getCommand(Model $object)
    {
        foreach ($this->features as $feature => $spec) {
            $featureState = $this->getElement($feature)->isChecked();

            if (
                ! $this->getAuth()->hasPermission($spec['permission'])
                || $featureState === self::LEAVE_UNCHANGED
                || (int) $featureState === (int) $this->featureStatus[$feature]
            ) {
                continue;
            }

            $command = new ToggleObjectFeatureCommand();
            $command->setObject($object);
            $command->setFeature($feature);
            $command->setEnabled((int) $featureState);

            yield $command;
        }
    }
}
