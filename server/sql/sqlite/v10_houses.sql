ALTER TABLE houses_flats ADD COLUMN contract text;
CREATE INDEX houses_flats__contract on houses_flats(contract);
