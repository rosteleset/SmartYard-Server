-- cameras
CREATE TABLE cameras
(
    camera_id serial primary key,
    enabled integer not null,
    model character varying not null,
    url character varying not null,
    stream character varying,
    credentials character varying not null,                                                                                          -- plaintext:login:password, token:token, or something else
    publish character varying,
    flussonic character varying,
    comment character varying
);
CREATE INDEX cameras_url on cameras(url);

