# Icinga DB Web Changelog

Please make sure to always read our [Upgrading](https://icinga.com/docs/icinga-db-web/latest/doc/05-Upgrading/)
documentation before switching to a new version.

## 1.5.0

All included changes can be found on the milestone: https://github.com/Icinga/icingadb-web/milestone/16?closed=1

### Icinga Notifications v1.0.0 Support

This release accompanies the release of Icinga Notifications v1.0.0 and Icinga DB v1.6.0. Earlier versions of Icinga
Notifications are no longer compatible. The integration covers the following features:

* Setting up Icinga DB for Icinga Notifications [has never been easier](doc/03-Configuration.md#icinga-notifications-configuration).
* Host and service details show recipients that were notified about the current problem.
* The event overview and object histories indicate how many alerts were triggered by which event and provide easy access
  to more details.

### New Features

* The event overview, notification history and object histories are now separated by date and allow choosing between
  relative and absolute display of times. #1065
* The host and service overview now include a fourth view mode that represents the table layout previously only
  available by changing the address bar. You can now also choose the displayed columns easily via the … button
  in the table header. #82

### Breaking Changes

* The filter column `host.user.name` applies only to contacts associated with a host now. If used in a restriction,
  users may find that they have access to more hosts or fewer services than before. #975

### Fixes

* Exporting the tactical overview to CSV or JSON works now. #1334
* Custom variables with `host_` or `service_` prefix now have values in tabular view. #1400

## 1.4.0

All included changes can be found on the milestone: https://github.com/Icinga/icingadb-web/milestone/14?closed=1

### New Features

* PHP 8.5 is now supported (#1315)
* Exported SQL queries are now easier to copy (#991)
* History queries can now disable the MySQL/MariaDB query optimizer via configuration to work around performance
  problems on affected database versions (#1219)
* State pictograms now cover additional states and show tooltips to make them easier to understand (#1271, #1262)
* SLA history can now be filtered by hostgroup (#1324)
* `[INFO]` and `(INFO)` in plugin output are now rendered as visual state indicators instead of plain text (#1348)

### Fixes

* Empty or incomplete object custom variables are handled correctly (#1332)
* Setups without the `monitoring` module no longer produce unnecessary integration errors (#1346)
* JSON exports with pagination now generate valid JSON without a leading comma (#1349)

## 1.3.0 (2025-11-19)

All included changes can be found on the milestone: https://github.com/Icinga/icingadb-web/milestone/10?closed=1

### Icinga Notifications

This release accompanies the release of Icinga Notifications v0.2.0. We have changed how notification sources are
configured, and now an integration into Icinga Web is mandatory. Icinga DB Web v1.3.0 will serve as the integration
for Icinga DB v1.5.0.

### New Features

* The *Services* tab of a host and the *History* tab of hosts and services are now filterable (#981, #1293)
* Exporting hosts and services to CSV or JSON by using the `?columns=…` query parameter now includes only the columns
  specified in the parameter in the results (#1011)

### Breaking Changes

* The restrictions `icingadb/denylist/variables` and `icingadb/protect/variables` from different roles are now
  merged into a single list, respectively. This means that variables denied in one role will not show up anymore
  if another role denies access to different variables. The same applies to `icingadb/protect/variables`, in which
  case variables protected in one role will now be protected even if another role protects different variables.
  This has been done to simplify the configuration and to get it more in line with how refusals work in Icinga Web.

### Maintenance

* The migration widget in the upper right has now been removed. We assume most users may be rather annoyed by it, and
  those who are still unsure when to migrate fully to Icinga DB are unlikely to be finally convinced by it anyway.
  Modules with support for Icinga DB will now default to use it if available.
* The upgrading documentation includes more details and other changes, make sure to read it carefully.

## 1.2.4 (2025-11-19)

This release includes fixes for the following issues:

* Commandtransport in cluster mode is broken #1292
* Redis config: Path "/nix/store" is not writable #1269
* Hide list selection-count and action list hint in minimal layout #1298

## 1.2.3 (2025-10-16)

**Notice:** This is a security release. It is recommended to upgrade _quickly_.

See the related CVE: https://github.com/Icinga/icingadb-web/security/advisories/GHSA-w57j-28jc-8429
Included changes can be found on the milestone: https://github.com/Icinga/icingadb-web/milestone/13?closed=1
And a detailed description about the most important ones on our blog: https://icinga.com/blog/releasing-icinga-2-v2-15-1-2-14-7-and-2-13-13-and-icinga-db-web-v1-2-3-and-1-1-4/

## 1.2.2 (2025-07-16)

**Notice:** This is a security release. It is recommended to upgrade _quickly_.

See the related CVE: https://github.com/Icinga/icingadb-web/security/advisories/GHSA-q2w7-mrx8-5473
Included changes can be found on the milestone: https://github.com/Icinga/icingadb-web/milestone/12?closed=1
And a detailed description about the most important ones on our blog: https://icinga.com/blog/releasing-icinga-web-2-v2-12-5-icinga-db-web-v1-2-2/

## 1.2.1 (2025-06-30)

This release [fixes an issue](https://github.com/Icinga/icingadb-web/pull/1229) with version comparison that caused
incorrect detection of the Icinga DB version. As a result, the health check in Icinga Web will now accurately reflect
the current Icinga DB version and no longer incorrectly indicate that an upgrade to version 1.4.0 is still required.

## 1.2.0 (2025-06-18)

Included changes can be found on the milestone: https://github.com/Icinga/icingadb-web/milestone/7?closed=1
And a detailed description about the most important ones on our blog: https://icinga.com/blog/icinga-dependency-views/

## 1.1.3 (2024-08-06)

Included changes can be found on the milestone: https://github.com/Icinga/icingadb-web/milestone/8?closed=1
And a detailed description about the most important ones on our blog: https://icinga.com/blog/2024/08/06/releasing-icinga-db-web-v1-1-3-and-a-ipl-security-release/

## 1.1.2 (2024-04-11)

Included changes can be found on the milestone: https://github.com/Icinga/icingadb-web/milestone/6?closed=1
And a detailed description about the most important ones on our blog: https://icinga.com/blog/2024/04/11/releasing-icinga-db-1-2-0-and-icinga-db-web-1-1-2

## 1.1.1 (2023-11-15)

Included changes can be found on the milestone: https://github.com/Icinga/icingadb-web/milestone/5?closed=1
And a detailed description about the most important ones on our blog: https://icinga.com/blog/2023/11/16/releasing-icinga-db-web-v1-1-1-and-icinga-web-2-12-1/

## 1.1.0 (2023-09-28)

Included changes can be found on the milestone: https://github.com/Icinga/icingadb-web/milestone/2?closed=1
And a detailed description about the most important ones on our blog: https://icinga.com/blog/2023/09/28/releasing-icinga-db-web-v1-1/

## 1.0.2 (2022-11-04)

You can find all issues related to this release on the respective [Milestone](https://github.com/Icinga/icingadb-web/milestone/4?closed=1).

Notable fixes in this release are that the *GenericTTS* module is now supported and that the legacy integration
of modules with no official support for Icinga DB Web is working again, even if the *monitoring* module is disabled.
Action and Note URLs, which disappeared with v1.0.1, are also visible again.

Some enhancements also found their way in this release. They include improved compatibility with Icinga DB's
asynchronous behavior and its migration tool included in the v1.1.0 release.

## 1.0.1 (2022-09-08)

Here are Fixes: https://github.com/Icinga/icingadb-web/milestone/3?closed=1
Here someone blogged about them: https://icinga.com/blog/2022/09/08/releasing-icinga-db-web-v1-0-1/

## 1.0.0 (2022-06-30)

First stable release

## 1.0.0 RC2 (2021-11-12)

Second release candidate

## 1.0.0 RC1 (2020-03-13)

Initial release
