-- models
CREATE TABLE domophones_models
(
    domphone_model_id integer not null primary key autoincrement,
    model text
);

-- panels
CREATE TABLE domophones_panels
(
    domophone_panel_id integer not null primary key autoincrement,
    url text,
    domphone_model_id integer
);
CREATE UNIQUE INDEX domophones_panels_uniq on domophones_panels(url);

-- panels <-> entrances
CREATE TABLE domophones_panels_entrances
(
    domophone_panel_entrance_id integer not null primary key autoincrement,
    domophone_panel_id integer,
    house_entrance_id integer
);
CREATE UNIQUE INDEX domophones_panels_entrances_uniq on domophones_panels_entrances(domophone_panel_id, house_entrance_id);

-- panels <-> flats (cms attached)
CREATE TABLE domophones_panels_flats
(
    domophone_panel_flat_id integer not null primary key autoincrement,
    domophone_panel_id integer,
    house_flat_id integer
);
CREATE UNIQUE INDEX domophones_panels_flats_uniq on domophones_panels_flats(domophone_panel_id, house_flat_id);

-- domophone's specific flat settings
CREATE TABLE domophones_flats
(
    house_flat_id integer primary key not null,
    blocked integer default 0,
    code text,
    cms integer default 0,
    cms_number integer,
    cms_blocked integer default 0,
    cms_levels text,
    auto_open text,
    white_rabbit text,
    sip integer default 0,
    sip_password text
);
