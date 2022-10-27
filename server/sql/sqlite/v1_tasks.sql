-- tasks
CREATE TABLE tasks
(
    task_id integer not null primary key autoincrement,
    object_type text,
    object_id integer,
    task text,
    paraams text
);

