ALTER TABLE addresses_regions ADD IF NOT EXISTS lat real;
ALTER TABLE addresses_regions ADD IF NOT EXISTS lon real;

ALTER TABLE addresses_areas ADD IF NOT EXISTS lat real;
ALTER TABLE addresses_areas ADD IF NOT EXISTS lon real;

ALTER TABLE addresses_cities ADD IF NOT EXISTS lat real;
ALTER TABLE addresses_cities ADD IF NOT EXISTS lon real;

ALTER TABLE addresses_settlements ADD IF NOT EXISTS lat real;
ALTER TABLE addresses_settlements ADD IF NOT EXISTS lon real;

ALTER TABLE addresses_streets ADD IF NOT EXISTS lat real;
ALTER TABLE addresses_streets ADD IF NOT EXISTS lon real;

ALTER TABLE addresses_houses ADD IF NOT EXISTS lat real;
ALTER TABLE addresses_houses ADD IF NOT EXISTS lon real;
