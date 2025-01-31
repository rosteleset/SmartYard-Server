ALTER TABLE cameras ADD COLUMN frs_mode INTEGER DEFAULT 1;                                                              -- 0 - off, 1 - recognition, 2 - detection
ALTER TABLE houses_entrances ADD COLUMN alt_camera_id_1 INTEGER;
ALTER TABLE houses_entrances ADD COLUMN alt_camera_id_2 INTEGER;
ALTER TABLE houses_entrances ADD COLUMN alt_camera_id_3 INTEGER;
ALTER TABLE houses_entrances ADD COLUMN alt_camera_id_4 INTEGER;
ALTER TABLE houses_entrances ADD COLUMN alt_camera_id_5 INTEGER;
ALTER TABLE houses_entrances ADD COLUMN alt_camera_id_6 INTEGER;
ALTER TABLE houses_entrances ADD COLUMN alt_camera_id_7 INTEGER;
