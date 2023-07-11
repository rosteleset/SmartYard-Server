-- panels
CREATE TABLE houses_domophones
(
    house_domophone_id integer primary key autoincrement,
    enabled integer not null,
    model text not null,
    server text not null,
    url text not null,
    credentials text not null,                                                                                          -- plaintext:login:password, token:token, or something else
    dtmf text not null,
    first_time integer default 1,
    nat integer,
    locks_are_open integer default 1,
    ip text,
    comment text
);
CREATE UNIQUE INDEX domophones_ip_port on houses_domophones(url);

-- entrances
CREATE TABLE houses_entrances
(
    house_entrance_id integer primary key autoincrement,
    entrance_type text,
    entrance text not null,
    lat real,
    lon real,
    shared integer,
    plog integer,
    caller_id text,                                                                                                     -- callerId
-- domophone's specific entrance settings
    camera_id integer,
    house_domophone_id integer not null,
    domophone_output integer,
    cms text,                                                                                                           -- for visualization only
    cms_type integer,
    cms_levels text
);
CREATE UNIQUE INDEX houses_entrances_uniq on houses_entrances(house_domophone_id, domophone_output);
CREATE INDEX houses_entrances_multihouse on houses_entrances(shared);

-- domophones apartments -> cms
CREATE TABLE houses_entrances_cmses
(
    house_entrance_id integer not null,
    cms text not null,
    dozen integer not null,
    unit text not null,
    apartment integer not null                                                                                          -- flat number
);
CREATE UNIQUE INDEX houses_entrances_cmses_uniq1 on houses_entrances_cmses(house_entrance_id, cms, dozen, unit);
CREATE UNIQUE INDEX houses_entrances_cmses_uniq2 on houses_entrances_cmses(house_entrance_id, apartment);

-- houses <-> entrances
CREATE TABLE houses_houses_entrances
(
    address_house_id integer not null,
    house_entrance_id integer not null,
-- domophone's specific entrance settings
    prefix integer not null
);
CREATE UNIQUE INDEX houses_houses_entrances_uniq1 on houses_houses_entrances(address_house_id, house_entrance_id);
CREATE UNIQUE INDEX houses_houses_entrances_uniq2 on houses_houses_entrances(house_entrance_id, prefix);
CREATE INDEX houses_houses_entrances_address_house_id on houses_houses_entrances(address_house_id);
CREATE INDEX houses_houses_entrances_house_entrance_id on houses_houses_entrances(house_entrance_id);
CREATE INDEX houses_houses_entrances_prefix on houses_houses_entrances(prefix);

-- flats
CREATE TABLE houses_flats
(
    house_flat_id integer primary key autoincrement,
    address_house_id integer not null,
    floor integer,
    flat text not null,
    code text,                                                                                                          -- code for adding subscriber to flat
    plog integer,                                                                                                       -- 0 - disabled, 1 - all, 2 - owner only, 3 - disabled by administrator
-- domophone's specific flat settings
    manual_block integer,                                                                                               -- 1/0 manaul blocking (by abonent?)
    auto_block integer,                                                                                                 -- 1/0 auto block (by billing system?)
    admin_block integer,                                                                                                -- 1/0 blocked by admin
    open_code text,                                                                                                     -- door open code
    auto_open integer,                                                                                                  -- UNIX timestamp
    white_rabbit integer,                                                                                               -- 1/0
    sip_enabled integer,                                                                                                -- 0 - disabled, 1 - classic sip, 2 - webrtc
    sip_password text,                                                                                                  -- sip password
    last_opened integer,                                                                                                -- UNIX timestamp
    cms_enabled integer
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
    house_rfid_id integer primary key autoincrement,
    rfid text not null,
    access_type integer not null,                                                                                       -- 0 - universal, 1 - subscriber, 2 - flat, 3 - entrance, 4 - house
    access_to integer not null,                                                                                         -- 0 - universal, > 0 - subscriber_id, flat_id, ...
    last_seen integer,                                                                                                  -- UNIX timestamp
    comments text
);
CREATE UNIQUE INDEX houses_rfids_uniq on houses_rfids(rfid, access_type, access_to);

-- mobile subscribers
CREATE TABLE houses_subscribers_mobile
(
    house_subscriber_id integer primary key autoincrement,
    id text,                                                                                                            -- phone
    auth_token text,
    platform integer,                                                                                                   -- 0 - android, 1 - ios
    push_token text,
    push_token_type integer,                                                                                            -- 0 - fcm, 1 - apple, 2 - apple (dev), 3 - huawei
    voip_token text,                                                                                                    -- iOs only
    registered integer,                                                                                                 -- UNIX timestamp
    last_seen integer,                                                                                                  -- UNIX timestamp
    subscriber_name text,
    subscriber_patronymic text,
    voip_enabled integer
);
CREATE UNIQUE INDEX subscribers_mobile_id on houses_subscribers_mobile(id);

-- flats <-> subscribers
CREATE TABLE houses_flats_subscribers
(
    house_flat_id integer not null,
    house_subscriber_id integer not null,
    role integer                                                                                                        -- ?
);
CREATE UNIQUE INDEX houses_flats_subscribers_uniq on houses_flats_subscribers(house_flat_id, house_subscriber_id);

-- cameras <-> houses
CREATE TABLE houses_cameras_houses
(
    camera_id integer not null,
    address_house_id integer not null,
    common integer
);
CREATE UNIQUE INDEX houses_cameras_houses_uniq on houses_cameras_houses(camera_id, address_house_id);
CREATE INDEX houses_cameras_houses_camera_id on houses_cameras_houses(camera_id);
CREATE INDEX houses_cameras_houses_house_id on houses_cameras_houses(address_house_id);

-- cameras <-> flats
CREATE TABLE houses_cameras_flats
(
    camera_id integer not null,
    house_flat_id integer not null,
    common integer
);
CREATE UNIQUE INDEX houses_cameras_flats_uniq on houses_cameras_flats(camera_id, house_flat_id);
CREATE INDEX houses_cameras_flats_camera_id on houses_cameras_flats(camera_id);
CREATE INDEX houses_cameras_flats_flat_id on houses_cameras_flats(house_flat_id);

-- cameras <-> subscribers
CREATE TABLE houses_cameras_subscribers
(
    camera_id integer not null,
    house_subscriber_id integer not null
);
CREATE UNIQUE INDEX houses_cameras_subscribers_uniq on houses_cameras_subscribers(camera_id, house_subscriber_id);
CREATE INDEX houses_cameras_subscribers_camera_id on houses_cameras_subscribers(camera_id);
CREATE INDEX houses_cameras_subscribers_subscriber_id on houses_cameras_subscribers(house_subscriber_id);
