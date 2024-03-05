CREATE TABLE IF NOT EXISTS default.inbox
(
    `date`   UInt32,
    `id`     String,
    `msg`    String,
    `action` String
)
    ENGINE = MergeTree
        PARTITION BY toYYYYMM(FROM_UNIXTIME(date))
        ORDER BY date
        TTL FROM_UNIXTIME(date) + toIntervalYear(1)
        SETTINGS index_granularity = 8192;
