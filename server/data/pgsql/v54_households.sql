ALTER TABLE houses_flats_devices DROP CONSTRAINT IF EXISTS houses_flats_devices_pkey;
ALTER TABLE houses_flats_devices ADD COLUMN IF NOT EXISTS house_flat_device_id SERIAL NOT NULL;
ALTER TABLE houses_flats_devices ADD CONSTRAINT houses_flats_devices_pkey PRIMARY KEY (house_flat_device_id);
