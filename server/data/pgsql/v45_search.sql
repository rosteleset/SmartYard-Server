CREATE EXTENSION IF NOT EXISTS pg_trgm;
ALTER TABLE houses_subscribers_mobile ADD IF NOT EXISTS subscriber_full CHARACTER VARYING;
UPDATE houses_subscribers_mobile set subscriber_full = TRIM(COALESCE(subscriber_last || ' ', '') || COALESCE(subscriber_name || ' ', '') || COALESCE(subscriber_patronymic));
CREATE INDEX addresses_houses_house_full_trgm ON addresses_houses USING gist (house_full gist_trgm_ops);
CREATE INDEX houses_subscribers_mobile_subscriber_full_trgm ON houses_subscribers_mobile USING gist (subscriber_full gist_trgm_ops);
