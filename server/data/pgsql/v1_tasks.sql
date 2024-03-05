-- tasks_changes
CREATE TABLE tasks_changes
(
    task_change_id serial primary key,
    object_type character varying,
    object_id integer
);
CREATE UNIQUE INDEX tasks_changes_uniq on tasks_changes(object_type, object_id);
