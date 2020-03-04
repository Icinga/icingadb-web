# Installation

## Requirements

TODO: Check this

* Icinga Web 2 (&gt;= 2.8 RC1)
* PHP (&gt;= 5.6, preferably 7.x)
* [IcingaDB](https://github.com/Icinga/icingadb)
* Icinga Web 2 modules:
  * [reactbundle](https://github.com/Icinga/icingaweb2-module-reactbundle) (>= 0.4) (Icinga Web 2 module)
  * [Icinga PHP Library (ipl)](https://github.com/Icinga/icingaweb2-module-ipl) (>= 0.5) (Icinga Web 2 module)

## Installation

1. Just drop this module to a `icingadb` subfolder in your Icinga Web 2 module path.

2. Log in with a privileged user in Icinga Web 2 and enable the module in `Configuration -> Modules -> icingadb`.
Or use the `icingacli` and run `icingacli module enable icingadb`.

3. Create a new Icinga Web 2 resource for [Icinga DB's database](https://icinga.com/docs/icingadb/latest/doc/02-Installation/#configuring-mysql)
using the `Configuration -> Application -> Resources` menu.

4. The next step involves telling the module which database resource to use. This can be done in
`Configuration -> Modules -> icingadb -> Database`.

If you previously had the monitoring module installed and configured, you don't have to configure much else.
The sole exception might be the redis connection details, which you need to define if it's not locally installed.

The full configuration is described in its own [chapter](03-Configuration.md).

This concludes the installation. You should now be able to use Icinga DB Web.
