-- tasks
CREATE TABLE tasks
(
    task_id serial primary key,
    object_type character varying,
    object_id integer,
    task character varying,
    paraams character varying
);

