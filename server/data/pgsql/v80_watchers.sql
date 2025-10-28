ALTER TABLE houses_flats_devices DROP IF EXISTS paranoid;
ALTER TABLE houses_rfids DROP IF EXISTS watch;

CREATE TABLE houses_watchers (
    house_watcher_id SERIAL PRIMARY KEY,
    subscriber_device_id INTEGER,
    house_flat_id INTEGER,
    event_type CHARACTER VARYING,
    event_detail CHARACTER VARYING,
    comments CHARACTER VARYING
);

CREATE UNIQUE INDEX IF NOT EXISTS houses_watchers_uniq on houses_watchers (subscriber_device_id, house_flat_id, event_type, event_detail);
CREATE INDEX IF NOT EXISTS houses_watchers_subscriber_device_id on houses_watchers(subscriber_device_id);
CREATE INDEX IF NOT EXISTS houses_watchers_house_flat_id on houses_watchers(house_flat_id);
