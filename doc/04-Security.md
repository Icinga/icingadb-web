# Security

Icinga DB Web allows users to send commands to Icinga. Users may be restricted to a specific set of commands,
by use of **permissions**.

The same applies to routes, objects and variables. To these, users can be restricted by use of **restrictions**.

## Permissions

> **Restricted Command Permissions:**
>
> If a role [limits users](#filters) to a specific set of results, the command
> permissions or refusals of the very same role only apply to these results.

| Name                                           | Allow...                                                                         |
|------------------------------------------------|----------------------------------------------------------------------------------|
| icingadb/command/*                             | all commands                                                                     |
| icingadb/command/schedule-check                | to schedule host and service checks                                              |
| icingadb/command/schedule-check/active-only    | to schedule host and service checks (Only on objects with active checks enabled) |
| icingadb/command/acknowledge-problem           | to acknowledge host and service problems                                         |
| icingadb/command/remove-acknowledgement        | to remove problem acknowledgements                                               |
| icingadb/command/comment/*                     | to add and delete host and service comments                                      |
| icingadb/command/comment/add                   | to add host and service comments                                                 |
| icingadb/command/comment/delete                | to delete host and service comments                                              |
| icingadb/command/downtime/*                    | to schedule and delete host and service downtimes                                |
| icingadb/command/downtime/schedule             | to schedule host and service downtimes                                           |
| icingadb/command/downtime/delete               | to delete host and service downtimes                                             |
| icingadb/command/process-check-result          | to process host and service check results                                        |
| icingadb/command/feature/instance              | to toggle instance-wide features                                                 |
| icingadb/command/feature/object/*              | to toggle all features on host and service objects                               |
| icingadb/command/feature/object/active-checks  | to toggle active checks on host and service objects                              |
| icingadb/command/feature/object/passive-checks | to toggle passive checks on host and service objects                             |
| icingadb/command/feature/object/notifications  | to toggle notifications on host and service objects                              |
| icingadb/command/feature/object/event-handler  | to toggle event handlers on host and service objects                             |
| icingadb/command/feature/object/flap-detection | to toggle flap detection on host and service objects                             |
| icingadb/command/send-custom-notification      | to send custom notifications for hosts and services                              |
| icingadb/object/show-source                    | to view an object's source data. (May contain sensitive data!)                   |

## Restrictions

### Filters

Filters limit users to a specific set of results.

> **Note:**
>
> Filters from multiple roles will widen available access.

| Name                     | Description                                                            |
|--------------------------|------------------------------------------------------------------------|
| icingadb/filter/objects  | Restrict access to the Icinga objects that match the filter            |
| icingadb/filter/hosts    | Restrict access to the Icinga hosts and services that match the filter |
| icingadb/filter/services | Restrict access to the Icinga services that match the filter           |

`icingadb/filter/objects` will only allow users to access matching objects. This applies to all objects,
not just hosts or services. It should be one or more [filter expressions](#filter-expressions).

`icingadb/filter/hosts` will only allow users to access matching hosts and services. Other objects remain
unrestricted. It should be one or more [filter expressions](#filter-expressions).

`icingadb/filter/services` will only allow users to access matching services. Other objects remain unrestricted.
It should be one or more [filter expressions](#filter-expressions).

### Denylists

Denylists prevent users from accessing information and in some cases will block them entirely from it.

> **Note:**
>
> Denylists from multiple roles will further limit access.

Name                         | Description
-----------------------------|------------------------------------------------------------------
icingadb/denylist/routes    | Prevent access to routes that are part of the list
icingadb/denylist/variables | Hide custom variables of Icinga objects that are part of the list

`icingadb/denylist/routes` will block users from accessing defined routes and from related information elsewhere.
For example, if `hostgroups` are part of the list a user won't have access to the hostgroup overview nor to a host's
groups shown in its detail area. This should be a comma separated list. Possible values are: hostgroups, servicegroups,
users, usergroups

`icingadb/denylist/variables` will block users from accessing certain custom variables. A user affected by this won't
see that those variables even exist. This should be a comma separated list of [variable paths](#variable-paths). It is
possible to use [match patterns](#match-patterns).

### Protections

Protections prevent users from accessing actual data. They will know that there is something, but not what exactly.

> **Note:**
>
> Denylists from multiple roles will further limit access.

Name                       | Description
---------------------------|-----------------------------------------------------------------------------
icingadb/protect/variables | Obfuscate custom variable values of Icinga objects that are part of the list

`icingadb/protect/variables` will replace certain custom variable values with `***`. A user affected by this will still
be able to see the variable names though. This should be a comma separated list of [variable paths](#variable-paths).
It is possible to use [match patterns](#match-patterns).

### Formats

#### Filter Expressions

These consist of one or more [filter expressions](https://icinga.com/docs/icinga-web-2/latest/doc/06-Security/#filter-expressions).

Allowed columns are:

* host.name
* service.name
* hostgroup.name
* servicegroup.name
* host.user.name
* service.user.name
* host.usergroup.name
* service.usergroup.name
* host.vars.[\<variable path\>](#variable-paths)
* service.vars.[\<variable path\>](#variable-paths)

#### Match Patterns

Match patterns allow a very simple form of wildcard matching. Use `*` in any place to match zero or any characters.

#### Variable Paths

Icinga DB Web allows to express any custom variable depth in variable paths. So they may be not just names.

Consider the following variables:

```
vars.os = "Linux"
vars.team = {
  name = "Support",
  on-site = ["mo", "tue", "wed", "thu", "fr"]
}
```

To reference `vars.os`, use `os`. To reference `vars.team.name`, use `team.name`. To reference `vars.team.on-site`,
use `team.on-site[0]` for the first list position and `team.on-site[4]` for the last one.

Combined with a [match pattern](#match-patterns) it is also possible to perform a *contains* check: `team.on-site[*]`
