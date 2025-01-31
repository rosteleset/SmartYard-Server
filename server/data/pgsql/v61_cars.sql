CREATE INDEX IF NOT EXISTS houses_flats_cars ON houses_flats(cars);
CREATE INDEX IF NOT EXISTS houses_flats_cars_gin ON houses_flats USING gin (cars gin_trgm_ops);
