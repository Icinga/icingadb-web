# Migration

If you previously used the monitoring module, (built into Icinga Web 2) you may want to
migrate your existing configuration, custom dashboards and navigation items as well as
permissions or restrictions.

If that is the case, this chapter has you covered.

## Configuration

### Command Transports

Icinga DB Web still uses the same configuration format for command transports. This means that the file
`/etc/icingaweb2/modules/monitoring/commandtransports.ini` can simply be copied over to
`/etc/icingaweb2/modules/icingadb/commandtransports.ini`.

But note that Icinga DB Web doesn't support the commandfile (local and remote) anymore. Remove all sections
that do **not** define `transport=api`.

### Protected Customvars

The rules previously configured at `Configuration -> Modules -> monitoring -> Security` have moved into the
roles configuration as a new restriction. This is called `icingadb/protect/variables` and accepts the same
rules. Just copy them over.

## Navigation

The monitoring module provides two custom navigation item types: `host-action` and `service-action`
Icinga DB Web does the same, though uses different type names to achieve that: `icingadb-host-action`
and `icingadb-service-action`

With Icinga DB Web 1.1, its migrate command allows you to migrate these navigation items automatically:

`icingacli icingadb migrate navigation --user=<name> [--no-backup] [--override]`

By default, this only migrates navigation items of specific users and keeps the old ones. The `--user`
switch expects a username, with optional wildcards (`*`) to match multiple users. `--user=*` matches
all users. Pass `--no-backup` to fully remove the old monitoring navigation items.

A similar version of this command has already been available since Icinga Web 2.9.4. Due to this, the new
command allows you to perform the migration from scratch again with the `--override` switch. (Provided you
still have the old navigation items.) Otherwise, already migrated items are ignored. That's also a difference
to the previous command, which duplicated items instead.

## Dashboards

The dashboard item configuration does not change since it is related to Icinga Web. However, items that
reference views of the monitoring module should be changed in order to permanently reference views of
Icinga DB Web.

With Icinga DB Web 1.1, its migrate command allows you to migrate such dashboard items automatically:

`icingacli icingadb migrate dashboard --user=<name> [--no-backup]`

By default, this only migrates dashboards of specific users and creates backups. The `--user` switch
expects a username, with optional wildcards (`*`) to match multiple users. `--user=*` matches all users.
Pass `--no-backup` to disable backup creation. Please note, if you do so, that this makes resetting
changes more difficult.

### Automation

For those who integrate Icinga Web into e.g. custom dashboards, there is also a way to automate the
migration of urls. An API endpoint in Icinga DB Web allows for this:

`/icingaweb2/icingadb/migrate/monitoring-url`

If you `POST` a JSON list there, you'll get a JSON list back with the transformed urls in it.
The returned list is ordered the same and any unrecognized url is left unchanged:

**Input:**  
```json
[
    "/icingaweb2/monitoring/list/services?hostgroup_name=prod-hosts|(_host_env=prod&_host_stage!=testing)",
    "/icingaweb2/businessprocess/process/show?config=production"
]
```

**Output**:  
```json
[
    "/icingaweb2/icingadb/services?hostgroup.name=prod-hosts|(host.vars.env=prod&host.vars.stage!=testing)",
    "/icingaweb2/businessprocess/process/show?config=production"
]
```

**cURL example:**  
`curl -s -HContent-Type:application/json -HAccept:application/json -u icingaadmin:icinga http://localhost/icingaweb2/icingadb/migrate/monitoring-url -d '["/icingaweb2/monitoring/list/services?hostgroup_name=prod-hosts|(_host_env=prod&_host_stage!=testing)","/icingaweb2/businessprocess/process/show?config=production"]'`


## Views and Exports

### Url Parameter `addColumns`

The host and service list of the monitoring module allows to show/export additional information per object by using the
URL parameter `addColumns`. Icinga DB Web has a very similar but much enhanced parameter: `columns`

If you pass this to the host and service list of Icinga DB Web, you'll get an entirely different view mode in which you
have full control over the information displayed. The parameter accepts a comma separated list of columns. This list
also defines the order in which the columns are shown.

As of now, there is no dedicated control in the UI to conveniently choose those columns. You can use all columns
however, which are valid in the search bar as well. The migration widget, that's shown if you have access to
monitoring and Icinga DB Web, also assists you by providing an example set of columns conveying the same information
shown in the monitoring module lists.

## Access Control

### `monitoring/filter/objects`

This is now `icingadb/filter/objects` but still accepts the same filter syntax. Only the columns have changed
or support for them has been dropped. Check the table below for details:

| Old Column Name      | New Column Name        |
|----------------------|------------------------|
| instance\_name       | -                      |
| host\_name           | host.name              |
| hostgroup\_name      | hostgroup.name         |
| service\_description | service.name           |
| servicegroup\_name   | servicegroup.name      |
| \_host\_customvar    | host.vars.customvar    |
| \_service\_customvar | service.vars.customvar |

### `monitoring/blacklist/properties`

This is now `icingadb/denylist/variables`. However, it does not accept the same rules as
`monitoring/blacklist/properties`. It still accepts a comma separated list of GLOB like filters,
but with some features removed:

* No distinction between host and service variables (`host.vars.` and `service.vars.` prefixes are no longer keywords)
* No `**` to cross multiple level boundaries at once (`a.**.d` does not differ from `a.*.d`)
* Dots are not significant (`foo.*.oof` and `foo*oof` will both match `foo.bar.oof`)

Check the [security chapter](04-Security.md#variable-paths) for more details.

### Permissions

The command permissions have not changed. It is only the module identifier that has changed of course:
`monitoring/command/*` is now `icingadb/command/*`

The `no-monitoring/contacts` permission (or *fake refusal*) is now a restriction: `icingadb/denylist/routes`.
Add `users,usergroups` to it to achieve the same effect.

### Perform The Migration

To apply the necessary changes automatically, Icinga DB Web 1.1 provides this command:

`icingacli icingadb migrate role [--role=<name>] [--group=<name>] [--override] [--no-backup]`

By default, this only migrates roles with matching names or matching groups, doesn't change roles that were
already manually migrated and creates backups. Either `--role` or `--group` must be passed, but not both.
Both accept wildcards and just `*` matches all roles. Pass `--override` to forcefully update roles that appear
to be already migrated. Please note that this will reset changes made to Icinga DB Web's rules, which were not
equally applied to their monitoring module counterparts. Pass `--no-backup` to disable backup creation. Please
note, if you do so, that this makes resetting changes more difficult.

With respect to permissions, the command will only migrate the command permissions. If a role grants full or
general access to the monitoring module, this is not automatically migrated. You have to adjust this manually.
It gives you the chance to review the performed changes, before letting them loose on your users. Please also
take in mind, that Icinga DB Web handles permissions and restrictions differently. Our blog provides details
on that: https://icinga.com/blog/2021/04/07/web-access-control-redefined/#icingadb-permission-linkage

## General configuration via config.ini

If some of the default options of the monitoring module (for example setting
acknowledgements to expire by default) were adjusted via the `config.ini` file,
migrating those options is done by simply copying the configuration file to the Icinga DB Web module.
In most cases this would be a

```
cp /etc/icingaweb2/modules/monitoring/config.ini /etc/icingaweb2/modules/icingadb/config.ini
```

The behaviour of those options remains the same.
