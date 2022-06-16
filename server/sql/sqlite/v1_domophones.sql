-- panels
CREATE TABLE domophones_devices
(
    domophone_device_id integer not null primary key autoincrement,
    model text not null,
    version text not null,
    ip text,
    credentials text,                                                                                                   -- plaintext:login:password, token:token, or something else
    cms text,                                                                                                           -- for visualization only
    caller_id text,
    comments text
);

-- entrances
CREATE TABLE domophones_entrances
(
    domophone_entrance_id integer not null primary key autoincrement,
    house_entrance_id integer not null,                                                                                 -- link to house entrance
    domophone_device_id integer,
    domophone_device_output integer,
    camera_device_id integer
);

-- panels <-> flats (cms attached)
CREATE TABLE domophones_panels_flats
(
    domophone_panel_id integer not null,
    house_flat_id integer not null,
    cms text not null,
    dozen integer not null,
    unit text not null,
    num integer                                                                                                         -- cms number
);
CREATE UNIQUE INDEX domophones_panels_flats_uniq on domophones_panels_flats(domophone_panel_id, house_flat_id);

-- domophone's specific flat settings
CREATE TABLE domophones_flats
(
    house_flat_id integer primary key not null,
    manual_block integer,                                                                                               -- 1/0 manaul blocking (by abonent?)
    auto_block integer,                                                                                                 -- 1/0 auto block (by billing system?)
    code text,                                                                                                          -- door open code
    cms integer,                                                                                                        -- 1/0 cms enabled
    cms_blocked integer,                                                                                                -- 1/0 cms blocked
    cms_levels text,                                                                                                    -- cms levels
    auto_open text,                                                                                                     -- "YYYY-MM-DD HH:MM:SS.SSS"
    white_rabbit integer,                                                                                               -- 1/0
    sip integer,                                                                                                        -- 0 - disabled, 1 - classic sip, 2 - webrtc
    sip_password text                                                                                                   -- sip password
);

-- rfid keys
CREATE TABLE domophones_keys
(
    domophone_key_id integer not null primary key autoincrement,
    type integer not null,                                                                                              -- 0 - subscriber, 1 - flat, 2 - device, 3 - house, 4 - house cluster, 5 - universal
    target_id integer not null,
    rfid text
);

