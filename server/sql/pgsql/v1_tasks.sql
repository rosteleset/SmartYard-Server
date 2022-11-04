-- tasks
CREATE TABLE tasks
(
    task_id serial primary key,
    object_type character varying,
    object_id integer,
    task character varying,
    params character varying,
    pid integer,
    done integer,
    started timestamp,                                                                                                  -- "YYYY-MM-DD HH:MM:SS.SSS"
    ended timestamp                                                                                                     -- "YYYY-MM-DD HH:MM:SS.SSS"
);

CREATE TABLE tasks_config_queue
(
    task_queue_id serial primary key,
    object_type character varying,
    object_id integer
);