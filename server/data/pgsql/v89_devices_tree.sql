CREATE TABLE core_devices_tree (leaf_id SERIAL PRIMARY KEY, tree CHARACTER VARYING, name CHARACTER VARYING);
CREATE INDEX core_devices_tree_tree ON core_devices_tree(tree);
CREATE INDEX core_devices_tree_name ON core_devices_tree(name);
