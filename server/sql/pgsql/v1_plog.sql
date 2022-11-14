-- plog_door_open
CREATE TABLE plog_door_open
(
    plog_door_open_id serial primary key,
    date timestamp,
    ip character varying,
    event integer,
    door integer,
    detail character varying
);
CREATE INDEX plog_door_open_date on plog_door_open (date);

-- plog_call_done
CREATE TABLE plog_call_done
(
    plog_call_done_id serial primary key,
    date timestamp,
    ip character varying,
    call_id integer
);
CREATE INDEX plog_call_done_date on plog_call_done (date);
