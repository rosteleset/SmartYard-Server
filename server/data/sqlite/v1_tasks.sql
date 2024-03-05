-- tasks_changes
CREATE TABLE tasks_changes
(
    task_change_id integer primary key autoincrement,
    object_type text,
    object_id integer
);
CREATE UNIQUE INDEX tasks_changes_uniq on tasks_changes(object_type, object_id);
