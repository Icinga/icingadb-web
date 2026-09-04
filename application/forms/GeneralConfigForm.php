<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Icingadb\Forms;

use Icinga\Application\Config as ApplicationConfig;
use Icinga\Application\Logger;
use Icinga\Application\Modules\Module;
use Icinga\Data\ResourceFactory;
use Icinga\Module\Icingadb\Common\Backend;
use Icinga\Module\Icingadb\Common\Database;
use Icinga\Module\Icingadb\Model\Config as ConfigModel;
use Icinga\Module\Icingadb\Model\Instance;
use Icinga\Module\Notifications\Integrations\Source;
use Icinga\Web\Form\ConfigForm;
use Icinga\Web\Session;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Sql\Config;
use ipl\Sql\Connection;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;
use ipl\Web\Common\CalloutType;
use ipl\Web\Url;
use ipl\Web\Widget\Callout;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;
use RuntimeException;
use Throwable;

use function ipl\Stdlib\iterable_value_first;

class GeneralConfigForm extends ConfigForm
{
    use Database;

    /** @var string Config key under which Icinga DB's Notifications socket URL is stored */
    public const URL_CONFIG_KEY = 'ICINGADB_NOTIFICATIONS_URL';

    protected $defaultAttributes = [
        'class' => ['icinga-form', 'icinga-controls', 'general-config-form'],
        'name'  => 'general-config-form'
    ];

    /** @var bool Whether the assisted configuration is unavailable and the form is read-only */
    private bool $locked = false;

    /** @var string[] The reasons why the toggle is disabled */
    private array $lockMessages = [];

    /** @var bool Whether notifications are enabled */
    private bool $notificationsEnabled = false;

    /** @var ?string The database resource the form is rendered for */
    private ?string $resource = null;

    /** @var bool Whether a different database has been selected than the one the form was rendered for */
    private bool $databaseChanged = false;

    /** @var bool Whether Icinga Notifications lacks a source for the responsible instance */
    private bool $sourceMissing = false;

    /** @var bool Whether Icinga DB reports that it fails to transmit notifications */
    private bool $unhealthy = false;

    public function __construct(ApplicationConfig $config)
    {
        parent::__construct($config);

        $this->applyDefaultElementDecorators();
    }

    protected function onSuccess(): void
    {
        $this->saveNotificationsConfig();
        parent::onSuccess();
    }

    protected function assemble(): void
    {
        $this->setCsrfCounterMeasureId(Session::getSession()->getId());

        $this->resource = $this->getPopulatedValue('icingadb__resource', $this->config->get('icingadb', 'resource'));

        $this->addDatabaseSection();

        if (Module::exists('notifications')) {
            $this->readNotificationsConfig();
            $this->addNotificationsSection();
        }
    }

    private function addDatabaseSection(): void
    {
        $this->addHtml(HtmlElement::create('h2', null, $this->translate('Database')));

        $dbResources = ResourceFactory::getResourceConfigs('db')->keys();

        $this->addElement('select', 'icingadb__resource', [
            'label'        => $this->translate('Database'),
            'description'  => $this->translate('Database resource'),
            'options'      => array_combine($dbResources, $dbResources),
            'pleaseChoose' => true,
            'required'     => true,
            'class'        => ['autosubmit']
        ]);

        $this->addElement('hidden', 'renderedForDb', ['ignore' => true]);
        $this->databaseChanged = $this->getPopulatedValue('renderedForDb', $this->resource) !== $this->resource;
        $this->getElement('renderedForDb')->setValue($this->resource);
    }

