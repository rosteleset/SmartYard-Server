ALTER TABLE houses_paths ADD COLUMN house_path_type TEXT DEFAULT 'list';
ALTER TABLE houses_cameras_houses ADD COLUMN path_order INTEGER;
ALTER TABLE houses_cameras_flats ADD COLUMN path_order INTEGER;
