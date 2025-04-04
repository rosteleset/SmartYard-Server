ALTER TABLE custom_fields ADD COLUMN magic_icon TEXT;
ALTER TABLE custom_fields ADD COLUMN magic_function TEXT;

CREATE TABLE custom_fields_values
(
    custom_fields_value_id INTEGER PRIMARY KEY AUTOINCREMENT,
    apply_to INTEGER,
    value CHARACTER VARYING
);
