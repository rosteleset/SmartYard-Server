ALTER TABLE houses_flats_devices COLUMN paranoid;
ALTER TABLE houses_rfids COLUMN watch;

CREATE TABLE houses_watchers (
    house_watcher_id INTEGER PRIMARY KEY AUTOINCREMENT,
    subscriber_device_id INTEGER,
    house_flat_id INTEGER,
    event_type TEXT,
    event_detail TEXT,
    comments TEXT
);

CREATE UNIQUE INDEX houses_watchers_uniq on houses_watchers (subscriber_device_id, house_flat_id, event_type, event_detail);
CREATE INDEX houses_watchers_subscriber_device_id on houses_watchers(subscriber_device_id);
CREATE INDEX houses_watchers_house_flat_id on houses_watchers(house_flat_id);
