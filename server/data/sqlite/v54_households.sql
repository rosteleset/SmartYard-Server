DROP TABLE houses_flats_devices;

-- flats <-> devices
CREATE TABLE houses_flats_devices
(
    houses_flat_device_id INTEGER PRIMARY KEY AUTOINCREMENT,
    house_flat_id INTEGER NOT NULL,
    subscriber_device_id INTEGER NOT NULL,
    voip_enabled INTEGER
);
CREATE UNIQUE INDEX houses_flats_devices_uniq ON houses_flats_devices(house_flat_id, subscriber_device_id);
