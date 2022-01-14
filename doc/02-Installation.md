# Installation

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Setup](#setup)

## Requirements

* PHP (>= 7.3)
  * Older versions (7.0+) still work, but may stop doing so with near future updates
* MySQL or PostgreSQL PHP libraries
* The following PHP modules must be installed: cURL, dom, json, libxml, pdo
* [Icinga DB](https://github.com/Icinga/icingadb)
* [Icinga Web 2](https://github.com/Icinga/icingaweb2) (>= 2.9)
* [Icinga PHP Library (ipl)](https://github.com/Icinga/icinga-php-library) (>= 0.8)
* [Icinga PHP Thirdparty](https://github.com/Icinga/icinga-php-thirdparty) (>= 0.10)
* For exports to PDF the [pdfexport](https://github.com/Icinga/icingaweb2-module-pdfexport) (>= 0.10)
  module is required (Optional)

## Installation

### Using Packages

We provide a package for supported platforms. Search for `icingadb-web` with your preferred package manager.

### From Source

Please see Icinga Web 2's documentation on [how to install modules](https://icinga.com/docs/icinga-web-2/latest/doc/08-Modules/#installation).
Use `icingadb` as name.

## Setup

1. Log in with a privileged user in Icinga Web 2 and enable the module in `Configuration -> Modules -> icingadb`.
Or use the `icingacli` and run `icingacli module enable icingadb`.

2. Create a new Icinga Web 2 resource for [Icinga DB's database](https://icinga.com/docs/icingadb/latest/doc/02-Installation/#configuring-mysql)
using the `Configuration -> Application -> Resources` menu.

3. The next step involves telling the module which database resource to use. This can be done in
`Configuration -> Modules -> icingadb -> Database`. Choose the resource you've created just now.

4. Finally head over to `Configuration -> Modules -> icingadb -> Redis` and define how the module should connect
with Icinga DB's Redis.

The full configuration is described in its own [chapter](03-Configuration.md).

If you previously had the monitoring module installed and configured, you might want to [migrate](10-Migration.md)
some settings.

This concludes the installation. You should now be able to use Icinga DB Web.
