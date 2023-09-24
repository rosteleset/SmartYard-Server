ALTER TABLE houses_flats ADD COLUMN contract character varying;
CREATE INDEX houses_flats__contract on houses_flats(contract);
