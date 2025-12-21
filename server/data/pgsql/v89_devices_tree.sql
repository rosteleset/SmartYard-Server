CREATE TABLE core_devices_tree
(
    leaf_id SERIAL PRIMARY KEY,
    tree CHARACTER VARYING,
    name CHARACTER VARYING
);
CREATE INDEX IF NOT EXISTS core_devices_tree_tree ON core_devices_tree(tree);
CREATE INDEX IF NOT EXISTS core_devices_tree_name ON core_devices_tree(name);

ALTER TABLE cameras ADD IF NOT EXISTS tree CHARACTER VARYING;
CREATE INDEX IF NOT EXISTS cameras_tree ON cameras(tree);

ALTER TABLE houses_domophones ADD IF NOT EXISTS tree CHARACTER VARYING;
CREATE INDEX IF NOT EXISTS houses_domophones_tree ON houses_domophones(tree);
