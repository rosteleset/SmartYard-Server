curl -O 'https://builds.clickhouse.com/master/amd64/clickhouse' && chmod a+x clickhouse

sudo ./clickhouse install

```
CREATE TABLE default.syslog
(
    `date` DateTime,
    `ip` IPv4,
    `unit` String,
    `msg` String
    INDEX syslog_ip ip TYPE set(100) GRANULARITY 1024,
    INDEX syslog_unit unit TYPE set(100) GRANULARITY 1024
)
ENGINE = MergeTree
PARTITION BY toYYYYMMDD(date)
ORDER BY date
TTL date + toIntervalDay(31)
SETTINGS index_granularity = 8192
```

```
CREATE TABLE default.inbox
(
    `date` DateTime,
    `id` String,
    `msg` String,
    `action` String,
    `code` String
)
ENGINE = MergeTree
PARTITION BY toYYYYMM(date)
ORDER BY date
TTL date + toIntervalYear(1)
SETTINGS index_granularity = 8192
```

```
SET allow_experimental_object_type = 1;
```

```
CREATE TABLE default.plog
(
    `date` DateTime,
    `event_uuid` UUID,
    `hidden` Int8,
    `image_uuid` UUID,
    `flat_id` Int32,
    `domophone_id` Int32,
    `domophone_output` Int8,
    `domophone_output_description` String,
    `event` Int8,
    `opened` Int8,
    `face` JSON,
    `rfid` String,
    `code` String,
    `user_phone` String,
    `gate_phone` String,
    `preview` Int8,
    INDEX plog_date date TYPE set(100) GRANULARITY 1024,
    INDEX plog_event_uuid event_uuid TYPE set(100) GRANULARITY 1024,
    INDEX plog_hidden hidden TYPE set(100) GRANULARITY 1024,
    INDEX plog_flat_id flat_id TYPE set(100) GRANULARITY 1024,
    INDEX plog_domophone_id domophone_id TYPE set(100) GRANULARITY 1024,
    INDEX plog_domophone_output domophone_output TYPE set(100) GRANULARITY 1024
)
ENGINE = MergeTree
PARTITION BY toYYYYMMDD(date)
ORDER BY date
TTL date + toIntervalMonth(6)
SETTINGS index_granularity = 1024;
```