# Configuration

Icinga DB Web is configured via the web interface. Below you will find an overview of the necessary settings.

## Database Configuration

Connection configuration for the database to which Icinga DB synchronizes monitoring data.

1. Create a new resource for the Icinga DB database via the `Configuration → Application → Resources` menu.

2. Configure the resource you just created as the database connection for the Icinga DB Web module using the
   `Configuration → Modules → icingadb → Database` menu.

## Redis Configuration

Connection configuration for the Redis server where Icinga 2 writes check results.
This data is used to display the latest state information in Icinga DB Web.

1. Configure the connection to the Redis server through the `Configuration → Modules → icingadb → Redis` menu.

!!! info

    If you are running a high-availability Icinga 2 setup,
    also configure the secondary master's Redis connection details.
    Icinga DB Web then uses this connection if the primary one is not available.

## Command Transport Configuration

In order to acknowledge problems, force checks, schedule downtimes, etc.,
Icinga DB Web needs access to the Icinga 2 API.
For this you need an `ApiUser` object with at least the following permissions on the Icinga 2 side:

* `actions/*`
* `objects/query/*`
* `objects/modify/*`
* `status/query`

!!! tip

    For single-node setups it is recommended to manage API credentials in the `/etc/icinga2/conf.d/api-users.conf` file.
    If you are running a high-availability Icinga 2 setup, please manage the credentials in the master zone.

1. Please add the following Icinga 2 configuration and change the password accordingly:
   ```
   object ApiUser "icingadb-web" {
       password = "CHANGEME"
       permissions = [ "actions/*", "objects/modify/*", "objects/query/*", "status/query" ]
   }
   ```
2. Restart Icinga 2 for these changes to take effect.
3. Then configure a command transport for Icinga DB Web
   using the credentials you just created via the `Configuration → Modules → icingadb → Command Transports` menu.

!!! info

    If you are running a high-availability Icinga 2 setup,
    also configure the secondary master's API command transport.
    Icinga DB Web then uses this transport if the primary one is not available.

## Security

To grant users permissions to run commands and restrict them to specific views,
see the [Security](04-Security.md) documentation for the necessary steps.
