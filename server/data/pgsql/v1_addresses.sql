-- regions
CREATE TABLE addresses_regions
(
    address_region_id serial PRIMARY KEY,
    region_uuid character varying,
    region_iso_code character varying,
    region_with_type character varying NOT NULL,
    region_type character varying,
    region_type_full character varying,
    region character varying NOT NULL,
    timezone character varying
);
CREATE UNIQUE INDEX addresses_regions_region_uuid ON addresses_regions(region_uuid);
CREATE UNIQUE INDEX addresses_regions_region ON addresses_regions(region);

-- areas
CREATE TABLE addresses_areas
(
    address_area_id serial PRIMARY KEY,
    address_region_id integer NOT NULL,
    area_uuid character varying,
    area_with_type character varying NOT NULL,
    area_type character varying,
    area_type_full character varying,
    area character varying NOT NULL,
    timezone character varying
);
CREATE UNIQUE INDEX addresses_areas_area_uuid ON addresses_areas(area_uuid);
CREATE UNIQUE INDEX addresses_areas_area ON addresses_areas(address_region_id, area);
CREATE INDEX addresses_areas_address_region_id ON addresses_areas(address_region_id);

-- cities
CREATE TABLE addresses_cities
(
    address_city_id serial PRIMARY KEY,
    address_region_id integer,
    address_area_id integer,
    city_uuid character varying,
    city_with_type character varying NOT NULL,
    city_type character varying,
    city_type_full character varying,
    city character varying NOT NULL,
    timezone character varying
);
CREATE UNIQUE INDEX addresses_cities_city_uuid ON addresses_cities(city_uuid);
CREATE UNIQUE INDEX addresses_cities_city ON addresses_cities(address_region_id, address_area_id, city);
CREATE INDEX addresses_cities_address_region_id ON addresses_cities(address_region_id);
CREATE INDEX addresses_cities_address_area_id ON addresses_cities(address_area_id);

-- settlements
CREATE TABLE addresses_settlements
(
    address_settlement_id serial PRIMARY KEY,
    address_area_id integer,
    address_city_id integer,
    settlement_uuid character varying,
    settlement_with_type character varying NOT NULL,
    settlement_type character varying,
    settlement_type_full character varying,
    settlement character varying NOT NULL 
);
CREATE UNIQUE INDEX addresses_settlements_settlement_uuid ON addresses_settlements(settlement_uuid);
CREATE UNIQUE INDEX addresses_settlements_settlement ON addresses_settlements(address_area_id, address_city_id, settlement);
CREATE INDEX addresses_settlements_address_region_id ON addresses_settlements(address_city_id);
CREATE INDEX addresses_settlements_address_area_id ON addresses_settlements(address_area_id);

-- streets
CREATE TABLE addresses_streets
(
    address_street_id serial PRIMARY KEY,
    address_city_id integer,
    address_settlement_id integer,
    street_uuid character varying,
    street_with_type character varying NOT NULL,
    street_type character varying,
    street_type_full character varying,
    street character varying NOT NULL 
);
CREATE UNIQUE INDEX addresses_streets_street_uuid ON addresses_streets(street_uuid);
CREATE UNIQUE INDEX addresses_streets_street ON addresses_streets(address_city_id, address_settlement_id, street);
CREATE INDEX addresses_streets_address_address_settlement_id ON addresses_streets(address_settlement_id);
CREATE INDEX addresses_streets_address_address_city_id ON addresses_streets(address_city_id);

-- houses
CREATE TABLE addresses_houses
(
    address_house_id serial PRIMARY KEY,
    address_settlement_id integer,
    address_street_id integer,
    house_uuid character varying,
    house_type character varying,
    house_type_full character varying,
    house_full character varying NOT NULL,
    house character varying NOT NULL 
);
CREATE UNIQUE INDEX addresses_houses_house_uuid ON addresses_houses(house_uuid);
CREATE UNIQUE INDEX addresses_houses_house ON addresses_houses(address_settlement_id, address_street_id, house);
CREATE INDEX addresses_houses_address_settlement_id ON addresses_houses(address_settlement_id);
CREATE INDEX addresses_houses_address_street_id ON addresses_houses(address_street_id);
CREATE INDEX addresses_houses_house_full ON addresses_houses(house_full);
