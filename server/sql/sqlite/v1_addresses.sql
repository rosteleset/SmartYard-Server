-- regions
CREATE TABLE addresses_regions
(
    address_region_id integer not null primary key autoincrement,
    region_fias_id text,
    region_iso_code text,
    region_with_type text not null,
    region_type text,
    region_type_full text,
    region text not null
);
CREATE UNIQUE INDEX addresses_regions_region_fias_id on addresses_regions(region_fias_id);
CREATE UNIQUE INDEX addresses_regions_region on addresses_regions(region);

-- areas
CREATE TABLE addresses_areas
(
    address_area_id integer not null primary key autoincrement,
    address_region_id integer not null,
    area_fias_id text,
    area_with_type text not null,
    area_type text,
    area_type_full text,
    area text not null
);
CREATE UNIQUE INDEX addresses_areas_area_fias_id on addresses_areas(area_fias_id);
CREATE UNIQUE INDEX addresses_areas_area on addresses_areas(address_region_id, area);
CREATE INDEX addresses_areas_address_region_id on addresses_areas(address_region_id);

-- cities
CREATE TABLE addresses_cities
(
    address_city_id integer not null primary key autoincrement,
    address_region_id integer,
    address_area_id integer,
    city_fias_id text,
    city_with_type text not null,
    city_type text,
    city_type_full text,
    city text not null
);
CREATE UNIQUE INDEX addresses_cities_city_fias_id on addresses_cities(city_fias_id);
CREATE UNIQUE INDEX addresses_cities_city on addresses_cities(address_region_id, address_area_id, city);
CREATE INDEX addresses_cities_address_region_id on addresses_cities(address_region_id);
CREATE INDEX addresses_cities_address_area_id on addresses_cities(address_area_id);

-- settlements
CREATE TABLE addresses_settlements
(
    address_settlement_id integer not null primary key autoincrement,
    address_area_id integer,
    address_city_id integer,
    settlement_fias_id text,
    settlement_with_type text not null,
    settlement_type text,
    settlement_type_full text,
    settlement text not null
);
CREATE UNIQUE INDEX addresses_settlements_settlement_fias_id on addresses_settlements(settlement_fias_id);
CREATE UNIQUE INDEX addresses_settlements_settlement on addresses_settlements(address_area_id, address_city_id, settlement);
CREATE INDEX addresses_settlements_address_region_id on addresses_settlements(address_city_id);
CREATE INDEX addresses_settlements_address_area_id on addresses_settlements(address_area_id);

-- streets
CREATE TABLE addresses_streets
(
    address_street_id integer not null primary key autoincrement,
    address_city_id integer,
    address_settlement_id integer,
    street_fias_id text,
    street_with_type text not null,
    street_type text,
    street_type_full text,
    street text not null
);
CREATE UNIQUE INDEX addresses_streets_street_fias_id on addresses_streets(street_fias_id);
CREATE UNIQUE INDEX addresses_streets_street on addresses_streets(address_city_id, address_settlement_id, street);
CREATE INDEX addresses_streets_address_address_settlement_id on addresses_streets(address_settlement_id);
CREATE INDEX addresses_streets_address_address_city_id on addresses_streets(address_city_id);

-- houses
CREATE TABLE addresses_houses
(
    address_house_id integer not null primary key autoincrement,
    address_settlement_id integer,
    address_street_id integer,
    house_fias_id text,
    house_type text,
    house_type_full text,
    house text not null
);
CREATE UNIQUE INDEX addresses_houses_house_fias_id on addresses_houses(house_fias_id);
CREATE UNIQUE INDEX addresses_houses_house on addresses_houses(address_settlement_id, address_street_id, house);
CREATE INDEX addresses_houses_address_settlement_id on addresses_houses(address_settlement_id);
CREATE INDEX addresses_houses_address_street_id on addresses_houses(address_street_id);

-- entrances
CREATE TABLE addresses_entrances
(
    address_entrance_id integer not null primary key autoincrement,
    address_house_id integer,
    entrance text not null
);
CREATE UNIQUE INDEX addresses_entrances_entrance on addresses_entrances(address_house_id, entrance);

-- flats
CREATE TABLE addresses_flats
(
    address_flat_id integer not null primary key autoincrement,
    address_entrance_id integer,
    address_house_id integer,
    floor integer,
    flat_fias_id text,
    flat_type text,
    flat_type_full text,
    flat text not null
);
CREATE UNIQUE INDEX addresses_flats_flat_fias_id on addresses_flats(flat_fias_id);
CREATE UNIQUE INDEX addresses_flats_flat on addresses_flats(address_house_id, flat);
CREATE INDEX addresses_flats_address_entrance_id on addresses_flats(address_entrance_id);
CREATE INDEX addresses_flats_address_house_id on addresses_flats(address_house_id);
