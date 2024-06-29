-- changa mobile subscribers
ALTER TABLE houses_subscribers_mobile
    DROP COLUMN auth_token,
    DROP COLUMN platform,
    DROP COLUMN push_token,
    DROP COLUMN push_token_type,
    DROP COLUMN voip_token,
    DROP COLUMN last_seen,
    DROP COLUMN voip_enabled;

-- flats <-> devices
CREATE TABLE houses_flats_devices
(
    house_flat_id INTEGER NOT NULL,
    subscriber_device_id INTEGER NOT NULL,
    voip_enabled INTEGER
);
CREATE UNIQUE INDEX houses_flats_devices_uniq ON houses_flats_devices(house_flat_id, subscriber_device_id);
