-- tasks
CREATE TABLE tasks
(
    task_id integer not null primary key autoincrement,
    object_type text,
    object_id integer,
    task text,
    params text,
    pid integer,
    done integer,
    started text,                                                                                                     -- "YYYY-MM-DD HH:MM:SS.SSS"
    ended text                                                                                                        -- "YYYY-MM-DD HH:MM:SS.SSS"
);

CREATE TABLE tasks_config_queue
(
    task_queue_id integer not null primary key autoincrement,
    object_type text,
    object_id integer
);