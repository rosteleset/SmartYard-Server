ALTER TABLE houses_flats_devices ADD COLUMN paranoid INTEGER DEFAULT 0;
CREATE INDEX houses_flats_devices_paranoid ON houses_flats_devices(paranoid);
ALTER TABLE houses_rfids ADD COLUMN watch INTEGER DEFAULT 0;
CREATE INDEX houses_rfids_watch ON houses_rfids (watch);
