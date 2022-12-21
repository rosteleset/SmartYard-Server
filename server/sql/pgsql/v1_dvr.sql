CREATE TABLE camera_records
(
    record_id serial primary key,
    camera_id integer not null,
    subscriber_id integer not null,
    start integer,
    finish integer,
    filename text,
    expire integer,
    state integer                                                                                                       -- 0 = created, 1 = in progress, 2 = completed, 3 = error
);
CREATE INDEX camera_records_status on camera_records(state);
CREATE INDEX camera_records_expire on camera_records(expire);
