ALTER TABLE houses_flats ADD IF NOT EXISTS login character varying;
ALTER TABLE houses_flats ADD IF NOT EXISTS password character varying;
CREATE UNIQUE INDEX IF NOT EXISTS houses_flats_login on houses_flats(login);
