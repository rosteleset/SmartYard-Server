-- cameras
CREATE TABLE cameras
(
    camera_id serial PRIMARY KEY,
    enabled integer NOT NULL,
    model character varying NOT NULL,
    url character varying NOT NULL,
    stream character varying,
    credentials character varying NOT NULL,                                                                             -- plaintext:login:password, token:token, or something else
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
CREATE INDEX cameras_url ON cameras(url);

