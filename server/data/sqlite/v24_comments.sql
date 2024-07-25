ALTER TABLE cameras ADD COLUMN comments text;
--UPDATE cameras set comments = comment;
--ALTER TABLE cameras DROP COLUMN comment;

ALTER TABLE houses_domophones ADD COLUMN comments text;
UPDATE houses_domophones set comments = comment;
ALTER TABLE houses_domophones DROP COLUMN comment;

ALTER TABLE companies ADD COLUMN comments text;
UPDATE companies set comments = comment;
ALTER TABLE companies DROP COLUMN comment;
