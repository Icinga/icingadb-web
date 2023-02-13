CREATE TABLE host (
    id binary(20) NOT NULL PRIMARY KEY,
    display_name varchar(254) NOT NULL
);

CREATE TABLE host_state (
    id binary(20) NOT NULL,
    host_id binary(20) NOT NULL,
    hard_state TINYINT UNSIGNED NOT NULL,
    previous_hard_state TINYINT UNSIGNED DEFAULT NULL,

    PRIMARY KEY(id, host_id)
);

CREATE TABLE service (
    id binary(20) NOT NULL,
    host_id binary(20) NOT NULL,
    display_name varchar(254) NOT NULL,

    PRIMARY KEY(id, host_id)
);

CREATE TABLE service_state (
    id binary(20) NOT NULL PRIMARY KEY,
    host_id binary(20) NOT NULL,
    service_id binary(20) NOT NULL,
    hard_state TINYINT UNSIGNED NOT NULL,
    previous_hard_state TINYINT UNSIGNED DEFAULT NULL
);

CREATE TABLE sla_history_state (
    id binary(20) NOT NULL PRIMARY KEY,
    environment_id binary(20) DEFAULT NULL,
    endpoint_id binary(20) DEFAULT NULL,
    object_type enum('host', 'service') NOT NULL,
    host_id binary(20) NOT NULL,
    service_id binary(20) DEFAULT NULL,

    event_time bigint unsigned NOT NULL,
    hard_state TINYINT UNSIGNED NOT NULL,
    previous_hard_state TINYINT UNSIGNED NOT NULL
);

CREATE TABLE sla_history_downtime (
    id binary(20) NOT NULL PRIMARY KEY,
    environment_id binary(20) DEFAULT NULL,
    endpoint_id binary(20) DEFAULT NULL,
    object_type enum('host', 'service') NOT NULL,
    host_id binary(20) NOT NULL,
    service_id binary(20) DEFAULT NULL,

    downtime_id binary(20) NOT NULL,
    downtime_start BIGINT UNSIGNED NOT NULL,
    downtime_end BIGINT UNSIGNED NOT NULL
);
