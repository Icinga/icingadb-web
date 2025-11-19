# Icinga DB Web Changelog

Please make sure to always read our [Upgrading](https://icinga.com/docs/icinga-db-web/latest/doc/05-Upgrading/)
documentation before switching to a new version.

## 1.3.0 (2025-11-19)

All included changes can be found on the milestone: https://github.com/Icinga/icingadb-web/milestone/10?closed=1

### Icinga Notifications

This release accompanies the release of Icinga Notifications v0.2.0. We have changed how notification sources are
configured, and now an integration into Icinga Web is mandatory. Icinga DB Web v1.3 will serve as the integration
for Icinga DB v1.5.

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
asynchronous behavior and its migration tool included in the v1.1 release.

## 1.0.1 (2022-09-08)

Here are Fixes: https://github.com/Icinga/icingadb-web/milestone/3?closed=1
Here someone blogged about them: https://icinga.com/blog/2022/09/08/releasing-icinga-db-web-v1-0-1/

## 1.0.0 (2022-06-30)

First stable release

## 1.0.0 RC2 (2021-11-12)

Second release candidate

## 1.0.0 RC1 (2020-03-13)

Initial release
