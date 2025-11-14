# Upgrading Icinga DB Web

Specific version upgrades are described below. Please note that version upgrades are incremental.
If you are upgrading across multiple versions, make sure to follow the steps for each of them.

## Upgrading to Icinga DB Web v1.3

**Removed Features**

* The migration widget in the top right has been removed. If you have not adjusted your navigation items,
  dashboards and bookmarks to support the new filter syntax, you will need to do so manually now (see [Upgrading to
  Icinga DB Web v1.1](#upgrading-to-icinga-db-web-v11) for details).
  * Modules with support for IDO and Icinga DB will now default to use Icinga DB.
  * The accompanying endpoint `icingadb/migrate/monitoring-url` has been removed.
* The class `Icinga\Module\Icingadb\Model\Behavior\BoolCast` has been removed.
  * Use `\ipl\Orm\Behavior\BoolCast` instead.

## Upgrading to Icinga DB Web v1.2

**Requirements**

This version is released alongside Icinga DB 1.4.0 and Icinga 2.15.0. A change to the internal communication API
requires these updates to be applied together.

**Terminology Changes**

Due to user feedback, we changed the terminology of _users_ and _user groups_ configured in Icinga. Both are now called
_contacts_ and _contact groups_ respectively.

**Behavior Changes**

Macros referencing properties of type datetime are now rendered in ATOM format by default instead of unix timestamps.
(e.g. `$host.state.last_state_change$`)

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
