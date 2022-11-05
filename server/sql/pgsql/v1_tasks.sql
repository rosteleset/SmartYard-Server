-- tasks
CREATE TABLE tasks
(
    task_id serial primary key,
    object_type character varying,
    object_id integer,
    task character varying,
    params character varying
);

CREATE TABLE tasks_config_queue
(
    task_queue_id serial primary key,
    object_type character varying,
    object_id integer
);
CREATE UNIQUE INDEX tasks_config_queue_uniq on tasks_config_queue(object_type, object_id);
