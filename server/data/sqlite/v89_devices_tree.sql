CREATE TABLE houses_devices_tree
(
    tree TEXT NOT NULL PRIMARY KEY,
    name TEXT
);
CREATE INDEX houses_devices_tree_name ON houses_devices_tree(name);

ALTER TABLE cameras ADD COLUMN tree CHARACTER VARYING DEFAULT '';
CREATE INDEX cameras_tree ON cameras(tree);

ALTER TABLE houses_domophones ADD COLUMN tree CHARACTER VARYING DEFAULT '';
CREATE INDEX houses_domophones_tree ON houses_domophones(tree);

UPDATE cameras SET tree = '';
UPDATE houses_domophones SET tree = '';
