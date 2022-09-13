-- cameras
CREATE TABLE cameras
(
    camera_id integer not null primary key autoincrement,
    enabled integer not null,
    model text not null,
    url text not null,
    stream text,
    credentials text not null,                                                                                          -- plaintext:login:password, token:token, or something else
    publish text,
    flussonic text,
    comment text
);
CREATE INDEX cameras_url on cameras(url);

