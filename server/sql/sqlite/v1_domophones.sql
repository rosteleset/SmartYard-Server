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

-- domophones cmses
CREATE TABLE domophones_cmses
(
    domophone_id integer not null,
    apartment integer not null,                                                                                         -- flat number
    cms text not null,
    dozen integer not null,
    unit text not null
);
CREATE UNIQUE INDEX domophones_cmses_uniq on domophones_cmses(domophone_id, cms, dozen, unit);

-- domophones flats
CREATE TABLE domophones_flats
(
    domophone_id integer not null,
    apartment integer not null,                                                                                         -- flat number
    cms_levels text,                                                                                                    -- cms levels
    house_flat_id integer not null
);
CREATE UNIQUE INDEX domophones_flats_uniq on domophones_flats(domophone_id, apartment, house_flat_id);

-- domophone's specific flat settings
CREATE TABLE domophones_flats
(
    house_flat_id integer primary key not null,
    manual_block integer,                                                                                               -- 1/0 manaul blocking (by abonent?)
    auto_block integer,                                                                                                 -- 1/0 auto block (by billing system?)
    code text,                                                                                                          -- door open code
    auto_open text,                                                                                                     -- "YYYY-MM-DD HH:MM:SS.SSS"
    white_rabbit integer,                                                                                               -- 1/0
    sip_enabled integer,                                                                                                -- 0 - disabled, 1 - classic sip, 2 - webrtc
    sip_password text                                                                                                   -- sip password
);

-- rfid keys
CREATE TABLE domophones_keys
(
    domophone_key_id integer not null primary key autoincrement,
    type integer not null,                                                                                              -- 0 - universal, 1 - subscriber, 2 - flat, 3 - device, 4 - house
    target_id integer not null,
    rfid text
);

