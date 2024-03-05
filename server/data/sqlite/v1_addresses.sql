-- regions
CREATE TABLE addresses_regions
(
    address_region_id integer primary key autoincrement,
    region_uuid text,
    region_iso_code text,
    region_with_type text not null,
    region_type text,
    region_type_full text,
    region text not null,
    timezone text
);
CREATE UNIQUE INDEX addresses_regions_region_uuid on addresses_regions(region_uuid);
CREATE UNIQUE INDEX addresses_regions_region on addresses_regions(region);

-- areas
CREATE TABLE addresses_areas
(
    address_area_id integer primary key autoincrement,
    address_region_id integer not null,
    area_uuid text,
    area_with_type text not null,
    area_type text,
    area_type_full text,
    area text not null,
    timezone text
);
CREATE UNIQUE INDEX addresses_areas_area_uuid on addresses_areas(area_uuid);
CREATE UNIQUE INDEX addresses_areas_area on addresses_areas(address_region_id, area);
CREATE INDEX addresses_areas_address_region_id on addresses_areas(address_region_id);

-- cities
CREATE TABLE addresses_cities
(
    address_city_id integer primary key autoincrement,
    address_region_id integer,
    address_area_id integer,
    city_uuid text,
    city_with_type text not null,
    city_type text,
    city_type_full text,
    city text not null,
    timezone text
);
CREATE UNIQUE INDEX addresses_cities_city_uuid on addresses_cities(city_uuid);
CREATE UNIQUE INDEX addresses_cities_city on addresses_cities(address_region_id, address_area_id, city);
CREATE INDEX addresses_cities_address_region_id on addresses_cities(address_region_id);
CREATE INDEX addresses_cities_address_area_id on addresses_cities(address_area_id);

-- settlements
CREATE TABLE addresses_settlements
(
    address_settlement_id integer primary key autoincrement,
    address_area_id integer,
    address_city_id integer,
    settlement_uuid text,
    settlement_with_type text not null,
    settlement_type text,
    settlement_type_full text,
    settlement text not null
);
CREATE UNIQUE INDEX addresses_settlements_settlement_uuid on addresses_settlements(settlement_uuid);
CREATE UNIQUE INDEX addresses_settlements_settlement on addresses_settlements(address_area_id, address_city_id, settlement);
CREATE INDEX addresses_settlements_address_region_id on addresses_settlements(address_city_id);
CREATE INDEX addresses_settlements_address_area_id on addresses_settlements(address_area_id);

-- streets
CREATE TABLE addresses_streets
(
    address_street_id integer primary key autoincrement,
    address_city_id integer,
    address_settlement_id integer,
    street_uuid text,
    street_with_type text not null,
    street_type text,
    street_type_full text,
    street text not null
);
CREATE UNIQUE INDEX addresses_streets_street_uuid on addresses_streets(street_uuid);
CREATE UNIQUE INDEX addresses_streets_street on addresses_streets(address_city_id, address_settlement_id, street);
CREATE INDEX addresses_streets_address_address_settlement_id on addresses_streets(address_settlement_id);
CREATE INDEX addresses_streets_address_address_city_id on addresses_streets(address_city_id);

-- houses
CREATE TABLE addresses_houses
(
    address_house_id integer primary key autoincrement,
    address_settlement_id integer,
    address_street_id integer,
    house_uuid text,
    house_type text,
    house_type_full text,
    house_full text not null,
    house text not null
);
CREATE UNIQUE INDEX addresses_houses_house_uuid on addresses_houses(house_uuid);
CREATE UNIQUE INDEX addresses_houses_house on addresses_houses(address_settlement_id, address_street_id, house);
CREATE INDEX addresses_houses_address_settlement_id on addresses_houses(address_settlement_id);
CREATE INDEX addresses_houses_address_street_id on addresses_houses(address_street_id);
CREATE INDEX addresses_houses_house_full on addresses_houses(house_full);
