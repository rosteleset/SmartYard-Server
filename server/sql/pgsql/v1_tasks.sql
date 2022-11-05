-- tasks_changes
CREATE TABLE tasks_changes
(
    task_change_id serial primary key,
    object_type character varying,
    object_id integer
);
CREATE UNIQUE INDEX tasks_changes_uniq on tasks_changes(object_type, object_id);

CREATE TABLE tasks_queue
(
    task_queue_id serial primary key,
    object_type character varying,
    object_id integer,
    task character varying,
    params character varying
);
CREATE UNIQUE INDEX tasks_config_queue_uniq on tasks_config_queue(object_type, object_id);
