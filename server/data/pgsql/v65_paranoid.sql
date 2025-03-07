ALTER TABLE houses_flats_devices ADD IF NOT EXISTS paranoid INTEGER DEFAULT 0;
CREATE INDEX IF NOT EXISTS houses_flats_devices_paranoid ON houses_flats_devices(paranoid);
ALTER TABLE houses_rfids ADD IF NOT EXISTS watch INTEGER DEFAULT 0;
