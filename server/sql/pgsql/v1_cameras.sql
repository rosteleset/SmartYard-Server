-- cameras
CREATE TABLE cameras
(
    camera_id serial primary key,
    enabled integer not null,
    model character varying not null,
    url character varying not null,
    stream character varying,
    credentials character varying not null,                                                                             -- plaintext:login:password, token:token, or something else
    name character varying,
    dvr_stream character varying,
    timezone character varying, 
    lat real,
    lon real,
    direction real,
    angle real,
    distance real,
    frs character varying,
    md_left integer,
    md_top integer,
    md_width integer,
    md_height integer,
    common integer,
    ip text,
    comment character varying
);
CREATE INDEX cameras_url on cameras(url);

