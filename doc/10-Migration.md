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

## Dashboards and Navigation

![Url Migration Preview](res/url-migration-preview.png)

The dashboard and menu item configuration does not change since these are related
to Icinga Web 2. However, if you've used the monitoring module's urls and you want
to update them, this might be straight forward if it's only the url path that needs
to change. Complex filters though can be cumbersome to rewrite.

That is why we provided the migration widget shown above. It will show up for every
monitoring module url for which there is a counterpart in Icinga DB Web. You can then
switch to the respective view in Icinga DB Web with a single click and either use the
new url from the address bar or add it the usual way to the dashboard and sidebar.

Host and service actions on the other hand are part of the monitoring module. For them
Icinga DB Web provides their own counterparts. You don't need to migrate them manually
though. The `migrate` command of Icinga Web 2 (>= v2.9.4) provides an action for that:

`icingacli migrate navigation [--user=<username>] [--delete]`

By default this will migrate the configuration of all users and won't delete the old
ones. It can be restricted to a single user and the removal of the old configuration
can be enabled as well.

### Automation

For those who have a plethora of custom dashboards or navigation items there is also
a way to automate the migration of these urls. There is an API endpoint in Icinga DB
Web that the very same widget from above utilizes:

`/icingaweb2/icingadb/migrate/monitoring-url`

If you `POST` a JSON list there, you'll get a JSON list back with the transformed
urls in it. The returned list is ordered the same and any not recognized url is left
unchanged:

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

As of now, there is no dedicated control in the UI to conveniently choose those columns. You can use all columns however,
which are valid in the search bar as well. The migration widget mentioned earlier also assists you by providing an
example set of columns conveying the same information shown in the monitoring module lists.

## Restrictions

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

## Permissions

The command permissions have not changed. It is only the module identifier that has changed of course:
`monitoring.command.*` is now `icingadb.command.*`

The `no-monitoring/contacts` permission (or *fake refusal*) is now a restriction: `icingadb/denylist/routes`.
Add `users,usergroups` to it to achieve the same effect.