    private function addNotificationsSection(): void
    {
        $this->addElement(
            'fieldset',
            'notifications',
            [
                'ignore' => true,
                'label' => $this->translate('Icinga Notifications')
            ]
        );

        $notifications = $this->getElement('notifications');
        $notifications->addHtml(HtmlElement::create('p', ['class' => 'description'], [
            Text::create($this->translate(
                'When enabled, Icinga DB connects to Icinga Notifications through its Unix domain socket.'
            )),
            Text::create(' '),
            new Link(
                [
                    $this->translate('Consult the documentation for more information'),
                    ' ',
                    new Icon('arrow-up-right-from-square')
                ],
                Url::fromPath(
                    'https://icinga.com/docs/icinga-db/latest/doc/03-Configuration/#notifications-configuration'
                ),
                ['target' => '_blank']
            )
        ]));

        if ($this->locked) {
            $notifications->addHtml(
                new Callout(
                    CalloutType::Info,
                    count($this->lockMessages) === 1
                        ? $this->lockMessages[0]
                        : HtmlElement::create(
                            'ul',
                            ['class' => 'lock-reasons'],
                            array_map(
                                fn(string $message) => HtmlElement::create('li', null, $message),
                                $this->lockMessages
                            )
                        ),
                    $this->translate('Assisted configuration is not possible')
                )
            );
        }

        if ($this->sourceMissing) {
            $notifications->addHtml(
                new Callout(
                    CalloutType::Warning,
                    $this->translate(
                        'Icinga Notifications does not have a source for Icinga DB configured yet.'
                        . ' Storing this form may fix the problem.'
                    ),
                    $this->translate('No source in Icinga Notifications')
                )
            );
        } elseif ($this->notificationsEnabled && $this->unhealthy) {
            $notifications->addHtml(
                new Callout(
                    CalloutType::Warning,
                    $this->translate(
                        'Icinga DB currently fails to transmit notifications, please check its log for details.'
                    ),
                    $this->translate('Notifications are not transmitted')
                )
            );
        }

        if ($this->locked || $this->databaseChanged) {
            $notifications->clearPopulatedValue('enabled');
        }

        $notifications->addElement('checkbox', 'enabled', [
            'disabled' => $this->locked,
            'label'    => $this->translate('Enable notifications'),
            'value'    => $this->notificationsEnabled
        ]);
    }

    /**
     * Read the current Icinga Notifications configuration
     *
     * Lock the assisted configuration if it is not applicable with a hint explaining why.
     *
     * @return void
     */
    private function readNotificationsConfig(): void
    {
        if (Str::isEmpty($this->resource)) {
            $this->lockNotifications($this->translate('No database has been configured yet.'));

            return;
        }

        try {
            if ($this->resource !== $this->config->get('icingadb', 'resource')) {
                $config = new Config(ResourceFactory::getResourceConfig($this->resource));
                Backend::setDb(new Connection($config));
            }

            if (! Backend::supportsNotifications()) {
                $this->lockNotifications(
                    $this->translate('The Icinga DB schema is outdated. Please update to configure notifications.')
                );

                return;
            }

            $instances = Instance::on($this->getDb())->columns(
                [
                    'endpoint_id',
                    'notifications_discovered_socket_path',
                    'notifications_synchronize_with_database',
                    'icingadb_service_user',
                    'notifications_healthy'
                ]
            )->filter(Filter::equal('responsible', true))->execute();

            if (count($instances) === 0) {
                $this->lockNotifications($this->translate('No Icinga DB instance was found.'));

                return;
            } elseif (count($instances) > 1) {
                $this->lockNotifications(
                    $this->translate('Assisted configuration for multiple environments is not supported.')
                );

                return;
            }

            $instance = iterable_value_first($instances);
            if (! $instance->notifications_synchronize_with_database) {
                $this->lockNotifications(
                    $this->translate('Synchronization of the configuration with the database is disabled.')
                );

                return;
            }

            if ($instance->notifications_discovered_socket_path === null) {
                $this->lockNotifications(
                    $this->translate('No Unix socket for Icinga Notifications was discovered.')
                );
            }

            $serviceUser = $instance->icingadb_service_user;
            $this->unhealthy = $instance->notifications_healthy === false;

            $configuredEndpoints = [];
            $managedLocally = false;
            foreach (
                ConfigModel::on($this->getDb())
                    ->columns(['endpoint_id', 'locked', 'environment_id'])
                    ->filter(Filter::equal('env_key', static::URL_CONFIG_KEY)) as $configRow
            ) {
                $configuredEndpoints[$configRow->endpoint_id] = true;

                if ($configRow->locked) {
                    $managedLocally = true;
                }
            }

            if ($managedLocally) {
                $this->lockNotifications($this->translate('The configuration is managed locally.'));
            }

            $enabled = isset($configuredEndpoints[$instance->endpoint_id ?? $this->defaultEndpointId()]);

            if ($enabled) {
                if (Source::get($serviceUser)->getName() === null) {
                    $enabled = false;
                    $this->sourceMissing = true;
                }
            }

            $this->notificationsEnabled = $enabled;
        } catch (Throwable $e) {
            Logger::error('Failed to read the Icinga Notifications configuration: %s', $e);
            $this->lockNotifications(
                $this->translate(
                    'Failed to read the configuration from the database. Please check the log for details.'
                )
            );
        }
    }

