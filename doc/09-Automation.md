# Automation

It is possible to issue command actions without a browser. To do so, a form needs to be submitted by a tool such as
curl. This is also used in the example below.

## Request Format

The request is required to be an Icinga Web API request. For this it is necessary to transmit the `Accept` HTTP header
and set it to `application/json`. In addition to this, the request must be authenticated using the `Basic` schema.
The HTTP request method is `POST` if not documented otherwise and it might be necessary to manually configure this
(e.g. `-X POST` for curl).

All endpoints support filters. To issue commands only for specific items, define a filter in the request's query string.
If this filter is omitted, all items are affected.

The options need to be transmitted in the request body as `multipart/form-data`.

## Response Format

If the request succeeds, the HTTP response code is `200` and the response body contains a JSON object such as this:

```json
{
  "status": "success",
  "data": [
    {
      "type": "success",
      "message": "Added comment successfully"
    }
  ]
}
```

If there's something wrong with the options, the HTTP response code is `422` and the response body contains a JSON
object such as this:

```json
{
  "status": "fail",
  "data": {
    "comment": [],
    "expire": [],
    "expire_time": ["The expire time must not be in the past"]
  }
}
```

## Example

```shell
USER="icingaadmin"
PASSWORD="icinga"
BASEURL="http://localhost/icingaweb2"
FILTER="host.name=docker-master"
curl -H "Accept: application/json" -u $USER:$PASSWORD "$BASEURL/icingadb/hosts/add-comment?$FILTER" \
  -F "comment=kaput" -F "expire_time=2023-10-05T20:00:00" -F "expire=y"
```

```shell
USER="icingaadmin"
PASSWORD="icinga"
BASEURL="http://localhost/icingaweb2"
FILTER="host.name=docker-master"
curl -H "Accept: application/json" -u $USER:$PASSWORD -X POST "$BASEURL/icingadb/hosts/delete-comment?$FILTER"
```

## Option Types

### Text

A simple text message. May contain newlines.

### Number

An integer value.

### BoolEnum

A string with the value of `y` for `true` and `n` for `false`.

### DateTime

A date time string in the following format: `Y-m-d\TH:i:s`

The timezone this is interpreted in depends on the user who's transmitting the request.
To change this, log in as this user and change its preference setting.

### State

| Value | Description    | Applicable To  |
|-------|----------------|----------------|
| 0     | UP / OK        | Host / Service |
| 1     | DOWN / WARNING | Host / Service |
| 2     | CRITICAL       | Service        |
| 3     | UNKNOWN        | Service        |

### PerfData

Please have a look at the [Monitoring Plugins Development Guidelines](https://www.monitoring-plugins.org/doc/guidelines.html#AEN201).

### ChildOption

| Value | Description                                         |
|-------|-----------------------------------------------------|
| 0     | Do nothing with child hosts                         |
| 1     | Schedule triggered downtime for all child hosts     |
| 2     | Schedule non-triggered downtime for all child hosts |

## Endpoints

!!! info

    Some of the endpoints support configuration options. These are explained in
    the [Configuration](03-Configuration.md#available-settings-and-defaults) section.

### Acknowledge Problem

#### Routes

* icingadb/hosts/acknowledge
* icingadb/services/acknowledge

#### Options

| Option      | Required | Type     | Depends On | Default      | Configuration Option     |
|-------------|----------|----------|------------|--------------|--------------------------|
| comment     | y        | Text     | -          | -            | acknowledge_comment_text |
| persistent  | n        | BoolEnum | -          | n            | acknowledge_persistent   |
| notify      | n        | BoolEnum | -          | y            | acknowledge_notify       |
| sticky      | n        | BoolEnum | -          | n            | acknowledge_sticky       |
| expire      | n        | BoolEnum | -          | n            | acknowledge_expire       |
| expire_time | y        | DateTime | expire     | now + 1 hour | acknowledge_expire_time  |

### Add Comment

#### Routes

* icingadb/hosts/add-comment
* icingadb/services/add-comment

#### Options

| Option      | Required | Type     | Depends On | Default      | Configuration Option |
|-------------|----------|----------|------------|--------------|----------------------|
| comment     | y        | Text     | -          | -            | -                    |
| expire      | n        | BoolEnum | -          | n            | comment_expire       |
| expire_time | y        | DateTime | expire     | now + 1 hour | comment_expire_time  |

### Delete Comments

#### Routes

* icingadb/comments/delete

#### Options

None.

### Check Now

#### Routes

* icingadb/hosts/check-now
* icingadb/services/check-now

#### Options

None.

### Process Check Result

#### Routes

* icingadb/hosts/process-checkresult
* icingadb/services/process-checkresult

#### Options

| Option   | Required | Type     |
|----------|----------|----------|
| status   | y        | State    |
| output   | y        | Text     |
| perfdata | n        | PerfData |

### Remove Acknowledgement

#### Routes

* icingadb/hosts/remove-acknowledgement
* icingadb/services/remove-acknowledgement

#### Options

None.

### Schedule Check

#### Routes

* icingadb/hosts/schedule-check
* icingadb/services/schedule-check

#### Options

| Option      | Required | Type     | Default      |
|-------------|----------|----------|--------------|
| check_time  | y        | DateTime | now + 1 hour |
| force_check | n        | BoolEnum | -            |

### Schedule Host Downtime

#### Routes

* icingadb/hosts/schedule-downtime

#### Options

| Option        | Required | Type        | Depends On | Default                                        | Configuration Option                              |
|---------------|----------|-------------|------------|------------------------------------------------|---------------------------------------------------|
| comment       | y        | Text        | -          | -                                              | hostdowntime_comment_text                         |
| start         | y        | DateTime    | -          | now                                            | -                                                 |
| end           | y        | DateTime    | -          | now + 1 hour (fixed), now + 2 hours (flexible) | hostdowntime_end_fixed, hostdowntime_end_flexible |
| flexible      | n        | BoolEnum    | -          | n                                              | -                                                 |
| hours         | y        | Number      | flexible   | 2                                              | hostdowntime_flexible_duration                    |
| minutes       | y        | Number      | flexible   | 0                                              | hostdowntime_flexible_duration                    |
| all_services  | n        | BoolEnum    | -          | n                                              | hostdowntime_all_services                         |
| child_options | n        | ChildOption | -          | -                                              | -                                                 |

### Schedule Service Downtime

#### Routes

* icingadb/services/schedule-downtime

#### Options

| Option   | Required | Type        | Depends On | Default                                        | Configuration Option                                    |
|----------|----------|-------------|------------|------------------------------------------------|---------------------------------------------------------|
| comment  | y        | Text        | -          | -                                              | servicedowntime_comment_text                            |
| start    | y        | DateTime    | -          | now                                            | -                                                       |
| end      | y        | DateTime    | -          | now + 1 hour (fixed), now + 2 hours (flexible) | servicedowntime_end_fixed, servicedowntime_end_flexible |
| flexible | n        | BoolEnum    | -          | n                                              | -                                                       |
| hours    | y        | Number      | flexible   | 2                                              | servicedowntime_flexible_duration                       |
| minutes  | y        | Number      | flexible   | 0                                              | servicedowntime_flexible_duration                       |

### Delete Downtimes

#### Routes

* icingadb/downtimes/delete

#### Options

None.

### Send Custom Notification

#### Routes

* icingadb/hosts/send-custom-notification
* icingadb/services/send-custom-notification

#### Options

| Option  | Required | Type     | Default | Configuration Option       |
|---------|----------|----------|---------|----------------------------|
| comment | y        | Text     | -       | -                          |
| forced  | n        | BoolEnum | n       | custom_notification_forced |
