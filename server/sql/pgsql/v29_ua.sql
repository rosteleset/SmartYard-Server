ALTER TABLE houses_subscribers_devices ADD COLUMN ua CHARACTER VARYING;
ALTER TABLE houses_subscribers_devices ADD IF NOT EXISTS ip TEXT;