    /**
     * Apply the assisted Icinga Notifications configuration to the database
     *
     * @return void
     *
     * @throws RuntimeException If the configuration could not be applied
     */
    private function saveNotificationsConfig(): void
    {
        if ($this->locked || ! $this->hasElement('notifications')) {
            return;
        }

        $enable = $this->getValue('notifications')['enabled'] === 'y';

        try {
            $serviceUsers = [];
            $endpoints = [];
            $environmentId = null;
            // The loop is required so that all instances are configured in the HA case
            foreach (
                Instance::on($this->getDb())->columns([
                    'environment_id',
                    'endpoint_id',
                    'icingadb_service_user',
                    'notifications_discovered_socket_path'
                ]) as $instance
            ) {
                $environmentId = $instance->environment_id;
                $endpointId = $instance->endpoint_id ?? $this->defaultEndpointId();
                $endpoints[$endpointId] = $instance->notifications_discovered_socket_path;
                $serviceUsers[$instance->icingadb_service_user] = true;
            }

            if ($enable) {
                foreach (array_keys($serviceUsers) as $serviceUser) {
                    $source = Source::get($serviceUser);
                    if ($source->getName() === null) {
                        $source->setName('Icinga DB');
                    }

                    $source->setType('icinga2');
                }
            }

            $this->getDb()->transaction(function () use ($enable, $endpoints, $environmentId) {
                $environmentId = $this->encodeBinary($environmentId, 'environment_id');
                foreach ($endpoints as $endpointId => $socketPath) {
                    $endpointId = $this->encodeBinary($endpointId, 'endpoint_id');

                    $this->getDb()->delete('icingadb_config', [
                        'environment_id = ?' => $environmentId,
                        'endpoint_id = ?' => $endpointId,
                        'env_key = ?' => static::URL_CONFIG_KEY,
                        'locked = ?' => 'n'
                    ]);

                    if ($enable && $socketPath !== null) {
                        $this->getDb()->insert('icingadb_config', [
                            'environment_id' => $environmentId,
                            'endpoint_id' => $endpointId,
                            'env_key' => static::URL_CONFIG_KEY,
                            'env_value' => 'unix://' . $socketPath,
                            'locked' => 'n'
                        ]);
                    }
                }
            });

            if (isset($source)) {
                $source->save();
            }
        } catch (Throwable $e) {
            Logger::error('Assisted Icinga Notifications configuration failed: %s', $e);

            throw new RuntimeException(
                $enable
                    ? $this->translate('Failed to enable notifications. Please check the log.')
                    : $this->translate('Failed to disable notifications. Please check the log.'),
                previous: $e
            );
        }
    }

    private function lockNotifications(string $message): static
    {
        $this->locked = true;
        if (! in_array($message, $this->lockMessages, true)) {
            $this->lockMessages[] = $message;
        }

        return $this;
    }

    private function encodeBinary(string $value, string $column): string
    {
        $query = ConfigModel::on($this->getDb());

        return $query->getResolver()
            ->getBehaviors($query->getModel())
            ->persistProperty($value, $column);
    }

    /**
     * Get the default endpoint id, to use if none is configured
     *
     * @return string
     */
    private function defaultEndpointId(): string
    {
        return str_repeat(chr(0), 20);
    }
}
