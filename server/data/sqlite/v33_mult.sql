INSERT INTO houses_subscribers_devices (
    house_subscriber_id,
    device_token,
    auth_token,
    platform,
    push_token,
    push_token_type,
    voip_token,
    registered,
    last_seen,
    voip_enabled
)
SELECT
    house_subscriber_id,
    'default',  -- устанавливаем значение по умолчанию для device_token
    auth_token,
    platform,
    push_token,
    push_token_type,
    voip_token,
    registered,
    last_seen,
    voip_enabled
FROM houses_subscribers_mobile;

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
