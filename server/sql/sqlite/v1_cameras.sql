-- cameras
CREATE TABLE cameras
(
    camera_id integer not null primary key autoincrement,
    enabled integer not null,
    model text not null,
    ip text not null ,
    port integer not null,
    rtsp_stream text,
    credentials text not null,                                                                                          -- plaintext:login:password, token:token, or something else
    comment text
);
CREATE INDEX cameras_ip on cameras(ip);

