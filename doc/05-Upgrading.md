# Upgrading Icinga DB Web

Specific version upgrades are described below. Please note that version upgrades are incremental.
If you are upgrading across multiple versions, make sure to follow the steps for each of them.

## Upgrading to Icinga DB Web v1.2

**Deprecations**

The following classes have been deprecated and will be removed in a future release:
* `\Icinga\Module\Icingadb\Command\Object\PropagateHostDowntimeCommand`
* `\Icinga\Module\Icingadb\Command\Object\ScheduleHostDowntimeCommand`
* `\Icinga\Module\Icingadb\Command\Object\ScheduleServiceDowntimeCommand`

The following methods have been deprecated and will be removed in a future release:
* `\Icinga\Module\Icingadb\Common\IcingaRedis::instance()`: Use `\Icinga\Module\Icingadb\Common\Backend::getRedis()` instead.

## Upgrading to Icinga DB Web v1.1

**Breaking Changes**

We've extended our filter syntax to include new signs for *like* and *unlike* comparisons. These are `~` and `!~`,
respectively. The *equal* (`=`) and *unequal* (`!=`) operators won't perform any wildcard matching anymore due to
this. If you have dashboards, navigation items or bookmarks that attempt to perform wildcard matching with *equal*/
*unequal* comparisons, the migration widget in the top right will toggle and suggest you an automatically transformed
alternative.

Please note that due to our release process, this change already affects installations of Icinga DB Web v1.0.x.

The module's migration command already performs the transformations necessary once you migrate navigation items,
dashboards and roles. (As described in the [Migration](10-Migration.md) chapter.) If you already migrated such
manually in the past, and you don't want to perform the entire migration again, you can use the following command
to only transform filters of such:

`icingacli icingadb migrate filter [--no-backup]`

By default, this creates backups of menu items, dashboards and roles. Pass `--no-backup` to disable this.

## Upgrading to Icinga DB Web v1.0

**Requirements**

* You need at least Icinga DB version 1.0.0 to run Icinga DB Web v1.0.0.

**Configuration Changes**

* The restrictions `icingadb/blacklist/routes` and `icingadb/blacklist/variables` have been renamed to
  `icingadb/denylist/routes` and `icingadb/denylist/variables` respectively. If you use this restriction,
  make sure to adjust `/etc/icingaweb2/roles.ini` accordingly.
