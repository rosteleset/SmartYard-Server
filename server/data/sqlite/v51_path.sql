ALTER TABLE houses_entrances ADD COLUMN path CHARACTER VARYING;
ALTER TABLE houses_entrances ADD COLUMN distance INTEGER;                                                               -- "distance" (order) (virtual) from camera to domophone
CREATE INDEX houses_entrances_camera_id ON houses_entrances_cameras(camera_id);

DROP TABLE houses_entrances_cameras;

CREATE TABLE houses_paths (
    house_path_id INTEGER PRIMARY KEY AUTOINCREMENT,
    house_path_tree TEXT DEFAULT 'default',
    house_path_parent INTEGER,
    house_path_name TEXT,
    house_path_icon TEXT
);

CREATE INDEX houses_paths_tree on houses_paths(house_path_tree);
CREATE INDEX houses_paths_parent on houses_paths(house_path_parent);
CREATE INDEX houses_paths_name on houses_paths(house_path_name);
