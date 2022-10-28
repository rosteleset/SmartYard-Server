-- tasks
CREATE TABLE tasks
(
    task_id serial primary key,
    object_type character varying,
    object_id integer,
    task character varying,
    paraams character varying,
    pid integer,
    done integer,
    started string,                                                                                                     -- "YYYY-MM-DD HH:MM:SS.SSS"
    ended string                                                                                                        -- "YYYY-MM-DD HH:MM:SS.SSS"
);

CREATE TABLE tasks_config_queue
(
    task_queue_id serial primary key,
    object_type character varying,
    object_id integer
);