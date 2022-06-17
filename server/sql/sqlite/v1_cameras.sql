-- cameras
CREATE TABLE cameras
(
    camera_id integer not null primary key autoincrement,
    enabled integer,
    model text not null,
    version text not null,
    ip text,
    credentials text,                                                                                                   -- plaintext:login:password, token:token, or something else
    comments text
);
CREATE INDEX cameras_ip on cameras(ip);

