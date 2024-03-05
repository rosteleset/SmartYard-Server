CREATE TABLE IF NOT EXISTS default.ttlog
(
    `date`   UInt32,
    `issue`  String,
    `login`  String,
    `action` String,
    `old`    String,
    `new`    String,
    INDEX ttlog_date date TYPE set(100) GRANULARITY 1024,
    INDEX ttlog_issue issue TYPE set(100) GRANULARITY 1024,
    INDEX ttlog_login login TYPE set(100) GRANULARITY 1024
)
    ENGINE = MergeTree
        PARTITION BY toYYYYMMDD(FROM_UNIXTIME(date))
        ORDER BY date
        TTL FROM_UNIXTIME(date) + toIntervalYear(5)
        SETTINGS index_granularity = 1024;
