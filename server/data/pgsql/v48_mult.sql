ALTER TABLE houses_subscribers_devices ADD IF NOT EXISTS push_disable INTEGER DEFAULT 0;
ALTER TABLE houses_subscribers_devices ADD IF NOT EXISTS money_disable INTEGER DEFAULT 0;
