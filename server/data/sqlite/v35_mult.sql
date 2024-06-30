-- changa mobile subscribers
ALTER TABLE houses_subscribers_mobile DROP COLUMN auth_token;
ALTER TABLE houses_subscribers_mobile DROP COLUMN platform;
ALTER TABLE houses_subscribers_mobile DROP COLUMN push_token;
ALTER TABLE houses_subscribers_mobile DROP COLUMN push_token_type;
ALTER TABLE houses_subscribers_mobile DROP COLUMN voip_token;
ALTER TABLE houses_subscribers_mobile DROP COLUMN last_seen;
ALTER TABLE houses_subscribers_mobile DROP COLUMN voip_enabled;

-- flats <-> devices
CREATE TABLE houses_flats_devices
(
    house_flat_id INTEGER NOT NULL,
    subscriber_device_id INTEGER NOT NULL,
    voip_enabled INTEGER
);
CREATE UNIQUE INDEX houses_flats_devices_uniq ON houses_flats_devices(house_flat_id, subscriber_device_id);
