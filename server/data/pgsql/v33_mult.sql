-- changa indexes
DROP INDEX houses_subscribers_devices_uniq_1;
DROP INDEX houses_subscribers_devices_uniq_2;
DROP INDEX houses_subscribers_devices_uniq_3;
DROP INDEX houses_subscribers_devices_house_subscriber_id;

CREATE INDEX houses_subscribers_devices_device_token on houses_subscribers_devices(device_token);
CREATE UNIQUE INDEX houses_subscribers_devices_auth_token on houses_subscribers_devices(auth_token);
CREATE UNIQUE INDEX houses_subscribers_devices_push_token on houses_subscribers_devices(push_token);
CREATE UNIQUE INDEX houses_subscribers_devices_uniq on houses_subscribers_devices(house_subscriber_id, device_token);

-- changa mobile subscribers
ALTER TABLE houses_subscribers_mobile
    DROP COLUMN auth_token,
    DROP COLUMN platform,
    DROP COLUMN push_token,
    DROP COLUMN push_token_type,
    DROP COLUMN voip_token;
    DROP COLUMN last_seen;
    DROP COLUMN voip_enabled;

-- flats <-> devices
CREATE TABLE houses_flats_devices
(
    house_flat_id integer not null,
    subscriber_device_id integer not null,
    voip_enabled integer                                                                                                        -- ?
);
CREATE UNIQUE INDEX houses_flats_devices_uniq on houses_flats_devices(house_flat_id, subscriber_device_id);