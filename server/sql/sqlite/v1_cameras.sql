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
    publish text,
    flussonic text,
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
    comment text
);
CREATE INDEX cameras_url on cameras(url);

