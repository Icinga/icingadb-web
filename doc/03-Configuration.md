# Configuration

1. [Database](#database)
2. [Redis](#redis)
3. [Command Transports](#command-transports)
4. [Security](#security)

## Database

If not already done during the installation of Icinga DB Web, setup the Icinga DB database backend now.

Create a new [Icinga Web 2 resource](https://icinga.com/docs/icingaweb2/latest/doc/04-Resources/#database)
for [Icinga DB's database](https://icinga.com/docs/icingadb/latest/doc/02-Installation/#configuring-mysql)
using the `Configuration -> Application -> Resources` menu.

Then tell Icinga DB Web which database resource to use. This can be done in
`Configuration -> Modules -> icingadb -> Database`.

## Redis

To view the most recent state information in Icinga DB Web, make sure to configure the connection details to
[Icinga DB's redis](https://icinga.com/docs/icingadb/latest/doc/02-Installation/#installing-icinga-db-redis)
at `Configuration -> Modules -> icingadb -> Redis`.

### Secondary Master

If you are running a high availability zone with two masters, you can provide the Redis connection details
of the secondary master as well. Icinga DB Web will then use that in case the primary one isn't available.

### Using TLS

If you have setup Redis to only accept encrypted connections, you will need to tell Icinga DB Web the CA certificate
being used for it. This will apply to both connections, the primary and secondary one.

#### Authentication

It is also possible to authenticate requests over TLS. For this, tell Icinga DB Web which client certificate and
private key to use.

### Manual Configuration

The configuration is stored in two different configuration files.

The TLS configuration is stored in Icinga DB Web's main configuration. It is located at
`/etc/icingaweb2/modules/icingadb/config.ini` by default. In it, the section `redis`
contains the relevant directives.

The connection configuration is stored in `/etc/icingaweb2/modules/icingadb/redis.ini`. In it, there may be two
sections with the relevant directives: `redis1` and `redis2`

#### Example

**config.ini**
```ini
[redis]
tls = "1"
insecure = "0"
ca = "/var/lib/icingaweb2/modules/icingadb/redis/d37c36724cbf43f204ace4caa5b1b919-ca.pem"
cert = "/var/lib/icingaweb2/modules/icingadb/redis/d5d43b3a1a77227d8c0ee12adc04483c-cert.pem"
key = "/var/lib/icingaweb2/modules/icingadb/redis/f27abcbe23546134a8515283f1987e15-key.pem"
```

**redis.ini**
```ini
[redis1]
host = "redis-one"
port = "6380"

[redis2]
host = "redis-two"
port = "6380"
```

## Command Transports

Command transports are used to perform actions on the Icinga master such as acknowledgements and scheduling downtimes.
(amongst others)

These can be configured in `Configuration -> Modules -> icingadb -> Command Transports`.

### Icinga 2 Preparations

If not already done, [set up Icinga 2's api](https://icinga.com/docs/icinga-2/latest/doc/12-icinga2-api/#setting-up-the-api).
Icinga DB Web requires access to this api, so make sure to create a user with appropriate permissions and ensure it is
reachable by the web server.

#### Required Permissions

* actions/*
* objects/query/*
* objects/modify/*

### Multiple Transports

You can define multiple command transports. Icinga DB Web will try one transport after another to send a command until
it is successfully sent.

### Manual Configuration

The configuration is stored in an INI-file located at `/etc/icingaweb2/modules/icingadb/commandtransports.ini` by
default. In it, every transport starts with a section header containing its name followed by its config directives.

The section order also defines which transport is used first over another by Icinga DB Web.

#### Example

```ini
[icinga2]
transport = "api"
host = "127.0.0.1" ; Icinga 2 host
port = "5665"
username = "icingaweb2"
password = "bea11beb7b810ea9ce6ea" ; Change this!
```

## Security

Setting up permissions and restrictions is covered in its own [chapter](04-Security.md).
