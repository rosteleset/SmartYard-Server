ALTER TABLE notes ADD COLUMN note_type CHARACTER VARYING;
UPDATE notes SET note_type = 'checks' WHERE checks = 1;
UPDATE notes SET note_type = 'text' WHERE note_type IS NULL;
ALTER TABLE notes DROP COLUMN checks;
