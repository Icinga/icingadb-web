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
use ipl\Web\FormElement\TermInput;
use ipl\Web\FormElement\TermInput\Term;
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

    /** @var string Config key under which the default relations for events sent to Icinga Notifications are stored */
    public const RELATIONS_CONFIG_KEY = 'ICINGADB_NOTIFICATIONS_DEFAULT_RELATIONS';

    protected $defaultAttributes = [
        'class' => ['icinga-form', 'icinga-controls', 'general-config-form'],
        'name'  => 'general-config-form'
    ];

    /** @var bool Whether the notifications section is read-only */
    private bool $notificationsLocked = false;

    /** @var string[] The reasons why the notifications section is disabled */
    private array $notificationsLockReasons = [];

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

    /** @var ?string The configured default relations as comma separated JSONPaths, null if not configured */
    private ?string $defaultRelations = null;

    /** @var array<string, string[]> Lock reasons for individual config items, keyed by config key */
    private array $configKeyLockReasons = [];

    public function __construct(ApplicationConfig $config)
    {
        parent::__construct($config);

        $this->applyDefaultElementDecorators();
    }

    /**
     * Get the relations that can be included by default in events sent to Icinga Notifications
     *
     * @return array<string, string>
     */
    public static function knownRelations(): array
    {
        return [
            '$.host' => t('Host'),
            '$.hostgroups[*].name' => t('Hostgroups'),
            '$.services[*].name' => t('Services'),
            '$.servicegroups[*].name' => t('Servicegroups'),
            '$.host.vars' => t('Host Variables'),
            '$.services[*].vars' => t('Service Variables'),
        ];
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

        if ($this->notificationsLocked) {
            $notifications->addHtml(
                $this->createLockReasons(
                    $this->notificationsLockReasons,
                    $this->translate('Notifications configuration is not possible')
                )
            );
        }

        if (isset($this->configKeyLockReasons[static::URL_CONFIG_KEY])) {
            $notifications->addHtml(
                $this->createLockReasons(
                    $this->configKeyLockReasons[static::URL_CONFIG_KEY],
                    $this->translate('Enabling or disabling notifications is not possible')
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

        if ($this->notificationsLocked || $this->databaseChanged) {
            $notifications->clearPopulatedValue('enabled');
            $notifications->clearPopulatedValue('relations');
        }

        $notifications->addElement('checkbox', 'enabled', [
            'disabled' => $this->notificationsLocked || isset($this->configKeyLockReasons[static::URL_CONFIG_KEY]),
            'label'    => $this->translate('Enable notifications'),
            'value'    => $this->notificationsEnabled
        ]);

        $notifications->addHtml(
            HtmlElement::create(
                'p',
                ['class' => 'description'],
                Text::create(
                    $this->translate(
                        'Relations to include in every event sent to Icinga Notifications.'
                        . ' If Icinga Notifications requires a relation that is not included, it has to request it'
                        . ' from Icinga DB. Choosing the relations your event rules commonly use makes'
                        . ' this communication more efficient.'
                    )
                )
            )
        );

        if (isset($this->configKeyLockReasons[static::RELATIONS_CONFIG_KEY])) {
            $notifications->addHtml(
                $this->createLockReasons(
                    $this->configKeyLockReasons[static::RELATIONS_CONFIG_KEY],
                    $this->translate('Configuring default relations is not possible')
                )
            );
        }

        $relations = (new TermInput(
            'relations',
            [
                'label' => $this->translate('Default relations'),
                'disabled' => $this->notificationsLocked
                    || isset($this->configKeyLockReasons[static::RELATIONS_CONFIG_KEY])
            ]
        ))
            ->setVerticalTermDirection()
            ->setReadOnly()
            ->setSuggestionUrl(Url::fromPath('icingadb/config/complete'))
            ->setValue($this->defaultRelations ?? '')
            ->on(TermInput::ON_ENRICH, $this->validateAndEnrichRelations(...))
            ->on(TermInput::ON_ADD, $this->validateAndEnrichRelations(...))
            ->on(TermInput::ON_SAVE, $this->validateAndEnrichRelations(...))
            ->on(TermInput::ON_PASTE, $this->validateAndEnrichRelations(...));

        $decorators = $notifications->getDefaultElementDecorators();
        $relations->setDefaultElementDecorators($decorators);
        $relations->addElementDecoratorLoaderPaths([['ipl\\Web\\Compat\\FormDecorator', 'Decorator']]);
        $relations->getDecorators()
            ->addDecoratorLoader('ipl\\Web\\Compat\\FormDecorator', 'Decorator')
            ->addDecorators(array_filter($decorators, fn($decorator) => $decorator !== 'Fieldset'));

        $notifications->addElement($relations);
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
                $this->lockConfigKey(
                    static::URL_CONFIG_KEY,
                    $this->translate('No Unix socket for Icinga Notifications was discovered.')
                );
            }

            $serviceUser = $instance->icingadb_service_user;
            $this->unhealthy = $instance->notifications_healthy === false;

            $configuredEndpoints = [];
            $configRows = ConfigModel::on($this->getDb())
                ->columns(['endpoint_id', 'locked', 'env_key', 'env_value'])
                ->filter(Filter::equal('env_key', [static::URL_CONFIG_KEY, static::RELATIONS_CONFIG_KEY]));
            foreach ($configRows as $configRow) {
                if ($configRow->locked) {
                    $this->lockConfigKey(
                        $configRow->env_key,
                        $this->translate('This configuration item is managed locally.')
                    );
                }

                if ($configRow->env_key === static::URL_CONFIG_KEY) {
                    $configuredEndpoints[$configRow->endpoint_id] = true;
                } elseif ($configRow->env_key === static::RELATIONS_CONFIG_KEY && $this->defaultRelations === null) {
                    $this->defaultRelations = $configRow->env_value;
                }
            }

            $enabled = isset($configuredEndpoints[$instance->endpoint_id ?? $this->defaultEndpointId()]);

            if ($enabled && ! isset($this->configKeyLockReasons[static::URL_CONFIG_KEY])) {
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
        if ($this->notificationsLocked || ! $this->hasElement('notifications')) {
            return;
        }

        $writableKeys = array_diff(
            [static::URL_CONFIG_KEY, static::RELATIONS_CONFIG_KEY],
            array_keys($this->configKeyLockReasons)
        );
        if (empty($writableKeys)) {
            return;
        }

        $enable = $this->getValue('notifications')['enabled'] === 'y';
        $relations = $this->getValue('notifications')['relations'];

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

            if ($enable && in_array(static::URL_CONFIG_KEY, $writableKeys, true)) {
                foreach (array_keys($serviceUsers) as $serviceUser) {
                    $source = Source::get($serviceUser);
                    if ($source->getName() === null) {
                        $source->setName('Icinga DB');
                    }

                    $source->setType('icinga2');
                }
            }

            $this->getDb()
                ->transaction(function () use ($enable, $endpoints, $environmentId, $writableKeys, $relations) {
                    $environmentId = $this->encodeBinary($environmentId, 'environment_id');
                    foreach ($endpoints as $endpointId => $socketPath) {
                        $endpointId = $this->encodeBinary($endpointId, 'endpoint_id');

                        $this->getDb()->delete('icingadb_config', [
                            'environment_id = ?' => $environmentId,
                            'endpoint_id = ?' => $endpointId,
                            'env_key IN (?)' => $writableKeys,
                            'locked = ?' => 'n'
                        ]);

                        if ($enable && $socketPath !== null && in_array(static::URL_CONFIG_KEY, $writableKeys, true)) {
                            $this->getDb()->insert('icingadb_config', [
                                'environment_id' => $environmentId,
                                'endpoint_id' => $endpointId,
                                'env_key' => static::URL_CONFIG_KEY,
                                'env_value' => 'unix://' . $socketPath,
                                'locked' => 'n'
                            ]);
                        }

                        if ($relations !== '' && in_array(static::RELATIONS_CONFIG_KEY, $writableKeys, true)) {
                            $this->getDb()->insert('icingadb_config', [
                                'environment_id' => $environmentId,
                                'endpoint_id' => $endpointId,
                                'env_key' => static::RELATIONS_CONFIG_KEY,
                                'env_value' => $relations,
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
                $this->translate('Failed to save the Icinga Notifications configuration. Please check the log.'),
                previous: $e
            );
        }
    }

    private function lockNotifications(string $message): static
    {
        $this->notificationsLocked = true;
        if (! in_array($message, $this->notificationsLockReasons, true)) {
            $this->notificationsLockReasons[] = $message;
        }

        return $this;
    }

    private function lockConfigKey(string $key, string $message): static
    {
        if (! in_array($message, $this->configKeyLockReasons[$key] ?? [], true)) {
            $this->configKeyLockReasons[$key][] = $message;
        }

        return $this;
    }

    /**
     * @param string[] $messages
     */
    private function createLockReasons(array $messages, ?string $title = null): Callout
    {
        return new Callout(
            CalloutType::Info,
            count($messages) === 1
                ? $messages[0]
                : HtmlElement::create(
                    'ul',
                    ['class' => 'lock-reasons'],
                    array_map(
                        fn(string $message) => HtmlElement::create('li', null, $message),
                        $messages
                    )
                ),
            $title
        );
    }

    /**
     * @param array<Term> $terms
     */
    private function validateAndEnrichRelations(array $terms): void
    {
        $knownRelations = static::knownRelations();

        foreach ($terms as $term) {
            if (isset($knownRelations[$term->getSearchValue()])) {
                $term->setLabel($knownRelations[$term->getSearchValue()]);
            } else {
                $term->setMessage($this->translate('Is not a supported relation'));
            }
        }
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
