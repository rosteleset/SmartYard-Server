-- cameras
CREATE TABLE cameras
(
    camera_id serial primary key,
    enabled integer not null,
    model character varying not null,
    ip character varying not null ,
    port integer not null,
    stream character varying,
    credentials character varying not null,                                                                                          -- plaintext:login:password, token:token, or something else
    comment character varying
);
CREATE INDEX cameras_ip on cameras(ip);

