ALTER TABLE addresses_regions ADD COLUMN lat real;
ALTER TABLE addresses_regions ADD COLUMN lon real;

ALTER TABLE addresses_areas ADD COLUMN lat real;
ALTER TABLE addresses_areas ADD COLUMN lon real;

ALTER TABLE addresses_cities ADD COLUMN lat real;
ALTER TABLE addresses_cities ADD COLUMN lon real;

ALTER TABLE addresses_settlements ADD COLUMN lat real;
ALTER TABLE addresses_settlements ADD COLUMN lon real;

ALTER TABLE addresses_streets ADD COLUMN lat real;
ALTER TABLE addresses_streets ADD COLUMN lon real;

ALTER TABLE addresses_houses ADD COLUMN lat real;
ALTER TABLE addresses_houses ADD COLUMN lon real;
