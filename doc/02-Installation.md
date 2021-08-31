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
* [Icinga PHP Library (ipl)](https://github.com/Icinga/icinga-php-library) (>= 0.7)
* [Icinga PHP Thirdparty](https://github.com/Icinga/icinga-php-thirdparty) (>= 0.10)
* For exports to PDF the [pdfexport](https://github.com/Icinga/icingaweb2-module-pdfexport) module is required (Optional)

## Installation

We can't provide you with any packages just yet. But rest assured we will have
packages for Icinga DB Web and we will make sure you'll get a note if so. For
the meantime please use one of the following methods to install Icinga DB Web.

### From Release Tarball

Download the [latest version](https://github.com/Icinga/icingadb-web/releases) and
extract it to a folder named `icingadb` in one of your Icinga Web 2 module paths.

You might want to use a script as follows for this task:

    ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
    REPO_URL="https://github.com/Icinga/icingadb-web"
    TARGET_DIR="${ICINGAWEB_MODULEPATH}/icingadb"
    MODULE_VERSION="1.0.0-rc1"
    URL="${REPO_URL}/archive/v${MODULE_VERSION}.tar.gz"
    install -d -m 0755 "${TARGET_DIR}"
    wget -q -O - "$URL" | tar xfz - -C "${TARGET_DIR}" --strip-components 1

Now proceed with the [setup](#setup).

### From Git Repository

Another convenient method is the installation directly from our Git repository.
Just clone the repository to one of your Icinga Web 2 module paths. It will be
immediately ready for use:

    ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
    REPO_URL="https://github.com/Icinga/icingadb-web"
    TARGET_DIR="${ICINGAWEB_MODULEPATH}/icingadb"
    MODULE_VERSION="1.0.0-rc1"
    git clone "${REPO_URL}" "${TARGET_DIR}"

You can now directly use our current Git master or check out a specific version:

    cd "${TARGET_DIR}" && git checkout "v${MODULE_VERSION}"

Now proceed with the [setup](#setup).

## Setup

1. Log in with a privileged user in Icinga Web 2 and enable the module in `Configuration -> Modules -> icingadb`.
Or use the `icingacli` and run `icingacli module enable icingadb`.

2. Create a new Icinga Web 2 resource for [Icinga DB's database](https://icinga.com/docs/icingadb/latest/doc/02-Installation/#configuring-mysql)
using the `Configuration -> Application -> Resources` menu.

3. The next step involves telling the module which database resource to use. This can be done in
`Configuration -> Modules -> icingadb -> Database`.

If you previously had the monitoring module installed and configured, you don't have to configure much else.
The sole exception might be the redis connection details, which you need to define if it's not locally installed.

The full configuration is described in its own [chapter](03-Configuration.md).

This concludes the installation. You should now be able to use Icinga DB Web.
