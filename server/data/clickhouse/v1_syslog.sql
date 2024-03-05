CREATE TABLE IF NOT EXISTS default.syslog
(
    `date`   UInt32,
    `ip`     IPv4,
    `sub_id` String,
    `unit`   String,
    `msg`    String,
    INDEX syslog_ip ip TYPE set(100) GRANULARITY 1024,
    INDEX syslog_sub_id sub_id TYPE set(100) GRANULARITY 1024,
    INDEX syslog_unit unit TYPE set(100) GRANULARITY 1024
) ENGINE = MergeTree
      PARTITION BY (toYYYYMMDD(FROM_UNIXTIME(date)), unit)
      ORDER BY date
      TTL FROM_UNIXTIME(date) + toIntervalDay(31)
      SETTINGS index_granularity = 8192;
