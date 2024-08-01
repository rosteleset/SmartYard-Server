CREATE TABLE IF NOT EXISTS default.nlog
(
    `date`    UInt32,
    `login`   String,
    `subject` String,
    `message` String,
    `target`  String,
    INDEX nlog_date date TYPE set(100) GRANULARITY 1024,
    INDEX nlog_login login TYPE set(100) GRANULARITY 1024
)
    ENGINE = MergeTree
        PARTITION BY toYYYYMMDD(FROM_UNIXTIME(date))
        ORDER BY date
        TTL FROM_UNIXTIME(date) + toIntervalYear(5)
        SETTINGS index_granularity = 1024;
