# Upgrading Icinga DB Web

Specific version upgrades are described below. Please note that version upgrades are incremental.
If you are upgrading across multiple versions, make sure to follow the steps for each of them.

## Upgrading to Icinga DB Web v1.0

**Requirements**

* You need at least Icinga DB version 1.0.0 to run Icinga DB Web v1.0.0.

**Configuration Changes**

* The restrictions `icingadb/blacklist/routes` and `icingadb/blacklist/variables` have been renamed to
  `icingadb/denylist/routes` and `icingadb/denylist/variables` respectively. If you use this restriction,
  make sure to adjust `/etc/icingaweb2/roles.ini` accordingly.
