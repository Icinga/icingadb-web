<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Forms\Command\Instance;

use Icinga\Module\Icingadb\Command\Instance\ToggleInstanceFeatureCommand;
use Icinga\Module\Icingadb\Forms\Command\CommandForm;
use Icinga\Web\Notification;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use Traversable;

class ToggleInstanceFeaturesForm extends CommandForm
{
    protected $features;

    protected $featureStatus;

    /**
     * ToggleFeature(s) being used to submit this form
     *
     * @var ToggleInstanceFeatureCommand[]
     */
    protected $submittedFeatures = [];

    public function __construct(array $featureStatus)
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

        $this->on(self::ON_SUCCESS, function () {
            if ($this->errorOccurred) {
                return;
            }

            foreach ($this->submittedFeatures as $feature) {
                $enabled = $feature->getEnabled();
                switch ($feature->getFeature()) {
                    case ToggleInstanceFeatureCommand::FEATURE_ACTIVE_HOST_CHECKS:
                        if ($enabled) {
                            $message = t('Enabled active host checks successfully');
                        } else {
                            $message = t('Disabled active host checks successfully');
                        }

                        break;
                    case ToggleInstanceFeatureCommand::FEATURE_ACTIVE_SERVICE_CHECKS:
                        if ($enabled) {
                            $message = t('Enabled active service checks successfully');
                        } else {
                            $message = t('Disabled active service checks successfully');
                        }

                        break;
                    case ToggleInstanceFeatureCommand::FEATURE_EVENT_HANDLERS:
                        if ($enabled) {
                            $message = t('Enabled event handlers successfully');
                        } else {
                            $message = t('Disabled event handlers checks successfully');
                        }

                        break;
                    case ToggleInstanceFeatureCommand::FEATURE_FLAP_DETECTION:
                        if ($enabled) {
                            $message = t('Enabled flap detection successfully');
                        } else {
                            $message = t('Disabled flap detection successfully');
                        }

                        break;
                    case ToggleInstanceFeatureCommand::FEATURE_NOTIFICATIONS:
                        if ($enabled) {
                            $message = t('Enabled notifications successfully');
                        } else {
                            $message = t('Disabled notifications successfully');
                        }

                        break;
                    case ToggleInstanceFeatureCommand::FEATURE_PERFORMANCE_DATA:
                        if ($enabled) {
                            $message = t('Enabled performance data successfully');
                        } else {
                            $message = t('Disabled performance data successfully');
                        }

                        break;
                    default:
                        $message = t('Invalid feature option');
                        break;
                }

                Notification::success($message);
            }
        });
    }

    protected function assembleElements()
    {
        $disabled = ! $this->getAuth()->hasPermission('icingadb/command/feature/instance');
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

    protected function getCommands(Traversable $objects): Traversable
    {
        foreach ($this->features as $feature => $spec) {
            $featureState = $this->getElement($feature)->isChecked();

            if ((int) $featureState === (int) $this->featureStatus[$feature]) {
                continue;
            }

            $command = new ToggleInstanceFeatureCommand();
            $command->setFeature($feature);
            $command->setEnabled($featureState);

            $this->submittedFeatures[] = $command;

            yield $command;
        }
    }
}
