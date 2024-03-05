ALTER TABLE default.syslog
    ADD COLUMN IF NOT EXISTS sub_id String AFTER ip,
    ADD INDEX IF NOT EXISTS syslog_sub_id sub_id TYPE set(100) GRANULARITY 1024;
