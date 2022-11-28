-- cameras
CREATE TABLE cameras
(
    camera_id integer not null primary key autoincrement,
    enabled integer not null,
    model text not null,
    url text not null,
    stream text,
    credentials text not null,                                                                                          -- plaintext:login:password, token:token, or something else
    name text,
    dvr_stream text,
    lat real,
    lon real,
    direction real,
    angle real,
    distance real,
    frs text,
    md_left integer,
    md_top integer,
    md_width integer,
    md_height integer,
    common integer,
    ip text,
    comment text
);
CREATE INDEX cameras_url on cameras(url);

CREATE TABLE camera_records
(
    record_id integer not null primary key autoincrement,
    camera_id integer not null,
    subscriber_id integer not null,
    start integer,
    finish integer,
    filename text,
    expire integer,
    state integer -- 0 = created, 1 = in progress, 2 = completed, 3 = error
);
CREATE INDEX camera_records_status on camera_records(state);
CREATE INDEX camera_records_expire on camera_records(expire);
