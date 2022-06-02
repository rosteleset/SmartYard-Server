CREATE TABLE buildings (bid integer not null primary key autoincrement, address text not null, guid text);
CREATE UNIQUE INDEX buildings_guid on buildings(guid);
CREATE UNIQUE INDEX buildings_address on buildings(address);
CREATE TABLE entrances (eid integer not null primary key autoincrement, bid integer not null, entrance text not null);
CREATE INDEX entrances_bid on entrances(bid);
CREATE TABLE flats (fid integer not null primary key autoincrement, eid integer not null, flat_number integer, floor integer);
CREATE INDEX flats_eid on flats(eid);
CREATE INDEX flats_floor on flats(floor);
CREATE UNIQUE INDEX flats_flat_number on flats(flat_number);