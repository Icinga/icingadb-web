<!-- {% if index %} -->
# Installing Icinga DB Web

The recommended way to install Icinga DB Web is to use prebuilt packages for
all supported platforms from our official release repository.
Please follow the steps listed for your target operating system,
which guide you through setting up the repository and installing Icinga DB Web.

To upgrade an existing Icinga DB Web installation to a newer version,
see the [Upgrading](05-Upgrading.md) documentation for the necessary steps.

![Icinga DB Web](res/icingadb-web.png)

Before installing Icinga DB Web, make sure you have installed the
[Icinga DB daemon](https://icinga.com/docs/icinga-db/latest/doc/02-Installation/).

<!-- {% else %} -->
<!-- {% if not icingaDocs %} -->

## Installing the Package

If the [repository](https://packages.icinga.com) is not configured yet, please add it first.
Then use your distribution's package manager to install the `icingadb-web` package
or install [from source](02-Installation.md.d/From-Source.md).
<!-- {% endif %} -->

This concludes the installation. Now proceed with the [configuration](03-Configuration.md).
<!-- {% endif %} --><!-- {# end else if index #} -->
