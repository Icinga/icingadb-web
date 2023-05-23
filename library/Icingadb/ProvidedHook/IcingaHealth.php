<?php

// Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2

namespace Icinga\Module\Icingadb\ProvidedHook;

use Icinga\Application\Hook\HealthHook;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Instance;
use ipl\Web\Url;

class IcingaHealth extends HealthHook
{
    use Database;

    /** @var Instance */
    protected $instance;

    public function getName(): string
    {
        return 'Icinga DB';
    }

    public function getUrl(): Url
    {
        return Url::fromPath('icingadb/health');
    }

    public function checkHealth()
    {
        $instance = $this->getInstance();

        if ($instance === null) {
            $this->setState(self::STATE_UNKNOWN);
            $this->setMessage(t(
                'Icinga DB is not running or not writing into the database'
                . ' (make sure the icinga feature "icingadb" is enabled)'
            ));
        } elseif ($instance->heartbeat->getTimestamp() < time() - 60) {
            $this->setState(self::STATE_CRITICAL);
            $this->setMessage(t(
                'Icinga DB is not running or not writing into the database'
                . ' (make sure the icinga feature "icingadb" is enabled)'
            ));
        } else {
            $this->setState(self::STATE_OK);
            $this->setMessage(t('Icinga DB is running and writing into the database'));
            $warningMessages = [];

            if (! $instance->icinga2_active_host_checks_enabled) {
                $this->setState(self::STATE_WARNING);
                $warningMessages[] = t('Active host checks are disabled');
            }

            if (! $instance->icinga2_active_service_checks_enabled) {
                $this->setState(self::STATE_WARNING);
                $warningMessages[] = t('Active service checks are disabled');
            }

            if (! $instance->icinga2_notifications_enabled) {
                $this->setState(self::STATE_WARNING);
                $warningMessages[] = t('Notifications are disabled');
            }

            if ($this->getState() === self::STATE_WARNING) {
                $this->setMessage(implode("; ", $warningMessages));
            }
        }

        if ($instance !== null) {
            $this->setMetrics([
                'heartbeat' => $instance->heartbeat->getTimestamp(),
                'responsible' => $instance->responsible,
                'icinga2_active_host_checks_enabled' => $instance->icinga2_active_host_checks_enabled,
                'icinga2_active_service_checks_enabled' => $instance->icinga2_active_service_checks_enabled,
                'icinga2_event_handlers_enabled' => $instance->icinga2_event_handlers_enabled,
                'icinga2_flap_detection_enabled' => $instance->icinga2_flap_detection_enabled,
                'icinga2_notifications_enabled' => $instance->icinga2_notifications_enabled,
                'icinga2_performance_data_enabled' => $instance->icinga2_performance_data_enabled,
                'icinga2_start_time' => $instance->icinga2_start_time->getTimestamp(),
                'icinga2_version' => $instance->icinga2_version,
                'endpoint' => ['name' => $instance->endpoint->name]
            ]);
        }
    }

    /**
     * Get an Icinga DB instance
     *
     * @return ?Instance
     */
    protected function getInstance()
    {
        if ($this->instance === null) {
            $this->instance = Instance::on($this->getDb())
                ->with('endpoint')
                ->columns([
                    'heartbeat',
                    'responsible',
                    'icinga2_active_host_checks_enabled',
                    'icinga2_active_service_checks_enabled',
                    'icinga2_event_handlers_enabled',
                    'icinga2_flap_detection_enabled',
                    'icinga2_notifications_enabled',
                    'icinga2_performance_data_enabled',
                    'icinga2_start_time',
                    'icinga2_version',
                    'endpoint.name'
                ])
                ->first();
        }

        return $this->instance;
    }
}
