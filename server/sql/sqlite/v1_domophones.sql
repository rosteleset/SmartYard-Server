-- panels
CREATE TABLE domophones
(
    domophone_id integer not null primary key autoincrement,
    enabled integer,
    model text not null,
    version text not null,
    cms text,                                                                                                           -- for visualization only
    ip text,
    credentials text,                                                                                                   -- plaintext:login:password, token:token, or something else
    caller_id text,
    comments text,
    locks_disabled integer,
    cms_levels text
);
CREATE INDEX domophones_ip on domophones(ip);

-- entrances
CREATE TABLE domophones_entrances
(
    house_entrance_id integer not null primary key,                                                                     -- link to house entrance
    domophone_id integer not null,
    domophone_output integer,
    camera_id integer
);
CREATE UNIQUE INDEX domophones_entrances_uniq on domophones_entrances(domophone_id, domophone_output);

-- domophones apartments -> cms
CREATE TABLE domophones_cmses
(
    domophone_id integer not null,
    apartment integer not null,                                                                                         -- flat number
    cms text not null,
    dozen integer not null,
    unit text not null
);
CREATE UNIQUE INDEX domophones_cmses_uniq on domophones_cmses(domophone_id, cms, dozen, unit);

-- domophones rfid keys
CREATE TABLE domophones_keys
(
    domophone_key_id integer not null primary key autoincrement,
    domophone_id integer not null,
    rfid text,
    last_seen text,                                                                                                     -- "YYYY-MM-DD HH:MM:SS.SSS"
    comments text
);
CREATE UNIQUE INDEX domophones_keys_uniq on domophones_keys(domophone_id, rfid);

