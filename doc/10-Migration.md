# Migration

1. [Configuration](#configuration)
2. [Dashboards and Navigation](#dashboards-and-navigation)
3. [Restrictions](#restrictions)
4. [Permissions](#permissions)

If you previously used the monitoring module, (built into Icinga Web 2) you may want to
migrate your existing configuration, custom dashboards and navigation items as well as
permissions or restrictions.

If that is the case, this chapter has you covered.

## Configuration

As already mentioned in the [configuration](03-Configuration.md) chapter, Icinga DB Web
currently utilizes the monitoring module's configuration. There is no need to migrate
command transport and security configuration at the moment.

## Dashboards and Navigation

![Url Migration Preview](res/url-migration-preview.png)

The dashboard and navigation configuration does not change since these are related
to Icinga Web 2. However, if you've used the monitoring module's urls and you want
to update them, this might be straight forward if it's only the url path that needs
to change. Complex filters though can be cumbersome to rewrite.

That is why we provided the migration widget shown above. It will show up for every
monitoring module url for which there is a counterpart in Icinga DB Web. You can then
switch to the respective view in Icinga DB Web with a single click and either use the
new url from the address bar or add it the usual way to the dashboard and sidebar.

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

## Restrictions

> **No migration required:** The monitoring module restrictions are currently utilized
> transparently.

## Permissions

> **No migration required:** The monitoring module permissions are currently utilized
> transparently.
