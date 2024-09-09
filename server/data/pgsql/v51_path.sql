ALTER TABLE houses_entrances ADD IF NOT EXISTS path CHARACTER VARYING;
ALTER TABLE houses_entrances ADD IF NOT EXISTS distance INTEGER;                                                        -- "distance" (order) (virtual) from camera to domophone
CREATE INDEX houses_entrances_camera_id ON houses_entrances_cameras(camera_id);

DROP TABLE houses_entrances_cameras;

CREATE TABLE houses_paths (
    house_path_id SERIAL PRIMARY KEY,
    house_path_tree CHARACTER VARYING DEFAULT 'default',
    house_path_parent INTEGER,
    house_path_name CHARACTER VARYING,
    house_path_icon CHARACTER VARYING
);

CREATE INDEX houses_paths_tree on houses_paths(house_path_tree);
CREATE INDEX houses_paths_parent on houses_paths(house_path_parent);
CREATE INDEX houses_paths_name on houses_paths(house_path_name);
