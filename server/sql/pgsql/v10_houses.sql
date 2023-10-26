ALTER TABLE houses_flats ADD IF NOT EXISTS contract character varying;
CREATE INDEX houses_flats_contract on houses_flats(contract);
