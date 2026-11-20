SET allow_experimental_object_type = 1;
SET allow_experimental_json_type = 1;

CREATE TABLE IF NOT EXISTS default.plog_service
(
    `date`        UInt32,
    `event_uuid`  UUID,
    `image_uuid`  UUID,
    `access_type` Int8,
    `domophone`   JSON,
    `event`       Int8,
    `opened`      Int8,
    `face`        JSON,
    `rfid`        String,
    `code`         String,
    `phones`       JSON,
    `preview`     Int8,
    `vehicle`     String,
    INDEX plog_service_date date TYPE set(100) GRANULARITY 1024,
    INDEX plog_service_event_uuid event_uuid TYPE set(100) GRANULARITY 1024,
    INDEX plog_service_access_type access_type TYPE set(100) GRANULARITY 1024
) ENGINE = MergeTree
      PARTITION BY toYYYYMMDD(FROM_UNIXTIME(date))
      ORDER BY date
      TTL FROM_UNIXTIME(date) + toIntervalMonth(6)
      SETTINGS index_granularity = 1024;
