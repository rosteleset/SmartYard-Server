DROP INDEX tasks_changes_uniq;
ALTER TABLE tasks_changes DROP COLUMN object_id;
ALTER TABLE tasks_changes ADD COLUMN object_id text;
CREATE UNIQUE INDEX tasks_changes_uniq on tasks_changes(object_type, object_id);
