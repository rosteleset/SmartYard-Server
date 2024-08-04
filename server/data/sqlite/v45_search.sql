ALTER TABLE houses_subscribers_mobile ADD COLUMN subscriber_full TEXT;
UPDATE houses_subscribers_mobile set subscriber_full = TRIM(CONCAT(COALESCE(CONCAT(subscriber_last, ' '), ''), COALESCE(CONCAT(subscriber_name, ' '), ''), COALESCE(subscriber_last, '')));
