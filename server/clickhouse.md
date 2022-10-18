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
TTL date + toIntervalYear(3)
SETTINGS index_granularity = 8192


CREATE TABLE default.plog (
  date DateTime,
  uuid UUID,
  image String,
  flat_id Int32,
  object_id Int32,
  object_type Int32 DEFAULT 0,
  object_mechanizma Int32 DEFAULT 0,
  mechanizma_description String,
  event Int32,
  opened Int8 default 0,
  preview Int8 DEFAULT 0,
  hidden Int8 DEFAULT 0,
  face_id Int32 default 0,
  face_left Int32 default 0,
  face_top Int32 default 0,
  face_width Int32 default 0,
  face_height Int32 default 0,
  rfid_key String,
  code String,
  phone String,
  phone_from String,
  phone_to String,
  INDEX plog_object_id object_id TYPE set(100) GRANULARITY 1024,
  INDEX plog_uuid uuid TYPE set(100) GRANULARITY 1024,
  INDEX plog_flat_id flat_id TYPE set(100) GRANULARITY 1024,
  INDEX plog_date date TYPE set(100) GRANULARITY 1024,
  INDEX plog_hidden hidden TYPE set(100) GRANULARITY 1024
)
ENGINE = MergeTree
PARTITION BY toYYYYMMDD(date)
ORDER BY date
TTL date + toIntervalMonth(6)
SETTINGS index_granularity = 1024
