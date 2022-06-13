-- entrances
CREATE TABLE houses_entrances
(
    house_entrance_id integer not null primary key autoincrement,
    entrance_type text,
    entrance_type_full text,
    entrance text not null,
    multihouse integer default 0
);
CREATE INDEX houses_entrances_multihouse on houses_entrances(multihouse);

-- houses <-> entrances
CREATE TABLE houses_houses_entrances
(
    houses_house_entrance_id integer not null primary key autoincrement,
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
    flat text not null
);
CREATE UNIQUE INDEX houses_flats_uniq on houses_flats(address_house_id, flat);
CREATE INDEX houses_flats_address_house_id on houses_flats(address_house_id);

-- entrances <-> flats
CREATE TABLE houses_entrances_flats
(
    houses_entrance_flat_id integer not null primary key autoincrement,
    house_entrance_id integer not null,
    house_flat_id integer not null
);
CREATE UNIQUE INDEX houses_entrances_flats_uniq on houses_entrances_flats (house_entrance_id, house_flat_id);
CREATE INDEX houses_entrances_flats_house_entrance_id on houses_entrances_flats(house_entrance_id);
CREATE INDEX houses_entrances_flats_house_flat_id on houses_entrances_flats(house_flat_id);