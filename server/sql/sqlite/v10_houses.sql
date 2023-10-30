ALTER TABLE houses_flats ADD COLUMN contract text;
CREATE INDEX houses_flats_contract on houses_flats(contract);
