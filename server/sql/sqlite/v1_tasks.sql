-- tasks
CREATE TABLE tasks
(
    task_id integer not null primary key autoincrement,
    object_type text,
    object_id integer,
    task text,
    params text
);

CREATE TABLE tasks_config_queue
(
    task_queue_id integer not null primary key autoincrement,
    object_type text,
    object_id integer
);
CREATE UNIQUE INDEX tasks_config_queue_uniq on tasks_config_queue(object_type, object_id);
