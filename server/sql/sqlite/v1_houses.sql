-- entrances
CREATE TABLE houses_entrances
(
    house_entrance_id integer not null primary key autoincrement,
    entrance_type text,
    entrance text not null,
    lat real,
    lon real,
    shared integer,
-- domophone's specisic entrance settings
    domophone_id integer not null,
    domophone_output integer,
    cms text,                                                                                                           -- for visualization only
    cms_type integer,
    camera_id integer
);
CREATE UNIQUE INDEX houses_entrances_uniq on houses_entrances(domophone_id, domophone_output);
CREATE INDEX houses_entrances_multihouse on houses_entrances(shared);

-- domophones apartments -> cms
CREATE TABLE houses_entrances_cmses
(
    domophone_id integer not null,
    apartment integer not null,                                                                                         -- flat number
    cms text not null,
    dozen integer not null,
    unit text not null
);
CREATE UNIQUE INDEX houses_entrances_cmses_uniq on houses_entrances_cmses(domophone_id, cms, dozen, unit);

-- houses <-> entrances
CREATE TABLE houses_houses_entrances
(
    address_house_id integer not null,
    house_entrance_id integer not null,
-- domophone's specisic entrance settings
    prefix integer not null
);
CREATE UNIQUE INDEX houses_houses_entrances_uniq_1 on houses_houses_entrances(address_house_id, house_entrance_id);
CREATE UNIQUE INDEX houses_houses_entrances_uniq_2 on houses_houses_entrances(house_entrance_id, prefix);
CREATE INDEX houses_houses_entrances_address_house_id on houses_houses_entrances(address_house_id);
CREATE INDEX houses_houses_entrances_house_entrance_id on houses_houses_entrances(house_entrance_id);
CREATE INDEX houses_houses_entrances_prefix on houses_houses_entrances(prefix);

-- flats
CREATE TABLE houses_flats
(
    house_flat_id integer not null primary key autoincrement,
    address_house_id integer not null,
    floor integer,
    flat text not null,
-- domophone's specific flat settings
    manual_block integer,                                                                                               -- 1/0 manaul blocking (by abonent?)
    auto_block integer,                                                                                                 -- 1/0 auto block (by billing system?)
    open_code text,                                                                                                     -- door open code
    auto_open text,                                                                                                     -- "YYYY-MM-DD HH:MM:SS.SSS"
    white_rabbit integer,                                                                                               -- 1/0
    sip_enabled integer,                                                                                                -- 0 - disabled, 1 - classic sip, 2 - webrtc
    sip_password text                                                                                                   -- sip password
);
CREATE UNIQUE INDEX houses_flats_uniq on houses_flats(address_house_id, flat);
CREATE INDEX houses_flats_address_house_id on houses_flats(address_house_id);

-- entrances <-> flats
CREATE TABLE houses_entrances_flats
(
    house_entrance_id integer not null,
    house_flat_id integer not null,
-- domophone's specific flat settings
    apartment integer,                                                                                                  -- flat number
    cms_levels text                                                                                                     -- cms levels
);
CREATE UNIQUE INDEX houses_entrances_flats_uniq on houses_entrances_flats (house_entrance_id, house_flat_id);
CREATE INDEX houses_entrances_flats_house_entrance_id on houses_entrances_flats(house_entrance_id);
CREATE INDEX houses_entrances_flats_house_flat_id on houses_entrances_flats(house_flat_id);

-- rfid keys
CREATE TABLE houses_rfids
(
    house_rfid_id integer not null primary key autoincrement,
    rfid text not null,
    access_type integer not null,                                                                                       -- 0 - universal, 1 - subscriber, 2 - flat, 3 - entrance, 4 - house
    access_to integer not null,                                                                                         -- 0 - universal, > 0 - subscribers_id, flat_id, ...
    last_seen text,                                                                                                     -- "YYYY-MM-DD HH:MM:SS.SSS"
    comments text
);
CREATE UNIQUE INDEX houses_rfids_uniq on houses_rfids(rfid, access_type, access_to);

-- flats <-> subscribers
CREATE TABLE houses_flats_subscribers
(
    house_flat_id integer not null,
    subscriber_mobile_id integer not null,
    role integer                                                                                                        -- ?
);
CREATE UNIQUE INDEX houses_flats_subscribers_uniq on houses_flats_subscribers(house_flat_id, subscriber_mobile_id);
