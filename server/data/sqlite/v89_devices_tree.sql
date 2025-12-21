CREATE TABLE core_devices_tree
(
    leaf_id INTEGER PRIMARY KEY AUTOINCREMENT,
    tree TEXT,
    name TEXT
);
CREATE INDEX core_devices_tree_tree ON core_devices_tree(tree);
CREATE INDEX core_devices_tree_name ON core_devices_tree(name);

ALTER TABLE cameras ADD COLUMN tree CHARACTER VARYING;
CREATE INDEX cameras_tree ON cameras(tree);

ALTER TABLE houses_domophones ADD COLUMN tree CHARACTER VARYING;
CREATE INDEX houses_domophones_tree ON houses_domophones(tree);
