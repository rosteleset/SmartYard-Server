-- tasks_changes
CREATE TABLE tasks_changes
(
    task_change_id integer not null primary key autoincrement,
    object_type text,
    object_id integer
);
CREATE UNIQUE INDEX tasks_changes_uniq on tasks_changes(object_type, object_id);

CREATE TABLE tasks_queue
(
    task_queue_id integer not null primary key autoincrement,
    object_type text,
    object_id integer,
    task text,
    params text
);
CREATE UNIQUE INDEX tasks_config_queue_uniq on tasks_config_queue(object_type, object_id);
