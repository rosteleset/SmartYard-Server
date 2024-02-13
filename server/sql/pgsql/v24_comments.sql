ALTER TABLE cameras ADD COLUMN comments character varying;
UPDATE cameras set comments = comment;
ALTER TABLE cameras DROP COLUMN comment;

ALTER TABLE houses_domophones ADD COLUMN comments character varying;
UPDATE houses_domophones set comments = comment;
ALTER TABLE houses_domophones DROP COLUMN comment;

ALTER TABLE companies ADD COLUMN comments character varying;
UPDATE companies set comments = comment;
ALTER TABLE companies DROP COLUMN comment;
