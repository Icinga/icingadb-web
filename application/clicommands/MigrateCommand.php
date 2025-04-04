<?php

/* Icinga DB Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Clicommands;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Cli\Command;
use Icinga\Data\ConfigObject;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;
use Icinga\Module\Icingadb\Compat\UrlMigrator;
use Icinga\Util\DirectoryIterator;
use Icinga\Web\Request;
use ipl\Stdlib\Str;
use ipl\Web\Filter\QueryString;
use ipl\Web\Url;

class MigrateCommand extends Command
{
    /** @var bool Skip the migration, only perform transformations */
    protected $skipMigration = false;

    public function init(): void
    {
        Logger::getInstance()->setLevel(Logger::INFO);
    }

    /**
     * Migrate monitoring navigation items to Icinga DB Web
     *
     * USAGE
     *
     *  icingacli icingadb migrate navigation [options]
     *
     * REQUIRED OPTIONS:
     *
     *  --user=<name> Migrate navigation items whose owner matches the given
     *                name or owners matching the given pattern. Wildcard
     *                matching by `*` possible.
     *
     * OPTIONS:
     *
     *  --override    Replace existing or already migrated items
     *                (Attention: Actions are not backed up)
     *
     *  --no-backup   Remove monitoring actions and don't back up menu items
     */
    public function navigationAction(): void
    {
        /** @var string $user */
        $user = $this->params->getRequired('user');
        $noBackup = $this->params->get('no-backup');

        $preferencesPath = Config::resolvePath('preferences');
        $sharedNavigation = Config::resolvePath('navigation');
        if (! file_exists($preferencesPath) && ! file_exists($sharedNavigation)) {
            Logger::info('There are no user navigation items to migrate');
            return;
        }

        $rc = 0;
        $directories = file_exists($preferencesPath) ? new DirectoryIterator($preferencesPath) : [];

        $anythingChanged = false;

        /** @var string $directory */
        foreach ($directories as $directory) {
            /** @var string $username */
            $username = $directories->key() === false ? '' : $directories->key();
            if (fnmatch($user, $username) === false) {
                continue;
            }

            $menuItems = $this->readFromIni($directory . '/menu.ini', $rc);
            $hostActions = $this->readFromIni($directory . '/host-actions.ini', $rc);
            $serviceActions = $this->readFromIni($directory . '/service-actions.ini', $rc);
            $icingadbHostActions = $this->readFromIni($directory . '/icingadb-host-actions.ini', $rc);
            $icingadbServiceActions = $this->readFromIni($directory . '/icingadb-service-actions.ini', $rc);

            $menuUpdated = false;
            $originalMenuItems = $this->readFromIni($directory . '/menu.ini', $rc);

            Logger::info(
                'Transforming legacy wildcard filters of existing Icinga DB Web items for user "%s"',
                $username
            );

            if (! $menuItems->isEmpty()) {
                $menuUpdated = $this->transformNavigationItems($menuItems, $username, $rc);
                $anythingChanged |= $menuUpdated;
            }

            if (! $icingadbHostActions->isEmpty()) {
                $anythingChanged |= $this->transformNavigationItems($icingadbHostActions, $username, $rc);
            }

            if (! $icingadbServiceActions->isEmpty()) {
                $anythingChanged |= $this->transformNavigationItems(
                    $icingadbServiceActions,
                    $username,
                    $rc
                );
            }

            if (! $this->skipMigration) {
                Logger::info('Migrating monitoring navigation items for user "%s" to Icinga DB Web', $username);

                if (! $menuItems->isEmpty()) {
                    $menuUpdated = $this->migrateNavigationItems($menuItems, $username, $directory . '/menu.ini', $rc);
                    $anythingChanged |= $menuUpdated;
                }

                if (! $hostActions->isEmpty()) {
                    $anythingChanged |= $this->migrateNavigationItems(
                        $hostActions,
                        $username,
                        $directory . '/icingadb-host-actions.ini',
                        $rc
                    );
                }

                if (! $serviceActions->isEmpty()) {
                    $anythingChanged |= $this->migrateNavigationItems(
                        $serviceActions,
                        $username,
                        $directory . '/icingadb-service-actions.ini',
                        $rc
                    );
                }
            }

            if ($menuUpdated && ! $noBackup) {
                $this->createBackupIni("$directory/menu", $originalMenuItems);
            }
        }

        // Start migrating shared navigation items
        $menuItems = $this->readFromIni($sharedNavigation . '/menu.ini', $rc);
        $hostActions = $this->readFromIni($sharedNavigation . '/host-actions.ini', $rc);
        $serviceActions = $this->readFromIni($sharedNavigation . '/service-actions.ini', $rc);
        $icingadbHostActions = $this->readFromIni($sharedNavigation . '/icingadb-host-actions.ini', $rc);
        $icingadbServiceActions = $this->readFromIni($sharedNavigation . '/icingadb-service-actions.ini', $rc);

        $menuUpdated = false;
        $originalMenuItems = $this->readFromIni($sharedNavigation . '/menu.ini', $rc);

        Logger::info('Transforming legacy wildcard filters of existing shared Icinga DB Web navigation items');

        if (! $menuItems->isEmpty()) {
            $menuUpdated = $this->transformNavigationItems($menuItems, $user, $rc);
            $anythingChanged |= $menuUpdated;
        }

        if (! $icingadbHostActions->isEmpty()) {
            $anythingChanged |= $this->transformNavigationItems($icingadbHostActions, $user, $rc);
        }

        if (! $icingadbServiceActions->isEmpty()) {
            $anythingChanged |= $this->transformNavigationItems(
                $icingadbServiceActions,
                $user,
                $rc
            );
        }

        if (! $this->skipMigration) {
            Logger::info('Migrating shared monitoring navigation items to the Icinga DB Web items');

            if (! $menuItems->isEmpty()) {
                $menuUpdated = $this->migrateNavigationItems($menuItems, $user, $sharedNavigation . '/menu.ini', $rc);
                $anythingChanged |= $menuUpdated;
            }

            if (! $hostActions->isEmpty()) {
                $anythingChanged |= $this->migrateNavigationItems(
                    $hostActions,
                    $user,
                    $sharedNavigation . '/icingadb-host-actions.ini',
                    $rc
                );
            }

            if (! $serviceActions->isEmpty()) {
                $anythingChanged |= $this->migrateNavigationItems(
                    $serviceActions,
                    $user,
                    $sharedNavigation . '/icingadb-service-actions.ini',
                    $rc
                );
            }
        }

        if ($menuUpdated && ! $noBackup) {
            $this->createBackupIni("$sharedNavigation/menu", $originalMenuItems);
        }

        if ($rc > 0) {
            if ($this->skipMigration) {
                Logger::error('Failed to transform some icingadb navigation items');
            } else {
                Logger::error('Failed to migrate some monitoring navigation items');
            }

            exit($rc);
        }

        if (! $anythingChanged) {
            Logger::info('Nothing to do');
        } elseif ($this->skipMigration) {
            Logger::info('Successfully transformed all icingadb navigation item filters');
        } else {
            Logger::info('Successfully migrated all monitoring navigation items');
        }
    }


    /**
     * Migrate monitoring restrictions and permissions to Icinga DB Web
     *
     * Migrated roles do not grant general or full access to users afterward.
     * It is recommended to review any changes made by this command, before
     * manually granting access.
     *
     * USAGE
     *
     *  icingacli icingadb migrate role [options]
     *
     * REQUIRED OPTIONS: (Use either, not both)
     *
     *  --group=<name> Update roles that are assigned to the given group or to
     *                 groups matching the pattern. Wildcard matching by `*`
     *                 possible.
     *
     *  --role=<name>  Update role with the given name or roles whose names
     *                 match the pattern. Wildcard matching by `*` possible.
     *
     * OPTIONS:
     *
     *  --override     Reset any existing Icinga DB Web rules
     *
     *  --no-backup    Don't back up roles
     */
    public function roleAction(): void
    {
        /** @var ?bool $override */
        $override = $this->params->get('override');
        $noBackup = $this->params->get('no-backup');

        /** @var ?string $groupName */
        $groupName = $this->params->get('group');
        /** @var ?string $roleName */
        $roleName = $this->params->get('role');

        if ($roleName === null && $groupName === null) {
            $this->fail("One of the parameters 'group' or 'role' must be supplied");
        } elseif ($roleName !== null && $groupName !== null) {
            $this->fail("Use either 'group' or 'role'. Both cannot be used as role overrules group.");
        }

        $rc = 0;
        $changed = false;

        $restrictions = Config::$configDir . '/roles.ini';
        $rolesConfig = $this->readFromIni($restrictions, $rc);
        $monitoringRestriction = 'monitoring/filter/objects';
        $monitoringPropertyBlackList = 'monitoring/blacklist/properties';
        $icingadbRestrictions = [
            'objects'  => 'icingadb/filter/objects',
            'hosts'    => 'icingadb/filter/hosts',
            'services' => 'icingadb/filter/services'
        ];

        $icingadbPropertyDenyList = 'icingadb/denylist/variables';
        foreach ($rolesConfig as $name => $role) {
            /** @var string[] $role */
            $role = iterator_to_array($role);

            if ($roleName === '*' || $groupName === '*') {
                $roleMatch = true;
            } elseif ($roleName !== null && fnmatch($roleName, $name)) {
                $roleMatch = true;
            } elseif ($groupName !== null && isset($role['groups'])) {
                $roleGroups = array_map('trim', explode(',', $role['groups']));
                $roleMatch = false;
                foreach ($roleGroups as $roleGroup) {
                    if (fnmatch($groupName, $roleGroup)) {
                        $roleMatch = true;
                        break;
                    }
                }
            } else {
                $roleMatch = false;
            }

            if ($roleMatch && ! $this->skipMigration && $this->shouldUpdateRole($role, $override)) {
                if (isset($role[$monitoringRestriction])) {
                    Logger::info(
                        'Migrating monitoring restriction filter for role "%s" to the Icinga DB Web restrictions',
                        $name
                    );
                    $transformedFilter = UrlMigrator::transformFilter(
                        QueryString::parse($role[$monitoringRestriction])
                    );

                    if ($transformedFilter) {
                        $role[$icingadbRestrictions['objects']] = QueryString::render($transformedFilter);
                        $changed = true;
                    }
                }

                if (isset($role[$monitoringPropertyBlackList])) {
                    Logger::info(
                        'Migrating monitoring blacklisted properties for role "%s" to the Icinga DB Web deny list',
                        $name
                    );

                    $icingadbProperties = [];
                    foreach (explode(',', $role[$monitoringPropertyBlackList]) as $property) {
                        $icingadbProperties[] = preg_replace('/^(?:host|service)\.vars\./i', '', $property, 1);
                    }

                    $role[$icingadbPropertyDenyList] = str_replace(
                        '**',
                        '*',
                        implode(',', array_unique($icingadbProperties))
                    );

                    $changed = true;
                }

                if (isset($role['permissions'])) {
                    $updatedPermissions = [];
                    Logger::info(
                        'Migrating monitoring permissions for role "%s" to the Icinga DB Web permissions',
                        $name
                    );

                    if (strpos($role['permissions'], 'monitoring')) {
                        $monitoringProtection = Config::module('monitoring')
                            ->get('security', 'protected_customvars');

                        if ($monitoringProtection !== null) {
                            $role['icingadb/protect/variables'] = $monitoringProtection;
                            $changed = true;
                        }
                    }

                    foreach (explode(',', $role['permissions']) as $permission) {
                        if (Str::startsWith($permission, 'icingadb/') || $permission === 'module/icingadb') {
                            continue;
                        } elseif (Str::startsWith($permission, 'monitoring/command/')) {
                            $changed = true;
                            $updatedPermissions[] = $permission;
                            $updatedPermissions[] = str_replace('monitoring/', 'icingadb/', $permission);
                        } elseif ($permission === 'no-monitoring/contacts') {
                            $changed = true;
                            $updatedPermissions[] = $permission;
                            $role['icingadb/denylist/routes'] = 'contacts,contactgroups';
                        } else {
                            $updatedPermissions[] = $permission;
                        }
                    }

                    $role['permissions'] = implode(',', $updatedPermissions);
                }

                if (isset($role['refusals']) && is_string($role['refusals'])) {
                    $updatedRefusals = [];
                    Logger::info(
                        'Migrating monitoring refusals for role "%s" to the Icinga DB Web refusals',
                        $name
                    );

                    foreach (explode(',', $role['refusals']) as $refusal) {
                        if (Str::startsWith($refusal, 'icingadb/') || $refusal === 'module/icingadb') {
                            continue;
                        } elseif (Str::startsWith($refusal, 'monitoring/command/')) {
                            $changed = true;
                            $updatedRefusals[] = $refusal;
                            $updatedRefusals[] = str_replace('monitoring/', 'icingadb/', $refusal);
                        } else {
                            $updatedRefusals[] = $refusal;
                        }
                    }

                    $role['refusals'] = implode(',', $updatedRefusals);
                }
            }

            if ($roleMatch) {
                foreach ($icingadbRestrictions as $object => $icingadbRestriction) {
                    if (isset($role[$icingadbRestriction]) && is_string($role[$icingadbRestriction])) {
                        $filter = QueryString::parse($role[$icingadbRestriction]);
                        $filter = UrlMigrator::transformLegacyWildcardFilter($filter);

                        if ($filter) {
                            $filter = QueryString::render($filter);
                            if ($filter !== $role[$icingadbRestriction]) {
                                Logger::info(
                                    'Icinga Db Web restriction of role "%s" for %s changed from "%s" to "%s"',
                                    $name,
                                    $object,
                                    $role[$icingadbRestriction],
                                    $filter
                                );

                                $role[$icingadbRestriction] = $filter;
                                $changed = true;
                            }
                        }
                    }
                }
            }

            $rolesConfig->setSection($name, $role);
        }

        if ($changed) {
            if (! $noBackup) {
                $this->createBackupIni(Config::$configDir . '/roles');
            }

            try {
                $rolesConfig->saveIni();
            } catch (NotWritableError $error) {
                Logger::error($error);
                if ($this->skipMigration) {
                    Logger::error('Failed to transform icingadb restrictions');
                } else {
                    Logger::error('Failed to migrate monitoring restrictions');
                }

                exit(256);
            }

            if ($this->skipMigration) {
                Logger::info('Successfully transformed all icingadb restrictions');
            } else {
                Logger::info('Successfully migrated monitoring restrictions and permissions in roles');
            }
        } else {
            Logger::info('Nothing to do');
        }
    }

    /**
     * Migrate monitoring dashboards to Icinga DB Web
     *
     * USAGE
     *
     *  icingacli icingadb migrate dashboard [options]
     *
     * REQUIRED OPTIONS:
     *
     *  --user=<name> Migrate dashboards whose owner matches the given
     *                name or owners matching the given pattern. Wildcard
     *                matching by `*` possible.
     *
     * OPTIONS:
     *
     *  --no-backup   Don't back up dashboards
     */
    public function dashboardAction(): void
    {
        /** @var string $user */
        $user = $this->params->getRequired('user');
        $noBackup = $this->params->get('no-backup');

        $dashboardsPath = Config::resolvePath('dashboards');
        if (! file_exists($dashboardsPath)) {
            Logger::info('There are no dashboards to migrate');
            return;
        }

        $rc = 0;
        $directories = new DirectoryIterator($dashboardsPath);

        $anythingChanged = false;

        /** @var string $directory */
        foreach ($directories as $directory) {
            /** @var string $userName */
            $userName = $directories->key() === false ? '' : $directories->key();
            if (fnmatch($user, $userName) === false) {
                continue;
            }

            $dashboardsConfig = $this->readFromIni($directory . '/dashboard.ini', $rc);
            $backupConfig = $this->readFromIni($directory . '/dashboard.ini', $rc);

            Logger::info(
                'Migrating monitoring dashboards to Icinga DB Web dashboards for user "%s"',
                $userName
            );

            $changed = false;
            /** @var ConfigObject<string> $dashboardConfig */
            foreach ($dashboardsConfig->getConfigObject() as $name => $dashboardConfig) {
                $dashboardUrlString = $dashboardConfig->get('url');
                if ($dashboardUrlString !== null) {
                    $dashBoardUrl = Url::fromPath($dashboardUrlString, [], new Request());
                    if (! $this->skipMigration && Str::startsWith(ltrim($dashboardUrlString, '/'), 'monitoring/')) {
                        $dashboardConfig->url = UrlMigrator::transformUrl($dashBoardUrl)->getRelativeUrl();
                        $changed = true;
                    }

                    if (Str::startsWith(ltrim($dashboardUrlString, '/'), 'icingadb/')) {
                        $finalUrl = $dashBoardUrl->onlyWith(['sort', 'limit', 'view', 'columns', 'page']);
                        $params = $dashBoardUrl->without(['sort', 'limit', 'view', 'columns', 'page'])->getParams();
                        $filter = QueryString::parse($params->toString());
                        $filter = UrlMigrator::transformLegacyWildcardFilter($filter);
                        if ($filter) {
                            $oldFilterString = $params->toString();
                            $newFilterString = QueryString::render($filter);

                            if ($oldFilterString !== $newFilterString) {
                                Logger::info(
                                    'Icinga Db Web filter of dashboard "%s" has changed from "%s" to "%s"',
                                    $name,
                                    $params->toString(),
                                    QueryString::render($filter)
                                );
                                $finalUrl->setFilter($filter);

                                $dashboardConfig->url = $finalUrl->getRelativeUrl();
                                $changed = true;
                            }
                        }
                    }
                }
            }


            if ($changed && $noBackup === null) {
                $this->createBackupIni("$directory/dashboard", $backupConfig);
            }

            if ($changed) {
                $anythingChanged = true;
            }

            try {
                $dashboardsConfig->saveIni();
            } catch (NotWritableError $error) {
                Logger::error($error);
                $rc = 256;
            }
        }

        if ($rc > 0) {
            if ($this->skipMigration) {
                Logger::error('Failed to transform some icingadb dashboards');
            } else {
                Logger::error('Failed to migrate some monitoring dashboards');
            }

            exit($rc);
        }

        if (! $anythingChanged) {
            Logger::info('Nothing to do');
        } elseif ($this->skipMigration) {
            Logger::info('Successfully transformed all icingadb dashboards');
        } else {
            Logger::info('Successfully migrated dashboards for all the matched users');
        }
    }

    /**
     * Migrate Icinga DB Web wildcard filters of navigation items, dashboards and roles
     *
     * USAGE
     *
     *  icingacli icingadb migrate filter
     *
     * OPTIONS:
     *
     *  --no-backup   Don't back up menu items, dashboards and roles
     */
    public function filterAction(): void
    {
        $this->skipMigration = true;

        $this->params->set('user', '*');
        $this->navigationAction();
        $this->dashboardAction();

        $this->params->set('role', '*');
        $this->roleAction();
    }

    private function transformNavigationItems(Config $config, string $owner, int &$rc): bool
    {
        $updated = false;
        /** @var ConfigObject<string> $newConfigObject */
        foreach ($config->getConfigObject() as $section => $newConfigObject) {
            $configOwner = $newConfigObject->get('owner') ?? '';
            if ($configOwner && $configOwner !== $owner) {
                continue;
            }

            if (
                $newConfigObject->get('type') === 'icingadb-host-action'
                || $newConfigObject->get('type') === 'icingadb-service-action'
            ) {
                /** @var ?string $legacyFilter */
                $legacyFilter = $newConfigObject->get('filter');
                if ($legacyFilter !== null) {
                    $filter = QueryString::parse($legacyFilter);
                    $filter = UrlMigrator::transformLegacyWildcardFilter($filter);
                    if ($filter) {
                        $filter = QueryString::render($filter);
                        if ($legacyFilter !== $filter) {
                            $newConfigObject->filter = $filter;
                            $updated = true;
                            Logger::info(
                                'Icinga DB Web filter of action "%s" is changed from %s to "%s"',
                                $section,
                                $legacyFilter,
                                $filter
                            );
                        }
                    }
                }
            }

            /** @var string $url */
            $url = $newConfigObject->get('url');
            if ($url && Str::startsWith(ltrim($url, '/'), 'icingadb/')) {
                $url = Url::fromPath($url, [], new Request());
                $finalUrl = $url->onlyWith(['sort', 'limit', 'view', 'columns', 'page']);
                $params = $url->without(['sort', 'limit', 'view', 'columns', 'page'])->getParams();
                $filter = QueryString::parse($params->toString());
                $filter = UrlMigrator::transformLegacyWildcardFilter($filter);
                if ($filter) {
                    $oldFilterString = $params->toString();
                    $newFilterString = QueryString::render($filter);

                    if ($oldFilterString !== $newFilterString) {
                        Logger::info(
                            'Icinga Db Web filter of navigation item "%s" has changed from "%s" to "%s"',
                            $section,
                            $oldFilterString,
                            $newFilterString
                        );

                        $newConfigObject->url = $finalUrl->setFilter($filter)->getRelativeUrl();
                        $updated = true;
                    }
                }
            }
        }

        if ($updated) {
            try {
                $config->saveIni();
            } catch (NotWritableError $error) {
                Logger::error($error);
                $rc = 256;

                return false;
            }
        }

        return $updated;
    }

    /**
     * Migrate the given config to the given new config path
     *
     * @param Config $config
     * @param string $owner
     * @param string $path
     * @param int $rc
     *
     * @return bool
     */
    private function migrateNavigationItems(Config $config, string $owner, string $path, int &$rc): bool
    {
        $deleteLegacyFiles = $this->params->get('no-backup');
        $override = $this->params->get('override');
        $newConfig = $config->getConfigFile() === $path ? $config : $this->readFromIni($path, $rc);

        $updated = false;
        /** @var ConfigObject<string> $configObject */
        foreach ($config->getConfigObject() as $configObject) {
            $configOwner = $configObject->get('owner') ?? '';
            if ($configOwner && $configOwner !== $owner) {
                continue;
            }

            $migrateFilter = false;
            if ($configObject->type === 'host-action') {
                $updated = true;
                $migrateFilter = true;
                $configObject->type = 'icingadb-host-action';
            } elseif ($configObject->type === 'service-action') {
                $updated = true;
                $migrateFilter = true;
                $configObject->type = 'icingadb-service-action';
            }

            /** @var ?string $urlString */
            $urlString = $configObject->get('url');
            if ($urlString !== null) {
                $urlString = str_replace(
                    ['$SERVICEDESC$', '$HOSTNAME$', '$HOSTADDRESS$', '$HOSTADDRESS6$'],
                    ['$service.name$', '$host.name$', '$host.address$', '$host.address6$'],
                    $urlString
                );
                if ($urlString !== $configObject->url) {
                    $configObject->url = $urlString;
                    $updated = true;
                }

                $url = Url::fromPath($urlString, [], new Request());

                try {
                    $urlString = UrlMigrator::transformUrl($url)->getRelativeUrl();
                    $configObject->url = $urlString;
                    $updated = true;
                } catch (\InvalidArgumentException $err) {
                    // Do nothing
                }
            }

            /** @var ?string $legacyFilter */
            $legacyFilter = $configObject->get('filter');
            if ($migrateFilter && $legacyFilter) {
                $updated = true;
                $filter = QueryString::parse($legacyFilter);
                $filter = UrlMigrator::transformFilter($filter);
                if ($filter !== false) {
                    $configObject->filter = QueryString::render($filter);
                } else {
                    unset($configObject->filter);
                }
            }

            $section = $config->key();
            if (! $newConfig->hasSection($section) || $newConfig === $config || $override) {
                $newConfig->setSection($section, $configObject);
            }
        }

        if ($updated) {
            try {
                $newConfig->saveIni();

                // Remove the legacy file only if explicitly requested
                if ($deleteLegacyFiles && $newConfig !== $config) {
                    unlink($config->getConfigFile());
                }
            } catch (NotWritableError $error) {
                Logger::error($error);
                $rc = 256;

                return false;
            }
        }

        return $updated;
    }

    /**
     * Get the navigation items config from the given ini path
     *
     * @param string $path Absolute path of the ini file
     * @param int $rc      The return code used to exit the action
     *
     * @return Config
     */
    private function readFromIni($path, &$rc)
    {
        try {
            $config = Config::fromIni($path);
        } catch (NotReadableError $error) {
            Logger::error($error);

            $config = new Config();
            $rc = 128;
        }

        return $config;
    }

    private function createBackupIni(string $path, Config $config = null): void
    {
        $counter = 0;
        while (true) {
            $filepath = $counter > 0
                ? "$path.backup$counter.ini"
                : "$path.backup.ini";

            if (! file_exists($filepath)) {
                if ($config) {
                    $config->saveIni($filepath);
                } else {
                    copy("$path.ini", $filepath);
                }

                break;
            } else {
                $counter++;
            }
        }
    }

    /**
     * Checks if the given role should be updated
     *
     * @param string[] $role
     * @param bool     $override
     *
     * @return bool
     */
    private function shouldUpdateRole(array $role, ?bool $override): bool
    {
        return ! (
                isset($role['icingadb/filter/objects'])
                || isset($role['icingadb/filter/hosts'])
                || isset($role['icingadb/filter/services'])
                || isset($role['icingadb/denylist/routes'])
                || isset($role['icingadb/denylist/variables'])
                || isset($role['icingadb/protect/variables'])
                || (isset($role['permissions']) && str_contains($role['permissions'], 'icingadb'))
            )
            || $override;
    }
}
