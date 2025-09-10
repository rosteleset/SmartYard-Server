ALTER TABLE tt_tags DROP COLUMN background;
ALTER TABLE tt_tags DROP COLUMN foreground;
ALTER TABLE tt_tags ADD COLUMN color TEXT;

UPDATE notes SET color = SUBSTRING(color, 4) WHERE color LIKE 'bg-%';
