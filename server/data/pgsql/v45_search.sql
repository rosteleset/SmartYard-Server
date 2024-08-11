DROP INDEX IF EXISTS addresses_houses_house_full_fts;
DROP INDEX IF EXISTS houses_subscribers_mobile_subscriber_full_fts;
ALTER TABLE houses_subscribers_mobile ADD IF NOT EXISTS subscriber_full CHARACTER VARYING;
UPDATE houses_subscribers_mobile set subscriber_full = TRIM(COALESCE(subscriber_last || ' ', '') || COALESCE(subscriber_name || ' ', '') || COALESCE(subscriber_patronymic));
CREATE INDEX IF NOT EXISTS houses_subscribers_mobile_subscriber_full ON houses_subscribers_mobile(subscriber_full);
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS fuzzystrmatch;
CREATE INDEX IF NOT EXISTS addresses_houses_house_full_trgm ON addresses_houses USING GIST (house_full gist_trgm_ops);
CREATE INDEX IF NOT EXISTS houses_subscribers_mobile_subscriber_full_trgm ON houses_subscribers_mobile USING GIST (subscriber_full gist_trgm_ops);
