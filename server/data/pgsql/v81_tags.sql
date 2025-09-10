ALTER TABLE tt_tags DROP IF EXISTS background;
ALTER TABLE tt_tags DROP IF EXISTS foreground;
ALTER TABLE tt_tags ADD IF NOT EXISTS color CHARACTER VARYING;

UPDATE notes SET color = SUBSTRING(color, 4) WHERE color LIKE 'bg-%';
