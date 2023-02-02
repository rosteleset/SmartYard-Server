-- plog_door_open
CREATE TABLE plog_door_open
(
    plog_door_open_id integer primary key autoincrement,
    date integer,                                                                                                       -- UNIX timestamp
    ip text,
    event integer,
    door integer,
    detail text,
    expire integer
);
CREATE INDEX plog_door_open_date on plog_door_open (date);
CREATE INDEX plog_door_open_expire on plog_door_open (expire);

-- plog_call_done
CREATE TABLE plog_call_done
(
    plog_call_done_id integer primary key autoincrement,
    date integer,                                                                                                       -- UNIX timestamp
    ip text,
    call_id integer,
    expire integer
);
CREATE INDEX plog_call_done_date on plog_call_done (date);
CREATE INDEX plog_call_done_expire on plog_call_done (expire);
