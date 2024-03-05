ALTER TABLE houses_flats ADD COLUMN login text;
ALTER TABLE houses_flats ADD COLUMN password text;
CREATE UNIQUE INDEX houses_flats_login on houses_flats(login);
