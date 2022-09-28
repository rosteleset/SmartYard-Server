curl -O 'https://builds.clickhouse.com/master/amd64/clickhouse' && chmod a+x clickhouse

sudo ./clickhouse install

CREATE TABLE default.syslog
(
    `date` DateTime,
    `ip` IPv4,
    `msg` String
)
ENGINE = MergeTree
PARTITION BY toYYYYMMDD(date)
ORDER BY date
TTL date + toIntervalDay(31)
SETTINGS index_granularity = 8192
