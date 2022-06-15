-- models
CREATE TABLE domophones_models
(
    domphone_model_id integer not null primary key autoincrement,
    model text,
    class text
);

-- panels
CREATE TABLE domophones_devices
(
    domophone_device_id integer not null primary key autoincrement,
    domophone_model_id integer not null,
    ip text,
    credentials text                                                                                                    -- plaintext:login:password, token:token, or something else
);

-- entrances
CREATE TABLE domophones_entrances
(
    address_house_id integer not null,                                                                                  -- link to house address
    house_entrance_id integer not null,                                                                                 -- link to house entrance
    domophone_device_id integer,
    domophone_device_output integer,
    camera_device_id integer,
    address text not null                                                                                               -- "backup" address string
);

-- panels <-> flats (cms attached)
CREATE TABLE domophones_panels_flats
(
    domophone_panel_id integer,
    house_flat_id integer
);
CREATE UNIQUE INDEX domophones_panels_flats_uniq on domophones_panels_flats(domophone_panel_id, house_flat_id);

-- domophone's specific flat settings
CREATE TABLE domophones_flats
(
    house_flat_id integer primary key not null,
    flat_number text,                                                                                                   -- "backup" flat number
    manual_block integer,                                                                                               -- 1/0 manaul blocking (by abonent?)
    auto_block integer,                                                                                                 -- 1/0 auto block (by billing system?)
    code text,                                                                                                          -- door open code
    cms integer,                                                                                                        -- 1/0 cms enabled
    cms_blocked integer,                                                                                                -- 1/0 cms blocked
    cms_number integer,                                                                                                 -- cms number
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
    type integer not null,                                                                                              -- 0 - subscriber, 1 - flat, 2 - engtrance, 3 - house, 4 - house cluster, 5 - universal
    target_id integer not null,
    rfid text
);

