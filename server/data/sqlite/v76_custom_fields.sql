ALTER TABLE custom_fields ADD IF NOT EXISTS tab TEXT;
ALTER TABLE custom_fields ADD IF NOT EXISTS weight INTEGER DEFAULT 0;
ALTER TABLE custom_fields RENAME COLUMN magic_icon TO magic_class;
