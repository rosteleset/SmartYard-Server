CREATE TABLE IF NOT EXISTS houses_devices_tree
(
    tree CHARACTER VARYING NOT NULL PRIMARY KEY,
    name CHARACTER VARYING
);
CREATE INDEX IF NOT EXISTS houses_devices_tree_name ON houses_devices_tree(name);

ALTER TABLE cameras ADD IF NOT EXISTS tree CHARACTER VARYING DEFAULT '';
CREATE INDEX IF NOT EXISTS cameras_tree ON cameras(tree);

ALTER TABLE houses_domophones ADD IF NOT EXISTS tree CHARACTER VARYING DEFAULT '';
CREATE INDEX IF NOT EXISTS houses_domophones_tree ON houses_domophones(tree);

UPDATE cameras SET tree = '';
UPDATE houses_domophones SET tree = '';
