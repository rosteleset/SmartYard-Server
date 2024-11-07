ALTER TABLE houses_flats_devices ADD COLUMN house_flat_device_id INTEGER AUTOINCREMENT NOT NULL;
ALTER TABLE houses_flats_devices ADD CONSTRAINT houses_flats_devices_pkey PRIMARY KEY (house_flat_device_id);
