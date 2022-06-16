-- entrances
CREATE TABLE houses_entrances
(
    house_entrance_id integer not null primary key autoincrement,
    entrance_type text,
    entrance text not null,
    shared integer,
    lat real,
    lon real
);
CREATE INDEX houses_entrances_multihouse on houses_entrances(shared);

-- houses <-> entrances
CREATE TABLE houses_houses_entrances
(
    address_house_id integer not null,
    house_entrance_id integer not null
);
CREATE UNIQUE INDEX houses_houses_entrances_uniq on houses_houses_entrances(address_house_id, house_entrance_id);
CREATE INDEX houses_houses_entrances_address_house_id on houses_houses_entrances(address_house_id);
CREATE INDEX houses_houses_entrances_house_entrance_id on houses_houses_entrances(house_entrance_id);

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
    code text,                                                                                                          -- door open code
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

-- flats rfid keys
CREATE TABLE houses_flats_keys
(
    house_flat_key_id integer not null primary key autoincrement,
    house_flat_id integer not null,
    rfid text,
    last_seen text,                                                                                                     -- "YYYY-MM-DD HH:MM:SS.SSS"
    comments text
);
CREATE UNIQUE INDEX domophones_keys_uniq on houses_flats_keys(house_flat_id, rfid);
