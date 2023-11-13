<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Tests\Icinga\Module\Icingadb\Clicommands;

use Icinga\Application\Cli;
use Icinga\Application\Config;
use Icinga\Application\Modules\Manager;
use Icinga\Cli\Params;
use Icinga\Data\ConfigObject;
use Icinga\Exception\IcingaException;
use Icinga\Exception\MissingParameterException;
use Icinga\File\Storage\TemporaryLocalFileStorage;
use Icinga\Module\Icingadb\Clicommands\MigrateCommand;
use PHPUnit\Framework\TestCase;

class MigrateCommandTest extends TestCase
{
    protected $config = [
        'dashboards' => [
            'initial' => [
                'hosts' => [
                    'title' => 'Hosts'
                ],
                'hosts.problems' => [
                    'title' => 'Host Problems',
                    'url'   => 'monitoring/list/hosts?host_problem=1'
                ],
                'hosts.group_members' => [
                    'title' => 'Group Members',
                    'url'   => 'monitoring/list/hosts?hostgroup_name=group1|hostgroup_name=%28group2%29'
                ],
                'hosts.variables' => [
                    'title' => 'Host Variables',
                    'url'   => 'monitoring/list/hosts?(_host_foo=bar&_host_bar=foo)|_host_rab=oof'
                ],
                'hosts.wildcards' => [
                    'title' => 'Host Wildcards',
                    'url'   => 'monitoring/list/hosts?host_name=%2Afoo%2A|host_name=%2Abar%2A'
                        . '&sort=host_severity&dir=asc&limit=25'
                ],
                'hosts.encoded_params' => [
                    'title' => 'Host Encoded Params',
                    'url'   => 'monitoring/list/hosts?host_name=%28foo%29&sort=_host_%28foo%29'
                ],
                'icingadb' => [
                    'title' => 'Icinga DB'
                ],
                'icingadb.no-wildcards' => [
                    'title' => 'No Wildcards',
                    'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name=linux-hosts'
                ],
                'icingadb.wildcards' => [
                    'title' => 'Wildcards',
                    'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name=%2Alinux%2A'
                ],
                'icingadb.also-wildcards' => [
                    'title' => 'Also Wildcards',
                    'url'   => 'icingadb/hosts?host.name=%2Afoo%2A'
                ],
                'icingadb.with-sort-and-limit' => [
                    'title' => 'With Sort And Limit',
                    'url'   => 'icingadb/hosts?host.name=%2Afoo%2A|host.name=bar&sort=host.state.severity&limit=50'
                ],
                'not-monitoring-or-icingadb' => [
                    'title' => 'Not Monitoring Or Icinga DB'
                ],
                'not-monitoring-or-icingadb.something' => [
                    'title' => 'Something',
                    'url'   => 'somewhere/something?foo=%2Abar%2A'
                ]
            ],
            'expected' => [
                'hosts' => [
                    'title' => 'Hosts'
                ],
                'hosts.problems' => [
                    'title' => 'Host Problems',
                    'url'   => 'icingadb/hosts?host.state.is_problem=y'
                ],
                'hosts.group_members' => [
                    'title' => 'Group Members',
                    'url'   => 'icingadb/hosts?hostgroup.name=group1|hostgroup.name=%28group2%29'
                ],
                'hosts.variables' => [
                    'title' => 'Host Variables',
                    'url'   => 'icingadb/hosts?(host.vars.foo=bar&host.vars.bar=foo)|host.vars.rab=oof'
                ],
                'hosts.wildcards' => [
                    'title' => 'Host Wildcards',
                    'url'   => 'icingadb/hosts?host.name~%2Afoo%2A|host.name~%2Abar%2A'
                        . '&sort=host.state.severity%20asc&limit=25'
                ],
                'hosts.encoded_params' => [
                    'title' => 'Host Encoded Params',
                    'url'   => 'icingadb/hosts?host.name=%28foo%29&sort=host.vars.%28foo%29'
                ],
                'icingadb' => [
                    'title' => 'Icinga DB'
                ],
                'icingadb.no-wildcards' => [
                    'title' => 'No Wildcards',
                    'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name=linux-hosts'
                ],
                'icingadb.wildcards' => [
                    'title' => 'Wildcards',
                    'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name~%2Alinux%2A'
                ],
                'icingadb.also-wildcards' => [
                    'title' => 'Also Wildcards',
                    'url'   => 'icingadb/hosts?host.name~%2Afoo%2A'
                ],
                'icingadb.with-sort-and-limit' => [
                    'title' => 'With Sort And Limit',
                    'url'   => 'icingadb/hosts?host.name~%2Afoo%2A|host.name=bar&sort=host.state.severity&limit=50'
                ],
                'not-monitoring-or-icingadb' => [
                    'title' => 'Not Monitoring Or Icinga DB'
                ],
                'not-monitoring-or-icingadb.something' => [
                    'title' => 'Something',
                    'url'   => 'somewhere/something?foo=%2Abar%2A'
                ]
            ]
        ],
        'menu-items' => [
            'initial' => [
                'foreign-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'example.com?q=foo'
                ],
                'monitoring-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'monitoring/list/hosts?host_problem=1'
                ],
                'icingadb-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'icingadb/hosts?host.name=%2Afoo%2A'
                ]
            ],
            'expected' => [
                'foreign-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'example.com?q=foo'
                ],
                'monitoring-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'icingadb/hosts?host.state.is_problem=y'
                ],
                'icingadb-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'icingadb/hosts?host.name~%2Afoo%2A'
                ]
            ]
        ],
        'shared-menu-items' => [
            'initial' => [
                'foreign-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'example.com?q=foo',
                    'owner'     => 'test'
                ],
                'monitoring-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'monitoring/list/hosts?host_problem=1',
                    'owner'     => 'test'
                ],
                'icingadb-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'icingadb/hosts?host.name=%2Afoo%2A',
                    'owner'     => 'test'
                ],
                'other-monitoring-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'monitoring/list/hosts?host_problem=1',
                    'owner'     => 'not-test'
                ]
            ],
            'expected' => [
                'foreign-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'example.com?q=foo',
                    'owner'     => 'test'
                ],
                'monitoring-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'icingadb/hosts?host.state.is_problem=y',
                    'owner'     => 'test'
                ],
                'icingadb-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'icingadb/hosts?host.name~%2Afoo%2A',
                    'owner'     => 'test'
                ],
                'other-monitoring-url' => [
                    'type'      => 'menu-item',
                    'target'    => '_blank',
                    'url'       => 'monitoring/list/hosts?host_problem=1',
                    'owner'     => 'not-test'
                ]
            ]
        ],
        'host-actions' => [
            'initial' => [
                'hosts' => [
                    'type'      => 'host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host_name=%2Afoo%2A'
                ],
                'hosts_encoded_params' => [
                    'type'      => 'host-action',
                    'url'       => 'monitoring/list/hosts?host_name=%28foo%29&sort=_host_%28foo%29',
                    'filter'    => '_host_%28foo%29=bar'
                ]
            ],
            'expected' => [
                'hosts' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host.name~%2Afoo%2A'
                ],
                'hosts_encoded_params' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'icingadb/hosts?host.name=%28foo%29&sort=host.vars.%28foo%29',
                    'filter'    => 'host.vars.%28foo%29=bar'
                ]
            ]
        ],
        'icingadb-host-actions' => [
            'initial' => [
                'hosts' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host.name=%2Afoo%2A'
                ]
            ],
            'expected' => [
                'hosts' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host.name~%2Afoo%2A'
                ]
            ]
        ],
        'service-actions' => [
            'initial' => [
                'services' => [
                    'type'      => 'service-action',
                    'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                    'filter'    => '_service_foo=bar&_service_bar=%2Afoo%2A'
                ],
                'services_encoded_params' => [
                    'type'      => 'host-action',
                    'url'       => 'monitoring/list/services?host_name=%28foo%29&sort=_host_%28foo%29',
                    'filter'    => '_host_%28foo%29=bar'
                ]
            ],
            'expected' => [
                'services' => [
                    'type'      => 'icingadb-service-action',
                    'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                    'filter'    => 'service.vars.foo=bar&service.vars.bar~%2Afoo%2A'
                ],
                'services_encoded_params' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'icingadb/services?host.name=%28foo%29&sort=host.vars.%28foo%29',
                    'filter'    => 'host.vars.%28foo%29=bar'
                ]
            ]
        ],
        'icingadb-service-actions' => [
            'initial' => [
                'services' => [
                    'type'      => 'icingadb-service-action',
                    'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                    'filter'    => 'service.vars.foo=%2Abar%2A'
                ]
            ],
            'expected' => [
                'services' => [
                    'type'      => 'icingadb-service-action',
                    'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                    'filter'    => 'service.vars.foo~%2Abar%2A'
                ]
            ]
        ],
        'shared-host-actions' => [
            'initial' => [
                'shared-hosts' => [
                    'type'      => 'host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host_name=%2Afoo%2A',
                    'owner'     => 'test'
                ],
                'hosts_encoded_params' => [
                    'type'      => 'host-action',
                    'url'       => 'monitoring/list/hosts?host_name=%28foo%29&sort=_host_%28foo%29',
                    'filter'    => '_host_%28foo%29=bar',
                    'owner'     => 'test'
                ],
                'other-hosts' => [
                    'type'      => 'host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host_name=%2Afoo%2A',
                    'owner'     => 'not-test'
                ]
            ],
            'expected' => [
                'shared-hosts' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'example.com/search?q=$host.name$',
                    'filter'    => 'host.name~%2Afoo%2A',
                    'owner'     => 'test'
                ],
                'hosts_encoded_params' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'icingadb/hosts?host.name=%28foo%29&sort=host.vars.%28foo%29',
                    'filter'    => 'host.vars.%28foo%29=bar',
                    'owner'     => 'test'
                ]
            ]
        ],
        'host-actions-legacy-macros' => [
            'initial' => [
                'hosts' => [
                    'type'      => 'host-action',
                    'url'       => 'example.com/search?q=$HOSTNAME$,$HOSTADDRESS$,$HOSTADDRESS6$',
                    'filter'    => 'host_name=%2Afoo%2A'
                ]
            ],
            'expected' => [
                'hosts' => [
                    'type'      => 'icingadb-host-action',
                    'url'       => 'example.com/search?q=$host.name$,$host.address$,$host.address6$',
                    'filter'    => 'host.name~%2Afoo%2A'
                ]
            ]
        ],
        'service-actions-legacy-macros' => [
            'initial' => [
                'services' => [
                    'type'      => 'service-action',
                    'url'       => 'example.com/search?q=$SERVICEDESC$,$HOSTNAME$,$HOSTADDRESS$,$HOSTADDRESS6$',
                    'filter'    => '_service_foo=bar&_service_bar=%2Afoo%2A'
                ]
            ],
            'expected' => [
                'services' => [
                    'type'      => 'icingadb-service-action',
                    'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                    'filter'    => 'service.vars.foo=bar&service.vars.bar~%2Afoo%2A'
                ]
            ]
        ],
        'all-roles' => [
            'initial' => [
                'no-wildcards' => [
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
                ],
                'wildcards' => [
                    'monitoring/filter/objects' => 'host_name=%2Afoo%2A|hostgroup_name=%2Afoo%2A'
                ],
                'encoded_column' => [
                    'monitoring/filter/objects' => '_host_%28foo%29=bar'
                ],
                'blacklist' => [
                    'monitoring/blacklist/properties'   => 'host.vars.foo,service.vars.bar*,host.vars.a.**.d'
                ],
                'full-access' => [
                    'permissions'   => 'module/monitoring,monitoring/*'
                ],
                'general-read-access' => [
                    'permissions'   => 'module/monitoring'
                ],
                'general-write-access' => [
                    'permissions'   => 'module/monitoring,monitoring/command/*'
                ],
                'full-fine-grained-access' => [
                    'permissions'   => 'module/monitoring'
                        . ',monitoring/command/schedule-check'
                        . ',monitoring/command/acknowledge-problem'
                        . ',monitoring/command/remove-acknowledgement'
                        . ',monitoring/command/comment/add'
                        . ',monitoring/command/comment/delete'
                        . ',monitoring/command/downtime/schedule'
                        . ',monitoring/command/downtime/delete'
                        . ',monitoring/command/process-check-result'
                        . ',monitoring/command/feature/instance'
                        . ',monitoring/command/feature/object/active-checks'
                        . ',monitoring/command/feature/object/passive-checks'
                        . ',monitoring/command/feature/object/notifications'
                        . ',monitoring/command/feature/object/event-handler'
                        . ',monitoring/command/feature/object/flap-detection'
                        . ',monitoring/command/send-custom-notification'
                ],
                'full-with-refusals' => [
                    'permissions'   => 'module/monitoring,monitoring/command/*',
                    'refusals'      => 'monitoring/command/downtime/*,monitoring/command/feature/instance'
                ],
                'active-only' => [
                    'permissions'   => 'module/monitoring,monitoring/command/schedule-check/active-only'
                ],
                'no-monitoring-contacts' => [
                    'permissions'   => 'module/monitoring,no-monitoring/contacts'
                ],
                'reporting-only' => [
                    'permissions'   => 'module/reporting'
                ],
                'icingadb' => [
                    'icingadb/filter/objects'   => 'host.name=%2Afoo%2A|hostgroup.name=%2Afoo%2A',
                    'icingadb/filter/services'  => 'service.name=%2Abar%2A&service.vars.env=prod',
                    'icingadb/filter/hosts'     => 'host.vars.env=%2Afoo%2A'
                ]
            ],
            'expected' => [
                'no-wildcards' => [
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo',
                    'icingadb/filter/objects'   => 'host.name=foo|hostgroup.name=foo'
                ],
                'wildcards' => [
                    'monitoring/filter/objects' => 'host_name=%2Afoo%2A|hostgroup_name=%2Afoo%2A',
                    'icingadb/filter/objects'   => 'host.name~%2Afoo%2A|hostgroup.name~%2Afoo%2A'
                ],
                'encoded_column' => [
                    'monitoring/filter/objects' => '_host_%28foo%29=bar',
                    'icingadb/filter/objects' => 'host.vars.%28foo%29=bar'
                ],
                'blacklist' => [
                    'monitoring/blacklist/properties'   => 'host.vars.foo,service.vars.bar*,host.vars.a.**.d',
                    'icingadb/denylist/variables'       => 'foo,bar*,a.*.d'
                ],
                'full-access' => [
                    'permissions'   => 'module/monitoring,monitoring/*'
                ],
                'general-read-access' => [
                    'permissions'   => 'module/monitoring'
                ],
                'general-write-access' => [
                    'permissions'   => 'module/monitoring,monitoring/command/*,icingadb/command/*'
                ],
                'full-fine-grained-access' => [
                    'permissions'   => 'module/monitoring'
                        . ',monitoring/command/schedule-check'
                        . ',icingadb/command/schedule-check'
                        . ',monitoring/command/acknowledge-problem'
                        . ',icingadb/command/acknowledge-problem'
                        . ',monitoring/command/remove-acknowledgement'
                        . ',icingadb/command/remove-acknowledgement'
                        . ',monitoring/command/comment/add'
                        . ',icingadb/command/comment/add'
                        . ',monitoring/command/comment/delete'
                        . ',icingadb/command/comment/delete'
                        . ',monitoring/command/downtime/schedule'
                        . ',icingadb/command/downtime/schedule'
                        . ',monitoring/command/downtime/delete'
                        . ',icingadb/command/downtime/delete'
                        . ',monitoring/command/process-check-result'
                        . ',icingadb/command/process-check-result'
                        . ',monitoring/command/feature/instance'
                        . ',icingadb/command/feature/instance'
                        . ',monitoring/command/feature/object/active-checks'
                        . ',icingadb/command/feature/object/active-checks'
                        . ',monitoring/command/feature/object/passive-checks'
                        . ',icingadb/command/feature/object/passive-checks'
                        . ',monitoring/command/feature/object/notifications'
                        . ',icingadb/command/feature/object/notifications'
                        . ',monitoring/command/feature/object/event-handler'
                        . ',icingadb/command/feature/object/event-handler'
                        . ',monitoring/command/feature/object/flap-detection'
                        . ',icingadb/command/feature/object/flap-detection'
                        . ',monitoring/command/send-custom-notification'
                        . ',icingadb/command/send-custom-notification'
                ],
                'full-with-refusals' => [
                    'permissions'   => 'module/monitoring,monitoring/command/*,icingadb/command/*',
                    'refusals'      => 'monitoring/command/downtime/*'
                        . ',icingadb/command/downtime/*'
                        . ',monitoring/command/feature/instance'
                        . ',icingadb/command/feature/instance'
                ],
                'active-only' => [
                    'permissions'   => 'module/monitoring'
                        . ',monitoring/command/schedule-check/active-only'
                        . ',icingadb/command/schedule-check/active-only'
                ],
                'no-monitoring-contacts' => [
                    'permissions'               => 'module/monitoring,no-monitoring/contacts',
                    'icingadb/denylist/routes'  => 'users,usergroups'
                ],
                'reporting-only' => [
                    'permissions'   => 'module/reporting'
                ],
                'icingadb' => [
                    'icingadb/filter/objects'   => 'host.name~%2Afoo%2A|hostgroup.name~%2Afoo%2A',
                    'icingadb/filter/services'  => 'service.name~%2Abar%2A&service.vars.env=prod',
                    'icingadb/filter/hosts'     => 'host.vars.env~%2Afoo%2A'
                ]
            ]
        ],
        'single-role-or-group' => [
            'initial' => [
                'one' => [
                    'groups'                    => 'support,helpdesk',
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
                ],
                'two' => [
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
                ],
                'three' => [
                    'icingadb/filter/objects'   => 'host.name=%2Afoo%2A'
                ]
            ],
            'expected' => [
                'one' => [
                    'groups'                    => 'support,helpdesk',
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo',
                    'icingadb/filter/objects'   => 'host.name=foo|hostgroup.name=foo'
                ],
                'two' => [
                    'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
                ],
                'three' => [
                    'icingadb/filter/objects'   => 'host.name=%2Afoo%2A'
                ]
            ]
        ]
    ];

    protected $defaultConfigDir;

    protected $fileStorage;

    protected function setUp(): void
    {
        $this->defaultConfigDir = Config::$configDir;
        $this->fileStorage = new TemporaryLocalFileStorage();

        Config::$configDir = dirname($this->fileStorage->resolvePath('bogus'));
    }

    protected function tearDown(): void
    {
        Config::$configDir = $this->defaultConfigDir;
        unset($this->fileStorage); // Should clean up automatically
        Config::module('monitoring', 'config', true);
    }

    protected function getConfig(string $case): array
    {
        return [$this->config[$case]['initial'], $this->config[$case]['expected']];
    }

    protected function createConfig(string $path, array $data): void
    {
        $config = new Config(new ConfigObject($data));
        $config->saveIni($this->fileStorage->resolvePath($path));
    }

    protected function loadConfig(string $path): array
    {
        return Config::fromIni($this->fileStorage->resolvePath($path))->toArray();
    }

    protected function createCommandInstance(string ...$params): MigrateCommand
    {
        array_unshift($params, 'program');

        $app = $this->createConfiguredMock(Cli::class, [
            'getParams' => new Params($params),
            'getModuleManager' => $this->createConfiguredMock(Manager::class, [
                'loadEnabledModules' => null
            ])
        ]);

        return new MigrateCommand(
            $app,
            'migrate',
            'toicingadb',
            'dashboard',
            false
        );
    }

    /**
     * Checks the following:
     * - Whether only a single user is handled
     * - Whether backups are made
     * - Whether a second run changes nothing, if nothing changed
     * - Whether a second run keeps the backup, if nothing changed
     * - Whether a new backup isn't made, if nothing changed
     * - Whether existing Icinga DB dashboards are transformed regarding wildcard filters
     */
    public function testDashboardMigrationBehavesAsExpectedByDefault()
    {
        [$initialConfig, $expected] = $this->getConfig('dashboards');

        $this->createConfig('dashboards/test/dashboard.ini', $initialConfig);
        $this->createConfig('dashboards/test2/dashboard.ini', $initialConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->dashboardAction();

        $config = $this->loadConfig('dashboards/test/dashboard.ini');
        $this->assertSame($expected, $config);

        $config2 = $this->loadConfig('dashboards/test2/dashboard.ini');
        $this->assertSame($initialConfig, $config2);

        $backup = $this->loadConfig('dashboards/test/dashboard.backup.ini');
        $this->assertSame($initialConfig, $backup);

        $command = $this->createCommandInstance('--user', 'test');
        $command->dashboardAction();

        $configAfterSecondRun = $this->loadConfig('dashboards/test/dashboard.ini');
        $this->assertSame($config, $configAfterSecondRun);

        $backupAfterSecondRun = $this->loadConfig('dashboards/test/dashboard.backup.ini');
        $this->assertSame($backup, $backupAfterSecondRun);

        $backup1AfterSecondRun = $this->loadConfig('dashboards/test/dashboard.backup1.ini');
        $this->assertEmpty($backup1AfterSecondRun);
    }

    /**
     * Checks the following:
     * - Whether a second run creates a new backup, if something changed
     */
    public function testDashboardMigrationCreatesMultipleBackups()
    {
        $initialOldConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'monitoring/list/hosts?host_problem=1'
            ]
        ];
        $initialNewConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'icingadb/hosts?host.state.is_problem=y'
            ],
            'hosts.group_members' => [
                'title' => 'Group Members',
                'url'   => 'monitoring/list/hosts?hostgroup_name=group1|hostgroup_name=group2'
            ]
        ];
        $expectedNewConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'icingadb/hosts?host.state.is_problem=y'
            ]
        ];
        $expectedFinalConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'icingadb/hosts?host.state.is_problem=y'
            ],
            'hosts.group_members' => [
                'title' => 'Group Members',
                'url'   => 'icingadb/hosts?hostgroup.name=group1|hostgroup.name=group2'
            ]
        ];

        $this->createConfig('dashboards/test/dashboard.ini', $initialOldConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->dashboardAction();

        $newConfig = $this->loadConfig('dashboards/test/dashboard.ini');
        $this->assertSame($expectedNewConfig, $newConfig);
        $oldBackup = $this->loadConfig('dashboards/test/dashboard.backup.ini');
        $this->assertSame($initialOldConfig, $oldBackup);

        $this->createConfig('dashboards/test/dashboard.ini', $initialNewConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->dashboardAction();

        $finalConfig = $this->loadConfig('dashboards/test/dashboard.ini');
        $this->assertSame($expectedFinalConfig, $finalConfig);
        $newBackup = $this->loadConfig('dashboards/test/dashboard.backup1.ini');
        $this->assertSame($initialNewConfig, $newBackup);
    }

    /**
     * Checks the following:
     * - Whether backups are skipped
     *
     * @depends testDashboardMigrationBehavesAsExpectedByDefault
     */
    public function testDashboardMigrationSkipsBackupIfRequested()
    {
        [$initialConfig, $expected] = $this->getConfig('dashboards');

        $this->createConfig('dashboards/test/dashboard.ini', $initialConfig);

        $command = $this->createCommandInstance('--user', 'test', '--no-backup');
        $command->dashboardAction();

        $config = $this->loadConfig('dashboards/test/dashboard.ini');
        $this->assertSame($expected, $config);

        $backup = $this->loadConfig('dashboards/test/dashboard.backup.ini');
        $this->assertEmpty($backup);
    }

    /**
     * Checks the following:
     * - Whether multiple users are handled
     * - Whether multiple backups are made
     *
     * @depends testDashboardMigrationBehavesAsExpectedByDefault
     */
    public function testDashboardMigrationMigratesAllUsers()
    {
        [$initialConfig, $expected] = $this->getConfig('dashboards');

        $users = ['foo', 'bar', 'raboof'];

        foreach ($users as $user) {
            $this->createConfig("dashboards/$user/dashboard.ini", $initialConfig);
        }

        $command = $this->createCommandInstance('--user', '*');
        $command->dashboardAction();

        foreach ($users as $user) {
            $config = $this->loadConfig("dashboards/$user/dashboard.ini");
            $this->assertSame($expected, $config);

            $backup = $this->loadConfig("dashboards/$user/dashboard.backup.ini");
            $this->assertSame($initialConfig, $backup);
        }
    }

    public function testDashboardMigrationExpectsUserSwitch()
    {
        $this->expectException(MissingParameterException::class);
        $this->expectExceptionMessage('Required parameter \'user\' missing');

        $command = $this->createCommandInstance();
        $command->dashboardAction();
    }

    /**
     * Checks the following:
     * - Whether only a single user is handled
     * - Whether shared items are migrated, depending on the owner
     * - Whether old configs are kept/or backups are created
     * - Whether a second run changes nothing, if nothing changed
     * - Whether a second run keeps the backup, if nothing changed
     * - Whether a new backup isn't created, if nothing changed
     */
    public function testNavigationMigrationBehavesAsExpectedByDefault()
    {
        [$initialMenuConfig, $expectedMenu] = $this->getConfig('menu-items');
        [$initialHostConfig, $expectedHosts] = $this->getConfig('host-actions');
        [$initialServiceConfig, $expectedServices] = $this->getConfig('service-actions');

        $this->createConfig('preferences/test/menu.ini', $initialMenuConfig);
        $this->createConfig('preferences/test/host-actions.ini', $initialHostConfig);
        $this->createConfig('preferences/test/service-actions.ini', $initialServiceConfig);
        $this->createConfig('preferences/test2/menu.ini', $initialMenuConfig);
        $this->createConfig('preferences/test2/host-actions.ini', $initialHostConfig);
        $this->createConfig('preferences/test2/service-actions.ini', $initialServiceConfig);

        [$initialSharedMenuConfig, $expectedSharedMenu] = $this->getConfig('shared-menu-items');
        $this->createConfig('navigation/menu.ini', $initialSharedMenuConfig);

        [$initialSharedConfig, $expectedShared] = $this->getConfig('shared-host-actions');
        $this->createConfig('navigation/host-actions.ini', $initialSharedConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $menuConfig = $this->loadConfig('preferences/test/menu.ini');
        $this->assertSame($expectedMenu, $menuConfig);

        $sharedMenuConfig = $this->loadConfig('navigation/menu.ini');
        $this->assertSame($expectedSharedMenu, $sharedMenuConfig);

        $menuConfig2 = $this->loadConfig('preferences/test2/menu.ini');
        $this->assertSame($initialMenuConfig, $menuConfig2);

        $menuBackup = $this->loadConfig('preferences/test/menu.backup.ini');
        $this->assertSame($initialMenuConfig, $menuBackup);

        $hosts = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $services = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($expectedHosts, $hosts);
        $this->assertSame($expectedServices, $services);

        $sharedConfig = $this->loadConfig('navigation/icingadb-host-actions.ini');
        $this->assertSame($expectedShared, $sharedConfig);

        $hosts2 = $this->loadConfig('preferences/test2/icingadb-host-actions.ini');
        $services2 = $this->loadConfig('preferences/test2/icingadb-service-actions.ini');
        $this->assertEmpty($hosts2);
        $this->assertEmpty($services2);

        $oldHosts = $this->loadConfig('preferences/test/host-actions.ini');
        $oldServices = $this->loadConfig('preferences/test/service-actions.ini');
        $this->assertSame($initialHostConfig, $oldHosts);
        $this->assertSame($initialServiceConfig, $oldServices);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $menuConfigAfterSecondRun = $this->loadConfig('preferences/test/menu.ini');
        $this->assertSame($menuConfig, $menuConfigAfterSecondRun);

        $menuBackupAfterSecondRun = $this->loadConfig('preferences/test/menu.backup.ini');
        $this->assertSame($menuBackup, $menuBackupAfterSecondRun);

        $menuBackup1AfterSecondRun = $this->loadConfig('preferences/test/menu.backup1.ini');
        $this->assertEmpty($menuBackup1AfterSecondRun);

        $hostsAfterSecondRun = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $servicesAfterSecondRun = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($hosts, $hostsAfterSecondRun);
        $this->assertSame($services, $servicesAfterSecondRun);
    }

    /**
     * Checks the following:
     * - Whether a second run creates a new backup, if something changed
     *
     * @depends testNavigationMigrationBehavesAsExpectedByDefault
     */
    public function testNavigationMigrationCreatesMultipleBackups()
    {
        $initialOldConfig = [
            'hosts' => [
                'title' => 'Host Problems',
                'url'   => 'monitoring/list/hosts?host_problem=1'
            ]
        ];
        $initialNewConfig = [
            'hosts' => [
                'title' => 'Host Problems',
                'url'   => 'icingadb/hosts?host.state.is_problem=y'
            ],
            'group_members' => [
                'title' => 'Group Members',
                'url'   => 'monitoring/list/hosts?hostgroup_name=group1|hostgroup_name=group2'
            ]
        ];
        $expectedNewConfig = [
            'hosts' => [
                'title' => 'Host Problems',
                'url'   => 'icingadb/hosts?host.state.is_problem=y'
            ]
        ];
        $expectedFinalConfig = [
            'hosts' => [
                'title' => 'Host Problems',
                'url'   => 'icingadb/hosts?host.state.is_problem=y'
            ],
            'group_members' => [
                'title' => 'Group Members',
                'url'   => 'icingadb/hosts?hostgroup.name=group1|hostgroup.name=group2'
            ]
        ];

        $this->createConfig('preferences/test/menu.ini', $initialOldConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $newConfig = $this->loadConfig('preferences/test/menu.ini');
        $this->assertSame($expectedNewConfig, $newConfig);
        $oldBackup = $this->loadConfig('preferences/test/menu.backup.ini');
        $this->assertSame($initialOldConfig, $oldBackup);

        $this->createConfig('preferences/test/menu.ini', $initialNewConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $finalConfig = $this->loadConfig('preferences/test/menu.ini');
        $this->assertSame($expectedFinalConfig, $finalConfig);
        $newBackup = $this->loadConfig('preferences/test/menu.backup1.ini');
        $this->assertSame($initialNewConfig, $newBackup);
    }

    /**
     * Checks the following:
     * - Whether backups are skipped
     *
     * @depends testNavigationMigrationBehavesAsExpectedByDefault
     */
    public function testNavigationMigrationSkipsBackupIfRequested()
    {
        [$initialConfig, $expected] = $this->getConfig('menu-items');

        $this->createConfig('preferences/test/menu.ini', $initialConfig);

        $command = $this->createCommandInstance('--user', 'test', '--no-backup');
        $command->navigationAction();

        $config = $this->loadConfig('preferences/test/menu.ini');
        $this->assertSame($expected, $config);

        $backup = $this->loadConfig('preferences/test/menu.backup.ini');
        $this->assertEmpty($backup);
    }

    /**
     * Checks the following:
     * - Whether existing Icinga DB Actions are transformed regarding wildcard filters
     */
    public function testNavigationMigrationTransformsAlreadyExistingIcingaDBActions()
    {
        [$initialHostConfig, $expectedHosts] = $this->getConfig('icingadb-host-actions');
        [$initialServiceConfig, $expectedServices] = $this->getConfig('icingadb-service-actions');

        $this->createConfig('preferences/test/icingadb-host-actions.ini', $initialHostConfig);
        $this->createConfig('preferences/test/icingadb-service-actions.ini', $initialServiceConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $hosts = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $services = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($expectedHosts, $hosts);
        $this->assertSame($expectedServices, $services);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $hostsAfterSecondRun = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $servicesAfterSecondRun = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($hosts, $hostsAfterSecondRun);
        $this->assertSame($services, $servicesAfterSecondRun);
    }

    /**
     * Checks the following:
     * - Whether legacy host/service macros are migrated
     */
    public function testNavigationMigrationMigratesLegacyMacros()
    {
        [$initialHostConfig, $expectedHosts] = $this->getConfig('host-actions-legacy-macros');
        [$initialServiceConfig, $expectedServices] = $this->getConfig('service-actions-legacy-macros');

        $this->createConfig('preferences/test/host-actions.ini', $initialHostConfig);
        $this->createConfig('preferences/test/service-actions.ini', $initialServiceConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $hosts = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $services = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($expectedHosts, $hosts);
        $this->assertSame($expectedServices, $services);
    }

    /**
     * Checks the following:
     * - Whether old configs are removed
     */
    public function testNavigationMigrationDeletesOldConfigsIfRequested()
    {
        [$initialHostConfig, $expectedHosts] = $this->getConfig('host-actions');
        [$initialServiceConfig, $expectedServices] = $this->getConfig('service-actions');

        $this->createConfig('preferences/test/host-actions.ini', $initialHostConfig);
        $this->createConfig('preferences/test/service-actions.ini', $initialServiceConfig);

        $command = $this->createCommandInstance('--user', 'test', '--no-backup');
        $command->navigationAction();

        $hosts = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $services = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $this->assertSame($expectedHosts, $hosts);
        $this->assertSame($expectedServices, $services);

        $oldHosts = $this->loadConfig('preferences/test/host-actions.ini');
        $oldServices = $this->loadConfig('preferences/test/service-actions.ini');
        $this->assertEmpty($oldHosts);
        $this->assertEmpty($oldServices);
    }

    /**
     * Checks the following:
     * - Whether existing configs are left alone by default
     * - Whether existing configs are overridden if requested
     */
    public function testNavigationMigrationOverridesExistingActionsIfRequested()
    {
        $initialOldUserConfig = [
            'hosts' => [
                'type'      => 'host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host_name=%2Afoo%2A'
            ]
        ];
        $initialOldSharedConfig = [
            'hosts' => [
                'type'      => 'host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host_name=%2Afoo%2A',
                'owner'     => 'test'
            ]
        ];
        $initialNewUserConfig = [
            'hosts' => [
                'type'      => 'icingadb-host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host.name~%2Abar%2A'
            ]
        ];
        $initialNewSharedConfig = [
            'hosts' => [
                'type'      => 'icingadb-host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host.name~%2Abar%2A',
                'owner'     => 'test'
            ]
        ];
        $expectedFinalUserConfig = [
            'hosts' => [
                'type'      => 'icingadb-host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host.name~%2Afoo%2A'
            ]
        ];
        $expectedFinalSharedConfig = [
            'hosts' => [
                'type'      => 'icingadb-host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host.name~%2Afoo%2A',
                'owner'     => 'test'
            ]
        ];

        $this->createConfig('preferences/test/host-actions.ini', $initialOldUserConfig);
        $this->createConfig('preferences/test/icingadb-host-actions.ini', $initialNewUserConfig);
        $this->createConfig('navigation/host-actions.ini', $initialOldSharedConfig);
        $this->createConfig('navigation/icingadb-host-actions.ini', $initialNewSharedConfig);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();

        $finalUserConfig = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $this->assertSame($initialNewUserConfig, $finalUserConfig);

        $finalSharedConfig = $this->loadConfig('navigation/icingadb-host-actions.ini');
        $this->assertSame($initialNewSharedConfig, $finalSharedConfig);

        $command = $this->createCommandInstance('--user', 'test', '--override');
        $command->navigationAction();

        $finalUserConfig = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $this->assertSame($expectedFinalUserConfig, $finalUserConfig);

        $finalSharedConfig = $this->loadConfig('navigation/icingadb-host-actions.ini');
        $this->assertSame($expectedFinalSharedConfig, $finalSharedConfig);
    }

    public function testNavigationMigrationExpectsUserSwitch()
    {
        $this->expectException(MissingParameterException::class);
        $this->expectExceptionMessage('Required parameter \'user\' missing');

        $command = $this->createCommandInstance();
        $command->navigationAction();
    }

    /**
     * Checks the following:
     * - Whether only a single role is handled
     * - Whether role name matching works
     */
    public function testRoleMigrationHandlesASingleRoleOnlyIfRequested()
    {
        [$initialConfig, $expected] = $this->getConfig('single-role-or-group');

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--role', 'one');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expected, $config);
    }

    /**
     * Checks the following:
     * - Whether only a single role is handled
     * - Whether group matching works
     */
    public function testRoleMigrationHandlesARoleWithMatchingGroups()
    {
        [$initialConfig, $expected] = $this->getConfig('single-role-or-group');

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--group', 'support');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expected, $config);
    }

    /**
     * Checks the following:
     * - Whether permissions are properly migrated
     * - Whether refusals are properly migrated
     * - Whether restrictions are properly migrated
     * - Whether blacklists are properly migrated
     * - Whether backups are created
     * - Whether a second run changes nothing, if nothing changed
     * - Whether a second run keeps the backup, if nothing changed
     * - Whether a new backup isn't created, if nothing changed
     */
    public function testRoleMigrationMigratesAllRoles()
    {
        [$initialConfig, $expected] = $this->getConfig('all-roles');

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--role', '*');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expected, $config);

        $backup = $this->loadConfig('roles.backup.ini');
        $this->assertSame($initialConfig, $backup);

        $command = $this->createCommandInstance('--role', '*');
        $command->roleAction();

        $configAfterSecondRun = $this->loadConfig('roles.ini');
        $this->assertSame($config, $configAfterSecondRun);

        $backupAfterSecondRun = $this->loadConfig('roles.backup.ini');
        $this->assertSame($backup, $backupAfterSecondRun);

        $backup2 = $this->loadConfig('roles.backup1.ini');
        $this->assertEmpty($backup2);
    }

    /**
     * Checks the following:
     * - Whether backups are skipped
     *
     * @depends testRoleMigrationMigratesAllRoles
     */
    public function testRoleMigrationSkipsBackupIfRequested()
    {
        [$initialConfig, $expected] = $this->getConfig('all-roles');

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--role', '*', '--no-backup');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expected, $config);

        $backup = $this->loadConfig('roles.backup.ini');
        $this->assertEmpty($backup);
    }

    /**
     * Checks the following:
     * - Whether monitoring's variable protection rules are migrated to all roles granting access to monitoring
     */
    public function testRoleMigrationAlsoMigratesVariableProtections()
    {
        $initialConfig = [
            'one' => [
                'permissions' => 'module/monitoring'
            ],
            'two' => [
                'permissions' => 'module/monitoring'
            ],
            'three' => [
                'permissions' => 'module/reporting'
            ]
        ];
        $expectedConfig = [
            'one' => [
                'permissions'                   => 'module/monitoring',
                'icingadb/protect/variables'    => 'ob.*,env'
            ],
            'two' => [
                'permissions'                   => 'module/monitoring',
                'icingadb/protect/variables'    => 'ob.*,env'
            ],
            'three' => [
                'permissions'                   => 'module/reporting'
            ]
        ];

        $this->createConfig('modules/monitoring/config.ini', [
            'security' => [
                'protected_customvars' => 'ob.*,env'
            ]
        ]);

        // Invalidate config cache
        Config::module('monitoring', 'config', true);

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--role', '*');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expectedConfig, $config);
    }

    /**
     * Checks the following:
     * - Whether already migrated roles are skipped during migration
     * - Whether already migrated roles are transformed regarding wildcard filters
     */
    public function testRoleMigrationSkipsRolesThatAlreadyGrantAccessToIcingaDbButTransformWildcardRestrictions()
    {
        $initialConfig = [
            'only-monitoring' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*',
                'monitoring/filter/objects' => 'host_name=%2Afoo%2A'
            ],
            'monitoring-and-icingadb' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*,module/icingadb',
                'monitoring/filter/objects' => 'host_name=%2Abar%2A',
                'icingadb/filter/objects' => 'host.name=%2Afoo%2A'
            ]
        ];
        $expectedConfig = [
            'only-monitoring' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*,icingadb/command/comment/*',
                'monitoring/filter/objects' => 'host_name=%2Afoo%2A',
                'icingadb/filter/objects' => 'host.name~%2Afoo%2A'
            ],
            'monitoring-and-icingadb' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*,module/icingadb',
                'monitoring/filter/objects' => 'host_name=%2Abar%2A',
                'icingadb/filter/objects' => 'host.name~%2Afoo%2A'
            ]
        ];

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--role', '*');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expectedConfig, $config);
    }

    /**
     * Checks the following:
     * - Whether already migrated roles are reset if requested
     */
    public function testRoleMigrationOverridesAlreadyMigratedRolesIfRequested()
    {
        $initialConfig = [
            'only-monitoring' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*',
                'monitoring/filter/objects' => 'host_name=%2Afoo%2A'
            ],
            'monitoring-and-icingadb' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*,module/icingadb',
                'monitoring/filter/objects' => 'host_name=%2Abar%2A',
                'icingadb/filter/objects' => 'host.name=%2Afoo%2A'
            ]
        ];
        $expectedConfig = [
            'only-monitoring' => [
                'permissions' => 'module/monitoring,monitoring/command/comment/*,icingadb/command/comment/*',
                'monitoring/filter/objects' => 'host_name=%2Afoo%2A',
                'icingadb/filter/objects' => 'host.name~%2Afoo%2A'
            ],
            'monitoring-and-icingadb' => [
                'permissions' => 'module/monitoring'
                    . ',monitoring/command/comment/*'
                    . ',icingadb/command/comment/*',
                'monitoring/filter/objects' => 'host_name=%2Abar%2A',
                'icingadb/filter/objects' => 'host.name~%2Abar%2A'
            ]
        ];

        $this->createConfig('roles.ini', $initialConfig);

        $command = $this->createCommandInstance('--role', '*', '--override');
        $command->roleAction();

        $config = $this->loadConfig('roles.ini');
        $this->assertSame($expectedConfig, $config);
    }

    public function testRoleMigrationExpectsTheRoleOrGroupSwitch()
    {
        $this->expectException(IcingaException::class);
        $this->expectExceptionMessage("One of the parameters 'group' or 'role' must be supplied");

        $command = $this->createCommandInstance();
        $command->roleAction();
    }

    public function testRoleMigrationExpectsEitherTheRoleOrGroupSwitchButNotBoth()
    {
        $this->expectException(IcingaException::class);
        $this->expectExceptionMessage("Use either 'group' or 'role'. Both cannot be used as role overrules group.");

        $command = $this->createCommandInstance('--role=foo', '--group=bar');
        $command->roleAction();
    }

    public function testFilterMigrationWorksAsExpected()
    {
        $initialHostActionConfig = [
            'hosts' => [
                'type'      => 'host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host_name=%2Afoo%2A'
            ]
        ];
        $expectedHostActionConfig = $initialHostActionConfig;

        $initialIcingadbHostActionConfig = [
            'hosts' => [
                'type'      => 'icingadb-host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host.name=%2Afoo%2A'
            ]
        ];
        $expectedIcingadbHostActionConfig = [
            'hosts' => [
                'type'      => 'icingadb-host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host.name~%2Afoo%2A'
            ]
        ];

        $initialServiceActionConfig = [
            'services' => [
                'type'      => 'service-action',
                'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                'filter'    => '_service_foo=bar&_service_bar=%2Afoo%2A'
            ]
        ];
        $expectedServiceActionConfig = $initialServiceActionConfig;

        $initialIcingadbServiceActionConfig = [
            'services' => [
                'type'      => 'icingadb-service-action',
                'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                'filter'    => 'service.vars.foo=bar&service.vars.bar=%2Afoo%2A'
            ]
        ];
        $expectedIcingadbServiceActionConfig = [
            'services' => [
                'type'      => 'icingadb-service-action',
                'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                'filter'    => 'service.vars.foo=bar&service.vars.bar~%2Afoo%2A'
            ]
        ];

        $initialMenuConfig = [
            'foreign-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'example.com?q=foo'
            ],
            'monitoring-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'monitoring/list/hosts?host_problem=1'
            ],
            'icingadb-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'icingadb/hosts?host.name=%2Afoo%2A'
            ]
        ];
        $expectedMenuConfig = [
            'foreign-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'example.com?q=foo'
            ],
            'monitoring-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'monitoring/list/hosts?host_problem=1'
            ],
            'icingadb-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'icingadb/hosts?host.name~%2Afoo%2A'
            ]
        ];

        $initialDashboardConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'monitoring/list/hosts?host_problem=1'
            ],
            'icingadb' => [
                'title' => 'Icinga DB'
            ],
            'icingadb.wildcards' => [
                'title' => 'Wildcards',
                'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name=%2Alinux%2A'
            ]
        ];
        $expectedDashboardConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'monitoring/list/hosts?host_problem=1'
            ],
            'icingadb' => [
                'title' => 'Icinga DB'
            ],
            'icingadb.wildcards' => [
                'title' => 'Wildcards',
                'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name~%2Alinux%2A'
            ]
        ];

        $initialRoleConfig = [
            'one' => [
                'groups'                    => 'support,helpdesk',
                'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
            ],
            'two' => [
                'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
            ],
            'three' => [
                'icingadb/filter/objects'   => 'host.name=%2Afoo%2A'
            ]
        ];
        $expectedRoleConfig = [
            'one' => [
                'groups'                    => 'support,helpdesk',
                'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
            ],
            'two' => [
                'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
            ],
            'three' => [
                'icingadb/filter/objects'   => 'host.name~%2Afoo%2A'
            ]
        ];

        $this->createConfig('preferences/test/host-actions.ini', $initialHostActionConfig);
        $this->createConfig('preferences/test/service-actions.ini', $initialServiceActionConfig);
        $this->createConfig('preferences/test/icingadb-host-actions.ini', $initialIcingadbHostActionConfig);
        $this->createConfig('preferences/test/icingadb-service-actions.ini', $initialIcingadbServiceActionConfig);
        $this->createConfig('dashboards/test/dashboard.ini', $initialDashboardConfig);
        $this->createConfig('preferences/test/menu.ini', $initialMenuConfig);
        $this->createConfig('roles.ini', $initialRoleConfig);

        $command = $this->createCommandInstance();
        $command->filterAction();

        $hostActionConfig = $this->loadConfig('preferences/test/host-actions.ini');
        $serviceActionConfig = $this->loadConfig('preferences/test/service-actions.ini');
        $icingadbHostActionConfig = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $icingadbServiceActionConfig = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $dashboardBackup = $this->loadConfig('dashboards/test/dashboard.backup.ini');
        $dashboardConfig = $this->loadConfig('dashboards/test/dashboard.ini');
        $menuBackup = $this->loadConfig('preferences/test/menu.backup.ini');
        $menuConfig = $this->loadConfig('preferences/test/menu.ini');
        $roleBackup = $this->loadConfig('roles.backup.ini');
        $roleConfig = $this->loadConfig('roles.ini');

        $this->assertSame($expectedHostActionConfig, $hostActionConfig);
        $this->assertSame($expectedServiceActionConfig, $serviceActionConfig);
        $this->assertSame($initialDashboardConfig, $dashboardBackup);
        $this->assertSame($initialMenuConfig, $menuBackup);
        $this->assertSame($initialRoleConfig, $roleBackup);

        $this->assertSame($expectedIcingadbHostActionConfig, $icingadbHostActionConfig);
        $this->assertSame($expectedIcingadbServiceActionConfig, $icingadbServiceActionConfig);
        $this->assertSame($expectedDashboardConfig, $dashboardConfig);
        $this->assertSame($expectedMenuConfig, $menuConfig);
        $this->assertSame($expectedRoleConfig, $roleConfig);
    }

    /**
     * @depends testFilterMigrationWorksAsExpected
     */
    public function testFilterMigrationSkipsBackupsIfRequested()
    {
        $initialHostActionConfig = [
            'hosts' => [
                'type'      => 'host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host_name=%2Afoo%2A'
            ]
        ];
        $expectedHostActionConfig = $initialHostActionConfig;

        $initialIcingadbHostActionConfig = [
            'hosts' => [
                'type'      => 'icingadb-host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host.name=%2Afoo%2A'
            ]
        ];
        $expectedIcingadbHostActionConfig = [
            'hosts' => [
                'type'      => 'icingadb-host-action',
                'url'       => 'example.com/search?q=$host.name$',
                'filter'    => 'host.name~%2Afoo%2A'
            ]
        ];

        $initialServiceActionConfig = [
            'services' => [
                'type'      => 'service-action',
                'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                'filter'    => '_service_foo=bar&_service_bar=%2Afoo%2A'
            ]
        ];
        $expectedServiceActionConfig = $initialServiceActionConfig;

        $initialIcingadbServiceActionConfig = [
            'services' => [
                'type'      => 'icingadb-service-action',
                'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                'filter'    => 'service.vars.foo=bar&service.vars.bar=%2Afoo%2A'
            ]
        ];
        $expectedIcingadbServiceActionConfig = [
            'services' => [
                'type'      => 'icingadb-service-action',
                'url'       => 'example.com/search?q=$service.name$,$host.name$,$host.address$,$host.address6$',
                'filter'    => 'service.vars.foo=bar&service.vars.bar~%2Afoo%2A'
            ]
        ];

        $initialMenuConfig = [
            'foreign-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'example.com?q=foo'
            ],
            'monitoring-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'monitoring/list/hosts?host_problem=1'
            ],
            'icingadb-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'icingadb/hosts?host.name=%2Afoo%2A'
            ]
        ];
        $expectedMenuConfig = [
            'foreign-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'example.com?q=foo'
            ],
            'monitoring-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'monitoring/list/hosts?host_problem=1'
            ],
            'icingadb-url' => [
                'type'      => 'menu-item',
                'target'    => '_blank',
                'url'       => 'icingadb/hosts?host.name~%2Afoo%2A'
            ]
        ];

        $initialDashboardConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'monitoring/list/hosts?host_problem=1'
            ],
            'icingadb' => [
                'title' => 'Icinga DB'
            ],
            'icingadb.wildcards' => [
                'title' => 'Wildcards',
                'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name=%2Alinux%2A'
            ]
        ];
        $expectedDashboardConfig = [
            'hosts' => [
                'title' => 'Hosts'
            ],
            'hosts.problems' => [
                'title' => 'Host Problems',
                'url'   => 'monitoring/list/hosts?host_problem=1'
            ],
            'icingadb' => [
                'title' => 'Icinga DB'
            ],
            'icingadb.wildcards' => [
                'title' => 'Wildcards',
                'url'   => 'icingadb/hosts?host.state.is_problem=y&hostgroup.name~%2Alinux%2A'
            ]
        ];

        $initialRoleConfig = [
            'one' => [
                'groups'                    => 'support,helpdesk',
                'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
            ],
            'two' => [
                'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
            ],
            'three' => [
                'icingadb/filter/objects'   => 'host.name=%2Afoo%2A'
            ]
        ];
        $expectedRoleConfig = [
            'one' => [
                'groups'                    => 'support,helpdesk',
                'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
            ],
            'two' => [
                'monitoring/filter/objects' => 'host_name=foo|hostgroup_name=foo'
            ],
            'three' => [
                'icingadb/filter/objects'   => 'host.name~%2Afoo%2A'
            ]
        ];

        $this->createConfig('preferences/test/host-actions.ini', $initialHostActionConfig);
        $this->createConfig('preferences/test/service-actions.ini', $initialServiceActionConfig);
        $this->createConfig('preferences/test/icingadb-host-actions.ini', $initialIcingadbHostActionConfig);
        $this->createConfig('preferences/test/icingadb-service-actions.ini', $initialIcingadbServiceActionConfig);
        $this->createConfig('dashboards/test/dashboard.ini', $initialDashboardConfig);
        $this->createConfig('preferences/test/menu.ini', $initialMenuConfig);
        $this->createConfig('roles.ini', $initialRoleConfig);

        $command = $this->createCommandInstance('--no-backup');
        $command->filterAction();

        $hostActionConfig = $this->loadConfig('preferences/test/host-actions.ini');
        $serviceActionConfig = $this->loadConfig('preferences/test/service-actions.ini');
        $icingadbHostActionConfig = $this->loadConfig('preferences/test/icingadb-host-actions.ini');
        $icingadbServiceActionConfig = $this->loadConfig('preferences/test/icingadb-service-actions.ini');
        $dashboardBackup = $this->loadConfig('dashboards/test/dashboard.backup.ini');
        $dashboardConfig = $this->loadConfig('dashboards/test/dashboard.ini');
        $menuBackup = $this->loadConfig('preferences/test/menu.backup.ini');
        $menuConfig = $this->loadConfig('preferences/test/menu.ini');
        $roleBackup = $this->loadConfig('roles.backup.ini');
        $roleConfig = $this->loadConfig('roles.ini');

        $this->assertSame($expectedHostActionConfig, $hostActionConfig);
        $this->assertSame($expectedServiceActionConfig, $serviceActionConfig);
        $this->assertEmpty($dashboardBackup);
        $this->assertEmpty($menuBackup);
        $this->assertEmpty($roleBackup);

        $this->assertSame($expectedIcingadbHostActionConfig, $icingadbHostActionConfig);
        $this->assertSame($expectedIcingadbServiceActionConfig, $icingadbServiceActionConfig);
        $this->assertSame($expectedDashboardConfig, $dashboardConfig);
        $this->assertSame($expectedMenuConfig, $menuConfig);
        $this->assertSame($expectedRoleConfig, $roleConfig);
    }

    public function testNavigationMigrationWorksEvenIfOnlySharedItemsExist()
    {
        $this->expectNotToPerformAssertions();

        $this->createConfig('navigation/menu.ini', []);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();
    }

    public function testNavigationMigrationWorksEvenIfOnlyUserItemsExist()
    {
        $this->expectNotToPerformAssertions();

        $this->createConfig('preferences/test/menu.ini', []);

        $command = $this->createCommandInstance('--user', 'test');
        $command->navigationAction();
    }

    public function testDashboardMigrationWorksEvenIfNoDashboardsExist()
    {
        $this->expectNotToPerformAssertions();

        $command = $this->createCommandInstance('--user', 'test');
        $command->dashboardAction();
    }
}
