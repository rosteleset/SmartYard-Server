ALTER TABLE houses_paths ADD IF NOT EXISTS house_path_type CHARACTER VARYING DEFAULT 'list';
ALTER TABLE houses_cameras_houses ADD IF NOT EXISTS path_order INTEGER;
ALTER TABLE houses_cameras_flats ADD IF NOT EXISTS path_order INTEGER;
