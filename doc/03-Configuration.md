# Configuration

If Icinga Web has been installed but not yet set up, please visit Icinga Web and follow the web-based setup wizard.
For Icinga Web setups already running, log in to Icinga Web with a privileged user and follow the steps below to
configure Icinga DB Web:

If you have previously used the monitoring module, there is an option to [migrate](10-Migration.md) some settings.

## Database Configuration

Connection configuration for the database to which Icinga DB synchronizes monitoring data.

1. Create a new resource for the Icinga DB database via the `Configuration → Application → Resources` menu.

2. Configure the resource you just created as the database connection for the Icinga DB Web module using the
   `Configuration → Modules → icingadb → Database` menu.

## Redis® Configuration

Connection configuration for the Redis® server where Icinga 2 writes check results.
This data is used to display the latest state information in Icinga DB Web.

1. Configure the connection to the Redis® server through the `Configuration → Modules → icingadb → Redis` menu.

!!! info

    If you are running a high-availability Icinga 2 setup,
    also configure the secondary master's Redis® connection details.
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

## General Configuration

You can adjust some default values of options users have while interacting with particular dialogs in the UI. (e.g. While acknowledging a problem)
These options can not be adjusted in the UI directly, but have to be set in the
configuration file `/etc/icingaweb2/modules/icingadb/config.ini`.

### Available Settings and defaults

| Option                            | Description                                                                                                                                                                                                                         | Default             |
|-----------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------|
| acknowledge_expire                | Toggles "Use Expire Time" in the Acknowledgement dialog.                                                                                                                                                                            | **0 (false)**       |
| acknowledge_expire_time           | Sets the value for "Expire Time" in the Acknowledgement dialog. It is calculated as now + this setting. Format is a [PHP Dateinterval](http://www.php.net/manual/en/dateinterval.construct.php).                                    | **1 hour (PT1H)**.  |
| acknowledge_notify                | Toggles "Send Notification" in the Acknowledgement dialog.                                                                                                                                                                          | **1 (true)**        |
| acknowledge_persistent            | Toggles "Persistent Comment" in the Acknowledgement dialog.                                                                                                                                                                         | **0 (false)**       |
| acknowledge_sticky                | Toggles "Sticky Acknowledgement" in the Acknowledgement dialog.                                                                                                                                                                     | **0 (false)**       |
| comment_expire                    | Toggles "Use Expire Time" in the Comment dialog.                                                                                                                                                                                    | **0 (false)**       |
| hostdowntime_comment_text         | Sets the value for "Comment" in the Host Downtime dialog                                                                                                                                                                            | ""                  |
| servicedowntime_comment_text      | Sets the value for "Comment" in the Service Downtime dialog.                                                                                                                                                                        | ""                  |
| comment_expire_time               | Sets the value for "Expire Time" in the Comment dialog. It is calculated as now + this setting. Format is a [PHP Dateinterval](http://www.php.net/manual/en/dateinterval.construct.php).                                            | **1 hour (PT1H)**   |
| custom_notification_forced        | Toggles "Forced" in the Custom Notification dialog.                                                                                                                                                                                 | **0 (false)**       |
| hostdowntime_all_services         | Toggles "All Services" in the Schedule Host Downtime dialog.                                                                                                                                                                        | **0 (false)**       |
| hostdowntime_end_fixed            | Sets the value for "End Time" in the Schedule Host Downtime dialog for a **Fixed** downtime. It is calculated as now + this setting. Format is a [PHP Dateinterval](http://www.php.net/manual/en/dateinterval.construct.php).       | **1 hour (PT1H)**.  |
| hostdowntime_end_flexible         | Sets the value for "End Time" in the Schedule Host Downtime dialog for a **Flexible** downtime. It is calculated as now + this setting. Format is a [PHP Dateinterval](http://www.php.net/manual/en/dateinterval.construct.php).    | **2 hours (PT2H)**. |
| hostdowntime_flexible_duration    | Sets the value for "Flexible Duration" in the Schedule Host Downtime dialog for a **Flexible** downtime. Format is a [PHP Dateinterval](http://www.php.net/manual/en/dateinterval.construct.php).                                   | **2 hour (PT2H)**.  |
| servicedowntime_end_fixed         | Sets the value for "End Time" in the Schedule Service Downtime dialog for a **Fixed** downtime. It is calculated as now + this setting. Format is a [PHP Dateinterval](http://www.php.net/manual/en/dateinterval.construct.php).    | **1 hour (PT1H)**.  |
| servicedowntime_end_flexible      | Sets the value for "End Time" in the Schedule Service Downtime dialog for a **Flexible** downtime. It is calculated as now + this setting. Format is a [PHP Dateinterval](http://www.php.net/manual/en/dateinterval.construct.php). | **1 hour (PT1H)**.  |
| servicedowntime_flexible_duration | Sets the value for "Flexible Duration" in the Schedule Service Downtime dialog for a **Flexible** downtime. Format is a [PHP Dateinterval](http://www.php.net/manual/en/dateinterval.construct.php).                                | **2 hour (PT2H)**.  |
| plugin_output_character_limit     | Sets the maximum number of characters to display in plugin output.                                                                                                                                                                  | **10000**           |

### Example

Setting acknowledgements with 2 hours expire time by default.

```
[settings]
acknowledge_expire = 1
acknowledge_expire_time = PT2H
```

## Security

To grant users permissions to run commands and restrict them to specific views,
see the [Security](04-Security.md) documentation for the necessary steps.
